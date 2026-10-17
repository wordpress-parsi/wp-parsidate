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
 * Requires: jalalidatepicker.min.js + jalali-date.js (WP-Parsidate),
 *           form-field-datepicker.min.js (Directories).
 */
(function () {
  'use strict';

  // jalali-date.js (window.WpPdJalaliDate) is the single conversion engine of
  // the plugin, enqueued as a dependency of this script. Without it nothing
  // here can produce a value Directories accepts, so leave the original
  // Directories datepicker in place instead of half-converting fields.
  if (!window.jQuery || !window.WpPdJalaliDate) {
    return;
  }

  var $ = window.jQuery;
  var Engine = window.WpPdJalaliDate;
  var settings = window.WpPdDrtsJalali || {};

  // Separator used in the visible Jalali value. Must match the active
  // jalaliDatepicker separatorChars.date: "-" in wp-admin (set by
  // wp-parsidate datepicker.js), "/" on the front-end (library default).
  var DATE_SEP = settings.dateSeparator === '-' ? '-' : '/';
  var RANGE_SEP = ' - ';
  var MULTI_SEP = ',';
  var TIME_SEP = ' ';
  var FIELD_SELECTOR = '.drts-form-datepicker-date';
  var CONTAINER_SELECTOR = '.drts-form-datepicker-inputs';

  // The jalaliDatepicker library keeps a single global instance: every
  // startWatch() call merges into the same options object and re-binds the
  // global listener with that call's selector. Track whether we extended it.
  var watcherArmed = false;
  var observer = null;
  var fieldCounter = 0;

  /* ------------------------------------------------------------------ *
   * Conversion helpers
   * ------------------------------------------------------------------ */

  function pad2(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function normalizeDigits(str) {
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
  function convert(method, args, keys) {
    try {
      var r = Engine[method](args[0], args[1], args[2]);
      return {y: r[keys[0]], m: r[keys[1]], d: r[keys[2]]};
    } catch (e) {
      return null;
    }
  }

  /** Gregorian Y/M/D -> Jalali {y, m, d}. */
  function toJalali(gy, gm, gd) {
    return convert('toJalaali', [gy, gm, gd], ['jy', 'jm', 'jd']);
  }

  /** Jalali Y/M/D -> Gregorian {y, m, d}. */
  function toGregorian(jy, jm, jd) {
    return convert('toGregorian', [jy, jm, jd], ['gy', 'gm', 'gd']);
  }

  /** 'Y-m-d' -> Jalali 'jY<jsep>jM<jsep>jD' (latin digits). */
  function gregorianToJalaliString(g) {
    var m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(normalizeDigits(g || '').trim());
    if (!m) {
      return '';
    }
    var j = toJalali(+m[1], +m[2], +m[3]);
    if (!j) {
      return '';
    }
    return j.y + DATE_SEP + pad2(j.m) + DATE_SEP + pad2(j.d);
  }

  /** Jalali 'jY/jM/jD' (any digits) -> Gregorian 'Y-m-d'. */
  function jalaliToGregorianString(s) {
    var m = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec(normalizeDigits(s || '').trim());
    if (!m) {
      return '';
    }
    var jy = +m[1], jm = +m[2], jd = +m[3];
    if (jm < 1 || jm > 12 || jd < 1 || jd > 31) {
      return '';
    }
    var g = toGregorian(jy, jm, jd);
    if (!g) {
      return '';
    }
    return g.y + '-' + pad2(g.m) + '-' + pad2(g.d);
  }

  /** 'H:i', 'H:i:s' or 'h:i A' (any digits) -> 'H:i' (latin, 24h). */
  function normalizeTime(t) {
    t = normalizeDigits(t).trim().toUpperCase().replace(/\./g, '');
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
    return pad2(h) + ':' + min;
  }

  /* ------------------------------------------------------------------ *
   * Value marshalling
   * ------------------------------------------------------------------ */

  /**
   * Build the hidden-input Gregorian value from the Directories calendar
   * options that PHP rendered (settings.selected.dates / settings.selected.time).
   */
  function buildGregorianValue(selected, withTime) {
    if (!selected || !selected.dates || !selected.dates.length) {
      return '';
    }
    var parts = normalizeDigits(String(selected.dates[0])).split(',').map(function (p) {
      var m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(p.trim());
      return m ? m[1] + '-' + pad2(+m[2]) + '-' + pad2(+m[3]) : '';
    }).filter(Boolean);
    if (!parts.length) {
      return '';
    }
    var value = parts.join(',');
    if (withTime && parts.length === 1 && selected.time) {
      var t = normalizeTime(selected.time);
      if (t) {
        value += TIME_SEP + t;
      }
    }
    return value;
  }

  /** Hidden Gregorian value -> visible Jalali text. */
  function gregorianValueToVisible(g, mode) {
    if (!g) {
      return '';
    }
    var out = [];
    var parts = String(g).split(',');
    for (var i = 0; i < parts.length; i++) {
      var piece = parts[i].trim().split(/\s+/);
      var j = gregorianToJalaliString(piece[0]);
      if (!j) {
        return '';
      }
      if (piece[1]) {
        var t = normalizeTime(piece[1]);
        if (t) {
          j += TIME_SEP + t;
        }
      }
      // jalaliDatepicker expects latin digits in the input value (persianDigits
      // only affects the calendar UI) and normalizes the value itself after a
      // pick, so keep the text field latin to avoid unparsable initial values.
      out.push(j);
    }
    return out.join(mode === 'range' ? RANGE_SEP : MULTI_SEP);
  }

  /** Visible Jalali text -> hidden Gregorian value (null when unparsable). */
  function visibleToGregorianValue(v, mode) {
    v = normalizeDigits(v).trim();
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
      var g = jalaliToGregorianString(piece[0]);
      if (!g) {
        return null;
      }
      if (piece[1]) {
        var t = normalizeTime(piece[1]);
        if (t) {
          g += TIME_SEP + t;
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
  function markInputDateOnly(el) {
    if (!watcherArmed || settings.context === 'admin' || !el || !el.hasAttribute || !el.hasAttribute('data-jdp')) {
      return;
    }
    if (el.hasAttribute('data-jdp-only-date') || el.hasAttribute('data-jdp-only-time')) {
      return;
    }
    if (el.closest && el.closest(CONTAINER_SELECTOR)) {
      return; // handled per field by setupField()
    }
    el.setAttribute('data-jdp-only-date', '');
  }

  function markOtherInputsDateOnly(root) {
    if (settings.context === 'admin' || !root || !root.querySelectorAll) {
      return; // wp-admin datepicker.js already marks its own fields
    }
    var inputs = root.querySelectorAll('input[data-jdp]');
    for (var i = 0; i < inputs.length; i++) {
      markInputDateOnly(inputs[i]);
    }
  }

  /**
   * Extend the shared jalaliDatepicker watcher with Directories inputs.
   *
   * In wp-admin wp-parsidate datepicker.js already started it (date separator
   * "-", time enabled, month names loaded), so only the selector is extended
   * and the existing options are preserved. On the front-end we may be the
   * first integration: enable time (date-only inputs opt out per input) and
   * load the localized month names.
   */
  function armWatcher() {
    if (!window.jalaliDatepicker) {
      return false;
    }

    var options = {};
    var selectorExtended = false;

    // Read per-input min/max from data-jdp-min-date / data-jdp-max-date.
    options.minDate = 'attr';
    options.maxDate = 'attr';

    if (settings.context === 'admin') {
      // wp-parsidate datepicker.js owns the admin selector (ACF, WooCommerce,
      // …). Never replace it: only extend it when it is readable.
      if (typeof window.WPP_JALALI_SELECTOR === 'string' && window.WPP_JALALI_SELECTOR) {
        options.selector = window.WPP_JALALI_SELECTOR + ',' + FIELD_SELECTOR;
        selectorExtended = true;
      }
    } else {
      options.selector = 'input[data-jdp],' + FIELD_SELECTOR;
      selectorExtended = true;
    }

    if (settings.context !== 'admin') {
      options.time = true;
      // Directories stores times as "H:i"; the picker input must match.
      options.hasSecond = false;
      options.persianDigits = settings.persianDigits !== false;
      options.zIndex = 999999999;
      options.rangeSeparator = RANGE_SEP;
      options.multipleSeparator = MULTI_SEP;
      if (settings.months && settings.months.length === 12) {
        options.months = settings.months;
      }
    }

    try {
      window.jalaliDatepicker.startWatch(options);
    } catch (e) {
      return false;
    }

    watcherArmed = true;

    if (!selectorExtended) {
      bindFocusFallback();
    }

    markOtherInputsDateOnly(document);

    return true;
  }

  /**
   * Open the picker for Directories inputs when the shared watcher selector
   * could not be extended (wp-parsidate datepicker.js not loaded yet).
   */
  function bindFocusFallback() {
    $(document).off('focusin.wppdDrtsJalali').on('focusin.wppdDrtsJalali', FIELD_SELECTOR, function () {
      if (window.jalaliDatepicker) {
        window.jalaliDatepicker.show(this);
      }
    });
  }

  /** Arm the watcher when a Directories date field is already in the DOM. */
  function armWatcherIfNeeded() {
    if (watcherArmed || !document.querySelector(FIELD_SELECTOR)) {
      return;
    }
    armWatcher();
  }

  /* ------------------------------------------------------------------ *
   * Per-field setup
   * ------------------------------------------------------------------ */

  /** Read the Directories calendar options rendered on the visible input. */
  function getFieldOptions($input) {
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
  function setupField($date) {
    var $input = $date.find(FIELD_SELECTOR);
    var $val = $date.find('.drts-form-datepicker-date-val');

    if (!$input.length || !$val.length) {
      return false;
    }
    if ($input[0]._wppdJalali) {
      return true; // same DOM node already converted (ajax re-init)
    }

    var options = getFieldOptions($input);
    if (!options) {
      return false;
    }

    // The watcher is shared with other wp-parsidate integrations and may not
    // be armed yet (AJAX injected form on a page without Directories fields).
    if (!watcherArmed) {
      armWatcher();
    }

    var gregorian = String($val.val() || '');

    // Initial value rendered by Directories (settings.selected.dates): the
    // original implementation copies it into the hidden input, we do the same.
    if (!gregorian) {
      gregorian = buildGregorianValue(options.selected, options.withTime);
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
      var j = gregorianToJalaliString(options.date[pair[0]]);
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
      $val.attr('id', 'wppd-drts-dp-' + (++fieldCounter));
      $input.attr('data-jdp-target-value-input', '#' + $val.attr('id'));
      $input.attr('data-jdp-target-value-type', 'gregorian');
    } else {
      $input.removeAttr('data-jdp-target-value-input');
      $input.removeAttr('data-jdp-target-value-type');
    }

    // Initial Jalali display value
    $input.val(gregorian ? gregorianValueToVisible(gregorian, options.mode) : '');

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
      var g = visibleToGregorianValue($input.val(), options.mode);
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
  function installOverride() {
    if (!window.DRTS || !window.DRTS.Form || !window.DRTS.Form.field) {
      return false;
    }
    if (window.DRTS.Form.field.datepicker && window.DRTS.Form.field.datepicker.__wppdJalali) {
      return true;
    }

    var original = window.DRTS.Form.field.datepicker;
    var wrapped = function ($date) {
      var handled = false;
      try {
        handled = setupField($date);
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
  function observeForms() {
    if (observer || typeof MutationObserver === 'undefined' || !document.body) {
      return;
    }

    observer = new MutationObserver(function (mutations) {
      for (var i = 0; i < mutations.length; i++) {
        if (mutations[i].type === 'attributes') {
          // Another integration tagged an input with data-jdp after we armed
          // the watcher: keep it date-only, otherwise it would grow a time
          // section that only Directories datetime fields need.
          markInputDateOnly(mutations[i].target);
          continue;
        }
        var nodes = mutations[i].addedNodes;
        for (var j = 0; j < nodes.length; j++) {
          var node = nodes[j];
          if (!node || node.nodeType !== 1) {
            continue;
          }
          if (node.matches && node.matches(FIELD_SELECTOR)) {
            armWatcher();
          } else if (node.querySelector && node.querySelector(FIELD_SELECTOR)) {
            armWatcher();
          }
          if (watcherArmed) {
            markOtherInputsDateOnly(node);
          }
        }
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['data-jdp']
    });
  }

  function boot() {
    armWatcherIfNeeded();
    observeForms();

    var tries = 0;
    (function tryInstall() {
      if (installOverride()) {
        // Directories may have initialized a form before we replaced the
        // handler; convert whatever is already on the page.
        $(CONTAINER_SELECTOR).each(function () {
          try {
            setupField($(this));
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
        if (watcherArmed) {
          armWatcher();
        }
      }, 0);
    } else {
      window.addEventListener('load', function () {
        if (watcherArmed) {
          armWatcher();
        }
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
