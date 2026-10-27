<?php

namespace Tests\Feature\Laboran;

use Illuminate\Support\Facades\DB;

/**
 * P. Matriks jadwal: seluruh hari aktif diinput + rutinitas KBM
 * (Upacara / Pembiasaan / Istirahat) tampil di matriks & slot per hari.
 */
class LaboranJadwalSemuaHariTest extends LaboranTestCase
{
    public function test_matriks_menampilkan_sesi_semua_hari(): void
    {
        $rombel = DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
        })->first();
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        // Selasa 2026-11-03 & Rabu 2026-11-04
        foreach ([['2026-11-03', 'UJI-SELASA'], ['2026-11-04', 'UJI-RABU']] as [$tgl, $mapel]) {
            DB::table('laboran_jadwal_penggunaan')->insert([
                'jurusan_id'     => self::JUR_ATPH,
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'jenis_lokasi'   => 'lahan',
                'ptk_id'         => $ptk,
                'rombel_id'      => $rombel->rombongan_belajar_id,
                'mata_pelajaran' => $mapel,
                'tanggal'        => $tgl,
                'jam_mulai'      => '08:00',
                'jam_selesai'    => '09:30',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid']));

        $res->assertStatus(200);
        $res->assertSee('UJI-SELASA', false);
        $res->assertSee('UJI-RABU', false);
        $res->assertSee('Jumat: ', false);
    }

    public function test_rutinitas_upacara_pembiasaan_istirahat_tampil_di_matriks(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid']));

        $res->assertStatus(200);
        $res->assertSee('Upacara', false);
        $res->assertSee('Pembiasaan', false);
        $res->assertSee('Istirahat', false);
    }

    public function test_slot_endpoint_mengembalikan_slot_per_hari(): void
    {
        $res = $this->withSession($this->adminSession())
            ->getJson(route('dashboard.laboran.jadwal.slots', ['tanggal' => '2026-11-06']));

        $res->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame('Jumat', $res->json('hari'));

        $perHari = $res->json('per_hari');
        $this->assertArrayHasKey('Senin', $perHari);
        $this->assertArrayHasKey('Jumat', $perHari);

        // Jumat punya JP lebih sedikit daripada Senin (Pengaturan KBM)
        $this->assertLessThan(count($perHari['Senin']), count($perHari['Jumat']));

        // Slot Jumat mengandung blok istirahat/upacara/pembiasaan sesuai konfigurasi
        $jumat = $res->json('per_hari.Jumat');
        $this->assertNotEmpty($jumat);
    }

    public function test_jam_ke_minggu_menghasilkan_jam_mulai_dari_slot_hari(): void
    {
        // Jumat 2026-11-06 (JP lebih sedikit) — jam ke-1 harus dari slot Jumat
        $rombel = DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
        })->first();
        $pemb = DB::table('pembelajaran')->where('rombongan_belajar_id', $rombel->rombongan_belajar_id)
            ->whereNotNull('nama_mata_pelajaran')->orderBy('nama_mata_pelajaran')->first();

        $slotsJumat = \App\Models\JadwalPengaturan::getSlots('Jumat');
        $this->assertNotEmpty($slotsJumat, 'Slot Jumat harus tersedia.');
        $slot1 = $slotsJumat[1];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama'  => 'Lahan Praktik ATPH',
                'jenis_lokasi'    => 'lahan',
                'pembelajaran_id' => $pemb->pembelajaran_id,
                'rombel_id'       => $rombel->rombongan_belajar_id,
                'pemberlakuan'    => 'mingguan',
                'pemberlakuan_minggu' => 1,
                'hari'            => ['Jumat'],
                'tanggal'         => '2026-11-06',
                'jam_ke_mulai'    => 1,
                'jam_ke_selesai'  => 1,
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_jadwal_penggunaan')
            ->where('tanggal', '2026-11-06')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame(substr($slot1['jam_mulai'], 0, 5), substr((string) $row->jam_mulai, 0, 5), 'Jam mulai harus dari slot Jumat.');
        $this->assertSame(substr($slot1['jam_selesai'], 0, 5), substr((string) $row->jam_selesai, 0, 5));
    }
}
