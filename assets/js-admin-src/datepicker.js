const WPP_JALALI_SELECTOR = [
  '.date-picker',
  '.acf-date-picker input.hasDatepicker',
  'body.woocommerce_page_wc-reports input.hasDatepicker',
  'input.sale_price_dates_from',
  'input.sale_price_dates_to',
  'input#_sale_price_dates_from',
  'input#_sale_price_dates_to',
  '.cx-control input.wpp-jalali-date'
].join(',');

let wppDatepickerInitialize = false;

// jQuery UI datepicker binds `input.on("focus", this._showDatepicker)`, capturing
// whatever _showDatepicker is at bind time. Installing this guard synchronously
// (before plugins initialize their datepickers on DOM ready) makes those fields
// never open the jQuery UI popup, so only the Jalali datepicker shows.
function wppBlockJQueryUIDatepicker() {
  if (!window.jQuery || !window.jQuery.datepicker || !window.jQuery.datepicker._showDatepicker) {
    return;
  }

  const wppOriginalShow = window.jQuery.datepicker._showDatepicker;

  window.jQuery.datepicker._showDatepicker = function (input) {
    input = input.target || input;

    if (window.jQuery(input).is(WPP_JALALI_SELECTOR)) {
      return;
    }

    return wppOriginalShow.apply(this, arguments);
  };
}

wppBlockJQueryUIDatepicker();

// The Jalali picker is configured with time enabled so JetEngine datetime
// fields can also pick a time. Every date-only field must opt out per input
// via data-jdp-only-date, otherwise it would show the time section too.
function wppdIsJetEngineDateTimeInput(input) {
  var hidden = input.previousElementSibling;

  return !!hidden && hidden.tagName === 'INPUT' && hidden.type === 'hidden' &&
    hidden.hasAttribute('data-datetime-settings');
}

function wppdMarkOnlyDateInputs(root) {
  if (!wppDatepickerInitialize || !root || !root.querySelectorAll) {
    return;
  }

  const inputs = root.querySelectorAll(WPP_JALALI_SELECTOR);
  let i;

  for (i = 0; i < inputs.length; i++) {
    const el = inputs[i];
    console.log(el.getAttribute('data-jdp-only-datetime'))

    if (el.getAttribute('data-jdp-only-date') || el.getAttribute('data-jdp-only-datetime') || el.getAttribute('data-jdp-only-time') || wppdIsJetEngineDateTimeInput(el)) {
      continue;
    }

    el.setAttribute('data-jdp-only-date', '');
  }
}

// Watch for fields added after init (ACF, WooCommerce, JetEngine repeaters).
(function () {
  let observer;

  if (typeof MutationObserver === 'undefined') {
    return;
  }

  observer = new MutationObserver(function (mutations) {
    for (var i = 0; i < mutations.length; i++) {
      if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
        wppdMarkOnlyDateInputs(mutations[i].target);
      }
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    wppdMarkOnlyDateInputs(document);
    observer.observe(document.body, {childList: true, subtree: true});
  });
})();

function initWPPJalaliDatePicker() {
  if (!window.jalaliDatepicker) {
    return;
  }

  wppBlockJQueryUIDatepicker();

  const options = {
    selector: WPP_JALALI_SELECTOR,
    separatorChars: {
      date: '-',
      between: ' ',
      time: ':',
      targetDate: '-',
      targetBetween: ' ',
      targetTime: ':'
    },
    zIndex: 999999999,
    time: true,
    persianDigits: true,
    hasSecond: false
  };

  if (window.WPP_I18N && window.WPP_I18N.months) {
    options.months = window.WPP_I18N.months;
  }

  window.jalaliDatepicker.startWatch(options);

  setTimeout(function () {
    wppDatepickerInitialize = true;
    wppdMarkOnlyDateInputs(document);
  }, 2000);
}

document.addEventListener("DOMContentLoaded", function () {
  initWPPJalaliDatePicker()
});

jQuery(document.body).on('woocommerce_variations_added', function () {
  initWPPJalaliDatePicker()
});

// Safety net: hide any jQuery UI popup still open on a Jalali-handled field
// (e.g. datepicker initialized by an inline head script before this file ran).
jQuery(document).on('focusin', WPP_JALALI_SELECTOR, function () {
  if (window.jQuery.datepicker && window.jQuery.datepicker._datepickerShowing) {
    window.jQuery.datepicker._hideDatepicker();
  }
});
