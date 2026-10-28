<?php

namespace Tests\Feature\Laboran;

use App\Services\LaboranDokumenService;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * N. DOKUMEN CETAK A4 modul Laboratorium.
 *
 * Memastikan setiap halaman dari Data Peserta Didik dan seterusnya dapat
 * dicetak sebagai dokumen A4 standar, tersedia dalam versi RINGKASAN dan
 * LENGKAP, memuat nama Kepala Program Keahlian (Kaprog), serta setiap cetakan
 * tercatat pada `laboran_log_cetak` + otomatis masuk `laboran_arsip`.
 */
class LaboranCetakDokumenTest extends LaboranTestCase
{
    /**
     * Seluruh endpoint cetak yang harus tersedia (nama route => jenis dokumen).
     *
     * @return array<string, string>
     */
    private function endpoints(): array
    {
        return [
            'dashboard.laboran.dashboard.cetak'      => 'dashboard',
            'dashboard.laboran.peserta-didik.cetak'  => 'peserta-didik',
            'dashboard.laboran.kebutuhan.cetak'      => 'kebutuhan',
            'dashboard.laboran.perawatan.cetak'      => 'perawatan',
            'dashboard.laboran.pemeriksaan.cetak'    => 'pemeriksaan',
            'dashboard.laboran.pengadaan.cetak'      => 'pengadaan',
            'dashboard.laboran.peminjaman-alat.cetak' => 'peminjaman',
            'dashboard.laboran.kerusakan.cetak'      => 'kerusakan',
            'dashboard.laboran.arsip.cetak'          => 'arsip',
            'dashboard.laboran.jurnal.cetak'         => 'jurnal',
            'dashboard.laboran.kebersihan.cetak'     => 'kebersihan',
            'dashboard.laboran.inventaris.cetak'     => 'inventaris',
            'dashboard.laboran.bhp.cetak'            => 'bhp',
            'dashboard.laboran.laporan.cetak'        => 'laporan-penggunaan',
            'dashboard.laboran.laporan-lengkap.cetak' => 'laporan-lengkap',
            'dashboard.laboran.jadwal.cetak'         => 'jadwal-per-guru',
        ];
    }

    public function test_semua_halaman_cetak_merender_a4_standar(): void
    {
        $session = $this->adminSession();

        foreach ($this->endpoints() as $route => $jenis) {
            if ($route === 'dashboard.laboran.kerusakan.cetak') {
                continue; // butuh {id}; diuji terpisah di bawah.
            }

            $res = $this->withSession($session)->get(route($route));
            $res->assertStatus(200);
            $body = $res->getContent();

            $this->assertMatchesRegularExpression(
                '/@page\s*\{[^}]*size:\s*A4\s+(portrait|landscape)/i',
                $body,
                "Dokumen {$jenis} tidak mendeklarasikan kertas A4."
            );
            $this->assertStringContainsString(
                'Kepala Program Keahlian',
                $body,
                "Dokumen {$jenis} tidak memuat kolom tanda tangan Kepala Program Keahlian."
            );

            // Tanda tangan hanya 3 pihak: Laboran, Kaprog, Kepala Sekolah.
            // Kolom Kepala Laboratorium dihapus dari seluruh dokumen.
            $this->assertStringNotContainsString(
                'Kepala Laboratorium',
                $body,
                "Dokumen {$jenis} masih memuat kolom tanda tangan Kepala Laboratorium."
            );

            // Label format/versi pada toolbar & footer juga dihapus.
            $this->assertStringNotContainsString('FORMAT: A4', $body, "Dokumen {$jenis} masih memuat label FORMAT.");
            $this->assertStringNotContainsString('Format: Kertas A4', $body, "Dokumen {$jenis} masih memuat label format footer.");
            $this->assertStringNotContainsString('VERSI:', $body, "Dokumen {$jenis} masih memuat label VERSI.");

            // Urutan tanda tangan: Laboran → Kaprog → Kepala Sekolah.
            $posLaboran = strpos($body, 'Petugas Laboran');
            $posKaprog  = strpos($body, 'Kepala Program Keahlian');
            $posKepsek  = strpos($body, 'Kepala Satuan Pendidikan');

            $this->assertNotFalse($posLaboran, "Dokumen {$jenis} tidak memuat blok tanda tangan Laboran.");
            $this->assertNotFalse($posKepsek, "Dokumen {$jenis} tidak memuat blok tanda tangan Kepala Sekolah.");
            $this->assertTrue(
                $posLaboran < $posKaprog && $posKaprog < $posKepsek,
                "Urutan tanda tangan dokumen {$jenis} harus Laboran → Kaprog → Kepala Sekolah."
            );
        }
    }

    public function test_mode_ringkasan_dan_lengkap_tersedia(): void
    {
        $session = $this->adminSession();

        foreach (['ringkasan', 'lengkap'] as $mode) {
            $res = $this->withSession($session)
                ->get(route('dashboard.laboran.peserta-didik.cetak', ['mode' => $mode]));
            $res->assertStatus(200);

            // Label "FORMAT: A4 ... • VERSI: ..." sengaja dihilangkan dari
            // dokumen. Mode tetap harus terlihat dari isi dokumen, bukan label.
            $res->assertDontSee('FORMAT: A4', false);
            $res->assertDontSee('VERSI:', false);
        }

        $ringkas = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.cetak', ['mode' => 'ringkasan']));
        $ringkas->assertSee('Rekapitulasi Peserta Didik per Kelas', false);

        $lengkap = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.cetak', ['mode' => 'lengkap']));
        $lengkap->assertSee('Daftar Rinci Peserta Didik', false);
    }

    public function test_orientasi_landscape_dihormati(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.peminjaman-alat.cetak', ['orientasi' => 'landscape']));

        $res->assertStatus(200);
        $this->assertMatchesRegularExpression('/size:\s*A4\s+landscape/i', $res->getContent());
    }

    public function test_kerusakan_cetak_per_berita_acara(): void
    {
        $id = DB::table('laboran_berita_acara_kerusakan')->insertGetId([
            'jurusan_id'        => self::JUR_ATPH,
            'nomor_ba'          => 'BAK/CETAK/' . uniqid(),
            'nama_alat'         => 'Alat Cetak Uji',
            'tanggal_kejadian'  => now()->toDateString(),
            'tanggal_ba'        => now()->toDateString(),
            'tingkat_kerusakan' => 'ringan',
            'jumlah_rusak'      => 1,
            'satuan'            => 'unit',
            'status'            => 'diajukan',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kerusakan.cetak', ['id' => $id]));

        $res->assertStatus(200);
        $this->assertMatchesRegularExpression('/size:\s*A4/i', $res->getContent());
        $res->assertSee('Kepala Program Keahlian', false);
    }

    public function test_cetakan_tercatat_di_log_dan_arsip(): void
    {
        $this->assertTrue(Table::exists('laboran_log_cetak'), 'Tabel laboran_log_cetak belum dimigrasi.');

        $beforeLog   = DB::table('laboran_log_cetak')->count();
        $beforeArsip = DB::table('laboran_arsip')->count();

        $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kebutuhan.cetak', ['mode' => 'lengkap']))
            ->assertStatus(200);

        $this->assertSame($beforeLog + 1, DB::table('laboran_log_cetak')->count(),
            'Setiap dokumen cetak harus tercatat di laboran_log_cetak.');

        $log = DB::table('laboran_log_cetak')->latest('id')->first();
        $this->assertSame('kebutuhan', $log->jenis_dokumen);
        $this->assertSame('lengkap', $log->mode);
        $this->assertStringStartsWith('CET/', $log->nomor_cetak);
        $this->assertNotNull($log->arsip_id, 'Log cetak harus tertaut ke arsip digital.');

        $this->assertSame($beforeArsip + 1, DB::table('laboran_arsip')->count(),
            'Dokumen cetak harus otomatis didaftarkan ke laboran_arsip.');

        $arsip = DB::table('laboran_arsip')->where('id', $log->arsip_id)->first();
        $this->assertNotNull($arsip);
        $this->assertSame('laboran_log_cetak', $arsip->sumber_tabel);
        $this->assertSame((int) $log->id, (int) $arsip->sumber_id);
    }

    public function test_cetakan_berulang_tidak_menggandakan_log(): void
    {
        $session = $this->adminSession();
        $route   = route('dashboard.laboran.arsip.cetak');

        $before = DB::table('laboran_log_cetak')->count();

        $this->withSession($session)->get($route)->assertStatus(200);
        $this->withSession($session)->get($route)->assertStatus(200);

        // Idempoten dalam jendela 10 detik untuk tanda tangan cetak yang sama.
        $this->assertSame($before + 1, DB::table('laboran_log_cetak')->count());
    }

    public function test_riwayat_cetak_tampil_di_menu_arsip(): void
    {
        $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.peserta-didik.cetak'))
            ->assertStatus(200);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.arsip.index', ['tab' => 'riwayat']));

        $res->assertStatus(200);
        $res->assertSee('Riwayat Cetak Dokumen', false);
        $res->assertSee('Nomor Cetak', false);
    }

    public function test_tombol_cetak_tersedia_di_halaman_indeks(): void
    {
        $session = $this->adminSession();

        $peta = [
            'dashboard.laboran.dashboard'       => 'dashboard.laboran.dashboard.cetak',
            'dashboard.laboran.peserta-didik.index' => 'dashboard.laboran.peserta-didik.cetak',
            'dashboard.laboran.kebutuhan.index' => 'dashboard.laboran.kebutuhan.cetak',
            'dashboard.laboran.perawatan.index' => 'dashboard.laboran.perawatan.cetak',
            'dashboard.laboran.pemeriksaan.index' => 'dashboard.laboran.pemeriksaan.cetak',
            'dashboard.laboran.pengadaan.index' => 'dashboard.laboran.pengadaan.cetak',
            'dashboard.laboran.peminjaman-alat.index' => 'dashboard.laboran.peminjaman-alat.cetak',
            'dashboard.laboran.arsip.index'     => 'dashboard.laboran.arsip.cetak',
        ];

        foreach ($peta as $halaman => $cetak) {
            $res = $this->withSession($session)->get(route($halaman));
            $res->assertStatus(200);
            $res->assertSee(route($cetak), false);
        }
    }

    public function test_cetak_ulang_dari_riwayat_mengarah_ke_dokumen_asli(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->get(route('dashboard.laboran.kebutuhan.cetak', ['mode' => 'lengkap']))
            ->assertStatus(200);

        $log = DB::table('laboran_log_cetak')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('kebutuhan', $log->jenis_dokumen);

        // Cetak ulang mengarah ke halaman cetak dokumen aslinya.
        $redir = $this->withSession($session)
            ->get(route('dashboard.laboran.arsip.cetak-ulang', $log->id));

        $redir->assertRedirect();
        $target = $redir->headers->get('Location');
        $this->assertStringContainsString('/dashboard/laboran/kebutuhan/cetak', $target);
        $this->assertStringContainsString('catat=0', $target);

        // Membuka halaman cetak ulang TIDAK menambah log baru.
        $before = DB::table('laboran_log_cetak')->count();
        $this->withSession($session)->get($target)->assertStatus(200);
        $this->assertSame($before, DB::table('laboran_log_cetak')->count(),
            'Cetak ulang tidak boleh menggandakan riwayat cetak.');
    }

    public function test_tombol_cetak_ulang_tampil_di_tab_riwayat(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.cetak'))
            ->assertStatus(200);

        $log = DB::table('laboran_log_cetak')->latest('id')->first();
        $this->assertNotNull($log);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.arsip.index', ['tab' => 'riwayat']));

        $res->assertStatus(200);
        $res->assertSee(route('dashboard.laboran.arsip.cetak-ulang', $log->id), false);
    }

    public function test_tab_default_arsip_adalah_peta_ordner(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.arsip.index'));

        $res->assertStatus(200);
        $res->assertSee('Peta Ordner Arsip', false);
    }

    /**
     * Tab Riwayat Cetak memakai datatable baku: toolbar filter/pencarian,
     * tabel responsif, dan paginasi — semuanya server-side.
     */
    public function test_riwayat_cetak_memakai_datatable_baku(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)->get(route('dashboard.laboran.kebutuhan.cetak'))->assertStatus(200);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.arsip.index', ['tab' => 'riwayat']));

        $res->assertStatus(200);

        // Struktur datatable baku.
        $res->assertSee('id="tableDataContainer"', false);
        $res->assertSee('datatable-toolbar', false);
        $res->assertSee('table-responsive-stack', false);
        $res->assertSee('id="perPageSelect"', false);
        $res->assertSee('id="liveSearch"', false);
        $res->assertSee('id="filterJenisCetak"', false);
        $res->assertSee('id="filterModeCetak"', false);

        // Filtering server-side benar-benar menyaring baris.
        $this->withSession($session)->get(route('dashboard.laboran.peserta-didik.cetak'))->assertStatus(200);

        $kebutuhan = $this->withSession($session)->get(
            route('dashboard.laboran.arsip.index', ['tab' => 'riwayat', 'jenis' => 'kebutuhan'])
        );
        $kebutuhan->assertStatus(200);
        $kebutuhan->assertSee('kebutuhan', false);

        // Setiap baris menautkan tombol cetak ulang; hitung baris yang tampil
        // agar terbukti filter jenis benar-benar menyaring di sisi server.
        $jumlahKebutuhan = DB::table('laboran_log_cetak')
            ->where('jenis_dokumen', 'kebutuhan')
            ->count();
        $this->assertSame(
            min($jumlahKebutuhan, 15),
            substr_count($kebutuhan->getContent(), '/riwayat/')
        );

        // Pencarian nomor cetak mengembalikan halaman tanpa error.
        $log = DB::table('laboran_log_cetak')->latest('id')->first();
        $cari = $this->withSession($session)->get(
            route('dashboard.laboran.arsip.index', ['tab' => 'riwayat', 'q' => $log->nomor_cetak])
        );
        $cari->assertStatus(200);
        $cari->assertSee($log->nomor_cetak, false);

        // Filter yang tidak cocok → empty state tabel.
        $kosong = $this->withSession($session)->get(
            route('dashboard.laboran.arsip.index', ['tab' => 'riwayat', 'q' => 'TIDAK-ADA-' . uniqid()])
        );
        $kosong->assertStatus(200);
        $kosong->assertSee('Belum ada riwayat cetak', false);
    }

    public function test_kaprog_ditampilkan_sesuai_jurusan(): void
    {
        $service = app(LaboranDokumenService::class);

        // Tanpa penugasan KAPROG aktif pada data nyata, hasilnya null dan
        // dokumen menampilkan titik-titik — tetapi kunci tetap tersedia.
        $pejabat = $service->pejabat([
            'jurusan_id' => self::JUR_ATPH,
            'ptk_id'     => null,
            'nama'       => 'Petugas Uji',
        ]);

        $this->assertArrayHasKey('kaprog', $pejabat);
        $this->assertArrayHasKey('kepalaLab', $pejabat);
        $this->assertArrayHasKey('kepalaSekolah', $pejabat);
        $this->assertArrayHasKey('petugas', $pejabat);
    }

    /**
     * Nama Kaprog yang SUDAH ditetapkan harus tercetak pada dokumen, dan
     * jurusan dokumen (kode anak) tetap cocok dengan jurusan penugasan
     * (kode induk) lewat relasi awalan dua arah.
     */
    public function test_nama_kaprog_tercetak_pada_dokumen(): void
    {
        $refId = DB::table('ref_tugas_tambahan')->where('kode', 'KAPROG')->value('id');
        $this->assertNotNull($refId, 'Referensi tugas tambahan KAPROG tidak tersedia.');

        $gtk = DB::table('gtk')->where('jenis_ptk_id_str', 'Guru')->first();
        $this->assertNotNull($gtk, 'Data GTK tidak tersedia untuk pengujian.');

        // Penugasan memakai kode jurusan INDUK (26010); dokumen memakai ANAK (26010475).
        // `tmt_tugas` dibuat paling baru agar terpilih di antara penugasan nyata.
        $dutyId = DB::table('ptk_tugas_tambahan')->insertGetId([
            'user_id'           => null,
            'ptk_id'            => $gtk->ptk_id,
            'tugas_tambahan_id' => $refId,
            'nomor_sk'          => 'SK/KAPROG/' . uniqid(),
            'tmt_tugas'         => now()->addYears(5)->toDateString(),
            'jurusan_id'        => '26010',
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        try {
            $kaprog = app(LaboranDokumenService::class)->kaprog(self::JUR_ATPH);

            $this->assertNotNull($kaprog, 'Kaprog harus teresolusi lewat kecocokan awalan jurusan.');
            $this->assertSame($gtk->nama, $kaprog->nama);
        } finally {
            DB::table('ptk_tugas_tambahan')->where('id', $dutyId)->delete();
        }
    }

    /**
     * `jurusanCocok()` menangani nilai multi-jurusan dipisah koma maupun JSON.
     */
    public function test_pencocokan_jurusan_mendukung_multinilai(): void
    {
        $refId = DB::table('ref_tugas_tambahan')->where('kode', 'KAPROG')->value('id');
        $gtk   = DB::table('gtk')->where('jenis_ptk_id_str', 'Guru')->first();

        $buat = fn (?string $jurusan) => DB::table('ptk_tugas_tambahan')->insertGetId([
            'user_id'           => null,
            'ptk_id'            => $gtk->ptk_id,
            'tugas_tambahan_id' => $refId,
            'nomor_sk'          => 'SK/KAPROG/' . uniqid(),
            'tmt_tugas'         => now()->subYear()->toDateString(),
            'jurusan_id'        => $jurusan,
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $ids = [];
        try {
            foreach (['26020,26020550', '["12012","12012175"]'] as $nilai) {
                $ids[] = $id = $buat($nilai);
                $service = app(LaboranDokumenService::class);

                $this->assertNotNull(
                    $service->kaprog(str_contains($nilai, '2602') ? '26020550' : '12012175'),
                    "Kaprog tidak teresolusi untuk penugasan multi-jurusan: {$nilai}"
                );
            }
        } finally {
            DB::table('ptk_tugas_tambahan')->whereIn('id', $ids)->delete();
        }
    }

    public function test_registri_dokumen_mencakup_semua_modul(): void
    {
        foreach ([
            'dashboard', 'peserta-didik', 'inventaris', 'bhp', 'jadwal',
            'jadwal-per-guru', 'laporan-penggunaan', 'kebutuhan', 'perawatan',
            'pemeriksaan', 'pengadaan', 'kerusakan', 'peminjaman', 'jurnal',
            'kebersihan', 'laporan-lengkap', 'arsip',
        ] as $jenis) {
            $this->assertArrayHasKey(
                $jenis,
                LaboranDokumenService::DOKUMEN,
                "Jenis dokumen {$jenis} belum terdaftar."
            );
            $this->assertNotEmpty(LaboranDokumenService::DOKUMEN[$jenis]['label']);
            $this->assertNotEmpty(LaboranDokumenService::DOKUMEN[$jenis]['kategori']);
        }
    }
}
