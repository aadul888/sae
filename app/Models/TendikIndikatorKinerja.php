<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TendikIndikatorKinerja extends Model
{
    use HasFactory;

    protected $table = 'tendik_indikator_kinerja';

    protected $fillable = [
        'bidang',
        'sasaran',
        'indikator_kinerja',
        'target_kuantitas',
        'satuan',
        'target_label',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'target_kuantitas' => 'integer',
        'urutan' => 'integer',
        'is_active' => 'boolean',
    ];

    public function aktivitas()
    {
        return $this->hasMany(TendikAktivitas::class, 'indikator_id', 'id');
    }
}
