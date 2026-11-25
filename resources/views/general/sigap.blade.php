@extends('layouts.app')
@section('title', 'SIGAP | MLTI')
@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <h1>Pengaduan SIGAP</h1>
            <p class="text-muted mb-0">Laporkan kerusakan fasilitas umum kantor. Kolom bertanda * wajib diisi.</p>
        </div>
        <a href="{{ route('general.sigap.history') }}" class="btn btn-primary"><i class="bi bi-clock-history me-1"></i>Pengaduan Saya</a>
    </div>

    <section class="section">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                <strong><i class="bi bi-x-circle me-1"></i>Pengaduan belum dapat dikirim.</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('general.sigap.store') }}">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    {{-- 1. Lokasi --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="sg-step-head mb-3"><span class="sg-step">1</span>
                                <div><h6>Lokasi Kerusakan</h6><small>Ruangan tempat fasilitas yang rusak berada.</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="f-room">Ruangan *</label>
                                    <select id="f-room" name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                        <option value="">Pilih ruangan</option>
                                        @foreach ($rooms as $room)
                                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->ruang }}</option>
                                        @endforeach
                                    </select>
                                    @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="f-location">Detail lokasi</label>
                                    <input id="f-location" name="location" maxlength="255" value="{{ old('location') }}"
                                        class="form-control @error('location') is-invalid @enderror" placeholder="Opsional. Contoh: dekat jendela sisi barat">
                                    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Jenis kerusakan --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="sg-step-head mb-3"><span class="sg-step">2</span>
                                <div><h6>Jenis Kerusakan *</h6><small>Pilih kategori yang paling sesuai.</small></div>
                            </div>
                            <div class="row g-2 sg-cat-group @error('category') is-invalid @enderror" role="radiogroup" aria-label="Jenis kerusakan">
                                @foreach ($categories as $value => $label)
                                    <div class="col-6 col-md-3">
                                        <label class="sg-cat position-relative">
                                            <input type="radio" name="category" value="{{ $value }}" @checked(old('category') === $value) required>
                                            <i class="bi {{ \App\Models\GeneralComplaint::CATEGORY_ICONS[$value] ?? 'bi-tools' }}"></i>
                                            <span>{{ $label }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('category')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- 3. Deskripsi --}}
                    <div class="card">
                        <div class="card-body pt-4">
                            <div class="sg-step-head mb-3"><span class="sg-step">3</span>
                                <div><h6>Deskripsi *</h6><small>Jelaskan kondisi kerusakan agar petugas dapat menyiapkan penanganan.</small></div>
                            </div>
                            <textarea id="f-description" name="description" rows="5" minlength="5" maxlength="2000" required
                                class="form-control @error('description') is-invalid @enderror"
                                placeholder="Contoh: AC tidak dingin sejak pagi dan mengeluarkan bunyi berisik.">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Minimal 5 karakter.</small>
                                <small class="text-muted"><span id="desc-count">{{ mb_strlen(old('description', '')) }}</span>/2000</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="sg-sticky">
                        <div class="card">
                            <div class="card-body pt-4">
                                <h6 class="fw-semibold mb-3"><i class="bi bi-signpost-split me-1"></i>Alur Penanganan</h6>
                                <ul class="sg-timeline sg-flow mb-3">
                                    <li class="done"><span class="sg-dot bg-sg-primary"><i class="bi bi-send"></i></span>
                                        <div><strong>Dikirim</strong><small class="d-block text-muted">Anda menerima nomor tiket SIGAP.</small></div></li>
                                    <li class="done"><span class="sg-dot bg-sg-primary"><i class="bi bi-gear"></i></span>
                                        <div><strong>Diproses</strong><small class="d-block text-muted">Tim Umum menerima notifikasi dan menindaklanjuti.</small></div></li>
                                    <li class="done"><span class="sg-dot bg-sg-primary"><i class="bi bi-check2"></i></span>
                                        <div><strong>Selesai</strong><small class="d-block text-muted">Catatan petugas dapat dilihat di riwayat.</small></div></li>
                                </ul>
                                <button class="btn btn-primary w-100 py-2"><i class="bi bi-send me-1"></i>Kirim Pengaduan</button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body pt-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-semibold mb-0 text-nowrap"><i class="bi bi-clock-history me-1"></i>Pengaduan Terakhir</h6>
                                    @if ($recent->isNotEmpty())
                                        <a href="{{ route('general.sigap.history') }}" class="small text-nowrap">Lihat semua</a>
                                    @endif
                                </div>
                                @if ($recent->isEmpty())
                                    <p class="text-muted small mb-0">Anda belum pernah mengirim pengaduan.</p>
                                @else
                                    <ul class="sg-side-list">
                                        @foreach ($recent as $item)
                                            <li>
                                                <div class="min-w-0">
                                                    <a href="{{ route('general.sigap.history.show', $item) }}" class="sg-mono small">{{ $item->ticket_id }}</a>
                                                    <small class="d-block text-muted text-truncate"><i class="bi {{ $item->categoryIcon() }} me-1"></i>{{ $item->category }} · {{ $item->room->ruang ?? '-' }}</small>
                                                </div>
                                                <span class="badge rounded-pill flex-shrink-0 {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@push('styles')
    @include('general._sigap-styles')
@endpush

@push('scripts')
    <script>
        (() => {
            const desc = document.getElementById('f-description');
            const count = document.getElementById('desc-count');
            desc.addEventListener('input', () => count.textContent = desc.value.length);
        })();
    </script>
@endpush
