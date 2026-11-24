@extends('layouts.app')
@section('title', 'Pengajuan Zoom | MLTI')
@section('content')
    <div class="pagetitle">
        <h1>Pengajuan Zoom</h1>
        <p class="text-muted mb-0">Periksa kalender seluruh pengajuan untuk melihat ketersediaan slot.</p>
    </div>
    <section class="section">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0 fw-bold">Formulir Pengajuan Zoom</h5><a
                        href="{{ route('general.booking.history', 'zoom') }}" class="btn text-white zoom-history"><i
                            class="bi bi-clock-history me-1"></i>Riwayat Pengajuan</a>
                </div>
                <form method="POST" action="{{ route('general.booking.store', 'zoom') }}" id="zoom-form">@csrf
                    <div class="row g-3">
                        <div class="col-lg-5"><label class="form-label fw-semibold">Judul kegiatan *</label><input
                                name="title" class="form-control" value="{{ old('title') }}" required></div>
                        <div class="col-lg-3"><label class="form-label fw-semibold">Perkiraan partisipan *</label>
                            <div class="input-group"><input type="number" name="participants" min="1"
                                    class="form-control" value="{{ old('participants') }}" required><span
                                    class="input-group-text">orang</span></div>
                        </div>
                        <div class="col-lg-4"><label class="form-label fw-semibold d-block">Waktu kegiatan *</label>
                            <div class="time-toggle"><input class="btn-check" type="radio" name="mode"
                                    id="mode-hours" checked><label for="mode-hours"><i
                                        class="bi bi-clock me-1"></i>Jam</label><input class="btn-check" type="radio"
                                    name="mode" id="mode-days"><label for="mode-days"><i
                                        class="bi bi-calendar-range me-1"></i>Hari</label></div>
                        </div>
                        <div class="col-md-4" id="single-date-wrap"><label class="form-label">Tanggal *</label><input
                                type="date" id="single-date" class="form-control"></div>
                        <div class="col-md-4" id="start-time-wrap"><label class="form-label">Jam mulai *</label><input
                                type="time" id="start-time" class="form-control" value="08:00"></div>
                        <div class="col-md-4" id="end-time-wrap"><label class="form-label">Jam selesai *</label><input
                                type="time" id="end-time" class="form-control" value="09:00"></div>
                        <div class="col-md-6 d-none" id="start-date-wrap"><label class="form-label">Tanggal mulai
                                *</label><input type="date" id="start-date" class="form-control"></div>
                        <div class="col-md-6 d-none" id="end-date-wrap"><label class="form-label">Tanggal selesai
                                *</label><input type="date" id="end-date" class="form-control"></div>
                        <input type="hidden" name="starts_at" id="starts-at"><input type="hidden" name="ends_at"
                            id="ends-at">
                        <div class="col-12"><label class="streaming-option"><input class="form-check-input"
                                    type="checkbox" name="live_streaming" value="1"
                                    {{ old('live_streaming') ? 'checked' : '' }}><span><strong>Akses live
                                        streaming</strong><small>Centang jika kegiatan memerlukan akses siaran
                                        langsung.</small></span></label>
                            @error('starts_at')
                                <small class="text-danger d-block mt-2">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-12 text-end"><button class="btn text-white px-4 zoom-form-submit">Kirim
                                Pengajuan</button></div>
                    </div>
                </form>
            </div>
        </div>

        @include('general._calendar', ['id' => 'zoom-calendar'])
    </section>
    @push('styles')
        <style>
            .zoom-history,
            .zoom-form-submit {
                background: linear-gradient(135deg, #ff84ba, #99c2ff);
                border: 0
            }

            .time-toggle {
                display: inline-flex;
                padding: 3px;
                background: #fff2f8;
                border: 1px solid #ffd5e7;
                border-radius: 9px;
                gap: 2px
            }

            .time-toggle label {
                margin: 0;
                border: 0;
                background: transparent;
                border-radius: 6px;
                padding: 5px 11px;
                color: #83586b;
                font-size: .82rem;
                font-weight: 600;
                cursor: pointer
            }

            .time-toggle .btn-check:checked+label {
                background: linear-gradient(135deg, #ff84ba, #99c2ff);
                color: #fff;
                box-shadow: 0 2px 5px rgba(255, 132, 186, .28)
            }

            .streaming-option {
                display: flex;
                align-items: center;
                gap: 11px;
                padding: 11px 14px;
                border: 1px solid #f5d7e6;
                border-radius: 10px;
                background: linear-gradient(90deg, #fff8fc, #f7fbff);
                cursor: pointer
            }

            .streaming-option input {
                width: 18px;
                height: 18px;
                accent-color: #ff84ba
            }

            .streaming-option small {
                display: block;
                color: #87717c;
                margin-top: 2px
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            (function () {
                const p = n => String(n).padStart(2, '0'),
                    dateInput = d => `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`,
                    today = new Date();
                ['single-date', 'start-date', 'end-date'].forEach(id => {
                    const e = document.getElementById(id);
                    e.min = dateInput(today);
                    e.value = dateInput(today);
                });

                function mode() {
                    const days = document.getElementById('mode-days').checked;
                    ['single-date-wrap', 'start-time-wrap', 'end-time-wrap'].forEach(id => document.getElementById(id).classList.toggle('d-none', days));
                    ['start-date-wrap', 'end-date-wrap'].forEach(id => document.getElementById(id).classList.toggle('d-none', !days));
                }
                document.querySelectorAll('[name=mode]').forEach(e => e.onchange = mode);
                document.getElementById('zoom-form').onsubmit = e => {
                    const days = document.getElementById('mode-days').checked,
                        s = days ? document.getElementById('start-date').value + 'T00:00' : document.getElementById('single-date').value + 'T' + document.getElementById('start-time').value,
                        f = days ? document.getElementById('end-date').value + 'T23:59' : document.getElementById('single-date').value + 'T' + document.getElementById('end-time').value;
                    if (new Date(f) <= new Date(s)) {
                        e.preventDefault();
                        alert('Waktu selesai harus setelah waktu mulai.');
                        return;
                    }
                    document.getElementById('starts-at').value = s;
                    document.getElementById('ends-at').value = f;
                };

                BookingCalendar.mount(document.getElementById('zoom-calendar'), {
                    events: @json($calendarBookings),
                    startHour: 7,
                    endHour: 17,
                    view: 'day',
                });
            })();
        </script>
    @endpush
@endsection
