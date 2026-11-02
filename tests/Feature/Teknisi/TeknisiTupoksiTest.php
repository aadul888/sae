<?php

namespace Tests\Feature\Teknisi;

use App\Support\Table;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke test modul TUPOKSI & Supervisi Teknisi (5 indikator).
 *
 * Berjalan di database operasional: cleanup HANYA menghapus baris yang
 * dibuat selama test (snapshot id di setUp), bukan mengosongkan tabel.
 */
class TeknisiTupoksiTest extends TestCase
{
    /** @var array<int, string> */
    protected const TABLES = [
        'teknisi_area_tugas',
        'teknisi_pelaksanaan_harian',
        'teknisi_sarana_rapat',
        'teknisi_work_order',
        'teknisi_import_log',
    ];

    /** @var array<string, array<int, int>> */
    protected array $preexistingIds = [];

    /** @var array<string, int> batas id saat setUp (cadangan bila tabel kosong) */
    protected array $preexistingMaxIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::TABLES as $table) {
            try {
                if (Table::exists($table)) {
                    $this->preexistingIds[$table] = DB::table($table)->pluck('id')->all();
                    $this->preexistingMaxIds[$table] = (int) DB::table($table)->max('id');
                } else {
                    $this->preexistingIds[$table] = [];
                    $this->preexistingMaxIds[$table] = 0;
                }
            } catch (\Throwable $e) {
                $this->preexistingIds[$table] = [];
                $this->preexistingMaxIds[$table] = 0;
            }
        }
    }

    protected function tearDown(): void
    {
        // Hapus berkas template sementara yang dibuat respons (tidak terkirim saat test).
        foreach (glob(storage_path('app/temp_teknisi_*.xlsx')) ?: [] as $f) {
            @unlink($f);
        }

        foreach (self::TABLES as $table) {
            try {
                if (!Table::exists($table)) {
                    continue;
                }
                $preexisting = $this->preexistingIds[$table] ?? [];
                $idsBaru = DB::table($table)
                    ->when(!empty($preexisting), fn ($q) => $q->whereNotIn('id', $preexisting))
                    ->when(empty($preexisting), fn ($q) => $q->where('id', '>', $this->preexistingMaxIds[$table] ?? 0))
                    ->pluck('id')
                    ->all();
                if (!empty($idsBaru)) {
                    DB::table($table)->whereIn('id', $idsBaru)->delete();
                }
            } catch (\Throwable $e) {
            }
        }

        parent::tearDown();
    }

    protected function adminSession(): array
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

    /** @return array<string, mixed> payload dasar per modul */
    protected function payload(string $route): array
    {
        return match ($route) {
            'area-tugas'        => ['area' => 'Koridor Lantai 1', 'petugas_nama' => 'Teknisi Uji', 'tanggal_mulai' => now()->toDateString()],
            'sarana-rapat'      => ['tanggal_rapat' => now()->toDateString(), 'nama_rapat' => 'Rapat Uji', 'daftar_sarana_text' => 'Proyektor | 1 | baik'],
            default             => [],
        };
    }

    public function test_index_modul_merespons_200(): void
    {
        $session = $this->adminSession();

        foreach (['area-tugas', 'sarana-rapat', 'pelaksanaan-harian', 'jurnal-perbaikan'] as $route) {
            $this->withSession($session)
                ->get(route('dashboard.teknisi.' . $route . '.index'))
                ->assertStatus(200);
        }
    }

    public function test_store_dan_index_modul_tupoksi(): void
    {
        $session = $this->adminSession();

        // Store tiap modul tupoksi lalu pastikan baris masuk.
        foreach (['area-tugas', 'sarana-rapat'] as $route) {
            $table = 'teknisi_' . str_replace('-', '_', $route);

            $this->withSession($session)
                ->postJson(route('dashboard.teknisi.' . $route . '.store'), $this->payload($route))
                ->assertStatus(200);

            $this->assertGreaterThan(
                count($this->preexistingIds[$table] ?? []),
                DB::table($table)->count(),
                "Baris {$table} tidak bertambah."
            );
        }
    }

    /**
     * Pelaksanaan harian: konfirmasi rencana area tugas selesai/belum.
     */
    public function test_pelaksanaan_harian_konfirmasi_status(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.area-tugas.store'), $this->payload('area-tugas'))
            ->assertStatus(200);

        $rencana = DB::table('teknisi_area_tugas')->orderByDesc('id')->first();
        $this->assertNotNull($rencana, 'Baris area tugas tidak masuk.');

        $this->withSession($session)
            ->get(route('dashboard.teknisi.pelaksanaan-harian.index'))
            ->assertStatus(200);

        // Baris pelaksanaan hari ini ter-generate otomatis dari rencana aktif.
        $row = DB::table('teknisi_pelaksanaan_harian')
            ->where('area_tugas_id', $rencana->id)
            ->where('tanggal', now()->toDateString())
            ->first();
        $this->assertNotNull($row, 'Baris pelaksanaan harian tidak ter-generate.');
        $this->assertSame('belum', $row->status);

        $this->withSession($session)
            ->putJson(route('dashboard.teknisi.pelaksanaan-harian.status', $row->id), ['status' => 'selesai'])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertSame('selesai', DB::table('teknisi_pelaksanaan_harian')->where('id', $row->id)->value('status'));
    }

    /**
     * Ceklist persiapan rapat otomatis masuk Jurnal Pelaksanaan Tugas.
     */
    public function test_sarana_rapat_masuk_jurnal_pelaksanaan(): void
    {
        $session = $this->adminSession();

        $nama = 'Rapat Ceklist Uji ' . uniqid();

        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.sarana-rapat.store'), [
                'tanggal_rapat' => now()->toDateString(),
                'nama_rapat'    => $nama,
                'siap'          => [],
            ])
            ->assertStatus(200);

        $sr = DB::table('teknisi_sarana_rapat')->where('nama_rapat', $nama)->first();
        $this->assertNotNull($sr, 'Ceklist sarana rapat tidak tersimpan.');

        $pel = DB::table('teknisi_pelaksanaan_harian')->where('sarana_rapat_id', $sr->id)->first();
        $this->assertNotNull($pel, 'Baris pelaksanaan tidak dibuat dari ceklist rapat.');
        $this->assertSame(now()->toDateString(), (string) $pel->tanggal);
        $this->assertStringContainsString($nama, $pel->area);

        // Baris muncul di Jurnal Pelaksanaan (cari agar tak bergantung paginasi).
        $this->withSession($session)
            ->get(route('dashboard.teknisi.pelaksanaan-harian.index', ['q' => $nama]))
            ->assertStatus(200)
            ->assertSee($nama, false);
    }

    /** Jurnal Perbaikan = turunan baca-saja dari Work Order selesai. */
    public function test_jurnal_perbaikan_baca_saja_dari_work_order(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->get(route('dashboard.teknisi.jurnal-perbaikan.index'))
            ->assertStatus(200);

        $this->withSession($session)
            ->get(route('dashboard.teknisi.jurnal-perbaikan.cetak'))
            ->assertStatus(200);

        // Tidak ada lagi route tulis untuk jurnal perbaikan.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dashboard.teknisi.jurnal-perbaikan.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('dashboard.teknisi.jurnal-perbaikan.update'));
    }

    /**
     * Nama petugas pada berkas impor diabaikan — selalu akun yang mengunggah.
     */
    public function test_import_tupoksi_mengabaikan_nama_petugas_dari_berkas(): void
    {
        $session = $this->adminSession();
        $namaLogin = $session['user']['nama'];

        $area = 'Area Uji Petugas ' . uniqid();
        $csv = "area,petugas_nama,petugas_ptk_id,status\n"
            . "{$area},Petugas Palsu,ptk-palsu,aktif\n";

        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.laporan.import', ['modul' => 'area-tugas']), [
                'file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('area.csv', $csv),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_area_tugas')->where('area', $area)->first();
        $this->assertNotNull($row, 'Baris area tugas tidak masuk.');
        $this->assertSame($namaLogin, $row->petugas_nama, 'Nama petugas harus dari akun login.');
        $this->assertSame((string) $session['user']['pengguna_id'], (string) $row->user_id);
        $this->assertNotSame('ptk-palsu', (string) $row->petugas_ptk_id);
    }

    public function test_template_import_cetak_setiap_modul(): void
    {
        $session = $this->adminSession();

        $spec = [
            'area-tugas'   => ['area,zona,periode_jadwal,petugas_nama,shift,jenis_tugas,status', 'Koridor Uji,Zona X,harian,Teknisi Uji,pagi,tukang_kebersihan,aktif'],
            'sarana-rapat' => ['tanggal_rapat,nama_rapat,ruang_nama,tahap,kondisi_ruang,jumlah_peserta,kelengkapan_sarana,status', '2026-10-01,Rapat Uji,Ruang Uji,sebelum_acara,bersih,20,lengkap,selesai'],
        ];

        foreach ($spec as $modul => [$header, $row]) {
            // 1. Template tersedia.
            $this->withSession($session)
                ->get(route('dashboard.teknisi.laporan.template', ['modul' => $modul]))
                ->assertStatus(200);

            // 2. Import CSV.
            $csv = $header . "\n" . $row . "\n";
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('import_' . $modul . '.csv', $csv);

            $this->withSession($session)
                ->postJson(route('dashboard.teknisi.laporan.import', ['modul' => $modul]), ['file' => $file])
                ->assertStatus(200)
                ->assertJsonPath('status', 'success');

            $table = 'teknisi_' . str_replace('-', '_', $modul);
            $this->assertGreaterThan(
                count($this->preexistingIds[$table] ?? []),
                DB::table($table)->count(),
                "Baris {$table} tidak bertambah setelah import."
            );

            // 2b. Pengunggah tercatat: kolom user_id + riwayat unggah.
            $imported = DB::table($table)->orderByDesc('id')->first();
            $this->assertSame(
                (string) $session['user']['pengguna_id'],
                (string) $imported->user_id,
                "Import {$modul} harus mencatat akun pengunggah."
            );

            $log = DB::table('teknisi_import_log')->where('modul', $modul)->orderByDesc('id')->first();
            $this->assertNotNull($log, "Riwayat unggah {$modul} tidak tercatat.");
            $this->assertSame((string) $session['user']['pengguna_id'], (string) $log->user_id);
            $this->assertSame($session['user']['nama'], $log->user_nama);

            // 3. Cetak laporan (per halaman).
            $this->withSession($session)
                ->get(route('dashboard.teknisi.laporan.cetak', ['modul' => $modul]))
                ->assertStatus(200);
        }
    }

    // =========================================================================
    // LAPORAN BERKALA (AREA TUGAS): minggu · bulan · triwulan · semester · tahun
    // =========================================================================

    /** Rentang tanggal tiap tipe periode harus tepat (dasar seluruh laporan). */
    public function test_periode_laporan_menghasilkan_rentang_tepat(): void
    {
        $minggu = \App\Support\PeriodeLaporan::resolve('minggu', 1, 2026, 1, '1', '', '2026-11-16');
        $this->assertSame('2026-11-16', $minggu['start'], 'Minggu mulai hari Senin.');
        $this->assertSame('2026-11-22', $minggu['end']);

        $bulan = \App\Support\PeriodeLaporan::resolve('bulan', 2, 2026, 1, '1', '', null);
        $this->assertSame('2026-02-01', $bulan['start']);
        $this->assertSame('2026-02-28', $bulan['end']);

        $triwulan = \App\Support\PeriodeLaporan::resolve('triwulan', 1, 2026, 3, '1', '', null);
        $this->assertSame('2026-07-01', $triwulan['start']);
        $this->assertSame('2026-09-30', $triwulan['end']);

        $ganjil = \App\Support\PeriodeLaporan::resolve('semester', 1, 2026, 1, '1', '2026/2027', null);
        $this->assertSame('2026-07-01', $ganjil['start']);
        $this->assertSame('2026-12-31', $ganjil['end']);

        $genap = \App\Support\PeriodeLaporan::resolve('semester', 1, 2026, 1, '2', '2026/2027', null);
        $this->assertSame('2027-01-01', $genap['start']);
        $this->assertSame('2027-06-30', $genap['end']);

        $tahun = \App\Support\PeriodeLaporan::resolve('tahun', 1, 2026, 1, '1', '', null);
        $this->assertSame('2026-01-01', $tahun['start']);
        $this->assertSame('2026-12-31', $tahun['end']);

        // Tipe tak dikenal jatuh ke bulanan (tidak pernah error).
        $fallback = \App\Support\PeriodeLaporan::resolve('ngawur', 5, 2026, 1, '1', '', null);
        $this->assertSame('bulan', $fallback['tipe']);
    }

    /** Setiap tipe periode dapat mencetak data pada rentangnya. */
    public function test_cetak_laporan_area_tugas_lima_tipe_periode(): void
    {
        $session = $this->adminSession();
        $this->sisipAreaTugas('2026-11-16');

        $periode = [
            'minggu'   => ['periode' => 'minggu', 'tanggal' => '2026-11-16'],
            'bulan'    => ['periode' => 'bulan', 'bulan' => 11, 'tahun' => 2026],
            'triwulan' => ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026],
            'semester' => ['periode' => 'semester', 'semester' => 1, 'tahun_ajaran' => '2026/2027'],
            'tahun'    => ['periode' => 'tahun', 'tahun' => 2026],
        ];

        foreach ($periode as $tipe => $query) {
            $res = $this->withSession($session)
                ->get(route('dashboard.teknisi.laporan.cetak', array_merge(['modul' => 'area-tugas'], $query)));

            $res->assertStatus(200);
            $body = $res->getContent();

            $this->assertMatchesRegularExpression('/@page\s*\{[^}]*size:\s*A4/i', $body, "Periode {$tipe} bukan A4.");
            $this->assertStringContainsString('AREA TUGAS', strtoupper($body), "Periode {$tipe} tidak memuat judul.");
            $this->assertStringNotContainsString(
                'Tidak ada data pada periode ini',
                $body,
                "Periode {$tipe} tidak menemukan data yang seharusnya masuk rentang."
            );
        }
    }

    /** Versi ringkasan = rekap; lengkap = rincian per catatan. */
    public function test_cetak_laporan_area_tugas_versi_ringkasan_dan_lengkap(): void
    {
        $session = $this->adminSession();
        $this->sisipAreaTugas('2026-11-16');

        $ringkas = $this->withSession($session)->get(route('dashboard.teknisi.laporan.cetak', [
            'modul' => 'area-tugas', 'periode' => 'tahun', 'tahun' => 2026, 'mode' => 'ringkasan',
        ]));
        $ringkas->assertStatus(200);
        $ringkas->assertSee('Rekapitulasi jumlah catatan', false);
        $ringkas->assertDontSee('Data ke-', false);

        $lengkap = $this->withSession($session)->get(route('dashboard.teknisi.laporan.cetak', [
            'modul' => 'area-tugas', 'periode' => 'tahun', 'tahun' => 2026, 'mode' => 'lengkap',
        ]));
        $lengkap->assertStatus(200);
        // Versi lengkap kini berupa SATU tabel utuh: tanpa paginasi "Data ke-".
        $lengkap->assertDontSee('Data ke-', false);
        $lengkap->assertSee('lap-detail', false);
        // Kolom banyak (>=7) → orientasi default landscape.
        $lengkap->assertSee('A4 landscape', false);

        // Tanda tangan 3 pihak: Teknisi, Kepala TAS, Kepala Sekolah.
        $lengkap->assertSee('Teknisi,', false);
        $lengkap->assertSee('Kepala Tenaga Administrasi Sekolah,', false);
        $lengkap->assertSee('Kepala Satuan Pendidikan,', false);
    }

    /**
     * Pemilih periode hidup di halaman INDEKS (dropdown cetak), bukan lagi di
     * halaman cetak. Halaman cetak tetap melayani seluruh nilai `periode`.
     */
    public function test_cetak_laporan_area_tugas_memuat_pemilih_periode(): void
    {
        $index = $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.area-tugas.index'));
        $index->assertStatus(200);
        foreach (['Mingguan', 'Bulanan', 'Triwulan', 'Semester', 'Tahunan'] as $label) {
            $index->assertSee($label, false);
        }

        // Kontrak dropdown: form cetak harus `data-no-ajax` agar tidak diubah
        // menjadi refresh tabel oleh dashboard-live-search.js, tombol kirim
        // berlabel "Cetak", dan membuka tab baru.
        $index->assertSee('data-no-ajax', false);
        $index->assertSee('Cetak', false);

        foreach (['minggu', 'bulan', 'triwulan', 'semester', 'tahun'] as $tipe) {
            $res = $this->withSession($this->adminSession())
                ->get(route('dashboard.teknisi.laporan.cetak', [
                    'modul' => 'area-tugas', 'periode' => $tipe, 'bulan' => 11, 'tahun' => 2026,
                ]));
            $res->assertStatus(200);
        }

        $lama = $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.laporan.cetak', [
                'modul' => 'area-tugas', 'dari' => '2026-01-01', 'sampai' => '2026-12-31',
            ]));
        $lama->assertStatus(200);
    }

    /** Sisipkan satu jadwal area tugas (dibersihkan `tearDown`). */
    private function sisipAreaTugas(string $tanggal): void
    {
        DB::table('teknisi_area_tugas')->insert([
            'nomor_jadwal'    => 'AT/UJI/' . uniqid(),
            'area'            => 'Gedung Uji Laporan',
            'zona'            => 'Zona Uji',
            'periode_jadwal'  => 'mingguan',
            'petugas_nama'    => 'Teknisi Uji',
            'shift'           => 'pagi',
            'tanggal_mulai'   => $tanggal,
            'jenis_tugas'     => 'tukang_kebersihan',
            'status'          => 'aktif',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
