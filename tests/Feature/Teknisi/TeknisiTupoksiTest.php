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
        'teknisi_kebersihan_ruang',
        'teknisi_kebersihan_rapat',
        'teknisi_sarana_rapat',
        'teknisi_jurnal_perbaikan',
        'teknisi_supervisi',
        'teknisi_supervisi_item',
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
            'kebersihan-ruang'  => ['tanggal_kegiatan' => now()->toDateString(), 'ruang_nama' => 'Lab Komputer 1', 'kondisi' => 'bersih'],
            'kebersihan-rapat'  => ['tanggal_kegiatan' => now()->toDateString(), 'nama_rapat' => 'Rapat Uji', 'kondisi' => 'bersih'],
            'sarana-rapat'      => ['tanggal_rapat' => now()->toDateString(), 'nama_rapat' => 'Rapat Uji', 'daftar_sarana_text' => 'Proyektor | 1 | baik'],
            'jurnal-perbaikan'  => ['tanggal' => now()->toDateString(), 'lokasi_unit' => 'Lab TKJ', 'jenis_pekerjaan' => 'perbaikan', 'uraian_kegiatan' => 'Ganti kabel LAN'],
            default             => [],
        };
    }

    public function test_index_5_modul_merespons_200(): void
    {
        $session = $this->adminSession();

        foreach (['area-tugas', 'kebersihan-ruang', 'kebersihan-rapat', 'sarana-rapat', 'jurnal-perbaikan'] as $route) {
            $this->withSession($session)
                ->get(route('dashboard.teknisi.' . $route . '.index'))
                ->assertStatus(200);
        }
    }

    public function test_store_dan_index_supervisi(): void
    {
        $session = $this->adminSession();

        // Store tiap modul tupoksi lalu pastikan baris masuk.
        foreach (['area-tugas', 'kebersihan-ruang', 'kebersihan-rapat', 'sarana-rapat', 'jurnal-perbaikan'] as $route) {
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

        // Supervisi: 5 indikator dengan skor 1-4.
        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.supervisi.store'), [
                'teknisi_nama'     => 'Teknisi Uji',
                'supervisor_nama'  => 'Supervisor Uji',
                'tanggal_supervisi' => now()->toDateString(),
                'tahun_ajaran'     => '2025/2026',
                'semester'         => 'ganjil',
                'skor'             => [1 => 4, 2 => 3, 3 => 4, 4 => 3, 5 => 4],
                'catatan'          => [1 => 'Baik', 2 => 'Cukup', 3 => 'Baik', 4 => 'Cukup', 5 => 'Baik'],
                'catatan_umum'     => 'Cukup baik.',
            ])
            ->assertStatus(200);

        $row = DB::table('teknisi_supervisi')->where('teknisi_nama', 'Teknisi Uji')->first();
        $this->assertNotNull($row);
        $this->assertSame(5, DB::table('teknisi_supervisi_item')->where('supervisi_id', $row->id)->count());

        $this->withSession($session)
            ->get(route('dashboard.teknisi.supervisi.index'))
            ->assertStatus(200);

        $this->withSession($session)
            ->get(route('dashboard.teknisi.supervisi.cetak', $row->id))
            ->assertStatus(200);
    }

    public function test_template_import_cetak_setiap_modul(): void
    {
        $session = $this->adminSession();

        $spec = [
            'area-tugas'       => ['area,zona,periode_jadwal,petugas_nama,shift,jenis_tugas,status', 'Koridor Uji,Zona X,harian,Teknisi Uji,pagi,tukang_kebersihan,aktif'],
            'kebersihan-ruang' => ['tanggal_kegiatan,ruang_nama,kategori_ruang,kondisi,status', '2026-10-01,Ruang Uji,perkantoran,bersih,selesai'],
            'kebersihan-rapat' => ['tanggal_kegiatan,lokasi,nama_rapat,area,tahap,kondisi,status', '2026-10-01,Ruang Rapat Uji,Rapat Uji,ruang_rapat,sebelum_acara,bersih,selesai'],
            'sarana-rapat'     => ['tanggal_rapat,nama_rapat,ruang_nama,jumlah_peserta,kelengkapan_sarana,status', '2026-10-01,Rapat Uji,Ruang Uji,20,lengkap,selesai'],
            'jurnal-perbaikan' => ['tanggal,lokasi_unit,jenis_pekerjaan,uraian_kegiatan,status', '2026-10-01,Lab Uji,perbaikan,Ganti kabel LAN,selesai'],
            'supervisi'        => ['tanggal_supervisi,teknisi_nama,supervisor_nama,status,skor_1,skor_2,skor_3,skor_4,skor_5', '2026-10-01,Teknisi Uji,Supervisor Uji,final,4,3,4,3,4'],
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
        $lengkap->assertSee('Data ke-', false);

        // Tanda tangan 3 pihak: Teknisi, Kepala TAS, Kepala Sekolah.
        $lengkap->assertSee('Teknisi,', false);
        $lengkap->assertSee('Kepala Tenaga Administrasi Sekolah,', false);
        $lengkap->assertSee('Kepala Satuan Pendidikan,', false);
    }

    /** Bar alat memuat kelima pilihan periode + tautan lama (dari/sampai) tetap hidup. */
    public function test_cetak_laporan_area_tugas_memuat_pemilih_periode(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.laporan.cetak', [
                'modul' => 'area-tugas', 'periode' => 'minggu', 'tanggal' => '2026-11-16',
            ]));

        $res->assertStatus(200);
        foreach (['Mingguan', 'Bulanan', 'Triwulan', 'Semester', 'Tahunan'] as $label) {
            $res->assertSee($label, false);
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
