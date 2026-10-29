<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat unggah (import) Excel modul Teknisi.
 *
 * Setiap unggahan mencatat pengguna yang sedang login: id, nama, peran, nama
 * berkas, jumlah baris, dan alamat IP. Tabel ini melengkapi penanda per-baris
 * (`user_id` pada tabel modul) sehingga jejak "siapa mengunggah apa" tersimpan
 * meski baris kemudian diubah atau dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teknisi_import_log')) {
            Schema::create('teknisi_import_log', function (Blueprint $table) {
                $table->id();
                $table->string('modul', 60)->index();          // key modul (mis. 'area-tugas')
                $table->string('judul', 200)->nullable();      // label modul untuk tampilan
                $table->string('file_name', 255)->nullable();
                $table->unsignedInteger('rows_imported')->default(0);
                $table->unsignedInteger('rows_updated')->default(0);
                $table->string('user_id', 50)->nullable()->index();
                $table->string('user_nama', 150)->nullable();
                $table->string('user_role', 30)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['modul', 'created_at'], 'idx_tek_import_modul_tgl');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teknisi_import_log');
    }
};
