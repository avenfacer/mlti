@extends('layouts.app')
@section('title', 'Kelola Ruang Jambi | MLTI')
@php $permanent = $facilities->where('type', \App\Models\Facility::TETAP); @endphp
@section('content')
    <div class="pagetitle">
        <h1>Kelola Ruang Jambi</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('umum.ruang.index') }}">Pengajuan Ruang Jambi</a></li>
                <li class="breadcrumb-item active">Kelola</li>
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

        <div class="card">
                <ul class="nav rj-tabs mb-3">
                    @foreach (['ruang' => ['Ruang Rapat', 'bi-door-open'], 'fasilitas' => ['Fasilitas', 'bi-tools'], 'maintenance' => ['Maintenance', 'bi-cone-striped']] as $key => [$label, $icon])
                        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('umum.ruang.manage', ['tab' => $key]) }}"><i class="bi {{ $icon }} me-1"></i>{{ $label }}</a></li>
                    @endforeach
                </ul>
            <div class="card-body">
                @if ($tab === 'ruang')
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <small class="text-muted">Ruang rapat diambil dari daftar ruang kantor. Menghapus dari daftar ini tidak memengaruhi data ruang atau perangkat.</small>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal" data-mode="create" @disabled($availableRooms->isEmpty())><i class="bi bi-plus-lg me-1"></i>Tambah Ruang Rapat</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Ruang</th><th>Kapasitas</th><th>Fasilitas tetap</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($meetingRooms as $room)
                                    <tr>
                                        <td><strong>{{ $room->name }}</strong><small class="d-block text-muted">{{ $room->location }}</small></td>
                                        <td>{{ $room->capacity }} orang</td>
                                        <td>@forelse ($room->facilities as $f)<span class="rj-chip">{{ $f->name }}</span>@empty<span class="text-muted">-</span>@endforelse</td>
                                        <td><span class="badge bg-{{ $room->active ? 'success' : 'secondary' }}">{{ $room->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#roomModal" data-mode="edit"
                                                data-room="{{ json_encode(['name' => $room->name, 'location' => $room->location, 'capacity' => $room->capacity, 'description' => $room->description, 'facility_ids' => $room->facilities->pluck('id')->map(fn ($id) => (int) $id)]) }}"
                                                data-action="{{ route('umum.ruang.manage.rooms.update', $room) }}"><i class="bi bi-pencil"></i></button>
                                            <form method="POST" action="{{ route('umum.ruang.manage.rooms.toggle', $room) }}" class="d-inline">@csrf
                                                <button class="btn btn-sm btn-outline-{{ $room->active ? 'warning' : 'success' }}" title="{{ $room->active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="bi bi-power"></i></button></form>
                                            <form method="POST" action="{{ route('umum.ruang.manage.rooms.destroy', $room) }}" class="d-inline" onsubmit="return confirm('Hapus ruang ini dari daftar ruang rapat?')">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus dari ruang rapat"><i class="bi bi-trash"></i></button></form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada ruang rapat.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($tab === 'fasilitas')
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <small class="text-muted"><strong>Tetap</strong>: terpasang di ruang. <strong>Tambahan</strong>: dapat diminta pemohon saat pengajuan.</small>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#facilityModal" data-mode="create"><i class="bi bi-plus-lg me-1"></i>Tambah Fasilitas</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Nama</th><th>Jenis</th><th>Stok</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($facilities as $facility)
                                    <tr>
                                        <td><strong>{{ $facility->name }}</strong><small class="d-block text-muted">{{ $facility->description }}</small></td>
                                        <td><span class="badge bg-{{ $facility->type === 'tetap' ? 'primary' : 'info text-dark' }}">{{ ucfirst($facility->type) }}</span></td>
                                        <td>{{ $facility->stock ?? '-' }}</td>
                                        <td><span class="badge bg-{{ $facility->active ? 'success' : 'secondary' }}">{{ $facility->active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#facilityModal" data-mode="edit"
                                                data-facility="{{ json_encode($facility->only(['name', 'type', 'description', 'stock'])) }}"
                                                data-action="{{ route('umum.ruang.manage.facilities.update', $facility) }}"><i class="bi bi-pencil"></i></button>
                                            <form method="POST" action="{{ route('umum.ruang.manage.facilities.toggle', $facility) }}" class="d-inline">@csrf
                                                <button class="btn btn-sm btn-outline-{{ $facility->active ? 'warning' : 'success' }}" title="{{ $facility->active ? 'Nonaktifkan' : 'Aktifkan' }}"><i class="bi bi-power"></i></button></form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada fasilitas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <small class="text-muted">Ruang tidak dapat diajukan maupun disetujui selama jadwal maintenance.</small>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#blockModal" @disabled($meetingRooms->isEmpty())><i class="bi bi-plus-lg me-1"></i>Tambah Jadwal Maintenance</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Ruang</th><th>Mulai</th><th>Selesai</th><th>Alasan</th><th>Dibuat oleh</th><th class="text-center">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($blocks as $block)
                                    <tr class="{{ $block->ends_at->isPast() ? 'text-muted' : '' }}">
                                        <td>{{ $block->room->ruang ?? '-' }}</td>
                                        <td>{{ $block->starts_at->translatedFormat('d M Y, H:i') }}</td>
                                        <td>{{ $block->ends_at->translatedFormat('d M Y, H:i') }}</td>
                                        <td>{{ $block->reason }}</td>
                                        <td>{{ $block->creator->name ?? '-' }}</td>
                                        <td class="text-center">
                                            <form method="POST" action="{{ route('umum.ruang.manage.blocks.destroy', $block) }}" onsubmit="return confirm('Hapus jadwal maintenance ini?')">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal maintenance.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Modal ruang rapat --}}
    <div class="modal fade" id="roomModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" data-create-action="{{ route('umum.ruang.manage.rooms.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header"><h5 class="modal-title">Ruang Rapat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3" data-only="create">
                        <label class="form-label fw-semibold">Ruang *</label>
                        <select name="room_id" class="form-select">
                            @foreach ($availableRooms as $r)
                                <option value="{{ $r->id }}">{{ $r->ruang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" data-only="edit"><label class="form-label fw-semibold">Ruang</label><input class="form-control" data-field="name" disabled></div>
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label fw-semibold">Lokasi</label><input name="location" class="form-control" placeholder="Contoh: Lantai 2"></div>
                        <div class="col-md-4"><label class="form-label fw-semibold">Kapasitas *</label><input type="number" name="capacity" min="2" class="form-control" required></div>
                        <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label><textarea name="description" rows="2" class="form-control"></textarea></div>
                        <div class="col-12"><label class="form-label fw-semibold d-block">Fasilitas tetap</label>
                            @foreach ($permanent as $f)
                                <label class="form-check form-check-inline"><input type="checkbox" class="form-check-input" name="facility_ids[]" value="{{ $f->id }}"><span class="form-check-label">{{ $f->name }}</span></label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>

    {{-- Modal fasilitas --}}
    <div class="modal fade" id="facilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content" data-create-action="{{ route('umum.ruang.manage.facilities.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header"><h5 class="modal-title">Fasilitas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-md-8"><label class="form-label fw-semibold">Nama *</label><input name="name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Jenis *</label>
                        <select name="type" class="form-select"><option value="tambahan">Tambahan</option><option value="tetap">Tetap</option></select></div>
                    <div class="col-md-8"><label class="form-label fw-semibold">Deskripsi</label><input name="description" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Stok</label><input type="number" min="0" name="stock" class="form-control" placeholder="Opsional"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>

    {{-- Modal maintenance --}}
    <div class="modal fade" id="blockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('umum.ruang.manage.blocks.store') }}" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Jadwal Maintenance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label fw-semibold">Ruang *</label>
                        <select name="room_id" class="form-select" required>
                            @foreach ($meetingRooms as $r)
                                <option value="{{ $r->room_id }}" @selected(old('room_id') == $r->room_id)>{{ $r->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Tanggal mulai *</label><input type="date" name="date" value="{{ old('date') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Tanggal selesai</label><input type="date" name="end_date" value="{{ old('end_date') }}" class="form-control"><small class="text-muted">Kosongkan bila satu hari.</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Jam mulai *</label><input type="time" name="start" value="{{ old('start', '07:30') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Jam selesai *</label><input type="time" name="end" value="{{ old('end', '17:00') }}" class="form-control" required></div>
                    <div class="col-12"><label class="form-label fw-semibold">Alasan *</label><input name="reason" value="{{ old('reason') }}" class="form-control" placeholder="Contoh: Perbaikan AC" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    @include('ruang-jambi._styles')
@endpush

@push('scripts')
    <script>
        // Satu modal untuk tambah & ubah: isi field dari data-* tombol pemicu.
        ['roomModal', 'facilityModal'].forEach(id => {
            const modal = document.getElementById(id);
            modal.addEventListener('show.bs.modal', e => {
                const btn = e.relatedTarget, form = modal.querySelector('form');
                const editing = btn && btn.dataset.mode === 'edit';
                const data = editing ? JSON.parse(btn.dataset.room || btn.dataset.facility) : {};
                form.reset();
                form.action = editing ? btn.dataset.action : form.dataset.createAction;
                form.querySelector('[name=_method]').value = editing ? 'PUT' : 'POST';
                form.querySelectorAll('[data-only]').forEach(el => {
                    el.classList.toggle('d-none', el.dataset.only !== (editing ? 'edit' : 'create'));
                    el.querySelectorAll('select').forEach(s => s.disabled = editing);
                });
                Object.entries(data).forEach(([key, value]) => {
                    if (key === 'facility_ids') {
                        form.querySelectorAll('[name="facility_ids[]"]').forEach(c => c.checked = value.includes(Number(c.value)));
                        return;
                    }
                    const field = form.querySelector(`[name="${key}"]`) || form.querySelector(`[data-field="${key}"]`);
                    if (field) field.value = value ?? '';
                });
            });
        });
        @if (session('open_modal'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById(@json(session('open_modal')))).show();
        @endif
    </script>
@endpush
