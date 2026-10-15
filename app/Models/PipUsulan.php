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
        'prioritas',
        'alasan_usulan',
        'keterangan',
        'diusulkan_oleh',
        'status_verifikasi',
        'diverifikasi_oleh',
        'verifikasi_admin_at',
        'catatan_admin',
    ];

    protected $casts = [
        'alasan_usulan'       => 'array',
        'verifikasi_admin_at' => 'datetime',
        'prioritas'           => 'integer',
    ];

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
        'belum_diajukan' => 'Belum Diusulkan ke Web PIP',
        'sudah_diajukan' => 'Sudah Diusulkan ke Web PIP',
        'ditolak'        => 'Ditolak',
    ];

    public function pesertaDidik()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
