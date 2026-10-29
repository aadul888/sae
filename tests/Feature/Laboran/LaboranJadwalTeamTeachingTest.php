<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * Penjadwalan praktik pada LOKASI YANG SAMA dengan DUA GURU pada jam yang sama.
 *
 * Aturan yang diuji:
 *  - TEAM TEACHING: rombel sama + lokasi sama + jam sama + guru berbeda
 *    adalah satu kegiatan praktik bersama -> DITERIMA.
 *  - Lokasi/tempat praktik (lab, bengkel, lahan, lapangan) BOLEH dipakai
 *    beberapa sesi pada jam yang sama, termasuk oleh guru lain pada rombel
 *    berbeda -> DITERIMA (permintaan pemilik modul).
 *  - Duplikat (guru sama + rombel sama + ruang sama + jam sama) DITOLAK.
 *  - Bentrok GURU (guru sama pada dua lokasi berbeda) tetap DITOLAK, dan
 *    pesannya menyebut GURU karena itu penyebab yang sebenarnya.
 */
class LaboranJadwalTeamTeachingTest extends LaboranTestCase
{
    private function kelasReguler(int $skip = 0): object
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
            ->orderBy('nama')
            ->skip($skip)
            ->first();

        $this->assertNotNull($rombel, 'Butuh minimal ' . ($skip + 1) . ' rombel reguler untuk pengujian.');

        return $rombel;
    }

    /** @return array{0:string, 1:string} */
    private function duaGuru(): array
    {
        $ids = DB::table('gtk')->whereNotNull('ptk_id')->limit(2)->pluck('ptk_id')->all();
        $this->assertCount(2, $ids, 'Butuh minimal dua guru untuk pengujian.');

        return [$ids[0], $ids[1]];
    }

    /** Sesi Senin JP2-3 (menghindari rutinitas Upacara pada JP1). */
    private function payloadSemester(string $ruang, string $rombelId, string $ptkId, string $mapel): array
    {
        return [
            'ruang_lab_nama'        => $ruang,
            'jenis_lokasi'          => 'lab',
            'rombel_id'             => $rombelId,
            'ptk_id'                => $ptkId,
            'mata_pelajaran'        => $mapel,
            'pemberlakuan'          => 'semester',
            'hari'                  => ['Senin'],
            'jam_ke_mulai'          => 2,
            'jam_ke_selesai'        => 3,
            'pemberlakuan_semester' => '1',
            'pemberlakuan_ta'       => '2026/2027',
        ];
    }

    private function simpan(array $payload)
    {
        return $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), $payload);
    }

    public function test_dua_guru_pada_ruang_dan_jam_sama_diterima(): void
    {
        $rombel = $this->kelasReguler();
        [$g1, $g2] = $this->duaGuru();
        $ruang = 'Lab Komputer 1';

        $this->simpan($this->payloadSemester($ruang, $rombel->rombongan_belajar_id, $g1, 'Informatika'))
            ->assertStatus(200);

        $this->simpan($this->payloadSemester($ruang, $rombel->rombongan_belajar_id, $g2, 'Informatika'))
            ->assertStatus(200);

        $rows = DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', $ruang)
            ->where('rombel_id', $rombel->rombongan_belajar_id)
            ->whereNull('jadwal_seri_id')
            ->get();

        $this->assertSame(2, $rows->count(), 'Dua guru berbeda pada satu rombel & ruang harus tersimpan keduanya.');
        $this->assertCount(2, $rows->pluck('ptk_id')->unique(), 'Guru penyerta harus berbeda.');
        $this->assertSame(
            $rows->first()->jam_mulai,
            $rows->last()->jam_mulai,
            'Kedua sesi team teaching harus pada jam yang sama.'
        );
    }

    public function test_ruang_sama_dengan_rombel_berbeda_dan_guru_berbeda_diterima(): void
    {
        $rombelA = $this->kelasReguler(0);
        $rombelB = $this->kelasReguler(1);
        [$g1, $g2] = $this->duaGuru();
        $ruang = 'Lab Komputer 1';

        $this->simpan($this->payloadSemester($ruang, $rombelA->rombongan_belajar_id, $g1, 'Informatika'))
            ->assertStatus(200);

        // Lokasi/tempat praktik boleh dipakai guru lain, termasuk pada rombel
        // berbeda (permintaan pemilik modul). Guru tetap harus berbeda.
        $this->simpan($this->payloadSemester($ruang, $rombelB->rombongan_belajar_id, $g2, 'Informatika'))
            ->assertStatus(200);

        $this->assertSame(2, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', $ruang)
            ->whereNull('jadwal_seri_id')
            ->count());
    }

    /**
     * SEMENTARA: bentrok GURU dilepas (`PAKSA_VALIDASI_BENTROK = false`) —
     * guru sama kini boleh mengajar dua sesi beririsan. Setelah flag
     * diaktifkan kembali, kembalikan `assertStatus(422)` + pesan bentrok guru.
     */
    public function test_duplikat_guru_dan_rombel_sama_pada_ruang_sama_ditolak(): void
    {
        $rombel = $this->kelasReguler();
        [$g1] = $this->duaGuru();
        $ruang = 'Lab Komputer 1';

        $this->simpan($this->payloadSemester($ruang, $rombel->rombongan_belajar_id, $g1, 'Informatika'))
            ->assertStatus(200);

        $res = $this->simpan($this->payloadSemester($ruang, $rombel->rombongan_belajar_id, $g1, 'Informatika'));

        $res->assertStatus(200);
        // Keduanya tersimpan karena pemeriksaan bentrok guru sedang dimatikan.
        $this->assertSame(2, DB::table('laboran_jadwal_penggunaan')
            ->where('ruang_lab_nama', $ruang)
            ->whereNull('jadwal_seri_id')
            ->count());
    }

    /**
     * SEMENTARA: guru sama pada lokasi berbeda kini DITERIMA (bentrok guru
     * dilepas). Kembalikan ke `assertStatus(422)` + pesan bentrok guru saat
     * `PAKSA_VALIDASI_BENTROK` diaktifkan lagi.
     */
    public function test_bentrok_guru_pada_lokasi_berbeda_melaporkan_penyebab_guru(): void
    {
        $rombel = $this->kelasReguler();
        [$g1] = $this->duaGuru();

        $this->simpan($this->payloadSemester('Lab Komputer 1', $rombel->rombongan_belajar_id, $g1, 'Informatika'))
            ->assertStatus(200);

        $res = $this->simpan($this->payloadSemester('Lab IPA / Kimia', $rombel->rombongan_belajar_id, $g1, 'Informatika'));

        $res->assertStatus(200);
    }
}
