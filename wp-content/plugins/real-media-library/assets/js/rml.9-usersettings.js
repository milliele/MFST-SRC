/* global rmlOpts */

// See meta.js for save processing

window.rml.hooks.register("tree/prepare/aioSettings", function(aioSettings, $) {
    if (rmlOpts.hasUserSettings) {
        aioSettings.toolbarButtons.items.unshift({
            name: "settings",
            content: '<i class="fa fa-cog"></i>',
            onClick: function() {
                var container = $(this);
                
                window.rml.sweetAlert({
                    title: "",
                    text: '<div class="spinner is-active"></div><br /><br />',
                    html: true,
                    showConfirmButton: false
                });
                
                $.ajax({
                    url: rmlOpts.ajaxUrl,
                    data: {
                        action: "rml_meta_content",
                        nonce: rmlOpts.nonces.metaContent,
                        folderId: ""
                    },
                    success: function(response) {
                        /**
                         * The user opens the general settings. The user settings fields are not yet parsed to the dialog HTML.
                         * 
                         * @property {string} response The HTML response from the server
                         * @event window.rml.hooks#usersettings/loaded
                         * @this allInOneTree
                         */
                        window.rml.hooks.call("usersettings/loaded", [ response, "", rmlOpts.lang.toolbarItems.settings.toolTipTitle ], container);
                    }
                });
            },
            toolTipTitle: rmlOpts.lang.toolbarItems.settings.toolTipTitle,
            toolTipText: rmlOpts.lang.toolbarItems.settings.toolTipText
        });
    }
});