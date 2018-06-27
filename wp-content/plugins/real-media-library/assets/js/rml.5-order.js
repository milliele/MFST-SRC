/* global RMLFindDeep rmlOpts jQuery */

/*
 * Make the list table sortable but deactivate it at first startup
 */
window.rml.hooks.register("ready/ML", function($) {
    var wpTableList = $(".wp-list-table.media tbody"),
    	lastIdInView;
    if (wpTableList) {
        wpTableList.sortable({
            disabled: true,
            appendTo: "body",
            tolerance: "pointer",
            scrollSensitivity: 50,
            placeholder: "ui-sortable-helper-wp-media-list",
            scrollSpeed: 50,
            distance: 10,
            cursor: 'move',
            start: function(e, ui) {
                ui.placeholder.height(ui.helper[0].scrollHeight);
                var $ = jQuery;
                
                // The last ID (grid mode is done in the backbone collection)
                lastIdInView = $(".wp-list-table.media tbody tr:last").find('input[name="media[]"]').val();
            },
            update: function(e, ui) {
                var next = ui.item.next(), nextId, attachmentId;
                
                // The next id
                if (typeof next.html() === "undefined") {
                    nextId = false;
                }else{
                    nextId = next.find('input[name="media[]"]').val();
                }
                
                // The current id
                attachmentId = ui.item.find('input[name="media[]"]').val();
                
                /**
                 * The user wants to relocate an attachment within a folder. This hook is called before the request to the server.
                 * 
                 * @property {jQuery} folderObj The a-tag folder object
                 * @property {int} attachmentId The attachment id which is relocated
                 * @property {int} nextId The new attachment id next to attachmentId
                 * @property {int} lastId The last id of the visibile attachments (if pagination has no reached all pages yet)
                 * @property {event} event The drag event
                 * @property {object} ui The drag event ui
                 * @event window.rml.hooks#attachments/relocate
                 */
                window.rml.hooks.call("attachments/relocate", [ window.rml.library.getObjectOfMediaPickerOrActive(), attachmentId, nextId, lastIdInView, next, e, ui ]);
            }
        });
        window.rml.library.wpTableList = wpTableList;
    }
});

/*
 * Initialize the order functionality in the grid mode.
 */
window.rml.hooks.register("ready", function($) {
    if (!RMLFindDeep(window, "wp.media.view.Attachments")) {
		return;
	}
	
	var wp = window.wp;
	
	/*
	 * 
	 * @original wp.media.view.Attachments
	 */
	var Attachments = wp.media.view.Attachments;
	wp.media.view.Attachments = wp.media.view.Attachments.extend({
		/*
		 * Change the comparater that the rml_folder order is respected.
		 */
		initialize: function () {
            // call the original method
			Attachments.prototype.initialize.apply(this,arguments);
			
			// comparator creation
			var collection = this.collection,
				defaultComparator = this.collection.comparator,
				defaultComparatorAvailable = typeof defaultComparator === "function";
			this.collection.comparator = function(a, b, c) {
			    if (collection.props.get("rml_folder") !== undefined) {
			        if (typeof a.attributes.rmlGalleryOrder !== "undefined" &&
			            typeof b.attributes.rmlGalleryOrder !== "undefined") {
		                var aO = a.attributes.rmlGalleryOrder,
		                    bO = b.attributes.rmlGalleryOrder;
		                    
		                if (aO != -1 && bO != -1) {
			                // Sort it as i reveice it from the ajax query
	    			        if (aO < bO) {
	    			            return -1;
	    			        }else if (aO > bO) {
	    			            return 1;
	    			        }else{
	    			            return 0;
	    			        }
		                }
		            }
			    }
			    
			    if (defaultComparatorAvailable) {
			    	return defaultComparator.apply(this, arguments);
			    }
			    
			    // The default comparator
			    //var d = this.props.get("orderby"),
                //e = this.props.get("order") || "DESC",
                //f = a.cid,
                //g = b.cid;
                //return a = a.get(d), b = b.get(d), ("date" === d || "modified" === d) && (a = a || new Date, b = b || new Date), c && c.ties && (f = g = null), "DESC" === e ? wp.media.compare(a, b, f, g) : wp.media.compare(b, a, g, f);
			};
        },
		/*
		 * Add sortable functionality to add an AJAX request.
		 * The sortable options are the same as from media-view.js of
		 * wordpress core.
		 * 
		 * @hook attachmentOrder
		 */
	    initSortable: function() {
	    	var collection = this.collection,
	    		_Attachments = this;
			if ( wp.media.isTouchDevice || ! this.options.sortable || ! $.fn.sortable ) {
				return;
			}
	    	
	        this.options.sortable = {
	        	appendTo: 'body',
		        tolerance: "pointer",
		        scrollSensitivity: 50,
		        scrollSpeed: 50,
		        scroll: true,
		        distance: 10,
		        cursor: 'move',
		        
		        // Record the initial `index` of the dragged model.
				start: function( event, ui ) {
					ui.item.data('lastId', collection.models[collection.models.length - 1].id);
					
					// @original-source
					ui.item.data('sortableIndexStart', ui.item.index());
				},
				update: function( event, ui ) {
					var next = ui.item.next(), nextId, attachmentId = ui.item.data("id"),
						lastId = ui.item.data("lastId");
            
		            // The next id
		            if (typeof next.html() === "undefined") {
		                nextId = false;
		            }else{
		                nextId = next.data("id");
		            }
		            window.rml.hooks.call("attachments/relocate", [ window.rml.library.getObjectOfMediaPickerOrActive(), attachmentId, nextId, lastId, next, event, ui, _Attachments ]); // already documentated
					
					// @original-source
					var model = collection.at( ui.item.data('sortableIndexStart') ),
					comparator = collection.comparator;
					
					// Temporarily disable the comparator to prevent `add`
					// from re-sorting.
					delete collection.comparator;
	
					// Silently shift the model to its new index.
					collection.remove( model, {
						silent: true
					});
					collection.add( model, {
						silent: true,
						at:     ui.item.index()
					});
	
					// Restore the comparator.
					collection.comparator = comparator;
	
					// Fire the `reset` event to ensure other collections sync.
					collection.trigger( 'reset', collection );
	
					// If the collection is sorted by menu order,
					// update the menu order.
					collection.saveMenuOrder();
				}
	        };
	        
	        Attachments.prototype.initSortable.call(this);
	    },
	    /*
	     * Fully rewrite the refreshSortable functionality.
	     */
	    refreshSortable: function(rmlOrder) {
    		if ( wp.media.isTouchDevice || ! this.options.sortable || ! $.fn.sortable ) {
    			return;
    		}
    		
    		// If the `collection` has a `comparator`, disable sorting.
    		var collection = this.collection,
    			orderby = collection.props.get('orderby'),
    			enabled = rmlOrder || 'menuOrder' === orderby || ! collection.comparator;
    		
    		this.$el.sortable( 'option', 'disabled', ! enabled );
    	}
	});
});

window.rml.hooks.register("attachments/relocate", function(folderObj, attachmentId, nextId, lastId, next, event, ui) {
    var data = {
        'action': 'rml_attachment_order',
        'nonce': rmlOpts.nonces.attachmentOrder,
        'folderId': folderObj.attr("data-aio-id"),
        'attachmentId': attachmentId,
        'nextId': nextId,
        'lastId': lastId
    };
    
    ui.item.stop().fadeTo(100, 0.2);
    jQuery.post(
        rmlOpts.ajaxUrl, 
        data,
        function(response){
        	ui.item.stop().fadeTo(100, 1);
        	/**
             * The user has relocated an attachment within a folder.
             * 
             * @property {jQuery} folderObj The a-tag folder object
             * @property {object} data The data sent to the server
             * @property {object} response The response from the server
             * @event window.rml.hooks#attachments/relocated
             */
        	window.rml.hooks.call("attachments/relocated", [folderObj, data, response]);
        }
    );
});

window.rml.hooks.register("grid/request", function(options, originalOptions, jqXHR, fid, $) {
    var foundFilter = false, obj = $(".rml-fid-" + fid), contentCustomOrder;
    
    // Valid folder
    if (fid < 0 || !obj || obj.length < 0 || (contentCustomOrder = obj.attr("data-content-custom-order")) !== "1") {
    	return;
    }
    
    // check monthnum und year
    // post_mime_type = "all"; monthnum year
    try {
        if (originalOptions.data.query.post_mime_type
            || originalOptions.data.query.monthnum
            || originalOptions.data.query.year) {
                if (originalOptions.data.query.post_mime_type != "all"
                    || originalOptions.data.query.monthnum > 0
                    || originalOptions.data.query.year > 0) {
                    foundFilter = true;
                }
            }
    }catch (e){}
    
    if (foundFilter) {
        $("body").addClass("rml-view-gallery-filter-on");
    }else{
        $("body").removeClass("rml-view-gallery-filter-on");
    }
    
    originalOptions.data.query.order = "ASC";
    originalOptions.data.query.orderby = "rml";
    
    var swallow = $.param(originalOptions.data);
    options.data = swallow;
});

/*
 * The metadata order by should apply the selected option.
 */
window.rml.hooks.register("action/rml_attachment_order_by", function(post, $) {
	post.orderby = $(this).parent().children("select").val();
});

/*
 * If the order is resetted in the metadata, then reload the attachments browser or table list view.
 */
window.rml.hooks.register("action/done/rml_attachment_order_reset action/done/rml_attachment_order_by action/done/rml_attachment_order_by_last_custom", function(options, response, $) {
	var fid = options.method, action = options.action;

	// Update the trees
	if (action !== "rml_attachment_order_by_last_custom") {
		$(".rml-fid-" + fid).each(function() {
			$(this).attr("data-content-custom-order", action === "rml_attachment_order_by" ? "1" : "0");
		});
	}
	
	// Reload the grids / table list
	$(".aio-tree-instance.rml-container").each(function() {
		$(this).allInOneTree("toolbarButton", "refresh");
	});
});