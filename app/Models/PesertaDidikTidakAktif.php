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
        'kebutuhan_khusus',
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
        'nomor_telepon_rumah',
        'alamat_jalan',
        'rt',
        'rw',
        'dusun',
        'kelurahan',
        'kecamatan',
        'kode_pos',
        'jenis_tinggal',
        'alat_transportasi',
        'skhun',
        'penerima_kps',
        'no_kps',
        'nama_ayah',
        'tahun_lahir_ayah',
        'jenjang_pendidikan_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',
        'kebutuhan_khusus_ayah',
        'nama_ibu',
        'tahun_lahir_ibu',
        'jenjang_pendidikan_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',
        'kebutuhan_khusus_ibu',
        'nama_wali',
        'tahun_lahir_wali',
        'jenjang_pendidikan_wali',
        'pekerjaan_wali',
        'penghasilan_wali',
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
