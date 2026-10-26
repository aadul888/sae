<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * H. Peminjaman Alat (terpadu Laboran & Sarpras) beserta mutasi stok.
 */
class LaboranPeminjamanTest extends LaboranTestCase
{
    public function test_peminjaman_mengurangi_dan_mengembalikan_stok(): void
    {
        [, $itemId] = $this->seedItems();
        $session = $this->adminSession();

        $stokAwal = (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia');
        $this->assertEquals(5, $stokAwal);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'siswa',
                'peminjam_nama'           => 'Budi Uji',
                'peminjam_kelas'          => 'XI TKJ 1',
                'keperluan'               => 'Praktik jaringan',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(3)->toDateString(),
                'item_ids'                => [$itemId],
                'item_jumlah'             => [$itemId => 2],
            ])
            ->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertSame('diajukan', $pinjam->status);
        $this->assertStringStartsWith('PJM/', $pinjam->nomor_pinjam);

        // Setujui -> belum mengubah stok
        $this->withSession($session)
            ->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), ['status' => 'disetujui'])
            ->assertStatus(200);
        $this->assertEquals(5, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));

        // Serah terima -> stok berkurang
        $this->withSession($session)
            ->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), ['status' => 'dipinjam'])
            ->assertStatus(200);
        $this->assertEquals(3, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));

        // Pengembalian -> stok pulih
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.kembalikan', $pinjam->id), [
                'kondisi_kembali' => 'baik',
            ])
            ->assertStatus(200);

        $this->assertEquals(5, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));
        $this->assertSame('dikembalikan', DB::table('laboran_peminjaman')->where('id', $pinjam->id)->value('status'));
    }

    public function test_peminjaman_menolak_stok_tidak_cukup(): void
    {
        [, $itemId] = $this->seedItems();

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'siswa',
                'peminjam_nama'           => 'Budi Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'item_ids'                => [$itemId],
                'item_jumlah'             => [$itemId => 99],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak cukup', $res->json('message'));
        $this->assertSame(0, DB::table('laboran_peminjaman')->count());
    }

    /**
     * Peminjaman terpadu: satu transaksi dapat memuat alat laboratorium
     * sekaligus aset sarpras, dengan peminjam terisi otomatis dari data siswa.
     */
    public function test_peminjaman_alat_dengan_siswa_otomatis_dan_sarpras(): void
    {
        $session = $this->adminSession();
        $siswa = DB::table('peserta_didik')->first();

        // 1 item lab
        [$alatId] = $this->seedItems();

        // 1 aset sarpras
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'AST-UJI-' . uniqid(),
            'nama_barang'         => 'Laptop Inventaris Uji',
            'kategori'            => 'Elektronik',
            'jumlah'              => 1,
            'satuan'              => 'unit',
            'kondisi'             => 'baik',
            'status_ketersediaan' => 'tersedia',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
            'peminjam_tipe'           => 'siswa',
            'peminjam_id'             => $siswa ? $siswa->peserta_didik_id : '12345',
            'peminjam_nama'           => $siswa ? $siswa->nama : 'Siswa Uji',
            'keperluan'               => 'Praktik lapangan',
            'tanggal_pinjam'          => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'item_ids'                => [$alatId],
            'item_jumlah'             => [$alatId => 1],
            'aset_ids'                => [$asetId],
            'aset_jumlah'             => [$asetId => 1],
        ])->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertNotNull($pinjam);
        $this->assertSame(2, (int) $pinjam->jumlah_item);

        // Serah terima -> aset sarpras berstatus dipinjam
        $this->withSession($session)->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), [
            'status' => 'dipinjam',
        ])->assertStatus(200);

        $this->assertSame('dipinjam', DB::table('sarpras_aset')->where('id', $asetId)->value('status_ketersediaan'));

        // Pengembalian -> aset kembali tersedia
        $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.kembalikan', $pinjam->id), [
            'kondisi_kembali' => 'baik',
        ])->assertStatus(200);

        $this->assertSame('tersedia', DB::table('sarpras_aset')->where('id', $asetId)->value('status_ketersediaan'));

        DB::table('sarpras_aset')->where('id', $asetId)->delete();
    }
}
