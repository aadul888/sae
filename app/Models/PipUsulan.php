<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PipUsulan extends Model
{
    protected $table = 'pip_usulan';

    protected $fillable = [
        'peserta_didik_id',
        'rombongan_belajar_id',
        'tahun',
        'fase',
        'tahun_pelajaran',
        'judul_usulan',
        'prioritas',
        'alasan_usulan',
        'keterangan',
        'diusulkan_oleh',
        'status_verifikasi',
        'diverifikasi_oleh',
        'verifikasi_admin_at',
        'catatan_admin',
        'diubah_oleh',
        'diubah_at',
        'perubahan_terakhir',
        'riwayat_perubahan',
    ];

    protected $casts = [
        'alasan_usulan'       => 'array',
        'verifikasi_admin_at' => 'datetime',
        'prioritas'           => 'integer',
        'diubah_at'           => 'datetime',
        'riwayat_perubahan'   => 'array',
    ];

    /**
     * 2 Fase Pengusulan PIP Resmi (Fase 1: Jan-Jun, Fase 2: Jul-Des)
     */
    public const FASE_LIST = [
        'fase_1' => 'Fase 1 (Januari - Juni)',
        'fase_2' => 'Fase 2 (Juli - Desember)',
    ];

    public static function getFaseLabel(?string $fase): string
    {
        if (empty($fase)) {
            return 'Fase 1 (Januari - Juni)';
        }
        return self::FASE_LIST[$fase] ?? (ucfirst(str_replace('_', ' ', (string) $fase)) ?: 'Fase 1 (Januari - Juni)');
    }

    /**
     * 8 Kriteria Alasan Diusulkan Resmi (Sesuai Aplikasi PIP / SiPintar)
     */
    public const KRITERIA_ALASAN = [
        'DTSEN Desil 1 s.d. 4'          => 'DTSEN Desil 1 s.d. 4',
        'Rentan Putus Sekolah'          => 'Rentan Putus Sekolah',
        'Yatim dan atau Piatu'          => 'Yatim dan atau Piatu',
        'Berada di Panti Asuhan/Sosial' => 'Berada di Panti Asuhan/Sosial',
        'Berkebutuhan Khusus'           => 'Berkebutuhan Khusus',
        'Orang Tua terdampak PHK'       => 'Orang Tua terdampak PHK',
        'Orang Tua berstatus Narapidana'=> 'Orang Tua berstatus Narapidana',
        'Murid dalam binaan LPKA'       => 'Murid dalam binaan LPKA',
    ];

    public const STATUS_VERIFIKASI = [
        'belum_diajukan' => 'Belum Diusulkan',
        'sudah_diajukan' => 'Sudah Diusulkan',
        'ditolak'        => 'Ditolak',
    ];

    public function pesertaDidik()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
