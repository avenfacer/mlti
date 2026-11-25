<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['ticket_id', 'reported_by', 'room_id', 'location', 'category', 'description', 'status', 'handled_by', 'admin_notes', 'resolved_at'])]
class GeneralComplaint extends Model
{
    public const CATEGORIES = [
        'AC rusak' => 'AC rusak',
        'Pintu rusak' => 'Pintu / jendela rusak',
        'Listrik' => 'Listrik',
        'Plumbing' => 'Plumbing / air',
        'Kebersihan' => 'Kebersihan',
        'Furnitur' => 'Furnitur (meja/kursi)',
        'Lainnya' => 'Lainnya',
    ];

    public const CATEGORY_ICONS = [
        'AC rusak' => 'bi-snow',
        'Pintu rusak' => 'bi-door-closed',
        'Listrik' => 'bi-lightning-charge',
        'Plumbing' => 'bi-droplet',
        'Kebersihan' => 'bi-trash3',
        'Furnitur' => 'bi-lamp',
        'Lainnya' => 'bi-three-dots',
    ];

    public const STATUSES = [
        'menunggu' => 'Menunggu',
        'diproses' => 'Diproses',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (GeneralComplaint $complaint): void {
            if ($complaint->ticket_id) {
                return;
            }

            do {
                $ticketId = 'SGP-'.strtoupper(Str::random(6));
            } while (static::where('ticket_id', $ticketId)->exists());

            $complaint->ticket_id = $ticketId;
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ticket_id';
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by', 'nip_lama');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by', 'nip_lama');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function locationLabel(): string
    {
        $parts = array_filter([$this->room?->ruang, $this->location]);

        return $parts ? implode(' — ', $parts) : '-';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'selesai' => 'bg-success',
            'diproses' => 'bg-primary',
            'ditolak' => 'bg-danger',
            default => 'bg-warning text-dark',
        };
    }

    public function categoryIcon(): string
    {
        return self::CATEGORY_ICONS[$this->category] ?? 'bi-tools';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['menunggu', 'diproses'], true);
    }
}
