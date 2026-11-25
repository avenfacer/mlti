@extends('layouts.app')
@section('title', 'Tindak Lanjut ' . $complaint->ticket_id . ' | MLTI')
@section('content')
    <div class="pagetitle">
        <h1>Tindak Lanjut Pengaduan SIGAP</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="breadcrumb-item"><a href="{{ route('umum.complaints') }}">Laporan SIGAP</a></li>
                <li class="breadcrumb-item active">{{ $complaint->ticket_id }}</li>
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
                @include('general._sigap-detail', ['showReporter' => true])
            </div>
            <div class="col-lg-5">
                <div class="card sg-sticky">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title my-0"><i class="bi bi-wrench-adjustable me-1"></i>Form Tindak Lanjut</h5>
                        <a href="{{ route('umum.complaints') }}" class="btn btn-sm btn-secondary">Kembali</a>
                    </div>
                    <div class="card-body pt-3">
                        <form method="POST" action="{{ route('umum.complaints.update', $complaint) }}" id="followup-form">
                            @csrf
                            @method('PATCH')
                            <label class="form-label fw-bold">Status pengaduan</label>
                            <div class="row g-2 mb-3" role="radiogroup" aria-label="Status pengaduan">
                                @foreach ($statuses as $value => $label)
                                    <div class="col-6">
                                        <label class="sg-status-opt">
                                            <input type="radio" name="status" value="{{ $value }}" @checked(old('status', $complaint->status) === $value) required>
                                            <span class="rounded-circle d-inline-block {{ (new \App\Models\GeneralComplaint(['status' => $value]))->statusBadgeClass() }}" style="width: 10px; height: 10px"></span>
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <label class="form-label fw-bold" for="admin_notes">Catatan untuk pelapor</label>
                            <textarea id="admin_notes" name="admin_notes" rows="5" maxlength="2000"
                                class="form-control mb-1 @error('admin_notes') is-invalid @enderror"
                                placeholder="Contoh: Teknisi AC dijadwalkan datang hari Kamis pagi.">{{ old('admin_notes', $complaint->admin_notes) }}</textarea>
                            @error('admin_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted d-block mb-3">Catatan ditampilkan kepada pelapor. Wajib diisi jika pengaduan ditolak.</small>
                            <button class="btn btn-primary w-100 py-2"><i class="bi bi-check2-circle me-1"></i>Simpan &amp; Perbarui Status</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('general._sigap-styles')
@endpush

@push('scripts')
    <script>
        document.getElementById('followup-form').addEventListener('submit', e => {
            const status = e.target.querySelector('input[name=status]:checked');
            const note = document.getElementById('admin_notes');
            if (status && status.value === 'ditolak' && !note.value.trim()) {
                e.preventDefault();
                note.classList.add('is-invalid');
                note.focus();
            }
        });
    </script>
@endpush
