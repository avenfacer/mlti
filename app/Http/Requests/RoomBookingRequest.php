<?php

namespace App\Http\Requests;

use App\Models\Facility;
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

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:200'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'pic' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start' => ['required', 'date_format:H:i'],
            'end' => ['required', 'date_format:H:i', 'after:start'],
            'participants' => ['required', 'integer', 'min:2', 'max:1000'],
            'room_id' => ['required', 'integer', Rule::exists('meeting_rooms', 'room_id')],
            'facility_ids' => ['array'],
            'facility_ids.*' => ['integer', 'distinct', Rule::exists('facilities', 'id')
                ->where('type', Facility::TAMBAHAN)->where('active', true)],
            'other_needs' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul kegiatan',
            'purpose' => 'agenda',
            'pic' => 'penanggung jawab',
            'date' => 'tanggal',
            'start' => 'jam mulai',
            'end' => 'jam selesai',
            'participants' => 'jumlah peserta',
            'room_id' => 'ruangan',
            'facility_ids' => 'fasilitas tambahan',
            'facility_ids.*' => 'fasilitas tambahan',
            'other_needs' => 'kebutuhan lainnya',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'title.min' => 'Judul kegiatan minimal :min karakter.',
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
