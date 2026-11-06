<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Berita;
use App\Models\Pengumuman;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BeritaDanPengumumanTest extends TestCase
{
    use DatabaseTransactions;

    public function test_halaman_utama_menampilkan_berita_utama_dan_berita_lainnya(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Warta &amp; Kabar Sekolah', false);
        $response->assertSee('Berita &amp; Informasi Terkini', false);
    }

    public function test_halaman_kumpulan_berita_publik_bisa_diakses(): void
    {
        $response = $this->get('/berita');
        $response->assertStatus(200);
        $response->assertSee('Warta &amp;', false);
        $response->assertSee('Berita Terkini');
        $response->assertSee('Semua Kategori');
    }

    public function test_halaman_detail_berita_publik_bisa_dibaca_dan_views_bertambah(): void
    {
        $berita = Berita::active()->first();
        if (!$berita) {
            $berita = Berita::create([
                'judul'        => 'Uji Coba Berita Test',
                'slug'         => 'uji-coba-berita-test',
                'kategori'     => 'Akademik',
                'isi'          => 'Konten uji coba berita sekolah.',
                'penulis_nama' => 'Redaksi Test',
                'is_active'    => true,
                'views'        => 5,
            ]);
        }

        $viewsAwal = $berita->views;
        $response = $this->get('/berita/' . $berita->slug);
        $response->assertStatus(200);
        $response->assertSee($berita->judul);

        $berita->refresh();
        $this->assertEquals($viewsAwal + 1, $berita->views);
    }

    public function test_dashboard_pengumuman_memuat_tab_pengumuman_broadcast_dan_berita(): void
    {
        $adminUser = [
            'id'       => 1,
            'username' => 'admin',
            'role'     => 'admin',
            'nama'     => 'Administrator Test',
        ];

        // Tab pengumuman
        $resPengumuman = $this->withSession(['user' => $adminUser])->get('/dashboard/pengumuman?tab=pengumuman');
        $resPengumuman->assertStatus(200);
        $resPengumuman->assertSee('Ditujukan Kepada');

        // Tab broadcast
        $resBroadcast = $this->withSession(['user' => $adminUser])->get('/dashboard/pengumuman?tab=broadcast');
        $resBroadcast->assertStatus(200);
        $resBroadcast->assertSee('Teks Berjalan Running Text');

        // Tab berita
        $resBerita = $this->withSession(['user' => $adminUser])->get('/dashboard/pengumuman?tab=berita');
        $resBerita->assertStatus(200);
        $resBerita->assertSee('Judul &amp; Ringkasan Berita', false);
    }
}
