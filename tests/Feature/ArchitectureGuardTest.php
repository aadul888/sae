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
            'docs/HAK_AKSES.md',
            'docs/MODULE_DEPENDENCIES.md',
            'docs/UPGRADE_SERVER_AAPANEL.md',
        ];

        foreach ($required as $relative) {
            $this->assertFileExists(
                base_path($relative),
                "Dokumentasi {$relative} hilang. Dokumentasi developer wajib ada di docs/ (ter-track git)."
            );
        }
    }

    /**
     * Mencegah penumpukan dokumen: setiap dokumen di docs/ wajib terdaftar
     * pada indeks docs/README.md, dan jumlahnya dijaga wajar.
     */
    public function test_docs_directory_stays_indexed_and_bounded(): void
    {
        $readme = file_get_contents(base_path('docs/README.md'));

        $files = glob(base_path('docs/*.md'));
        $unlisted = [];

        foreach ($files as $file) {
            $name = basename($file);
            if ($name === 'README.md') {
                continue;
            }
            // Setiap dokumen harus ditautkan dari indeks.
            if (!str_contains($readme, $name)) {
                $unlisted[] = $name;
            }
        }

        $this->assertSame(
            [],
            $unlisted,
            'Dokumen berikut belum ditautkan di docs/README.md: ' . implode(', ', $unlisted)
        );

        // Batas atas agar dokumentasi tidak kembali menumpuk tanpa kendali.
        $this->assertLessThanOrEqual(
            15,
            count($files),
            'Jumlah dokumen di docs/ melebihi 15. Gabungkan ke dokumen yang sudah ada '
                . 'atau arsipkan yang sudah selesai, alih-alih menambah file baru.'
        );
    }

    /**
     * Dokumen yang sudah selesai/usang harus berada di .agents/archive,
     * bukan bercampur dengan panduan aktif.
     */
    public function test_no_stale_documents_in_active_agent_root(): void
    {
        $stale = [
            'REFACTORING_PLAN.md',
            'FILES_OVER_1000_LINES.md',
            'PRD.md',
            'HAK_AKSES_DAN_FITUR.md',
            'SARAN-MENU-TENDIK-PER-BIDANG.md',
            'tupoksi_tas.md',
        ];

        foreach ($stale as $name) {
            $this->assertFileDoesNotExist(
                base_path('.agents/' . $name),
                "Dokumen selesai/usang '{$name}' seharusnya berada di .agents/archive/, bukan di root .agents/."
            );
        }
    }
}
