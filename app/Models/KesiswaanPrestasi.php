<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KesiswaanPrestasi extends Model
{
    use HasFactory;

    protected $table = 'kesiswaan_prestasi';

    protected $fillable = [
        'peserta_didik_id',
        'kategori',
        'bidang_lomba',
        'nama_event',
        'penyelenggara',
        'tingkat',
        'peringkat',
        'tanggal_prestasi',
        'pembimbing_ptk_id',
        'pembimbing_nama',
        'sertifikat_file',
        'foto_kegiatan',
        'nomor_piagam',
        'catatan',
    ];

    protected $casts = [
        'tanggal_prestasi' => 'date',
    ];

    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function pembimbing()
    {
        return $this->belongsTo(Gtk::class, 'pembimbing_ptk_id', 'ptk_id');
    }
}
