<?php

namespace Tests\Feature\Laboran;

use App\Models\JadwalPengaturan;
use App\Services\LaboranJadwalService;
use Illuminate\Support\Facades\DB;

/**
 * Jam praktik harus MENGIKUTI jam mapel pada Jadwal KBM:
 *  - endpoint guru & mapel menyertakan jam KBM per mapel,
 *  - jam tersedia / sisa JP ikut dilaporkan untuk ditampilkan di dropdown,
 *  - pilihan "Jam ke-N s/d Jam ke-M" dibatasi pada jam mapel tersebut.
 */
class LaboranJadwalJamKbmTest extends LaboranTestCase
{
    private function service(): LaboranJadwalService
    {
        return new LaboranJadwalService();
    }

    /**
     * Ambil satu baris KBM aktif (sumber yang berlaku) yang punya rentang jam.
     */
    private function kbmContoh(): ?object
    {
        return DB::table('jadwal_kbm')
            ->where('sumber', $this->service()->sumberKbmAktif())
            ->where('is_active', true)
            ->where('pembelajaran_id', 'not like', 'ROUTINE_%')
            ->whereNotNull('pembelajaran_id')
            ->where('jam_ke_mulai', '>', 0)
            ->orderBy('rombongan_belajar_id')
            ->orderBy('jam_ke_mulai')
            ->select('pembelajaran_id', 'rombongan_belajar_id', 'hari', 'jam_ke_mulai', 'jam_ke_selesai')
            ->first();
    }

    public function test_peta_jam_kbm_mengembalikan_rentang_jam_mapel(): void
    {
        $kbm = $this->kbmContoh();
        if (!$kbm) {
            $this->markTestSkipped('Tidak ada Jadwal KBM aktif untuk pengujian.');
        }

        $peta = $this->service()->jamKeMapelKbm($kbm->rombongan_belajar_id, $kbm->hari);
        $this->assertNotEmpty($peta, 'Peta jam per mapel tidak boleh kosong.');

        $kunci = 'id:' . $kbm->pembelajaran_id;
        $this->assertArrayHasKey($kunci, $peta, 'Mapel dari KBM harus ada di peta jam.');

        $jam = $peta[$kunci]['jam_ke'];
        $this->assertNotEmpty($jam);
        $this->assertContains((int) $kbm->jam_ke_mulai, $jam);
        $this->assertContains((int) $kbm->jam_ke_selesai, $jam);

        // Rentang harus berurutan penuh (mis. JP2-4 => [2,3,4]).
        $this->assertSame(
            range((int) $kbm->jam_ke_mulai, (int) $kbm->jam_ke_selesai),
            $jam,
            'Jam KBM harus dimuat sebagai rentang utuh.'
        );
    }

    public function test_endpoint_guru_mapel_menyertakan_jam_kbm_dan_sisa_jp(): void
    {
        $kbm = $this->kbmContoh();
        if (!$kbm) {
            $this->markTestSkipped('Tidak ada Jadwal KBM aktif untuk pengujian.');
        }

        $res = $this->withSession($this->laboranSession(self::JUR_ATPH))
            ->get(route('dashboard.laboran.jadwal.guru-mapel', [
                'rombel_id' => $kbm->rombongan_belajar_id,
                'hari'      => $kbm->hari,
            ]));

        $res->assertStatus(200);
        $items = $res->json('guru_mapel');
        $this->assertNotEmpty($items, 'Daftar guru & mapel tidak boleh kosong.');

        foreach ($items as $item) {
            $this->assertArrayHasKey('jam_ke', $item, 'Setiap item wajib menyertakan jam KBM.');
            $this->assertArrayHasKey('jam_tersedia', $item);
            $this->assertArrayHasKey('jp_sisa', $item);
            $this->assertArrayHasKey('kuota_jp', $item);
        }

        // Label dropdown memuat jumlah JP teralokasi / total (permintaan pemilik
        // modul), sedangkan detail jam tersedia ada di tooltip.
        $berlabel = collect($items)->firstWhere('pembelajaran_id', $kbm->pembelajaran_id);
        $this->assertNotNull($berlabel, 'Mapel contoh harus muncul di daftar.');
        $this->assertMatchesRegularExpression(
            '/\(\d+ JP \/ \d+ JP\)$/',
            $berlabel['label'],
            'Label harus berakhir dengan format "(teralokasi JP / total JP)".'
        );
        $this->assertStringContainsString('Jam tersedia', (string) ($berlabel['tooltip'] ?? ''));
    }

    public function test_jam_tersedia_berkurang_setelah_praktik_dijadwalkan(): void
    {
        $kbm = $this->kbmContoh();
        if (!$kbm) {
            $this->markTestSkipped('Tidak ada Jadwal KBM aktif untuk pengujian.');
        }

        $slots = JadwalPengaturan::getSlots($kbm->hari);
        $jp = (int) $kbm->jam_ke_mulai;
        if (empty($slots[$jp]['schedulable'])) {
            $this->markTestSkipped('Slot JP awal KBM bukan slot yang dapat dijadwalkan.');
        }

        $tanggal = '2026-11-02';
        $sebelum = $this->service()->ringkasanKuotaPerMapelKbm(
            $kbm->rombongan_belajar_id,
            $kbm->hari,
            [$kbm->pembelajaran_id],
            $tanggal
        )[$kbm->pembelajaran_id];

        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'     => (string) (DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $kbm->rombongan_belajar_id)
                ->value('jurusan_id') ?? ''),
            'ruang_lab_nama' => 'Lahan Praktik Uji Jam KBM',
            'jenis_lokasi'   => 'lahan',
            'ptk_id'         => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'      => $kbm->rombongan_belajar_id,
            'pembelajaran_id' => $kbm->pembelajaran_id,
            'mata_pelajaran' => 'Uji Jam KBM',
            'tanggal'        => $tanggal,
            'jam_mulai'      => $slots[$jp]['jam_mulai'],
            'jam_selesai'    => $slots[$jp]['jam_selesai'],
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $sesudah = $this->service()->ringkasanKuotaPerMapelKbm(
            $kbm->rombongan_belajar_id,
            $kbm->hari,
            [$kbm->pembelajaran_id],
            $tanggal
        )[$kbm->pembelajaran_id];

        $this->assertSame(1, $sesudah['terpakai'], 'JP praktik yang tersimpan harus terhitung terpakai.');
        $this->assertSame($sebelum['sisa'] - 1, $sesudah['sisa'], 'Sisa JP berkurang satu.');
        $this->assertNotContains($jp, $sesudah['jam_tersedia'], 'Jam yang sudah dipakai tidak boleh tersedia lagi.');
        $this->assertLessThan(
            count($sebelum['jam_tersedia']),
            count($sesudah['jam_tersedia']),
            'Daftar jam tersedia harus menyusut.'
        );
    }

    public function test_kuota_mengikuti_sumber_kbm_yang_berlaku(): void
    {
        $svc = $this->service();
        $sumber = $svc->sumberKbmAktif();

        $kbm = DB::table('jadwal_kbm')
            ->where('sumber', $sumber)
            ->where('is_active', true)
            ->where('pembelajaran_id', 'not like', 'ROUTINE_%')
            ->whereNotNull('pembelajaran_id')
            ->where('jam_ke_mulai', '>', 0)
            ->select('pembelajaran_id', 'rombongan_belajar_id', 'hari')
            ->first();

        if (!$kbm) {
            $this->markTestSkipped('Tidak ada Jadwal KBM aktif untuk pengujian.');
        }

        $kuota = $svc->kuotaJpMapelKbm($kbm->rombongan_belajar_id, $kbm->hari, $kbm->pembelajaran_id);
        $this->assertGreaterThan(0, $kuota, 'Kuota JP mapel harus terbaca dari sumber KBM yang berlaku.');

        // Mapel yang TIDAK ada di KBM pada hari itu tidak punya kuota.
        $lain = DB::table('pembelajaran')
            ->where('rombongan_belajar_id', $kbm->rombongan_belajar_id)
            ->where('pembelajaran_id', '<>', $kbm->pembelajaran_id)
            ->value('pembelajaran_id');

        if ($lain) {
            $adaDiHariItu = DB::table('jadwal_kbm')
                ->where('rombongan_belajar_id', $kbm->rombongan_belajar_id)
                ->where('pembelajaran_id', $lain)
                ->where('hari', $kbm->hari)
                ->where('sumber', $sumber)
                ->where('is_active', true)
                ->exists();

            if (!$adaDiHariItu) {
                $this->assertSame(
                    0,
                    $svc->kuotaJpMapelKbm($kbm->rombongan_belajar_id, $kbm->hari, $lain),
                    'Mapel di luar KBM hari itu tidak boleh punya kuota.'
                );
            }
        }
    }
}
