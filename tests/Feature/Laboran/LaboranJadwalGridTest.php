<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * N. Matriks Grid jadwal laboran (pola Jadwal KBM): view_mode grid/table,
 * kolom = hari, kartu sesi memuat lokasi, endpoint guru-mapel modal simpel.
 */
class LaboranJadwalGridTest extends LaboranTestCase
{
    public function test_halaman_default_menampilkan_matriks_grid(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);
        $res->assertSee('Matriks Grid', false);
        $res->assertSee('Rekap Tabel', false);
        $res->assertSee('gridMatrixContainer', false);
        $res->assertSee('Jam Pelajaran', false);
    }

    public function test_kolom_matriks_adalah_hari_bukan_lokasi(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid']));

        $res->assertStatus(200);
        // Header kolom = hari
        $res->assertSee('Sabtu', false);
        // Kartu berisi info lokasi via teks bantu
        $res->assertSee('sesi', false);
    }

    public function test_endpoint_guru_mapel_mengembalikan_daftar_gabungan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->getJson(route('dashboard.laboran.jadwal.guru-mapel'));

        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $list = $res->json('guru_mapel');
        $this->assertIsArray($list);
        $this->assertNotEmpty($list, 'Data pembelajaran harus tersedia pada DB uji.');

        $first = $list[0];
        $this->assertArrayHasKey('pembelajaran_id', $first);
        $this->assertArrayHasKey('mata_pelajaran', $first);
        $this->assertArrayHasKey('rombel_nama', $first);
        $this->assertArrayHasKey('nama_guru', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertStringContainsString('—', $first['label']);
    }

    public function test_sesi_terjadwal_muncul_pada_matriks_hari_sesuai(): void
    {
        $rombel = DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
        })->first();

        // 2026-11-02 = Senin
        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'     => self::JUR_ATPH,
            'ruang_lab_nama' => 'Lahan Praktik ATPH',
            'jenis_lokasi'   => 'lahan',
            'ptk_id'         => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'UJI-GRID-MATRIKS',
            'tanggal'        => '2026-11-02',
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '09:30',
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));

        $res->assertStatus(200);
        $res->assertSee('UJI-GRID-MATRIKS', false);
        $res->assertSee('Lahan Praktik ATPH', false);
        $res->assertSee('Isi Jadwal', false);
    }

    public function test_mode_tabel_tetap_menampilkan_rekap(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'table']));

        $res->assertStatus(200);
        $res->assertSee('tableDataContainer', false);
        $res->assertDontSee('gridMatrixContainer', false);
    }
}
