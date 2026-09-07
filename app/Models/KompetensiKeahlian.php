<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KompetensiKeahlian extends Model
{
    protected $table = 'kompetensi_keahlian';

    protected $fillable = [
        'kode',
        'nama',
        'bidang_keahlian',
        'program_keahlian',
        'tahun_berlaku',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tahun_berlaku' => 'integer',
    ];
}
