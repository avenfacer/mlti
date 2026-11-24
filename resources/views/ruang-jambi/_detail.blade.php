{{-- Detail pengajuan Ruang Jambi + riwayat. Dipakai halaman pegawai dan Tim Umum. --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title my-0"><i class="bi bi-info-circle me-1"></i>Detail Pengajuan</h5>
        @include('ruang-jambi._status-badge')
    </div>
    <div class="card-body pt-3">
        <div class="rj-code mb-3">
            <small>Kode Referensi</small>
            <strong>{{ $booking->booking_code }}</strong>
        </div>
        <table class="table table-borderless rj-detail mb-0">
            <tr><th>Kegiatan</th><td class="fw-semibold">{{ $booking->title }}</td></tr>
            @if ($booking->purpose)
                <tr><th>Agenda</th><td style="white-space: pre-line">{{ $booking->purpose }}</td></tr>
            @endif
            <tr><th>Ruangan</th><td>{{ $booking->room->ruang ?? '-' }}
                @if ($booking->meetingRoom)
                    <small class="d-block text-muted">{{ $booking->meetingRoom->location ? $booking->meetingRoom->location . ' · ' : '' }}Kapasitas {{ $booking->meetingRoom->capacity }} orang</small>
                @endif
            </td></tr>
            <tr><th>Jadwal</th><td>{{ $booking->starts_at->translatedFormat('l, d F Y') }}<small class="d-block text-muted">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }} WIB</small></td></tr>
            <tr><th>Peserta</th><td>{{ $booking->participants }} orang</td></tr>
            <tr><th>Pemohon</th><td>{{ $booking->requester->name ?? '-' }}<small class="d-block text-muted">{{ $booking->requester->jabatan ?? '' }}</small></td></tr>
            <tr><th>Penanggung jawab</th><td>{{ $booking->pic ?: '-' }}</td></tr>
            <tr><th>Fasilitas tambahan</th><td>
                @forelse ($booking->facilities as $facility)
                    <span class="badge bg-light text-dark border me-1 mb-1"><i class="bi bi-plus-circle me-1"></i>{{ $facility->name }}</span>
                @empty
                    <span class="text-muted">Tidak ada</span>
                @endforelse
                @if ($booking->other_needs)
                    <small class="d-block text-muted mt-1">Lainnya: {{ $booking->other_needs }}</small>
                @endif
            </td></tr>
            @if ($booking->notes)
                <tr><th>Catatan pemohon</th><td style="white-space: pre-line">{{ $booking->notes }}</td></tr>
            @endif
            @if ($booking->admin_notes)
                <tr><th>Catatan Tim Umum</th><td style="white-space: pre-line">{{ $booking->admin_notes }}</td></tr>
            @endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title my-0"><i class="bi bi-clock-history me-1"></i>Riwayat</h5>
    </div>
    <div class="card-body pt-3">
        <ul class="rj-timeline mb-0">
            @foreach ($booking->histories as $history)
                @php [$label, $icon, $color] = $history->label(); @endphp
                <li>
                    <span class="rj-dot bg-{{ $color }}"><i class="bi {{ $icon }}"></i></span>
                    <div>
                        <strong>{{ $label }}</strong> <small class="text-muted">oleh {{ $history->actor_name }}</small>
                        <small class="d-block text-muted">{{ $history->created_at?->translatedFormat('d M Y, H:i') }}</small>
                        @if ($history->note)
                            <div class="rj-note">{{ $history->note }}</div>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>
