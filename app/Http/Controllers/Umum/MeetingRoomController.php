<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Facility;
use App\Models\MaintenanceBlock;
use App\Models\MeetingRoom;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Kelola Ruang Jambi: ruang rapat, fasilitas, dan jadwal maintenance.
 */
class MeetingRoomController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['ruang', 'fasilitas', 'maintenance'], true) ? $request->query('tab') : 'ruang';
        $meetingRooms = MeetingRoom::with(['room', 'facilities'])->get()->sortBy(fn ($r) => $r->name)->values();
        $availableRooms = Room::whereNotIn('id', $meetingRooms->pluck('room_id'))->orderBy('ruang')->get();
        $facilities = Facility::orderBy('type')->orderBy('name')->get();
        $blocks = MaintenanceBlock::with(['room', 'creator'])->where('ends_at', '>=', now()->subDays(30))
            ->orderBy('starts_at')->get();

        return view('ruang-jambi.umum.manage', compact('tab', 'meetingRooms', 'availableRooms', 'facilities', 'blocks'));
    }

    // ---- Ruang rapat ------------------------------------------------------

    public function storeRoom(Request $request)
    {
        $data = $this->validateRoom($request, true);
        $room = MeetingRoom::create($data + ['active' => true]);
        $room->facilities()->sync($data['facility_ids'] ?? []);

        return $this->back('ruang', 'Ruang rapat berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, MeetingRoom $meetingRoom)
    {
        $data = $this->validateRoom($request, false);
        $meetingRoom->update($data);
        $meetingRoom->facilities()->sync($data['facility_ids'] ?? []);

        return $this->back('ruang', 'Data ruang rapat berhasil diperbarui.');
    }

    public function toggleRoom(MeetingRoom $meetingRoom)
    {
        $meetingRoom->update(['active' => ! $meetingRoom->active]);

        return $this->back('ruang', $meetingRoom->active ? 'Ruang rapat diaktifkan.' : 'Ruang rapat dinonaktifkan.');
    }

    public function destroyRoom(MeetingRoom $meetingRoom)
    {
        $hasActive = Booking::where('type', 'room')->where('room_id', $meetingRoom->room_id)
            ->whereIn('status', [Booking::MENUNGGU, Booking::DISETUJUI, Booking::PERLU_REVISI])
            ->where('ends_at', '>', now())->exists();
        if ($hasActive) {
            return $this->back('ruang', 'Ruang masih memiliki pengajuan aktif. Nonaktifkan ruang sebagai gantinya.', 'error');
        }
        $meetingRoom->delete();

        return $this->back('ruang', 'Ruang dihapus dari daftar ruang rapat. Data ruang dan perangkat tidak terpengaruh.');
    }

    private function validateRoom(Request $request, bool $creating): array
    {
        return $request->validate([
            'room_id' => $creating ? ['required', 'integer', Rule::exists('rooms', 'id'), Rule::unique('meeting_rooms', 'room_id')] : ['prohibited'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:2', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'facility_ids' => ['array'],
            'facility_ids.*' => ['integer', Rule::exists('facilities', 'id')->where('type', Facility::TETAP)],
        ], [], ['room_id' => 'ruangan', 'capacity' => 'kapasitas', 'location' => 'lokasi']);
    }

    // ---- Fasilitas --------------------------------------------------------

    public function storeFacility(Request $request)
    {
        Facility::create($this->validateFacility($request) + ['active' => true]);

        return $this->back('fasilitas', 'Fasilitas berhasil ditambahkan.');
    }

    public function updateFacility(Request $request, Facility $facility)
    {
        $facility->update($this->validateFacility($request));

        return $this->back('fasilitas', 'Fasilitas berhasil diperbarui.');
    }

    public function toggleFacility(Facility $facility)
    {
        $facility->update(['active' => ! $facility->active]);

        return $this->back('fasilitas', $facility->active ? 'Fasilitas diaktifkan.' : 'Fasilitas dinonaktifkan.');
    }

    private function validateFacility(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in([Facility::TETAP, Facility::TAMBAHAN])],
            'description' => ['nullable', 'string', 'max:1000'],
            'stock' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ], [], ['name' => 'nama fasilitas', 'type' => 'jenis', 'stock' => 'stok']);
        if ($data['type'] === Facility::TETAP) {
            $data['stock'] = null;
        }

        return $data;
    }

    // ---- Maintenance ------------------------------------------------------

    public function storeBlock(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('meeting_rooms', 'room_id')],
            'date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:255'],
        ], [], ['room_id' => 'ruangan', 'date' => 'tanggal', 'end_date' => 'tanggal selesai', 'start' => 'jam mulai', 'end' => 'jam selesai', 'reason' => 'alasan']);

        $startsAt = Carbon::parse("{$data['date']} {$data['start']}");
        $endsAt = Carbon::parse(($data['end_date'] ?? $data['date'])." {$data['end']}");
        if ($endsAt <= $startsAt) {
            return back()->withErrors(['end' => 'Waktu selesai harus setelah waktu mulai.'])->withInput()->with('open_modal', 'blockModal');
        }

        MaintenanceBlock::create([
            'room_id' => $data['room_id'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $data['reason'],
            'created_by' => $request->user()->nip_lama,
        ]);

        $affected = Booking::overlapping((int) $data['room_id'], $startsAt, $endsAt)
            ->whereIn('status', [Booking::MENUNGGU, Booking::DISETUJUI])->count();
        $message = 'Jadwal maintenance berhasil ditambahkan.'
            .($affected ? " Perhatian: ada {$affected} pengajuan aktif pada rentang tersebut, mohon ditindaklanjuti." : '');

        return $this->back('maintenance', $message);
    }

    public function destroyBlock(MaintenanceBlock $block)
    {
        $block->delete();

        return $this->back('maintenance', 'Jadwal maintenance dihapus.');
    }

    private function back(string $tab, string $message, string $type = 'success')
    {
        return redirect()->route('umum.ruang.manage', ['tab' => $tab])->with($type, $message);
    }
}
