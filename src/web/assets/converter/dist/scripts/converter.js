(()=>{"use strict";var e={n:t=>{var n=t&&t.__esModule?()=>t.default:()=>t;return e.d(n,{a:n}),n},d:(t,n)=>{for(var o in n)e.o(n,o)&&!e.o(t,o)&&Object.defineProperty(t,o,{enumerable:!0,get:n[o]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=Craft;var n=e.n(t);const o=Garnish;var r=e.n(o);const i=document.getElementById("type"),s=document.querySelector('input[name="fieldId"]');if("benf\\neo\\Field"===i.dataset.value&&null!==s){const e=n().cp.$primaryForm,t=e.find('input[type="submit"]');let o=document.getElementById("Matrix-convert_button"),i=document.getElementById("Matrix-convert_spinner"),a=!0;const l=n=>{a=!!n,o.classList.toggle("disabled",!a),t.toggleClass("disabled",!a),a?e.off("submit.neo"):e.on("submit.neo",(e=>e.preventDefault()))},d=()=>{l(!1),i.classList.remove("hidden"),n().postActionRequest("neo/conversion/convert-to-matrix",{fieldId:s.value},((e,t)=>{e.success?(n().cp.removeListener(r().$win,"beforeunload"),window.location.reload()):(l(!0),n().cp.displayError(n().t("neo","Could not convert Neo field to Matrix")),e.errors?.forEach((e=>n().cp.displayError(e))))}))},c=()=>{const e=document.getElementById("craft-fields-Matrix");null!==e&&null===e.querySelector("#conversion-prompt")&&(e.insertAdjacentHTML("afterbegin",`\n      <div id="conversion-prompt">\n        <div class="field">\n          <div class="heading">\n            <label>${n().t("neo","Convert from Neo")}</label>\n            <div class="instructions"><p>${n().t("neo","This field is currently of the Neo type. You may automatically convert it to Matrix along with all of its content.")}</p></div>\n          </div>\n          <div class="input ltr">\n            <input id="Matrix-convert_button" type="button" class="btn submit" value="${n().t("neo","Convert")}">\n            <span id="Matrix-convert_spinner" class="spinner hidden"></span>\n          </div>\n          <p class="warning">${n().t("neo","By converting to Matrix, structural information will be lost.")}</p>\n        </div>\n      </div>\n      <hr>\n    `),o=document.getElementById("Matrix-convert_button"),i=document.getElementById("Matrix-convert_spinner"),o.addEventListener("click",(e=>{e.preventDefault(),a&&window.confirm(n().t("neo","Are you sure? This is a one way operation. You cannot undo conversion from Neo to Matrix."))&&d()})))};new window.MutationObserver(c).observe(document.getElementById("settings"),{childList:!0,subtree:!0})}})();
//# sourceMappingURL=converter.js.map