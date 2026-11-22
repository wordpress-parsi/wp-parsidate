'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');
const months = ['ژانویه', 'فوریه', 'مارس', 'آوریل', 'می', 'ژوئن', 'جولای', 'آگوست', 'سپتامبر', 'اکتبر', 'نوامبر', 'دسامبر'];

function loadRangeCalendar() {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);

  const adapterSource = fs.readFileSync(
    path.join(pluginRoot, 'assets/js-src/bookly-admin-jalali-daterange.js'),
    'utf8'
  );
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      BooklyL10nGlobal: {
        datePicker: {
          monthNames: months,
          monthNamesShort: months,
        },
      },
      WpPdBooklyAdminJalaliDaterange: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        separator: ' - ',
      },
      addEventListener() {},
      setTimeout,
      clearTimeout,
      clearInterval,
      setInterval,
      console,
    },
    document: {
      readyState: 'loading',
      addEventListener() {},
      querySelectorAll() {
        return [];
      },
      querySelector() {
        return null;
      },
    },
    getComputedStyle(element) {
      return {
        display: element.__display || 'none',
        visibility: element.__visibility || 'hidden',
      };
    },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    adapterSource + '\nglobalThis.testRangeCalendar = new WpPdBooklyAdminJalaliRangeCalendar();',
    context
  );

  return { calendar: context.testRangeCalendar, context };
}

function createPopover(dates) {
  const cells = dates.map((value) => ({
    getAttribute(name) {
      return name === 'data-value' ? value : null;
    },
  }));
  const root = {
    querySelectorAll(selector) {
      return selector === '[data-range-calendar-day][data-selected]' ? cells : [];
    },
  };
  const popover = {
    querySelector(selector) {
      return selector === '[data-range-calendar-root]' ? root : null;
    },
  };

  return { popover, root, cells };
}

test('formats the Gregorian range Bookly prints beside the filter trigger', () => {
  const { calendar } = loadRangeCalendar();
  const dates = calendar.parseGregorianLabel('نوامبر 23 - دسامبر 3, 2026');

  assert.deepEqual(JSON.parse(JSON.stringify(dates)), [
    { gy: 2026, gm: 11, gd: 23 },
    { gy: 2026, gm: 12, gd: 3 },
  ]);
  assert.equal(calendar.formatGregorianDates(dates), '۲ آذر ۱۴۰۵ - ۱۲ آذر ۱۴۰۵');
});

test('parses a day-first Gregorian range label', () => {
  const { calendar } = loadRangeCalendar();

  assert.deepEqual(
    JSON.parse(JSON.stringify(calendar.parseGregorianLabel('23 نوامبر - 3 دسامبر 2026'))),
    [
      { gy: 2026, gm: 11, gd: 23 },
      { gy: 2026, gm: 12, gd: 3 },
    ]
  );
});

test('parses the collapsed same-month range Bookly prints as "month day - day"', () => {
  const { calendar } = loadRangeCalendar();
  const dates = calendar.parseGregorianLabel('دسامبر 3 - 9, 2026');

  assert.deepEqual(JSON.parse(JSON.stringify(dates)), [
    { gy: 2026, gm: 12, gd: 3 },
    { gy: 2026, gm: 12, gd: 9 },
  ]);
  assert.equal(calendar.formatGregorianDates(dates), '۱۲ آذر ۱۴۰۵ - ۱۸ آذر ۱۴۰۵');
});

test('parses the collapsed same-month range in a day-first label', () => {
  const { calendar } = loadRangeCalendar();

  assert.deepEqual(
    JSON.parse(JSON.stringify(calendar.parseGregorianLabel('3 - 9 دسامبر 2026'))),
    [
      { gy: 2026, gm: 12, gd: 3 },
      { gy: 2026, gm: 12, gd: 9 },
    ]
  );
});

test('replaces the trigger range text without replacing its text node', () => {
  const { calendar, context } = loadRangeCalendar();
  const textNode = { nodeType: 3, nodeValue: 'نوامبر 23 - دسامبر 3, 2026' };
  const attributes = { title: 'نوامبر 23 - دسامبر 3, 2026' };
  const span = {
    childNodes: [textNode],
    textContent: textNode.nodeValue,
    getAttribute(name) {
      return attributes[name] || null;
    },
    setAttribute(name, value) {
      attributes[name] = value;
    },
  };
  const trigger = {
    querySelectorAll(selector) {
      return selector === 'span' ? [span] : [];
    },
  };
  context.document.querySelectorAll = (selector) => {
    return selector === '[data-popover-trigger]' ? [trigger] : [];
  };

  calendar.localizeTriggers();

  assert.equal(textNode.nodeValue, '۲ آذر ۱۴۰۵ - ۱۲ آذر ۱۴۰۵');
  assert.equal(attributes.title, '۲ آذر ۱۴۰۵ - ۱۲ آذر ۱۴۰۵');
});

test('seeds the Jalali field from the range Bookly already selected', () => {
  const { calendar } = loadRangeCalendar();
  const { popover } = createPopover(['2026-12-03', '2026-11-23', '2026-11-30']);

  assert.deepEqual(
    JSON.parse(JSON.stringify(calendar.selectedRange(popover))),
    { start: '2026-11-23', end: '2026-12-03' }
  );
  assert.equal(calendar.rangeValue(popover, false), '۲ آذر ۱۴۰۵ - ۱۲ آذر ۱۴۰۵');
  assert.equal(calendar.rangeValue(popover, true), '۲ آذر ۱۴۰۵');

  const input = {
    value: '',
    closest(selector) {
      return selector === '[data-slot="popover-content"]' ? popover : null;
    },
  };
  calendar.seedRangeInput({ input, popover, single: false });

  assert.equal(input.value, '۲ آذر ۱۴۰۵ - ۱۲ آذر ۱۴۰۵');
});

test('seeds the field while the picker container exists but is hidden', () => {
  const { calendar, context } = loadRangeCalendar();
  const { popover } = createPopover(['2026-12-03', '2026-12-09']);
  const container = { __display: 'grid', __visibility: 'hidden', offsetHeight: 100 };
  context.document.querySelector = (selector) => {
    return selector === 'jdp-container' ? container : null;
  };
  const input = {
    value: '',
    closest() {
      return popover;
    },
  };

  calendar.seedRangeInput({ input, popover, single: false });

  assert.equal(input.value, '۱۲ آذر ۱۴۰۵ - ۱۸ آذر ۱۴۰۵');
});

test('does not overwrite a range the user picked before Bookly applies it', () => {
  const { calendar } = loadRangeCalendar();
  const { popover } = createPopover(['2026-12-03', '2026-12-09']);
  const input = {
    value: '1405-09-20 - 1405-09-25',
    closest() {
      return popover;
    },
  };

  calendar.seedRangeInput({ input, popover, single: false, dirty: true, seeded: '۱۲ آذر ۱۴۰۵ - ۱۸ آذر ۱۴۰۵' });

  assert.equal(input.value, '1405-09-20 - 1405-09-25');
});

test('does not overwrite the field while the Jalali picker is open', () => {
  const { calendar, context } = loadRangeCalendar();
  const { popover } = createPopover(['2026-11-23', '2026-12-03']);
  const container = { __display: 'block', __visibility: 'visible', offsetHeight: 100 };
  context.document.querySelector = (selector) => {
    return selector === 'jdp-container' ? container : null;
  };
  const input = {
    value: '1405-09-10 - 1405-09-15',
    closest() {
      return popover;
    },
  };

  calendar.seedRangeInput({ input, popover, single: false });

  assert.equal(input.value, '1405-09-10 - 1405-09-15');
});

test('pushes a single Jalali date back as Gregorian for the appointment dialog', () => {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);
  const adapterSource = fs.readFileSync(
    path.join(pluginRoot, 'assets/js-src/bookly-admin-jalali-daterange.js'),
    'utf8'
  );
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      WpPdBooklyAdminJalaliDaterange: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        separator: ' - ',
      },
      jQuery() {
        return { trigger() {} };
      },
      addEventListener() {},
      console,
    },
    document: { readyState: 'loading', addEventListener() {}, body: {}, querySelectorAll() { return []; } },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    adapterSource + '\nglobalThis.testAdapter = new WpPdBooklyAdminJalaliDaterange();',
    context
  );
  const adapter = context.testAdapter;

  const calls = [];
  const picker = {
    singleDatePicker: true,
    startDate: { clone: () => ({ format: () => '2026-11-23' }) },
    endDate: { clone: () => ({ format: () => '2026-11-23' }) },
    setStartDate(date) { calls.push(['setStartDate', date]); },
    setEndDate(date) { calls.push(['setEndDate', date]); },
    callback() {},
  };
  const entry = {
    single: true,
    picker,
    element: { getAttribute() { return ''; } },
    overlay: { value: '۱۴۰۵-۰۹-۰۲', style: {} },
  };
  adapter.refreshText = () => {};
  adapter.apply(entry);

  assert.equal(calls.length, 2);
  assert.equal(calls[0][0], 'setStartDate');
  assert.equal(calls[0][1].getFullYear(), 2026);
  assert.equal(calls[0][1].getMonth(), 10);
  assert.equal(calls[0][1].getDate(), 23);
  assert.deepEqual(calls[1], calls[0].map((value, i) => i === 0 ? 'setEndDate' : value));
});

test('leaves the trigger untouched when it is not a date range', () => {
  const { calendar, context } = loadRangeCalendar();
  const textNode = { nodeType: 3, nodeValue: 'Page appearance' };
  const span = {
    childNodes: [textNode],
    textContent: textNode.nodeValue,
    getAttribute() {
      return null;
    },
    setAttribute() {
      throw new Error('should not update a non-date trigger');
    },
  };
  const trigger = {
    querySelectorAll(selector) {
      return selector === 'span' ? [span] : [];
    },
  };
  context.document.querySelectorAll = (selector) => {
    return selector === '[data-popover-trigger]' ? [trigger] : [];
  };

  calendar.localizeTriggers();

  assert.equal(textNode.nodeValue, 'Page appearance');
});
