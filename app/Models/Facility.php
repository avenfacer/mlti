<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'type', 'description', 'stock', 'active'])]
class Facility extends Model
{
    /** Terpasang permanen di ruangan. */
    public const TETAP = 'tetap';

    /** Dapat diminta saat pengajuan. */
    public const TAMBAHAN = 'tambahan';

    protected $casts = ['active' => 'boolean', 'stock' => 'integer'];

    public function scopeAdditional($query)
    {
        return $query->where('type', self::TAMBAHAN);
    }

    public function scopePermanent($query)
    {
        return $query->where('type', self::TETAP);
    }
}
