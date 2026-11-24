'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

function createInput(attributes = {}, previousElementSibling = null) {
  const attrs = { ...attributes };

  return {
    attributes: attrs,
    previousElementSibling,
    getAttribute(name) {
      return Object.prototype.hasOwnProperty.call(attrs, name) ? attrs[name] : null;
    },
    setAttribute(name, value) {
      attrs[name] = value;
    },
    hasAttribute(name) {
      return Object.prototype.hasOwnProperty.call(attrs, name);
    },
  };
}

/**
 * Loads the shipped generic datepicker wiring (not the Bookly adapters) with a
 * minimal DOM/jQuery stand-in.
 */
function loadDatepicker() {
  const source = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin-src/datepicker.js'), 'utf8');

  const listeners = {};
  const originalShowCalls = [];
  const startWatchCalls = [];
  const timeouts = [];
  const state = { observerCallback: null, observed: false };

  const originalShow = function (input) {
    originalShowCalls.push(input);
  };

  function jquery(target) {
    return {
      is: () => target && target.jalali === true,
      on: () => jquery(target),
    };
  }

  jquery.datepicker = {
    _showDatepicker: originalShow,
    _datepickerShowing: false,
    _hideDatepicker: () => {},
  };

  const document = {
    body: { tagName: 'BODY' },
    addEventListener(type, handler) {
      (listeners[type] = listeners[type] || []).push(handler);
    },
    querySelectorAll: () => state.inputs || [],
  };

  const context = {
    window: {
      jQuery: jquery,
      jalaliDatepicker: {
        startWatch(options) {
          startWatchCalls.push(options);
        },
      },
      WPP_I18N: { months: ['Farvardin', 'Ordibehesht'] },
    },
    document,
    jQuery: jquery,
    MutationObserver: class {
      constructor(callback) {
        state.observerCallback = callback;
      }

      observe() {
        state.observed = true;
      }
    },
    setTimeout(callback) {
      timeouts.push(callback);
      return timeouts.length;
    },
    console,
  };

  context.globalThis = context;
  vm.createContext(context);
  vm.runInContext(source, context);

  function fire(type) {
    (listeners[type] || []).forEach((handler) => handler());
  }

  return { context, state, originalShowCalls, startWatchCalls, timeouts, fire, document };
}

test('exposes the shared selector other integrations extend', () => {
  const { context } = loadDatepicker();
  const selector = context.window.WPP_JALALI_SELECTOR;

  assert.equal(typeof selector, 'string');

  for (const expected of ['.date-picker', 'input.hasDatepicker', 'input.sale_price_dates_from', 'input.sale_price_dates_to', '.cx-control input.wpp-jalali-date']) {
    assert.ok(selector.includes(expected), `selector must include ${expected}`);
  }
});

test('blocks the jQuery UI popup on Jalali fields only', () => {
  const { context, originalShowCalls } = loadDatepicker();

  const jalaliInput = createInput({ class: 'date-picker' });
  jalaliInput.jalali = true;
  const otherInput = createInput({ class: 'something-else' });

  context.window.jQuery.datepicker._showDatepicker(jalaliInput);
  assert.equal(originalShowCalls.length, 0, 'jQuery UI must not open on a Jalali field');

  context.window.jQuery.datepicker._showDatepicker({ target: otherInput });
  assert.equal(originalShowCalls.length, 1, 'other datepickers keep working');
  assert.equal(originalShowCalls[0], otherInput, 'the guard forwards the unwrapped input');
});

test('starts the watcher with time support and localized months', () => {
  const { fire, startWatchCalls, context } = loadDatepicker();

  fire('DOMContentLoaded');

  assert.equal(startWatchCalls.length, 1);
  const options = startWatchCalls[0];

  assert.equal(options.selector, context.window.WPP_JALALI_SELECTOR);
  assert.equal(options.time, true, 'JetEngine datetime fields need the time section');
  assert.equal(options.persianDigits, true);
  assert.equal(options.hasSecond, false);
  assert.deepEqual(options.months, ['Farvardin', 'Ordibehesht']);
  assert.equal(options.separatorChars.date, '-');
});

test('marks date-only fields and leaves datetime and time-only fields alone', () => {
  const { context, state, timeouts, fire } = loadDatepicker();

  fire('DOMContentLoaded');

  const plain = createInput();
  const jetEngineDatetime = createInput({}, { tagName: 'INPUT', type: 'hidden', hasAttribute: () => true });
  const timeOnly = createInput({ 'data-jdp-only-time': '' });
  const alreadyMarked = createInput({ 'data-jdp-only-date': '' });

  state.inputs = [plain, jetEngineDatetime, timeOnly, alreadyMarked];

  // The flag and the first marking pass are scheduled 2s after initialization.
  timeouts.forEach((callback) => callback());

  assert.equal(plain.getAttribute('data-jdp-only-date'), '', 'plain fields show date only');
  assert.equal(jetEngineDatetime.getAttribute('data-jdp-only-date'), null, 'JetEngine datetime keeps its time picker');
  assert.equal(timeOnly.getAttribute('data-jdp-only-date'), null, 'explicit time-only fields are respected');
  assert.equal(alreadyMarked.getAttribute('data-jdp-only-datetime'), null);
  assert.ok(context);
});

test('marks fields added later through the mutation observer', () => {
  const { state, timeouts, fire } = loadDatepicker();

  fire('DOMContentLoaded');
  assert.equal(state.observed, true, 'the observer watches the document body');

  timeouts.forEach((callback) => callback());

  const added = createInput();
  state.observerCallback([{ addedNodes: [added], target: { querySelectorAll: () => [added] } }]);

  assert.equal(added.getAttribute('data-jdp-only-date'), '');
});
