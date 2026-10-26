<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian Modul-Modul Baru Sarpras:
 *  1. Kartu Inventaris Ruangan (KIR) & Cetak Lembar KIR
 *  2. Barang Habis Pakai & ATK (Persediaan & Mutasi Masuk/Keluar)
 *  3. Laporan & Buku Induk Inventaris Sekolah & Cetak
 */
class SarprasModulesTest extends TestCase
{
    private array $createdPersediaan = [];
    private array $createdRuang = [];
    private array $createdAset = [];

    protected function tearDown(): void
    {
        if (!empty($this->createdPersediaan)) {
            DB::table('sarpras_persediaan_mutasi')->whereIn('persediaan_id', $this->createdPersediaan)->delete();
            DB::table('sarpras_persediaan')->whereIn('id', $this->createdPersediaan)->delete();
        }
        if (!empty($this->createdAset)) {
            DB::table('sarpras_aset')->whereIn('id', $this->createdAset)->delete();
        }
        if (!empty($this->createdRuang)) {
            DB::table('sarpras_ruang')->whereIn('id', $this->createdRuang)->delete();
        }

        parent::tearDown();
    }

    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ];
    }

    public function test_kir_index_renders_successfully(): void
    {
        $session = ['user' => $this->adminSession()];

        $res = $this->withSession($session)->get(route('dashboard.sarpras.kir.index'));
        $res->assertStatus(200);
        $res->assertViewIs('dashboard.sarpras.kir');
        $this->assertStringContainsString('Kartu Inventaris Ruangan (KIR)', $res->getContent());
    }

    public function test_kir_show_and_cetak_sheet(): void
    {
        $session = ['user' => $this->adminSession()];

        $ruangId = DB::table('sarpras_ruang')->insertGetId([
            'kode_ruang' => 'R-KIR-TEST-01',
            'nama_ruang' => 'Ruang Teori XI ATPH',
            'gedung'     => 'Gedung C',
            'lantai'     => '1',
            'panjang'    => 9.0,
            'lebar'      => 8.0,
            'luas'       => 72.0,
            'kondisi'    => 'baik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRuang[] = (int) $ruangId;

        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'       => 'AST-KIR-TEST-01',
            'nama_barang'     => 'Meja Guru Kayu Jati',
            'kategori'        => 'Mebel/Furnitur',
            'sumber_dana'     => 'BOS Reguler',
            'tahun_perolehan' => 2024,
            'ruang_id'        => $ruangId,
            'jumlah'          => 1,
            'satuan'          => 'unit',
            'kondisi'         => 'baik',
            'jumlah_baik'     => 1,
            'status_ketersediaan' => 'tersedia',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        $this->createdAset[] = (int) $asetId;

        // 1. AJAX Show Detail
        $resShow = $this->withSession($session)->getJson(route('dashboard.sarpras.kir.show', $ruangId));
        $resShow->assertStatus(200);
        $resShow->assertJson(['status' => 'success']);
        $this->assertSame('Ruang Teori XI ATPH', $resShow->json('ruang.nama_ruang'));
        $this->assertCount(1, $resShow->json('items'));

        // 2. Cetak Lembar KIR
        $resCetak = $this->withSession($session)->get(route('dashboard.sarpras.kir.cetak', $ruangId));
        $resCetak->assertStatus(200);
        $this->assertStringContainsString('KARTU INVENTARIS RUANGAN (KIR)', $resCetak->getContent());
        $this->assertStringContainsString('Ruang Teori XI ATPH', $resCetak->getContent());
        $this->assertStringContainsString('Meja Guru Kayu Jati', $resCetak->getContent());
    }

    public function test_persediaan_crud_and_mutasi_stock(): void
    {
        $session = ['user' => $this->adminSession()];

        // 1. Store Barang Persediaan
        $resStore = $this->withSession($session)->postJson(route('dashboard.sarpras.persediaan.store'), [
            'nama_barang'   => 'Kertas HVS PaperOne A4 80gr',
            'kategori'      => 'Kertas & Cetak',
            'spesifikasi'   => 'A4 80 gram 500 lembar per rim',
            'stok_saat_ini' => 50,
            'stok_minimum'  => 10,
            'satuan'        => 'rim',
            'harga_satuan'  => 55000,
            'lokasi_simpan' => 'Lemari ATK Ruang TU',
            'sumber_dana'   => 'BOS Reguler',
        ]);

        $resStore->assertStatus(200);
        $resStore->assertJson(['status' => 'success']);

        $barang = DB::table('sarpras_persediaan')->where('nama_barang', 'Kertas HVS PaperOne A4 80gr')->first();
        $this->assertNotNull($barang);
        $this->createdPersediaan[] = (int) $barang->id;
        $this->assertEquals(50, $barang->stok_saat_ini);

        // 2. Mutasi Keluar
        $resKeluar = $this->withSession($session)->postJson(route('dashboard.sarpras.persediaan.mutasi', $barang->id), [
            'tanggal'              => now()->toDateString(),
            'jenis_mutasi'         => 'keluar',
            'jumlah'               => 15,
            'penerima_atau_sumber' => 'Panitia Ujian Sekolah',
            'keperluan'            => 'Pencetakan Naskah Soal Ujian',
        ]);

        $resKeluar->assertStatus(200);
        $resKeluar->assertJson(['status' => 'success', 'stok' => 35]);

        $barangUpdated = DB::table('sarpras_persediaan')->where('id', $barang->id)->first();
        $this->assertEquals(35, $barangUpdated->stok_saat_ini);

        // 3. Mutasi Masuk
        $resMasuk = $this->withSession($session)->postJson(route('dashboard.sarpras.persediaan.mutasi', $barang->id), [
            'tanggal'              => now()->toDateString(),
            'jenis_mutasi'         => 'masuk',
            'jumlah'               => 20,
            'penerima_atau_sumber' => 'Toko Gramedia / Pengadaan BOS',
            'keperluan'            => 'Penambahan Stok Kuartal IV',
        ]);

        $resMasuk->assertStatus(200);
        $resMasuk->assertJson(['status' => 'success', 'stok' => 55]);

        $barangUpdated2 = DB::table('sarpras_persediaan')->where('id', $barang->id)->first();
        $this->assertEquals(55, $barangUpdated2->stok_saat_ini);
    }

    public function test_persediaan_mutasi_rejects_insufficient_stock(): void
    {
        $session = ['user' => $this->adminSession()];

        $id = DB::table('sarpras_persediaan')->insertGetId([
            'kode_barang'   => 'BHP-TEST-MIN-01',
            'nama_barang'   => 'Tinta Epson 003 Black',
            'kategori'      => 'Tinta & Toner',
            'stok_saat_ini' => 3,
            'stok_minimum'  => 2,
            'satuan'        => 'botol',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->createdPersediaan[] = (int) $id;

        // Coba mutasi keluar 10 botol padahal stok hanya 3 botol
        $res = $this->withSession($session)->postJson(route('dashboard.sarpras.persediaan.mutasi', $id), [
            'tanggal'              => now()->toDateString(),
            'jenis_mutasi'         => 'keluar',
            'jumlah'               => 10,
            'penerima_atau_sumber' => 'Guru Penguji',
        ]);

        $res->assertStatus(422);
        $res->assertJson(['status' => 'error']);

        // Pastikan stok tidak berkurang
        $this->assertEquals(3, DB::table('sarpras_persediaan')->where('id', $id)->value('stok_saat_ini'));
    }

    public function test_laporan_buku_induk_renders_and_cetak(): void
    {
        $session = ['user' => $this->adminSession()];

        // 1. Index Laporan Buku Induk
        $resIndex = $this->withSession($session)->get(route('dashboard.sarpras.laporan.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertViewIs('dashboard.sarpras.laporan');
        $this->assertStringContainsString('Buku Induk Inventaris', $resIndex->getContent());

        // 2. Cetak Dokumen Resmi Buku Induk
        $resCetak = $this->withSession($session)->get(route('dashboard.sarpras.laporan.cetak'));
        $resCetak->assertStatus(200);
        $this->assertStringContainsString('BUKU INDUK INVENTARIS ASET DAN SARANA SEKOLAH', $resCetak->getContent());
    }
}
