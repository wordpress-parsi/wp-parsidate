/******/ (() => { // webpackBootstrap
/*!******************************************************!*\
  !*** ./assets/js-admin-src/woocommerce-analytics.js ***!
  \******************************************************/
/**
 * WooCommerce Analytics Solar Date - Jalali calendar overlay.
 *
 * - Draws a real single-month Solar (Jalali) calendar over the native
 *   react-dates grid and writes picks back into WooCommerce's Gregorian
 *   range inputs.
 * - Disables future dates (WooCommerce Analytics doesn't allow them).
 * - Rewrites the selected-range summary label at the top into Solar.
 * - Swaps preset ("Last month", ...) request boundaries for the true Solar
 *   ones and rewrites the chart legend/caption ranges.
 * - Localizes the D3 chart X axis (day ticks + month/year band) into Solar
 *   (second class in this file).
 *
 * Structure: WpPdWcAnBase holds every helper both modules share (settings,
 * digits, Jalali math, query parsing, month maps); WpPdWoocommerceAnalyticsNew
 * is the overlay + preset engine; WpPdWcAnChartAxis is the chart localizer.
 * The overlay lives on document.body (outside React's DOM). Clicks are
 * intercepted by a capture-phase document listener registered at page load
 * (before react-dates' own outside-click handler), so selecting a day keeps
 * the popover open.
 */
(function () {
  'use strict';

  if (!window.WpPdJalaliDate) {
    return;
  }

  // ---- shared Solar helpers (DRY base for both modules below) -----------
  // Holds everything the overlay module and the chart-axis module both
  // need: settings, digit conversion, Jalali math, query parsing and the
  // unified Gregorian month-name map (English full/abbreviated + Persian
  // spellings, including the variants from the chart localizer).

  class WpPdWcAnBase {
    constructor() {
      const s = window.WpPdWcAn_SETTINGS || {};
      this.DEBUG = s.debug === true;
      this.LOG_TAG = '[WpPdWcAn]';
      this.usePersianDigits = s.usePersianDigits !== false;
      this.MONTHS = s.monthNames || ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',];
      this.WD = s.weekdayShort || ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

      // RIGHT-TO-LEFT MARK: flags converted text AND keeps the line's base
      // direction RTL so Solar dates render/align correctly.
      this.MARK = '\u200f';

      this.G_MONTHS = {
        january: 1,
        february: 2,
        march: 3,
        april: 4,
        may: 5,
        june: 6,
        july: 7,
        august: 8,
        september: 9,
        october: 10,
        november: 11,
        december: 12,
        jan: 1,
        feb: 2,
        mar: 3,
        apr: 4,
        jun: 6,
        jul: 7,
        aug: 8,
        sep: 9,
        sept: 9,
        oct: 10,
        nov: 11,
        dec: 12,
        'ژانویه': 1,
        'فوریه': 2,
        'مارس': 3,
        'مارچ': 3,
        'آوریل': 4,
        'اوریل': 4,
        'آپریل': 4,
        'مه': 5,
        'می': 5,
        'ژوئن': 6,
        'ژوئیه': 7,
        'ژوییه': 7,
        'جولای': 7,
        'اوت': 8,
        'آگوست': 8,
        'اگوست': 8,
        'سپتامبر': 9,
        'اکتبر': 10,
        'نوامبر': 11,
        'دسامبر': 12,
      };
      this.G_KEYS = Object.keys(this.G_MONTHS).sort(function (a, b) {
        return b.length - a.length;
      });
    }

    escapeRegExp(str) {
      return String(str).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Alternation of every known month name, longest first, regex-escaped.
    monthAlternation() {
      return this.G_KEYS.map((k) => this.escapeRegExp(k)).join('|');
    }

    monthNumber(name) {
      return this.G_MONTHS[String(name).toLowerCase().trim()] || 0;
    }

    jalaliMonthName(jm) {
      return this.MONTHS[jm - 1] || '';
    }

    log() {
      if (this.DEBUG && window.console) {
        window.console.log.apply(window.console, [this.LOG_TAG].concat([].slice.call(arguments)));
      }
    }

    faDigits(value) {
      if (!this.usePersianDigits) {
        return String(value);
      }
      const map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
      return String(value).replace(/[0-9]/g, function (d) {
        return map[parseInt(d, 10)];
      });
    }

    normalizeDigits(str) {
      return String(str)
        .replace(/[۰-۹]/g, function (d) {
          return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d);
        })
        .replace(/[٠-٩]/g, function (d) {
          return '٠١٢٣٤٥٦٧٨٩'.indexOf(d);
        });
    }

    pad2(n) {
      return n < 10 ? '0' + n : '' + n;
    }

    jToG(jy, jm, jd) {
      return window.WpPdJalaliDate.toGregorian(jy, jm, jd);
    }

    gToJ(gy, gm, gd) {
      return window.WpPdJalaliDate.toJalaali(gy, gm, gd);
    }

    jToGa(jy, jm, jd) {
      const g = this.jToG(jy, jm, jd);
      return [g.gy, g.gm, g.gd];
    }

    toJalaliTriple(gy, gm, gd) {
      const j = this.gToJ(gy, gm, gd);
      return [j.jy, j.jm, j.jd];
    }

    tripleFromDate(d) {
      const j = this.gToJ(d.getFullYear(), d.getMonth() + 1, d.getDate());
      return [j.jy, j.jm, j.jd];
    }

    tripleToDate(t) {
      const g = this.jToGa(t[0], t[1], t[2]);
      return new Date(g[0], g[1] - 1, g[2]);
    }

    daysInJMonth(jy, jm) {
      if (jm <= 6) {
        return 31;
      }
      if (jm <= 11) {
        return 30;
      }
      return window.WpPdJalaliDate.isLeapJalaaliYear(jy) ? 30 : 29;
    }

    addDays(d, n) {
      return new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
    }

    daysBetween(a, b) {
      return Math.round((b.getTime() - a.getTime()) / 86400000);
    }

    parseQuery(search) {
      const params = {};
      String(search || '').replace(/^\?/, '').split('&').forEach((pair) => {
        if (!pair) {
          return;
        }
        const i = pair.indexOf('=');
        const k = i === -1 ? pair : pair.slice(0, i);
        const v = i === -1 ? '' : pair.slice(i + 1);
        try {
          params[decodeURIComponent(k)] = decodeURIComponent(v.replace(/\+/g, ' '));
        } catch (e) {
          params[k] = v;
        }
      });
      return params;
    }

    buildQuery(params) {
      const parts = [];
      for (const k in params) {
        if (!Object.prototype.hasOwnProperty.call(params, k)) {
          continue;
        }
        if (params[k] === undefined || params[k] === null) {
          continue;
        }
        parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
      }
      return parts.join('&');
    }

    isAnalyticsScreen(p) {
      if (p.page !== 'wc-admin') {
        return false;
      }
      const path = p.path || '';
      return path.indexOf('analytics') !== -1 || path.indexOf('customers') !== -1;
    }
  }

  class WpPdWoocommerceAnalyticsNew extends WpPdWcAnBase {
    constructor() {
      super();
      const s = window.WpPdWcAn_SETTINGS || {};
      this.DEBUG = s.debug === true;
      this.LOG_TAG = '[WpPdWoocommerceAnalyticsNew]';
      this.ENABLED = s.enableOverlay !== false;
      this.inputDateOrder = s.inputDateOrder || 'MDY';
      this.swapInputs = s.swapInputs === true;
      // Matches "<month> <d> - [<month> ]<d>، <year>" (Latin or Persian digits).
      const ALT = this.monthAlternation();
      this.RANGE_RE = new RegExp('(' + ALT + ')\\s+([0-9۰-۹]{1,2})\\s*[-–]\\s*(?:(' + ALT + ')\\s+)?([0-9۰-۹]{1,2})\\s*[،,]\\s*([0-9۰-۹]{4})', 'g');
      // Matches a single date "<month> <d>، <year>" (report table cells).
      this.SINGLE_RE = new RegExp('(' + ALT + ')\\s+([0-9۰-۹]{1,2})\\s*[،,]\\s*([0-9۰-۹]{4})', 'g');
      // Matches the import-status bar value, e.g. "Jul 22 14:30" or
      // "Jul 23 at 02:00" (dateI18n "M j H:i" / "M j \a\t H:i") - month,
      // day and a 24-hour time, but NO year. The "at" is locale-dependent,
      // so anything between the day and the time is tolerated.
      this.STATUS_RE = new RegExp('^(' + ALT + ')\\s+([0-9۰-۹]{1,2})(?:\\s+.+?\\s+|\\s+)([0-9۰-۹]{1,2}):([0-9۰-۹]{2})$', 'i');
      // True when a text contains any known Gregorian month name - used to
      // tell human-readable date cells (e.g. "3 آگوست 2026") apart from
      // numeric ones (e.g. "2026-08-03").
      this.G_MONTH_RE = new RegExp('(' + ALT + ')', 'i');

      // ---- state ----------------------------------------------------------
      this.cal = null;
      this.view = null;
      this.sel = {start: null, end: null};
      this.phase = 'start';
      this.inited = false;
      this.scheduled = false;
      this.lastScan = 0;

      this.onDocEvent = this.onDocEvent.bind(this);

      // Solar PRESET-range handling is installed immediately - before the
      // app issues its first Analytics request - so the very first render
      // already queries the Solar range.
      this.initNetworkHandling();

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.start());
      } else {
        this.start();
      }
    }

    // ---- helpers (shared date helpers + log() live on WpPdWcAnBase) ----

    firstColumn(jy, jm) {
      const g = this.jToG(jy, jm, 1);
      const dow = new Date(g.gy, g.gm - 1, g.gd).getDay();
      return (dow + 1) % 7;
    }

    cmp(g) {
      return g.gy * 10000 + g.gm * 100 + g.gd;
    }

    todayCmp() {
      const n = new Date();
      return n.getFullYear() * 10000 + (n.getMonth() + 1) * 100 + n.getDate();
    }

    fmtInput(g) {
      switch (this.inputDateOrder) {
        case 'DMY':
          return this.pad2(g.gd) + '/' + this.pad2(g.gm) + '/' + g.gy;
        case 'YMD':
          return g.gy + '/' + this.pad2(g.gm) + '/' + this.pad2(g.gd);
        case 'MDY':
        default:
          return this.pad2(g.gm) + '/' + this.pad2(g.gd) + '/' + g.gy;
      }
    }

    parseInput(raw) {
      const str = this.normalizeDigits(String(raw || '').replace(this.MARK, '')).trim();
      const iso = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
      if (iso) {
        return {gy: +iso[1], gm: +iso[2], gd: +iso[3]};
      }
      const m = str.match(/^(\d{1,4})\/(\d{1,4})\/(\d{1,4})$/);
      if (!m) {
        return null;
      }
      let g;
      switch (this.inputDateOrder) {
        case 'DMY':
          g = {gy: +m[3], gm: +m[2], gd: +m[1]};
          break;
        case 'YMD':
          g = {gy: +m[1], gm: +m[2], gd: +m[3]};
          break;
        case 'MDY':
        default:
          g = {gy: +m[3], gm: +m[1], gd: +m[2]};
      }
      if (g.gm < 1 || g.gm > 12 || g.gd < 1 || g.gd > 31) {
        return null;
      }
      return g;
    }

    setNativeValue(input, value) {
      try {
        const proto = window.HTMLInputElement.prototype;
        const setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
        setter.call(input, value);
      } catch (e) {
        input.value = value;
      }
      input.dispatchEvent(new Event('input', {bubbles: true}));
      input.dispatchEvent(new Event('change', {bubbles: true}));
    }

    // ---- summary label conversion ("سفارشی سازی (ژوئن 22 - ...)") --------

    fmtJRange(j1, j2) {
      if (j1.jy === j2.jy && j1.jm === j2.jm) {
        return (this.faDigits(j1.jd) + ' - ' + this.faDigits(j2.jd) + ' ' + this.MONTHS[j1.jm - 1] + ' ' + this.faDigits(j1.jy));
      }
      if (j1.jy === j2.jy) {
        return (this.faDigits(j1.jd) + ' ' + this.MONTHS[j1.jm - 1] + ' - ' + this.faDigits(j2.jd) + ' ' + this.MONTHS[j2.jm - 1] + ' ' + this.faDigits(j1.jy));
      }
      return (this.faDigits(j1.jd) + ' ' + this.MONTHS[j1.jm - 1] + ' ' + this.faDigits(j1.jy) + ' - ' + this.faDigits(j2.jd) + ' ' + this.MONTHS[j2.jm - 1] + ' ' + this.faDigits(j2.jy));
    }

    convertDatesString(str) {
      if (str.indexOf(this.MARK) !== -1) {
        return null;
      }
      let changed = false;

      // Ranges first (they contain a dash and two "month day" parts).
      let out = str.replace(this.RANGE_RE, (m, mo1, d1, mo2, d2, yr) => {
        let gm1 = this.G_MONTHS[mo1.toLowerCase()];
        if (!gm1) {
          return m;
        }
        const gm2 = mo2 ? this.G_MONTHS[mo2.toLowerCase()] : gm1;
        const y = +this.normalizeDigits(yr);
        const dd1 = +this.normalizeDigits(d1);
        const dd2 = +this.normalizeDigits(d2);
        let y1 = y;
        let y2 = y;
        if (mo2 && gm2 < gm1) {
          y1 = y - 1; // range wraps a year boundary.
        }
        try {
          const j1 = this.gToJ(y1, gm1, dd1);
          const j2 = this.gToJ(y2, gm2, dd2);
          changed = true;
          return this.MARK + this.fmtJRange(j1, j2);
        } catch (e) {
          return m;
        }
      });

      // Then any remaining single dates (table cells: "جولای 22, 2026").
      out = out.replace(this.SINGLE_RE, (m, mo, d, yr) => {
        const gm = this.G_MONTHS[mo.toLowerCase()];
        if (!gm) {
          return m;
        }
        try {
          const j = this.gToJ(+this.normalizeDigits(yr), gm, +this.normalizeDigits(d));
          changed = true;
          return (this.MARK + this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(j.jy));
        } catch (e) {
          return m;
        }
      });

      return changed ? out : null;
    }

    convertTextNode(node) {
      const v = node.nodeValue;
      if (!v || v.indexOf(this.MARK) !== -1 || !/[0-9۰-۹]{4}/.test(v)) {
        return;
      }
      try {
        const host = node.parentElement;
        if (host && host.closest && host.closest('[data-wcasd-skip]')) {
          return;
        }
      } catch (e) {
      }
      const conv = this.convertDatesString(v);
      if (conv === null || conv === v) {
        return;
      }
      node.nodeValue = conv;
      try {
        const el = node.parentElement;
        if (el && el.style && !el.closest('table')) {
          el.style.direction = 'rtl';
          el.style.textAlign = 'right';
        }
      } catch (e) {
      }
    }

    // Convert every date text node within a node (or the node itself).
    convertIn(node) {
      if (!node) {
        return;
      }
      if (node.nodeType === 3) {
        this.convertTextNode(node);
        return;
      }
      if (node.nodeType !== 1) {
        return;
      }
      const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
      const list = [];
      let n;
      while ((n = walker.nextNode())) {
        list.push(n);
      }
      for (let i = 0; i < list.length; i++) {
        this.convertTextNode(list[i]);
      }
    }

    // Throttled full-page sweep - a safety net for anything the per-mutation
    // path might miss.
    convertDates() {
      const now = Date.now();
      if (now - this.lastScan < 400) {
        return;
      }
      this.lastScan = now;
      this.convertIn(document.getElementById('wpbody-content') || document.body);
      this.convertImportStatusValues();
      this.convertReportTableDates();
    }

    // ---- import-status bar ("Last updated" / "Next update") --------------

    // The two .woocommerce-analytics-import-status-bar__value spans show a
    // Gregorian "M j [at] H:i" date with NO year (e.g. "Jul 22 14:30").
    // Pick the most plausible Gregorian year from the label's orientation:
    // "Last updated" -> the most recent occurrence, "Next update" -> the
    // next occurrence.
    importStatusDate(str, next) {
      const m = String(str || '').trim().match(this.STATUS_RE);
      if (!m) {
        return null;
      }
      const gm = this.G_MONTHS[m[1].toLowerCase()];
      if (!gm) {
        return null;
      }
      const gd = +this.normalizeDigits(m[2]);
      const hh = +this.normalizeDigits(m[3]);
      const mm = +this.normalizeDigits(m[4]);
      const n = new Date();
      const cand = new Date(n.getFullYear(), gm - 1, gd);
      const today = new Date(n.getFullYear(), n.getMonth(), n.getDate());
      if (next && cand.getTime() < today.getTime()) {
        cand.setFullYear(n.getFullYear() + 1);
      } else if (!next && cand.getTime() > today.getTime()) {
        cand.setFullYear(n.getFullYear() - 1);
      }
      return {gy: cand.getFullYear(), gm: gm, gd: gd, hh: hh, mm: mm};
    }

    convertImportStatusValue(span) {
      const v = span.textContent || '';
      if (!v || v.indexOf(this.MARK) !== -1) {
        return;
      }
      const item = span.parentElement;
      let label = '';
      if (item) {
        const lb = item.querySelector('.woocommerce-analytics-import-status-bar__label');
        label = lb ? lb.textContent : '';
      }
      const next = /next|بعدی|آتی|بعد/.test(label);
      const d = this.importStatusDate(v, next);
      if (!d) {
        return;
      }
      const j = this.gToJ(d.gy, d.gm, d.gd);
      const text = this.MARK + this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(this.pad2(d.hh) + ':' + this.pad2(d.mm));
      // Mutate the existing text node in place so React's reference to it
      // stays valid and later re-renders still land on the same node.
      const tn = span.firstChild;
      if (tn && tn.nodeType === 3) {
        tn.nodeValue = text;
      } else {
        span.textContent = text;
      }
    }

    convertImportStatusValues() {
      const vals = document.querySelectorAll('.woocommerce-analytics-import-status-bar__value');
      for (let i = 0; i < vals.length; i++) {
        this.convertImportStatusValue(vals[i]);
      }
    }

    // ---- report-table <time> date cells ----------------------------------

    // Mutate a node's first text node in place (nodeValue) so React's
    // reference to it stays valid and later re-renders still land on the
    // same node; replacing the node would freeze future updates.
    setNodeText(node, text) {
      if (!node) {
        return;
      }
      const tn = node.firstChild;
      if (tn && tn.nodeType === 3) {
        tn.nodeValue = text;
      } else {
        node.textContent = text;
      }
    }

    // The Date component used for report-table cells renders
    // <time datetime="2026-08-03 00:00:00"> with a visible
    // aria-hidden span showing the site's dateFormat (day-month-year, e.g.
    // "3 آگوست 2026" on fa_IR sites) and a screen-reader-text span. The
    // visible order is NOT matched by SINGLE_RE, so convert it here from
    // the authoritative datetime attribute.
    convertTimeElement(time) {
      const dt = time.getAttribute('datetime');
      if (!dt) {
        return;
      }
      const m = dt.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) {
        return;
      }
      const gy = +m[1], gm = +m[2], gd = +m[3];
      if (gm < 1 || gm > 12 || gd < 1 || gd > 31) {
        return;
      }
      const vis = time.querySelector('span[aria-hidden="true"]');
      if (!vis) {
        return;
      }
      const cur = vis.textContent || '';
      if (cur.indexOf(this.MARK) !== -1) {
        return; // already converted
      }
      if (!this.G_MONTH_RE.test(cur)) {
        return; // leave numeric "Y-m-d" renders untouched
      }
      let j;
      try {
        j = this.gToJ(gy, gm, gd);
      } catch (e) {
        return;
      }
      const text = this.MARK + this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(j.jy);
      this.setNodeText(vis, text);
    }

    convertTimeElements(root) {
      const times = root && root.querySelectorAll ? root.querySelectorAll('.woocommerce-report-table time[datetime]') : [];
      for (let i = 0; i < times.length; i++) {
        this.convertTimeElement(times[i]);
      }
    }

    convertReportTableDates() {
      this.convertTimeElements(document.getElementById('wpbody-content') || document.body);
    }

    // ---- locate the native picker & its inputs --------------------------

    findDayPicker() {
      const dp = document.querySelector('.DayPicker');
      if (dp && dp.offsetParent !== null) {
        return dp;
      }
      return null;
    }

    orderInputs(list) {
      let start = null;
      let end = null;
      list.forEach(function (i) {
        const hint = ((i.getAttribute('aria-label') || '') + ' ' + (i.placeholder || '')).toLowerCase();
        if (/start|from|شروع|از|ابتدا/.test(hint)) {
          start = i;
        } else if (/end|to|پایان|تا|انتها/.test(hint)) {
          end = i;
        }
      });
      if (!start || !end) {
        start = list[0];
        end = list[1];
      }
      if (this.swapInputs) {
        const t = start;
        start = end;
        end = t;
      }
      return {start: start, end: end};
    }

    findInputs(dp) {
      let node = dp;
      let tries = 0;
      while (node && tries < 8) {
        const all = [].slice.call(node.querySelectorAll('input'));
        const dated = all.filter(function (i) {
          return this.parseInput(i.value);
        }, this);
        if (dated.length >= 2) {
          return this.orderInputs(dated);
        }
        const texts = all.filter(function (i) {
          return i.type === 'text' || !i.type;
        });
        if (texts.length >= 2) {
          return this.orderInputs(texts);
        }
        node = node.parentElement;
        tries++;
      }
      return null;
    }

    // ---- state ----------------------------------------------------------

    today() {
      const n = new Date();
      return this.gToJ(n.getFullYear(), n.getMonth() + 1, n.getDate());
    }

    initFromInputs(dp) {
      const inputs = this.findInputs(dp);
      const startG = inputs && this.parseInput(inputs.start.value);
      const endG = inputs && this.parseInput(inputs.end.value);
      if (startG) {
        this.sel.start = startG;
        this.sel.end = endG || startG;
        const j = this.gToJ(startG.gy, startG.gm, startG.gd);
        this.view = {jy: j.jy, jm: j.jm};
      } else {
        const t = this.today();
        this.view = {jy: t.jy, jm: t.jm};
        this.sel.start = null;
        this.sel.end = null;
      }
      this.phase = 'start';
      this.log('init. inputs found:', !!inputs, '| start:', startG, '| end:', endG);
    }

    // ---- overlay build & render -----------------------------------------

    ensureStyles() {
      if (document.getElementById('wcasd-style')) {
        return;
      }
      const css = '.wcasd-cal{position:fixed;z-index:2147483000;background:#fff;border:1px solid #e0e0e0;' + 'border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.15);padding:12px;direction:rtl;' + 'font-family:inherit;box-sizing:border-box;}' + '.wcasd-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}' + '.wcasd-title{font-weight:700;font-size:15px;color:#1e1e1e;}' + '.wcasd-nav{border:1px solid #dcdcde;background:#fff;border-radius:6px;width:34px;height:34px;' + 'cursor:pointer;font-size:16px;line-height:1;color:#1e1e1e;}' + '.wcasd-nav:hover{background:#f0f0f1;}' + '.wcasd-wd,.wcasd-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;direction:rtl;}' + '.wcasd-wd span{text-align:center;font-size:12px;color:#787c82;padding:4px 0;}' + '.wcasd-day{height:38px;border:0;background:transparent;border-radius:6px;cursor:pointer;' + 'font-size:14px;color:#1e1e1e;}' + '.wcasd-day:hover{background:#f0f0f1;}' + '.wcasd-day.wcasd-range{background:#e8edff;}' + '.wcasd-day.wcasd-sel{background:#3858e9;color:#fff;font-weight:700;}' + '.wcasd-day.wcasd-empty{visibility:hidden;cursor:default;}' + '.wcasd-day:disabled{color:#c3c4c7;cursor:default;background:transparent;}' + '.wcasd-day:disabled:hover{background:transparent;}' + '.wcasd-foot{margin-top:8px;font-size:12px;color:#50575e;text-align:center;}';
      const st = document.createElement('style');
      st.id = 'wcasd-style';
      st.textContent = css;
      document.head.appendChild(st);
    }

    buildOverlay() {
      this.ensureStyles();
      this.cal = document.createElement('div');
      this.cal.className = 'wcasd-cal';
      document.body.appendChild(this.cal);
    }

    position(dp) {
      if (!this.cal) {
        return;
      }
      const r = dp.getBoundingClientRect();
      this.cal.style.top = Math.round(r.top) + 'px';
      this.cal.style.left = Math.round(r.left) + 'px';
      this.cal.style.width = Math.round(Math.max(r.width, 280)) + 'px';
    }

    moveMonth(delta) {
      let m = this.view.jm + delta;
      let y = this.view.jy;
      if (m < 1) {
        m = 12;
        y--;
      } else if (m > 12) {
        m = 1;
        y++;
      }
      this.view = {jy: y, jm: m};
      this.render();
    }

    writeInputs() {
      const dp = this.findDayPicker();
      if (!dp) {
        return;
      }
      const inputs = this.findInputs(dp);
      if (!inputs || !this.sel.start || !this.sel.end) {
        this.log('writeInputs skipped. inputs:', !!inputs);
        return;
      }
      let a = this.sel.start;
      let b = this.sel.end;
      if (this.cmp(a) > this.cmp(b)) {
        const t = a;
        a = b;
        b = t;
      }
      this.setNativeValue(inputs.start, this.fmtInput(a));
      this.setNativeValue(inputs.end, this.fmtInput(b));
      this.log('wrote inputs ->', this.fmtInput(a), '..', this.fmtInput(b));
    }

    onDay(jy, jm, jd) {
      const g = this.jToG(jy, jm, jd);
      if (this.cmp(g) > this.todayCmp()) {
        return; // guard: future dates are not selectable.
      }
      if (this.phase === 'start' || !this.sel.start) {
        this.sel.start = g;
        this.sel.end = g;
        this.phase = 'end';
      } else {
        if (this.cmp(g) < this.cmp(this.sel.start)) {
          this.sel.end = this.sel.start;
          this.sel.start = g;
        } else {
          this.sel.end = g;
        }
        this.phase = 'start';
      }
      this.writeInputs();
      this.render();
    }

    render() {
      if (!this.cal || !this.view) {
        return;
      }
      this.cal.textContent = '';
      const maxCmp = this.todayCmp();

      const head = document.createElement('div');
      head.className = 'wcasd-head';

      const prev = document.createElement('button');
      prev.className = 'wcasd-nav';
      prev.type = 'button';
      prev.setAttribute('data-nav', 'prev');
      prev.textContent = '→';

      const title = document.createElement('div');
      title.className = 'wcasd-title';
      title.textContent = this.MONTHS[this.view.jm - 1] + ' ' + this.faDigits(this.view.jy);

      const next = document.createElement('button');
      next.className = 'wcasd-nav';
      next.type = 'button';
      next.setAttribute('data-nav', 'next');
      next.textContent = '←';

      head.appendChild(prev);
      head.appendChild(title);
      head.appendChild(next);
      this.cal.appendChild(head);

      const wd = document.createElement('div');
      wd.className = 'wcasd-wd';
      for (let w = 0; w < 7; w++) {
        const sp = document.createElement('span');
        sp.textContent = this.WD[w];
        wd.appendChild(sp);
      }
      this.cal.appendChild(wd);

      const grid = document.createElement('div');
      grid.className = 'wcasd-grid';
      let lead = this.firstColumn(this.view.jy, this.view.jm);
      let total = this.daysInJMonth(this.view.jy, this.view.jm);
      let i;
      for (i = 0; i < lead; i++) {
        const e = document.createElement('button');
        e.className = 'wcasd-day wcasd-empty';
        e.type = 'button';
        e.disabled = true;
        grid.appendChild(e);
      }
      for (i = 1; i <= total; i++) {
        const g = this.jToG(this.view.jy, this.view.jm, i);
        const gc = this.cmp(g);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wcasd-day';
        btn.setAttribute('data-jy', this.view.jy);
        btn.setAttribute('data-jm', this.view.jm);
        btn.setAttribute('data-jday', i);
        btn.textContent = this.faDigits(i);
        if (gc > maxCmp) {
          btn.disabled = true; // future date.
        } else if (this.sel.start && this.sel.end) {
          let lo = Math.min(this.cmp(this.sel.start), this.cmp(this.sel.end));
          let hi = Math.max(this.cmp(this.sel.start), this.cmp(this.sel.end));
          if (gc === this.cmp(this.sel.start) || gc === this.cmp(this.sel.end)) {
            btn.className += ' wcasd-sel';
          } else if (gc > lo && gc < hi) {
            btn.className += ' wcasd-range';
          }
        }
        grid.appendChild(btn);
      }
      this.cal.appendChild(grid);

      if (this.sel.start && this.sel.end) {
        let js = this.gToJ(this.sel.start.gy, this.sel.start.gm, this.sel.start.gd);
        let je = this.gToJ(this.sel.end.gy, this.sel.end.gm, this.sel.end.gd);
        let foot = document.createElement('div');
        foot.className = 'wcasd-foot';
        foot.textContent = 'از ' + this.faDigits(js.jy + '/' + this.pad2(js.jm) + '/' + this.pad2(js.jd)) + ' تا ' + this.faDigits(je.jy + '/' + this.pad2(je.jm) + '/' + this.pad2(je.jd));
        this.cal.appendChild(foot);
      }
    }

    // ---- global click interception --------------------------------------

    onDocEvent(e) {
      if (!this.cal || !this.cal.contains(e.target)) {
        return;
      }
      if (e.type === 'mousedown' || e.type === 'pointerdown') {
        e.preventDefault();
      }
      e.stopImmediatePropagation();
      e.stopPropagation();

      if (e.type !== 'click') {
        return;
      }
      let nav = e.target.closest('[data-nav]');
      if (nav) {
        this.moveMonth(nav.getAttribute('data-nav') === 'next' ? 1 : -1);
        return;
      }
      let day = e.target.closest('[data-jday]');
      if (day && !day.disabled) {
        this.onDay(parseInt(day.getAttribute('data-jy'), 10), parseInt(day.getAttribute('data-jm'), 10), parseInt(day.getAttribute('data-jday'), 10));
      }
    }

    // ---- Solar PRESET ranges via apiFetch / network middleware ----------
    // The URL keeps the real preset (period=last_month). WooCommerce turns
    // it into GREGORIAN boundaries on each Analytics REST request; we
    // recognise those and swap them for the true SOLAR boundaries - the
    // primary range AND the comparison range - so the data moves onto the
    // Solar calendar without touching the URL. The preset stays selected;
    // captions are rewritten to the Solar preset range.

    PRESET_UNITS() {
      return {
        today: ['day', 'todate'],
        yesterday: ['day', 'last'],
        week: ['week', 'todate'],
        last_week: ['week', 'last'],
        month: ['month', 'todate'],
        last_month: ['month', 'last'],
        quarter: ['quarter', 'todate'],
        last_quarter: ['quarter', 'last'],
        year: ['year', 'todate'],
        last_year: ['year', 'last'],
      };
    }

    presetUnit(key) {
      return this.PRESET_UNITS()[key];
    }

    // Query parsing, building, screen check and date shifting live on
    // WpPdWcAnBase (parseQuery/buildQuery/isAnalyticsScreen/addDays/
    // daysBetween).

    isoDay(d) {
      return d.getFullYear() + '-' + this.pad2(d.getMonth() + 1) + '-' + this.pad2(d.getDate());
    }

    // Gregorian mirror of WooCommerce's own preset math (Sunday-start
    // weeks), used only to RECOGNISE which range an outgoing request
    // represents.
    gStartOf(d, unit) {
      const y = d.getFullYear(), m = d.getMonth(), dd = d.getDate();
      switch (unit) {
        case 'week':
          return this.addDays(new Date(y, m, dd), -(new Date(y, m, dd).getDay()));
        case 'month':
          return new Date(y, m, 1);
        case 'quarter':
          return new Date(y, Math.floor(m / 3) * 3, 1);
        case 'year':
          return new Date(y, 0, 1);
        default:
          return new Date(y, m, dd);
      }
    }

    gEndOf(d, unit) {
      const s = this.gStartOf(d, unit);
      switch (unit) {
        case 'week':
          return this.addDays(s, 6);
        case 'month':
          return new Date(s.getFullYear(), s.getMonth() + 1, 0);
        case 'quarter':
          return new Date(s.getFullYear(), s.getMonth() + 3, 0);
        case 'year':
          return new Date(s.getFullYear(), 11, 31);
        default:
          return s;
      }
    }

    gShift(d, n, unit) {
      const y = d.getFullYear(), m = d.getMonth(), dd = d.getDate();
      switch (unit) {
        case 'day':
          return this.addDays(d, n);
        case 'week':
          return this.addDays(d, n * 7);
        case 'month':
          return new Date(y, m + n, dd);
        case 'quarter':
          return new Date(y, m + n * 3, dd);
        case 'year':
          return new Date(y + n, m, dd);
        default:
          return d;
      }
    }

    gregorianPresetRanges(key, compare, now) {
      const spec = this.presetUnit(key);
      if (!spec) {
        return null;
      }
      const unit = spec[0], running = spec[1] === 'todate';
      let pStart, pEnd, sStart, sEnd, span;
      if (running) {
        pStart = this.gStartOf(now, unit);
        pEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        span = this.daysBetween(pStart, pEnd);
        if (compare === 'previous_period') {
          sStart = this.gShift(pStart, -1, unit);
          sEnd = this.gShift(pEnd, -1, unit);
        } else {
          sStart = this.gShift(pStart, -1, 'year');
          sEnd = this.addDays(sStart, span);
        }
      } else {
        pStart = this.gShift(this.gStartOf(now, unit), -1, unit);
        pEnd = this.gEndOf(pStart, unit);
        span = this.daysBetween(pStart, pEnd);
        if (compare === 'previous_period') {
          if (unit === 'year') {
            sStart = this.gShift(this.gStartOf(now, unit), -2, unit);
            sEnd = this.gEndOf(sStart, unit);
          } else {
            sEnd = this.addDays(pStart, -1);
            sStart = this.addDays(sEnd, -span);
          }
        } else if (unit === 'week') {
          sStart = this.gShift(pStart, -1, 'year');
          sEnd = this.gShift(pEnd, -1, 'year');
        } else {
          sStart = this.gShift(pStart, -1, 'year');
          sEnd = this.gEndOf(sStart, unit);
        }
      }
      return {primaryStart: pStart, primaryEnd: pEnd, secondaryStart: sStart, secondaryEnd: sEnd};
    }

    // Solar equivalents (dates as [jy, jm, jd] triples) - jToGa,
    // tripleFromDate and tripleToDate live on WpPdWcAnBase.

    tripleToIso(t) {
      const g = this.jToGa(t[0], t[1], t[2]);
      return g[0] + '-' + this.pad2(g[1]) + '-' + this.pad2(g[2]);
    }

    shiftJMonths(t, delta) {
      const total = t[0] * 12 + (t[1] - 1) + delta;
      const jy = Math.floor(total / 12);
      const jm = total - jy * 12 + 1;
      return [jy, jm, Math.min(t[2], this.daysInJMonth(jy, jm))];
    }

    jShift(t, n, unit) {
      switch (unit) {
        case 'day':
          return this.tripleFromDate(this.addDays(this.tripleToDate(t), n));
        case 'week':
          return this.tripleFromDate(this.addDays(this.tripleToDate(t), n * 7));
        case 'month':
          return this.shiftJMonths(t, n);
        case 'quarter':
          return this.shiftJMonths(t, n * 3);
        case 'year':
          return this.shiftJMonths(t, n * 12);
        default:
          return t;
      }
    }

    jEndOf(t, unit) {
      const jy = t[0], jm = t[1];
      switch (unit) {
        case 'week':
          return this.tripleFromDate(this.addDays(this.tripleToDate(t), 6));
        case 'month':
          return [jy, jm, this.daysInJMonth(jy, jm)];
        case 'quarter':
          return [jy, jm + 2, this.daysInJMonth(jy, jm + 2)];
        case 'year':
          return [jy, 12, this.daysInJMonth(jy, 12)];
        default:
          return t;
      }
    }

    jalaliPresetBounds(key, now) {
      const today = this.tripleFromDate(now);
      const jy = today[0], jm = today[1];
      let qs, pqs, pqm, pm, py, dow, start, end;
      switch (key) {
        case 'month':
          return [[jy, jm, 1], today];
        case 'last_month':
          pm = jm - 1;
          py = jy;
          if (pm < 1) {
            pm = 12;
            py--;
          }
          return [[py, pm, 1], [py, pm, this.daysInJMonth(py, pm)]];
        case 'quarter':
          qs = Math.floor((jm - 1) / 3) * 3 + 1;
          return [[jy, qs, 1], today];
        case 'last_quarter':
          qs = Math.floor((jm - 1) / 3) * 3 + 1;
          pqs = qs - 3;
          py = jy;
          if (pqs < 1) {
            pqs += 12;
            py--;
          }
          pqm = pqs + 2;
          return [[py, pqs, 1], [py, pqm, this.daysInJMonth(py, pqm)]];
        case 'year':
          return [[jy, 1, 1], today];
        case 'last_year':
          return [[jy - 1, 1, 1], [jy - 1, 12, this.daysInJMonth(jy - 1, 12)]];
        case 'week':
          dow = (now.getDay() + 1) % 7;
          start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - dow);
          return [this.tripleFromDate(start), today];
        case 'last_week':
          dow = (now.getDay() + 1) % 7;
          start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - dow - 7);
          end = new Date(now.getFullYear(), now.getMonth(), now.getDate() - dow - 1);
          return [this.tripleFromDate(start), this.tripleFromDate(end)];
        default:
          return null;
      }
    }

    jalaliPresetRanges(key, compare, now) {
      const spec = this.presetUnit(key);
      const primary = this.jalaliPresetBounds(key, now);
      if (!spec || !primary) {
        return null;
      }
      const unit = spec[0], running = spec[1] === 'todate';
      const span = this.daysBetween(this.tripleToDate(primary[0]), this.tripleToDate(primary[1]));
      let sStart, sEnd;
      if (running) {
        sStart = compare === 'previous_period' ? this.jShift(primary[0], -1, unit) : this.jShift(primary[0], -1, 'year');
        sEnd = this.tripleFromDate(this.addDays(this.tripleToDate(sStart), span));
      } else if (compare === 'previous_period') {
        sEnd = this.tripleFromDate(this.addDays(this.tripleToDate(primary[0]), -1));
        sStart = this.tripleFromDate(this.addDays(this.tripleToDate(sEnd), -span));
      } else {
        sStart = this.jShift(primary[0], -1, 'year');
        sEnd = unit === 'week' ? this.tripleFromDate(this.addDays(this.tripleToDate(sStart), span)) : this.jEndOf(sStart, unit);
      }
      return {primary: primary, secondary: [sStart, sEnd]};
    }

    // --- The middleware: swap request boundaries Gregorian -> Solar ------

    swapDatePart(value, iso) {
      return iso + String(value).slice(10);
    }

    rewriteAnalyticsPath(path) {
      if (!path || path.indexOf('wc-analytics/') === -1) {
        return path;
      }
      const split = path.indexOf('?');
      if (split === -1) {
        return path;
      }
      const params = this.parseQuery(path.slice(split + 1));
      if (!params.after || !params.before) {
        return path;
      }
      const urlParams = this.parseQuery(window.location.search);
      if (!this.isAnalyticsScreen(urlParams)) {
        return path;
      }
      const preset = urlParams.period || 'month';
      if (preset === 'custom' || !this.presetUnit(preset)) {
        return path;
      }
      const compare = urlParams.compare || 'previous_year';
      const now = new Date();
      const greg = this.gregorianPresetRanges(preset, compare, now);
      const jal = this.jalaliPresetRanges(preset, compare, now);
      if (!greg || !jal) {
        return path;
      }
      // Remember the effective Solar ranges actually sent to the REST API
      // (primary + comparison + requested interval) so the chart-axis
      // module can correlate plotted points with real dates.
      try {
        window.WCASD.effective = {
          primary: [this.tripleToDate(jal.primary[0]), this.tripleToDate(jal.primary[1])],
          secondary: [this.tripleToDate(jal.secondary[0]), this.tripleToDate(jal.secondary[1])],
          interval: params.interval ? String(params.interval) : '',
        };
      } catch (e) {
      }
      const after = String(params.after).slice(0, 10);
      const before = String(params.before).slice(0, 10);
      let target = null;
      if (after === this.isoDay(greg.primaryStart) && before === this.isoDay(greg.primaryEnd)) {
        target = jal.primary;
      } else if (after === this.isoDay(greg.secondaryStart) && before === this.isoDay(greg.secondaryEnd)) {
        target = jal.secondary;
      }
      if (!target) {
        return path;
      }
      params.after = this.swapDatePart(params.after, this.tripleToIso(target[0]));
      params.before = this.swapDatePart(params.before, this.tripleToIso(target[1]));
      this.log('preset', preset, 'swap ->', params.after, params.before);
      return path.slice(0, split) + '?' + this.buildQuery(params);
    }

    installFetchMiddleware() {
      const apiFetch = window.wp && window.wp.apiFetch;
      if (!apiFetch || !apiFetch.use) {
        return false;
      }
      if (apiFetch.wcasdPatched) {
        return true;
      }
      apiFetch.use((options, next) => {
        let interval = '';
        try {
          const p = options && (typeof options.path === 'string'
            ? options.path
            : (typeof options.url === 'string' ? options.url : ''));
          if (p) {
            const np = this.rewriteAnalyticsPath(p);
            if (options && typeof options.path === 'string') {
              options.path = np;
            } else if (options) {
              options.url = np;
            }
            const qi = np.indexOf('?');
            if (qi !== -1) {
              interval = this.parseQuery(np.slice(qi + 1)).interval || '';
            }
          }
        } catch (e) {
        }
        const res = next(options);
        try {
          const rp = (options && (options.path || options.url)) || '';
          if (typeof rp === 'string' && rp.indexOf('wc-analytics/') !== -1 &&
            res && typeof res.then === 'function') {
            res.then((response) => {
              try {
                this.rememberBuckets(response, interval);
              } catch (e) {
              }
              return response;
            }, () => {
            });
          }
        } catch (e) {
        }
        return res;
      });
      apiFetch.wcasdPatched = true;
      return true;
    }

    ensureFetchMiddleware() {
      if (this.installFetchMiddleware()) {
        return;
      }
      try {
        window.wp = window.wp || {};
        if (!window.wp.wcasdApiFetchHooked && !Object.getOwnPropertyDescriptor(window.wp, 'apiFetch')) {
          let stored;
          Object.defineProperty(window.wp, 'apiFetch', {
            configurable: true,
            enumerable: true,
            get: () => stored,
            set: (v) => {
              stored = v;
              try {
                this.installFetchMiddleware();
              } catch (e) {
              }
            },
          });
          window.wp.wcasdApiFetchHooked = true;
        }
      } catch (e) {
      }
      let tries = 0;
      const timer = setInterval(() => {
        tries++;
        let done = false;
        try {
          done = this.installFetchMiddleware();
        } catch (e) {
        }
        if (done || tries > 200) {
          clearInterval(timer);
        }
      }, 25);
    }

    // --- Chart data: expose what the Shamsi chart-axis module needs ------

    // The chart-axis module (bottom of this file) reads the effective Solar
    // ranges, the request interval and the actual bucket dates returned by
    // the Analytics REST responses from window.WCASD.
    exposeChartData() {
      window.WCASD = window.WCASD || {};
      window.WCASD.jalaliPresetRanges = (key, compare, now) => this.jalaliPresetRanges(key, compare, now);
      window.WCASD.buckets = window.WCASD.buckets || [];
      window.WCASD.effective = window.WCASD.effective || {primary: null, secondary: null, interval: ''};
    }

    // Capture the real bucket boundaries of an Analytics response. The D3
    // charts label their X axis with bare day numbers that carry no date,
    // so the axis localizer correlates tick positions with these dates.
    rememberBuckets(response, interval) {
      if (!window.WCASD || !response || !response.intervals || !response.intervals.length) {
        return;
      }
      const dates = [];
      for (let i = 0; i < response.intervals.length; i++) {
        const row = response.intervals[i];
        const raw = row && (row.date_start || row.date_start_gmt);
        if (!raw) {
          return;
        }
        const parts = String(raw).slice(0, 10).split('-');
        if (parts.length !== 3) {
          return;
        }
        dates.push(new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)));
      }
      window.WCASD.buckets.unshift({dates: dates, interval: interval || ''});
      if (window.WCASD.buckets.length > 12) {
        window.WCASD.buckets.length = 12;
      }
    }

    initNetworkHandling() {
      try {
        this.exposeChartData();
        // NOTE: the global fetch()/XMLHttpRequest patch (patchNetworkLayer)
        // is intentionally NOT installed here - it wrapped every request
        // containing "wc-analytics/" and made WooCommerce's API calls fail
        // with fetch_error, breaking the analytics charts. The apiFetch
        // middleware below handles the Analytics requests this feature needs.
        this.ensureFetchMiddleware();
        this.watchHistory();
      } catch (e) {
      }
    }

    // --- Captions: rewrite the bracketed range to the Solar preset range --

    tripleObj(t) {
      return {jy: t[0], jm: t[1], jd: t[2]};
    }

    replaceBracket(span, replacement) {
      if (!span || !replacement) {
        return false;
      }
      const text = span.textContent || '';
      const open = text.indexOf('(');
      const close = text.lastIndexOf(')');
      if (open === -1 || close <= open + 1) {
        return false;
      }
      if (!/[0-9۰-۹]/.test(text.slice(open + 1, close))) {
        return false;
      }
      const next = text.slice(0, open + 1) + replacement + text.slice(close);
      if (span.textContent !== next) {
        span.textContent = next;
      }
      return true;
    }

    restorePresetLabel() {
      const params = this.parseQuery(window.location.search);
      if (!this.isAnalyticsScreen(params)) {
        return;
      }
      const preset = params.period || 'month';
      if (preset === 'custom' || !this.presetUnit(preset)) {
        return;
      }
      const ranges = this.jalaliPresetRanges(preset, params.compare || 'previous_year', new Date());
      if (!ranges) {
        return;
      }
      const texts = [
        this.MARK + this.fmtJRange(this.tripleObj(ranges.primary[0]), this.tripleObj(ranges.primary[1])),
        this.MARK + this.fmtJRange(this.tripleObj(ranges.secondary[0]), this.tripleObj(ranges.secondary[1])),
      ];
      const groups = document.querySelectorAll('.woocommerce-dropdown-button__labels');
      Array.prototype.forEach.call(groups, (group) => {
        const spans = group.querySelectorAll('span');
        const limit = Math.min(spans.length, texts.length);
        let touched = false;
        for (let i = 0; i < limit; i++) {
          if (this.replaceBracket(spans[i], texts[i])) {
            touched = true;
          }
        }
        if (touched) {
          group.setAttribute('data-wcasd-skip', '1');
        }
      });
      const lists = document.querySelectorAll('.woocommerce-legend__list');
      Array.prototype.forEach.call(lists, (list) => {
        const items = list.querySelectorAll('.woocommerce-legend__item');
        Array.prototype.forEach.call(items, (item, index) => {
          const id = item.getAttribute('id') || '';
          let which = index;
          if (id.indexOf('__secondary') !== -1) {
            which = 1;
          } else if (id.indexOf('__primary') !== -1) {
            which = 0;
          }
          if (which > 1 || !texts[which]) {
            return;
          }
          const title = item.querySelector('.woocommerce-legend__item-title');
          if (this.replaceBracket(title, texts[which])) {
            title.setAttribute('data-wcasd-skip', '1');
          }
        });
      });
    }

    watchHistory() {
      if (!window.history || !window.history.pushState || window.history.wcasdWatched) {
        return;
      }
      const restore = () => this.restorePresetLabel();
      ['pushState', 'replaceState'].forEach((m) => {
        const orig = window.history[m];
        window.history[m] = function () {
          const r = orig.apply(this, arguments);
          setTimeout(restore, 0);
          return r;
        };
      });
      window.addEventListener('popstate', restore);
      window.history.wcasdWatched = true;
    }

    // ---- main loop ------------------------------------------------------

    tick() {
      this.scheduled = false;

      // Convert the summary label AND report-table date cells, whether or
      // not the picker is open.
      try {
        this.convertDates();
      } catch (err) {
      }

      // Keep preset captions on the Solar preset range.
      try {
        this.restorePresetLabel();
      } catch (err) {
      }

      if (!this.ENABLED) {
        return;
      }
      const dp = this.findDayPicker();
      if (!dp) {
        if (this.cal) {
          this.cal.parentNode.removeChild(this.cal);
          this.cal = null;
        }
        this.inited = false;
        return;
      }
      if (dp.style.visibility !== 'hidden') {
        dp.style.visibility = 'hidden';
      }
      if (!this.cal) {
        this.buildOverlay();
      }
      if (!this.inited) {
        this.initFromInputs(dp);
        this.inited = true;
        this.render();
      }
      this.position(dp);
    }

    schedule() {
      if (this.scheduled) {
        return;
      }
      this.scheduled = true;
      window.requestAnimationFrame(() => this.tick());
    }

    start() {
      this.log('active. order =', this.inputDateOrder, '| enabled =', this.ENABLED);

      ['mousedown', 'pointerdown', 'mouseup', 'click', 'touchstart'].forEach(function (ev) {
        document.addEventListener(ev, this.onDocEvent, true);
      }, this);

      this.schedule();
      // Convert dates the instant they appear (chart tooltips, new rows,
      // re-rendered labels) - runs before paint, so no Gregorian flash.
      new MutationObserver((mutations) => {
        try {
          this.restorePresetLabel();
        } catch (e) {
        }
        for (let i = 0; i < mutations.length; i++) {
          const mu = mutations[i];
          if (mu.type === 'characterData') {
            this.convertTextNode(mu.target);
          } else if (mu.type === 'childList') {
            for (let j = 0; j < mu.addedNodes.length; j++) {
              this.convertIn(mu.addedNodes[j]);
            }
          }
        }
        this.convertImportStatusValues();
        this.convertReportTableDates();
        this.schedule();
      }).observe(document.body, {
        childList: true, subtree: true, characterData: true,
      });
      window.addEventListener('scroll', () => this.schedule(), true);
      window.addEventListener('resize', () => this.schedule());
    }
  }

  new WpPdWoocommerceAnalyticsNew();

/**
 * Shamsi chart-axis localizer.
 *
 * The Analytics D3 charts label their X axis with bare Gregorian day numbers
 * and a month/year "band"; the tick itself doesn't carry its date. So we
 * correlate each tick's X position with the real date of the nearest plotted
 * point (from point aria-labels, or the line-chart focus grid), then relabel
 * the ticks and the month band in Solar. Bucket dates come from the REST
 * response (captured into window.WCASD.buckets by the main script above),
 * with fallbacks to the effective Solar range and the URL preset range.
 *
 * Depends on window.WpPdJalaliDate, window.WpPdWcAn_SETTINGS, the shared
 * WpPdWcAnBase above, and the data the main class exposes on window.WCASD.
 */
  class WpPdWcAnChartAxis extends WpPdWcAnBase {
    constructor() {
      super();
      this.LOG_TAG = '[WpPdWcAnChartAxis]';
      this.MONTH_PATTERN = '(' + this.monthAlternation() + ')';
      this.DIGIT_CLASS = '[0-9\u06F0-\u06F9\u0660-\u0669]';
      this.BUCKET_INTERVALS = ['day', 'week', 'week_sunday', 'month', 'quarter', 'year', 'hour'];
      this.timer = null;

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.start());
      } else {
        this.start();
      }
    }

    // ---- data the main script exposes -------------------------------------

    getBuckets() {
      return (window.WCASD && window.WCASD.buckets) || [];
    }

    getEffective() {
      return (window.WCASD && window.WCASD.effective) ||
        {primary: null, secondary: null, interval: ''};
    }

    presetRanges(period, compare, now) {
      return window.WCASD && window.WCASD.jalaliPresetRanges
        ? window.WCASD.jalaliPresetRanges(period, compare, now)
        : null;
    }

    // ---- date/label parsing -----------------------------------------------

    parseDateFromLabel(label) {
      if (!label) {
        return null;
      }
      const text = this.normalizeDigits(String(label));
      const re = new RegExp(this.MONTH_PATTERN + '\\s+(\\d{1,2})\\s*,?\\s*(\\d{4})', 'i');
      const m = text.match(re);
      if (m) {
        const mo = this.monthNumber(m[1]);
        if (mo) {
          return [parseInt(m[3], 10), mo, parseInt(m[2], 10)];
        }
      }
      const iso = text.match(/(\d{4})-(\d{2})-(\d{2})/);
      if (iso) {
        return [parseInt(iso[1], 10), parseInt(iso[2], 10), parseInt(iso[3], 10)];
      }
      return null;
    }

    parseRangeFromLabel(label) {
      if (!label) {
        return null;
      }
      const text = this.normalizeDigits(String(label));
      const two = text.match(new RegExp(
        this.MONTH_PATTERN + '\\s+(\\d{1,2})\\s*[-\u2013\u2014]\\s*' +
        this.MONTH_PATTERN + '\\s+(\\d{1,2})\\s*[,\u060C]?\\s*(\\d{4})', 'i'));
      if (two) {
        const sM = this.monthNumber(two[1]), eM = this.monthNumber(two[3]);
        if (sM && eM) {
          const eY = parseInt(two[5], 10);
          const sY = sM > eM ? eY - 1 : eY;
          return [
            new Date(sY, sM - 1, parseInt(two[2], 10)),
            new Date(eY, eM - 1, parseInt(two[4], 10)),
          ];
        }
      }
      const one = text.match(new RegExp(
        this.MONTH_PATTERN + '\\s+(\\d{1,2})\\s*[-\u2013\u2014]\\s*(\\d{1,2})\\s*[,\u060C]?\\s*(\\d{4})', 'i'));
      if (one) {
        const mo = this.monthNumber(one[1]);
        if (mo) {
          const y = parseInt(one[4], 10);
          return [
            new Date(y, mo - 1, parseInt(one[2], 10)),
            new Date(y, mo - 1, parseInt(one[3], 10)),
          ];
        }
      }
      const single = this.parseDateFromLabel(text);
      if (single) {
        const day = new Date(single[0], single[1] - 1, single[2]);
        return [day, day];
      }
      return null;
    }

    // ---- bucketing ---------------------------------------------------------

    intervalFromDates(dates) {
      if (!dates || dates.length < 2) {
        return 'day';
      }
      const gap = this.daysBetween(dates[0], dates[1]);
      if (gap <= 0) {
        return 'hour';
      }
      if (gap === 1) {
        return 'day';
      }
      if (gap <= 7) {
        return 'week';
      }
      if (gap <= 31) {
        return 'month';
      }
      if (gap <= 120) {
        return 'quarter';
      }
      return 'year';
    }

    monthStep(interval) {
      if (interval === 'quarter') {
        return 3;
      }
      if (interval === 'year') {
        return 12;
      }
      return 1;
    }

    bucketStarts(start, end, interval) {
      const out = [];
      const total = this.daysBetween(start, end) + 1;
      let i;
      if (total < 1 || total > 4000) {
        return out;
      }
      if (interval === 'hour') {
        for (i = 0; i < total * 24; i++) {
          out.push(this.addDays(start, Math.floor(i / 24)));
        }
        return out;
      }
      if (interval === 'day') {
        for (i = 0; i < total; i++) {
          out.push(this.addDays(start, i));
        }
        return out;
      }
    if (interval === 'week' || interval === 'week_sunday') {
      const firstDay = interval === 'week' ? 1 : 0;
      const cursor = this.addDays(start, 0);
      out.push(cursor);
      const offset = (7 + firstDay - cursor.getDay()) % 7;
      let next = this.addDays(cursor, offset || 7);
      while (next <= end) {
        out.push(next);
        next = this.addDays(next, 7);
      }
      return out;
    }
    if (interval === 'month' || interval === 'quarter' || interval === 'year') {
      const step = this.monthStep(interval);
      out.push(this.addDays(start, 0));
      let year = start.getFullYear();
      let month = start.getMonth();
      if (interval === 'year') {
        year += 1;
        month = 0;
      } else {
        month = month - (month % step) + step;
      }
      let boundary = new Date(year, month, 1);
      while (boundary <= end) {
        out.push(boundary);
        boundary = new Date(boundary.getFullYear(), boundary.getMonth() + step, 1);
      }
      return out;
    }
    return out;
  }

    // ---- geometry & point resolution --------------------------------------

    chartPointXs(svg) {
      const xs = [];
      Array.prototype.forEach.call(
        svg.querySelectorAll('g.focusspaces g.focus g.focus-grid line'),
        function (line) {
          const x = parseFloat(line.getAttribute('x1'));
          if (!isNaN(x)) {
            xs.push(x);
          }
        }
      );
      return xs;
    }

    isoToJalaliTriple(value) {
      const m = (value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) {
        return null;
      }
      return this.toJalaliTriple(parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10));
    }

    currentPrimaryRange() {
      const params = this.parseQuery(window.location.search);
      if (!this.isAnalyticsScreen(params)) {
        return null;
      }
      const period = params.period || 'month';
      if (period === 'custom') {
        const after = this.isoToJalaliTriple(params.after);
        const before = this.isoToJalaliTriple(params.before);
        return after && before ? [after, before] : null;
      }
      const ranges = this.presetRanges(period, params.compare || 'previous_year', new Date());
      return ranges ? ranges.primary : null;
    }

    resolvePlottedDates(svg) {
      const xs = this.chartPointXs(svg);
      if (xs.length < 2) {
        this.log('axis: only', xs.length, 'focus-grid point(s), need 2+');
        return null;
      }

      const buckets = this.getBuckets();
      for (let c = 0; c < buckets.length; c++) {
        if (buckets[c] && buckets[c].dates && buckets[c].dates.length === xs.length) {
          this.log('axis: matched', xs.length, 'buckets');
          return {
            xs: xs,
            dates: buckets[c].dates,
            interval: buckets[c].interval || this.intervalFromDates(buckets[c].dates),
          };
        }
      }

      const eff = this.getEffective();
      const ranges = [];
      if (eff.primary) {
        ranges.push(eff.primary);
      }

      const series = svg.querySelectorAll('g.lines g.line-g[aria-label], g.line-g[aria-label]');
      for (let si = series.length - 1; si >= 0; si--) {
        const parsed = this.parseRangeFromLabel(series[si].getAttribute('aria-label'));
        if (parsed) {
          ranges.push(parsed);
          break;
        }
      }

      const preset = this.currentPrimaryRange();
      if (preset) {
        ranges.push([this.tripleToDate(preset[0]), this.tripleToDate(preset[1])]);
      }

      if (!ranges.length) {
        this.log('axis: no date range available (no buckets, no aria range, no preset)');
        return null;
      }

      const intervals = [];
      const urlInterval = this.parseQuery(window.location.search).interval;
      if (eff.interval) {
        intervals.push(eff.interval);
      }
      if (urlInterval) {
        intervals.push(urlInterval);
      }
      for (let b = 0; b < this.BUCKET_INTERVALS.length; b++) {
        if (intervals.indexOf(this.BUCKET_INTERVALS[b]) === -1) {
          intervals.push(this.BUCKET_INTERVALS[b]);
        }
      }
      if (intervals.indexOf('week_sunday') === -1) {
        intervals.push('week_sunday');
      }

      for (let r = 0; r < ranges.length; r++) {
        for (let k = 0; k < intervals.length; k++) {
          const dates = this.bucketStarts(ranges[r][0], ranges[r][1], intervals[k]);
          if (dates.length === xs.length) {
            this.log('axis: range+interval match', intervals[k], dates.length, 'points');
            return {xs: xs, dates: dates, interval: intervals[k]};
          }
        }
      }
      this.log('axis: no range produced', xs.length, 'buckets');
      return null;
    }

    pointsFromLineRange(svg) {
      const resolved = this.resolvePlottedDates(svg);
      if (!resolved) {
        return [];
      }
      const points = [];
      for (let i = 0; i < resolved.dates.length; i++) {
        const day = resolved.dates[i];
        points.push({x: resolved.xs[i], date: [day.getFullYear(), day.getMonth() + 1, day.getDate()]});
      }
      points.wcasdInterval = resolved.interval;
      return points;
    }

    ancestorOffsetX(el, stop) {
      let offset = 0;
      let node = el.parentNode;
      while (node && node !== stop && node.getAttribute) {
        const transform = node.getAttribute('transform');
        if (transform) {
          const m = transform.match(/translate\(\s*(-?[\d.]+)/);
          if (m) {
            offset += parseFloat(m[1]);
          }
        }
        node = node.parentNode;
      }
      return offset;
    }

    shapeCenterX(shape, stop) {
      const cx = parseFloat(shape.getAttribute('cx'));
      if (!isNaN(cx)) {
        return cx + this.ancestorOffsetX(shape, stop);
      }
      const rx = parseFloat(shape.getAttribute('x'));
      if (isNaN(rx)) {
        return NaN;
      }
      const group = shape.parentNode;
      if (group && group.classList && group.classList.contains('bargroup')) {
        const focus = group.querySelector('rect.barfocus');
        const focusWidth = focus ? parseFloat(focus.getAttribute('width')) : NaN;
        if (!isNaN(focusWidth)) {
          return this.ancestorOffsetX(shape, stop) + focusWidth / 2;
        }
      }
      const rw = parseFloat(shape.getAttribute('width'));
      return rx + (isNaN(rw) ? 0 : rw / 2) + this.ancestorOffsetX(shape, stop);
    }

    compareDateTriples(a, b) {
      return (a[0] - b[0]) || (a[1] - b[1]) || (a[2] - b[2]);
    }

    tickPosition(tick) {
      const transform = tick.getAttribute('transform') || '';
      const m = transform.match(/translate\(\s*(-?[\d.]+)/);
      return m ? parseFloat(m[1]) : null;
    }

    nearestDate(points, x, tolerance) {
      let best = null;
      let bestDistance = tolerance > 0 ? tolerance : 4;
      for (let i = 0; i < points.length; i++) {
        const distance = Math.abs(points[i].x - x);
        if (distance <= bestDistance) {
          bestDistance = distance;
          best = points[i].date;
        }
      }
      return best;
    }

    axisLooksLikeDates(ticks) {
      let checked = 0;
      let dateLike = 0;
      Array.prototype.forEach.call(ticks, (tick) => {
        const text = tick.querySelector('text');
        const value = text ? this.normalizeDigits((text.textContent || '').trim()) : '';
        if (!value) {
          return;
        }
        checked++;
        if (/^\d{1,4}([\/\-:.]\d{1,2})*$/.test(value) ||
          new RegExp('^' + this.MONTH_PATTERN, 'i').test(value)) {
          dateLike++;
        }
      });
      return checked === 0 || dateLike >= Math.ceil(checked / 2);
    }

    // ---- axis rewriting -----------------------------------------------------

    // Last resort for the month/year band: a tick like "Aug 2026" carries
    // its own year, so it can be converted without any geometry - show the
    // Solar month in effect on that Gregorian month's first day. Bare month
    // names ("Sep") carry no year and are left alone.
    localizeMonthBandText(monthTick) {
      const monthText = monthTick && monthTick.querySelector('text');
      if (!monthText) {
        return false;
      }
      const cur = monthText.textContent || '';
      if (!cur || cur.indexOf(this.MARK) !== -1) {
        return false;
      }
      const m = this.normalizeDigits(cur).match(new RegExp('^' + this.MONTH_PATTERN + '\\s+(\\d{4})\\s*$', 'i'));
      if (!m) {
        return false;
      }
      const gm = this.monthNumber(m[1]);
      const gy = parseInt(m[2], 10);
      if (!gm || !gy) {
        return false;
      }
      let j;
      try {
        j = this.toJalaliTriple(gy, gm, 1);
      } catch (e) {
        return false;
      }
      const label = this.jalaliMonthName(j[1]) + ' ' + this.faDigits(j[0]);
      if (monthText.textContent !== label) {
        monthText.textContent = label;
      }
      return true;
    }

    localizeMonthBandTexts(monthTicks) {
      let done = 0;
      Array.prototype.forEach.call(monthTicks || [], (tick) => {
        if (this.localizeMonthBandText(tick)) {
          done++;
        }
      });
      if (done) {
        this.log('axis: converted', done, 'band label(s) from text');
      }
    }

    applyAxisTicks(group, marks, labelFn) {
      const ticks = Array.prototype.slice.call(group.querySelectorAll('g.tick'));
      if (!ticks.length) {
        return;
      }
      while (ticks.length < marks.length) {
        const clone = ticks[0].cloneNode(true);
        group.appendChild(clone);
        ticks.push(clone);
      }
      while (ticks.length > marks.length) {
        const extra = ticks.pop();
        if (extra.parentNode) {
          extra.parentNode.removeChild(extra);
        }
      }
      for (let i = 0; i < marks.length; i++) {
        const transform = 'translate(' + marks[i].x + ',0)';
        if (ticks[i].getAttribute('transform') !== transform) {
          ticks[i].setAttribute('transform', transform);
        }
        const text = ticks[i].querySelector('text');
        const label = labelFn(marks[i], i);
        if (text && text.textContent !== label) {
          text.textContent = label;
        }
      }
    }

    localizeMonthBandAxis(svg) {
      const dayAxis = svg.querySelector('g.axis:not(.axis-month):not(.y-axis)');
      if (!dayAxis) {
        return false;
      }
      const dayTicks = dayAxis.querySelectorAll('g.tick');
      if (dayTicks.length < 2) {
        return false;
      }
      const digits = new RegExp(this.DIGIT_CLASS);
      for (let t = 0; t < dayTicks.length; t++) {
        const current = (dayTicks[t].textContent || '').trim();
        if (!current || digits.test(current)) {
          return false;
        }
      }
      const resolved = this.resolvePlottedDates(svg);
      if (!resolved) {
        this.log('axis: month-band mode but no plotted dates');
        return false;
      }
      const marks = [];
      let previousKey = null;
      const daily = (resolved.interval === 'day' || resolved.interval === 'hour');
      for (let i = 0; i < resolved.dates.length; i++) {
        const day = resolved.dates[i];
        const j = this.toJalaliTriple(day.getFullYear(), day.getMonth() + 1, day.getDate());
        const key = j[0] + '-' + j[1];
        const isBoundary = daily ? (j[2] === 1) : (key !== previousKey);
        if (i === 0 || isBoundary) {
          marks.push({x: resolved.xs[i], j: j});
        }
        previousKey = key;
      }
      if (!marks.length) {
        return false;
      }
      this.applyAxisTicks(dayAxis, marks, (mark) => this.jalaliMonthName(mark.j[1]));
      const monthAxis = svg.querySelector('g.axis.axis-month');
      if (monthAxis) {
        let lastYear = null;
        this.applyAxisTicks(monthAxis, marks, (mark) => {
          if (mark.j[0] === lastYear) {
            return '';
          }
          lastYear = mark.j[0];
          return this.faDigits(mark.j[0]);
        });
      }
      const pipes = svg.querySelector('g.pipes');
      if (pipes) {
        this.applyAxisTicks(pipes, marks, function () {
          return '';
        });
      }
      return true;
    }

    localizeChartAxes(root) {
      const scope = root && root.querySelectorAll ? root : document;
      const containers = scope.querySelectorAll('.d3-chart__container, .woocommerce-chart');
      if (!containers.length) {
        this.log('axis: no chart containers found');
        return;
      }
      Array.prototype.forEach.call(containers, (container) => {
        const svg = container.querySelector('svg');
        if (!svg) {
          return;
        }
        try {
          if (this.localizeMonthBandAxis(svg)) {
            return;
          }
        } catch (e) {
          this.log('axis: month-band error', e && e.message);
        }

        const dayAxis = svg.querySelector('g.axis:not(.axis-month):not(.y-axis)');
        const monthAxis = svg.querySelector('g.axis.axis-month');
        if (!dayAxis) {
          this.log('axis: no day axis');
          return;
        }
        const dayTicks = dayAxis.querySelectorAll('g.tick');
        const monthTicks = monthAxis ? monthAxis.querySelectorAll('g.tick') : [];
        if (!dayTicks.length) {
          this.log('axis: day axis has no ticks');
          return;
        }
        if (!this.axisLooksLikeDates(dayTicks)) {
          this.log('axis: day ticks do not look like dates');
          return;
        }

        const space = dayAxis.parentNode;
        let points = [];
        const byX = {};
        Array.prototype.forEach.call(
          svg.querySelectorAll('circle[aria-label], rect[aria-label]'),
          (shape) => {
            const date = this.parseDateFromLabel(shape.getAttribute('aria-label'));
            if (!date) {
              return;
            }
            const x = this.shapeCenterX(shape, space);
            if (isNaN(x)) {
              return;
            }
            const key = Math.round(x);
            const current = byX[key];
            if (!current || this.compareDateTriples(date, current.date) > 0) {
              byX[key] = {x: x, date: date};
            }
          }
        );
        Object.keys(byX).forEach((key) => {
          points.push(byX[key]);
        });
        points.sort(function (a, b) {
          return a.x - b.x;
        });

        if (!points.length) {
          try {
            points = this.pointsFromLineRange(svg);
          } catch (e) {
            this.log('axis: line-range fallback error', e && e.message);
          }
        }
        if (!points.length) {
          this.log('axis: no plotted points (no aria shapes, no focus grid)');
          // Geometry is unavailable, but the month band can still be
          // converted when a tick carries its own year ("Aug 2026").
          this.localizeMonthBandTexts(monthTicks);
          return;
        }

        let previousMonthKey = null;
        const spacing = points.length > 1 ? Math.abs(points[1].x - points[0].x) : 8;
        const tolerance = Math.max(4, spacing / 2 + 1);
        const interval = points.wcasdInterval || 'day';
        const monthMode = (interval === 'month' || interval === 'quarter' || interval === 'year');

        Array.prototype.forEach.call(dayTicks, (tick, index) => {
          const x = this.tickPosition(tick);
          if (null === x) {
            return;
          }
          const monthTick = monthTicks[index];
          const date = this.nearestDate(points, x, tolerance);
          if (!date) {
            // No correlated date for this tick (e.g. coordinate frames do
            // not line up) - still try the self-contained band label.
            if (monthTick) {
              this.localizeMonthBandText(monthTick);
            }
            return;
          }
          const j = this.toJalaliTriple(date[0], date[1], date[2]);
          const text = tick.querySelector('text');
          if (text) {
            const dayLabel = monthMode ? this.jalaliMonthName(j[1]) : this.faDigits(j[2]);
            if (text.textContent !== dayLabel) {
              text.textContent = dayLabel;
            }
          }
          const monthText = monthTick.querySelector('text');
          if (!monthText) {
            return;
          }
          const key = monthMode ? String(j[0]) : (j[0] + '-' + j[1]);
          let label = '';
          if (key !== previousMonthKey) {
            label = monthMode
              ? this.faDigits(j[0])
              : this.jalaliMonthName(j[1]) + ' ' + this.faDigits(j[0]);
            previousMonthKey = key;
          }
          if (monthText.textContent !== label) {
            monthText.textContent = label;
          }
        });
      });
    }

    // ---- runner -------------------------------------------------------------

    run() {
      clearTimeout(this.timer);
      this.timer = setTimeout(() => {
        try {
          this.localizeChartAxes(document);
        } catch (e) {
        }
      }, 60);
    }

    start() {
      this.run();
      new MutationObserver(() => this.run()).observe(document.body, {childList: true, subtree: true});
      document.addEventListener('mouseover', (e) => {
        if (e.target && e.target.closest &&
          e.target.closest('.d3-chart__container, .woocommerce-chart')) {
          this.run();
        }
      }, true);
      window.addEventListener('resize', () => this.run());
    }
  }

  new WpPdWcAnChartAxis();
})();

/******/ })()
;