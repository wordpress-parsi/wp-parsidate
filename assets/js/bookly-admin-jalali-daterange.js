/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!********************************************************!*\
  !*** ./assets/js-src/bookly-admin-jalali-daterange.js ***!
  \********************************************************/


/**
 * Jalali presentation for Bookly's admin daterangepicker instances.
 *
 * Bookly builds its admin date filters (Appointments, Payments, Dashboard,
 * Cloud SMS/Voice/WhatsApp, booking wizard, customer dialog) and the
 * appointment dialog's date field on Dan Grossman's daterangepicker, which
 * formats through moment with an English locale.
 *
 * The underlying Bookly state must stay Gregorian, so this adapter never
 * rewrites the picker's dates or payloads. Instead it:
 *
 *   1. wraps `jQuery.fn.daterangepicker` so every instance created by Bookly
 *      is decorated as soon as Bookly builds it;
 *   2. covers the native field with a fixed-position Jalali input showing the
 *      same range in Persian letters/digits;
 *   3. opens WP-Parsidate's jalaliDatepicker instead of the native popup;
 *   4. converts the chosen Jalali date(s) back to Gregorian and pushes them
 *      into the instance through Bookly's own API (`setStartDate`,
 *      `setEndDate`, the picker callback and `apply.daterangepicker`).
 *
 * @package WP-Parsidate
 */

class WpPdBooklyAdminJalaliDaterange {
  constructor() {
    this.settings = window.WpPdBooklyAdminJalaliDaterange || {};
    this.months = this.settings.months || [];
    this.separator = this.settings.separator || ' - ';
    this.entries = new Map();
    this.observer = null;
    this.scheduled = false;
  }

  boot() {
    const $ = window.jQuery;

    if (!$ || !$.fn || !$.fn.daterangepicker || !window.WpPdJalaliDate || !window.jalaliDatepicker || !document.body) {
      return;
    }

    this.patchPickerShow();
    this.patchPickerFactory();
    this.decorateAll();

    document.addEventListener('scroll', () => this.positionAll(), true);
    window.addEventListener('resize', () => this.positionAll());
    this.observe();
  }

  /**
   * The shared picker keeps one mode for every field. Set it from the input
   * being opened so the calendar follows the field it belongs to.
   */
  patchPickerShow() {
    const picker = window.jalaliDatepicker;

    if (!picker || typeof picker.show !== 'function' || picker.__wppdModePatched) {
      return;
    }

    const originalShow = picker.show.bind(picker);
    const self = this;

    picker.show = function (input) {
      const mode = input && typeof input.getAttribute === 'function' && input.getAttribute('data-jdp-mode') === 'range'
        ? 'range'
        : 'single';

      try {
        picker.updateOptions({ mode: mode, rangeSeparator: self.separator });
      } catch (error) {
        // Older builds without updateOptions keep their current mode.
      }

      return originalShow(input);
    };

    picker.__wppdModePatched = true;
  }

  /**
   * Decorate every daterangepicker Bookly creates from now on.
   */
  patchPickerFactory() {
    const $ = window.jQuery;
    const original = $.fn.daterangepicker;
    const self = this;

    $.fn.daterangepicker = function () {
      const result = original.apply(this, arguments);

      this.each(function () {
        self.decorate(this);
      });

      return result;
    };
  }

  decorateAll() {
    if (!document.querySelectorAll) {
      return;
    }

    const $ = window.jQuery;

    document.querySelectorAll('input, button, .ec-title, [class*="bookly-js-date"]').forEach((element) => {
      if ($(element).data('daterangepicker')) {
        this.decorate(element);
      }
    });
  }

  /**
   * The administrative calendar title has its own overlay in
   * bookly-admin-jalali-calendar.js; leave that element alone.
   */
  isReserved(element) {
    return !!(element.closest && element.closest('.bookly-js-calendar .ec-toolbar'));
  }

  decorate(element) {
    const $ = window.jQuery;

    if (!element || this.isReserved(element)) {
      return;
    }

    const picker = $(element).data('daterangepicker');

    if (!picker || !picker.container) {
      return;
    }

    const existing = this.entries.get(element);

    if (existing && existing.picker === picker) {
      this.position(existing);
      return;
    }

    if (existing && existing.overlay && existing.overlay.parentNode) {
      existing.overlay.parentNode.removeChild(existing.overlay);
      this.entries.delete(element);
    }

    const overlay = document.createElement('input');
    overlay.type = 'text';
    overlay.className = 'wppd-bookly-jalali-date-range';
    overlay.setAttribute('data-jdp', '');
    overlay.setAttribute('data-jdp-only-date', '');
    overlay.setAttribute('autocomplete', 'off');
    overlay.setAttribute('aria-label', element.getAttribute('aria-label') || '');
    overlay.setAttribute('data-jdp-mode', picker.singleDatePicker ? 'single' : 'range');
    overlay.style.position = 'fixed';
    overlay.style.zIndex = '100000';
    overlay.style.boxSizing = 'border-box';
    overlay.style.margin = '0';

    const entry = {
      element,
      picker,
      overlay,
      single: !!picker.singleDatePicker,
    };

    overlay.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      this.open(entry);
    });
    overlay.addEventListener('change', () => this.scheduleApply(entry));
    overlay.addEventListener('jdp:change', () => this.scheduleApply(entry));
    overlay.addEventListener('blur', () => {
      window.setTimeout(() => this.refreshText(entry), 0);
    });
    overlay.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' || event.key === 'Esc') {
        overlay.blur();
      }
    });

    const elementEl = element;
    elementEl.setAttribute('tabindex', '-1');
    elementEl.addEventListener('focus', (event) => {
      event.preventDefault();
      if (typeof picker.hide === 'function') {
        picker.hide();
      }
      elementEl.blur();
    });

    this.wrapInstance(entry);

    document.body.appendChild(overlay);
    this.entries.set(element, entry);
    this.refreshText(entry);
    this.position(entry);
  }

  /**
   * Keep the visible Jalali text in sync when Bookly moves the picker
   * programmatically (filters restoring a saved range, the appointment dialog
   * loading a record, the dialog's own setStartDate calls).
   */
  wrapInstance(entry) {
    const picker = entry.picker;
    const self = this;

    ['setStartDate', 'setEndDate', 'updateElement'].forEach((method) => {
      if (typeof picker[method] !== 'function' || picker[method].__wppdWrapped) {
        return;
      }

      const original = picker[method].bind(picker);
      const wrapped = function () {
        const result = original.apply(null, arguments);
        self.refreshText(entry);
        self.position(entry);
        return result;
      };

      wrapped.__wppdWrapped = true;
      picker[method] = wrapped;
    });
  }

  refreshText(entry) {
    const picker = entry.picker;
    const start = picker.startDate && picker.startDate.format ? picker.startDate.format('YYYY-MM-DD') : '';
    let text = start ? this.formatDate(start) : '';

    if (!entry.single) {
      const end = picker.endDate && picker.endDate.format ? picker.endDate.format('YYYY-MM-DD') : '';
      text += (text ? this.separator : '') + (end ? this.formatDate(end) : '');
    }

    if (entry.overlay.value !== text) {
      entry.overlay.value = text;
    }
  }

  position(entry) {
    const rect = entry.element.getBoundingClientRect();
    const style = window.getComputedStyle ? window.getComputedStyle(entry.element) : null;
    const hidden = !rect.width || !rect.height || (style && (style.visibility === 'hidden' || style.display === 'none'));

    if (hidden) {
      entry.overlay.style.display = 'none';
      return;
    }

    entry.overlay.style.display = '';
    entry.overlay.style.left = rect.left + 'px';
    entry.overlay.style.top = rect.top + 'px';
    entry.overlay.style.width = rect.width + 'px';
    entry.overlay.style.height = rect.height + 'px';

    if (style) {
      entry.overlay.style.font = style.font;
      entry.overlay.style.fontFamily = style.fontFamily;
      entry.overlay.style.fontSize = style.fontSize;
      entry.overlay.style.color = style.color;
      entry.overlay.style.direction = style.direction;
      entry.overlay.style.textAlign = style.textAlign === 'start' ? 'right' : style.textAlign;
      entry.overlay.style.background = style.backgroundColor;
      entry.overlay.style.border = '0';
      entry.overlay.style.padding = style.padding;
    }
  }

  positionAll() {
    this.entries.forEach((entry) => this.position(entry));
  }

  open(entry) {
    const picker = entry.picker;

    if (typeof picker.hide === 'function') {
      picker.hide();
    }

    const start = picker.startDate && picker.startDate.format ? picker.startDate.format('YYYY-MM-DD') : '';
    const end = picker.endDate && picker.endDate.format ? picker.endDate.format('YYYY-MM-DD') : '';
    const startJalali = start ? this.toJalaliIso(start) : '';
    const endJalali = end ? this.toJalaliIso(end) : '';

    entry.overlay.value = entry.single || !endJalali
      ? startJalali
      : startJalali + this.separator + endJalali;

    this.position(entry);
    window.jalaliDatepicker.show(entry.overlay);
  }

  scheduleApply(entry) {
    if (entry.single) {
      this.apply(entry);
      return;
    }

    window.clearTimeout(entry.timer);
    entry.timer = window.setTimeout(() => this.apply(entry), 400);
  }

  apply(entry) {
    const value = String(entry.overlay.value || '').trim();
    const parts = value.split(/\s+-\s+|\s+to\s+/i);
    const start = this.parseDate(parts[0]);
    const end = entry.single ? start : this.parseDate(parts[1] || parts[0]);

    if (!start || !end) {
      this.refreshText(entry);
      return;
    }

    const picker = entry.picker;
    const startDate = new Date(start.gy, start.gm - 1, start.gd);
    const endDate = new Date(end.gy, end.gm - 1, end.gd);

    if (typeof picker.setStartDate === 'function') {
      picker.setStartDate(startDate);
    }
    if (typeof picker.setEndDate === 'function') {
      picker.setEndDate(endDate);
    }

    const startMoment = picker.startDate && picker.startDate.clone ? picker.startDate.clone() : null;
    const endMoment = picker.endDate && picker.endDate.clone ? picker.endDate.clone() : null;

    if (startMoment && endMoment && typeof picker.callback === 'function') {
      picker.callback(startMoment, endMoment, picker.chosenLabel);
    }

    if (startMoment && endMoment) {
      window.jQuery(entry.element).trigger('apply.daterangepicker', [startMoment, endMoment]);
    }

    this.refreshText(entry);
  }

  observe() {
    if (!window.MutationObserver || this.observer) {
      return;
    }

    this.observer = new window.MutationObserver(() => {
      if (this.scheduled) {
        return;
      }

      this.scheduled = true;
      window.requestAnimationFrame(() => {
        this.scheduled = false;
        this.decorateAll();
        this.entries.forEach((entry, element) => {
          if (!element.isConnected) {
            if (entry.overlay.parentNode) {
              entry.overlay.parentNode.removeChild(entry.overlay);
            }
            this.entries.delete(element);
            return;
          }
          this.position(entry);
        });
      });
    });

    this.observer.observe(document.body, { childList: true, subtree: true });
  }

  formatDate(value) {
    const jalali = this.toJalali(value);

    if (!jalali) {
      return '';
    }

    const month = this.months[jalali.jm - 1];

    return month
      ? this.toPersianDigits(jalali.jd + ' ' + month + ' ' + jalali.jy)
      : '';
  }

  toJalaliIso(value) {
    const jalali = this.toJalali(value);

    return jalali
      ? jalali.jy + '-' + this.pad(jalali.jm) + '-' + this.pad(jalali.jd)
      : '';
  }

  toJalali(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));

    if (!match) {
      return null;
    }

    const gregorian = { gy: +match[1], gm: +match[2], gd: +match[3] };
    const parsed = new Date(Date.UTC(gregorian.gy, gregorian.gm - 1, gregorian.gd));

    if (
      gregorian.gm < 1 || gregorian.gm > 12 || gregorian.gd < 1 || gregorian.gd > 31 ||
      parsed.getUTCFullYear() !== gregorian.gy ||
      parsed.getUTCMonth() !== gregorian.gm - 1 ||
      parsed.getUTCDate() !== gregorian.gd
    ) {
      return null;
    }

    try {
      return window.WpPdJalaliDate.toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);
    } catch (error) {
      return null;
    }
  }

  parseDate(value) {
    const match = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec(this.toEnglishDigits(value).trim());

    if (!match) {
      return null;
    }

    const jalali = { jy: +match[1], jm: +match[2], jd: +match[3] };

    if (jalali.jm < 1 || jalali.jm > 12 || jalali.jd < 1 || jalali.jd > 31) {
      return null;
    }

    let gregorian;

    try {
      gregorian = window.WpPdJalaliDate.toGregorian(jalali.jy, jalali.jm, jalali.jd);
    } catch (error) {
      return null;
    }

    const roundTrip = window.WpPdJalaliDate.toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);

    return roundTrip.jy === jalali.jy && roundTrip.jm === jalali.jm && roundTrip.jd === jalali.jd
      ? gregorian
      : null;
  }

  pad(value) {
    return String(value).padStart(2, '0');
  }

  toEnglishDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }
}

/**
 * Jalali entry for Bookly's own admin range calendar.
 *
 * The Appointments/Payments/Dashboard/Cloud date filters do not use
 * daterangepicker: Bookly renders a custom range calendar whose day cells are
 * `<div data-value="YYYY-MM-DD">` and whose month/year pickers are native
 * selects. That calendar stays the source of truth. This adapter hides its
 * Gregorian grids, adds a Jalali range field in the same popover, and on a
 * chosen range drives the hidden calendar by selecting the matching Gregorian
 * cells and pressing Bookly's own Apply button.
 */
class WpPdBooklyAdminJalaliRangeCalendar {
  constructor() {
    this.settings = window.WpPdBooklyAdminJalaliDaterange || {};
    this.months = this.settings.months || [];
    this.separator = this.settings.separator || ' - ';
    this.observer = null;
    this.scheduled = false;
    this.activePopover = null;
    this.busy = false;
    this.entries = new WeakMap();
    this.gregorianMonthMap = null;
  }

  boot() {
    if (!window.jQuery || !window.WpPdJalaliDate || !window.jalaliDatepicker || !document.body) {
      return;
    }

    this.guardPickerClicks();
    this.decorateAll();
    this.localizeTriggers();
    this.observe();
  }

  /**
   * The Jalali popup is portalled to <body>, so clicking it would look like an
   * outside click to Bookly's popover and close the calendar we are driving.
   */
  guardPickerClicks() {
    document.addEventListener('pointerdown', (event) => {
      const target = event.target;

      if (!this.activePopover || !target || !target.closest) {
        return;
      }

      if (target.closest('jdp-container')) {
        event.stopPropagation();
      }
    }, true);
  }

  /**
   * Bookly prints the applied range beside the trigger in Gregorian month
   * names. The range itself stays Gregorian; only the label is rewritten.
   */
  localizeTriggers() {
    if (!document.querySelectorAll) {
      return;
    }

    document.querySelectorAll('[data-popover-trigger]').forEach((trigger) => {
      trigger.querySelectorAll('span').forEach((span) => {
        const text = span.textContent || '';
        const title = span.getAttribute('title') || '';
        const dates = this.parseGregorianLabel(text) || this.parseGregorianLabel(title);

        if (!dates) {
          return;
        }

        const localized = this.formatGregorianDates(dates);

        if (!localized) {
          return;
        }

        this.setText(span, localized);

        if (title && title !== localized) {
          span.setAttribute('title', localized);
        }
      });
    });
  }

  /**
   * Keep the node Bookly's framework updates instead of replacing all of the
   * span's children; otherwise a later render would write into a detached node.
   */
  setText(element, value) {
    const nodes = element.childNodes ? Array.from(element.childNodes) : null;

    if (nodes) {
      const textNode = nodes.find((node) => node.nodeType === 3);

      if (textNode) {
        if (textNode.nodeValue !== value) {
          textNode.nodeValue = value;
        }
        return;
      }

      const child = nodes.find((node) => node.nodeType === 1);

      if (child && child !== element) {
        this.setText(child, value);
        return;
      }
    }

    if (element.textContent !== value) {
      element.textContent = value;
    }
  }

  /**
   * Month names come from Bookly's own date-picker localization so the label
   * parses in every locale Bookly is translated to.
   */
  gregorianMonths() {
    if (this.gregorianMonthMap) {
      return this.gregorianMonthMap;
    }

    const map = new Map();
    const add = (list) => {
      if (!Array.isArray(list)) {
        return;
      }

      list.forEach((name, index) => {
        if (!name || index > 11) {
          return;
        }

        const key = String(name);

        if (!map.has(key)) {
          map.set(key, index + 1);
        }
      });
    };
    const sources = [
      window.BooklyL10nGlobal && window.BooklyL10nGlobal.datePicker,
      window.BooklyL10n && window.BooklyL10n.datePicker,
    ];

    sources.forEach((picker) => {
      if (!picker) {
        return;
      }

      add(picker.monthNames);
      add(picker.monthNamesShort);
    });

    if (!map.size) {
      [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
      ].forEach((name, index) => map.set(name, index + 1));
    }

    this.gregorianMonthMap = map;

    return map;
  }

  parseGregorianLabel(value) {
    // Bookly prints an en dash in some locales, and collapses same-month
    // ranges to "December 3 - 9, 2026".
    const text = this.toEnglishDigits(value || '')
      .replace(/[\u2013\u2014\u2212]/g, '-')
      .trim();

    if (!text) {
      return null;
    }

    const months = this.gregorianMonths();

    if (!months.size) {
      return null;
    }

    const names = Array.from(months.keys())
      .sort((a, b) => b.length - a.length)
      .map((name) => name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
    const month = '(' + names.join('|') + ')';
    const monthFirstRange = new RegExp(
      month + '\\s+(\\d{1,2})(?!\\d)\\s*-\\s*(\\d{1,2})(?!\\d)(?:\\s*,\\s*(\\d{4}))?',
      'g'
    );
    const dayFirstRange = new RegExp(
      '(\\d{1,2})(?!\\d)\\s*-\\s*(\\d{1,2})(?!\\d)\\s+' + month + '(?:\\s*,\\s*(\\d{4}))?',
      'g'
    );
    const monthFirst = new RegExp(month + '\\s+(\\d{1,2})(?!\\d)(?:\\s*,\\s*(\\d{4}))?', 'g');
    const dayFirst = new RegExp('(\\d{1,2})(?!\\d)\\s+' + month + '(?:\\s*,\\s*(\\d{4}))?', 'g');
    const found = []
      .concat(this.collectGregorianMatches(monthFirstRange, 'monthFirstRange', text))
      .concat(this.collectGregorianMatches(dayFirstRange, 'dayFirstRange', text))
      .concat(this.collectGregorianMatches(monthFirst, 'monthFirst', text))
      .concat(this.collectGregorianMatches(dayFirst, 'dayFirst', text))
      .sort((a, b) => a.index - b.index || b.end - a.end);

    // A collapsed range also matches its own first day as a single date; keep
    // the longer match and drop the overlap.
    let covered = -1;
    const matched = [];

    found.forEach((item) => {
      if (item.index < covered) {
        return;
      }

      covered = Math.max(covered, item.end);
      matched.push(item);
    });

    if (!matched.length) {
      return null;
    }

    let fallbackYear = 0;

    matched.forEach((item) => {
      if (!fallbackYear && item.year) {
        fallbackYear = item.year;
      }
    });

    fallbackYear = fallbackYear || new Date().getFullYear();

    const dates = [];

    matched.forEach((item) => {
      const gy = item.year || fallbackYear;

      [item.day, item.endDay].forEach((day) => {
        if (!day) {
          return;
        }

        const date = { gy, gm: item.month, gd: day };

        if (this.isValidGregorian(gy, item.month, day) && !dates.some(
          (existing) => existing.gy === date.gy && existing.gm === date.gm && existing.gd === date.gd
        )) {
          dates.push(date);
        }
      });
    });

    return dates.length ? dates : null;
  }

  collectGregorianMatches(regex, kind, text) {
    const matches = [];
    const months = this.gregorianMonths();
    let match;

    while ((match = regex.exec(text)) !== null) {
      let monthName;
      let day;
      let endDay = 0;
      let year;

      if (kind === 'monthFirstRange') {
        monthName = match[1];
        day = +match[2];
        endDay = +match[3];
        year = match[4] ? +match[4] : 0;
      } else if (kind === 'dayFirstRange') {
        day = +match[1];
        endDay = +match[2];
        monthName = match[3];
        year = match[4] ? +match[4] : 0;
      } else if (kind === 'dayFirst') {
        day = +match[1];
        monthName = match[2];
        year = match[3] ? +match[3] : 0;
      } else {
        monthName = match[1];
        day = +match[2];
        year = match[3] ? +match[3] : 0;
      }

      const month = months.get(monthName);

      if (month) {
        matches.push({ index: match.index, end: match.index + match[0].length, month, day, endDay, year });
      }

      if (match[0].length === 0) {
        regex.lastIndex++;
      }
    }

    return matches;
  }

  isValidGregorian(gy, gm, gd) {
    if (gy < 1000 || gm < 1 || gm > 12 || gd < 1 || gd > 31) {
      return false;
    }

    const parsed = new Date(Date.UTC(gy, gm - 1, gd));

    return parsed.getUTCFullYear() === gy &&
      parsed.getUTCMonth() === gm - 1 &&
      parsed.getUTCDate() === gd;
  }

  formatGregorianDates(dates) {
    if (!dates || !dates.length) {
      return '';
    }

    const start = this.formatDate(this.toIso(dates[0]));

    if (!start) {
      return '';
    }

    if (dates.length < 2) {
      return start;
    }

    const end = this.formatDate(this.toIso(dates[dates.length - 1]));

    return end ? start + this.separator + end : start;
  }

  /**
   * The applied range is already selected in Bookly's hidden calendar; reuse
   * it so the Jalali field opens on the current range instead of empty or on
   * today.
   */
  selectedRange(popover) {
    const root = popover && popover.querySelector ? popover.querySelector('[data-range-calendar-root]') : null;

    if (!root) {
      return null;
    }

    const values = [];

    root.querySelectorAll('[data-range-calendar-day][data-selected]').forEach((cell) => {
      const value = cell.getAttribute('data-value');

      if (/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
        values.push(value);
      }
    });

    if (!values.length) {
      return null;
    }

    values.sort();

    return { start: values[0], end: values[values.length - 1] };
  }

  rangeValue(popover, single) {
    const range = this.selectedRange(popover);

    if (!range) {
      return '';
    }

    const start = this.formatDate(range.start);

    if (!start) {
      return '';
    }

    if (single || range.end === range.start) {
      return start;
    }

    const end = this.formatDate(range.end);

    return end ? start + this.separator + end : start;
  }

  /**
   * The picker sets inline `display`/`visibility`, but the admin stylesheet
   * forces `display: grid !important`, so only `visibility` reflects whether
   * the popup is really open.
   */
  pickerVisible() {
    const container = document.querySelector('jdp-container');

    if (!container) {
      return false;
    }

    const style = getComputedStyle(container);

    return style.visibility !== 'hidden' && container.offsetHeight > 0;
  }

  seedRangeInput(entry) {
    if (this.pickerVisible() || entry.dirty) {
      return;
    }

    const popover = entry.input.closest('[data-slot="popover-content"]')
      || (entry.popover && entry.popover.isConnected ? entry.popover : null);

    if (!popover) {
      return;
    }

    // A value the user picked but Bookly has not applied yet must survive the
    // re-render that follows; only replace an empty or previously seeded field.
    if (entry.input.value && entry.input.value !== entry.seeded) {
      return;
    }

    const value = this.rangeValue(popover, entry.single);

    if (value) {
      entry.input.value = value;
      entry.seeded = value;
    }
  }

  decorateAll() {
    if (!document.querySelectorAll) {
      return;
    }

    document.querySelectorAll('[data-slot="popover-content"]').forEach((popover) => {
      if (popover.getAttribute('data-state') === 'closed') {
        return;
      }

      if (popover.querySelector('[data-range-calendar-root]')) {
        this.decorate(popover);
      }
    });
  }

  decorate(popover) {
    const existing = this.entries.get(popover);

    if (popover.dataset.wppdJalaliFilter === '1') {
      this.activePopover = popover;

      if (existing) {
        this.seedRangeInput(existing);
      }

      return;
    }

    const root = popover.querySelector('[data-range-calendar-root]');

    if (!root) {
      return;
    }

    // Filters confirm with an Apply button; the appointment date popover
    // applies the moment a day is pressed, so it takes a single date.
    const single = !Array.from(popover.querySelectorAll('button')).some(
      (button) => (button.textContent || '').trim() === 'Apply'
    );

    popover.dataset.wppdJalaliFilter = '1';
    root.style.display = 'none';

    const field = document.createElement('div');
    field.className = 'wppd-bookly-jalali-filter';
    field.style.display = 'flex';
    field.style.padding = '12px 12px 0';
    field.style.direction = 'rtl';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'bookly:input bookly:input-h-9 wppd-bookly-jalali-filter-input';
    input.style.width = '100%';
    input.setAttribute('data-jdp', '');
    input.setAttribute('data-jdp-only-date', '');
    input.setAttribute('data-jdp-mode', single ? 'single' : 'range');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute(
      'placeholder',
      single
        ? this.settings.datePlaceholder || this.settings.rangePlaceholder || ''
        : this.settings.rangePlaceholder || ''
    );

    field.appendChild(input);
    popover.insertBefore(field, popover.firstChild);

    const entry = { popover, input, single, watch: null, lastCommitted: '', seeded: '', dirty: false };

    this.entries.set(popover, entry);
    this.seedRangeInput(entry);

    input.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      this.activePopover = popover;
      window.clearTimeout(this.timer);

      // Open on the range Bookly already applied. Only an empty filter falls
      // back to today, so closing the picker cannot silently change anything.
      if (!input.value) {
        const current = this.rangeValue(popover, entry.single);
        const today = this.todayJalali();
        input.value = current || (today
          ? (entry.single ? today : today + this.separator + today)
          : '');
        entry.seeded = input.value;
      }

      window.jalaliDatepicker.show(input);
      this.watchValue(entry);
    });
    input.addEventListener('change', () => {
      entry.dirty = true;
      this.schedule(entry);
    });
    input.addEventListener('jdp:change', () => {
      entry.dirty = true;
      this.schedule(entry);
    });

    popover.addEventListener('click', () => {
      this.activePopover = popover;
    });

    this.activePopover = popover;
  }

  /**
   * The picker does not always fire change events reliably, so once it closes
   * compare its value with the last one we pushed into Bookly ourselves.
   */
  watchValue(entry) {
    window.clearInterval(entry.watch);

    const initial = entry.input.value;
    let settled = entry.input.value;
    let settledAt = 0;

    entry.watch = window.setInterval(() => {
      const open = this.pickerVisible();
      const value = entry.input.value;

      if (value !== settled) {
        settled = value;
        settledAt = Date.now();
      }

      if (!open) {
        window.clearInterval(entry.watch);
        entry.watch = null;

        // Closing without choosing must not apply the seeded "today" value.
        if (value && value !== initial && value !== entry.lastCommitted) {
          this.commit(entry);
        }
        return;
      }

      // The picker can stay open after a range is chosen; once its value stops
      // changing, that is the user's choice.
      if (settledAt && Date.now() - settledAt > 700 && value !== entry.lastCommitted) {
        settledAt = 0;
        this.commit(entry);
      }
    }, 250);
  }

  /**
   * The picker reports a change after each of the two clicks that make up a
   * range, so wait until the value settles before driving Bookly.
   */
  schedule(entry) {
    window.clearTimeout(this.timer);
    this.timer = window.setTimeout(() => this.commit(entry), 400);
  }

  async commit(entry) {
    // Popovers can be re-created on open, so always resolve the one that owns
    // this field instead of trusting the node captured at decoration time.
    const popover = entry.input.closest('[data-slot="popover-content"]')
      || (entry.popover.isConnected ? entry.popover : null);

    if (!popover) {
      return;
    }

    const now = Date.now();

    // Guard against overlapping runs instead of a lock flag, which one failed
    // run could leave stuck and silently disable the adapter.
    if (this.busy && now - this.busy < 1000) {
      return;
    }

    if (entry.lastCommitted && entry.lastCommitted === entry.input.value) {
      return;
    }

    entry.popover = popover;
    this.busy = now;

    try {
      if (await this.commitRange(entry)) {
        entry.lastCommitted = entry.input.value;
        entry.seeded = entry.input.value;
        entry.dirty = false;
        this.hidePicker();
      }
    } finally {
      this.busy = 0;
    }
  }

  hidePicker() {
    if (window.jalaliDatepicker && typeof window.jalaliDatepicker.hide === 'function') {
      window.jalaliDatepicker.hide();
    }
  }

  async commitRange(entry) {
    const parts = String(entry.input.value || '').trim().split(/\s+-\s+|\s+to\s+/i);

    // The picker reports the first end of a range straight away; wait for the
    // second one instead of driving Bookly twice.
    if (!entry.single && parts.filter(Boolean).length < 2) {
      return false;
    }

    const start = this.parseDate(parts[0]);
    const end = entry.single ? start : this.parseDate(parts[1] || parts[0]);

    if (!start || !end) {
      return false;
    }

    entry.input.value = entry.single
      ? this.formatDate(this.toIso(start))
      : this.formatDate(this.toIso(start)) + this.separator + this.formatDate(this.toIso(end));

    await this.clearSelection(entry.popover);
    await this.selectDate(entry.popover, this.toIso(start), 0);

    if (!entry.single) {
      await this.selectDate(entry.popover, this.toIso(end), 1);
    }

    await this.pressApply(entry);
    this.localizeTriggers();

    return true;
  }

  /**
   * The calendar re-renders on every selection, so a press can land between
   * frames; press again until the range sticks, then confirm once Apply is live.
   */
  async pressApply(entry) {
    for (let attempt = 0; attempt < 4; attempt++) {
      const apply = Array.from(entry.popover.querySelectorAll('button')).find(
        (button) => (button.textContent || '').trim() === 'Apply'
      );

      if (!apply) {
        return;
      }

      if (apply.disabled) {
        await this.tick();
        continue;
      }

      this.press(apply);
      await this.tick();

      if (!entry.popover.isConnected || entry.popover.getAttribute('data-state') === 'closed') {
        return;
      }
    }
  }

  /**
   * The calendar keeps whatever range the filter applied last, and pressing a
   * new day would extend that range instead of starting a new one.
   */
  async clearSelection(popover) {
    for (let attempt = 0; attempt < 5; attempt++) {
      const cell = popover.querySelector('div[data-range-calendar-day][data-selected]');

      if (!cell) {
        return;
      }

      this.press(cell);
      await this.tick();
    }
  }

  async selectDate(popover, iso, calendarIndex) {
    const selector = 'div[data-range-calendar-day][data-value="' + iso + '"]';

    for (let attempt = 0; attempt < 3; attempt++) {
      const cell = this.pickCell(popover, selector);

      if (!cell) {
        await this.navigate(popover, iso, calendarIndex);
        await this.tick();
        continue;
      }

      this.press(cell);

      if (await this.waitSelected(popover, selector)) {
        return;
      }
    }
  }

  /**
   * Both months show trailing days of their neighbour, so the end date of a
   * range usually exists twice; the later node belongs to the later month.
   */
  pickCell(popover, selector) {
    const cells = Array.from(popover.querySelectorAll(selector));

    return cells.length ? cells[cells.length - 1] : null;
  }

  isSelected(popover, selector) {
    return Array.from(popover.querySelectorAll(selector)).some(
      (cell) => cell.hasAttribute('data-selected') || cell.getAttribute('aria-selected') === 'true'
    );
  }

  /**
   * The calendar marks the pressed day on its next render, so give it time
   * before deciding the press was lost - pressing again too early toggles
   * the day back off.
   */
  async waitSelected(popover, selector) {
    for (let attempt = 0; attempt < 10; attempt++) {
      await this.tick();

      if (this.isSelected(popover, selector)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Bookly's range calendar ignores a bare `click()`; it reacts to the full
   * pointer sequence a real press produces.
   */
  press(element) {
    const options = {
      bubbles: true,
      cancelable: true,
      view: window,
      button: 0,
      pointerId: 1,
      isPrimary: true,
      pointerType: 'mouse',
    };

    ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'].forEach((type) => {
      element.dispatchEvent(new window.MouseEvent(type, options));
    });
  }

  /**
   * The hidden selects are controlled by the calendar, so setting them does
   * nothing; step through months with the calendar's own arrows instead.
   */
  async navigate(popover, iso, calendarIndex) {
    const parts = iso.split('-');
    const target = +parts[0] * 12 + (+parts[1] - 1);

    for (let step = 0; step < 24; step++) {
      const selects = Array.from(popover.querySelectorAll('[data-range-calendar-root] select'));
      const index = Math.min(calendarIndex * 2, Math.max(0, selects.length - 2));
      const monthSelect = selects[index];
      const yearSelect = selects[index + 1];

      if (!monthSelect || !yearSelect) {
        return;
      }

      const current = +yearSelect.value * 12 + (+monthSelect.value - 1);

      if (current === target) {
        return;
      }

      const label = current < target ? 'Next' : 'Previous';
      let scope = monthSelect;
      let button = null;

      for (let up = 0; up < 6 && scope && !button; up++) {
        scope = scope.parentElement;
        button = scope && scope.querySelector('button[aria-label="' + label + '"]');
      }

      if (!button) {
        return;
      }

      this.press(button);
      await this.tick();
    }
  }

  todayJalali() {
    const now = new Date();

    return this.toJalaliIso(now.getFullYear() + '-' + this.pad(now.getMonth() + 1) + '-' + this.pad(now.getDate()));
  }

  toJalaliIso(iso) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(iso || ''));

    if (!match) {
      return '';
    }

    try {
      const jalali = window.WpPdJalaliDate.toJalaali(+match[1], +match[2], +match[3]);

      return jalali.jy + '-' + this.pad(jalali.jm) + '-' + this.pad(jalali.jd);
    } catch (error) {
      return '';
    }
  }

  tick() {
    return new Promise((resolve) => {
      window.setTimeout(resolve, 80);
    });
  }

  observe() {
    if (!window.MutationObserver || this.observer) {
      return;
    }

    this.observer = new window.MutationObserver(() => {
      if (this.scheduled) {
        return;
      }

      this.scheduled = true;
      window.setTimeout(() => {
        this.scheduled = false;
        if (!document.querySelector('[data-range-calendar-root]')) {
          this.activePopover = null;
        }
        this.decorateAll();
        this.localizeTriggers();
      }, 0);
    });

    this.observer.observe(document.body, {
      childList: true,
      characterData: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['data-state'],
    });
  }

  toIso(date) {
    return date.gy + '-' + this.pad(date.gm) + '-' + this.pad(date.gd);
  }

  formatDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));

    if (!match) {
      return '';
    }

    try {
      const jalali = window.WpPdJalaliDate.toJalaali(+match[1], +match[2], +match[3]);
      const month = this.months[jalali.jm - 1];

      return month ? this.toPersianDigits(jalali.jd + ' ' + month + ' ' + jalali.jy) : '';
    } catch (error) {
      return '';
    }
  }

  parseDate(value) {
    const normalized = this.toEnglishDigits(value).trim();
    const numeric = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/.exec(normalized);
    const named = /^(\d{1,2})\s+(\S+)\s+(\d{4})$/.exec(normalized);
    let jalali = null;

    if (numeric) {
      jalali = { jy: +numeric[1], jm: +numeric[2], jd: +numeric[3] };
    } else if (named) {
      const index = this.months.indexOf(named[2]);

      if (index >= 0) {
        jalali = { jy: +named[3], jm: index + 1, jd: +named[1] };
      }
    }

    if (!jalali || jalali.jm < 1 || jalali.jm > 12 || jalali.jd < 1 || jalali.jd > 31) {
      return null;
    }

    try {
      const gregorian = window.WpPdJalaliDate.toGregorian(jalali.jy, jalali.jm, jalali.jd);
      const roundTrip = window.WpPdJalaliDate.toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);

      return roundTrip.jy === jalali.jy && roundTrip.jm === jalali.jm && roundTrip.jd === jalali.jd
        ? gregorian
        : null;
    } catch (error) {
      return null;
    }
  }

  pad(value) {
    return String(value).padStart(2, '0');
  }

  toEnglishDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }
}

// The localized settings live in `window.WpPdBooklyAdminJalaliDaterange`, so the
// classes are exposed under distinct names.
window.WpPdBooklyAdminJalaliDaterangeAdapter = WpPdBooklyAdminJalaliDaterange;
window.WpPdBooklyAdminJalaliRangeCalendarAdapter = WpPdBooklyAdminJalaliRangeCalendar;

const bootBooklyAdminJalaliDaterange = () => {
  new WpPdBooklyAdminJalaliDaterange().boot();
  new WpPdBooklyAdminJalaliRangeCalendar().boot();
};
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyAdminJalaliDaterange);
} else {
  bootBooklyAdminJalaliDaterange();
}

/******/ })()
;