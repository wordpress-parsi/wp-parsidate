/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!*************************************************!*\
  !*** ./assets/js-src/bookly-jalali-calendar.js ***!
  \*************************************************/


class WpPdBooklyJalaliCalendar {
  constructor() {
    this.settings = window.WpPdBooklyJalali || {};
    this.instances = new WeakMap();
    this.activeInstance = null;
    this.monthNames = window.BooklyL10nGlobal &&
      window.BooklyL10nGlobal.datePicker &&
      window.BooklyL10nGlobal.datePicker.monthNamesShort || [];
  }

  boot() {
    if (!window.WpPdJalaliDate || !window.jalaliDatepicker) {
      return;
    }

    this.process(document);
    this.observe();
    document.addEventListener('click', (event) => this.closeOnOutsideClick(event));
    document.addEventListener('keydown', (event) => this.closeOnEscape(event));
  }

  observe() {
    if (!window.MutationObserver || !document.body) {
      return;
    }

    let scheduled = false;
    const observer = new MutationObserver(() => {
      if (scheduled) {
        return;
      }

      scheduled = true;
      window.requestAnimationFrame(() => {
        scheduled = false;
        this.process(document);
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  process(root) {
    const forms = [];
    if (root.matches && root.matches('.bookly-form')) {
      forms.push(root);
    }
    if (root.querySelectorAll) {
      root.querySelectorAll('.bookly-form').forEach((form) => forms.push(form));
    }

    forms.forEach((form) => {
      form.querySelectorAll('.bookly-js-date-from').forEach((input) => this.prepareServiceDate(input));
      form.querySelectorAll('.bookly-js-slot-calendar').forEach((calendar) => this.prepareTimeCalendar(calendar));
    });
  }

  prepareServiceDate(input) {
    let instance = this.instances.get(input);
    if (!instance) {
      const nativeCalendar = input.parentElement &&
        input.parentElement.querySelector('.bookly-js-datepicker-calendar');
      const calendarContainer = nativeCalendar && nativeCalendar.parentElement;
      // Bookly can render the service date as a dropdown instead of the inline
      // calendar. There is no calendar container in that mode, so fall back to
      // the dropdown control and keep feeding Bookly Gregorian dates.
      const dropdown = calendarContainer ? null : this.findDateDropdown(input);
      if (!calendarContainer && !dropdown) {
        return;
      }

      const anchor = dropdown && dropdown !== input ? dropdown : input;
      const displayInput = this.createDateInput(input);
      const label = input.id && document.querySelector('label[for="' + this.escapeSelector(input.id) + '"]');
      if (label) {
        label.htmlFor = displayInput.id;
      }

      input.style.display = 'none';
      if (dropdown && dropdown !== input) {
        dropdown.style.display = 'none';
      }
      anchor.parentNode.insertBefore(displayInput, anchor.nextSibling);
      const status = this.createStatusElement(displayInput);
      if (nativeCalendar) {
        nativeCalendar.classList.add('wppd-bookly-native-calendar');
      }

      instance = this.createInstance(
        input,
        nativeCalendar || dropdown,
        displayInput,
        status,
        'service',
        dropdown ? 'dropdown' : 'calendar'
      );
      this.instances.set(input, instance);
    }

    if (instance.mode === 'calendar') {
      this.hideNativeCalendar(instance.nativeCalendar);
    }
  }

  findDateDropdown(input) {
    if (!input.parentNode) {
      return null;
    }

    if (input.tagName === 'SELECT') {
      return input;
    }

    const root = (input.closest && (
      input.closest('.bookly-js-available-date') ||
      input.closest('.bookly-form-group')
    )) || input.parentElement;

    // Bookly's dropdown date control is either a select next to the field or
    // the field itself; both are written through their own change path.
    return (root && root.querySelector('select')) || input;
  }

  prepareTimeCalendar(container) {
    let instance = this.instances.get(container);
    if (!instance) {
      const source = container.querySelector('.bookly-js-selected-date');
      if (!source) {
        return;
      }

      const displayInput = this.createDateInput(source);
      displayInput.classList.add('bookly-date-from');
      container.insertBefore(displayInput, source.nextSibling);
      const status = this.createStatusElement(displayInput);

      instance = this.createInstance(source, container, displayInput, status, 'time');
      this.instances.set(container, instance);
    }

    this.hideNativeCalendar(container);
  }

  createDateInput(source) {
    const input = document.createElement('input');
    input.type = 'text';
    input.id = source.id ? source.id + '-jalali' : '';
    input.className = source.className.replace('bookly-js-date-from', '').replace('bookly-js-selected-date', '') +
      ' wppd-bookly-date-input';
    input.readOnly = true;
    input.autocomplete = 'off';
    input.setAttribute('data-jdp', '');
    input.setAttribute('data-jdp-only-date', '');
    input.setAttribute('aria-haspopup', 'dialog');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-label', this.settings.calendar || 'Jalali calendar');
    input.value = this.formatJalali(this.initialDate(source));

    return input;
  }

  createStatusElement(input) {
    const status = document.createElement('span');
    status.className = 'wppd-bookly-date-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    input.insertAdjacentElement('afterend', status);
    return status;
  }

  createInstance(source, nativeCalendar, displayInput, status, type, mode) {
    const initialDate = this.initialDate(source);
    const initialGregorian = initialDate &&
      this.toGregorianDate(initialDate.jy, initialDate.jm, initialDate.jd);
    const instance = {
      type,
      mode: mode || 'calendar',
      source,
      nativeCalendar,
      displayInput,
      status,
      lastValue: displayInput.value,
      selectedDate: initialGregorian
        ? this.formatIso(initialGregorian.gy, initialGregorian.gm, initialGregorian.gd)
        : '',
      isOpen: false,
      selecting: false,
    };

    displayInput.addEventListener('focus', () => this.open(instance));
    displayInput.addEventListener('click', () => this.open(instance));
    displayInput.addEventListener('jdp:change', () => this.selectDate(instance));

    return instance;
  }

  initialDate(source) {
    const value = source && (source.getAttribute('data-value') || source.value);
    return this.toJalali(value) ||
      this.toJalali(this.settings.today) ||
      this.toJalali(new Date().toISOString().slice(0, 10));
  }

  async selectDate(instance) {
    if (instance.selecting) {
      return;
    }

    const target = this.parseJalaliDate(instance.displayInput.value);
    if (!target) {
      instance.displayInput.value = instance.lastValue;
      instance.status.textContent = this.settings.unavailable || '';
      this.resetActiveInstance();
      return;
    }

    const selectedDate = this.formatIso(target.gy, target.gm, target.gd);
    if (selectedDate === instance.selectedDate) {
      instance.lastValue = instance.displayInput.value;
      instance.status.textContent = '';
      this.resetActiveInstance();
      return;
    }

    instance.selecting = true;
    instance.displayInput.disabled = true;
    instance.displayInput.setAttribute('aria-busy', 'true');
    instance.status.textContent = '';

    try {
      if (!await this.selectNativeDate(instance, target)) {
        instance.displayInput.value = instance.lastValue;
        instance.status.textContent = this.settings.unavailable || '';
        return;
      }

      instance.lastValue = instance.displayInput.value;
      instance.selectedDate = selectedDate;
    } catch (error) {
      instance.displayInput.value = instance.lastValue;
      instance.status.textContent = this.settings.unavailable || '';
      window.console.error('WP-Parsidate could not select the Bookly date.', error);
    } finally {
      instance.selecting = false;
      instance.isOpen = false;
      instance.displayInput.disabled = false;
      instance.displayInput.removeAttribute('aria-busy');
      instance.displayInput.setAttribute('aria-expanded', 'false');
      if (this.activeInstance === instance) {
        this.activeInstance = null;
      }
    }
  }

  async selectNativeDate(instance, target) {
    if (instance.mode === 'dropdown') {
      return this.selectDropdownDate(instance, target);
    }

    if (instance.type === 'service') {
      await new Promise((resolve) => window.setTimeout(resolve, 0));
      instance.source.dispatchEvent(new Event('focus'));
    }

    for (let attempt = 0; attempt < 24; attempt++) {
      await this.nextFrame();
      const title = this.findBooklyCalendarTitle(instance.nativeCalendar);
      const current = title && this.parseBooklyMonth(title.textContent);
      if (!current) {
        continue;
      }

      if (current.year === target.gy && current.month === target.gm) {
        const dayNode = Array.from(instance.nativeCalendar.querySelectorAll('.bookly-calendar-current-month-mark'))
          .find((node) => this.dayNumber(node.textContent) === target.gd);
        const clickable = dayNode && (dayNode.matches('button') ? dayNode : dayNode.querySelector('button') || dayNode);
        if (
          !clickable ||
          clickable.disabled ||
          clickable.getAttribute('disabled') === 'true' ||
          clickable.getAttribute('aria-disabled') === 'true'
        ) {
          return false;
        }
        clickable.click();
        return true;
      }

      const direction = target.gy * 12 + target.gm - (current.year * 12 + current.month);
      const selector = direction > 0 ? '.bookly-calendar-right-button-mark' : '.bookly-calendar-left-button-mark';
      const marker = instance.nativeCalendar.querySelector(selector);
      const control = marker && marker.closest('button, [role="button"]');
      if (
        !control ||
        control.disabled ||
        control.getAttribute('disabled') === 'true' ||
        control.getAttribute('aria-disabled') === 'true'
      ) {
        return false;
      }
      control.click();
    }

    return false;
  }

  selectDropdownDate(instance, target) {
    const dropdown = instance.nativeCalendar;
    const iso = this.formatIso(target.gy, target.gm, target.gd);

    if (dropdown.tagName === 'SELECT') {
      const option = Array.from(dropdown.options).find((item) => {
        return this.matchIsoDate(item.value) === iso ||
          this.matchIsoDate(item.getAttribute('data-value')) === iso ||
          this.matchIsoDate(item.textContent) === iso;
      });

      if (!option) {
        return false;
      }

      dropdown.value = option.value;
      if (dropdown.value !== option.value) {
        return false;
      }
    } else {
      dropdown.setAttribute('data-value', iso);
      dropdown.value = this.formatGregorian(target);
    }

    this.dispatchDateChange(dropdown);
    return true;
  }

  formatGregorian(target) {
    const picker = window.BooklyL10nGlobal && window.BooklyL10nGlobal.datePicker;
    const format = picker && picker.format;
    const months = picker && (picker.monthNames || picker.months);

    if (!format || !months || typeof window.moment !== 'function') {
      return this.formatIso(target.gy, target.gm, target.gd);
    }

    return window.moment(this.formatIso(target.gy, target.gm, target.gd), 'YYYY-MM-DD').format(format);
  }

  dispatchDateChange(control) {
    if (window.jQuery && control.tagName !== 'SELECT') {
      const $control = window.jQuery(control);
      $control.data('value', control.getAttribute('data-value'));
      $control.data('changed', true);
    }

    // Bookly's own form listens with native handlers, so jQuery's
    // trigger('change') alone would not reach it.
    if (control.tagName !== 'SELECT') {
      control.dispatchEvent(new Event('input', { bubbles: true }));
    }
    control.dispatchEvent(new Event('change', { bubbles: true }));
  }

  matchIsoDate(value) {
    const match = /(\d{4})-(\d{2})-(\d{2})/.exec(this.toEnglishDigits(value || ''));
    return match ? match[1] + '-' + match[2] + '-' + match[3] : '';
  }

  findBooklyCalendarTitle(container) {
    return container.querySelector('.bookly-calendar-middle-button-mark') ||
      container.querySelector('.bookly-calendar-title-mark');
  }

  parseBooklyMonth(value) {
    const title = String(value || '').trim();
    for (let index = 0; index < this.monthNames.length; index++) {
      const monthName = String(this.monthNames[index] || '').trim();
      if (monthName && title.indexOf(monthName) === 0) {
        const year = parseInt(this.toEnglishDigits(title.slice(monthName.length)), 10);
        return Number.isNaN(year) ? null : { month: index + 1, year };
      }
    }

    return null;
  }

  dayNumber(value) {
    const matches = this.toEnglishDigits(value).match(/\d+/g);
    return matches ? parseInt(matches[matches.length - 1], 10) : 0;
  }

  hideNativeCalendar(target) {
    if (!target) {
      return;
    }

    const title = this.findBooklyCalendarTitle(target);
    if (!title) {
      return;
    }

    let nativeRoot = title;
    while (nativeRoot.parentElement && nativeRoot.parentElement !== target) {
      nativeRoot = nativeRoot.parentElement;
    }
    nativeRoot.classList.add('wppd-bookly-native-calendar');
  }

  open(instance) {
    if (!window.jalaliDatepicker) {
      return;
    }

    const picker = document.querySelector('jdp-container');
    if (this.activeInstance === instance && picker && picker.style.display !== 'none') {
      return;
    }

    this.resetActiveInstance();
    this.activeInstance = instance;
    instance.isOpen = true;
    window.jalaliDatepicker.show(instance.displayInput);
    instance.displayInput.setAttribute('aria-expanded', 'true');
  }

  closeOnOutsideClick(event) {
    const picker = document.querySelector('jdp-container');
    if (
      event.target.closest && event.target.closest('jdp-container') ||
      event.target.closest && event.target.closest('.wppd-bookly-date-input')
    ) {
      return;
    }

    window.requestAnimationFrame(() => {
      if (!picker || picker.style.display === 'none') {
        this.resetActiveInstance();
      }
    });
  }

  closeOnEscape(event) {
    if (event.key !== 'Escape') {
      return;
    }

    window.requestAnimationFrame(() => {
      this.resetActiveInstance();
    });
  }

  resetActiveInstance() {
    if (!this.activeInstance) {
      return;
    }

    this.activeInstance.isOpen = false;
    this.activeInstance.displayInput.setAttribute('aria-expanded', 'false');
    this.activeInstance = null;
  }

  toJalali(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(this.toEnglishDigits(value || ''));
    if (!match) {
      return null;
    }

    const year = +match[1];
    const month = +match[2];
    const day = +match[3];
    const date = new Date(Date.UTC(year, month - 1, day));
    if (
      year < 1 ||
      month < 1 ||
      month > 12 ||
      day < 1 ||
      day > 31 ||
      date.getUTCFullYear() !== year ||
      date.getUTCMonth() !== month - 1 ||
      date.getUTCDate() !== day
    ) {
      return null;
    }

    return window.WpPdJalaliDate.toJalaali(year, month, day);
  }

  toGregorianDate(year, month, day) {
    if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day) ||
      year < 1 || month < 1 || month > 12 || day < 1 || day > 31
    ) {
      return null;
    }

    return window.WpPdJalaliDate.toGregorian(year, month, day);
  }

  parseJalaliDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(this.toEnglishDigits(value || ''));
    if (!match) {
      return null;
    }

    const jalali = { jy: +match[1], jm: +match[2], jd: +match[3] };
    const gregorian = this.toGregorianDate(jalali.jy, jalali.jm, jalali.jd);
    if (!gregorian) {
      return null;
    }

    const roundTrip = this.toJalali(this.formatIso(gregorian.gy, gregorian.gm, gregorian.gd));
    return roundTrip && roundTrip.jy === jalali.jy && roundTrip.jm === jalali.jm && roundTrip.jd === jalali.jd
      ? gregorian
      : null;
  }

  formatIso(year, month, day) {
    return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
  }

  formatJalali(date) {
    if (!date || !Number.isInteger(date.jy) || !Number.isInteger(date.jm) || !Number.isInteger(date.jd)) {
      return '';
    }

    return date.jy + '-' + String(date.jm).padStart(2, '0') + '-' + String(date.jd).padStart(2, '0');
  }

  toEnglishDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  escapeSelector(value) {
    return window.CSS && window.CSS.escape
      ? window.CSS.escape(value)
      : String(value).replace(/([ #;?%&,.+*~':"!^$[\]()=>|/@])/g, '\\$1');
  }

  nextFrame() {
    return new Promise((resolve) => window.requestAnimationFrame(resolve));
  }
}

const wppdBooklyJalaliCalendar = new WpPdBooklyJalaliCalendar();
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => wppdBooklyJalaliCalendar.boot());
} else {
  wppdBooklyJalaliCalendar.boot();
}

/******/ })()
;