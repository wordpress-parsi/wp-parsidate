'use strict';

const MODERN_FORM_CONTAINERS = [
  '.bookly-services-form-container',
  '.bookly-search-form-container',
  '.bookly-staff-form-container',
  '.bookly-tags-form-container',
];

const CALENDAR_TITLE_SELECTOR = MODERN_FORM_CONTAINERS.map(
  (container) => container + ' .bookly-calendar-middle-button-mark, ' + container + ' .bookly-calendar-title-mark'
).join(', ');

const SUMMARY_CONTAINER_SELECTOR = MODERN_FORM_CONTAINERS
  .concat(['.bookly-modal-header-mark', '.bookly-modal-body-mark'])
  .join(', ');

// Bookly's Svelte components keep references to their text nodes. Replacing
// textContent detaches those nodes, so later month re-renders update a detached
// node and the visible label stays stale. Mutate the existing node instead.
function setTextContent(node, text) {
  if (!node) {
    return;
  }

  const children = node.childNodes ? Array.from(node.childNodes) : null;
  if (children) {
    const textNode = children.find((child) => child.nodeType === 3);
    if (textNode) {
      if (textNode.nodeValue !== text) {
        textNode.nodeValue = text;
      }
      return;
    }

    const element = children.find((child) => child.nodeType === 1);
    if (element) {
      setTextContent(element, text);
      return;
    }
  }

  if (node.textContent !== text) {
    node.textContent = text;
  }
}

class WpPdBooklyProServicesForm {
  constructor() {
    this.settings = window.BooklyL10nModernBookingForm || {};
    this.titleInputs = new WeakMap();
    this.calendarEntries = new WeakMap();
    this.entries = new Set();
    this.scheduled = false;
    this.monthNames = this.settings.datePicker && this.settings.datePicker.monthNamesShort || [];
    const integrationSettings = window.WpPdBooklyProServicesFormSettings || {};
    this.jalaliMonthNames = integrationSettings.months || [];
    const configuredFirstDay = Number(integrationSettings.firstDay);
    const booklyFirstDay = Number(this.settings.datePicker && this.settings.datePicker.firstDay);
    this.firstDay = Number.isInteger(booklyFirstDay) && booklyFirstDay >= 0 && booklyFirstDay <= 6
      ? booklyFirstDay
      : Number.isInteger(configuredFirstDay) && configuredFirstDay >= 0 && configuredFirstDay <= 6
        ? configuredFirstDay
        : 0;
  }

  boot() {
    if (!window.WpPdJalaliDate || !window.jalaliDatepicker || !document.body) {
      return;
    }

    this.settings.moment_format_date = 'YYYY-MM-DD';
    this.process();
    this.observe();
    window.addEventListener('resize', () => {
      this.entries.forEach((entry) => this.positionInput(entry.title, entry.input));
    });
  }

  observe() {
    if (!window.MutationObserver) {
      return;
    }

    const observer = new MutationObserver(() => {
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
    const titles = Array.from(document.querySelectorAll(CALENDAR_TITLE_SELECTOR));
    this.entries.forEach((entry) => {
      if (!entry.title.isConnected) {
        entry.input.remove();
        this.entries.delete(entry);
        if (entry.calendar && this.calendarEntries.get(entry.calendar) === entry) {
          this.calendarEntries.delete(entry.calendar);
        }
      }
    });

    titles.forEach((title) => {
      const entry = this.prepareCalendarTitle(title);
      if (entry) {
        entry.calendar = this.findCalendar(title);
        this.localizeCalendar(entry);
      }
    });
    this.localizeAppointmentSummary();
  }

  prepareCalendarTitle(title) {
    if (this.titleInputs.has(title)) {
      const entry = this.titleInputs.get(title);
      this.positionInput(title, entry.input);
      return entry;
    }

    const calendar = this.findCalendar(title);
    if (!calendar || !title.parentElement) {
      return null;
    }

    // One calendar must only ever own one picker, even if Bookly renders more
    // than one title mark for it (responsive duplicates).
    const calendarEntry = this.calendarEntries.get(calendar);
    if (calendarEntry) {
      this.titleInputs.set(title, calendarEntry);
      return calendarEntry;
    }

    const parent = title.parentElement;
    if (window.getComputedStyle(parent).position === 'static') {
      parent.style.position = 'relative';
    }

    // Adopt a picker another instance already attached to this calendar instead
    // of stacking a second invisible input on the same title.
    const existingInput = parent.children && Array.from(parent.children).find(
      (child) => child !== title && child.classList && child.classList.contains('wppd-bookly-pro-services-form-date')
    );
    if (existingInput) {
      const adopted = { title, calendar, input: existingInput, lastValue: existingInput.value || '' };
      this.titleInputs.set(title, adopted);
      this.calendarEntries.set(calendar, adopted);
      this.entries.add(adopted);
      this.positionInput(title, existingInput);
      return adopted;
    }

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'wppd-bookly-pro-services-form-date';
    input.readOnly = true;
    input.tabIndex = 0;
    input.autocomplete = 'off';
    input.setAttribute('data-jdp', '');
    input.setAttribute('data-jdp-only-date', '');
    input.setAttribute('aria-label', this.settings.l10n && this.settings.l10n.date || 'Jalali calendar');
    input.style.position = 'absolute';
    input.style.opacity = '0';
    input.style.zIndex = '2';

    const entry = { title, calendar, input, lastValue: '' };
    input.addEventListener('click', () => this.open(entry));
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.open(entry);
      }
    });
    input.addEventListener('jdp:change', () => {
      this.selectDate(entry).catch((error) => {
        entry.input.value = entry.lastValue;
        window.console.error('WP-Parsidate could not select the Jalali date in the Bookly Pro modern form.', error);
      });
    });

    parent.appendChild(input);
    this.titleInputs.set(title, entry);
    this.calendarEntries.set(calendar, entry);
    this.entries.add(entry);
    this.positionInput(title, input);
    return entry;
  }

  findCalendar(title) {
    let container = title.parentElement;
    while (container) {
      if (
        container.querySelector('.bookly-calendar-current-month-mark') &&
        container.querySelector('.bookly-calendar-left-button-mark') &&
        container.querySelector('.bookly-calendar-right-button-mark')
      ) {
        return container;
      }
      container = container.parentElement;
    }

    return null;
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
    const current = this.getGregorianMonth(entry.title);
    if (!current) {
      window.console.error('WP-Parsidate could not read the Bookly Pro modern-form calendar month.');
      return;
    }

    const selectedDay = Array.from(
      entry.calendar.querySelectorAll('.bookly-calendar-current-month-mark')
    ).find((node) => {
      return node.getAttribute('aria-selected') === 'true' ||
        node.getAttribute('aria-pressed') === 'true' ||
        node.classList && (
          node.classList.contains('bg-bookly') ||
          node.classList.contains('bookly:bg-white')
        );
    });
    const selectedDate = selectedDay && this.getGregorianCellDate(selectedDay);
    const day = selectedDate
      ? selectedDate.day
      : selectedDay ? this.dayNumber(selectedDay.textContent) : 1;
    const jalali = window.WpPdJalaliDate.toJalaali(current.year, current.month, day);
    entry.input.value = this.formatJalali(jalali);
    entry.lastValue = entry.input.value;
    window.jalaliDatepicker.show(entry.input);
  }

  async selectDate(entry) {
    const target = this.parseJalaliDate(entry.input.value);
    if (!target) {
      entry.input.value = entry.lastValue;
      return false;
    }

    const selectedDate = this.formatIso(target.gy, target.gm, target.gd);
    entry.input.disabled = true;
    entry.input.setAttribute('aria-busy', 'true');

    try {
      const result = await this.selectNativeDate(entry, target);
      if (result !== true) {
        entry.input.value = entry.lastValue;
        // A disabled day is Bookly saying "not available", not a failure.
        if (result !== 'unavailable') {
          window.console.error('WP-Parsidate could not select the Jalali date in the Bookly Pro modern form.');
        }
        return false;
      }

      entry.lastValue = entry.input.value;
      entry.selectedDate = selectedDate;
      return true;
    } catch (error) {
      entry.input.value = entry.lastValue;
      window.console.error('WP-Parsidate could not select the Jalali date in the Bookly Pro modern form.', error);
      return false;
    } finally {
      entry.input.disabled = false;
      entry.input.removeAttribute('aria-busy');
    }
  }

  async selectNativeDate(entry, target) {
    for (let attempt = 0; attempt < 36; attempt++) {
      await this.nextFrame();
      const current = this.getGregorianMonth(entry.title);
      if (!current) {
        continue;
      }

      if (current.year === target.gy && current.month === target.gm) {
        const dayNode = Array.from(
          entry.calendar.querySelectorAll('.bookly-calendar-current-month-mark')
        ).find((node) => {
          const date = this.getGregorianCellDate(node);
          return date ? date.day === target.gd : this.dayNumber(node.textContent) === target.gd;
        });
        const clickable = dayNode && (dayNode.matches && dayNode.matches('button')
          ? dayNode
          : dayNode.querySelector && dayNode.querySelector('button') || dayNode);
        if (!clickable || this.isDisabled(clickable)) {
          return 'unavailable';
        }

        clickable.click();
        return true;
      }

      const direction = target.gy * 12 + target.gm - (current.year * 12 + current.month);
      const selector = direction > 0
        ? '.bookly-calendar-right-button-mark'
        : '.bookly-calendar-left-button-mark';
      const marker = entry.calendar.querySelector(selector);
      const control = marker && marker.closest && marker.closest('button, [role="button"]') || marker;
      if (!control || this.isDisabled(control)) {
        return false;
      }
      control.click();
    }

    return false;
  }

  isDisabled(element) {
    return element.disabled ||
      element.getAttribute('disabled') === 'true' ||
      element.getAttribute('aria-disabled') === 'true';
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

  getGregorianMonth(title) {
    const label = title.querySelector && title.querySelector('span') || title;
    const visibleLabel = String(label.textContent || '').trim();
    const data = title.dataset || {};
    let sourceLabel = data.wppdGregorianMonthLabel;

    if (!sourceLabel || visibleLabel !== data.wppdLocalizedMonthLabel) {
      sourceLabel = visibleLabel;
      data.wppdGregorianMonthLabel = sourceLabel;
    }

    return this.parseBooklyMonth(sourceLabel);
  }

  localizeCalendar(entry) {
    const current = this.getGregorianMonth(entry.title);
    if (!current || !entry.calendar) {
      return;
    }

    const jalaliMonth = window.WpPdJalaliDate.toJalaali(current.year, current.month, 15);
    const monthName = this.jalaliMonthNames[jalaliMonth.jm - 1];
    if (!monthName) {
      return;
    }

    const label = entry.title.querySelector && entry.title.querySelector('span') || entry.title;
    const localizedLabel = monthName + ' ' + this.toPersianDigits(jalaliMonth.jy);
    if (String(label.textContent || '').trim() !== localizedLabel) {
      setTextContent(label, localizedLabel);
    }
    entry.title.dataset.wppdLocalizedMonthLabel = localizedLabel;

    const dates = entry.calendar.querySelector('.bookly-calendar-dates-mark');
    const grid = dates && dates.children && dates.children[1];
    if (!grid || !grid.children) {
      return;
    }

    const monthStart = new Date(Date.UTC(current.year, current.month - 1, 1));
    const offset = (monthStart.getUTCDay() - this.firstDay + 7) % 7;
    monthStart.setUTCDate(monthStart.getUTCDate() - offset);
    const cells = Array.from(grid.children).flatMap((week) => Array.from(week.children || []));

    cells.forEach((cell, index) => {
      const date = new Date(monthStart);
      date.setUTCDate(monthStart.getUTCDate() + index);
      const gregorian = {
        year: date.getUTCFullYear(),
        month: date.getUTCMonth() + 1,
        day: date.getUTCDate(),
      };
      const jalali = window.WpPdJalaliDate.toJalaali(gregorian.year, gregorian.month, gregorian.day);
      const dayLabel = cell.querySelectorAll && Array.from(cell.querySelectorAll('span')).pop();
      if (dayLabel) {
        const day = this.toPersianDigits(jalali.jd);
        if (String(dayLabel.textContent || '').trim() !== day) {
          setTextContent(dayLabel, day);
        }
      }

      cell.dataset.wppdGregorianYear = gregorian.year;
      cell.dataset.wppdGregorianMonth = gregorian.month;
      cell.dataset.wppdGregorianDay = gregorian.day;
    });
  }

  localizeAppointmentSummary() {
    if (!document.querySelectorAll || !document.createTreeWalker || !window.NodeFilter) {
      return;
    }

    document.querySelectorAll(SUMMARY_CONTAINER_SELECTOR).forEach((container) => {
      const walker = document.createTreeWalker(container, window.NodeFilter.SHOW_TEXT);
      let node = walker.nextNode();
      while (node) {
        const localized = String(node.nodeValue || '').replace(
          /\b(\d{4}-\d{2}-\d{2})\b/g,
          (date) => this.formatGregorianDate(date) || date
        );
        if (localized !== node.nodeValue) {
          node.nodeValue = localized;
        }
        node = walker.nextNode();
      }
    });
  }

  formatGregorianDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
    if (!match) {
      return '';
    }

    const gregorian = { year: +match[1], month: +match[2], day: +match[3] };
    const parsedDate = new Date(Date.UTC(gregorian.year, gregorian.month - 1, gregorian.day));
    if (
      gregorian.year < 1 ||
      gregorian.month < 1 ||
      gregorian.month > 12 ||
      gregorian.day < 1 ||
      gregorian.day > 31 ||
      parsedDate.getUTCFullYear() !== gregorian.year ||
      parsedDate.getUTCMonth() !== gregorian.month - 1 ||
      parsedDate.getUTCDate() !== gregorian.day
    ) {
      return '';
    }

    let jalali;
    try {
      jalali = window.WpPdJalaliDate.toJalaali(gregorian.year, gregorian.month, gregorian.day);
    } catch (error) {
      return '';
    }

    const month = this.jalaliMonthNames[jalali.jm - 1];
    return month
      ? this.toPersianDigits(jalali.jd + ' ' + month + ' ' + jalali.jy)
      : '';
  }

  getGregorianCellDate(cell) {
    const data = cell.dataset || {};
    if (data.wppdGregorianYear && data.wppdGregorianMonth && data.wppdGregorianDay) {
      return {
        year: Number(data.wppdGregorianYear),
        month: Number(data.wppdGregorianMonth),
        day: Number(data.wppdGregorianDay),
      };
    }

    return null;
  }

  dayNumber(value) {
    const matches = this.toEnglishDigits(value).match(/\d+/g);
    return matches ? parseInt(matches[matches.length - 1], 10) : 0;
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
      const roundTrip = window.WpPdJalaliDate.toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);
      return roundTrip.jy === jalali.jy && roundTrip.jm === jalali.jm && roundTrip.jd === jalali.jd
        ? gregorian
        : null;
    } catch (error) {
      return null;
    }
  }

  formatJalali(date) {
    return date.jy + '-' + String(date.jm).padStart(2, '0') + '-' + String(date.jd).padStart(2, '0');
  }

  formatIso(year, month, day) {
    return year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
  }

  toEnglishDigits(value) {
    return String(value || '')
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }

  nextFrame() {
    return new Promise((resolve) => window.requestAnimationFrame(resolve));
  }
}

window.WpPdBooklyProServicesForm = WpPdBooklyProServicesForm;
let booted = false;
const bootBooklyProServicesForm = () => {
  // Keep a second copy of this script from booting a competing instance.
  if (booted) {
    return;
  }
  booted = true;
  new WpPdBooklyProServicesForm().boot();
};
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyProServicesForm);
} else {
  bootBooklyProServicesForm();
}
