<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian fitur baru Sarpras:
 *  1. Input Ruang & Gedung dengan panjang, lebar, dan luas.
 *  2. Inventaris & Aset terhubung dengan jurusan serta inventaris laboran.
 */
class SarprasRuangDanAsetTest extends TestCase
{
    private array $createdRuang = [];
    private array $createdAset  = [];
    private array $createdLab   = [];

    protected function tearDown(): void
    {
        if (!empty($this->createdRuang)) {
            DB::table('sarpras_ruang')->whereIn('id', $this->createdRuang)->delete();
        }
        if (!empty($this->createdAset)) {
            DB::table('sarpras_aset')->whereIn('id', $this->createdAset)->delete();
        }
        if (!empty($this->createdLab)) {
            DB::table('laboran_bahan_alat')->whereIn('id', $this->createdLab)->delete();
        }

        parent::tearDown();
    }

    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name'        => $admin->nama,
            'nama'        => $admin->nama,
            'username'    => $admin->username,
            'role'        => 'admin',
            'peran_id_str'=> $admin->peran_id_str,
        ];
    }

    public function test_ruang_store_with_panjang_lebar_and_auto_luas(): void
    {
        $session = ['user' => $this->adminSession()];

        $response = $this->withSession($session)->postJson(route('dashboard.sarpras.ruang.store'), [
            'kode_ruang' => 'TEST-R-DIM-01',
            'nama_ruang' => 'Ruang Uji Dimensi',
            'gedung'     => 'Gedung Uji',
            'lantai'     => '1',
            'panjang'    => 10.5,
            'lebar'      => 8.0,
            'kondisi'    => 'baik',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $ruang = DB::table('sarpras_ruang')->where('kode_ruang', 'TEST-R-DIM-01')->first();
        $this->assertNotNull($ruang);
        $this->createdRuang[] = $ruang->id;

        $this->assertEquals(10.5, (float) $ruang->panjang);
        $this->assertEquals(8.0, (float) $ruang->lebar);
        $this->assertEquals(84.0, (float) $ruang->luas); // 10.5 * 8.0 = 84.0
    }

    public function test_ruang_update_custom_luas(): void
    {
        $session = ['user' => $this->adminSession()];

        $id = DB::table('sarpras_ruang')->insertGetId([
            'kode_ruang' => 'TEST-R-DIM-02',
            'nama_ruang' => 'Ruang Uji Bentuk L',
            'gedung'     => 'Gedung Uji',
            'lantai'     => '2',
            'panjang'    => 12.0,
            'lebar'      => 9.0,
            'luas'       => 108.0,
            'kondisi'    => 'baik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRuang[] = (int) $id;

        $response = $this->withSession($session)->putJson(route('dashboard.sarpras.ruang.update', $id), [
            'kode_ruang' => 'TEST-R-DIM-02',
            'nama_ruang' => 'Ruang Uji Bentuk L (Diperbarui)',
            'gedung'     => 'Gedung Uji',
            'lantai'     => '2',
            'panjang'    => 12.0,
            'lebar'      => 9.0,
            'luas'       => 95.5, // custom / irregular shape
            'kondisi'    => 'baik',
        ]);

        $response->assertStatus(200);

        $updated = DB::table('sarpras_ruang')->where('id', $id)->first();
        $this->assertEquals(95.5, (float) $updated->luas);
    }

    public function test_aset_store_with_jurusan_allocation(): void
    {
        $session = ['user' => $this->adminSession()];

        $response = $this->withSession($session)->postJson(route('dashboard.sarpras.aset.store'), [
            'kode_aset'    => 'AST-TEST-JUR-01',
            'nama_barang'  => 'Router Cisco Catalyst Jurusan TKJ',
            'kategori'     => 'Elektronik',
            'sumber_dana'  => 'BOS Reguler',
            'kondisi'      => 'baik',
            'jurusan_id'   => '16012425', // TKJ
            'jumlah'       => 4,
            'satuan'       => 'unit',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $aset = DB::table('sarpras_aset')->where('kode_aset', 'AST-TEST-JUR-01')->first();
        $this->assertNotNull($aset);
        $this->createdAset[] = $aset->id;

        $this->assertSame('16012425', $aset->jurusan_id);
    }

    public function test_aset_index_renders_both_sarpras_and_laboran_tabs(): void
    {
        $session = ['user' => $this->adminSession()];

        // 1. Tab Sarpras
        $resSarpras = $this->withSession($session)->get(route('dashboard.sarpras.aset.index', ['tab' => 'sarpras']));
        $resSarpras->assertStatus(200);
        $resSarpras->assertViewHas('tab', 'sarpras');
        $resSarpras->assertViewHas('jurusanList');
        $this->assertStringContainsString('Aset Sekolah &amp; Sarpras', $resSarpras->getContent());
        $this->assertStringContainsString('Inventaris Jurusan (Laboran)', $resSarpras->getContent());

        // 2. Tab Laboran
        $resLaboran = $this->withSession($session)->get(route('dashboard.sarpras.aset.index', ['tab' => 'laboran']));
        $resLaboran->assertStatus(200);
        $resLaboran->assertViewHas('tab', 'laboran');
        $resLaboran->assertViewHas('labList');
        $this->assertStringContainsString('Total Item Jurusan', $resLaboran->getContent());
    }

    public function test_aset_tab_laboran_displays_laboran_items_across_jurusans(): void
    {
        $session = ['user' => $this->adminSession()];

        // Tambah dummy item di laboran_bahan_alat
        $labId = DB::table('laboran_bahan_alat')->insertGetId([
            'kode_item'      => 'ALT-TEST-LAB-01',
            'nama_item'      => 'Oscilloscope Digital Laboratorium Elektronika',
            'jenis'          => 'alat',
            'ruang_lab_nama' => 'Lab Komputer & Jaringan',
            'jurusan_id'     => '16012425', // TKJ
            'stok_total'     => 5,
            'stok_tersedia'  => 5,
            'jumlah_baik'    => 5,
            'satuan'         => 'unit',
            'kondisi'        => 'baik',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        $this->createdLab[] = (int) $labId;

        $res = $this->withSession($session)->get(route('dashboard.sarpras.aset.index', [
            'tab' => 'laboran',
            'q'   => 'Oscilloscope Digital',
        ]));

        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertStringContainsString('Oscilloscope Digital Laboratorium Elektronika', $html);
        $this->assertStringContainsString('ALT-TEST-LAB-01', $html);
        $this->assertStringContainsString('TKJ', $html);
    }

    public function test_aset_store_auto_generates_kode_and_saves_rincian_kondisi(): void
    {
        $session = ['user' => $this->adminSession()];

        $response = $this->withSession($session)->postJson(route('dashboard.sarpras.aset.store'), [
            'kode_aset'           => '', // dikosongkan agar dibuat otomatis
            'nama_barang'         => 'Traktor Roda Dua Alat Praktik',
            'kategori'            => 'Mesin',
            'tanggal_perolehan'   => '2025-05-10',
            'tahun_perolehan'     => '2025',
            'sumber_dana'         => 'DAK Fisik',
            'harga_perolehan'     => 35000000,
            'jumlah'              => 10,
            'satuan'              => 'unit',
            'jumlah_baik'         => 6,
            'jumlah_rusak_ringan' => 3,
            'jumlah_rusak_berat'  => 1,
            'jurusan_id'          => '26010475', // ATPH
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $aset = DB::table('sarpras_aset')->where('nama_barang', 'Traktor Roda Dua Alat Praktik')->first();
        $this->assertNotNull($aset);
        $this->createdAset[] = $aset->id;

        // Kode dibuat otomatis dengan format AST-YYYYMM-NNNN
        $this->assertMatchesRegularExpression('/^AST-\d{6}-\d{4}$/', $aset->kode_aset);
        $this->assertEquals(6, $aset->jumlah_baik);
        $this->assertEquals(3, $aset->jumlah_rusak_ringan);
        $this->assertEquals(1, $aset->jumlah_rusak_berat);
        $this->assertEquals('baik', $aset->kondisi); // kondisi dominan
        $this->assertEquals('2025-05-10', $aset->tanggal_perolehan);
        $this->assertEquals('2025', $aset->tahun_perolehan);
        $this->assertEquals('26010475', $aset->jurusan_id);
    }

    public function test_aset_template_download_and_service_generation(): void
    {
        $session = ['user' => $this->adminSession()];

        $res = $this->withSession($session)->get(route('dashboard.sarpras.aset.template'));
        $res->assertStatus(200);
        $res->assertHeader('content-disposition');
        $this->assertStringContainsString('Format_Import_Aset_Sarpras.xlsx', $res->headers->get('content-disposition'));
    }

    /**
     * Teknisi: boleh lihat + ubah Ruang & Gedung dan buka Inventaris & Aset,
     * tetapi TIDAK boleh menambah atau menghapus ruang.
     */
    public function test_teknisi_boleh_lihat_dan_ubah_ruang_tanpa_tambah_hapus(): void
    {
        $teknisi = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->where('p.is_active', true)
            ->where('r.kode', 'like', 'TEKNISI%')
            ->whereNotNull('p.user_id')
            ->select('p.user_id', 'p.ptk_id')
            ->first();

        if (!$teknisi) {
            $this->markTestSkipped('Tidak ada akun teknisi aktif untuk diuji.');
        }

        $session = ['user' => [
            'id'           => $teknisi->user_id,
            'pengguna_id'  => $teknisi->user_id,
            'name'         => 'Teknisi Uji',
            'nama'         => 'Teknisi Uji',
            'username'     => 'teknisi_ruang_uji',
            'role'         => 'tendik',
            'peran_id_str' => 'Tenaga Administrasi',
            'ptk_id'       => $teknisi->ptk_id,
        ]];

        // Baca: boleh
        $this->withSession($session)
            ->get(route('dashboard.sarpras.ruang.index'))
            ->assertStatus(200);

        // Inventaris & Aset: boleh dibuka
        $this->withSession($session)
            ->get(route('dashboard.sarpras.aset.index'))
            ->assertStatus(200);

        // Ubah: boleh
        $id = DB::table('sarpras_ruang')->insertGetId([
            'kode_ruang' => 'TEST-R-TEKNISI-01',
            'nama_ruang' => 'Ruang Uji Teknisi',
            'gedung'     => 'Gedung Uji',
            'lantai'     => '1',
            'kondisi'    => 'baik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRuang[] = (int) $id;

        $this->withSession($session)->putJson(route('dashboard.sarpras.ruang.update', $id), [
            'kode_ruang' => 'TEST-R-TEKNISI-01',
            'nama_ruang' => 'Ruang Uji Teknisi',
            'gedung'     => 'Gedung Uji',
            'lantai'     => '1',
            'kondisi'    => 'rusak_ringan',
        ])->assertStatus(200);

        $this->assertSame('rusak_ringan', DB::table('sarpras_ruang')->where('id', $id)->value('kondisi'));

        // Tambah: ditolak
        $this->withSession($session)->postJson(route('dashboard.sarpras.ruang.store'), [
            'kode_ruang' => 'TEST-R-TEKNISI-02',
            'nama_ruang' => 'Ruang Baru Teknisi',
            'kondisi'    => 'baik',
        ])->assertStatus(403);

        // Hapus: ditolak
        $this->withSession($session)
            ->deleteJson(route('dashboard.sarpras.ruang.destroy', $id))
            ->assertStatus(403);
    }
}
