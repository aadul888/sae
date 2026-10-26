<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * D. Perawatan / Pemeliharaan / Penataan Alat (K3) + Kartu Perawatan.
 */
class LaboranPerawatanTest extends LaboranTestCase
{
    public function test_simpan_perawatan_menyimpan_apd_dan_menyesuaikan_kondisi(): void
    {
        [$itemId] = $this->seedItems();
        $session = $this->adminSession();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.perawatan.store'), [
            'item_id'           => $itemId,
            'jenis_perawatan'   => 'rutin',
            'kategori_k3'       => 'mekanik',
            'tanggal_perawatan' => now()->toDateString(),
            'uraian_pekerjaan'  => 'Pembersihan dan pelumasan',
            'apd_digunakan'     => ['Sarung Tangan', 'Sepatu Safety'],
            'status'            => 'selesai',
            'kondisi_akhir'     => 'rusak_ringan',
        ]);

        $res->assertStatus(200);

        $row = DB::table('laboran_perawatan')->where('item_id', $itemId)->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('PRW/', $row->kode_perawatan);
        $this->assertSame('mekanik', $row->kategori_k3);

        $apd = json_decode($row->apd_digunakan, true);
        $this->assertContains('Sarung Tangan', $apd);

        // Kondisi inventaris tersinkron
        $this->assertSame('rusak_ringan', DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kondisi'));
    }

    public function test_kartu_perawatan_menghitung_jadwal_berikutnya(): void
    {
        [$itemId] = $this->seedItems();
        $mulai = '2026-01-10';

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.store'), [
                'item_id'       => $itemId,
                'nama_alat'     => 'Cangkul Uji',
                'tanggal_mulai' => $mulai,
                'interval_hari' => 30,
                'status'        => 'aktif',
            ])
            ->assertStatus(200);

        $kartu = DB::table('laboran_kartu_perawatan')->where('item_id', $itemId)->first();
        $this->assertNotNull($kartu);
        $this->assertSame('2026-02-09', $kartu->jadwal_berikutnya);
        $this->assertStringStartsWith('KRT/', $kartu->kode_kartu);
    }

    public function test_tandai_kartu_selesai_menggeser_jadwal(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.store'), [
                'item_id'       => $itemId,
                'nama_alat'     => 'Cangkul Uji',
                'tanggal_mulai' => now()->toDateString(),
                'interval_hari' => 15,
            ])
            ->assertStatus(200);

        $kartu = DB::table('laboran_kartu_perawatan')->where('item_id', $itemId)->first();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.selesai', $kartu->id))
            ->assertStatus(200);

        $baru = DB::table('laboran_kartu_perawatan')->where('id', $kartu->id)->value('jadwal_berikutnya');
        $this->assertSame(now()->addDays(15)->toDateString(), $baru);
    }
}
