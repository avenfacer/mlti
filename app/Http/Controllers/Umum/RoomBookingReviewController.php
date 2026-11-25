<?php

namespace App\Http\Controllers\Umum;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\RoomBookingService;
use Illuminate\Http\Request;

/**
 * Ruang Jambi (sisi Tim Umum): daftar pengajuan dan tindak lanjut.
 */
class RoomBookingReviewController extends Controller
{
    public function __construct(private RoomBookingService $service) {}

    public function index(Request $request)
    {
        $this->service->completePast();
        $status = $request->query('status');
        $status = array_key_exists((string) $status, Booking::STATUS_LABELS) ? $status : null;

        $counts = Booking::where('type', 'room')->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $bookings = Booking::with(['requester', 'room'])->where('type', 'room')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$term}%")->orWhere('booking_code', 'like', "%{$term}%")))
            ->orderByRaw('case when status = ? then 0 else 1 end', [Booking::MENUNGGU])
            ->orderByDesc('starts_at')
            ->paginate(15)->withQueryString();

        return view('ruang-jambi.umum.index', compact('bookings', 'counts', 'status'));
    }

    public function show(Booking $booking)
    {
        abort_unless($booking->type === 'room', 404);
        $booking->load(['room', 'meetingRoom', 'requester', 'histories']);

        // Peringatan dini: apakah pengajuan ini masih bisa disetujui?
        $conflicts = $booking->status === Booking::MENUNGGU
            ? $this->service->check($booking->meetingRoom, $booking->starts_at, $booking->ends_at, (int) $booking->participants, $booking->id)
            : [];
        // Pengajuan menunggu lain di slot yang sama (akan bentrok bila ini disetujui).
        $competing = $booking->status === Booking::MENUNGGU
            ? Booking::with('requester')->overlapping($booking->room_id, $booking->starts_at, $booking->ends_at)
                ->where('status', Booking::MENUNGGU)->whereKeyNot($booking->id)->get()
            : collect();

        return view('ruang-jambi.umum.show', compact('booking', 'conflicts', 'competing'));
    }

    public function approve(Request $request, Booking $booking)
    {
        return $this->act($request, $booking, 'approve', false, 'Pengajuan disetujui.');
    }

    public function reject(Request $request, Booking $booking)
    {
        return $this->act($request, $booking, 'reject', true, 'Pengajuan ditolak.', 'Alasan penolakan');
    }

    public function revision(Request $request, Booking $booking)
    {
        return $this->act($request, $booking, 'requestRevision', true, 'Permintaan revisi dikirim ke pemohon.', 'Catatan revisi');
    }

    public function cancel(Request $request, Booking $booking)
    {
        return $this->act($request, $booking, 'cancel', false, 'Pengajuan dibatalkan.');
    }

    private function act(Request $request, Booking $booking, string $method, bool $noteRequired, string $success, string $label = 'Catatan')
    {
        abort_unless($booking->type === 'room', 404);
        $data = $request->validate(
            ['note' => [$noteRequired ? 'required' : 'nullable', 'string', 'max:2000']],
            ['note.required' => "{$label} wajib diisi."],
        );
        $note = isset($data['note']) && trim($data['note']) !== '' ? trim($data['note']) : null;

        $this->service->{$method}($request->user(), $booking, $note);

        return redirect()->route('umum.ruang.show', $booking)->with('success', $success);
    }
}
