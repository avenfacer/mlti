/*
 * Kalender jadwal (hari / minggu / bulan) untuk pengajuan Zoom & Ruang Jambi.
 *
 * BookingCalendar.mount(root, {
 *   events: [{ title, subtitle?, status: 'menunggu'|'disetujui'|'maintenance', start, end }],
 *   startHour: 7, endHour: 17, view: 'week',
 *   filter: (event) => boolean   // opsional, dievaluasi ulang setiap render()
 * })
 *
 * `root` harus berisi elemen dengan atribut data-cal="calendar|range|prev|next|today"
 * dan tombol [data-view="day|week|month"].
 */
(function (global) {
    'use strict';

    var DAY_NAMES = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    var HOUR_PX = 52;

    function pad(n) { return String(n).padStart(2, '0'); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function monday(d) {
        d = new Date(d);
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() - (d.getDay() + 6) % 7);
        return d;
    }
    function sameDay(a, b) {
        return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }
    function cls(status) {
        return status === 'disetujui' ? 'approved' : (status === 'maintenance' ? 'maintenance' : 'pending');
    }
    function hm(d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); }

    function mount(root, options) {
        var opts = Object.assign({ events: [], startHour: 7, endHour: 17, view: 'week' }, options || {});
        var q = function (name) { return root.querySelector('[data-cal="' + name + '"]'); };
        var calendar = q('calendar');
        var range = q('range');
        var cursor = new Date();
        var view = opts.view;
        var events = opts.events.map(function (e) {
            return Object.assign({}, e, { _s: new Date(e.start), _e: new Date(e.end) });
        });

        function visible() {
            return opts.filter ? events.filter(opts.filter) : events;
        }

        function drawGrid(days) {
            var hours = opts.endHour - opts.startHour;
            var html = '<div class="cal-head"></div>';
            days.forEach(function (day) {
                html += '<div class="cal-head' + (sameDay(day, new Date()) ? ' today' : '') + '">' + DAY_NAMES[(day.getDay() + 6) % 7] + '<b>' + day.getDate() + '</b></div>';
            });
            html += '<div class="schedule-axis">';
            for (var h = opts.startHour; h < opts.endHour; h++) {
                html += '<span style="top:' + ((h - opts.startHour) * HOUR_PX + 8) + 'px">' + pad(h) + '.00</span>';
            }
            html += '</div>';

            var list = visible();
            days.forEach(function (day) {
                var dayStart = new Date(day); dayStart.setHours(opts.startHour, 0, 0, 0);
                var dayEnd = new Date(day); dayEnd.setHours(opts.endHour, 0, 0, 0);
                var items = list.filter(function (e) { return e._s < dayEnd && e._e > dayStart; })
                    .sort(function (a, b) { return a._s - b._s; });

                // Susun ke lajur agar kegiatan yang beririsan tidak saling menimpa. Lebar lajur
                // dihitung per kelompok irisan, sehingga kegiatan yang tidak beririsan tetap selebar kolom.
                var placed = [], cluster = [], lanes = [], clusterEnd = null;
                function closeCluster() {
                    cluster.forEach(function (p) { p.count = lanes.length; });
                    cluster = []; lanes = []; clusterEnd = null;
                }
                items.forEach(function (e) {
                    if (clusterEnd !== null && e._s >= clusterEnd) closeCluster();
                    var lane = lanes.findIndex(function (end) { return end <= e._s; });
                    if (lane === -1) { lane = lanes.length; lanes.push(e._e); } else { lanes[lane] = e._e; }
                    var p = { e: e, lane: lane };
                    cluster.push(p); placed.push(p);
                    clusterEnd = clusterEnd === null || e._e > clusterEnd ? e._e : clusterEnd;
                });
                closeCluster();

                var blocks = placed.map(function (p) {
                    var width = 96 / Math.max(1, p.count);
                    var e = p.e;
                    var vs = e._s > dayStart ? e._s : dayStart;
                    var ve = e._e < dayEnd ? e._e : dayEnd;
                    var top = (vs - dayStart) / 3600000 * HOUR_PX;
                    var height = Math.max(22, (ve - vs) / 3600000 * HOUR_PX - 4);
                    var tip = e.title + (e.subtitle ? ' · ' + e.subtitle : '') + ' (' + hm(e._s) + '–' + hm(e._e) + ')';
                    return '<div class="timed-event ' + cls(e.status) + '" title="' + esc(tip) + '" style="top:' + top + 'px;height:' + height +
                        'px;left:' + (2 + p.lane * width) + '%;width:' + (width - 1) + '%"><strong>' + esc(e.title) + '</strong><small>' +
                        hm(e._s) + ' - ' + hm(e._e) + (e.subtitle ? ' · ' + esc(e.subtitle) : '') + '</small></div>';
                });
                html += '<div class="schedule-day">' + blocks.join('') + '</div>';
            });

            calendar.className = 'schedule-grid';
            calendar.style.setProperty('--day-count', days.length);
            calendar.style.setProperty('--grid-height', (hours * HOUR_PX) + 'px');
            calendar.style.minWidth = days.length === 1 ? '420px' : '900px';
            calendar.innerHTML = html;
        }

        function drawMonth() {
            var first = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            var start = monday(first);
            var list = visible();
            range.textContent = cursor.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            var html = '';
            DAY_NAMES.forEach(function (n) { html += '<div class="cal-head">' + n + '</div>'; });
            for (var i = 0; i < 42; i++) {
                var day = new Date(start); day.setDate(day.getDate() + i);
                var dayStart = new Date(day); dayStart.setHours(0, 0, 0, 0);
                var dayEnd = new Date(day); dayEnd.setHours(23, 59, 59, 999);
                var items = list.filter(function (e) { return e._s <= dayEnd && e._e >= dayStart; });
                html += '<div class="month-day ' + (day.getMonth() === cursor.getMonth() ? '' : 'muted') + (sameDay(day, new Date()) ? ' today' : '') + '"><b>' + day.getDate() + '</b>' +
                    items.slice(0, 3).map(function (e) {
                        return '<span class="month-event ' + cls(e.status) + '" title="' + esc(e.title + (e.subtitle ? ' · ' + e.subtitle : '')) + '">' + hm(e._s) + ' ' + esc(e.title) + '</span>';
                    }).join('') + (items.length > 3 ? '<span class="month-more">+' + (items.length - 3) + ' lainnya</span>' : '') + '</div>';
            }
            calendar.className = 'month-calendar';
            calendar.style.minWidth = '';
            calendar.innerHTML = html;
        }

        function render() {
            if (view === 'month') return drawMonth();
            var days;
            if (view === 'day') {
                days = [new Date(cursor)];
                range.textContent = cursor.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            } else {
                var s = monday(cursor), e = new Date(s);
                e.setDate(e.getDate() + 6);
                days = Array.from({ length: 7 }, function (_, i) { var d = new Date(s); d.setDate(d.getDate() + i); return d; });
                range.textContent = s.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) + ' - ' + e.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
            }
            drawGrid(days);
        }

        function move(dir) {
            if (view === 'month') cursor.setMonth(cursor.getMonth() + dir);
            else cursor.setDate(cursor.getDate() + dir * (view === 'week' ? 7 : 1));
            render();
        }

        q('prev').onclick = function () { move(-1); };
        q('next').onclick = function () { move(1); };
        q('today').onclick = function () { cursor = new Date(); render(); };
        var buttons = root.querySelectorAll('[data-view]');
        buttons.forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.view === view);
            btn.onclick = function () {
                view = btn.dataset.view;
                buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
                render();
            };
        });

        render();

        return {
            render: render,
            goTo: function (date) { cursor = new Date(date); render(); },
        };
    }

    global.BookingCalendar = { mount: mount, escape: esc };
})(window);
