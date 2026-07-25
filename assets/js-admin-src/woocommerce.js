jQuery(document).ready(function ($) {
  function wppdWcConv2EnNum(str) {
    return str.replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
  }

  function wppdWcOrderDateFix() {
    if ($('body.woocommerce_page_wc-orders form#order input[name="order_date"]').length === 0) return;

    let wcOrderDateFields = ['order_date', 'order_date_hour', 'order_date_minute', 'order_date_second'];

    wcOrderDateFields.forEach(function (fieldName) {
      let value = $('body.woocommerce_page_wc-orders form#order input[name="' + fieldName + '"]').attr('value');
      value = wppdWcConv2EnNum(value);
      $('body.woocommerce_page_wc-orders form#order input[name="' + fieldName + '"]').attr('value', value).val(value).trigger('change');
    })
  }

  wppdWcOrderDateFix();
});
