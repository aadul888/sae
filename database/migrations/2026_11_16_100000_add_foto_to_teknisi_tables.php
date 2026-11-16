<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bukti foto per baris data Teknisi & IT.
 *
 * Satu kolom `foto` (path relatif disk `public`) per tabel transaksional.
 * Modul yang sudah punya kolom foto sendiri (`teknisi_log_harian.foto`,
 * `teknisi_jurnal_perbaikan.foto_sebelum/foto_sesudah`,
 * `teknisi_kebersihan_*`) tidak disentuh.
 */
return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [
        'teknisi_pemeliharaan',
        'teknisi_peminjaman',
        'teknisi_work_order',
        'teknisi_aset_mutasi',
        'teknisi_usulan_pengadaan',
        'teknisi_pelaksanaan_harian',
        'teknisi_area_tugas',
        'teknisi_sarana_rapat',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || Schema::hasColumn($table, 'foto')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->string('foto')->nullable()->after('id');
            });
        }

        // Bukti pengembalian peminjaman (foto saat barang/ruang dikembalikan).
        if (Schema::hasTable('teknisi_peminjaman')
            && !Schema::hasColumn('teknisi_peminjaman', 'foto_kembali')) {
            Schema::table('teknisi_peminjaman', function (Blueprint $t) {
                $t->string('foto_kembali')->nullable()->after('foto');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'foto')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('foto'));
            }
        }

        if (Schema::hasTable('teknisi_peminjaman')
            && Schema::hasColumn('teknisi_peminjaman', 'foto_kembali')) {
            Schema::table('teknisi_peminjaman', fn (Blueprint $t) => $t->dropColumn('foto_kembali'));
        }
    }
};
