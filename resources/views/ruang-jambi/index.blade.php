@extends('layouts.app')
@section('title', 'Pengajuan Ruang Jambi Saya | MLTI')
@section('content')
    <div class="pagetitle d-flex justify-content-between align-items-end flex-wrap gap-2">
        <div>
            <h1>Pengajuan Ruang Jambi Saya</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('ruang.create') }}">Ruang Jambi</a></li>
                    <li class="breadcrumb-item active">Pengajuan Saya</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('ruang.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Ajukan Ruang</a>
    </div>
    <section class="section">
        <div class="card">
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Kegiatan</th>
                                <th>Jadwal</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td><a href="{{ route('ruang.show', $booking) }}" class="rj-mono">{{ $booking->booking_code }}</a></td>
                                    <td>{{ $booking->title }}<small class="d-block text-muted"><i class="bi bi-door-open me-1"></i>{{ $booking->room->ruang ?? '-' }}</small></td>
                                    <td class="text-nowrap">{{ $booking->starts_at->translatedFormat('d M Y') }}<small class="d-block text-muted">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</small></td>
                                    <td>@include('ruang-jambi._status-badge')</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('ruang.show', $booking) }}" class="btn btn-sm btn-secondary"><i class="bi bi-eye"></i> Detail</a>
                                        @if ($booking->isEditable())
                                            <a href="{{ route('ruang.edit', $booking) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> {{ $booking->status === 'perlu_revisi' ? 'Revisi' : 'Ubah' }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>Belum ada pengajuan.
                                        <a href="{{ route('ruang.create') }}">Ajukan ruang sekarang</a>.
                                    </td>
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
