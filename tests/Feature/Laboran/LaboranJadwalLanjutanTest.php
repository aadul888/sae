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

        // Modal simpel: guru & mapel diambil dari data pembelajaran kelas.
        $pemb = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->whereNotNull('nama_mata_pelajaran')->orderBy('nama_mata_pelajaran')->first();
        $this->assertNotNull($pemb, 'Data pembelajaran kelas tidak tersedia.');

        // 2026-11-02 = Senin. Pilih Senin + Rabu, rentang 2 minggu.
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama'  => 'Lahan Praktik ATPH',
                'jenis_lokasi'    => 'lahan',
                'rombel_id'       => $rombel->rombongan_belajar_id,
                'pemberlakuan'    => 'mingguan',
                'hari'            => ['Senin', 'Rabu'],
                'tanggal'         => '2026-11-02',
                'tanggal_akhir'   => '2026-11-15',
                'jam_mulai'       => '07:15',
                'jam_selesai'     => '08:45',
            ])
            ->assertStatus(200);

        $rows = DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Lahan Praktik ATPH')
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

        $pemb = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->whereNotNull('nama_mata_pelajaran')->orderBy('nama_mata_pelajaran')->first();
        $this->assertNotNull($pemb, 'Data pembelajaran kelas tidak tersedia.');

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama'  => 'Lahan Praktik ATPH',
                'jenis_lokasi'    => 'lahan',
                'rombel_id'       => $rombel->rombongan_belajar_id,
                'pemberlakuan'    => 'bulanan',
                'hari'            => ['Senin'],
                'tanggal'         => '2026-11-02',
                'pemberlakuan_bulan' => 11,
                'pemberlakuan_tahun' => 2026,
                // 07:15 menghindari Upacara Senin (s/d 07:15).
                'jam_mulai'       => '07:15',
                'jam_selesai'     => '08:45',
            ])
            ->assertStatus(200);

        // November 2026 punya 5 hari Senin: 2, 9, 16, 23, 30
        $this->assertSame(5, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Lahan Praktik ATPH')
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
            // 07:15 menghindari rutinitas pagi (Upacara Senin / Pembiasaan Jumat).
            'jam_mulai'      => '07:15',
            'jam_selesai'    => '09:15',
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

    public function test_tab_pemberlakuan_mengubah_mode_aktif(): void
    {
        $session = $this->adminSession();
        $ptkId = $session['user']['ptk_id'] ?? null;

        // Klik tab Semester -> mode aktif laboran ini berubah
        $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'semester']))
            ->assertStatus(200);

        $this->assertSame('semester', $this->modeMilik($ptkId));

        // Klik tab Mingguan -> kembali
        $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'mingguan']))
            ->assertStatus(200);

        $this->assertSame('mingguan', $this->modeMilik($ptkId));

        // Nilai tidak valid diabaikan
        $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'bulanan']))
            ->assertStatus(200);

        $this->assertSame('mingguan', $this->modeMilik($ptkId));
    }

    public function test_pemberlakuan_jadwal_dapat_diaktifkan_per_mode(): void
    {
        $session = $this->adminSession();
        $ptkId = $session['user']['ptk_id'] ?? null;

        // Aktifkan mode semester
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode'   => 'semester',
                'status' => 'aktif',
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame('semester', $this->modeMilik($ptkId));
        $this->assertSame('aktif', $this->statusMilik($ptkId));
        $this->assertSame('semester', \App\Services\LaboranJadwalService::getModeDiberlakukan($ptkId));

        // Kembali ke draft
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode'   => 'semester',
                'status' => 'draft',
            ])
            ->assertStatus(200);

        $this->assertNull(\App\Services\LaboranJadwalService::getModeDiberlakukan($ptkId));
    }

    public function test_pemberlakuan_mode_eksklusif_mingguan_mengganti_semester(): void
    {
        $session = $this->adminSession();
        $ptkId = $session['user']['ptk_id'] ?? null;

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'semester', 'status' => 'aktif',
            ])->assertStatus(200);

        // Aktifkan mingguan -> hanya satu mode yang diberlakukan
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'mingguan', 'status' => 'aktif',
            ])->assertStatus(200);

        $this->assertSame('mingguan', $this->modeMilik($ptkId));
        $this->assertSame('mingguan', \App\Services\LaboranJadwalService::getModeDiberlakukan($ptkId));

        // Matikan
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.toggle-pemberlakuan'), [
                'mode' => 'mingguan', 'status' => 'draft',
            ])->assertStatus(200);
    }

    /**
     * Mode pemberlakuan milik satu laboran (baris ptk_id), fallback baris global.
     */
    private function modeMilik(?string $ptkId): ?string
    {
        return \App\Services\LaboranJadwalService::getPengaturanJadwal($ptkId)->mode_pemberlakuan;
    }

    /** Status pemberlakuan milik satu laboran. */
    private function statusMilik(?string $ptkId): ?string
    {
        return \App\Services\LaboranJadwalService::getPengaturanJadwal($ptkId)->status;
    }

    public function test_pemberlakuan_ketat_hanya_menampilkan_jadwal_mode_aktif(): void
    {
        $session = $this->adminSession();
        $rombel = $this->kelasReguler();
        $guru = DB::table('gtk')->value('ptk_id');

        // 1. Buat 1 jadwal mingguan
        DB::table('laboran_jadwal_penggunaan')->insert([
            'ruang_lab_nama'   => 'Lab Komputer 1',
            'jenis_lokasi'     => 'lab',
            'rombel_id'        => $rombel->rombongan_belajar_id,
            'ptk_id'           => $guru,
            'mata_pelajaran'   => 'Mapel Khusus Mingguan',
            'tanggal'          => '2026-11-02',
            'pemberlakuan'     => 'mingguan',
            'jam_mulai'        => '08:00',
            'jam_selesai'      => '09:30',
            'status'           => 'dijadwalkan',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // 2. Buat 1 jadwal semester
        DB::table('laboran_jadwal_penggunaan')->insert([
            'ruang_lab_nama'   => 'Bengkel Mesin 1',
            'jenis_lokasi'     => 'bengkel',
            'rombel_id'        => $rombel->rombongan_belajar_id,
            'ptk_id'           => $guru,
            'mata_pelajaran'   => 'Mapel Khusus Semester',
            'tanggal'          => '2026-11-02',
            'pemberlakuan'     => 'semester',
            'jam_mulai'        => '10:00',
            'jam_selesai'      => '11:30',
            'status'           => 'dijadwalkan',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Pada mode Mingguan: hanya mapel mingguan yang muncul, tidak ada semester
        $resMingguan = $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'mingguan', 'view_mode' => 'table']));
        $resMingguan->assertStatus(200);
        $resMingguan->assertSee('Mapel Khusus Mingguan');
        $resMingguan->assertDontSee('Mapel Khusus Semester');

        // Pada mode Semester: hanya mapel semester yang muncul, tidak ada mingguan
        $resSemester = $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => 'semester', 'view_mode' => 'table']));
        $resSemester->assertStatus(200);
        $resSemester->assertSee('Mapel Khusus Semester');
        $resSemester->assertDontSee('Mapel Khusus Mingguan');
    }

    public function test_modal_input_jadwal_semester_dengan_minggu_dan_tanggal_awal(): void
    {
        $session = $this->adminSession();
        $rombel = $this->kelasReguler();
        $guru = DB::table('gtk')->value('ptk_id');

        $payload = [
            'ruang_lab_nama'            => 'Lab Kimia Terpadu',
            'jenis_lokasi'              => 'lab',
            'rombel_id'                 => $rombel->rombongan_belajar_id,
            'ptk_id'                    => $guru,
            'mata_pelajaran'            => 'Praktikum Kimia Semester',
            'pemberlakuan'              => 'semester',
            'hari_semester'             => 'Senin',
            'jam_ke_mulai'              => 2,
            'jam_ke_selesai'            => 3,
            'pemberlakuan_semester'     => '1',
            'pemberlakuan_ta'           => '2026/2027',
            'pemberlakuan_minggu'       => '1',
            'tanggal'                   => '2026-11-02', // Tanggal awal (Senin)
            'pemberlakuan_minggu_akhir' => '4',          // 4 minggu
        ];

        $res = $this->withSession($session)
            ->postJson(route('dashboard.laboran.jadwal.store'), $payload);

        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        // Harus terbuat 4 sesi berkala untuk minggu 1 s.d. 4
        $count = DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', 'Praktikum Kimia Semester')
            ->where('pemberlakuan', 'semester')
            ->count();

        $this->assertSame(4, $count);
    }
}

