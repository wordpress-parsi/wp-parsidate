/******/ (() => { // webpackBootstrap
/*!********************************************!*\
  !*** ./assets/js-admin-src/woocommerce.js ***!
  \********************************************/
jQuery(document).ready(function ($) {
  function wppdWcConv2EnNum(str) {
    return str.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
  }

  function wppdWcOrderDateFix() {
    if ($('body.woocommerce_page_wc-orders form#order input[name="order_date"]').length === 0) return;

    let wcOrderDateFields = ['input[name="order_date"]', 'input[name="order_date_hour"]', 'input[name="order_date_minute"]', 'input[name="order_date_second"]', 'input.hasDatepicker'];

    wcOrderDateFields.forEach(function (fieldSelector) {
      let inputField = $('body.woocommerce_page_wc-orders form#order ' + fieldSelector);

      inputField.each(function (index) {
        // Read the live value, not the value attribute: the order screen writes latin
        // digits into these fields, and copying the attribute back would put the
        // Persian digits the browser cannot submit into the number inputs again.
        const value = wppdWcConv2EnNum($(this).val() || '');

        $(this).val(value).trigger('change');
      });
    })
  }

  wppdWcOrderDateFix();

  setTimeout(function () {
    const wcAnalyticsNotice = $('body.woocommerce_page_wc-admin #wp__notice-list .wppd-admin-notice');
    if (wcAnalyticsNotice) {
      wcAnalyticsNotice.closest('#wp__notice-list').removeClass('woocommerce-layout__notice-list-hide');
    }

  }, 2000)
});

/******/ })()
;