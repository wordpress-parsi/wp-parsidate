'use strict';

class WpPdPodsJalaliDatepicker {
  static DISPLAY_CLASS = 'wppd-pods-jalali-input';
  static SELECTOR = 'input.pods-form-ui-field-type-date:not([type="hidden"]), input.pods-form-ui-field-type-datetime:not([type="hidden"]), input.wppd-pods-jalali-input';

  constructor() {
    this.settings = window.WpPdPodsJalali || {};
    this.processed = new WeakSet();
    this.fieldCounter = 0;
  }

  normalizeDigits(value) {
    return String(value == null ? '' : value)
      .replace(/[۰-۹]/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(digit))
      .replace(/[٠-٩]/g, (digit) => '٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
  }

  pad2(value) {
    return String(parseInt(value, 10) || 0).padStart(2, '0');
  }

  isDateTimeField(field) {
    return field.classList.contains('pods-form-ui-field-type-datetime') &&
      !field.classList.contains('pods-form-ui-field-type-date');
  }

  getHiddenInput(input) {
    let hidden = input.nextElementSibling;
    if (hidden && hidden.tagName === 'INPUT' && hidden.type === 'hidden') {
      return hidden;
    }

    const parent = input.parentElement;
    if (parent) {
      hidden = parent.querySelector('input[type="hidden"]');
      if (hidden) {
        return hidden;
      }
    }

    return null;
  }

  destroyJQueryUiDatepicker(input) {
    const $ = window.jQuery;
    if (!$ || !$.fn.datepicker || !input.classList.contains('hasDatepicker')) {
      return;
    }

    $(input).datepicker('destroy');
  }

  /**
   * Convert a Pods Gregorian storage value (Y-m-d / Y-m-d H:i:s) to Jalali text.
   */
  toJalaliValue(value, isDateTime) {
    const match = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}:\d{2}(?::\d{2})?))?$/.exec(this.normalizeDigits(value || ''));
    if (!match || !window.WpPdJalaliDate) {
      return '';
    }

    const year = +match[1];
    const month = +match[2];
    const day = +match[3];

    // Pods stores empty values as 0000-00-00; they are not real dates.
    if (year < 1 || month < 1 || month > 12 || day < 1 || day > 31) {
      return '';
    }

    let jalali;
    try {
      jalali = window.WpPdJalaliDate.toJalaali(year, month, day);
    } catch (error) {
      return '';
    }

    if (!jalali) {
      return '';
    }

    const separator = this.settings.dateSeparator || '/';

    return jalali.jy + separator +
      String(jalali.jm).padStart(2, '0') + separator +
      String(jalali.jd).padStart(2, '0') +
      (isDateTime && match[4] ? ' ' + match[4] : '');
  }

  /**
   * Convert Jalali text to Pods Gregorian storage format.
   *
   * @returns {string|null} null when the text is not a usable Jalali date.
   */
  toGregorianValue(value, isDateTime) {
    const text = this.normalizeDigits(value == null ? '' : value).trim();
    if ('' === text) {
      return '';
    }

    const match = /^(\d{4})[/-](\d{1,2})[/-](\d{1,2})(?:[ T](\d{1,2}):(\d{1,2})(?::(\d{1,2}))?)?$/.exec(text);
    if (!match || !window.WpPdJalaliDate) {
      return null;
    }

    const year = +match[1];
    const month = +match[2];
    const day = +match[3];

    if (year < 1 || month < 1 || month > 12 || day < 1 || day > 31) {
      return null;
    }

    let jalali;
    try {
      jalali = window.WpPdJalaliDate.toGregorian(year, month, day);
    } catch (error) {
      return null;
    }

    if (!jalali) {
      return null;
    }

    const date = jalali.gy + '-' + this.pad2(jalali.gm) + '-' + this.pad2(jalali.gd);
    if (!isDateTime) {
      return date;
    }

    return date + ' ' + this.pad2(match[4]) + ':' + this.pad2(match[5]) + ':' + this.pad2(match[6]);
  }

  /**
   * Push a value through React's controlled input so Pods keeps its own state.
   */
  setReactValue(input, value) {
    const descriptor = window.HTMLInputElement &&
      Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');

    if (descriptor && descriptor.set) {
      descriptor.set.call(input, value);
    } else {
      input.value = value;
    }

    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  findDfvHidden(container) {
    return container.querySelector('input[type="hidden"][name], input[hidden][name]');
  }

  findDfvSource(container) {
    return container.querySelector('input.pods-form-ui-field-type-date, input.pods-form-ui-field-type-datetime');
  }

  /**
   * Pods renders its editor fields with React (Pods DFV). The visible input is a
   * Dropdown toggle that opens the WordPress (Gregorian) date picker, so it must
   * not stay in the form: hide it, show our own Jalali input next to it, and keep
   * the Gregorian value in Pods' hidden field.
   *
   * @returns {boolean} true when the field was prepared.
   */
  prepareDfvField(source, container) {
    const hidden = this.findDfvHidden(container);
    if (!hidden) {
      return false;
    }

    const isDateTime = this.isDateTimeField(source);
    const host = source.closest('.components-dropdown') || source;

    let display = container.querySelector('input.' + WpPdPodsJalaliDatepicker.DISPLAY_CLASS);

    if (!display) {
      display = document.createElement('input');
      display.type = 'text';
      display.className = WpPdPodsJalaliDatepicker.DISPLAY_CLASS;
      display.setAttribute('data-jdp', '');
      display.setAttribute('autocomplete', 'off');
    }

    // React may re-render the panel and drop or move our input, so keep it next
    // to the Pods dropdown on every pass instead of only when it is created.
    if (display.parentNode !== (host.parentNode || container) || display.previousSibling !== host) {
      if (host.parentNode) {
        host.parentNode.insertBefore(display, host.nextSibling);
      } else {
        container.appendChild(display);
      }
    }

    display.setAttribute(isDateTime ? 'data-jdp-only-datetime' : 'data-jdp-only-date', '');
    display.setAttribute('data-wppd-pods-datetime', isDateTime ? '1' : '0');

    // The field label points at the (now hidden) Pods input; keep the name here.
    const label = source.id && window.CSS && CSS.escape
      ? document.querySelector('label[for="' + CSS.escape(source.id) + '"]')
      : null;
    if (label && label.textContent.trim()) {
      display.setAttribute('aria-label', label.textContent.trim());
    }

    // Keep the original field in the DOM (Pods reads its state) but out of sight.
    host.style.display = 'none';

    if (!display.wppdPodsBound) {
      display.wppdPodsBound = true;
      display.addEventListener('change', () => this.syncDfvValue(display));
      display.addEventListener('focus', () => this.fillDfvDisplay(display, true));
    }

    this.fillDfvDisplay(display);

    return true;
  }

  fillDfvDisplay(display, force) {
    const container = display.closest('.pods-dfv-container') || display.parentElement;
    if (!container) {
      return;
    }

    // Never rewrite the text while the user is typing in it: panel re-renders
    // happen often and the value only reaches the hidden field on change.
    if (!force && document.activeElement === display) {
      return;
    }

    const hidden = this.findDfvHidden(container);
    const isDateTime = display.getAttribute('data-wppd-pods-datetime') === '1';
    const value = this.toJalaliValue(hidden ? hidden.value : '', isDateTime);

    if (value !== display.value) {
      display.value = value;
    }
  }

  syncDfvValue(display) {
    const container = display.closest('.pods-dfv-container') || display.parentElement;
    if (!container) {
      return;
    }

    const isDateTime = display.getAttribute('data-wppd-pods-datetime') === '1';
    const value = this.toGregorianValue(display.value, isDateTime);
    if (null === value) {
      return;
    }

    const source = this.findDfvSource(container);
    if (source) {
      this.setReactValue(source, value);
    }

    const hidden = this.findDfvHidden(container);
    if (hidden) {
      hidden.value = value;
    }
  }

  processInput(input) {
    if (!input || input.classList.contains(WpPdPodsJalaliDatepicker.DISPLAY_CLASS)) {
      return;
    }

    const dfvContainer = input.closest('.pods-dfv-container');

    if (dfvContainer) {
      // Deliberately not guarded by `processed`: React re-renders the Pods DFV
      // panel and can remove the injected input while keeping this source node,
      // so setup has to run again to put the Jalali input back.
      this.prepareDfvField(input, dfvContainer);
      return;
    }

    if (this.processed.has(input)) {
      return;
    }

    this.processed.add(input);
    this.destroyJQueryUiDatepicker(input);

    input.setAttribute('data-jdp', '');

    const isDateTime = this.isDateTimeField(input);
    if (isDateTime) {
      input.setAttribute('data-jdp-only-datetime', '');
    } else {
      input.setAttribute('data-jdp-only-date', '');
    }

    const hidden = this.getHiddenInput(input);
    if (!hidden) {
      return;
    }

    if (!hidden.id) {
      hidden.id = 'wppd-pods-hidden-' + (++this.fieldCounter);
    }

    input.setAttribute('data-jdp-target-value-input', '#' + hidden.id);
    input.setAttribute('data-jdp-target-value-type', 'gregorian');

    if (hidden.value) {
      const value = this.toJalaliValue(hidden.value, isDateTime);
      if (value) {
        input.value = value;
      }
    }
  }

  process(root) {
    if (!root || !root.querySelectorAll) {
      return;
    }

    if (root.nodeType === 1 && root.matches(WpPdPodsJalaliDatepicker.SELECTOR)) {
      this.processInput(root);
    }

    root.querySelectorAll(WpPdPodsJalaliDatepicker.SELECTOR).forEach((input) => this.processInput(input));
  }

  boot() {
    if (!window.jalaliDatepicker || !window.WpPdJalaliDate) {
      return;
    }

    this.process(document);

    window.jalaliDatepicker.startWatch({
      selector: WpPdPodsJalaliDatepicker.SELECTOR,
      separatorChars: {
        date: this.settings.dateSeparator || '/',
        between: ' ',
        time: ':',
        targetDate: '-',
        targetBetween: ' ',
        targetTime: ':'
      },
      zIndex: 999999999,
      time: true,
      persianDigits: this.settings.persianDigits !== false
    });

    if (typeof MutationObserver !== 'undefined') {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.type === 'attributes' && mutation.target.matches(WpPdPodsJalaliDatepicker.SELECTOR)) {
            this.destroyJQueryUiDatepicker(mutation.target);
            return;
          }

          mutation.addedNodes.forEach((node) => {
            if (node.nodeType === 1) {
              this.process(node);
            }
          });

          // React can remove our injected input without adding anything back,
          // so re-check the Pods field it belonged to. Scoped to the DFV
          // container so unrelated editor changes never walk the document.
          if (mutation.removedNodes.length && mutation.target.nodeType === 1) {
            const dfvScope = mutation.target.closest('.pods-dfv-container');
            if (dfvScope) {
              this.process(dfvScope);
            }
          }
        });
      });
      observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class'],
        childList: true,
        subtree: true
      });
    }

    document.addEventListener('focus', (event) => {
      const input = event.target;
      if (input instanceof Element && input.matches(WpPdPodsJalaliDatepicker.SELECTOR)) {
        this.destroyJQueryUiDatepicker(input);
      }
    }, true);
  }
}

const wppdPodsJalali = new WpPdPodsJalaliDatepicker();

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => wppdPodsJalali.boot());
} else {
  wppdPodsJalali.boot();
}
