<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat per peserta didik untuk Residu Verval PD.
     *
     * Import tetap mengganti isi tabel `verval_pd_residu` (kondisi terkini),
     * tetapi setiap import/ubah sel/status/catatan dicatat di sini sehingga
     * jejak lama tidak hilang walau diimpor ulang. Satu NISN = satu rangkaian riwayat.
     */
    public function up(): void
    {
        if (!Schema::hasTable('verval_pd_residu_riwayat')) {
            Schema::create('verval_pd_residu_riwayat', function (Blueprint $table) {
                $table->id();
                $table->string('kunci', 190)->index();          // NISN, atau "N:<nama>" bila NISN kosong
                $table->string('nisn', 30)->nullable()->index();
                $table->string('nama', 150)->nullable();
                $table->string('tipe', 20)->default('import')->index(); // import | sel | status | catatan
                $table->string('kolom', 50)->nullable();
                $table->text('nilai_lama')->nullable();
                $table->text('nilai_baru')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('admin_name', 150)->nullable();
                $table->timestamps();

                $table->index('created_at');
            });
        }

        // Backfill: catat satu peristiwa import untuk data Residu yang sudah ada
        // agar tab Riwayat tidak kosong tepat setelah migrasi.
        if (Schema::hasTable('verval_pd_residu') && Schema::hasTable('verval_pd_residu_riwayat')
            && DB::table('verval_pd_residu_riwayat')->count() === 0) {
            $now = now();
            $payload = [];
            $rows = DB::table('verval_pd_residu')
                ->select('nisn', 'nama', 'rombel_sekolah', 'imported_by', 'created_at')
                ->get();

            foreach ($rows as $r) {
                $nisn = trim((string) $r->nisn);
                $nama = trim((string) $r->nama);
                $payload[] = [
                    'kunci' => $nisn !== '' ? $nisn : 'N:' . $nama,
                    'nisn' => $nisn !== '' ? $nisn : null,
                    'nama' => $nama !== '' ? $nama : null,
                    'tipe' => 'import',
                    'kolom' => null,
                    'nilai_lama' => null,
                    'nilai_baru' => $r->rombel_sekolah,
                    'keterangan' => 'Data Residu hasil import Dukcapil (Rombel/Sekolah: ' . ($r->rombel_sekolah ?: '-') . ').',
                    'admin_name' => $r->imported_by,
                    'created_at' => $r->created_at ?: $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($payload, 500) as $chunk) {
                DB::table('verval_pd_residu_riwayat')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('verval_pd_residu_riwayat');
    }
};
