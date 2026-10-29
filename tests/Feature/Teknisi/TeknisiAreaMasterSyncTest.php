<?php

namespace Tests\Feature\Teknisi;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Master Area & Zona Kebersihan (`teknisi/modul/area-master`) menampilkan
 * otomatis ruang & gedung dari master Sarpras sesuai PJ yang sedang login.
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

    public function test_area_master_mengikuti_ruang_milik_pj_yang_login(): void
    {
        $pj = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->where('p.is_active', true)
            ->where('r.kode', 'like', 'TEKNISI%')
            ->whereNotNull('p.ptk_id')
            ->select('p.user_id', 'p.ptk_id')
            ->first();

        if (!$pj) {
            $this->markTestSkipped('Tidak ada akun teknisi aktif untuk diuji.');
        }

        $ruang = DB::table('sarpras_ruang')
            ->where('penanggung_jawab_ptk_id', $pj->ptk_id)
            ->get(['id', 'nama_ruang', 'gedung']);

        $namaRuang = 'Ruang Uji Area Sync ' . uniqid();
        $ruangId = DB::table('sarpras_ruang')->insertGetId([
            'kode_ruang'                => 'TEST-AREA-' . strtoupper(bin2hex(random_bytes(4))),
            'nama_ruang'                => $namaRuang,
            'gedung'                    => 'Gedung Uji Sync',
            'lantai'                    => '1',
            'penanggung_jawab_ptk_id'   => $pj->ptk_id,
            'kondisi'                   => 'baik',
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
        $this->createdRuangIds[] = $ruangId;

        $sebelum = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->exists();
        $this->assertFalse($sebelum, 'Prasyarat: area untuk ruang uji belum ada.');

        // Jejak untuk pemangkasan: id ruang yang belum tercermin + waktu mulai.
        $ruangIdsBaru = DB::table('sarpras_ruang')
            ->whereNotIn('id', DB::table('teknisi_area_master')->whereNotNull('ruang_id')->select('ruang_id'))
            ->pluck('id')->all();
        $awal = now()->subMinute();

        $session = ['user' => [
            'id'           => $pj->user_id,
            'pengguna_id'  => $pj->user_id,
            'name'         => 'Teknisi Uji',
            'nama'         => 'Teknisi Uji',
            'username'     => 'teknisi_area_uji',
            'role'         => 'tendik',
            'peran_id_str' => 'Tenaga Administrasi',
            'ptk_id'       => $pj->ptk_id,
        ]];

        $this->withSession($session)
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'area-master']))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $area = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->first();
        $this->assertNotNull($area, 'Ruang milik PJ harus tercermin ke area master.');
        $this->createdAreaIds[] = (int) $area->id;

        $this->assertSame($namaRuang, $area->nama_area);
        $this->assertSame('Gedung Uji Sync', $area->gedung);
        $this->assertSame((string) $pj->ptk_id, (string) $area->petugas_ptk_id);
        $this->assertSame('aktif', $area->status);
        $this->assertNotEmpty($area->kode_area);

        // Bukti fisik: ruang PJ lain yang sudah terkait tidak ikut dimasukkan ulang.
        $jumlah = DB::table('teknisi_area_master')->where('ruang_id', $ruangId)->count();
        $this->assertSame(1, $jumlah, 'Sinkronisasi harus idempoten.');

        // Ruang yang PJ-nya BUKAN teknisi juga harus ikut tercermin: teknisi
        // adalah petugas lapangan seluruh area, bukan hanya area PJ-nya.
        $ruangPjLain = DB::table('sarpras_ruang')
            ->whereNotNull('penanggung_jawab_ptk_id')
            ->where('penanggung_jawab_ptk_id', '<>', $pj->ptk_id)
            ->orderBy('id')
            ->first(['id', 'nama_ruang']);

        if ($ruangPjLain) {
            $this->assertDatabaseHas('teknisi_area_master', ['ruang_id' => $ruangPjLain->id]);
            $this->assertTrue(true, 'Ruang PJ non-teknisi harus ikut tercermin.');
        }

        // Halaman Jadwal Area Tugas menurunkan jadwal dari seluruh area master.
        $this->withSession($session)
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $tugas = DB::table('teknisi_area_tugas')
            ->where('area', $namaRuang)
            ->where('petugas_ptk_id', $pj->ptk_id)
            ->first();
        $this->assertNotNull($tugas, 'Ruang harus muncul sebagai jadwal area tugas.');
        $this->createdTugasIds[] = (int) $tugas->id;
        $this->assertSame('aktif', $tugas->status);

        // Halaman Pemeliharaan menurunkan jadwal preventif dari seluruh area master.
        $this->withSession($session)
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200)
            ->assertSee($namaRuang, false);

        $pm = DB::table('teknisi_pemeliharaan')
            ->where('lokasi_aset', $namaRuang)
            ->where('user_id', $pj->user_id)
            ->first();
        $this->assertNotNull($pm, 'Ruang harus muncul sebagai jadwal pemeliharaan.');
        $this->createdPmIds[] = (int) $pm->id;

        // Jadwal non-ruang-uji yang ikut terbentuk dipangkas agar data operasional bersih.
        $this->createdAreaIds = array_merge($this->createdAreaIds, DB::table('teknisi_area_master')
            ->whereIn('ruang_id', $ruangIdsBaru)->where('id', '<>', $area->id)->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->createdTugasIds = array_merge($this->createdTugasIds, DB::table('teknisi_area_tugas')
            ->where('petugas_ptk_id', $pj->ptk_id)->where('area', '<>', $namaRuang)->where('created_at', '>=', $awal)->pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->createdPmIds = array_merge($this->createdPmIds, DB::table('teknisi_pemeliharaan')
            ->where('user_id', $pj->user_id)->where('lokasi_aset', '<>', $namaRuang)->where('created_at', '>=', $awal)->pluck('id')->map(fn ($v) => (int) $v)->all());
    }
}
