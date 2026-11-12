<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipPenggunaanBukti extends Model
{
    protected $table = 'pip_penggunaan_bukti';

    protected $fillable = [
        'penggunaan_dana_id',
        'file_path',
        'nama_asli',
        'keterangan',
    ];
}
