<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\RoomBookingSheet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Kirim satu pengajuan Ruang Jambi ke Google Sheet di latar belakang, agar
 * gangguan/lambatnya Google API tidak menggagalkan pengajuan pegawai.
 */
class SyncRoomBookingToSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $bookingId)
    {
        $this->afterCommit();
    }

    public function handle(RoomBookingSheet $sheet): void
    {
        $booking = Booking::find($this->bookingId);
        if ($booking) {
            $sheet->sync($booking);
        }
    }
}
