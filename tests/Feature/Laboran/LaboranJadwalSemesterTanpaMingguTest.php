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

        // Mengisi 1 slot jam menghasilkan 1 sesi sesuai jam yang dipilih
        $this->assertSame(1, $rows->count(), 'Mengisi 1 slot jam harus menghasilkan 1 sesi sesuai jam yang dipilih.');
        $row = $rows->first();
        $this->assertNotEmpty($row->jam_mulai);
        $this->assertNotEmpty($row->jam_selesai);
    }

    public function test_header_tanpa_tombol_tambah_dan_tab_menentukan_mode_aktif(): void
    {
        $session = $this->adminSession();

        foreach (['mingguan', 'semester'] as $mode) {
            $res = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index', [
                'mode_pemberlakuan' => $mode,
            ]));
            $res->assertStatus(200);

            $html = $res->getContent();

            // Tombol tambah (+) di header dihapus: penjadwalan hanya lewat slot matriks grid.
            $res->assertDontSee('id="btnOpenModalJadwal"', false);

            // Tepat satu tab pemberlakuan yang aktif (penanda mode untuk JS pembuka modal).
            $this->assertSame(1, substr_count($html, 'data-is-active="1"'), 'Hanya satu tab pemberlakuan yang boleh aktif.');
            $this->assertStringContainsString('data-mode="' . $mode . '"', $html);
        }
    }

    public function test_semester_disimpan_pada_hari_yang_dipilih(): void
    {
        $session = $this->adminSession();
        $rombel  = $this->kelasReguler();
        $this->assertNotNull($rombel, 'Butuh minimal satu rombel reguler untuk pengujian.');

        $guru  = DB::table('gtk')->value('ptk_id');
        $mapel = 'Praktikum Hari Pilihan ' . uniqid();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'        => 'Lab Uji Hari ' . uniqid(),
            'jenis_lokasi'          => 'lab',
            'rombel_id'             => $rombel->rombongan_belajar_id,
            'ptk_id'                => $guru,
            'mata_pelajaran'        => $mapel,
            'pemberlakuan'          => 'semester',
            'hari'                  => ['Sabtu'],
            'jam_ke_mulai'          => 1,
            'jam_ke_selesai'        => 1,
            'pemberlakuan_semester' => '1',
            'pemberlakuan_ta'       => '2026/2027',
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $tanggal = DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', $mapel)
            ->value('tanggal');

        $this->assertNotNull($tanggal);
        $this->assertSame(
            Carbon::SATURDAY,
            Carbon::parse($tanggal)->dayOfWeek,
            'Sesi harus tersimpan pada hari yang dipilih (Sabtu), bukan hari lain.'
        );
    }
}
