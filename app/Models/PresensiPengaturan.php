<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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
        'jurusan_aktif',
        'toleransi_terlambat_menit',
        'require_camera',
        'allow_rfid',
        'allow_qr',
        'require_location',
        'latitude',
        'longitude',
        'radius_meter',
        'is_active',
        'auto_alpha_time',
    ];

    protected $casts = [
        'hari_aktif' => 'array',
        'jurusan_aktif' => 'array',
        'toleransi_terlambat_menit' => 'integer',
        'require_camera' => 'boolean',
        'allow_rfid' => 'boolean',
        'allow_qr' => 'boolean',
        'require_location' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meter' => 'integer',
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
            'jurusan_aktif' => null,
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

    /**
     * Periksa apakah suatu jurusan (Kompetensi Keahlian) diizinkan presensi
     */
    public function isJurusanAktif(?string $jurusanId): bool
    {
        if (empty($this->jurusan_aktif) || !is_array($this->jurusan_aktif)) {
            return true; // Default: seluruh jurusan aktif jika belum dibatasi
        }

        if (!$jurusanId) {
            return true;
        }

        return in_array((string) $jurusanId, array_map('strval', $this->jurusan_aktif), true);
    }

    /**
     * Ambil daftar seluruh kompetensi keahlian/jurusan dari rombongan belajar & metadata logo
     */
    public static function getJurusanList()
    {
        return DB::table('rombongan_belajar as rb')
            ->whereNotNull('rb.jurusan_id_str')
            ->where('rb.jurusan_id_str', '<>', '')
            ->leftJoin('peserta_didik as pd', 'rb.rombongan_belajar_id', '=', 'pd.rombongan_belajar_id')
            ->leftJoin('jurusan_meta as jm', 'rb.jurusan_id', '=', 'jm.jurusan_id')
            ->select(
                'rb.jurusan_id as kode',
                'rb.jurusan_id_str as nama',
                'jm.logo_path',
                'jm.singkatan',
                DB::raw('COUNT(DISTINCT rb.rombongan_belajar_id) as total_rombel'),
                DB::raw('COUNT(DISTINCT pd.peserta_didik_id) as total_siswa')
            )
            ->groupBy('rb.jurusan_id', 'rb.jurusan_id_str', 'jm.logo_path', 'jm.singkatan')
            ->orderBy('rb.jurusan_id_str')
            ->get()
            ->map(function ($j) {
                $j->logo_url = !empty($j->logo_path) ? asset('storage/' . ltrim($j->logo_path, '/')) : null;
                return $j;
            });
    }

    /**
     * Ambil data referensi satuan pendidikan (sekolah) dari tabel sekolah
     */
    public static function getSekolah(): ?object
    {
        return DB::table('sekolah')->first();
    }

    /**
     * Ambil koordinat lintang efektif (prioritas: kustom setting, fallback: data Dapodik sekolah)
     */
    public function getEffectiveLatitude(): ?float
    {
        if ($this->latitude !== null && (float) $this->latitude != 0) {
            return (float) $this->latitude;
        }
        $sekolah = self::getSekolah();
        return ($sekolah && !empty($sekolah->lintang)) ? (float) $sekolah->lintang : null;
    }

    /**
     * Ambil koordinat bujur efektif (prioritas: kustom setting, fallback: data Dapodik sekolah)
     */
    public function getEffectiveLongitude(): ?float
    {
        if ($this->longitude !== null && (float) $this->longitude != 0) {
            return (float) $this->longitude;
        }
        $sekolah = self::getSekolah();
        return ($sekolah && !empty($sekolah->bujur)) ? (float) $sekolah->bujur : null;
    }

    /**
     * Radius presensi efektif dalam meter
     */
    public function getEffectiveRadius(): int
    {
        return (int) ($this->radius_meter ?: 100);
    }

    /**
     * Hitung jarak dua titik koordinat menggunakan rumus Haversine (hasil dalam meter)
     */
    public static function calculateDistance(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadius = 6371000; // Radius bumi dalam meter

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Verifikasi apakah posisi GPS berada di dalam radius toleransi sekolah
     */
    public function checkLocationRadius(?float $userLat, ?float $userLon): array
    {
        $targetLat = $this->getEffectiveLatitude();
        $targetLon = $this->getEffectiveLongitude();
        $radius = $this->getEffectiveRadius();
        $distance = self::calculateDistance($targetLat, $targetLon, $userLat, $userLon);

        if (!$this->require_location) {
            return [
                'required' => false,
                'allowed' => true,
                'distance_meter' => $distance !== null ? $distance : 0,
                'radius_meter' => $radius,
                'message' => 'Geolokasi tidak diwajibkan.',
            ];
        }

        if ($targetLat === null || $targetLon === null) {
            return [
                'required' => true,
                'allowed' => true,
                'distance_meter' => $distance !== null ? $distance : 0,
                'radius_meter' => $radius,
                'message' => 'Titik koordinat sekolah belum disetel di Dapodik/pengaturan.',
            ];
        }

        if ($userLat === null || $userLon === null) {
            return [
                'required' => true,
                'allowed' => false,
                'distance_meter' => null,
                'radius_meter' => $radius,
                'message' => 'Koordinat GPS perangkat tidak terdeteksi. Pastikan izin lokasi / GPS di browser Anda telah diaktifkan.',
            ];
        }

        $distance = self::calculateDistance($targetLat, $targetLon, $userLat, $userLon);
        $radius = $this->getEffectiveRadius();
        $isAllowed = ($distance !== null && $distance <= $radius);

        return [
            'required' => true,
            'allowed' => $isAllowed,
            'distance_meter' => $distance,
            'radius_meter' => $radius,
            'target_lat' => $targetLat,
            'target_lon' => $targetLon,
            'message' => $isAllowed
                ? "Lokasi berada di dalam radius ({$distance} m dari sekolah, maks {$radius} m)."
                : "Lokasi berada di luar radius ({$distance} m dari sekolah, batas maks {$radius} m).",
        ];
    }
}
