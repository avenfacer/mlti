<?php

/*
 * Aturan pengajuan Ruang Jambi (ruang rapat). Jam dalam WIB (APP_TIMEZONE).
 *
 * Pilihan isian (layout, penanggung jawab, kepanitiaan, dokumen) mengikuti
 * Google Form "Pengajuan Ruangan Meeting" BPS Provinsi Jambi yang dipakai sebelumnya.
 */
return [
    'open_time' => env('RUANG_JAMBI_OPEN', '07:30'),
    'close_time' => env('RUANG_JAMBI_CLOSE', '17:00'),
    'max_duration_hours' => (int) env('RUANG_JAMBI_MAX_HOURS', 8),
    'slot_minutes' => 30,

    // Layout tempat/ruangan: key => [label, keterangan, ikon Bootstrap Icons].
    'layouts' => [
        'classroom' => ['Classroom', 'Meja dan kursi berbaris menghadap ke depan seperti kelas.', 'bi-easel'],
        'round_table' => ['Round Table', 'Peserta duduk di meja bundar.', 'bi-people'],
        'panel' => ['Panel Discussion', 'Susunan beberapa kursi atau sofa sejajar menghadap audiens.', 'bi-mic'],
        'mini_round_table' => ['Mini Round Table', 'Beberapa meja ditempatkan menyebar dalam ruangan untuk jamuan, maks. 4 orang per meja.', 'bi-grid-3x3-gap'],
        'pelantikan' => ['Pelantikan', 'Ruangan hanya tersedia meja untuk penandatanganan naskah.', 'bi-pen'],
        'ruang_tamu' => ['Ruang Tamu', 'Kursi/sofa tamu, untuk audiensi atau kunjungan singkat.', 'bi-cup-hot'],
    ],

    // Jumlah maksimal pejabat yang duduk di depan (0 = "Tidak Ada").
    'max_officials' => 5,

    // Penanggung jawab acara. Pilihan "Lainnya" membuka isian bebas.
    'pic_options' => [
        'Ketua Tim Kerja Sumber Daya Manusia dan Hukum',
        'Ketua Tim Kerja Keuangan dan BMN',
        'Ketua Tim Kerja Perencanaan',
        'Ketua Tim Kerja Humas dan Protokol',
        'Ketua Tim Kerja Layanan dan Sistem Manajemen Mutu',
        'Ketua Tim Kerja MR (Manajemen Risiko) dan QG (Quality Gates)',
        'Ketua Tim Kerja Pembinaan Statistik Sektoral dan EPSS',
        'Ketua Tim Kerja Diseminasi Statistik dan Pojok Statistik',
        'Ketua Tim Kerja Analisis Statistik dan Penyiapan Materi Pimpinan (PMP)',
        'Ketua Tim Kerja Neraca Wilayah',
        'Ketua Tim Kerja Statistik Harga',
        'Ketua Tim Kerja Statistik Distribusi dan Jasa',
        'Ketua Tim Kerja Statistik Ketahanan Sosial dan Desa Cantik',
        'Ketua Tim Kerja Statistik Duknaker dan Kesejahteraan Rakyat',
        'Ketua Tim Kerja Statistik Sumber Daya Hayati (Berdaya)',
        'Ketua Tim Kerja Sensus Ekonomi 2026',
    ],

    // Template susunan kepanitiaan: key => [label, tautan template].
    'committees' => [
        'rapat' => ['Acara Rapat', 'http://s.bps.go.id/Kepanitiaan_Acara_Rapat'],
        'internal' => ['Acara Internal', 'http://s.bps.go.id/Kepanitiaan_Acara_Internal'],
        'kunjungan_tamu' => ['Kunjungan Tamu', 'http://s.bps.go.id/Kepanitiaan_Kunjungan_Tamu'],
        'ujian_kompetensi' => ['Ujian Kompetensi', 'http://s.bps.go.id/Kepanitiaan_Ujian_Kompetensi'],
        'rilis_brs' => ['Rilis BRS', 'http://s.bps.go.id/Kepanitiaan_Acara_RilisBRS'],
        'pelantikan' => ['Pelantikan / Sumpah', 'http://s.bps.go.id/Kepanitiaan_Acara_Pelantikan_Sumpah'],
        'apel' => ['Apel', 'http://s.bps.go.id/Kepanitiaan_Apel'],
        'upacara' => ['Apel / Upacara Bendera', 'http://s.bps.go.id/Kepanitiaan_Apel_Upacara_Bendera'],
    ],

    // Dokumen pendukung (disimpan di disk privat, diunduh lewat route berotorisasi).
    'memo_template_url' => 'http://s.bps.go.id/format_notadinas',
    'document_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
    'document_max_kb' => 5120,

    // Rekap pengajuan ke Google Sheet (akun layanan di config/google.php).
    // Bagikan spreadsheet ke email akun layanan sebagai Editor.
    'google_sheet' => [
        'enabled' => (bool) env('RUANG_JAMBI_SHEET_ENABLED', false),
        'spreadsheet_id' => env('RUANG_JAMBI_SHEET_ID'),
        'sheet' => env('RUANG_JAMBI_SHEET_TAB', 'Pengajuan MLTI'),
    ],
];
