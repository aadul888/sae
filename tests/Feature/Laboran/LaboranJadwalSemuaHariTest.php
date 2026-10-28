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
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Selasa']));

        $res->assertStatus(200);
        $res->assertSee('UJI-SELASA', false);
        // Matriks menampilkan pemilih seluruh hari (Senin..Sabtu).
        $res->assertSee('Sabtu', false);
    }

    /**
     * Matriks berbaris JAM KE-1..13 dan berkolom KELAS. Sesi praktik harus muncul
     * pada kolom kelasnya, dan sesi multi-JP digabung menjadi satu kartu (rowspan).
     */
    public function test_matriks_baris_jam_kolom_kelas_dan_sesi_multi_jp_digabung(): void
    {
        $kelas = DB::table('rombongan_belajar')
            ->where(function ($q) {
                $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
            })
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('pembelajaran')
                    ->whereColumn('pembelajaran.rombongan_belajar_id', 'rombongan_belajar.rombongan_belajar_id');
            })
            ->first();
        $this->assertNotNull($kelas, 'Butuh minimal satu rombel reguler.');

        DB::table('laboran_jadwal_penggunaan')->insert([
            'jurusan_id'     => $kelas->jurusan_id ?: self::JUR_ATPH,
            'ruang_lab_nama' => 'Lahan Praktik ATPH',
            'jenis_lokasi'   => 'lahan',
            'ptk_id'         => (string) DB::table('gtk')->value('ptk_id'),
            'rombel_id'      => $kelas->rombongan_belajar_id,
            'mata_pelajaran' => 'UJI-BARIS-JAM',
            'tanggal'        => '2026-11-02', // Senin
            'jam_mulai'      => '08:00',
            'jam_selesai'    => '09:30', // Jam ke-3..4
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));

        $res->assertStatus(200);

        // Baris = Jam ke-1..13 beserta pukul.
        $res->assertSee('Jam ke-1', false);
        $res->assertSee('Jam ke-13', false);
        $res->assertSee('06:30-07:15', false);

        // Kolom = kelas (nama kelas jadi header kolom).
        $res->assertSee($kelas->nama, false);

        // Sesi 2 JP tampil sebagai satu kartu yang membentang dua baris jam.
        $res->assertSee('UJI-BARIS-JAM', false);
        $res->assertSee('Jam ke-3-4', false);
        $res->assertSee('rowspan="2"', false);
    }

    public function test_matriks_tidak_lagi_menampilkan_filter_jurusan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid']));

        $res->assertStatus(200);
        // Semua kelas dalam cakupan digabung: tidak ada tombol filter jurusan lagi.
        $res->assertDontSee('Jurusan:', false);
    }

    public function test_rutinitas_tampil_pada_hari_yang_dipilih(): void
    {
        // Senin: Upacara pada jam ke-1; hari lain (mis. Jumat) memuat Pembiasaan.
        $senin = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Senin']));
        $senin->assertStatus(200);
        $senin->assertSee('Upacara', false);
        $senin->assertSee('Istirahat', false);

        $jumat = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.jadwal.index', ['view_mode' => 'grid', 'hari_grid' => 'Jumat']));
        $jumat->assertStatus(200);
        $jumat->assertSee('Pembiasaan', false);
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
        // Jumat 2026-11-06 (JP lebih sedikit) — JP2 harus dari slot Jumat
        // (JP1 Jumat = Pembiasaan, tidak boleh tertimpa).
        $rombel = DB::table('rombongan_belajar')->where(function ($q) {
            $q->where('jenis_rombel', 1)->orWhere('jenis_rombel', '1');
        })->first();

        $slotsJumat = \App\Models\JadwalPengaturan::getSlots('Jumat');
        $this->assertNotEmpty($slotsJumat, 'Slot Jumat harus tersedia.');
        $slot1 = $slotsJumat[2];

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jadwal.store'), [
                'ruang_lab_nama'  => 'Lahan Praktik ATPH',
                'jenis_lokasi'    => 'lahan',
                'rombel_id'       => $rombel->rombongan_belajar_id,
                'pemberlakuan'    => 'mingguan',
                'pemberlakuan_minggu' => 1,
                'hari'            => ['Jumat'],
                'tanggal'         => '2026-11-06',
                'jam_ke_mulai'    => 2,
                'jam_ke_selesai'  => 2,
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
