"use strict";

/*global mOxie RMLFormat wp rmlOpts jQuery Image uploader prepareMediaItemInit */

window.rml.uploader = {
    uploading: {},

    /*
     * Get the rml folder id from a dropdown is something is visible.
     * 
     * @return opts object or undefined
     */
    idFromSelect: function() {
        var fromSelect = jQuery(".attachments-filter-upload-chooser-fromSelect:visible"), activeOption;
        if (fromSelect.length > 0) {
            fromSelect = fromSelect.next().children("select"),
            activeOption = fromSelect.find('option[data-id="' + fromSelect.val() + '"]');
            if (activeOption.length > 0) {
                return {
                    slug: activeOption.attr("data-slug"),
                    id: parseInt(fromSelect.val()),
                    deny: ''
                };
            }
        }
        return undefined;
    },
    
    /*
     * Bind actions to the uploading process.
     */
    init: function() {
        var $ = jQuery;
        if (typeof window.wp !== 'undefined' && typeof wp.Uploader === 'function') {
            $.extend( wp.Uploader.prototype, {
                init : function() { // plupload 'PostInit'
                    /**
                     * The uploader is initialized.
                     * 
                     * @event window.rml.hooks#uploader/init
                     * @this wp.Uploader
                     */
                    window.rml.hooks.call("uploader/init", [ ], this);
                    
                    this.uploader.bind('FileFiltered', function( up, file ) {
                        // Here we can check, if a custom select for the upload exists and use this one instead
                        file.rmlFolderOpts = window.rml.uploader.idFromSelect();
                    });
                
                    this.uploader.bind('FilesAdded', function( up, files ) {
            			for ( var i = 0 ; i < files.length ; i++ ) {
            			    // Get current folder options
            			    var active = window.rml.library.getObjectOfMediaPickerOrActive(),
            		            opts = window.rml.uploader.settings(active, files[i], opts);
            		        window.rml.uploader.uploading[files[i].attachment.cid] = opts;
            		        
            		        files[i].rmlFolder = opts.id;

            			    // Call the frontend view (left sidebar)
            			    window.rml.uploader.prepareUpload(files[i], active, opts);
            			    
            			    /**
                             * The uploader is initialized.
                             * 
                             * @param {object} uploader The uploader
                             * @param {object} file The file
                             * @param {object} active The active folder tree object (a-tag)
                             * @param {object} opts The opts for the uploader (can be modified by a hook)
                             * @event window.rml.hooks#uploader/FilesAddedSingle
                             * @this wp.Uploader
                             */
            			    window.rml.hooks.call("uploader/FilesAddedSingle", [ up, files[i], active, opts ], this);
            			}
            		});
            		
            		this.uploader.bind('BeforeUpload', function(uploader, file) {
            		    /**
                         * Before a file gets uploaded.
                         * 
                         * @param {object} uploader The uploader
                         * @param {object} file The file
                         * @event window.rml.hooks#uploader/BeforeUpload
                         * @this wp.Uploader
                         */
            		    window.rml.hooks.call("uploader/BeforeUpload", [ uploader, file ], this);
            		    
            		    // Set server-side-readable rmlfolder id
            		    var params = uploader.settings.multipart_params;
                        params.rmlFolder = file.rmlFolder;
                        
                        if (!params.rmlFolder) {
                            var active = window.rml.library.getObjectOfMediaPickerOrActive(),
            		            opts = window.rml.uploader.settings(active, file, opts);
            		        params.rmlFolder = opts.id;
                        }
            		});
            		
            		// The upload progress on left sidebar
            		this.uploader.bind('UploadProgress', function(up, file) {
            		    /**
                         * A files is currently uploading.
                         * 
                         * @param {object} uploader The uploader
                         * @param {object} file The file
                         * @event window.rml.hooks#uploader/UploadProgress
                         * @this wp.Uploader
                         */
            		    window.rml.hooks.call("uploader/UploadProgress", [ up, file ], this);
            		    window.rml.uploader.progress(up, file);
            		});
            		
            		// Reset the upload queue
            		this.uploader.bind('UploadComplete', function(up, files) {
            		    up.splice();
            		    up.total.reset();
            		    
            		    // Refresh the count of the container
            		    try {
                		    window.rml.library.refreshAllCounts();
            		    }catch(e) {}
            		});
                },
                success : function( file_attachment ) { // plupload 'FileUploaded'
                    /**
                     * A file is successfully uploaded.
                     * 
                     * @param {object} attachment The file attachment
                     * @event window.rml.hooks#uploader/success
                     * @this wp.Uploader
                     */
                    window.rml.hooks.call("upload/success", [ file_attachment ], this);
                    window.rml.uploader.success(file_attachment);
                }
            });
        }
    },
    
    /*
     * Success handler for uploaded files. Move the file to
     * the specific folder.
     */
    success: function(fileObj) {
        var $ = jQuery;
        var item = $('.rml-uploading-' + fileObj.cid);
        if (item.length > 0) {
            var data = {
                    'action': 'bulk_move',
                    'ids' : [fileObj.id],
                    'to' : parseInt(item.attr("data-id"))
                }, container = item.parents('.rml-container.rml-no-dummy'),
                activeId = container.allInOneTree("active").attr("data-aio-id"),
                rmlGalleryOrder = fileObj.get('rmlGalleryOrder'),
                atIdx = rmlGalleryOrder === -1 ? 0 : rmlGalleryOrder,
                backbone = window.rml.library.getBackboneOfAIO(container);
            
            // Add to collection
            if (activeId === item.attr("data-id") && backbone.view) {
                backbone.view.collection.add(fileObj, { at: atIdx });
            }
            
            // Styling
            item.find(".percent").css("width", "100%");
            item.find(".read_percent").html("100%");
            item.remove();
            
            window.rml.hooks.call("upload/success/moved", [ fileObj, data ], this);
            
            // Check if last
            if (jQuery(".rml-uploading").find(".rml-uploading-item").length <= 0) {
                container.allInOneTree("toolbarButton", "refresh");
                $(".rml-uploading").stop().slideUp();
                window.rml.hooks.call("upload/success/movedAll", [ ], this);
            }
        }
    },
    
    /*
     * Handles a progress for a uploading item
     */
    progress: function(up, file) {
        var $ = jQuery;
        
        file.percent -= 1;
        if (file.percent < 0) {
            file.percent = 0;
        }
        
        var item = $('.rml-uploading-' + file.id),
            percent =  file.percent + "%";
        
        if (item.length > 0) {
            item.find(".percent").css("width", percent);
            item.find(".read_percent").html(percent);
            
            if (file.percent > 98) {
                item.find(".percent").addClass("finish");
            }
        }
    },
    
    /*
     * Get the settings (slug, id, deny) when uploading to a current folder.
     * 
     * @param active Active folder object
     * @param file plupload file object
     * @return object
     */
    settings: function(active, file) {
        if (typeof file.rmlFolderOpts === "object") {
            return file.rmlFolderOpts; // @see FileFilteres event above
        }
        
        var $ = jQuery, opts = {
                slug: null,
                name: "",
                id: null,
                deny: ''
            };
        
        // Get active folder from attachments browser
        if (active.length > 0) {
            opts.slug = active.attr("data-slug");
            if (typeof opts.slug === "undefined") {
                opts.slug = "/";
            }
            opts.id = parseInt(active.attr("data-aio-id"));
    
            // Check folder type and automatically redirect it to root folder.
            if (active.attr("data-restrictions").indexOf("ins") > -1) {
                // Wrong permission
                opts.slug = "/";
                opts.id = rmlOpts.root;
                opts.deny = '<div class="warnings">\
                    <span class="deny-gallery">' + rmlOpts.lang.toolbarItems.restrictions.ins + '</span>\
                </div>';
            }else{
                window.rml.hooks.call("uploadCheck", [ opts, active.attr("data-aio-type"), file ], active);
            }
        }else{
            opts.slug = "/";
            
            // Get the id
            var select = $(".attachment-filters.attachment-filters-rml");
            if (select.length > 0 && select.val() != "all") {
                opts.id = parseInt(select.val());
            }else{
                opts.id = rmlOpts.root;
            }
        }
        
        // Fix "All files"
        opts.id = isNaN(opts.id) ? "" : opts.id;
        return opts;
    },
    
    /*
     * Create a uploading file row with preview image
     */
    prepareUpload: function(file, active, opts) {
        var $ = jQuery;
        
        // Create row item
        var item = $( '<div class="rml-uploading-item rml-uploading-' + file.attachment.cid + ' rml-uploading-' + file.id + '""\
                    data-slug="' + opts.slug + '" \
                    data-id="' + opts.id + '"\>\
                <div class="left"></div>\
                <div class="right">\
                    <div class="filename">' + file.name + '</div>\
                    <div class="folder">\
                        <i class="fa fa-folder-o"></i> ' + opts.slug + '<br />\
                        ' + window.rml.uploader.humanFileSize(file.size, true) + ' - <span class="read_percent">0%</span>\
                    </div>\
                    <div class="bar">\
                        <div class="percent"></div>\
                    </div>\
                    ' + opts.deny + '\
                </div>\
                <div class="rml-fix"></div>\
            </div>' ).appendTo( '.rml-uploading-list' );
        
        $(".rml-uploading").show();
    	var image = $( new Image() ).appendTo( item.find(".left") );
    	
    	if ($(".rml-hide-upload-preview-1").length == 0) {
        	var preloader = new mOxie.Image();
        	preloader.onload = function() {
        	    preloader.downsize( 48, 48 );
        	    image.prop( "src", preloader.getAsDataURL() );
        	};
        	preloader.load( file.getSource() );
    	}
    },
    
    // @link http://stackoverflow.com/questions/10420352/converting-file-size-in-bytes-to-human-readable
    humanFileSize: function(bytes, si) {
        var thresh = si ? 1000 : 1024;
        if(Math.abs(bytes) < thresh) {
            return bytes + ' B';
        }
        var units = si
            ? ['kB','MB','GB','TB','PB','EB','ZB','YB']
            : ['KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];
        var u = -1;
        do {
            bytes /= thresh;
            ++u;
        } while(Math.abs(bytes) >= thresh && u < units.length - 1);
        return bytes.toFixed(1)+' '+units[u];
    },
    
    secondsFormat: function(totalSec) {
        var hours   = Math.floor(totalSec / 3600);
        var minutes = Math.floor((totalSec - (hours * 3600)) / 60);
        var seconds = totalSec - (hours * 3600) - (minutes * 60);
        
        var result = (hours < 10 ? "0" + hours : hours) + ":" + (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds  < 10 ? "0" + seconds : seconds);
        return result;
    }
};

/*
 * Update the reamin info for the total upload progress:
 * - bytes per seconds
 * - loaded / total size
 * - remain time
 * 
 * @hooks uploader/UploadProgress
 */
window.rml.hooks.register("uploader/UploadProgress", function(uploader, file) {
    jQuery(".rml-uploading-details-remain-bytes strong")
        .html(window.rml.uploader.humanFileSize(uploader.total.bytesPerSec, true));
        
    jQuery(".rml-uploading-details-remain-loaded strong")
        .html(window.rml.uploader.humanFileSize(uploader.total.loaded, true));
    jQuery(".rml-uploading-details-remain-loaded span")
        .html(window.rml.uploader.humanFileSize(uploader.total.size, true));
    
    var remainTime = Math.floor((uploader.total.size - uploader.total.loaded) / uploader.total.bytesPerSec),
        queued = uploader.total.queued,
        labelQueued = queued > 2 ? RMLFormat(rmlOpts.lang.uploadRemainFiles, queued - 2) : "",
        queuedFiles = jQuery(".rml-uploading-queue");
    if (labelQueued) {
        queuedFiles.html(labelQueued).show();
    }else{
        queuedFiles.hide().html("");
    }
    jQuery(".rml-uploading-details-remain-time strong").html(window.rml.uploader.secondsFormat(remainTime));
});

/*
 * For Media > "Add new"
 * Set an interval, which searches for new upload sections where a folder can be selected.
 */
window.rml.hooks.register("general", function($) {
    setInterval(function() {
        // Search new modals with attachments browser
        $(".attachments-filter-upload-chooser").each(function() {
            var isInitialized = $(this).data("initialized"), label = $(this), select = $(this).next().children("select");
            
            if (!isInitialized) {
                $(this).data("initialized", true);
                
                // Check, if there is a tree on left side, so we do not need any select
                if (label.parents(".attachments-browser").length > 0) {
                    label.html(rmlOpts.lang.uploaderUsesLeftTree).show();
                }else{
                    label.addClass("attachments-filter-upload-chooser-fromSelect")
                    if (label.parents(".uploader-inline-content").length > 0) {
                        // We are in a normal uploader, so fill the select
                        label.show();
                        label.next().show();
                        $.post(
                	        rmlOpts.ajaxUrl, 
                	        {
                	            'action': 'rml_options_default',
                	            'nonce' : rmlOpts.nonces.treeContent
                	        }
                	    ).done(function(response) {
                	        if (response.success) {
                	            select.html(response.data);
                	        }
                	    });
                    }
                }
            }
        });
    }, 500);
});

/*
 * For Media > "Add new"
 * 
 * Adds the property to the asyn-upload.php file and modifies the output row while
 * uploading a new file. 
 * 
 * @see wp-includes/js/plupload/handlers.js
 */
window.rml.hooks.register("ready", function($) {
    if (!$("body").hasClass("media-new-php")) {
        return;
    }
    
    /*
     * When the file is uploaded, then the original filename is overwritten. Now we
     * must add it again to the row after the filename.
     */
    if (prepareMediaItemInit) {
        var copyPrepareMediaItemInit = prepareMediaItemInit;
        prepareMediaItemInit = function(file) {
            copyPrepareMediaItemInit.apply(this, arguments);
            if (file.rmlFolderHTML) {
                var mediaRowFilename = $('#media-item-' + file.id).find(".filename");
                if (mediaRowFilename.length > 0) {
                    mediaRowFilename.after(file.rmlFolderHTML);
                }
            }
        }
    }
    
    setTimeout(function() {
        if (uploader) {
    		// Add event to the uploader so the parameter for the folder id is sent
    		uploader.bind('BeforeUpload', function(uploader, file) {
    		    // Set server-side-readable rmlfolder id
    		    var params = uploader.settings.multipart_params, slugLabel = "/";
                params.rmlFolder = file.rmlFolder;
                
                if (!params.rmlFolder) {
    		        var opts = window.rml.uploader.idFromSelect();
    		        params.rmlFolder = opts ? opts.id : -1;
    		        slugLabel = opts ? opts.slug.toUpperCase() : slugLabel;
                }
                
                var mediaRowFilename = $('#media-item-' + file.id).find(".filename");
                if (mediaRowFilename.length > 0) {
                    file.rmlFolderHTML = '<div class="media-item-rml-folder">' + slugLabel + '</div>';
                    mediaRowFilename.after(file.rmlFolderHTML);
                }
    		});
        }
    }.bind(this), 500);
});

/*
 * Checks, if the uploading folder is a collection or gallery and restrict the upload,
 * move the file to unorganized folder.
 */
window.rml.hooks.register("uploadCheck", function(opts, type, file, $) {
    if (type == "1") {
        opts.slug = "/";
        opts.id = rmlOpts.root;
        opts.deny = '<div class="warnings">\
            <span class="deny-collection">' + rmlOpts.lang.uploadingCollection + '</span>\
        </div>';
    }else if (type == "2") {
        // May only contain image files
        var allowedExts = [ 'jpg', 'jpeg', 'jpe', 'gif', 'png' ],
            ext = file.name.substr(file.name.lastIndexOf('.') + 1).toLowerCase();
        if ($.inArray(ext, allowedExts) <= -1) {
            opts.slug = "/";
            opts.id = rmlOpts.root;
            opts.deny = '<div class="warnings">\
                <span class="deny-gallery">' + rmlOpts.lang.uploadingGallery + '</span>\
            </div>';
        }
    }
});