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

    /**
     * Telusuri tag HTML dengan menghormati ekspresi Blade {{ ... }} yang
     * dapat memuat karakter '>' (mis. {{ $row->foto_url }}).
     *
     * @return array<int, array{tag: string, body: string}>
     */
    private function findTags(string $src, string $tagName): array
    {
        $result = [];
        $len = strlen($src);
        $pos = 0;
        $needle = '<' . $tagName;
        $closing = '</' . $tagName . '>';

        while (($start = stripos($src, $needle, $pos)) !== false) {
            $charAfter = $start + strlen($needle) < $len ? $src[$start + strlen($needle)] : '';
            if ($charAfter !== '' && !ctype_space($charAfter) && $charAfter !== '/' && $charAfter !== '>') {
                $pos = $start + strlen($needle);
                continue;
            }

            $i = $start + strlen($needle);
            $inBlade = 0;

            while ($i < $len) {
                if ($i + 1 < $len && substr($src, $i, 2) === '{{') {
                    $inBlade++;
                    $i += 2;
                    continue;
                }
                if ($i + 1 < $len && substr($src, $i, 2) === '}}' && $inBlade > 0) {
                    $inBlade--;
                    $i += 2;
                    continue;
                }
                if ($inBlade === 0 && $src[$i] === '>') {
                    break;
                }
                $i++;
            }

            $openTag = substr($src, $start, $i - $start + 1);
            $bodyStart = $i + 1;
            if ($bodyStart >= $len) {
                break;
            }

            $close = stripos($src, $closing, $bodyStart);
            $body = $close === false ? '' : substr($src, $bodyStart, $close - $bodyStart);
            $pos = $close === false ? $bodyStart + 1 : $close + strlen($closing);

            $result[] = ['tag' => $openTag, 'body' => $body];
        }

        return $result;
    }

    /**
     * Seluruh tombol aksi pada Header Banner (.dash-banner-actions) wajib icon-only
     * (38x38px) dan dilarang menyisipkan teks label di dalam tag tombol/link.
     */
    public function test_dash_banner_actions_buttons_are_icon_only(): void
    {
        $viewPath = resource_path('views');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewPath));
        $offenders = [];

        foreach ($it as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (!str_contains($content, 'dash-banner-actions')) {
                continue;
            }

            // Ekstrak blok .dash-banner-actions
            if (preg_match_all('/<div\s+class=["\'][^"\']*dash-banner-actions[^"\']*["\'][^>]*>(.*?)<\/div>/is', $content, $matches)) {
                foreach ($matches[1] as $block) {
                    $buttons = array_merge(
                        $this->findTags($block, 'button'),
                        $this->findTags($block, 'a')
                    );

                    foreach ($buttons as $btn) {
                        $inner = $btn['body'];

                        // Hapus komentar blade/html, directive blade, tag ikon/svg, dan badge
                        $cleanInner = preg_replace('/\{\{--.*?--\}\}/s', '', $inner);
                        $cleanInner = preg_replace('/<!--.*?-->/s', '', $cleanInner);
                        $cleanInner = preg_replace('/<i\b[^>]*>.*?<\/i>/is', '', $cleanInner);
                        $cleanInner = preg_replace('/<svg\b[^>]*>.*?<\/svg>/is', '', $cleanInner);
                        $cleanInner = preg_replace('/<span\s+class=["\'][^"\']*badge[^"\']*["\'][^>]*>.*?<\/span>/is', '', $cleanInner);
                        $cleanInner = preg_replace('/@[a-zA-Z]+(?:\([^)]*\))?/', '', $cleanInner);

                        $textOnly = trim(strip_tags($cleanInner));
                        if ($textOnly !== '') {
                            $rel = str_replace($viewPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                            $offenders[] = "{$rel}: tombol memuat teks '{$textOnly}'";
                        }
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Tombol pada header banner (.dash-banner-actions) wajib icon-only tanpa teks label: ' . PHP_EOL . implode(PHP_EOL, $offenders)
        );
    }
}
