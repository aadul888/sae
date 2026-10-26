<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * C. Analisis & Kebutuhan Alat/Bahan, termasuk analisis otomatis dari stok.
 */
class LaboranKebutuhanTest extends LaboranTestCase
{
    public function test_simpan_kebutuhan_menghitung_selisih(): void
    {
        $res = $this->withSession($this->adminSession())->postJson(route('dashboard.laboran.kebutuhan.store'), [
            'nama_item'        => 'Traktor Mini Uji',
            'jenis'            => 'alat',
            'jumlah_kebutuhan' => 5,
            'jumlah_tersedia'  => 2,
            'satuan'           => 'unit',
            'prioritas'        => 'kritis',
            'status'           => 'draf',
            'tanggal_input'    => now()->toDateString(),
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'success');

        $row = DB::table('laboran_kebutuhan')->where('nama_item', 'Traktor Mini Uji')->first();
        $this->assertNotNull($row);
        $this->assertEquals(3, (float) $row->selisih);
        $this->assertSame('kritis', $row->prioritas);
    }

    public function test_halaman_kebutuhan_menyajikan_analisis_stok(): void
    {
        $this->seedItems();

        $res = $this->withSession($this->adminSession())->get(route('dashboard.laboran.kebutuhan.index'));

        $res->assertStatus(200);
        $res->assertViewHas('analisis');

        $analisis = $res->viewData('analisis');
        $this->assertArrayHasKey('kritis', $analisis);
        $this->assertArrayHasKey('perlu_perhatian', $analisis);
        $this->assertArrayHasKey('total_item', $analisis);
    }

    public function test_jalankan_analisis_kebutuhan(): void
    {
        $session = $this->adminSession();

        // Buat 1 item inventaris kritis (stok habis)
        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama' => 'Lab Komputer 1',
            'kode_item'      => 'KRIT-UJI-' . uniqid(),
            'nama_item'      => 'Kabel LAN Uji Habis',
            'jenis'          => 'bahan_habis_pakai',
            'stok_total'     => 10,
            'stok_tersedia'  => 0, // Habis
            'satuan'         => 'roll',
            'kondisi'        => 'rusak_berat',
            'jurusan_id'     => self::JUR_TKJ,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.kebutuhan.jalankan-analisis'))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $kebutuhan = DB::table('laboran_kebutuhan')->where('nama_item', 'Kabel LAN Uji Habis')->first();
        $this->assertNotNull($kebutuhan, 'Analisis otomatis harus memasukkan item kritis ke daftar kebutuhan.');
        $this->assertSame('kritis', $kebutuhan->prioritas);
    }
}
