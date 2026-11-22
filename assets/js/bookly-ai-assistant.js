/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**********************************************!*\
  !*** ./assets/js-src/bookly-ai-assistant.js ***!
  \**********************************************/


// Bookly's AI assistant is a Svelte widget: it keeps references to its own
// text nodes. Replacing textContent detaches those nodes and later chat
// updates stop showing, so localize the existing text nodes in place.
const AI_CONTAINER_SELECTOR = '.bookly-ai-assistant-app';

class WpPdBooklyAiAssistant {
  constructor() {
    this.settings = window.WpPdBooklyAiAssistantSettings || {};
    this.scheduled = false;
    this.months = (this.settings.months || []).slice();
    this.format = this.settings.format || 'j F Y';
  }

  boot() {
    if (!window.WpPdJalaliDate || !document.body || !document.querySelectorAll) {
      return;
    }

    this.patchShowForm();
    this.process();
    this.observe();
  }

  /**
   * Called at script execution time, before DOMContentLoaded. Bookly's
   * shortcode template calls showForm() from a DOMContentLoaded handler that
   * is registered in the body; an adapter booting on the same event would
   * patch too late. The adapter is printed in the footer, after Bookly's own
   * script (it is a dependency), so patching here reaches the real call.
   */
  patchEarly() {
    this.patchShowForm();
  }

  /**
   * The chat's payment card formats dates through `moment_format_date`, which
   * Bookly builds from the WordPress date format (e.g. "D MMMM YYYY"). Force
   * an ISO date there so the visible value is matchable by localizeText(), the
   * same approach used by the admin and modern-form adapters.
   */
  patchShowForm() {
    const assistant = window.BooklyAiAssistant;
    if (!assistant || typeof assistant.showForm !== 'function' || assistant.showForm.__wppdPatched) {
      return;
    }

    const original = assistant.showForm;
    assistant.showForm = function (...args) {
      const config = args[1];
      if (config && typeof config === 'object') {
        config.moment_format_date = 'YYYY-MM-DD';
      }
      return original.apply(this, args);
    };
    assistant.showForm.__wppdPatched = true;
  }

  observe() {
    if (!window.MutationObserver || !window.requestAnimationFrame) {
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

    observer.observe(document.body, {childList: true, characterData: true, subtree: true});
  }

  process() {
    const containers = Array.from(document.querySelectorAll(AI_CONTAINER_SELECTOR));
    containers.forEach((container) => this.localizeDates(container));
  }

  localizeDates(root) {
    if (!document.createTreeWalker || !window.NodeFilter) {
      return;
    }

    const walker = document.createTreeWalker(root, window.NodeFilter.SHOW_TEXT);
    let node = walker.nextNode();
    while (node) {
      const localized = this.localizeText(String(node.nodeValue || ''));
      if (localized !== node.nodeValue) {
        node.nodeValue = localized;
      }
      node = walker.nextNode();
    }
  }

  localizeText(text) {
    return text.replace(/\b(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?\b/g, (match, year, month, day, hour, minute) => {
      const date = this.formatGregorianDate(year, month, day);
      if (!date) {
        return match;
      }

      // Preserve the machine-readable value's time in the visible text.
      return hour && minute ? `${date} ${hour}:${minute}` : date;
    });
  }

  formatGregorianDate(year, month, day) {
    const y = Number(year);
    const m = Number(month);
    const d = Number(day);
    const parsed = new Date(Date.UTC(y, m - 1, d));
    if (
      y < 1 ||
      m < 1 ||
      m > 12 ||
      d < 1 ||
      d > 31 ||
      parsed.getUTCFullYear() !== y ||
      parsed.getUTCMonth() !== m - 1 ||
      parsed.getUTCDate() !== d
    ) {
      return '';
    }

    let jalali;
    try {
      jalali = window.WpPdJalaliDate.toJalaali(y, m, d);
    } catch (error) {
      return '';
    }

    const monthName = this.months[jalali.jm - 1];
    const parts = this.format
      .replace(/j/g, String(jalali.jd))
      .replace(/F/g, monthName || String(jalali.jm))
      .replace(/Y/g, String(jalali.jy));

    return this.toPersianDigits(parts);
  }

  toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[digit]);
  }
}

window.WpPdBooklyAiAssistant = WpPdBooklyAiAssistant;

// Patch showForm now, at footer script execution time (before the body
// template's DOMContentLoaded handler calls it), then boot the DOM scanner on
// the normal event. patchShowForm() is idempotent, so boot() can call it too.
new WpPdBooklyAiAssistant().patchEarly();

const bootBooklyAiAssistant = () => new WpPdBooklyAiAssistant().boot();
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyAiAssistant);
} else {
  bootBooklyAiAssistant();
}

/******/ })()
;