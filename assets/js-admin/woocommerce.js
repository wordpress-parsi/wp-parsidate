/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js-admin-src/woocommerce.js"
/*!********************************************!*\
  !*** ./assets/js-admin-src/woocommerce.js ***!
  \********************************************/
() {

eval("{jQuery(document).ready(function ($) {\n  function wppdWcConv2EnNum(str) {\n    return str.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));\n  }\n\n  function wppdWcOrderDateFix() {\n    if ($('body.woocommerce_page_wc-orders form#order input[name=\"order_date\"]').length === 0) return;\n\n    let wcOrderDateFields = ['order_date', 'order_date_hour', 'order_date_minute', 'order_date_second'];\n\n    wcOrderDateFields.forEach(function (fieldName) {\n      let value = $('body.woocommerce_page_wc-orders form#order input[name=\"' + fieldName + '\"]').attr('value');\n      value = wppdWcConv2EnNum(value);\n      $('body.woocommerce_page_wc-orders form#order input[name=\"' + fieldName + '\"]').attr('value', value).val(value).trigger('change');\n    })\n  }\n\n  wppdWcOrderDateFix();\n});\n\n\n//# sourceURL=webpack://wp-parsidate/./assets/js-admin-src/woocommerce.js?\n}");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	let __webpack_exports__ = {};
/******/ 	__webpack_modules__["./assets/js-admin-src/woocommerce.js"]();
/******/ 	
/******/ })()
;