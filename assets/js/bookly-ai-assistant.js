/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/*!**********************************************!*\
  !*** ./assets/js-src/bookly-ai-assistant.js ***!
  \**********************************************/


// Bookly's AI assistant is a Svelte widget: it keeps references to its own
// text nodes. Replacing textContent detaches those nodes and later chat
// updates stop showing, so localize the existing text nodes in place.
const AI_CONTAINER_SELECTOR = '.bookly-ai-assistant-app';
const AI_MESSAGE_SELECTOR = '.bookly-ai-markdown';

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

    this.process();
    this.observe();
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
    containers.forEach((container) => {
      const messages = container.querySelectorAll(AI_MESSAGE_SELECTOR);
      const targets = messages.length ? messages : [container];
      targets.forEach((target) => this.localizeDates(target));
    });
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
const bootBooklyAiAssistant = () => new WpPdBooklyAiAssistant().boot();
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', bootBooklyAiAssistant);
} else {
  bootBooklyAiAssistant();
}

/******/ })()
;