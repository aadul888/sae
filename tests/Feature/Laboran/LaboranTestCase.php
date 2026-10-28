<?php

namespace Tests\Feature\Laboran;

use App\Support\LaboranScope;
use App\Support\Table;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Basis pengujian modul Laboratorium per JURUSAN.
 *
 * Menyediakan fixture bersama agar setiap berkas uji turunan tidak
 * menduplikasi logika session, seeding, maupun pembersihan data.
 *
 * PENTING: pengujian berjalan pada database yang juga memuat data nyata,
 * sehingga cleanup WAJIB menghapus hanya baris yang dibuat selama test
 * (snapshot id pada setUp), bukan mengosongkan tabel.
 */
abstract class LaboranTestCase extends TestCase
{
    protected const JUR_ATPH = '26010475';
    protected const JUR_TKJ  = '16012425';

    /** @var array<string, mixed> data sementara antar-langkah test */
    protected array $created = [];

    /**
     * Tabel modul laboran yang dibersihkan setelah setiap test.
     */
    protected const LAB_TABLES = [
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
        // Tabel Sarpras yang disentuh modul peminjaman alat (aset dipinjam
        // lewat `laboran_peminjaman_item.sumber='aset'`). Dibersihkan dengan
        // pola snapshot id agar data nyata tidak terhapus.
        'sarpras_aset',
    ];

    /** @var array<string, array<int, int>> id yang sudah ada SEBELUM test (milik data nyata) */
    protected array $preexistingIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        try {
            DB::table('laboran_jadwal_pengaturan')->where('id', 1)->update([
                'mode_pemberlakuan' => 'semester',
                'status'            => 'aktif',
            ]);
        } catch (\Throwable $e) {}

        $this->preexistingIds = [];
        foreach (self::LAB_TABLES as $table) {
            try {
                if (!Table::exists($table)) {
                    continue;
                }
                $this->preexistingIds[$table] = DB::table($table)->pluck('id')->all();
            } catch (\Throwable $e) {
                $this->preexistingIds[$table] = [];
            }
        }
    }

    protected function tearDown(): void
    {
        // Hapus HANYA baris yang tidak ada pada snapshot setUp().
        foreach (self::LAB_TABLES as $table) {
            try {
                if (!Table::exists($table)) {
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
    // FIXTURE
    // =========================================================================

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
     * Sesi laboran dengan jurusan tertentu (memanfaatkan baris tugas LABORAN nyata).
     */
    protected function laboranSession(?string $jurusanId): array
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

    /**
     * Buat 2 item inventaris uji: satu milik ATPH, satu milik TKJ.
     *
     * @return array{0:int, 1:int, 2:string} [idAtph, idTkj, ptkId]
     */
    protected function seedItems(): array
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

    /**
     * Salin berkas template hasil controller ke path sementara agar dapat
     * dibaca kembali oleh SimpleXlsxReader tanpa mengganggu respons download.
     */
    protected function templateFilePath(object $controller): string
    {
        $resp = $controller->templateExcel();
        $path = $resp->getFile()->getPathname();
        $copy = storage_path('app/test_tpl_' . uniqid() . '.xlsx');
        copy($path, $copy);
        @unlink($path);

        return $copy;
    }
}
