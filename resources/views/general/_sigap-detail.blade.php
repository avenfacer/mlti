{{-- Detail pengaduan SIGAP + progres penanganan. Dipakai halaman pelapor dan Tim Umum. --}}
@php
    $handled = $complaint->status !== 'menunggu';
    $closed = in_array($complaint->status, ['selesai', 'ditolak'], true);
    $rejected = $complaint->status === 'ditolak';
@endphp
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title my-0"><i class="bi bi-info-circle me-1"></i>Detail Pengaduan</h5>
        <span class="badge rounded-pill {{ $complaint->statusBadgeClass() }}">{{ $complaint->statusLabel() }}</span>
    </div>
    <div class="card-body pt-3">
        <div class="sg-code mb-3">
            <small>Nomor Tiket</small>
            <strong>{{ $complaint->ticket_id }}</strong>
        </div>
        <table class="table table-borderless sg-detail mb-0">
            <tr><th>Jenis kerusakan</th><td class="fw-semibold"><i class="bi {{ $complaint->categoryIcon() }} me-1 sg-icon"></i>{{ $complaint->category }}</td></tr>
            <tr><th>Lokasi</th><td><i class="bi bi-door-open me-1 text-muted"></i>{{ $complaint->room->ruang ?? '-' }}
                @if ($complaint->location)
                    <small class="d-block text-muted">{{ $complaint->location }}</small>
                @endif
            </td></tr>
            <tr><th>Deskripsi</th><td style="white-space: pre-line">{{ $complaint->description }}</td></tr>
            @if ($showReporter ?? false)
                <tr><th>Pelapor</th><td>{{ $complaint->reporter->name ?? '-' }}<small class="d-block text-muted">{{ $complaint->reporter->jabatan ?? '' }}</small></td></tr>
            @endif
            <tr><th>Tanggal lapor</th><td>{{ $complaint->created_at->format('d M Y') }}<small class="d-block text-muted">{{ $complaint->created_at->format('H:i') }} WIB</small></td></tr>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title my-0"><i class="bi bi-clock-history me-1"></i>Progres Penanganan</h5>
    </div>
    <div class="card-body pt-3">
        <ul class="sg-timeline">
            <li class="done">
                <span class="sg-dot bg-sg-primary"><i class="bi bi-send"></i></span>
                <div>
                    <strong>Pengaduan dikirim</strong> <small class="text-muted">oleh {{ $complaint->reporter->name ?? 'pelapor' }}</small>
                    <small class="d-block text-muted">{{ $complaint->created_at->format('d M Y, H:i') }}</small>
                </div>
            </li>
            <li class="{{ $handled ? 'done' : 'pending' }}">
                <span class="sg-dot {{ $handled ? 'bg-primary' : '' }}"><i class="bi bi-gear"></i></span>
                <div>
                    <strong>Ditindaklanjuti Tim Umum</strong>
                    @if ($handled && $complaint->handler)
                        <small class="text-muted">oleh {{ $complaint->handler->name }}</small>
                    @endif
                    <small class="d-block text-muted">{{ $handled ? 'Pengaduan sudah ditangani petugas.' : 'Menunggu ditinjau petugas.' }}</small>
                </div>
            </li>
            <li class="{{ $closed ? 'done' : 'pending' }}">
                <span class="sg-dot {{ $closed ? ($rejected ? 'bg-danger' : 'bg-success') : '' }}"><i class="bi {{ $rejected ? 'bi-x-lg' : 'bi-check2' }}"></i></span>
                <div>
                    <strong>{{ $rejected ? 'Ditolak' : 'Selesai' }}</strong>
                    @if ($complaint->resolved_at)
                        <small class="d-block text-muted">{{ $complaint->resolved_at->format('d M Y, H:i') }}</small>
                    @endif
                    @if ($complaint->admin_notes)
                        <div class="sg-note"><i class="bi bi-chat-left-text me-1"></i>{{ $complaint->admin_notes }}</div>
                    @endif
                </div>
            </li>
        </ul>
    </div>
</div>
