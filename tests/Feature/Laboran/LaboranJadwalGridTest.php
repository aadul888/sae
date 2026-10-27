<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * N. Matriks Grid jadwal laboran (pola Jadwal KBM): view_mode grid/table,
 * tab hari, matriks lokasi x jam pelajaran.
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

    public function test_tab_hari_grid_mengubah_hari_terpilih(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Rabu']));

        $res->assertStatus(200);
        $res->assertSee('KBM Rabu', false);
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
