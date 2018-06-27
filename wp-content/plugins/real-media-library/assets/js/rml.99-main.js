/* global jQuery rmlOpts RMLFindDeep RMLFormat sweetAlert RMLUrlParams */

(function( $ ) {
    $(function() { // Mini javascript libraries
        if (RMLUrlParams().post_type !== "inbound-email") { // Inbound Now Pro compatibility, perhaps they update sweetalert because they are using a very old version...
            /*! Sweet alert | MIT | 2014 Tristan Edwards */
            !function(e,t,n){"use strict";!function o(e,t,n){function a(s,l){if(!t[s]){if(!e[s]){var i="function"==typeof require&&require;if(!l&&i)return i(s,!0);if(r)return r(s,!0);var u=new Error("Cannot find module '"+s+"'");throw u.code="MODULE_NOT_FOUND",u}var c=t[s]={exports:{}};e[s][0].call(c.exports,function(t){var n=e[s][1][t];return a(n?n:t)},c,c.exports,o,e,t,n)}return t[s].exports}for(var r="function"==typeof require&&require,s=0;s<n.length;s++)a(n[s]);return a}({1:[function(o,a,r){var s,l,i,u,c=function(e){return e&&e.__esModule?e:{"default":e}},d=o("./modules/handle-dom"),f=o("./modules/utils"),p=o("./modules/handle-swal-dom"),m=o("./modules/handle-click"),v=o("./modules/handle-key"),y=c(v),h=o("./modules/default-params"),g=c(h),b=o("./modules/set-params"),w=c(b);r["default"]=i=u=function(){function o(e){var t=a;return t[e]===n?g["default"][e]:t[e]}var a=arguments[0];if(d.addClass(t.body,"stop-scrolling"),p.resetInput(),a===n)return f.logStr("SweetAlert expects at least 1 attribute!"),!1;var r=f.extend({},g["default"]);switch(typeof a){case"string":r.title=a,r.text=arguments[1]||"",r.type=arguments[2]||"";break;case"object":if(a.title===n)return f.logStr('Missing "title" argument!'),!1;r.title=a.title;for(var i in g["default"])r[i]=o(i);r.confirmButtonText=r.showCancelButton?"Confirm":g["default"].confirmButtonText,r.confirmButtonText=o("confirmButtonText"),r.doneFunction=arguments[1]||null;break;default:return f.logStr('Unexpected type of argument! Expected "string" or "object", got '+typeof a),!1}w["default"](r),p.fixVerticalPosition(),p.openModal(arguments[1]);for(var c=p.getModal(),v=c.querySelectorAll("button"),h=["onclick","onmouseover","onmouseout","onmousedown","onmouseup","onfocus"],b=function(e){return m.handleButton(e,r,c)},C=0;C<v.length;C++)for(var S=0;S<h.length;S++){var x=h[S];v[C][x]=b}p.getOverlay().onclick=b,s=e.onkeydown;var k=function(e){return y["default"](e,r,c)};e.onkeydown=k,e.onfocus=function(){setTimeout(function(){l!==n&&(l.focus(),l=n)},0)},u.enableButtons()},i.setDefaults=u.setDefaults=function(e){if(!e)throw new Error("userParams is required");if("object"!=typeof e)throw new Error("userParams has to be a object");f.extend(g["default"],e)},i.close=u.close=function(){var o=p.getModal();d.fadeOut(p.getOverlay(),5),d.fadeOut(o,5),d.removeClass(o,"showSweetAlert"),d.addClass(o,"hideSweetAlert"),d.removeClass(o,"visible");var a=o.querySelector(".sa-icon.sa-success");d.removeClass(a,"animate"),d.removeClass(a.querySelector(".sa-tip"),"animateSuccessTip"),d.removeClass(a.querySelector(".sa-long"),"animateSuccessLong");var r=o.querySelector(".sa-icon.sa-error");d.removeClass(r,"animateErrorIcon"),d.removeClass(r.querySelector(".sa-x-mark"),"animateXMark");var i=o.querySelector(".sa-icon.sa-warning");return d.removeClass(i,"pulseWarning"),d.removeClass(i.querySelector(".sa-body"),"pulseWarningIns"),d.removeClass(i.querySelector(".sa-dot"),"pulseWarningIns"),setTimeout(function(){var e=o.getAttribute("data-custom-class");d.removeClass(o,e)},300),d.removeClass(t.body,"stop-scrolling"),e.onkeydown=s,e.previousActiveElement&&e.previousActiveElement.focus(),l=n,clearTimeout(o.timeout),!0},i.showInputError=u.showInputError=function(e){var t=p.getModal(),n=t.querySelector(".sa-input-error");d.addClass(n,"show");var o=t.querySelector(".sa-error-container");d.addClass(o,"show"),o.querySelector("p").innerHTML=e,setTimeout(function(){i.enableButtons()},1),t.querySelector("input").focus()},i.resetInputError=u.resetInputError=function(e){if(e&&13===e.keyCode)return!1;var t=p.getModal(),n=t.querySelector(".sa-input-error");d.removeClass(n,"show");var o=t.querySelector(".sa-error-container");d.removeClass(o,"show")},i.disableButtons=u.disableButtons=function(e){var t=p.getModal(),n=t.querySelector("button.confirm"),o=t.querySelector("button.cancel");n.disabled=!0,o.disabled=!0},i.enableButtons=u.enableButtons=function(e){var t=p.getModal(),n=t.querySelector("button.confirm"),o=t.querySelector("button.cancel");n.disabled=!1,o.disabled=!1},"undefined"!=typeof e?e.sweetAlert=e.swal=i:f.logStr("SweetAlert is a frontend module!"),a.exports=r["default"]},{"./modules/default-params":2,"./modules/handle-click":3,"./modules/handle-dom":4,"./modules/handle-key":5,"./modules/handle-swal-dom":6,"./modules/set-params":8,"./modules/utils":9}],2:[function(e,t,n){var o={title:"",text:"",type:null,allowOutsideClick:!1,showConfirmButton:!0,showCancelButton:!1,closeOnConfirm:!0,closeOnCancel:!0,confirmButtonText:"OK",confirmButtonColor:"#8CD4F5",cancelButtonText:"Cancel",imageUrl:null,imageSize:null,timer:null,customClass:"",html:!1,animation:!0,allowEscapeKey:!0,inputType:"text",inputPlaceholder:"",inputValue:"",showLoaderOnConfirm:!1};n["default"]=o,t.exports=n["default"]},{}],3:[function(t,n,o){var a=t("./utils"),r=(t("./handle-swal-dom"),t("./handle-dom")),s=function(t,n,o){function s(e){m&&n.confirmButtonColor&&(p.style.backgroundColor=e)}var u,c,d,f=t||e.event,p=f.target||f.srcElement,m=-1!==p.className.indexOf("confirm"),v=-1!==p.className.indexOf("sweet-overlay"),y=r.hasClass(o,"visible"),h=n.doneFunction&&"true"===o.getAttribute("data-has-done-function");switch(m&&n.confirmButtonColor&&(u=n.confirmButtonColor,c=a.colorLuminance(u,-.04),d=a.colorLuminance(u,-.14)),f.type){case"mouseover":s(c);break;case"mouseout":s(u);break;case"mousedown":s(d);break;case"mouseup":s(c);break;case"focus":var g=o.querySelector("button.confirm"),b=o.querySelector("button.cancel");m?b.style.boxShadow="none":g.style.boxShadow="none";break;case"click":var w=o===p,C=r.isDescendant(o,p);if(!w&&!C&&y&&!n.allowOutsideClick)break;m&&h&&y?l(o,n):h&&y||v?i(o,n):r.isDescendant(o,p)&&"BUTTON"===p.tagName&&sweetAlert.close()}},l=function(e,t){var n=!0;r.hasClass(e,"show-input")&&(n=e.querySelector("input").value,n||(n="")),t.doneFunction(n),t.closeOnConfirm&&sweetAlert.close(),t.showLoaderOnConfirm&&sweetAlert.disableButtons()},i=function(e,t){var n=String(t.doneFunction).replace(/\s/g,""),o="function("===n.substring(0,9)&&")"!==n.substring(9,10);o&&t.doneFunction(!1),t.closeOnCancel&&sweetAlert.close()};o["default"]={handleButton:s,handleConfirm:l,handleCancel:i},n.exports=o["default"]},{"./handle-dom":4,"./handle-swal-dom":6,"./utils":9}],4:[function(n,o,a){var r=function(e,t){return new RegExp(" "+t+" ").test(" "+e.className+" ")},s=function(e,t){r(e,t)||(e.className+=" "+t)},l=function(e,t){var n=" "+e.className.replace(/[\t\r\n]/g," ")+" ";if(r(e,t)){for(;n.indexOf(" "+t+" ")>=0;)n=n.replace(" "+t+" "," ");e.className=n.replace(/^\s+|\s+$/g,"")}},i=function(e){var n=t.createElement("div");return n.appendChild(t.createTextNode(e)),n.innerHTML},u=function(e){e.style.opacity="",e.style.display="block"},c=function(e){if(e&&!e.length)return u(e);for(var t=0;t<e.length;++t)u(e[t])},d=function(e){e.style.opacity="",e.style.display="none"},f=function(e){if(e&&!e.length)return d(e);for(var t=0;t<e.length;++t)d(e[t])},p=function(e,t){for(var n=t.parentNode;null!==n;){if(n===e)return!0;n=n.parentNode}return!1},m=function(e){e.style.left="-9999px",e.style.display="block";var t,n=e.clientHeight;return t="undefined"!=typeof getComputedStyle?parseInt(getComputedStyle(e).getPropertyValue("padding-top"),10):parseInt(e.currentStyle.padding),e.style.left="",e.style.display="none","-"+parseInt((n+t)/2)+"px"},v=function(e,t){if(+e.style.opacity<1){t=t||16,e.style.opacity=0,e.style.display="block";var n=+new Date,o=function(e){function t(){return e.apply(this,arguments)}return t.toString=function(){return e.toString()},t}(function(){e.style.opacity=+e.style.opacity+(new Date-n)/100,n=+new Date,+e.style.opacity<1&&setTimeout(o,t)});o()}e.style.display="block"},y=function(e,t){t=t||16,e.style.opacity=1;var n=+new Date,o=function(e){function t(){return e.apply(this,arguments)}return t.toString=function(){return e.toString()},t}(function(){e.style.opacity=+e.style.opacity-(new Date-n)/100,n=+new Date,+e.style.opacity>0?setTimeout(o,t):e.style.display="none"});o()},h=function(n){if("function"==typeof MouseEvent){var o=new MouseEvent("click",{view:e,bubbles:!1,cancelable:!0});n.dispatchEvent(o)}else if(t.createEvent){var a=t.createEvent("MouseEvents");a.initEvent("click",!1,!1),n.dispatchEvent(a)}else t.createEventObject?n.fireEvent("onclick"):"function"==typeof n.onclick&&n.onclick()},g=function(t){"function"==typeof t.stopPropagation?(t.stopPropagation(),t.preventDefault()):e.event&&e.event.hasOwnProperty("cancelBubble")&&(e.event.cancelBubble=!0)};a.hasClass=r,a.addClass=s,a.removeClass=l,a.escapeHtml=i,a._show=u,a.show=c,a._hide=d,a.hide=f,a.isDescendant=p,a.getTopMargin=m,a.fadeIn=v,a.fadeOut=y,a.fireClick=h,a.stopEventPropagation=g},{}],5:[function(t,o,a){var r=t("./handle-dom"),s=t("./handle-swal-dom"),l=function(t,o,a){var l=t||e.event,i=l.keyCode||l.which,u=a.querySelector("button.confirm"),c=a.querySelector("button.cancel"),d=a.querySelectorAll("button[tabindex]");if(-1!==[9,13,32,27].indexOf(i)){for(var f=l.target||l.srcElement,p=-1,m=0;m<d.length;m++)if(f===d[m]){p=m;break}9===i?(f=-1===p?u:p===d.length-1?d[0]:d[p+1],r.stopEventPropagation(l),f.focus(),o.confirmButtonColor&&s.setFocusStyle(f,o.confirmButtonColor)):13===i?("INPUT"===f.tagName&&(f=u,u.focus()),f=-1===p?u:n):27===i&&o.allowEscapeKey===!0?(f=c,r.fireClick(f,l)):f=n}};a["default"]=l,o.exports=a["default"]},{"./handle-dom":4,"./handle-swal-dom":6}],6:[function(n,o,a){var r=function(e){return e&&e.__esModule?e:{"default":e}},s=n("./utils"),l=n("./handle-dom"),i=n("./default-params"),u=r(i),c=n("./injected-html"),d=r(c),f=".sweet-alert",p=".sweet-overlay",m=function(){var e=t.createElement("div");for(e.innerHTML=d["default"];e.firstChild;)t.body.appendChild(e.firstChild)},v=function(e){function t(){return e.apply(this,arguments)}return t.toString=function(){return e.toString()},t}(function(){var e=t.querySelector(f);return e||(m(),e=v()),e}),y=function(){var e=v();return e?e.querySelector("input"):void 0},h=function(){return t.querySelector(p)},g=function(e,t){var n=s.hexToRgb(t);e.style.boxShadow="0 0 2px rgba("+n+", 0.8), inset 0 0 0 1px rgba(0, 0, 0, 0.05)"},b=function(n){var o=v();l.fadeIn(h(),10),l.show(o),l.addClass(o,"showSweetAlert"),l.removeClass(o,"hideSweetAlert"),e.previousActiveElement=t.activeElement;var a=o.querySelector("button.confirm");a.focus(),setTimeout(function(){l.addClass(o,"visible")},500);var r=o.getAttribute("data-timer");if("null"!==r&&""!==r){var s=n;o.timeout=setTimeout(function(){var e=(s||null)&&"true"===o.getAttribute("data-has-done-function");e?s(null):sweetAlert.close()},r)}},w=function(){var e=v(),t=y();l.removeClass(e,"show-input"),t.value=u["default"].inputValue,t.setAttribute("type",u["default"].inputType),t.setAttribute("placeholder",u["default"].inputPlaceholder),C()},C=function(e){if(e&&13===e.keyCode)return!1;var t=v(),n=t.querySelector(".sa-input-error");l.removeClass(n,"show");var o=t.querySelector(".sa-error-container");l.removeClass(o,"show")},S=function(){var e=v();e.style.marginTop=l.getTopMargin(v())};a.sweetAlertInitialize=m,a.getModal=v,a.getOverlay=h,a.getInput=y,a.setFocusStyle=g,a.openModal=b,a.resetInput=w,a.resetInputError=C,a.fixVerticalPosition=S},{"./default-params":2,"./handle-dom":4,"./injected-html":7,"./utils":9}],7:[function(e,t,n){var o='<div class="sweet-overlay" tabIndex="-1"></div><div class="sweet-alert"><div class="sa-icon sa-error">\n      <span class="sa-x-mark">\n        <span class="sa-line sa-left"></span>\n        <span class="sa-line sa-right"></span>\n      </span>\n    </div><div class="sa-icon sa-warning">\n      <span class="sa-body"></span>\n      <span class="sa-dot"></span>\n    </div><div class="sa-icon sa-info"></div><div class="sa-icon sa-success">\n      <span class="sa-line sa-tip"></span>\n      <span class="sa-line sa-long"></span>\n\n      <div class="sa-placeholder"></div>\n      <div class="sa-fix"></div>\n    </div><div class="sa-icon sa-custom"></div><h2>Title</h2>\n    <p>Text</p>\n    <fieldset>\n      <input type="text" tabIndex="3" />\n      <div class="sa-input-error"></div>\n    </fieldset><div class="sa-error-container">\n      <div class="icon">!</div>\n      <p>Not valid!</p>\n    </div><div class="sa-button-container">\n      <button class="cancel" tabIndex="2">Cancel</button>\n      <div class="sa-confirm-button-container">\n        <button class="confirm" tabIndex="1">OK</button><div class="la-ball-fall">\n          <div></div>\n          <div></div>\n          <div></div>\n        </div>\n      </div>\n    </div></div>';n["default"]=o,t.exports=n["default"]},{}],8:[function(e,t,o){var a=e("./utils"),r=e("./handle-swal-dom"),s=e("./handle-dom"),l=["error","warning","info","success","input","prompt"],i=function(e){var t=r.getModal(),o=t.querySelector("h2"),i=t.querySelector("p"),u=t.querySelector("button.cancel"),c=t.querySelector("button.confirm");if(o.innerHTML=e.html?e.title:s.escapeHtml(e.title).split("\n").join("<br>"),i.innerHTML=e.html?e.text:s.escapeHtml(e.text||"").split("\n").join("<br>"),e.text&&s.show(i),e.customClass)s.addClass(t,e.customClass),t.setAttribute("data-custom-class",e.customClass);else{var d=t.getAttribute("data-custom-class");s.removeClass(t,d),t.setAttribute("data-custom-class","")}if(s.hide(t.querySelectorAll(".sa-icon")),e.type&&!a.isIE8()){var f=function(){for(var o=!1,a=0;a<l.length;a++)if(e.type===l[a]){o=!0;break}if(!o)return logStr("Unknown alert type: "+e.type),{v:!1};var i=["success","error","warning","info"],u=n;-1!==i.indexOf(e.type)&&(u=t.querySelector(".sa-icon.sa-"+e.type),s.show(u));var c=r.getInput();switch(e.type){case"success":s.addClass(u,"animate"),s.addClass(u.querySelector(".sa-tip"),"animateSuccessTip"),s.addClass(u.querySelector(".sa-long"),"animateSuccessLong");break;case"error":s.addClass(u,"animateErrorIcon"),s.addClass(u.querySelector(".sa-x-mark"),"animateXMark");break;case"warning":s.addClass(u,"pulseWarning"),s.addClass(u.querySelector(".sa-body"),"pulseWarningIns"),s.addClass(u.querySelector(".sa-dot"),"pulseWarningIns");break;case"input":case"prompt":c.setAttribute("type",e.inputType),c.value=e.inputValue,c.setAttribute("placeholder",e.inputPlaceholder),s.addClass(t,"show-input"),setTimeout(function(){c.focus(),c.addEventListener("keyup",swal.resetInputError)},400)}}();if("object"==typeof f)return f.v}if(e.imageUrl){var p=t.querySelector(".sa-icon.sa-custom");p.style.backgroundImage="url("+e.imageUrl+")",s.show(p);var m=80,v=80;if(e.imageSize){var y=e.imageSize.toString().split("x"),h=y[0],g=y[1];h&&g?(m=h,v=g):logStr("Parameter imageSize expects value with format WIDTHxHEIGHT, got "+e.imageSize)}p.setAttribute("style",p.getAttribute("style")+"width:"+m+"px; height:"+v+"px")}t.setAttribute("data-has-cancel-button",e.showCancelButton),e.showCancelButton?u.style.display="inline-block":s.hide(u),t.setAttribute("data-has-confirm-button",e.showConfirmButton),e.showConfirmButton?c.style.display="inline-block":s.hide(c),e.cancelButtonText&&(u.innerHTML=s.escapeHtml(e.cancelButtonText)),e.confirmButtonText&&(c.innerHTML=s.escapeHtml(e.confirmButtonText)),e.confirmButtonColor&&(c.style.backgroundColor=e.confirmButtonColor,c.style.borderLeftColor=e.confirmLoadingButtonColor,c.style.borderRightColor=e.confirmLoadingButtonColor,r.setFocusStyle(c,e.confirmButtonColor)),t.setAttribute("data-allow-outside-click",e.allowOutsideClick);var b=e.doneFunction?!0:!1;t.setAttribute("data-has-done-function",b),e.animation?"string"==typeof e.animation?t.setAttribute("data-animation",e.animation):t.setAttribute("data-animation","pop"):t.setAttribute("data-animation","none"),t.setAttribute("data-timer",e.timer)};o["default"]=i,t.exports=o["default"]},{"./handle-dom":4,"./handle-swal-dom":6,"./utils":9}],9:[function(t,n,o){var a=function(e,t){for(var n in t)t.hasOwnProperty(n)&&(e[n]=t[n]);return e},r=function(e){var t=/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(e);return t?parseInt(t[1],16)+", "+parseInt(t[2],16)+", "+parseInt(t[3],16):null},s=function(){return e.attachEvent&&!e.addEventListener},l=function(t){e.console&&e.console.log("SweetAlert: "+t)},i=function(e,t){e=String(e).replace(/[^0-9a-f]/gi,""),e.length<6&&(e=e[0]+e[0]+e[1]+e[1]+e[2]+e[2]),t=t||0;var n,o,a="#";for(o=0;3>o;o++)n=parseInt(e.substr(2*o,2),16),n=Math.round(Math.min(Math.max(0,n+n*t),255)).toString(16),a+=("00"+n).substr(n.length);return a};o.extend=a,o.hexToRgb=r,o.isIE8=s,o.logStr=l,o.colorLuminance=i},{}]},{},[1]),"function"==typeof define&&define.amd?define(function(){return sweetAlert}):"undefined"!=typeof module&&module.exports&&(module.exports=sweetAlert)}(window,document);
        }
        
        /*!
         * jQuery HC-Sticky
         * =============
         * Version: 1.2.43
         * Copyright: Some Web Media
         * Author: Some Web Guy
         * Author URL: http://twitter.com/some_web_guy
         * Website: http://someweblog.com/
         * Plugin URL: https://github.com/somewebmedia/hc-sticky
         * License: Released under the MIT License www.opensource.org/licenses/mit-license.php
         * Description: Cross-browser jQuery plugin that makes any element attached to the page and always visible while you scroll.
         */
        !function(t){"use strict";var o=t(window),i=window.document,e=t(i),n=function(){for(var t,o=3,e=i.createElement("div"),n=e.getElementsByTagName("i");e.innerHTML="<!--[if gt IE "+ ++o+"]><i></i><![endif]-->",n[0];);return o>4?o:t}(),s=function(){var t=void 0!==window.pageXOffset?window.pageXOffset:"CSS1Compat"==i.compatMode?window.document.documentElement.scrollLeft:window.document.body.scrollLeft,o=void 0!==window.pageYOffset?window.pageYOffset:"CSS1Compat"==i.compatMode?window.document.documentElement.scrollTop:window.document.body.scrollTop;"undefined"==typeof s.x&&(s.x=t,s.y=o),"undefined"==typeof s.distanceX?(s.distanceX=t,s.distanceY=o):(s.distanceX=t-s.x,s.distanceY=o-s.y);var e=s.x-t,n=s.y-o;s.direction=0>e?"right":e>0?"left":0>=n?"down":n>0?"up":"first",s.x=t,s.y=o};o.on("scroll",s),t.fn.style=function(o){if(!o)return null;var e,n=t(this),s=n.clone().css("display","none");s.find("input:radio").attr("name","copy-"+Math.floor(100*Math.random()+1)),n.after(s);var a=function(t,o){var e;return t.currentStyle?e=t.currentStyle[o.replace(/-\w/g,function(t){return t.toUpperCase().replace("-","")})]:window.getComputedStyle&&(e=i.defaultView.getComputedStyle(t,null).getPropertyValue(o)),e=/margin/g.test(o)?parseInt(e)===n[0].offsetLeft?e:"auto":e};return"string"==typeof o?e=a(s[0],o):(e={},t.each(o,function(t,o){e[o]=a(s[0],o)})),s.remove(),e||null},t.fn.extend({hcSticky:function(i){return 0==this.length?this:(this.pluginOptions("hcSticky",{top:0,bottom:0,bottomEnd:0,innerTop:0,innerSticker:null,className:"sticky",wrapperClassName:"wrapper-sticky",stickTo:null,responsive:!0,followScroll:!0,offResolutions:null,onStart:t.noop,onStop:t.noop,on:!0,fn:null},i||{},{reinit:function(){t(this).hcSticky()},stop:function(){t(this).pluginOptions("hcSticky",{on:!1}).each(function(){var o=t(this),i=o.pluginOptions("hcSticky"),e=o.parent("."+i.wrapperClassName),n=o.offset().top-e.offset().top;o.css({position:"absolute",top:n,bottom:"auto",left:"auto",right:"auto"}).removeClass(i.className)})},off:function(){t(this).pluginOptions("hcSticky",{on:!1}).each(function(){var o=t(this),i=o.pluginOptions("hcSticky"),e=o.parent("."+i.wrapperClassName);o.css({position:"relative",top:"auto",bottom:"auto",left:"auto",right:"auto"}).removeClass(i.className),e.css("height","auto")})},on:function(){t(this).each(function(){t(this).pluginOptions("hcSticky",{on:!0,remember:{offsetTop:o.scrollTop()}}).hcSticky()})},destroy:function(){var i=t(this),e=i.pluginOptions("hcSticky"),n=i.parent("."+e.wrapperClassName);i.removeData("hcStickyInit").css({position:n.css("position"),top:n.css("top"),bottom:n.css("bottom"),left:n.css("left"),right:n.css("right")}).removeClass(e.className),o.off("resize",e.fn.resize).off("scroll",e.fn.scroll),i.unwrap()}}),i&&"undefined"!=typeof i.on&&(i.on?this.hcSticky("on"):this.hcSticky("off")),"string"==typeof i?this:this.each(function(){var i=t(this),a=i.pluginOptions("hcSticky"),r=function(){var t=i.parent("."+a.wrapperClassName);return t.length>0?(t.css({height:i.outerHeight(!0),width:function(){var o=t.style("width");return o.indexOf("%")>=0||"auto"==o?("border-box"==i.css("box-sizing")||"border-box"==i.css("-moz-box-sizing")?i.css("width",t.width()):i.css("width",t.width()-parseInt(i.css("padding-left")-parseInt(i.css("padding-right")))),o):i.outerWidth(!0)}()}),t):!1}()||function(){var o=i.style(["width","margin-left","left","right","top","bottom","float","display"]),e=i.css("display"),s=t("<div>",{"class":a.wrapperClassName}).css({display:e,height:i.outerHeight(!0),width:function(){return o.width.indexOf("%")>=0||"auto"==o.width&&"inline-block"!=e&&"inline"!=e?(i.css("width",parseFloat(i.css("width"))),o.width):"auto"!=o.width||"inline-block"!=e&&"inline"!=e?"auto"==o["margin-left"]?i.outerWidth():i.outerWidth(!0):i.width()}(),margin:o["margin-left"]?"auto":null,position:function(){var t=i.css("position");return"static"==t?"relative":t}(),"float":o["float"]||null,left:o.left,right:o.right,top:o.top,bottom:o.bottom,"vertical-align":"top"});return i.wrap(s),7===n&&0===t("head").find("style#hcsticky-iefix").length&&t('<style id="hcsticky-iefix">.'+a.wrapperClassName+" {zoom: 1;}</style>").appendTo("head"),i.parent()}();if(!i.data("hcStickyInit")){i.data("hcStickyInit",!0);var c=a.stickTo&&("document"==a.stickTo||a.stickTo.nodeType&&9==a.stickTo.nodeType||"object"==typeof a.stickTo&&a.stickTo instanceof("undefined"!=typeof HTMLDocument?HTMLDocument:Document))?!0:!1,l=a.stickTo?c?e:"string"==typeof a.stickTo?t(a.stickTo):a.stickTo:r.parent();i.css({top:"auto",bottom:"auto",left:"auto",right:"auto"}),o.load(function(){i.outerHeight(!0)>l.height()&&(r.css("height",i.outerHeight(!0)),i.hcSticky("reinit"))});var p=function(t){i.hasClass(a.className)||(t=t||{},i.css({position:"fixed",top:t.top||0,left:t.left||r.offset().left}).addClass(a.className),a.onStart.apply(i[0]),r.addClass("sticky-active"))},f=function(t){t=t||{},t.position=t.position||"absolute",t.top=t.top||0,t.left=t.left||0,("fixed"==i.css("position")||parseInt(i.css("top"))!=t.top)&&(i.css({position:t.position,top:t.top,left:t.left}).removeClass(a.className),a.onStop.apply(i[0]),r.removeClass("sticky-active"))},d=function(e){if(a.on&&i.is(":visible")){if(i.outerHeight(!0)>=l.height())return void f();var n,d=a.innerSticker?t(a.innerSticker).position().top:a.innerTop?a.innerTop:0,u=r.offset().top,h=l.height()-a.bottomEnd+(c?0:u),m=r.offset().top-a.top+d,g=i.outerHeight(!0)+a.bottom,y=o.height(),w=o.scrollTop(),v=i.offset().top,b=v-w;if("undefined"!=typeof a.remember&&a.remember){var k=v-a.top-d;return void(g-d>y&&a.followScroll?w>k&&w+y<=k+i.height()&&(a.remember=!1):a.remember.offsetTop>k?k>=w&&(p({top:a.top-d}),a.remember=!1):w>=k&&(p({top:a.top-d}),a.remember=!1))}w>m?h+a.bottom-(a.followScroll&&g>y?0:a.top)<=w+g-d-(g-d>y-(m-d)&&a.followScroll&&(n=g-y-d)>0?n:0)?f({top:h-g+a.bottom-u}):g-d>y&&a.followScroll?y>=b+g?"down"==s.direction?p({top:y-g}):0>b&&"fixed"==i.css("position")&&f({top:v-(m+a.top-d)-s.distanceY}):"up"==s.direction&&v>=w+a.top-d?p({top:a.top-d}):"down"==s.direction&&v+g>y&&"fixed"==i.css("position")&&f({top:v-(m+a.top-d)-s.distanceY}):p({top:a.top-d}):f()}},u=!1,h=!1,m=function(){if(y(),g(),a.on){var t=function(){"fixed"==i.css("position")?i.css("left",r.offset().left):i.css("left",0)};if(a.responsive){h||(h=i.clone().attr("style","").css({visibility:"hidden",height:0,overflow:"hidden",paddingTop:0,paddingBottom:0,marginTop:0,marginBottom:0}),r.after(h));var o=r.style("width"),e=h.style("width");"auto"==e&&"auto"!=o&&(e=parseInt(i.css("width"))),e!=o&&r.width(e),u&&clearTimeout(u),u=setTimeout(function(){u=!1,h.remove(),h=!1},250)}if(t(),i.outerWidth(!0)!=r.width()){var n="border-box"==i.css("box-sizing")||"border-box"==i.css("-moz-box-sizing")?r.width():r.width()-parseInt(i.css("padding-left"))-parseInt(i.css("padding-right"));n=n-parseInt(i.css("margin-left"))-parseInt(i.css("margin-right")),i.css("width",n)}}};i.pluginOptions("hcSticky",{fn:{scroll:d,resize:m}});var g=function(){if(a.offResolutions){t.isArray(a.offResolutions)||(a.offResolutions=[a.offResolutions]);var e=!0;t.each(a.offResolutions,function(t,n){0>n?o.width()<-1*n&&(e=!1,i.hcSticky("off")):o.width()>n&&(e=!1,i.hcSticky("off"))}),e&&!a.on&&i.hcSticky("on")}};g(),o.on("resize",m);var y=function(){var i=!1;void 0!=t._data(window,"events").scroll&&t.each(t._data(window,"events").scroll,function(t,o){o.handler==a.fn.scroll&&(i=!0)}),i||(a.fn.scroll(!0),o.on("scroll",a.fn.scroll))};y()}}))}})}(jQuery,this),function(t){"use strict";t.fn.extend({pluginOptions:function(o,i,e,n){return this.data(o)||this.data(o,{}),o&&"undefined"==typeof i?this.data(o).options:(e=e||i||{},"object"==typeof e||void 0===e?this.each(function(){var s=t(this);s.data(o).options?s.data(o,t.extend(s.data(o),{options:t.extend(s.data(o).options,e||{})})):(s.data(o,{options:t.extend(i,e||{})}),n&&(s.data(o).commands=n))}):"string"==typeof e?this.each(function(){t(this).data(o).commands[e].call(this)}):this)}})}(jQuery);

    });
})(jQuery);

/**
 * This first fired event. It is not similar to jQuery(document).ready().
 * 
 * @event window.rml.hooks#general
 */
window.rml.hooks.call("general");

jQuery(document).ready(function() {
    if (!rmlOpts) {
        return;
    }
    
    // Initialize the uploader
    if (RMLFindDeep(window, "wp.media")) {
        window.rml.uploader.init();
    }
    
    var $ = jQuery, container, isListMode = rmlOpts.listMode == "list"; // Our whole container
    window.rml.sweetAlert = sweetAlert;
    
    // Add rml-touch class if touch device
    if ('ontouchstart' in window || window.navigator.maxTouchPoints) {
        $("body").addClass("rml-touch");
    }
    
    // Dismiss a upgrade notice
    $(document).on("click", ".rml-migration-dismiss", function(event) {
        var build = $(this).attr("data-build");
        
        jQuery.post(
            rmlOpts.ajaxUrl,
            {
                'action': 'rml_migrate_dismiss',
                'nonce' : rmlOpts.nonces.migrateDismiss,
                'build' : build
            }
        );
        
        $(this).parents(".notice").slideUp();
        event.preventDefault();
        return false;
    });
    
    /**
     * DOM-Ready hook.
     * 
     * @event window.rml.hooks#ready
     */
    window.rml.hooks.call("ready");
    
    /*
     * Create the AllInOne jquery plugin settings for the default media library.
     */
    window.rml.defaultAioSettings = {
        container: {
            listMode: rmlOpts.listMode,
            isMediaLibrary: false,
            isModalMode: false,
            tooltipsterSettings: {
                speed: 0
            },
            theme: "wordpress",
            onRearrangeRelocate: function(id, parentsID, deferred, prevId, nextId, totalPrevId, totalNextId, args) {
                var args = arguments;
                
                jQuery.post(
                    rmlOpts.ajaxUrl,
                    {
                        'action': 'rml_relocate',
                        'nonce' : rmlOpts.nonces.bulkSort, // Use same nonce
                        'id' : id,
                        'parent': parentsID,
                        'nextId': nextId
                    },
                    function(response){
                        if (response.success) {
                            deferred.resolve();
                            
                            /**
                             * The folder hierarchy is changed and a folder is relocated. The arguments passed
                             * to this event are the same as the callback function of {@link allInOneTree} "onRearrangeRelocate".
                             * 
                             * @event window.rml.hooks#folder/relocated
                             * @this allInOneTree
                             */
                            window.rml.hooks.call("folder/relocated", args, this);
                        }else{
                            deferred.reject();
                            sweetAlert("Oops...", response.data, "error");
                        }
                    }.bind(this)
                );
            },
            doNotUseSaveInRearrange: true
        },
        toolbarButtons: {
            items: [{
                name: "order",
                content: '<i class="fa fa-arrows"></i>',
                labelBack: rmlOpts.lang.done,
                onPreDisable: function() {
                    // Check, if the active folder has a custom order enabled
                    var active = $(this).allInOneTree("active");
                    return active && active.attr("data-content-custom-order") !== "2";
                },
                onAllowed: function() {
                    // Show error if the current view has filters.
                    if ($("body").hasClass("rml-view-gallery-filter-on")) {
                        window.rml.sweetAlert("Oops...", rmlOpts.lang.orderFailedFilterOn, "error");
                        return false;
                    }
                    
                    // There is no content custom order, yet
                    var active = $(this).allInOneTree("active"),
                        contentCustomOrder = active.attr("data-content-custom-order"),
                        fAsk = function(onSubmit) {
                        sweetAlert({
                            title: rmlOpts.lang.sortConfirmTitle,   
                            text: rmlOpts.lang.sortConfirmText,   
                            showCancelButton: true,   
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: rmlOpts.lang.sortConfirmTitle,
                            cancelButtonText: rmlOpts.lang.cancel,
                            imageUrl: rmlOpts.pluginUrl + "assets/image/find-details.png",
                            html: true
                        }, onSubmit);
                    }.bind(this), _listMode = $(this).allInOneTree("option", "container.listMode");
                    
                    // Table mode
                    if (_listMode === "list" && rmlOpts.wpListModeOrder !== "1" && typeof window.rml.library.wpTableList !== "undefined") {
                        if (contentCustomOrder !== "1") {
                            fAsk(function() {
                                window.location.href = rmlOpts.wpListModeOrder;
                            });
                        }else{
                            window.location.href = rmlOpts.wpListModeOrder;
                        }
                        return false;
                    }else if (_listMode !== "list" && contentCustomOrder !== "1") { // Grid mode
                        fAsk(function() {
                            active.attr("data-content-custom-order", "1");
                            $(this).allInOneTree("toolbarButton", "refresh");
                        }.bind(this));
                    }
                    return true;
                },
                onClick: function() {
                    // Show the sortable
                    var backbone = window.rml.library.getBackboneOfAIO($(this));
                    $(this).allInOneTree("movement", false);
                    if (backbone.view) {
                        backbone.view.attachments.refreshSortable(true);
                    }else{
                        /*
                         * There is no view. Then it is definitivly
                         * the list table.
                         */
                        var list = window.rml.library.wpTableList;
                        if (typeof list !== "undefined") {
                            list.sortable("enable");
                            list.disableSelection();
                        }
                    }
                },
                onCancel: function() {
                    var backbone = window.rml.library.getBackboneOfAIO($(this));
                    $(this).allInOneTree("movement", true);
                    if (backbone.view) {
                        backbone.view.attachments.refreshSortable(false);
                    }else{
                        /*
                         * There is no view. Then it is definitivly
                         * the list table.
                         */
                        var list = window.rml.library.wpTableList;
                        if (typeof list !== "undefined") {
                            list.sortable("disable");
                            list.enableSelection();
                        }
                    }
                },
                toolTipTitle: rmlOpts.lang.toolbarItems.order.toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems.order.toolTipText,
                toolTipTextDisabledLink: rmlOpts.lang.toolbarItems.order.toolTipTextDisabledLink
            }, {
                name: "refresh",
                content: '<i class="fa fa-refresh"></i>',
                onClick: function() {
                    /**
                     * The tree button "Refresh" is pressed.
                     * 
                     * @event window.rml.hooks#tree/refresh
                     * @this allInOneTree
                     */
                    window.rml.hooks.call("tree/refresh", false, this);
                },
                toolTipTitle: rmlOpts.lang.toolbarItems.refresh.toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems.refresh.toolTipText
            },{
                name: "rename",
                content: '<i class="fa fa-pencil"></i>',
                visibleInActiveFolderType: [ "0", "1", "2" ],
                /*
                 * RENAME PROCESS
                 */
                onSave: function(folderId, newName) {
                    $(this).allInOneTree("loader", true);
                    jQuery.post(
                        rmlOpts.ajaxUrl, 
                        {
                            'action': 'rml_folder_rename',
                            'nonce' : rmlOpts.nonces.folderRename,
                            'name' : newName,
                            'id' : folderId
                        },
                        function(response){
                            $(this).allInOneTree("loader", false);
                            if (response.success) {
                                $(this).allInOneTree("toolbarButton", "rename");
                                
                                /**
                                 * A folder is renamed.
                                 * 
                                 * @event window.rml.hooks#folder/renamed
                                 * @property {string} folderId The folder id
                                 * @property {string} newName The new name for the folder
                                 * @property {string} slug The new slug for the folder
                                 * @this allInOneTree
                                 */
                                window.rml.hooks.call("folder/renamed", [ folderId, newName, response.data.slug ], this);
                            }else{
                                sweetAlert("Oops...", response.data.join(), "error");
                            }
                        }.bind(this)
                    );
                },
                usePredefinedFunction: "rename",
                toolTipTitle: rmlOpts.lang.toolbarItems.rename.toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems.rename.toolTipText,
                toolTipTextDisabledLink: rmlOpts.lang.toolbarItems.rename.toolTipTextDisabledLink
            }, {
                name: "delete",
                content: '<i class="fa fa-trash-o"></i>',
                visibleInActiveFolderType: [ "0", "1", "2" ],
                onClick: function() {
                    var toDelete = $(this).allInOneTree("active");

                    // Get data
                    var slug = toDelete.attr("data-slug"),
                        id = parseInt(toDelete.attr("data-aio-id"));
                    if (!slug) return;
                    
                    if (toDelete.parent().children("ul").children("li").length > 0) {
                        // There are subfolders
                        sweetAlert("Oops...", rmlOpts.lang.deleteFailed, "error");
                    }else{
                        sweetAlert({   
                            title: rmlOpts.lang.deleteConfirmTitle,   
                            text: rmlOpts.lang.deleteConfirm,   
                            type: "warning",   
                            showCancelButton: true,   
                            confirmButtonColor: "#DD6B55",   
                            confirmButtonText: rmlOpts.lang.deleteConfirmSubmit,   
                            cancelButtonText: rmlOpts.lang.deleteConfirmCancel,
                            closeOnConfirm: false
                        }, function(){
                            $(this).allInOneTree("loader", true);
                            
                            // We first delete it and then send post (UX, it can not fail?!)
                            jQuery.post(
                                rmlOpts.ajaxUrl, 
                                {
                                    'action': 'rml_folder_delete',
                                    'nonce' : rmlOpts.nonces.folderDelete,
                                    'id' : id
                                },
                                function(response){
                                    $(this).allInOneTree("loader", false);
                                    if (response.success) {
                                        $(this).allInOneTree("remove", toDelete);
                                        
                                        /**
                                         * A folder is removed.
                                         * 
                                         * @property {string} id The folder id
                                         * @event window.rml.hooks#folder/removed
                                         * @this allInOneTree
                                         */
                                        window.rml.hooks.call("folder/removed", id, this);
                                    }else{
                                        sweetAlert("Oops...", response.data.join(), "error");
                                    }
                                }.bind(this)
                            );
                            sweetAlert.close();
                        }.bind(this));
                    }
                },
                toolTipTitle: rmlOpts.lang.toolbarItems["delete"].toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems["delete"].toolTipText,
                toolTipTextDisabledLink: rmlOpts.lang.toolbarItems["delete"].toolTipTextDisabledLink
            }, {
                name: "rearrange",
                content: '<i class="fa fa-sort"></i>',
                usePredefinedFunction: "rearrange",
                labelBack: rmlOpts.lang.done,
                toolTipTitle: rmlOpts.lang.toolbarItems.rearrange.toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems.rearrange.toolTipText,
                onAllowed: function() {
                    if ($(this).hasClass("aio-searching")) {
                        window.rml.sweetAlert("Oops...", rmlOpts.lang.searchRearrange, "error");
                        return false;
                    }
                    return true;
                }
            }, {
                name: "details",
                content: '<i class="fa fa-chevron-circle-down"></i>',
                visibleInActiveFolderType: [ "0", "1", "2", "4" ],
                toolTipTitle: rmlOpts.lang.toolbarItems.details.toolTipTitle,
                toolTipText: rmlOpts.lang.toolbarItems.details.toolTipText,
                toolTipTextDisabledLink: rmlOpts.lang.toolbarItems.details.toolTipTextDisabledLink,
                onAllowed: function() {
                    // Show error if the current view has filters.
                    if (window.rml.library.isMediaLibrary !== true) {
                        window.rml.sweetAlert("Oops...", rmlOpts.lang.detailsFailedOn, "error");
                        return false;
                    }else if ($(".rml-meta-media-picker").length > 0) {
                        if ($(".rml-meta-media-picker").parents(".sweet-alert.hideSweetAlert").length <= 0) {
                            // It is media library, check if already opened
                            window.rml.sweetAlert("Oops...", rmlOpts.lang.detailsFailedAlreadyOpen, "error");
                            return false;
                        }
                    }
                    return true;
                },
                /*
                 * META DATA OPEN PROCESS
                 */
                onClick: function() {
                    var container = $(this),
                        active = $(this).allInOneTree("active"),
                        name = active.find(".aio-node-name").text(),
                        id = active.attr("data-aio-id");
                        
                    window.rml.sweetAlert({
                        title: '',
                        text: '<div class="spinner is-active"></div><br /><br />',
                        html: true,
                        showConfirmButton: false
                    });
                    
                    $.ajax({
                        url: rmlOpts.ajaxUrl,
                        data: {
                            action: "rml_meta_content",
                            nonce: rmlOpts.nonces.metaContent,
                            folderId: id
                        },
                        invokeData: {
                            fid: id,
                            name: name
                        },
                        success: function(response) {
                            /**
                             * The user opens the folder details and the folder data is loaded from the 
                             * server. The folder metadata is not yet parsed to the dialog HTML.
                             * 
                             * @property {string} response The HTML response from the server
                             * @property {string} id The folder id
                             * @property {string} name The folder name
                             * @event window.rml.hooks#metadata/loaded
                             * @this allInOneTree
                             */
                            window.rml.hooks.call("metadata/loaded", [ response, this.invokeData.fid, this.invokeData.name ], container);
                        }
                    });
                }
            }],
            labelBack: rmlOpts.lang.cancel
        },
        createTypes: [{
            type: "0",
            name: "folder",
            icon: '<i class="fa fa-folder-open"></i><i class="fa fa-folder"></i>',
            cssClasses: "page-title-action add-new-h2",
            visibleInActiveFolderType: ["0", "3", "4"],
            label: '<i class="fa fa-folder-open-o"></i>&nbsp;<i class="fa fa-plus"></i>',
            toolTipTitle: rmlOpts.lang.createTypes.folder.toolTipTitle,
            toolTipText: rmlOpts.lang.createTypes.folder.toolTipText
        },{
            type: "1",
            name: "collection",
            icon: '<i class="mwf-collection"></i>',
            cssClasses: "page-title-action add-new-h2",
            visibleInActiveFolderType: ["0", "1", "3", "4"],
            label: '<i class="mwf-collection"></i>&nbsp;&nbsp;<i class="fa fa-plus"></i>',
            toolTipTitle: rmlOpts.lang.createTypes.collection.toolTipTitle,
            toolTipText: rmlOpts.lang.createTypes.collection.toolTipText
        },{
            type: "2",
            name: "gallery",
            icon: '<i class="mwf-gallery"></i>',
            cssClasses: "page-title-action add-new-h2",
            visibleInActiveFolderType: ["1"],
            label: '<i class="mwf-gallery"></i>&nbsp;&nbsp;<i class="fa fa-plus"></i>',
            toolTipTitle: rmlOpts.lang.createTypes.gallery.toolTipTitle,
            toolTipText: rmlOpts.lang.createTypes.gallery.toolTipText
        }],
        createCancel: {
            label: rmlOpts.lang.cancel,
            cssClasses: "page-title-action add-new-h2"
        },
        movement: {
            selector: "#wpbody-content ul.attachments > li, .wp-list-table.media tbody tr",
            onGetLabel: function(draggable) {
                // Parse the item ids
                var draggableObjects = $();
                window.rml.library.iterateDraggedItems(jQuery(draggable), $(this), function(tr) {
                    $.merge(draggableObjects, tr);
                }, function(attributes) {
                    $.merge(draggableObjects, (this.browser.find('li[data-id="'  + attributes.id + '"]')));
                });
                var items = draggableObjects.length > 0 ? draggableObjects.length : 1;
                
                // If there is a shortcut item, add class to body
                if (draggableObjects.filter(".rml-shortcut").length > 0) {
                    $("body").addClass("rml-holding-shortcuts");
                }else{
                    $("body").removeClass("rml-holding-shortcuts");
                }
                $("body").removeClass("rml-holding");
                
                // Prepare label
                var labelMove = items > 1 ? rmlOpts.lang.moveMultipleFiles : rmlOpts.lang.moveSingleFile,
                    labelAppend = items > 1 ? rmlOpts.lang.appendMultipleFiles : rmlOpts.lang.appendSingleFile,
                    containerMove = '<div class="rml-movement-move"><i class="fa fa-arrow-right" style="margin-right:5px;"></i> ' + RMLFormat(labelMove, items) + '</div>',
                    containerAppend = '<div class="rml-movement-append"><i class="fa fa-share rml-shortcut-icon" style="margin-right:5px;"></i> ' + RMLFormat(labelAppend, items) + '</div>';
                return containerMove + containerAppend;
            },
            onGetHelper: function(draggableLabel) {
                return $('<div class="aio-movement-helper rml-movement-helper">' + draggableLabel + '</div>');
            },
            droppableSettings: {
                /*
                 * Register your custom type to the window.rml.typeAccept object.
                 * It is a function that returns a boolean.
                 * 
                 * @see window.rml.typeAccept
                 */
                accept: function(obj) {
                    var result, container = $(this).parents(".aio-tree");
                    try {
                        var type = $(this).attr("data-aio-type");
                        var func = window.rml.typeAccept[type];
                        result = (func.bind(this))(obj, container);
                    }catch(e) {}
                    return result;
                },
                /*
                 * MOVE PROCESS
                 */
                drop: function(event, ui) {
                    $(event.target).addClass("needs-refresh");
                    var container = $(this).parents(".aio-tree"),
                        ids = [], isShortcut = $("body").hasClass("rml-holding"),
                        folderId = $(event.target).attr("data-aio-id"),
                        activeId = container.allInOneTree("active").attr("data-aio-id"),
                        removeElements = [ ],
                        removeElementsIterate = function(cb) {
                            for (var i = 0; i < removeElements.length; i++) {
                                (cb.bind(removeElements[i]))();
                            }
                        };
                    
                    // Parse the item ids and remove them
                    window.rml.library.iterateDraggedItems(ui.draggable, container, function(tr) {
                        ids.push(parseInt(tr.find('input[name="media[]"]').attr("value")));
                        removeElements.push(tr);
                    }, function(attributes) {
                        ids.push(attributes.id);
                        removeElements.push(this.browser.find('li[data-id="'  + attributes.id + '"]'));
                    });
                    
                    // The function to progress the move
                    var doIt = function() {
                        container.allInOneTree("loader", true);
                        removeElementsIterate(function() {
                            $(this).fadeTo(250, 0.3);
                        });
                        
                        /**
                         * Move or copy attachments to a folder. This hook is called before server request.
                         * 
                         * @property {int[]} ids The attachment ids to move
                         * @property {string} folderId The destination folder id
                         * @property {boolean} isShortcut True if the attachments are copied to the destination folder
                         * @event window.rml.hooks#attachments/move
                         * @this allInOneTree
                         */
                        window.rml.hooks.call("attachments/move", [ ids, folderId, isShortcut], container);
                        
                        jQuery.post(
                            rmlOpts.ajaxUrl, 
                            {
                                'action': 'rml_bulk_move',
                                'nonce': rmlOpts.nonces.bulkMove,
                                'ids': ids,
                                'to': folderId,
                                'isShortcut': isShortcut
                            },
                            function(response) {
                                if (response.success) {
                                    // Refresh if needed
                                    if ((activeId == "" && isShortcut) || (isShortcut && activeId == folderId)) {
                                        container.allInOneTree("toolbarButton", "refresh");
                                    }
                                    
                                    removeElementsIterate(function() {
                                        if (!container.data("isListMode") && $(this).hasClass("selected") && $(this).children(".attachment-preview").length > 0) {
                                            $(this).children(".attachment-preview").click(); // Deselect for the next bulk select action
                                        }
                                        
                                        // Remove, or not
                                        if (isShortcut || (!isShortcut && activeId == folderId) || activeId == "") {
                                            $(this).fadeTo(0, 1);
                                        }else{
                                            // Remove from view
                                            $(this).remove();
                                        }
                                    });
                                    
                                    if (container.data("isListMode")) {
                                        // Add no media
                                        if ($(".wp-list-table.media tbody tr").length <= 0) {
                                            $(".wp-list-table.media tbody").html('<tr class="no-items"><td class="colspanchange" colspan="6">' + rmlOpts.lang.noMedia + '</td></tr></tbody>');
                                        }
                                    }
                                    
                                    // Get the new count
                                    window.rml.library.refreshCount(container, function() {
                                        container.allInOneTree("loader", false);
                                    });
                                    
                                    /**
                                     * Attachments are moved or copied to a folder.
                                     * 
                                     * @property {int[]} ids The attachment ids to move
                                     * @property {string} folderId The destination folder id
                                     * @property {boolean} isShortcut True if the attachments are copied to the destination folder
                                     * @event window.rml.hooks#attachments/moved
                                     * @this allInOneTree
                                     */
                                    window.rml.hooks.call("attachments/moved", [ ids, folderId, isShortcut ], container);
                                    
                                }else{
                                    // You are not allowed to insert files here
                                    removeElementsIterate(function() {
                                        $(this).fadeTo(0, 1);
                                    });
                                    
                                    window.rml.sweetAlert("Oops...", response.data.join(), "error");
                                    container.allInOneTree("loader", false);
                                }
                            }
                        );
                    };
                    
                    if ($(this).attr("data-aio-id") == "") {
                        // Give a warning that this is from different sources
                        window.rml.sweetAlert({   
                            title: rmlOpts.lang.deleteConfirmTitle,   
                            text: rmlOpts.lang.moveFromAllConfirmText,   
                            type: "warning",   
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",   
                            confirmButtonText: rmlOpts.lang.moveFromAllConfirmSubmit,   
                            cancelButtonText: rmlOpts.lang.deleteConfirmCancel,
                        }, function(){
                            doIt();
                        });
                    }else{
                        doIt();
                    }
                }
            }
        },
        others: {
            rootParentId: rmlOpts.root,
            /*
             * CREATE PROCESS
             */
            onCreateFolder: function(name, createType, parentID, obj) {
                $(this).allInOneTree("loader", true);
                var args = arguments;
                
                // Create the node
                jQuery.post(
                    rmlOpts.ajaxUrl, 
                    {
                        'action': 'rml_folder_create',
                        'nonce' : rmlOpts.nonces.folderCreate,
                        'name': name,
                        'parent' : parentID,
                        'type': createType.type
                    },
                    function(response){
                        $(this).allInOneTree("loader", false);
                        if (response.success) {
                            window.rml.hooks.call("createdBeforeRendering", [ args, response ], $(this)); // private hook
                        }else{
                            sweetAlert("Oops...", $("<div/>").html(response.data.join()).text(), "error");
                        }
                    }.bind(this)
                );
            },
            labelRearrangeSave: rmlOpts.lang.save,
            onAfterFinish: function() {
                var containerOptions = $(this).allInOneTree("option").container,
                    thisListMode = containerOptions.listMode,
                    thisIsDefaultMediaLibrary = containerOptions.isMediaLibrary,
                    thisIsModalMode = containerOptions.isModalMode,
                    container = $(this),
                    customSelect = container.allInOneTree("option", "container.customSelectToChange"),
                    selectedId = RMLUrlParams().rml_folder || "";
                    
                $(this).show().addClass("rml-mode-" + thisListMode);
                
                // Make sortable if needed
                if (thisListMode === "list" && window.location.hash === "#order") {
                    $(this).allInOneTree("toolbarButton", "order");
                    window.location.hash = "";
                }
                
                // Load the content
                if ($(this).hasClass("aio-lazy")) {
                    // Get selected folder depending on modal or other
            	    if (customSelect && (customSelect = jQuery(customSelect)) && container.allInOneTree("option", "container.isModalMode")) {
            	    	selectedId = customSelect.val();
            	    }
                    
                    // Load tree content
                    window.rml.library.initializeTreeContentLoader(selectedId, $(this)).done(function(response) {
                        var backbone = window.rml.library.getBackboneOfAIO(container);
                        if (backbone.view) {
                            backbone.view._treeLoaded.resolve(response);
                        }
                    
                        $(this).allInOneTree("nodesHTML", $(response.data.nodes).html(), true, selectedId);
                        $(this).allInOneTree("counts", { "-1": response.data.cntRoot });
                        
                        /**
                         * This hooks is called after a tree is initialized.
                         * 
                         * @event window.rml.hooks#tree/afterInit
                         * @this allInOneTree
                         */
                        window.rml.hooks.call("tree/afterInit", false, container);
                        
                        /**
                         * This hook is called after a tree is initialized in a given view. $listMode
                         * can be replaced with "grid" or "list".
                         * 
                         * @event window.rml.hooks#tree/afterInit/$listMode
                         * @this allInOneTree
                         */
                        window.rml.hooks.call("tree/afterInit/" + thisListMode, false, container);
                        
                        // default media library page
                        if (thisIsDefaultMediaLibrary) {
                            /**
                             * This hook is called after a tree is initialized in the default
                             * media library page of wordpress backend (upload.php).
                             * 
                             * @event window.rml.hooks#tree/afterInit/ML
                             * @this allInOneTree
                             */
                            window.rml.hooks.call("tree/afterInit/ML", false, container);
                            
                            /**
                             * This hook is called after a tree is initialized in the default
                             * media library page of wordpress backend (upload.php) for a given view.
                             * $listMode can be replaced with "grid" or "list".
                             * 
                             * @event window.rml.hooks#tree/afterInit/ML/$listMode
                             * @this allInOneTree
                             */
                            window.rml.hooks.call("tree/afterInit/ML/" + thisListMode, false, container);
                        }
                        
                        if (thisIsModalMode !== false) {
                            /**
                             * This hook is called after a tree is initialized in the "Insert media"-dialog.
                             * 
                             * @event window.rml.hooks#tree/afterInit/modal
                             * @this allInOneTree
                             */
                            window.rml.hooks.call("tree/afterInit/modal", thisIsModalMode, container);
                        }
                    }.bind(this));
                }
            },
            onSwitchFolder: function(obj, oldID) {
                // Nothing to do here, yet...
            },
            onTypeButtonClicked: function(button, event) {
                // Avoid open the "Add new" media dialog
                if (event.stopImmediatePropagation) {
                    event.stopImmediatePropagation();
                }
            }
        }
    };
    
    /*
     * *************************************************************************
     *          The code in this is called, when we are in the media
     *                  library section. Here begins the magic!
     * *************************************************************************
     */
    if ($("body").hasClass("wp-admin")
          && $("body").hasClass("upload-php")) {
        
        /**
         * DOM-Ready hook for "Media Library" page (upload.php).
         * 
         * @event window.rml.hooks#ready/ML
         */
        window.rml.hooks.call("ready/ML");
        window.rml.library.isMediaLibrary = true;
              
        /*
         * Create the container (sidebar)
         */
        $("body").addClass("upload-php-mode-" + rmlOpts.listMode + " activate-aio");
        container = $(".rml-container.rml-dummy").clone().prependTo("body.wp-admin.upload-php #wpbody");
        container.removeClass("rml-dummy").addClass("rml-no-dummy");
        
        /**
         * A tree container is placed to the DOM in the Media Library page of wordpress backend (upload.php).
         * But the tree is not initialized yet through the {@link allInOneTree} plugin.
         * 
         * @event window.rml.hooks#tree/prepare/ML
         * @this jQuery
         */
        window.rml.hooks.call("tree/prepare/ML", false, this);
        window.rml.library.container = container;
        
        // Add media library relevant options
        var aioSettings = $.extend(true, {}, window.rml.defaultAioSettings, {
            container: {
                isMediaLibrary: true,
                customSelectToChange: "#wpbody-content .attachment-filters-rml",
                resizeOpposite: "#wpbody-content",
                theme: "wordpress",
                hcStickySettings: {
                   top: 32,
                   bottom: 50,
                   offResolutions: -700
                },
                onResizeFinished: function(width) {
                    // Save to the cookies so the opposite container can be resized on sideload
                    jQuery.post(
                        rmlOpts.ajaxUrl, 
                        {
                            'action': 'rml_sidebar_resize',
                            'nonce' : rmlOpts.nonces.sidebarResize,
                            'width' : width
                        },
                        function(response){ }
                    );
                    
                    // If grid mode then update the columns
                    var backbone;
                    if ($(this).allInOneTree("option", "container.listMode") === "grid" &&
                        (backbone = window.rml.library.getBackboneOfAIO($(this)))) {
                        backbone.view.attachments.setColumns();
                    }
                }
            }
        });
        
        /**
         * The tree is not initialized yet through the {@link allInOneTree} plugin. Here
         * you can modify the options for the jQuery plugin.
         * 
         * @property {object} aioSettings The settings for the jQuery plugin
         * @event window.rml.hooks#tree/prepare/aioSettings
         * @this jQuery
         */
        window.rml.hooks.call("tree/prepare/aioSettings", aioSettings, container);
        
        /**
         * The tree is not initialized yet through the {@link allInOneTree} plugin. Here
         * you can modify the options for the jQuery plugin. The $listMode can be
         * replaced by 'grid' or 'list'.
         * 
         * @property {object} aioSettings The settings for the jQuery plugin
         * @event window.rml.hooks#tree/prepare/aioSettings/$listMode
         * @this jQuery
         */
        window.rml.hooks.call("tree/prepare/aioSettings/" + rmlOpts.listMode, aioSettings, container);
        
        // Create the tree
        container.data("isListMode", isListMode);
        container.allInOneTree(aioSettings);
    }else{
        window.rml.library.customLists();
        window.rml.hooks.call("ready/noMediaLibrary"); // private hook
    }
    
    /*
     * Handler for changing the folder in the attachment popup.
     */
    jQuery(document).on("change", ".rml-folder-edit select", function() {
        jQuery(this).css("opacity", "1");
    });
});