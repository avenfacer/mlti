<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booking_id', 'actor_nip', 'actor_name', 'action', 'note', 'created_at'])]
class BookingHistory extends Model
{
    public const UPDATED_AT = null;

    public const LABELS = [
        'diajukan' => ['Pengajuan dibuat', 'bi-send', 'rj-primary'],
        'diubah' => ['Pengajuan diubah', 'bi-pencil-square', 'rj-revisi'],
        'disetujui' => ['Disetujui', 'bi-check-circle', 'success'],
        'ditolak' => ['Ditolak', 'bi-x-circle', 'danger'],
        'perlu_revisi' => ['Diminta revisi', 'bi-arrow-repeat', 'warning'],
        'dibatalkan' => ['Dibatalkan', 'bi-slash-circle', 'secondary'],
        'selesai' => ['Selesai', 'bi-flag', 'dark'],
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function label(): array
    {
        return self::LABELS[$this->action] ?? [ucfirst($this->action), 'bi-dot', 'secondary'];
    }
}
