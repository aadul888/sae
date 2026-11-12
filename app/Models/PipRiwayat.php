<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipRiwayat extends Model
{
    protected $table = 'pip_riwayat';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'nama_pd',
        'kelas',
        'rombel',
        'rombongan_belajar_id',
        'nama_ibu_kandung',
        'nama_ayah',
        'tanggal_lahir',
        'tempat_lahir',
        'jenis_kelamin',
        'nominal',
        'nomor_sk',
        'jenis_sk', // 'pemberian', 'nominasi'
        'tanggal_sk',
        'tahap',
        'tahap_id',
        'tahap_keterangan',
        'fase',
        'tahun',
        'tahun_pelajaran',
        'judul_usulan',
        'status_cair',
        'tanggal_cair',
        'tanggal_aktivasi',
        'no_rekening',
        'no_kip',
        'no_kks',
        'no_kps',
        'virtual_acc',
        'keterangan_pencairan',
        'sekolah_id',
        'npsn',
        'nomenklatur',
        'nik',
        'nama_rekening',
        'nama_kartu',
        'semester_id',
        'layak_pip',
        'confirmation_text',
        'nama_pengusul',
        'raw_data',
    ];

    /**
     * Query riwayat PIP milik seorang peserta didik (by ID, fallback NISN).
     * ponytail: pencocokan NISN saja; tambahkan NIK bila data legacy tidak punya NISN.
     */
    public static function milikSiswa(string $pesertaDidikId, ?string $nisn = null)
    {
        return static::query()->where(function ($q) use ($pesertaDidikId, $nisn) {
            $q->where('peserta_didik_id', $pesertaDidikId);
            if (!empty($nisn)) {
                $q->orWhere('nisn', $nisn);
            }
        });
    }

    public static function terdaftar(string $pesertaDidikId, ?string $nisn = null): bool
    {
        if (!\App\Support\Table::exists('pip_riwayat')) {
            return false;
        }

        return static::milikSiswa($pesertaDidikId, $nisn)->exists();
    }

    public const JENIS_SK = [
        'pemberian' => 'SK Pemberian (Pencairan Dana)',
        'nominasi'  => 'SK Nominasi (Aktivasi Rekening)',
    ];

    protected $casts = [
        'nominal'  => 'float',
        'raw_data' => 'array',
    ];

    public function pesertaDidik()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
