'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

class FakeElement {
  constructor() {
    this.listeners = {};
    this.style = {};
    this.children = [];
    this.attributes = {};
    this.scrollLeft = 0;
    this.scrollTop = 0;
    this.parentElement = null;
    this.value = '';
  }

  addEventListener(type, callback) {
    this.listeners[type] = callback;
  }

  appendChild(child) {
    child.parentElement = this;
    this.children.push(child);
  }

  setAttribute(name, value) {
    this.attributes[name] = value;
  }

  getBoundingClientRect() {
    return this.rect || { left: 0, top: 0, width: 100, height: 30 };
  }

  dispatch(type, event = {}) {
    const calls = [];
    event.preventDefault = () => calls.push('preventDefault');
    event.stopPropagation = () => calls.push('stopPropagation');
    event.stopImmediatePropagation = () => calls.push('stopImmediatePropagation');
    this.listeners[type](event);
    return calls;
  }
}

function loadCalendar() {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);

  const calendarSource = fs.readFileSync(
    path.join(pluginRoot, 'assets/js-src/bookly-admin-jalali-calendar.js'),
    'utf8'
  );
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      WpPdBooklyAdminJalali: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
      },
      addEventListener() {},
    },
    document: { readyState: 'loading' },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    calendarSource + '\nglobalThis.testCalendar = new WpPdBooklyAdminJalaliCalendar();',
    context
  );

  return { calendar: context.testCalendar, context };
}

test('formats Bookly admin Gregorian appointment dates as Jalali', () => {
  const { calendar } = loadCalendar();

  assert.equal(calendar.formatGregorianDate('2026-11-30'), '۹ آذر ۱۴۰۵');
});

test('keeps the time intact when formatting Bookly appointment date-times', () => {
  const { calendar } = loadCalendar();

  assert.equal(calendar.localizeDateText('2026-11-30 08:30'), '۹ آذر ۱۴۰۵ 08:30');
});

test('localizes Bookly calendar header day numbers from their Gregorian dates', () => {
  const { calendar, context } = loadCalendar();
  const headers = [
    {
      textContent: 'Mon, 30',
      getAttribute: (name) => name === 'datetime' ? '2026-11-30' : null,
    },
    {
      textContent: 'Tue, 1',
      getAttribute: (name) => name === 'datetime' ? '2026-12-01' : null,
    },
  ];
  context.document.querySelectorAll = (selector) => {
    return selector === '.bookly-js-calendar .ec-col-head time' ? headers : [];
  };

  calendar.localizeCalendarDayHeaders();

  assert.deepEqual(headers.map((header) => header.textContent), ['Mon, ۹', 'Tue, ۱۰']);
});

test('converts rendered ISO dates inside the Bookly appointment card', () => {
  const { calendar, context } = loadCalendar();
  const dateElement = {};
  const textNode = {
    nodeValue: '2026-11-30',
    parentElement: { closest: () => null },
  };
  let visited = false;
  context.window.NodeFilter = { SHOW_TEXT: 4 };
  context.document.querySelectorAll = () => [dateElement];
  context.document.createTreeWalker = () => ({
    nextNode() {
      if (visited) {
        return null;
      }
      visited = true;
      return textNode;
    },
  });

  calendar.localizeAppointmentCard();

  assert.equal(textNode.nodeValue, '۹ آذر ۱۴۰۵');
});

test('localizes Bookly appointment dates when the dialog is portalled outside its host', () => {
  const { calendar, context } = loadCalendar();
  const dateElement = {};
  const textNode = {
    nodeValue: '2026-11-30',
    parentElement: { closest: () => null },
  };
  let visited = false;
  context.window.NodeFilter = { SHOW_TEXT: 4 };
  context.document.getElementById = () => null;
  context.document.querySelectorAll = () => [dateElement];
  context.document.createTreeWalker = () => ({
    nextNode() {
      if (visited) {
        return null;
      }

      visited = true;
      return textNode;
    },
  });

  calendar.localizeAppointmentCard();

  assert.equal(textNode.nodeValue, '۹ آذر ۱۴۰۵');
});

test('localizes appointment dates when Bookly updates an existing text node', () => {
  const { calendar, context } = loadCalendar();
  const dateElement = {};
  const textNode = {
    nodeValue: 'November 30, 2026',
    parentElement: { closest: () => null },
  };
  let observerCallback;
  let observerOptions;
  let visited = false;
  context.window.NodeFilter = { SHOW_TEXT: 4 };
  context.window.requestAnimationFrame = (callback) => callback();
  context.window.MutationObserver = class {
    constructor(callback) {
      observerCallback = callback;
    }

    observe(target, options) {
      observerOptions = options;
    }
  };
  context.document.body = {};
  context.document.querySelectorAll = () => [];
  context.document.querySelectorAll = (selector) => selector === 'p[class*="tabular-nums"]' ? [dateElement] : [];
  context.document.createTreeWalker = () => ({
    nextNode() {
      if (visited) {
        return null;
      }

      visited = true;
      return textNode;
    },
  });

  calendar.observe();
  textNode.nodeValue = '2026-11-30';
  observerCallback([]);

  assert.equal(observerOptions.characterData, true);
  assert.equal(textNode.nodeValue, '۹ آذر ۱۴۰۵');
});

test('makes Bookly appointment-card dates machine-readable before rendering', () => {
  const { calendar, context } = loadCalendar();
  context.document.body = {};
  context.document.querySelectorAll = () => [];
  context.document.getElementById = () => null;
  context.window.jalaliDatepicker = {};
  context.window.BooklyL10nAppointmentCard = { moment_format_date: 'D MMM YYYY' };
  context.window.MutationObserver = class {
    observe() {}
  };

  calendar.boot();

  assert.equal(context.window.BooklyL10nAppointmentCard.moment_format_date, 'YYYY-MM-DD');
});

test('applies a Jalali admin-calendar selection through Bookly’s Gregorian date picker', () => {
  const { calendar } = loadCalendar();
  const calls = [];
  const picker = {
    startDate: null,
    endDate: null,
    chosenLabel: null,
    setStartDate(date) {
      this.startDate = {
        clone: () => ({ toDate: () => date }),
      };
    },
    setEndDate(date) {
      this.endDate = {
        clone: () => ({ toDate: () => date }),
      };
    },
    clickApply() {
      if (this.isShowing) {
        this.callback(this.startDate.clone(), this.endDate.clone(), this.chosenLabel);
      }
    },
    callback(start, end, label) {
      calls.push([
        'callback',
        start.toDate().getFullYear(),
        start.toDate().getMonth() + 1,
        start.toDate().getDate(),
        end.toDate().getFullYear(),
        end.toDate().getMonth() + 1,
        end.toDate().getDate(),
        label,
      ]);
    },
  };

  assert.equal(calendar.applyJalaliDate('1405-09-09', picker), true);
  assert.deepEqual(calls, [[
    'callback',
    2026,
    11,
    30,
    2026,
    11,
    30,
    null,
  ]]);
});

test('does not send an invalid Jalali admin date to Bookly', () => {
  const { calendar } = loadCalendar();
  let applied = false;
  const picker = {
    callback() {
      applied = true;
    },
    setStartDate() {},
    setEndDate() {},
    clickApply() {},
    startDate: { clone: () => ({}) },
    endDate: { clone: () => ({}) },
  };

  assert.equal(calendar.applyJalaliDate('1405-13-09', picker), false);
  assert.equal(applied, false);
});

test('opens the shared picker from Bookly’s calendar title and applies its Jalali selection', async () => {
  const { calendar, context } = loadCalendar();
  const calls = [];
  const datePicker = {
    startDate: { format: () => '2026-11-30' },
    hide() {
      calls.push(['hide']);
    },
    setStartDate(date) {
      this.startDate = { clone: () => ({ toDate: () => date }) };
    },
    setEndDate(date) {
      this.endDate = { clone: () => ({ toDate: () => date }) };
    },
    clickApply() {
      calls.push(['apply']);
    },
    callback(start, end) {
      calls.push([
        'callback',
        start.toDate().getFullYear(),
        start.toDate().getMonth() + 1,
        start.toDate().getDate(),
        end.toDate().getFullYear(),
        end.toDate().getMonth() + 1,
        end.toDate().getDate(),
      ]);
    },
  };
  const shown = [];
  const parent = new FakeElement();
  const title = new FakeElement();
  parent.rect = { left: 0, top: 0, width: 300, height: 50 };
  title.parentElement = parent;
  title.rect = { left: 100, top: 10, width: 120, height: 30 };
  context.window.jQuery = () => ({ data: () => datePicker });
  context.window.getComputedStyle = () => ({ position: 'static' });
  context.window.jalaliDatepicker = {
    show(input) {
      shown.push(input);
      context.document.querySelector = () => ({ style: { display: 'block' } });
    },
  };
  context.document = {
    createElement() {
      return new FakeElement();
    },
    querySelector() {
      return { style: { display: 'none' } };
    },
  };

  calendar.prepareCalendarTitle(title);
  const input = parent.children[0];
  assert.equal(input.value, '1405-09-09');

  const clickCalls = title.dispatch('click');
  datePicker.startDate = { format: () => '2026-12-01' };
  await Promise.resolve();
  assert.equal(input.value, '1405-09-10');
  input.value = '1405-09-10';
  input.dispatch('jdp:change');

  assert.deepEqual(clickCalls, ['preventDefault']);
  assert.equal(input.value, '1405-09-10');
  assert.equal(shown[0], input);
  assert.deepEqual(calls, [
    ['hide'],
    ['callback', 2026, 12, 1, 2026, 12, 1],
  ]);
});
