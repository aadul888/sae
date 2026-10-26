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

        // 1. Buat 1 item inventaris kritis (stok habis)
        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama'      => 'Lab Komputer 1',
            'kode_item'           => 'KRIT-UJI-' . uniqid(),
            'nama_item'           => 'Kabel LAN Uji Habis',
            'jenis'               => 'bahan_habis_pakai',
            'stok_total'          => 10,
            'stok_tersedia'       => 0, // Habis
            'satuan'              => 'roll',
            'kondisi'             => 'rusak_berat',
            'jurusan_id'          => self::JUR_TKJ,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 2. Buat item alat dengan kondisi rincian (10 unit: 5 baik, 3 rusak ringan, 2 rusak berat)
        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama'      => 'Lahan Praktik Pertanian',
            'kode_item'           => 'ALT-TRK-' . uniqid(),
            'nama_item'           => 'Traktor Lahan Uji',
            'jenis'               => 'alat',
            'stok_total'          => 10,
            'stok_tersedia'       => 10,
            'jumlah_baik'         => 5,
            'jumlah_rusak_ringan' => 3,
            'jumlah_rusak_berat'  => 2,
            'satuan'              => 'unit',
            'kondisi'             => 'baik',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // 3. Buat Berita Acara Kerusakan
        DB::table('laboran_berita_acara_kerusakan')->insert([
            'jurusan_id'        => self::JUR_ATPH,
            'nomor_ba'          => 'BAK/TEST/' . uniqid(),
            'nama_alat'         => 'Sensor pH Tanah Digital Uji',
            'tanggal_kejadian'  => now()->toDateString(),
            'tanggal_ba'        => now()->toDateString(),
            'tingkat_kerusakan' => 'berat',
            'jumlah_rusak'      => 4,
            'satuan'            => 'unit',
            'penyebab'          => 'Uji tanah lapangan',
            'status'            => 'disetujui',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.kebutuhan.jalankan-analisis'))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $kebutuhan = DB::table('laboran_kebutuhan')->where('nama_item', 'Kabel LAN Uji Habis')->first();
        $this->assertNotNull($kebutuhan, 'Analisis otomatis harus memasukkan item kritis ke daftar kebutuhan.');
        $this->assertSame('kritis', $kebutuhan->prioritas);

        // Traktor dengan 2 rusak berat harus terdeteksi dan dihitung selisihnya
        $kebTraktor = DB::table('laboran_kebutuhan')->where('nama_item', 'Traktor Lahan Uji')->first();
        $this->assertNotNull($kebTraktor, 'Traktor dengan rusak berat harus otomatis masuk kebutuhan.');
        $this->assertSame('kritis', $kebTraktor->prioritas);
        $this->assertGreaterThanOrEqual(2, (float) $kebTraktor->selisih);

        // BA Kerusakan harus otomatis masuk kebutuhan
        $kebBa = DB::table('laboran_kebutuhan')->where('nama_item', 'Sensor pH Tanah Digital Uji')->first();
        $this->assertNotNull($kebBa, 'Alat di Berita Acara Kerusakan harus otomatis masuk kebutuhan.');
        $this->assertSame('kerusakan', $kebBa->sumber_analisis);
        $this->assertEquals(4, (float) $kebBa->selisih);
    }

    public function test_stok_penuh_tapi_rusak_tetap_masuk_analisis(): void
    {
        // Kasus: stok 1/1 (terisi penuh secara kuantitas) TAPI kondisi rusak —
        // analisis berbasis kesehatan unit harus tetap mendeteksi sebagai kritis.
        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama'      => 'Lab Tanaman Uji',
            'kode_item'           => 'ALT-1on1-' . uniqid(),
            'nama_item'           => 'Alat Ukur Suhu Tanah Uji',
            'jenis'               => 'alat',
            'stok_total'          => 1,
            'stok_tersedia'       => 1,
            'jumlah_baik'         => 0,
            'jumlah_rusak_ringan' => 1,
            'jumlah_rusak_berat'  => 0,
            'jumlah_kedaluwarsa'  => 0,
            'satuan'              => 'unit',
            'kondisi'             => 'rusak_ringan',
            'jurusan_id'          => self::JUR_ATPH,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $res = $this->withSession($this->adminSession())->get(route('dashboard.laboran.kebutuhan.index'));
        $res->assertStatus(200);

        $analisis = $res->viewData('analisis');
        $semua = array_merge($analisis['kritis'], $analisis['perlu_perhatian']);
        $terdeteksi = collect($semua)->first(fn ($it) => $it->nama_item === 'Alat Ukur Suhu Tanah Uji');

        $this->assertNotNull($terdeteksi, 'Item stok 1/1 dengan kondisi rusak harus tetap terdeteksi oleh analisis kesehatan unit.');
        $this->assertStringContainsString('laik pakai', $terdeteksi->ringkasan_masalah ?? '');
        $this->assertGreaterThan(0, (float) $terdeteksi->rekomendasi_selisih);

        // Jalankan analisis: item harus masuk daftar kebutuhan dengan tersedia laik = 0
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kebutuhan.jalankan-analisis'))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $keb = DB::table('laboran_kebutuhan')->where('nama_item', 'Alat Ukur Suhu Tanah Uji')->first();
        $this->assertNotNull($keb, 'Item tidak laik pakai walau stok 1/1 harus masuk daftar kebutuhan.');
        $this->assertEquals(0, (float) $keb->jumlah_tersedia);
        $this->assertEquals(1, (float) $keb->selisih);
        $this->assertStringContainsString('tidak laik pakai', (string) $keb->analisis_kebutuhan);
    }
}
