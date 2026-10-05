<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class MonitoringReporterService
{
    /**
     * Kumpulkan agregat jumlah data (hanya angka) dari tabel-tabel sync / Dapodik.
     * Tidak mengirimkan data pribadi siswa/guru, murni statistik integer.
     */
    public static function collectCounts(): array
    {
        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $setting = Schema::hasTable('settings') ? DB::table('settings')->where('id', 1)->first() : null;

        $npsn = $sekolah->npsn ?? ($setting->npsn ?? '00000000');
        $namaSekolah = $sekolah->nama ?? ($setting->app_name ?? 'SAE Instance');
        $bentukPendidikan = $sekolah->bentuk_pendidikan_id_str ?? null;

        // Ambil data angka saja dari tabel-tabel sync
        $totalPesertaDidik = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0;
        $totalGtk = Schema::hasTable('gtk') ? DB::table('gtk')->count() : 0;
        $totalGuru = 0;
        $totalTendik = 0;

        if ($totalGtk > 0 && Schema::hasColumn('gtk', 'jenis_ptk_id_str')) {
            $totalGuru = DB::table('gtk')->where('jenis_ptk_id_str', 'like', '%guru%')->count();
            $totalTendik = max(0, $totalGtk - $totalGuru);
        }

        $totalRombel = Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0;
        $totalPengguna = Schema::hasTable('users') ? DB::table('users')->count() : 0;
        $totalAdmin = 0;

        if ($totalPengguna > 0 && Schema::hasColumn('users', 'role')) {
            $totalAdmin = DB::table('users')->where('role', 'admin')->count();
        }

        $totalSarpras = Schema::hasTable('sarpras') ? DB::table('sarpras')->count() : 0;
        $totalJadwal = Schema::hasTable('jadwal_pelajaran') ? DB::table('jadwal_pelajaran')->count() : 0;

        // Rincian angka per tabel sync (angka saja)
        $tableCounts = [
            'sekolah' => $sekolah ? 1 : 0,
            'peserta_didik' => $totalPesertaDidik,
            'gtk' => $totalGtk,
            'rombongan_belajar' => $totalRombel,
            'users' => $totalPengguna,
        ];

        if ($totalSarpras > 0) $tableCounts['sarpras'] = $totalSarpras;
        if ($totalJadwal > 0) $tableCounts['jadwal_pelajaran'] = $totalJadwal;

        // Deteksi tipe instalasi: server online vs server lokal
        $appUrl = config('app.url') ?? 'http://localhost';
        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $host);

        $tipeInstalasi = $isLocal ? 'lokal' : 'online';

        // ponytail: app_version diambil dari setting atau konstanta UpdateService; fallback ke 1.0.0
        $appVersion = $setting->app_version ?? (defined('\App\Services\UpdateService::CURRENT_VERSION') ? UpdateService::CURRENT_VERSION : '1.0.0');

        return [
            'npsn' => (string) $npsn,
            'nama_sekolah' => (string) $namaSekolah,
            'bentuk_pendidikan' => $bentukPendidikan,
            'tipe_instalasi' => $tipeInstalasi,
            'app_url' => $appUrl,
            'app_version' => $appVersion,
            'php_version' => PHP_VERSION,
            'database_driver' => config('database.default', 'mysql'),
            'last_sync_dapodik_at' => $setting->last_sync ?? null,
            'total_peserta_didik' => (int) $totalPesertaDidik,
            'total_gtk' => (int) $totalGtk,
            'total_guru' => (int) $totalGuru,
            'total_tendik' => (int) $totalTendik,
            'total_rombel' => (int) $totalRombel,
            'total_pengguna' => (int) $totalPengguna,
            'total_admin' => (int) $totalAdmin,
            'total_sarpras' => (int) $totalSarpras,
            'total_jadwal' => (int) $totalJadwal,
            'table_counts' => $tableCounts,
        ];
    }

    /**
     * Kirim data statistik monitoring angka ke aplikasi sae-core.
     */
    public static function send(): array
    {
        $payload = self::collectCounts();
        $coreUrl = rtrim(config('services.sae_core.url', env('SAE_CORE_URL', 'http://localhost/sae-core')), '/');
        $apiKey = config('services.sae_core.api_key', env('SAE_CORE_API_KEY', env('SAE_API_KEY', '')));

        if (empty($coreUrl)) {
            return [
                'success' => false,
                'message' => 'URL sae-core belum dikonfigurasi (SAE_CORE_URL).',
            ];
        }

        try {
            $endpoint = $coreUrl . '/api/sync/telemetry';
            $response = Http::timeout(5)
                ->withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Laporan monitoring berhasil dikirim ke sae-core.',
                    'response' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Server sae-core merespons status HTTP ' . $response->status(),
                'response' => $response->json(),
            ];
        } catch (\Throwable $e) {
            // Safe fallback jika sae-core offline / tidak dapat dijangkau
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke sae-core: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Kirim secara asynchronous atau background non-blocking.
     */
    public static function reportAsync(): void
    {
        try {
            self::send();
        } catch (\Throwable $e) {
            // Abaikan kesalahan koneksi agar tidak mengganggu alur utama
        }
    }
}
