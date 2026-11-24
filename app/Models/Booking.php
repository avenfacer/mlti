<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booking_code', 'requested_by', 'type', 'room_id', 'id_zoom_room', 'title', 'purpose', 'pic', 'participants', 'other_needs', 'notes', 'live_streaming', 'admin_notes', 'starts_at', 'ends_at', 'status'])]
class Booking extends Model
{
    public const MENUNGGU = 'menunggu';

    public const DISETUJUI = 'disetujui';

    public const DITOLAK = 'ditolak';

    public const PERLU_REVISI = 'perlu_revisi';

    public const DIBATALKAN = 'dibatalkan';

    public const SELESAI = 'selesai';

    /** Label tampilan dan warna badge Bootstrap per status. */
    public const STATUS_LABELS = [
        self::MENUNGGU => ['Menunggu', 'warning text-dark'],
        self::DISETUJUI => ['Disetujui', 'success'],
        self::DITOLAK => ['Ditolak', 'danger'],
        self::PERLU_REVISI => ['Perlu Revisi', 'rj-revisi'],
        self::DIBATALKAN => ['Dibatalkan', 'secondary'],
        self::SELESAI => ['Selesai', 'dark'],
    ];

    /** Status yang memblokir ruang (reservasi terkonfirmasi). */
    public const BLOCKING = [self::DISETUJUI, self::SELESAI];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'live_streaming' => 'boolean'];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by', 'nip_lama');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function meetingRoom()
    {
        return $this->belongsTo(MeetingRoom::class, 'room_id', 'room_id');
    }

    public function zoomRoom()
    {
        return $this->belongsTo(ZoomRoom::class, 'id_zoom_room');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'booking_facility');
    }

    public function histories()
    {
        return $this->hasMany(BookingHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function scopeOverlapping($query, int $roomId, $start, $end)
    {
        return $query->where('type', 'room')->where('room_id', $roomId)
            ->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::MENUNGGU, self::PERLU_REVISI], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::MENUNGGU, self::PERLU_REVISI, self::DISETUJUI], true)
            && $this->starts_at->isFuture();
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status][0] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_LABELS[$this->status][1] ?? 'secondary';
    }

    public function getRouteKeyName(): string
    {
        return 'booking_code';
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            if ($booking->booking_code || ! in_array($booking->type, ['zoom', 'room'], true)) {
                return;
            }

            $prefix = $booking->type === 'zoom' ? 'ZMT-' : 'RJB-';
            do {
                $booking->booking_code = $prefix.static::randomLetters(6);
            } while (static::where('booking_code', $booking->booking_code)->exists());
        });
    }

    private static function randomLetters(int $length): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        for ($index = 0; $index < $length; $index++) {
            $result .= $letters[random_int(0, strlen($letters) - 1)];
        }

        return $result;
    }
}
