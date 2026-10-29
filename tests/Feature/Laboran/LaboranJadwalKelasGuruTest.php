<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * Penyesuaian modul Jadwal Praktik Laboran:
 *  - Dropdown Kelas dibatasi sesuai jurusan pengguna.
 *  - Dropdown Guru & Mata Pelajaran menampilkan NAMA GURU lebih dahulu.
 */
class LaboranJadwalKelasGuruTest extends LaboranTestCase
{
    public function test_kelas_pada_modal_jadwal_dibatasi_sesuai_jurusan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);

        $rombelBase = function () {
            return DB::table('rombongan_belajar')
                ->where(function ($q) {
                    $q->where('jenis_rombel', 1)
                      ->orWhere('jenis_rombel', '1')
                      ->orWhere('jenis_rombel_str', 'Kelas');
                });
        };

        $expected = $rombelBase()->where('jurusan_id', self::JUR_ATPH)
            ->pluck('rombongan_belajar_id')->map('strval')->sort()->values()->all();
        $tkjIds = $rombelBase()->where('jurusan_id', self::JUR_TKJ)
            ->pluck('rombongan_belajar_id')->map('strval')->all();

        $this->assertNotEmpty($expected, 'Butuh kelas jurusan ATPH untuk pengujian.');

        $actual = collect($res->viewData('allRombel'))
            ->pluck('rombongan_belajar_id')->map('strval')->sort()->values()->all();

        $this->assertSame($expected, $actual, 'Dropdown kelas harus hanya memuat rombel jurusan pengguna.');
        $this->assertEmpty(array_intersect($tkjIds, $actual), 'Kelas jurusan lain tidak boleh muncul.');
    }

    public function test_dropdown_guru_mapel_menampilkan_nama_guru_lebih_dahulu(): void
    {
        $row = DB::table('pembelajaran as p')
            ->join('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->whereNotNull('p.nama_mata_pelajaran')
            ->where('p.nama_mata_pelajaran', '<>', '')
            ->whereNotNull('g.nama')
            ->where('g.nama', '<>', '')
            ->select('p.pembelajaran_id', 'p.rombongan_belajar_id', 'g.nama as nama_guru')
            ->first();

        if (!$row || empty($row->rombongan_belajar_id)) {
            $this->markTestSkipped('Tidak ada data pembelajaran dengan guru untuk pengujian.');
        }

        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.jadwal.guru-mapel', [
            'rombel_id' => $row->rombongan_belajar_id,
        ]));

        $res->assertStatus(200);
        $items = $res->json('guru_mapel');
        $this->assertNotEmpty($items, 'Daftar guru & mapel tidak boleh kosong.');

        foreach ($items as $item) {
            $this->assertStringStartsWith(
                $item['nama_guru'],
                $item['label'],
                'Label dropdown harus diawali nama guru.'
            );
        }
    }

    /**
     * Filter "hanya mapel yang terjadwal di KBM" SEMENTARA DILEPAS bersama
     * `PAKSA_VALIDASI_KBM = false`. Yang diuji sekarang: parameter `hari`
     * tetap diterima dan daftar tidak berubah/berkurang karenanya.
     */
    public function test_guru_mapel_dibatasi_pada_jadwal_kbm_kelas_dan_hari(): void
    {
        $rombelId = DB::table('pembelajaran')
            ->whereNotNull('nama_mata_pelajaran')
            ->where('nama_mata_pelajaran', '<>', '')
            ->value('rombongan_belajar_id');

        if (!$rombelId) {
            $this->markTestSkipped('Tidak ada data pembelajaran untuk pengujian.');
        }

        $kbm = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombelId)
            ->where('is_active', true)
            ->where('pembelajaran_id', 'not like', 'ROUTINE_%')
            ->whereNotNull('pembelajaran_id')
            ->select('pembelajaran_id', 'hari')
            ->first();

        if (!$kbm) {
            $this->markTestSkipped('Kelas uji tidak punya jadwal KBM aktif.');
        }

        $session = $this->laboranSession(self::JUR_ATPH);

        // Tanpa `hari`: seluruh mapel pembelajaran kelas (perilaku lama).
        $semua = $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.guru-mapel', ['rombel_id' => $rombelId]))
            ->json('guru_mapel');

        // Dengan `hari`: hanya mapel yang terjadwal di KBM hari itu.
        $terfilter = $this->withSession($session)
            ->get(route('dashboard.laboran.jadwal.guru-mapel', [
                'rombel_id' => $rombelId,
                'hari'      => $kbm->hari,
            ]))
            ->json('guru_mapel');

        $this->assertNotEmpty($semua, 'Daftar tanpa filter hari tidak boleh kosong.');
        $this->assertNotEmpty($terfilter, 'Daftar dengan filter hari tidak boleh kosong.');

        // ponytail: filter KBM dilepas sementara — daftar dengan `hari` harus
        // sama dengan daftar tanpa `hari`. Kembalikan pemeriksaan "hanya mapel
        // KBM" di sini setelah `PAKSA_VALIDASI_KBM` diaktifkan lagi.
        $this->assertSame(
            count($semua),
            count($terfilter),
            'Filter KBM sedang dilepas: parameter hari tidak boleh menyaring pilihan.'
        );
    }
}
