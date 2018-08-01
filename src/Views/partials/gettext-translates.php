/*! sprintf-js v1.1.1 | Copyright (c) 2007-present, Alexandru Mărășteanu <hello@alexei.ro> | BSD-3-Clause */
!function(){"use strict";function e(e){return r(n(e),arguments)}function t(t,r){return e.apply(null,[t].concat(r||[]))}function r(t,r){var n,i,a,o,p,c,u,f,l,d=1,g=t.length,b="";for(i=0;i<g;i++)if("string"==typeof t[i])b+=t[i];else if(Array.isArray(t[i])){if((o=t[i])[2])for(n=r[d],a=0;a<o[2].length;a++){if(!n.hasOwnProperty(o[2][a]))throw new Error(e('[sprintf] property "%s" does not exist',o[2][a]));n=n[o[2][a]]}else n=o[1]?r[o[1]]:r[d++];if(s.not_type.test(o[8])&&s.not_primitive.test(o[8])&&n instanceof Function&&(n=n()),s.numeric_arg.test(o[8])&&"number"!=typeof n&&isNaN(n))throw new TypeError(e("[sprintf] expecting number but found %T",n));switch(s.number.test(o[8])&&(f=n>=0),o[8]){case"b":n=parseInt(n,10).toString(2);break;case"c":n=String.fromCharCode(parseInt(n,10));break;case"d":case"i":n=parseInt(n,10);break;case"j":n=JSON.stringify(n,null,o[6]?parseInt(o[6]):0);break;case"e":n=o[7]?parseFloat(n).toExponential(o[7]):parseFloat(n).toExponential();break;case"f":n=o[7]?parseFloat(n).toFixed(o[7]):parseFloat(n);break;case"g":n=o[7]?String(Number(n.toPrecision(o[7]))):parseFloat(n);break;case"o":n=(parseInt(n,10)>>>0).toString(8);break;case"s":n=String(n),n=o[7]?n.substring(0,o[7]):n;break;case"t":n=String(!!n),n=o[7]?n.substring(0,o[7]):n;break;case"T":n=Object.prototype.toString.call(n).slice(8,-1).toLowerCase(),n=o[7]?n.substring(0,o[7]):n;break;case"u":n=parseInt(n,10)>>>0;break;case"v":n=n.valueOf(),n=o[7]?n.substring(0,o[7]):n;break;case"x":n=(parseInt(n,10)>>>0).toString(16);break;case"X":n=(parseInt(n,10)>>>0).toString(16).toUpperCase()}s.json.test(o[8])?b+=n:(!s.number.test(o[8])||f&&!o[3]?l="":(l=f?"+":"-",n=n.toString().replace(s.sign,"")),c=o[4]?"0"===o[4]?"0":o[4].charAt(1):" ",u=o[6]-(l+n).length,p=o[6]&&u>0?c.repeat(u):"",b+=o[5]?l+n+p:"0"===c?l+p+n:p+l+n)}return b}function n(e){if(i[e])return i[e];for(var t,r=e,n=[],a=0;r;){if(null!==(t=s.text.exec(r)))n.push(t[0]);else if(null!==(t=s.modulo.exec(r)))n.push("%");else{if(null===(t=s.placeholder.exec(r)))throw new SyntaxError("[sprintf] unexpected placeholder");if(t[2]){a|=1;var o=[],p=t[2],c=[];if(null===(c=s.key.exec(p)))throw new SyntaxError("[sprintf] failed to parse named argument key");for(o.push(c[1]);""!==(p=p.substring(c[0].length));)if(null!==(c=s.key_access.exec(p)))o.push(c[1]);else{if(null===(c=s.index_access.exec(p)))throw new SyntaxError("[sprintf] failed to parse named argument key");o.push(c[1])}t[2]=o}else a|=2;if(3===a)throw new Error("[sprintf] mixing positional and named placeholders is not (yet) supported");n.push(t)}r=r.substring(t[0].length)}return i[e]=n}var s={not_string:/[^s]/,not_bool:/[^t]/,not_type:/[^T]/,not_primitive:/[^v]/,number:/[diefg]/,numeric_arg:/[bcdiefguxX]/,json:/[j]/,not_json:/[^j]/,text:/^[^\x25]+/,modulo:/^\x25{2}/,placeholder:/^\x25(?:([1-9]\d*)\$|\(([^\)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,key:/^([a-z_][a-z_\d]*)/i,key_access:/^\.([a-z_][a-z_\d]*)/i,index_access:/^\[(\d+)\]/,sign:/^[\+\-]/},i=Object.create(null);"undefined"!=typeof exports&&(exports.sprintf=e,exports.vsprintf=t),"undefined"!=typeof window&&(window.sprintf=e,window.vsprintf=t,"function"==typeof define&&define.amd&&define(function(){return{sprintf:e,vsprintf:t}}))}();

/* https://github.com/oscarotero/Gettext */
!function(t,n){"function"==typeof define&&define.amd?define(["sprintf-js"],function(t){return n(t.vsprintf)}):"object"==typeof module&&module.exports?module.exports=n(require("sprintf-js").vsprintf):t.Translator=n(window.vsprintf)}(this,function(t){function n(t){this.dictionary={},this.plurals={},this.domain=null,t&&this.loadTranslations(t)}function e(t,n,e,i){return e=e||"",!!(t[n]&&t[n][e]&&t[n][e][i])&&t[n][e][i]}function i(n,e){return e.length?e[0]instanceof Array?t(n,e[0]):t(n,e):n}return n.prototype={loadTranslations:function(t){var n=t.domain||"";if(null===this.domain&&(this.domain=n),this.dictionary[n])return function(t,n){for(var e in n)if(t[e])for(var i in n[e])t[e][i]=n[e][i];else t[e]=n[e]}(this.dictionary[n],t.messages),this;if(t.fn)this.plurals[n]={fn:t.fn};else if(t["plural-forms"]){var e=t["plural-forms"].split(";",2);this.plurals[n]={count:parseInt(e[0].replace("nplurals=","")),code:e[1].replace("plural=","return ")+";"}}return this.dictionary[n]=t.messages,this},defaultDomain:function(t){return this.domain=t,this},gettext:function(t){return this.dpgettext(this.domain,null,t)},ngettext:function(t,n,e){return this.dnpgettext(this.domain,null,t,n,e)},dngettext:function(t,n,e,i){return this.dnpgettext(t,null,n,e,i)},npgettext:function(t,n,e,i){return this.dnpgettext(this.domain,t,n,e,i)},pgettext:function(t,n){return this.dpgettext(this.domain,t,n)},dgettext:function(t,n){return this.dpgettext(t,null,n)},dpgettext:function(t,n,i){var r=e(this.dictionary,t,n,i);return!1!==r&&""!==r[0]?r[0]:i},dnpgettext:function(t,n,i,r,o){var s=function(t,n,e){if(!t[n])return 1==e?0:1;t[n].fn||(t[n].fn=new Function("n",t[n].code));return t[n].fn.call(this,e)+0}(this.plurals,t,o),u=e(this.dictionary,t,n,i);return u[s]&&""!==u[s]?u[s]:0===s?i:r},__:function(t){return i(this.gettext(t),Array.prototype.slice.call(arguments,1))},n__:function(t,n,e){return i(this.ngettext(t,n,e),Array.prototype.slice.call(arguments,3))},p__:function(t,n){return i(this.pgettext(t,n),Array.prototype.slice.call(arguments,2))},d__:function(t,n){return i(this.dgettext(t,n),Array.prototype.slice.call(arguments,2))},dp__:function(t,n,e){return i(this.dgettext(t,n,e),Array.prototype.slice.call(arguments,3))},np__:function(t,n,e,r){return i(this.npgettext(t,n,e,r),Array.prototype.slice.call(arguments,4))},dnp__:function(t,n,e,r,o){return i(this.dnpgettext(t,n,e,r,o),Array.prototype.slice.call(arguments,5))}},n});

/*
 * CrudAdmin Binder into global variables and VueJS 2
 * for simple global scope integration into VueJs use Vue.use(Gettext)
 */
(function(){
  var a = new Translator(<?php echo $translations ?>),
      selectors = Object.keys(a.__proto__).concat(['_', 'Gettext']),
      getSelector = function(selector){
        return function(){
          return a[(selector in a) ? selector : '__'](...arguments);
        };
      };

  selectors.map(function(selector){
    //If window variable is used, for example by lodash library
    if ( selector in window )
      return;

    var f = window[selector] = getSelector(selector)

    //Vue.js installation
    f.install = function (Vue, options) {
      for ( var i = 0; i < selectors.length; i++ ){
          Vue.prototype[selectors[i]] = getSelector(selectors[i]);
      }
    };
  });

  window.GettextTranslates = a;
})();