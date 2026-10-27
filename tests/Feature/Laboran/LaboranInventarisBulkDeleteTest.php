<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * M. Inventaris: hapus massal via ceklist (bulk delete).
 */
class LaboranInventarisBulkDeleteTest extends LaboranTestCase
{
    public function test_hapus_massal_menghapus_item_cakupan_dan_melewati_dipinjam(): void
    {
        [$atphId, $tkjId] = $this->seedItems();
        $session = $this->adminSession();

        // TKJ sedang dipinjam aktif -> harus dilewati
        DB::table('laboran_peminjaman')->insert([
            'jurusan_id'              => self::JUR_TKJ,
            'nomor_pinjam'            => 'PJM/UJI/' . uniqid(),
            'peminjam_tipe'           => 'siswa',
            'peminjam_nama'           => 'Siswa Uji Bulk',
            'keperluan'               => 'Praktik uji bulk',
            'tanggal_pinjam'          => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'jumlah_item'             => 1,
            'status'                  => 'dipinjam',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
        $pinjamId = (int) DB::table('laboran_peminjaman')->latest('id')->value('id');
        DB::table('laboran_peminjaman_item')->insert([
            'peminjaman_id' => $pinjamId,
            'item_id'       => $tkjId,
            'sumber'        => 'inventaris',
            'nama_item'     => 'Switch Cisco Uji',
            'jumlah'        => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $res = $this->withSession($session)
            ->postJson(route('dashboard.laboran.inventaris.bulk-delete'), [
                'ids' => [$atphId, $tkjId],
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(1, (int) $res->json('deleted'));
        $this->assertSame(1, (int) $res->json('skipped'));
        $this->assertNull(DB::table('laboran_bahan_alat')->where('id', $atphId)->first());
        $this->assertNotNull(DB::table('laboran_bahan_alat')->where('id', $tkjId)->first());
    }

    public function test_hapus_massal_tanpa_pilihan_ditolak(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.inventaris.bulk-delete'), ['ids' => []])
            ->assertStatus(422);
    }

    public function test_hapus_massal_laboran_hanya_cakupan_jurusannya(): void
    {
        [$atphId, $tkjId] = $this->seedItems();

        $res = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->postJson(route('dashboard.laboran.inventaris.bulk-delete'), [
                'ids' => [$atphId, $tkjId],
            ])
            ->assertStatus(200);

        $this->assertSame(1, (int) $res->json('deleted'));
        $this->assertNull(DB::table('laboran_bahan_alat')->where('id', $atphId)->first());
        $this->assertNotNull(DB::table('laboran_bahan_alat')->where('id', $tkjId)->first());
    }
}
