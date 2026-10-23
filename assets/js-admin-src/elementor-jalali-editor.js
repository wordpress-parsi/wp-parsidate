/**
 * Jalali date & time pickers for Elementor editor widget settings.
 *
 * Elementor renders every DATE_TIME control (the Countdown widget "Due Date",
 * for example) as a text input that it initializes with its bundled Gregorian
 * flatpickr. This script swaps that picker for the Jalali datepicker while
 * keeping Elementor's own input — and therefore the saved widget setting — in
 * Gregorian format:
 *
 *   <input data-setting="due_date" style="display:none">   Elementor, "2026-12-01 10:30"
 *   <input class="wpp-jdp-editor-input">                   user sees "1405/09/10 10:30"
 *
 * Elementor keeps writing Gregorian values into its hidden input (initial
 * render, dynamic tags, undo/redo), so the value accessor of that input is
 * overridden to mirror every write into the visible Jalali field. Changes made
 * in the visible field are converted back to Gregorian and dispatched as an
 * `input` event so the panel stores them.
 *
 * Class structure:
 *   WpPdJalaliConvert                 - Gregorian <-> Jalali string helpers
 *   WpPdElementorEditorJalaliField    - wraps a single Elementor DATE_TIME control
 *   WpPdElementorEditorJalaliDate     - orchestrator: boots the picker, scans the panel
 *
 * Requires: jalali-date.js (conversion engine) and jalalidatepicker.min.js,
 *           both bundled with WP-Parsidate. WPP_Elementor_Jalali_Editor holds
 *           the localized month names.
 */
(function () {
  'use strict';

  const DISPLAY_CLASS = 'wpp-jdp-editor-input';
  const CONTROL_SELECTOR = 'input.elementor-date-time-picker';

  /**
   * Gregorian <-> Jalali string helpers on top of the bundled WpPdJalaliDate
   * engine. Values use Elementor's "Y-m-d H:i" format on the Gregorian side
   * and "YYYY/MM/DD HH:MM" on the Jalali side.
   */
  class WpPdJalaliConvert {
    static pad(n) {
      return (n < 10 ? '0' : '') + n;
    }

    /**
     * Convert Persian/Arabic digits to latin so typed values can be parsed.
     */
    static latinDigits(value) {
      return String(value === undefined || value === null ? '' : value)
        .replace(/[\u06F0-\u06F9]/g, (digit) => String('\u06F0\u06F1\u06F2\u06F3\u06F4\u06F5\u06F6\u06F7\u06F8\u06F9'.indexOf(digit)))
        .replace(/[\u0660-\u0669]/g, (digit) => String('\u0660\u0661\u0662\u0663\u0664\u0665\u0666\u0667\u0668\u0669'.indexOf(digit)));
    }

    /**
     * Whether a stored Elementor value carries a time part.
     */
    static hasTime(value) {
      return /[ T]\d{1,2}:\d{1,2}/.test(String(value || ''));
    }

    /**
     * Only the flatpickr formats Elementor uses for its date controls are
     * converted. Anything else keeps Elementor's own picker untouched.
     */
    static isSupportedFormat(format) {
      return /^Y-m-d( H:i)?$/.test(String(format || 'Y-m-d'));
    }

    /**
     * Gregorian "Y-m-d[ H:i]" -> Jalali "YYYY/MM/DD[ HH:MM]" (for display).
     */
    static toJalali(value) {
      const m = /^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T](\d{1,2}):(\d{1,2}))?/.exec(String(value || '').trim());
      if (!m) {
        return '';
      }

      let j;
      try {
        j = window.WpPdJalaliDate.toJalaali(+m[1], +m[2], +m[3]);
      } catch (e) {
        return '';
      }

      if (!j || !j.jy) {
        return '';
      }

      // Latin digits on purpose: the Jalali picker reads its initial value back
      // from this field, and it only parses latin digits.
      const date = j.jy + '/' + this.pad(j.jm) + '/' + this.pad(j.jd);
      if (m[4] === undefined) {
        return date;
      }

      return date + ' ' + this.pad(+m[4]) + ':' + this.pad(+m[5]);
    }

    /**
     * Jalali "YYYY/MM/DD[ HH:MM]" -> Gregorian "Y-m-d[ H:i]" (for saving).
     *
     * `withTime` keeps the time part when the control is a date & time control;
     * `fallbackTime` ("HH:MM") is used when only a date was typed.
     */
    static toGregorian(value, withTime, fallbackTime) {
      const m = /^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})(?:[ T](\d{1,2}):(\d{1,2}))?/.exec(this.latinDigits(value).trim());
      if (!m) {
        return '';
      }

      const jy = +m[1];
      const jm = +m[2];
      const jd = +m[3];

      if (jy < 1 || jm < 1 || jm > 12 || jd < 1 || jd > 31) {
        return '';
      }

      let g;
      try {
        g = window.WpPdJalaliDate.toGregorian(jy, jm, jd);
      } catch (e) {
        return '';
      }

      if (!g || !g.gy) {
        return '';
      }

      const date = g.gy + '-' + this.pad(g.gm) + '-' + this.pad(g.gd);
      if (!withTime) {
        return date;
      }

      let hour = m[4] === undefined ? null : +m[4];
      let minute = m[5] === undefined ? null : +m[5];

      if (hour === null || hour > 23 || minute === null || minute > 59) {
        if (!fallbackTime) {
          return date;
        }

        return date + ' ' + fallbackTime;
      }

      return date + ' ' + this.pad(hour) + ':' + this.pad(minute);
    }
  }

  /**
   * Wraps a single Elementor DATE_TIME control: hides the Gregorian input that
   * Elementor saves from and shows a Jalali date & time field in its place.
   */
  class WpPdElementorEditorJalaliField {
    constructor(input) {
      this.input = input;
      this.display = null;
      this.withTime = true;
    }

    get isReady() {
      return !!this.input.dataset.wppJalaliEditorReady;
    }

    /**
     * Prepare the control. Safe to call repeatedly; returns true only once.
     */
    mount() {
      if (this.isReady) {
        return false;
      }

      const value = this.input.value || '';
      const flatpickr = this.input._flatpickr;
      const config = flatpickr && flatpickr.config ? flatpickr.config : null;

      // Controls may customize the picker; leave those alone.
      if (config && !WpPdJalaliConvert.isSupportedFormat(config.dateFormat)) {
        return false;
      }

      // Elementor tells us whether the control stores a time (DATE_TIME default)
      // or a date only (Pro form min/max dates).
      this.withTime = config && typeof config.enableTime !== 'undefined'
        ? !!config.enableTime
        : (value === '' || WpPdJalaliConvert.hasTime(value));

      this.destroyFlatpickr();
      this.overrideValueAccessor();
      this.hideInput();
      this.createDisplayInput(value);
      this.bindDisplayEvents();

      this.input.dataset.wppJalaliEditorReady = '1';

      return true;
    }

    /**
     * Elementor initializes its flatpickr in the control's `onReady`, before the
     * panel element reaches the DOM. Drop it so only the Jalali picker shows.
     */
    destroyFlatpickr() {
      if (!this.input._flatpickr) {
        return;
      }

      try {
        this.input._flatpickr.destroy();
      } catch (e) { /* noop */ }

      this.input._flatpickr = undefined;
    }

    /**
     * Mirror every Gregorian write of Elementor (applySavedValue, dynamic tags,
     * undo/redo) into the visible Jalali field.
     */
    overrideValueAccessor() {
      const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
      const field = this;

      Object.defineProperty(this.input, 'value', {
        configurable: true,
        enumerable: true,
        get: function () {
          return descriptor.get.call(this);
        },
        set: function (newValue) {
          descriptor.set.call(this, newValue);
          field.showValue(newValue);
        },
      });
    }

    hideInput() {
      this.input.style.display = 'none';
    }

    createDisplayInput(value) {
      const display = document.createElement('input');

      display.type = 'text';
      display.id = (this.input.id || 'wpp-jdp-' + Math.random().toString(36).slice(2)) + '-jalali';
      display.className = DISPLAY_CLASS;
      display.autocomplete = 'off';
      display.value = WpPdJalaliConvert.toJalali(value);

      if (!this.withTime) {
        display.setAttribute('data-jdp-only-date', '');
      }

      this.input.parentNode.insertBefore(display, this.input.nextSibling);
      this.display = display;
      this.repointLabel();
    }

    /**
     * Keep the control label attached to the field the user actually sees.
     */
    repointLabel() {
      if (!this.input.id) {
        return;
      }

      const control = this.input.closest('.elementor-control');

      if (!control) {
        return;
      }

      const label = control.querySelector('label[for="' + this.input.id + '"]');

      if (label) {
        label.setAttribute('for', this.display.id);
      }
    }

    bindDisplayEvents() {
      this.display.addEventListener('change', () => this.commit());
      this.display.addEventListener('blur', () => {
        this.commit();
        // Normalize (or discard) whatever was typed.
        this.showValue(this.input.value);
      });
    }

    /**
     * Show a Gregorian value as Jalali in the visible field.
     */
    showValue(value) {
      if (!this.display) {
        return;
      }

      this.display.value = WpPdJalaliConvert.toJalali(value);
    }

    /**
     * Convert the visible Jalali value back and hand it to Elementor.
     */
    commit() {
      const current = this.input.value || '';
      const fallbackTime = (/(\d{1,2}:\d{1,2})/.exec(current) || [])[1] || '';
      const raw = String(this.display.value || '').trim();

      // The picker writes NaN parts when it has no day selected; keep the last
      // valid value instead of saving garbage.
      if (/NaN/.test(raw)) {
        this.showValue(current);

        return;
      }

      if (!raw) {
        this.input.value = '';
        this.input.dispatchEvent(new Event('input', { bubbles: true }));

        return;
      }

      const gregorian = WpPdJalaliConvert.toGregorian(raw, this.withTime, fallbackTime);

      if (!gregorian || gregorian === current) {
        return;
      }

      // Goes through the overridden accessor, so the field is normalized too.
      this.input.value = gregorian;
      this.input.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  /**
   * Orchestrator: boots the Jalali datepicker and prepares every DATE_TIME
   * control as the panel renders it (widgets, popovers, repeaters).
   */
  class WpPdElementorEditorJalaliDate {
    constructor(settings) {
      this.settings = settings || {};
      this.fieldSelector = CONTROL_SELECTOR;
      this.observer = null;
    }

    init() {
      if (!window.WpPdJalaliDate || !window.jalaliDatepicker) {
        return false;
      }

      this.startWatcher();
      this.bindPickerEvents();
      this.scan();
      this.observePanel();

      return true;
    }

    /**
     * The picker keeps itself open on time changes when a time is involved, so
     * close it as soon as a day is picked - that is what users expect from a
     * date field. Times can still be adjusted by reopening the picker.
     */
    bindPickerEvents() {
      // Capture phase: the picker rebuilds its calendar on a day click, which
      // detaches the clicked cell from the container.
      document.addEventListener('click', (event) => {
        const day = event.target && event.target.closest ? event.target.closest('.jdp-day') : null;

        if (!day || !day.closest('jdp-container')) {
          return;
        }

        // Let the picker commit the day first, then close it.
        setTimeout(() => window.jalaliDatepicker.hide(), 0);
      }, true);
    }

    /**
     * The Jalali picker renders date & time; date-only controls opt out per
     * input through `data-jdp-only-date`.
     */
    buildPickerOptions() {
      const options = {
        selector: '.' + DISPLAY_CLASS,
        zIndex: 999999999,
        time: true,
        hasSecond: false,
        autoReadOnlyInput: false,
        hideAfterChange: true,
        // Keep the calendar open after a time change so hour and minute can be
        // adjusted in one go; a picked day closes it (see bindPickerEvents()).
        hideAfterChangeWithTime: false,
      };

      if (this.settings.months && this.settings.months.length === 12) {
        options.months = this.settings.months;
      }

      if (typeof this.settings.persianDigits !== 'undefined') {
        options.persianDigits = !!this.settings.persianDigits;
      }

      return options;
    }

    startWatcher() {
      window.jalaliDatepicker.startWatch(this.buildPickerOptions());
    }

    /**
     * Prepare every Elementor date & time control under `root`.
     */
    scan(root) {
      const scope = root || document;

      if (!scope.querySelectorAll) {
        return;
      }

      scope.querySelectorAll(this.fieldSelector).forEach((input) => this.setupField(input));
    }

    /**
     * Whether a removed panel node was one of our Jalali fields.
     */
    containsField(node) {
      if (node.classList && node.classList.contains(DISPLAY_CLASS)) {
        return true;
      }

      return !!(node.querySelector && node.querySelector('.' + DISPLAY_CLASS));
    }

    hidePicker() {
      try {
        window.jalaliDatepicker.hide();
      } catch (e) { /* noop */ }
    }

    setupField(input) {
      if (input.dataset.wppJalaliEditorReady) {
        return;
      }

      new WpPdElementorEditorJalaliField(input).mount();
    }

    /**
     * Controls are rendered on demand, so watch the panel for new ones.
     */
    observePanel() {
      if (typeof MutationObserver === 'undefined' || !document.body) {
        return;
      }

      this.observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          mutation.removedNodes.forEach((node) => {
            // The panel re-renders when another element is edited; drop the
            // picker along with the field it belongs to.
            if (node.nodeType === 1 && this.containsField(node)) {
              this.hidePicker();
            }
          });

          mutation.addedNodes.forEach((node) => {
            if (!node || node.nodeType !== 1) {
              return;
            }

            if (node.matches && node.matches(this.fieldSelector)) {
              this.setupField(node);

              return;
            }

            this.scan(node);
          });
        });
      });

      this.observer.observe(document.body, { childList: true, subtree: true });
    }
  }

  /* ------------------------------------------------------------------ *
   * Boot
   * ------------------------------------------------------------------ */

  window.WpPdJalaliConvert = WpPdJalaliConvert;
  window.WpPdElementorEditorJalaliField = WpPdElementorEditorJalaliField;
  window.WpPdElementorEditorJalaliDate = WpPdElementorEditorJalaliDate;

  const boot = () => {
    window.wpParsidateElementorJalaliEditor = new WpPdElementorEditorJalaliDate(window.WPP_Elementor_Jalali_Editor);
    window.wpParsidateElementorJalaliEditor.init();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
