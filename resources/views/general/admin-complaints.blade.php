@extends('layouts.app')
@section('title', 'Laporan SIGAP | MLTI')
@php $status = request('status'); @endphp
@section('content')
    <div class="pagetitle">
        <h1>Kelola Pengaduan SIGAP</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="breadcrumb-item active">Laporan SIGAP</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        <div class="card">
            <ul class="nav sg-tabs mb-3">
                <li class="nav-item"><a class="nav-link {{ $status ? '' : 'active' }}" href="{{ route('umum.complaints', request()->only('q', 'category')) }}">Semua <span class="badge bg-sg-primary">{{ $counts->sum() }}</span></a></li>
                @foreach ($statuses as $value => $label)
                    @php $badge = (new \App\Models\GeneralComplaint(['status' => $value]))->statusBadgeClass(); @endphp
                    <li class="nav-item"><a class="nav-link {{ $status === $value ? 'active' : '' }}" href="{{ route('umum.complaints', ['status' => $value] + request()->only('q', 'category')) }}">{{ $label }}
                        @if ($counts[$value] ?? 0)
                            <span class="badge {{ $badge }}">{{ $counts[$value] }}</span>
                        @endif
                    </a></li>
                @endforeach
            </ul>
            <div class="card-body">
                <form method="GET" class="d-flex justify-content-end flex-wrap gap-2 mb-3">
                    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                    <select name="category" class="form-select" style="max-width: 220px" aria-label="Jenis kerusakan" onchange="this.form.submit()">
                        <option value="">Semua jenis kerusakan</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" style="max-width: 280px" placeholder="Cari tiket / pelapor / lokasi" aria-label="Cari pengaduan">
                    <button class="btn btn-primary" aria-label="Cari"><i class="bi bi-search"></i></button>
                    @if (request()->hasAny(['q', 'category']) && (request('q') || request('category')))
                        <a href="{{ route('umum.complaints', $status ? ['status' => $status] : []) }}" class="btn btn-light" title="Reset pencarian"><i class="bi bi-x-lg"></i></a>
                    @endif
                </form>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tiket</th>
                                <th>Pelapor</th>
                                <th>Kerusakan / Lokasi</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($complaints as $item)
                                <tr>
                                    <td class="text-nowrap"><span class="sg-mono">{{ $item->ticket_id }}</span><small class="d-block text-muted">{{ $item->created_at->format('d M Y, H:i') }}</small></td>
                                    <td style="min-width: 150px; max-width: 200px"><strong>{{ $item->reporter->name ?? '-' }}</strong><small class="d-block text-muted text-truncate" title="{{ $item->reporter->jabatan ?? '' }}">{{ $item->reporter->jabatan ?? '' }}</small></td>
                                    <td style="min-width: 220px; max-width: 320px">
                                        <i class="bi {{ $item->categoryIcon() }} me-1 sg-icon"></i>{{ $item->category }}
                                        <small class="d-block text-muted"><i class="bi bi-door-open me-1"></i>{{ $item->locationLabel() }}</small>
                                        <small class="d-block text-muted text-truncate" title="{{ $item->description }}">{{ $item->description }}</small>
                                    </td>
                                    <td><span class="badge rounded-pill {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
                                    <td class="text-center"><a href="{{ route('umum.complaints.show', $item) }}" class="btn btn-sm {{ $item->isOpen() ? 'btn-primary' : 'btn-secondary' }} text-nowrap">
                                        <i class="bi {{ $item->isOpen() ? 'bi-pencil-square' : 'bi-eye' }}"></i> {{ $item->isOpen() ? 'Tindak Lanjut' : 'Detail' }}</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-1"></i>Belum ada pengaduan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $complaints->links() }}</div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('general._sigap-styles')
@endpush
