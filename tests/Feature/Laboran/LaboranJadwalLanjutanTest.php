<?php

namespace Tests\Feature\Laboran;

use App\Services\LaboranJadwalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * L. Penjadwalan lanjutan: pemberlakuan mingguan/bulanan, izin lokasi
 *    bersamaan untuk lahan & lapangan, serta jadwal per guru.
 */
class LaboranJadwalLanjutanTest extends LaboranTestCase
{
    private function kelasReguler(): ?object
    {
        return DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
            })
            ->first();
    }

    // =========================================================================
    // PEMBERLAKUAN MINGGUAN / BULANAN
    // =========================================================================

    public function test_pemberlakuan_mingguan_multi_hari_menghasilkan_sesi_berulang(): void
    {
        $rombel = $this->kelasReguler();
        $this->assertNotNull($rombel, 'Rombel reguler tidak tersedia.');

        // 2026-11-02 = Senin. Pilih Senin + Rabu, rentang 2 minggu.
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi'   => 'lahan',
                'rombel_id'      => $rombel->rombongan_belajar_id,
                'mata_pelajaran' => 'UJI-MINGGUAN-MULTI',
                'pemberlakuan'   => 'mingguan',
                'hari'           => ['Senin', 'Rabu'],
                'tanggal'        => '2026-11-02',
                'tanggal_akhir'  => '2026-11-15',
                'jam_mulai'      => '07:00',
                'jam_selesai'    => '08:30',
            ])
            ->assertStatus(200);

        $rows = DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', 'UJI-MINGGUAN-MULTI')
            ->orderBy('tanggal')
            ->pluck('tanggal')
            ->all();

        // Senin 2 & 9, Rabu 4 & 11 November 2026 = 4 sesi
        $this->assertCount(4, $rows, 'Mingguan multi-hari harus menghasilkan 4 sesi.');
        $this->assertContains('2026-11-02', $rows);
        $this->assertContains('2026-11-04', $rows);
        $this->assertContains('2026-11-09', $rows);
        $this->assertContains('2026-11-11', $rows);
    }

    public function test_pemberlakuan_bulanan_menghasilkan_seluruh_bulan(): void
    {
        $rombel = $this->kelasReguler();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi'   => 'lahan',
                'rombel_id'      => $rombel->rombongan_belajar_id,
                'mata_pelajaran' => 'UJI-BULANAN-PENUH',
                'pemberlakuan'   => 'bulanan',
                'hari'           => ['Senin'],
                'tanggal'        => '2026-11-02',
                'pemberlakuan_bulan' => 11,
                'pemberlakuan_tahun' => 2026,
                'jam_mulai'      => '07:00',
                'jam_selesai'    => '08:30',
            ])
            ->assertStatus(200);

        // November 2026 punya 5 hari Senin: 2, 9, 16, 23, 30
        $this->assertSame(5, DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', 'UJI-BULANAN-PENUH')
            ->count());
    }

    public function test_generate_dates_mingguan_tanpa_tanggal_akhir_mencakup_satu_bulan(): void
    {
        $svc = app(LaboranJadwalService::class);

        $dates = $svc->generateDates(
            'mingguan', '2026-11-02', null, null, null, null,
            ['Senin'], null
        );

        $this->assertCount(5, $dates, 'Tanpa tanggal akhir, mingguan mencakup 1 bulan penuh.');
        $this->assertSame('2026-11-02', $dates[0]);
        $this->assertSame('2026-11-30', end($dates));
    }

    public function test_generate_dates_menurunkan_hari_dari_tanggal_bila_kosong(): void
    {
        $svc = app(LaboranJadwalService::class);

        // 2026-11-05 = Kamis
        $dates = $svc->generateDates('mingguan', '2026-11-05', null, null, null, null, [], '2026-11-19');

        $this->assertCount(3, $dates, 'Kamis 5, 12, 19 November = 3 sesi.');
        foreach ($dates as $d) {
            $this->assertSame(Carbon::THURSDAY, Carbon::parse($d)->dayOfWeek);
        }
    }

    // =========================================================================
    // LOKASI BERSAMAAN
    // =========================================================================

    public function test_lahan_mengizinkan_dua_sesi_pada_jam_sama(): void
    {
        $rombel = $this->kelasReguler();
        $tanggal = now()->addDays(20)->toDateString();

        $guruA = DB::table('gtk')->value('ptk_id');
        $guruB = DB::table('gtk')->skip(1)->value('ptk_id');

        $base = [
            'ruang_lab_nama' => 'Lahan Praktik ATPH',
            'jenis_lokasi'   => 'lahan',
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'Praktik Olah Tanah',
            'tanggal'        => $tanggal,
            'jam_mulai'      => '07:00',
            'jam_selesai'    => '09:00',
        ];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($base, ['ptk_id' => $guruA]))
            ->assertStatus(200);

        // Guru berbeda, lokasi & jam sama -> DITERIMA
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($base, ['ptk_id' => $guruB]))
            ->assertStatus(200);

        $this->assertSame(2, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Lahan Praktik ATPH')
            ->where('tanggal', $tanggal)
            ->count());
    }

    public function test_lab_tetap_eksklusif_satu_sesi_per_jam(): void
    {
        $rombel = $this->kelasReguler();
        $tanggal = now()->addDays(21)->toDateString();

        $base = [
            'ruang_lab_nama' => 'Lab Komputer 1',
            'jenis_lokasi'   => 'lab',
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'Informatika',
            'tanggal'        => $tanggal,
            'jam_mulai'      => '07:00',
            'jam_selesai'    => '09:00',
        ];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), $base)
            ->assertStatus(200);

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($base, [
                'jam_mulai'   => '08:00',
                'jam_selesai' => '10:00',
            ]))
            ->assertStatus(422);
    }

    // =========================================================================
    // JADWAL PER GURU (kini diakses langsung via cetak dari halaman jadwal)
    // =========================================================================

    public function test_cetak_jadwal_per_guru_menghasilkan_dokumen(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.cetak', [
                'mode' => 'mingguan', 'tanggal' => '2026-11-02',
            ]));

        $res->assertStatus(200);
        $res->assertSee('JADWAL PRAKTIK PER GURU', false);
    }

    public function test_cetak_jadwal_mode_semester_menghasilkan_dokumen(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.cetak', [
                'mode' => 'semester', 'semester' => '1', 'tahun' => 2026,
            ]));

        $res->assertStatus(200);
        $res->assertSee('Semester', false);
    }

    public function test_pemberlakuan_jadwal_dapat_diaktifkan_per_mode(): void
    {
        $session = $this->adminSession();

        // Aktifkan mode semester
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode'   => 'semester',
                'status' => 'aktif',
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $row = DB::table('laboran_jadwal_pengaturan')->where('id', 1)->first();
        $this->assertSame('semester', $row->mode_pemberlakuan);
        $this->assertSame('aktif', $row->status);
        $this->assertSame('semester', \App\Services\LaboranJadwalService::getModeDiberlakukan());

        // Kembali ke draft
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode'   => 'semester',
                'status' => 'draft',
            ])
            ->assertStatus(200);

        $this->assertNull(\App\Services\LaboranJadwalService::getModeDiberlakukan());
    }

    public function test_pemberlakuan_mode_eksklusif_mingguan_mengganti_semester(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'semester', 'status' => 'aktif',
            ])->assertStatus(200);

        // Aktifkan mingguan -> hanya satu mode yang diberlakukan
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'mingguan', 'status' => 'aktif',
            ])->assertStatus(200);

        $row = DB::table('laboran_jadwal_pengaturan')->where('id', 1)->first();
        $this->assertSame('mingguan', $row->mode_pemberlakuan);
        $this->assertSame('mingguan', \App\Services\LaboranJadwalService::getModeDiberlakukan());

        // Matikan
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'mingguan', 'status' => 'draft',
            ])->assertStatus(200);
    }

}

