/* global jQuery rmlOpts */

window.rml.hooks.register("ready/ML", function($) {
    $(document).on("click", "form.rml-meta nav li", function() {
        var form = $(this).parents("form"), key = $(this).attr("data-key");
        $(this).parent().children().removeClass("nav-tab-active");
        $(this).addClass("nav-tab-active");
        form.find("table:visible").hide();
        form.find('table[data-key="' + key + '"]').show();
        
        /**
         * The user switched to another tab view in the user settings / folder meta dialog.
         * 
         * @property {object} key The tab key
         * @property {jQuery} li The li element
         * @event window.rml.hooks#metadata/tab
         * @this jQuery form element
         */
        window.rml.hooks.call("metadata/tab", [key, $(this)], form);
    });
});

/*
 * Create handler for failed changes. Show the error messages
 * at the top of the meta box.
 */
window.rml.hooks.register("metadata/save/failed usersettings/save/failed", function(response, fields, sweet, name, $) {
    window.rml.sweetAlert.enableButtons();
    
    var liHTML = "<li>" + response.data.errors.join("</li><li>") + "</li>";
    jQuery(".rml-meta-errors").html(liHTML).show();
    window.rml.library.sweetAlertPosition();
});

/*
 * Create handler for successful changes. Close the
 * dialog.
 * 
 * It also handles the rename process.
 */
window.rml.hooks.register("metadata/save/success usersettings/save/success", function(response, fields, sweet, name, $) {
    window.rml.sweetAlert.close();
    
    // Rename the folder object
    if (response.data.newSlug) {
        var folderId = fields.folderId,
            slug = response.data.newSlug,
            newName = fields.name;
        window.rml.hooks.call("folder/renamed", [ folderId, newName, slug ], this); // already documentated
    }
});

/*
 * Create sweet alert with this folder meta.
 */
window.rml.hooks.register("metadata/loaded usersettings/loaded", function(response, fid, name, $) {
    var that = this;
    
    // Show the custom fields dialog!
    window.rml.sweetAlert({
        title: $("<div/>").text(name).html(),
        text: response,
        html: true,
        confirmButtonText: rmlOpts.lang.save,
        cancelButtonText: rmlOpts.lang.close,
        closeOnConfirm: false,
        showLoaderOnConfirm: true,
        showCancelButton: true
    }, function() {
        var sweet = this;
        jQuery(".rml-meta-errors").hide();
        window.rml.library.sweetAlertPosition();
        
        // Serialize the meta data form
        var data = jQuery("form.rml-meta").serializeArray();
        var fields = { };
        jQuery.each( data, function( key, value ) {
            fields[value.name] = value.value;
        });
        
        fields.action = "rml_meta_save";
        fields.nonce = rmlOpts.nonces.metaSave;
        
        // Post it!
        jQuery.ajax({
            url: rmlOpts.ajaxUrl,
            type: 'POST',
            data: fields,
            success: function(response) {
                if (fields.folderId === "all") {
                    /**
                     * The user has saved the metadata in the general user settings. $status can be replaced with
                     * "success" or "failed".
                     * 
                     * @property {object} response The response from the server after saved
                     * @property {object} fields The POST query
                     * @property {jQuery} sweet The dialog
                     * @event window.rml.hooks#usersettings/save/$status
                     * @this allInOneTree
                     */
                    window.rml.hooks.call("usersettings/save/" + (response.success ? "success" : "failed"), [ response, fields, sweet, "" ], that);
                }else{
                    /**
                     * The user has saved the metadata in the folder details. $status can be replaced with
                     * "success" or "failed".
                     * 
                     * @property {object} response The response from the server after saved
                     * @property {object} fields The POST query
                     * @property {jQuery} sweet The dialog
                     * @property {string} name The name of the folder
                     * @event window.rml.hooks#metadata/save/$status
                     * @this allInOneTree
                     */
                    window.rml.hooks.call("metadata/save/" + (response.success ? "success" : "failed"), [ response, fields, sweet, name ], that);
                }
            }
        });
    });
    
    // Do not call metadata/dialog when it is general user settings
    if (!fid) {
        return;
    }
    
    setTimeout(function() {
        /**
         * The folder details dialog is parsed.
         * 
         * @property {string} response The HTML response from the server
         * @property {string} id The folder id
         * @property {string} name The folder name
         * @event window.rml.hooks#metadata/dialog
         * @this allInOneTree
         */
        window.rml.hooks.call("metadata/dialog", [ response, fid, name ], that);
    }.bind(this), 500);
});

/*
 * ========================================================
 * 
 *          Use the media picker in the cover image.
 * 
 * ========================================================
 */
window.rml.hooks.register("metadata/dialog", function(response, fid, name, $) {
    // Check the filter on in the media gallery
    var hasFilter = $("body").hasClass("rml-view-gallery-filter-on");
    
    var picker = $(".rml-meta-media-picker");
    if (picker.length <= 0) {
        return;
    }
    
    picker.wpMediaPicker({
        label_add: rmlOpts.lang.metadata.coverImage.label_add,
        label_replace: rmlOpts.lang.metadata.coverImage.label_replace,
        label_remove: rmlOpts.lang.metadata.coverImage.label_remove,
        label_modal: rmlOpts.lang.metadata.coverImage.label_modal,
        label_button: rmlOpts.lang.metadata.coverImage.label_button,
        query: {
            post_mime_type: 'image'
        },
        onShow: function() {
            $(".sweet-overlay,.sweet-alert").fadeOut();
        },
        onClose: function() {
            // Remove RML container
            var modal = this.wpWpMediaPicker.workflow.$el.parents(".rml-media-modal").removeClass("rml-media-modal");
            modal.find(".rml-container").remove();
            modal.find(".attachments-browser").data("initialized", false);
            
            $(".sweet-overlay,.sweet-alert").fadeIn();
            // Fix filter
            if (!hasFilter) {
                $("body").removeClass("rml-view-gallery-filter-on");
            }
        },
        htmlChange: function() {
            setTimeout(function() {
                picker.parents("td").find(".spinner").remove();
                $(".rml-meta-media-picker").parents("fieldset").show();
                window.rml.library.sweetAlertPosition();
            }.bind(this), 500);
        }
    });
});

/*
 * Add action button handler.
 */
window.rml.hooks.register("ready", function($) {
    // Wipe data and action buttons
    $(document).on("click", ".rml-button-wipe, .sweet-alert a.actionbutton", function(e) {
        if (window.confirm(rmlOpts.lang.wipe)) {
            var button = $(this), method = button.attr("data-method"), action = $(this).attr("data-action"), id = $(this).attr("id");
            button.html('<div class="spinner is-active" style="float: initial;margin: 0;"></div>').prop("disabled", true);
            button.attr("disabled", "disabled"); // for <a>-tags
            
            var post = {
                action: action,
                nonce: rmlOpts.nonces[$(this).attr("data-nonce-key")],
                method: method
            };
            
            /**
             * Action buttons are buttons in the folder details or in the RML option page.
             * With this hook you can modify the post data for a specific action. $action can
             * be replaced with the data-action attribute of the button.
             * 
             * @property {object} post The post data
             * @event window.rml.hooks#action/$action
             * @this jQuery
             */
            window.rml.hooks.call("action/" + action, [ post ], button);
            
            jQuery.ajax({
                url: rmlOpts.ajaxUrl,
                data: post,
                method: button.attr("data-http-method") || "GET",
                success: function(response) {
                    /**
                     * Action button is pressed and the response of server is available. $action can
                     * be replaced with the data-action attribute of the button.
                     * 
                     * @property {object} post The post data
                     * @property {object} response The response of the server
                     * @event window.rml.hooks#action/done/$action
                     * @this jQuery
                     */
                    window.rml.hooks.call("action/done/" + action, [ post, response ], button);
                    button.html("<i class=\"fa fa-check\"></i> " + (response.success ? rmlOpts.lang.done : rmlOpts.lang.failed)).attr("disabled", false);
                }
            });
        }
        e.preventDefault();
        return false;
    });
});

/*
 * Export handler
 */
window.rml.hooks.register("action/done/rml_export", function(post, response, $) {
    $("#rml_export_data textarea").get(0).value = response.data.str;
});

/*
 * Import handler
 */
window.rml.hooks.register("action/rml_import", function(post, $) {
    post.import = encodeURIComponent($("#rml_import_data textarea").get(0).value);
});