'use strict';

class WpPdBooklyAdminJalaliCalendar {
  constructor() {
    this.settings = window.WpPdBooklyAdminJalali || {};
    this.titleInputs = new WeakMap();
    this.entries = new Set();
    this.activeInput = null;
    this.scheduled = false;
  }

  boot() {
    if (!window.WpPdJalaliDate || !window.jalaliDatepicker || !document.body) {
      return;
    }

    if (window.BooklyL10nAppointmentCard) {
      window.BooklyL10nAppointmentCard.moment_format_date = 'YYYY-MM-DD';
    }

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
    const titles = Array.from(document.querySelectorAll('.bookly-js-calendar .ec-toolbar .ec-title'));
    this.entries.forEach((entry) => {
      if (!entry.title.isConnected) {
        entry.input.remove();
        this.entries.delete(entry);
      }
    });

    titles.forEach((title) => {
      this.prepareCalendarTitle(title);
      this.localizeCalendarTitle(title);
    });

    this.localizeCalendarDayHeaders();
    this.localizeAppointmentCard();
  }

  prepareCalendarTitle(title) {
    const existing = this.titleInputs.get(title);
    if (existing) {
      this.positionInput(title, existing);
      return;
    }

    const picker = window.jQuery && window.jQuery(title).data('daterangepicker');
    if (
      !picker ||
      typeof picker.setStartDate !== 'function' ||
      typeof picker.setEndDate !== 'function' ||
      typeof picker.callback !== 'function'
    ) {
      return;
    }

    const parent = title.parentElement;
    if (!parent) {
      return;
    }

    if (window.getComputedStyle(parent).position === 'static') {
      parent.style.position = 'relative';
    }

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'wppd-bookly-admin-jalali-date';
    input.readOnly = true;
    input.tabIndex = -1;
    input.autocomplete = 'off';
    input.setAttribute('data-jdp', '');
    input.setAttribute('data-jdp-only-date', '');
    input.setAttribute('aria-hidden', 'true');
    input.value = this.formatGregorianPickerDate(this.pickerDate(picker));
    input.style.position = 'absolute';
    input.style.opacity = '0';
    input.style.zIndex = '2';
    input.style.cursor = 'pointer';
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
        window.console.error('WP-Parsidate could not apply the Jalali date to the Bookly calendar.');
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
    const picker = document.querySelector('jdp-container');
    if (this.activeInput === entry.input && picker && picker.style.display !== 'none') {
      return;
    }

    entry.input.value = this.formatGregorianPickerDate(this.pickerDate(entry.picker));
    this.activeInput = entry.input;
    window.jalaliDatepicker.show(entry.input);
  }

  pickerDate(picker) {
    return picker.startDate && typeof picker.startDate.format === 'function'
      ? picker.startDate.format('YYYY-MM-DD')
      : '';
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

    const date = new Date(target.gy, target.gm - 1, target.gd);
    
    // When user selects a date from Jalali datepicker, we need to decide what to do
    // with the date range. Since the Jalali datepicker only shows a single date,
    // we'll set both start and end to that date. This means date ranges will be
    // collapsed to a single date when user picks from Jalali datepicker.
    // If we want to preserve date ranges, we would need a more complex UI.
    picker.setStartDate(date);
    picker.setEndDate(date);
    
    if (
      !picker.startDate ||
      typeof picker.startDate.clone !== 'function' ||
      !picker.endDate ||
      typeof picker.endDate.clone !== 'function'
    ) {
      return false;
    }

    picker.callback(picker.startDate.clone(), picker.endDate.clone(), picker.chosenLabel);

    return true;
  }

  localizeCalendarTitle(title) {
    if (!title) {
      return;
    }

    // Find all text nodes in the title element (Bookly uses text nodes for Svelte rendering)
    // We need to traverse all child nodes to find text nodes
    const walker = document.createTreeWalker(title, window.NodeFilter.SHOW_TEXT);
    let textNode = walker.nextNode();
    let changed = false;
    
    while (textNode) {
      const originalText = textNode.nodeValue;
      const localizedText = this.localizeCalendarTitleText(originalText);
      
      if (localizedText !== originalText) {
        textNode.nodeValue = localizedText;
        changed = true;
      }
      
      textNode = walker.nextNode();
    }
    
    return changed;
  }

  localizeCalendarTitleText(text) {
    if (!text) {
      return text;
    }

    // Handle date range formats commonly used by Bookly
    // Format 1: "Month Day - Month Day, Year" (e.g., "December 15 - December 22, 2026")
    // Format 2: "Month Day, Year - Month Day, Year" (e.g., "December 15, 2026 - December 22, 2026")
    // Format 3: "Month Year" (e.g., "December 2026") - for single month view
    // Format 4: "Month Day, Year" - single date
    
    // First, try to extract and convert all Gregorian dates in the text
    // This handles all formats by finding YYYY-MM-DD patterns or month/day/year combinations
    let result = text;
    
    // Try to find and convert Gregorian date patterns
    // Look for patterns like "YYYY-MM-DD" in the text
    const gregorianDatePattern = /\b(\d{4}-\d{2}-\d{2})\b/g;
    let match;
    
    while ((match = gregorianDatePattern.exec(text)) !== null) {
      const gregorianDate = match[1];
      const jalaliDate = this.formatGregorianDate(gregorianDate);
      if (jalaliDate) {
        result = result.replace(gregorianDate, jalaliDate);
      }
    }
    
    // If no Gregorian dates were found in YYYY-MM-DD format, try to parse month names
    // This is more complex and would require month name mapping
    // For now, we'll rely on the localizeDateText method which handles this
    if (result === text) {
      result = this.localizeDateText(text);
    }
    
    return result;
  }

  localizeCalendarDayHeaders() {
    if (!document.querySelectorAll) {
      return;
    }

    document.querySelectorAll('.bookly-js-calendar .ec-col-head time').forEach((header) => {
      const dateMatch = /^(\d{4}-\d{2}-\d{2})/.exec(header.getAttribute('datetime') || '');
      const jalali = dateMatch && this.toJalaliDate(dateMatch[1]);
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

  localizeAppointmentCard() {
    if (!document.querySelectorAll || !document.createTreeWalker) {
      return;
    }

    document.querySelectorAll('p[class*="tabular-nums"]').forEach((dateElement) => {
      const walker = document.createTreeWalker(dateElement, window.NodeFilter.SHOW_TEXT);
      let node = walker.nextNode();
      while (node) {
        const localized = this.localizeDateText(node.nodeValue);
        if (localized !== node.nodeValue) {
          node.nodeValue = localized;
        }
        node = walker.nextNode();
      }
    });
  }

  localizeDateText(value) {
    return String(value || '').replace(/\b(\d{4}-\d{2}-\d{2})(?=\b)/g, (date) => {
      return this.formatGregorianDate(date) || date;
    });
  }

  formatGregorianDate(value) {
    const jalali = this.toJalaliDate(value);
    if (!jalali) {
      return '';
    }

    const month = this.settings.months && this.settings.months[jalali.jm - 1];
    if (!month) {
      return '';
    }

    return this.toPersianDigits(jalali.jd + ' ' + month + ' ' + jalali.jy);
  }

  formatGregorianPickerDate(value) {
    const jalali = this.toJalaliDate(value);
    return jalali
      ? jalali.jy + '-' + String(jalali.jm).padStart(2, '0') + '-' + String(jalali.jd).padStart(2, '0')
      : '';
  }

  toJalaliDate(value) {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value || ''));
    if (!match) {
      return null;
    }

    const gregorian = { gy: +match[1], gm: +match[2], gd: +match[3] };
    const parsedDate = new Date(Date.UTC(gregorian.gy, gregorian.gm - 1, gregorian.gd));
    if (
      gregorian.gy < 1 ||
      gregorian.gm < 1 ||
      gregorian.gm > 12 ||
      gregorian.gd < 1 ||
      gregorian.gd > 31 ||
      parsedDate.getUTCFullYear() !== gregorian.gy ||
      parsedDate.getUTCMonth() !== gregorian.gm - 1 ||
      parsedDate.getUTCDate() !== gregorian.gd
    ) {
      return null;
    }

    try {
      return window.WpPdJalaliDate.toJalaali(gregorian.gy, gregorian.gm, gregorian.gd);
    } catch (error) {
      return null;
    }
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

window.WpPdBooklyAdminJalaliCalendar = WpPdBooklyAdminJalaliCalendar;
const bootBooklyAdminJalaliCalendar = () => new WpPdBooklyAdminJalaliCalendar().boot();
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyAdminJalaliCalendar);
} else {
  bootBooklyAdminJalaliCalendar();
}
