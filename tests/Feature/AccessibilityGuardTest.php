<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard aksesibilitas dasar.
 *
 * Memastikan elemen interaktif memiliki label yang terbaca pembaca layar,
 * dan gambar memiliki teks alternatif.
 */
class AccessibilityGuardTest extends TestCase
{
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

    public function test_all_images_have_alt_text(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $src = file_get_contents($file);

            foreach ($this->findTags($src, 'img') as $item) {
                if (!preg_match('/\balt\s*=/i', $item['tag'])) {
                    $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file);
                    $offenders[] = $rel . ' :: ' . preg_replace('/\s+/', ' ', substr($item['tag'], 0, 80));
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Elemen <img> tanpa atribut alt: ' . PHP_EOL . implode(PHP_EOL, array_slice($offenders, 0, 10))
        );
    }

    public function test_icon_only_buttons_have_accessible_label(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $src = file_get_contents($file);

            foreach ($this->findTags($src, 'button') as $item) {
                $tag = $item['tag'];
                $body = $item['body'];

                $hasLabel = preg_match('/\baria-label\s*=/i', $tag)
                    || preg_match('/\btitle\s*=/i', $tag)
                    // Teks statis di dalam tombol
                    || trim(strip_tags(preg_replace('/\{\{.*?\}\}/s', 'x', $body))) !== ''
                    // Teks dinamis dari Blade
                    || preg_match('/\{\{.*?\}\}/s', $body);

                if (!$hasLabel) {
                    $rel = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $file);
                    $offenders[] = $rel . ' :: ' . preg_replace('/\s+/', ' ', substr($tag, 0, 80));
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Tombol ikon tanpa aria-label/title: ' . PHP_EOL . implode(PHP_EOL, array_slice($offenders, 0, 10))
        );
    }

    public function test_skip_link_exists_for_keyboard_users(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        // Tautan lewati navigasi membantu pengguna keyboard.
        $this->assertMatchesRegularExpression(
            '/#main|lewati|skip/i',
            $layout,
            'Layout publik sebaiknya menyediakan tautan "lewati ke konten utama".'
        );
    }
}
