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
     * Form peminjam memakai live search (nama/NISN/NIP) melalui endpoint orang
     * bersama, dan lokasi pengambilan berupa input dengan datalist.
     */
    public function test_form_peminjam_memakai_live_search(): void
    {
        $html = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->get(route('dashboard.laboran.peminjaman-alat.index'))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('data-people-search="' . route('dashboard.people.search') . '"', $html);
        $this->assertStringContainsString('data-people-kind="siswa"', $html);
        $this->assertStringContainsString('id="daftarLokasiPeminjaman"', $html);
    }

    /**
     * Modal ajukan peminjaman menampilkan seluruh aset Sarpras dalam cakupan
     * (termasuk aset umum sekolah yang belum berjurusan).
     */
    public function test_modal_ajukan_menampilkan_semua_aset_sarpras(): void
    {
        $umum = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-UMUM-' . uniqid(),
            'nama_barang'         => 'Mikroskop Umum Uji',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 3,
            'jumlah_baik'         => 3,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => null,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $atph = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-ATPH-' . uniqid(),
            'nama_barang'         => 'Traktor Mini ATPH',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 2,
            'jumlah_baik'         => 2,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $tkj = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-TKJ-' . uniqid(),
            'nama_barang'         => 'Router TKJ Uji',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 2,
            'jumlah_baik'         => 2,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => self::JUR_TKJ,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $html = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->get(route('dashboard.laboran.peminjaman-alat.index'))
            ->assertStatus(200)
            ->getContent();

        $this->assertStringContainsString('Aset Sarpras', $html);
        $this->assertStringContainsString('name="aset_ids[]"', $html);
        $this->assertStringContainsString('Mikroskop Umum Uji', $html);
        $this->assertStringContainsString('Traktor Mini ATPH', $html);
        $this->assertStringNotContainsString('Router TKJ Uji', $html);

        $this->assertStringContainsString('name="aset_ids[]" value="' . $umum . '"', $html);
        $this->assertStringContainsString('name="aset_ids[]" value="' . $atph . '"', $html);
        $this->assertStringNotContainsString('name="aset_ids[]" value="' . $tkj . '"', $html);
    }

    /**
     * Peminjaman dapat memakai aset Sarpras: baris item tercatat dengan
     * sumber "aset" dan aset_id terisi (bukan item inventaris lab).
     */
    public function test_peminjaman_aset_sarpras_tersimpan_dengan_sumber_aset(): void
    {
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-PJM-' . uniqid(),
            'nama_barang'         => 'Proyektor Uji Pinjam',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 4,
            'jumlah_baik'         => 4,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Uji',
                'keperluan'               => 'Praktik proyektor',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 2],
            ])
            ->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertNotNull($pinjam);
        $this->assertSame(1, (int) $pinjam->jumlah_item);

        $item = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $pinjam->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('aset', $item->sumber);
        $this->assertSame($asetId, (int) $item->aset_id);
        $this->assertNull($item->item_id);
        $this->assertSame('Proyektor Uji Pinjam', $item->nama_item);
        $this->assertEquals(2, (float) $item->jumlah);
    }

    /**
     * Aset yang sedang dipinjam / hilang tidak boleh diajukan.
     */
    public function test_peminjaman_menolak_aset_tidak_tersedia(): void
    {
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-DIPINJAM-' . uniqid(),
            'nama_barang'         => 'Bor Listrik Uji',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 1,
            'jumlah_baik'         => 1,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'dipinjam',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $res = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 1],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak tersedia', $res->json('message'));
        $this->assertSame(0, DB::table('laboran_peminjaman')->count());
    }

    /**
     * Jumlah alat harus bilangan bulat: 1, 2, 3, ... (bukan 1.01).
     */
    public function test_jumlah_alat_harus_bilangan_bulat(): void
    {
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-BULAT-' . uniqid(),
            'nama_barang'         => 'Kursi Uji Bulat',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 5,
            'jumlah_baik'         => 5,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 1,01 ditolak
        $res = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 1.01],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('bilangan bulat', $res->json('message'));
        $this->assertSame(0, DB::table('laboran_peminjaman')->count());

        // 2 diterima
        $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 2],
            ])
            ->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $item = DB::table('laboran_peminjaman_item')->where('peminjaman_id', $pinjam->id)->first();
        $this->assertEquals(2, (float) $item->jumlah);
    }

    /**
     * Meminjam sebagian unit aset tidak boleh mengunci seluruh aset; sisa unit
     * tetap dapat dipinjam sampai unitnya habis.
     */
    public function test_peminjaman_sebagian_unit_tidak_mengunci_aset(): void
    {
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'TST-SEBAGIAN-' . uniqid(),
            'nama_barang'         => 'Mikroskop Uji Sebagian',
            'kategori'            => 'alat',
            'kondisi'             => 'baik',
            'jumlah'              => 3,
            'jumlah_baik'         => 3,
            'satuan'              => 'unit',
            'status_ketersediaan' => 'tersedia',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        // Pinjam 2 dari 3 unit.
        $resStore = $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 2],
            ]);

        $resStore->assertStatus(200);
        $pinjamId = (int) $resStore->json('id');
        $this->assertGreaterThan(0, $pinjamId, 'Peminjaman harus tersimpan dan mengembalikan id.');

        // Setujui + serah terima -> unit tertahan.
        $this->withSession($session)
            ->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjamId), ['status' => 'dipinjam'])
            ->assertStatus(200);

        // Aset tetap dapat dipinjam karena masih ada 1 unit.
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Lain',
                'keperluan'               => 'Praktik sisa unit',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 1],
            ])
            ->assertStatus(200);

        // Meminta seluruh 3 unit lagi ditolak (sisa 0).
        $res = $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'guru',
                'peminjam_nama'           => 'Guru Ketiga',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'aset_ids'                => [$asetId],
                'aset_jumlah'             => [$asetId => 3],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('melebihi unit tersedia', $res->json('message'));
    }
}
