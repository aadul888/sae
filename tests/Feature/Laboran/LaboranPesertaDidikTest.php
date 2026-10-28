<?php

namespace Tests\Feature\Laboran;

use App\Services\SimpleXlsxReader;
use Illuminate\Support\Facades\DB;

/**
 * B. Modul "Data Peserta Didik" Laboran (baca-saja, per jurusan).
 *
 * Menguji:
 *  - Cakupan jurusan pada tab Aktif & Tidak Aktif.
 *  - Halaman dapat dirender (tab aktif maupun tidak aktif).
 *  - Detail biodata: peserta didik di luar jurusan ditolak (403).
 *  - Ekspor Excel mengembalikan berkas .xlsx berisi data.
 *  - Admin melihat seluruh jurusan.
 */
class LaboranPesertaDidikTest extends LaboranTestCase
{
    private const JUR_ATPH_NAMA = 'Agribisnis Tanaman Pangan dan Hortikultura';

    protected function atphStudentId(): string
    {
        $id = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->orderBy('pd.nama')
            ->value('pd.peserta_didik_id');

        $this->assertNotNull($id, 'Data peserta didik jurusan ATPH tidak tersedia untuk pengujian.');

        return (string) $id;
    }

    protected function tkjStudentId(): string
    {
        $id = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_TKJ)
            ->orderBy('pd.nama')
            ->value('pd.peserta_didik_id');

        $this->assertNotNull($id, 'Data peserta didik jurusan TKJ tidak tersedia untuk pengujian.');

        return (string) $id;
    }

    public function test_laboran_hanya_melihat_peserta_didik_jurusannya(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $expected = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->count();

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.index', ['perPage' => 100]));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertGreaterThan(0, $list->total(), 'Laboran harus melihat data jurusannya.');

        $this->assertSame($expected, $list->total(), 'Total data harus sama dengan jumlah peserta didik jurusan ATPH.');

        // Tidak boleh ada satu pun baris dari jurusan lain.
        foreach ($list->items() as $row) {
            $this->assertSame(self::JUR_ATPH, $row->jurusan_id, 'Kebocoran data jurusan lain terdeteksi.');
        }

        $stats = $res->viewData('stats');
        $this->assertSame($expected, $stats['total']);
    }

    public function test_laboran_tidak_aktif_terbatas_jurusannya(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $expected = DB::table('peserta_didik_tidak_aktif as pta')
            ->leftJoin('rombongan_belajar as rb', 'pta.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('peserta_didik as pdx')
                    ->whereColumn('pdx.peserta_didik_id', 'pta.peserta_didik_id');
            })
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->count();

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.index', ['tab' => 'tidak_aktif']));

        $res->assertStatus(200);
        $this->assertSame('tidak_aktif', $res->viewData('tab'));

        $list = $res->viewData('list');
        $this->assertSame($expected, $list->total());

        foreach ($list->items() as $row) {
            $this->assertSame(self::JUR_ATPH, $row->jurusan_id);
        }
    }

    public function test_detail_biodata_dalam_jurusan_dapat_dibuka(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)
            ->getJson(route('dashboard.laboran.peserta-didik.show', [
                'id'  => $this->atphStudentId(),
                'tab' => 'aktif',
            ]));

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'success');
        $res->assertJsonPath('jurusan_id', self::JUR_ATPH);
    }

    public function test_detail_biodata_luar_jurusan_ditolak(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->getJson(route('dashboard.laboran.peserta-didik.show', [
                'id'  => $this->tkjStudentId(),
                'tab' => 'aktif',
            ]))
            ->assertStatus(403);
    }

    public function test_ekspor_excel_menghasilkan_berkas_xlsx(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.export', ['tab' => 'aktif']));

        $res->assertStatus(200);
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $res->headers->get('content-type'),
        );

        $file = $res->getFile()->getPathname();
        $this->assertFileExists($file);
        $this->assertGreaterThan(1000, filesize($file), 'Berkas xlsx tampak kosong.');

        // Isi berkas harus benar-benar memuat data peserta didik jurusan tersebut.
        $parsed = SimpleXlsxReader::readFile($file);
        $this->assertNotEmpty($parsed, 'Berkas xlsx tidak memuat baris data.');

        $expected = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->count();

        $this->assertCount($expected, $parsed, 'Jumlah baris ekspor tidak sesuai jumlah peserta didik jurusan.');

        // `deleteFileAfterSend()` hanya berjalan saat respons dikirim (send()),
        // yang tidak dilakukan pada pengujian -> bersihkan manual.
        @unlink($file);
    }

    public function test_ekspor_excel_tab_tidak_aktif_dan_filter_kelas(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        // Tidak aktif: seluruhnya (jurusan ATPH hanya 1 baris nyata).
        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.export', ['tab' => 'tidak_aktif']));

        $res->assertStatus(200);

        $file = $res->getFile()->getPathname();
        $parsed = SimpleXlsxReader::readFile($file);

        $expected = DB::table('peserta_didik_tidak_aktif as pta')
            ->leftJoin('rombongan_belajar as rb', 'pta.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('peserta_didik as pdx')
                    ->whereColumn('pdx.peserta_didik_id', 'pta.peserta_didik_id');
            })
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->count();

        $this->assertCount($expected, $parsed, 'Jumlah baris ekspor tab tidak aktif tidak sesuai.');
        @unlink($file);

        // Filter kelas: satu rombel ATPH harus menghasilkan baris < total.
        $rombel = (string) DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->orderBy('pd.nama_rombel')
            ->value('pd.nama_rombel');

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.export', [
                'tab'    => 'aktif',
                'rombel' => $rombel,
            ]));

        $res->assertStatus(200);

        $file = $res->getFile()->getPathname();
        $parsedFiltered = SimpleXlsxReader::readFile($file);
        $this->assertNotEmpty($parsedFiltered, 'Ekspor filter kelas tidak memuat data.');
        @unlink($file);

        $expectedFiltered = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->where('rb.jurusan_id', self::JUR_ATPH)
            ->where('pd.nama_rombel', $rombel)
            ->count();

        $this->assertCount($expectedFiltered, $parsedFiltered, 'Ekspor filter kelas mengembalikan baris di luar kelas.');
        $this->assertLessThanOrEqual(
            DB::table('peserta_didik as pd')
                ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('rb.jurusan_id', self::JUR_ATPH)
                ->count(),
            count($parsedFiltered),
            'Jumlah baris filter kelas melebihi total jurusan.',
        );
    }

    public function test_admin_melihat_seluruh_jurusan(): void
    {
        $session = $this->adminSession();

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.index', ['perPage' => 10]));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertSame(DB::table('peserta_didik')->count(), $list->total());
    }

    /**
     * Penugasan multi-jurusan (`jurusan_id` berupa string dipisah koma, mis.
     * "26010,26010475") harus diuraikan menjadi beberapa jurusan sehingga
     * laboran melihat gabungan data dari semua jurusan yang ditugaskan.
     */
    public function test_penugasan_multi_jurusan_menggabungkan_data(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH . ',' . self::JUR_TKJ);

        $expected = DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->whereIn('rb.jurusan_id', [self::JUR_ATPH, self::JUR_TKJ])
            ->count();

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.index', ['perPage' => 2000]));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertSame($expected, $list->total(), 'Data jurusan gabungan tidak sesuai.');

        $seen = [];
        foreach ($list->items() as $row) {
            $seen[$row->jurusan_id] = true;
        }
        $this->assertArrayHasKey(self::JUR_ATPH, $seen, 'Jurusan pertama tidak terwakili.');
        $this->assertArrayHasKey(self::JUR_TKJ, $seen, 'Jurusan kedua tidak terwakili.');

        // Detail dari jurusan kedua (bukan jurusan utama) tetap boleh dibuka.
        $this->withSession($session)
            ->getJson(route('dashboard.laboran.peserta-didik.show', [
                'id'  => $this->tkjStudentId(),
                'tab' => 'aktif',
            ]))
            ->assertStatus(200);
    }

    /**
     * Struktur halaman harus mengikuti acuan `manajemen-data/peserta-didik-aktif`
     * (banner, kartu statistik, tab, datatable terpadu, modal biodata & ekspor).
     */
    public function test_struktur_halaman_sesuai_acuan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.peserta-didik.index'));

        $res->assertStatus(200);

        foreach ([
            'id="tableDataContainer"',
            'class="datatable-toolbar"',
            'id="liveSearch"',
            'id="filterRombel"',
            'id="filterGender"',
            'id="perPageSelect"',
            'id="btnOpenExportPd"',
            'id="btnOpenExportPdToolbar"',
            'id="exportPdModal"',
            'id="formExportPd"',
            'id="biodataModal"',
            'id="bioStatusKeluarRow"',
            'id="bioMapelList"',
            'btn-detail-pd',
            'periode-nav-desktop',
            'periode-nav-mobile',
            'dash-stat-grid',
            'js/laboran-peserta-didik.js',
        ] as $needle) {
            $res->assertSee($needle, false);
        }

        // Hanya aksi detail yang tersedia: tidak boleh ada tombol tambah/ubah/hapus.
        $res->assertDontSee('id="btnTambahPesertaDidik"', false);
        $res->assertDontSee('btn-edit-pd', false);
        $res->assertDontSee('btn-hapus-pd', false);

        // Menu sidebar muncul tepat sebelum "Analisis & Kebutuhan".
        $html = $res->getContent();
        $posMenuPd = strpos($html, 'laboran/peserta-didik');
        $posAnalisis = strpos($html, 'laboran/kebutuhan');

        $this->assertNotFalse(
            $posMenuPd,
            'Menu Data Peserta Didik tidak dirender pada sidebar.',
        );
        $this->assertNotFalse($posAnalisis);
        $this->assertLessThan(
            $posAnalisis,
            $posMenuPd,
            'Menu "Data Peserta Didik" harus berada sebelum "Analisis & Kebutuhan".',
        );
    }
}
