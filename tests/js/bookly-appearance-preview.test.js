'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

function loadAdapter(today = '2026-11-23') {
  const engineSource = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const engineContext = { window: {} };
  vm.runInNewContext(engineSource, engineContext);

  const adapterSource = fs.readFileSync(
    path.join(pluginRoot, 'assets/js-src/bookly-appearance-preview.js'),
    'utf8'
  );
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      WpPdBooklyAppearancePreviewSettings: { today },
      addEventListener() {},
    },
    document: {
      readyState: 'loading',
      addEventListener() {},
      body: {},
      querySelectorAll() { return []; },
    },
    console,
  };
  vm.createContext(context);
  vm.runInContext(
    adapterSource + '\nglobalThis.testAdapter = new WpPdBooklyAppearancePreview();',
    context
  );
  return context.testAdapter;
}

function calendarWithDays(count, firstDay = 1) {
  const spans = [];
  for (let i = 0; i < count; i++) {
    const textNode = { nodeType: 3, nodeValue: String(firstDay + i), nextSibling: null };
    spans.push({ firstChild: textNode, textContent: textNode.nodeValue, nodeValue: textNode.nodeValue });
  }
  const buttons = spans.map((span) => ({
    querySelector(selector) { return selector === 'span' ? span : null; },
  }));
  return {
    buttons,
    spans,
    querySelectorAll(selector) { return selector === '.bookly-calendar-button' ? buttons : []; },
  };
}

test('computes the Gregorian Monday-start from the first of the month', () => {
  const adapter = loadAdapter('2026-01-01');
  assert.deepEqual(JSON.parse(JSON.stringify(adapter.firstGregorianDay())), { year: 2025, month: 12, day: 29 });
});

test('computes the Gregorian Monday-start when the first is a Monday', () => {
  const adapter = loadAdapter('2026-12-05');
  assert.deepEqual(JSON.parse(JSON.stringify(adapter.firstGregorianDay())), { year: 2026, month: 11, day: 30 });
});

test('localizes each preview day number using its reconstructed Gregorian date', () => {
  const adapter = loadAdapter('2026-11-23');
  const calendar = calendarWithDays(3, 26); // Oct 26, 27, 28 (Monday start for Nov 2026)
  adapter.localizeCalendar(calendar);

  // Oct 26 2026 = ۴ آبان ۱۴۰۵, Oct 27 = ۵ آبان, Oct 28 = ۶ آبان
  assert.equal(calendar.spans[0].firstChild.nodeValue, '۴');
  assert.equal(calendar.spans[1].firstChild.nodeValue, '۵');
  assert.equal(calendar.spans[2].firstChild.nodeValue, '۶');
});

test('mutates the existing text node rather than replacing it', () => {
  const adapter = loadAdapter('2026-11-23');
  const calendar = calendarWithDays(1, 26);
  const originalNode = calendar.spans[0].firstChild;
  adapter.localizeCalendar(calendar);
  assert.equal(calendar.spans[0].firstChild, originalNode);
});
