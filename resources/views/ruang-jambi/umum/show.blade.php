@extends('layouts.app')
@section('title', 'Tindak Lanjut ' . $booking->booking_code . ' | MLTI')
@section('content')
    <div class="pagetitle">
        <h1>Tindak Lanjut Pengajuan Ruang Jambi</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('umum.ruang.index') }}">Pengajuan Ruang Jambi</a></li>
                <li class="breadcrumb-item active">{{ $booking->booking_code }}</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                @foreach ($errors->all() as $error)
                    <div><i class="bi bi-x-circle me-1"></i>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <div class="row">
            <div class="col-lg-7">
                @include('ruang-jambi._detail')
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title my-0"><i class="bi bi-wrench-adjustable me-1"></i>Form Tindak Lanjut</h5>
                        <a href="{{ route('umum.ruang.index') }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
                    </div>
                    <div class="card-body pt-3">
                        @if ($booking->status === 'menunggu')
                            @if ($conflicts)
                                <div class="alert alert-danger small">
                                    <strong><i class="bi bi-exclamation-octagon me-1"></i>Tidak dapat disetujui saat ini:</strong>
                                    <ul class="mb-0 ps-3">@foreach ($conflicts as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                                </div>
                            @else
                                <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Ruang tersedia pada jadwal ini.</div>
                            @endif
                            @if ($competing->isNotEmpty())
                                <div class="alert alert-warning small">
                                    <strong>Pengajuan lain yang menunggu di slot yang sama:</strong>
                                    <ul class="mb-0 ps-3">
                                        @foreach ($competing as $other)
                                            <li><a href="{{ route('umum.ruang.show', $other) }}">{{ $other->booking_code }}</a> · {{ $other->title }} ({{ $other->starts_at->format('H:i') }}–{{ $other->ends_at->format('H:i') }}, {{ $other->requester->name ?? '-' }})</li>
                                        @endforeach
                                    </ul>
                                    <small class="d-block mt-1">Bila pengajuan ini disetujui, pengajuan tersebut tidak dapat disetujui lagi.</small>
                                </div>
                            @endif

                            <form method="POST" id="review-form">
                                @csrf
                                <label class="form-label fw-bold" for="note">Catatan untuk pemohon</label>
                                <textarea id="note" name="note" rows="4" class="form-control mb-1" placeholder="Wajib diisi untuk penolakan dan permintaan revisi.">{{ old('note') }}</textarea>
                                <small class="text-muted d-block mb-3">Catatan dikirim ke pemohon sebagai notifikasi.</small>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-success" formaction="{{ route('umum.ruang.approve', $booking) }}" @disabled($conflicts)>
                                        <i class="bi bi-check-circle me-1"></i>Setujui</button>
                                    <div class="row g-2">
                                        <div class="col"><button class="btn btn-outline-warning w-100" formaction="{{ route('umum.ruang.revision', $booking) }}" data-need-note>
                                            <i class="bi bi-arrow-repeat me-1"></i>Minta Revisi</button></div>
                                        <div class="col"><button class="btn btn-outline-danger w-100" formaction="{{ route('umum.ruang.reject', $booking) }}" data-need-note>
                                            <i class="bi bi-x-circle me-1"></i>Tolak</button></div>
                                    </div>
                                </div>
                            </form>
                        @elseif ($booking->isCancellable())
                            <p class="small text-muted">Pengajuan berstatus <strong>{{ $booking->statusLabel() }}</strong>. Anda masih dapat membatalkannya bila ruang tidak dapat dipakai.</p>
                            <form method="POST" action="{{ route('umum.ruang.cancel', $booking) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                @csrf
                                <textarea name="note" rows="3" class="form-control mb-2" placeholder="Alasan pembatalan (disarankan)"></textarea>
                                <button class="btn btn-outline-danger w-100"><i class="bi bi-slash-circle me-1"></i>Batalkan Pengajuan</button>
                            </form>
                        @else
                            <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Pengajuan berstatus <strong>{{ $booking->statusLabel() }}</strong>; tidak ada tindak lanjut yang diperlukan.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('ruang-jambi._styles')
@endpush

@push('scripts')
    <script>
        document.querySelectorAll('#review-form [data-need-note]').forEach(btn => btn.addEventListener('click', e => {
            const note = document.getElementById('note');
            if (!note.value.trim()) {
                e.preventDefault();
                note.classList.add('is-invalid');
                note.focus();
            }
        }));
    </script>
@endpush
