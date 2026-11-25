<?php

namespace App\Services;

use App\Models\Booking;
use Revolution\Google\Sheets\Facades\Sheets;

/**
 * Salin pengajuan Ruang Jambi ke Google Sheet (rekap untuk Tim Umum).
 *
 * Satu baris per pengajuan, dikunci oleh kolom "Kode Pengajuan" (kolom A):
 * baris yang sudah ada diperbarui (mis. saat status berubah), selain itu ditambahkan.
 * Kolom mengikuti Google Form "Pengajuan Ruangan Meeting" yang dipakai sebelumnya,
 * ditambah kode, pemohon, status, dan catatan petugas.
 */
class RoomBookingSheet
{
    public const HEADER = [
        'Kode Pengajuan', 'Timestamp', 'Nama Acara', 'Tanggal', 'Waktu Mulai', 'Waktu Selesai',
        'Tempat/Ruangan', 'Layout Tempat/Ruangan', 'Pejabat yang duduk di depan', 'Penanggungjawab Acara',
        'Susunan Kepanitiaan', 'Surat Pendukung', 'Nota Dinas', 'Jumlah Peserta', 'Pemohon', 'NIP Pemohon', 'Status', 'Catatan Petugas',
        'Terakhir Diperbarui',
    ];

    public static function enabled(): bool
    {
        return (bool) config('ruang_jambi.google_sheet.enabled') && filled(config('ruang_jambi.google_sheet.spreadsheet_id'));
    }

    public function sync(Booking $booking): void
    {
        if (! self::enabled() || $booking->type !== 'room') {
            return;
        }

        $sheet = $this->sheet();
        $codes = array_map(fn ($row) => $row[0] ?? null, $sheet->range('A:A')->all());

        if ($codes === []) {
            $this->sheet()->range('A1')->update([self::HEADER]);
            $codes = [self::HEADER[0]];
        }

        $row = $this->row($booking);
        $index = array_search($booking->booking_code, $codes, true);

        if ($index === false) {
            $this->sheet()->range('A1')->append([$row], 'RAW', 'INSERT_ROWS');
        } else {
            $this->sheet()->range('A'.($index + 1))->update([$row]);
        }
    }

    /**
     * Sel kosong harus string kosong: nilai null membuat baris dikirim sebagai objek JSON
     * dan ditolak Google ("Unknown name \"0\" at 'data.values[0]'").
     *
     * @return list<string|int>
     */
    public function row(Booking $booking): array
    {
        return array_map(fn ($value) => $value ?? '', $this->cells($booking));
    }

    /** @return list<string|int|null> */
    private function cells(Booking $booking): array
    {
        $booking->loadMissing(['room', 'requester']);
        $layout = $booking->layoutInfo();
        $committee = $booking->committeeInfo();
        $document = fn (string $key, string $path) => $booking->{$path}
            ? route('ruang.document', [$booking, $key])
            : '';

        return [
            $booking->booking_code,
            $booking->created_at?->format('d/m/Y H:i:s') ?? '',
            $booking->title,
            $booking->starts_at->format('d/m/Y'),
            $booking->starts_at->format('H:i'),
            $booking->ends_at->format('H:i'),
            $booking->room->ruang ?? 'Ruang #'.$booking->room_id,
            $layout[0] ?? '',
            $booking->officialsLabel() ?? '',
            $booking->pic,
            $committee[0] ?? '',
            $document('surat-pendukung', 'letter_path'),
            $document('nota-dinas', 'memo_path'),
            (int) $booking->participants,
            $booking->requester->name ?? '',
            (string) $booking->requested_by,
            $booking->statusLabel(),
            (string) $booking->admin_notes,
            now()->format('d/m/Y H:i:s'),
        ];
    }

    private function sheet()
    {
        return Sheets::spreadsheet(config('ruang_jambi.google_sheet.spreadsheet_id'))
            ->sheet(config('ruang_jambi.google_sheet.sheet'));
    }
}
