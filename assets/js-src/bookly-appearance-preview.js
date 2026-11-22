'use strict';

/**
 * Jalali day numbers for Bookly's admin Appearance preview calendar.
 *
 * Bookly renders this mockup server-side: the month/year title and weekday
 * labels already flow through WordPress date_i18n() (localized by WP-Parsidate),
 * but day numbers come straight from PHP DateTime::format('j') as Gregorian.
 * The preview has no interactive picker and no Bookly script handles, so this
 * adapter localizes the rendered day numbers only.
 *
 * Gregorian values behind the mockup are reconstructable: the template starts
 * at "first day of this month, monday this week" and prints consecutive days.
 */

class WpPdBooklyAppearancePreview {
  constructor() {
    this.settings = window.WpPdBooklyAppearancePreviewSettings || {};
  }

  boot() {
    if (!window.WpPdJalaliDate || !document.body || !document.querySelectorAll) {
      return;
    }

    this.process();
  }

  process() {
    const calendars = Array.from(document.querySelectorAll('.bookly-js-datepicker-calendar-mode'));
    calendars.forEach((calendar) => this.localizeCalendar(calendar));
  }

  localizeCalendar(calendar) {
    const buttons = Array.from(calendar.querySelectorAll('.bookly-calendar-button'));
    if (!buttons.length) {
      return;
    }

    const first = this.firstGregorianDay();
    if (!first) {
      return;
    }

    buttons.forEach((button, index) => {
      const day = new Date(Date.UTC(first.year, first.month - 1, first.day + index));
      const jalali = window.WpPdJalaliDate.toJalaali(
        day.getUTCFullYear(),
        day.getUTCMonth() + 1,
        day.getUTCDate()
      );
      const target = button.querySelector('span') || button;
      this.setNodeText(target, this.toPersianDigits(String(jalali.jd)));
    });
  }

  firstGregorianDay() {
    const todayValue = this.settings.today;
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(todayValue || ''));
    if (!match) {
      return null;
    }

    const year = +match[1];
    const month = +match[2];
    const day = +match[3];
    const firstOfMonth = new Date(Date.UTC(year, month - 1, 1));

    // PHP: date_create('first day of this month')->modify('monday this week')
    const weekday = firstOfMonth.getUTCDay(); // 0 = Sunday
    const offset = (weekday + 6) % 7; // days since Monday
    const start = new Date(Date.UTC(year, month - 1, 1 - offset));

    return {
      year: start.getUTCFullYear(),
      month: start.getUTCMonth() + 1,
      day: start.getUTCDate(),
    };
  }

  setNodeText(node, text) {
    const child = node.firstChild;
    if (child && child.nodeType === 3 && child.nextSibling === null) {
      if (child.nodeValue !== text) {
        child.nodeValue = text;
      }
      return;
    }
    if (node.textContent !== text) {
      node.textContent = text;
    }
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }
}

window.WpPdBooklyAppearancePreview = WpPdBooklyAppearancePreview;
const bootBooklyAppearancePreview = () => new WpPdBooklyAppearancePreview().boot();
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyAppearancePreview);
} else {
  bootBooklyAppearancePreview();
}
