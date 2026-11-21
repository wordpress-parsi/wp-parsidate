/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**********************************************!*\
  !*** ./assets/js-src/bookly-pro-calendar.js ***!
  \**********************************************/


class WpPdBooklyProCalendar {
  constructor() {
    this.titleInputs = new WeakMap();
    this.entries = new Set();
    this.activeInput = null;
    this.scheduled = false;
  }

  boot() {
    if (!window.WpPdJalaliDate || !window.jalaliDatepicker || !document.body) {
      return;
    }

    this.process();
    this.observe();
  }

  observe() {
    if (!window.MutationObserver) {
      return;
    }

    const observer = new window.MutationObserver(() => {
      if (this.scheduled) {
        return;
      }

      this.scheduled = true;
      window.requestAnimationFrame(() => {
        this.scheduled = false;
        this.process();
      });
    });

    observer.observe(document.body, { childList: true, characterData: true, subtree: true });
  }

  process() {
    const calendars = Array.from(document.querySelectorAll('.bookly-frontend-calendar'));
    this.entries.forEach((entry) => {
      if (!entry.title.isConnected) {
        entry.input.remove();
        this.entries.delete(entry);
      }
    });

    calendars.forEach((calendar) => {
      calendar.querySelectorAll('.ec-toolbar .ec-title').forEach((title) => {
        this.prepareCalendarTitle(title);
      });
      this.localizeDayHeaders(calendar);
    });
  }

  prepareCalendarTitle(title) {
    if (this.titleInputs.has(title)) {
      this.positionInput(title, this.titleInputs.get(title));
      return;
    }

    const picker = window.jQuery && window.jQuery(title).data('daterangepicker');
    if (
      !picker ||
      typeof picker.setStartDate !== 'function' ||
      typeof picker.setEndDate !== 'function' ||
      typeof picker.callback !== 'function' ||
      !title.parentElement
    ) {
      return;
    }

    const parent = title.parentElement;
    if (window.getComputedStyle(parent).position === 'static') {
      parent.style.position = 'relative';
    }

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'wppd-bookly-pro-calendar-date';
    input.readOnly = true;
    input.tabIndex = -1;
    input.autocomplete = 'off';
    input.setAttribute('data-jdp', '');
    input.setAttribute('data-jdp-only-date', '');
    input.setAttribute('aria-hidden', 'true');
    input.style.position = 'absolute';
    input.style.opacity = '0';
    input.style.zIndex = '2';
    input.style.pointerEvents = 'none';

    const entry = { title, input, picker };
    title.addEventListener('click', (event) => {
      event.preventDefault();
      Promise.resolve().then(() => {
        if (typeof picker.hide === 'function') {
          picker.hide();
        }
        this.open(entry);
      });
    });
    input.addEventListener('jdp:change', () => {
      if (!this.applyJalaliDate(input.value, picker)) {
        window.console.error('WP-Parsidate could not apply the Jalali date to the Bookly Pro calendar.');
      }
      this.activeInput = null;
    });

    parent.appendChild(input);
    this.titleInputs.set(title, input);
    this.entries.add(entry);
    this.positionInput(title, input);
  }

  positionInput(title, input) {
    const parent = title.parentElement;
    if (!parent) {
      return;
    }

    const titleRect = title.getBoundingClientRect();
    const parentRect = parent.getBoundingClientRect();
    input.style.left = titleRect.left - parentRect.left + parent.scrollLeft + 'px';
    input.style.top = titleRect.top - parentRect.top + parent.scrollTop + 'px';
    input.style.width = titleRect.width + 'px';
    input.style.height = titleRect.height + 'px';
  }

  open(entry) {
    const datePicker = document.querySelector('jdp-container');
    if (this.activeInput === entry.input && datePicker && datePicker.style.display !== 'none') {
      return;
    }

    const date = entry.picker.startDate && entry.picker.startDate.format
      ? entry.picker.startDate.format('YYYY-MM-DD')
      : '';
    const jalali = this.toJalaliDate(date);
    entry.input.value = jalali
      ? jalali.jy + '-' + String(jalali.jm).padStart(2, '0') + '-' + String(jalali.jd).padStart(2, '0')
      : '';
    this.activeInput = entry.input;
    window.jalaliDatepicker.show(entry.input);
  }

  applyJalaliDate(value, picker) {
    const target = this.parseJalaliDate(value);
    if (
      !target ||
      !picker ||
      typeof picker.setStartDate !== 'function' ||
      typeof picker.setEndDate !== 'function' ||
      typeof picker.callback !== 'function'
    ) {
      return false;
    }

    picker.setStartDate(new Date(target.gy, target.gm - 1, target.gd));
    picker.setEndDate(new Date(target.gy, target.gm - 1, target.gd));
    if (!picker.startDate || !picker.startDate.clone || !picker.endDate || !picker.endDate.clone) {
      return false;
    }

    picker.callback(picker.startDate.clone(), picker.endDate.clone(), picker.chosenLabel);
    return true;
  }

  localizeDayHeaders(calendar) {
    calendar.querySelectorAll('.ec-col-head time').forEach((header) => {
      const match = /^(\d{4}-\d{2}-\d{2})/.exec(header.getAttribute('datetime') || '');
      const jalali = match && this.toJalaliDate(match[1]);
      if (!jalali) {
        return;
      }

      const localized = header.textContent.replace(/[\d۰-۹٠-٩]{1,2}/, () => {
        return this.toPersianDigits(jalali.jd);
      });
      if (localized !== header.textContent) {
        header.textContent = localized;
      }
    });
  }

  toJalaliDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
    if (!match) {
      return null;
    }

    const gy = +match[1];
    const gm = +match[2];
    const gd = +match[3];
    const date = new Date(Date.UTC(gy, gm - 1, gd));
    if (
      gy < 1 ||
      gm < 1 ||
      gm > 12 ||
      gd < 1 ||
      gd > 31 ||
      date.getUTCFullYear() !== gy ||
      date.getUTCMonth() !== gm - 1 ||
      date.getUTCDate() !== gd
    ) {
      return null;
    }

    return window.WpPdJalaliDate.toJalaali(gy, gm, gd);
  }

  parseJalaliDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(this.toEnglishDigits(value));
    if (!match) {
      return null;
    }

    const jalali = { jy: +match[1], jm: +match[2], jd: +match[3] };
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

  toEnglishDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }
}

window.WpPdBooklyProCalendar = WpPdBooklyProCalendar;
const bootBooklyProCalendar = () => new WpPdBooklyProCalendar().boot();
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyProCalendar);
} else {
  bootBooklyProCalendar();
}

/******/ })()
;