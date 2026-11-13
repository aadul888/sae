<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * C. Modul "Log Presensi" Laboran (baca-saja, per jurusan).
 *
 * Menguji:
 *  - Hanya baris presensi dari rombel jurusan Laboran yang tampil.
 *  - Statistik tanggal terpilih mengikuti cakupan jurusan.
 *  - Filter rombel di luar cakupan diabaikan (bukan error).
 *  - Tanggal tidak valid jatuh ke hari ini.
 *  - Struktur halaman & posisi menu sidebar.
 *
 * PENTING: tabel `presensi_harian` bersifat operasional dan TIDAK dibersihkan
 * oleh LaboranTestCase. Test ini hanya MEMBACA presensi yang sudah ada dan
 * memilih tanggal yang benar-benar memuat data ATPH.
 */
class LaboranLogPresensiTest extends LaboranTestCase
{
    /**
     * Tanggal terbaru yang memiliki baris presensi jurusan ATPH.
     */
    private function atphLogDate(): string
    {
        $date = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->max('ph.tanggal');

        $this->assertNotNull($date, 'Data presensi jurusan ATPH tidak tersedia untuk pengujian.');

        return (string) $date;
    }

    private function atphLogCount(string $date, string $rombelId = '', string $status = ''): int
    {
        $query = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('ph.tanggal', $date);

        if ($rombelId !== '') {
            $query->where('ph.rombongan_belajar_id', $rombelId);
        }
        if ($status !== '') {
            $query->where('ph.status', $status);
        }

        return (int) $query->count('ph.id');
    }

    /** @return array<int, string> */
    private function atphRombelIds(): array
    {
        return DB::table('rombongan_belajar')
            ->where('jurusan_id', self::JUR_ATPH)
            ->pluck('rombongan_belajar_id')
            ->all();
    }

    public function test_laboran_hanya_melihat_log_presensi_jurusannya(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();
        $expected = $this->atphLogCount($tanggal);
        $this->assertGreaterThan(0, $expected);

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal' => $tanggal,
            'perPage' => 100,
        ]));

        $res->assertStatus(200);

        $logs = $res->viewData('logs');
        $this->assertSame($expected, $logs->total(), 'Total log harus sama dengan presensi jurusan ATPH pada tanggal tersebut.');

        $allowed = $this->atphRombelIds();
        foreach ($logs->items() as $row) {
            $this->assertContains(
                (string) $row->rombongan_belajar_id,
                $allowed,
                'Kebocoran presensi jurusan lain terdeteksi.',
            );
        }

        // Rombel filter hanya berisi kelas reguler milik ATPH.
        foreach ($res->viewData('rombelList') as $r) {
            $this->assertContains((string) $r->rombongan_belajar_id, $allowed);
        }
    }

    public function test_statistik_mengikuti_cakupan_jurusan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal' => $tanggal,
        ]));

        $res->assertStatus(200);
        $stats = $res->viewData('stats');

        $totalSiswa = (int) DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->count('pd.peserta_didik_id');

        $this->assertSame($totalSiswa, $stats['total']);
        $this->assertSame($this->atphLogCount($tanggal, '', 'H'), $stats['hadir']);
        $this->assertSame($this->atphLogCount($tanggal, '', 'T'), $stats['terlambat']);
        $this->assertSame($this->atphLogCount($tanggal, '', 'I'), $stats['izin']);
        $this->assertSame($this->atphLogCount($tanggal, '', 'S'), $stats['sakit']);
        $this->assertSame($this->atphLogCount($tanggal, '', 'A'), $stats['alpha']);
    }

    public function test_filter_status_dan_rombel_dalam_cakupan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();

        $rombelId = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('ph.tanggal', $tanggal)
            ->orderBy('ph.rombongan_belajar_id')
            ->value('ph.rombongan_belajar_id');
        $this->assertNotNull($rombelId);

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal'   => $tanggal,
            'rombel_id' => $rombelId,
            'status'    => 'H',
        ]));

        $res->assertStatus(200);
        $this->assertSame((string) $rombelId, $res->viewData('rombelId'));
        $this->assertSame('H', $res->viewData('status'));

        $expected = $this->atphLogCount($tanggal, (string) $rombelId, 'H');
        $this->assertSame($expected, $res->viewData('logs')->total());

        foreach ($res->viewData('logs')->items() as $row) {
            $this->assertSame('H', $row->status);
            $this->assertSame((string) $rombelId, (string) $row->rombongan_belajar_id);
        }
    }

    public function test_filter_rombel_luar_cakupan_diabaikan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();

        $tkjRombel = DB::table('rombongan_belajar')
            ->where('jurusan_id', self::JUR_TKJ)
            ->where('jenis_rombel', '1')
            ->value('rombongan_belajar_id');

        $this->assertNotNull($tkjRombel, 'Rombel TKJ tidak tersedia untuk pengujian.');

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal'   => $tanggal,
            'rombel_id' => $tkjRombel,
            'perPage'   => 100,
        ]));

        $res->assertStatus(200);
        $this->assertSame('', $res->viewData('rombelId'), 'Rombel luar cakupan harus diabaikan.');
        $this->assertSame($this->atphLogCount($tanggal), $res->viewData('logs')->total());
    }

    public function test_tanggal_tidak_valid_jatuh_ke_hari_ini(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal' => '2026-02-30',
        ]));

        $res->assertStatus(200);
        $this->assertSame(now()->toDateString(), $res->viewData('tanggal'));

        // perPage di luar daftar valid kembali ke 15.
        $res2 = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'perPage' => 7,
            'status'  => 'Z',
        ]));
        $res2->assertStatus(200);
        $this->assertSame(15, $res2->viewData('perPage'));
        $this->assertSame('', $res2->viewData('status'));
    }

    public function test_struktur_halaman_dan_posisi_menu(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi', [
            'tanggal' => $this->atphLogDate(),
        ]));

        $res->assertStatus(200);

        foreach ([
            'id="tableDataContainer"',
            'class="datatable-toolbar"',
            'id="liveSearch"',
            'id="filterRombel"',
            'id="filterStatus"',
            'id="filterTanggal"',
            'id="perPageSelect"',
            'id="modalFotoSnapshot"',
            'id="imgFotoSnapshot"',
            'id="fotoSnapshotTitle"',
            'table-presensi-log',
            'js/laboran-log-presensi.js',
        ] as $needle) {
            $res->assertSee($needle, false);
        }

        // Tombol snapshot hanya muncul bila ada foto pada tanggal tsb.
        $hasSnapshot = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('ph.tanggal', $this->atphLogDate())
            ->where(function ($q) {
                $q->whereNotNull('ph.foto_masuk')->orWhereNotNull('ph.foto_pulang');
            })
            ->exists();

        if ($hasSnapshot) {
            $res->assertSee('btn-preview-snapshot', false);
        }

        // Tidak ada aksi tulis pada modul baca-saja.
        $res->assertDontSee('id="btnTambahLogPresensi"', false);

        // Menu "Log Presensi" berada setelah "Data Peserta Didik" dan sebelum
        // "Analisis & Kebutuhan" pada sidebar.
        $html = $res->getContent();
        $posPd = strpos($html, '<span class="nav-label">Data Peserta Didik</span>');
        $posLog = strpos($html, '<span class="nav-label">Log Presensi</span>');
        $posKebutuhan = strpos($html, '<span class="nav-label">Analisis &amp; Kebutuhan</span>');

        $this->assertNotFalse($posPd);
        $this->assertNotFalse($posLog, 'Menu "Log Presensi" tidak dirender pada sidebar.');
        $this->assertNotFalse($posKebutuhan);
        $this->assertLessThan($posLog, $posPd, 'Menu "Log Presensi" harus setelah "Data Peserta Didik".');
        $this->assertLessThan($posKebutuhan, $posLog, 'Menu "Log Presensi" harus sebelum "Analisis & Kebutuhan".');
    }

    public function test_cetak_laporan_presensi_ringkasan_dan_lengkap(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();

        // 1. Mode Ringkasan (default)
        $res = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tanggal' => $tanggal,
            'mode'    => 'ringkasan',
        ]));

        $res->assertStatus(200);
        $res->assertSee('LAPORAN PRESENSI PESERTA DIDIK', false);
        $res->assertSee('Rekapitulasi Presensi per Kelas', false);
        $this->assertSame('ringkasan', $res->viewData('mode'));
        $this->assertNotEmpty($res->viewData('rekap'));

        // 2. Mode Lengkap
        $resLengkap = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tanggal' => $tanggal,
            'mode'    => 'lengkap',
        ]));

        $resLengkap->assertStatus(200);
        $resLengkap->assertSee('Daftar Rinci Presensi Peserta Didik', false);
        $this->assertSame('lengkap', $resLengkap->viewData('mode'));
        $this->assertSame($this->atphLogCount($tanggal), $resLengkap->viewData('items')->count());

        // Pastikan tidak ada data jurusan lain pada items cetak
        $allowed = $this->atphRombelIds();
        foreach ($resLengkap->viewData('items') as $row) {
            $this->assertContains((string) $row->rombongan_belajar_id, $allowed);
        }

        // 3. Cetak per Rombel / Kelas tertentu
        $rombelId = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('ph.tanggal', $tanggal)
            ->value('ph.rombongan_belajar_id');

        $resPerKelas = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tanggal'   => $tanggal,
            'rombel_id' => $rombelId,
            'mode'      => 'lengkap',
        ]));

        $resPerKelas->assertStatus(200);
        $this->assertNotNull($resPerKelas->viewData('rombelObj'));
        $this->assertSame((string) $rombelId, (string) $resPerKelas->viewData('rombelId'));
        $this->assertSame($this->atphLogCount($tanggal, (string) $rombelId), $resPerKelas->viewData('items')->count());
        foreach ($resPerKelas->viewData('items') as $row) {
            $this->assertSame((string) $rombelId, (string) $row->rombongan_belajar_id);
        }
    }

    public function test_cetak_laporan_presensi_per_murid_dan_multi_periode(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $tanggal = $this->atphLogDate();

        // Ambil salah satu siswa ATPH yang memiliki log presensi
        $logSample = DB::table('presensi_harian as ph')
            ->join('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('ph.tanggal', $tanggal)
            ->select('ph.peserta_didik_id', 'pd.nama', 'ph.rombongan_belajar_id')
            ->first();

        $this->assertNotNull($logSample);

        // 1. Endpoint siswa-options JSON ter-scope jurusan
        $resOptions = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.siswa-options', [
            'rombel_id' => $logSample->rombongan_belajar_id,
        ]));
        $resOptions->assertStatus(200);
        $resOptions->assertJsonFragment(['peserta_didik_id' => $logSample->peserta_didik_id]);

        // 2. Cetak per Murid - Periode Harian
        $resMuridHari = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tipe_laporan'     => 'murid',
            'peserta_didik_id' => $logSample->peserta_didik_id,
            'periode'          => 'hari',
            'tanggal'          => $tanggal,
        ]));
        $resMuridHari->assertStatus(200);
        $resMuridHari->assertSee('LAPORAN REKAPITULASI PRESENSI PESERTA DIDIK', false);
        $resMuridHari->assertSee($logSample->nama, false);
        $this->assertSame('murid', $resMuridHari->viewData('tipeLaporan'));
        $this->assertNotNull($resMuridHari->viewData('siswa'));
        $this->assertSame($logSample->peserta_didik_id, $resMuridHari->viewData('siswa')->peserta_didik_id);

        // 3. Cetak per Murid - Periode Bulanan
        $resMuridBulan = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tipe_laporan'     => 'murid',
            'peserta_didik_id' => $logSample->peserta_didik_id,
            'periode'          => 'bulan',
            'bulan'            => (int) date('m', strtotime($tanggal)),
            'tahun'            => (int) date('Y', strtotime($tanggal)),
        ]));
        $resMuridBulan->assertStatus(200);
        $resMuridBulan->assertSee('Hari Efektif (HEB)', false);
        $this->assertArrayHasKey('hari_efektif_berjalan', $resMuridBulan->viewData('stats'));

        // 4. Cetak per Kelas - Periode Bulanan & Semester
        $resKelasBulan = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tipe_laporan' => 'kelas',
            'rombel_id'    => $logSample->rombongan_belajar_id,
            'periode'      => 'bulan',
            'bulan'        => (int) date('m', strtotime($tanggal)),
            'tahun'        => (int) date('Y', strtotime($tanggal)),
            'mode'         => 'lengkap',
        ]));
        $resKelasBulan->assertStatus(200);
        $this->assertSame('kelas', $resKelasBulan->viewData('tipeLaporan'));
        $this->assertNotEmpty($resKelasBulan->viewData('range'));

        $resKelasSemester = $this->withSession($session)->get(route('dashboard.laboran.log-presensi.cetak', [
            'tipe_laporan' => 'kelas',
            'periode'      => 'semester',
            'semester'     => '1',
            'tahun_ajaran' => '2025/2026',
            'mode'         => 'ringkasan',
        ]));
        $resKelasSemester->assertStatus(200);
        $this->assertStringContainsString('Semester Ganjil', $resKelasSemester->viewData('range')['label']);
    }
}
