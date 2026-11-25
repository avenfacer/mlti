@extends('layouts.app')
@section('title', 'Pengajuan ' . $booking->booking_code . ' | MLTI')
@section('content')
    <div class="pagetitle">
        <h1>Detail Pengajuan Ruang Jambi</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('ruang.create') }}">Ruang Jambi</a></li>
                <li class="breadcrumb-item"><a href="{{ route('ruang.index') }}">Pengajuan Saya</a></li>
                <li class="breadcrumb-item active">{{ $booking->booking_code }}</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        @if ($errors->has('booking'))
            <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first('booking') }}</div>
        @endif
        @if ($booking->status === 'perlu_revisi')
            <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-exclamation-triangle-fill me-1"></i>Tim Umum meminta revisi pengajuan ini.</span>
                <a href="{{ route('ruang.edit', $booking) }}" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square me-1"></i>Revisi Sekarang</a>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-8">
                @include('ruang-jambi._detail')
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title my-0"><i class="bi bi-lightning me-1"></i>Aksi</h5></div>
                    <div class="card-body pt-3 d-grid gap-2">
                        @if ($booking->isEditable())
                            <a href="{{ route('ruang.edit', $booking) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i>{{ $booking->status === 'perlu_revisi' ? 'Revisi Pengajuan' : 'Ubah Pengajuan' }}</a>
                        @endif
                        @if ($booking->isCancellable())
                            <form method="POST" action="{{ route('ruang.cancel', $booking) }}" onsubmit="return confirm('Batalkan pengajuan ini?')">
                                @csrf
                                <textarea name="note" rows="2" class="form-control form-control-sm mb-2" placeholder="Alasan pembatalan (opsional)"></textarea>
                                <button class="btn btn-outline-danger w-100"><i class="bi bi-slash-circle me-1"></i>Batalkan Pengajuan</button>
                            </form>
                        @endif
                        @if (!$booking->isEditable() && !$booking->isCancellable())
                            <p class="text-muted small mb-0">Tidak ada aksi yang tersedia untuk pengajuan ini.</p>
                        @endif
                        <a href="{{ route('ruang.create') }}" class="btn btn-light"><i class="bi bi-plus-lg me-1"></i>Ajukan Ruang Lain</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('ruang-jambi._styles')
@endpush
