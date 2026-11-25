<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ruang Jambi: tandai pengajuan disetujui yang sudah lewat sebagai "selesai".
// (Juga dijalankan otomatis saat halaman Ruang Jambi dibuka.)
Artisan::command('ruang-jambi:complete', function (\App\Services\RoomBookingService $service) {
    $this->info($service->completePast() . ' pengajuan ditandai selesai.');
})->purpose('Tandai pengajuan Ruang Jambi yang sudah lewat sebagai selesai');

\Illuminate\Support\Facades\Schedule::command('ruang-jambi:complete')->everyFiveMinutes();
