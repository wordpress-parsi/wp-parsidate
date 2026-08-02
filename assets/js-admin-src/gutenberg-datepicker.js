/**
 * WP Post Solar Date - Solar calendar + native-style date fields for the
 * Gutenberg publish date-time picker.
 *
 * We hide only the native date FIELDS ROW (month select + year/day inputs)
 * and the CALENDAR - the time row is left completely untouched. Over that
 * area we render:
 *   - a real <select> for the Solar month (a true drop-down),
 *   - real typeable inputs for the Solar year and day,
 *   - a Solar calendar grid.
 * Everything drives the real post date through wp.data. The overlay re-syncs
 * from wp.data on external changes (e.g. the "Now" button).
 *
 * NOTE: form controls are allowed to take focus (so typing / the dropdown
 * work). Whether the publishing popover stays open while they're focused depends
 * on the editor build; if it closes, the fallback is to inject inside the
 * popover instead.
 */
(function () {
    'use strict';

    if (!window.WpPdJalaliDate) {
        return;
    }

    class WpPdGutenbergDatePicker {
        constructor() {
            var s = window.WpPdGdp_SETTINGS || {};
            this.DEBUG = s.debug === true;
            this.ENABLED = s.enableOverlay !== false;
            this.usePersianDigits = s.usePersianDigits !== false;
            this.MONTHS = s.monthNames || [
                'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
                'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
            ];
            this.WD = s.weekdayShort || ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

            this.G_MONTHS = {
                january: 1, february: 2, march: 3, april: 4, may: 5, june: 6,
                july: 7, august: 8, september: 9, october: 10, november: 11, december: 12,
                'ژانویه': 1, 'فوریه': 2, 'مارس': 3, 'آوریل': 4, 'مه': 5, 'می': 5,
                'ژوئن': 6, 'ژوئیه': 7, 'جولای': 7, 'اوت': 8, 'آگوست': 8,
                'سپتامبر': 9, 'اکتبر': 10, 'نوامبر': 11, 'دسامبر': 12,
            };
            this.G_KEYS = Object.keys(this.G_MONTHS).sort(function (a, b) {
                return b.length - a.length;
            });

            // ---- state ------------------------------------------------------
            this.cal = null;
            this.view = null;
            this.sel = null;
            this.showFields = false;
            this.inited = false;
            this.scheduled = false;
            this.hidden = [];
            this.lastCmp = -1;
            this.lastAttr = null;
            this.toggleObs = [];

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
                    ['[WPParsiDateGutenbergDatePicker]'].concat([].slice.call(arguments))
                );
            }
        }

        faDigits(v) {
            if (!this.usePersianDigits) {
                return String(v);
            }
            var map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return String(v).replace(/[0-9]/g, function (d) {
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
            return (new Date(g.gy, g.gm - 1, g.gd).getDay() + 1) % 7;
        }

        cmp(g) {
            return g.gy * 10000 + g.gm * 100 + g.gd;
        }

        // ---- wp.data bridge -------------------------------------------------

        editor() {
            return window.wp && window.wp.data ? window.wp.data : null;
        }

        // Raw 'date' attribute string, or null. Gutenberg's "Now" button runs
        // editPost({ date: null }) and the attribute then STAYS null (floating
        // date) until the post is saved — so null is a normal, expected state.
        currentAttrRaw() {
            var d = this.editor();
            if (d) {
                try {
                    var str = d.select('core/editor').getEditedPostAttribute('date');
                    return str ? String(str) : null;
                } catch (e) {
                }
            }
            return null;
        }

        currentGregorian() {
            var str = this.currentAttrRaw();
            var m = str && str.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                return {gy: +m[1], gm: +m[2], gd: +m[3]};
            }
            // Floating date ("Now" was pressed) -> today.
            var n = new Date();
            return {gy: n.getFullYear(), gm: n.getMonth() + 1, gd: n.getDate()};
        }

        setPostDate(g) {
            var d = this.editor();
            if (!d) {
                return null;
            }
            var time = null;
            var cur = this.currentAttrRaw();
            var m = cur && cur.match(/T(\d{2}:\d{2}:\d{2})/);
            if (m) {
                time = m[1];
            } else {
                // Attribute is null (user pressed "Now"). Preserve the CURRENT
                // clock time, never 00:00:00 — otherwise a future pick after
                // "Now" gets silently scheduled at midnight while the native
                // time row still displays the current time.
                var n = new Date();
                time = this.pad2(n.getHours()) + ':' + this.pad2(n.getMinutes()) + ':' +
                    this.pad2(n.getSeconds());
            }
            var iso = g.gy + '-' + this.pad2(g.gm) + '-' + this.pad2(g.gd) + 'T' + time;
            try {
                d.dispatch('core/editor').editPost({date: iso});
                this.log('set post date ->', iso);
                return iso;
            } catch (e) {
            }
            return null;
        }

        // ---- locate native picker / calendar / fields -----------------------

        // True when a node belongs to OUR overlay. Now that the overlay is
        // mounted inside the picker (see mountOverlay), every native-detection
        // scan must exclude our nodes: our Persian-digit day buttons would match
        // findDayCells, and our 4-digit Jalali year input would make
        // findFieldsRow climb all the way up to the picker root and hide
        // everything, including the overlay itself.
        isOurs(n) {
            return !!(n && n.closest && n.closest('.wppd-gdp-cal'));
        }

        findPicker() {
            var p = document.querySelector(
                '.block-editor-publish-date-time-picker, .components-datetime'
            );
            return p && p.offsetParent !== null ? p : null;
        }

        findDayCells(picker) {
            return [].slice
                .call(
                    picker.querySelectorAll('button, td, [role="gridcell"], [role="button"]')
                )
                .filter(function (n) {
                    if (this.isOurs(n)) {
                        return false;
                    }
                    var t = this.normalizeDigits((n.textContent || '').trim());
                    return /^\d{1,2}$/.test(t) && +t >= 1 && +t <= 31;
                }, this);
        }

        isMonthSelect(sel) {
            if (!sel || !sel.options) {
                return false;
            }
            var hit = 0;
            for (var i = 0; i < sel.options.length; i++) {
                var t = this.normalizeDigits((sel.options[i].textContent || '').trim())
                    .toLowerCase();
                for (var k = 0; k < this.G_KEYS.length; k++) {
                    if (t.indexOf(this.G_KEYS[k].toLowerCase()) !== -1) {
                        hit++;
                        break;
                    }
                }
            }
            return hit >= 6;
        }

        findMonthSelect(picker) {
            var sels = picker.querySelectorAll('select');
            for (var i = 0; i < sels.length; i++) {
                if (this.isMonthSelect(sels[i])) {
                    return sels[i];
                }
            }
            return null;
        }

        // The fields row = smallest ancestor of the month <select> that also holds
        // a 4-digit (year) input. This deliberately excludes the time row, which
        // has no 4-digit input.
        findFieldsRow(monthSelect) {
            var node = monthSelect.parentElement;
            for (var t = 0; t < 5 && node; t++) {
                var ins = [].slice.call(node.querySelectorAll('input'));
                var yr = ins.filter(function (i) {
                    return !this.isOurs(i) &&
                        /^\d{4}$/.test(this.normalizeDigits(i.value || ''));
                }, this);
                if (yr.length) {
                    return node;
                }
                node = node.parentElement;
            }
            return monthSelect.parentElement;
        }

        findCaption(picker) {
            var els = [].slice.call(picker.querySelectorAll('*'));
            var best = null;
            for (var i = 0; i < els.length; i++) {
                if (this.isOurs(els[i])) {
                    continue;
                }
                var raw = (els[i].textContent || '').trim();
                if (raw.length > 24) {
                    continue;
                }
                var t = this.normalizeDigits(raw).toLowerCase();
                if (!/\d{4}/.test(t)) {
                    continue;
                }
                for (var k = 0; k < this.G_KEYS.length; k++) {
                    if (new RegExp('^' + this.G_KEYS[k].toLowerCase() + '\\s+\\d{4}$').test(t)) {
                        if (!best || raw.length < best.textContent.trim().length) {
                            best = els[i];
                        }
                        break;
                    }
                }
            }
            return best;
        }

        unionRect(els) {
            var t = Infinity, l = Infinity, r = -Infinity, b = -Infinity;
            els.forEach(function (e) {
                if (!e) {
                    return;
                }
                var q = e.getBoundingClientRect();
                if (q.width || q.height) {
                    t = Math.min(t, q.top);
                    l = Math.min(l, q.left);
                    r = Math.max(r, q.right);
                    b = Math.max(b, q.bottom);
                }
            });
            return {top: t, left: l, right: r, bottom: b, width: r - l, height: b - t};
        }

        commonAncestor(nodes) {
            var a = nodes[0];
            for (var i = 1; i < nodes.length && a; i++) {
                while (a && !a.contains(nodes[i])) {
                    a = a.parentElement;
                }
            }
            return a;
        }

        // ---- state ----------------------------------------------------------

        initState() {
            this.sel = this.currentGregorian();
            var j = this.gToJ(this.sel.gy, this.sel.gm, this.sel.gd);
            this.view = {jy: j.jy, jm: j.jm};
            this.lastCmp = this.cmp(this.sel);
            this.lastAttr = this.currentAttrRaw();
        }

        applySolar(jy, jm, jd) {
            jd = Math.max(1, Math.min(jd, this.daysInJMonth(jy, jm)));
            var g = this.jToG(jy, jm, jd);
            this.sel = g;
            this.view = {jy: jy, jm: jm};
            this.lastCmp = this.cmp(g);
            this.lastAttr = this.setPostDate(g);
            this.render();
        }

        // ---- styles (WordPress-like) ----------------------------------------

        ensureStyles() {
            if (document.getElementById('wppd-gdp-style')) {
                return;
            }
            var css =
                '.wppd-gdp-cal{position:absolute;z-index:10;background:#fff;padding:8px 4px;' +
                'direction:rtl;font-family:inherit;color:#1e1e1e;box-sizing:border-box;}' +
                '.wppd-gdp-fields{display:flex;gap:8px;margin-bottom:8px;padding:0 4px;}' +
                '.wppd-gdp-f{display:flex;}' +
                '.wppd-gdp-f-day{flex:0 0 58px;}.wppd-gdp-f-month{flex:1;}.wppd-gdp-f-year{flex:0 0 78px;}' +
                '.wppd-gdp-inp,.wppd-gdp-msel{width:100%;box-sizing:border-box;border:1px solid #949494;' +
                'border-radius:2px;height:36px;padding:0 8px;font-size:13px;color:#1e1e1e;background:#fff;' +
                'font-family:inherit;}' +
                '.wppd-gdp-inp{text-align:center;}' +
                '.wppd-gdp-inp:focus,.wppd-gdp-msel:focus{border-color:#3858e9;outline:1px solid #3858e9;}' +
                '.wppd-gdp-head{display:flex;align-items:center;justify-content:space-between;padding:0 4px;margin-bottom:2px;}' +
                '.wppd-gdp-title{font-weight:600;font-size:13px;}' +
                '.wppd-gdp-nav{border:0;background:transparent;width:28px;height:28px;cursor:pointer;color:#1e1e1e;' +
                'border-radius:2px;font-size:16px;line-height:1;}' +
                '.wppd-gdp-nav:hover{background:#f0f0f1;}' +
                '.wppd-gdp-wd,.wppd-gdp-grid{display:grid;grid-template-columns:repeat(7,1fr);direction:rtl;}' +
                '.wppd-gdp-wd span{text-align:center;font-size:11px;color:#757575;padding:4px 0;}' +
                '.wppd-gdp-day{height:33px;width:33px;margin:1px auto;border:0;background:transparent;' +
                'border-radius:100%;cursor:pointer;font-size:13px;color:#1e1e1e;}' +
                '.wppd-gdp-day:hover{background:#f0f0f1;}' +
                '.wppd-gdp-day.wppd-gdp-sel{background:#3858e9;color:#fff;}' +
                '.wppd-gdp-day.wppd-gdp-empty{visibility:hidden;cursor:default;}';
            var st = document.createElement('style');
            st.id = 'wppd-gdp-style';
            st.textContent = css;
            document.head.appendChild(st);
        }

        buildOverlay(parent) {
            this.ensureStyles();
            this.cal = document.createElement('div');
            this.cal.className = 'wppd-gdp-cal';
            this.mountOverlay(parent);
        }

        // Mount INSIDE the picker root (inside the popover wrapper). This is the
        // fallback the file header anticipated: Gutenberg's popover closes via
        // useFocusOutside, which queues a close on every blur and cancels it
        // only when the next focus lands back INSIDE the wrapper. A body-mounted
        // input can therefore never hold focus without closing the popover;
        // an inside-mounted one behaves exactly like the native fields.
        mountOverlay(parent) {
            if (!this.cal || !parent) {
                return;
            }
            try {
                var cs = window.getComputedStyle(parent);
                if (cs && cs.position === 'static') {
                    parent.style.position = 'relative';
                }
            } catch (e) {
            }
            parent.appendChild(this.cal);
        }

        hideNative(els) {
            els.forEach(function (e) {
                if (e && e.style && e.style.visibility !== 'hidden') {
                    e.style.visibility = 'hidden';
                    this.hidden.push(e);
                }
            }, this);
        }

        // ---- rendering ------------------------------------------------------

        renderFields(selJ) {
            var row = document.createElement('div');
            row.className = 'wppd-gdp-fields';

            // Day (typeable). RTL: appears rightmost.
            var dayWrap = document.createElement('div');
            dayWrap.className = 'wppd-gdp-f wppd-gdp-f-day';
            var dayInp = document.createElement('input');
            dayInp.type = 'text';
            dayInp.inputMode = 'numeric';
            dayInp.className = 'wppd-gdp-inp';
            dayInp.value = this.faDigits(selJ.jd);
            this.commit(dayInp, (n) => {
                this.applySolar(selJ.jy, selJ.jm, n);
            });
            dayWrap.appendChild(dayInp);
            row.appendChild(dayWrap);

            // Month (real <select> drop-down).
            var mWrap = document.createElement('div');
            mWrap.className = 'wppd-gdp-f wppd-gdp-f-month';
            var msel = document.createElement('select');
            msel.className = 'wppd-gdp-msel';
            for (var i = 1; i <= 12; i++) {
                var opt = document.createElement('option');
                opt.value = i;
                opt.textContent = this.MONTHS[i - 1];
                if (i === selJ.jm) {
                    opt.selected = true;
                }
                msel.appendChild(opt);
            }
            msel.addEventListener('change', () => {
                this.applySolar(selJ.jy, parseInt(msel.value, 10), selJ.jd);
            });
            mWrap.appendChild(msel);
            row.appendChild(mWrap);

            // Year (typeable).
            var yWrap = document.createElement('div');
            yWrap.className = 'wppd-gdp-f wppd-gdp-f-year';
            var yInp = document.createElement('input');
            yInp.type = 'text';
            yInp.inputMode = 'numeric';
            yInp.className = 'wppd-gdp-inp';
            yInp.value = this.faDigits(selJ.jy);
            this.commit(yInp, (n) => {
                this.applySolar(n, selJ.jm, selJ.jd);
            });
            yWrap.appendChild(yInp);
            row.appendChild(yWrap);

            this.cal.appendChild(row);
        }

        // Apply on Enter or blur (change), parsing Persian/Latin digits. Only
        // fires when the value actually differs from what was last applied, so
        // the blur that happens while clicking the native "Now" button cannot
        // re-issue a stale date write that races Gutenberg's editPost(null).
        commit(input, cb) {
            var applied = this.normalizeDigits(input.value);

            function fire() {
                var raw = this.normalizeDigits(input.value);
                var n = parseInt(raw, 10);
                if (!isNaN(n) && raw !== applied) {
                    applied = raw;
                    cb(n);
                }
            }

            input.addEventListener('change', fire.bind(this));
            input.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    fire.call(this);
                }
            }.bind(this));
        }

        renderCalendar() {
            var head = document.createElement('div');
            head.className = 'wppd-gdp-head';
            var prev = document.createElement('button');
            prev.className = 'wppd-gdp-nav';
            prev.type = 'button';
            prev.setAttribute('data-nav', 'prev');
            prev.textContent = '→';
            var title = document.createElement('div');
            title.className = 'wppd-gdp-title';
            title.textContent = this.MONTHS[this.view.jm - 1] + ' ' + this.faDigits(this.view.jy);
            var next = document.createElement('button');
            next.className = 'wppd-gdp-nav';
            next.type = 'button';
            next.setAttribute('data-nav', 'next');
            next.textContent = '←';
            head.appendChild(prev);
            head.appendChild(title);
            head.appendChild(next);
            this.cal.appendChild(head);

            var wd = document.createElement('div');
            wd.className = 'wppd-gdp-wd';
            for (var w = 0; w < 7; w++) {
                var sp = document.createElement('span');
                sp.textContent = this.WD[w];
                wd.appendChild(sp);
            }
            this.cal.appendChild(wd);

            var grid = document.createElement('div');
            grid.className = 'wppd-gdp-grid';
            var lead = this.firstColumn(this.view.jy, this.view.jm);
            var total = this.daysInJMonth(this.view.jy, this.view.jm);
            var i;
            for (i = 0; i < lead; i++) {
                var e = document.createElement('button');
                e.className = 'wppd-gdp-day wppd-gdp-empty';
                e.type = 'button';
                e.disabled = true;
                grid.appendChild(e);
            }
            for (i = 1; i <= total; i++) {
                var gg = this.jToG(this.view.jy, this.view.jm, i);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'wppd-gdp-day';
                btn.setAttribute('data-jy', this.view.jy);
                btn.setAttribute('data-jm', this.view.jm);
                btn.setAttribute('data-jday', i);
                btn.textContent = this.faDigits(i);
                if (this.sel && this.cmp(gg) === this.cmp(this.sel)) {
                    btn.className += ' wppd-gdp-sel';
                }
                grid.appendChild(btn);
            }
            this.cal.appendChild(grid);
        }

        render() {
            if (!this.cal || !this.view) {
                return;
            }
            this.cal.textContent = '';
            var selJ = this.sel
                ? this.gToJ(this.sel.gy, this.sel.gm, this.sel.gd)
                : {jy: this.view.jy, jm: this.view.jm, jd: 1};
            if (this.showFields) {
                this.renderFields(selJ);
            }
            this.renderCalendar();
        }

        // ---- click interception ---------------------------------------------

        onDocEvent(e) {
            if (!this.cal || !this.cal.contains(e.target)) {
                return;
            }
            var isForm = /^(INPUT|SELECT|OPTION|TEXTAREA)$/.test(e.target.tagName);
            // Form controls: do NOT stop propagation and do NOT preventDefault.
            // The overlay lives inside the popover wrapper now, and the wrapper's
            // useFocusOutside needs to see the focus/mouse activity on these
            // controls to cancel its queued close. Blocking it here is what used
            // to close the picker when clicking the day/month/year fields.
            if (isForm) {
                return;
            }
            // Buttons (day cells, nav): keep focus where it is and keep the
            // event away from everything else.
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
                var m = this.view.jm + (nav.getAttribute('data-nav') === 'next' ? 1 : -1);
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
                return;
            }
            var day = e.target.closest('[data-jday]');
            if (day && !day.disabled) {
                this.applySolar(
                    parseInt(day.getAttribute('data-jy'), 10),
                    parseInt(day.getAttribute('data-jm'), 10),
                    parseInt(day.getAttribute('data-jday'), 10)
                );
            }
        }

        // ---- sidebar schedule label (Jalali) --------------------------------

        // The sidebar "Publish" row shows the post date through the
        // editor-post-schedule__dialog-toggle button. We overwrite Gutenberg's
        // Gregorian label with a fixed Jalali format (PHP-style "j F Y g:i a"):
        // day-of-month, full month name, Jalali year, 12-hour time. Updated on
        // every tick so a date change in the picker (or via the editor data
        // store / "Now" button) is reflected in the panel.
        jalaliPanelLabel() {
            var attr = this.currentAttrRaw();
            var m = attr && attr.match(
                /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/
            );
            if (!m) {
                return null;
            }
            var j = this.gToJ(+m[1], +m[2], +m[3]);
            var h = +m[4];
            var h12 = h % 12 === 0 ? 12 : h % 12;
            var ampm = h < 12 ? 'ق‌ظ' : 'ب‌ظ';

            return this.faDigits(j.jd) + ' ' + this.MONTHS[j.jm - 1] + ' ' +
                this.faDigits(j.jy) + '، ' +
                this.faDigits(h12) + ':' + this.faDigits(this.pad2(+m[5])) + ' ' + ampm;
        }

        updateToggle(btn) {
            if (!btn) {
                return;
            }
            var label = this.jalaliPanelLabel();
            if (label === null) {
                return; // floating date -> keep the native "Immediately" label.
            }
            var text = (btn.textContent || '').trim();
            if (label !== text) {
                // Mutate the existing text node in place (nodeValue) so React's
                // reference to it stays valid and later label re-renders land on
                // the same node; replacing the node would freeze future updates.
                var tn = btn.firstChild;
                if (tn && tn.nodeType === 3) {
                    tn.nodeValue = label;
                } else {
                    btn.textContent = label;
                }
            }
            var aria = btn.getAttribute('aria-label');
            if (aria && aria.indexOf(text) !== -1) {
                var jalaliAria = aria.replace(text, label);
                if (jalaliAria !== aria) {
                    btn.setAttribute('aria-label', jalaliAria);
                }
            }
        }

        // React updates the toggle label in place (characterData) when the date
        // changes, without a childList mutation; observe the button itself so we
        // re-apply the Jalali label even when no other DOM changes trigger tick.
        ensureToggleObserver(btn) {
            for (var i = 0; i < this.toggleObs.length; i++) {
                var o = this.toggleObs[i];
                if (!o._btn || !o._btn.isConnected) {
                    try {
                        o.disconnect();
                    } catch (e) {
                    }
                    this.toggleObs.splice(i, 1);
                    i--;
                    continue;
                }
                if (o._btn === btn) {
                    return;
                }
            }
            try {
                var mo = new MutationObserver(() => this.schedule());
                mo._btn = btn;
                mo.observe(btn, {
                    childList: true,
                    characterData: true,
                    subtree: true,
                });
                this.toggleObs.push(mo);
            } catch (e) {
            }
        }

        updateScheduleToggles() {
            var btns = document.querySelectorAll(
                '.editor-post-schedule__dialog-toggle'
            );
            for (var i = 0; i < btns.length; i++) {
                this.updateToggle(btns[i]);
                this.ensureToggleObserver(btns[i]);
            }
        }

        // ---- main loop ------------------------------------------------------

        teardown() {
            this.toggleObs.forEach(function (o) {
                try {
                    o.disconnect();
                } catch (x) {
                }
            });
            this.toggleObs = [];
            if (this.cal) {
                if (this.cal.parentNode) {
                    this.cal.parentNode.removeChild(this.cal);
                }
                this.cal = null;
            }
            this.hidden.forEach(function (e) {
                try {
                    e.style.visibility = '';
                } catch (x) {
                }
            });
            this.hidden = [];
            this.inited = false;
        }

        tick() {
            this.scheduled = false;
            if (!this.ENABLED) {
                return;
            }
            // Keep the sidebar schedule toggle in Jalali even when the date
            // picker itself is closed (its label lives outside the popover).
            this.updateScheduleToggles();
            var picker = this.findPicker();
            if (!picker) {
                if (this.cal) {
                    this.teardown();
                }
                return;
            }
            var days = this.findDayCells(picker);
            if (days.length < 20) {
                return;
            }

            var monthSelect = this.findMonthSelect(picker);
            var calendarEl = this.commonAncestor(days);
            var hideTargets;
            var posEls;

            if (monthSelect) {
                this.showFields = true;
                var fieldsRow = this.findFieldsRow(monthSelect);
                hideTargets = [fieldsRow, calendarEl]; // NOT the time row.
                posEls = [fieldsRow, calendarEl];
            } else {
                this.showFields = false;
                var caption = this.findCaption(picker);
                hideTargets = [calendarEl, caption];
                posEls = [caption, calendarEl];
            }

            if (!this.cal) {
                this.buildOverlay(picker);
            } else if (this.cal.parentNode !== picker) {
                // React rebuilt the picker root (or first run after update):
                // re-mount our overlay inside the current root.
                this.mountOverlay(picker);
            }
            if (!this.inited) {
                this.initState();
                this.inited = true;
                this.render();
            } else {
                // Resync on ANY attribute change, including a change to null
                // (the "Now" button). Comparing only the date part misses
                // null-transitions that land on the same calendar day.
                var raw = this.currentAttrRaw();
                var cur = this.currentGregorian();
                if (raw !== this.lastAttr || this.cmp(cur) !== this.lastCmp) {
                    this.sel = cur;
                    var j = this.gToJ(cur.gy, cur.gm, cur.gd);
                    this.view = {jy: j.jy, jm: j.jm};
                    this.lastCmp = this.cmp(cur);
                    this.lastAttr = raw;
                    this.log('external date change ->', raw);
                    this.render();
                }
            }
            this.hideNative(hideTargets);

            var rect = this.unionRect(posEls);
            var rootRect = picker.getBoundingClientRect();
            this.cal.style.top = Math.round(rect.top - rootRect.top) + 'px';
            this.cal.style.left = Math.round(rect.left - rootRect.left) + 'px';
            this.cal.style.width = Math.round(Math.max(rect.width || 0, 240)) + 'px';

            // A 6-row Jalali month is taller than the hidden native grid, so the
            // overlay would spill past the popover card. Grow the native
            // calendar's reserved space (it is visibility:hidden, so it still
            // takes layout space) until the card bottom reaches the overlay
            // bottom. Idempotent; recomputed every tick.
            if (calendarEl && calendarEl.getBoundingClientRect) {
                var calRect = calendarEl.getBoundingClientRect();
                var needH = Math.ceil(rect.top + this.cal.offsetHeight - calRect.top);
                if (needH > 0 && calendarEl.style.minHeight !== needH + 'px') {
                    calendarEl.style.minHeight = needH + 'px';
                }
            }
        }

        schedule() {
            if (this.scheduled) {
                return;
            }
            this.scheduled = true;
            window.requestAnimationFrame(() => this.tick());
        }

        start() {
            this.log('active. wp.data:', !!this.editor(), '| enabled:', this.ENABLED);
            ['mousedown', 'pointerdown', 'mouseup', 'click', 'touchstart'].forEach(
                function (ev) {
                    document.addEventListener(ev, this.onDocEvent, true);
                }, this
            );
            this.schedule();
            new MutationObserver(() => this.schedule()).observe(document.body, {
                childList: true,
                subtree: true,
                // The popover repositions itself via style/transform when its
                // content grows (our min-height fix) — track that too, or the
                // fixed overlay drifts off the card until the next DOM change.
                attributes: true,
                attributeFilter: ['style', 'class'],
            });
            window.addEventListener('scroll', () => this.schedule(), true);
            window.addEventListener('resize', () => this.schedule());

            if (this.DEBUG) {
                setTimeout(() => {
                    var p = this.findPicker();
                    this.log(
                        'scan -> picker:', !!p,
                        '| day cells:', p ? this.findDayCells(p).length : 0,
                        '| month select:', p ? !!this.findMonthSelect(p) : false
                    );
                }, 4000);
            }
        }
    }

    new WpPdGutenbergDatePicker();
})();
