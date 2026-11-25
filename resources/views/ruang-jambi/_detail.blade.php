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
            <tr><th>Nama acara</th><td class="fw-semibold">{{ $booking->title }}</td></tr>
            <tr><th>Ruangan</th><td>{{ $booking->room->ruang ?? '-' }}
                @if ($booking->meetingRoom)
                    <small class="d-block text-muted">{{ $booking->meetingRoom->location ? $booking->meetingRoom->location . ' · ' : '' }}Kapasitas {{ $booking->meetingRoom->capacity }} orang</small>
                @endif
            </td></tr>
            <tr><th>Jadwal</th><td>{{ $booking->starts_at->translatedFormat('l, d F Y') }}<small class="d-block text-muted">{{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }} WIB</small></td></tr>
            <tr><th>Peserta</th><td>{{ $booking->participants }} orang
                @if ($booking->meetingRoom && $booking->participants > $booking->meetingRoom->capacity)
                    <small class="d-block text-warning-emphasis fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Melebihi kapasitas ruangan ({{ $booking->meetingRoom->capacity }} orang).</small>
                @endif
            </td></tr>
            <tr><th>Pemohon</th><td>{{ $booking->requester->name ?? '-' }}<small class="d-block text-muted">{{ $booking->requester->jabatan ?? '' }}</small></td></tr>
            @if ($layout = $booking->layoutInfo())
                <tr><th>Layout ruangan</th><td><i class="bi {{ $layout[2] }} me-1"></i>{{ $layout[0] }}
                    @if ($layout[1])<small class="d-block text-muted">{{ $layout[1] }}</small>@endif
                </td></tr>
            @endif
            @if ($booking->officialsLabel())
                <tr><th>Pejabat di depan</th><td>{{ $booking->officialsLabel() }}</td></tr>
            @endif
            <tr><th>Penanggung jawab acara</th><td>{{ $booking->pic ?: '-' }}</td></tr>
            @if ($committee = $booking->committeeInfo())
                <tr><th>Susunan kepanitiaan</th><td>{{ $committee[0] }}
                    <a href="{{ $committee[1] }}" target="_blank" rel="noopener" class="small ms-1"><i class="bi bi-box-arrow-up-right"></i> Template</a>
                </td></tr>
            @endif
            @if ($booking->memo_path || $booking->letter_path)
                <tr><th>Dokumen</th><td>
                    @foreach (\App\Models\Booking::DOCUMENTS as $key => [$pathColumn, $nameColumn, $label])
                        @if ($booking->{$pathColumn})
                            <a class="rj-doc" href="{{ route('ruang.document', [$booking, $key]) }}" target="_blank" title="{{ $booking->{$nameColumn} }}">
                                <i class="bi bi-file-earmark-text"></i><span><strong>{{ $label }}</strong> <small class="text-muted">{{ \Illuminate\Support\Str::limit($booking->{$nameColumn}, 28) }}</small></span>
                            </a>
                        @endif
                    @endforeach
                </td></tr>
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
