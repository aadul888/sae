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
}
