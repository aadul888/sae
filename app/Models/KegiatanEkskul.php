<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanEkskul extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_ekskul';

    protected $fillable = [
        'nama_ekskul',
        'kategori',
        'pembina_ptk_id',
        'pelatih_nama',
        'jadwal_hari',
        'jam_mulai',
        'jam_selesai',
        'tempat',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pembina()
    {
        return $this->belongsTo(Gtk::class, 'pembina_ptk_id', 'ptk_id');
    }

    public function anggota()
    {
        return $this->hasMany(KegiatanEkskulAnggota::class, 'ekskul_id');
    }

    public function agenda()
    {
        return $this->hasMany(KegiatanAgenda::class, 'ekskul_id');
    }
}
