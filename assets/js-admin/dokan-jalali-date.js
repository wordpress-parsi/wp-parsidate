/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	const __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter/value functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			if(Array.isArray(definition)) {
/******/ 				var i = 0;
/******/ 				while(i < definition.length) {
/******/ 					var key = definition[i++];
/******/ 					var binding = definition[i++];
/******/ 					if(!__webpack_require__.o(exports, key)) {
/******/ 						if(binding === 0) {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, value: definition[i++] });
/******/ 						} else {
/******/ 							Object.defineProperty(exports, key, { enumerable: true, get: binding });
/******/ 						}
/******/ 					} else if(binding === 0) { i++; }
/******/ 				}
/******/ 			} else {
/******/ 				for(var key in definition) {
/******/ 					if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 						Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 					}
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
let __webpack_exports__ = {};
/*!**************************************************!*\
  !*** ./assets/js-admin-src/dokan-jalali-date.js ***!
  \**************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DokanJalaliDate: () => (/* binding */ DokanJalaliDate)
/* harmony export */ });
/**
 * Dokan Jalali Date Integration - ES6 Source
 *
 * Replaces Dokan's jQuery UI / daterangepicker date fields with Jalali
 * (Shamsi) datepickers while the value that is submitted to the server stays
 * Gregorian.
 *
 * Strategy:
 *   - Dokan initializes its own pickers on `.datepicker`, `.dokan-start-date`,
 *     `.dokan-end-date` (jQuery UI) and on `.dokan-daterangepicker` (bootstrap
 *     daterangepicker). Those instances are destroyed first, otherwise both
 *     pickers open on the same field.
 *   - Single date fields use a dual input pattern:
 *       visible input  -> Jalali value shown to the user
 *       hidden input   -> Gregorian value posted with the form
 *   - Date range fields keep the visible input and write the Gregorian
 *     start/end dates into the hidden inputs Dokan already ships.
 *
 * This exact file is used for both the vendor dashboard (frontend) and
 * wp-admin; the context only changes the localized config.
 *
 * Class structure:
 *   DokanJalaliDate - orchestrator; `boot()` is the single entry point and
 *                     runs at most once per page.
 *
 * @package WP-Parsidate
 */

const DEFAULT_CONFIG = {
  isAdmin: 0,
  months: [],
  persianDigits: true,
  dateSeparator: '/',
  datepickerEnabled: true,
  productDatesEnabled: true,
  datesDisplayEnabled: true,
};

/**
 * Main Dokan Jalali Date class
 */
class DokanJalaliDate {
  /**
   * Single date inputs Dokan ships across Lite and Pro.
   */
  static SINGLE_SELECTOR = [
    '.datepicker',
    '.dokan-start-date',
    '.dokan-end-date',
    'input[name="_sale_price_dates_from"]',
    'input[name="_sale_price_dates_to"]',
    'input[name^="access_expires"]',
    '#order_date',
    '#shipped-date',
  ].join(',');

  /**
   * Date range inputs (orders filter, reports, reverse withdrawal, store
   * support, seller vacation, auctions...).
   */
  static RANGE_SELECTOR = '.dokan-daterangepicker';

  /**
   * Month dropdown of the vendor product listing (Dokan's "All dates" filter).
   * Its values are Gregorian YYYYMM; they are rebuilt as Jalali YYYYMM and the
   * server converts the selected month back to a Gregorian date range.
   */
  static MONTH_FILTER_SELECTOR = '#filter-by-date';

  /**
   * Hidden inputs that receive the Gregorian start/end of a range field.
   */
  static RANGE_TARGETS = [
    ['.dokan-daterangepicker-start-date', '.dokan-daterangepicker-end-date'],
    ['#dokan-seller-vacation-date-from', '#dokan-seller-vacation-date-to'],
  ];

  static DISPLAY_CLASS = 'wpp-dokan-jalali-input';

  static HIDDEN_MARK = 'wpp-dokan-jalali-gregorian';

  static RANGE_SEP = ' - ';

  constructor() {
    const raw = window.WppDokanJalali || {};
    // wp_localize_script stringifies booleans, so a disabled toggle arrives as
    // "0" (truthy). Normalize the flags before anything tests them.
    const flag = (value, fallback) => (
      value === undefined || value === null || value === ''
        ? fallback
        : Number(value) === 1 || value === true
    );

    this.config = {
      ...DEFAULT_CONFIG,
      ...raw,
      persianDigits: flag(raw.persianDigits, DEFAULT_CONFIG.persianDigits),
      datepickerEnabled: flag(raw.datepickerEnabled, DEFAULT_CONFIG.datepickerEnabled),
      productDatesEnabled: flag(raw.productDatesEnabled, DEFAULT_CONFIG.productDatesEnabled),
      datesDisplayEnabled: flag(raw.datesDisplayEnabled, DEFAULT_CONFIG.datesDisplayEnabled),
    };
    this.dateSeparator = this.config.dateSeparator || '/';
    this.$ = window.jQuery;
    this.engine = window.WpPdJalaliDate;
    this.picker = window.jalaliDatepicker;
    this.observer = null;
    this.processedFields = new WeakSet();
    this.initialized = false;
    this.modeWatcherInstalled = false;
    this.reportChartsPatched = false;

    if (!this.$ || !this.engine || !this.picker) {
      console.warn('[WP-Parsidate] Missing dependencies for Dokan Jalali date integration');
      return;
    }

    // The report chart is drawn on DOM ready (possibly before boot()), and its
    // x-axis labels are canvas text, so the patch has to be installed now.
    this.patchReportCharts();
  }

  /**
   * Convert the x-axis labels of Dokan's Flot report charts to Jalali.
   *
   * Flot renders tick labels on a canvas, so they cannot be rewritten after
   * the chart is drawn. Wrapping jQuery.plot adds a Jalali tickFormatter to
   * time axes before Flot renders: tick positions stay Gregorian (they are
   * data points) and only the labels change.
   */
  patchReportCharts() {
    const $ = this.$;

    if (this.reportChartsPatched || !this.config.datesDisplayEnabled || !$ || !$.plot) {
      return;
    }

    this.reportChartsPatched = true;

    const originalPlot = $.plot;
    const self = this;

    const jalaliTick = (value) => {
      try {
        const date = new Date(value);
        const result = self.engine.toJalaali(date.getFullYear(), date.getMonth() + 1, date.getDate());

        if (!result || !result.jy) {
          return '';
        }

        const month = (self.config.months || [])[result.jm - 1] || '';
        const day = self.config.persianDigits ? self.toPersianDigits(self.pad(result.jd)) : self.pad(result.jd);

        return day + ' ' + month;
      } catch (e) {
        return '';
      }
    };

    const patched = function (placeholder, data, options) {
      if (options && Array.isArray(options.xaxes)) {
        options.xaxes.forEach((axis) => {
          if (!axis || axis.mode !== 'time' || typeof axis.tickFormatter === 'function') {
            return;
          }

          // Day-based buckets are true days in both calendars. Month/year
          // buckets in these reports are Gregorian calendar months, which
          // cannot be labeled with a single Jalali month; leave those alone.
          if (String(axis.timeformat || '').indexOf('%d') === -1) {
            return;
          }

          axis.tickFormatter = (value) => jalaliTick(value);
        });
      }

      return originalPlot.apply(this, arguments);
    };

    // Flot keeps its plugins on $.plot.plugins, and its own draw call reads
    // that property at run time; without this the wrapper breaks every chart.
    Object.assign(patched, originalPlot);
    $.plot = patched;
  }

  /**
   * Pad number to 2 digits
   */
  pad(n) {
    return (n < 10 ? '0' : '') + n;
  }

  /**
   * Convert Persian/Arabic digits to Latin
   */
  normalizeDigits(str) {
    return String(str ?? '')
      .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
      .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
  }

  /**
   * Check whether a value is a Jalali date (its year is not a Gregorian one)
   */
  isJalaliDate(dateStr) {
    const match = /^(\d{4})[\/\-]/.exec(this.normalizeDigits(dateStr || '').trim());
    if (!match) return false;

    const year = +match[1];
    return year >= 1200 && year <= 1599;
  }

  /**
   * Convert Gregorian date (Y-m-d) to Jalali (jY/jM/jD)
   */
  gregorianToJalali(dateStr) {
    const match = /^(\d{4})-(\d{1,2})-(\d{1,2})/.exec(this.normalizeDigits(dateStr || '').trim());
    if (!match) return '';

    try {
      const result = this.engine.toJalaali(+match[1], +match[2], +match[3]);
      if (!result || !result.jy) return '';
      return result.jy + this.dateSeparator + this.pad(result.jm) + this.dateSeparator + this.pad(result.jd);
    } catch (e) {
      return '';
    }
  }

  /**
   * Convert Jalali date (jY/jM/jD) to Gregorian (Y-m-d)
   */
  jalaliToGregorian(dateStr) {
    const match = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/.exec(this.normalizeDigits(dateStr).trim());
    if (!match) return '';

    const jy = +match[1];
    const jm = +match[2];
    const jd = +match[3];

    // Validate Jalali date
    if (jy < 1 || jm < 1 || jm > 12 || jd < 1 || jd > 31) return '';

    // Check if it's actually a Jalali year (1200-1599)
    if (jy < 1200 || jy > 1599) {
      // Probably already Gregorian, return as-is in normalized format
      return jy + '-' + this.pad(jm) + '-' + this.pad(jd);
    }

    try {
      const result = this.engine.toGregorian(jy, jm, jd);
      if (!result || !result.gy) return '';
      return result.gy + '-' + this.pad(result.gm) + '-' + this.pad(result.gd);
    } catch (e) {
      return '';
    }
  }

  /**
   * Convert a Gregorian date range to Jalali
   */
  gregorianRangeToJalali(startDate, endDate) {
    const jalaliStart = this.gregorianToJalali(startDate);
    const jalaliEnd = this.gregorianToJalali(endDate);
    if (!jalaliStart || !jalaliEnd) return '';
    return jalaliStart + DokanJalaliDate.RANGE_SEP + jalaliEnd;
  }

  /**
   * Parse a Jalali date range string to Gregorian start/end
   */
  jalaliRangeToGregorian(rangeStr) {
    const parts = String(rangeStr || '').split(/\s+-\s+|\s+to\s+/i);
    if (parts.length !== 2) return {start: '', end: ''};

    return {
      start: this.jalaliToGregorian(parts[0].trim()),
      end: this.jalaliToGregorian(parts[1].trim()),
    };
  }

  /**
   * Remove the picker Dokan (or any other script) attached to a field.
   * Must run before the Jalali picker is attached, otherwise both open.
   */
  destroyNativePicker(el) {
    const $el = this.$(el);

    try {
      const daterangepicker = $el.data('daterangepicker');
      if (daterangepicker && typeof daterangepicker.remove === 'function') {
        daterangepicker.remove();
      }
      $el.off('.daterangepicker');
    } catch (e) { /* noop */
    }

    try {
      if (this.$.fn.datepicker && $el.hasClass('hasDatepicker')) {
        $el.datepicker('destroy');
        $el.removeClass('hasDatepicker');
      }
    } catch (e) { /* noop */
    }
  }

  /**
   * Setup a single date field with Jalali datepicker
   */
  setupDateField(input) {
    if (
      !input
      || this.processedFields.has(input)
      || input.classList.contains(DokanJalaliDate.DISPLAY_CLASS)
      || input.type === 'hidden'
      || input.hasAttribute('data-jdp')
    ) {
      return;
    }

    this.destroyNativePicker(input);

    const $input = this.$(input);
    const originalValue = $input.val() || '';
    // Dokan prints the saved date through wp_date(), which wp-parsidate already
    // converts to Jalali, so the field can start with either calendar.
    const gregorianValue = this.isJalaliDate(originalValue) ? this.jalaliToGregorian(originalValue) : originalValue;

    // Create hidden input to hold Gregorian value
    const fieldId = $input.attr('id') || 'wpp-dokan-' + Math.random().toString(36).slice(2);
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';

    // Only carry a name when the field actually had one, otherwise the hidden
    // input would be submitted as `name="null"`.
    if ($input.attr('name')) {
      hiddenInput.name = $input.attr('name');
    }
    hiddenInput.id = fieldId + '-gregorian';
    hiddenInput.setAttribute(DokanJalaliDate.HIDDEN_MARK, '');
    hiddenInput.value = gregorianValue;

    // Create visible Jalali input
    const visibleInput = document.createElement('input');
    visibleInput.type = 'text';
    visibleInput.className = DokanJalaliDate.DISPLAY_CLASS + ' dokan-form-control';
    visibleInput.id = fieldId + '-jalali';
    visibleInput.placeholder = $input.attr('placeholder') || '';
    visibleInput.setAttribute('data-jdp', '');
    visibleInput.setAttribute('data-jdp-only-date', '');
    visibleInput.autocomplete = 'off';

    // Copy relevant attributes. 'pattern' is left out: it validates the
    // Gregorian YYYY-MM-DD shape and would reject a Jalali display value.
    ['maxlength', 'required'].forEach((attr) => {
      if ($input.attr(attr)) {
        visibleInput.setAttribute(attr, $input.attr(attr));
      }
    });

    // Set initial Jalali value
    if (gregorianValue) {
      visibleInput.value = this.gregorianToJalali(gregorianValue);
    }

    // Insert hidden and visible inputs
    $input.after(visibleInput);
    $input.after(hiddenInput);
    $input.hide();

    // Remove original name attribute to prevent submission
    $input.removeAttr('name');

    // The retired field still holds a Jalali value while keeping Dokan's
    // Gregorian `pattern`; hidden fields are not excluded from native
    // validation, so it would block the product form submit. Disable it.
    $input.removeAttr('pattern').prop('disabled', true);

    // Sync changes from visible to hidden
    const self = this;
    this.$(visibleInput).on('change blur', function () {
      const jalaliValue = visibleInput.value.trim();
      const gregorian = jalaliValue ? self.jalaliToGregorian(jalaliValue) : '';
      hiddenInput.value = gregorian;
      $input.val(gregorian).trigger('change');
    });

    // Keep the control label pointing at the field the user sees.
    if ($input.attr('id')) {
      const label = $input.closest('.dokan-form-group, .form-group, .dokan-input-group')
        .find('label[for="' + $input.attr('id') + '"]');
      if (label.length) {
        label.attr('for', visibleInput.id);
      }
    }

    // The picker is started once for every Jalali field in initPickers().
    this.processedFields.add(input);
  }

  /**
   * Find the hidden inputs that hold the Gregorian start/end of a range field.
   */
  findRangeTargets(input) {
    const $input = this.$(input);
    const scope = $input.closest('form, .dokan-form-group, .dokan-dashboard-content, .wrap');

    for (let i = 0; i < DokanJalaliDate.RANGE_TARGETS.length; i++) {
      const $start = (scope.length ? scope : this.$(document)).find(DokanJalaliDate.RANGE_TARGETS[i][0]).first();
      const $end = (scope.length ? scope : this.$(document)).find(DokanJalaliDate.RANGE_TARGETS[i][1]).first();

      if ($start.length && $end.length) {
        return {start: $start, end: $end};
      }
    }

    return null;
  }

  /**
   * Setup a date range field with Jalali datepicker
   */
  setupDateRangeField(input) {
    if (!input || this.processedFields.has(input) || input.type === 'hidden' || input.classList.contains(DokanJalaliDate.DISPLAY_CLASS)) {
      return;
    }

    this.destroyNativePicker(input);

    const $input = this.$(input);
    const targets = this.findRangeTargets(input);

    // Replace the Gregorian range picker with the Jalali one
    $input.attr('data-jdp', '');
    $input.attr('data-jdp-mode', 'range');
    $input.removeAttr('readonly');
    $input.attr('autocomplete', 'off');

    // Set initial Jalali range from the Gregorian values Dokan rendered
    if (targets) {
      const startValue = targets.start.val() || '';
      const endValue = targets.end.val() || '';

      if (startValue && endValue) {
        const jalaliRange = this.gregorianRangeToJalali(startValue, endValue);
        if (jalaliRange) {
          $input.val(jalaliRange);
        }
      }
    }

    const self = this;
    $input.on('change blur', function () {
      if (!targets) {
        return;
      }

      const rangeStr = $input.val().trim();

      if (!rangeStr) {
        targets.start.val('');
        targets.end.val('');
        return;
      }

      const gregorian = self.jalaliRangeToGregorian(rangeStr);

      if (gregorian.start) {
        targets.start.val(gregorian.start);
      }
      if (gregorian.end) {
        targets.end.val(gregorian.end);
      }
    });

    this.processedFields.add(input);
  }

  /**
   * Convert the Gregorian YYYYMM value of one month option to the Jalali
   * YYYYMM months it overlaps. A Gregorian month spans at most two Jalali
   * months (for example 2026-10 covers Mehr and Aban 1405).
   */
  gregorianMonthToJalaliKeys(gregorianMonth) {
    const year = +String(gregorianMonth).slice(0, 4);
    const month = +String(gregorianMonth).slice(4, 6);

    if (!year || !month) {
      return [];
    }

    // Day 0 of the next month is the last day of this month.
    const lastDay = new Date(year, month, 0).getDate();

    let first;
    let last;

    try {
      first = this.engine.toJalaali(year, month, 1);
      last = this.engine.toJalaali(year, month, lastDay);
    } catch (e) {
      return [];
    }

    if (!first || !last || !first.jy || !last.jy) {
      return [];
    }

    const keys = [];
    const end = last.jy * 100 + last.jm;

    for (let key = first.jy * 100 + first.jm, i = 0; i < 3 && key <= end; i++) {
      keys.push(key);
      key = key % 100 === 12 ? (Math.floor(key / 100) + 1) * 100 + 1 : key + 1;
    }

    return keys;
  }

  /**
   * Format a Jalali YYYYMM key as "مهر ۱۴۰۵" for the month dropdown.
   */
  jalaliMonthLabel(key) {
    const year = Math.floor(key / 100);
    const month = key % 100;
    const name = (this.config.months || [])[month - 1] || '';
    const yearText = this.config.persianDigits ? this.toPersianDigits(year) : String(year);

    return name ? `${name} ${yearText}` : String(key);
  }

  /**
   * Convert Latin digits to Persian digits.
   */
  toPersianDigits(value) {
    return String(value ?? '').replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }

  /**
   * Whether a YYYYMM value already is a Jalali month key.
   */
  isJalaliMonthKey(value) {
    const str = String(value ?? '');
    const key = +str;
    const year = Math.floor(key / 100);
    const month = key % 100;

    return /^\d{6}$/.test(str)
      && String(key) === str
      && year >= 1200
      && year <= 1599
      && month >= 1
      && month <= 12;
  }

  /**
   * Rebuild Dokan's product month filter with Jalali months.
   *
   * The server reports the Jalali months that contain products, so the options
   * only need to be relabelled. If the native Gregorian list is still present
   * (the PHP filter did not run), it is converted client-side. The PHP side
   * turns the selected Jalali YYYYMM value into an exact Gregorian range.
   */
  setupMonthFilter(container) {
    let select = null;

    if (container.matches && container.matches(DokanJalaliDate.MONTH_FILTER_SELECTOR)) {
      select = container;
    } else if (container.querySelector) {
      select = container.querySelector(DokanJalaliDate.MONTH_FILTER_SELECTOR);
    }

    if (!select || select.dataset.wppJalaliMonths === '1') {
      return;
    }

    const nativeOptions = [...select.options].filter((option) => /^\d{6}$/.test(option.value));

    if (!nativeOptions.length) {
      return;
    }

    const months = new Set();
    nativeOptions.forEach((option) => {
      if (this.isJalaliMonthKey(option.value)) {
        months.add(+option.value);
      } else {
        // Fallback for the native Gregorian list when the server-side month
        // list was not replaced.
        this.gregorianMonthToJalaliKeys(option.value).forEach((key) => months.add(key));
      }
    });

    if (!months.size) {
      return;
    }

    // Keep the selected month after a reload.
    const selected = new URLSearchParams(window.location.search).get('date') || '';

    if (this.isJalaliMonthKey(selected)) {
      months.add(+selected);
    }

    const sorted = [...months].sort((a, b) => b - a);

    while (select.options.length > 1) {
      select.remove(1);
    }

    sorted.forEach((key) => {
      const option = document.createElement('option');
      option.value = String(key);
      option.textContent = this.jalaliMonthLabel(key);
      select.appendChild(option);
    });

    if (this.isJalaliMonthKey(selected) && months.has(+selected)) {
      select.value = selected;
    }

    select.dataset.wppJalaliMonths = '1';
    this.syncMonthFilterWithSearch(select);
  }

  /**
   * Dokan renders the month dropdown and the search box in two separate forms,
   * and the search form only carries the active date as a hidden field. Mirror
   * the selected month into that form so a search does not drop the filter.
   */
  syncMonthFilterWithSearch(select) {
    if (select.dataset.wppJalaliSearchSync === '1') {
      return;
    }

    select.dataset.wppJalaliSearchSync = '1';

    const area = (select.closest && select.closest('.dokan-product-listing-area')) || document;

    const sync = () => {
      const form = area.querySelector('.dokan-product-search-form');

      if (!form) {
        return;
      }

      let input = form.querySelector('input[type="hidden"][name="date"]');
      const value = select.value;

      if (this.isJalaliMonthKey(value)) {
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'date';
          form.appendChild(input);
        }

        input.value = value;
      } else if (input) {
        input.remove();
      }
    };

    select.addEventListener('change', sync);

    area.addEventListener('submit', (event) => {
      if (event.target && event.target.matches && event.target.matches('.dokan-product-search-form')) {
        sync();
      }
    }, true);
  }

  /**
   * Scan and setup all datepicker fields in a container
   */
  scan(root) {
    const container = root || document;

    if (!this.config.datepickerEnabled && !this.config.productDatesEnabled) {
      return;
    }

    container.querySelectorAll(DokanJalaliDate.SINGLE_SELECTOR).forEach((field) => {
      this.setupDateField(field);
    });

    container.querySelectorAll(DokanJalaliDate.RANGE_SELECTOR).forEach((field) => {
      this.setupDateRangeField(field);
    });

    this.setupMonthFilter(container);
  }

  /**
   * Dokan re-initializes pickers when it re-renders parts of the form (the
   * "add product" popup does it on every open), so drop them again.
   */
  cleanupDisplayPickers() {
    document.querySelectorAll('.' + DokanJalaliDate.DISPLAY_CLASS).forEach((el) => {
      const $el = this.$(el);

      if ($el.data('daterangepicker') || $el.hasClass('hasDatepicker')) {
        this.destroyNativePicker(el);
      }
    });
  }

  /**
   * Watch for dynamically added forms (AJAX, modal popups, popup re-render)
   */
  observe() {
    if (this.observer || typeof MutationObserver === 'undefined' || !document.body) {
      return;
    }

    this.observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!node || node.nodeType !== 1) {
            return;
          }

          // Our own hidden/visible inputs are inserted into the DOM and match
          // the selectors again, which would create an endless loop.
          if (
            node.hasAttribute(DokanJalaliDate.HIDDEN_MARK)
            || node.hasAttribute('data-jdp')
            || node.classList.contains(DokanJalaliDate.DISPLAY_CLASS)
          ) {
            return;
          }

          if (node.matches && node.matches(DokanJalaliDate.RANGE_SELECTOR)) {
            this.setupDateRangeField(node);
          } else if (node.matches && node.matches(DokanJalaliDate.SINGLE_SELECTOR)) {
            this.setupDateField(node);
          }

          if (node.querySelector) {
            this.scan(node);
          }
        });
      });
    });

    this.observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  /**
   * Start the picker for every Jalali field.
   *
   * jalaliDatepicker keeps a single global watcher, so calling startWatch()
   * per field leaves only the last field working. One call with a selector
   * that matches all of them (and the shared wp-parsidate selector, when the
   * datepicker module already configured it) is the correct setup.
   */
  initPickers() {
    const selector = [window.WPP_JALALI_SELECTOR, '[data-jdp]'].filter(Boolean).join(',');

    try {
      this.picker.startWatch({
        selector: selector,
        hasSecond: false,
        persianDigits: this.config.persianDigits,
        zIndex: 999999,
        mode: 'single',
        rangeSeparator: DokanJalaliDate.RANGE_SEP,
      });
    } catch (e) {
      console.warn('[WP-Parsidate] Failed to initialize picker:', e);
    }

    this.watchMode();
  }

  /**
   * jalaliDatepicker keeps one global config and this build ignores the
   * per-field `data-jdp-mode` attribute, so the mode is switched for the field
   * being focused. The listener runs in the capture phase, before the picker's
   * own focusin handler opens it.
   */
  watchMode() {
    if (this.modeWatcherInstalled) {
      return;
    }

    this.modeWatcherInstalled = true;

    document.addEventListener('focusin', (event) => {
      const target = event.target;

      if (!target || !target.matches || !target.matches('[data-jdp]')) {
        return;
      }

      const isRange = target.getAttribute('data-jdp-mode') === 'range';

      try {
        this.picker.updateOptions({
          mode: isRange ? 'range' : 'single',
          rangeSeparator: DokanJalaliDate.RANGE_SEP,
        });
      } catch (e) { /* older builds without updateOptions */
      }
    }, true);
  }

  /**
   * Re-scan after Dokan rendered or re-rendered part of the dashboard.
   */
  rescan() {
    this.scan();
    this.cleanupDisplayPickers();
  }

  /**
   * Initialize the integration. Safe to call more than once: only the first
   * call attaches the picker, the observer and the Dokan event handlers.
   */
  boot() {
    if (this.initialized || !this.$ || !this.engine || !this.picker) {
      return;
    }

    if (!this.config.datepickerEnabled && !this.config.productDatesEnabled) {
      return;
    }

    this.initialized = true;

    // Initial scan
    this.scan();

    // Open the picker for the fields created above
    this.initPickers();

    // Watch for AJAX content
    this.observe();

    // Re-scan on Dokan's AJAX events
    this.$(document).on(
      'dokan_product_editor_loaded dokan_product_editor_popup_opened dokan_form_loaded dokan-product-editor-loaded',
      () => {
        setTimeout(() => this.rescan(), 100);
      }
    );

    // Dokan initializes its own pickers on DOM ready (sometimes after this
    // script) and re-renders parts of the product form later; scan once more
    // so those instances are dropped as well.
    setTimeout(() => this.rescan(), 400);
    window.addEventListener('load', () => this.rescan());
  }
}

/**
 * Single bootstrap for the page.
 *
 * The instance is stored under a namespaced window key, so loading the bundle
 * twice (or loading the frontend and admin builds on the same page) cannot
 * create a second integration with duplicate observers and event handlers.
 */
if (
  window.jQuery
  && window.WpPdJalaliDate
  && window.jalaliDatepicker
  && !window.WppDokanJalaliDate
) {
  window.WppDokanJalaliDate = new DokanJalaliDate();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.WppDokanJalaliDate.boot());
  } else {
    window.WppDokanJalaliDate.boot();
  }
}

/******/ })()
;