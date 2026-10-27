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
     * Peminjaman alat lab dengan peminjam siswa terisi otomatis dari data siswa
     * (daftar siswa dibatasi sesuai jurusan laboran).
     */
    public function test_peminjaman_alat_dengan_siswa_otomatis(): void
    {
        $session = $this->adminSession();
        $siswa = DB::table('peserta_didik')->first();

        // 1 item lab
        [$alatId] = $this->seedItems();

        $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
            'peminjam_tipe'           => 'siswa',
            'peminjam_id'             => $siswa ? $siswa->peserta_didik_id : '12345',
            'peminjam_nama'           => $siswa ? $siswa->nama : 'Siswa Uji',
            'keperluan'               => 'Praktik lapangan',
            'tanggal_pinjam'          => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'item_ids'                => [$alatId],
            'item_jumlah'             => [$alatId => 1],
        ])->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertNotNull($pinjam);
        $this->assertSame(1, (int) $pinjam->jumlah_item);

        // Serah terima -> stok berkurang
        $this->withSession($session)->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), [
            'status' => 'dipinjam',
        ])->assertStatus(200);

        // Pengembalian
        $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.kembalikan', $pinjam->id), [
            'kondisi_kembali' => 'baik',
        ])->assertStatus(200);

        $this->assertSame('dikembalikan', DB::table('laboran_peminjaman')->where('id', $pinjam->id)->value('status'));
    }

    /**
     * Daftar siswa peminjam hanya memuat peserta didik dari jurusan laboran.
     */
    public function test_daftar_siswa_peminjam_dibatasi_jurusan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)->get(route('dashboard.laboran.peminjaman-alat.index'));
        $res->assertStatus(200);

        $siswaList = $res->viewData('siswaList');
        $jurusanIds = DB::table('rombongan_belajar')
            ->whereIn('rombongan_belajar_id', $siswaList->pluck('rombongan_belajar_id')->filter()->all())
            ->pluck('jurusan_id')
            ->unique();

        $this->assertTrue(
            $jurusanIds->every(fn ($j) => $j === self::JUR_ATPH),
            'Daftar siswa peminjam harus hanya dari jurusan laboran.'
        );
    }
}
