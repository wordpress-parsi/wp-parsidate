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

  const calendarSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-src/bookly-pro-calendar.js'), 'utf8');
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      WpPdBooklyProCalendar: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
      },
      addEventListener() {},
    },
    document: { readyState: 'loading', addEventListener() {} },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    calendarSource + '\nglobalThis.testCalendar = new WpPdBooklyProCalendar();',
    context
  );

  return { calendar: context.testCalendar, context };
}

test('formats Gregorian dates for Bookly Pro as Jalali', () => {
  const { calendar } = loadCalendar();

  assert.deepEqual(
    { ...calendar.toJalaliDate('2026-11-30') },
    { jy: 1405, jm: 9, jd: 9 }
  );
});

test('localizes Bookly Pro frontend calendar day numbers', () => {
  const { calendar } = loadCalendar();
  const header = {
    textContent: 'Mon, 30',
    getAttribute: (name) => name === 'datetime' ? '2026-11-30' : null,
  };
  const container = {
    querySelectorAll: (selector) => selector === '.ec-col-head time' ? [header] : [],
  };

  calendar.localizeDayHeaders(container);

  assert.equal(header.textContent, 'Mon, ۹');
});

test('passes Pro calendar Jalali selections to Bookly as Gregorian dates', () => {
  const { calendar } = loadCalendar();
  const received = [];
  const picker = {
    startDate: null,
    endDate: null,
    chosenLabel: 'Custom range',
    setStartDate(date) {
      this.startDate = { clone: () => ({ toDate: () => date }) };
    },
    setEndDate(date) {
      this.endDate = { clone: () => ({ toDate: () => date }) };
    },
    callback(start, end, label) {
      received.push([
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

  assert.equal(calendar.applyJalaliDate('۱۴۰۵-۰۹-۰۹', picker), true);
  assert.deepEqual(received, [[2026, 11, 30, 2026, 11, 30, 'Custom range']]);
});

test('does not pass invalid Jalali dates to Bookly Pro', () => {
  const { calendar } = loadCalendar();
  let called = false;
  const picker = {
    setStartDate() {},
    setEndDate() {},
    callback() {
      called = true;
    },
  };

  assert.equal(calendar.applyJalaliDate('1405-13-09', picker), false);
  assert.equal(called, false);
});
