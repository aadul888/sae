<?php

namespace Tests\Feature\Laboran;

use App\Models\JadwalPengaturan;
use Illuminate\Support\Facades\DB;

/**
 * Validasi baru penjadwalan praktik Laboran:
 *  1. Jam praktik tidak boleh menimpa rutinitas KBM (Upacara/Pembiasaan/Istirahat).
 *  2. Total JP praktik per mapel tidak boleh melebihi JP mapel di Jadwal KBM
 *     pada hari yang sama, dan mapel harus punya KBM pada hari tsb.
 */
class LaboranJadwalValidasiTest extends LaboranTestCase
{
    private function kelasReguler(): object
    {
        $rombel = DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
            })
            ->first();

        $this->assertNotNull($rombel, 'Butuh rombel reguler untuk pengujian.');

        return $rombel;
    }

    /**
     * Rentang jam untuk sejumlah JP berurutan yang seluruhnya dapat
     * dijadwalkan (tidak menyeberangi jam rutinitas seperti Istirahat).
     *
     * @return array{mulai:string,selesai:string}|null
     */
    private function rentangJp(string $hari, int $jp): ?array
    {
        if ($jp <= 0) {
            return null;
        }

        $runs = [];
        $run = [];
        foreach (JadwalPengaturan::getSlots($hari) as $slot) {
            if (!empty($slot['schedulable'])) {
                $run[] = $slot;
                continue;
            }
            if (!empty($run)) {
                $runs[] = $run;
                $run = [];
            }
        }
        if (!empty($run)) {
            $runs[] = $run;
        }

        foreach ($runs as $r) {
            if (count($r) >= $jp) {
                return ['mulai' => $r[0]['mulai'], 'selesai' => $r[$jp - 1]['selesai']];
            }
        }

        return null;
    }

    /**
     * Mapel dengan KBM pada hari tertentu yang memungkinkan pengujian kuota:
     * rentang untuk tepat `jp` DAN `jp + 1` harus berada dalam satu blok jam
     * yang dapat dijadwalkan (tidak menyeberangi Istirahat/Upacara).
     *
     * @return array{pembelajaran_id:string,jp:int}|null
     */
    private function mapelDenganKuota(object $rombel, string $hari): ?array
    {
        // Hanya sumber KBM yang sedang diberlakukan yang menjadi acuan kuota
        // (jadwal KBM bisa berasal dari penjadwal otomatis maupun input manual).
        $sumberAktif = (new \App\Services\LaboranJadwalService())->sumberKbmAktif();

        $rows = DB::table('jadwal_kbm')
            ->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->where('sumber', $sumberAktif)
            ->where('is_active', true)
            ->where('hari', $hari)
            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
            ->selectRaw('pembelajaran_id, SUM(GREATEST(1, jam_ke_selesai - jam_ke_mulai + 1)) as jp')
            ->groupBy('pembelajaran_id')
            ->orderBy('jp')
            ->get();

        foreach ($rows as $row) {
            $jp = (int) $row->jp;
            if ($this->rentangJp($hari, $jp) !== null && $this->rentangJp($hari, $jp + 1) !== null) {
                return ['pembelajaran_id' => $row->pembelajaran_id, 'jp' => $jp];
            }
        }

        return null;
    }

    public function test_jam_praktik_menimpa_upacara_ditolak(): void
    {
        $rombel = $this->kelasReguler();
        $slotUpacara = JadwalPengaturan::getSlots('Senin')[1] ?? null;
        $this->assertNotNull($slotUpacara, 'Slot JP1 Senin harus tersedia.');

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi' => 'lahan',
                'rombel_id' => $rombel->rombongan_belajar_id,
                'pemberlakuan' => 'sekali',
                'tanggal' => '2026-11-02', // Senin
                'jam_mulai' => $slotUpacara['jam_mulai'],
                'jam_selesai' => $slotUpacara['jam_selesai'],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('menimpa', $res->json('message'));
    }

    public function test_jam_praktik_di_luar_rutinitas_diterima(): void
    {
        $rombel = $this->kelasReguler();
        $slot = JadwalPengaturan::getSlots('Senin')[2] ?? null;
        $this->assertNotNull($slot, 'Slot JP2 Senin harus tersedia.');

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi' => 'lahan',
                'rombel_id' => $rombel->rombongan_belajar_id,
                'pemberlakuan' => 'sekali',
                'tanggal' => '2026-11-02', // Senin
                'jam_mulai' => $slot['jam_mulai'],
                'jam_selesai' => $slot['jam_selesai'],
            ])
            ->assertStatus(200);
    }

    /**
     * SEMENTARA: blokir kuota JP dilepas (`PAKSA_VALIDASI_KBM = false`).
     * Praktik melebihi kuota mapel kini DITERIMA. Setelah flag diaktifkan
     * kembali, ubah kembali menjadi `assertStatus(422)` + pesan "melebihi".
     */
    public function test_praktik_melebihi_kuota_jp_mapel_diterima_saat_validasi_kbm_dilepas(): void
    {
        $rombel = $this->kelasReguler();

        // Mapel + hari yang punya KBM (mis. Selasa: tanpa rutinitas pagi).
        $kbm = $this->mapelDenganKuota($rombel, 'Selasa');
        $this->assertNotNull($kbm, 'Butuh mapel dengan KBM hari Selasa untuk pengujian.');

        $kuota = $kbm['jp'];
        $rentang = $this->rentangJp('Selasa', $kuota + 1);
        $this->assertNotNull($rentang, 'Rentang melebihi kuota harus tersedia dalam satu blok yang dapat dijadwalkan.');

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi' => 'lahan',
                'pembelajaran_id' => $kbm['pembelajaran_id'],
                'rombel_id' => $rombel->rombongan_belajar_id,
                'pemberlakuan' => 'sekali',
                'tanggal' => '2026-11-03', // Selasa
                'jam_mulai' => $rentang['mulai'],
                'jam_selesai' => $rentang['selesai'],
            ])
            ->assertStatus(200);
    }

    public function test_praktik_sesuai_kuota_jp_mapel_diterima(): void
    {
        $rombel = $this->kelasReguler();

        $kbm = $this->mapelDenganKuota($rombel, 'Selasa');
        $this->assertNotNull($kbm, 'Butuh mapel dengan KBM hari Selasa untuk pengujian.');

        $rentang = $this->rentangJp('Selasa', $kbm['jp']);
        $this->assertNotNull($rentang, 'Rentang sesuai kuota harus tersedia dalam satu blok yang dapat dijadwalkan.');

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi' => 'lahan',
                'pembelajaran_id' => $kbm['pembelajaran_id'],
                'rombel_id' => $rombel->rombongan_belajar_id,
                'pemberlakuan' => 'sekali',
                'tanggal' => '2026-11-03', // Selasa
                'jam_mulai' => $rentang['mulai'],
                'jam_selesai' => $rentang['selesai'],
            ])
            ->assertStatus(200);
    }

    /**
     * Tanggal yang jatuh pada hari tanpa KBM (Minggu) harus ditolak dengan
     * pesan yang jelas, bukan divalidasi memakai slot hari lain.
     */
    public function test_tanggal_hari_libur_ditolak_dengan_pesan_jelas(): void
    {
        $rombel = $this->kelasReguler();

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi' => 'lahan',
                'rombel_id' => $rombel->rombongan_belajar_id,
                'pemberlakuan' => 'sekali',
                'tanggal' => '2026-11-01', // Minggu
                'jam_mulai' => '07:00',
                'jam_selesai' => '08:30',
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak memiliki jadwal KBM', $res->json('message'));
    }
}
