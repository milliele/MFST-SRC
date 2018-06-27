/*!
 * jQuery AIO All In One Tree
 *
 * @license
 * Copyright MatthiasWeb (Matthias Günter)
 * https://matthias-web.com
 * 
 * Version 1.3.12
 * - Fixed bug with search bar
 *
 * Version 1.3.11
 * - Fixed bug after creating a new post the nodes are not clickable
 *
 * Version 1.3.10
 * - Fixed bug with touch devices
 *
 * Version 1.3.9
 * - Added expand/collapse sidebar functionality
 * - Removed onResizeInit option
 * - Improved the save of localStorage items within one row per AIO instance
 * - Fixed bug with htmlentities of PHP string
 * - Fixed bug with ESC key in rename mode
 * - Fixed bug with creating a new folder and switch back to previous
 *
 * Version 1.3.8
 * - Added option setAsActive to nodesHTML method
 * - Removed JSDoc comments prefix
 *
 * Version 1.3.7
 * - Added .toolbarButton("cancel") to cancel active toolbar button
 * - Fixed bug with disabled browser localStorage
 * - Fixed bug with multiple All In One tree's
 * - Fixed bug with instant movement when rearrange mode is with "Save" button
 *
 * Version 1.3.6
 * - Added others.onTypeButtonClicked option
 *
 * Version 1.3.5
 * - Added ESC to close the rename folder action
 * - Added F2 handler to rename a folder
 * - Added double click event to open folder
 * - Added search input field for folders
 * - Improved rearrange mode
 *
 * Version 1.3.4
 * - Fixed bug with multiple aio-tree's with same ID
 *
 * Version 1.3.3
 * - Improved the way of rearrange mode, the folders gets expand after 700ms of hover
 * 
 * Version 1.3.2
 * - Added option for create types and toolbar items to define a custom function callback to enable the button
 * 
 * Version 1.3.1
 * - Fixed bug with IE8
 * 
 * Version 1.3
 * - Added lazy loading class "aio-lazy". Use onAfterFinish function to resolve the loader and aio lazy class (see CSS)
 * - Added option/callback so the rearrange can work immediatly after relocate an item
 * 
 * Version 1.2
 * - Added a return value for the $.loader() function to retrieve the current state (boolean)
 * 
 * Version 1.1
 * - Added rootParentId option (Default: -1)
 * 
 * Version 1.0
 * - Initial release
 */
 
"use strict";
/* global jQuery */

(function($) {
    /* Function.prototype.bind polyfill */
    Function.prototype.bind=(function(){}).bind||function(b){if(typeof this!=="function"){throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");}function c(){}var a=[].slice,f=a.call(arguments,1),e=this,d=function(){return e.apply(this instanceof c?this:b||window,f.concat(a.call(arguments)));};c.prototype=this.prototype;d.prototype=new c();return d;};
    
    /* Array.prototype.indexOf polyfill */
    Array.prototype.indexOf||(Array.prototype.indexOf=function(a,b){for(var c=b||0,d=this.length;c<d;c++)if(this[c]===a)return c;return-1});
    
    /*
     * Extend the nested sortable so there exists an event to expand on hover.
     */
    if ($.mjs && $.mjs.nestedSortable) {
        $.widget("mguenter.nestedSortable", $.extend({}, $.mjs.nestedSortable.prototype, {
            _mouseDrag: function(event) {
                $.mjs.nestedSortable.prototype._mouseDrag.apply(this, arguments);
                
                if ($(event.srcElement).parents(".aio-nodes").length > 0) {
                    var left = event.clientX, top = event.clientY;
                    
                    var item, itemElement, i, minLeft, minTop, maxLeft, maxTop, foundItem = false;
        			for (i = this.items.length - 1; i >= 0; i--) {
        				item = this.items[i];
        				minLeft = item.left;
        				minTop = item.top;
        				maxLeft = minLeft + item.width;
        				maxTop = minTop + item.height;
        				
        				if (left >= minLeft && left <= maxLeft && top >= minTop && top <= maxTop && !this.currentContainer.currentItem.is(item.item)) {
        				    event.hoverListElement = item.item;
        				    this._trigger("hoverListElement", event, this._uiHash());
        				    foundItem = true;
        				}
        			}
        			
        			// Trigger no hover element
        			if (!foundItem) {
        			    this._trigger("noHoverListElement", event, this._uiHash());
        			}
                }
            }
        }));
    }
    
    // IE Disable links
    $(document).on("click focus", ".aio-tree [disabled=\"disabled\"]", function(event) {
        event.preventDefault();
        $(document.activeElement).blur();
        return false;
    });
    
    /*
     * Handler for the collapsable / expandable folder
     * structure. It adds the [+] / [-] buttons to the
     * folders - and also the handlers.
     * 
     * It also handles the switchFolder event.
     * @see TypeHandler::_doSwitchFolder
     */
    var ContainerHandler = function(e) {
        /*
         * Parsed Opposite object (jQuery)
         */
        this._usedOpposite = null;
        
        /*
         * @see this::_updateCollapsableNodes
         */
        this._usedCollapsable = false;
        
        /*
         * Determines, if the localStorage can be used if
         * it should be used. This depends on the id (generated?)
         * and the availability of the localStorage.
         */
        this._useCollapsableLocalStorage = false;
        this.init(e);
    };
        
    /*
     * Initialize with given widget options.
     * 
     * @param e the widget
     */
    ContainerHandler.prototype.init = function(e) {
        // Initialize collapsable
        if (!e._generatedId &&
                e.options.container.isCollapsable) {
            this._doCollapsable(e);
        }else{
            e._nodes.find("li").addClass("aio-open");
        }
        
        // Initialize resizable container
        this._doResizable(e);
        
        // Initialize the switchFolder event
        $(document).on("click", "." + e._eventClassId + " .aio-list-standard a", function(event) {
            if (typeof e.options.others.onSwitchFolder === "function" && !$(this).hasClass("aio-disable-link")) {
                return (e.options.others.onSwitchFolder.bind(e.element))($(this), event);
            }
        });
    },
    
    /*
     * Reinit the position of the sticky
     * container or create a new sticky container, if needed.
     * 
     * @see this::_usedSticky
     */
    ContainerHandler.prototype.sticky = function(e) {
        // Initialize sticky container
        if (e.options.container.isSticky) {
            if (typeof $.fn.hcSticky !== "undefined") {
                if (e.element.data("sticky")) {
                    e.element.hcSticky('reinit');
                    return;
                }
                
                e.element.hcSticky(e.options.container.hcStickySettings);
                e.element.data("sticky", true);
            }else{
                throw "If want to use the sticky container please include the HC Sticky jQuery plugin, see http://someweblog.com/hcsticky-jquery-floating-sticky-plugin/ !";
            }
        }
        //e.element.hcSticky('reinit');
    },
    
    /*
     * Initialize the tooltips. This should be done
     * in the end of tree creation. It can also be
     * reinitialized through the reinit method.
     */
    ContainerHandler.prototype._doTooltips = function(e) {
        var callback = e.options.container.onDoTooltips;
        if (typeof callback === "function") {
            (callback.bind(e))();
        }else{
            // Use tooltipster
            if (typeof $.fn.tooltipster === "function") {
                var title, text, disabledLinkText, html;
                $(".aio-tooltip").each(function() {
                    title = $(this).attr("data-aio-tooltip-title");
                    text = $(this).attr("data-aio-tooltip-text");
                    disabledLinkText = $(this).attr("data-aio-tooltip-text-disabled");
                    if ($(this).hasClass("aio-disable-link")) { // Use the disabled text in tooltip
                        text = disabledLinkText;
                    }
                    
                    // Do the tooltip
                    if (title.length > 0 || text.length > 0) {
                        html = (title.length > 0 ? '<div class="aio-tooltip-title">' + title + '</div>' : "") + '<div class="aio-tooltip-text">' + text + "</p>";
                        if ($(this).hasClass("tooltipstered")) {
                            try {
                                $(this).tooltipster("destroy");
                            } catch (error) { }
                        }
                        $(this).tooltipster($.extend({
                            content: html,
                            contentAsHTML: true,
                            theme: "tooltipster-aio",
                            animation: "grow",
                            maxWidth: 225
                        }, e.options.container.tooltipsterSettings));
                    }
                });
            }else{
                throw "Please include tooltipster jQuery plugin (http://iamceege.github.io/tooltipster/)";
            }
        }
    },
    
    /*
     * Initialize the collapsable tree nodes.
     */
    ContainerHandler.prototype._doCollapsable = function(e) {
        this._usedCollapsable = true;
        this._useCollapsableLocalStorage = e._localStorage && e.options.container.collapsableLocalstorage;
        this._updateCollapsableNodes(e);
        var _useCollapsableLocalStorage = this._useCollapsableLocalStorage;
        
        // Event handler for doubleclick to expand
        $(document).on("dblclick", "." + e._eventClassId + " a", function() {
            var expander = $(this).parent().children(".aio-expander");
            if (expander.is(":visible")) {
                expander.click();
            }
        });
        
        // Event handler for the expander
        $(document).on("click", "." + e._eventClassId + " .aio-expander", function() {
            var isOpen = $(this).hasClass("aio-open"),
                removeClass = isOpen ? "aio-open" : "aio-close",
                addClass = isOpen ? "aio-close" : "aio-open",
                status = addClass == "aio-open" ? "1" : "0";
            $(this).removeClass(removeClass).addClass(addClass);
            $(this).parent().removeClass(removeClass).addClass(addClass);
            
            // Save status in localStorage
            var aLink = $(this).parent().children("a");
            if (typeof e.options.others.onExpand === "function") {
                (e.options.others.onExpand.bind(e.element))(status, $(this).parent().children("a"));
            }
            
            if (_useCollapsableLocalStorage && e._hasAttr("data-aio-id", aLink)) {
                var id = aLink.attr("data-aio-id");
                //window.localStorage.setItem('AIO' + e._id + '-expand-' + id, status); @deprecated
                e._lsSet("expand." + id, status);
            }
        });
    },
    
    /*
     * Put a [+] or [-] icon to the folders.
     * This function is only called, if _doCollapsable
     * is called once. Call this function also when
     * you add a new node or delete one.
     * 
     * @param e the widget
     * @return boolean if update was performed
     */
    ContainerHandler.prototype._updateCollapsableNodes = function(e) {
        if (this._usedCollapsable !== true) {
            return false;
        }
        
        var expander, treeClass, treeStatus, aLink, id,
            _useCollapsableLocalStorage = this._useCollapsableLocalStorage;
        e._nodes.find(".aio-expander").remove();
        e._nodes.find("li").each(function() {
            if ($(this).children("ul").children("li").length > 0) {
                // Reset all
                treeStatus = undefined;
                treeClass = "aio-close";
                
                aLink = $(this).children("a");
                if (_useCollapsableLocalStorage && e._hasAttr("data-aio-id", aLink)) {
                    id = aLink.attr("data-aio-id");
                    //treeStatus = window.localStorage.getItem("AIO" + e._id + "-expand-" + id); @deprecated
                    treeStatus = e._lsGet("expand." + id);
                    if (treeStatus == "1") {
                        treeClass = "aio-open";
                    }else if (treeStatus == "0"){
                        treeStatus = "aio-close";
                    }
                }
                
                if (!e._defined(treeStatus)) { // Set default if not set
                    treeClass = 'aio-' + e.options.container.collapsableDefaultStatus;
                }
                $(this).addClass(treeClass);
                expander = $("<div class=\"aio-expander " + treeClass + "\"><i class=\"fa fa-minus-square-o\"></i><i class=\"fa fa-plus-square-o\"></i></div>").appendTo($(this));
            }
        });
        
        return true;
    },
    
    /*
     * Initialize the resize handler button and add
     * document listeners to the resizeable button.
     * 
     * @param e jQuery widget
     */
    ContainerHandler.prototype._doResizable = function(e) {
        var touchable = e._isTouch(), cOptions = e.options.container,
            /* 
             * Function to get the current width of the container
             * depending on the max size
             */
            saveWidth = function() {
                var saveWidth = e.element.width(), resizeMin = e.options.container;
                if (e.element.hasClass("aio-wrap-collapse")) {
                    return 1; // collapsed
                }else{
                    if (saveWidth < resizeMin) {
                        saveWidth = resizeMin;
                    }
                }
                return saveWidth > e.options.container.resizeMax ? e.options.container.resizeMax : saveWidth;
            };

        // On Touch devices?
        var resizeButton = $('<div class="aio-split-resize"></div>').prependTo(e.element.children(".aio-wrap"));
        if (!(!e._generatedId &&
                e.options.container.isResizable &&
                (e._localStorage || cOptions.resizeLocalStorage == false))
            || !cOptions.resizeTouch && touchable.touch) {
            resizeButton.addClass("aio-hide");
        }
        
        // Get width
        var lWidth = 0, useResizeLocalStorage = cOptions.resizeLocalStorage && e._localStorage;
        if (useResizeLocalStorage) {
            //lWidth = +window.localStorage.getItem("AIO" + e._id + "-width"); @deprecated
            lWidth = +e._lsGet("width");
            if (!e._defined(lWidth)) {
                lWidth = cOptions.defaultWidth;
            }
        }
        if (lWidth < cOptions.resizeMin && lWidth !== 1) {
            lWidth = cOptions.resizeMin;
        }
        
        // Get opposite to element (or handler)
        var oppositeHandler = null;
        if (typeof cOptions.onResizeOpposite === "function") {
            oppositeHandler = cOptions.onResizeOpposite;
        }else{
            // Resize element
            var opposite = e._parseElements(cOptions.resizeOpposite);
            
            if (!resizeButton.hasClass("aio-hide")) {
                if ((!e._defined(opposite) || opposite.length == 0)) {
                    throw "If you want to resize the container, you need an opposite site! (options.container.resizeOpposite)";
                }
                opposite = opposite.first();
                this._usedOpposite = opposite;
                
                // Create unique ID for the opposite
                var guid = e._idGenerate(opposite);
                
                // Create default opposite handler
                oppositeHandler = function(width) {
                    if (cOptions.resizeOppositeMin > 0 && opposite.width() < cOptions.resizeOppositeMin + 1)
                        return false;
                    
                    // Handle resize
                    try {
                        e._injectStyle("opposite", "#" + guid + "{width: -webkit-calc(100% - " + width + "px) !important;width: -moz-calc(100% - " + width + "px) !important;width: calc(100% - " + width + "px) !important;}");
                    } catch (e) {
                        return false;
                    }
                };
            }else{
                oppositeHandler = function() {};
            }
        }

        // We need an oppositeHandler
        if (oppositeHandler === null) {
            throw "There is no opposite handler!";
        }
        
        // Create handler for resizing
        var container = e.element;
        var resizeHandler = typeof cOptions.customResizeHandler === "function" ? cOptions.customResizeHandler : function(ev, force) {
            var isEvent = ev instanceof jQuery.Event,
                min = cOptions.resizeMin,
                max = cOptions.resizeMax,
                x = isEvent ? ev.pageX - container.offset().left : ev,
                move = x >= min && x <= max;
                
            if (isEvent) {
                ev.preventDefault();
            }
            
            // Allow collapse
            if (x < min - 50) {
                move = x = 1;
            }
            
            if ((move || force) && oppositeHandler(x) !== false) {
                container.css("width", x + "px").toggleClass("aio-wrap-collapse", move === 1);
                if (container.data("sticky")) {
                    container.parent().css("width", x + "px");
                }
            }
        };
        resizeHandler = resizeHandler.bind(e.element);
        oppositeHandler = oppositeHandler.bind(e.element);
        
        // Add handler to the resize button
        var currentlyResizing = false;
        resizeButton.on(touchable.down, function(ev) {
            ev.preventDefault();
            $(document).on(touchable.move, resizeHandler);
            currentlyResizing = true;
        });
        
        $(document).on(touchable.up, function(ev) {
            $(document).unbind(touchable.move, resizeHandler);
            
            // Save in localStorage
            if (currentlyResizing) {
                if (useResizeLocalStorage) {
                    //window.localStorage.setItem("AIO" + e._id + "-width", saveWidth()); @deprecated
                    e._lsSet("width", saveWidth());
                }
                
                // Call finished callback
                if (typeof cOptions.onResizeFinished === "function") {
                    (cOptions.onResizeFinished.bind(e.element))(e.element.width());
                }
            }
            currentlyResizing = false;
        });

        // Call the resize once
        resizeHandler(lWidth);
        resizeButton.show().on("dblclick", function() { // Expand / Collapse sidebar by doubleclick
            var collapseWidth, collapseRestoreWidth = 250;
            
            // Determine restore width
            if (useResizeLocalStorage && !(collapseRestoreWidth = e._lsGet("rwidth"))) {
                collapseRestoreWidth = 250;
            }
            collapseWidth = container.hasClass("aio-wrap-collapse") ? collapseRestoreWidth : 1;
            
            // Save restore width
            if (useResizeLocalStorage) {
                if (collapseWidth === 1) {
                    e._lsSet("rwidth", e._lsGet("width") || 250);
                }
                e._lsSet("width", collapseWidth);
            }
            resizeHandler(collapseWidth, true);
            
            // Call finished callback
            if (typeof cOptions.onResizeFinished === "function") {
                (cOptions.onResizeFinished.bind(e.element))(collapseWidth);
            }
        });
    };
    
    /*
     * Handler for the drag and drop movement.
     * This defines a selector for the draggable items
     * and the droppable folders.
     * 
     * If you use your own MovementHandler implement the following functions:
     * reinit, state
     */
    var MovementHandler = function(e) {
        this.currentState = true;
        this.currentStateText = "enable";
        this.init(e);
    };
        
    /*
     * Initialize with given widget options.
     * 
     * @param e the widget
     */
    MovementHandler.prototype.init = function(e) {
        this._doDraggable(e.options.movement, e);
        this._doDroppable(e.options.movement, e);
    };
    
    /*
     * Reinitialize the drag and drop. 
     * 
     * @param e the widget
     */
    MovementHandler.prototype.reinit = function(e) {
        // Reinitialize draggable with the current movement state
        this._doDraggable(e.options.movement, e);
        this._doDroppable(e.options.movement, e);
    };
    
    /*
     * Disable or enable the drag & drop.
     * 
     * @param e the widget
     * @param state boolean the state of the movement
     */
    MovementHandler.prototype.state = function(e, state) {
        this.currentState = state;
        this.currentStateText = state ? "enable" : "disable";
        this.reinit(e);
    };
    
    /*
     * Initialize the draggable.
     * 
     * @param movement the movement options
     * @param e the widget
     */
    MovementHandler.prototype._doDraggable = function(movement, e) {
        var onGetLabel = typeof movement.onGetLabel === "function" ? movement.onGetLabel : this.draggableLabel,
            onGetHelper = typeof movement.onGetHelper === "function" ? movement.onGetHelper : this.draggableHelper;
        
        onGetLabel = onGetLabel.bind(e.element);
        onGetHelper = onGetHelper.bind(e.element);
        var draggableSettings = {
            revert: 'invalid',
            appendTo: 'body',
            cursorAt: { top: 0, left: 0 },
            distance: 10,
            scrollSensitivity: 50,
            scrollSpeed: 50,
            refreshPositions: true,
            helper: function() {
                // Draggable label
                var label = onGetLabel(this, arguments);
                return onGetHelper(label, this, arguments);
            },
            start: function(event) {
                e._standardList.addClass("aio-currently-dragging");
                
                // FIX https://bugs.jqueryui.com/ticket/4261
                jQuery(document.activeElement).blur();
            },
            stop: function() {
                e._standardList.removeClass("aio-currently-dragging");
            }
        };
        $.extend(draggableSettings, movement.draggableSettings);
        $(movement.selector).draggable(draggableSettings).draggable(this.currentStateText);
    };
    
    /*
     * Initialize the droppable.
     * 
     * @param movement the movement options
     * @param e the widget
     */
    MovementHandler.prototype._doDroppable = function(movement, e) {
        e._standardList.find("a").droppable($.extend({
            activeClass: "ui-state-default",
            hoverClass: "ui-state-hover",
            tolerance: "pointer"
        }, movement.droppableSettings)).droppable(this.currentStateText);
    };
    
    /*
     * Return a label for the movement, by default only print out "Move".
     * There is also a font-awesome icon.
     */
    MovementHandler.prototype.draggableLabel = function() {
        return '<i class="fa fa-arrow-right" style="margin-right:5px;"></i> Move';
    };
    
    /*
     * Create a DIV container for the draggable helper.
     * 
     * @param draggableLabel @see this::draggableLabel
     */
    MovementHandler.prototype.draggableHelper = function(draggableLabel) {
        return $('<div class="aio-movement-helper">' + draggableLabel + '</div>');
    };
    
    /*
     * Loader handler for the toolbar.
     */
    var LoaderHandler = function(e) {
        this.container = null;
        this.init(e);
    };
        
    /*
     * Add a handler container to the toolbar.
     * 
     * @param e the widget
     */
    LoaderHandler.prototype.init = function(e) {
        this.container = $('<div class="aio-tree-loader"></div>').appendTo(e._toolbar);
    };
    
    /*
     * Allow multiple arguments to allow
     * $(...).allInOneTree("loader", "show");
     * $(...).allInOneTree("loader", "hide");
     * $(...).allInOneTree("loader", true);
     * $(...).allInOneTree("loader", false);
     * $(...).allInOneTree("loader", "89"); => 89%
     * $(...).allInOneTree("loader", 50, 500); => 50 / 500 * 100 = 10%
     */
    LoaderHandler.prototype.fromArguments = function(args) {
        // There are now arguments...
        if (args.length === 0) {
            return this.container.width() > 0;
        }
        
        var percentage;
        if (args.length === 1) {
            if (args[0] == "show" || args[0] == true) {
                percentage = 100;
            }else if (args[0] == "hide" || args[1] == false) {
                percentage = 0;
            }else{
                percentage = args[0];
            }
        }else if (args.length === 2) {
            percentage = args[0] / args[1] * 100;
        }
        this.change(percentage);
    };
    
    /*
     * Change the visibility of the loader.
     * If percentage is <= 0, then the loader
     * is not visible. Otherwise it is shown.
     */
    LoaderHandler.prototype.change = function(percentage) {
        if (percentage > 0) {
            this.container.parents(".aio-tree").addClass("loading");
            this.container.css("width", percentage + "%");
        }else{
            this.container.parents(".aio-tree").removeClass("loading");
            this.container.css("width", "0px");
        }
    };
    
    /*
     * Toolbar handler
     */
    var ToolbarHandler = function(e) {
        this.itemsContainer = null;
        this.widget = null; /* Here we save the widget, because it is needed in the predefined functions */
        this._rearrangeInvokeSimulate = null; /* Simulate the rearrange when dragging a folder item outside the rearrange mode */
        this.init(e);
    };
    
    /*
     * Initialize the toolbar items
     */
    ToolbarHandler.prototype.init = function(e) {
        this.widget = e;
        this.itemsContainer = e._toolbar.find(".aio-toolbar-items");
        
        this._iterateButtons(e, function(button) {
            this._doCreateLink(button, e);
        }.bind(this));
    };
    
    /*
     * Create a link for each create type
     */
    ToolbarHandler.prototype._doCreateLink = function(button, e) {
        // Check if link is already available
        if (this.itemsContainer.find('a[data-aio-name="' + button.name + '"]').length > 0) {
            return;
        }
        
        var classes = typeof button.cssClasses === "string" ? button.cssClasses : "";
            classes = classes + ((typeof button.usePredefinedFunction == "string") ? " aio-predefined-" + button.usePredefinedFunction : "");
        var labelBack = button.labelBack || e.options.toolbarButtons.labelBack;
        var visibleInActiveFolderType = typeof button.visibleInActiveFolderType === "object" && button.visibleInActiveFolderType.length > 0 ? button.visibleInActiveFolderType.join(";") : ":",
            hasPreDisableFunction = typeof button.onPreDisable === "function" ? "toolbar" : "",
            html = '<a href="#" class="' + classes + ' aio-tooltip" style="display:none;" data-aio-predisable="' + hasPreDisableFunction + '" data-aio-name="' + button.name + '" data-aio-allowed="' + visibleInActiveFolderType + '" data-aio-name="' + button.name + '" data-aio-tooltip-title="' + (typeof button.toolTipTitle !== "undefined" ? button.toolTipTitle : "") + '" data-aio-tooltip-text-disabled="' + (typeof button.toolTipTextDisabledLink !== "undefined" ? button.toolTipTextDisabledLink : "") + '" data-aio-tooltip-text="' + (typeof button.toolTipText !== "undefined" ? button.toolTipText : "") + '">' + button.content + '<span class="aio-toolbar-back">' + labelBack + '</span></a>',
            link = $(html).appendTo(this.itemsContainer),
            usePredefinedFunction = typeof button.usePredefinedFunction == "string" && typeof this["_doPredefined" + button.usePredefinedFunction] === "function",
            usePredefinedInitFunction = usePredefinedFunction && typeof this["_doPredefinedInit" + button.usePredefinedFunction] === "function";
        
        // Save the data
        button.element = link;
        link.data("aio-link", button);
        
        // Predefined init
        if (usePredefinedInitFunction) {
            this["_doPredefinedInit" + button.usePredefinedFunction](e, $(this), button);
        }
        
        // Click handler
        var that = this;
        link.click(function(event) {
            var active = e.active(),
                isCancelActive = $(this).hasClass("isCancelActive"),
                isCreateNew = active.length > 0 && active.attr("data-aio-id") == "AIO_NEW_TO_CREATE",
                setToActive = false;
                
            // Custom allowed function
            if (typeof button.onAllowed === "function" && !isCancelActive) {
                var result = (button.onAllowed.bind(e.element))(e, $(this));
                if (result === false) {
                    event.preventDefault();
                    return false;
                }
            }
             
            // Disable link
            if ($(this).hasClass("aio-disable-link") && !isCreateNew) {
                event.preventDefault();
                return false;
            }
            
            // Perhaps it is a new creating folder?
            if (isCreateNew) {
                if (isCancelActive) {
                    e.element.removeClass("aio-prepare-create-new");
                    
                    // Is there any folder, that was active before?
                    var _activeBeforeCreateForm = e._typesHandler._activeBeforeCreateForm;
                    if (_activeBeforeCreateForm != null && typeof _activeBeforeCreateForm === "object") {
                        setToActive = _activeBeforeCreateForm;
                    }
                    e._typesHandler._activeBeforeCreateForm = null;
                }else{
                    e.element.addClass("aio-prepare-create-new");
                }
            }
            
            // Predefined function?
            if (usePredefinedFunction) {
                that["_doPredefined" + button.usePredefinedFunction](e, $(this), isCancelActive, button);
                // Disable types handler
                e._typesHandler.state(isCancelActive);
            }
            
            // Custom onCancel function
            if (typeof button.onCancel === "function") {
                if (isCancelActive) {
                    that.itemsContainer.add($(this)).removeClass("isCancelActive");
                    (button.onCancel.bind(e.element))($(this));
                }else{
                    that.itemsContainer.add($(this)).addClass("isCancelActive");
                }
                
                // Disable types handler
                e._typesHandler.state(isCancelActive);
            }
            
            // Custom onClick function
            if (typeof button.onClick === "function" && !isCancelActive) {
                (button.onClick.bind(e.element))(e, $(this));
            }
            
            // Reset to before active (when creating new folder)
            if (e._defined(setToActive)) {
                e.active(setToActive);
            }

            event.preventDefault();
            return false;
        });
    };
    
    /*
     * PREDEFINED INIT FUNCTION: Rename
     */
    ToolbarHandler.prototype._doPredefinedInitrename = function(e, obj, button) {
        // Event handler for F2 to rename
        $(document).on("keydown", "." + e._eventClassId + " a", function(event) {
            if(event.which == 113) { // F2
                button.element.click();
                return false;
            }
        });
    };
    
    /*
     * PREDEFINED INIT FUNCTION: Rearrange
     */
    ToolbarHandler.prototype._doPredefinedInitrearrange = function(e, obj, button) {
        // Check if this is allowed
        if (!e.options.container.doNotUseSaveInRearrange) {
            return;
        }
        
        var that = this;
        $(document).on("dragstart", "." + e._eventClassId + " a[data-aio-id]", function(ev) {
            if (!$(this).parents(".aio-tree").hasClass("aio-rearrange")) {
                that._rearrangeInvokeSimulate = [ $(this), ev ];
                e.toolbarButton("rearrange");
                ev.preventDefault();
                return false;
            }
        });
    };

    /*
     * PREDEFINED FUNCTION: Rename
     * It also includes the creating callback
     */
    ToolbarHandler.prototype._doPredefinedrename = function(e, obj, isCancelActive, button) {
        var a = e.active(),
            name = a.find(".aio-node-name"), input, submitButton,
            isCreateNew = a.length > 0 && a.attr("data-aio-id") == "AIO_NEW_TO_CREATE",
            onSave = function() {
                var newName = input.val(),
                    callbackOnSave = isCreateNew ? e.options.others.onCreateFolder : button.onSave;
                if (typeof callbackOnSave === "function") {
                    callbackOnSave = (callbackOnSave.bind(e.element));
                    if (isCreateNew) {
                        callbackOnSave(newName, a.data("createType"), a.data("createToParent"), a);
                    }else{
                        callbackOnSave(a.attr("data-aio-id"), newName);
                    }
                }else{
                    throw "If you want to use the rename/create function please define an onSave/onCreateFolder action!";
                }
            }.bind(e);
            
        if (name.css("visibility") !== "hidden") {
            // Create input and button
            name.css("visibility", "hidden");
            e.nodes(false); // Deactive node links
            this.itemsContainer.add(obj).addClass("isCancelActive");
            input = $('<input type="text"/>').insertAfter(a);
            submitButton = $('<button class="aio-rename-submit">' + e.options.others.labelRenameSave + '</button>').insertAfter(input);
            input.val(name.text()).focus();
            
            // Add event handlers to input and button
            input.keypress(function(e) {
                if(e.which == 13) {
                    onSave();
                }
            }).keyup(function(_e) {
                if(_e.which == 27) {
                    if (isCreateNew) {
                        e.element.find(".aio-tree-headline a.aio-cancel-create-new:visible").click();
                    }else{
                        e.toolbarButton("rename");
                    }
                }
            });
            submitButton.on("click", function(e) {
                onSave();
                e.preventDefault();
                return false;
            });
        }else{
            // Disable renaming
            name.css("visibility", "visible");
            e.nodes(true);
            this.itemsContainer.add(obj).removeClass("isCancelActive");
            a.parent().children("input,button.aio-rename-submit").remove();
        }
    };
    
    /*
     * PREDEFINED FUNCTION: Rearrange
     */
    ToolbarHandler.prototype._doPredefinedrearrange = function(e, obj, isCancelActive) {
        if (typeof $.fn.nestedSortable === "function") {
            var nodesUL = e._nodes.children("ul"),
                saveButton = e.element.find(".aio-rearrange-save"),
                onRearrangeSave = e.options.container.onRearrangeSave,
                onRearrangeCancel = e.options.container.onRearrangeCancel;
            if (isCancelActive) {
                // Go back to normal mode
                if (obj) {
                    this.itemsContainer.add(obj).removeClass("isCancelActive");
                }
                e._standardList.children("a").attr("disabled", false);
                e._standardList.children(".aio-search").attr("disabled", false);
                e.element.removeClass("aio-rearrange");
                nodesUL.nestedSortable("destroy");
                nodesUL.data("nestedSortable", false);
                saveButton.stop().hide();
                
                // Repaste the HTML
                if (!e.options.container.doNotUseSaveInRearrange) {
                    var nodesHTML = nodesUL.data("nodesHTML");
                    nodesUL.html(nodesHTML);
                }
                
                // The <a>'s should a href!
                e.nodes(true);
                
                // Change state of draggable and droppable to enabled
                e.movement(true);
                
                if (typeof onRearrangeCancel === "function") {
                    (onRearrangeCancel.bind(e.element))(e, event);
                }
                
            }else{
                // Go to the rearrange mode
                e.element.addClass("aio-rearrange");
                if (obj) {
                    this.itemsContainer.add(obj).addClass("isCancelActive");
                }
                e._standardList.children("a").attr("disabled", "disabled");
                e._standardList.children(".aio-search").attr("disabled", "disabled");
                nodesUL.data("nodesHTML", nodesUL.html());
                
                // Add a rearrange save button (if not exists)
                if (!e.options.container.doNotUseSaveInRearrange) {
                    // @TODO nur möglich wenn kein API modus
                    if (saveButton.length <= 0) {
                        saveButton = $('<button class="aio-rearrange-save button button-primary" style="display:none;">' + e.options.others.labelRearrangeSave + '</button>').insertAfter(e._toolbar);
                        var that = this;
                        /*
                         * Click handler for the save rearranged order button.
                         * 
                         * @see options.container.onRearrangeSave
                         */
                        saveButton.click(function(event) {
                            if (typeof onRearrangeSave === "function") {
                                (onRearrangeSave.bind(e.element))
                                    (that._doPredefinedrearrangeParse(e, nodesUL), nodesUL.html(), $(this), event);
                            }
                            
                            event.preventDefault();
                            return false;
                        });
                    }
                    saveButton.show();
                }
                
                // Change state of draggable and droppable to disabled
                e.movement(false);
                
                // The <a>'s should not contain a href!
                e.nodes(false);
                
                // Make the list nested sortable
                this._doPredefinedrearrangeActivate(nodesUL, e);
                nodesUL.data("nestedSortable", true);
            }
        }else{
            throw "Please include nestedSortable jQuery plugin (https://github.com/ilikenwf/nestedSortable)";
        }
    };
    
    /*
     * Use this function to make the nestedSortable really
     * sortable!
     * 
     * @see this:_doPredefinedrearrange
     */
    ToolbarHandler.prototype._doPredefinedrearrangeActivate = function(nodesUL, e) {
        // Clear sortable
        try {
            nodesUL.nestedSortable("destroy");
        }catch (e) {
            // Silence is golden.
        }
        
        var hoverListTimeout, hoverListElement, ri = this._rearrangeInvokeSimulate,
            appendTo = nodesUL.scrollParent();
        appendTo = appendTo.is(document) ? "parent" : appendTo;
        
        // Do the nested sortable
        nodesUL.nestedSortable($.extend({
            //revert: 'invalid',
            //appendTo: 'body',
            //cursorAt: { top: 0, left: 0 },
            //distance: 10,
            //scrollSensitivity: 50,
            //scrollSpeed: 50,
            //refreshPositions: true,
            
            handle: 'a[data-aio-href]',
            items: 'li',
            listType: 'ul',
            tolerance: "intersect",
            toleranceElement: '> a',
            helper: "clone",
            forceHelperSize: false,
            forcePlaceholderSize: true,
            doNotClear: true,
            hoverListElement: function(event, ui) {
                var _hoverListElement = event.hoverListElement;
                if (!_hoverListElement.is(hoverListElement)) {
                    hoverListElement = _hoverListElement;
                    clearTimeout(hoverListTimeout);
                    
                    // Check if it is expandable
                    var _expander = _hoverListElement.children(".aio-expander");
                    if (_expander.length === 1 && !_expander.hasClass("aio-open")) {
                        hoverListTimeout = setTimeout(function() {
                            _expander.click();
                            setTimeout(function() {
                                nodesUL.nestedSortable("refreshPositions");
                            }, 50);
                        }, 700);
                    }
                }
            },
            noHoverListElement: function() {
                clearTimeout(hoverListTimeout);
                hoverListElement = null;
            },
            sort: function(event, ui) {
                // Fix position the first time we sort
                if (!ui.helper.data("refreshPositions")) {
                    nodesUL.nestedSortable("refreshPositions");
                    ui.helper.data("refreshPositions", true);
                }
            },
            start: function(event, ui) {
                // Save this so we can revert to it
                if (e.options.container.doNotUseSaveInRearrange) {
                    ui.item.data("oldNodesHTML", nodesUL.data("nodesHTML"));
                }
            },
            relocate: function(event, ui) {
                // Update collapsable trees
                e.reinit("tree");
                
                // Do the doNotUseSaveInRearrange method
                var args = $.makeArray(arguments),
                    doNotUseSaveInRearrange = e.options.container.doNotUseSaveInRearrange,
                    onRearrangeRelocate = e.options.container.onRearrangeRelocate;
                if (doNotUseSaveInRearrange) {
                    if (typeof onRearrangeRelocate !== "function") {
                        throw "Please define the onRearrangeRelocate function in the options!";
                    }
                    
                    // Activate the deferred
                    var deferred = $.Deferred(),
                        active = ui.item,
                        nextToActive = active.next(),
                        prevToActive = active.prev(),
                        nextId = nextToActive.is("li") ? nextToActive.children("a").attr("data-aio-id") : false,
                        prevId = prevToActive.is("li") ? prevToActive.children("a").attr("data-aio-id") : false,
                        totalNext, totalPrev, totalNextId, totalPrevId, iIndex = -1, currentInAll, // In total prev and next
                        allNodes = e._nodes.find("a[data-aio-id]"),
                        oldNodesHTML = active.data("oldNodesHTML"),
                        id = active.children("a").attr("data-aio-id"),
                        parentsA = active.parent().parent().children("a[data-aio-id]"), // LI < UL < LI > A
                        parentsID = parentsA.length > 0 ? parentsA.attr("data-aio-id") : e.options.others.rootParentId;
                    e.loader(true);
                    
                    // Search for the total prev and next id
                    allNodes.each(function() {
                        iIndex++;
                        if ($(this).attr("data-aio-id") === id) {
                            return false;
                        }
                    });
                    totalNext = allNodes.eq(iIndex + 1);
                    totalNextId = totalNext.is("a") ? totalNext.attr("data-aio-id") : false;
                    
                    if (iIndex - 1 >= 0) {
                        totalPrev = allNodes.eq(iIndex - 1);
                        totalPrevId = totalPrev.is("a") ? totalPrev.attr("data-aio-id") : false;
                    }else{
                        totalPrevId = false;
                    }
                    
                    /*
                     * Parameters:
                     * 
                     * [0] The id which is moved
                     * [1] The new parent id it should be moved
                     * [2] A jquery deferred object which can be resolved or rejected
                     * [3] The prev id
                     * [4] The next id
                     * [5] The total prev id
                     * [6] The total next id
                     * [7] This arguments
                     */
                    onRearrangeRelocate.apply(e.element, [ id, parentsID, deferred, prevId, nextId, totalPrevId, totalNextId, args ]);
                    
                    // Done handler
                    deferred.then(function() {
                        // Silence is golden.
                    }.bind(this), function() {
                        // Revert to old
                        e.nodesHTML(oldNodesHTML);
                    }.bind(this)).always(function() {
                        e.loader(false);
                    }.bind(this));
                }
            },
            isAllowed: function(placeholder, placeholderParent, currentItem) {
                // Check if loader is active, then do not allow
                if (e.loader()) {
                    return false;
                }
                
                var classNameRestrict = "aio-restrict-hierarchical-change", result = true;
                // Check if current restricted
                if (currentItem.parents("." + classNameRestrict).length > 0) {
                    // Same level is allowed
                    var id = currentItem.attr("id");
                    if (placeholder.parent().children("#" + id).length === 0) {
                        result = false;
                    }
                }
                
                // Check if new destination is restricted
                if (placeholder.parents("." + classNameRestrict).length > 0) {
                    result = false;
                }
                return result;
            },
            stop: function() {
                if (ri) {
                    e.toolbarButton("rearrange");
                }
                this._rearrangeInvokeSimulate = null;
            }.bind(this)
        }, e.options.container.nestedSortableSettings));
        
        // Invoke simulated
        if (ri && dispatchEvent) {
            var handle = ri[0][0], rie = ri[1], x = rie.clientX, y = rie.clientY;
            
            // Forward and dispatch the move event
            this._dispatchEvent(handle, 'mousedown',
                this._createRearrangeEvent('mousedown', handle, { clientX: x, clientY: y }));
            this._dispatchEvent(document, 'mousemove',
                this._createRearrangeEvent('mousemove', document, { clientX: x+1, clientY: y+1 }));
        }
    };
    
    ToolbarHandler.prototype._dispatchEvent = function (el, type, evt) {
        if (el.dispatchEvent) {
            el.dispatchEvent(evt);
        } else if (el.fireEvent) {
            el.fireEvent('on' + type, evt);
        }
        return evt;
    };
    
    ToolbarHandler.prototype._createRearrangeEvent = function (type, target, options) {
        var evt;
        var e = $.extend({
            target: target,
            preventDefault: function() { },
            stopImmediatePropagation: function() { },
            stopPropagation: function() { },
            isPropagationStopped: function() { return true; },
            isImmediatePropagationStopped: function() { return true; },
            isDefaultPrevented: function() { return true; },
            bubbles: true,
            cancelable: (type != "mousemove"),
            view: window,
            detail: 0,
            screenX: 0,
            screenY: 0,
            clientX: 0,
            clientY: 0,
            ctrlKey: false,
            altKey: false,
            shiftKey: false,
            metaKey: false,
            button: 0,
            relatedTarget: undefined
        }, options || {});
        
        if ($.isFunction(document.createEvent)) {
            evt = document.createEvent("MouseEvents");
            evt.initMouseEvent(type, e.bubbles, e.cancelable, e.view, e.detail,
            e.screenX, e.screenY, e.clientX, e.clientY,
            e.ctrlKey, e.altKey, e.shiftKey, e.metaKey,
            e.button, e.relatedTarget || document.body.parentNode);
        } else if (document.createEventObject) {
            evt = document.createEventObject();
            $.extend(evt, e);
            evt.button = { 0:1, 1:4, 2:2 }[evt.button] || evt.button;
        }
        return evt;
    };
    
    /*
     * Parses the nestedSortable to an object array.
     * 
     * @param obj the nestedSortable object
     * @return { raw: original from nestedSortable, parsed: arraid for FID and PID }
     * @see this:_doPredefinedrearrange
     */
    ToolbarHandler.prototype._doPredefinedrearrangeParse = function(e, obj) {
        var arraied = obj.nestedSortable('toArray', {
            startDepthCount: 0,
            onGetId: function(obj, depth, _left, o) {
                var id = obj.children("a").attr("data-aio-id");
                return id;
            }
        });
        
        arraied.shift();
        var raw = [];
        if (typeof arraied === "object" && arraied.length > 1) {
            $.each(arraied, function(k, v) {
                if (v.parent_id == null) {
                    v.parent_id = e.options.others.rootParentId;
                }
                raw.push({fid: v.id, pid: v.parent_id});
            });
        }
        
        return { raw: arraied, parsed: raw };
    };
    
    /*
     * Iterate through the toolbar buttons and execute a given callback.
     */
    ToolbarHandler.prototype._iterateButtons = function(e, callback) {
        var buttons = e.options.toolbarButtons.items;
        if (typeof buttons === "object" || buttons.length > 0) {
            // Iterate the create types
            var button, name;
            for (var i = 0; i < buttons.length; i++) {
                button = buttons[i];
                
                // Validate
                name = button.name;
                if (!e._defined(name)) {
                    console.error("Toolbar button must have a name.");
                    continue;
                }

                if (callback(button, e) === false) {
                    break;
                }
            }
        }
    };
    
    /*
     * Handler for the create types. It creates
     * the buttons based on the options.
     * 
     * @see options.createTypes
     */
    var TypesHandler = function(e) {
        /*
         * Here are the links for the createTypes saved
         */
        this._linkContainer = false;
        
        /*
         * Timeout for the animation of createTypes
         */
        this._animationTimeout = undefined;
        
        /*
         * That is the active node before a new one is created
         */
        this._activeBeforeCreateForm = null;
        this.init(e);
    };
        
    /*
     * Initialize the create types and create
     * <a> links.
     * 
     * @param e the widget
     */
    TypesHandler.prototype.init = function(e) {
        this._iterateCreateTypes(e, function(target) {
            this._doCreateLink(target, e);
        }.bind(this));
        
        // Create a "Cancel" button
        if (this._linkContainer != false) {
            var html = '<a href="javascript:void();" class="' + e.options.createCancel.cssClasses + ' aio-cancel-create-new">' + e.options.createCancel.label + '</a>';
            var link = $(html).appendTo(this._linkContainer);
            link.click(function(event) {
                e.toolbarButton("rename");
                e._nodes.find(".aio-create-new").parent().remove(); // Remove the input field
                
                if (typeof e.options.others.onTypeButtonClicked === "function") {
                    (e.options.others.onTypeButtonClicked.bind(e.element))(link, event);
                }
                event.preventDefault();
                return false;
            });
        }
    };
        
    /*
     * Enable or disbale the link container buttons
     * 
     * @param boolean state
     */
    TypesHandler.prototype.state = function(state) {
        if (typeof this._linkContainer === "object") {
            if (state) {
                this._linkContainer.removeClass("aio-disable-all");
            }else{
                this._linkContainer.addClass("aio-disable-all");
            }
        }
    };
        
    /*
     * Create a link for each create type
     */
    TypesHandler.prototype._doCreateLink = function(target, e) {
        // Check if link is already available
        var linkContainer = e.element.find(".aio-tree-headline");
        this._linkContainer = linkContainer;
        if (linkContainer.find('a[data-aio-name="' + target.name + '"]').length > 0) {
            return;
        }
        
        var classes = typeof target.cssClasses === "string" ? target.cssClasses : "",
            visibleInActiveFolderType = typeof target.visibleInActiveFolderType === "object" && target.visibleInActiveFolderType.length > 0 ? target.visibleInActiveFolderType.join(";") : ":",
            hasPreDisableFunction = typeof target.onPreDisable === "function" ? "type" : "",
            html = '<a href="#" class="' + classes + ' aio-tooltip" style="display:none;" data-aio-name="' + target.name + '" data-aio-predisable="' + hasPreDisableFunction + '" data-aio-allowed="' + visibleInActiveFolderType + '" data-aio-tooltip-title="' + (typeof target.toolTipTitle !== "undefined" ? target.toolTipTitle : "") + '" data-aio-tooltip-text="' + (typeof target.toolTipText !== "undefined" ? target.toolTipText : "") + '">' + target.label + '</a>',
            link = $(html).appendTo(linkContainer);
        
        // Click handler for this link
        link.data("createType", target);
        link.click(function(event) {
            if ($(this).hasClass("aio-disable-link")) {
                event.preventDefault();
                return false;
            }
            
            this._doCreateForm($(this), e, target);
            
            if (typeof e.options.others.onTypeButtonClicked === "function") {
                (e.options.others.onTypeButtonClicked.bind(e.element))(link, event);
            }
            event.preventDefault();
            return false;
        }.bind(this));
        
        // Save the data
        link.data("aio-link", target);
    };
    
    /*
     * Click handler for the create form
     */
    TypesHandler.prototype._doCreateForm = function(obj, e, target) {
        // Check, where it should be appended
        var active = e.active(), parentUL, parentID = e.options.others.rootParentId;
        if (active.length <= 0 || active.parents(".aio-nodes").length <= 0) { 
            parentUL = e._nodes.children("ul");
        }else{
            parentUL = active.parent().children("ul");
            parentID = active.attr("data-aio-id");
            // Expand it
            var expander = active.parent().children(".aio-expander");
            if (expander.length > 0) {
                if (expander.hasClass("aio-close")) {
                    expander.click();
                }
            }else{
                active.parent().addClass("aio-open");
            }
        }
        if (active.length > 0) {
            this._activeBeforeCreateForm = active;
        }
        
        // Create the form
        var li = $('<li>\
            <a href="javascript:void(0)" class="aio-create-new" data-aio-type="" data-aio-id="AIO_NEW_TO_CREATE" data-aio-href="">\
        		' + target.icon + '\
        		<div class="aio-node-name"></div>\
        		<span class="aio-cnt aio-cnt-0">0</span>\
        	</a>\
        	<ul></ul>\
        </li>').appendTo(parentUL);
        li.children("a").data("createType", target);
        li.children("a").data("createToParent", parentID);
        e.active("AIO_NEW_TO_CREATE");
        e.toolbarButton("rename");
    };
    
    /*
     * Iterate through the create types and execute a given callback.
     */
    TypesHandler.prototype._iterateCreateTypes = function(e, callback) {
        var createTypes = e.options.createTypes;
        if (typeof createTypes === "object" || createTypes.length > 0) {
            // Iterate the create types
            var type, name;
            for (var i = 0; i < createTypes.length; i++) {
                type = createTypes[i];
                
                // Validate
                name = type.name;
                if (!e._defined(name)) {
                    console.error("Create type must have a name.");
                    continue;
                }

                callback(type, e);
            }
        }
    };
    
    /*
     * This functions shows or hides the createTypes
     * for the given folder type.
     */
    TypesHandler.prototype._doSwitchFolder = function(e, obj, animationDuration) {
        var allowed, objType = obj.attr("data-aio-type"), show, preDisable,
            animationArr = { show: [], hide: [] };
        if (!animationDuration) {
            animationDuration = 300;
        }
        
        // Get searchable items which
        var items = typeof this._linkContainer === "object" ? this._linkContainer.find("a") : false,
            toolbarItems = e._toolbar.find(".aio-toolbar-items").find("a");
        items = toolbarItems.add(items);
        
        // Iterate all the items, and check if enabled
        items.each(function() {
            if ($(this).hasClass("aio-cancel-create-new")) {
                return true;
            }
            
            if ((preDisable = $(this).attr("data-aio-predisable")).length > 0) {
                // Do the function predisable check
                (preDisable === "toolbar" ? e._toolbarHandler._iterateButtons : e._typesHandler._iterateCreateTypes)(e, function(button) {
                    if ($(this).attr("data-aio-name") === button.name && typeof button.onPreDisable === "function") {
                        show = (button.onPreDisable.bind(e.element))(button);
                    }
                }.bind(this));
            }else{
                // Do the "allowed folder type" check
                allowed = $(this).attr("data-aio-allowed");
                allowed = allowed === ":" ? [] : allowed.split(";");
                show = false;
                
                if (allowed.length > 0) {
                    if (allowed.indexOf(objType) > -1) {
                        show = true;
                    }
                }else{
                    show = true;
                }
            }
            animationArr[show ? "show" : "hide"].push($(this));
        });
        
        var currentItem;
        var showFn = function() {
            if (animationArr.show.length > 0) {
                for (var i = 0; i < animationArr.show.length; i++) {
                    currentItem = animationArr.show[i].stop();
                    if (currentItem.parent().hasClass("aio-toolbar-items")) {
                        // "Enable" toolbar items only
                        currentItem.removeClass("aio-disable-link");
                    }else{
                        animationArr.show[i].stop().fadeIn(animationDuration).removeClass("aio-hidden");
                    }
                }
            }
            e.reinit("tooltips");
        }.bind(this);
        
        // Do the animation
        if (animationArr.hide.length > 0) {
            for (var i = 0; i < animationArr.hide.length; i++) {
                currentItem = animationArr.hide[i].stop();
                if (currentItem.parent().hasClass("aio-toolbar-items")) {
                    // "Disable" toolbar items only
                    currentItem.addClass("aio-disable-link");
                }else{
                    currentItem.fadeOut(animationDuration).addClass("aio-hidden");
                }
            }
            if (e._defined(this._animationTimeout)) {
                clearTimeout(this._animationTimeout);
            }
            this._animationTimeout = setTimeout(function() {
                showFn();
            }.bind(this), animationDuration > 0 ? animationDuration + 50 : 0);
        }else{
            showFn();
        }
    };
    
    /*
     * Provides the search handling for the tree.
     */
    var SearchHandler = function(e) {
        this._fixRestoreContent = false;
        this._restoreContent = undefined;
        this._oldSelectedId = undefined;
        this._searchTimeout = undefined;
        this._noSearchResults = undefined;
        this._treeHeadline = undefined;
        this.init(e);
    };
        
    SearchHandler.prototype.init = function(e) {
        var that = this;
        this._noSearchResults = $("." + e._eventClassId + " .aio-no-results");
        this._treeHeadline = $("." + e._eventClassId + " .aio-tree-headline");

        // Listen to input changes
        //$(document).on("propertychange input", "." + e._eventClassId + " .aio-search input", function(event) {
        $("." + e._eventClassId + " .aio-search input").on("propertychange input", function(event) { // This must listen to the object directly because IE does not listen to this events
            if (event.type === "propertychange" && event.originalEvent.propertyName === "value" || event.type !== "propertychange") {
                that._doSearch(e, $(this), event.target.value.toLowerCase());
            }
        });
        
        // Listen to close event
        $(document).on("click", "." + e._eventClassId + " .aio-search .fa-close", function(event) {
        //$("." + e._eventClassId + " .aio-search .fa-close").on("click", function(event) {
            $(this).parent().children("input").val("").trigger("input");
        });
        
        // Listen to focus event
        $(document).on("click", "." + e._eventClassId + " .aio-search", function(event) {
        //$("." + e._eventClassId + " .aio-search").on("click", function(event) {
            if (!$(event.target).is("input") && !$(event.target).hasClass("fa-close")) {
                $(this).children("input").focus();
            }
        });
        $(document).on("focus", "." + e._eventClassId + " .aio-search input", function(event) {
        //$("." + e._eventClassId + " .aio-search input").on("focus", function(event) {
            $(this).parent().addClass("aio-search-focus");
        });
        
        // Listen to blur event
        $(document).on("blur", "." + e._eventClassId + " .aio-search input", function(event) {
        //$("." + e._eventClassId + " .aio-search input").on("blur", function(event) {
            $(this).parent().removeClass("aio-search-focus");
            e._nodes.find(".aio-search-selected").removeClass("aio-search-selected");
            that._fixRestoreContent = !!$(this).val();
        });
        
        // Listen to arrow keys
        $(document).on("keydown", "." + e._eventClassId + " .aio-search input", function(event) {
        //$("." + e._eventClassId + " .aio-search input").on("keydown", function(event) {
            var selected = e._nodes.find(".aio-search-selected");
            if ((event.which === 40 || event.which === 38) && !!$(this).val()) { // down, up
                // Detect next selected
                var nextSelected, sfn = event.which === 38 ? "prev" : "next";
                if (selected.length > 0) {
                    nextSelected = selected.parent()[sfn]().children("a");
                    selected.removeClass("aio-search-selected");
                }else{
                    sfn = event.which === 38 ? "last" : "first";
                    nextSelected = e._nodes.find("a[data-aio-id]:" + sfn);
                }
                nextSelected.addClass("aio-search-selected");
               
                if (nextSelected.length > 0) {
                    e.nodeToViewPort(nextSelected);
                }
                event.preventDefault();
                return false;
            }else if (event.which === 13 && selected.length > 0) { // Enter press opens folder
                selected[0].click();
            }else if (event.which === 27) {
                $(this).parent().children(".fa-close").click();
                event.preventDefault();
                return false;
            }
            return true;
        });
    };
    
    SearchHandler.prototype._doRestoreContent = function(e) {
        // Already restored?
        if (!this._restoreContent) {
            return;
        }
        
        var restoredContent = this._restoreContent.wrap("<p/>").parent();
        
        // Get selected id from search
        var newSelectedId = e.active().attr("data-aio-id");
        if (this._oldSelectedId === undefined || newSelectedId == this._oldSelectedId) {
            newSelectedId = false;
        }
        
        // Reset
        e._nodes.html(restoredContent.html()).css("min-height", "");
        e.element.removeClass("aio-searching");
        this._restoreContent = undefined;
        this._oldSelectedId = undefined;
        this._noSearchResults.hide();
        this._treeHeadline.removeClass("aio-disable-all");
        e.reinit();
        
        // Reveal to the top of this item
        e.active(newSelectedId, true, true);
    };
    
    SearchHandler.prototype._doFixRestoreContent = function(e, isSearch) {
        // Fix restore content with changes from search results
        e._nodes.find(".aio-search-selected").removeClass("aio-search-selected");
        if (this._fixRestoreContent && this._restoreContent) {
            var that = this;
            e._nodes.find("a[data-aio-id]").each(function() {
                that._restoreContent.find("a[data-aio-id=\"" + $(this).attr("data-aio-id") + "\"]")
                    .replaceWith($(this).clone().wrap("<p/>").parent().html());
            });
        }
        this._fixRestoreContent = false;
    };
    
    SearchHandler.prototype._doSearch = function(e, searchField, value) {
        var icons = searchField.parent().children("i"),
            showCloseIcon = +value.length,
            isSearch = value.length > 0;
        icons.eq(showCloseIcon).show();
        icons.eq(+!showCloseIcon).hide();
        this._doFixRestoreContent(e, isSearch);
        if (isSearch) {
            // Save old content
            if (this._restoreContent === undefined) {
                this._restoreContent = $(e._nodes.html());
                this._oldSelectedId = e.active().attr("data-aio-id");
                e._nodes.css("min-height", e._nodes.height());
            }
            this._treeHeadline.addClass("aio-disable-all");
            e._nodes.html("");
            e.element.addClass("aio-searching");
            
            clearTimeout(this._searchTimeout);
            this._searchTimeout = setTimeout(function() {
                // Check if empty
                if (!searchField.val()) {
                    this._doRestoreContent(e);
                    return;
                }
                
                // Get results
                var results = [];
                this._restoreContent.find('a[data-aio-id] .aio-node-name').each(function() {
                    if ($(this).text().toLowerCase().indexOf(value) > -1) {
                        results.push("<li>" + $(this).parent().clone().wrap("<p/>").parent().html() + "</li>");
                    }
                });
                e._nodes.html("<ul>" + results.slice(0,20).join("") + "</ul>");
                if (results.length > 0) {
                    this._noSearchResults.hide();
                }else{
                    this._noSearchResults.show();
                }
                e.reinit();
            }.bind(this), value.length > 1 ? 500 : 0);
        }else{
            this._doRestoreContent(e);
        }
    };
    
    /**
     * @classdesc A jQuery plugin / widget to provide functionality for a tree view with
     * toolbar, resizer and create-buttons.
     * 
     * @class
     * @alias allInOneTree
     * @hideconstructor
     * @param {boolean} [oSettings.container.isCollapsable=true] Make the tree collapsable (only works if the element has an id attribute and browser supports localStorage)
     * @param {boolean} [oSettings.container.isResizable=true] Make the tree resizable (only works if the element has an id attribute and browser supports localStorage)
     * @param {boolean} [oSettings.container.isSticky=true] Make the tree sticky
     * @param {string} [oSettings.container.theme] Adds a class to the tree with .aio-theme-{theme}. If you want to apply multiple themes use a " " delimtter (space)
     * @param {boolean} [oSettings.container.collapsableLocalstorage] Saves the state of an expanded / collapsed tree node in the localStorage. The nodes (a-tags) must have a data-aio-id attribute so the state can be saved. (only works if the element has an id attribute and browser supports localStorage)
     * @param {('open'|'close')} [oSettings.container.collapsableDefaultStatus="open"] The default state of a node when the given node item is not saved in the localStorage
     * @param {int} [oSettings.container.defaultWidth=250] 
     * @param {DOMElement|jQuery} [oSettings.container.resizeOpposite] The opposite site of the container if the sidebar grows (single item)
     * @param {function} [oSettings.container.customResizeHandler] This function will be called instead of doing anything while resizing
     * @param {function} [oSettings.container.onResizeOpposite] This function will be called instead of changing the resizeOpposite element
     * @param {function} [oSettings.container.onResizeFinished] This function will be called when the resizing is finished
     * @param {boolean} [oSettings.container.resizeTouch=false] Determines if the resizable works on touch devices
     * @param {boolean} [oSettings.container.resizeLocalStorage=true] Determines if the resized width should be saved in the localStorage
     * @param {boolean} [oSettings.container.resizeMin=250] Minimal tree width (px)
     * @param {boolean} [oSettings.container.resizeMax=800] Maximal tree width (px)
     * @param {boolean} [oSettings.container.resizeOppositeMin=200] Minimal opposite width (px)
     * @param {function} [oSettings.container.onRearrangeCancel] This function will be called when the rearrange mode is cancelled or closed. Use this function to reinit new generated HTML, for example
     * @param {function} [oSettings.container.onRearrangeRelocate] This function will be called when an item is relocated. Use this if you do not want to use the "Save" button, instead save immediatly (also set the property doNotUseSaveInRearrange to true).
     *                                                           Parameters: [0] The id which is moved [1] The new parent id it should be moved [2] A jquery deferred object which can be resolved or rejected [3] The prev id [4] The next id [5] This arguments
     *                                                           Use the deferred object to .resolve() to finish the process, the .reject() to revert the location of the dragged item. For error messages use your own code in this function
     * @param {function} [oSettings.container.onRearrangeSave] This function will be called when the "Save" button is pressed in rearrange mode. Use the AIO loader and the toolbarButton method to hide the rearrange mode
     * @param {boolean} [oSettings.container.doNotUseSaveInRearrange] Set to true, to use the onRearrangeRelocate function and hide the Save button
     * @param {function} [oSettings.container.onDoTooltips] This function will be called instead of the default tooltip handler. Controls, which have a tooltip, have the class .aio-tooltip
     * @param {object} [oSettings.container.tooltipsterSettings] Settings for tooltipster jQuery plugin
     * @param {object} [oSettings.container.nestedSortableSettings] Settings for nestedSortable jQuery plugin
     * @param {object} [oSettings.container.hcStickySettings] Settings for HC Sticky jQuery plugin
     * @param {object} [oSettings.container.customLoaderHandler] Custom loader handler (see LoaderHandler in sourcecode)
     * @param {object[]} [oSettings.createTypes] Defines multiple create types, for example collections and galleries. There can be multiple create types. If you want no create types, define it as false
     * @param {string} oSettings.createTypes.name Unique folder identifier
     * @param {string} [oSettings.createTypes.icon] The icon when creating a new node, for example <code><i class="fa fa-folder-open"></i></code>
     * @param {array|string} [oSettings.createTypes.visibleInActiveFolderType] Make the create button visible in this folders to create subfolders. The nodes (a-tags) needs a data-aio-type attribute
     * @param {function} [oSettings.createTypes.onPreDisable] Check if the button should be disabled. If you set this option the option visibleInActiveFolderType is ignored
     * @param {string} [oSettings.createTypes.label] Label for the button (can be HTML)
     * @param {string} [oSettings.createTypes.cssClasses] Additional CSS class for the create button
     * @param {string} [oSettings.createTypes.toolTipTitle] Tooltip title
     * @param {string} [oSettings.createTypes.toolTipText] Tooltip text
     * @param {string} [oSettings.createCancel.label="Cancel"] Defines the cancel button text for the create mode
     * @param {string} [oSettings.createCancel.cssClasses] Additional CSS class for the cancel button
     * @param {string} [oSettings.toolbarButtons.labelBack="Cancel"] Default label for the cancel button of toolbar buttons
     * @param {object[]} [oSettings.toolbarButtons.items] The toolbar items
     * @param {string} oSettings.toolbarButtons.items.name Unique button identifier
     * @param {string} [oSettings.toolbarButtons.items.content] Content in the toolbar, best practice is to use a icon font
     * @param {string} [oSettings.toolbarButtons.items.cssClasses] Additional CSS classes added to the button
     * @param {array|string} [oSettings.toolbarButtons.items.visibleInActiveFolderType] Make the toolbar button clickable in this folders to create subfolders. The nodes (a-tags) needs a data-aio-type attribute
     * @param {function} [oSettings.toolbarButtons.items.onPreDisable] Check if the button should be disabled. If you set this option the option visibleInActiveFolderType is ignored.
     * @param {function} [oSettings.toolbarButtons.items.onClick] The button is clicked
     * @param {function} [oSettings.toolbarButtons.items.onCancel] If this option is set and the button is clicked there is a cancel button showed
     * @param {function} [oSettings.toolbarButtons.items.onAllowed] This function will be called before the onClick event and determines if the action can be accessed. It must return a boolean
     * @param {('rearrange'|'rename')} [oSettings.toolbarButtons.items.usePredefinedFunction] Use a predefined function
     * @param {string} [oSettings.toolbarButtons.items.toolTipTitle] Tooltip title
     * @param {string} [oSettings.toolbarButtons.items.toolTipText] Tooltip text
     * @param {string} [oSettings.toolbarButtons.items.toolTipTextDisabledLink] Tooltip text when the button is disabled (see visibleInActiveFolderType)
     * @param {string} [oSettings.toolbarButtons.items.labelBack] Overrides the default cancel text
     * @param {string} [oSettings.movement.selector=".aio-draggable-item"] The selector for the jQuery.draggable function
     * @param {function} [oSettings.movement.onDrop] Handle the onDrop functionality
     * @param {function} [oSettings.movement.onGetLabel] This function will be called instead of the "Move" label for the helper
     * @param {function} [oSettings.movement.onGetHelper] This function will be called instead of the <div class="aio-movement-helper"> output
     * @param {object} [oSettings.movement.draggableSettings] Draggable settings for jQuery draggable
     * @param {object} [oSettings.movement.droppableSettings] Droppable settings for jQuery droppable
     * @param {object} [oSettings.movement.customMovementHandler] Custom movement handler (see MovementHandler)
     * @param {int} [oSettings.others.rootParentId=-1] The parent root ID
     * @param {string} [oSettings.others.labelRenameSave="OK"] The button text when renaming a folder
     * @param {string} [oSettings.others.labelRearrangeSave="Save new order"] The button text when rearrange the folder hierarchy
     * @param {function} [oSettings.others.onExpand] This function will be called after a folder is expanded / collapsed
     * @param {function} [oSettings.others.onAfterFinish] This function will be called after the tree is initialized
     * @param {function} [oSettings.others.onSwitchFolder] This function will be called when an folder is clicked (a-tag)
     * @param {function} [oSettings.others.onCreateFolder] This function will be called when a new folder is created
     * @param {function} [oSettings.others.onTypeButtonClicked] This fucntion will be called when a type button is pressed (event cancel button)
     * @example <caption>How to instantiate a tree</caption>
     * $(".rml-container").allInOneTree({ your: config })
     * @memberof external:"jQuery.fn"
     * @see {@link external:"jQuery.ui"|Requires jQuery UI}
     * @see {@link external:"jQuery.fn.nestedSortable"|Requires jQuery UI Nested Sortable plugin}
     * @see {@link external:"jQuery.fn.tooltipster"|Requires jQuery Tooltipster plugin}
     * @see {@link external:"jQuery.fn.hcSticky"|Requires jQuery hcSticky plugin}
     */
    var AIOTree = /** @lends allInOneTree.prototype */ {
        options: {
            /*
             * First step: Define the container options.
             * 
             * @see ContainerHandler
             */
            container: {
                isCollapsable: true,
                isResizable: true,
                isSticky: true,
                theme: "",
                
                // Collapsable options
                collapsableLocalstorage: true,
                collapsableDefaultStatus: "open",
                
                // Resizable options
                defaultWidth: 250,
                resizeOpposite: null,
                customResizeHandler: false,
                onResizeOpposite: false,
                onResizeFinished: false,
                resizeTouch: false,
                resizeLocalStorage: true,
                resizeMin: 250,
                resizeMax: 800,
                resizeOppositeMin: 200,
                
                onRearrangeCancel: false,      
                onRearrangeRelocate: false,
                onRearrangeSave: false,        
                doNotUseSaveInRearrange: false,
                onDoTooltips: false,           
                tooltipsterSettings: { },      
                nestedSortableSettings: { },   
                hcStickySettings: { },         
                customLoaderHandler: false     
            },
            
            createTypes: [{
                name: "folder",
                icon: '<i class="fa fa-folder-open"></i><i class="fa fa-folder"></i>',
                visibleInActiveFolderType: false,
                onPreDisable: false,
                label: "New",        
                cssClasses: false,   
                toolTipTitle: "",
                toolTipText: ""
            }],
            
            createCancel: {
                label: "Cancel",
                cssClasses: ""
            },
            
            toolbarButtons: {
                items: [{
                    name: "rename",
                    content: '<i class="fa fa-pencil"></i>',
                    visibleInActiveFolderType: false,
                    usePredefinedFunction: "rename",
                    toolTipTitle: "Rename",
                    toolTipText: "Rename the current selected folder.",
                    toolTipTextDisabledLink: "This link is disabled! Please select a folder."
                }, {
                    name: "rearrange",                    
                    content: '<i class="fa fa-sort"></i>',
                    cssClasses: false,                    
                    visibleInActiveFolderType: false,     
                    onPreDisable: false,                  
                    onClick: false,                       
                    onCancel: false,                      
                    onAllowed: false,                     
                    usePredefinedFunction: "rearrange",   
                    toolTipTitle: "Rearrange",
                    toolTipText: "Change the hierarchical order of the folders."
                }],
                labelBack: "Cancel"
            },
            
            movement: {
                customMovementHandler: false,   
                selector: ".aio-draggable-item",
                onDrop: false,                  
                onGetLabel: false,              
                onGetHelper: false,             
                draggableSettings: { },         
                droppableSettings: { }          
            },
            
            /*
             * Allows a more flexible tree. The nodes are created from a JSON object and
             * a given template, so you CAN use the nodesJSON method instead of nodesHTML.
             * Also, instead of set a others.onExpand function (to load children)
             * or to set a others.onAfterFinish function (to load root children) you can
             * set a extendedMode.apiUrl.
             * 
             * @experimental
             * @TODO json aufbau erklären der API
             */
            extendedMode:{
                enabled: false,                                                 // Use the extended mode
                onCreateTemplate: false,                                        // FUNCTION this function will be called to generate the template and return simple HTML of a <li>                                                                                // If not setted, the default generator will be used
            },
            
            others: {
                rootParentId: -1,
                labelRenameSave: "OK",
                labelRearrangeSave: "Save new order",
                onExpand: false,
                onAfterFinish: false, 
                onSwitchFolder: false,
                onCreateFolder: false,
                onTypeButtonClicked: false
            }
        },
        
        /*
         * ID of the DOM element.
         * This id is for the localStorage bindings, for example the
         * collapsable states of each folder.
         */
        _id: "",
        
        /*
         * Class ID of the DOM element.
         * Build your document listeners to this ID.
         */
        _eventClassId: "",
        
        /*
         * Is the id generated or had the DOM
         * element already an ID?
         */
        _generatedId: false,
        
        /*
         * Local storage available?
         */
        _localStorage: false,
        
        /*
         * The used movement handler
         */
        _movementHandler: null,
        
        /*
         * The used type handler
         */
        _typesHandler: null,
        
        /*
         * The used container handler
         */
        _containerHandler: null,
        
        /*
         * The used loader handler
         */
        _loaderHandler: null,
        
        /*
         * The used toolbar handler
         */
        _toolbarHandler: null,
        
        /*
         * The search handler
         */
        _searchHandler: null,
        
        /*
         * .aio-list-standard container
         */
        _standardList: null,
        
        /*
         * .aio-nodes container
         */
        _nodes: null,
        
        /*
         * .aio-toolbar container
         */
        _toolbar: null,
        
        _create: function() {
            // Validate
            if (!this.element.hasClass("aio-tree")) {
                throw "Add class .aio-tree to the item.";
            }
            
            // Get standard list
            this._standardList = this.element.find(".aio-list-standard");
            if (this._standardList.length !== 1) {
                throw "Please define one .aio-standard-list container";
            }
            
            // Get nodes list
            this._nodes = this.element.find(".aio-list-standard .aio-nodes");
            if (this._standardList.length !== 1) {
                throw "Please define one .aio-list-standard > .aio-nodes container";
            }
            
            // Get toolbar
            this._toolbar = this.element.find(".aio-toolbar");
            if (this._toolbar.length !== 1) {
                throw "Please define one .aio-toolbar container. If you want to hide the toolbar, create one and hide it with display:none;.";
            }
            
            // We need draggable, droppable and sortable
            if (typeof $.fn.draggable !== "function" || typeof $.fn.sortable !== "function") {
                throw "Please include jQuery UI with draggable and sortable support (https://jqueryui.com/).";
            }
            
            this._eventClassId = this._guid();
            this.element.addClass("aio-tree-instance " + this._eventClassId);
            
            // Themefiy the tree
            var theme = this.options.container.theme, themeClass = [];
            if (this._defined(theme) && theme.length > 0) {
                var themeSplit = theme.split(" ");
                for (var i = 0; i < themeSplit.length; i++) {
                    themeClass.push("aio-theme-" + themeSplit[i]);
                }
                this.element.addClass(themeClass.join(" "));
            }else{
                this.element.addClass("aio-theme-default");
            }
            
            // Check (perhaps add) an ID to the container
            this._idGenerate();
            this._testLocalStorage();

            // Handle collapsable, sticky and resizable container
            this._containerHandler = new ContainerHandler(this);
            
            // Search handler
            this._searchHandler = new SearchHandler(this);
            
            // Handle create types
            this._typesHandler = new TypesHandler(this);
            
            // Handle drag and drop
            if (typeof this.options.movement.customMovementHandler === "object") {
                this._movementHandler = new this.options.customMovementHandler(this, MovementHandler); // Pass the default MovementHandler, too
            }else{
                this._movementHandler = new MovementHandler(this);
            }
            
            // Handle toolbar
            this._toolbarHandler = new ToolbarHandler(this);
            
            // Handle loader
            if (typeof this.options.container.customLoaderHandler === "object") {
                this._loaderHandler = new this.options.container.customLoaderHandler(this);
            }else{
                this._loaderHandler = new LoaderHandler(this);
            }
            
            /*
             * End initalizations
             */
            // Init active folder
            var activeFolder = this.active();
            if (activeFolder.length > 0) {
                this._typesHandler._doSwitchFolder(this, activeFolder, 0);
            }
             
            // Init tooltips (for createTypes)
            this._containerHandler._doTooltips(this);
            
            // Remove fade effect if wanted
            setTimeout(function() {
                this.element.removeClass("aio-tree-fade");
            }.bind(this), 50);
            
            // After init callback
            if (typeof this.options.others.onAfterFinish === "function") {
                (this.options.others.onAfterFinish.bind(this.element))();
            }
            
            // Sticky it
            this._containerHandler.sticky(this);
            
            this.element.data("allInOneTree", true);
            this._checkFolderContent();
            
            // Check if lazy loading, then show a loading indicator
            if (this.element.hasClass("aio-lazy")) {
                this.loader(true);
            }
        },
        
        /*
         * @experimental
         * @TODO runterziehen zu den anderen Methoden und doku
         */
        nodesJSON: function(data, isLazyLoadingResponse) {
            var newHTML = "";
            this.nodesHTML(newHTML, isLazyLoadingResponse);
        },
        
        /*
         * The default template generator
         */
        _onCreateTemplate: function() {
            
        },
        
        /*
         * =====================================================================
         *                      PUBLIC METHODS
         * =====================================================================
         */
        
        /**
         * Reinit all components of the tree.
         * 
         * Possible types: movement tooltips tree rearrange
         * 
         * @example
         * container.allInOneTree("reinit"); // => reinit all
         * container.allInOneTree("reinit", "movement"); // => reinit only movement
         * @param {string} [type] Only reinit a set of types. If you want to reinit multiple, seperate by space "movement tooltips"
         */
        reinit: function(type) {
            var allowAll = arguments.length == 0,
                types = allowAll ? [ "movement", "tooltips", "tree", "rearrange" ] : type.split(" ");

            if (types.indexOf("movement") > -1) {
                this._movementHandler.reinit(this);
            }
            if (types.indexOf("tooltips") > -1) {
                this._containerHandler._doTooltips(this);
            }
            if (types.indexOf("tree") > -1) {
                this._containerHandler._updateCollapsableNodes(this);
            }
            if (types.indexOf("rearrange") > -1) {
                var nodesUL = this._nodes.children("ul");
                if (nodesUL.data("nestedSortable")) {
                    this._toolbarHandler._doPredefinedrearrange(this, undefined, false);
                }
            }
            
            // Always reinit sticky container
            this._containerHandler.sticky(this);
            this._checkFolderContent();
            
            // Always reinit nodes
            this.nodes(this._nodesLastEnabled);
        },

        /**
         * Enable or disable the movement handler. If deactivated no items are moveable.
         * 
         * @param {boolean} state The state to enable or disable
         */
        movement: function(state) {
            this._movementHandler.state(this, state);
        },
        
        /**
         * Scroll to a given node object.
         * 
         * @param {jQuery} $object The jQuery object to scroll
         */
        nodeToViewPort: function($object) {
            if (jQuery.fn.scrollParent) {
                var win = $object.scrollParent(),
                    scroller = win.is(document) ? $("html,body") : win;
                win = win.is(document) ? $(window) : win;
                var winScrollTop = win.scrollTop(),
                    fixedHeader = this.element.find(".aio-reached-overflow"),
                    fixedHeaderTop = parseInt(fixedHeader.css("top") || 0),
                    fixedHeaderHeight = +fixedHeader.outerHeight() + fixedHeaderTop,
                    scrollPosition = winScrollTop + fixedHeaderHeight,
                    visibleArea = winScrollTop + win.height(),
                    objEndPos = ($object.offset().top + $object.outerHeight()),
                    directionDown = scrollPosition <= objEndPos,
                    directionUp = visibleArea >= objEndPos,
                    inViewPort = directionDown && directionUp;
                if (!inViewPort) {
                    var additional = $object.outerHeight() * 2;
                    scroller.stop().animate({
                        scrollTop: directionUp ? winScrollTop - additional : winScrollTop + additional
                    });
                }
            }else{
                console.warn("jQuery UI is not available (jQuery.scrollParent)");
            }
        },
        
        /**
         * Simulate a click for a toolbar button by name.
         * 
         * @param {string} name The unique identifier name of the toolbar button
         */
        toolbarButton: function(name) {
            if (name === "cancel") {
                // Deactive current cancelable
                var isCancelActive = this._toolbar.find("a.isCancelActive");
                if (isCancelActive.length === 1) {
                    this.toolbarButton(isCancelActive.attr("data-aio-name"));
                }
            }else{
                this._toolbarHandler._iterateButtons(this, function(button) {
                    if (button.name === name) {
                        if (typeof button.element === "object") {
                            button.element.click();
                        }
                        return false;
                    }
                }.bind(this));
            }
        },
        
        /**
         * Get the active folder or if you pass the first parameter to set active folder.
         * 
         * @param {any} [id] The id of the node (setter) or a jQuery object directly (a-tag of the folder)
         * @param {boolean} [avoidOnSwitchEvent] Set this to true if you call this function from the onSwitchFolder event for example
         * @param {boolean} [reveal] If true the window is scrolled to the newly activated item and reveals the tree
         * @returns {jQuery|boolean} jQuery object if getter or boolean if setter
         * @example <caption>Set active folder to ID 123</caption>
         * container.allInOneTree("active", "123");
         * $active = container.allInOneTree("active"); // => 123 a-tag is returned
         */
        active: function(id, avoidOnSwitchEvent, reveal) {
            var active = this._standardList.find("a.active");
            if (typeof id !== "undefined") {
                var newActive;
                
                if (typeof id === "object") {
                    newActive = id;
                }else{
                    newActive = this.byId(id);
                }
                
                if (typeof newActive === "object" && newActive.length > 0) {
                    // Switch activate
                    active.removeClass("active");
                    newActive.addClass("active");
                    
                    // Reveal
                    if (reveal) {
                        var parentable = newActive, closeable;
                        while ((closeable = parentable.parents("li.aio-close")).length > 0) {
                            closeable.children(".aio-expander").click();
                        }
                    }
                    
                    // Do switch event
                    this._typesHandler._doSwitchFolder(this, newActive);
                    if (typeof this.options.others.onSwitchFolder === "function" && avoidOnSwitchEvent !== true) {
                        return (this.options.others.onSwitchFolder.bind(this.element))(newActive, typeof active === "object" ? active.attr("data-aio-id") : undefined);
                    }
                    return true;
                }else{
                    return false;
                }
            }else{
                return active;
            }
        },
        
        /**
         * Change the toolbar loader to a specific state. Have a look at
         * the example to learn how to use it.
         * 
         * @example
         * $(...).allInOneTree("loader", "show");
         * $(...).allInOneTree("loader", "hide");
         * $(...).allInOneTree("loader", true);
         * $(...).allInOneTree("loader", false);
         * $(...).allInOneTree("loader", "89"); => 89%
         * $(...).allInOneTree("loader", 50, 500); => 50 / 500 * 100 = 10%
         */
        loader: function() {
            return this._loaderHandler.fromArguments(arguments);
        },
        
        /**
         * Simply enable or disable the links in the tree.
         * 
         * @param {boolean} enabled Enable or disable the links
         */
        nodes: function(enabled) {
            var sDeactivated = "javascript:void(0)";
            this._nodesLastEnabled = enabled;
            this._standardList.find("a").each(function() {
                if (enabled) {
            	    $(this).attr("href", $(this).attr("data-aio-href")).removeClass("aio-disable-link");
                }else if ($(this).attr("href") !== sDeactivated) {
                	$(this).attr("data-aio-href", $(this).attr("href")).addClass("aio-disable-link");
                	$(this).attr("href", sDeactivated);
                }
            });
        },
        _nodesLastEnabled: true,
        
        /**
         * Paste new HTML to the nodes list or get the nodes HTML. For example
         * if you change the hierarchical order (rearrange). If you change
         * it be sure that you reinit() AFTER your toolbarButton click.
         * 
         * Please do not paste HTML via jQuery yourself!
         * 
         * @param {string} [newHTML] HTML string putted into .aio-nodes > ul
         * @param {boolean} [isLazyLoadingResponse] If true the response of newHTML is regarding lazy loading and the .aio-lazy is removed from the tree classes
         * @param {any} [setAsActive] Set this folder as active
         * @returns {string} If you pass no paramter the HTML of the nodes will be returned
         */
        nodesHTML: function(newHTML, isLazyLoadingResponse, setAsActive) {
            var nodesUL = this._nodes.children("ul"), active = this.active(), activeId = active.attr("data-aio-id");
            if (nodesUL.length === 0) {
                throw "There is no <ul></ul> available in the .aio-nodes container!";
            }
            
            var fLazy = function() {
                // Reactive the active so the toolbar buttons are setted right
                if (isLazyLoadingResponse) {
                    this.element.removeClass("aio-lazy");
                    this.loader(false);
                    
                    if (setAsActive !== undefined) {
                        this.active(setAsActive, true);
                    }else if (active.length > 0) {
                        this.active(activeId, true);
                    }
                }
            }.bind(this);
            
            if (newHTML) {
                nodesUL.data("nodesHTML", newHTML);
                nodesUL.html(newHTML);
                this.reinit();
                fLazy();
            }else{
                fLazy();
                return nodesUL.html();
            }
        },
        
        /**
         * Updates the counts of the given folder ID array.
         * 
         * @param {object[]} arr Multiple folders, key is folder id and value is folder count
         */
        counts: function(arr) {
            if (typeof arr === "object") {
                var folder;
                $.each(arr, function(key, value) {
                    folder = this.byId(key);
                    if (folder !== false) {
                        folder.find(".aio-cnt").attr("class", "aio-cnt aio-cnt-" + value).html(value);
                    }
                }.bind(this));
            } 
        },
        
        /**
         * Get an folder by id. This includes standard list nodes, too.
         * 
         * @param {any} id The id of the folder
         * @returns {jQuery}
         */
        byId: function(id) {
            if (typeof id !== "undefined") {
                return this._standardList.find('a[data-aio-id="' + id + '"]');
            }else{
                return false;
            }
        },
        
        /**
         * Rename an element. <strong>Note:</strong> If you are using only one parameter (newName) the
         * active folder will be renamed!
         * 
         * @param {jQuery|any} [element] The id of the element or a jQuery object of the a-tag
         * @param {string} newName The new name
         * @returns {boolean}
         */
        rename: function(element, newName) {
            var obj;
            // Get the element
            if (arguments.length == 1) {
                obj = this.active();
                newName = element;
            }else if (arguments.length == 2) {
                obj = element instanceof jQuery ? element : this.byId(element);
            }else{
                return false;
            }
            
            // Change the name if element is found (<a>)
            if (obj instanceof jQuery) {
                obj.children(".aio-node-name").text(newName);
                var input = obj.children("input"); // Check if rename input exists!
                if (input.length > 0) {
                    input.val(newName);
                }
                return true;
            }else{
                return false;
            }
        },
        
        /**
         * Delete an element. <strong>Note:</strong> If you are using no parameter the
         * active folder will be deleted!
         * 
         * @param {jQuery|any} [element] The id of the element or a jQuery object of the a-tag
         * @returns {boolean}
         */
        remove: function(element) {
            var obj;
            // Get the element
            if (arguments.length == 1 && typeof element === "object") {
                obj = element instanceof jQuery ? element : this.byId(element);
            }else{
                obj = this.active();
            }
            
            // Delete it
            if (obj instanceof jQuery) {
                // Remove the list object
                var li = obj.parent(),
                    parentOfLi = li.parent().parent(),
                    nextActive = false,
                    active = this.active(),
                    activeID = active.attr("data-aio-id");
                
                if (active.length > 0 && activeID) { // We have active object!
                    if (active.is(obj)) {
                        // Select first or parent folder if we delete the active folder
                        if (parentOfLi.is("li")) {
                            // Select parent
                            nextActive = parentOfLi.children("a");
                        }else{
                            nextActive = "firstNode";
                        }
                    }else if (parentOfLi.children("ul").find('a[data-aio-id="' + activeID + '"]').length > 0) { // Check if active is in deleted parent
                        nextActive = "firstNode";
                    }
                }
                
                // Remove it!
                li.remove();
                
                // check if the first node should be taken
                if (nextActive === "firstNode") {
                    if (this._nodes.find("a").length > 0) {
                        nextActive = this._nodes.find('a:first');
                    }else if (this._standardList.find("a").length > 0) {
                        nextActive = this._standardList.find("a:first");
                    }
                }
                
                // Set next active
                if (nextActive instanceof jQuery) {
                    this.active(nextActive);
                }
                
                // Reinit the tree
                this.reinit("tree");
                return true;
            }else{
                return false;
            }
        },
        
        /**
         * Parses the nestedSortable to an object array.
         * 
         * @returns {object} { raw: original from nestedSortable, parsed: arraid for FID and PID } or false
         */
        serialize: function() {
            var nodesUL = this._nodes.children("ul"), th = this._toolbarHandler,
                alreadyNestedSortableActive;
            if (nodesUL.length > 0) {
                alreadyNestedSortableActive = nodesUL.data("nestedSortable");
                if (!alreadyNestedSortableActive) { // activate
                    th._doPredefinedrearrangeActivate(nodesUL, this);
                }
                var parsed = th._doPredefinedrearrangeParse(this, nodesUL); // get result
                if (!alreadyNestedSortableActive) { // deactivate
                    nodesUL.nestedSortable("destroy");
                }
                
                return parsed;
            }else{
                return false;
            }
        },
        
        /*
         * Parses the breadcrumb to the current folder.
         * You can define a return type which defines the
         * type of breadcrumb.
         * 
         * array: returns the breadcrumbs with its names in an array
         * string: return the breadcrumbs joined with a "/" into a string
         * objects: return the breadcrumbs with all folder details in an object array
         * 
         * @param returnType "array"|"string"|"objects"
         * @return your defined returnType
         */
        breadcrumb: function(returnType) {
            var active = this.active();
            if (active) {
                // Get the array for the current active folder
                var toTop = [], isStandard = !active.parents(".aio-nodes").length > 0;
                
                // iterate the hierarchy
                var iterateHierarchy = function(cb) {
                    while (active !== false) {
                        active = active.parent() // => LI
                                        .parent() // => UL
                                        .parent() // => LI OF PARENT FOLDER
                                        .children('a[data-aio-id]');
                        if (active.length > 0) {
                            cb();
                        }else{
                            active = false;
                        }
                    }
                };
                
                // define return types
                if (returnType === "objects") {
                    // We need a more complex array (object array)
                    var getFolderInfo = function(aTag) {
                        return {
                            id: aTag.attr("data-aio-id"),
                            type: aTag.attr("data-aio-type"),
                            name: aTag.children(".aio-node-name").text(),
                            obj: aTag,
                            link: aTag.attr("href")
                        };
                    };
                    toTop.push(getFolderInfo(active)); // The current active
                    if (!isStandard) {
                        iterateHierarchy(function() {
                            toTop.push(getFolderInfo(active));
                        });
                    }
                    toTop.reverse();
                    
                    // Return the objects
                    return toTop;
                }else{
                    // We need only a single array
                    toTop.push(active.children(".aio-node-name").text()); // The current active
                    if (!isStandard) {
                        iterateHierarchy(function() {
                            toTop.push(active.children(".aio-node-name").text());
                        });
                    }
                    toTop.reverse();
                    
                    if (!returnType || returnType === "array") {
                        if (!isStandard) {
                            toTop.unshift("/");
                        }
                        return toTop;
                    }
                    if (returnType === "string") {
                        var result = toTop.join("/");
                        if (!isStandard) {
                            result = "/" + result;
                        }
                        return result;
                    }
                }
            }
            return "";
        },
        
        /*
         * =====================================================================
         *                      PRIVATE METHODS
         * =====================================================================
         */
        
        /*
         * If there are no folders add the no-content.
         */
        _checkFolderContent: function() {
            this.element.toggleClass("aio-no-folders", this._nodes.find("li").length === 0);
        },
        
        _lsCache: undefined,
        _lsSet: function(path, value) {
            var collection = this._lsGet();
            this._objectPathSet(collection, path, value);
            window.localStorage.setItem("AIO" + this._id, JSON.stringify(collection));
            this._lsCache = undefined;
        },
        
        _lsGet: function(path) {
            if (!this._lsCache) {
                this._lsCache = jQuery.parseJSON(window.localStorage.getItem("AIO" + this._id) || "{}");
            }
            return path ? this._objectPathGet(this._lsCache, path) : this._lsCache;
        },
        
        _objectPathGet: function(object, path) {
            var aNames = (path || "").split(".");
    		for (var i = 0; object && i < aNames.length; i++) {
    			object = object[aNames[i]];
    		}
    		return object;
        },
        
        _objectPathSet: function(object, path, value) {
            var l, aNames = (path || "").split(".");
    		if ( ( l = aNames.length ) > 0 ) {
    			for (var i = 0; object && i < l - 1; i++) {
    				if (!object[aNames[i]] ) {
    					object[aNames[i]] = {};
    				}
    				object = object[aNames[i]];
    			}
    			object[aNames[l - 1]] = value;
    		}
        },
        
        /*
         * Detects, if the localStorage is available.
         * 
         * @see _localStorage
         */
        _testLocalStorage: function() {
            var test = 'test';
            try {
                var _localStorage = window["localStorage"];
                _localStorage.setItem(test, test);
                _localStorage.removeItem(test);
                this._localStorage = true;
            } catch(e) { }
        },
        
        /*
         * Assign the ID to the tree
         * If the object has a ID use this one, otherwise
         * use a generated one.
         * 
         * @param item the jQuery item it should append (optional)
         * @return if param item is given then return a generated id
         */
        _idGenerate: function(item) {
            var id;
            if (item instanceof jQuery) {
                if (this._hasAttr("id", item)) {
                    return item.attr("id");
                }else{
                    id = this._guid();
                    item.attr("id", id);
                    return id;
                }
            }else{
                var e = this.element;
                if (this._hasAttr("id")) {
                    id = e.attr("id");
                }else{
                    id = this._guid();
                    e.attr("id", id);
                    this._generatedId = true;
                }
                this._id = id;
            }
        },
        
        /*
         * Checks, if a given jQuery object has an
         * attribute. If you pass no obj, the element is used.
         * 
         * @param name the name of the attribute
         * @param obj (optional) jQuery object
         * @return boolean
         */
        _hasAttr: function(name, obj) {
            var e = obj instanceof jQuery ? obj : this.element,
                attr = e.attr(name);
            return typeof attr !== typeof undefined && attr !== false;
        },
        
        /*
         * This function returns a jQuery object
         * with one/multiple elements. The selector
         * can be a jQuery object, a string or a function.
         * 
         * @param obj string|jQuery object|function
         * @param noJquery set to true => only string and function is allowed and output is no jQuery object
         * @return jQuery object or undefined
         */
        _parseElements: function(obj, noJquery) {
            if (typeof obj === "string") {
                return noJquery !== true ? $(obj) : obj;
            }else if (typeof obj === "function") {
                return (obj.bind(this.element))(jQuery);
            }else if (obj instanceof jQuery && noJquery !== true) {
                return obj;
            }else{
                return undefined;
            }
        },
        
        _defined: function(obj) {
            return typeof obj !== typeof undefined && obj !== false && obj !== null;
        },
        
        /*
         * Checks if the current device is touch and returns
         * an array for mousedown mouseup and mousemove event names.
         */
        _isTouch: function () {
          var isTouch = 'ontouchstart' in window || window.navigator.maxTouchPoints;
          if (isTouch) {
              return { touch: isTouch, up: "touchend", down: "touchstart", move: "touchmove" };
          }else{
              return { touch: isTouch, up: "mouseup", down: "mousedown", move: "mousemove" };
          }
        },
        
        /*
         * Inject a style for a given type.
         */
        _injectStyle: function(type, rule) {
            var id = this._id + "-style-" + type;
            $("#" + id).remove();
            var div = $("<div />", {
                html: '&shy;<style>' + rule + '</style>'
            }).attr("id", id).hide().appendTo("body");
            return div;
        },
        
        /*
         * GUID generator
         */
        _guid: function() {
          function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
              .toString(16)
              .substring(1);
          }
          return "aio-" + s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
        }
    };

    $.widget('mguenter.allInOneTree', AIOTree);
})(jQuery);