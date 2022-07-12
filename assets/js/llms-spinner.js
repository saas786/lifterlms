!function(){"use strict";var t={d:function(n,e){for(var r in e)t.o(e,r)&&!t.o(n,r)&&Object.defineProperty(n,r,{enumerable:!0,get:e[r]})},o:function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},r:function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})}},n={};t.r(n),t.d(n,{get:function(){return a},start:function(){return d},stop:function(){return c}});const e="llms-spinning",r="default";var o=window.wp.i18n;function i(t){let n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:r;const i=document.createElement("div"),l=(0,o.__)("Loading…","lifterlms");return i.innerHTML=`<i class="llms-spinner ${n}" role="alert" aria-live="assertive"><span class="screen-reader-text">${l}</span></i>`,i.classList.add(e),t.appendChild(i),i}function l(t){if((t="string"==typeof t?document.querySelectorAll(t):t)instanceof NodeList)return Array.from(t);const n=[];return t instanceof Element?n.push(t):"undefined"!=typeof jQuery&&t instanceof jQuery&&t.toArray().forEach((t=>n.push(t))),n}function s(){const t="llms-spinner-styles";if(!document.getElementById(t)){const n=document.createElement("style");n.textContent="\n\t.llms-spinning {\n\t\tbackground: rgba( 250, 250, 250, 0.7 );\n\t\tbottom: 0;\n\t\tdisplay: none;\n\t\tleft: 0;\n\t\tposition: absolute;\n\t\tright: 0;\n\t\ttop: 0;\n\t}\n\n\t.llms-spinner {\n\t\tanimation: llms-spinning 1.5s linear infinite;\n\t\tbox-sizing: border-box;\n\t\tborder: 4px solid #313131;\n\t\tborder-radius: 50%;\n\t\theight: 40px;\n\t\tleft: 50%;\n\t\tmargin-left: -20px;\n\t\tmargin-top: -20px;\n\t\tposition: absolute;\n\t\ttop: 50%;\n\t\twidth: 40px;\n\n\t}\n\n\t.llms-spinner.small {\n\t\tborder-width: 2px;\n\t\theight: 20px;\n\t\tmargin-left: -10px;\n\t\tmargin-top: -10px;\n\t\twidth: 20px;\n\t}\n\n\t@keyframes llms-spinning {\n\t\t0% {\n\t\t\ttransform: rotate( 0deg )\n\t\t}\n\t\t50% {\n\t\t\tborder-radius: 5%;\n\t\t}\n\t\t100% {\n\t\t\ttransform: rotate( 220deg) \n\t\t}\n\t}\n".replace(/\n/g,"").replace(/\t/g," ").replace(/\s\s+/g," "),n.id=t,document.head.appendChild(n)}}function a(t){let n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:r,e=!(arguments.length>2&&void 0!==arguments[2])||arguments[2];s();const o=l(t);if(!o.length)return null;const a=o[0],d=a.querySelector(".llms-spinning")||i(a,n);return e&&"undefined"!=typeof jQuery?jQuery(d):d}function d(t){let n=arguments.length>1&&void 0!==arguments[1]?arguments[1]:r;l(t).forEach((t=>{const e=a(t,n,!1);e&&(e.style.display="block")}))}function c(t){l(t).forEach((t=>{const n=a(t,r,!1);n&&(n.style.display="none")}))}window.LLMS=window.LLMS||{},window.LLMS.Spinner=n}();