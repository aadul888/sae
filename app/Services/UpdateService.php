<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ZipArchive;


use App\Support\Table;

class UpdateService
{
    const CURRENT_VERSION = '1.0.6';
    const GITHUB_REPO = 'aadul888/sae-smk';

    /**
     * Cek status update sistem (git remote / GitHub API, atau pending migrasi)
     */
    public function checkUpdate(): array
    {
        // Non-interaktif: cegah git menunggu input kredensial. putenv() berfungsi
        // di Linux maupun Windows; prefiks "VAR=x cmd" hanya valid di shell POSIX.
        @putenv('GIT_TERMINAL_PROMPT=0');

        $setting = Table::exists('settings') ? DB::table('settings')->where('id', 1)->first() : null;
        // Versi = konstanta kode; settings.app_version hanya mirror (ditulis saat update via updater).
        $currentVersion = self::CURRENT_VERSION;
        $lastUpdateAt = $setting->last_update_at ?? null;

        $hasGit = is_dir(base_path('.git'));
        $gitBranch = 'main';
        $gitHead = $this->getLocalCommit($setting);
        // Baseline sebenarnya = commit yang BENAR-BENAR terpasang. Bila deploy
        // memakai fallback ZIP (git pull gagal), HEAD lokal tidak bergerak dan
        // settings.last_commit_hash yang maju; jadi bandingkan dengan yang lebih baru.
        $localCommit = $this->resolveDeployedCommit($setting, $gitHead);
        $remoteCommit = '';
        $behindCount = 0;
        $updatesAvailable = false;
        $remoteVersion = $currentVersion;
        $changes = [];

        // 1. Cek dari Git CLI jika repositori lokal adalah git
        if ($hasGit) {
            try {
                $basePath = base_path();
                $gitCmd = 'git -C ' . escapeshellarg($basePath);

                // Pastikan safe.directory terdaftar
                @shell_exec('git config --global --add safe.directory ' . escapeshellarg($basePath) . ' 2>&1');

                $gitBranch = trim(@shell_exec($gitCmd . ' rev-parse --abbrev-ref HEAD 2>&1') ?: 'main');
                if (str_contains($gitBranch, ' ') || str_contains($gitBranch, 'fatal:')) {
                    $gitBranch = 'main';
                }
                @shell_exec($gitCmd . ' fetch origin ' . escapeshellarg($gitBranch) . ' 2>&1');

                // Hitung selisih dari baseline terpasang, bukan dari HEAD mentah.
                $rangeBase = $localCommit !== '' ? $localCommit : 'HEAD';
                $behindCount = (int) trim(@shell_exec($gitCmd . ' rev-list --count ' . escapeshellarg($rangeBase) . '..origin/' . escapeshellarg($gitBranch) . ' 2>&1') ?: '0');
                if ($behindCount > 0) {
                    $updatesAvailable = true;
                    $changesRaw = @shell_exec($gitCmd . ' log ' . escapeshellarg($rangeBase) . '..origin/' . escapeshellarg($gitBranch) . ' --oneline -n 10 2>&1');
                    if ($changesRaw && !str_contains($changesRaw, 'fatal:')) {
                        $changes = array_filter(explode("\n", trim($changesRaw)));
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to GitHub API
            }
        }

        // 2. Fallback cek via GitHub REST API jika git CLI nihil/gagal/non-git
        if (!$updatesAvailable) {
            $remoteData = $this->fetchGitHubLatestCommit($gitBranch);
            if ($remoteData && !empty($remoteData['sha'])) {
                $remoteCommit = substr($remoteData['sha'], 0, 7);
                if (!$this->commitMatches($remoteData['sha'], $localCommit)) {
                    $updatesAvailable = true;
                    $behindCount = 1;
                    if (!empty($remoteData['message'])) {
                        $firstLine = explode("\n", trim($remoteData['message']))[0];
                        $changes[] = $remoteCommit . ' ' . $firstLine;
                    }
                }
            }
        }

        // 3. Cek database migrations yang pending
        $pendingMigrations = $this->getPendingMigrations();

        return [
            'current_version' => $currentVersion,
            'remote_version' => $remoteVersion,
            'last_update_at' => $lastUpdateAt,
            'has_git' => $hasGit,
            'git_branch' => $gitBranch,
            'git_commit' => $localCommit ? substr($localCommit, 0, 7) : '-',
            'remote_commit' => $remoteCommit ?: '-',
            'behind_count' => $behindCount,
            'updates_available' => $updatesAvailable || !empty($pendingMigrations),
            'has_git_updates' => $updatesAvailable,
            'pending_migrations' => $pendingMigrations,
            'changes' => $changes,
        ];
    }

    /**
     * Dapatkan hash commit lokal terkini
     */
    protected function getLocalCommit($setting = null): string
    {
        $dbHash = $setting->last_commit_hash ?? '';

        if (is_dir(base_path('.git'))) {
            $basePath = base_path();
            @shell_exec('git config --global --add safe.directory ' . escapeshellarg($basePath) . ' 2>&1');
            $cliHash = trim(@shell_exec('git -C ' . escapeshellarg($basePath) . ' rev-parse HEAD 2>&1') ?: '');
            if (preg_match('/^[a-f0-9]{40}$/i', $cliHash)) {
                return $cliHash;
            }

            // Fallback baca file refs
            $headPath = base_path('.git/HEAD');
            if (is_file($headPath)) {
                $headContent = trim((string) @file_get_contents($headPath));
                if (preg_match('/^[a-f0-9]{40}$/i', $headContent)) {
                    return !empty($dbHash) ? $dbHash : $headContent;
                }
                if (str_starts_with($headContent, 'ref: ')) {
                    $ref = trim(substr($headContent, 5));
                    $refFile = base_path('.git/' . $ref);
                    if (is_file($refFile)) {
                        return !empty($dbHash) ? $dbHash : trim((string) @file_get_contents($refFile));
                    }
                }
            }
        }

        return $dbHash;
    }

    /**
     * Commit yang benar-benar terpasang. Ambil yang lebih baru antara HEAD git
     * dan settings.last_commit_hash (hasil deploy ZIP saat git pull gagal).
     */
    protected function resolveDeployedCommit($setting, string $gitHead): string
    {
        $dbHash = trim((string) ($setting->last_commit_hash ?? ''));
        $valid = static fn (?string $h): bool => is_string($h) && (bool) preg_match('/^[a-f0-9]{40}$/i', $h);

        if (!$valid($gitHead)) {
            return $valid($dbHash) ? $dbHash : $gitHead;
        }
        if (!$valid($dbHash) || strcasecmp($gitHead, $dbHash) === 0) {
            return $gitHead;
        }

        // Repositori git tersedia: pilih yang merupakan keturunan (lebih baru).
        if (is_dir(base_path('.git'))) {
            $gitCmd = 'git -C ' . escapeshellarg(base_path());
            @shell_exec('git config --global --add safe.directory ' . escapeshellarg(base_path()) . ' 2>&1');
            if ($this->isAncestor($gitCmd, $dbHash, $gitHead)) {
                return $gitHead; // HEAD memuat commit DB → HEAD lebih baru
            }
            if ($this->isAncestor($gitCmd, $gitHead, $dbHash)) {
                return $dbHash; // deploy ZIP sudah maju, HEAD tertinggal
            }
        }

        // Tidak bisa dibandingkan: pakai yang dicatat updater (paling baru dipasang).
        return $dbHash;
    }

    /** True bila $ancestor adalah leluhur $descendant menurut git. */
    protected function isAncestor(string $gitCmd, string $ancestor, string $descendant): bool
    {
        // Pakai penanda keluaran, bukan exit code: shell_exec bekerja walau exec()
        // dimatikan pada disable_functions server sekolah.
        $out = (string) @shell_exec(
            $gitCmd . ' merge-base --is-ancestor '
            . escapeshellarg($ancestor) . ' ' . escapeshellarg($descendant) . ' 2>&1 && echo __SAE_YES__'
        );

        return str_contains($out, '__SAE_YES__');
    }

    /** True bila hash remote sama dengan baseline terpasang (prefix 7 char). */
    protected function commitMatches(string $remoteFull, string $localFull): bool
    {
        $remote = strtolower(trim($remoteFull));
        $local = strtolower(trim($localFull));
        if ($local === '') {
            return false;
        }

        return substr($remote, 0, 7) === substr($local, 0, 7);
    }

    /**
     * Ambil info commit terbaru langsung dari GitHub API
     */
    protected function fetchGitHubLatestCommit(string $branch = 'main'): ?array
    {
        try {
            $token = config('services.github.token') ?: env('GITHUB_TOKEN');
            $url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/commits/' . $branch;

            $request = Http::timeout(10)->withHeaders([
                'User-Agent' => 'SAE-System-Updater',
                'Accept' => 'application/vnd.github+json',
            ]);

            if ($token) {
                $request->withToken($token);
            }

            $response = $request->get($url);
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'sha' => $data['sha'] ?? '',
                    'message' => $data['commit']['message'] ?? '',
                    'date' => $data['commit']['committer']['date'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            // Ignore API connection errors
        }

        return null;
    }

    /**
     * Dapatkan daftar migrasi yang belum dieksekusi
     */
    public function getPendingMigrations(): array
    {
        try {
            if (!Table::exists('migrations')) {
                return [];
            }

            $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();
            $migrationFiles = File::files(database_path('migrations'));

            $pending = [];
            foreach ($migrationFiles as $file) {
                $name = $file->getBasename('.php');
                if (!in_array($name, $ranMigrations)) {
                    $pending[] = $name;
                }
            }

            return $pending;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Jalankan proses update otomatis:
     * 1. Pull Git atau Download & Extract ZIP dari GitHub
     * 2. Migrate Database
     * 3. Catat versi & hash commit baru
     * 4. Clear & optimize Cache
     */
    public function runUpdate(): array
    {
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');

        // Kontrol pusat (sae-core): bila distribusi update untuk aplikasi ini
        // dimatikan, tolak pembaruan. Fail-open saat sae-core tak terjangkau.
        if (!\App\Services\CoreControlService::updatesAllowed()) {
            return [
                'success' => false,
                'message' => 'Distribusi pembaruan untuk aplikasi ini sedang dinonaktifkan oleh pusat (sae-core).',
                'logs' => ['[BLOKIR] update_enabled = false dari sae-core.'],
            ];
        }

        $logs = [];
        $success = true;
        $newCommit = null;
        $zipResult = null;
        $codeSynced = true;

        // 0. Pra-perbaikan hak akses agar git pull & zip fallback tidak Permission Denied
        $this->ensurePermissions();

        // Non-interaktif untuk seluruh pemanggilan git di proses ini (portable).
        @putenv('GIT_TERMINAL_PROMPT=0');

        // 1. Sync file kode
        if (is_dir(base_path('.git'))) {
            $basePath = base_path();
            $gitCmd = 'git -C ' . escapeshellarg($basePath);

            // 1a. Tambahkan safe.directory agar git tidak menolak operasi
            @shell_exec('git config --global --add safe.directory ' . escapeshellarg($basePath) . ' 2>&1');
            $logs[] = "[SAFE DIR] Registered " . $basePath;

            $branch = trim(@shell_exec($gitCmd . ' rev-parse --abbrev-ref HEAD 2>&1') ?: 'main');
            if (str_contains($branch, ' ') || str_contains($branch, 'fatal:')) {
                $branch = 'main';
            }

            // 1b. Reset file lokal yang termodifikasi (storage/.gitignore dll) agar tidak block pull
            @shell_exec($gitCmd . ' reset --hard HEAD 2>&1');
            @shell_exec($gitCmd . ' checkout -- . 2>&1');
            $logs[] = "[GIT RESET] Local changes reset sebelum pull.";

            // 1c. Fetch dulu untuk memastikan remote refs up to date
            $fetchOut = trim(@shell_exec($gitCmd . ' fetch origin ' . escapeshellarg($branch) . ' 2>&1') ?: '');
            if ($fetchOut) {
                $logs[] = "[GIT FETCH] " . $fetchOut;
            }

            // Deteksi kendala permission fatal pada repository git (misal direktori .git/objects dimiliki root)
            $hasFetchPermissionError = !empty($fetchOut) && (
                str_contains($fetchOut, 'insufficient permission') ||
                str_contains($fetchOut, 'Permission denied') ||
                str_contains($fetchOut, 'failed to write object') ||
                str_contains($fetchOut, 'unpack-objects failed') ||
                str_contains($fetchOut, 'fatal: unable to access')
            );

            if ($hasFetchPermissionError) {
                $logs[] = "[GIT PERMISSION NOTICE] Akses tulis direktori .git/objects ditolak sistem (dimiliki root/user lain).";
                $logs[] = "[GIT PERMISSION TIP] Solusi di terminal server: jalankan 'chown -R www:www " . $basePath . "' agar Git CLI berfungsi kembali.";
            }

            $gitPullSuccess = false;

            // 1d. Coba pull hanya jika fetch tidak mengalami error permission fatal
            if (!$hasFetchPermissionError) {
                $pullOutput = @shell_exec($gitCmd . ' pull --no-rebase origin ' . escapeshellarg($branch) . ' 2>&1');
                $pullText = trim($pullOutput ?: 'No output');
                $logs[] = "[GIT PULL ($branch)] " . $pullText;

                $pullHasError = str_contains($pullText, 'fatal:') || str_contains($pullText, 'error:') || str_contains($pullText, 'Aborting') || str_contains($pullText, 'insufficient permission');
                $gitPullSuccess = ($pullOutput !== null && !$pullHasError);

                // 1e. Jika git pull gagal karena untracked working tree files, merge conflict, dll:
                if (!$gitPullSuccess) {
                    $logs[] = "[GIT SYNC] Terdeteksi kendala merge/untracked files. Melakukan hard reset ke origin/$branch...";
                    $resetOut = trim(@shell_exec($gitCmd . ' reset --hard origin/' . escapeshellarg($branch) . ' 2>&1') ?: '');
                    $logs[] = "[GIT RESET --HARD] " . $resetOut;

                    @shell_exec($gitCmd . ' clean -fd 2>&1');

                    $checkHead = trim(@shell_exec($gitCmd . ' rev-parse HEAD 2>&1') ?: '');
                    $checkOrigin = trim(@shell_exec($gitCmd . ' rev-parse origin/' . escapeshellarg($branch) . ' 2>&1') ?: '');

                    if ($checkHead && $checkOrigin && $checkHead === $checkOrigin && !str_contains($resetOut, 'fatal:') && !str_contains($resetOut, 'error:')) {
                        $gitPullSuccess = true;
                        $logs[] = "[GIT SUCCESS] Repositori berhasil disinkronkan ke commit " . substr($checkHead, 0, 7);
                    }
                }
            } else {
                $logs[] = "[GIT PERMISSION BYPASS] Melewati git pull/reset karena repository lokal terkendala hak akses filesystem.";
            }

            // 1f. Jika git pull gagal atau permission error: otomatis fallback deploy ZIP dari GitHub
            if (!$gitPullSuccess) {
                $logs[] = "[FALLBACK] Git sync terkendala, otomatis beralih ke Fallback ZIP Deployment dari GitHub...";
                $this->ensurePermissions();
                $zipResult = $this->deployFromGitHubZip($branch);
                foreach ($zipResult['logs'] as $zl) {
                    $logs[] = $zl;
                }
                if ($zipResult['success']) {
                    $newCommit = $zipResult['sha'] ?? null;
                    $logs[] = "[FALLBACK SUCCESS] Berhasil menyinkronkan file kode ke commit GitHub: " . ($newCommit ? substr($newCommit, 0, 7) : 'terbaru');
                } else {
                    // Kode TIDAK utuh: jangan lanjut migrate/catat versi, agar tidak
                    // tercatat "sudah update" padahal isi masih separuh.
                    $success = false;
                    $codeSynced = false;
                }
            }

            if ($codeSynced && (empty($newCommit) || str_contains($newCommit, 'fatal:'))) {
                $newCommit = trim(@shell_exec($gitCmd . ' rev-parse HEAD 2>&1') ?: '');
                if (empty($newCommit) || str_contains($newCommit, 'fatal:')) {
                    $newCommit = $zipResult['sha'] ?? null;
                }
            }
        } else {
            // Standalone / Non-Git mode: Unduh ZIP dari GitHub & timpa file
            $this->ensurePermissions();
            $zipResult = $this->deployFromGitHubZip('main');
            foreach ($zipResult['logs'] as $zl) {
                $logs[] = $zl;
            }
            if (!$zipResult['success']) {
                $success = false;
                $codeSynced = false;
            }
            $newCommit = $zipResult['sha'] ?? null;
        }

        if (!$codeSynced) {
            $logs[] = "[BERHENTI] Sinkronisasi file kode gagal. Migrasi & pencatatan versi dilewati. "
                . "Perbaiki izin tulis (mis. 'chown -R www:www " . base_path() . "' atau 'chmod -R 775') "
                . "lalu jalankan pembaruan lagi.";
        }

        // 2. Sinkronkan dependensi Composer SEBELUM migrasi.
        //    Langkah ini wajib: bila kode sudah diperbarui tetapi folder vendor
        //    masih versi lama, artisan akan gagal dengan error seperti
        //    "Method Illuminate\Database\MySqlConnection::hasDirectConnection
        //    does not exist" karena framework baru memanggil API yang belum ada
        //    di vendor lama.
        $depsOk = false;
        if ($codeSynced) {
            $depsOk = $this->syncComposerDependencies($logs);
            if (!$depsOk) {
                $success = false;
                $logs[] = "[DEPS] Dependensi Composer gagal disinkronkan. Migrasi dilewati "
                    . "agar database tidak berubah dengan vendor yang tidak sesuai.";
            }
        }

        // 3. Jalankan Migrasi Database (hanya bila kode & dependensi siap)
        if ($codeSynced && $depsOk) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                $logs[] = "[MIGRATE] " . trim(Artisan::output());
            } catch (\Throwable $e) {
                $success = false;
                $logs[] = "[MIGRATE ERROR] " . $e->getMessage();
            }
        }

        // 4. Catat Versi & Waktu Update ke Database (hanya bila kode benar-benar terpasang)
        try {
            if ($codeSynced && Table::exists('settings')) {
                $commitShort = $newCommit ? substr($newCommit, 0, 7) : null;
                $updateData = [
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('settings', 'app_version')) {
                    $updateData['app_version'] = self::CURRENT_VERSION;
                }
                if (Schema::hasColumn('settings', 'last_update_at')) {
                    $updateData['last_update_at'] = now();
                }
                if (Schema::hasColumn('settings', 'last_commit_hash')) {
                    $updateData['last_commit_hash'] = $newCommit ?: null;
                }

                DB::table('settings')->where('id', 1)->update($updateData);
                $logs[] = "[VERSION] Versi " . self::CURRENT_VERSION . ($commitShort ? " ($commitShort)" : "") . " tersimpan.";
            }
        } catch (\Throwable $e) {
            $logs[] = "[VERSION ERROR] " . $e->getMessage();
        }

        // 5. Clear Caches, Symlink Storage & Optimize
        try {
            $storagePublic = public_path('storage');
            if (is_dir($storagePublic) && !is_link($storagePublic)) {
                $storageEntries = @scandir($storagePublic);
                if (is_array($storageEntries) && count(array_diff($storageEntries, ['.', '..'])) === 0) {
                    @rmdir($storagePublic);
                }
            }

            if (!file_exists($storagePublic) && !is_link($storagePublic)) {
                Artisan::call('storage:link');
                $logs[] = "[STORAGE] Symlink public/storage dibuat.";
            }
            Artisan::call('optimize:clear');
            $logs[] = "[OPTIMIZE] " . trim(Artisan::output());

            // Reset bytecode cache OPcache agar perubahan PHP langsung aktif di RAM server
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $logs[] = "[OPCACHE] Bytecode memory cache berhasil dibersihkan.";
            }

            @clearstatcache(true);

            // Trigger reload worker LiteSpeed / OpenLiteSpeed jika berjalan di lingkungan OLS/LSPHP
            if (file_exists('/tmp/lshttpd/')) {
                @touch(base_path('.lsphp_restart.txt'));
                $logs[] = "[LITESPEED] LSPHP reload marker dibuat.";
            }
        } catch (\Throwable $e) {
            $logs[] = "[OPTIMIZE NOTICE] " . $e->getMessage();
        }

        // 6. Normalisasi Permission storage & cache setelah update
        $this->ensurePermissions();

        // 7. Otomatis laporkan pembaruan versi ke sae-core
        try {
            \App\Services\MonitoringReporterService::reportAsync();
            $logs[] = "[MONITORING] Laporan status pasca-update terkirim ke sae-core.";
        } catch (\Throwable $e) {}

        return [
            'success' => $success,
            'logs' => $logs,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    /**
     * Sinkronkan dependensi Composer setelah kode diperbarui.
     *
     * Latar belakang masalah: proses update sebelumnya hanya melakukan git pull
     * lalu langsung `migrate`. Bila kode sudah naik ke Laravel 13 tetapi folder
     * `vendor/` masih Laravel 12, migrasi gagal dengan pesan:
     *
     *   Method Illuminate\Database\MySqlConnection::hasDirectConnection does not exist.
     *
     * Metode itu baru ada di framework versi lebih baru, sehingga vendor harus
     * disinkronkan lebih dulu.
     *
     * @param  array<int, string>  $logs  Ditambahkan secara by-reference.
     * @return bool  True bila dependensi siap dipakai.
     */
    protected function syncComposerDependencies(array &$logs): bool
    {
        // Deteksi apakah lock file lebih baru dari autoload hasil install.
        $lockFile = base_path('composer.lock');
        $installedFile = base_path('vendor/composer/installed.json');

        if (!file_exists($lockFile)) {
            $logs[] = "[DEPS] composer.lock tidak ada — sinkronisasi dependensi dilewati.";
            return true;
        }

        $needsInstall = !file_exists($installedFile);

        // Bandingkan versi framework terpasang dengan yang diminta composer.lock.
        // Perbandingan versi lebih andal daripada mtime, karena git pull selalu
        // memperbarui mtime composer.lock sehingga cek mtime akan memicu
        // composer install pada setiap update meski versi sebenarnya sama.
        $lock = json_decode((string) @file_get_contents($lockFile), true);
        $lockedFramework = null;
        foreach (($lock['packages'] ?? []) as $pkg) {
            if (($pkg['name'] ?? '') === 'laravel/framework') {
                $lockedFramework = $pkg['version'] ?? null;
                break;
            }
        }

        $installedFramework = null;
        if (file_exists($installedFile)) {
            $installed = json_decode((string) @file_get_contents($installedFile), true);
            foreach (($installed['packages'] ?? []) as $pkg) {
                if (($pkg['name'] ?? '') === 'laravel/framework') {
                    $installedFramework = $pkg['version'] ?? null;
                    break;
                }
            }
        }

        if ($lockedFramework !== null && $installedFramework !== null && $lockedFramework !== $installedFramework) {
            $needsInstall = true;
            $logs[] = "[DEPS] Versi framework tidak sesuai: terpasang {$installedFramework}, dibutuhkan {$lockedFramework}.";
        }

        // Bila versi framework sama, periksa apakah ada paket lain yang berubah
        // dengan membandingkan sidik jari daftar paket pada lock vs terpasang.
        if (!$needsInstall && $lockedFramework !== null && $installedFramework !== null) {
            $lockedNames = [];
            foreach (($lock['packages'] ?? []) as $pkg) {
                $lockedNames[] = ($pkg['name'] ?? '') . '@' . ($pkg['version'] ?? '');
            }
            $installedNames = [];
            foreach (($installed['packages'] ?? []) as $pkg) {
                $installedNames[] = ($pkg['name'] ?? '') . '@' . ($pkg['version'] ?? '');
            }
            sort($lockedNames);
            sort($installedNames);

            if ($lockedNames !== $installedNames) {
                $needsInstall = true;
                $logs[] = "[DEPS] Daftar paket berbeda antara composer.lock dan vendor/.";
            }
        }

        if (!$needsInstall) {
            $logs[] = "[DEPS] Dependensi sudah sesuai ({$installedFramework}).";
            return true;
        }

        // Composer hanya dicari saat benar-benar perlu install. Bila dependensi
        // sudah sesuai, ketiadaan Composer bukan kegagalan (dulu selalu gagal).
        $composerCmd = $this->findComposer();
        if ($composerCmd === null) {
            $logs[] = "[DEPS] Dependensi berubah tetapi Composer tidak ditemukan. Jalankan manual: composer install --no-dev --optimize-autoloader";
            return false;
        }

        // Jalankan composer install. Batas waktu diperpanjang karena proses ini berat.
        @set_time_limit(600);
        $basePath = escapeshellarg(base_path());
        $output = @shell_exec(
            'cd ' . $basePath . ' && COMPOSER_MEMORY_LIMIT=-1 ' . $composerCmd
            . ' install --no-dev --optimize-autoloader --no-interaction 2>&1'
        );

        $output = trim((string) $output);
        if ($output !== '') {
            $logs[] = "[DEPS] " . $output;
        }

        // Verifikasi hasil: framework harus sesuai composer.lock.
        $verifyInstalled = null;
        if (file_exists($installedFile)) {
            $installed = json_decode((string) @file_get_contents($installedFile), true);
            foreach (($installed['packages'] ?? []) as $pkg) {
                if (($pkg['name'] ?? '') === 'laravel/framework') {
                    $verifyInstalled = $pkg['version'] ?? null;
                    break;
                }
            }
        }

        if ($lockedFramework !== null && $verifyInstalled !== $lockedFramework) {
            $logs[] = "[DEPS ERROR] composer install selesai tetapi framework masih {$verifyInstalled} "
                . "(dibutuhkan {$lockedFramework}). Periksa izin tulis folder vendor/ atau jalankan manual di server.";
            return false;
        }

        // Muat ulang autoloader agar kelas baru langsung dikenali proses ini.
        if (file_exists(base_path('vendor/autoload.php'))) {
            @require_once base_path('vendor/autoload.php');
        }

        $logs[] = "[DEPS] Dependensi berhasil disinkronkan ke framework {$verifyInstalled}.";
        return true;
    }

    /**
     * Cari Composer yang benar-benar bisa dijalankan (global, composer.phar, aaPanel).
     * Kembalikan perintah lengkap atau null bila tidak ada.
     */
    protected function findComposer(): ?string
    {
        $candidates = [
            'composer',
            'php ' . escapeshellarg(base_path('composer.phar')),
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(base_path('composer.phar')),
        ];

        // aaPanel menyimpan composer di dalam folder PHP-nya.
        foreach (glob('/www/server/php/*/bin/composer') ?: [] as $aaComposer) {
            $candidates[] = escapeshellarg($aaComposer);
        }

        foreach ($candidates as $candidate) {
            $probe = trim(@shell_exec($candidate . ' --version 2>&1') ?: '');
            if ($probe !== '' && stripos($probe, 'composer') !== false && stripos($probe, 'not found') === false) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Tulis paksa satu file ketika copy/file_put_contents biasa gagal karena
     * file lama dimiliki user lain (mis. root). Strategi: pastikan direktori
     * dapat ditulis, hapus file lama (butuh izin direktori saja), lalu salin
     * ulang; terakhir coba pindahkan dari folder ekstraksi.
     */
    protected function forceWriteFile(string $src, string $dest, string $rel): bool
    {
        $dir = dirname($dest);
        @chmod($dir, 0777);
        @chmod($dest, 0666);

        // Slot dikosongkan dulu: menulis file baru tak terhalang file lama.
        @unlink($dest);
        if (@copy($src, $dest)) {
            return true;
        }

        // Salin isi secara manual setelah slot dikosongkan.
        $content = @file_get_contents($src);
        if ($content !== false && @file_put_contents($dest, $content) !== false) {
            return true;
        }

        // Upaya terakhir di server: paksa lewat sudo bila tersedia (file ber-owner root).
        if (DIRECTORY_SEPARATOR === '/' && $this->hasPasswordlessSudo()) {
            @shell_exec('sudo -n cp -f ' . escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1');
            if (is_file($dest) && @filesize($dest) === @filesize($src)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pastikan permission storage, cache, .git, dan direktori web writable oleh PHP.
     * Termasuk upaya memperbaiki file yang dimiliki root (mis. sisa akses SSH)
     * agar web user bisa menimpanya saat update.
     */
    protected function ensurePermissions(): void
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            return;
        }

        $base = escapeshellarg(base_path());
        $storage = escapeshellarg(storage_path());
        $cache = escapeshellarg(base_path('bootstrap/cache'));
        $gitDir = escapeshellarg(base_path('.git'));

        // 1. Perbaiki kepemilikan bila memungkinkan: file sisa akses SSH sering
        //    dimiliki root, sehingga web user tak bisa menimpanya. Bila kita root,
        //    tulisan selalu berhasil. Bila bukan, coba lewat sudo NOPASSWD.
        $me = trim(@shell_exec('id -un 2>&1') ?: '');
        $myGroup = trim(@shell_exec('id -gn 2>&1') ?: '');
        if ($me !== '' && $me !== 'root' && $myGroup !== '') {
            $target = escapeshellarg($me . ':' . $myGroup);
            if ($this->hasPasswordlessSudo()) {
                @shell_exec('sudo -n chown -R ' . $target . ' ' . $base . ' 2>&1');
            }
        }

        // 2. Pastikan runtime Laravel selalu writable
        @shell_exec("chmod -R 777 $storage $cache 2>/dev/null");

        // 3. Pastikan repository .git writable agar git fetch/pull/unpack tidak permission denied
        if (is_dir(base_path('.git'))) {
            @shell_exec("chmod -R 777 $gitDir 2>/dev/null");
        }

        // 4. Pastikan folder target deploy dapat ditulis
        @shell_exec("chmod -R 777 " . escapeshellarg(public_path('img')) . " 2>/dev/null");
        @shell_exec("chmod -R 775 " . escapeshellarg(app_path()) . " " . escapeshellarg(resource_path()) . " " . escapeshellarg(database_path()) . " " . escapeshellarg(public_path()) . " " . escapeshellarg(base_path('routes')) . " 2>/dev/null");

        // 5. Bila masih ada yang tak bisa ditulis, coba lagi lewat sudo (file ber-owner root).
        if ($this->hasPasswordlessSudo() && !$this->isProjectWritable()) {
            @shell_exec('sudo -n chmod -R 775 ' . $base . ' 2>&1');
        }
    }

    /** True bila bisa memakai sudo tanpa prompt password (mis. user punya NOPASSWD). */
    protected function hasPasswordlessSudo(): bool
    {
        if (DIRECTORY_SEPARATOR !== '/') {
            return false;
        }

        $out = (string) @shell_exec('sudo -n true 2>&1 && echo __SAE_SUDO__');
        return str_contains($out, '__SAE_SUDO__');
    }

    /** Cek cepat apakah file aplikasi kunci dapat ditulis ulang. */
    protected function isProjectWritable(): bool
    {
        foreach ([base_path('routes/web.php'), base_path('artisan')] as $probe) {
            if (is_file($probe) && !is_writable($probe)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Download dan ekstrak ZIP repositori untuk server tanpa Git CLI
     */
    protected function deployFromGitHubZip(string $branch = 'main'): array
    {
        $logs = [];
        try {
            $token = config('services.github.token') ?: env('GITHUB_TOKEN');
            $zipUrl = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/zipball/' . $branch;

            $request = Http::timeout(120)->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'SAE-System-Updater',
            ]);
            if ($token) {
                $request->withToken($token);
            }

            $response = $request->get($zipUrl);
            if (!$response->successful()) {
                $logs[] = "[ZIP ERROR] Gagal mengunduh ZIP: HTTP " . $response->status();
                return ['success' => false, 'logs' => $logs, 'sha' => null];
            }

            $zipData = $response->body();
            $tmpZip = sys_get_temp_dir() . '/sae-update-' . uniqid() . '.zip';
            File::put($tmpZip, $zipData);

            $zip = new ZipArchive;
            if ($zip->open($tmpZip) !== true) {
                @unlink($tmpZip);
                $logs[] = "[ZIP ERROR] Gagal membuka file zip update.";
                return ['success' => false, 'logs' => $logs, 'sha' => null];
            }

            $tmpExtract = sys_get_temp_dir() . '/sae-extract-' . uniqid();
            $zip->extractTo($tmpExtract);
            $zip->close();
            @unlink($tmpZip);

            // Cari folder inner
            $dirs = File::directories($tmpExtract);
            $inner = !empty($dirs) ? $dirs[0] : null;
            if (!$inner || !is_dir($inner)) {
                $logs[] = "[ZIP ERROR] Struktur direktori ZIP tidak valid.";
                return ['success' => false, 'logs' => $logs, 'sha' => null];
            }

            // Sync files (kecuali .env, storage, node_modules)
            $excluded = ['.env', 'storage', 'node_modules', '.git'];
            $copied = 0;
            $failedCopies = [];
            $allFiles = File::allFiles($inner);
            foreach ($allFiles as $file) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($inner) + 1));

                $skip = false;
                foreach ($excluded as $exc) {
                    if ($rel === $exc || str_starts_with($rel, $exc . '/')) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) continue;

                $dest = base_path($rel);
                $destDir = dirname($dest);
                if (!is_dir($destDir)) {
                    @File::makeDirectory($destDir, 0777, true, true);
                }

                $src = $file->getPathname();

                // Isi sudah sama → lewati. Ini yang paling penting: tanpa cek ini
                // updater menulis ulang SEMUA file (1000+) setiap kali, sehingga
                // ribuan file ber-owner lain ikut gagal padahal tidak berubah.
                if (is_file($dest)
                    && @filesize($dest) === @filesize($src)
                    && @md5_file($dest) === @md5_file($src)) {
                    $copied++;
                    continue;
                }

                if (file_exists($dest) && !is_writable($dest)) {
                    @chmod($dest, 0666);
                }

                // File lama bisa dimiliki user lain (mis. root). Menghapus file
                // cukup butuh izin TULIS DIREKTORI, bukan kepemilikan file, lalu
                // file baru dibuat PHP dan otomatis writable.
                if (file_exists($dest)) {
                    @unlink($dest);
                }

                $ok = @copy($src, $dest);
                if (!$ok) {
                    $content = @file_get_contents($src);
                    if ($content !== false && @file_put_contents($dest, $content) !== false) {
                        $copied++;
                    } elseif ($this->forceWriteFile($src, $dest, $rel)) {
                        $copied++;
                    } else {
                        $failedCopies[] = $rel;
                    }
                } else {
                    $copied++;
                }
            }

            File::deleteDirectory($tmpExtract);

            if (!empty($failedCopies)) {
                $logs[] = "[ZIP NOTICE] $copied file berhasil disalin, " . count($failedCopies) . " file gagal izin tulis: " . implode(', ', array_slice($failedCopies, 0, 3));
            } else {
                $logs[] = "[ZIP SYNC] Berhasil menyalin $copied file pembaruan sistem.";
            }

            $latestCommit = $this->fetchGitHubLatestCommit($branch);

            // File yang gagal disalin berarti kode tidak utuh: jangan tandai sukses,
            // supaya commit terpasang tidak tercatat padahal isi masih campuran.
            if (!empty($failedCopies)) {
                $logs[] = "[ZIP ERROR] Sinkronisasi tidak utuh: " . count($failedCopies) . " file gagal ditulis.";
                return [
                    'success' => false,
                    'logs' => $logs,
                    'sha' => null,
                ];
            }

            return [
                'success' => true,
                'logs' => $logs,
                'sha' => $latestCommit['sha'] ?? null,
            ];
        } catch (\Throwable $e) {
            $logs[] = "[ZIP ERROR] " . $e->getMessage();
            return ['success' => false, 'logs' => $logs, 'sha' => null];
        }
    }
}
