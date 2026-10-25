<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UiRenderRegressionTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ];
    }

    public function test_core_pages_render_without_error(): void
    {
        $session = ['user' => $this->adminSession()];

        $paths = [
            '/dashboard/admin',
            '/dashboard/identitas-sekolah',
            '/dashboard/presensi/kelas',
            '/dashboard/kesiswaan/kedisiplinan',
            '/dashboard/persuratan/keluar',
        ];

        foreach ($paths as $path) {
            $res = $this->withSession($session)->get($path);
            $this->assertSame(200, $res->getStatusCode(), "Halaman {$path} gagal dirender.");
        }
    }

    public function test_rendered_html_has_balanced_structure(): void
    {
        $res = $this->withSession(['user' => $this->adminSession()])->get('/dashboard/admin');
        $this->assertSame(200, $res->getStatusCode());

        $html = $res->getContent();
        $clean = preg_replace('/<script\b.*?<\/script>/is', '', $html);
        $clean = preg_replace('/<style\b.*?<\/style>/is', '', $clean);

        foreach (['div', 'main', 'nav', 'header', 'aside', 'table', 'form'] as $tag) {
            $open = preg_match_all('/<' . $tag . '\b/i', $clean);
            $close = preg_match_all('/<\/' . $tag . '>/i', $clean);
            $this->assertSame($open, $close, "Tag <{$tag}> tidak seimbang ({$open} buka vs {$close} tutup).");
        }
    }

    public function test_design_tokens_loaded_before_app_stylesheet(): void
    {
        $res = $this->withSession(['user' => $this->adminSession()])->get('/dashboard/admin');
        $html = $res->getContent();

        $posTokens = strpos($html, 'css/tokens.css');
        $posComponents = strpos($html, 'css/components.css');
        $posSae = strpos($html, 'css/sae.css');

        $this->assertNotFalse($posTokens, 'tokens.css tidak dimuat.');
        $this->assertNotFalse($posComponents, 'components.css tidak dimuat.');
        $this->assertNotFalse($posSae, 'sae.css tidak dimuat.');

        // Urutan wajib: tokens -> components -> sae, agar override tema bekerja.
        $this->assertLessThan($posComponents, $posTokens, 'tokens.css harus dimuat sebelum components.css.');
        $this->assertLessThan($posSae, $posComponents, 'components.css harus dimuat sebelum sae.css.');
    }

    public function test_no_unresolved_css_tokens_in_stylesheets(): void
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

        $undefined = array_diff(array_keys($used), array_keys($defined));

        // Token tanpa definisi hanya boleh ada bila selalu disertai fallback.
        $withoutFallback = [];
        foreach ($undefined as $token) {
            $pattern = '/var\(' . preg_quote($token, '/') . '\s*\)/i';
            foreach (glob(public_path('css/*.css')) as $file) {
                if (preg_match($pattern, file_get_contents($file))) {
                    $withoutFallback[] = $token;
                    break;
                }
            }
        }

        $this->assertSame(
            [],
            $withoutFallback,
            'Token CSS dipakai tanpa definisi dan tanpa fallback: ' . implode(', ', $withoutFallback)
        );
    }
}
