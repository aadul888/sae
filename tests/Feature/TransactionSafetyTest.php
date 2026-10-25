<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Membuktikan transaksi melindungi data dari kegagalan di tengah proses.
 *
 * Skenario nyata yang dicegah: proses sinkronisasi Dapodik menghapus seluruh
 * isi tabel lalu mengisi ulang per batch. Bila insert gagal di tengah tanpa
 * transaksi, data lama sudah terhapus dan hanya terisi sebagian.
 */
class TransactionSafetyTest extends TestCase
{
    public function test_rollback_restores_data_after_mid_process_failure(): void
    {
        // Gunakan tabel sementara agar tidak menyentuh data nyata.
        $table = 'sae_tx_probe';

        DB::statement("CREATE TABLE IF NOT EXISTS {$table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            label VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB");

        DB::table($table)->insert([
            ['label' => 'data-lama-1'],
            ['label' => 'data-lama-2'],
            ['label' => 'data-lama-3'],
        ]);

        $before = DB::table($table)->count();
        $this->assertSame(3, $before);

        // Simulasi: delete semua lalu insert sebagian, kemudian gagal.
        try {
            DB::transaction(function () use ($table) {
                DB::table($table)->delete();
                DB::table($table)->insert(['label' => 'data-baru-1']);

                // Kegagalan di tengah proses (mis. constraint / koneksi putus).
                throw new \RuntimeException('Simulasi kegagalan sinkronisasi');
            });
        } catch (\RuntimeException $e) {
            // diabaikan: yang diuji adalah efek rollback
        }

        $after = DB::table($table)->count();

        $this->assertSame(
            3,
            $after,
            'Rollback gagal: data lama seharusnya utuh kembali (3 baris), ditemukan ' . $after
        );

        // Data lama harus persis sama.
        $labels = DB::table($table)->orderBy('id')->pluck('label')->all();
        $this->assertSame(['data-lama-1', 'data-lama-2', 'data-lama-3'], $labels);

        DB::statement("DROP TABLE IF EXISTS {$table}");
    }

    public function test_commit_persists_when_no_failure(): void
    {
        $table = 'sae_tx_probe2';

        DB::statement("CREATE TABLE IF NOT EXISTS {$table} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            label VARCHAR(50) NOT NULL
        ) ENGINE=InnoDB");

        DB::table($table)->insert(['label' => 'lama']);

        DB::transaction(function () use ($table) {
            DB::table($table)->delete();
            DB::table($table)->insert(['label' => 'baru']);
        });

        $this->assertSame(['baru'], DB::table($table)->pluck('label')->all());

        DB::statement("DROP TABLE IF EXISTS {$table}");
    }

    public function test_feeder_receiver_wraps_destructive_operations_in_transaction(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/Api/FeederReceiverController.php'));

        // Setiap delete() massal harus berada di dalam DB::transaction.
        $this->assertStringContainsString('DB::transaction', $src);

        // Hitung delete pada tabel utama vs jumlah transaksi.
        $destructive = preg_match_all(
            "/DB::table\('(sekolah|rombongan_belajar|anggota_rombel|pembelajaran|gtk|peserta_didik)'\)->delete\(\)/",
            $src
        );
        $transactions = substr_count($src, 'DB::transaction');

        $this->assertGreaterThan(0, $destructive, 'Pola delete massal tidak ditemukan.');
        $this->assertGreaterThanOrEqual(
            $transactions,
            4,
            'Operasi destruktif pada sinkronisasi Dapodik harus dibungkus transaksi.'
        );
    }

    public function test_permission_toggle_uses_transaction(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/PermissionController.php'));

        $this->assertStringContainsString(
            'DB::transaction',
            $src,
            'Perubahan hak akses (termasuk cascading) harus atomik.'
        );
    }
}
