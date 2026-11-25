<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Profil ruang rapat Ruang Jambi. PK = rooms.id, sehingga satu ruang kantor
 * (mis. Ruang Aula) dapat sekaligus menjadi lokasi perangkat dan ruang rapat.
 */
#[Fillable(['room_id', 'location', 'capacity', 'description', 'active'])]
class MeetingRoom extends Model
{
    protected $primaryKey = 'room_id';

    public $incrementing = false;

    protected $casts = ['active' => 'boolean', 'capacity' => 'integer'];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'facility_meeting_room', 'room_id', 'facility_id');
    }

    public function getNameAttribute(): string
    {
        return $this->room->ruang ?? ('Ruang #'.$this->room_id);
    }
}
