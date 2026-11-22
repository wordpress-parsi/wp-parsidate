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
    path.join(pluginRoot, 'assets/js-src/bookly-ai-assistant.js'),
    'utf8'
  );
  const context = {
    window: {
      WpPdJalaliDate: engineContext.window.WpPdJalaliDate,
      WpPdBooklyAiAssistantSettings: {
        months: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        format: 'j F Y',
      },
      addEventListener() {},
      console,
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
    adapterSource + '\nglobalThis.testAdapter = new WpPdBooklyAiAssistant();',
    context
  );

  return context.testAdapter;
}

test('localizes Gregorian dates in AI chat text while keeping time text', () => {
  const adapter = loadAdapter();
  assert.equal(
    adapter.localizeText('Available on 2026-11-23 at 08:30.'),
    'Available on ۲ آذر ۱۴۰۵ at 08:30.'
  );
});

test('localizes appointment datetime strings', () => {
  const adapter = loadAdapter();
  assert.equal(
    adapter.localizeText('appointment #1 — 2026-11-23 08:30:00'),
    'appointment #1 — ۲ آذر ۱۴۰۵ 08:30'
  );
});

test('leaves invalid dates untouched', () => {
  const adapter = loadAdapter();
  assert.equal(adapter.localizeText('2026-13-45'), '2026-13-45');
});
