<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class UpdateService
{
    const CURRENT_VERSION = '1.0.1';
    const GITHUB_REPO = 'aadul888/sae';

    /**
     * Cek status update sistem (git remote / GitHub API, atau pending migrasi)
     */
    public function checkUpdate(): array
    {
        $setting = Schema::hasTable('settings') ? DB::table('settings')->where('id', 1)->first() : null;
        $currentVersion = $setting->app_version ?? self::CURRENT_VERSION;
        $lastUpdateAt = $setting->last_update_at ?? null;

        $hasGit = is_dir(base_path('.git'));
        $gitBranch = 'main';
        $localCommit = $this->getLocalCommit($setting);
        $gitCommit = $localCommit;
        $remoteCommit = '';
        $behindCount = 0;
        $updatesAvailable = false;
        $remoteVersion = $currentVersion;
        $changes = [];

        // 1. Cek dari Git CLI jika repositori lokal adalah git
        if ($hasGit) {
            try {
                $gitBranch = trim(@shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?: 'main');
                @shell_exec('git fetch origin ' . escapeshellarg($gitBranch) . ' 2>&1');
                
                $behindCount = (int) trim(@shell_exec('git rev-list --count HEAD..origin/' . escapeshellarg($gitBranch) . ' 2>&1') ?: '0');
                if ($behindCount > 0) {
                    $updatesAvailable = true;
                    $changesRaw = @shell_exec('git log HEAD..origin/' . escapeshellarg($gitBranch) . ' --oneline -n 10 2>&1');
                    if ($changesRaw) {
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
                if (!empty($remoteData['message'])) {
                    $firstLine = explode("\n", trim($remoteData['message']))[0];
                    $changes[] = $remoteCommit . ' ' . $firstLine;
                }
                
                if (empty($localCommit) || strpos(strtolower($remoteData['sha']), strtolower($localCommit)) !== 0) {
                    $updatesAvailable = true;
                    $behindCount = 1;
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
        if (is_dir(base_path('.git'))) {
            $cliHash = trim(@shell_exec('git rev-parse HEAD 2>&1') ?: '');
            if (preg_match('/^[a-f0-9]{40}$/i', $cliHash)) {
                return $cliHash;
            }

            // Fallback baca file refs
            $headPath = base_path('.git/HEAD');
            if (is_file($headPath)) {
                $headContent = trim((string) @file_get_contents($headPath));
                if (preg_match('/^[a-f0-9]{40}$/i', $headContent)) {
                    return $headContent;
                }
                if (str_starts_with($headContent, 'ref: ')) {
                    $ref = trim(substr($headContent, 5));
                    $refFile = base_path('.git/' . $ref);
                    if (is_file($refFile)) {
                        return trim((string) @file_get_contents($refFile));
                    }
                }
            }
        }

        return $setting->last_commit_hash ?? '';
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
            if (!Schema::hasTable('migrations')) {
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
        $logs = [];
        $success = true;
        $newCommit = null;

        // 1. Sync file kode
        if (is_dir(base_path('.git'))) {
            $branch = trim(@shell_exec('git rev-parse --abbrev-ref HEAD') ?: 'main');
            $pullOutput = @shell_exec('git pull origin ' . escapeshellarg($branch) . ' 2>&1');
            $logs[] = "[GIT PULL] " . trim($pullOutput ?: 'No output');
            $newCommit = trim(@shell_exec('git rev-parse HEAD 2>&1') ?: '');
        } else {
            // Standalone / Non-Git mode: Unduh ZIP dari GitHub & timpa file
            $zipResult = $this->deployFromGitHubZip('main');
            foreach ($zipResult['logs'] as $zl) {
                $logs[] = $zl;
            }
            if (!$zipResult['success']) {
                $success = false;
            }
            $newCommit = $zipResult['sha'] ?? null;
        }

        // 2. Jalankan Migrasi Database
        try {
            Artisan::call('migrate', ['--force' => true]);
            $logs[] = "[MIGRATE] " . trim(Artisan::output());
        } catch (\Throwable $e) {
            $success = false;
            $logs[] = "[MIGRATE ERROR] " . $e->getMessage();
        }

        // 3. Catat Versi & Waktu Update ke Database
        try {
            if (Schema::hasTable('settings')) {
                $commitShort = $newCommit ? substr($newCommit, 0, 7) : null;
                DB::table('settings')->where('id', 1)->update([
                    'app_version' => self::CURRENT_VERSION,
                    'last_update_at' => now(),
                    'last_commit_hash' => $newCommit ?: null,
                    'updated_at' => now(),
                ]);
                $logs[] = "[VERSION] Versi " . self::CURRENT_VERSION . ($commitShort ? " ($commitShort)" : "") . " tersimpan.";
            }
        } catch (\Throwable $e) {
            $logs[] = "[VERSION ERROR] " . $e->getMessage();
        }

        // 4. Clear Caches & Optimize
        try {
            Artisan::call('optimize:clear');
            $logs[] = "[OPTIMIZE] " . trim(Artisan::output());
        } catch (\Throwable $e) {
            $logs[] = "[OPTIMIZE NOTICE] " . $e->getMessage();
        }

        return [
            'success' => $success,
            'logs' => $logs,
            'timestamp' => now()->toDateTimeString(),
        ];
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
                    File::makeDirectory($destDir, 0755, true, true);
                }
                File::copy($file->getPathname(), $dest);
                $copied++;
            }

            File::deleteDirectory($tmpExtract);
            $logs[] = "[ZIP SYNC] Berhasil menyalin $copied file pembaruan sistem.";

            $latestCommit = $this->fetchGitHubLatestCommit($branch);
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
