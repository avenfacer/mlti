<?php

/*
 * Aturan pengajuan Ruang Jambi (ruang rapat). Jam dalam WIB (APP_TIMEZONE).
 */
return [
    'open_time' => env('RUANG_JAMBI_OPEN', '07:30'),
    'close_time' => env('RUANG_JAMBI_CLOSE', '17:00'),
    'max_duration_hours' => (int) env('RUANG_JAMBI_MAX_HOURS', 8),
    'slot_minutes' => 30,
];
