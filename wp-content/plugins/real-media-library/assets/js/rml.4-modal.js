/* global jQuery */

/*
 * Initialize a new tree for the media modal.
 */
window.rml.hooks.register("newModal", function(filter, $) {
    var menu = $(this).find(".media-menu"),
        container,
        containerID = $(this).parent().attr("id"),
        customSelectToChange = "#" + containerID + " .attachment-filters-rml",
        isAlreadyLoaded = !!menu.data("rml");
        
    if (!isAlreadyLoaded) {
        menu.append('<div class="separator"></div>');
        menu.data("rml", true);
    }else{
        menu.find(".aio-tree").remove();
    }
    
    $(this).addClass("rml-media-modal");
    $(this).find(".media-frame.hide-menu").removeClass("hide-menu"); // Never hide the medie menu frame
    
    // If it is the "Edit gallery" modal then create no tree
    var mediaButtonReverse = $(this).find(".media-button-reverse");
    if (mediaButtonReverse.is(":visible")) {
        return;
    }
    
    // Add tree container to left menu
    container = $(".rml-container.rml-dummy").clone().appendTo(menu);
    container.removeClass("rml-dummy").addClass("rml-no-dummy");
    
    /**
     * A tree container is placed to the DOM in a modal view ("Insert media"-dialog).
     * But the tree is not initialized yet through the {@link allInOneTree} plugin.
     * The context passed to the hook functions is the media modal window object (.media-modal).
     * 
     * @property {jQuery} container The container
     * @property {boolean} isAlreadyLoaded The modal window gets re-opened
     * @property {string} customSelectToChange The query selector for the backbone select dropdown of the RML filter
     * @event window.rml.hooks#tree/prepare/modal
     * @this jQuery
     */
    window.rml.hooks.call("tree/prepare/modal", [ container, isAlreadyLoaded, customSelectToChange ], this);
    
    // Add modal library relevant options
    var aioSettings = $.extend(true, {}, window.rml.defaultAioSettings, {
        container: {
            isListMode: false,
            isModalMode: [ menu, $(this) ],
            listMode: "grid",
            customSelectToChange: customSelectToChange,
            isSticky: false,
            isResizable: false,
            isWordpressModal: true,
            theme: "wordpress wordpress-fixed"
        },
        movement: {
            selector: "#" + containerID + " ul.attachments > li"
        }
    });
    
    // Apply filters to the allInOneTree
    window.rml.hooks.call("tree/prepare/aioSettings", aioSettings, container); // already documentated
    window.rml.hooks.call("tree/prepare/aioSettings/grid", aioSettings, container); // already documentated
    
    /**
     * The tree is not initialized yet through the {@link allInOneTree} plugin. Here
     * you can modify the options for the jQuery plugin. This hook is explicit called
     * in a modal window ("Insert media"-dialog).
     * 
     * @property {object} aioSettings The settings for the jQuery plugin
     * @event window.rml.hooks#tree/prepare/aioSettings/modal
     * @this jQuery
     */
    window.rml.hooks.call("tree/prepare/aioSettings/modal", aioSettings, container);
    
    // Reset and create the tree
    container.allInOneTree(aioSettings);
});

/*
 * Set an interval, which searches for new modal selects.
 * 
 * @hook newModal
 * @see window.rml.library::initializeToolbar
 */
window.rml.hooks.register("general", function($) {
    setInterval(function() {
        // Search new modals with attachments browser
        $(".media-modal .attachments-browser").each(function() {
            var ul = $(this).children("ul.attachments");
            if ($(this).is(":visible") && !$(this).data("initialized") && ul.is(":visible")) {
                $(this).data("initialized", true);
                window.rml.hooks.call("newModal", $(this), $(this).parents(".media-modal")); // Private hook
            }
        });
        
        // Search for rml shortcut info containers
        $(".rml-shortcut-info:visible:not(.rml-shortcut-info-init)").each(function() {
            window.rml.library.showShortcutInfo($(this));
        });
    }, 500);
});

/*
 * When we are in a modal window and change the tabs, please reinit the active
 * folder to the current selected <select> value.
 */
window.rml.hooks.register("tree/afterInit/modal", function() {
    var $ = jQuery, container = $(this),
        backbone = window.rml.library.getBackboneOfAIO(container);
    if (backbone.view) {
        var select = backbone.view.$(".attachment-filters-rml-loaded");
        if (select.length > 0) {
            var selected = select.val();
            if (selected !== "all") {
                container.allInOneTree("active", selected, true);
            }
        }
    }
});