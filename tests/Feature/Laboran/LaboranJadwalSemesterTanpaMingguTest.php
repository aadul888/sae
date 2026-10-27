<?php

namespace Tests\Feature\Laboran;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Penjadwalan SEMESTER tidak lagi memerlukan input Minggu Ke- / Tanggal Awal /
 * Sampai Minggu Ke-: sekali simpan langsung berlaku untuk 1 semester penuh.
 */
class LaboranJadwalSemesterTanpaMingguTest extends LaboranTestCase
{
    private function kelasReguler(): ?object
    {
        return DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
            })
            ->first();
    }

    public function test_semester_tanpa_minggu_dan_tanggal_menghasilkan_seluruh_semester(): void
    {
        $session = $this->adminSession();
        $rombel  = $this->kelasReguler();
        $this->assertNotNull($rombel, 'Butuh minimal satu rombel reguler untuk pengujian.');

        $guru  = DB::table('gtk')->value('ptk_id');
        $mapel = 'Praktikum Semester Penuh ' . uniqid();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'        => 'Lab Uji Semester ' . uniqid(),
            'jenis_lokasi'          => 'lab',
            'rombel_id'             => $rombel->rombongan_belajar_id,
            'ptk_id'                => $guru,
            'mata_pelajaran'        => $mapel,
            'pemberlakuan'          => 'semester',
            'hari_semester'         => 'Senin',
            'jam_ke_mulai'          => 1,
            'jam_ke_selesai'        => 2,
            'pemberlakuan_semester' => '1',
            'pemberlakuan_ta'       => '2026/2027',
            // Sengaja TIDAK mengirim pemberlakuan_minggu / pemberlakuan_minggu_akhir / tanggal.
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $rows = DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', $mapel)
            ->where('pemberlakuan', 'semester')
            ->orderBy('tanggal')
            ->get();

        // Jumlah hari Senin pada Semester 1 TA 2026/2027 (1 Juli - 31 Desember 2026).
        $expected = 0;
        $cursor   = Carbon::parse('2026-07-01');
        $end      = Carbon::parse('2026-12-31');
        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeek === 1) {
                $expected++;
            }
            $cursor->addDay();
        }

        $this->assertSame($expected, $rows->count(), 'Semester harus menghasilkan sesi sepanjang 1 semester penuh.');
        $this->assertTrue($rows->count() > 4, 'Rentang semester harus jauh lebih panjang dari rentang mingguan.');

        foreach ($rows as $row) {
            $tanggal = Carbon::parse($row->tanggal);
            $this->assertSame(1, $tanggal->dayOfWeek, 'Sesi harus jatuh pada hari Senin.');
            $this->assertTrue(
                $tanggal->between(Carbon::parse('2026-07-01'), Carbon::parse('2026-12-31')),
                'Sesi harus berada di dalam rentang Semester 1 TA 2026/2027.'
            );
        }
    }

    public function test_tombol_tambah_mengikuti_tab_pemberlakuan_aktif(): void
    {
        $session = $this->adminSession();

        $mingguan = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index', [
            'mode_pemberlakuan' => 'mingguan',
        ]));
        $mingguan->assertStatus(200);
        $mingguan->assertSee('data-active-mode="mingguan"', false);

        $semester = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index', [
            'mode_pemberlakuan' => 'semester',
        ]));
        $semester->assertStatus(200);
        $semester->assertSee('data-active-mode="semester"', false);
    }
}
