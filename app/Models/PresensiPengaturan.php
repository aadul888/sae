<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PresensiPengaturan extends Model
{
    use HasFactory;

    protected $table = 'presensi_pengaturan';

    protected $fillable = [
        'jam_masuk_mulai',
        'jam_masuk_selesai',
        'jam_masuk_toleransi',
        'jam_pulang_mulai',
        'jam_pulang_selesai',
        'hari_aktif',
        'toleransi_terlambat_menit',
        'require_camera',
        'allow_rfid',
        'allow_qr',
        'is_active',
        'auto_alpha_time',
    ];

    protected $casts = [
        'hari_aktif' => 'array',
        'toleransi_terlambat_menit' => 'integer',
        'require_camera' => 'boolean',
        'allow_rfid' => 'boolean',
        'allow_qr' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Ambil singleton konfigurasi presensi
     */
    public static function getPengaturan(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'jam_masuk_mulai' => '06:00:00',
            'jam_masuk_selesai' => '07:15:00',
            'jam_masuk_toleransi' => '08:30:00',
            'jam_pulang_mulai' => '14:30:00',
            'jam_pulang_selesai' => '17:30:00',
            'hari_aktif' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
            'toleransi_terlambat_menit' => 0,
            'require_camera' => true,
            'allow_rfid' => true,
            'allow_qr' => true,
            'is_active' => true,
            'auto_alpha_time' => '09:00:00',
        ]);
    }

    /**
     * Periksa apakah hari ini adalah hari kerja / hari aktif belajar
     */
    public function isHariAktif(?Carbon $date = null): bool
    {
        $date = $date ?: Carbon::now();
        $namaHariIndo = match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => '',
        };

        $hariAktif = is_array($this->hari_aktif) ? $this->hari_aktif : [];
        return in_array($namaHariIndo, $hariAktif, true);
    }
}
