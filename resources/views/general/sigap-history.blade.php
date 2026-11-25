@extends('layouts.app')
@section('title', 'Pengaduan SIGAP Saya | MLTI')
@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <h1>Pengaduan SIGAP Saya</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('general.sigap') }}">SIGAP</a></li>
                    <li class="breadcrumb-item active">Pengaduan Saya</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('general.sigap') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Buat Pengaduan</a>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tiket</th>
                                <th>Kerusakan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($complaints as $item)
                                <tr>
                                    <td><a href="{{ route('general.sigap.history.show', $item) }}" class="sg-mono">{{ $item->ticket_id }}</a></td>
                                    <td style="min-width: 220px"><i class="bi {{ $item->categoryIcon() }} me-1 sg-icon"></i>{{ $item->category }}
                                        <small class="d-block text-muted"><i class="bi bi-door-open me-1"></i>{{ $item->locationLabel() }}</small></td>
                                    <td class="text-nowrap">{{ $item->created_at->format('d M Y') }}<small class="d-block text-muted">{{ $item->created_at->format('H:i') }}</small></td>
                                    <td><span class="badge rounded-pill {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
                                    <td class="text-center"><a href="{{ route('general.sigap.history.show', $item) }}" class="btn btn-sm btn-secondary text-nowrap"><i class="bi bi-eye"></i> Detail</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>Belum ada pengaduan.
                                        <a href="{{ route('general.sigap') }}">Buat pengaduan sekarang</a>.
                                    </td>
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
