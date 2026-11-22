<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Services\BackupService;
use App\Services\RestoreService;


use App\Support\Table;

class MaintenanceController extends Controller
{
    private function checkAdmin()
    {
        $user = session('user');
        if (!$user) {
            return request()->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Sesi login berakhir. Silakan masuk kembali lalu muat ulang halaman.'], 401)
                : redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return request()->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Akses ditolak: hanya administrator.'], 403)
                : redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));
        }

        return null;
    }

    public function index()
    {
        if ($res = $this->checkAdmin()) return $res;

        $settings = DB::table('settings')->where('id', 1)->first();
        $hasActiveData = (Table::exists('peserta_didik') && DB::table('peserta_didik')->count() > 0)
            || (Table::exists('rombongan_belajar') && DB::table('rombongan_belajar')->count() > 0)
            || (Table::exists('gtk') && DB::table('gtk')->count() > 0);

        $lastSync = $settings->last_sync ?? null;
        $archiveDownloadedAt = $settings->archive_downloaded_at ?? null;
        $syncAllowed = (bool)($settings->sync_allowed ?? false);

        // Perlu unduh arsip jika ada data aktif dan (belum pernah unduh arsip atau izin sinkronisasi terkunci)
        $isArchiveRequired = $hasActiveData && (!$archiveDownloadedAt || !$syncAllowed);

        $fotoDir = storage_path('app/public/assets/peserta-didik/foto');
        $fotoCount = File::isDirectory($fotoDir) ? count(File::files($fotoDir)) : 0;

        $counts = [
            'peserta_didik' => Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0,
            'peserta_didik_tidak_aktif' => Table::exists('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->count() : 0,
            'alumni_lulus' => Table::exists('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->where('status_keluar', 'Alumni')->count() : 0,
            'alumni_mutasi' => Table::exists('peserta_didik_tidak_aktif') ? DB::table('peserta_didik_tidak_aktif')->where('status_keluar', '<>', 'Alumni')->count() : 0,
            'gtk' => Table::exists('gtk') ? DB::table('gtk')->count() : 0,
            'rombongan_belajar' => Table::exists('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            'pembelajaran' => Table::exists('pembelajaran') ? DB::table('pembelajaran')->count() : 0,
            'foto_count' => $fotoCount,
        ];

        $tab = in_array(request('tab'), ['backup', 'restore'], true) ? request('tab') : 'backup';

        return view('dashboard.maintenance', compact('settings', 'hasActiveData', 'isArchiveRequired', 'lastSync', 'archiveDownloadedAt', 'syncAllowed', 'counts', 'tab'));
    }

    /**
     * Unduh dump SQL LENGKAP (seluruh tabel database) saja — ringkas & cepat.
     */
    public function downloadSql()
    {
        if ($res = $this->checkAdmin()) return $res;

        try {
            $result = (new BackupService())->createSqlBackup($this->adminName());
            $this->markArchiveDownloaded($result['filename']);

            return response()->download($result['path'], $result['filename'], [
                'Content-Type' => 'application/sql',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat dump SQL: ' . $e->getMessage());
        }
    }

    /**
     * Buat dump SQL via AJAX untuk tab Backup. Mengembalikan JSON berisi URL
     * unduhan sementara; berkas di-stream oleh fetchFile().
     */
    public function backup(Request $request)
    {
        if ($res = $this->checkAdmin()) return $res;

        // Tutup file session PHP agar request concurrent/SSE tidak deadlock atau worker starvation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Proses arsip bisa memakan waktu/memori; pastikan tidak dibunuh batas server.
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        try {
            $result = (new BackupService())->createSqlBackup($this->adminName());
            $this->markArchiveDownloaded($result['filename']);

            return response()->json([
                'status'       => 'success',
                'message'      => 'Dump SQL lengkap (seluruh tabel) berhasil dibuat & kunci feeder dibuka.',
                'filename'     => $result['filename'],
                'download_url' => route('dashboard.maintenance.fetch', ['filename' => $result['filename']], false),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses dump SQL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stream berkas arsip yang sudah dibuat di storage/app/archives lalu hapus setelah dikirim.
     */
    public function fetchFile(string $filename)
    {
        if ($res = $this->checkAdmin()) return $res;

        $safe = basename($filename);
        $path = storage_path('app/archives/' . $safe);

        if (!File::exists($path)) {
            abort(404, 'Berkas arsip tidak ditemukan atau sudah diunduh.');
        }

        return response()->download($path, $safe, [
            'Content-Type' => 'application/sql',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Pulihkan database dari unggahan .sql atau .zip paket arsip SAE.
     */
    public function restore(Request $request)
    {
        if ($res = $this->checkAdmin()) return $res;

        $request->validate([
            'restore_file' => 'required|file|max:512000',
        ], [
            'restore_file.required' => 'Pilih berkas arsip (.sql / .zip) yang akan dipulihkan.',
        ]);

        $file = $request->file('restore_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['sql', 'zip', 'txt'], true)) {
            $msg = 'Format berkas tidak didukung. Gunakan .sql atau .zip hasil arsip SAE.';

            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => $msg], 422)
                : redirect(route('dashboard.maintenance.index', ['tab' => 'restore'], false))->with('error', $msg);
        }

        try {
            $result = (new RestoreService())->restore($file);

            $msg = 'Restore database berhasil dari "' . $result['filename'] . '" (' . number_format($result['tables']) . ' tabel).';
            if (!empty($result['snapshot'])) {
                $msg .= ' Salinan pengaman sebelum restore tersimpan sebagai ' . $result['snapshot'] . '.';
            }

            return $request->expectsJson()
                ? response()->json(['status' => 'success', 'message' => $msg])
                : redirect(route('dashboard.maintenance.index', ['tab' => 'restore'], false))->with('success', $msg);
        } catch (\Throwable $e) {
            $msg = 'Gagal memulihkan database: ' . $e->getMessage();

            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => $msg], 500)
                : redirect(route('dashboard.maintenance.index', ['tab' => 'restore'], false))->with('error', $msg);
        }
    }

    private function adminName(): string
    {
        $user = session('user');

        return is_array($user)
            ? ($user['nama'] ?? ($user['username'] ?? 'Admin'))
            : ($user->nama ?? ($user->username ?? 'Admin'));
    }

    private function markArchiveDownloaded(string $filename): void
    {
        DB::table('settings')->where('id', 1)->update([
            'archive_downloaded_at' => now(),
            'archive_file_name'     => $filename,
            'sync_allowed'          => true,
            'updated_at'            => now(),
        ]);
    }

    public function cleanOldData(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        try {
            // Bersihkan sesi kedaluwarsa jika ada
            if (Table::exists('sessions')) {
                DB::table('sessions')->where('last_activity', '<', time() - (86400 * 7))->delete();
            }

            // Bersihkan file sementara di storage/app/archives (termasuk subfolder build)
            $tempDir = storage_path('app/archives');
            if (File::isDirectory($tempDir)) {
                File::cleanDirectory($tempDir);
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

