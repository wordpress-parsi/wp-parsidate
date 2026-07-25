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
        value = wppdWcConv2EnNum($(this).attr('value'));
        $(this).attr('value', value).val(value).trigger('change');
      });
    })
  }

  wppdWcOrderDateFix();
});
