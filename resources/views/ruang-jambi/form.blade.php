@extends('layouts.app')
@php
    $editing = $booking !== null;
    $heading = !$editing ? 'Pengajuan Ruang Jambi' : ($booking->status === 'perlu_revisi' ? 'Revisi Pengajuan Ruang Jambi' : 'Ubah Pengajuan Ruang Jambi');
    $val = fn (string $key, $default = '') => old($key, $default);
@endphp
@section('title', $heading . ' | MLTI')
@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <h1>{{ $heading }}</h1>
            <p class="text-muted mb-0">
                @if ($editing)
                    Kode referensi <strong>{{ $booking->booking_code }}</strong>. Setelah dikirim, status kembali menjadi "Menunggu".
                @else
                    Ajukan pemakaian ruang rapat. Kolom bertanda * wajib diisi. Ketersediaan ruang dihitung otomatis.
                @endif
            </p>
        </div>
        <a href="{{ route('ruang.index') }}" class="btn btn-primary"><i class="bi bi-clock-history me-1"></i>Pengajuan Saya</a>
    </div>

    <section class="section">
        @if ($revisionNote)
            <div class="alert alert-warning d-flex gap-2 border-0 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><strong>Catatan revisi dari Tim Umum</strong>
                    <div style="white-space: pre-line">{{ $revisionNote }}</div>
                </div>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <strong><i class="bi bi-x-circle me-1"></i>Pengajuan belum dapat dikirim.</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" id="rj-form" novalidate enctype="multipart/form-data"
            action="{{ $editing ? route('ruang.update', $booking) : route('ruang.store') }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="row g-4">
                <div class="col-xl-8">
                    {{-- 1. Informasi acara --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="rj-step-head mb-3"><span class="rj-step">1</span>
                                <div><h6>Informasi Acara</h6><small>Nama acara, tanggal, dan waktu pelaksanaan.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="f-title">Nama acara *</label>
                                    <input id="f-title" name="title" maxlength="200" class="form-control @error('title') is-invalid @enderror"
                                        value="{{ $val('title', $booking->title ?? '') }}" placeholder="Contoh: Rapat Koordinasi Pembinaan Statistik Sektoral" required>
                                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="f-date">Tanggal *</label>
                                    <input id="f-date" type="date" name="date" min="{{ now()->toDateString() }}"
                                        class="form-control @error('date') is-invalid @enderror"
                                        value="{{ $val('date', $booking?->starts_at->toDateString() ?? '') }}" required>
                                    @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="f-start">Waktu mulai (WIB) *</label>
                                    <select id="f-start" name="start" class="form-select @error('start') is-invalid @enderror" required>
                                        <option value="">Pilih jam</option>
                                        @foreach (array_slice($timeOptions, 0, -1) as $t)
                                            <option value="{{ $t }}" @selected($val('start', $booking?->starts_at->format('H:i')) === $t)>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" for="f-end">Waktu selesai (WIB) *</label>
                                    <select id="f-end" name="end" class="form-select @error('end') is-invalid @enderror" required>
                                        <option value="">Pilih jam</option>
                                        @foreach (array_slice($timeOptions, 1) as $t)
                                            <option value="{{ $t }}" @selected($val('end', $booking?->ends_at->format('H:i')) === $t)>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    @error('end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="f-participants">Jumlah peserta *</label>
                                    <div class="input-group has-validation">
                                        <input id="f-participants" type="number" name="participants" min="2" max="1000" inputmode="numeric"
                                            class="form-control @error('participants') is-invalid @enderror"
                                            value="{{ $val('participants', $booking->participants ?? '') }}" placeholder="Contoh: 20" required>
                                        <span class="input-group-text">orang</span>
                                        @error('participants')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <small class="text-muted" id="capacity-hint"></small>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Jam operasional {{ config('ruang_jambi.open_time') }}–{{ config('ruang_jambi.close_time') }} WIB, durasi maksimal {{ config('ruang_jambi.max_duration_hours') }} jam.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Tempat / ruangan --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="rj-step-head mb-3"><span class="rj-step">2</span>
                                <div><h6>Tempat / Ruangan *</h6><small>Ketersediaan dihitung dari tanggal, jam, dan jumlah peserta.</small></div>
                            </div>
                            <div class="alert alert-light border small mb-3" id="room-hint">
                                <i class="bi bi-door-open me-1"></i>Lengkapi tanggal, waktu mulai, dan waktu selesai untuk melihat ruangan yang tersedia.
                            </div>
                            <div role="radiogroup" aria-label="Pilihan ruangan" id="room-list">
                                @forelse ($rooms as $room)
                                    <label class="rj-room" data-room="{{ $room->room_id }}">
                                        <input type="radio" name="room_id" value="{{ $room->room_id }}"
                                            @checked((int) $val('room_id', $booking->room_id ?? 0) === (int) $room->room_id)>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                                <div>
                                                    <div class="fw-bold">{{ $room->name }}</div>
                                                    <small class="text-muted">
                                                        @if ($room->location)<i class="bi bi-geo-alt me-1"></i>{{ $room->location }} · @endif
                                                        <span class="rj-cap"><i class="bi bi-people me-1"></i>Kapasitas {{ $room->capacity }}</span>
                                                    </small>
                                                </div>
                                                <span class="badge rounded-pill align-self-start rj-room-badge"></span>
                                            </div>
                                            @if ($room->facilities->isNotEmpty())
                                                <div class="mt-2">
                                                    @foreach ($room->facilities as $facility)
                                                        <span class="rj-chip"><i class="bi bi-check2"></i>{{ $facility->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if ($room->description)
                                                <small class="d-block text-muted mt-1">{{ $room->description }}</small>
                                            @endif
                                            <div class="rj-room-reason"></div>
                                        </div>
                                    </label>
                                @empty
                                    <div class="text-center text-muted py-4"><i class="bi bi-door-closed fs-2 d-block mb-1"></i>Belum ada ruang rapat yang terdaftar.</div>
                                @endforelse
                            </div>
                            @error('room_id')<div class="text-danger small fw-semibold mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- 3. Layout & pejabat --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="rj-step-head mb-3"><span class="rj-step">3</span>
                                <div><h6>Layout Tempat / Ruangan *</h6><small>Tim Umum menata ruangan sesuai layout yang dipilih.</small></div>
                            </div>
                            <div class="row g-2" role="radiogroup" aria-label="Layout ruangan" id="layout-list">
                                @foreach ($layouts as $key => [$label, $desc, $icon])
                                    <div class="col-md-6">
                                        <label class="rj-facility rj-layout">
                                            <input type="radio" class="form-check-input" name="layout" value="{{ $key }}" data-name="{{ $label }}"
                                                @checked($val('layout', $booking->layout ?? '') === $key)>
                                            <i class="bi {{ $icon }} rj-layout-icon"></i>
                                            <span><span class="d-block fw-semibold small">{{ $label }}</span>
                                                <small class="text-muted">{{ $desc }}</small></span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('layout')<div class="text-danger small fw-semibold mt-1">{{ $message }}</div>@enderror

                            <label class="form-label fw-semibold mt-4 d-block">Pejabat yang duduk di depan *</label>
                            @php $officials = (string) $val('officials_count', $booking?->officials_count ?? 0); @endphp
                            <div class="rj-options" role="group" aria-label="Jumlah pejabat di depan" id="officials-group">
                                @for ($i = 0; $i <= config('ruang_jambi.max_officials'); $i++)
                                    <input type="radio" class="btn-check" name="officials_count" id="f-officials-{{ $i }}" value="{{ $i }}" @checked($officials === (string) $i)>
                                    <label class="btn btn-outline-secondary btn-sm" for="f-officials-{{ $i }}">{{ $i === 0 ? 'Tidak Ada' : "$i Orang" }}</label>
                                @endfor
                            </div>
                            <small class="d-block text-muted mt-1">Untuk menyiapkan kursi, papan nama, dan mikrofon di meja depan.</small>
                            @error('officials_count')<div class="text-danger small fw-semibold mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- 4. Penanggung jawab & kepanitiaan --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="rj-step-head mb-3"><span class="rj-step">4</span>
                                <div><h6>Penanggung Jawab & Kepanitiaan</h6><small>Tim kerja yang bertanggung jawab atas acara.</small></div>
                            </div>
                            @php
                                $currentPic = $editing ? (string) $booking->pic : '';
                                $picChoice = old('pic_choice', $currentPic === '' ? '' : (in_array($currentPic, $picOptions, true) ? $currentPic : 'lainnya'));
                                $picOther = old('pic_other', $picChoice === 'lainnya' ? $currentPic : '');
                            @endphp
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="f-pic">Penanggung jawab acara *</label>
                                    <select id="f-pic" name="pic_choice" class="form-select @error('pic_choice') is-invalid @enderror @error('pic') is-invalid @enderror" required>
                                        <option value="">Pilih penanggung jawab</option>
                                        @foreach ($picOptions as $option)
                                            <option value="{{ $option }}" @selected($picChoice === $option)>{{ $option }}</option>
                                        @endforeach
                                        <option value="lainnya" @selected($picChoice === 'lainnya')>Lainnya…</option>
                                    </select>
                                    @error('pic_choice')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <input id="f-pic-other" name="pic_other" maxlength="150" class="form-control mt-2 @error('pic') is-invalid @enderror {{ $picChoice === 'lainnya' ? '' : 'd-none' }}"
                                        value="{{ $picOther }}" placeholder="Tuliskan penanggung jawab, mis. Tim CAN">
                                    @error('pic')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="f-committee">Susunan kepanitiaan</label>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <select id="f-committee" name="committee" class="form-select flex-grow-1 @error('committee') is-invalid @enderror">
                                            <option value="">Tidak ada / belum ditentukan</option>
                                            @foreach ($committees as $key => [$label, $url])
                                                <option value="{{ $key }}" data-url="{{ $url }}" @selected($val('committee', $booking->committee ?? '') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <a class="btn btn-outline-secondary text-nowrap disabled" id="committee-link" href="#" target="_blank" rel="noopener" aria-disabled="true">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Buka template
                                        </a>
                                    </div>
                                    <small class="text-muted">Pilih salah satu template kepanitiaan, lalu lengkapi isiannya sesuai kebutuhan acara.</small>
                                    @error('committee')<div class="text-danger small fw-semibold mt-1">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. Dokumen --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="rj-step-head mb-3"><span class="rj-step">5</span>
                                <div><h6>Dokumen Pendukung</h6><small>PDF, JPG, PNG, atau Word, maksimal 5 MB per berkas.</small></div>
                            </div>
                            @php $accept = collect(config('ruang_jambi.document_mimes'))->map(fn ($m) => '.' . $m)->implode(','); @endphp
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="f-memo">Nota dinas {{ $editing && $booking->memo_path ? '' : '*' }}</label>
                                    <input id="f-memo" type="file" name="memo" accept="{{ $accept }}" class="form-control @error('memo') is-invalid @enderror">
                                    @error('memo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @if ($editing && $booking->memo_path)
                                        <small class="d-block mt-1"><i class="bi bi-paperclip me-1"></i>Berkas saat ini:
                                            <a href="{{ route('ruang.document', [$booking, 'nota-dinas']) }}" target="_blank">{{ $booking->memo_name }}</a>. Unggah ulang untuk mengganti.</small>
                                    @endif
                                    <small class="d-block text-muted mt-1">Format nota dinas: <a href="{{ config('ruang_jambi.memo_template_url') }}" target="_blank" rel="noopener">{{ str_replace('http://', '', config('ruang_jambi.memo_template_url')) }}</a></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="f-letter">Surat pendukung</label>
                                    <input id="f-letter" type="file" name="letter" accept="{{ $accept }}" class="form-control @error('letter') is-invalid @enderror">
                                    @error('letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @if ($editing && $booking->letter_path)
                                        <small class="d-block mt-1"><i class="bi bi-paperclip me-1"></i>Berkas saat ini:
                                            <a href="{{ route('ruang.document', [$booking, 'surat-pendukung']) }}" target="_blank">{{ $booking->letter_name }}</a>. Unggah ulang untuk mengganti.</small>
                                    @endif
                                    <small class="d-block text-muted mt-1">Opsional. Dapat berupa surat undangan yang menggambarkan tema acara secara umum.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Ringkasan --}}
                <div class="col-xl-4">
                    <div class="card rj-sticky">
                        <div class="rj-summary-head">
                            <h6 class="mb-0 fw-bold">Ringkasan Pemesanan</h6>
                            <small class="opacity-75">Periksa kembali sebelum mengirim</small>
                        </div>
                        <div class="card-body pt-3">
                            <dl class="rj-summary mb-0" id="summary"></dl>
                            <button type="button" class="btn btn-primary w-100 py-2 mt-2" id="btn-review">
                                <i class="bi bi-send me-1"></i>{{ $editing ? 'Kirim Perubahan' : 'Kirim Pengajuan' }}
                            </button>
                            <a href="{{ $editing ? route('ruang.show', $booking) : route('ruang.index') }}" class="btn btn-light w-100 mt-2">Batal</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Kirim pengajuan ruang?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-muted">Pastikan data berikut sudah benar. Pengajuan akan berstatus "Menunggu" hingga ditinjau Tim Umum.</p>
                            <div class="border rounded p-3"><p class="fw-bold mb-2" id="confirm-title"></p><dl class="rj-summary mb-0" id="confirm-summary"></dl></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Periksa Lagi</button>
                            <button type="submit" class="btn btn-primary" id="btn-submit"><i class="bi bi-send me-1"></i>Ya, Kirim</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <h5 class="fw-bold mt-2 mb-3"><i class="bi bi-calendar3 me-1"></i>Jadwal Ruang Jambi</h5>
        @php
            $roomFilter = '<select class="form-select form-select-sm" id="cal-room" style="width:auto;min-width:160px" aria-label="Filter ruang"><option value="">Semua ruang</option>'
                . $rooms->map(fn ($r) => '<option value="' . $r->room_id . '">' . e($r->name) . '</option>')->implode('')
                . '</select>';
        @endphp
        @include('general._calendar', ['id' => 'rj-calendar', 'filters' => $roomFilter, 'maintenanceLegend' => true])
    </section>
@endsection

@push('styles')
    @include('ruang-jambi._styles')
@endpush

@push('scripts')
    <script>
        (function () {
            const ROOMS = @json($roomsJson);
            const SCHEDULE = @json($schedule);
            const BLOCKS = @json($blocks);
            const EXCLUDE_ID = @json($booking?->id);
            const EXISTING_MEMO = @json($booking?->memo_name);
            const MAX_BYTES = {{ (int) config('ruang_jambi.document_max_kb') }} * 1024;
            const esc = BookingCalendar.escape;
            const $ = id => document.getElementById(id);
            const form = $('rj-form');
            const fmtDate = d => new Date(d + 'T00:00').toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
            const hm = iso => { const d = new Date(iso); return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0'); };

            function values() {
                const date = $('f-date').value, start = $('f-start').value, end = $('f-end').value;
                const valid = !!(date && start && end && end > start);
                return {
                    date, start, end, valid,
                    s: valid ? new Date(date + 'T' + start) : null,
                    e: valid ? new Date(date + 'T' + end) : null,
                    participants: parseInt($('f-participants').value, 10) || 0,
                    roomId: (form.querySelector('[name=room_id]:checked') || {}).value,
                };
            }

            // Padanan RoomBookingService::check() untuk umpan balik langsung (server tetap memvalidasi ulang).
            function roomStatus(room, v) {
                const overlap = x => new Date(x.start) < v.e && new Date(x.end) > v.s;
                if (!room.active) return { key: 'inactive', label: 'Nonaktif', color: 'secondary', blocked: true, reason: 'Tidak tersedia: ruangan nonaktif.' };
                const block = BLOCKS.find(b => b.room_id === room.id && overlap(b));
                if (block) return { key: 'maintenance', label: 'Maintenance', color: 'secondary', blocked: true, reason: `Tidak tersedia: maintenance ${hm(block.start)}–${hm(block.end)} (${block.reason}).` };
                const others = SCHEDULE.filter(b => b.room_id === room.id && b.id !== EXCLUDE_ID && overlap(b));
                const used = others.find(b => b.status === 'disetujui');
                if (used) return { key: 'in_use', label: 'Terpakai', color: 'danger', blocked: true, reason: `Tidak tersedia: sudah dipesan untuk "${used.title}" (${hm(used.start)}–${hm(used.end)}).` };
                // Melebihi kapasitas hanya peringatan: ruangan tetap dapat dipilih, keputusan ada pada Tim Umum.
                if (v.participants > room.capacity) return { key: 'over', label: 'Melebihi kapasitas', color: 'warning text-dark', blocked: false, reason: `Perhatian: peserta (${v.participants}) melebihi kapasitas ruangan (${room.capacity} orang). Ruangan tetap dapat diajukan; keputusan ada pada Tim Umum.` };
                if (others.length) return { key: 'pending', label: 'Ada pengajuan menunggu', color: 'warning text-dark', blocked: false, reason: 'Ada pengajuan lain yang menunggu persetujuan pada jam ini. Ruangan tetap dapat diajukan; keputusan ada pada Tim Umum.' };
                return { key: 'free', label: 'Tersedia', color: 'success', blocked: false, reason: '' };
            }

            function refreshRooms() {
                const v = values();
                $('room-hint').classList.toggle('d-none', v.valid);
                document.querySelectorAll('.rj-room').forEach(el => {
                    const room = ROOMS.find(r => r.id === Number(el.dataset.room));
                    const input = el.querySelector('input');
                    const badge = el.querySelector('.rj-room-badge');
                    const reason = el.querySelector('.rj-room-reason');
                    if (!v.valid) {
                        badge.className = 'badge rounded-pill align-self-start rj-room-badge bg-light text-muted border';
                        badge.textContent = 'Lengkapi waktu';
                        reason.textContent = '';
                        input.disabled = !room.active;
                        el.classList.toggle('disabled', !room.active);
                    } else {
                        const st = roomStatus(room, v);
                        badge.className = 'badge rounded-pill align-self-start rj-room-badge bg-' + st.color;
                        badge.textContent = st.label;
                        reason.textContent = st.reason;
                        reason.className = 'rj-room-reason ' + (st.blocked ? 'text-danger' : 'text-warning-emphasis');
                        input.disabled = st.blocked;
                        el.classList.toggle('disabled', st.blocked);
                        if (st.blocked && input.checked) input.checked = false;
                    }
                    el.classList.toggle('selected', input.checked);
                });
                const selected = ROOMS.find(r => String(r.id) === values().roomId);
                const over = selected && v.participants > selected.capacity;
                $('capacity-hint').textContent = selected ? `Kapasitas ${selected.name}: ${selected.capacity} orang${over ? ' — jumlah peserta melebihi kapasitas.' : ''}` : '';
                $('capacity-hint').className = over ? 'text-warning-emphasis fw-semibold small' : 'text-muted small';
                renderSummary();
            }

            function picValue() {
                return $('f-pic').value === 'lainnya' ? $('f-pic-other').value.trim() : $('f-pic').value;
            }

            // "Lainnya" membuka isian bebas; template kepanitiaan dapat dibuka langsung.
            function refreshPicAndCommittee() {
                const other = $('f-pic').value === 'lainnya';
                $('f-pic-other').classList.toggle('d-none', !other);
                $('f-pic-other').required = other;
                const url = ($('f-committee').selectedOptions[0] || {}).dataset?.url;
                const link = $('committee-link');
                link.href = url || '#';
                link.classList.toggle('disabled', !url);
                link.setAttribute('aria-disabled', url ? 'false' : 'true');
            }
            $('f-pic').addEventListener('change', () => { refreshPicAndCommittee(); if ($('f-pic').value === 'lainnya') $('f-pic-other').focus(); });
            $('f-committee').addEventListener('change', refreshPicAndCommittee);
            refreshPicAndCommittee();

            function summaryRows() {
                const v = values();
                const room = ROOMS.find(r => String(r.id) === v.roomId);
                const layout = form.querySelector('[name=layout]:checked');
                const officials = Number((form.querySelector('[name=officials_count]:checked') || {}).value || 0);
                const committee = $('f-committee').selectedOptions[0];
                const memo = $('f-memo').files[0] ? $('f-memo').files[0].name : EXISTING_MEMO;
                return [
                    ['bi-door-open', 'Ruangan', room ? `${room.name} (${room.capacity} orang)` : ''],
                    ['bi-calendar-event', 'Tanggal', v.date ? fmtDate(v.date) : ''],
                    ['bi-clock', 'Waktu', v.start && v.end ? `${v.start} – ${v.end} WIB` : ''],
                    ['bi-people', 'Peserta', v.participants ? `${v.participants} orang` : ''],
                    ['bi-grid-1x2', 'Layout', layout ? layout.dataset.name : ''],
                    ['bi-person-badge', 'Pejabat di depan', officials ? `${officials} orang` : 'Tidak ada'],
                    ['bi-person-check', 'Penanggung jawab', picValue()],
                    ['bi-diagram-3', 'Kepanitiaan', committee && committee.value ? committee.textContent : ''],
                    ['bi-file-earmark-text', 'Nota dinas', memo || ''],
                ].filter(([, label, value]) => value || label !== 'Kepanitiaan');
            }

            function renderSummary() {
                const html = summaryRows().map(([icon, label, value]) =>
                    `<dt><i class="bi ${icon} me-1"></i>${label}</dt><dd class="${value ? '' : 'empty'}">${value ? esc(value) : 'Belum diisi'}</dd>`).join('');
                $('summary').innerHTML = html;
                $('confirm-summary').innerHTML = html;
                $('confirm-title').textContent = $('f-title').value.trim();
            }

            function firstError() {
                const v = values();
                const title = $('f-title').value.trim();
                if (title.length < 5) return ['f-title', 'Nama acara minimal 5 karakter.'];
                if (!v.date) return ['f-date', 'Tanggal wajib diisi.'];
                if (!v.start) return ['f-start', 'Waktu mulai wajib dipilih.'];
                if (!v.end || v.end <= v.start) return ['f-end', 'Waktu selesai harus lebih dari waktu mulai.'];
                if (v.s <= new Date()) return ['f-start', 'Waktu mulai sudah terlewati.'];
                if (v.participants < 2) return ['f-participants', 'Jumlah peserta minimal 2 orang.'];
                if (!v.roomId) return ['room-list', 'Pilih salah satu ruangan yang tersedia.'];
                if (!form.querySelector('[name=layout]:checked')) return ['layout-list', 'Pilih layout tempat/ruangan.'];
                if (!$('f-pic').value) return ['f-pic', 'Pilih penanggung jawab acara.'];
                if ($('f-pic').value === 'lainnya' && !$('f-pic-other').value.trim()) return ['f-pic-other', 'Tuliskan nama penanggung jawab acara.'];
                if (!EXISTING_MEMO && !$('f-memo').files.length) return ['f-memo', 'Nota dinas wajib diunggah.'];
                for (const id of ['f-memo', 'f-letter']) {
                    const file = $(id).files[0];
                    if (file && file.size > MAX_BYTES) return [id, `Ukuran berkas "${file.name}" melebihi 5 MB.`];
                }
                return null;
            }

            $('btn-review').addEventListener('click', () => {
                const err = firstError();
                document.querySelectorAll('.rj-client-error').forEach(e => e.remove());
                if (err) {
                    const target = $(err[0]);
                    const msg = document.createElement('div');
                    msg.className = 'rj-client-error text-danger small fw-semibold mt-1';
                    msg.textContent = err[1];
                    (target.closest('.input-group') || target).insertAdjacentElement('afterend', msg);
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (target.focus) target.focus({ preventScroll: true });
                    return;
                }
                renderSummary();
                bootstrap.Modal.getOrCreateInstance($('confirmModal')).show();
            });
            form.addEventListener('submit', () => { $('btn-submit').disabled = true; $('btn-submit').textContent = 'Mengirim…'; });
            form.addEventListener('input', refreshRooms);
            form.addEventListener('change', refreshRooms);

            // Kalender: jadwal pengajuan + maintenance, dapat difilter per ruang.
            const events = SCHEDULE.map(b => ({ title: b.title, subtitle: b.room, status: b.status, start: b.start, end: b.end, room_id: b.room_id }))
                .concat(BLOCKS.map(b => ({ title: 'Maintenance', subtitle: (ROOMS.find(r => r.id === b.room_id) || {}).name, status: 'maintenance', start: b.start, end: b.end, room_id: b.room_id })));
            const calendar = BookingCalendar.mount($('rj-calendar'), {
                events,
                startHour: {{ $calendarHours[0] }},
                endHour: {{ $calendarHours[1] }},
                view: 'week',
                filter: e => !$('cal-room').value || String(e.room_id) === $('cal-room').value,
            });
            $('cal-room').addEventListener('change', calendar.render);
            $('f-date').addEventListener('change', () => { if ($('f-date').value) calendar.goTo($('f-date').value + 'T00:00'); });

            refreshRooms();
        })();
    </script>
@endpush
