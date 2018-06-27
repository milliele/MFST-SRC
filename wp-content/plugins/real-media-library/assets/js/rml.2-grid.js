/* global rmlOpts RMLisAIO */

/*
 * Change the AllInOneTree for the ajax-supported media grid view.
 * The switchFolder action hook is only called in grid mode!
 */
window.rml.hooks.register("tree/prepare/aioSettings/grid", function(settings, $) {
    /*
     * Folder switch handler => AJAX call
     */
    settings.others.onSwitchFolder = function(obj, oldID) {
        if (!RMLisAIO($(this))) { // avoid no AIO container
            return false;
        }
        
        var id = obj.attr("data-aio-id"),
            type = obj.attr("data-aio-type"),
            selectToChange; // The <select> tag for which we are changing
            
        // No action while creating a new folder
        if (id == "AIO_NEW_TO_CREATE" || oldID == "AIO_NEW_TO_CREATE") {
            return false;
        }
        
        // Set the active
        $(this).allInOneTree("active", id, true);

        // Get the <select> tag
        selectToChange = $($(this).allInOneTree("option").container.customSelectToChange);
        selectToChange.val(id.length > 0 ? id : "all").change();
        
        // Refresh if needed
        if (obj.hasClass("needs-refresh")) {
            $(this).allInOneTree("toolbarButton", "refresh");
            obj.removeClass("needs-refresh");
        }
        
        // Do a movement reinit (for the case, wordpress loads the files from the cache)
        setTimeout(function() {
            $(this).allInOneTree("reinit", "movement");
        }.bind(this), 500);
        
        /**
         * The user opens a folder in the tree. This hook is only called in grid mode!
         * 
         * @property {jQuery} obj The a-tag object in the tree
         * @property {string} id The folder id
         * @property {string} type The type of the folder
         * @event window.rml.hooks#tree/switch
         * @this allInOneTree
         */
        window.rml.hooks.call("tree/switch", [ obj, obj.attr("data-aio-id"), obj.attr("data-aio-type") ], this);
        return false;
    };
});

/*
 * Save another important hooks for the grid view in "Media Library" page
 */
window.rml.hooks.register("tree/afterInit/grid", function($) {
    var container = $(this); // Take the container from context
    
    /*
     * We are in grid mode, so if some attachments come new to
     * the grid view, make them draggable, too.
     */
    window.rml.hooks.register("grid/item/changed", function() {
        if (RMLisAIO(container)) {
            container.allInOneTree("reinit", "movement");
        }
    });
    
    /*
     * "Edit selection" button is clicked and the back to library
     * button is visible, then disable the movement.
     */
    var backbone = window.rml.library.getBackboneOfAIO(container);
    if (typeof backbone.view !== "undefined") {
        var returnToLibrary = backbone.view.$( '.media-button-backToLibrary' );
        if (returnToLibrary.length > 0) {
            container.allInOneTree("movement", false);
        }
    }
    
    /*
     * Simulate a click on the given first folder (can be active
     * from the switch list => grid view)
     */
    container.allInOneTree("active", container.allInOneTree("active"));
});

/*
 * When a grid item is deleted, check if there are associated shortcuts.
 */
window.rml.hooks.register("grid/item/removed", function(model, collection, event, $) {
    var id = model.attributes.id;
    for (var i = 0; i < collection.models.length; i++) {
        if (collection.models[i].attributes.rmlIsShortcut === id) {
            collection.remove(collection.models[i]);
            this.remove(collection.models[i]);
        }
    }
});

/*
 * Is called after each grid item in the attachments browser is rendered completely.
 */
window.rml.hooks.register("grid/item/rendered", function(el, model, $) {
    el.data("backboneModel", model);
    
    // Parse it
    var isShortcut = model.attributes.rmlIsShortcut > 0,
        icon = el.children(".attachment-preview").children(".rml-shortcut-container");
    icon.remove();
    
	if (isShortcut > 0) {
    	el.addClass("rml-shortcut rml-shortcut-grid");
    	icon = $('<div class="rml-shortcut-container"><i class="fa fa-share rml-shortcut-icon"></i></div>')
    		.appendTo(el.children(".attachment-preview"));
    	icon.tooltipster({
    	    contentAsHTML: true,
    	    content: '<div class="aio-tooltip-title">' + rmlOpts.lang.shortcut + '</div><div class="aio-tooltip-text">' + rmlOpts.lang.shortcutInfo + '</p>',
    	    delay: 300,
    	    theme: "tooltipster-aio",
    	    maxWidth: 300
    	});
	}else{
	    el.removeClass("rml-shortcut rml-shortcut-grid");
	}
});

/* E.M.L */
window.rml.hooks.register("ready/ML", function($) {
    if ($("body").hasClass("eml-grid")) {
        var mediaGrid = $("#wp-media-grid"),
            adminBar = $("#wpadminbar"),
            offsetTop = mediaGrid.offset().top,
            fnResize = function() {
                mediaGrid.css("height", $(window).height() - $("#wpadminbar").height() - 10);
            },
            fnScroll = function() {
                var scrollTop = $(window).scrollTop();
                mediaGrid[0].style.top = (scrollTop > offsetTop ? scrollTop : 0) + "px";
                //mediaGrid.css("top", scrollTop > offsetTop ? scrollTop - offsetTop : 0);
            };
    
        // Centerize container
        $(window).on("resize", fnResize);
        fnResize();
        
        // Scroll container
        $(window).on("scroll", fnScroll);
        fnScroll();
    }
});