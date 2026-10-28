<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * N. Matriks Grid jadwal laboran (pola Jadwal KBM): view_mode grid/table,
 * kolom = hari, kartu sesi memuat lokasi, endpoint guru-mapel modal simpel.
 */
class LaboranJadwalGridTest extends LaboranTestCase
{
    public function test_halaman_default_menampilkan_matriks_grid(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);
        $res->assertSee('Matriks Grid', false);
        $res->assertSee('Rekap Tabel', false);
        $res->assertSee('gridMatrixContainer', false);
        $res->assertSee('Jam Pelajaran', false);
    }

    public function test_kolom_matriks_adalah_hari_bukan_lokasi(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid']));

        $res->assertStatus(200);
        // Header kolom = hari
        $res->assertSee('Sabtu', false);
        // Kartu berisi info lokasi via teks bantu
        $res->assertSee('sesi', false);
    }

    public function test_endpoint_guru_mapel_mengembalikan_daftar_gabungan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->getJson(route('dashboard.laboran.jadwal.guru-mapel'));

        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $list = $res->json('guru_mapel');
        $this->assertIsArray($list);
        $this->assertNotEmpty($list, 'Data pembelajaran harus tersedia pada DB uji.');

        $first = $list[0];
        $this->assertArrayHasKey('pembelajaran_id', $first);
        $this->assertArrayHasKey('mata_pelajaran', $first);
        $this->assertArrayHasKey('rombel_nama', $first);
        $this->assertArrayHasKey('nama_guru', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertStringContainsString('—', $first['label']);
    }

    public function test_sesi_terjadwal_muncul_pada_matriks_jam_dan_kelas_sesuai(): void
    {
        $rombel = $this->kelasReguler();

        // 2026-11-02 = Senin, 08:00-09:30 = Jam ke-3..4
        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'     => self::JUR_ATPH,
            'ruang_lab_nama' => 'Lahan Praktik ATPH',
            'jenis_lokasi'   => 'lahan',
            'ptk_id'         => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => 'UJI-GRID-MATRIKS',
            'tanggal'        => '2026-11-02',
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '09:30',
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));

        $res->assertStatus(200);
        $res->assertSee('UJI-GRID-MATRIKS', false);
        $res->assertSee('Lahan Praktik ATPH', false);
        // Baris = jam ke-1..13, kolom = kelas; sesi 2 JP digabung via rowspan.
        $res->assertSee('Jam ke-13', false);
        $res->assertSee('Jam ke-3-4', false);
        $res->assertSee('rowspan="2"', false);
        $res->assertSee($rombel->nama, false);
        $res->assertSee('Isi', false);
    }

    public function test_mode_tabel_tetap_menampilkan_rekap(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'table']));

        $res->assertStatus(200);
        $res->assertSee('tableDataContainer', false);
        $res->assertDontSee('gridMatrixContainer', false);
    }

    // =========================================================================
    // Matriks baris = jam, kolom = kelas
    // =========================================================================

    /**
     * Rombel reguler yang punya pembelajaran DAN belum punya jadwal praktik pada
     * tanggal uji (2026-11-02), supaya data operasional tidak mengacaukan hitungan.
     */
    private function kelasReguler(): object
    {
        $rombel = DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('pembelajaran')
                    ->whereColumn('pembelajaran.rombongan_belajar_id', 'rombongan_belajar.rombongan_belajar_id');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('laboran_jadwal_penggunaan')
                    ->whereColumn('laboran_jadwal_penggunaan.rombel_id', 'rombongan_belajar.rombongan_belajar_id')
                    ->where('tanggal', '2026-11-02');
            })
            ->first();

        $this->assertNotNull($rombel, 'Butuh minimal satu rombel reguler untuk pengujian.');

        return $rombel;
    }

    /**
     * Satu sesi praktik uji pada tanggal Senin 2026-11-02 (Jam ke-3..4).
     */
    private function seedSesi(string $mapel, string $jurusanId, string $ruang, string $jenis = 'lahan'): void
    {
        $this->seedSesiDiKelas($mapel, $this->kelasReguler(), $jurusanId, $ruang, $jenis);
    }

    /** Varian yang menerima rombel eksplisit (agar dua sesi bisa satu kelas). */
    private function seedSesiDiKelas(string $mapel, object $rombel, string $jurusanId, string $ruang, string $jenis = 'lahan'): void
    {
        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'     => $jurusanId,
            'ruang_lab_nama' => $ruang,
            'jenis_lokasi'   => $jenis,
            'ptk_id'         => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'      => $rombel->rombongan_belajar_id,
            'mata_pelajaran' => $mapel,
            'tanggal'        => '2026-11-02',
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '09:30',
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    public function test_semua_kelas_digabung_tanpa_filter_jurusan(): void
    {
        $this->seedSesi('UJI-GABUNG-ATPH', self::JUR_ATPH, 'Lahan Praktik ATPH');
        $this->seedSesi('UJI-GABUNG-TKJ', self::JUR_TKJ, 'Lab Komputer 1', 'lab');

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));

        $res->assertStatus(200);
        // Berbagai jurusan tampil bersamaan (digabung), tanpa pemilih jurusan.
        $res->assertSee('UJI-GABUNG-ATPH', false);
        $res->assertSee('UJI-GABUNG-TKJ', false);
        $res->assertDontSee('Jurusan:', false);
    }

    public function test_sel_dengan_banyak_sesi_memakai_penampung_dibagi_rata(): void
    {
        // Dua sesi pada kelas + hari + jam sama (lahan boleh dipakai bersamaan).
        $rombel = $this->kelasReguler();
        $this->seedSesiDiKelas('UJI-STACK-A', $rombel, self::JUR_ATPH, 'Lahan Praktik ATPH');
        $this->seedSesiDiKelas('UJI-STACK-B', $rombel, self::JUR_ATPH, 'Lahan Praktik ATPH Blok B');

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));

        $res->assertStatus(200);
        $res->assertSee('UJI-STACK-A', false);
        $res->assertSee('UJI-STACK-B', false);
        // Kedua kartu berada dalam satu penampung yang membagi tinggi sel rata.
        $res->assertSee('jadwal-sesi-stack', false);
        $res->assertSee('data-jumlah-sesi="2"', false);

        // Kartu berisi mapel + guru + lokasi saja; jam hanya di tooltip (title).
        $html = $res->getContent();
        $this->assertStringContainsString('title="Jam ke-3-4 · 08:00-09:30"', $html);
        $this->assertStringContainsString('Lahan Praktik ATPH Blok B', $html);
    }
}
