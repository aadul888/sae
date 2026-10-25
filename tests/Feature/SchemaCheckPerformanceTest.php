<?php

namespace Tests\Feature;

use App\Support\Table;
use Tests\TestCase;

/**
 * Guard performa & arsitektur untuk pemeriksaan keberadaan tabel.
 *
 * `Schema::hasTable()` menjalankan query ke information_schema setiap kali
 * dipanggil. Sebelumnya aplikasi memanggilnya 467 kali; kini memakai
 * App\Support\Table yang ber-cache per proses.
 */
class SchemaCheckPerformanceTest extends TestCase
{
    public function test_table_helper_caches_results(): void
    {
        Table::flush();

        $queriesBefore = $this->countQueries(function () {
            Table::exists('pengguna');
        });

        $queriesAfter = $this->countQueries(function () {
            // Panggilan kedua dan ketiga harus tanpa query.
            Table::exists('pengguna');
            Table::exists('pengguna');
            Table::exists('pengguna');
        });

        $this->assertGreaterThan(0, $queriesBefore, 'Panggilan pertama seharusnya menjalankan query.');
        $this->assertSame(0, $queriesAfter, 'Panggilan berikutnya harus memakai cache (0 query).');
    }

    public function test_table_helper_matches_schema_facade(): void
    {
        Table::flush();

        foreach (['pengguna', 'peserta_didik', 'gtk', 'tabel_tidak_ada_xyz'] as $table) {
            $this->assertSame(
                \Illuminate\Support\Facades\Schema::hasTable($table),
                Table::exists($table),
                "Hasil Table::exists() berbeda dengan Schema::hasTable() untuk tabel {$table}."
            );
        }
    }

    public function test_exists_all_and_any_helpers(): void
    {
        Table::flush();

        $this->assertTrue(Table::existsAll('pengguna', 'peserta_didik'));
        $this->assertFalse(Table::existsAll('pengguna', 'tabel_tidak_ada_xyz'));
        $this->assertTrue(Table::existsAny('tabel_tidak_ada_xyz', 'pengguna'));
        $this->assertFalse(Table::existsAny('tabel_tidak_ada_xyz', 'tabel_lain_tidak_ada'));
    }

    public function test_flush_clears_cache(): void
    {
        Table::flush();
        Table::exists('pengguna');

        $queries = $this->countQueries(function () {
            Table::exists('pengguna');
        });
        $this->assertSame(0, $queries);

        Table::flush();
        $queriesAfterFlush = $this->countQueries(function () {
            Table::exists('pengguna');
        });
        $this->assertGreaterThan(0, $queriesAfterFlush, 'Setelah flush, query harus dijalankan lagi.');
    }

    public function test_no_raw_schema_hastable_left_in_app(): void
    {
        $offenders = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path()));

        foreach ($it as $f) {
            if (!$f->isFile() || !str_ends_with($f->getFilename(), '.php')) {
                continue;
            }
            // Helper sendiri yang mengimplementasikan pemanggilan Schema.
            if ($f->getFilename() === 'Table.php') {
                continue;
            }
            if (str_contains(file_get_contents($f->getPathname()), 'Schema::hasTable')) {
                $offenders[] = $f->getFilename();
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Gunakan App\Support\Table::exists() (ber-cache) alih-alih Schema::hasTable(): '
                . implode(', ', $offenders)
        );
    }

    private function countQueries(callable $callback): int
    {
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $callback();
        $count = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        return $count;
    }
}
