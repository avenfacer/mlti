<?php

use App\Models\Booking;
use App\Services\RoomBookingService;
use App\Services\RoomBookingSheet;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ruang Jambi: tandai pengajuan disetujui yang sudah lewat sebagai "selesai".
// (Juga dijalankan otomatis saat halaman Ruang Jambi dibuka.)
Artisan::command('ruang-jambi:complete', function (RoomBookingService $service) {
    $this->info($service->completePast().' pengajuan ditandai selesai.');
})->purpose('Tandai pengajuan Ruang Jambi yang sudah lewat sebagai selesai');

// Ruang Jambi: salin ulang seluruh pengajuan ke Google Sheet rekap (mis. untuk data lama).
Artisan::command('ruang-jambi:sync-sheet', function (RoomBookingSheet $sheet) {
    if (! RoomBookingSheet::enabled()) {
        $this->error('Sinkronisasi Google Sheet belum diaktifkan (RUANG_JAMBI_SHEET_ENABLED / RUANG_JAMBI_SHEET_ID).');

        return 1;
    }
    $bookings = Booking::where('type', 'room')->orderBy('id')->get();
    $this->withProgressBar($bookings, fn ($booking) => $sheet->sync($booking));
    $this->newLine();
    $this->info($bookings->count().' pengajuan disalin ke Google Sheet.');
})->purpose('Salin seluruh pengajuan Ruang Jambi ke Google Sheet');

Schedule::command('ruang-jambi:complete')->everyFiveMinutes();
