<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class UpdateService
{
    const CURRENT_VERSION = '1.0.1';

    /**
     * Cek status update sistem (git, remote repository, atau update pack)
     */
    public function checkUpdate(): array
    {
        $setting = Schema::hasTable('settings') ? DB::table('settings')->where('id', 1)->first() : null;
        $currentVersion = $setting->app_version ?? self::CURRENT_VERSION;
        $lastUpdateAt = $setting->last_update_at ?? null;

        $hasGit = is_dir(base_path('.git'));
        $gitBranch = 'main';
        $gitCommit = $setting->last_commit_hash ?? null;
        $updatesAvailable = false;
        $remoteVersion = $currentVersion;
        $changes = [];

        // 1. Cek dari Git jika repositori lokal adalah git
        if ($hasGit) {
            try {
                $gitBranch = trim(@shell_exec('git rev-parse --abbrev-ref HEAD 2>&1') ?: 'main');
                $localCommit = trim(@shell_exec('git rev-parse --short HEAD 2>&1') ?: '');
                $gitCommit = $localCommit ?: $gitCommit;
                
                // Fetch remote changes
                @shell_exec('git fetch origin 2>&1');
                
                // Cek commit yang tertinggal dari remote branch
                $behindCount = (int) trim(@shell_exec('git rev-list --count HEAD..origin/' . escapeshellarg($gitBranch) . ' 2>&1') ?: '0');
                if ($behindCount > 0) {
                    $updatesAvailable = true;
                    $changesRaw = @shell_exec('git log HEAD..origin/' . escapeshellarg($gitBranch) . ' --oneline -n 10 2>&1');
                    if ($changesRaw) {
                        $changes = array_filter(explode("\n", trim($changesRaw)));
                    }
                }
            } catch (\Throwable $e) {
                // Ignore git execution error
            }
        }

        // 2. Cek database migrations yang pending
        $pendingMigrations = $this->getPendingMigrations();

        return [
            'current_version' => $currentVersion,
            'remote_version' => $remoteVersion,
            'last_update_at' => $lastUpdateAt,
            'has_git' => $hasGit,
            'git_branch' => $gitBranch,
            'git_commit' => $gitCommit,
            'behind_count' => $behindCount ?? 0,
            'updates_available' => $updatesAvailable || !empty($pendingMigrations),
            'has_git_updates' => $updatesAvailable,
            'pending_migrations' => $pendingMigrations,
            'changes' => $changes,
        ];
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
     * 1. Pull Git (jika ada)
     * 2. Migrate Database
     * 3. Clear & optimize Cache
     */
    public function runUpdate(): array
    {
        $logs = [];
        $success = true;

        // 1. Pull Git repository jika ada
        if (is_dir(base_path('.git'))) {
            $branch = trim(@shell_exec('git rev-parse --abbrev-ref HEAD') ?: 'main');
            $pullOutput = @shell_exec('git pull origin ' . escapeshellarg($branch) . ' 2>&1');
            $logs[] = "[GIT PULL] " . trim($pullOutput ?: 'No output');
        } else {
            $logs[] = "[GIT] Non-git environment (melewati git pull).";
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
                $commit = is_dir(base_path('.git')) ? trim(@shell_exec('git rev-parse --short HEAD') ?: '') : null;
                DB::table('settings')->where('id', 1)->update([
                    'app_version' => self::CURRENT_VERSION,
                    'last_update_at' => now(),
                    'last_commit_hash' => $commit,
                    'updated_at' => now(),
                ]);
                $logs[] = "[VERSION] Versi " . self::CURRENT_VERSION . " tersimpan ke database settings.";
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
}
