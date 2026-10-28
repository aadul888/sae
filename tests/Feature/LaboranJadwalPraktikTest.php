<?php

namespace Tests\Feature;

use App\Http\Controllers\LaboranJadwalController;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian modul penjadwalan praktik Laboran:
 *  - Penjadwalan guru pada lab, bengkel, LAHAN, dan LAPANGAN.
 *  - Mata pelajaran terisi otomatis dari data kelas (pembelajaran Dapodik).
 *  - Topik / materi praktik bersifat OPSIONAL.
 *  - Laporan per bulan, triwulan, semester, dan tahun (halaman + cetak).
 */
class LaboranJadwalPraktikTest extends TestCase
{
    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
            'ptk_id'       => $admin->ptk_id ?? null,
        ]];
    }

    private function kelasReguler(): object
    {
        $rombel = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('pembelajaran')
                    ->whereColumn('pembelajaran.rombongan_belajar_id', 'rombongan_belajar.rombongan_belajar_id');
            })
            ->orderBy('nama')
            ->first();

        $this->assertNotNull($rombel, 'Data kelas reguler dengan pembelajaran tidak tersedia untuk pengujian.');

        return $rombel;
    }

    /**
     * ID baris yang sudah ada SEBELUM test berjalan.
     *
     * Pengujian memakai database operasional, jadi cleanup tidak boleh
     * mengosongkan tabel: cukup hapus baris yang benar-benar dibuat test ini.
     *
     * @var array<int, int>
     */
    private array $jadwalIdsSebelum = [];

    /** @var int batas id saat setUp (cadangan bila tabel kosong) */
    private int $jadwalMaxIdSebelum = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jadwalIdsSebelum = DB::table('laboran_jadwal_penggunaan')->pluck('id')->all();
        $this->jadwalMaxIdSebelum = (int) DB::table('laboran_jadwal_penggunaan')->max('id');
    }

    protected function tearDown(): void
    {
        // Hanya hapus baris yang dibuat selama test (jangan sentuh data nyata).
        // PENTING: hapus berdasarkan daftar id eksplisit. Bila daftar baris baru
        // kosong, TIDAK ada perintah DELETE yang dijalankan — sehingga tidak
        // mungkin mengosongkan tabel (insiden data nyata terhapus).
        $idBaru = DB::table('laboran_jadwal_penggunaan')
            ->when(!empty($this->jadwalIdsSebelum), fn ($q) => $q->whereNotIn('id', $this->jadwalIdsSebelum))
            ->when(empty($this->jadwalIdsSebelum), fn ($q) => $q->where('id', '>', $this->jadwalMaxIdSebelum))
            ->pluck('id')
            ->all();

        if (!empty($idBaru)) {
            DB::table('laboran_jadwal_penggunaan')->whereIn('id', $idBaru)->delete();
        }

        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Halaman
    // -------------------------------------------------------------------------

    public function test_halaman_jadwal_menampilkan_pilihan_jenis_lokasi_termasuk_lahan_dan_lapangan(): void
    {
        $res = $this->withSession($this->adminSession())->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);
        $res->assertViewIs('dashboard.laboran.jadwal');
        $res->assertViewHas('lokasiByJenis');
        $res->assertViewHas('allRombel');

        $lokasiByJenis = $res->viewData('lokasiByJenis');

        $this->assertArrayHasKey('lab', $lokasiByJenis);
        $this->assertArrayHasKey('bengkel', $lokasiByJenis);
        $this->assertArrayHasKey('lahan', $lokasiByJenis);
        $this->assertArrayHasKey('lapangan', $lokasiByJenis);
    }

    public function test_deteksi_jenis_lokasi_dari_nama_ruang(): void
    {
        $this->assertSame('lab', LaboranJadwalController::detectJenisLokasi('Lab Komputer 1'));
        $this->assertSame('bengkel', LaboranJadwalController::detectJenisLokasi('Bengkel Praktik Otomotif (TKR)'));
        $this->assertSame('lahan', LaboranJadwalController::detectJenisLokasi('Lahan Praktik ATPH'));
        $this->assertSame('lahan', LaboranJadwalController::detectJenisLokasi('Kebun Sekolah'));
        $this->assertSame('lahan', LaboranJadwalController::detectJenisLokasi('Greenhouse / Screen House'));
        $this->assertSame('lapangan', LaboranJadwalController::detectJenisLokasi('Lapangan Upacara & Olahraga'));
        $this->assertSame('lainnya', LaboranJadwalController::detectJenisLokasi('Ruang Rapat Guru'));
    }

    // -------------------------------------------------------------------------
    // Mata pelajaran otomatis dari kelas
    // -------------------------------------------------------------------------

    public function test_endpoint_pembelajaran_mengembalikan_mapel_dari_kelas(): void
    {
        $rombel = $this->kelasReguler();

        $res = $this->withSession($this->adminSession())->getJson(
            route('dashboard.laboran.jadwal.pembelajaran', ['rombel_id' => $rombel->rombongan_belajar_id])
        );

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'success');

        $items = $res->json('pembelajaran');
        $this->assertIsArray($items);
        $this->assertNotEmpty($items, 'Mata pelajaran harus terisi otomatis dari data kelas.');

        $first = $items[0];
        $this->assertArrayHasKey('pembelajaran_id', $first);
        $this->assertArrayHasKey('nama_mata_pelajaran', $first);
        $this->assertNotEmpty($first['nama_mata_pelajaran']);
    }

    public function test_endpoint_pembelajaran_tanpa_rombel_mengembalikan_daftar_kosong(): void
    {
        $res = $this->withSession($this->adminSession())
            ->getJson(route('dashboard.laboran.jadwal.pembelajaran'));

        $res->assertStatus(200);
        $res->assertJsonPath('pembelajaran', []);
    }

    // -------------------------------------------------------------------------
    // Simpan jadwal: mapel otomatis + topik opsional
    // -------------------------------------------------------------------------

    public function test_simpan_jadwal_lahan_dengan_mapel_otomatis_dan_tanpa_topik(): void
    {
        $rombel = $this->kelasReguler();

        $res = $this->withSession($this->adminSession())->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'       => 'Lahan Praktik ATPH',
            'jenis_lokasi'         => 'lahan',
            'rombel_id'            => $rombel->rombongan_belajar_id,
            'mata_pelajaran'       => 'Bahasa Indonesia',
            'topik_praktik'        => '',
            'tanggal'              => now()->addDay()->toDateString(),
            'jam_mulai'            => '08:00',
            'jam_selesai'          => '09:30',
            'alat_bahan_digunakan' => 'Cangkul, bibit',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'success');

        $row = DB::table('laboran_jadwal_penggunaan')->latest('id')->first();

        $this->assertNotNull($row);
        $this->assertSame('Lahan Praktik ATPH', $row->ruang_lab_nama);
        $this->assertSame('lahan', $row->jenis_lokasi);
        $this->assertSame($rombel->rombongan_belajar_id, $row->rombel_id);

        // Mata pelajaran & guru pengampu terisi otomatis dari pembelajaran
        $this->assertSame('Bahasa Indonesia', $row->mata_pelajaran);
        $this->assertNotNull($row->pembelajaran_id, 'pembelajaran_id harus terisi otomatis.');
        $this->assertNotNull($row->ptk_id, 'Guru pengampu harus terisi otomatis dari pembelajaran.');

        // Topik / materi praktik opsional -> boleh NULL
        $this->assertNull($row->topik_praktik);
    }

    public function test_simpan_jadwal_mewajibkan_guru_mapel(): void
    {
        // Modal simpel: tanpa pembelajaran_id, rombel & mapel tidak dikenal — 422.
        $res = $this->withSession($this->adminSession())->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama' => 'Lab Komputer 1',
            'jenis_lokasi'   => 'lab',
            'tanggal'        => now()->toDateString(),
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '09:30',
        ]);

        $res->assertStatus(422);

        // Tanpa rombel & pembelajaran, sistem menolak karena guru tidak dapat
        // ditentukan (kolom ptk_id wajib terisi) — dijamin tidak 500.
        $this->assertNotSame(500, $res->getStatusCode());
    }

    /**
     * Lokasi praktik BOLEH dipakai beberapa sesi pada jam yang sama,
     * termasuk oleh guru lain pada rombel berbeda (permintaan pemilik modul).
     * Yang tetap eksklusif adalah GURU.
     */
    public function test_lokasi_lab_boleh_dipakai_guru_lain_pada_jam_sama(): void
    {
        $rombel = $this->kelasReguler();
        $tanggal = '2026-11-03'; // Selasa

        $payload = [
            'ruang_lab_nama' => 'Lab Komputer 1',
            'jenis_lokasi'   => 'lab',
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'Informatika',
            'tanggal'        => $tanggal,
            'jam_mulai'      => '07:00',
            'jam_selesai'    => '08:30',
        ];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), $payload)
            ->assertStatus(200);

        // Guru BERBEDA pada lokasi & jam yang sama harus DITERIMA.
        $gtkLain = DB::table('gtk')->skip(1)->value('ptk_id');

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($payload, [
                'jam_mulai'   => '08:00',
                'jam_selesai' => '09:00',
                'ptk_id'      => $gtkLain,
            ]))
            ->assertStatus(200);

        $this->assertSame(2, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Lab Komputer 1')
            ->where('tanggal', $tanggal)
            ->count());
    }

    /**
     * Lahan & lapangan adalah area terbuka: beberapa rombel boleh praktik
     * bersamaan pada jam yang sama (mis. olah tanah di petak berbeda).
     */
    public function test_lapangan_dan_lahan_mengizinkan_jadwal_bersamaan(): void
    {
        $rombel = $this->kelasReguler();
        $tanggal = now()->addDays(3)->toDateString();

        $payload = [
            'ruang_lab_nama' => 'Lapangan Praktik Outdoor',
            'jenis_lokasi'   => 'lapangan',
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan',
            'tanggal'        => $tanggal,
            // 07:15 menghindari Upacara (Senin, s/d 07:15) & Pembiasaan (Jumat, s/d 07:10).
            'jam_mulai'      => '07:15',
            'jam_selesai'    => '08:45',
        ];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), $payload)
            ->assertStatus(200);

        // Sesi kedua pada lokasi & jam yang sama harus DITERIMA,
        // dengan guru berbeda (bentrok guru tetap divalidasi terpisah).
        $gtkLain = DB::table('gtk')->skip(1)->value('ptk_id');

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($payload, [
                'jam_mulai'   => '07:30',
                'jam_selesai' => '09:00',
                'ptk_id'      => $gtkLain,
            ]))
            ->assertStatus(200);

        $this->assertSame(2, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Lapangan Praktik Outdoor')
            ->where('tanggal', $tanggal)
            ->count());
    }

    /**
     * Bentrok GURU tetap ditolak meskipun lokasinya lahan/lapangan.
     */
    public function test_bentrok_guru_tetap_ditolak_di_lapangan(): void
    {
        $rombel = $this->kelasReguler();
        // Tanggal tetap (Selasa) agar tidak bergantung tanggal berjalan dan
        // tidak jatuh pada akhir pekan (Sabtu/Minggu tanpa slot KBM).
        $tanggal = '2026-11-03';
        $ptkId = (string) DB::table('gtk')->value('ptk_id');

        $payload = [
            'ruang_lab_nama' => 'Lapangan Praktik Outdoor',
            'jenis_lokasi'   => 'lapangan',
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'Pendidikan Jasmani',
            'tanggal'        => $tanggal,
            'jam_mulai'      => '07:00',
            'jam_selesai'    => '08:30',
            'ptk_id'         => $ptkId,
        ];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), $payload)
            ->assertStatus(200);

        // Guru sama, jam beririsan, lokasi berbeda -> harus 422
        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), array_merge($payload, [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi'   => 'lahan',
                'jam_mulai'      => '08:00',
                'jam_selesai'    => '09:00',
            ]));

        $res->assertStatus(422);
        $this->assertStringContainsString('sudah memiliki jadwal praktik lain', $res->json('message'));
    }

    public function test_ubah_status_tidak_menghapus_topik(): void
    {
        $rombel = $this->kelasReguler();

        $id = DB::table('laboran_jadwal_penggunaan')->insertGetId([
            'ruang_lab_nama'   => 'Lab IPA / Kimia',
            'jenis_lokasi'     => 'lab',
            'ptk_id'           => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'        => $rombel->rombongan_belajar_id,
            'mata_pelajaran'   => 'Projek IPAS',
            'topik_praktik'    => 'Uji pH larutan',
            'tanggal'          => now()->toDateString(),
            'jam_mulai'        => '10:00:00',
            'jam_selesai'      => '11:30:00',
            'status'           => 'dijadwalkan',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $res = $this->withSession($this->adminSession())->putJson(
            route('dashboard.laboran.jadwal.update', $id),
            [
                'form_mode'         => 'status',
                'status'            => 'selesai',
                'laporan_kerusakan' => 'Tabung reaksi pecah 1 buah',
            ]
        );

        $res->assertStatus(200);

        $row = DB::table('laboran_jadwal_penggunaan')->where('id', $id)->first();
        $this->assertSame('selesai', $row->status);
        $this->assertSame('Tabung reaksi pecah 1 buah', $row->laporan_kerusakan);
        $this->assertSame('Uji pH larutan', $row->topik_praktik, 'Topik tidak boleh hilang saat hanya mengubah status.');
    }

    // -------------------------------------------------------------------------
    // Laporan per periode
    // -------------------------------------------------------------------------

    public function test_halaman_laporan_merender_untuk_semua_tipe_periode(): void
    {
        $queries = [
            ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026],
            ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026],
            ['periode' => 'semester', 'semester' => '1', 'tahun_ajaran' => '2026/2027'],
            ['periode' => 'tahun', 'tahun_ajaran' => '2026/2027'],
        ];

        foreach ($queries as $query) {
            $res = $this->withSession($this->adminSession())
                ->get(route('dashboard.laboran.laporan.index', $query));

            $res->assertStatus(200);
            $res->assertViewIs('dashboard.laboran.laporan');
            $res->assertViewHas('range');
            $res->assertViewHas('rekapJenis');
            $res->assertViewHas('rekapLokasi');
        }
    }

    public function test_rentang_periode_bulan_triwulan_semester_tahun_benar(): void
    {
        $controller = new LaboranJadwalController();
        $method = new \ReflectionMethod($controller, 'resolveDateRange');
        $method->setAccessible(true);

        $bulan = $method->invoke($controller, 'bulan', 10, 2026, 4, '1', '2026/2027');
        $this->assertSame('2026-10-01', $bulan['start']);
        $this->assertSame('2026-10-31', $bulan['end']);

        $tw1 = $method->invoke($controller, 'triwulan', 10, 2026, 1, '1', '2026/2027');
        $this->assertSame('2026-01-01', $tw1['start']);
        $this->assertSame('2026-03-31', $tw1['end']);

        $tw4 = $method->invoke($controller, 'triwulan', 10, 2026, 4, '1', '2026/2027');
        $this->assertSame('2026-10-01', $tw4['start']);
        $this->assertSame('2026-12-31', $tw4['end']);

        $ganjil = $method->invoke($controller, 'semester', 10, 2026, 4, '1', '2026/2027');
        $this->assertSame('2026-07-01', $ganjil['start']);
        $this->assertSame('2026-12-31', $ganjil['end']);

        $genap = $method->invoke($controller, 'semester', 10, 2026, 4, '2', '2026/2027');
        $this->assertSame('2027-01-01', $genap['start']);
        $this->assertSame('2027-06-30', $genap['end']);

        $tahun = $method->invoke($controller, 'tahun', 10, 2026, 4, '1', '2026/2027');
        $this->assertSame('2026-07-01', $tahun['start']);
        $this->assertSame('2027-06-30', $tahun['end']);
    }

    public function test_cetak_laporan_menghasilkan_dokumen_untuk_setiap_periode(): void
    {
        $queries = [
            ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026],
            ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026],
            ['periode' => 'semester', 'semester' => '1', 'tahun_ajaran' => '2026/2027'],
            ['periode' => 'tahun', 'tahun_ajaran' => '2026/2027'],
        ];

        foreach ($queries as $query) {
            $res = $this->withSession($this->adminSession())
                ->get(route('dashboard.laboran.laporan.cetak', $query));

            $res->assertStatus(200);
            $res->assertSee('LAPORAN PENGGUNAAN LOKASI PRAKTIK', false);
            $res->assertSee('Rincian Seluruh Sesi Praktik', false);
        }
    }

    public function test_laporan_hanya_menghitung_data_dalam_periode(): void
    {
        $rombel = $this->kelasReguler();
        $ptkId  = (string) DB::table('gtk')->value('ptk_id');

        $urlBulan = route('dashboard.laboran.laporan.index', ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026]);
        $urlTw    = route('dashboard.laboran.laporan.index', ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026]);

        // Baseline: pengujian berjalan pada database operasional, jadi patokan
        // adalah SELISIH sebelum/sesudah insert (bukan angka mutlak).
        $baseBulan = $this->withSession($this->adminSession())->get($urlBulan);
        $baseSesi  = $baseBulan->viewData('totalSesi');
        $baseSelesai = $baseBulan->viewData('totalSelesai');
        $baseLokasi  = $baseBulan->viewData('lokasiUnik');
        $baseTw    = $this->withSession($this->adminSession())->get($urlTw)->viewData('totalSesi');
        $kebunAda  = DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', 'Kebun Sekolah')
            ->whereBetween('tanggal', ['2026-10-01', '2026-10-31'])
            ->exists();

        // Sesi di dalam periode (Oktober 2026)
        DB::table('laboran_jadwal_penggunaan')->insert([
            [
                'ruang_lab_nama' => 'Kebun Sekolah',
                'jenis_lokasi'   => 'lahan',
                'ptk_id'         => $ptkId,
                'rombel_id'      => $rombel->rombongan_belajar_id,
                'mata_pelajaran' => 'Agribisnis Tanaman Pangan dan Hortikultura',
                'topik_praktik'  => null,
                'tanggal'        => '2026-10-15',
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'selesai',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'ruang_lab_nama' => 'Kebun Sekolah',
                'jenis_lokasi'   => 'lahan',
                'ptk_id'         => $ptkId,
                'rombel_id'      => $rombel->rombongan_belajar_id,
                'mata_pelajaran' => 'Agribisnis Tanaman Pangan dan Hortikultura',
                'topik_praktik'  => 'Pemupukan',
                'tanggal'        => '2026-11-10',
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        $res = $this->withSession($this->adminSession())->get($urlBulan);

        $res->assertStatus(200);
        $this->assertSame($baseSesi + 1, $res->viewData('totalSesi'), 'Hanya sesi Oktober yang dihitung pada periode bulanan.');
        $this->assertSame($baseSelesai + 1, $res->viewData('totalSelesai'));
        $this->assertSame($baseLokasi + ($kebunAda ? 0 : 1), $res->viewData('lokasiUnik'));

        // Triwulan IV (Okt-Des) memuat keduanya
        $tw = $this->withSession($this->adminSession())->get($urlTw);

        $this->assertSame($baseTw + 2, $tw->viewData('totalSesi'));
    }
}
