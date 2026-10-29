<?php

namespace Tests\Unit;

use App\Support\RombelScope;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

/**
 * Filter rombel reguler adalah satu tempat (RombelScope) supaya hitungan kelas
 * di seluruh sistem konsisten: 35 kelas, bukan 79 (35 kelas + 35 mapil + 9 ekskul).
 */
class RombelScopeTest extends TestCase
{
    private static bool $booted = false;

    protected function setUp(): void
    {
        if (self::$booted) {
            return;
        }

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        // Table::exists() memakai Facade DB (db / db.schema) — daftarkan ke container capsule.
        $container = $capsule->getContainer();
        $container->instance('db', $capsule->getDatabaseManager());
        $container->instance('db.schema', $capsule->getConnection()->getSchemaBuilder());
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);

        Capsule::schema()->create('rombongan_belajar', function ($t) {
            $t->string('rombongan_belajar_id')->primary();
            $t->string('nama');
            $t->string('jenis_rombel')->nullable();
            $t->string('jenis_rombel_str')->nullable();
        });

        $rows = [
            // 35 kelas reguler
            ...array_map(fn($i) => ['rb-' . $i, 'Kelas ' . $i, '1', 'Kelas'], range(1, 35)),
            // 35 matapelajaran pilihan
            ...array_map(fn($i) => ['mp-' . $i, 'Kelas ' . $i, '16', 'Matapelajaran Pilihan'], range(1, 35)),
            // 9 ekstrakurikuler
            ...array_map(fn($i) => ['ek-' . $i, 'Ekskul ' . $i, '51', 'Ekstrakurikuler'], range(1, 9)),
        ];

        Capsule::table('rombongan_belajar')->insert(array_map(fn($r) => [
            'rombongan_belajar_id' => $r[0],
            'nama' => $r[1],
            'jenis_rombel' => $r[2],
            'jenis_rombel_str' => $r[3],
        ], $rows));

        self::$booted = true;
    }

    public function test_hanya_35_kelas_reguler_yang_terhitung_dari_79_baris(): void
    {
        $this->assertSame(79, Capsule::table('rombongan_belajar')->count());
        $this->assertSame(35, RombelScope::count());
    }

    public function test_mapil_dan_ekskul_tidak_pernah_dianggap_kelas(): void
    {
        $jenis = RombelScope::query()->pluck('jenis_rombel')->unique()->all();

        $this->assertSame(['1'], $jenis);
    }

    public function test_nama_kelas_yang_duplikat_dengan_mapil_hanya_muncul_sekali(): void
    {
        $nama = RombelScope::query()->pluck('nama');
        $mapilNama = Capsule::table('rombongan_belajar')->where('jenis_rombel', '16')->pluck('nama');

        // Nama mapil identik dengan nama kelas; dipastikan yang terambil hanya versi kelas.
        $this->assertSame(35, $nama->count());
        $this->assertSame([], $nama->duplicates()->all());
        $this->assertTrue($mapilNama->isNotEmpty());
    }

    public function test_prefix_kolom_dipakai_untuk_query_join(): void
    {
        $sql = RombelScope::reguler(Capsule::table('rombongan_belajar as rb'), 'rb.')->toSql();

        $this->assertStringContainsString('"rb"."jenis_rombel" = ?', $sql);
        $this->assertStringContainsString('"rb"."jenis_rombel_str" NOT LIKE ?', $sql);
    }

    public function test_baris_lama_tanpa_jenis_rombel_tapi_berlabel_kelas_tetap_masuk(): void
    {
        Capsule::table('rombongan_belajar')->insert([
            'rombongan_belajar_id' => 'lama-1',
            'nama' => 'XII LAMA',
            'jenis_rombel' => null,
            'jenis_rombel_str' => 'Kelas',
        ]);
        Capsule::table('rombongan_belajar')->insert([
            'rombongan_belajar_id' => 'lama-2',
            'nama' => 'XII PILIHAN LAMA',
            'jenis_rombel' => null,
            'jenis_rombel_str' => 'Matapelajaran Pilihan',
        ]);

        $this->assertSame(36, RombelScope::count());
    }
}
