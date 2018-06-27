/*!
 * @license
 * Copyright MatthiasWeb (Matthias Günter)
 * https://matthias-web.com
 */
/* global jQuery rmlOpts */

/*
 * Formats a message with {0}, {1}... placeholders with the passed arguments.
 * 
 * @param {string} message The message to format
 * @param {string} arg1... Multiple arguments
 * @returns {string} Formatted string
 */
function RMLFormat() {
    var args = arguments;
    return args[0].replace(/{(\d+)}/g, function(match, number) { 
      return typeof args[(+number)+1] != 'undefined'
        ? args[(+number)+1]
        : match
      ;
    });
}

function RMLWpIs(name) {
    return typeof window.wp !== "undefined" && typeof window.wp[name] !== "undefined";
}

function RMLisAIO(object) {
    return object instanceof jQuery && document.body.contains(object[0]) && object.data("allInOneTree");
}

function RMLDebug(message /*, messages ... */) {
    if (typeof rmlOpts === "object" && rmlOpts.debug) {
        var args = jQuery.makeArray(arguments);
        args.unshift("[RML_DEBUG]");
        if (console.debug) {
            console.debug.apply(console, args);
        }else if (console.log.apply) {
            console.log.apply(console, args);
        }
    }
}

function RMLFindDeep(obj, path) {
    var paths = path.split('.'), current = obj;
    for (var i = 0; i < paths.length; ++i) {
        if (current[paths[i]] == undefined) {
            return undefined;
        } else {
            current = current[paths[i]];
        }
    }
    return current;
}

/* @see http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript */
function RMLUrlParams(aIgnore) {
    return (function(a) {
        if (a == "") return {};
        var b = {};
        for (var i = 0; i < a.length; ++i)
        {
            var p=a[i].split('=', 2);
            if (p.length != 2) continue;
            if (aIgnore && aIgnore.length > 0 && jQuery.inArray(p[0], aIgnore) > -1) continue;
            b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, " "));
        }
        return b;
    })(window.location.search.substr(1).split('&'));
}

/* Function.prototype.bind polyfill */
Function.prototype.bind=(function(){}).bind||function(b){if(typeof this!=="function"){throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");}function c(){}var a=[].slice,f=a.call(arguments,1),e=this,d=function(){return e.apply(this instanceof c?this:b||window,f.concat(a.call(arguments)));};c.prototype=this.prototype;d.prototype=new c();return d;};

/* ReplaceWith should return the new object */
if (typeof jQuery.fn.replaceWithPush !== "function") {
    jQuery.fn.replaceWithPush = function(a) {
        var $ = jQuery, $a = $(a);
        this.replaceWith($a);
        return $a;
    };
}

/**
 * Hook System for Real Media Library plugin.
 * 
 * @namespace window.rml.hooks
 */
var RML_HOOK = {
    hooks: [],
    
    /**
     * Hooks a function on to a specific action.
     * 
     * @param {string} name The name of the event. Just have a look at the doc events.
     * @param {function} callback The callback with the arguments passed by the .call function. The last argument is jQuery ($)
     * @memberof window.rml.hooks
     * @method register
     */
    register: function(name, callback) {
        var names = name.split(" "),
            curName;
        for (var i = 0; i < names.length; i++) {
            curName = names[i];
            if ('undefined' == typeof(RML_HOOK.hooks[curName]))
                RML_HOOK.hooks[curName] = [];
            RML_HOOK.hooks[curName].push(callback);
        }
    },
    
    /**
     * Execute functions hooked on a specific action hook.
     * 
     * @param {string} name The name of the event. Just have a look at the doc events or create your own.
     * @param {mixed[]} args The arguments passed to the callback
     * @param {object} [context] The context passed to the callback
     * @memberof window.rml.hooks
     * @method call
     */
    call: function(name, args, context) {
        RMLDebug("Call js hook '" + name + "' with arguments", args);
        if ('undefined' != typeof(RML_HOOK.hooks[name])) {
            for (var i = 0; i < RML_HOOK.hooks[name].length; ++i) {
                if (typeof args === "object") {
                    if (Object.prototype.toString.call(args) === '[object Array]') {
                        args.push(jQuery);
                    }else{
                        args = [args, jQuery];
                    }
                    
                    if (false == RML_HOOK.hooks[name][i].apply(context, args)) {
                        break;
                    } 
                }else{
                    if (false == RML_HOOK.hooks[name][i].apply(context, [ jQuery ])) {
                        break;
                    }
                }
            }
        }
    },
    
    /**
     * Checks if a specific action hook exists.
     * 
     * @param {string} name The name of the event. Just have a look at the doc events or create your own.
     * @memberof window.rml.hooks
     * @method exists
     */
    exists: function(name) {
        return 'undefined' != typeof(RML_HOOK.hooks[name]);
    }
};

/*
 * General informations
 */
window.rml = {
    hooks: RML_HOOK,
    typeAccept: { }
}