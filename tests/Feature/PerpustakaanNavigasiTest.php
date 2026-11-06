<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menjaga struktur menu & tab pengelolaan perpustakaan tetap mengikuti
 * alur kerja pustakawan: Katalog -> Anggota -> Sirkulasi -> Kunjungan -> Laporan.
 */
class PerpustakaanNavigasiTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ]];
    }

    private function halaman(string $path): string
    {
        $res = $this->withSession($this->adminSession())->get($path);
        $this->assertSame(200, $res->getStatusCode(), "Halaman {$path} gagal dimuat.");

        return $res->getContent();
    }

    public function test_urutan_tab_utama_mengikuti_alur_kerja_pustakawan(): void
    {
        $html = $this->halaman('/dashboard/perpustakaan/koleksi');

        // Batasi pengecekan pada kontainer tab navigasi perpustakaan agar tidak tertukar teks sidebar
        if (preg_match('/<div class="periode-nav-desktop"[^>]*>(.*?)<\/div>/s', $html, $matches)) {
            $tabHtml = $matches[1];
        } else {
            $tabHtml = $html;
        }

        $label = [
            'Katalog Buku',
            'Tarik E-Book',
            'Import &amp; Scan',
            'Anggota &amp; Kartu',
            'Sirkulasi &amp; Denda',
            'Buku Kunjungan',
            'Laporan &amp; Rekap',
        ];

        $posisi = array_map(function (string $teks) use ($tabHtml) {
            $pos = strpos($tabHtml, $teks);
            $this->assertNotFalse($pos, "Tab \"{$teks}\" tidak ditemukan di navigasi tab.");

            return $pos;
        }, $label);

        $urut = $posisi;
        sort($urut);
        $this->assertSame($urut, $posisi, 'Urutan tab utama perpustakaan tidak sesuai alur kerja.');
    }

    public function test_setiap_tab_utama_menandai_dirinya_sendiri_sebagai_aktif(): void
    {
        $peta = [
            '/dashboard/perpustakaan/koleksi'   => 'Katalog Buku',
            '/dashboard/perpustakaan/ebook'     => 'Tarik E-Book',
            '/dashboard/perpustakaan/import'    => 'Import &amp; Scan',
            '/dashboard/perpustakaan/anggota'   => 'Anggota &amp; Kartu',
            '/dashboard/perpustakaan/sirkulasi' => 'Sirkulasi &amp; Denda',
            '/dashboard/perpustakaan/kunjungan' => 'Buku Kunjungan',
            '/dashboard/perpustakaan/laporan'   => 'Laporan &amp; Rekap',
        ];

        foreach ($peta as $path => $teks) {
            $html = $this->halaman($path);

            $this->assertMatchesRegularExpression(
                '/class="periode-nav-tab active">\s*<i[^>]*><\/i>\s*'.preg_quote($teks, '/').'/',
                $html,
                "Tab \"{$teks}\" tidak ditandai aktif pada {$path}."
            );
        }
    }

    public function test_navigasi_utama_satu_baris_ringkas_tanpa_subtab_ganda(): void
    {
        foreach (['/dashboard/perpustakaan/koleksi', '/dashboard/perpustakaan/ebook', '/dashboard/perpustakaan/import'] as $path) {
            $html = $this->halaman($path);

            // Sub-tab lama yang bertumpuk & redundan sudah tidak muncul
            $this->assertStringNotContainsString('Daftar Katalog', $html);
            $this->assertStringNotContainsString('Input Scan Barcode', $html);

            // Tab 1 baris utama ada di semua halaman
            $this->assertStringContainsString('Katalog Buku', $html);
            $this->assertStringContainsString('Tarik E-Book', $html);
            $this->assertStringContainsString('Import &amp; Scan', $html);
        }
    }

    public function test_sub_tab_laporan_tersedia_dan_menandai_tahun_ajaran(): void
    {
        foreach (['/dashboard/perpustakaan/laporan', '/dashboard/perpustakaan/laporan/tahun-ajaran'] as $path) {
            $html = $this->halaman($path);

            foreach (['Rekap Periode', 'Per Tahun Ajaran'] as $sub) {
                $this->assertStringContainsString($sub, $html, "Sub-tab \"{$sub}\" hilang di {$path}.");
            }
        }

        $tahunan = $this->halaman('/dashboard/perpustakaan/laporan/tahun-ajaran');
        $this->assertMatchesRegularExpression(
            '/class="periode-nav-tab active">\s*<i[^>]*><\/i>\s*Per Tahun Ajaran/',
            $tahunan,
            'Sub-tab Per Tahun Ajaran harus aktif di halaman laporan tahun ajaran.'
        );
    }

    public function test_sidebar_perpustakaan_memuat_tautan_laporan_tahun_ajaran(): void
    {
        $html = $this->halaman('/dashboard/perpustakaan/laporan');

        $this->assertStringContainsString('Import &amp; Scan Barcode', $html);
        $this->assertStringContainsString('Laporan Tahun Ajaran', $html);
        $this->assertStringContainsString('Halaman Publik Perpus', $html);
    }
}
