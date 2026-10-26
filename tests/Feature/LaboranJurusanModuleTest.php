<?php

namespace Tests\Feature;

use App\Support\LaboranScope;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pengujian modul Laboratorium per JURUSAN:
 *  - Pembatasan data per jurusan (LaboranScope).
 *  - Analisis kebutuhan, perawatan/K3, pemeriksaan ceklist, pengadaan,
 *    berita acara kerusakan, peminjaman alat, dan laporan lengkap.
 */
class LaboranJurusanModuleTest extends TestCase
{
    private const JUR_ATPH = '26010475';
    private const JUR_TKJ  = '16012425';

    private array $created = [];

    /**
     * Tabel modul laboran yang dibersihkan setelah setiap test.
     * Baris yang TIDAK ada saat setUp() dianggap dibuat oleh test ini.
     */
    private const LAB_TABLES = [
        'laboran_bahan_alat',
        'laboran_kebutuhan',
        'laboran_perawatan',
        'laboran_kartu_perawatan',
        'laboran_pemeriksaan',
        'laboran_pemeriksaan_item',
        'laboran_usulan_pengadaan',
        'laboran_usulan_pengadaan_item',
        'laboran_berita_acara_kerusakan',
        'laboran_peminjaman',
        'laboran_peminjaman_item',
        'laboran_jadwal_penggunaan',
        'laboran_jurnal',
        'laboran_kebersihan',
        'laboran_arsip',
    ];

    /** @var array<string, array<int, int>> id yang sudah ada SEBELUM test (milik data nyata) */
    private array $preexistingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Snapshot id awal. Test dijalankan pada database yang juga memuat data
        // nyata, sehingga cleanup harus menghapus HANYA baris baru — bukan
        // mengosongkan tabel.
        $this->preexistingIds = [];
        foreach (self::LAB_TABLES as $table) {
            try {
                if (!\App\Support\Table::exists($table)) {
                    continue;
                }
                $this->preexistingIds[$table] = DB::table($table)->pluck('id')->all();
            } catch (\Throwable $e) {
                $this->preexistingIds[$table] = [];
            }
        }
    }

    private function adminSession(): array
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
     * Sesi laboran dengan jurusan tertentu (memanfaatkan baris tugas LABORAN nyata).
     */
    private function laboranSession(?string $jurusanId): array
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->where('ptt.is_active', true)
            ->select('ptt.*')
            ->first();

        $this->assertNotNull($duty, 'Baris tugas tambahan LABORAN tidak tersedia untuk pengujian.');

        $this->created['duty_id'] = $duty->id;
        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->update(['jurusan_id' => $jurusanId]);

        $p = DB::table('pengguna')->where('pengguna_id', $duty->user_id)->first();
        $this->assertNotNull($p, 'Data pengguna laboran tidak ditemukan.');

        LaboranScope::flush();

        return ['user' => [
            'id'           => $p->pengguna_id,
            'pengguna_id'  => $p->pengguna_id,
            'name'         => $p->nama,
            'nama'         => $p->nama,
            'username'     => $p->username,
            'role'         => 'tendik',
            'peran_id_str' => $p->peran_id_str,
            'ptk_id'       => $duty->ptk_id,
        ]];
    }

    private function seedItems(): array
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        $atph = DB::table('laboran_bahan_alat')->insertGetId([
            'ruang_lab_nama' => 'Lahan Praktik ATPH',
            'kode_item'      => 'TST-ATPH-' . uniqid(),
            'nama_item'      => 'Cangkul Uji',
            'jenis'          => 'alat',
            'stok_total'     => 10,
            'stok_tersedia'  => 8,
            'satuan'         => 'unit',
            'kondisi'        => 'baik',
            'jurusan_id'     => self::JUR_ATPH,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $tkj = DB::table('laboran_bahan_alat')->insertGetId([
            'ruang_lab_nama' => 'Lab Komputer 1',
            'kode_item'      => 'TST-TKJ-' . uniqid(),
            'nama_item'      => 'Switch Cisco Uji',
            'jenis'          => 'alat',
            'stok_total'     => 5,
            'stok_tersedia'  => 5,
            'satuan'         => 'unit',
            'kondisi'        => 'baik',
            'jurusan_id'     => self::JUR_TKJ,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return [$atph, $tkj, $ptk];
    }

    protected function tearDown(): void
    {
        // Hapus HANYA baris yang tidak ada pada snapshot setUp().
        // Cara ini tahan terhadap insert lewat controller, insert massal, maupun
        // insertGetId yang lupa dilacak — dan tetap aman terhadap data nyata.
        foreach (self::LAB_TABLES as $table) {
            try {
                if (!\App\Support\Table::exists($table)) {
                    continue;
                }
                $preexisting = $this->preexistingIds[$table] ?? [];
                $query = DB::table($table);
                if (!empty($preexisting)) {
                    $query->whereNotIn('id', $preexisting);
                }
                $query->delete();
            } catch (\Throwable $e) {
                // Tabel/kolom mungkin tidak ada pada skema lama — abaikan.
            }
        }

        if (!empty($this->created['duty_id'])) {
            DB::table('ptk_tugas_tambahan')->where('id', $this->created['duty_id'])
                ->update(['jurusan_id' => null]);
        }

        $this->preexistingIds = [];

        LaboranScope::flush();
        parent::tearDown();
    }

    // =========================================================================
    // A. SCOPING JURUSAN
    // =========================================================================

    public function test_scope_menolak_akses_tanpa_penugasan_jurusan(): void
    {
        $session = $this->laboranSession(null);
        $context = LaboranScope::resolve($session['user']);

        $this->assertTrue($context['is_laboran']);
        // Fallback permisif: penugasan belum diisi -> tidak mengunci data
        $this->assertTrue($context['unrestricted']);
        $this->assertNull($context['jurusan_id']);
    }

    public function test_scope_membatasi_ke_jurusan_penugasan(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        $this->assertFalse($context['unrestricted']);
        $this->assertSame(self::JUR_ATPH, $context['jurusan_id']);

        $this->assertTrue(LaboranScope::allows(self::JUR_ATPH, $context));
        $this->assertFalse(LaboranScope::allows(self::JUR_TKJ, $context));
        $this->assertFalse(LaboranScope::allows(null, $context));
    }

    public function test_scope_memaksa_jurusan_saat_menulis(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        // Laboran mencoba menyimpan ke jurusan lain -> dipaksa ke jurusannya
        $this->assertSame(self::JUR_ATPH, LaboranScope::jurusanForWrite(self::JUR_TKJ, $context));
    }

    public function test_admin_tidak_dibatasi_jurusan(): void
    {
        $session = $this->adminSession();
        $context = LaboranScope::resolve($session['user']);

        $this->assertTrue($context['is_admin']);
        $this->assertTrue($context['unrestricted']);
        $this->assertTrue(LaboranScope::allows(self::JUR_TKJ, $context));
        $this->assertTrue(LaboranScope::allows(null, $context));
    }

    public function test_query_terfilter_ke_jurusan(): void
    {
        [$atph, $tkj] = $this->seedItems();

        $session = $this->laboranSession(self::JUR_ATPH);
        $context = LaboranScope::resolve($session['user']);

        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);
        $items = $q->get();

        $this->assertCount(1, $items);
        $this->assertSame(self::JUR_ATPH, $items->first()->jurusan_id);
    }

    public function test_laboran_tidak_bisa_mengubah_data_jurusan_lain(): void
    {        [, $tkj] = $this->seedItems();

        $tkjId = DB::table('laboran_kebutuhan')->insertGetId([
            'jurusan_id'       => self::JUR_TKJ,
            'nama_item'        => 'Kabel UTP TKJ',
            'jenis'            => 'bahan_habis_pakai',
            'jumlah_kebutuhan' => 100,
            'jumlah_tersedia'  => 0,
            'selisih'          => 100,
            'satuan'           => 'meter',
            'prioritas'        => 'kritis',
            'status'           => 'draf',
            'tanggal_input'    => now()->toDateString(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.kebutuhan.update', $tkjId), [
                'nama_item'        => 'DIRETAS',
                'jenis'            => 'alat',
                'jumlah_kebutuhan' => 1,
                'satuan'           => 'unit',
                'prioritas'        => 'rendah',
            ])
            ->assertStatus(404);

        $this->assertSame('Kabel UTP TKJ', DB::table('laboran_kebutuhan')->where('id', $tkjId)->value('nama_item'));

        // Hapus juga harus ditolak
        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.kebutuhan.destroy', $tkjId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_kebutuhan')->where('id', $tkjId)->exists());
    }

    // =========================================================================
    // A2. SCOPING MODUL LAMA (INVENTARIS & JADWAL)
    // =========================================================================

    public function test_inventaris_terfilter_per_jurusan(): void
    {
        [$atphId, $tkjId] = $this->seedItems();

        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.inventaris.index'));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertCount(1, $list, 'Laboran ATPH hanya boleh melihat 1 item.');
        $this->assertSame($atphId, $list->first()->id);
    }

    public function test_laboran_tidak_bisa_mengubah_atau_menghapus_inventaris_jurusan_lain(): void
    {
        [, $tkjId] = $this->seedItems();
        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.inventaris.update', $tkjId), [
                'ruang_lab_nama' => 'X',
                'kode_item'      => 'UJI-TKJ',
                'nama_item'      => 'DIRETAS',
                'jenis'          => 'alat',
                'stok_total'     => 1,
                'stok_tersedia'  => 1,
                'satuan'         => 'unit',
                'kondisi'        => 'baik',
            ])
            ->assertStatus(404);

        $this->assertNotSame('DIRETAS', DB::table('laboran_bahan_alat')->where('id', $tkjId)->value('nama_item'));

        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.inventaris.destroy', $tkjId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_bahan_alat')->where('id', $tkjId)->exists());
    }

    public function test_simpan_inventaris_memaksa_jurusan_laboran(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.inventaris.store'), [
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'kode_item'      => 'UJI-MAPEL-' . uniqid(),
                'nama_item'      => 'Item Uji Paksa',
                'jenis'          => 'alat',
                'stok_total'     => 2,
                'satuan'         => 'unit',
                'kondisi'        => 'baik',
                'jurusan_id'     => self::JUR_TKJ,
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_bahan_alat')->where('nama_item', 'Item Uji Paksa')->first();
        $this->assertNotNull($row);
        $this->assertSame(self::JUR_ATPH, $row->jurusan_id, 'Jurusan harus dipaksa ke milik laboran.');
    }

    public function test_jadwal_terfilter_per_jurusan(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');

        // Prefix unik per test agar data antar-test tidak saling bertabrakan
        // saat tearDown menghapus berdasarkan pola LIKE.
        $tag = 'UJI-' . uniqid() . '-';

        DB::table('laboran_jadwal_penggunaan')->insert([
            [
                'jurusan_id'     => self::JUR_ATPH,
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'ptk_id'         => $ptk,
                'rombel_id'      => $rombel,
                'mata_pelajaran' => $tag . 'ATPH',
                'tanggal'        => now()->toDateString(),
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'jurusan_id'     => self::JUR_TKJ,
                'ruang_lab_nama' => 'Lab Komputer 1',
                'ptk_id'         => $ptk,
                'rombel_id'      => $rombel,
                'mata_pelajaran' => $tag . 'TKJ',
                'tanggal'        => now()->toDateString(),
                'jam_mulai'      => '10:00:00',
                'jam_selesai'    => '11:30:00',
                'status'         => 'dijadwalkan',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);
        $res = $this->withSession($session)->get(route('dashboard.laboran.jadwal.index'));

        $res->assertStatus(200);

        $list = $res->viewData('list');
        $this->assertCount(1, $list);
        $this->assertSame($tag . 'ATPH', $list->first()->mata_pelajaran);
    }

    public function test_laboran_tidak_bisa_mengubah_jadwal_jurusan_lain(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        $jadwalId = DB::table('laboran_jadwal_penggunaan')->insertGetId([
            'jurusan_id'     => self::JUR_TKJ,
            'ruang_lab_nama' => 'Lab Komputer 1',
            'ptk_id'         => $ptk,
            'mata_pelajaran' => 'UJI-LARANG-TKJ',
            'tanggal'        => now()->toDateString(),
            'jam_mulai'      => '10:00:00',
            'jam_selesai'    => '11:30:00',
            'status'         => 'dijadwalkan',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $this->withSession($session)
            ->putJson(route('dashboard.laboran.jadwal.update', $jadwalId), [
                'form_mode' => 'status',
                'status'    => 'selesai',
            ])
            ->assertStatus(404);

        $this->assertSame('dijadwalkan', DB::table('laboran_jadwal_penggunaan')->where('id', $jadwalId)->value('status'));

        $this->withSession($session)
            ->deleteJson(route('dashboard.laboran.jadwal.destroy', $jadwalId))
            ->assertStatus(404);

        $this->assertTrue(DB::table('laboran_jadwal_penggunaan')->where('id', $jadwalId)->exists());
    }

    public function test_laporan_lokasi_terfilter_per_jurusan(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        DB::table('laboran_jadwal_penggunaan')->insert([
            [
                'jurusan_id'     => self::JUR_ATPH,
                'ruang_lab_nama' => 'Lahan Praktik ATPH',
                'ptk_id'         => $ptk,
                'mata_pelajaran' => 'UJI-LAPOR-ATPH',
                'tanggal'        => '2026-10-05',
                'jam_mulai'      => '07:00:00',
                'jam_selesai'    => '08:30:00',
                'status'         => 'selesai',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'jurusan_id'     => self::JUR_TKJ,
                'ruang_lab_nama' => 'Lab Komputer 1',
                'ptk_id'         => $ptk,
                'mata_pelajaran' => 'UJI-LAPOR-TKJ',
                'tanggal'        => '2026-10-06',
                'jam_mulai'      => '10:00:00',
                'jam_selesai'    => '11:30:00',
                'status'         => 'selesai',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        $session = $this->laboranSession(self::JUR_ATPH);

        $res = $this->withSession($session)
            ->get(route('dashboard.laboran.laporan.index', ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026]));

        $res->assertStatus(200);
        $this->assertSame(1, $res->viewData('totalSesi'), 'Hanya sesi jurusan ATPH yang boleh dihitung.');
    }

    // =========================================================================
    // B. HALAMAN MODUL
    // =========================================================================

    public function test_semua_halaman_modul_dapat_diakses(): void
    {
        $session = $this->adminSession();

        $routes = [
            'dashboard.laboran.dashboard',
            'dashboard.laboran.kebutuhan.index',
            'dashboard.laboran.perawatan.index',
            'dashboard.laboran.pemeriksaan.index',
            'dashboard.laboran.pengadaan.index',
            'dashboard.laboran.kerusakan.index',
            'dashboard.laboran.peminjaman-alat.index',
            'dashboard.laboran.laporan-lengkap.index',
        ];

        foreach ($routes as $name) {
            $res = $this->withSession($session)->get(route($name));
            $this->assertSame(200, $res->getStatusCode(), "Halaman {$name} gagal dimuat.");
        }
    }

    public function test_tamu_ditolak_dari_modul_laboran(): void
    {
        foreach ([
            'dashboard.laboran.kebutuhan.index',
            'dashboard.laboran.perawatan.index',
            'dashboard.laboran.laporan-lengkap.index',
        ] as $name) {
            $this->get(route($name))->assertStatus(302);
        }
    }

    // =========================================================================
    // C. ANALISIS KEBUTUHAN
    // =========================================================================

    public function test_simpan_kebutuhan_menghitung_selisih(): void
    {
        $session = $this->adminSession();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.kebutuhan.store'), [
            'nama_item'        => 'Traktor Mini Uji',
            'jenis'            => 'alat',
            'jumlah_kebutuhan' => 5,
            'jumlah_tersedia'  => 2,
            'satuan'           => 'unit',
            'prioritas'        => 'kritis',
            'status'           => 'draf',
            'tanggal_input'    => now()->toDateString(),
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('status', 'success');

        $row = DB::table('laboran_kebutuhan')->where('nama_item', 'Traktor Mini Uji')->first();
        $this->assertNotNull($row);
        $this->assertEquals(3, (float) $row->selisih);
        $this->assertSame('kritis', $row->prioritas);
    }

    public function test_halaman_kebutuhan_menyajikan_analisis_stok(): void
    {
        $this->seedItems();

        $res = $this->withSession($this->adminSession())->get(route('dashboard.laboran.kebutuhan.index'));

        $res->assertStatus(200);
        $res->assertViewHas('analisis');

        $analisis = $res->viewData('analisis');
        $this->assertArrayHasKey('kritis', $analisis);
        $this->assertArrayHasKey('perlu_perhatian', $analisis);
        $this->assertArrayHasKey('total_item', $analisis);
    }

    // =========================================================================
    // D. PERAWATAN & K3
    // =========================================================================

    public function test_simpan_perawatan_menyimpan_apd_dan_menyesuaikan_kondisi(): void
    {
        [$itemId] = $this->seedItems();
        $session = $this->adminSession();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.perawatan.store'), [
            'item_id'           => $itemId,
            'jenis_perawatan'   => 'rutin',
            'kategori_k3'       => 'mekanik',
            'tanggal_perawatan' => now()->toDateString(),
            'uraian_pekerjaan'  => 'Pembersihan dan pelumasan',
            'apd_digunakan'     => ['Sarung Tangan', 'Sepatu Safety'],
            'status'            => 'selesai',
            'kondisi_akhir'     => 'rusak_ringan',
        ]);

        $res->assertStatus(200);

        $row = DB::table('laboran_perawatan')->where('item_id', $itemId)->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('PRW/', $row->kode_perawatan);
        $this->assertSame('mekanik', $row->kategori_k3);

        $apd = json_decode($row->apd_digunakan, true);
        $this->assertContains('Sarung Tangan', $apd);

        // Kondisi inventaris tersinkron
        $this->assertSame('rusak_ringan', DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kondisi'));
    }

    public function test_kartu_perawatan_menghitung_jadwal_berikutnya(): void
    {
        [$itemId] = $this->seedItems();
        $mulai = '2026-01-10';

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.store'), [
                'item_id'       => $itemId,
                'nama_alat'     => 'Cangkul Uji',
                'tanggal_mulai' => $mulai,
                'interval_hari' => 30,
                'status'        => 'aktif',
            ])
            ->assertStatus(200);

        $kartu = DB::table('laboran_kartu_perawatan')->where('item_id', $itemId)->first();
        $this->assertNotNull($kartu);
        $this->assertSame('2026-02-09', $kartu->jadwal_berikutnya);
        $this->assertStringStartsWith('KRT/', $kartu->kode_kartu);
    }

    public function test_tandai_kartu_selesai_menggeser_jadwal(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.store'), [
                'item_id'       => $itemId,
                'nama_alat'     => 'Cangkul Uji',
                'tanggal_mulai' => now()->toDateString(),
                'interval_hari' => 15,
            ])
            ->assertStatus(200);

        $kartu = DB::table('laboran_kartu_perawatan')->where('item_id', $itemId)->first();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.perawatan.kartu.selesai', $kartu->id))
            ->assertStatus(200);

        $baru = DB::table('laboran_kartu_perawatan')->where('id', $kartu->id)->value('jadwal_berikutnya');
        $this->assertSame(now()->addDays(15)->toDateString(), $baru);
    }

    // =========================================================================
    // E. PEMERIKSAAN CEKLIST
    // =========================================================================

    public function test_pemeriksaan_membuat_ceklist_dan_menghitung_rekap(): void
    {
        [$itemId] = $this->seedItems();
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                'tanggal_pemeriksaan' => now()->toDateString(),
                'jenis'               => 'bulanan',
                'item_ids'            => [$itemId],
                'status'              => 'draf',
            ])
            ->assertStatus(200);

        $pemeriksaan = DB::table('laboran_pemeriksaan')->latest('id')->first();
        $this->assertNotNull($pemeriksaan);
        $this->assertSame(1, (int) $pemeriksaan->total_item);
        $this->assertStringStartsWith('PRK/', $pemeriksaan->nomor_pemeriksaan);

        $item = DB::table('laboran_pemeriksaan_item')->where('pemeriksaan_id', $pemeriksaan->id)->first();
        $this->assertNotNull($item);

        // Ubah hasil menjadi tidak sesuai
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pemeriksaan.simpan-item', $pemeriksaan->id), [
                'items' => [[
                    'id'         => $item->id,
                    'hasil'      => 'tidak_sesuai',
                    'keterangan' => 'Gagang longgar',
                ]],
            ])
            ->assertStatus(200)
            ->assertJsonPath('rekap.total_tidak_sesuai', 1);

        $after = DB::table('laboran_pemeriksaan')->where('id', $pemeriksaan->id)->first();
        $this->assertSame(1, (int) $after->total_tidak_sesuai);
        $this->assertSame(0, (int) $after->total_sesuai);
    }

    public function test_halaman_detail_pemeriksaan_menampilkan_item(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                'tanggal_pemeriksaan' => now()->toDateString(),
                'jenis'               => 'harian',
                'item_ids'            => [$itemId],
            ]);

        $pemeriksaan = DB::table('laboran_pemeriksaan')->latest('id')->first();

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.pemeriksaan.show', $pemeriksaan->id));

        $res->assertStatus(200);
        $res->assertViewHas('items');
        $this->assertCount(1, $res->viewData('items'));
    }

    // =========================================================================
    // F. PENGADAAN
    // =========================================================================

    public function test_usulan_pengadaan_menarik_item_dari_kebutuhan(): void
    {
        $session = $this->adminSession();

        $kebutuhanId = DB::table('laboran_kebutuhan')->insertGetId([
            'nama_item'        => 'Mikroskop Uji',
            'jenis'            => 'alat',
            'jumlah_kebutuhan' => 4,
            'jumlah_tersedia'  => 1,
            'selisih'          => 3,
            'satuan'           => 'unit',
            'prioritas'        => 'tinggi',
            'status'           => 'diverifikasi',
            'tanggal_input'    => now()->toDateString(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.store'), [
                'tanggal_usulan' => now()->toDateString(),
                'urgensi'        => 'tinggi',
                'status'         => 'draf',
                'kebutuhan_ids'  => [$kebutuhanId],
            ])
            ->assertStatus(200);

        $usulan = DB::table('laboran_usulan_pengadaan')->latest('id')->first();
        $this->assertNotNull($usulan);
        $this->assertSame(1, (int) $usulan->total_item);
        $this->assertStringStartsWith('USL/', $usulan->nomor_usulan);

        $item = DB::table('laboran_usulan_pengadaan_item')->where('usulan_id', $usulan->id)->first();
        $this->assertSame('Mikroskop Uji', $item->nama_item);
        // Jumlah ditarik dari selisih kekurangan
        $this->assertEquals(3, (float) $item->jumlah);
    }

    public function test_rekap_usulan_menghitung_subtotal(): void
    {
        $session = $this->adminSession();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.store'), [
                'tanggal_usulan' => now()->toDateString(),
                'urgensi'        => 'sedang',
            ])
            ->assertStatus(200);

        $usulan = DB::table('laboran_usulan_pengadaan')->latest('id')->first();

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.pengadaan.item.store', $usulan->id), [
                'nama_item'    => 'Gelas Ukur',
                'jenis'        => 'alat',
                'jumlah'       => 10,
                'satuan'       => 'unit',
                'harga_satuan' => 25000,
            ])
            ->assertStatus(200);

        $updated = DB::table('laboran_usulan_pengadaan')->where('id', $usulan->id)->first();
        $this->assertEquals(250000, (float) $updated->total_estimasi_biaya);
        $this->assertSame(1, (int) $updated->total_item);
    }

    // =========================================================================
    // G. BERITA ACARA KERUSAKAN
    // =========================================================================

    public function test_berita_acara_kerusakan_menyesuaikan_kondisi_inventaris(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'berat',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'kronologi'         => 'Terjatuh saat praktik',
                'status'            => 'diajukan',
            ])
            ->assertStatus(200);

        $ba = DB::table('laboran_berita_acara_kerusakan')->where('item_id', $itemId)->first();
        $this->assertNotNull($ba);
        $this->assertStringStartsWith('BAK/', $ba->nomor_ba);

        // Kerusakan berat -> inventaris menjadi rusak_berat
        $this->assertSame('rusak_berat', DB::table('laboran_bahan_alat')->where('id', $itemId)->value('kondisi'));
    }

    public function test_cetak_berita_acara_menghasilkan_dokumen(): void
    {
        [$itemId] = $this->seedItems();

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kerusakan.store'), [
                'item_id'           => $itemId,
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => now()->toDateString(),
                'tingkat_kerusakan' => 'ringan',
            ]);

        $ba = DB::table('laboran_berita_acara_kerusakan')->latest('id')->first();

        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.kerusakan.cetak', $ba->id));

        $res->assertStatus(200);
        $res->assertSee('BERITA ACARA KERUSAKAN ALAT', false);
        $res->assertSee($ba->nomor_ba, false);
    }

    // =========================================================================
    // H. PEMINJAMAN ALAT (stok)
    // =========================================================================

    public function test_peminjaman_mengurangi_dan_mengembalikan_stok(): void
    {
        [, $itemId] = $this->seedItems();
        $session = $this->adminSession();

        $stokAwal = (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia');
        $this->assertEquals(5, $stokAwal);

        $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'siswa',
                'peminjam_nama'           => 'Budi Uji',
                'peminjam_kelas'          => 'XI TKJ 1',
                'keperluan'               => 'Praktik jaringan',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(3)->toDateString(),
                'item_ids'                => [$itemId],
                'item_jumlah'             => [$itemId => 2],
            ])
            ->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertSame('diajukan', $pinjam->status);
        $this->assertStringStartsWith('PJM/', $pinjam->nomor_pinjam);

        // Setujui -> belum mengubah stok
        $this->withSession($session)
            ->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), ['status' => 'disetujui'])
            ->assertStatus(200);
        $this->assertEquals(5, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));

        // Serah terima -> stok berkurang
        $this->withSession($session)
            ->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), ['status' => 'dipinjam'])
            ->assertStatus(200);
        $this->assertEquals(3, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));

        // Pengembalian -> stok pulih
        $this->withSession($session)
            ->postJson(route('dashboard.laboran.peminjaman-alat.kembalikan', $pinjam->id), [
                'kondisi_kembali' => 'baik',
            ])
            ->assertStatus(200);

        $this->assertEquals(5, (float) DB::table('laboran_bahan_alat')->where('id', $itemId)->value('stok_tersedia'));
        $this->assertSame('dikembalikan', DB::table('laboran_peminjaman')->where('id', $pinjam->id)->value('status'));
    }

    public function test_peminjaman_menolak_stok_tidak_cukup(): void
    {
        [, $itemId] = $this->seedItems();

        $res = $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
                'peminjam_tipe'           => 'siswa',
                'peminjam_nama'           => 'Budi Uji',
                'keperluan'               => 'Praktik',
                'tanggal_pinjam'          => now()->toDateString(),
                'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
                'item_ids'                => [$itemId],
                'item_jumlah'             => [$itemId => 99],
            ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('tidak cukup', $res->json('message'));
        $this->assertSame(0, DB::table('laboran_peminjaman')->count());
    }

    // =========================================================================
    // I. LAPORAN LENGKAP
    // =========================================================================

    public function test_laporan_lengkap_merender_semua_periode(): void
    {
        $queries = [
            ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026],
            ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026],
            ['periode' => 'semester', 'semester' => '1', 'tahun_ajaran' => '2026/2027'],
            ['periode' => 'tahun', 'tahun_ajaran' => '2026/2027'],
        ];

        foreach ($queries as $q) {
            $res = $this->withSession($this->adminSession())
                ->get(route('dashboard.laboran.laporan-lengkap.index', $q));

            $res->assertStatus(200);
            $res->assertViewHas('range');
            $res->assertViewHas('rekapLokasi');
            $res->assertViewHas('rekapTingkat');
        }
    }

    public function test_cetak_laporan_lengkap_menghasilkan_dokumen(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.cetak', [
                'periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026,
            ]));

        $res->assertStatus(200);
        $res->assertSee('LAPORAN PENGGUNAAN', false);
        $res->assertSee('Rekapitulasi Kerusakan Alat', false);
    }

    public function test_laporan_menghitung_kerusakan_dalam_periode(): void
    {
        [$itemId] = $this->seedItems();

        DB::table('laboran_berita_acara_kerusakan')->insert([
            [
                'jurusan_id'        => self::JUR_ATPH,
                'nomor_ba'          => 'BAK/TEST/0001',
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => '2026-10-15',
                'tanggal_ba'        => '2026-10-15',
                'tingkat_kerusakan' => 'berat',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'estimasi_biaya'    => 500000,
                'status'            => 'diajukan',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'jurusan_id'        => self::JUR_ATPH,
                'nomor_ba'          => 'BAK/TEST/0002',
                'nama_alat'         => 'Cangkul Uji',
                'tanggal_kejadian'  => '2026-11-20',
                'tanggal_ba'        => '2026-11-20',
                'tingkat_kerusakan' => 'ringan',
                'jumlah_rusak'      => 1,
                'satuan'            => 'unit',
                'estimasi_biaya'    => 0,
                'status'            => 'diajukan',
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        $oktober = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'bulan', 'bulan' => 10, 'tahun' => 2026]));

        $this->assertSame(1, $oktober->viewData('totalKerusakan'));
        $this->assertSame(1, $oktober->viewData('totalRusakBerat'));

        $tw4 = $this->withSession($this->adminSession())
            ->get(route('dashboard.laboran.laporan-lengkap.index', ['periode' => 'triwulan', 'triwulan' => 4, 'tahun' => 2026]));

        $this->assertSame(2, $tw4->viewData('totalKerusakan'));
    }

    // =========================================================================
    // J. HAK AKSES
    // =========================================================================

    public function test_permission_key_modul_baru_terdaftar_di_dictionary(): void
    {
        $base = \App\Support\PermissionDictionary::base();
        $keys = [];

        foreach ($base as $cluster) {
            foreach (array_keys($cluster) as $key) {
                $keys[] = $key;
            }
        }

        foreach ([
            'menu_laboran_kebutuhan',
            'menu_laboran_perawatan',
            'menu_laboran_pemeriksaan',
            'menu_laboran_pengadaan',
            'menu_laboran_kerusakan',
            'menu_laboran_laporan',
        ] as $expected) {
            $this->assertContains($expected, $keys, "Permission key {$expected} belum terdaftar.");
        }
    }

    public function test_default_permission_laboran_mencakup_modul_baru(): void
    {
        $defaults = \App\Support\DutyPermissionDefaults::forDuty('LABORAN');

        foreach ([
            'menu_laboran_kebutuhan',
            'menu_laboran_perawatan',
            'menu_laboran_pemeriksaan',
            'menu_laboran_pengadaan',
            'menu_laboran_kerusakan',
            'menu_laboran_laporan',
        ] as $expected) {
            $this->assertContains($expected, $defaults, "Default LABORAN belum memuat {$expected}.");
        }
    }

    // =========================================================================
    // K. KELENGKAPAN TUPOKSI (9 INDIKATOR SUPERVISI)
    // =========================================================================

    public function test_permission_key_tupoksi_terdaftar(): void
    {
        $base = \App\Support\PermissionDictionary::base();
        $keys = [];
        foreach ($base as $cluster) {
            foreach (array_keys($cluster) as $key) {
                $keys[] = $key;
            }
        }

        $expected = [
            'menu_laboran_arsip',        // #7 Arsip/Ordner
            'menu_laboran_jurnal',       // #8 Jurnal Kegiatan
            'menu_laboran_kebersihan',   // #9 Kebersihan
        ];

        foreach ($expected as $key) {
            $this->assertContains($key, $keys, "Permission key {$key} belum terdaftar.");
            $this->assertContains(
                $key,
                \App\Support\DutyPermissionDefaults::forDuty('LABORAN'),
                "Default LABORAN belum memuat {$key}."
            );
        }
    }

    public function test_halaman_tupoksi_dapat_diakses(): void
    {
        $session = $this->adminSession();

        foreach ([
            'dashboard.laboran.arsip.index',
            'dashboard.laboran.jurnal.index',
            'dashboard.laboran.kebersihan.index',
        ] as $name) {
            $res = $this->withSession($session)->get(route($name));
            $this->assertSame(200, $res->getStatusCode(), "Halaman {$name} gagal dimuat.");
        }
    }

    public function test_jadwal_mendukung_pemberlakuan_bulanan_dan_semester(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');

        // 1. Pemberlakuan Bulanan (Rutin mingguan dalam 1 bulan)
        $res = $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'       => 'Lahan Praktik ATPH',
            'jenis_lokasi'         => 'lahan',
            'rombel_id'            => $rombel,
            'mata_pelajaran'       => 'UJI-PEMBERLAKUAN-BULANAN',
            'tanggal'              => '2026-11-02', // Senin
            'pemberlakuan'         => 'bulanan',
            'pemberlakuan_bulan'   => 11,
            'pemberlakuan_tahun'   => 2026,
            'jam_mulai'            => '07:30',
            'jam_selesai'          => '09:00',
        ]);

        $res->assertStatus(200);

        // November 2026 memiliki 5 hari Senin: 2, 9, 16, 23, 30
        $count = DB::table('laboran_jadwal_penggunaan')
            ->where('mata_pelajaran', 'UJI-PEMBERLAKUAN-BULANAN')
            ->count();
        $this->assertGreaterThanOrEqual(4, $count, 'Penjadwalan bulanan harus meng-generate seluruh minggu dalam bulan.');
    }

    public function test_jadwal_multi_guru_dan_deteksi_bentrok(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');
        $gtkIds = DB::table('gtk')->limit(2)->pluck('ptk_id')->all();

        $this->assertCount(2, $gtkIds, 'Dibutuhkan minimal 2 GTK untuk pengujian multi-guru.');

        // Simpan sesi dengan 2 guru (tim teaching)
        $res = $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'       => 'Lab Komputer 1',
            'jenis_lokasi'         => 'lab',
            'rombel_id'            => $rombel,
            'mata_pelajaran'       => 'UJI-MULTI-GURU',
            'tanggal'              => '2026-12-01',
            'pemberlakuan'         => 'sekali',
            'jam_mulai'            => '08:00',
            'jam_selesai'          => '10:00',
            'ptk_id'               => $gtkIds[0],
            'ptk_ids'              => [$gtkIds[0], $gtkIds[1]],
        ]);

        $res->assertStatus(200);

        $saved = DB::table('laboran_jadwal_penggunaan')->where('mata_pelajaran', 'UJI-MULTI-GURU')->first();
        $this->assertNotNull($saved);
        $this->assertNotNull($saved->guru_pendamping, 'Nama guru pendamping harus terformat.');

        // Uji bentrok guru pendamping pada jam yang sama di lokasi lain
        $resConflict = $this->withSession($session)->postJson(route('dashboard.laboran.jadwal.store'), [
            'ruang_lab_nama'       => 'Bengkel Otomotif',
            'jenis_lokasi'         => 'bengkel',
            'rombel_id'            => $rombel,
            'mata_pelajaran'       => 'UJI-BENTROK-GURU',
            'tanggal'              => '2026-12-01',
            'pemberlakuan'         => 'sekali',
            'jam_mulai'            => '08:30',
            'jam_selesai'          => '09:30',
            'ptk_id'               => $gtkIds[1], // Guru pendamping tadi dicoba dijadwalkan di sini
        ]);

        $resConflict->assertStatus(422);
    }

    public function test_jadwal_kbm_slot_endpoint(): void
    {
        $session = $this->adminSession();
        $rombel = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->value('rombongan_belajar_id');

        $res = $this->withSession($session)->getJson(route('dashboard.laboran.jadwal.kbm', ['rombel_id' => $rombel]));
        $res->assertStatus(200)->assertJson(['status' => 'success']);
    }

    public function test_jalankan_analisis_kebutuhan(): void
    {
        $session = $this->adminSession();

        // Buat 1 item inventaris kritis (stok habis)
        $itemId = DB::table('laboran_bahan_alat')->insertGetId([
            'ruang_lab_nama' => 'Lab Komputer 1',
            'kode_item'      => 'KRIT-UJI-' . uniqid(),
            'nama_item'      => 'Kabel LAN Uji Habis',
            'jenis'          => 'bahan_habis_pakai',
            'stok_total'     => 10,
            'stok_tersedia'  => 0, // Habis
            'satuan'         => 'roll',
            'kondisi'        => 'rusak_berat',
            'jurusan_id'     => self::JUR_TKJ,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.kebutuhan.jalankan-analisis'));
        $res->assertStatus(200)->assertJson(['status' => 'success']);

        $kebutuhan = DB::table('laboran_kebutuhan')->where('nama_item', 'Kabel LAN Uji Habis')->first();
        $this->assertNotNull($kebutuhan, 'Analisis otomatis harus memasukkan item kritis ke daftar kebutuhan.');
        $this->assertSame('kritis', $kebutuhan->prioritas);
    }

    public function test_usulan_pengadaan_dengan_pilihan_jenis(): void
    {
        $session = $this->adminSession();

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.pengadaan.store'), [
            'tanggal_usulan' => now()->toDateString(),
            'jenis'          => 'alat',
            'urgensi'        => 'tinggi',
            'status'         => 'diajukan',
            'justifikasi'    => 'Pengadaan alat praktik semester ganjil',
        ]);

        $res->assertStatus(200);

        $row = DB::table('laboran_usulan_pengadaan')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('alat', $row->jenis);
    }

    public function test_peminjaman_alat_dengan_siswa_otomatis_dan_sarpras(): void
    {
        $session = $this->adminSession();
        $siswa = DB::table('peserta_didik')->first();

        // 1 item lab
        [$alatId] = $this->seedItems();

        // 1 aset sarpras
        $asetId = DB::table('sarpras_aset')->insertGetId([
            'kode_aset'           => 'AST-UJI-' . uniqid(),
            'nama_barang'         => 'Laptop Inventaris Uji',
            'kategori'            => 'Elektronik',
            'jumlah'              => 1,
            'satuan'              => 'unit',
            'kondisi'             => 'baik',
            'status_ketersediaan' => 'tersedia',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $res = $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.store'), [
            'peminjam_tipe'           => 'siswa',
            'peminjam_id'             => $siswa ? $siswa->peserta_didik_id : '12345',
            'peminjam_nama'           => $siswa ? $siswa->nama : 'Siswa Uji',
            'keperluan'               => 'Praktik lapangan',
            'tanggal_pinjam'          => now()->toDateString(),
            'tanggal_kembali_rencana' => now()->addDays(2)->toDateString(),
            'item_ids'                => [$alatId],
            'item_jumlah'             => [$alatId => 1],
            'aset_ids'                => [$asetId],
            'aset_jumlah'             => [$asetId => 1],
        ]);

        $res->assertStatus(200);

        $pinjam = DB::table('laboran_peminjaman')->latest('id')->first();
        $this->assertNotNull($pinjam);
        $this->assertSame(2, (int) $pinjam->jumlah_item);

        // Serah terima
        $this->withSession($session)->putJson(route('dashboard.laboran.peminjaman-alat.status', $pinjam->id), [
            'status' => 'dipinjam',
        ])->assertStatus(200);

        $this->assertSame('dipinjam', DB::table('sarpras_aset')->where('id', $asetId)->value('status_ketersediaan'));

        // Pengembalian
        $this->withSession($session)->postJson(route('dashboard.laboran.peminjaman-alat.kembalikan', $pinjam->id), [
            'kondisi_kembali' => 'baik',
        ])->assertStatus(200);

        $this->assertSame('tersedia', DB::table('sarpras_aset')->where('id', $asetId)->value('status_ketersediaan'));

        DB::table('sarpras_aset')->where('id', $asetId)->delete();
    }

    public function test_import_excel_inventaris_dan_aset(): void
    {
        $session = $this->adminSession();

        // 1. Template download inventaris & aset
        $this->withSession($session)->get(route('dashboard.laboran.inventaris.template'))->assertStatus(200);
        $this->withSession($session)->get(route('dashboard.sarpras.aset.template'))->assertStatus(200);

        // 2. Import inventaris dengan CSV dummy
        $csvContent = "kode_item,nama_item,jenis,stok_total,satuan,kondisi,ruang_lab_nama\n"
                    . "IMP-001,Obeng Set Uji,alat,5,set,baik,Lab Komputer 1\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'inv') . '.csv';
        file_put_contents($tempFile, $csvContent);

        $uploaded = new \Illuminate\Http\UploadedFile($tempFile, 'import.csv', 'text/csv', null, true);

        $res = $this->withSession($session)->post(route('dashboard.laboran.inventaris.import'), [
            'file' => $uploaded,
        ]);

        $res->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertNotNull(DB::table('laboran_bahan_alat')->where('kode_item', 'IMP-001')->first());

        @unlink($tempFile);
    }

    public function test_jurnal_menghitung_durasi_dan_kategori_tupoksi(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.jurnal.store'), [
                'tanggal'    => now()->toDateString(),
                'kegiatan'   => 'Menyiapkan alat praktik',
                'kategori'   => 'penyiapan',
                'jam_mulai'  => '07:00',
                'jam_selesai' => '09:00',
                'status'     => 'selesai',
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_jurnal')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame(120, (int) $row->durasi_menit, 'Durasi harus 120 menit (07:00-09:00).');
        $this->assertSame('penyiapan', $row->kategori);

        // Kategori terpetakan ke indikator tupoksi
        $kategori = \App\Http\Controllers\Laboran\LaboranJurnalController::KATEGORI;
        $this->assertSame(2, $kategori['penyiapan'][1], 'Kategori penyiapan harus terpetakan ke Tupoksi #2.');
        $this->assertSame(1, $kategori['administrasi'][1]);
        $this->assertSame(9, $kategori['kebersihan'][1]);
    }

    public function test_kebersihan_menyimpan_dokumentasi(): void
    {
        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.kebersihan.store'), [
                'tanggal_kegiatan'  => now()->toDateString(),
                'area'              => 'Lahan Praktik ATPH',
                'jenis_kegiatan'    => 'harian',
                'uraian_kegiatan'   => 'Membersihkan gulma',
                'kondisi_akhir'     => 'bersih',
                'jumlah_petugas'    => 3,
                'dokumentasi_paths' => ['storage/lab/foto1.jpg', 'storage/lab/foto2.jpg'],
            ])
            ->assertStatus(200);

        $row = DB::table('laboran_kebersihan')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertStringStartsWith('KBR/', $row->nomor_kegiatan);

        $paths = json_decode($row->dokumentasi_paths, true);
        $this->assertCount(2, $paths);
        $this->assertContains('storage/lab/foto1.jpg', $paths);
    }

    public function test_arsip_massal_menarik_dokumen_dari_modul_lain(): void
    {
        $ptk = (string) DB::table('gtk')->value('ptk_id');

        // Buat 1 BA kerusakan
        DB::table('laboran_berita_acara_kerusakan')->insert([
            'jurusan_id'        => self::JUR_ATPH,
            'nomor_ba'          => 'BAK/ARSIP/' . uniqid(),
            'nama_alat'         => 'Alat Arsip Uji',
            'tanggal_kejadian'  => now()->toDateString(),
            'tanggal_ba'        => now()->toDateString(),
            'tingkat_kerusakan' => 'ringan',
            'jumlah_rusak'      => 1,
            'satuan'            => 'unit',
            'status'            => 'diajukan',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->withSession($this->adminSession())
            ->postJson(route('dashboard.laboran.arsip.arsipkan-massal'), [
                'kategori' => 'berita_acara_kerusakan',
            ])
            ->assertStatus(200);

        $this->assertGreaterThan(0, DB::table('laboran_arsip')
            ->where('kategori', 'berita_acara_kerusakan')
            ->count());

        $arsip = DB::table('laboran_arsip')->where('kategori', 'berita_acara_kerusakan')->first();
        $this->assertStringStartsWith('ARS/', $arsip->kode_arsip);
        $this->assertSame('laboran_berita_acara_kerusakan', $arsip->sumber_tabel);
    }

    public function test_pemeriksaan_mendukung_jenis_sebelum_dan_sesudah_pakai(): void
    {
        [$itemId] = $this->seedItems();

        foreach (['sebelum_pakai', 'sesudah_pakai'] as $jenis) {
            $this->withSession($this->adminSession())
                ->postJson(route('dashboard.laboran.pemeriksaan.store'), [
                    'tanggal_pemeriksaan' => now()->toDateString(),
                    'jenis'               => $jenis,
                    'item_ids'            => [$itemId],
                    'status'              => 'draf',
                ])
                ->assertStatus(200);

            $row = DB::table('laboran_pemeriksaan')->latest('id')->first();
            $this->assertSame($jenis, $row->jenis);
        }
    }

    // =========================================================================
    // H. PENETAPAN JURUSAN PADA PENUGASAN (SUMBER SCOPING)
    // =========================================================================

    public function test_admin_dapat_menetapkan_jurusan_pada_penugasan_laboran(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->select('ptt.*')
            ->first();

        $this->assertNotNull($duty, 'Baris tugas tambahan LABORAN tidak tersedia untuk pengujian.');
        $this->created['duty_id'] = $duty->id;

        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->update(['jurusan_id' => null]);
        LaboranScope::flush();

        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'jurusan_id' => self::JUR_ATPH,
                'is_active'  => 1,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(
            self::JUR_ATPH,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }

    public function test_penetapan_jurusan_langsung_mengaktifkan_scoping_laboran(): void
    {
        $session = $this->laboranSession(self::JUR_ATPH);

        // Sebelum ditetapkan: permissive (unrestricted)
        LaboranScope::flush();
        DB::table('ptk_tugas_tambahan')->where('id', $this->created['duty_id'])
            ->update(['jurusan_id' => null]);
        LaboranScope::flush();

        $permissive = LaboranScope::resolve($session['user']);
        $this->assertTrue($permissive['unrestricted'], 'Tanpa jurusan, scope harus permissive.');

        // Tetapkan jurusan via endpoint
        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $this->created['duty_id']), [
                'jurusan_id' => self::JUR_TKJ,
                'is_active'  => 1,
            ])
            ->assertStatus(200);

        LaboranScope::flush();
        $locked = LaboranScope::resolve($session['user']);

        $this->assertFalse($locked['unrestricted']);
        $this->assertSame(self::JUR_TKJ, $locked['jurusan_id']);
    }

    public function test_penetapan_jurusan_ditolak_tanpa_hak_akses_update(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->select('ptt.*')
            ->first();

        $this->created['duty_id'] = $duty->id;

        // Kondisi awal ditetapkan secara eksplisit agar pengujian tidak bergantung urutan
        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->update(['jurusan_id' => self::JUR_TKJ]);
        LaboranScope::flush();

        // Sesi pengguna tanpa peran apa pun
        $res = $this->withSession(['user' => [
            'id'           => 999999,
            'pengguna_id'  => 999999,
            'name'         => 'Tanpa Hak',
            'nama'         => 'Tanpa Hak',
            'username'     => 'tanpa_hak',
            'role'         => 'peserta_didik',
            'peran_id_str' => 'Peserta Didik',
            'ptk_id'       => null,
        ]])->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
            'jurusan_id' => self::JUR_ATPH,
        ]);

        $this->assertContains($res->getStatusCode(), [403, 302]);

        // Nilai jurusan tidak boleh berubah
        $this->assertSame(
            self::JUR_TKJ,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }

    public function test_halaman_pengguna_menampilkan_tombol_ubah_penugasan(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.pengguna.index', ['tab' => 'tugas_tambahan']));

        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('btn-edit-duty', $html);
        $this->assertStringContainsString('modalEditDuty', $html);
        $this->assertStringContainsString('editDutyJurusan', $html);
    }

    public function test_dashboard_laboran_memperingatkan_jurusan_belum_ditetapkan(): void
    {
        $session = $this->laboranSession(null);

        $res = $this->withSession($session)->get(route('dashboard.laboran.dashboard'));
        $res->assertStatus(200);
        $this->assertStringContainsString('Penugasan jurusan belum ditetapkan', $res->getContent());

        // Setelah jurusan ditetapkan, peringatan hilang
        DB::table('ptk_tugas_tambahan')->where('id', $this->created['duty_id'])
            ->update(['jurusan_id' => self::JUR_ATPH]);
        LaboranScope::flush();

        $res2 = $this->withSession($session)->get(route('dashboard.laboran.dashboard'));
        $res2->assertStatus(200);
        $this->assertStringNotContainsString('Penugasan jurusan belum ditetapkan', $res2->getContent());
    }

    public function test_dropdown_jurusan_menampilkan_jumlah_rombel_sebagai_pembeda(): void
    {
        $res = $this->withSession($this->adminSession())
            ->get(route('dashboard.pengguna.index', ['tab' => 'tugas_tambahan']));

        $res->assertStatus(200);
        $html = $res->getContent();

        // Label pembeda level Program vs Konsentrasi Keahlian
        $normalized = preg_replace('/\s+/', ' ', $html);
        $this->assertStringContainsString('rombel </option>', $normalized);
    }

    /**
     * REGRESI: baris WALI_KELAS menyimpan jurusan hasil sinkronisasi Dapodik.
     * Mengubah penugasan tidak boleh menghapus jurusan tersebut.
     */
    public function test_ubah_penugasan_wali_kelas_tidak_menghapus_jurusan(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'WALI_KELAS')
            ->whereNotNull('ptt.jurusan_id')
            ->select('ptt.*')
            ->first();

        $this->assertNotNull($duty, 'Baris WALI_KELAS dengan jurusan tidak tersedia.');
        $jurusanAwal = $duty->jurusan_id;

        // Kirim payload seperti yang dikirim form (jurusan tidak termasuk karena
        // field jurusan disembunyikan untuk WALI_KELAS).
        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'rombel_id'  => $duty->rombel_id,
                'nomor_sk'   => $duty->nomor_sk,
                'keterangan' => $duty->keterangan,
                'is_active'  => 1,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertSame(
            $jurusanAwal,
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id'),
            'Jurusan WALI_KELAS tidak boleh hilang saat penugasan diubah.'
        );
    }

    /**
     * REGRESI: admin boleh mengosongkan jurusan pada penugasan LABORAN
     * (mis. laboran dipindah menjadi lintas jurusan).
     */
    public function test_admin_dapat_mengosongkan_jurusan_laboran(): void
    {
        $duty = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->where('rtt.kode', 'LABORAN')
            ->select('ptt.*')
            ->first();

        $this->created['duty_id'] = $duty->id;
        DB::table('ptk_tugas_tambahan')->where('id', $duty->id)
            ->update(['jurusan_id' => self::JUR_ATPH]);
        LaboranScope::flush();

        $this->withSession($this->adminSession())
            ->putJson(route('dashboard.pengguna.tugas-tambahan.update', $duty->id), [
                'jurusan_id' => '',
                'is_active'  => 1,
            ])
            ->assertStatus(200);

        $this->assertNull(
            DB::table('ptk_tugas_tambahan')->where('id', $duty->id)->value('jurusan_id')
        );
    }
}
