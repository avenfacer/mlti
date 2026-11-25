<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomBookingRequest;
use App\Models\Booking;
use App\Models\MaintenanceBlock;
use App\Models\MeetingRoom;
use App\Services\RoomBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Ruang Jambi (sisi pegawai): ajukan, ubah/revisi, lihat, dan batalkan pengajuan ruang rapat.
 */
class RoomBookingController extends Controller
{
    public function __construct(private RoomBookingService $service) {}

    public function create()
    {
        $this->service->completePast();

        return view('ruang-jambi.form', $this->formData());
    }

    public function store(RoomBookingRequest $request)
    {
        $booking = $this->service->submit($request->user(), $request->validated());

        return redirect()->route('ruang.show', $booking)
            ->with('success', "Pengajuan berhasil dikirim dengan kode {$booking->booking_code}. Pengajuan akan ditinjau oleh Tim Umum.");
    }

    public function edit(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);
        if (! $booking->isEditable()) {
            return redirect()->route('ruang.show', $booking)->with('error', 'Pengajuan hanya dapat diubah selama berstatus Menunggu atau Perlu Revisi.');
        }
        $booking->load('histories');

        return view('ruang-jambi.form', $this->formData($booking));
    }

    public function update(RoomBookingRequest $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);
        $booking = $this->service->edit($request->user(), $booking, $request->validated());

        return redirect()->route('ruang.show', $booking)->with('success', 'Perubahan pengajuan berhasil dikirim.');
    }

    public function index(Request $request)
    {
        $this->service->completePast();
        $bookings = Booking::with('room')->where('type', 'room')
            ->where('requested_by', $request->user()->nip_lama)
            ->latest()->paginate(10);

        return view('ruang-jambi.index', compact('bookings'));
    }

    public function show(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);
        $booking->load(['room', 'meetingRoom', 'requester', 'histories']);

        return view('ruang-jambi.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorizeOwner($request, $booking);
        $data = $request->validate(['note' => 'nullable|string|max:2000']);
        $this->service->cancel($request->user(), $booking, $data['note'] ?? null);

        return redirect()->route('ruang.show', $booking)->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    /** Unduh nota dinas / surat pendukung. Hanya pemohon dan Tim Umum/Jarkom. */
    public function document(Request $request, Booking $booking, string $document)
    {
        abort_unless($booking->type === 'room', 404);
        $user = $request->user();
        $isReviewer = (int) $user->is_umum === 1 || (int) $user->is_jarkom === 1;
        abort_unless($isReviewer || (int) $booking->requested_by === (int) $user->nip_lama, 403);

        [$pathColumn, $nameColumn] = Booking::DOCUMENTS[$document] ?? abort(404);
        $path = $booking->{$pathColumn};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $booking->{$nameColumn} ?: basename($path));
    }

    private function authorizeOwner(Request $request, Booking $booking): void
    {
        abort_unless($booking->type === 'room', 404);
        abort_unless((int) $booking->requested_by === (int) $request->user()->nip_lama, 403);
    }

    /** Data form + JSON untuk pengecekan ketersediaan dan kalender di sisi klien. */
    private function formData(?Booking $booking = null): array
    {
        $rooms = MeetingRoom::with(['room', 'facilities' => fn ($q) => $q->where('active', true)])->get()
            ->sortBy(fn (MeetingRoom $r) => $r->name)->values();

        $from = now()->subDays(45)->startOfDay();
        $schedule = Booking::with('room')->where('type', 'room')->where('ends_at', '>=', $from)
            ->whereIn('status', [Booking::MENUNGGU, Booking::DISETUJUI, Booking::SELESAI])
            ->orderBy('starts_at')->get()
            ->map(fn (Booking $b) => [
                'id' => $b->id,
                'room_id' => (int) $b->room_id,
                'room' => $b->room->ruang ?? '-',
                'title' => $b->title,
                'status' => $b->status === Booking::MENUNGGU ? 'menunggu' : 'disetujui',
                'start' => $b->starts_at->toIso8601String(),
                'end' => $b->ends_at->toIso8601String(),
            ])->values();
        $blocks = MaintenanceBlock::where('ends_at', '>=', $from)->orderBy('starts_at')->get()
            ->map(fn (MaintenanceBlock $m) => [
                'room_id' => (int) $m->room_id,
                'reason' => $m->reason,
                'start' => $m->starts_at->toIso8601String(),
                'end' => $m->ends_at->toIso8601String(),
            ])->values();

        $roomsJson = $rooms->map(fn (MeetingRoom $r) => [
            'id' => (int) $r->room_id,
            'name' => $r->name,
            'capacity' => $r->capacity,
            'active' => $r->active,
        ])->values();

        $revisionNote = $booking?->status === Booking::PERLU_REVISI
            ? $booking->histories->where('action', Booking::PERLU_REVISI)->last()?->note
            : null;

        return [
            'booking' => $booking,
            'rooms' => $rooms,
            'schedule' => $schedule,
            'blocks' => $blocks,
            'roomsJson' => $roomsJson,
            'revisionNote' => $revisionNote,
            'timeOptions' => $this->timeOptions(),
            'layouts' => config('ruang_jambi.layouts'),
            'picOptions' => config('ruang_jambi.pic_options'),
            'committees' => config('ruang_jambi.committees'),
            // Rentang jam kalender: jam buka (dibulatkan ke bawah) s.d. jam tutup (dibulatkan ke atas).
            'calendarHours' => [
                (int) explode(':', config('ruang_jambi.open_time'))[0],
                (int) ceil($this->minutes(config('ruang_jambi.close_time')) / 60),
            ],
        ];
    }

    /** @return list<string> */
    private function timeOptions(): array
    {
        $step = (int) config('ruang_jambi.slot_minutes', 30);
        $close = $this->minutes(config('ruang_jambi.close_time'));
        $options = [];
        for ($m = $this->minutes(config('ruang_jambi.open_time')); $m <= $close; $m += $step) {
            $options[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        }

        return $options;
    }

    private function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}
