/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**************************************************************!*\
  !*** ./assets/js-admin-src/directories-jalali-datepicker.js ***!
  \**************************************************************/
/**
 * Jalali dates for Directories / Directories Pro date fields.
 *
 * Directories renders a date field as a readonly text input
 * (.drts-form-datepicker-date) plus a hidden input
 * (.drts-form-datepicker-date-val) that holds the submitted value, which
 * Directories validates and stores in Gregorian format: "Y-m-d", "Y-m-d H:i"
 * for date+time fields and "Y-m-d,Y-m-d" for date ranges. The visible input
 * normally drives the Gregorian Vanilla Calendar widget through its
 * data-calendar-options attribute.
 *
 * This script replaces DRTS.Form.field.datepicker so that a real Jalali
 * datepicker (jalaliDatepicker, bundled with WP-Parsidate) is attached to the
 * visible input, while the hidden input keeps receiving Gregorian values.
 * The server side is untouched: dates are still validated and stored as
 * Gregorian, and Directories keeps rendering them through date_i18n (which
 * WP-Parsidate already converts to Jalali).
 *
 * Supported: date, date+time and date range fields, including fields inside
 * "add more" repeater rows (Directories clones the markup, we re-bind) and
 * forms injected over AJAX.
 * Not supported: month/year pickers and multi-date (non-range) selection,
 * which keep the original Directories implementation.
 *
 * The stored value is normally Gregorian, but some Directories versions render
 * the selected date through a localized formatter (and WP-Parsidate's own
 * date_i18n/wp_date filters localize every date), so a Jalali value can arrive
 * in settings.selected.dates. Such values are converted back to Gregorian
 * before they are written to the hidden input, otherwise they were converted a
 * second time and a saved 30 Aban 1405 came back as 784-06-08 (issue #341).
 *
 * Requires: jalalidatepicker.min.js + jalali-date.js (WP-Parsidate),
 *           form-field-datepicker.min.js (Directories).
 */


class DirectoriesJalaliDatepicker {

  // Separator used in the visible Jalali value. Must match the active
  // jalaliDatepicker separatorChars.date: "-" in wp-admin (set by
  // wp-parsidate datepicker.js), "/" on the front-end (library default).
  static DATE_SEP = (window.WpPdDrtsJalali || {}).dateSeparator === '-' ? '-' : '/';
  static RANGE_SEP = ' - ';
  static MULTI_SEP = ',';
  static TIME_SEP = ' ';

  // Years a Jalali date rendered by a server-side formatter can fall in. A
  // Gregorian date a Directories date field can hold never does: the fields
  // start at 1900-01-01 by default.
  static JALALI_YEAR_MIN = 1200;
  static JALALI_YEAR_MAX = 1599;
  static FIELD_SELECTOR = '.drts-form-datepicker-date';
  static CONTAINER_SELECTOR = '.drts-form-datepicker-inputs';

  constructor() {
    this.$ = window.jQuery;
    this.Engine = window.WpPdJalaliDate;
    this.settings = window.WpPdDrtsJalali || {};
    this.watcherArmed = false;
    this.observer = null;
    this.fieldCounter = 0;
  }

  /* ------------------------------------------------------------------ *
   * Conversion helpers
   * ------------------------------------------------------------------ */

  pad2(n) {
    return (n < 10 ? '0' : '') + n;
  }

  normalizeDigits(str) {
    return String(str == null ? '' : str)
      .replace(/[۰-۹]/g, function (d) {
        return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
      })
      .replace(/[٠-٩]/g, function (d) {
        return '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
      });
  }

  /**
   * Run a conversion through the shared engine and normalize the result to
   * {y, m, d}. Returns null for out of range dates (the engine throws).
   */
  convert(method, args, keys) {
    try {
      var r = this.Engine[method](args[0], args[1], args[2]);
      return {y: r[keys[0]], m: r[keys[1]], d: r[keys[2]]};
    } catch (e) {
      return null;
    }
  }

  /** Gregorian Y/M/D -> Jalali {y, m, d}. */
  toJalali(gy, gm, gd) {
    return this.convert('toJalaali', [gy, gm, gd], ['jy', 'jm', 'jd']);
  }

  /** Jalali Y/M/D -> Gregorian {y, m, d}. */
  toGregorian(jy, jm, jd) {
    return this.convert('toGregorian', [jy, jm, jd], ['gy', 'gm', 'gd']);
  }

  /** 'Y-m-d' -> Jalali 'jY<jsep>jM<jsep>jD' (latin digits). */
  gregorianToJalaliString(g) {
    var m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(this.normalizeDigits(g || '').trim());
    if (!m) {
      return '';
    }
    var j = this.toJalali(+m[1], +m[2], +m[3]);
    if (!j) {
      return '';
    }
    return j.y + DirectoriesJalaliDatepicker.DATE_SEP + this.pad2(j.m) + DirectoriesJalaliDatepicker.DATE_SEP + this.pad2(j.d);
  }

  /** Jalali 'jY/jM/jD' (any digits) -> Gregorian 'Y-m-d'. */
  jalaliToGregorianString(s) {
    var m = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec(this.normalizeDigits(s || '').trim());
    if (!m) {
      return '';
    }
    var jy = +m[1], jm = +m[2], jd = +m[3];
    if (jm < 1 || jm > 12 || jd < 1 || jd > 31) {
      return '';
    }
    var g = this.toGregorian(jy, jm, jd);
    if (!g) {
      return '';
    }
    return g.y + '-' + this.pad2(g.m) + '-' + this.pad2(g.d);
  }

  /** 'H:i', 'H:i:s' or 'h:i A' (any digits) -> 'H:i' (latin, 24h). */
  normalizeTime(t) {
    t = this.normalizeDigits(t).trim().toUpperCase().replace(/\./g, '');
    var m = /^(\d{1,2}):(\d{2})(?::\d{2})?\s*([AP]M?)?$/.exec(t);
    if (!m) {
      return '';
    }
    var h = +m[1], min = m[2], ap = m[3] || '';
    if (ap.charAt(0) === 'P' && h < 12) {
      h += 12;
    }
    if (ap.charAt(0) === 'A' && h === 12) {
      h = 0;
    }
    if (h > 23) {
      return '';
    }
    return this.pad2(h) + ':' + min;
  }

  /* ------------------------------------------------------------------ *
   * Value marshalling
   * ------------------------------------------------------------------ */

  /** Whether a year belongs to the Jalali range of a rendered date. */
  isJalaliYear(year) {
    return year >= DirectoriesJalaliDatepicker.JALALI_YEAR_MIN
      && year <= DirectoriesJalaliDatepicker.JALALI_YEAR_MAX;
  }

  /**
   * Normalize one stored 'Y-m-d[ H:i]' piece to Gregorian 'Y-m-d[ H:i]'.
   * Jalali pieces (rendered server-side) are converted back to Gregorian,
   * Gregorian pieces are just zero-padded. Returns '' for non-date input.
   *
   * @param {string} raw One comma separated piece of a stored value
   * @param {boolean} withTime Keep the time when one is present
   * @returns {string}
   */
  normalizePiece(raw, withTime) {
    var parts = this.normalizeDigits(raw == null ? '' : raw).trim().split(/\s+/);
    var m = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec(parts[0] || '');
    if (!m) {
      return '';
    }

    var date;
    if (this.isJalaliYear(+m[1])) {
      var g = this.toGregorian(+m[1], +m[2], +m[3]);
      if (!g) {
        return '';
      }
      date = g.y + '-' + this.pad2(g.m) + '-' + this.pad2(g.d);
    } else {
      date = m[1] + '-' + this.pad2(+m[2]) + '-' + this.pad2(+m[3]);
    }

    if (withTime && parts[1]) {
      var t = this.normalizeTime(parts[1]);
      // Keep an unknown time format untouched instead of dropping it.
      date += DirectoriesJalaliDatepicker.TIME_SEP + (t || parts[1]);
    }

    return date;
  }

  /**
   * Normalize a stored Directories value to Gregorian.
   *
   * Accepts the hidden input value and settings.selected.dates[0]; both join
   * date range values with commas. Returns '' when any piece cannot be parsed
   * so callers can fall back to their previous behaviour.
   *
   * @param {string} value 'Y-m-d' or 'Y-m-d,Y-m-d', optionally with a time
   * @param {boolean} withTime Keep times
   * @returns {string}
   */
  normalizeValue(value, withTime) {
    value = this.normalizeDigits(value).trim();
    if (!value) {
      return '';
    }
    var parts = value.split(',');
    var out = [];
    for (var i = 0; i < parts.length; i++) {
      var piece = this.normalizePiece(parts[i], withTime);
      if (!piece) {
        return '';
      }
      out.push(piece);
    }
    return out.join(',');
  }

  /**
   * Build the hidden-input Gregorian value from the Directories calendar
   * options that PHP rendered (settings.selected.dates / settings.selected.time).
   */
  buildGregorianValue(selected, withTime) {
    if (!selected || !selected.dates || !selected.dates.length) {
      return '';
    }
    var value = this.normalizeValue(selected.dates[0], false);
    if (!value) {
      return '';
    }
    if (withTime && value.indexOf(',') === -1 && selected.time) {
      var t = this.normalizeTime(selected.time);
      if (t) {
        value += DirectoriesJalaliDatepicker.TIME_SEP + t;
      }
    }
    return value;
  }

  /** Hidden Gregorian value -> visible Jalali text. */
  gregorianValueToVisible(g, mode) {
    if (!g) {
      return '';
    }
    var out = [];
    var parts = String(g).split(',');
    for (var i = 0; i < parts.length; i++) {
      var piece = parts[i].trim().split(/\s+/);
      var j = this.gregorianToJalaliString(piece[0]);
      if (!j) {
        return '';
      }
      if (piece[1]) {
        var t = this.normalizeTime(piece[1]);
        if (t) {
          j += DirectoriesJalaliDatepicker.TIME_SEP + t;
        }
      }
      // jalaliDatepicker expects latin digits in the input value (persianDigits
      // only affects the calendar UI) and normalizes the value itself after a
      // pick, so keep the text field latin to avoid unparsable initial values.
      out.push(j);
    }
    return out.join(mode === 'range' ? DirectoriesJalaliDatepicker.RANGE_SEP : DirectoriesJalaliDatepicker.MULTI_SEP);
  }

  /** Visible Jalali text -> hidden Gregorian value (null when unparsable). */
  visibleToGregorianValue(v, mode) {
    v = this.normalizeDigits(v).trim();
    if (!v) {
      return '';
    }
    var rawParts;
    if (mode === 'range') {
      rawParts = v.split(/\s+-\s+|\s+to\s+/i);
    } else if (mode === 'multi') {
      rawParts = v.split(/\s*,\s*/);
    } else {
      rawParts = [v];
    }
    if (!rawParts.length) {
      return '';
    }
    var out = [];
    for (var i = 0; i < rawParts.length; i++) {
      var piece = String(rawParts[i]).trim().split(/\s+/);
      var g = this.jalaliToGregorianString(piece[0]);
      if (!g) {
        return null;
      }
      if (piece[1]) {
        var t = this.normalizeTime(piece[1]);
        if (t) {
          g += DirectoriesJalaliDatepicker.TIME_SEP + t;
        }
      }
      out.push(g);
    }
    return out.join(',');
  }

  /* ------------------------------------------------------------------ *
   * Global jalaliDatepicker watcher
   * ------------------------------------------------------------------ */

  /**
   * Whether an input should keep its date picker without a time section.
   * Every data-jdp input that is not a Directories date+time field has to opt
   * out explicitly, because the watcher shows one global time section.
   */
  markInputDateOnly = (el) => {
    if (!this.watcherArmed || this.settings.context === 'admin' || !el || !el.hasAttribute || !el.hasAttribute('data-jdp')) {
      return;
    }
    if (el.hasAttribute('data-jdp-only-date') || el.hasAttribute('data-jdp-only-time')) {
      return;
    }
    if (el.closest && el.closest(DirectoriesJalaliDatepicker.CONTAINER_SELECTOR)) {
      return; // handled per field by setupField()
    }
    el.setAttribute('data-jdp-only-date', '');
  };

  markOtherInputsDateOnly = (root) => {
    if (this.settings.context === 'admin' || !root || !root.querySelectorAll) {
      return; // wp-admin datepicker.js already marks its own fields
    }
    var inputs = root.querySelectorAll('input[data-jdp]');
    for (var i = 0; i < inputs.length; i++) {
      this.markInputDateOnly(inputs[i]);
    }
  };

  /**
   * Extend the shared jalaliDatepicker watcher with Directories inputs.
   *
   * In wp-admin wp-parsidate datepicker.js already started it (date separator
   * "-", time enabled, month names loaded), so only the selector is extended
   * and the existing options are preserved. On the front-end we may be the
   * first integration: enable time (date-only inputs opt out per input) and
   * load the localized month names.
   */
  armWatcher = () => {
    if (!window.jalaliDatepicker) {
      return false;
    }

    var options = {};
    var selectorExtended = false;

    // Read per-input min/max from data-jdp-min-date / data-jdp-max-date.
    options.minDate = 'attr';
    options.maxDate = 'attr';

    if (this.settings.context === 'admin') {
      // wp-parsidate datepicker.js owns the admin selector (ACF, WooCommerce,
      // …). Never replace it: only extend it when it is readable.
      if (typeof window.WPP_JALALI_SELECTOR === 'string' && window.WPP_JALALI_SELECTOR) {
        options.selector = window.WPP_JALALI_SELECTOR + ',' + DirectoriesJalaliDatepicker.FIELD_SELECTOR;
        selectorExtended = true;
      }
    } else {
      options.selector = 'input[data-jdp],' + DirectoriesJalaliDatepicker.FIELD_SELECTOR;
      selectorExtended = true;
    }

    if (this.settings.context !== 'admin') {
      options.time = true;
      // Directories stores times as "H:i"; the picker input must match.
      options.hasSecond = false;
      options.persianDigits = this.settings.persianDigits !== false;
      options.zIndex = 999999999;
      options.rangeSeparator = DirectoriesJalaliDatepicker.RANGE_SEP;
      options.multipleSeparator = DirectoriesJalaliDatepicker.MULTI_SEP;
      if (this.settings.months && this.settings.months.length === 12) {
        options.months = this.settings.months;
      }
    }

    try {
      window.jalaliDatepicker.startWatch(options);
    } catch (e) {
      return false;
    }

    this.watcherArmed = true;

    if (!selectorExtended) {
      this.bindFocusFallback();
    }

    this.markOtherInputsDateOnly(document);

    return true;
  };

  /**
   * Open the picker for Directories inputs when the shared watcher selector
   * could not be extended (wp-parsidate datepicker.js not loaded yet).
   */
  bindFocusFallback = () => {
    this.$(document).off('focusin.wppdDrtsJalali').on('focusin.wppdDrtsJalali', DirectoriesJalaliDatepicker.FIELD_SELECTOR, function () {
      if (window.jalaliDatepicker) {
        window.jalaliDatepicker.show(this);
      }
    });
  };

  /** Arm the watcher when a Directories date field is already in the DOM. */
  armWatcherIfNeeded = () => {
    if (this.watcherArmed || !document.querySelector(DirectoriesJalaliDatepicker.FIELD_SELECTOR)) {
      return;
    }
    this.armWatcher();
  };

  /* ------------------------------------------------------------------ *
   * Per-field setup
   * ------------------------------------------------------------------ */

  /** Read the Directories calendar options rendered on the visible input. */
  getFieldOptions($input) {
    var params = $input.data('calendar-options');
    if (!params || typeof params !== 'object') {
      return null;
    }

    var type = params.type || 'default';
    if (type === 'month' || type === 'year') {
      return null; // month/year pickers keep the original implementation
    }

    var calendarSettings = params.settings || {};
    var selection = calendarSettings.selection || {};
    var dayMode = selection.day || 'single';
    if (dayMode === 'multiple') {
      return null; // multi-date (non-range) selection is not supported
    }

    var time = selection.time;
    var withTime = !('multiple-ranged' === dayMode) &&
      (time === 24 || time === true || time === '24' || time === 'true');

    var mode = dayMode === 'multiple-ranged' ? 'range' : 'single';

    return {
      params: params,
      mode: mode,
      withTime: withTime,
      selected: calendarSettings.selected || {},
      date: params.date || {}
    };
  }

  /**
   * Convert one .drts-form-datepicker-inputs container to the Jalali picker.
   * Safe to call again for cloned repeater rows: everything it writes is
   * derived from the markup, so the clone (which keeps data-calendar-options
   * and the selected value) is converted as well.
   *
   * @param {jQuery} $date Container div rendered by Directories
   * @returns {boolean} true when handled, false to fall back to the original
   */
  setupField($date) {
    var self = this;
    var $input = $date.find(DirectoriesJalaliDatepicker.FIELD_SELECTOR);
    var $val = $date.find('.drts-form-datepicker-date-val');

    if (!$input.length || !$val.length) {
      return false;
    }
    if ($input[0]._wppdJalali) {
      return true; // same DOM node already converted (ajax re-init)
    }

    var options = this.getFieldOptions($input);
    if (!options) {
      return false;
    }

    // Hide the Vanilla Calendar container permanently — our Jalali picker
    // creates its own popup outside this div. This also neutralizes
    // Directories' focus.sabai handler (it checks ':visible' first) so the
    // Gregorian calendar never appears alongside the Jalali picker, even
    // when Directories' DOMContentLoaded inline script ran before our
    // override was installed.
    // NOTE: This runs only after getFieldOptions confirms a supported field
    // type, so unsupported fields (month/year/multi-date) keep their Vanilla
    // Calendar intact and fall back to the original Directories handler.
    var $cal = $date.find('.drts-form-datepicker-calendar');
    $cal.hide().addClass('wppd-jalali-replaced');
    // Remove Directories' jQuery focus handler that shows the calendar div.
    $input.off('focus.sabai');

    // The watcher is shared with other wp-parsidate integrations and may not
    // be armed yet (AJAX injected form on a page without Directories fields).
    if (!this.watcherArmed) {
      this.armWatcher();
    }

    // The stored value may already be Jalali (Directories' own init can seed
    // the hidden input from settings.selected.dates before our override takes
    // over). Keep the submitted value Gregorian and never convert it twice.
    var stored = String($val.val() || '');
    var gregorian = this.normalizeValue(stored, true);

    if (gregorian) {
      if (gregorian !== stored) {
        $val.val(gregorian);
      }
    } else {
      // Initial value rendered by Directories (settings.selected.dates): the
      // original implementation copies it into the hidden input, we do the same.
      gregorian = this.buildGregorianValue(options.selected, options.withTime);
      if (gregorian) {
        $val.val(gregorian);
      }
    }

    $input.attr('data-wppd-jalali', '1');
    $input.removeAttr('readonly'); // allow typing a Jalali date directly
    $input.attr('data-jdp', '');
    $input.removeAttr('data-jdp-only-time');
    if (options.withTime) {
      $input.removeAttr('data-jdp-only-date');
    } else {
      $input.attr('data-jdp-only-date', '');
    }

    if (options.mode === 'range') {
      $input.attr('data-jdp-mode', 'range');
    } else {
      $input.removeAttr('data-jdp-mode');
    }

    // Min/max dates come as Gregorian 'Y-m-d'
    [['min', 'data-jdp-min-date'], ['max', 'data-jdp-max-date']].forEach(function (pair) {
      var j = self.gregorianToJalaliString(options.date[pair[0]]);
      if (j) {
        $input.attr(pair[1], j);
      } else {
        $input.removeAttr(pair[1]);
      }
    });

    // For single-mode fields the picker itself can write the Gregorian value
    // into the hidden input; range fields are handled by our mirror below
    // (the picker joins range values with the range separator, Directories
    // expects a comma). A fresh id is required for cloned repeater rows.
    if (options.mode !== 'range') {
      $val.attr('id', 'wppd-drts-dp-' + (++this.fieldCounter));
      $input.attr('data-jdp-target-value-input', '#' + $val.attr('id'));
      $input.attr('data-jdp-target-value-type', 'gregorian');
    } else {
      $input.removeAttr('data-jdp-target-value-input');
      $input.removeAttr('data-jdp-target-value-type');
    }

    // Initial Jalali display value
    $input.val(gregorian ? this.gregorianValueToVisible(gregorian, options.mode) : '');

    // Clear button (the original handler belongs to the replaced implementation)
    var updateClear = function () {
      $date.find('.drts-clear').css('visibility', String($val.val() || '').length ? 'visible' : 'hidden');
    };

    $date.off('click.wppdJalali').on('click.wppdJalali', '.drts-clear', function () {
      $input.val('');
      $val.val('');
      updateClear();
      $date.closest('form').trigger('change.sabai');
    });

    // Mirror the visible Jalali value back into the hidden Gregorian input.
    var sync = function () {
      var g = self.visibleToGregorianValue($input.val(), options.mode);
      if (g === null) {
        return; // could not parse (user is still typing) -> keep old value
      }
      if (g !== $val.val()) {
        $val.val(g);
        updateClear();
        $date.closest('form').trigger('change.sabai');
      }
    };

    $input.off('change.wppdJalali blur.wppdJalali keyup.wppdJalali')
      .on('change.wppdJalali blur.wppdJalali keyup.wppdJalali', sync);

    // Form reset (entity_reset_form_field) clears the original inputs
    $date.closest('.drts-form-field').off('entity_reset_form_field.wppdJalali')
      .on('entity_reset_form_field.wppdJalali', function () {
        $input.val('');
        $val.val('');
        updateClear();
      });

    updateClear();

    $input[0]._wppdJalali = true;

    return true;
  }

  /* ------------------------------------------------------------------ *
   * DRTS override + late form injection
   * ------------------------------------------------------------------ */

  /**
   * Wrap DRTS.Form.field.datepicker so Directories initializes our Jalali
   * picker instead of the Gregorian Vanilla Calendar for every supported
   * field (initial render, AJAX forms and cloned repeater rows).
   */
  installOverride() {
    if (!window.DRTS || !window.DRTS.Form || !window.DRTS.Form.field) {
      return false;
    }
    if (window.DRTS.Form.field.datepicker && window.DRTS.Form.field.datepicker.__wppdJalali) {
      return true;
    }

    var original = window.DRTS.Form.field.datepicker;
    var self = this;
    var wrapped = function ($date) {
      var handled = false;
      try {
        handled = self.setupField($date);
      } catch (e) { /* fall back to the original implementation */ }
      if (!handled && original) {
        return original.apply(this, arguments);
      }
    };
    wrapped.__wppdJalali = true;
    window.DRTS.Form.field.datepicker = wrapped;

    return true;
  }

  /**
   * Watch for Directories forms and date inputs added after page load
   * (AJAX modals, dynamically rendered forms) so the watcher gets armed and
   * other integrations' inputs stay date-only.
   */
  observeForms() {
    if (this.observer || typeof MutationObserver === 'undefined' || !document.body) {
      return;
    }

    this.observer = new MutationObserver((mutations) => {
      for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].type === 'attributes') {
          // Another integration tagged an input with data-jdp after we armed
          // the watcher: keep it date-only, otherwise it would grow a time
          // section that only Directories datetime fields need.
          this.markInputDateOnly(mutations[i].target);
          continue;
        }
        var nodes = mutations[i].addedNodes;
        for (var j = 0; j < nodes.length; j++) {
          var node = nodes[j];
          if (!node || node.nodeType !== 1) {
            continue;
          }
          if (node.matches && node.matches(DirectoriesJalaliDatepicker.FIELD_SELECTOR)) {
            this.armWatcher();
          } else if (node.querySelector && node.querySelector(DirectoriesJalaliDatepicker.FIELD_SELECTOR)) {
            this.armWatcher();
          }
          if (this.watcherArmed) {
            this.markOtherInputsDateOnly(node);
          }
        }
      }
    });

    this.observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['data-jdp']
    });
  }

  /**
   * Entry point — wires up the override, seeds the watcher, and observes
   * late-injected forms.
   */
  boot = () => {
    var self = this;
    this.armWatcherIfNeeded();
    this.observeForms();

    var tries = 0;
    (function tryInstall() {
      if (self.installOverride()) {
        // Directories may have initialized a form before we replaced the
        // handler; convert whatever is already on the page.
        self.$(DirectoriesJalaliDatepicker.CONTAINER_SELECTOR).each(function () {
          try {
            self.setupField(self.$(this));
          } catch (e) { /* leave this field to the original implementation */ }
        });
        return;
      }
      // DRTS form scripts may not be loaded yet (or never, on pages without
      // Directories) — poll briefly, then stop.
      if (++tries < 12) {
        setTimeout(tryInstall, 250);
      }
    })();

    // run last so the selector survives other integrations' startWatch()
    if (document.readyState === 'complete') {
      setTimeout(function () {
        if (self.watcherArmed) {
          self.armWatcher();
        }
      }, 0);
    } else {
      window.addEventListener('load', function () {
        if (self.watcherArmed) {
          self.armWatcher();
        }
      });
    }
  };

}

if (window.jQuery && window.WpPdJalaliDate) {
  const instance = new DirectoriesJalaliDatepicker();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => instance.boot());
  } else {
    instance.boot();
  }
}

/******/ })()
;