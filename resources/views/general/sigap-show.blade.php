@extends('layouts.app')
@section('title', 'Pengaduan ' . $complaint->ticket_id . ' | MLTI')
@section('content')
    <div class="pagetitle">
        <h1>Detail Pengaduan SIGAP</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('general.sigap') }}">SIGAP</a></li>
                <li class="breadcrumb-item"><a href="{{ route('general.sigap.history') }}">Pengaduan Saya</a></li>
                <li class="breadcrumb-item active">{{ $complaint->ticket_id }}</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="row">
            <div class="col-lg-8">
                @include('general._sigap-detail')
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="card-title my-0"><i class="bi bi-lightning me-1"></i>Aksi</h5></div>
                    <div class="card-body pt-3 d-grid gap-2">
                        @if ($complaint->isOpen())
                            <p class="small text-muted mb-1"><i class="bi bi-info-circle me-1"></i>Tim Umum akan memperbarui status pengaduan ini. Anda dapat memantaunya di halaman ini.</p>
                        @endif
                        <a href="{{ route('general.sigap') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Pengaduan Baru</a>
                        <a href="{{ route('general.sigap.history') }}" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>Kembali ke Riwayat</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('general._sigap-styles')
@endpush
