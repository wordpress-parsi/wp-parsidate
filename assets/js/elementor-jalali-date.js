/******/ (() => { // webpackBootstrap
/*!************************************************!*\
  !*** ./assets/js-src/elementor-jalali-date.js ***!
  \************************************************/
/**
 * Jalali dates for Elementor Pro form "Date" fields (front-end).
 *
 * Converts Elementor's native `input.elementor-date-field` (type=date, Gregorian,
 * yyyy-mm-dd) into a Jalali datepicker. The visible input becomes a text input
 * showing the Jalali date, while a hidden sibling input keeps the Gregorian
 * value so Elementor form submissions / emails stay in Gregorian format.
 *
 * Class structure:
 *   WpPdJalaliDateConverter      - static Gregorian <-> Jalali conversion helpers
 *   WpPdElementorJalaliDateField - wraps a single Elementor date field
 *   WpPdElementorJalaliDate      - orchestrator: boots the datepicker and scans the DOM
 *
 * Requires: jalalidatepicker.min.js (bundled with WP-Parsidate).
 * Optional : WpPdJalaliDate (js-admin/jalali-date.js) for accurate conversions,
 *            WPP_Elementor_Jalali (localized settings).
 */
(function () {
  'use strict';

  /**
   * Gregorian <-> Jalali conversion helpers.
   *
   * Prefers the bundled Borkowski-algorithm engine (WpPdJalaliDate) and falls
   * back to Intl (Persian calendar) or a pure-JS algorithm when unavailable.
   */
  class WpPdJalaliDateConverter {
    static pad(n) {
      return (n < 10 ? '0' : '') + n;
    }

    /**
     * Gregorian Y/M/D -> Jalali parts {year, month, day}
     */
    static toJalaliParts(gy, gm, gd) {
      if (window.WpPdJalaliDate) {
        try {
          const j = window.WpPdJalaliDate.toJalaali(gy, gm, gd);
          return { year: j.jy, month: j.jm, day: j.jd };
        } catch (e) { /* fall through to Intl */ }
      }

      // Fallback: Intl Persian calendar, latin digits.
      try {
        const parts = new Intl.DateTimeFormat('en-u-ca-persian-nu-latn', {
          year: 'numeric', month: 'numeric', day: 'numeric'
        }).formatToParts(new Date(gy, gm - 1, gd));
        const o = {};
        parts.forEach((p) => { o[p.type] = p.value; });
        return { year: +o.year, month: +o.month, day: +o.day };
      } catch (e) {
        return null;
      }
    }

    /**
     * Gregorian "yyyy-mm-dd" -> Jalali "yyyy/mm/dd" (for showing initial values).
     */
    static gregorianToJalali(s) {
      const m = /^(\d{4})-(\d{1,2})-(\d{1,2})$/.exec(s || '');
      if (!m) {
        return '';
      }

      const j = this.toJalaliParts(+m[1], +m[2], +m[3]);
      if (!j) {
        return '';
      }

      return j.year + '/' + this.pad(j.month) + '/' + this.pad(j.day);
    }

    /**
     * Jalali "yyyy/mm/dd" -> Gregorian "yyyy-mm-dd" (for manual typed values).
     */
    static jalaliToGregorian(s) {
      const m = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec((s || '').trim());
      if (!m) {
        return '';
      }

      const jy = +m[1], jm = +m[2], jd = +m[3];
      if (jm < 1 || jm > 12 || jd < 1 || jd > 31) {
        return '';
      }

      if (window.WpPdJalaliDate) {
        try {
          const g = window.WpPdJalaliDate.toGregorian(jy, jm, jd);
          return g.gy + '-' + this.pad(g.gm) + '-' + this.pad(g.gd);
        } catch (e) { /* fall through */ }
      }

      return this.fallbackJalaliToGregorian(jy, jm, jd);
    }

    /**
     * Fallback algorithm (Jalali -> Gregorian).
     */
    static fallbackJalaliToGregorian(jy, jm, jd) {
      let gy, gm, gd, days;
      let jyr = jy + 1595;

      days = -355668 + (365 * jyr) + (~~(jyr / 33) * 8) + ~~(((jyr % 33) + 3) / 4) + jd +
        ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
      gy = 400 * ~~(days / 146097);
      days %= 146097;
      if (days > 36524) {
        gy += 100 * ~~(--days / 36524);
        days %= 36524;
        if (days >= 365) { days++; }
      }
      gy += 4 * ~~(days / 1461);
      days %= 1461;
      if (days > 365) {
        gy += ~~((days - 1) / 365);
        days = (days - 1) % 365;
      }
      gd = days + 1;

      const monthLengths = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
      for (gm = 0; gm < 13 && gd > monthLengths[gm]; gm++) {
        gd -= monthLengths[gm];
      }

      return gy + '-' + this.pad(gm) + '-' + this.pad(gd);
    }
  }

  /**
   * Wraps a single Elementor date field: swaps type=date for a text input
   * wired to the Jalali picker and mirrors the Gregorian value into a hidden
   * sibling input.
   */
  class WpPdElementorJalaliDateField {
    constructor(input) {
      this.input = input;
      this.hidden = null;
      this.syncHandler = null;
    }

    get isReady() {
      return !!this.input.dataset.jdpReady;
    }

    /**
     * Prepare the field. Safe to call repeatedly; returns true only on first mount.
     */
    mount() {
      if (this.isReady) {
        this.destroyFlatpickr(true);
        return false;
      }

      this.destroyFlatpickr(false);

      const name = this.input.getAttribute('name');
      if (!name) {
        return false;
      }

      this.hidden = this.createHiddenInput(name);
      this.prepareInput();
      this.applyPickerAttributes();
      this.bindSync();
      this.bindFormReset();

      this.input._wpPdJalaliField = this;
      this.input.dataset.jdpReady = '1';

      return true;
    }

    destroyFlatpickr(resetType) {
      if (!this.input._flatpickr) {
        return;
      }

      try {
        this.input._flatpickr.destroy();

        if (resetType) {
          this.input.type = 'text';
        }
      } catch (e) { /* noop */ }
    }

    createHiddenInput(name) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = name;
      hidden.id = (this.input.id || 'jdp' + Math.random().toString(36).slice(2)) + '-greg';
      hidden.className = 'elementor-date-field-gregorian';
      hidden.value = this.input.value || '';

      this.input.parentNode.insertBefore(hidden, this.input.nextSibling);

      return hidden;
    }

    prepareInput() {
      this.input.removeAttribute('name');
      this.input.removeAttribute('pattern');
      this.input.setAttribute('type', 'text');
      this.input.setAttribute('autocomplete', 'off');

      if (this.hidden.value) {
        this.input.value = WpPdJalaliDateConverter.gregorianToJalali(this.hidden.value);
      }
    }

    applyPickerAttributes() {
      this.input.setAttribute('data-jdp', '');
      this.input.setAttribute('data-jdp-target-value-input', '#' + this.hidden.id);
      this.input.setAttribute('data-jdp-target-value-type', 'gregorian');

      if (this.input.getAttribute('min')) {
        this.input.setAttribute('data-jdp-min-date', WpPdJalaliDateConverter.gregorianToJalali(this.input.getAttribute('min')));
      }
      if (this.input.getAttribute('max')) {
        this.input.setAttribute('data-jdp-max-date', WpPdJalaliDateConverter.gregorianToJalali(this.input.getAttribute('max')));
      }
    }

    bindSync() {
      this.syncHandler = () => this.sync();

      ['change', 'blur', 'keyup'].forEach((evt) => {
        this.input.addEventListener(evt, this.syncHandler);
      });
    }

    /**
     * Keep the hidden Gregorian field in sync when the user types/changes.
     */
    sync() {
      const v = this.input.value.trim();
      if (!v) {
        this.hidden.value = '';
        return;
      }

      const g = WpPdJalaliDateConverter.jalaliToGregorian(v);
      if (g) {
        this.hidden.value = g;
      }
    }

    bindFormReset() {
      if (!this.input.form) {
        return;
      }

      this.input.form.addEventListener('reset', () => {
        setTimeout(() => {
          this.input.value = '';
          this.hidden.value = '';
        }, 0);
      });
    }
  }

  /**
   * Orchestrator: boots the Jalali datepicker and keeps scanning the DOM for
   * fields rendered later (popups, dynamic content, post-AJAX re-renders).
   */
  class WpPdElementorJalaliDate {
    constructor(settings) {
      this.settings = settings || {};
      this.fieldSelector = 'input.elementor-date-field';
      this.pollInterval = 500;
      this.pollMaxTicks = 20;
      this.pollTicks = 0;
      this.pollTimer = null;
    }

    init() {
      if (typeof window.jalaliDatepicker === 'undefined') {
        return false;
      }

      window.jalaliDatepicker.startWatch(this.buildPickerOptions());
      this.scan();
      this.bindListeners();
      this.startPolling();

      return true;
    }

    buildPickerOptions() {
      const options = {
        minDate: 'attr',
        maxDate: 'attr',
        targetValueInput: 'attr',
        targetValueType: 'attr'
      };

      if (this.settings.months && this.settings.months.length === 12) {
        options.months = this.settings.months;
      }

      if (typeof this.settings.persianDigits !== 'undefined') {
        options.persianDigits = !!this.settings.persianDigits;
      }

      return options;
    }

    /**
     * Prepare every Elementor date field under `root` (defaults to the document).
     */
    scan(root) {
      (root || document).querySelectorAll(this.fieldSelector).forEach((input) => this.setupField(input));
    }

    setupField(input) {
      if (input._wpPdJalaliField) {
        input._wpPdJalaliField.mount();
        return;
      }

      new WpPdElementorJalaliDateField(input).mount();
    }

    bindListeners() {
      // Fields rendered after the initial scan.
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.scan());
      }

      // Elementor popups render their forms on show.
      if (window.jQuery) {
        window.jQuery(document).on('elementor/popup/show', () => {
          setTimeout(() => this.scan(), 100);
        });
      }

      // After Elementor form submit (AJAX), fields are re-rendered.
      document.addEventListener('submit', () => {
        setTimeout(() => this.scan(), 800);
      }, true);

      window.wpParsidateElementorJalaliScan = (root) => this.scan(root);
    }

    startPolling() {
      this.pollTimer = setInterval(() => {
        this.scan();

        if (++this.pollTicks > this.pollMaxTicks) {
          this.stopPolling();
        }
      }, this.pollInterval);
    }

    stopPolling() {
      if (this.pollTimer) {
        clearInterval(this.pollTimer);
        this.pollTimer = null;
      }
    }
  }

  /* ------------------------------------------------------------------ *
   * Boot
   * ------------------------------------------------------------------ */

  window.WpPdJalaliDateConverter = WpPdJalaliDateConverter;
  window.WpPdElementorJalaliDateField = WpPdElementorJalaliDateField;
  window.WpPdElementorJalaliDate = WpPdElementorJalaliDate;

  window.wpParsidateElementorJalali = new WpPdElementorJalaliDate(window.WPP_Elementor_Jalali);
  window.wpParsidateElementorJalali.init();
})();

/******/ })()
;