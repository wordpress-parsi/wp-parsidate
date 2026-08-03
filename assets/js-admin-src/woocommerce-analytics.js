/**
 * WooCommerce Analytics Solar Date - Jalali calendar overlay.
 *
 * - Draws a real single-month Solar (Jalali) calendar over the native
 *   react-dates grid and writes picks back into WooCommerce's Gregorian
 *   range inputs.
 * - Disables future dates (WooCommerce Analytics doesn't allow them).
 * - Rewrites the selected-range summary label at the top into Solar.
 *
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

  class WpPdWoocommerceAnalytics {
    constructor() {
      var s = window.WpPdWcAn_SETTINGS || {};
      this.DEBUG = s.debug === true;
      this.ENABLED = s.enableOverlay !== false;
      this.usePersianDigits = s.usePersianDigits !== false;
      this.inputDateOrder = s.inputDateOrder || 'MDY';
      this.swapInputs = s.swapInputs === true;
      this.MONTHS = s.monthNames || [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
      ];
      this.WD = s.weekdayShort || ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

      // RIGHT-TO-LEFT MARK: flags converted text AND keeps the line's base
      // direction RTL so Solar dates render/align correctly.
      this.MARK = '\u200f';

      // Gregorian month names (English full/abbreviated + Persian
      // spellings) -> number. The abbreviations cover dateI18n's "M"
      // format ("Jul 22 ...") used by the import-status bar.
      this.G_MONTHS = {
        january: 1, february: 2, march: 3, april: 4, may: 5, june: 6,
        july: 7, august: 8, september: 9, october: 10, november: 11, december: 12,
        jan: 1, feb: 2, mar: 3, apr: 4, jun: 6, jul: 7, aug: 8,
        sep: 9, oct: 10, nov: 11, dec: 12,
        'ژانویه': 1, 'فوریه': 2, 'مارس': 3, 'آوریل': 4, 'مه': 5, 'می': 5,
        'ژوئن': 6, 'ژوئیه': 7, 'جولای': 7, 'اوت': 8, 'آگوست': 8,
        'سپتامبر': 9, 'اکتبر': 10, 'نوامبر': 11, 'دسامبر': 12,
      };
      this.G_KEYS = Object.keys(this.G_MONTHS).sort(function (a, b) {
        return b.length - a.length;
      });
      // Matches "<month> <d> - [<month> ]<d>، <year>" (Latin or Persian digits).
      this.RANGE_RE = new RegExp(
        '(' + this.G_KEYS.join('|') + ')\\s+([0-9۰-۹]{1,2})\\s*[-–]\\s*(?:(' + this.G_KEYS.join('|') + ')\\s+)?([0-9۰-۹]{1,2})\\s*[،,]\\s*([0-9۰-۹]{4})',
        'g'
      );
      // Matches a single date "<month> <d>، <year>" (report table cells).
      this.SINGLE_RE = new RegExp(
        '(' + this.G_KEYS.join('|') + ')\\s+([0-9۰-۹]{1,2})\\s*[،,]\\s*([0-9۰-۹]{4})',
        'g'
      );
      // Matches the import-status bar value, e.g. "Jul 22 14:30" or
      // "Jul 23 at 02:00" (dateI18n "M j H:i" / "M j \a\t H:i") - month,
      // day and a 24-hour time, but NO year. The "at" is locale-dependent,
      // so anything between the day and the time is tolerated.
      this.STATUS_RE = new RegExp(
        '^(' + this.G_KEYS.join('|') + ')\\s+([0-9۰-۹]{1,2})(?:\\s+.+?\\s+|\\s+)([0-9۰-۹]{1,2}):([0-9۰-۹]{2})$',
        'i'
      );
      // True when a text contains any known Gregorian month name - used to
      // tell human-readable date cells (e.g. "3 آگوست 2026") apart from
      // numeric ones (e.g. "2026-08-03").
      this.G_MONTH_RE = new RegExp('(' + this.G_KEYS.join('|') + ')', 'i');

      // ---- state ----------------------------------------------------------
      this.cal = null;
      this.view = null;
      this.sel = {start: null, end: null};
      this.phase = 'start';
      this.inited = false;
      this.scheduled = false;
      this.lastScan = 0;

      this.onDocEvent = this.onDocEvent.bind(this);

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => this.start());
      } else {
        this.start();
      }
    }

    // ---- helpers --------------------------------------------------------

    log() {
      if (this.DEBUG && window.console) {
        window.console.log.apply(
          window.console,
          ['[WPParsiDateWoocommerceAnalytics]'].concat([].slice.call(arguments))
        );
      }
    }

    faDigits(value) {
      if (!this.usePersianDigits) {
        return String(value);
      }
      var map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
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

    daysInJMonth(jy, jm) {
      if (jm <= 6) {
        return 31;
      }
      if (jm <= 11) {
        return 30;
      }
      return window.WpPdJalaliDate.isLeapJalaaliYear(jy) ? 30 : 29;
    }

    firstColumn(jy, jm) {
      var g = this.jToG(jy, jm, 1);
      var dow = new Date(g.gy, g.gm - 1, g.gd).getDay();
      return (dow + 1) % 7;
    }

    cmp(g) {
      return g.gy * 10000 + g.gm * 100 + g.gd;
    }

    todayCmp() {
      var n = new Date();
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
      var str = this.normalizeDigits(String(raw || '').replace(this.MARK, '')).trim();
      var iso = str.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
      if (iso) {
        return {gy: +iso[1], gm: +iso[2], gd: +iso[3]};
      }
      var m = str.match(/^(\d{1,4})\/(\d{1,4})\/(\d{1,4})$/);
      if (!m) {
        return null;
      }
      var g;
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
        var proto = window.HTMLInputElement.prototype;
        var setter = Object.getOwnPropertyDescriptor(proto, 'value').set;
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
      var changed = false;

      // Ranges first (they contain a dash and two "month day" parts).
      var out = str.replace(this.RANGE_RE, (m, mo1, d1, mo2, d2, yr) => {
        var gm1 = this.G_MONTHS[mo1.toLowerCase()];
        if (!gm1) {
          return m;
        }
        var gm2 = mo2 ? this.G_MONTHS[mo2.toLowerCase()] : gm1;
        var y = +this.normalizeDigits(yr);
        var dd1 = +this.normalizeDigits(d1);
        var dd2 = +this.normalizeDigits(d2);
        var y1 = y;
        var y2 = y;
        if (mo2 && gm2 < gm1) {
          y1 = y - 1; // range wraps a year boundary.
        }
        try {
          var j1 = this.gToJ(y1, gm1, dd1);
          var j2 = this.gToJ(y2, gm2, dd2);
          changed = true;
          return this.MARK + this.fmtJRange(j1, j2);
        } catch (e) {
          return m;
        }
      });

      // Then any remaining single dates (table cells: "جولای 22, 2026").
      out = out.replace(this.SINGLE_RE, (m, mo, d, yr) => {
        var gm = this.G_MONTHS[mo.toLowerCase()];
        if (!gm) {
          return m;
        }
        try {
          var j = this.gToJ(+this.normalizeDigits(yr), gm, +this.normalizeDigits(d));
          changed = true;
          return (this.MARK + this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(j.jy));
        } catch (e) {
          return m;
        }
      });

      return changed ? out : null;
    }

    convertTextNode(node) {
      var v = node.nodeValue;
      if (!v || v.indexOf(this.MARK) !== -1 || !/[0-9۰-۹]{4}/.test(v)) {
        return;
      }
      var conv = this.convertDatesString(v);
      if (conv === null || conv === v) {
        return;
      }
      node.nodeValue = conv;
      try {
        var el = node.parentElement;
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
      var walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null, false);
      var list = [];
      var n;
      while ((n = walker.nextNode())) {
        list.push(n);
      }
      for (var i = 0; i < list.length; i++) {
        this.convertTextNode(list[i]);
      }
    }

    // Throttled full-page sweep - a safety net for anything the per-mutation
    // path might miss.
    convertDates() {
      var now = Date.now();
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
      var m = String(str || '').trim().match(this.STATUS_RE);
      if (!m) {
        return null;
      }
      var gm = this.G_MONTHS[m[1].toLowerCase()];
      if (!gm) {
        return null;
      }
      var gd = +this.normalizeDigits(m[2]);
      var hh = +this.normalizeDigits(m[3]);
      var mm = +this.normalizeDigits(m[4]);
      var n = new Date();
      var cand = new Date(n.getFullYear(), gm - 1, gd);
      var today = new Date(n.getFullYear(), n.getMonth(), n.getDate());
      if (next && cand.getTime() < today.getTime()) {
        cand.setFullYear(n.getFullYear() + 1);
      } else if (!next && cand.getTime() > today.getTime()) {
        cand.setFullYear(n.getFullYear() - 1);
      }
      return {gy: cand.getFullYear(), gm: gm, gd: gd, hh: hh, mm: mm};
    }

    convertImportStatusValue(span) {
      var v = span.textContent || '';
      if (!v || v.indexOf(this.MARK) !== -1) {
        return;
      }
      var item = span.parentElement;
      var label = '';
      if (item) {
        var lb = item.querySelector('.woocommerce-analytics-import-status-bar__label');
        label = lb ? lb.textContent : '';
      }
      var next = /next|بعدی|آتی|بعد/.test(label);
      var d = this.importStatusDate(v, next);
      if (!d) {
        return;
      }
      var j = this.gToJ(d.gy, d.gm, d.gd);
      var text = this.MARK + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(j.jd) +
        ' ' + this.faDigits(this.pad2(d.hh) + ':' + this.pad2(d.mm));
      // Mutate the existing text node in place so React's reference to it
      // stays valid and later re-renders still land on the same node.
      var tn = span.firstChild;
      if (tn && tn.nodeType === 3) {
        tn.nodeValue = text;
      } else {
        span.textContent = text;
      }
    }

    convertImportStatusValues() {
      var vals = document.querySelectorAll('.woocommerce-analytics-import-status-bar__value');
      for (var i = 0; i < vals.length; i++) {
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
      var tn = node.firstChild;
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
      var dt = time.getAttribute('datetime');
      if (!dt) {
        return;
      }
      var m = dt.match(/^(\d{4})-(\d{2})-(\d{2})/);
      if (!m) {
        return;
      }
      var gy = +m[1], gm = +m[2], gd = +m[3];
      if (gm < 1 || gm > 12 || gd < 1 || gd > 31) {
        return;
      }
      var vis = time.querySelector('span[aria-hidden="true"]');
      if (!vis) {
        return;
      }
      var cur = vis.textContent || '';
      if (cur.indexOf(this.MARK) !== -1) {
        return; // already converted
      }
      if (!this.G_MONTH_RE.test(cur)) {
        return; // leave numeric "Y-m-d" renders untouched
      }
      var j;
      try {
        j = this.gToJ(gy, gm, gd);
      } catch (e) {
        return;
      }
      var text = this.MARK + this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' + this.faDigits(j.jy);
      this.setNodeText(vis, text);
    }

    convertTimeElements(root) {
      var times = root && root.querySelectorAll
        ? root.querySelectorAll('.woocommerce-report-table time[datetime]')
        : [];
      for (var i = 0; i < times.length; i++) {
        this.convertTimeElement(times[i]);
      }
    }

    convertReportTableDates() {
      this.convertTimeElements(document.getElementById('wpbody-content') || document.body);
    }

    // ---- locate the native picker & its inputs --------------------------

    findDayPicker() {
      var dp = document.querySelector('.DayPicker');
      if (dp && dp.offsetParent !== null) {
        return dp;
      }
      return null;
    }

    orderInputs(list) {
      var start = null;
      var end = null;
      list.forEach(function (i) {
        var hint = ((i.getAttribute('aria-label') || '') + ' ' + (i.placeholder || '')).toLowerCase();
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
        var t = start;
        start = end;
        end = t;
      }
      return {start: start, end: end};
    }

    findInputs(dp) {
      var node = dp;
      var tries = 0;
      while (node && tries < 8) {
        var all = [].slice.call(node.querySelectorAll('input'));
        var dated = all.filter(function (i) {
          return this.parseInput(i.value);
        }, this);
        if (dated.length >= 2) {
          return this.orderInputs(dated);
        }
        var texts = all.filter(function (i) {
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
      var n = new Date();
      return this.gToJ(n.getFullYear(), n.getMonth() + 1, n.getDate());
    }

    initFromInputs(dp) {
      var inputs = this.findInputs(dp);
      var startG = inputs && this.parseInput(inputs.start.value);
      var endG = inputs && this.parseInput(inputs.end.value);
      if (startG) {
        this.sel.start = startG;
        this.sel.end = endG || startG;
        var j = this.gToJ(startG.gy, startG.gm, startG.gd);
        this.view = {jy: j.jy, jm: j.jm};
      } else {
        var t = this.today();
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
      var css = '.wcasd-cal{position:fixed;z-index:2147483000;background:#fff;border:1px solid #e0e0e0;' + 'border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.15);padding:12px;direction:rtl;' + 'font-family:inherit;box-sizing:border-box;}' + '.wcasd-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}' + '.wcasd-title{font-weight:700;font-size:15px;color:#1e1e1e;}' + '.wcasd-nav{border:1px solid #dcdcde;background:#fff;border-radius:6px;width:34px;height:34px;' + 'cursor:pointer;font-size:16px;line-height:1;color:#1e1e1e;}' + '.wcasd-nav:hover{background:#f0f0f1;}' + '.wcasd-wd,.wcasd-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;direction:rtl;}' + '.wcasd-wd span{text-align:center;font-size:12px;color:#787c82;padding:4px 0;}' + '.wcasd-day{height:38px;border:0;background:transparent;border-radius:6px;cursor:pointer;' + 'font-size:14px;color:#1e1e1e;}' + '.wcasd-day:hover{background:#f0f0f1;}' + '.wcasd-day.wcasd-range{background:#e8edff;}' + '.wcasd-day.wcasd-sel{background:#3858e9;color:#fff;font-weight:700;}' + '.wcasd-day.wcasd-empty{visibility:hidden;cursor:default;}' + '.wcasd-day:disabled{color:#c3c4c7;cursor:default;background:transparent;}' + '.wcasd-day:disabled:hover{background:transparent;}' + '.wcasd-foot{margin-top:8px;font-size:12px;color:#50575e;text-align:center;}';
      var st = document.createElement('style');
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
      var r = dp.getBoundingClientRect();
      this.cal.style.top = Math.round(r.top) + 'px';
      this.cal.style.left = Math.round(r.left) + 'px';
      this.cal.style.width = Math.round(Math.max(r.width, 280)) + 'px';
    }

    moveMonth(delta) {
      var m = this.view.jm + delta;
      var y = this.view.jy;
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
      var dp = this.findDayPicker();
      if (!dp) {
        return;
      }
      var inputs = this.findInputs(dp);
      if (!inputs || !this.sel.start || !this.sel.end) {
        this.log('writeInputs skipped. inputs:', !!inputs);
        return;
      }
      var a = this.sel.start;
      var b = this.sel.end;
      if (this.cmp(a) > this.cmp(b)) {
        var t = a;
        a = b;
        b = t;
      }
      this.setNativeValue(inputs.start, this.fmtInput(a));
      this.setNativeValue(inputs.end, this.fmtInput(b));
      this.log('wrote inputs ->', this.fmtInput(a), '..', this.fmtInput(b));
    }

    onDay(jy, jm, jd) {
      var g = this.jToG(jy, jm, jd);
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
      var maxCmp = this.todayCmp();

      var head = document.createElement('div');
      head.className = 'wcasd-head';

      var prev = document.createElement('button');
      prev.className = 'wcasd-nav';
      prev.type = 'button';
      prev.setAttribute('data-nav', 'prev');
      prev.textContent = '→';

      var title = document.createElement('div');
      title.className = 'wcasd-title';
      title.textContent = this.MONTHS[this.view.jm - 1] + ' ' + this.faDigits(this.view.jy);

      var next = document.createElement('button');
      next.className = 'wcasd-nav';
      next.type = 'button';
      next.setAttribute('data-nav', 'next');
      next.textContent = '←';

      head.appendChild(prev);
      head.appendChild(title);
      head.appendChild(next);
      this.cal.appendChild(head);

      var wd = document.createElement('div');
      wd.className = 'wcasd-wd';
      for (var w = 0; w < 7; w++) {
        var sp = document.createElement('span');
        sp.textContent = this.WD[w];
        wd.appendChild(sp);
      }
      this.cal.appendChild(wd);

      var grid = document.createElement('div');
      grid.className = 'wcasd-grid';
      var lead = this.firstColumn(this.view.jy, this.view.jm);
      var total = this.daysInJMonth(this.view.jy, this.view.jm);
      var i;
      for (i = 0; i < lead; i++) {
        var e = document.createElement('button');
        e.className = 'wcasd-day wcasd-empty';
        e.type = 'button';
        e.disabled = true;
        grid.appendChild(e);
      }
      for (i = 1; i <= total; i++) {
        var g = this.jToG(this.view.jy, this.view.jm, i);
        var gc = this.cmp(g);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'wcasd-day';
        btn.setAttribute('data-jy', this.view.jy);
        btn.setAttribute('data-jm', this.view.jm);
        btn.setAttribute('data-jday', i);
        btn.textContent = this.faDigits(i);
        if (gc > maxCmp) {
          btn.disabled = true; // future date.
        } else if (this.sel.start && this.sel.end) {
          var lo = Math.min(this.cmp(this.sel.start), this.cmp(this.sel.end));
          var hi = Math.max(this.cmp(this.sel.start), this.cmp(this.sel.end));
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
        var js = this.gToJ(this.sel.start.gy, this.sel.start.gm, this.sel.start.gd);
        var je = this.gToJ(this.sel.end.gy, this.sel.end.gm, this.sel.end.gd);
        var foot = document.createElement('div');
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
      var nav = e.target.closest('[data-nav]');
      if (nav) {
        this.moveMonth(nav.getAttribute('data-nav') === 'next' ? 1 : -1);
        return;
      }
      var day = e.target.closest('[data-jday]');
      if (day && !day.disabled) {
        this.onDay(
          parseInt(day.getAttribute('data-jy'), 10),
          parseInt(day.getAttribute('data-jm'), 10),
          parseInt(day.getAttribute('data-jday'), 10)
        );
      }
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

      if (!this.ENABLED) {
        return;
      }
      var dp = this.findDayPicker();
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
        for (var i = 0; i < mutations.length; i++) {
          var mu = mutations[i];
          if (mu.type === 'characterData') {
            this.convertTextNode(mu.target);
          } else if (mu.type === 'childList') {
            for (var j = 0; j < mu.addedNodes.length; j++) {
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

  new WpPdWoocommerceAnalytics();
})();
