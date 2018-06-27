/*
 * This file is only called in the media options.
 */
 
/* global wp jQuery */

window.rml.hooks.register("ready/noMediaLibrary", function(e, args) {
    var $ = jQuery;
    
    // append to known option
    var container = $('<div class="rml-options"><nav><ul></ul></nav></div>')
                        .insertBefore($('[for="rml_hide_upload_preview"]').parents("table").prev())
                        .removeClass("rml-dummy");
    var headline = $('<h2>Real Media Library</h2>').insertBefore(container);
    var nav = container.find("nav ul");
    var navLiCnt = 0;
    $('<a target="_blank" href="https://matthias-web.com/wordpress/real-media-library/add-ons/"><strong>Browse Add-Ons</strong></a>').insertAfter(headline);
                        
    // Search the option panels
    $("table.form-table").each(function() {
        var oHeadline = $(this).prev(),
            sHeadline = oHeadline.html();
        if (typeof sHeadline !== "undefined" && sHeadline.indexOf("RealMediaLibrary") === 0) {
            sHeadline = sHeadline.split(":", 2)[1];
            
            // Append headline to options panel
            var li = $("<li class=\"nav-tab " + ((navLiCnt === 0) ? "nav-tab-active" : "") + "\">" + sHeadline + "</li>").appendTo(nav);
            var section = $(this).appendTo(container);
            
            if (navLiCnt === 0) {
                section.show();
            }
            
            li.click(function() {
                container.children("table").hide();
                nav.find(".nav-tab-active").removeClass("nav-tab-active");
                $(this).addClass("nav-tab-active");
                section.show(); 
            });
            
            oHeadline.remove();
            navLiCnt++;
        }
    });
});