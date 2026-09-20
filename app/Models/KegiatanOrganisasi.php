<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanOrganisasi extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_organisasi';

    protected $fillable = [
        'jenis',
        'nama_organisasi',
        'masa_bakti',
        'ketua_peserta_didik_id',
        'pembina_ptk_id',
        'visi_misi',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ketua()
    {
        return $this->belongsTo(PesertaDidik::class, 'ketua_peserta_didik_id', 'peserta_didik_id');
    }

    public function pembina()
    {
        return $this->belongsTo(Gtk::class, 'pembina_ptk_id', 'ptk_id');
    }

    public function anggota()
    {
        return $this->hasMany(KegiatanOrganisasiAnggota::class, 'organisasi_id');
    }

    public function agenda()
    {
        return $this->hasMany(KegiatanAgenda::class, 'organisasi_id');
    }
}
