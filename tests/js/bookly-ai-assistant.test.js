'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

function loadAdapter(assistant = null) {
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
      BooklyAiAssistant: assistant,
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

test('forces an ISO date format on the AI showForm config before mount', () => {
  let mountedConfig;
  const assistant = {
    showForm(id, config) {
      mountedConfig = config;
    },
  };
  const adapter = loadAdapter(assistant);

  adapter.patchShowForm();
  assistant.showForm('widget', { moment_format_date: 'D MMMM YYYY', mode: 'embedded' });

  assert.equal(mountedConfig.moment_format_date, 'YYYY-MM-DD');
  assert.equal(mountedConfig.mode, 'embedded');

  // Idempotent: boot/patch twice must not wrap the already patched function.
  adapter.patchShowForm();
  assistant.showForm('widget', { moment_format_date: 'D MMMM YYYY' });
  assert.equal(mountedConfig.moment_format_date, 'YYYY-MM-DD');
});

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
