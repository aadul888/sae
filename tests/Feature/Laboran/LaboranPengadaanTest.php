<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * F. Daftar Usulan Pengadaan Alat & Bahan Praktik.
 */
class LaboranPengadaanTest extends LaboranTestCase
{
    public function test_usulan_pengadaan_menarik_item_dari_kebutuhan(): void
    {
        $session = $this->adminSession();

        $kebutuhanId = DB::table('laboran_kebutuhan')->insertGetId([
            'nama_item'        => 'Mikroskop Uji',
            'jenis'            => 'alat',
            'jumlah_kebutuhan' => 4,
            'jumlah_tersedia'  => 1,
            'selisih'          => 3,
            'satuan'           => 'unit',
            'prioritas'        => 'tinggi',
            'status'           => 'diverifikasi',
            'tanggal_input'    => now()->toDateString(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.store'), [
                'tanggal_usulan' => now()->toDateString(),
                'urgensi'        => 'tinggi',
                'status'         => 'draf',
                'kebutuhan_ids'  => [$kebutuhanId],
            ])
            ->assertStatus(200);

        $usulan = DB::table('laboran_usulan_pengadaan')->latest('id')->first();
        $this->assertNotNull($usulan);
        $this->assertSame(1, (int) $usulan->total_item);
        $this->assertStringStartsWith('USL/', $usulan->nomor_usulan);

        $item = DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $usulan->id)->first();
        $this->assertSame('Mikroskop Uji', $item->nama_item);
        // Jumlah ditarik dari selisih kekurangan
        $this->assertEquals(3, (float) $item->jumlah);
    }

    public function test_rekap_usulan_menghitung_subtotal(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.store'), [
                'tanggal_usulan' => now()->toDateString(),
                'urgensi'        => 'sedang',
            ])
            ->assertStatus(200);

        $usulan = DB::table('laboran_usulan_pengadaan')->latest('id')->first();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.item.store', $usulan->id), [
                'nama_item'    => 'Gelas Ukur',
                'jenis'        => 'alat',
                'jumlah'       => 10,
                'satuan'       => 'unit',
                'harga_satuan' => 25000,
            ])
            ->assertStatus(200);

        $updated = DB::table('laboran_usulan_pengadaan')->where('id', $usulan->id)->first();
        $this->assertEquals(250000, (float) $updated->total_estimasi_biaya);
        $this->assertSame(1, (int) $updated->total_item);
    }

    public function test_usulan_pengadaan_dengan_pilihan_jenis(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.pengadaan.store'), [
                'tanggal_usulan' => now()->toDateString(),
                'jenis'          => 'alat',
                'urgensi'        => 'tinggi',
                'status'         => 'diajukan',
                'justifikasi'    => 'Pengadaan alat praktik semester ganjil',
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_usulan_pengadaan')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('alat', $row->jenis);
    }
}
