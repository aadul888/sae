<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RefTugasTambahan extends Model
{
    protected $table = 'ref_tugas_tambahan';

    protected $fillable = [
        'kode',
        'nama',
        'kelompok',
        'bidang',
        'ekuivalensi_jam',
        'icon',
        'granted_permissions',
        'is_active',
    ];

    protected $casts = [
        'ekuivalensi_jam' => 'float',
        'granted_permissions' => 'array',
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(PtkTugasTambahan::class, 'tugas_tambahan_id');
    }
}
