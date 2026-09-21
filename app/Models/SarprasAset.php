<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SarprasAset extends Model
{
    use HasFactory;

    protected $table = 'sarpras_aset';

    protected $fillable = [
        'kode_aset',
        'nama_barang',
        'kategori',
        'merk_tipe',
        'no_seri_pabrik',
        'tahun_perolehan',
        'sumber_dana',
        'harga_perolehan',
        'kondisi',
        'ruang_id',
        'jumlah',
        'satuan',
        'status_ketersediaan',
        'foto',
    ];

    public function persuratan()
    {
        return $this->hasMany(Persuratan::class, 'sarpras_aset_id');
    }
}
