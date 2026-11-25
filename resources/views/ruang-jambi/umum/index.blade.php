@extends('layouts.app')
@section('title', 'Pengajuan Ruang Jambi | MLTI')
@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <h1>Kelola Pengajuan Ruang Jambi</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                    <li class="breadcrumb-item active">Pengajuan Ruang Jambi</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('umum.ruang.manage') }}" class="btn btn-secondary"><i class="bi bi-gear me-1"></i>Kelola Ruang & Fasilitas</a>
    </div>
    <section class="section">
        <div class="card">
            <ul class="nav rj-tabs mb-3">
                <li class="nav-item"><a class="nav-link {{ $status ? '' : 'active' }}" href="{{ route('umum.ruang.index', request()->only('q')) }}">Semua <span class="badge bg-rj-primary">{{ $counts->sum() }}</span></a></li>
                @foreach (\App\Models\Booking::STATUS_LABELS as $key => [$label, $color])
                    <li class="nav-item"><a class="nav-link {{ $status === $key ? 'active' : '' }}" href="{{ route('umum.ruang.index', ['status' => $key] + request()->only('q')) }}">{{ $label }}
                        @if ($counts[$key] ?? 0)
                            <span class="badge bg-{{ $color }}">{{ $counts[$key] }}</span>
                        @endif
                    </a></li>
                @endforeach
            </ul>
            <div class="card-body">
                <form method="GET" class="d-flex justify-content-end gap-2 mb-3">
                    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" style="max-width: 280px" placeholder="Cari kode / kegiatan" aria-label="Cari pengajuan">
                    <button class="btn btn-primary" aria-label="Cari"><i class="bi bi-search"></i></button>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 rj-table">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Pemohon</th>
                                <th>Kegiatan / Ruang</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td><span class="rj-mono">{{ $booking->booking_code }}</span></td>
                                    <td style="max-width: 220px"><strong>{{ $booking->requester->name ?? '-' }}</strong><small class="d-block text-muted text-truncate" title="{{ $booking->requester->jabatan ?? '' }}">{{ $booking->requester->jabatan ?? '' }}</small></td>
                                    <td style="min-width: 170px">{{ $booking->title }}<small class="d-block text-muted text-nowrap"><i class="bi bi-door-open me-1"></i>{{ $booking->room->ruang ?? '-' }} · {{ $booking->participants ?? '-' }} orang</small></td>
                                    <td class="text-nowrap">{{ $booking->starts_at->translatedFormat('d M Y') }}<small class="d-block text-muted">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</small></td>
                                    <td>@include('ruang-jambi._status-badge')</td>
                                    <td class="text-center"><a href="{{ route('umum.ruang.show', $booking) }}" class="btn btn-sm {{ $booking->status === 'menunggu' ? 'btn-primary' : 'btn-secondary' }} text-nowrap">
                                        <i class="bi {{ $booking->status === 'menunggu' ? 'bi-pencil-square' : 'bi-eye' }}"></i> {{ $booking->status === 'menunggu' ? 'Tindak Lanjut' : 'Detail' }}</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-2 d-block mb-1"></i>Belum ada pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $bookings->links() }}</div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
    @include('ruang-jambi._styles')
@endpush
