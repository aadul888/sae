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
}
