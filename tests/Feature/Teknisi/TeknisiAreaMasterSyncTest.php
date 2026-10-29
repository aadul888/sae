<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Area Master, Area Tugas, dan Pemeliharaan mengikuti penanggung jawab ruang
 * di master Sarpras secara langsung (tanpa aksi tambahan).
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
     * Ruang uji + dua akun teknisi (pemilik awal & pemilik baru).
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
            $this->markTestSkipped('Perlu minimal dua akun teknisi untuk menguji pergantian PJ.');
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

    public function test_area_master_mencerminkan_ruang_dan_petugas_dari_pj(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$pjA, , $ruangId, $namaRuang] = $siap;

        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $area = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->first();
        $this->assertNotNull($area, 'Ruang harus tercermin ke area master.');
        $this->createdAreaIds[] = (int) $area->id;

        $this->assertSame($namaRuang, $area->nama_area);
        $this->assertSame('Gedung Uji Sync', $area->gedung);
        $this->assertNull($area->user_id, 'Area master adalah data bersama, bukan milik akun.');
        $this->assertSame((string) $pjA->ptk_id, (string) $area->petugas_ptk_id);
        $this->assertSame($pjA->nama, $area->petugas_nama);
        $this->assertSame('aktif', $area->status);
        $this->assertNotEmpty($area->kode_area);

        $jumlah = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->count();
        $this->assertSame(1, $jumlah, 'Sinkronisasi harus idempoten.');
    }

    public function test_pergantian_pj_langsung_menggeser_area_tugas_dan_pemeliharaan(): void
    {
        $siap = $this->siapkanRuangUji();
        if (!$siap) {
            return;
        }
        [$pjA, $pjB, $ruangId, $namaRuang] = $siap;
        $awal = now()->subMinute();

        // Halaman A: ruang muncul sebagai jadwal milik A.
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $tugasA = DB::table('teknisi_area_tugas')
            ->where('ruang_id', $ruangId)->where('user_id', $pjA->user_id)->first();
        $this->assertNotNull($tugasA, 'Ruang PJ A harus muncul di jadwal area tugas A.');
        $this->createdTugasIds[] = (int) $tugasA->id;

        $pmA = DB::table('teknisi_pemeliharaan')
            ->where('ruang_id', $ruangId)->where('user_id', $pjA->user_id)->first();
        $this->assertNotNull($pmA, 'Ruang PJ A harus muncul di pemeliharaan A.');
        $this->createdPmIds[] = (int) $pmA->id;

        // PJ dipindahkan ke akun B lewat master Sarpras — tanpa aksi lain.
        DB::table('sarpras_ruang')->where('id', $ruangId)->update([
            'penanggung_jawab_ptk_id' => $pjB->ptk_id,
            'updated_at'              => now(),
        ]);

        // Halaman B: ruang langsung muncul.
        $this->withSession($this->sesi($pjB))
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);
        $this->withSession($this->sesi($pjB))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $this->assertDatabaseHas('teknisi_area_tugas', [
            'ruang_id' => $ruangId,
            'user_id'  => $pjB->user_id,
        ]);
        $this->assertDatabaseHas('teknisi_pemeliharaan', [
            'ruang_id' => $ruangId,
            'user_id'  => $pjB->user_id,
        ]);

        // Petugas di area master ikut berganti.
        $this->assertSame($pjB->nama, DB::table('teknisi_area_master')
            ->where('ruang_id', $ruangId)->value('petugas_nama'));

        // Halaman A: ruang tidak lagi tampil (filter PJ bersifat live).
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertDontSee($namaRuang, false);
        $this->withSession($this->sesi($pjA))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertDontSee($namaRuang, false);

        // Bersihkan jadwal milik B hasil pergantian PJ.
        $this->createdTugasIds = array_merge($this->createdTugasIds, DB::table('teknisi_area_tugas')
            ->where('ruang_id', $ruangId)->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->createdPmIds = array_merge($this->createdPmIds, DB::table('teknisi_pemeliharaan')
            ->where('ruang_id', $ruangId)->pluck('id')->map(fn ($v) => (int) $v)->all());

        // Jadwal lain yang ikut terbentuk saat uji dipangkas.
        $this->createdTugasIds = array_merge($this->createdTugasIds, DB::table('teknisi_area_tugas')
            ->whereIn('user_id', [$pjA->user_id, $pjB->user_id])
            ->where('created_at', '>=', $awal)
            ->where('ruang_id', '<>', $ruangId)
            ->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->createdPmIds = array_merge($this->createdPmIds, DB::table('teknisi_pemeliharaan')
            ->whereIn('user_id', [$pjA->user_id, $pjB->user_id])
            ->where('created_at', '>=', $awal)
            ->where('ruang_id', '<>', $ruangId)
            ->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->createdAreaIds = array_merge($this->createdAreaIds, DB::table('teknisi_area_master')
            ->where('created_at', '>=', $awal)->pluck('id')->map(fn ($v) => (int) $v)->all());
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

        $this->assertSame($pjA->nama, DB::table('teknisi_area_master')
            ->where('ruang_id', $ruangId)->value('petugas_nama'));

        // Teknisi B menyunting baris area milik PJ A (mis. mengganti frekuensi),
        // termasuk mengirim nama petugas sendiri seperti form lama.
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

        // Koordinator hanya melihat; jadwal tidak boleh dibuat atas namanya
        // sendiri (itu akan mengotori daftar tugas teknisi).
        $this->assertSame(0, DB::table('teknisi_area_tugas')
            ->where('user_id', $koordinator->pengguna_id)->count());
        $this->assertSame(0, DB::table('teknisi_pemeliharaan')
            ->where('user_id', $koordinator->pengguna_id)->count());
    }
}
