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
 *  - Sepuluh modul generik dapat dibuka, diunduh templatenya, dan dicetak.
 *
 * Berjalan di database operasional: cleanup hanya menghapus baris baru
 * (snapshot id di setUp).
 */
class TeknisiModulLapanganTest extends TestCase
{
    protected const MODULES = [
        'area-master',
        'inspeksi-utilitas',
        'apar-k3',
        'pest-control',
        'higiene-kantin',
        'pemakaian-bahan',
        'usulan-pengadaan',
        'tindak-lanjut',
        'aset-mutasi',
        'peminjaman-ruang',
    ];

    /** @var array<string, array<int, int>> */
    protected array $preexistingIds = [];

    /** @var array<string, int> */
    protected array $preexistingMaxIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $tables = array_merge(
            ['teknisi_area_master', 'teknisi_log_harian', 'teknisi_usulan_pengadaan',
             'teknisi_tindak_lanjut', 'teknisi_aset_mutasi', 'sarpras_peminjaman_ruang'],
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

    public function test_sepuluh_modul_dapat_dibuka_dicetak_dan_diunduh_template(): void
    {
        $session = $this->adminSession();

        foreach (self::MODULES as $modul) {
            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.index', ['modul' => $modul]))
                ->assertStatus(200);

            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.template', ['modul' => $modul]))
                ->assertStatus(200);

            $this->withSession($session)
                ->get(route('dashboard.teknisi.modul.cetak', ['modul' => $modul, 'periode' => 'tahun', 'tahun' => 2026]))
                ->assertStatus(200);
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

    public function test_modul_tidak_dikenal_ditolak_route(): void
    {
        $this->withSession($this->adminSession())
            ->get('/dashboard/teknisi/modul/modul-ngawur')
            ->assertStatus(404);
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
}
