<?php

namespace Tests\Feature;

use App\Models\JadwalKbmPekan;
use App\Models\User;
use App\Services\JadwalBulananService;
use App\Services\JadwalPekanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Uji lapis 2 pemberlakuan jadwal: pembentukan pekan dari Kalender
 * Pendidikan, pemberlakuan status, serta ekspansi cetak bulanan.
 *
 * Catatan: suite ini memakai database operasional (tanpa RefreshDatabase),
 * sehingga seluruh data uji pada `jadwal_kbm_pekan` WAJIB dibersihkan
 * pada `tearDown()` agar tidak mengganggu data nyata.
 */
class JadwalPekanTest extends TestCase
{
    private const TA = '2026/2027';
    private const SEM = '1';

    protected function tearDown(): void
    {
        if (\App\Support\Table::exists('jadwal_kbm_pekan')) {
            DB::table('jadwal_kbm_pekan')->where('tahun_ajaran', self::TA)->delete();
        }

        parent::tearDown();
    }

    public function test_build_rentang_pekan_mulai_senin_dan_memotong_batas_semester(): void
    {
        $service = app(JadwalPekanService::class);
        $rentang = $service->buildRentangPekan('2026-07-01', '2026-12-31');

        $this->assertNotEmpty($rentang);

        // Pekan 1 diawali tepat pada tanggal mulai semester (dipotong), bukan Senin sebelumnya.
        $this->assertSame(1, $rentang[0]['pekan_ke']);
        $this->assertSame('2026-07-01', $rentang[0]['tanggal_mulai']);

        // Pekan 2 dan seterusnya dimulai hari Senin.
        $this->assertSame('2026-07-06', $rentang[1]['tanggal_mulai']);
        $this->assertSame(Carbon::MONDAY, Carbon::parse($rentang[1]['tanggal_mulai'])->dayOfWeekIso);

        // Pekan terakhir dipotong pada tanggal akhir semester.
        $last = end($rentang);
        $this->assertSame('2026-12-31', $last['tanggal_selesai']);

        // Nomor pekan berurutan tanpa lompatan.
        $nomor = array_column($rentang, 'pekan_ke');
        $this->assertSame(range(1, count($rentang)), $nomor);

        // Tidak ada celah antar pekan.
        for ($i = 1; $i < count($rentang); $i++) {
            $prevEnd = Carbon::parse($rentang[$i - 1]['tanggal_selesai']);
            $currStart = Carbon::parse($rentang[$i]['tanggal_mulai']);
            $this->assertSame(
                $prevEnd->copy()->addDay()->toDateString(),
                $currStart->toDateString(),
                'Rentang pekan harus bersambung tanpa celah.'
            );
        }
    }

    public function test_generate_pekan_idempoten_dan_tidak_menimpa_pekan_berlaku(): void
    {
        $service = app(JadwalPekanService::class);

        $first = $service->generatePekan(self::TA, self::SEM);
        $this->assertGreaterThan(0, $first['created']);
        $this->assertSame(0, $first['updated']);

        $total = JadwalKbmPekan::periode(self::TA, self::SEM)->count();
        $this->assertSame($first['total'], $total);
        $this->assertSame(0, JadwalKbmPekan::periode(self::TA, self::SEM)->where('status', '!=', 'draft')->count());

        // Pekan pertama diberlakukan, lalu generate ulang.
        $pekan = JadwalKbmPekan::periode(self::TA, self::SEM)->orderBy('pekan_ke')->first();
        $service->ubahStatus($pekan->id, 'aktif', 'test');

        $second = $service->generatePekan(self::TA, self::SEM);
        $this->assertSame(0, $second['created'], 'Generate ulang tidak boleh menduplikasi pekan.');
        $this->assertSame($second['total'], JadwalKbmPekan::periode(self::TA, self::SEM)->count());

        // Pekan yang sudah aktif tidak boleh dikembalikan ke draft / diubah tanggalnya.
        $pekan->refresh();
        $this->assertSame('aktif', $pekan->status);
        $this->assertNotNull($pekan->diberlakukan_pada);
        $this->assertSame('test', $pekan->diberlakukan_oleh);
    }

    public function test_status_draft_menghapus_jejak_pemberlakuan(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);

        $pekan = JadwalKbmPekan::periode(self::TA, self::SEM)->orderBy('pekan_ke')->first();

        $aktif = $service->ubahStatus($pekan->id, 'aktif', 'operator-1');
        $this->assertSame('aktif', $aktif->status);
        $this->assertSame('operator-1', $aktif->diberlakukan_oleh);
        $this->assertNotNull($aktif->diberlakukan_pada);

        $draft = $service->ubahStatus($pekan->id, 'draft', 'operator-1');
        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->diberlakukan_pada);
        $this->assertNull($draft->diberlakukan_oleh);
    }

    public function test_update_pekan_menolak_rentang_tumpang_tindih(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);

        $list = JadwalKbmPekan::periode(self::TA, self::SEM)->orderBy('pekan_ke')->limit(2)->get();
        $this->assertCount(2, $list);

        [$pertama, $kedua] = [$list[0], $list[1]];

        try {
            $service->updatePekan($kedua->id, [
                'tanggal_mulai'   => $pertama->tanggal_mulai->toDateString(),
                'tanggal_selesai' => $pertama->tanggal_selesai->toDateString(),
                'status'          => 'draft',
            ]);
            $this->fail('Rentang yang bertabrakan seharusnya ditolak.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('bertabrakan', $e->getMessage());
        }

        // Tanggal pekan kedua tidak berubah setelah penolakan.
        $this->assertSame(
            $kedua->tanggal_mulai->toDateString(),
            $kedua->fresh()->tanggal_mulai->toDateString()
        );
    }

    public function test_update_pekan_menolak_status_dan_sumber_tidak_dikenal(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);
        $pekan = JadwalKbmPekan::periode(self::TA, self::SEM)->orderBy('pekan_ke')->first();

        $this->expectException(\InvalidArgumentException::class);
        $service->updatePekan($pekan->id, [
            'tanggal_mulai'   => $pekan->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $pekan->tanggal_selesai->toDateString(),
            'status'          => 'entah',
        ]);
    }

    public function test_cetak_bulanan_tanpa_pekan_tetap_mengekspansi_hari_efektif(): void
    {
        // Fitur pekan bersifat opt-in: periode tanpa pekan harus tetap tercetak.
        $data = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rekap',
            'sumber' => 'otomatis',
        ]));

        $this->assertSame('rekap', $data['format']);
        $this->assertCount(31, $data['tanggalList']);
        $this->assertGreaterThan(0, $data['ringkasan']['hari_efektif']);
        $this->assertSame(0, $data['ringkasan']['hari_draft']);
    }

    public function test_cetak_bulanan_hanya_mengekspansi_pekan_berlaku(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);

        // Semua pekan masih draft: tidak ada tanggal efektif.
        $draft = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rekap',
            'sumber' => 'otomatis',
        ]));
        $this->assertSame(0, $draft['ringkasan']['hari_efektif']);
        $this->assertGreaterThan(0, $draft['ringkasan']['hari_draft']);

        // Aktifkan satu pekan yang memuat Oktober 2026.
        $pekan = JadwalKbmPekan::periode(self::TA, self::SEM)
            ->where('tanggal_mulai', '<=', '2026-10-15')
            ->where('tanggal_selesai', '>=', '2026-10-15')
            ->first();
        $this->assertNotNull($pekan);

        $service->ubahStatus($pekan->id, 'aktif', 'test');

        $aktif = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rombel',
            'sumber' => 'otomatis',
        ]));

        $this->assertGreaterThan(0, $aktif['ringkasan']['hari_efektif']);
        $this->assertGreaterThan(0, $aktif['ringkasan']['total_jp']);

        // Hanya tanggal dalam pekan tersebut yang efektif.
        $efektif = array_values(array_filter($aktif['tanggalList'], fn ($t) => $t['is_efektif']));
        $this->assertNotEmpty($efektif);
        foreach ($efektif as $t) {
            $this->assertTrue(
                $t['tanggal'] >= $pekan->tanggal_mulai->toDateString()
                    && $t['tanggal'] <= $pekan->tanggal_selesai->toDateString(),
                "Tanggal {$t['tanggal']} di luar pekan yang diberlakukan."
            );
            $this->assertSame($pekan->pekan_ke, $t['pekan_ke']);
        }
    }

    public function test_cetak_bulanan_pekan_ujian_meniadakan_jp(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);

        $pekan = JadwalKbmPekan::periode(self::TA, self::SEM)
            ->where('tanggal_mulai', '<=', '2026-10-15')
            ->where('tanggal_selesai', '>=', '2026-10-15')
            ->first();

        $service->ubahStatus($pekan->id, 'ujian', 'test');

        $data = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rekap',
            'sumber' => 'otomatis',
        ]));

        // Pekan ujian tidak menyumbang JP efektif.
        $this->assertSame(0, $data['ringkasan']['total_jp']);
        $this->assertGreaterThan(0, $data['ringkasan']['hari_ujian']);
    }

    public function test_cetak_bulanan_default_hanya_satu_entitas(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);

        // Aktifkan seluruh pekan agar semua tanggal efektif.
        JadwalKbmPekan::periode(self::TA, self::SEM)->update(['status' => 'aktif']);

        $satu = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rombel',
            'sumber' => 'otomatis',
        ]));
        $this->assertLessThanOrEqual(1, $satu['rombels']->count(), 'Default cetak harus satu rombel agar dokumen ringan.');
        $this->assertGreaterThan(1, $satu['opsiRombel']->count(), 'Daftar opsi rombel tetap lengkap.');

        $semua = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rombel',
            'sumber' => 'otomatis',
            'semua'  => 1,
        ]));
        $this->assertTrue($semua['cetakSemua']);
        $this->assertSame($semua['opsiRombel']->count(), $semua['rombels']->count());
    }

    public function test_cetak_bulanan_pola_multi_jp_tidak_digandakan(): void
    {
        $service = app(JadwalPekanService::class);
        $service->generatePekan(self::TA, self::SEM);
        JadwalKbmPekan::periode(self::TA, self::SEM)->update(['status' => 'aktif']);

        $data = app(JadwalBulananService::class)->build(new Request([
            'bulan'  => 10,
            'tahun'  => 2026,
            'format' => 'rombel',
            'sumber' => 'otomatis',
        ]));

        // Satu baris `jadwal_kbm` multi-JP harus muncul tepat satu kali per tanggal.
        $contohTanggal = null;
        foreach ($data['tanggalList'] as $t) {
            if ($t['is_efektif'] && $t['items']->isNotEmpty()) {
                $contohTanggal = $t;
                break;
            }
        }

        $this->assertNotNull($contohTanggal, 'Harus ada minimal satu tanggal efektif berisi jadwal.');

        $ids = $contohTanggal['items']->pluck('id')->all();
        $this->assertSame(
            count($ids),
            count(array_unique($ids)),
            'Pola jadwal tidak boleh diduplikasi saat ekspansi tanggal.'
        );
    }

    public function test_route_pekan_dan_cetak_bulanan_terdaftar_dengan_permission(): void
    {
        $admin = User::query()->first();
        if (!$admin) {
            $this->markTestSkipped('Data pengguna admin tidak tersedia.');
        }

        $routes = [
            'dashboard.jadwal-kbm.cetak-bulanan' => 'GET',
            'dashboard.jadwal-kbm.pekan.generate' => 'POST',
            'dashboard.jadwal-kbm.pekan.update'   => 'PUT',
            'dashboard.jadwal-kbm.pekan.toggle'   => 'POST',
        ];

        foreach (array_keys($routes) as $name) {
            $this->assertTrue(
                app('router')->has($name),
                "Route {$name} belum terdaftar."
            );
        }

        // Mode "pekan" pada halaman jadwal harus bisa dirender.
        $resp = $this->withSession(['user' => [
            'id'          => $admin->pengguna_id,
            'pengguna_id' => $admin->pengguna_id,
            'role'        => 'admin',
        ]])->get(route('dashboard.jadwal-kbm.index', ['view_mode' => 'pekan']));

        $resp->assertStatus(200);
        $resp->assertSee('tabPekanContainer');
    }
}
