'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

function loadAdapter() {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);

  const adapterSource = fs.readFileSync(
    path.join(pluginRoot, 'assets/js-src/bookly-pro-services-form.js'),
    'utf8'
  );
  const shownInputs = [];
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      BooklyL10nModernBookingForm: {
        datePicker: {
          monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
          firstDay: 6,
        },
        l10n: { date: 'Date' },
      },
      WpPdBooklyProServicesFormSettings: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        firstDay: '0',
      },
      jalaliDatepicker: { show(input) { shownInputs.push(input); } },
      requestAnimationFrame(callback) {
        callback();
      },
      addEventListener() {},
      console,
    },
    document: {
      readyState: 'loading',
      addEventListener() {},
      querySelector() {
        return null;
      },
    },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    adapterSource + '\nglobalThis.testAdapter = new WpPdBooklyProServicesForm();',
    context
  );

  return { adapter: context.testAdapter, context, shownInputs };
}

function createCalendar(initialMonth, activeDay = null) {
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  let currentMonth = initialMonth;
  let selectedDay = null;
  const title = { get textContent() { return monthNames[currentMonth - 1] + ' 2026'; } };
  const calendar = {
    querySelector(selector) {
      if (selector === '.bookly-calendar-title-mark') {
        return title;
      }

      if (selector === '.bookly-calendar-right-button-mark') {
        return {
        closest: () => ({
          disabled: false,
          getAttribute: () => null,
          click() { currentMonth += 1; },
        }),
        };
      }

      if (selector === '.bookly-calendar-left-button-mark') {
        return {
        closest: () => ({
          disabled: false,
          getAttribute: () => null,
          click() { currentMonth -= 1; },
        }),
        };
      }

      return null;
    },
    querySelectorAll(selector) {
      if (selector !== '.bookly-calendar-current-month-mark') {
        return [];
      }

      const daysInMonth = new Date(2026, currentMonth, 0).getDate();
      return Array.from({ length: daysInMonth }, (_, index) => ({
        textContent: String(index + 1),
        disabled: false,
        getAttribute: () => null,
        classList: { contains: (className) => className === 'bg-bookly' && index + 1 === activeDay },
        click() {
          selectedDay = index + 1;
        },
      }));
    },
    getSelectedDay() {
      return selectedDay;
    },
  };

  return { calendar, title };
}

test('selects a Pro services-form Jalali date through Bookly as Gregorian', async () => {
  const { adapter } = loadAdapter();
  const { calendar, title } = createCalendar(11);
  const instance = {
    title,
    calendar,
    input: { value: '۱۴۰۵-۰۹-۱۰', disabled: false, setAttribute() {}, removeAttribute() {} },
    status: { textContent: '' },
    lastValue: '۱۴۰۵-۰۹-۰۹',
    selectedDate: '2026-11-30',
    selecting: false,
  };

  await adapter.selectDate(instance);

  assert.equal(calendar.getSelectedDay(), 1);
  assert.equal(instance.selectedDate, '2026-12-01');
  assert.equal(instance.lastValue, '۱۴۰۵-۰۹-۱۰');
  assert.equal(instance.input.disabled, false);
});

test('rejects invalid Jalali dates without selecting a Bookly day', async () => {
  const { adapter } = loadAdapter();
  const { calendar, title } = createCalendar(11);
  const instance = {
    title,
    calendar,
    input: { value: '۱۴۰۵-۱۳-۱۰', disabled: false, setAttribute() {}, removeAttribute() {} },
    status: { textContent: '' },
    lastValue: '۱۴۰۵-۰۹-۰۹',
    selectedDate: '2026-11-30',
    selecting: false,
  };

  await adapter.selectDate(instance);

  assert.equal(calendar.getSelectedDay(), null);
  assert.equal(instance.input.value, '۱۴۰۵-۰۹-۰۹');
  assert.equal(instance.input.disabled, false);
});

test('opens the Jalali picker on the equivalent month of the Bookly calendar', () => {
  const { adapter, shownInputs } = loadAdapter();
  const { calendar, title } = createCalendar(11, 30);
  const input = { value: '' };
  const entry = {
    title,
    calendar,
    input,
  };

  adapter.open(entry);

  assert.equal(input.value, '1405-09-09');
  assert.deepEqual(shownInputs, [input]);
});

test('detects the modern services-form calendar title after a service is selected', () => {
  const { adapter, context } = loadAdapter();
  const title = {};
  let selectedTitle = null;
  context.document.querySelectorAll = (selector) => {
    return selector.includes('.bookly-calendar-middle-button-mark')
      ? [title]
      : [];
  };
  adapter.prepareCalendarTitle = (calendarTitle) => {
    selectedTitle = calendarTitle;
  };

  adapter.process();

  assert.equal(selectedTitle, title);
});

test('localizes the modern services-form title and grid while retaining Gregorian cell dates', () => {
  const { adapter } = loadAdapter();
  const titleLabel = { textContent: 'Nov 2026' };
  const title = {
    dataset: {},
    querySelector: () => titleLabel,
  };
  const cells = Array.from({ length: 35 }, () => {
    const dayLabel = { textContent: '' };
    return {
      dataset: {},
      querySelectorAll: () => [dayLabel],
      dayLabel,
    };
  });
  const weeks = Array.from({ length: 5 }, (_, weekIndex) => ({
    children: cells.slice(weekIndex * 7, weekIndex * 7 + 7),
  }));
  const calendar = {
    querySelector: () => ({
      children: [{}, { children: weeks }],
    }),
  };

  adapter.localizeCalendar({ title, calendar });

  assert.equal(titleLabel.textContent, 'آبان ۱۴۰۵');
  assert.equal(cells[0].dayLabel.textContent, '۹');
  assert.equal(cells[1].dayLabel.textContent, '۱۰');
  assert.equal(cells[22].dayLabel.textContent, '۱');
  assert.deepEqual(cells[1].dataset, {
    wppdGregorianYear: 2026,
    wppdGregorianMonth: 11,
    wppdGregorianDay: 1,
  });
});

test('selects a Jalali-labelled services-form cell using its retained Gregorian date', async () => {
  const { adapter } = loadAdapter();
  const { calendar, title } = createCalendar(11);
  let clicked = false;
  calendar.querySelectorAll = () => [{
    textContent: '۱۰',
    dataset: {
      wppdGregorianYear: '2026',
      wppdGregorianMonth: '11',
      wppdGregorianDay: '1',
    },
    disabled: false,
    getAttribute: () => null,
    click() {
      clicked = true;
    },
  }];

  const selected = await adapter.selectNativeDate(
    { calendar, title },
    { gy: 2026, gm: 11, gd: 1 }
  );

  assert.equal(selected, true);
  assert.equal(clicked, true);
});
