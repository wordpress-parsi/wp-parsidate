'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

function loadCalendar() {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);

  const calendarSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-src/bookly-jalali-calendar.js'), 'utf8');
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const showCalls = [];
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      BooklyL10nGlobal: { datePicker: { monthNamesShort: monthNames } },
      jalaliDatepicker: {
        show(input) {
          showCalls.push(input);
        },
      },
      requestAnimationFrame(callback) {
        callback();
      },
      setTimeout,
    },
    Event,
    document: {
      readyState: 'loading',
      addEventListener() {},
      querySelector(selector) {
        return selector === 'jdp-container' ? { style: { display: 'block' } } : null;
      },
    },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    calendarSource + '\nglobalThis.testCalendar = new WpPdBooklyJalaliCalendar();',
    context
  );

  return {
    calendar: context.testCalendar,
    engine: engineContext.window.WpPdJalaliDate,
    monthNames,
    showCalls,
  };
}

test('formats Bookly Gregorian dates as valid Jalali picker values', () => {
  const { calendar } = loadCalendar();
  const source = {
    value: '',
    getAttribute(name) {
      return name === 'data-value' ? '2026-11-17' : null;
    },
  };
  const formatted = calendar.formatJalali(calendar.initialDate(source));

  assert.match(formatted, /^\d{4}-\d{2}-\d{2}$/);
  assert.equal(formatted.includes('undefined'), false);
});

test('converts picker selections back to Gregorian dates for Bookly', () => {
  const { calendar, engine } = loadCalendar();
  const gregorian = calendar.parseJalaliDate('1405-08-26');
  const expected = engine.toGregorian(1405, 8, 26);

  assert.ok(gregorian);
  assert.deepEqual([gregorian.gy, gregorian.gm, gregorian.gd], [expected.gy, expected.gm, expected.gd]);
});

test('rejects invalid Jalali dates before passing them to Bookly', () => {
  const { calendar } = loadCalendar();

  assert.equal(calendar.parseJalaliDate('1405-13-01'), null);
});

test('selects the converted Gregorian day in Bookly native calendar', async () => {
  const { calendar, monthNames } = loadCalendar();
  const target = calendar.parseJalaliDate('1405-08-26');
  let clicked = false;
  const day = {
    textContent: String(target.gd),
    disabled: false,
    matches: (selector) => selector === 'button',
    getAttribute: () => null,
    click() {
      clicked = true;
    },
  };
  const nativeCalendar = {
    querySelector(selector) {
      return selector === '.bookly-calendar-title-mark'
        ? { textContent: monthNames[target.gm - 1] + ' ' + target.gy }
        : null;
    },
    querySelectorAll(selector) {
      return selector === '.bookly-calendar-current-month-mark' ? [day] : [];
    },
  };

  assert.equal(await calendar.selectNativeDate({ type: 'time', nativeCalendar }, target), true);
  assert.equal(clicked, true);
});

test('selects Bookly 28.8 dates using its rendered month and day elements', async () => {
  const { calendar, monthNames } = loadCalendar();
  const target = calendar.parseJalaliDate('1405-08-28');
  let clicked = false;
  const day = {
    textContent: String(target.gd),
    matches: () => false,
    querySelector: () => null,
    getAttribute(name) {
      return name === 'disabled' ? 'false' : null;
    },
    click() {
      clicked = true;
    },
  };
  const nativeCalendar = {
    querySelector(selector) {
      return selector === '.bookly-calendar-middle-button-mark'
        ? { textContent: monthNames[target.gm - 1] + ' ' + target.gy }
        : null;
    },
    querySelectorAll(selector) {
      return selector === '.bookly-calendar-current-month-mark' ? [day] : [];
    },
  };

  assert.equal(await calendar.selectNativeDate({ type: 'time', nativeCalendar }, target), true);
  assert.equal(clicked, true);
});

test('opens Bookly native calendar after the shared picker click finishes', async () => {
  const { calendar, monthNames } = loadCalendar();
  const target = calendar.parseJalaliDate('1405-08-29');
  let pickerClickInProgress = true;
  let calendarOpened = false;
  let clicked = false;
  const nativeCalendar = {
    querySelector(selector) {
      return !calendarOpened || selector !== '.bookly-calendar-middle-button-mark'
        ? null
        : { textContent: monthNames[target.gm - 1] + ' ' + target.gy };
    },
    querySelectorAll(selector) {
      return calendarOpened && selector === '.bookly-calendar-current-month-mark'
        ? [{
          textContent: String(target.gd),
          disabled: false,
          matches: () => false,
          querySelector: () => null,
          getAttribute: () => 'false',
          click() {
            clicked = true;
          },
        }]
        : [];
    },
  };
  const instance = {
    type: 'service',
    source: {
      dispatchEvent() {
        calendarOpened = !pickerClickInProgress;
      },
    },
    nativeCalendar,
  };

  const selection = calendar.selectNativeDate(instance, target);
  pickerClickInProgress = false;

  assert.equal(await selection, true);
  assert.equal(clicked, true);
});

test('passes the shared picker selection to Bookly as Gregorian', async () => {
  const { calendar, engine } = loadCalendar();
  const displayInput = {
    value: '1405-08-26',
    disabled: false,
    setAttribute() {},
    removeAttribute() {},
  };
  const instance = {
    displayInput,
    lastValue: '1405-08-25',
    status: { textContent: '' },
    selecting: false,
  };
  let selectedDate;
  calendar.selectNativeDate = async (current, target) => {
    selectedDate = target;
    return true;
  };
  const expected = engine.toGregorian(1405, 8, 26);

  await calendar.selectDate(instance);

  assert.deepEqual(
    [selectedDate.gy, selectedDate.gm, selectedDate.gd],
    [expected.gy, expected.gm, expected.gd]
  );
  assert.equal(instance.lastValue, '1405-08-26');
  assert.equal(displayInput.disabled, false);
});

test('restores the previous date when Bookly rejects a Jalali selection', async () => {
  const { calendar } = loadCalendar();
  calendar.settings.unavailable = 'Unavailable';
  const instance = {
    displayInput: {
      value: '1405-08-26',
      disabled: false,
      setAttribute() {},
      removeAttribute() {},
    },
    lastValue: '1405-08-25',
    status: { textContent: '' },
    selecting: false,
  };
  calendar.selectNativeDate = async () => false;

  await calendar.selectDate(instance);

  assert.equal(instance.displayInput.value, '1405-08-25');
  assert.notEqual(instance.status.textContent, '');
  assert.equal(instance.displayInput.disabled, false);
});

test('accepts Bookly’s already-selected current date without clicking a disabled day', async () => {
  const { calendar } = loadCalendar();
  let nativeSelectCalls = 0;
  const source = {
    getAttribute(name) {
      return name === 'data-value' ? '2026-11-19' : null;
    },
  };
  const displayInput = {
    value: '1405-08-28',
    addEventListener() {},
    setAttribute() {},
    removeAttribute() {},
  };
  const instance = calendar.createInstance(
    source,
    {},
    displayInput,
    { textContent: '' },
    'service'
  );
  calendar.selectNativeDate = async () => {
    nativeSelectCalls++;
    return false;
  };

  await calendar.selectDate(instance);

  assert.equal(nativeSelectCalls, 0);
  assert.equal(instance.selectedDate, '2026-11-19');
  assert.equal(instance.status.textContent, '');
});

test('opens the shared picker only once for a focus and click pair', () => {
  const { calendar, showCalls } = loadCalendar();
  const instance = {
    displayInput: {
      setAttribute() {},
    },
    isOpen: false,
  };

  calendar.open(instance);
  calendar.open(instance);

  assert.equal(showCalls.length, 1);
});
