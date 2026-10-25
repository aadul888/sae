<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard arsitektur: mencegah duplikasi kode yang sudah diekstrak.
 *
 * `checkAuth()` sebelumnya disalin identik ke 20 controller. Kini hanya
 * boleh ada satu implementasi, di trait ChecksAuthenticatedSession.
 */
class ArchitectureGuardTest extends TestCase
{
    private function controllerFiles(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path('Http/Controllers')));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }

    public function test_check_auth_is_defined_only_in_trait(): void
    {
        $offenders = [];

        foreach ($this->controllerFiles() as $file) {
            $base = basename($file);

            // Trait adalah sumber implementasi; DashboardController punya
            // varian berbeda (menerima $allowedRole) yang sah.
            if (in_array($base, ['ChecksAuthenticatedSession.php', 'DashboardController.php'], true)) {
                continue;
            }

            $src = file_get_contents($file);
            if (preg_match('/function\s+checkAuth\s*\(/', $src)) {
                $offenders[] = $base;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'checkAuth() didefinisikan ulang di controller. Gunakan trait ChecksAuthenticatedSession: '
                . implode(', ', $offenders)
        );
    }

    public function test_controllers_using_trait_also_import_it(): void
    {
        $offenders = [];
        $import = 'use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;';

        foreach ($this->controllerFiles() as $file) {
            $src = file_get_contents($file);

            $usesTrait = (bool) preg_match('/^\s+use\s+ChecksAuthenticatedSession;/m', $src);
            if (!$usesTrait) {
                continue;
            }

            if (!str_contains($src, $import)) {
                $offenders[] = basename($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Controller memakai trait tanpa meng-import-nya: ' . implode(', ', $offenders)
        );
    }

    public function test_no_hardcoded_secrets_in_source(): void
    {
        $leaked = [
            'SAE_20260422090959_d6be672e5e92c802adeca5d8a4eccf0c',
            'sae_secret_live_key_2026',
        ];

        $dirs = [app_path(), config_path(), database_path(), base_path('routes')];

        foreach ($dirs as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
            foreach ($it as $f) {
                if (!$f->isFile() || !str_ends_with($f->getFilename(), '.php')) {
                    continue;
                }

                $src = file_get_contents($f->getPathname());
                foreach ($leaked as $secret) {
                    $this->assertStringNotContainsString(
                        $secret,
                        $src,
                        'Kunci rahasia ditemukan pada ' . $f->getFilename()
                    );
                }
            }
        }
    }

    public function test_env_example_documents_required_keys(): void
    {
        $env = file_get_contents(base_path('.env.example'));

        foreach (['SAE_CORE_API_KEY', 'SAE_CORE_URL', 'FORCE_HTTPS'] as $key) {
            $this->assertStringContainsString($key, $env, "{$key} belum didokumentasikan di .env.example.");
        }
    }

    public function test_developer_docs_are_tracked_in_repo(): void
    {
        // Dokumentasi developer tidak boleh hanya berada di .agents/ yang di-ignore.
        $required = [
            'docs/README.md',
            'docs/UI_DESIGN_STANDARDS.md',
            'docs/PROJECT_STRUCTURE.md',
            'docs/PANDUAN_PEMBUATAN_MODUL_DAN_FITUR.md',
            'docs/code-style-guide.md',
        ];

        foreach ($required as $relative) {
            $this->assertFileExists(
                base_path($relative),
                "Dokumentasi {$relative} hilang. Dokumentasi developer wajib ada di docs/ (ter-track git)."
            );
        }
    }
}
