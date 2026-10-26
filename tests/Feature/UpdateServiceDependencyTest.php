<?php

namespace Tests\Feature;

use App\Services\UpdateService;
use Tests\TestCase;

/**
 * Guard untuk proses update sistem lewat panel admin.
 *
 * Regresi yang dicegah: proses update sebelumnya hanya melakukan git pull lalu
 * langsung `migrate`. Ketika kode naik ke Laravel 13 tetapi folder vendor/
 * masih Laravel 12, migrasi gagal dengan:
 *
 *   Method Illuminate\Database\MySqlConnection::hasDirectConnection does not exist.
 */
class UpdateServiceDependencyTest extends TestCase
{
    private function service(): UpdateService
    {
        return app(UpdateService::class);
    }

    private function callSyncDeps(): array
    {
        $logs = [];
        $method = new \ReflectionMethod(UpdateService::class, 'syncComposerDependencies');
        $method->setAccessible(true);
        $ok = $method->invokeArgs($this->service(), [&$logs]);

        return ['ok' => $ok, 'logs' => $logs];
    }

    public function test_sync_dependencies_method_exists(): void
    {
        $this->assertTrue(
            method_exists(UpdateService::class, 'syncComposerDependencies'),
            'UpdateService harus punya syncComposerDependencies() agar vendor ikut diperbarui.'
        );
    }

    public function test_run_update_calls_composer_before_migrate(): void
    {
        $src = file_get_contents(app_path('Services/UpdateService.php'));

        $posDeps = strpos($src, 'syncComposerDependencies');
        $this->assertNotFalse($posDeps, 'Pemanggilan syncComposerDependencies tidak ditemukan.');

        // Posisi pemanggilan harus SEBELUM blok migrasi di dalam runUpdate.
        $posMigrate = strpos($src, "Artisan::call('migrate'");
        $this->assertNotFalse($posMigrate, 'Pemanggilan migrate tidak ditemukan.');

        $this->assertLessThan(
            $posMigrate,
            $posDeps,
            'Sinkronisasi dependensi harus dijalankan SEBELUM migrasi.'
        );
    }

    public function test_migrate_is_skipped_when_dependencies_fail(): void
    {
        $src = file_get_contents(app_path('Services/UpdateService.php'));

        $this->assertStringContainsString(
            'if ($depsOk)',
            $src,
            'Migrasi harus dijaga kondisi $depsOk agar tidak berjalan dengan vendor tidak sesuai.'
        );
    }

    public function test_dependency_check_reports_current_state(): void
    {
        $result = $this->callSyncDeps();

        $this->assertTrue($result['ok'], 'Dependensi lokal seharusnya sudah sesuai composer.lock.');
        $this->assertNotEmpty($result['logs'], 'Sinkronisasi dependensi harus mencatat log.');

        $joined = implode(' | ', $result['logs']);
        $this->assertStringContainsString(
            '[DEPS]',
            $joined,
            'Log harus memakai prefiks [DEPS] agar mudah ditelusuri di panel update.'
        );
    }

    public function test_framework_version_matches_lock(): void
    {
        $lock = json_decode(file_get_contents(base_path('composer.lock')), true);
        $installed = json_decode(file_get_contents(base_path('vendor/composer/installed.json')), true);

        $lockedFramework = null;
        foreach (($lock['packages'] ?? []) as $pkg) {
            if (($pkg['name'] ?? '') === 'laravel/framework') {
                $lockedFramework = $pkg['version'] ?? null;
                break;
            }
        }

        $installedFramework = null;
        foreach (($installed['packages'] ?? []) as $pkg) {
            if (($pkg['name'] ?? '') === 'laravel/framework') {
                $installedFramework = $pkg['version'] ?? null;
                break;
            }
        }

        $this->assertNotNull($lockedFramework, 'laravel/framework tidak ada di composer.lock.');
        $this->assertSame(
            $lockedFramework,
            $installedFramework,
            'Versi framework terpasang berbeda dengan composer.lock — jalankan composer install.'
        );
    }

    public function test_required_laravel_method_exists(): void
    {
        // Metode yang memicu error di server harus ada pada vendor saat ini.
        $this->assertTrue(
            method_exists(\Illuminate\Database\Connection::class, 'hasDirectConnection'),
            'Connection::hasDirectConnection() tidak ada — vendor Laravel terlalu lama untuk framework ini.'
        );
    }
}
