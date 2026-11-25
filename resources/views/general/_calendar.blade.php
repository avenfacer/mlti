{{--
    Kalender jadwal bersama (Zoom & Ruang Jambi). Dirender oleh public/assets/js/booking-calendar.js.
    Parameter: $id (id elemen root), $filters (HTML opsional di toolbar), $maintenanceLegend (bool).
--}}
<div class="card border-0 shadow-sm booking-calendar-card" id="{{ $id }}">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-2 calendar-toolbar">
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-light btn-sm" data-cal="today">Hari Ini</button>
                <button type="button" class="btn btn-light btn-sm" data-cal="prev" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="btn btn-light btn-sm" data-cal="next" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>
            </div>
            <strong data-cal="range"></strong>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                {!! $filters ?? '' !!}
                <div class="calendar-view">
                    <button type="button" data-view="day">Hari</button><button type="button" data-view="week">Minggu</button><button type="button" data-view="month">Bulan</button>
                </div>
                <small class="d-none d-lg-inline cal-legend">
                    <span class="badge text-bg-warning">Menunggu</span>
                    <span class="badge text-bg-success">Disetujui</span>
                    @if (!empty($maintenanceLegend))
                        <span class="badge text-bg-secondary">Maintenance</span>
                    @endif
                </small>
            </div>
        </div>
        <div class="calendar-scroll">
            <div data-cal="calendar"></div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            /* Palet tema aplikasi (custom.css), dipakai Zoom & Ruang Jambi. */
            .booking-calendar-card { --cal-accent: #ff84ba; --cal-accent-2: #99c2ff; --cal-soft: #fff2f8; --cal-soft-border: #ffd5e7; --cal-ink: #83586b; --cal-line: #eadfe5; --cal-muted: #a68a98; --cal-head: #fffafd; }
            .booking-calendar-card { border: 1px solid var(--cal-soft-border) !important; }
            .booking-calendar-card .calendar-toolbar { background: linear-gradient(90deg, var(--cal-head), #f5f9ff); }
            .booking-calendar-card .calendar-view { display: inline-flex; padding: 3px; background: var(--cal-soft); border: 1px solid var(--cal-soft-border); border-radius: 9px; gap: 2px }
            .booking-calendar-card .calendar-view button { margin: 0; border: 0; background: transparent; border-radius: 6px; padding: 5px 11px; color: var(--cal-ink); font-size: .82rem; font-weight: 600; cursor: pointer }
            .booking-calendar-card .calendar-view button.active { background: linear-gradient(135deg, var(--cal-accent), var(--cal-accent-2)); color: #fff; box-shadow: 0 2px 5px rgba(0, 0, 0, .15) }
            .booking-calendar-card .calendar-scroll { overflow-x: auto }
            .booking-calendar-card .cal-head { height: 62px; text-align: center; padding: 8px; font-size: .75rem; font-weight: 700; background: var(--cal-head); border-right: 1px solid var(--cal-line); border-bottom: 1px solid var(--cal-line) }
            .booking-calendar-card .cal-head b { display: block; font-size: 1.2rem; color: var(--cal-accent) }
            .booking-calendar-card .cal-head.today { background: var(--cal-soft) }
            .booking-calendar-card .schedule-grid { min-width: 900px; display: grid; grid-template-columns: 60px repeat(var(--day-count), minmax(110px, 1fr)); grid-template-rows: 62px var(--grid-height, 468px) }
            .booking-calendar-card .schedule-axis { position: relative; border-right: 1px solid var(--cal-line); background: #fff }
            .booking-calendar-card .schedule-axis span { position: absolute; right: 7px; transform: translateY(-50%); font-size: .72rem; color: var(--cal-muted) }
            .booking-calendar-card .schedule-day { position: relative; border-right: 1px solid var(--cal-line); background: repeating-linear-gradient(to bottom, #fff 0, #fff 51px, var(--cal-line) 51px, var(--cal-line) 52px) }
            .booking-calendar-card .timed-event { position: absolute; overflow: hidden; padding: 4px 6px; border-radius: 5px; border-left: 3px solid #f0aa00; background: #fff7d6; color: #765b00; font-size: .7rem; line-height: 1.2; z-index: 2 }
            .booking-calendar-card .timed-event.approved { border-color: #198754; background: #e5f6ec; color: #155b32 }
            .booking-calendar-card .timed-event.maintenance { border-color: #6c757d; background: repeating-linear-gradient(45deg, #f1f3f5, #f1f3f5 6px, #e9ecef 6px, #e9ecef 12px); color: #495057 }
            .booking-calendar-card .timed-event strong, .booking-calendar-card .timed-event small { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
            .booking-calendar-card .month-calendar { min-width: 850px; display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); grid-template-rows: 62px repeat(6, 110px); background: #fff }
            .booking-calendar-card .month-day { min-width: 0; height: 110px; padding: 8px; border-right: 1px solid var(--cal-line); border-bottom: 1px solid var(--cal-line); background: #fff; overflow: hidden }
            .booking-calendar-card .month-day.muted { background: #fbfbfd; color: #c6c0c8 }
            .booking-calendar-card .month-day.today { background: var(--cal-soft) }
            .booking-calendar-card .month-day b { display: block; color: var(--cal-accent); font-size: .85rem }
            .booking-calendar-card .month-event { display: block; margin-top: 4px; padding: 3px 5px; background: #fff7d6; border-left: 3px solid #f0aa00; color: #765b00; border-radius: 3px; font-size: .7rem; overflow: hidden; white-space: nowrap; text-overflow: ellipsis }
            .booking-calendar-card .month-event.approved { background: #e9f8ef; border-color: #198754; color: #155b32 }
            .booking-calendar-card .month-event.maintenance { background: #f1f3f5; border-color: #6c757d; color: #495057 }
            .booking-calendar-card .month-more { display: block; margin-top: 3px; font-size: .68rem; color: var(--cal-muted) }
        </style>
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/js/booking-calendar.js') }}"></script>
    @endpush
@endonce
