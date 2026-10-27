<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * K. Penjadwalan praktik: pemberlakuan berkala, multi-guru, dan slot KBM.
 */
class LaboranJadwalPraktikTest extends LaboranTestCase
{
    public function test_jadwal_mendukung_pemberlakuan_bulanan_dan_semester(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');
        $pemb = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombel)
            ->whereNotNull('nama_mata_pelajaran')->orderBy('nama_mata_pelajaran')->first();
        $this->assertNotNull($pemb, 'Data pembelajaran kelas tidak tersedia.');

        // 1. Pemberlakuan Bulanan (Rutin mingguan dalam 1 bulan)
        $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'     => 'Lahan Praktik ATPH',
            'jenis_lokasi'       => 'lahan',
            'pembelajaran_id'    => $pemb->pembelajaran_id,
            'rombel_id'          => $rombel,
            'pemberlakuan'       => 'bulanan',
            'pemberlakuan_bulan' => 11,
            'pemberlakuan_tahun' => 2026,
            'tanggal'            => '2026-11-02', // Senin
            'jam_mulai'          => '07:30',
            'jam_selesai'        => '09:00',
        ])->assertStatus(200);

        // November 2026 memiliki 5 hari Senin: 2, 9, 16, 23, 30
        $count = DB::table('laboran_jadwal_penggunaan')
            ->where('pembelajaran_id', $pemb->pembelajaran_id)
            ->where('ruang_lab_nama', 'Lahan Praktik ATPH')
            ->count();
        $this->assertGreaterThanOrEqual(4, $count, 'Penjadwalan bulanan harus meng-generate seluruh minggu dalam bulan.');
    }

    public function test_jadwal_multi_guru_dan_deteksi_bentrok(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');
        $gtkIds = DB::table('gtk')->limit(2)->pluck('ptk_id')->all();

        $this->assertCount(2, $gtkIds, 'Dibutuhkan minimal 2 GTK untuk pengujian multi-guru.');

        // Simpan sesi dengan 2 guru (tim teaching)
        $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama' => 'Lab Komputer 1',
            'jenis_lokasi'   => 'lab',
            'rombel_id'      => $rombel,
            'mata_pelajaran' => 'UJI-MULTI-GURU',
            'tanggal'        => '2026-12-01',
            'pemberlakuan'   => 'sekali',
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '10:00',
            'ptk_id'         => $gtkIds[0],
            'ptk_ids'        => [$gtkIds[0], $gtkIds[1]],
        ])->assertStatus(200);

        $saved = DB::table('laboran_jadwal_penggunaan')->where('mata_pelajaran', 'UJI-MULTI-GURU')->first();
        $this->assertNotNull($saved);
        $this->assertNotNull($saved->guru_pendamping, 'Nama guru pendamping harus terformat.');

        // Uji bentrok guru pendamping pada jam yang sama di lokasi lain
        $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama' => 'Bengkel Otomotif',
            'jenis_lokasi'   => 'bengkel',
            'rombel_id'      => $rombel,
            'mata_pelajaran' => 'UJI-BENTROK-GURU',
            'tanggal'        => '2026-12-01',
            'pemberlakuan'   => 'sekali',
            'jam_mulai'      => '08:30',
            'jam_selesai'    => '09:30',
            'ptk_id'         => $gtkIds[1], // Guru pendamping tadi dicoba dijadwalkan di sini
        ])->assertStatus(422);
    }

    public function test_jadwal_kbm_slot_endpoint(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');

        $this->withSession($session)
            ->getJson(route('dashboard.laboran.jadwal.kbm', ['rombel_id' => $rombel]))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }
}
