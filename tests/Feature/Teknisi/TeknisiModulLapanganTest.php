<?php

namespace Tests\Feature\Teknisi;

use App\Support\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Modul TEKNISI LAPANGAN: cakupan data per akun petugas.
 *
 * Aturan yang diuji:
 *  - Teknisi lapangan hanya melihat & mengubah catatannya sendiri.
 *  - Koordinator (admin) melihat seluruh catatan.
 *  - Sembilan modul generik dapat dibuka, diunduh templatenya, dan dicetak.
 *
 * Berjalan di database operasional: cleanup hanya menghapus baris baru
 * (snapshot id di setUp).
 */
class TeknisiModulLapanganTest extends TestCase
{
    protected const MODULES = [
        'inspeksi-utilitas',
        'apar-k3',
        'pest-control',
        'higiene-kantin',
        'pemakaian-bahan',
        'usulan-pengadaan',
        'aset-mutasi',
    ];

    /** @var array<string, array<int, int>> */
    protected array $preexistingIds = [];

    /** @var array<string, int> */
    protected array $preexistingMaxIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $tables = array_merge(
            ['teknisi_log_harian', 'teknisi_usulan_pengadaan',
             'teknisi_aset_mutasi', 'sarpras_peminjaman_ruang',
             'teknisi_import_log', 'teknisi_pemeliharaan', 'teknisi_work_order',
             'teknisi_pelaksanaan_harian'],
            ['teknisi_area_tugas']
        );

        foreach ($tables as $table) {
            try {
                if (Table::exists($table)) {
                    $this->preexistingIds[$table] = DB::table($table)->pluck('id')->all();
                    $this->preexistingMaxIds[$table] = (int) DB::table($table)->max('id');
                } else {
                    $this->preexistingIds[$table] = [];
                    $this->preexistingMaxIds[$table] = 0;
                }
            } catch (\Throwable $e) {
                $this->preexistingIds[$table] = [];
                $this->preexistingMaxIds[$table] = 0;
            }
        }
    }

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/temp_teknisi_mod_*.xlsx')) ?: [] as $f) {
            @unlink($f);
        }

        foreach ($this->preexistingIds as $table => $ids) {
            try {
                if (!Table::exists($table)) {
                    continue;
                }
                $baru = DB::table($table)
                    ->when(!empty($ids), fn ($q) => $q->whereNotIn('id', $ids))
                    ->when(empty($ids), fn ($q) => $q->where('id', '>', $this->preexistingMaxIds[$table] ?? 0))
                    ->pluck('id')
                    ->all();
                if (!empty($baru)) {
                    DB::table($table)->whereIn('id', $baru)->delete();
                }
            } catch (\Throwable $e) {
            }
        }

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Sesi
    // ------------------------------------------------------------------

    protected function adminSession(): array
    {
        $admin = DB::table('pengguna')->where('peran_id_str', 'Administrator')->first();

        return ['user' => [
            'id'           => $admin->pengguna_id,
            'pengguna_id'  => $admin->pengguna_id,
            'name'         => $admin->nama,
            'nama'         => $admin->nama,
            'username'     => $admin->username,
            'role'         => 'admin',
            'peran_id_str' => $admin->peran_id_str,
            'ptk_id'       => $admin->ptk_id ?? null,
        ]];
    }

    /**
     * Sesi teknisi lapangan (bukan koordinator) dari tugas tambahan TEKNISI_IT.
     *
     * @return array{user: array<string, mixed>}
     */
    protected function teknisiSession(int $index = 0): array
    {
        $rows = DB::table('ptk_tugas_tambahan as p')
            ->join('ref_tugas_tambahan as r', 'p.tugas_tambahan_id', '=', 'r.id')
            ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
            ->where('p.is_active', true)
            ->where('r.kode', 'like', 'TEKNISI%')
            ->whereNotNull('p.user_id')
            ->select('p.user_id', 'p.ptk_id', 'gtk.nama')
            ->orderBy('p.id')
            ->get();

        $this->assertGreaterThan($index, $rows->count(), 'Butuh minimal 2 akun teknisi untuk uji cakupan.');

        $row = $rows[$index];

        return ['user' => [
            'id'           => $row->user_id,
            'pengguna_id'  => $row->user_id,
            'name'         => $row->nama ?: 'Teknisi Uji',
            'nama'         => $row->nama ?: 'Teknisi Uji',
            'username'     => 'teknisi_uji_' . $index,
            'role'         => 'tendik',
            'peran_id_str' => 'Tenaga Administrasi',
            'ptk_id'       => $row->ptk_id,
        ]];
    }

    // ------------------------------------------------------------------
    // Uji
    // ------------------------------------------------------------------

    public function test_tujuh_modul_dapat_dibuka_dicetak_dan_diunduh_template(): void
    {
        $session = $this->adminSession();

        foreach (self::MODULES as $modul) {
            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.index', ['modul' => $modul]))
                ->assertStatus(200);

            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.cetak', ['modul' => $modul, 'periode' => 'tahun', 'tahun' => 2026]))
                ->assertStatus(200);

            // Modul inventaris (mutasi aset) tidak punya template impor.
            if ($modul === 'aset-mutasi') {
                continue;
            }

            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.template', ['modul' => $modul]))
                ->assertStatus(200);
        }
    }

    /**
     * Mutasi Lokasi Aset = daftar inventaris aset; hanya boleh memindahkan.
     * Tambah (store), impor, template, dan hapus tidak tersedia.
     */
    public function test_mutasi_aset_ambil_dari_inventaris_dan_hanya_memindahkan(): void
    {
        $session = $this->adminSession();
        $modul = 'aset-mutasi';

        $this->withSession($session)
            ->get(route('dashboard.teknisi.modul.index', ['modul' => $modul]))
            ->assertStatus(200);

        // Tambah/impor/template ditolak.
        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => $modul]), [])
            ->assertStatus(422);

        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.modul.import', ['modul' => $modul]), [
                'file' => UploadedFile::fake()->createWithContent('m.csv', "aset_id\n1\n"),
            ])
            ->assertStatus(422);

        $this->withSession($session)
            ->get(route('dashboard.teknisi.modul.template', ['modul' => $modul]))
            ->assertStatus(404);

        // Update memindahkan aset ke ruang tujuan.
        if (!Table::exists('sarpras_ruang') || !Table::exists('sarpras_aset')) {
            $this->markTestSkipped('Tabel sarpras tidak tersedia.');
        }

        $ruangA = DB::table('sarpras_ruang')->orderBy('id')->first();
        $ruangB = DB::table('sarpras_ruang')->where('id', '!=', $ruangA->id ?? 0)->orderBy('id')->first();
        if (!$ruangA || !$ruangB) {
            $this->markTestSkipped('Butuh dua ruang berbeda untuk uji pemindahan.');
        }

        // Aset uji sementara (dibuat sendiri, dihapus lagi di akhir).
        $namaAset = 'Aset Uji Mutasi ' . uniqid();
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'   => 'UJI-' . uniqid(),
            'nama_barang' => $namaAset,
            'kondisi'     => 'baik',
            'jumlah'      => 1,
            'satuan'      => 'unit',
            'ruang_id'    => $ruangA->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        try {
            $this->withSession($session)
                ->putJson(route('dashboard.teknisi.modul.update', ['modul' => $modul, 'id' => $asetId]), [
                    'ke_ruang_id' => $ruangB->id,
                    'tanggal'     => now()->toDateString(),
                    'alasan'      => 'Uji pemindahan',
                ])
                ->assertStatus(200)
                ->assertJsonPath('status', 'success');

            $this->assertSame((int) $ruangB->id, (int) DB::table('sarpras_aset')->where('id', $asetId)->value('ruang_id'), 'Aset harus pindah ke ruang tujuan.');

            $riwayat = DB::table('teknisi_aset_mutasi')->where('aset_id', $asetId)->orderByDesc('id')->first();
            $this->assertNotNull($riwayat, 'Riwayat mutasi harus tercatat.');
            $this->assertSame((int) $ruangB->id, (int) $riwayat->ke_ruang_id);

            // Pindah ke ruang yang sama ditolak.
            $this->withSession($session)
                ->putJson(route('dashboard.teknisi.modul.update', ['modul' => $modul, 'id' => $asetId]), [
                    'ke_ruang_id' => $ruangB->id,
                ])
                ->assertStatus(422);
        } finally {
            DB::table('teknisi_aset_mutasi')->where('aset_id', $asetId)->delete();
            DB::table('sarpras_aset')->where('id', $asetId)->delete();
        }
    }

    public function test_data_hanya_terlihat_oleh_pemiliknya_dan_koordinator(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $judul = 'Panel Listrik Uji ' . uniqid();

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal'  => now()->toDateString(),
                'subjek'   => $judul,
                'kategori' => 'panel_listrik',
                'kondisi'  => 'baik',
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_log_harian')->where('subjek', $judul)->first();
        $this->assertNotNull($row);
        $this->assertSame('inspeksi-utilitas', $row->modul);
        $this->assertSame((string) $a['user']['pengguna_id'], (string) $row->user_id, 'Baris harus milik pengunggah.');

        // Pemilik melihat catatannya.
        $this->withSession($a)
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'inspeksi-utilitas']))
            ->assertStatus(200)
            ->assertSee($judul, false);

        // Teknisi lain tidak melihat.
        $this->withSession($b)
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'inspeksi-utilitas']))
            ->assertStatus(200)
            ->assertDontSee($judul, false);

        // Koordinator melihat semua.
        $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'inspeksi-utilitas']))
            ->assertStatus(200)
            ->assertSee($judul, false);
    }

    public function test_teknisi_lain_tidak_dapat_mengubah_atau_menghapus(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $judul = 'Genset Uji ' . uniqid();

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal'  => now()->toDateString(),
                'subjek'   => $judul,
                'kategori' => 'genset',
            ])
            ->assertStatus(200);

        $id = DB::table('teknisi_log_harian')->where('subjek', $judul)->value('id');

        $this->withSession($b)
            ->putJson(route('dashboard.teknisi.modul.update', ['modul' => 'inspeksi-utilitas', 'id' => $id]), [
                'tanggal' => now()->toDateString(),
                'subjek'  => 'Diubah orang lain',
            ])
            ->assertStatus(403);

        $this->withSession($b)
            ->deleteJson(route('dashboard.teknisi.modul.destroy', ['modul' => 'inspeksi-utilitas', 'id' => $id]))
            ->assertStatus(403);

        $this->assertSame($judul, DB::table('teknisi_log_harian')->where('id', $id)->value('subjek'));

        // Pemilik tetap dapat mengubah.
        $this->withSession($a)
            ->putJson(route('dashboard.teknisi.modul.update', ['modul' => 'inspeksi-utilitas', 'id' => $id]), [
                'tanggal' => now()->toDateString(),
                'subjek'  => $judul . ' (revisi)',
            ])
            ->assertStatus(200);

        $this->assertSame($judul . ' (revisi)', DB::table('teknisi_log_harian')->where('id', $id)->value('subjek'));
    }

    public function test_import_menjadi_milik_pengunggah(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $judul = 'Sabun Uji ' . uniqid();
        $csv = "tanggal,kategori,subjek,jumlah,satuan\n"
            . now()->toDateString() . ",sabun,{$judul},5,pcs\n";

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.modul.import', ['modul' => 'pemakaian-bahan']), [
                'file' => UploadedFile::fake()->createWithContent('bahan.csv', $csv),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_log_harian')->where('subjek', $judul)->first();
        $this->assertNotNull($row, 'Baris hasil impor tidak masuk.');
        $this->assertSame((string) $a['user']['pengguna_id'], (string) $row->user_id);

        $this->withSession($b)
            ->get(route('dashboard.teknisi.modul.index', ['modul' => 'pemakaian-bahan']))
            ->assertStatus(200)
            ->assertDontSee($judul, false);
    }

    /**
     * Menu Teknisi: Master Area & Zona dihapus (ganda dengan Jadwal Area Tugas),
     * dan Bahan Habis Pakai tampil tepat setelah Inventaris & Aset.
     */
    public function test_menu_teknisi_tanpa_master_area_dengan_bhp_setelah_inventaris(): void
    {
        $menu = \App\Support\TeknisiModuleRegistry::menu();

        $this->assertArrayNotHasKey('area-master', \App\Support\TeknisiModuleRegistry::all());

        $master = array_column($menu['master'], 'label');
        $this->assertContains('Inventaris & Aset', $master);
        $this->assertContains('Bahan Habis Pakai', $master);

        $iAset = array_search('Inventaris & Aset', $master, true);
        $iBhp  = array_search('Bahan Habis Pakai', $master, true);
        $this->assertSame($iAset + 1, $iBhp, 'Bahan Habis Pakai harus tepat setelah Inventaris & Aset.');
    }

    public function test_modul_tidak_dikenal_ditolak_route(): void
    {
        $this->withSession($this->adminSession())
            ->get('/dashboard/teknisi/modul/modul-ngawur')
            ->assertStatus(404);
    }

    /**
     * Pemakaian bahan kebersihan: item dari menu BHP, dan tiap pemakaian
     * tercatat sebagai baris di Jurnal Pelaksanaan Tugas (idempoten).
     */
    public function test_pemakaian_bahan_masuk_jurnal_pelaksanaan(): void
    {
        $session = $this->adminSession();

        $subjek = 'Sabun Pelaksanaan ' . uniqid();

        $this->withSession($session)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'pemakaian-bahan']), [
                'tanggal'  => now()->toDateString(),
                'kategori' => 'sabun',
                'subjek'   => $subjek,
                'jumlah'   => 3,
                'satuan'   => 'botol',
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $log = DB::table('teknisi_log_harian')->where('subjek', $subjek)->where('modul', 'pemakaian-bahan')->first();
        $this->assertNotNull($log, 'Catatan pemakaian bahan tidak masuk.');

        $pel = DB::table('teknisi_pelaksanaan_harian')->where('log_harian_id', $log->id)->first();
        $this->assertNotNull($pel, 'Pemakaian bahan harus tercatat di Jurnal Pelaksanaan.');
        $this->assertStringContainsString($subjek, $pel->area);

        // Tampil di jurnal pelaksanaan.
        $this->withSession($session)
            ->get(route('dashboard.teknisi.pelaksanaan-harian.index'))
            ->assertStatus(200)
            ->assertSee($subjek, false);

        // Ubah pemakaian â†’ baris pelaksanaan ikut diperbarui (tidak dobel).
        $this->withSession($session)
            ->putJson(route('dashboard.teknisi.modul.update', ['modul' => 'pemakaian-bahan', 'id' => $log->id]), [
                'tanggal'  => now()->toDateString(),
                'kategori' => 'sabun',
                'subjek'   => $subjek . ' (revisi)',
                'jumlah'   => 5,
                'satuan'   => 'botol',
            ])
            ->assertStatus(200);

        $this->assertSame(1, DB::table('teknisi_pelaksanaan_harian')->where('log_harian_id', $log->id)->count());
        $this->assertStringContainsString($subjek . ' (revisi)', DB::table('teknisi_pelaksanaan_harian')->where('log_harian_id', $log->id)->value('area'));

        // Hapus pemakaian â†’ baris pelaksanaan ikut terhapus.
        $this->withSession($session)
            ->deleteJson(route('dashboard.teknisi.modul.destroy', ['modul' => 'pemakaian-bahan', 'id' => $log->id]))
            ->assertStatus(200);
        $this->assertSame(0, DB::table('teknisi_pelaksanaan_harian')->where('log_harian_id', $log->id)->count());
    }

    /**
     * Regresi: work order & pemeliharaan memakai kolom kepemilikan yang benar
     * (`teknisi_ptk_id` / hanya `user_id`), bukan `petugas_ptk_id` yang tidak ada.
     */
    public function test_work_order_dan_pemeliharaan_terbuka_untuk_teknisi_lapangan(): void
    {
        $this->withSession($this->teknisiSession(0))
            ->get(route('dashboard.teknisi.work-order'))
            ->assertStatus(200);

        $this->withSession($this->teknisiSession(0))
            ->get(route('dashboard.teknisi.pemeliharaan'))
            ->assertStatus(200);

        $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.work-order.cetak', ['periode' => 'tahun', 'tahun' => date('Y')]))
            ->assertStatus(200);
    }

    /**
     * Setiap unggahan Excel mencatat akun pengunggah â€” baik pada baris data
     * (`user_id`) maupun pada riwayat unggah `teknisi_import_log`.
     */
    public function test_import_work_order_dan_pemeliharaan_mencatat_pengunggah(): void
    {
        $a = $this->teknisiSession(0);
        $userId = (string) $a['user']['pengguna_id'];
        $nama = $a['user']['nama'];

        // Work order
        $lokasi = 'Ruang Uji WO ' . uniqid();
        $csvWo = "tanggal,lokasi_unit,kategori_perbaikan,deskripsi_kerusakan,tingkat_urgensi\n"
            . now()->toDateString() . ",{$lokasi},komputer,Layar tidak menyala,normal\n";

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.work-order.import'), [
                'file' => UploadedFile::fake()->createWithContent('wo.csv', $csvWo),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $wo = DB::table('teknisi_work_order')->where('lokasi_unit', $lokasi)->first();
        $this->assertNotNull($wo, 'Baris work order tidak masuk.');
        $this->assertSame($userId, (string) $wo->user_id, 'Work order harus mencatat akun pengunggah.');

        $logWo = DB::table('teknisi_import_log')->where('modul', 'work-order')->orderByDesc('id')->first();
        $this->assertNotNull($logWo);
        $this->assertSame($userId, (string) $logWo->user_id);
        $this->assertSame($nama, $logWo->user_nama);
        $this->assertSame('wo.csv', $logWo->file_name);

        // Pemeliharaan preventif
        $kegiatan = 'Servis AC Uji ' . uniqid();
        $csvPm = "nama_kegiatan,kategori,lokasi_aset,frekuensi,tgl_jadwal,status\n"
            . "{$kegiatan},ac_pendingin,Ruang Guru,bulanan," . now()->toDateString() . ",terjadwal\n";

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.pemeliharaan.import'), [
                'file' => UploadedFile::fake()->createWithContent('pm.csv', $csvPm),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $pm = DB::table('teknisi_pemeliharaan')->where('nama_kegiatan', $kegiatan)->first();
        $this->assertNotNull($pm, 'Baris pemeliharaan tidak masuk.');
        $this->assertSame($userId, (string) $pm->user_id, 'Pemeliharaan harus mencatat akun pengunggah.');

        $logPm = DB::table('teknisi_import_log')->where('modul', 'pemeliharaan')->orderByDesc('id')->first();
        $this->assertNotNull($logPm);
        $this->assertSame($userId, (string) $logPm->user_id);
        $this->assertSame('pm.csv', $logPm->file_name);
    }

    /**
     * Nama petugas TIDAK diambil dari berkas impor â€” selalu akun yang login.
     * Kolom `petugas_nama` di CSV harus diabaikan.
     */
    public function test_import_mengabaikan_nama_petugas_dari_berkas(): void
    {
        $a = $this->teknisiSession(0);
        $userId = (string) $a['user']['pengguna_id'];
        $namaLogin = $a['user']['nama'];

        $subjek = 'APAR Uji ' . uniqid();
        $csv = "tanggal,lokasi,petugas_nama,petugas_ptk_id,subjek,kategori,kondisi\n"
            . now()->toDateString() . ",Gedung A,Orang Palsu,ptk-palsu,{$subjek},apar,baik\n";

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.modul.import', ['modul' => 'apar-k3']), [
                'file' => UploadedFile::fake()->createWithContent('apar.csv', $csv),
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $row = DB::table('teknisi_log_harian')->where('subjek', $subjek)->first();
        $this->assertNotNull($row, 'Baris hasil impor tidak masuk.');
        $this->assertSame($userId, (string) $row->user_id);
        $this->assertSame($namaLogin, $row->petugas_nama, 'Nama petugas harus dari akun login, bukan berkas.');
        $this->assertNotSame('ptk-palsu', (string) $row->petugas_ptk_id, 'PTK petugas tidak boleh diambil dari berkas.');
    }

    /**
     * Cetak laporan TUPOKSI tunduk pada cakupan akun:
     * teknisi tidak boleh melihat baris milik teknisi lain di dokumen cetak.
     */
    public function test_cetak_tupoksi_ikut_cakupan_akun(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $areaA = 'Area A ' . uniqid();
        $areaB = 'Area B ' . uniqid();

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.area-tugas.store'), [
                'area'            => $areaA,
                'petugas_nama'    => $a['user']['nama'],
                'tanggal_mulai'   => now()->toDateString(),
            ])
            ->assertStatus(200);
        $this->withSession($b)
            ->postJson(route('dashboard.teknisi.area-tugas.store'), [
                'area'            => $areaB,
                'petugas_nama'    => $b['user']['nama'],
                'tanggal_mulai'   => now()->toDateString(),
            ])
            ->assertStatus(200);

        // B tidak melihat dokumen cetak milik A.
        $this->withSession($b)
            ->get(route('dashboard.teknisi.laporan.cetak', ['modul' => 'area-tugas']))
            ->assertStatus(200)
            ->assertDontSee($areaA, false);

        // Koordinator melihat semua.
        $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.laporan.cetak', ['modul' => 'area-tugas']))
            ->assertStatus(200)
            ->assertSee($areaA, false);
    }

    /**
     * Bulk delete mengenali seluruh modul Teknisi (10 lapangan + 6 TUPOKSI + WO + PM).
     */
    public function test_bulk_delete_mengenali_semua_modul(): void
    {
        $moduls = array_merge(self::MODULES, [
            'area-tugas', 'pelaksanaan-harian',
            'sarana-rapat',
            'work-order', 'pemeliharaan',
        ]);

        foreach ($moduls as $modul) {
            $this->withSession($this->adminSession())
                ->postJson(route('dashboard.teknisi.bulk-delete'), ['modul' => $modul, 'ids' => [999999999]])
                ->assertStatus(200)
                ->assertJsonPath('count', 0);
        }

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.teknisi.bulk-delete'), ['modul' => 'modul-ngawur', 'ids' => [1]])
            ->assertStatus(404);
    }

    /**
     * Bulk delete hanya menghapus baris milik pengguna; milik teknisi lain dilewati.
     */
    public function test_bulk_delete_hanya_menghapus_milik_pengguna(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $judulA = 'Bulk A ' . uniqid();
        $judulB = 'Bulk B ' . uniqid();

        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal' => now()->toDateString(), 'subjek' => $judulA, 'kategori' => 'genset',
            ])
            ->assertStatus(200);
        $this->withSession($b)
            ->postJson(route('dashboard.teknisi.modul.store', ['modul' => 'inspeksi-utilitas']), [
                'tanggal' => now()->toDateString(), 'subjek' => $judulB, 'kategori' => 'pompa_air',
            ])
            ->assertStatus(200);

        $idA = (int) DB::table('teknisi_log_harian')->where('subjek', $judulA)->value('id');
        $idB = (int) DB::table('teknisi_log_harian')->where('subjek', $judulB)->value('id');

        // B mencoba menghapus milik A: dilewati, tidak terhapus.
        $this->withSession($b)
            ->postJson(route('dashboard.teknisi.bulk-delete'), ['modul' => 'inspeksi-utilitas', 'ids' => [$idA]])
            ->assertStatus(200)
            ->assertJsonPath('count', 0)
            ->assertJsonPath('skipped', 1);
        $this->assertNotNull(DB::table('teknisi_log_harian')->where('id', $idA)->first());

        // A menghapus miliknya sendiri: terhapus.
        $this->withSession($a)
            ->postJson(route('dashboard.teknisi.bulk-delete'), ['modul' => 'inspeksi-utilitas', 'ids' => [$idA]])
            ->assertStatus(200)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('skipped', 0);
        $this->assertNull(DB::table('teknisi_log_harian')->where('id', $idA)->first());
    }

    /**
     * Regresi: cakupan berdasar PENGGUNA login, bukan PJ ruang.
     *
     * Baris yang `petugas_ptk_id`-nya milik pengguna login WAJIB muncul walau
     * `user_id`-nya akun lain (satu orang >1 akun), dan baris PM yang
     * `penanggung_jawab`-nya nama pengguna login juga muncul. Baris milik
     * petugas lain tidak muncul.
     */
    public function test_cakupan_pengguna_login_bukan_pj_ruang(): void
    {
        $a = $this->teknisiSession(0);
        $b = $this->teknisiSession(1);

        $ptkA = (string) $a['user']['ptk_id'];
        $ptkB = (string) $b['user']['ptk_id'];
        $userB = (string) $b['user']['pengguna_id'];

        $areaA = 'Regr-AT-A ' . uniqid(); // ptk A, user_id B -> A harus lihat
        $areaB = 'Regr-AT-B ' . uniqid(); // ptk B, user_id lain -> A tidak lihat
        $pmA   = 'Regr-PM-A ' . uniqid(); // penanggung_jawab = nama A
        $pmB   = 'Regr-PM-B ' . uniqid(); // penanggung_jawab = nama lain

        $now = now();
        DB::table('teknisi_area_tugas')->insert([
            ['nomor_jadwal' => 'REGR-' . uniqid(), 'user_id' => $userB, 'area' => $areaA,
             'petugas_ptk_id' => $ptkA, 'petugas_nama' => $a['user']['nama'],
             'periode_jadwal' => 'harian', 'status' => 'aktif', 'created_at' => $now, 'updated_at' => $now],
            ['nomor_jadwal' => 'REGR-' . uniqid(), 'user_id' => 'ffffffff-0000-0000-0000-000000000000',
             'area' => $areaB, 'petugas_ptk_id' => $ptkB, 'petugas_nama' => $b['user']['nama'],
             'periode_jadwal' => 'harian', 'status' => 'aktif', 'created_at' => $now, 'updated_at' => $now],
        ]);

        if (Table::exists('teknisi_pemeliharaan')) {
            DB::table('teknisi_pemeliharaan')->insert([
                ['kode_pemeliharaan' => 'REGR-' . uniqid(), 'nama_kegiatan' => $pmA,
                 'kategori' => 'bangunan', 'lokasi_aset' => 'Ruang Uji Regr',
                 'frekuensi' => 'bulanan', 'tgl_jadwal' => $now->toDateString(),
                 'penanggung_jawab' => $a['user']['nama'], 'status' => 'terjadwal',
                 'user_id' => $userB, 'created_at' => $now, 'updated_at' => $now],
                ['kode_pemeliharaan' => 'REGR-' . uniqid(), 'nama_kegiatan' => $pmB,
                 'kategori' => 'bangunan', 'lokasi_aset' => 'Ruang Uji Regr',
                 'frekuensi' => 'bulanan', 'tgl_jadwal' => $now->toDateString(),
                 'penanggung_jawab' => 'Orang Lain ' . uniqid(), 'status' => 'terjadwal',
                 'user_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // A melihat baris ber-ptk A (walau user_id-nya akun B), bukan baris ptk B.
        $this->withSession($a)
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($areaA, false)
            ->assertDontSee($areaB, false);

        // Teknisi B melihat baris miliknya sendiri (user_id cocok).
        $this->withSession($b)
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($areaA, false);

        if (Table::exists('teknisi_pemeliharaan')) {
            $this->withSession($a)
                ->get(route('dashboard.teknisi.pemeliharaan'))
                ->assertStatus(200)
                ->assertSee($pmA, false)
                ->assertDontSee($pmB, false);
        }

        // Koordinator melihat semuanya.
        $this->withSession($this->adminSession())
            ->get(route('dashboard.teknisi.area-tugas.index'))
            ->assertStatus(200)
            ->assertSee($areaA, false)
            ->assertSee($areaB, false);
    }
}
