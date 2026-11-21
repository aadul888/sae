<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Definisi ujian CBT (kertas ujian). Lihat .agents/docs/RENCANA_EKOSISTEM.md §14.7.
 * Pelaksanaan serentak (attempt/jawaban) belum ada — menunggu fondasi §10.
 */
class Ujian extends Model
{
    protected $table = 'ujian';

    protected $fillable = [
        'pembelajaran_id',
        'periode_id',
        'judul',
        'deskripsi',
        'tipe',
        'durasi_menit',
        'kkm',
        'jadwal_mulai',
        'jadwal_selesai',
        'acak_soal',
        'acak_opsi',
        'status',
        'token',
        'created_by',
    ];

    protected $casts = [
        'durasi_menit' => 'integer',
        'kkm' => 'integer',
        'jadwal_mulai' => 'datetime',
        'jadwal_selesai' => 'datetime',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
    ];

    public const TIPE = ['ulangan', 'uts', 'uas', 'latihan'];

    /** Buat token ujian acak (huruf besar + angka, hindari karakter ambigu). */
    public static function generateToken(int $length = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $token;
    }

    /** Periode asesmen pemilik ujian ini (opsional). */
    public function periode()
    {
        return $this->belongsTo(CbtPeriodeAsesmen::class, 'periode_id');
    }

    /** Soal yang terpasang pada ujian ini. */
    public function soalItems()
    {
        return $this->hasMany(UjianSoalItem::class, 'ujian_id')->orderBy('urutan');
    }
}
