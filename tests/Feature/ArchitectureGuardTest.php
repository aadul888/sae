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

    /**
     * Kebijakan dokumentasi: HANYA README.md yang boleh berada di repo.
     *
     * Seluruh dokumentasi pengembangan lain disimpan di `.agents/` (di-ignore git)
     * agar repo tetap bersih dan tidak dipenuhi berkas markdown.
     */
    public function test_only_root_readme_is_tracked_markdown(): void
    {
        $tracked = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));

            // Abaikan folder yang memang tidak masuk repo.
            if (preg_match('#^(vendor|node_modules|\.agents|\.agent|scratch|storage)/#', $rel)) {
                continue;
            }

            $tracked[] = $rel;
        }

        $this->assertSame(
            ['README.md'],
            $tracked,
            'Hanya README.md yang boleh berada di repo. Berkas berikut harus dipindah ke '
                . ".agents/ (di-ignore git) atau dihapus: " . implode(', ', $tracked)
        );
    }

    /**
     * Folder docs/ di root proyek tidak boleh ada — dokumentasi pengembangan
     * disimpan di .agents/docs/ agar tidak ikut ter-commit.
     */
    public function test_docs_folder_is_not_in_repo_root(): void
    {
        $this->assertDirectoryDoesNotExist(
            base_path('docs'),
            'Folder docs/ tidak boleh ada di root proyek. Simpan dokumentasi di .agents/docs/.'
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
