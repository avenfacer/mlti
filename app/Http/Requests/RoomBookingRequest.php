<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Data formulir pengajuan Ruang Jambi. Aturan ketersediaan ruang dicek
 * di RoomBookingService (kapasitas, bentrok, maintenance).
 */
class RoomBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Penanggung jawab dipilih dari daftar Ketua Tim Kerja (pic_choice) atau
     * diisi bebas bila memilih "Lainnya" (pic_other); hasilnya disimpan di `pic`.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('pic_choice')) {
            $choice = (string) $this->input('pic_choice');
            $this->merge(['pic' => $choice === 'lainnya' ? trim((string) $this->input('pic_other')) : $choice]);
        }
    }

    public function rules(): array
    {
        $document = ['file', 'mimes:'.implode(',', config('ruang_jambi.document_mimes')), 'max:'.config('ruang_jambi.document_max_kb')];
        // Nota dinas wajib saat pengajuan baru; saat ubah/revisi boleh memakai berkas sebelumnya.
        $booking = $this->route('booking');
        $memoRequired = ! ($booking && $booking->memo_path);

        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'pic_choice' => ['sometimes', 'required', 'string', Rule::in([...config('ruang_jambi.pic_options'), 'lainnya'])],
            'pic' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'participants' => ['required', 'integer', 'min:2', 'max:1000'],
            'room_id' => ['required', 'integer', Rule::exists('meeting_rooms', 'room_id')],
            'layout' => ['required', 'string', Rule::in(array_keys(config('ruang_jambi.layouts')))],
            'officials_count' => ['required', 'integer', 'min:0', 'max:'.config('ruang_jambi.max_officials')],
            'committee' => ['nullable', 'string', Rule::in(array_keys(config('ruang_jambi.committees')))],
            'memo' => [$memoRequired ? 'required' : 'nullable', ...$document],
            'letter' => ['nullable', ...$document],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'nama acara',
            'pic_choice' => 'penanggung jawab acara',
            'pic' => 'penanggung jawab acara',
            'date' => 'tanggal',
            'start' => 'jam mulai',
            'end' => 'jam selesai',
            'participants' => 'jumlah peserta',
            'room_id' => 'ruangan',
            'layout' => 'layout ruangan',
            'officials_count' => 'jumlah pejabat yang duduk di depan',
            'committee' => 'susunan kepanitiaan',
            'memo' => 'nota dinas',
            'letter' => 'surat pendukung',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'title.min' => 'Nama acara minimal :min karakter.',
            'pic.required' => 'Tuliskan nama penanggung jawab acara.',
            'mimes' => ':Attribute harus berformat :values.',
            'memo.max' => 'Ukuran nota dinas maksimal 5 MB.',
            'letter.max' => 'Ukuran surat pendukung maksimal 5 MB.',
            'participants.min' => 'Jumlah peserta minimal :min orang.',
            'participants.max' => 'Jumlah peserta maksimal :max orang.',
            'date.after_or_equal' => 'Tanggal tidak boleh di masa lalu.',
            'end.after' => 'Jam selesai harus lebih dari jam mulai.',
            'room_id.exists' => 'Ruangan yang dipilih bukan ruang rapat.',
        ];
    }

    /** Aturan jam operasional, durasi maksimal, dan waktu yang sudah lewat. */
    public function after(): array
    {
        return [function (Validator $v) {
            if ($v->errors()->hasAny(['date', 'start', 'end'])) {
                return;
            }
            $open = config('ruang_jambi.open_time');
            $close = config('ruang_jambi.close_time');
            $maxHours = (int) config('ruang_jambi.max_duration_hours');
            $date = (string) $this->input('date');
            $start = (string) $this->input('start');
            $end = (string) $this->input('end');

            if ($date === now()->toDateString() && $start <= now()->format('H:i')) {
                $v->errors()->add('start', 'Jam mulai sudah terlewati untuk hari ini.');
            }
            if ($start < $open || $end > $close) {
                $v->errors()->add('start', "Kegiatan harus berada dalam jam operasional {$open}–{$close} WIB.");
            }
            if (self::minutes($end) - self::minutes($start) > $maxHours * 60) {
                $v->errors()->add('end', "Durasi kegiatan maksimal {$maxHours} jam.");
            }
        }];
    }

    private static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}
