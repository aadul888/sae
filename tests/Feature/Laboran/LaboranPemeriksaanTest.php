<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * E. Pemeriksaan / Daftar Ceklist Alat & Bahan.
 */
class LaboranPemeriksaanTest extends LaboranTestCase
{
    public function test_pemeriksaan_membuat_ceklist_dan_menghitung_rekap(): void
    {
        [$itemId] = $this->seedItems();
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                'tanggal_pemeriksaan' => now()->toDateString(),
                'jenis'               => 'bulanan',
                'item_ids'            => [$itemId],
                'status'              => 'draf',
            ])
            ->assertStatus(200);

        $pemeriksaan = DB::table('laboran_pemeriksaan')->latest('id')->first();
        $this->assertNotNull($pemeriksaan);
        $this->assertSame(1, (int) $pemeriksaan->total_item);
        $this->assertStringStartsWith('PRK/', $pemeriksaan->nomor_pemeriksaan);

        $item = DB::table('laboran_pemeriksaan_item')->where('pemeriksaan_id', $pemeriksaan->id)->first();
        $this->assertNotNull($item);

        // Ubah hasil menjadi tidak sesuai
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pemeriksaan.simpan-item', $pemeriksaan->id), [
                'items' => [[
                    'id'         => $item->id,
                    'hasil'      => 'tidak_sesuai',
                    'keterangan' => 'Gagang longgar',
                ]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('rekap.total_tidak_sesuai', 1);

        $after = DB::table('laboran_pemeriksaan')->where('id', $pemeriksaan->id)->first();
        $this->assertSame(1, (int) $after->total_tidak_sesuai);
        $this->assertSame(0, (int) $after->total_sesuai);
    }

    public function test_halaman_detail_pemeriksaan_menampilkan_item(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                'tanggal_pemeriksaan' => now()->toDateString(),
                'jenis'               => 'harian',
                'item_ids'            => [$itemId],
            ]);

        $pemeriksaan = DB::table('laboran_pemeriksaan')->latest('id')->first();

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.pemeriksaan.show', $pemeriksaan->id));

        $res->assertStatus(200);
        $res->assertViewHas('items');
        $this->assertCount(1, $res->viewData('items'));
    }

    public function test_pemeriksaan_mendukung_jenis_sebelum_dan_sesudah_pakai(): void
    {
        [$itemId] = $this->seedItems();

        foreach (['sebelum_pakai', 'sesudah_pakai'] as $jenis) {
            $this->withSession($this->adminSession())
                ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                    'tanggal_pemeriksaan' => now()->toDateString(),
                    'jenis'               => $jenis,
                    'item_ids'            => [$itemId],
                    'status'              => 'draf',
                ])
                ->assertStatus(200);

            $row = DB::table('laboran_pemeriksaan')->latest('id')->first();
            $this->assertSame($jenis, $row->jenis);
        }
    }
}
