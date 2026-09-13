<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use ZipArchive;

use App\Services\BackupService;

class MaintenanceController extends Controller
{
    private function checkAdmin()
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));
        }

        return null;
    }

    public function index()
    {
        if ($res = $this->checkAdmin()) return $res;

        $settings = DB::table('settings')->where('id', 1)->first();
        $hasActiveData = (Schema::hasTable('peserta_didik') && DB::table('peserta_didik')->count() > 0)
            || (Schema::hasTable('rombongan_belajar') && DB::table('rombongan_belajar')->count() > 0)
            || (Schema::hasTable('gtk') && DB::table('gtk')->count() > 0);

        $lastSync = $settings->last_sync ?? null;
        $archiveDownloadedAt = $settings->archive_downloaded_at ?? null;
        $syncAllowed = (bool)($settings->sync_allowed ?? false);

        // Perlu unduh arsip jika ada data aktif dan (belum pernah unduh arsip atau izin sinkronisasi terkunci)
        $isArchiveRequired = $hasActiveData && (!$archiveDownloadedAt || !$syncAllowed);

        $fotoDir = storage_path('app/public/assets/peserta-didik/foto');
        $fotoCount = File::isDirectory($fotoDir) ? count(File::files($fotoDir)) : 0;

        $counts = [
            'peserta_didik' => Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
            'peserta_didik_tidak_aktif' => Schema::hasTable('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->count() : 0,
            'alumni_lulus' => Schema::hasTable('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->where('status_keluar', 'Alumni')->count() : 0,
            'alumni_mutasi' => Schema::hasTable('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->where('status_keluar', '<>', 'Alumni')->count() : 0,
            'gtk' => Schema::hasTable('gtk') ? DB::table('gtk')->count() : 0,
            'rombongan_belajar' => Schema::hasTable('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            'pembelajaran' => Schema::hasTable('pembelajaran') ? DB::table('pembelajaran')->count() : 0,
            'foto_count' => $fotoCount,
        ];

        return view('dashboard.maintenance', compact('settings', 'hasActiveData', 'isArchiveRequired', 'lastSync', 'archiveDownloadedAt', 'syncAllowed', 'counts'));
    }

    public function downloadArchive()
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));
        }

        $adminName = is_array($user) ? ($user['nama'] ?? ($user['username'] ?? 'Admin')) : ($user->nama ?? ($user->username ?? 'Admin'));

        try {
            $backupService = new BackupService();
            $result = $backupService->createComprehensiveBackup($adminName);

            // Tandai di database bahwa arsip telah diunduh dan izin sinkronisasi dibuka
            DB::table('settings')->where('id', 1)->update([
                'archive_downloaded_at' => now(),
                'archive_file_name' => $result['filename'],
                'sync_allowed' => true,
                'updated_at' => now(),
            ]);

            return response()->download($result['zip_path'], $result['filename'], [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses paket arsip: ' . $e->getMessage());
        }
    }

    /**
     * Generator ZIP murni PHP tanpa ekstensi ZipArchive (100% fail-safe)
     */
    private function createPurePhpZip(string $zipPath, array $files): bool
    {
        $zipData = '';
        $centralDir = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $uncompressedSize = strlen($content);
            $crc = crc32($content);
            $hasGz = function_exists('gzdeflate');
            $compressed = $hasGz ? gzdeflate($content) : $content;
            $compressedSize = strlen($compressed);
            $method = $hasGz ? "\x08\x00" : "\x00\x00"; // 8 = deflate, 0 = store

            $time = time();
            $dtime = dechex((date('Y', $time) - 1980) << 25 | date('m', $time) << 21 | date('d', $time) << 16 |
                date('H', $time) << 11 | date('i', $time) << 5 | date('s', $time) >> 1);
            $dtime = str_pad($dtime, 8, '0', STR_PAD_LEFT);
            $hexdtime = chr(hexdec(substr($dtime, 6, 2))) . chr(hexdec(substr($dtime, 4, 2))) .
                chr(hexdec(substr($dtime, 2, 2))) . chr(hexdec(substr($dtime, 0, 2)));

            // Local file header
            $fr = "\x50\x4b\x03\x04\x14\x00\x00\x00" . $method . $hexdtime
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)
                . $name
                . $compressed;

            $zipData .= $fr;

            // Central directory record
            $cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00" . $method . $hexdtime
                . pack('V', $crc)
                . pack('V', $compressedSize)
                . pack('V', $uncompressedSize)
                . pack('v', strlen($name))
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('v', 0)
                . pack('V', 32)
                . pack('V', $offset)
                . $name;

            $centralDir .= $cdrec;
            $offset = strlen($zipData);
        }

        // End of central directory record
        $endOfCentral = "\x50\x4b\x05\x06\x00\x00\x00\x00"
            . pack('v', count($files))
            . pack('v', count($files))
            . pack('V', strlen($centralDir))
            . pack('V', $offset)
            . "\x00\x00";

        return (bool) file_put_contents($zipPath, $zipData . $centralDir . $endOfCentral);
    }

    public function cleanOldData(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        try {
            // Bersihkan sesi kedaluwarsa jika ada
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('last_activity', '<', time() - (86400 * 7))->delete();
            }

            // Bersihkan file sementara di storage/app/archives
            $tempDir = storage_path('app/archives');
            if (File::isDirectory($tempDir)) {
                $files = File::files($tempDir);
                foreach ($files as $f) {
                    @unlink($f->getRealPath());
                }
            }

            // Bersihkan file sesi file driver yang kadaluarsa (> 7 hari)
            $sessionDir = storage_path('framework/sessions');
            if (File::isDirectory($sessionDir)) {
                $files = File::files($sessionDir);
                $cutoff = time() - (86400 * 7);
                foreach ($files as $f) {
                    if ($f->getFilename() !== '.gitignore' && $f->getMTime() < $cutoff) {
                        @unlink($f->getRealPath());
                    }
                }
            }

            // Clear framework cache
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');

            return response()->json([
                'status' => 'success',
                'message' => 'Pembersihan cache aplikasi, sesi kadaluarsa, dan file residu berhasil diselesaikan.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membersihkan sistem: ' . $e->getMessage(),
            ], 500);
        }
    }
}

