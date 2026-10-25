<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard anti-regresi konsistensi desain.
 *
 * Tujuan: mencegah nilai desain liar kembali masuk ke stylesheet maupun
 * inline style Blade. Semua ukuran font dan radius wajib memakai token
 * dari public/css/tokens.css.
 */
class DesignConsistencyTest extends TestCase
{
    /** Batas toleransi nilai mentah yang belum bisa dimigrasi (shorthand). */
    private const MAX_RAW_RADIUS_IN_VIEWS = 3;

    private function bladeFiles(): array
    {
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        foreach ($it as $f) {
            if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                $files[] = $f->getPathname();
            }
        }

        return $files;
    }

    private function appStylesheets(): array
    {
        $files = [];
        foreach (glob(public_path('css/*.css')) as $file) {
            // tokens.css & components.css adalah sumber token.
            if (in_array(basename($file), ['tokens.css', 'components.css'], true)) {
                continue;
            }
            $files[] = $file;
        }

        return $files;
    }

    public function test_stylesheets_use_tokens_for_font_size(): void
    {
        $offenders = [];

        foreach ($this->appStylesheets() as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/font-size:\s*[0-9.]+rem/i', $content, $m)) {
                $offenders[basename($file)] = count($m[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Stylesheet masih memakai font-size mentah (gunakan var(--text-*)): ' . json_encode($offenders)
        );
    }

    public function test_stylesheets_use_tokens_for_border_radius(): void
    {
        $offenders = [];

        foreach ($this->appStylesheets() as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/border-radius:\s*[0-9]+px/i', $content, $m)) {
                $offenders[basename($file)] = count($m[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Stylesheet masih memakai border-radius mentah (gunakan var(--radius-*)): ' . json_encode($offenders)
        );
    }

    public function test_views_use_tokens_for_font_size(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/font-size:\s*[0-9.]+rem/i', $content, $m)) {
                $offenders[str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file)] = count($m[0]);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'View masih memakai font-size mentah (gunakan var(--text-*)): ' . json_encode($offenders)
        );
    }

    public function test_views_use_tokens_for_border_radius(): void
    {
        $total = 0;

        foreach ($this->bladeFiles() as $file) {
            $content = file_get_contents($file);
            if (preg_match_all('/border-radius:\s*[0-9]+px/i', $content, $m)) {
                $total += count($m[0]);
            }
        }

        $this->assertLessThanOrEqual(
            self::MAX_RAW_RADIUS_IN_VIEWS,
            $total,
            "Jumlah border-radius mentah di view melebihi batas ({$total}). Gunakan var(--radius-*)."
        );
    }

    public function test_no_malformed_css_var_syntax_anywhere(): void
    {
        $files = array_merge($this->bladeFiles(), $this->appStylesheets());

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Pola rusak akibat transformasi token:
            //   var(--x)(...)            -> kurung ganda
            //   var(--x), 0.875rem       -> fallback menempel di luar var()
            // Pola SAH seperti rgba(var(--rgb), 0.15) TIDAK boleh dianggap galat,
            // sehingga yang diperiksa hanya bentuk hasil transformasi di atas.
            $this->assertDoesNotMatchRegularExpression(
                '/var\(--[a-z0-9-]+\)\s*\(/i',
                $content,
                'Sintaks CSS rusak (var() diikuti kurung) pada ' . basename($file)
            );

            $this->assertDoesNotMatchRegularExpression(
                '/var\(--[a-z0-9-]+\)\s*,\s*[0-9.]+(?:rem|px)\b/i',
                $content,
                'Sintaks CSS rusak (fallback menempel tanpa var()) pada ' . basename($file)
            );
        }
    }

    public function test_all_used_tokens_are_defined(): void
    {
        $defined = [];
        $used = [];

        foreach (glob(public_path('css/*.css')) as $file) {
            $content = file_get_contents($file);

            if (preg_match_all('/^\s*(--[a-z0-9-]+)\s*:/mi', $content, $m)) {
                foreach ($m[1] as $token) {
                    $defined[$token] = true;
                }
            }
            if (preg_match_all('/var\((--[a-z0-9-]+)/i', $content, $m)) {
                foreach ($m[1] as $token) {
                    $used[$token] = true;
                }
            }
        }

        // Token boleh tidak terdefinisi HANYA jika selalu memakai fallback.
        $mustBeDefined = [
            '--radius-xs', '--radius-sm', '--radius-md', '--radius-lg',
            '--radius-xl', '--radius-2xl', '--radius-pill',
            '--text-2xs', '--text-xs', '--text-sm', '--text-base',
            '--text-md', '--text-lg', '--text-xl', '--text-2xl', '--text-3xl',
        ];

        $missing = array_values(array_filter($mustBeDefined, fn ($t) => !isset($defined[$t])));

        $this->assertSame([], $missing, 'Token wajib belum didefinisikan: ' . implode(', ', $missing));
    }

    public function test_tokens_stylesheet_is_loaded_first(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/dashboard.blade.php'));

        $posTokens = strpos($layout, 'css/tokens.css');
        $posComponents = strpos($layout, 'css/components.css');
        $posSae = strpos($layout, 'css/sae.css');

        $this->assertNotFalse($posTokens, 'tokens.css tidak dimuat pada layout dashboard.');
        $this->assertNotFalse($posComponents, 'components.css tidak dimuat pada layout dashboard.');
        $this->assertNotFalse($posSae, 'sae.css tidak dimuat pada layout dashboard.');

        $this->assertLessThan($posComponents, $posTokens);
        $this->assertLessThan($posSae, $posComponents);
    }

    /**
     * Warna aksen wajib memakai token tema agar mode gelap/terang konsisten.
     *
     * Sebelumnya warna seperti #10b981 (success) dan #ef4444 (danger) ditulis
     * hardcoded pada inline style, sehingga tidak menyesuaikan saat pengguna
     * beralih ke mode terang.
     */
    public function test_brand_colors_in_views_use_tokens(): void
    {
        // Warna yang punya padanan token resmi di sae.css.
        $mustBeTokenized = [
            '#3b82f6' => '--primary',
            '#2563eb' => '--primary-hover',
            '#10b981' => '--success',
            '#059669' => '--success',
            '#ef4444' => '--danger',
            '#dc2626' => '--danger',
            '#f59e0b' => '--warning',
            '#d97706' => '--warning',
            '#8b5cf6' => '--purple',
            '#06b6d4' => '--accent',
            '#0284c7' => '--accent',
        ];

        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $content = file_get_contents($file);

            // Periksa hanya isi atribut style="..." (bukan skrip atau teks biasa).
            if (!preg_match_all('/style="([^"]*)"/i', $content, $m)) {
                continue;
            }

            foreach ($m[1] as $styleAttr) {
                // Abaikan deklarasi yang sudah memakai var() (fallback sah).
                if (stripos($styleAttr, 'var(--') !== false) {
                    continue;
                }

                foreach (array_keys($mustBeTokenized) as $hex) {
                    if (preg_match('/' . preg_quote($hex, '/') . '\b/i', $styleAttr)) {
                        $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file);
                        $offenders[$rel] = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertSame(
            [],
            array_keys($offenders),
            'Warna aksen ditulis hardcoded di inline style; gunakan var(--token, fallback): '
                . implode(', ', array_slice(array_keys($offenders), 0, 10))
        );
    }
}
