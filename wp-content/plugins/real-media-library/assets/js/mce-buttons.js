/* global tinymce rmlOpts jQuery */

jQuery.ajax({
    url: rmlOpts.ajaxUrl,
    data: {
        action: "rml_mce_options",
        nonce: rmlOpts.nonces.treeContent
    }
}).done(function(response) {
    if (response.success) {
        jQuery.extend(rmlOpts, response.data);
    }
});

tinymce.PluginManager.add('folder_gallery', function( editor, url ) {
    var sh_tag = 'folder-gallery';
    
    //add popup
    editor.addCommand('folder_gallery_popup', function(ui, v) {
        //setup defaults
        var fid = '', link = '', columns = '3', orderby = '', size = '';
        if (v.fid) fid = v.fid;
        if (v.link) link = v.link;
        if (v.columns) columns = v.columns;
        if (v.orderby) orderby = v.orderby;
        if (v.size) size = v.size;
        
        // Prepare columns values (1-9)
        var columnsValue = [];
        for (var i = 1; i <= 9; i++) {
            columnsValue.push({ text: "" + i, value: "" + i });
        }
        
        // open the popup
        editor.windowManager.open( {
            title: rmlOpts.lang.mceButtonTooltip,
            body: [
                { // add folder select
                    type: 'listbox',
                    name: 'fid',
                    label: rmlOpts.lang.mceBodyGallery,
                    value: fid,
                    'values': rmlOpts.mce.raw,
                    tooltip: rmlOpts.lang.mceListBoxDirsTooltip
                },
                { // add link to select
                    type: 'listbox',
                    name: 'link',
                    label: rmlOpts.lang.mceBodyLinkTo,
                    value: link,
                    'values': rmlOpts.lang.mceBodyLinkToValues
                },
                { // add columns (1-9) to select
                    type: 'listbox',
                    name: 'columns',
                    label: rmlOpts.lang.mceBodyColumns,
                    value: columns,
                    'values': columnsValue
                },
                { // add random order checkbox
                    type: 'checkbox',
                    name: 'orderby',
                    label: rmlOpts.lang.mceBodyRandomOrder,
                    value: orderby
                },
                { // add size to select
                    type: 'listbox',
                    name: 'size',
                    label: rmlOpts.lang.mceBodySize,
                    value: size,
                    'values': rmlOpts.lang.mceBodySizeValues
                }
            ],
            onsubmit: function( e ) { // when the ok button is clicked
                if (e.data.fid >= -1 && e.data.fid != "") {
                    // start the shortcode tag
                    var shortcode_str = '[' + sh_tag + ' fid="'+e.data.fid+'"';
                    
                    // check link type
                    if (typeof e.data.link != 'undefined' && e.data.link.length && e.data.link != 'post')
                        shortcode_str += ' link="' + e.data.link + '"';
                    
                    // check columns size
                    if (typeof e.data.columns != 'undefined' && e.data.columns != '3')
                        shortcode_str += ' columns="' + e.data.columns + '"';
                        
                    // check orderby
                    if (typeof e.data.orderby != 'undefined' && e.data.orderby == true)
                        shortcode_str += ' orderby="rand"';
                    else if (window.rml.library.getFolderInfo(e.data.fid, "type") == "2")
                        shortcode_str += ' orderby="rml"';
                        
                    // check size
                    if (typeof e.data.size != 'undefined' && e.data.size != 'thumbnail')
                        shortcode_str += ' size="' + e.data.size + '"';
                    
                    // add panel content
                    shortcode_str += ']';
         
                    // insert shortcode to TinyMCE
                    editor.insertContent(shortcode_str);
                }
            }
        });
    });
     
    if (typeof rmlOpts === "object") {
        // add button
        editor.addButton('folder_gallery', {
            icon: 'rml-folder-gallery',
            tooltip: rmlOpts.lang.mceButtonTooltip,
            onclick: function() {
                editor.execCommand('folder_gallery_popup','', {});
            }
        });
    }
});