/******/ (() => { // webpackBootstrap
/*!******************************************************!*\
  !*** ./assets/js-admin-src/elementor-jalali-date.js ***!
  \******************************************************/
/**
 * Jalali dates for Elementor Pro "Form Submissions" admin screen.
 *
 * The submissions table and detail view render dates through Backbone
 * templates that call moment().format(). We patch moment so any format that
 * contains the "[]" marker (used by Elementor's date templates) renders a
 * Jalali date, and additionally scan the DOM for any Gregorian dates that
 * slipped through (e.g. rendered before the patch was installed).
 *
 * Class structure:
 *   WpPdJalaliDisplayFormatter       - Intl-based Jalali formatting + Gregorian text conversion
 *   WpPdElementorJalaliMomentPatcher - patches moment so marked formats render Jalali
 *   WpPdElementorJalaliDomScanner    - DOM fallback: converts leftover Gregorian date strings
 *   WpPdElementorJalaliDateAdmin     - orchestrator: wires patcher + scanner and boots them
 *
 * Requires nothing; uses Intl (always available in modern browsers).
 */
(function () {
  'use strict';

  /**
   * Jalali date/time formatting and Gregorian text detection/conversion.
   *
   * Shared by the moment patcher and the DOM scanner so both render dates
   * through the exact same Intl formatter.
   */
  class WpPdJalaliDisplayFormatter {
    constructor() {
      this.dateFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian-nu-latn', {
        year: 'numeric', month: 'long', day: 'numeric'
      });

      // Persian + English Gregorian month spellings found in admin markup.
      this.gregorianMonths = {
        'ژانویه': 1, 'ژانويه': 1, 'فوریه': 2, 'فوريه': 2, 'مارس': 3, 'مارچ': 3,
        'آوریل': 4, 'آوريل': 4, 'اپریل': 4, 'مه': 5, 'می': 5, 'مي': 5, 'مِی': 5,
        'ژوئن': 6, 'جون': 6, 'ژوئیه': 7, 'ژوئيه': 7, 'جولای': 7, 'اوت': 8,
        'آگوست': 8, 'اگوست': 8, 'سپتامبر': 9, 'اکتبر': 10, 'نوامبر': 11, 'دسامبر': 12,
        'January': 1, 'February': 2, 'March': 3, 'April': 4, 'May': 5, 'June': 6,
        'July': 7, 'August': 8, 'September': 9, 'October': 10, 'November': 11, 'December': 12
      };

      this.gregorianDateRe = /^\s*([^\s\d,]+)\s+(\d{1,2}),\s*(\d{4})(?:\s+(\d{1,2}):(\d{2})\s*(ب\.ظ|ق\.ظ|[apAP]\.?[mM]\.?)?)?\s*$/;
    }

    static pad(n) {
      return (n < 10 ? '0' : '') + n;
    }

    /**
     * "1 مهر 1405" style Jalali date for a Date object.
     */
    formatJalaliDate(date) {
      return this.dateFormatter.format(date);
    }

    /**
     * "HH:MM" (24h) time string.
     */
    formatJalaliTime(hour, minute) {
      return WpPdJalaliDisplayFormatter.pad(hour) + ':' + WpPdJalaliDisplayFormatter.pad(minute);
    }

    /**
     * Convert a standalone Gregorian date string to Jalali.
     * Returns null when the text is not a recognizable Gregorian date.
     */
    convertGregorianText(txt) {
      const m = this.gregorianDateRe.exec(txt);
      if (!m || !this.gregorianMonths[m[1]]) {
        return null;
      }

      let h = m[4] ? parseInt(m[4], 10) : 0;
      const mi = m[5] ? parseInt(m[5], 10) : 0;
      const ap = m[6] || '';

      if (/ب\.ظ|p/i.test(ap) && h < 12) {
        h += 12;
      }
      if (/ق\.ظ|a/i.test(ap) && h === 12) {
        h = 0;
      }

      const d = new Date(parseInt(m[3], 10), this.gregorianMonths[m[1]] - 1, parseInt(m[2], 10), h, mi);

      return this.formatJalaliDate(d) + (m[4] ? ' ' + this.formatJalaliTime(h, mi) : '');
    }
  }

  /**
   * Patches moment so formats containing the "[]" marker (Elementor date
   * templates) render Jalali dates. Handles both "moment already loaded" and
   * "moment assigned later" cases via a property trap.
   */
  class WpPdElementorJalaliMomentPatcher {
    constructor(formatter) {
      this.formatter = formatter;
    }

    /**
     * Patch window.moment if present, otherwise trap future assignments.
     */
    install() {
      if (window.moment) {
        this.patch(window.moment);
        return true;
      }

      let _moment;
      try {
        const patcher = this;
        Object.defineProperty(window, 'moment', {
          configurable: true,
          enumerable: true,
          get: function () { return _moment; },
          set: function (v) { _moment = patcher.patch(v); }
        });
      } catch (e) { /* moment declared non-configurable; nothing to do */ }

      return false;
    }

    /**
     * Wrap moment.prototype.format. Idempotent via the __jalali flag.
     */
    patch(m) {
      if (!m || !m.prototype || m.prototype.__jalali) {
        return m;
      }

      const orig = m.prototype.format;
      const formatter = this.formatter;

      m.prototype.format = function (fmt) {
        try {
          if (typeof fmt === 'string' && fmt.indexOf('[]') > -1 && this.isValid()) {
            const d = new Date(this.year(), this.month(), this.date(), this.hour(), this.minute(), this.second());
            if (/Y{2,4}/.test(fmt)) {
              return formatter.formatJalaliDate(d);
            }
            if (/[hH]/.test(fmt) && /m/.test(fmt)) {
              return formatter.formatJalaliTime(this.hour(), this.minute());
            }
          }
        } catch (e) { /* fall through to original format */ }

        return orig.apply(this, arguments);
      };
      m.prototype.__jalali = true;

      return m;
    }
  }

  /**
   * DOM fallback: walks text nodes under the admin content area and converts
   * any Gregorian date strings that escaped the moment patch, re-scanning on
   * DOM mutations (Backbone re-renders).
   */
  class WpPdElementorJalaliDomScanner {
    constructor(formatter) {
      this.formatter = formatter;
      this.rootId = 'wpbody-content';
      this.debounceMs = 50;
      this.scanTimer = null;
      this.observer = null;
    }

    /**
     * Run the first scan and start observing; safe regardless of readyState.
     */
    init() {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.start());
        return;
      }

      this.start();
    }

    start() {
      this.scan();
      this.observe();
    }

    /**
     * Convert Gregorian date text nodes under `root` (defaults to the admin
     * content container).
     */
    scan(root) {
      const container = root || document.getElementById(this.rootId);
      if (!container) {
        return;
      }

      const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT, null);
      const matches = [];
      let node;

      while ((node = walker.nextNode())) {
        if (node.nodeValue && node.nodeValue.length < 40 && this.formatter.gregorianDateRe.test(node.nodeValue)) {
          matches.push(node);
        }
      }

      matches.forEach((n) => this.convertNode(n));
    }

    convertNode(node) {
      const v = this.formatter.convertGregorianText(node.nodeValue);
      if (v) {
        node.nodeValue = v;
      }
    }

    /**
     * Re-scan (debounced) whenever the DOM changes.
     */
    observe() {
      if (typeof MutationObserver === 'undefined') {
        return;
      }

      this.observer = new MutationObserver(() => this.scheduleScan());
      this.observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    scheduleScan() {
      clearTimeout(this.scanTimer);
      this.scanTimer = setTimeout(() => this.scan(), this.debounceMs);
    }

    stop() {
      clearTimeout(this.scanTimer);
      this.scanTimer = null;

      if (this.observer) {
        this.observer.disconnect();
        this.observer = null;
      }
    }
  }

  /**
   * Orchestrator: builds the shared formatter, installs the moment patch and
   * boots the DOM scanner.
   */
  class WpPdElementorJalaliDateAdmin {
    constructor() {
      this.formatter = new WpPdJalaliDisplayFormatter();
      this.momentPatcher = new WpPdElementorJalaliMomentPatcher(this.formatter);
      this.domScanner = new WpPdElementorJalaliDomScanner(this.formatter);
    }

    init() {
      this.momentPatcher.install();
      this.domScanner.init();

      window.wpParsidateElementorJalaliAdminScan = (root) => this.domScanner.scan(root);

      return true;
    }
  }

  /* ------------------------------------------------------------------ *
   * Boot
   * ------------------------------------------------------------------ */

  window.WpPdJalaliDisplayFormatter = WpPdJalaliDisplayFormatter;
  window.WpPdElementorJalaliMomentPatcher = WpPdElementorJalaliMomentPatcher;
  window.WpPdElementorJalaliDomScanner = WpPdElementorJalaliDomScanner;
  window.WpPdElementorJalaliDateAdmin = WpPdElementorJalaliDateAdmin;

  window.wpParsidateElementorJalaliAdmin = new WpPdElementorJalaliDateAdmin();
  window.wpParsidateElementorJalaliAdmin.init();
})();

/******/ })()
;