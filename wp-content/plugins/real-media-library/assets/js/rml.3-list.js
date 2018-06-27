/* global commonL10n rmlOpts */

/*
 * Change the AllInOneTree for the normal based WP List Table.
 */
window.rml.hooks.register("tree/afterInit/list", function($) {
    window.rml.library.updateRestrictions($(this).allInOneTree("active"), $(this));
});

/*
 * Warn the user when deleting files and give a hint while deleting shortcuts.
 */
window.rml.library.warnDelete = function() {
    return confirm((commonL10n.warnDelete || '') + rmlOpts.lang.warnDelete);
}

/*
 * Add the shortcut icon to shortcut items.
 */
window.rml.hooks.register("ready/ML", function($) {
    $(".rmlShortcutSpan").each(function() {
        var tr = $(this).parents("tr").addClass("rmlIsShortcut"),
            imgContainer = tr.children("td.title").find(".media-icon");
        
        var icon = $('<div class="rml-shortcut-container"><i class="fa fa-share rml-shortcut-icon"></i></div>')
    		.appendTo(imgContainer);
    	icon.tooltipster({
    	    contentAsHTML: true,
    	    content: '<div class="aio-tooltip-title">' + rmlOpts.lang.shortcut + '</div><div class="aio-tooltip-text">' + rmlOpts.lang.shortcutInfo + '</p>',
    	    delay: 300,
    	    theme: "tooltipster-aio",
    	    maxWidth: 300
    	});
    });
});