<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * G. Berita Acara Kerusakan Alat.
 */
class LaboranKerusakanTest extends LaboranTestCase
{
    public function test_berita_acara_kerusakan_menyesuaikan_kondisi_inventaris(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'berat',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'kronologi'         => 'Terjatuh saat praktik',
                'status'            => 'diajukan',
            ])
            ->assertStatus(200);

        $ba = DB::table('laboran_berita_acara_kerusakan')->where('item_id', $itemId)->first();
        $this->assertNotNull($ba);
        $this->assertStringStartsWith('BAK/', $ba->nomor_ba);

        // Kerusakan berat -> inventaris menjadi rusak_berat
        $this->assertSame('rusak_berat', DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kondisi'));
    }

    public function test_cetak_berita_acara_menghasilkan_dokumen(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'ringan',
            ]);

        $ba = DB::table('laboran_berita_acara_kerusakan')->latest('id')->first();

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kerusakan.cetak', $ba->id));

        $res->assertStatus(200);
        $res->assertSee('BERITA ACARA KERUSAKAN ALAT', false);
        $res->assertSee($ba->nomor_ba, false);
    }
}
