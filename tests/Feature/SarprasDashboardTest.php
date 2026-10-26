<?php

namespace Tests\Feature;

use App\Services\SarprasDashboardService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian Dashboard Induk Sarana & Prasarana (`dashboard/tendik?bidang=sarpras`).
 *
 * Dashboard ini adalah pusat kendali seluruh urusan sarpras dan induk bagi
 * modul turunan (laboran, teknisi, penjaga, keamanan). Yang diuji:
 *  - Agregasi angka KPI lintas modul dari tabel nyata.
 *  - Perhitungan skor kelayakan fisik.
 *  - Struktur payload chart yang dipakai JS.
 *  - Peringatan operasional otomatis.
 *  - Tautan kendali bidang turunan + gerbang hak akses.
 */
class SarprasDashboardTest extends TestCase
{
    private array $created = [];

    /** @var array<string, array<int, int>> */
    private array $preexistingIds = [];

    private const TABLES = [
        'teknisi_work_order',
        'sarpras_peminjaman',
        'laboran_peminjaman',
        'laboran_usulan_pengadaan',
        'kebersihan_checklist',
        'penjaga_malam_log',
        'keamanan_insiden',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->preexistingIds = [];
        foreach (self::TABLES as $table) {
            try {
                if (!\App\Support\Table::exists($table)) {
                    continue;
                }
                $this->preexistingIds[$table] = DB::table($table)->pluck('id')->all();
            } catch (\Throwable $e) {
                $this->preexistingIds[$table] = [];
            }
        }
    }

    protected function tearDown(): void
    {
        // Hapus hanya baris yang dibuat oleh test ini.
        foreach ($this->created as $table => $ids) {
            if (empty($ids)) {
                continue;
            }
            try {
                DB::table($table)->whereIn('id', $ids)->delete();
            } catch (\Throwable $e) {
                // abaikan: data uji mungkin sudah tidak ada
            }
        }

        parent::tearDown();
    }

    private function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return [
            'id' => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'name' => $admin->nama,
            'nama' => $admin->nama,
            'username' => $admin->username,
            'role' => 'admin',
            'peran_id_str' => $admin->peran_id_str,
        ];
    }

    private function track(string $table, int $id): void
    {
        $this->created[$table][] = $id;
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $res = $this->withSession(['user' => $this->adminSession()])
            ->get('/dashboard/tendik?bidang=sarpras');

        $res->assertStatus(200);
        $res->assertViewIs('dashboard.tendik');
        $res->assertViewHas('sarprasRingkasan');
        $res->assertViewHas('sarprasCharts');
        $res->assertViewHas('sarprasLintasBidang');

        $html = $res->getContent();
        $this->assertStringContainsString('Kendali Bidang Turunan Sarpras', $html);
        $this->assertStringContainsString('sarprasChartKondisiRuang', $html);
        $this->assertStringContainsString('sectionSarprasChartPayload', $html);
    }

    public function test_ringkasan_counts_real_master_data(): void
    {
        $data = SarprasDashboardService::build($this->adminSession());

        $this->assertSame(
            DB::table('sarpras_ruang')->count(),
            $data['ringkasan']['ruang_total'],
            'Total ruang harus sama dengan isi tabel sarpras_ruang.'
        );

        $this->assertSame(
            (int) (DB::table('sarpras_aset')->sum('jumlah') ?: 0),
            $data['ringkasan']['aset_unit'],
            'Total unit aset harus menjumlahkan kolom jumlah.'
        );

        $this->assertSame(
            DB::table('sarpras_ruang')->distinct()->count('gedung'),
            $data['ringkasan']['gedung_total'],
            'Jumlah gedung harus dihitung dari nilai distinct kolom gedung.'
        );
    }

    public function test_skor_kelayakan_is_percentage_of_good_items(): void
    {
        $data = SarprasDashboardService::build($this->adminSession());
        $r = $data['ringkasan'];

        $total = $r['ruang_total'] + $r['aset_unit'];
        $baik = $r['ruang_baik'] + $r['aset_baik_unit'];
        $expected = $total > 0 ? (int) round(($baik / $total) * 100) : 0;

        $this->assertSame($expected, $r['skor_kelayakan']);
        $this->assertGreaterThanOrEqual(0, $r['skor_kelayakan']);
        $this->assertLessThanOrEqual(100, $r['skor_kelayakan']);
    }

    public function test_charts_payload_has_expected_shape(): void
    {
        $data = SarprasDashboardService::build($this->adminSession());
        $charts = $data['charts'];

        foreach (['kondisiRuang', 'kondisiAset', 'kategoriAset', 'gedung', 'statusWorkOrder'] as $key) {
            $this->assertArrayHasKey($key, $charts, "Payload chart {$key} tidak tersedia.");
            $this->assertIsArray($charts[$key]);
        }

        $this->assertArrayHasKey('labels', $charts['trenPeminjaman']);
        $this->assertArrayHasKey('pinjam', $charts['trenPeminjaman']);
        $this->assertArrayHasKey('kembali', $charts['trenPeminjaman']);
        $this->assertCount(6, $charts['trenPeminjaman']['labels'], 'Tren peminjaman harus 6 bulan.');
    }

    public function test_work_order_aggregation_counts_by_status(): void
    {
        $now = now();
        $rows = [
            ['TEST-SD-WO-1', 'darurat', 'antrean'],
            ['TEST-SD-WO-2', 'normal', 'proses'],
            ['TEST-SD-WO-3', 'normal', 'selesai'],
        ];

        foreach ($rows as [$nomor, $urgensi, $status]) {
            $id = DB::table('teknisi_work_order')->insertGetId([
                'nomor_wo' => $nomor,
                'tanggal' => $now->toDateString(),
                'lokasi_unit' => 'Lab Uji',
                'kategori_perbaikan' => 'listrik',
                'deskripsi_kerusakan' => 'Uji agregasi dashboard sarpras',
                'tingkat_urgensi' => $urgensi,
                'pelapor_nama' => 'Tester',
                'status' => $status,
                'estimasi_biaya_part' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->track('teknisi_work_order', (int) $id);
        }

        $before = $this->countStatus('teknisi_work_order', ['antrean', 'proses', 'selesai']);
        $data = SarprasDashboardService::build($this->adminSession());
        $r = $data['ringkasan'];

        $this->assertSame($before['antrean'], $r['wo_antrean']);
        $this->assertSame($before['proses'], $r['wo_proses']);
        $this->assertGreaterThanOrEqual(1, $r['wo_darurat'], 'WO darurat yang masih terbuka harus terhitung.');

        $woChart = $data['charts']['statusWorkOrder'];
        $this->assertSame($before['antrean'], $woChart['Antrean']);
        $this->assertSame($before['proses'], $woChart['Proses']);
        $this->assertSame($before['selesai'], $woChart['Selesai']);
    }

    public function test_peminjaman_aggregates_both_legacy_and_unified_tables(): void
    {
        $now = now();

        $legacyId = DB::table('sarpras_peminjaman')->insertGetId([
            'nomor_pinjam' => 'TEST-SD-PINJAM-1',
            'aset_id' => DB::table('sarpras_aset')->value('id') ?: 1,
            'peminjam_tipe' => 'gtk',
            'peminjam_nama' => 'Uji Legacy',
            'keperluan' => 'Uji dashboard',
            'tanggal_pinjam' => $now->toDateString(),
            'tanggal_kembali_rencana' => $now->copy()->subDays(3)->toDateString(),
            'kondisi_sebelum' => 'baik',
            'status' => 'dipinjam',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->track('sarpras_peminjaman', (int) $legacyId);

        $unifiedId = DB::table('laboran_peminjaman')->insertGetId([
            'nomor_pinjam' => 'TEST-SD-PINJAM-2',
            'peminjam_tipe' => 'siswa',
            'peminjam_nama' => 'Uji Terpadu',
            'keperluan' => 'Uji dashboard',
            'tanggal_pinjam' => $now->toDateString(),
            'tanggal_kembali_rencana' => $now->copy()->addDays(3)->toDateString(),
            'jumlah_item' => 1,
            'status' => 'dipinjam',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->track('laboran_peminjaman', (int) $unifiedId);

        $data = SarprasDashboardService::build($this->adminSession());

        $this->assertGreaterThanOrEqual(2, $data['ringkasan']['pinjam_aktif']);
        $this->assertGreaterThanOrEqual(
            1,
            $data['ringkasan']['pinjam_terlambat'],
            'Peminjaman yang melewati tenggat harus dihitung terlambat.'
        );

        $nomor = array_column($data['daftar']['peminjamanAktif'], 'nomor_pinjam');
        $this->assertContains('TEST-SD-PINJAM-1', $nomor);
        $this->assertContains('TEST-SD-PINJAM-2', $nomor);
    }

    public function test_peringatan_flags_overdue_and_emergency(): void
    {
        $now = now();

        $woId = DB::table('teknisi_work_order')->insertGetId([
            'nomor_wo' => 'TEST-SD-WO-DARURAT',
            'tanggal' => $now->toDateString(),
            'lokasi_unit' => 'Gedung Uji',
            'kategori_perbaikan' => 'listrik',
            'deskripsi_kerusakan' => 'Korsleting',
            'tingkat_urgensi' => 'darurat',
            'pelapor_nama' => 'Tester',
            'status' => 'antrean',
            'estimasi_biaya_part' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->track('teknisi_work_order', (int) $woId);

        $data = SarprasDashboardService::build($this->adminSession());
        $teks = implode(' | ', array_column($data['peringatan'], 'text'));

        $this->assertStringContainsString('Work order darurat', $teks);

        foreach ($data['peringatan'] as $w) {
            $this->assertContains($w['tone'], ['danger', 'warning', 'info']);
            $this->assertGreaterThan(0, $w['count'], 'Peringatan tidak boleh muncul dengan hitungan nol.');
        }
    }

    public function test_lintas_bidang_cards_have_routes_and_permission_gate(): void
    {
        $data = SarprasDashboardService::build($this->adminSession());

        $this->assertNotEmpty($data['lintasBidang']);
        $this->assertGreaterThanOrEqual(10, count($data['lintasBidang']), 'Semua modul turunan harus terwakili.');

        foreach ($data['lintasBidang'] as $item) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($item['route']),
                "Route kendali '{$item['route']}' tidak terdaftar."
            );
            $this->assertArrayHasKey('allowed', $item);
            $this->assertIsBool($item['allowed']);
        }
    }

    public function test_penanggung_jawab_room_counts_match_room_table(): void
    {
        $data = SarprasDashboardService::build($this->adminSession());
        $totalPj = array_sum(array_column($data['penanggungJawab'], 'jumlah_ruang'));
        $totalTerisi = DB::table('sarpras_ruang')
            ->whereNotNull('penanggung_jawab_ptk_id')
            ->where('penanggung_jawab_ptk_id', '<>', '')
            ->count();

        $this->assertSame($totalTerisi, (int) $totalPj);
    }

    public function test_empty_tables_do_not_break_aggregation(): void
    {
        // Nilai harus numerik walau tabel modul turunan belum berisi data.
        $data = SarprasDashboardService::build($this->adminSession());

        foreach ($data['ringkasan'] as $key => $value) {
            $this->assertTrue(
                is_int($value) || is_float($value),
                "Ringkasan '{$key}' harus berupa angka, bukan " . gettype($value)
            );
        }
    }

    /**
     * @param  array<int, string>  $statuses
     * @return array<string, int>
     */
    private function countStatus(string $table, array $statuses): array
    {
        $out = [];
        foreach ($statuses as $status) {
            $out[$status] = DB::table($table)->where('status', $status)->count();
        }

        return $out;
    }
}
