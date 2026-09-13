<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaDidikTidakAktif extends Model
{
    protected $table = 'peserta_didik_tidak_aktif';

    protected $fillable = [
        'peserta_didik_id',
        'registrasi_id',
        'nipd',
        'nama',
        'nisn',
        'nik',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama_id_str',
        'nama_rombel_terakhir',
        'rombongan_belajar_id',
        'tingkat_pendidikan_terakhir',
        'kurikulum_id_str',
        'tahun_lulus',
        'tanggal_keluar',
        'status_keluar',
        'alasan_keluar',
        'foto_path',
        'email',
        'nomor_telepon_seluler',
        'alamat_jalan',
        'raw_data',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_keluar' => 'date',
    ];

    protected $appends = [
        'foto_url',
    ];

    /**
     * URL Foto Peserta Didik Tidak Aktif / Alumni
     */
    public function getFotoUrlAttribute(): ?string
    {
        if (empty($this->foto_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->foto_path, '/'));
    }

    /**
     * Scope untuk filter Alumni (Lulus)
     */
    public function scopeAlumni($query)
    {
        return $query->where('status_keluar', 'Alumni');
    }

    /**
     * Scope untuk filter Siswa Mutasi / Keluar
     */
    public function scopeMutasi($query)
    {
        return $query->where('status_keluar', '<>', 'Alumni');
    }
}
