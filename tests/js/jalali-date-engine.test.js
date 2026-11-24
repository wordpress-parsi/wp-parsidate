'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const pluginRoot = path.resolve(__dirname, '../..');

/**
 * The engine that ships in the plugin, not the source file: this is what runs
 * in every datepicker, Bookly adapter and the plugin.js date rendering.
 */
function loadEngine() {
  const source = fs.readFileSync(path.join(pluginRoot, 'assets/js-admin/jalali-date.js'), 'utf8');
  const context = { window: {} };
  vm.runInNewContext(source, context);

  return context.window.WpPdJalaliDate;
}

function jalali(engine, gy, gm, gd) {
  const result = engine.toJalaali(gy, gm, gd);

  return { jy: result.jy, jm: result.jm, jd: result.jd };
}

function gregorian(engine, jy, jm, jd) {
  const result = engine.toGregorian(jy, jm, jd);

  return { gy: result.gy, gm: result.gm, gd: result.gd };
}

test('converts Gregorian dates to Jalali at the year boundaries', () => {
  const engine = loadEngine();

  assert.deepEqual(jalali(engine, 2024, 3, 20), { jy: 1403, jm: 1, jd: 1 }, 'nowruz 1403');
  assert.deepEqual(jalali(engine, 2025, 3, 21), { jy: 1404, jm: 1, jd: 1 }, 'nowruz 1404');
  assert.deepEqual(jalali(engine, 2025, 3, 20), { jy: 1403, jm: 12, jd: 30 }, 'last day of leap 1403');
  assert.deepEqual(jalali(engine, 2024, 3, 19), { jy: 1402, jm: 12, jd: 29 }, 'last day of common 1402');
  assert.deepEqual(jalali(engine, 2024, 9, 22), { jy: 1403, jm: 7, jd: 1 }, 'first of Mehr');
  assert.deepEqual(jalali(engine, 1970, 1, 1), { jy: 1348, jm: 10, jd: 11 }, 'unix epoch');
});

test('converts Jalali dates back to Gregorian', () => {
  const engine = loadEngine();

  assert.deepEqual(gregorian(engine, 1403, 1, 1), { gy: 2024, gm: 3, gd: 20 });
  assert.deepEqual(gregorian(engine, 1403, 12, 30), { gy: 2025, gm: 3, gd: 20 });
  assert.deepEqual(gregorian(engine, 1404, 12, 29), { gy: 2026, gm: 3, gd: 20 });
  assert.deepEqual(gregorian(engine, 1348, 10, 11), { gy: 1970, gm: 1, gd: 1 });
});

test('reports Jalali leap years with the 33-year cycle, not a Gregorian rule', () => {
  const engine = loadEngine();

  assert.equal(engine.isLeapJalaaliYear(1399), true);
  assert.equal(engine.isLeapJalaaliYear(1403), true);
  assert.equal(engine.isLeapJalaaliYear(1400), false);
  assert.equal(engine.isLeapJalaaliYear(1402), false);
  assert.equal(engine.isLeapJalaaliYear(1404), false);
});

test('round-trips every sampled day without drift', () => {
  const engine = loadEngine();
  const start = Date.UTC(2000, 0, 1);
  const end = Date.UTC(2035, 11, 31);

  for (let time = start; time <= end; time += 7 * 24 * 60 * 60 * 1000) {
    const date = new Date(time);
    const gy = date.getUTCFullYear();
    const gm = date.getUTCMonth() + 1;
    const gd = date.getUTCDate();

    const back = gregorian(engine, jalali(engine, gy, gm, gd).jy, jalali(engine, gy, gm, gd).jm, jalali(engine, gy, gm, gd).jd);

    assert.deepEqual(back, { gy, gm, gd }, `${gy}-${gm}-${gd} must survive the round trip`);
  }
});

test('rejects years outside the supported range', () => {
  const engine = loadEngine();

  assert.throws(() => engine.toJalaali(5000, 1, 1), /Invalid Jalaali year/);
  assert.throws(() => engine.toGregorian(4000, 1, 1), /Invalid Jalaali year/);
});
