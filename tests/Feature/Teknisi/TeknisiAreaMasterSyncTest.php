<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Modul Teknisi Lapangan:
 *  - Master Area & Zona (area-master) adalah data bersama: cermin semua ruang
 *    Sarpras, kolom Petugas mengikuti PJ ruang.
 *  - Modul lain (area tugas, pemeliharaan, dll) hanya menampilkan data milik
 *    akun yang login (koordinator melihat semua).
 *
 * Berjalan di database operasional: hanya baris hasil uji yang dihapus.
 */
class TeknisiAreaMasterSyncTest extends TestCase
{
    /** @var array<int, int> */
    protected array $createdAreaIds = [];

    /** @var array<int, int> */
    protected array $createdTugasIds = [];

    /** @var array<int, int> */
    protected array $createdPmIds = [];

    /** @var array<int, int> */
    protected array $createdRuangIds = [];

    protected function tearDown(): void
    {
        if (!empty($this->createdAreaIds)) {
            DB::table('teknisi_area_master')->whereIn('id', $this->createdAreaIds)->delete();
        }
        if (!empty($this->createdTugasIds)) {
            DB::table('teknisi_area_tugas')->whereIn('id', $this->createdTugasIds)->delete();
        }
        if (!empty($this->createdPmIds)) {
            DB::table('teknisi_pemeliharaan')->whereIn('id', $this->createdPmIds)->delete();
        }
        if (!empty($this->createdRuangIds)) {
            DB::table('sarpras_ruang')->whereIn('id', $this->createdRuangIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Ruang uji + dua akun teknisi (pemilik awal & akun lain).
     *
     * @return array{0: object, 1: object, 2: int, 3: string}|null
     */
    private function siapkanRuangUji(): ?array
    {
        $teknisi = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->leftJoin('gtk as g', 'p.ptk_id', '=', 'g.ptk_id')
            ->where('p.is_active', true)
            ->where('r.kode', 'like', 'TEKNISI%')
            ->whereNotNull('p.ptk_id')
            ->select('p.user_id', 'p.ptk_id', DB::raw('g.nama as nama'))
            ->limit(2)
            ->get();

        if ($teknisi->count() < 2) {
            $this->markTestSkipped('Perlu minimal dua akun teknisi untuk menguji pemisahan data.');
        }

        $namaRuang = 'Ruang Uji Area Sync ' . uniqid();
        $ruangId = DB::table('sarpras_ruang')->insertGetId([
            'kode_ruang'              => 'TEST-AREA-' . strtoupper(bin2hex(random_bytes(4))),
            'nama_ruang'              => $namaRuang,
            'gedung'                  => 'Gedung Uji Sync',
            'lantai'                  => '1',
            'penanggung_jawab_ptk_id' => $teknisi[0]->ptk_id,
            'kondisi'                 => 'baik',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
        $this->createdRuangIds[] = $ruangId;

        return [$teknisi[0], $teknisi[1], (int) $ruangId, $namaRuang];
    }

    /** @return array<string, mixed> */
    private function sesi(object $akun): array
    {
        return ['user' => [
            'id'           => $akun->user_id,
            'pengguna_id'  => $akun->user_id,
            'name'         => $akun->nama,
            'nama'         => $akun->nama,
            'username'     => (string) $akun->user_id,
            'role'         => 'tugas_tambahan',
            'peran_id_str' => 'Tenaga Administrasi',
            'ptk_id'       => $akun->ptk_id,
        ]];
    }

    public function test_area_master_mencerminkan_pj_dan_hanya_ruang_pj_sendiri(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$pjA, $pjB, $ruangId, $namaRuang] = $siap;

        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $area = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->first();
        $this->assertNotNull($area, 'Ruang harus tercermin ke area master.');
        $this->createdAreaIds[] = (int) $area->id;

        $this->assertSame($namaRuang, $area->nama_area);
        $this->assertNull($area->user_id, 'Area master adalah cermin, bukan milik satu akun.');
        $this->assertSame((string) $pjA->ptk_id, (string) $area->petugas_ptk_id);
        $this->assertSame($pjA->nama, $area->petugas_nama);

        // Hanya PJ ruang yang melihat barisnya. Akun B (bukan PJ) tidak melihat
        // nomor area unik tersebut (dicek lewat kode_area, bukan nama ruang yang
        // mungkin muncul di datalist pencarian).
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200)
            ->assertSee($area->kode_area, false);
        $this->withSession($this->sesi($pjB))
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200)
            ->assertDontSee($area->kode_area, false);

        $jumlah = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->count();
        $this->assertSame(1, $jumlah, 'Sinkronisasi harus idempoten.');
    }

    public function test_jadwal_area_tugas_terpisah_per_akun(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$akunA, $akunB, , $namaRuang] = $siap;

        // A membuat jadwal area tugas.
        $this->withSession($this->sesi($akunA))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('dashboard.teknisi.area-tugas.store'), [
                'area'         => $namaRuang,
                'periode_jadwal' => 'harian',
                'petugas_nama' => $akunA->nama,
                'shift'        => 'pagi',
                'jenis_tugas'  => 'tukang_kebersihan',
                'status'       => 'aktif',
            ])
            ->assertStatus(200);

        $tugasA = DB::table('teknisi_area_tugas')
            ->where('area', $namaRuang)->where('user_id', $akunA->user_id)->first();
        $this->assertNotNull($tugasA, 'Jadwal harus tersimpan milik akun A.');
        $this->createdTugasIds[] = (int) $tugasA->id;

        // A melihat jadwalnya; B tidak. Dicek lewat nomor jadwal (unik di tabel,
        // tidak muncul di dropdown lokasi).
        $this->withSession($this->sesi($akunA))
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($tugasA->nomor_jadwal, false);
        $this->withSession($this->sesi($akunB))
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertDontSee($tugasA->nomor_jadwal, false);
    }

    public function test_pemeliharaan_terpisah_per_akun(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$akunA, $akunB, , $namaRuang] = $siap;

        $this->withSession($this->sesi($akunA))
            ->post(route('dashboard.teknisi.pemeliharaan.store'), [
                'nama_kegiatan' => 'Uji ' . $namaRuang,
                'kategori'      => 'bangunan',
                'lokasi_aset'   => $namaRuang,
                'frekuensi'     => 'bulanan',
                'tgl_jadwal'    => now()->toDateString(),
            ]);

        $pmA = DB::table('teknisi_pemeliharaan')
            ->where('lokasi_aset', $namaRuang)->where('user_id', $akunA->user_id)->first();
        $this->assertNotNull($pmA, 'Jadwal pemeliharaan harus tersimpan milik akun A.');
        $this->createdPmIds[] = (int) $pmA->id;

        $this->withSession($this->sesi($akunA))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertSee($pmA->kode_pemeliharaan, false);
        $this->withSession($this->sesi($akunB))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertDontSee($pmA->kode_pemeliharaan, false);
    }

    public function test_menyimpan_area_master_tidak_menggeser_petugas_dari_pj(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$pjA, $pjB, $ruangId, $namaRuang] = $siap;

        // Pastikan baris cermin ada lebih dulu.
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200);

        $area = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->first();
        $this->assertNotNull($area, 'Ruang uji harus tercermin ke area master.');
        $this->createdAreaIds[] = (int) $area->id;

        // Akun lain menyunting baris area milik PJ A, termasuk mengirim nama
        // petugas sendiri seperti form lama.
        $this->withSession($this->sesi($pjB))
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->put(route('dashboard.teknisi.modul.update', ['modul' => 'area-master', 'id' => $area->id]), [
                'nama_area'    => $namaRuang,
                'zona'         => 'Gedung Uji Sync',
                'gedung'       => 'Gedung Uji Sync',
                'lantai'       => '1',
                'kategori'     => $area->kategori,
                'frekuensi'    => 'mingguan',
                'petugas_nama' => $pjB->nama,
                'status'       => 'aktif',
                'keterangan'   => $area->keterangan,
            ])
            ->assertStatus(200);

        $sesudah = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->first();
        $this->assertSame(
            $pjA->nama,
            $sesudah->petugas_nama,
            'Petugas area master harus tetap PJ ruang, bukan akun yang menyunting.'
        );
        $this->assertSame((string) $pjA->ptk_id, (string) $sesudah->petugas_ptk_id);
        $this->assertNull($sesudah->user_id, 'Area master tetap data bersama (tanpa pemilik akun).');
        $this->assertSame('mingguan', $sesudah->frekuensi, 'Suntingan petugas (frekuensi) tetap tersimpan.');
    }

    public function test_koordinator_melihat_semua_ruang(): void
    {
        $koordinator = DB::table('pengguna')
            ->where('peran_id_str', 'Administrator')
            ->first(['pengguna_id', 'nama']);

        if (!$koordinator) {
            $this->markTestSkipped('Tidak ada akun koordinator (Administrator).');
        }

        $namaRuang = DB::table('sarpras_ruang')
            ->where('nama_ruang', 'not like', '%&%')
            ->orderBy('nama_ruang')
            ->value('nama_ruang');
        $this->assertNotEmpty($namaRuang, 'Prasyarat: master ruang tidak boleh kosong.');

        $session = ['user' => [
            'id'          => $koordinator->pengguna_id,
            'pengguna_id' => $koordinator->pengguna_id,
            'name'        => $koordinator->nama,
            'nama'        => $koordinator->nama,
            'role'        => 'admin',
            'ptk_id'      => null,
        ]];

        $this->withSession($session)
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        // Koordinator hanya melihat; tidak ada jadwal yang dibuat atas namanya.
        $this->assertSame(0, DB::table('teknisi_area_tugas')
            ->where('user_id', $koordinator->pengguna_id)->count());
        $this->assertSame(0, DB::table('teknisi_pemeliharaan')
            ->where('user_id', $koordinator->pengguna_id)->count());
    }
}
