/******/ (() => { // webpackBootstrap
/*!********************************************!*\
  !*** ./assets/js-admin-src/jalali-date.js ***!
  \********************************************/
/**
 * Jalaali (Solar Hijri) <-> Gregorian conversion engine.
 *
 * Standard, well-tested algorithm (jalaali-js by Behrang Noruzi Niya,
 * based on the work of Kazimierz M. Borkowski). MIT licensed. The math is
 * self-contained so the plugin has no external runtime dependency.
 *
 * Exposes a single class with static methods:
 *   WpPdJalaliDate.toJalaali(gy, gm, gd)     -> { jy, jm, jd }
 *   WpPdJalaliDate.toGregorian(jy, jm, jd)   -> { gy, gm, gd }
 *   WpPdJalaliDate.isLeapJalaaliYear(jy)     -> boolean
 *
 * All internal helpers (_div, _mod, _jalCal, _g2d, _d2g, _j2d, _d2j) live on
 * the class as static methods.
 *
 * Back-compat alias: window.jalaali === window.WpPdJalaliDate.
 */
(function () {
  'use strict';

  class WpPdJalaliDate {
    static _div(a, b) {
      return ~~(a / b);
    }

    static _mod(a, b) {
      return a - ~~(a / b) * b;
    }

    static _jalCal(jy) {
      var breaks = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,];
      var bl = breaks.length;
      var gy = jy + 621;
      var leapJ = -14;
      var jp = breaks[0];
      var jm;
      var jump = 0;
      var leap;
      var leapG;
      var march;
      var n;
      var i;

      if (jy < jp || jy >= breaks[bl - 1]) {
        throw new Error('Invalid Jalaali year ' + jy);
      }

      for (i = 1; i < bl; i += 1) {
        jm = breaks[i];
        jump = jm - jp;
        if (jy < jm) {
          break;
        }
        leapJ = leapJ + this._div(jump, 33) * 8 + this._div(this._mod(jump, 33), 4);
        jp = jm;
      }
      n = jy - jp;

      leapJ = leapJ + this._div(n, 33) * 8 + this._div(this._mod(n, 33) + 3, 4);
      if (this._mod(jump, 33) === 4 && jump - n === 4) {
        leapJ += 1;
      }

      leapG = this._div(gy, 4) - this._div((this._div(gy, 100) + 1) * 3, 4) - 150;
      march = 20 + leapJ - leapG;

      if (jump - n < 6) {
        n = n - jump + this._div(jump + 4, 33) * 33;
      }
      leap = this._mod(this._mod(n + 1, 33) - 1, 4);
      if (leap === -1) {
        leap = 4;
      }

      return {leap: leap, gy: gy, march: march};
    }

    static _g2d(gy, gm, gd) {
      var d = this._div((gy + this._div(gm - 8, 6) + 100100) * 1461, 4) + this._div(153 * this._mod(gm + 9, 12) + 2, 5) + gd - 34840408;
      d = d - this._div(this._div(gy + 100100 + this._div(gm - 8, 6), 100) * 3, 4) + 752;
      return d;
    }

    static _d2g(jdn) {
      var j;
      var i;
      var gd;
      var gm;
      var gy;
      j = 4 * jdn + 139361631;
      j = j + this._div(this._div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
      i = this._div(this._mod(j, 1461), 4) * 5 + 308;
      gd = this._div(this._mod(i, 153), 5) + 1;
      gm = this._mod(this._div(i, 153), 12) + 1;
      gy = this._div(j, 1461) - 100100 + this._div(8 - gm, 6);
      return {gy: gy, gm: gm, gd: gd};
    }

    static _j2d(jy, jm, jd) {
      var r = this._jalCal(jy);
      return (this._g2d(r.gy, 3, r.march) + (jm - 1) * 31 - this._div(jm, 7) * (jm - 7) + jd - 1);
    }

    static _d2j(jdn) {
      var gy = this._d2g(jdn).gy;
      var jy = gy - 621;
      var r = this._jalCal(jy);
      var jdn1f = this._g2d(gy, 3, r.march);
      var jd;
      var jm;
      var k;

      k = jdn - jdn1f;
      if (k >= 0) {
        if (k <= 185) {
          jm = 1 + this._div(k, 31);
          jd = this._mod(k, 31) + 1;
          return {jy: jy, jm: jm, jd: jd};
        }
        k -= 186;
      } else {
        jy -= 1;
        k += 179;
        if (r.leap === 1) {
          k += 1;
        }
      }
      jm = 7 + this._div(k, 30);
      jd = this._mod(k, 30) + 1;
      return {jy: jy, jm: jm, jd: jd};
    }

    static toJalaali(gy, gm, gd) {
      return this._d2j(this._g2d(gy, gm, gd));
    }

    static toGregorian(jy, jm, jd) {
      return this._d2g(this._j2d(jy, jm, jd));
    }

    static isLeapJalaaliYear(jy) {
      return this._jalCal(jy).leap === 0;
    }
  }

  window.WpPdJalaliDate = WpPdJalaliDate;
})();

/******/ })()
;