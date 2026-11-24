<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['room_id', 'starts_at', 'ends_at', 'reason', 'created_by'])]
class MaintenanceBlock extends Model
{
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'nip_lama');
    }

    public function scopeOverlapping($query, int $roomId, $start, $end)
    {
        return $query->where('room_id', $roomId)->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }
}
