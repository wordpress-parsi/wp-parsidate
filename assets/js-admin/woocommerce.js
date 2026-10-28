/******/ (() => { // webpackBootstrap
/*!********************************************!*\
  !*** ./assets/js-admin-src/woocommerce.js ***!
  \********************************************/
jQuery(document).ready(function ($) {
  setTimeout(function () {
    const wcAnalyticsNotice = $('body.woocommerce_page_wc-admin #wp__notice-list .wppd-admin-notice');
    if (wcAnalyticsNotice) {
      wcAnalyticsNotice.closest('#wp__notice-list').removeClass('woocommerce-layout__notice-list-hide');
    }

  }, 2000)
});

/******/ })()
;