<?php

namespace Tests\Feature;

use App\Models\KedisiplinanPelanggaran;
use App\Models\KedisiplinanTataTertib;
use App\Models\PesertaDidik;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KedisiplinanSanggahAndKeamananTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_sanggah_pelanggaran_flow(): void
    {
        $tatib = KedisiplinanTataTertib::first() ?? KedisiplinanTataTertib::create([
            'kode' => 'TST-01',
            'nama_aturan' => 'Aturan Uji',
            'tingkat' => 'sedang',
            'kategori' => 'perilaku',
            'bobot_poin' => 20,
            'is_active' => true,
        ]);

        $siswa = PesertaDidik::first();
        if (!$siswa) {
            $this->markTestSkipped('No peserta didik found.');
        }

        $pelanggaran = KedisiplinanPelanggaran::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tata_tertib_id' => $tatib->id,
            'tanggal_kejadian' => date('Y-m-d'),
            'tempat_kejadian' => 'Pos Gerbang',
            'poin' => 20,
            'status_tindak_lanjut' => 'pending',
        ]);

        $this->assertEquals(20, $pelanggaran->poin_netto);

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'role' => 'admin',
                'nama' => 'Guru BK Tester',
                'permissions' => ['menu_kesiswaan_kedisiplinan' => ['read' => true, 'create' => true, 'update' => true, 'delete' => true]],
            ],
        ])->postJson(route('dashboard.kesiswaan.kedisiplinan.pelanggaran.sanggah', $pelanggaran->id), [
            'sanggah_jenis' => 'bakti_sekolah',
            'sanggah_keterangan' => 'Membersihkan taman sekolah dan perpustakaan selama 60 menit.',
            'sanggah_poin_potong' => 20,
            'sanggah_validator' => 'Guru BK Tester',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');

        $pelanggaran->refresh();
        $this->assertEquals('selesai', $pelanggaran->status_tindak_lanjut);
        $this->assertEquals('selesai', $pelanggaran->sanggah_status);
        $this->assertEquals(20, $pelanggaran->sanggah_poin_potong);
        $this->assertEquals(0, $pelanggaran->poin_netto);
    }

    public function test_rekap_tab_is_default_and_renders_charts(): void
    {
        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'role' => 'admin',
                'nama' => 'Admin Tester',
                'permissions' => ['menu_kesiswaan_kedisiplinan' => ['read' => true]],
            ],
        ])->get(route('dashboard.kesiswaan.kedisiplinan.index'));

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi &amp; Analisis', false);
        $response->assertSee('chartTrenPelanggaran');
        $response->assertSee('chartTingkatPelanggaran');
        $response->assertSee('chartKategoriPelanggaran');
        $response->assertSee('chartStatusPelanggaran');
    }

    public function test_keamanan_cetak_per_modul_routes(): void
    {
        $session = ['user' => ['id' => 1, 'role' => 'admin', 'nama' => 'Admin', 'permissions' => [
            'menu_keamanan' => ['read' => true],
            'menu_buku_tamu' => ['read' => true],
        ]]];

        $cases = [
            'dashboard.keamanan.buku-tamu.cetak' => 'Laporan Buku Tamu',
            'dashboard.keamanan.izin.cetak' => 'Laporan e-Izin',
            'dashboard.keamanan.pelanggaran.cetak' => 'Laporan Pelanggaran',
            'dashboard.keamanan.patroli.cetak' => 'Log Patroli',
            'dashboard.keamanan.insiden.cetak' => 'Insiden',
        ];

        foreach ($cases as $routeName => $judul) {
            $res = $this->withSession($session)->get(route($routeName));
            $res->assertStatus(200);
            $res->assertSee($judul, false);
        }
    }

    public function test_keamanan_pelanggaran_store(): void
    {
        $tatib = KedisiplinanTataTertib::first();
        $siswa = PesertaDidik::first();
        if (!$tatib || !$siswa) {
            $this->markTestSkipped('Tatib or siswa not available.');
        }

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'role' => 'admin',
                'nama' => 'Pak Joko Satpam',
                'permissions' => ['menu_keamanan' => ['read' => true, 'create' => true]],
            ],
        ])->post(route('dashboard.keamanan.pelanggaran.store'), [
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tata_tertib_id' => $tatib->id,
            'tanggal_kejadian' => date('Y-m-d'),
            'tempat_kejadian' => 'Pos Gerbang Utama',
            'keterangan' => 'Siswa keluar gerbang tanpa surat izin saat jam pelajaran.',
        ]);

        $response->assertRedirect(route('dashboard.keamanan.pelanggaran.index'));
        $this->assertDatabaseHas('kedisiplinan_pelanggaran', [
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tempat_kejadian' => 'Pos Gerbang Utama',
            'pelapor_nama' => 'Pak Joko Satpam (Pos Keamanan)',
        ]);
    }

    public function test_delete_pelanggaran_total(): void
    {
        $tatib = KedisiplinanTataTertib::first();
        $siswa = PesertaDidik::first();
        if (!$tatib || !$siswa) {
            $this->markTestSkipped('Tatib or siswa not available.');
        }

        $pelanggaran = KedisiplinanPelanggaran::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tata_tertib_id' => $tatib->id,
            'tanggal_kejadian' => date('Y-m-d'),
            'tempat_kejadian' => 'Uji Delete',
            'poin' => 15,
            'status_tindak_lanjut' => 'pending',
        ]);

        $response = $this->withSession([
            'user' => [
                'id' => 1,
                'role' => 'admin',
                'nama' => 'Kesiswaan Admin',
                'permissions' => ['menu_kesiswaan_kedisiplinan' => ['delete' => true, 'read' => true]],
            ],
        ])->deleteJson(route('dashboard.kesiswaan.kedisiplinan.pelanggaran.destroy', $pelanggaran->id));

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $this->assertDatabaseMissing('kedisiplinan_pelanggaran', ['id' => $pelanggaran->id]);
    }

    public function test_keamanan_pelanggaran_tata_tertib_tab_readonly(): void
    {
        $session = [
            'user' => [
                'id' => 1,
                'role' => 'admin',
                'nama' => 'Pak Joko Satpam',
                'permissions' => ['menu_keamanan' => ['read' => true]],
            ],
        ];

        // 1. Tab Pelanggaran default
        $response = $this->withSession($session)->get(route('dashboard.keamanan.pelanggaran.index'));
        $response->assertStatus(200);
        $response->assertSee('Catatan Pelanggaran');
        $response->assertSee('Tata Tertib &amp; Poin', false);

        // 2. Tab Tata Tertib readonly
        $responseTatib = $this->withSession($session)->get(route('dashboard.keamanan.pelanggaran.index', ['tab' => 'tata_tertib']));
        $responseTatib->assertStatus(200);
        $responseTatib->assertSee('Panduan Readonly Tata Tertib');
        $responseTatib->assertSee('Uraian Tata Tertib &amp; Pelanggaran', false);
        $responseTatib->assertSee('Bobot Poin');
        $responseTatib->assertSee('Sanksi Rekomendasi');
    }
}
