<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pip_penggunaan_dana')) {
            Schema::create('pip_penggunaan_dana', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->index();
                $table->unsignedBigInteger('pip_riwayat_id')->nullable()->index();
                $table->string('jenis_sk', 30)->default('pemberian');
                $table->string('tahap_id', 50)->nullable();
                $table->string('tahap', 150)->nullable();
                $table->decimal('nominal', 12, 2)->unsigned()->default(0);
                $table->longText('rincian')->nullable(); // JSON [{kategori, nominal, keterangan}]
                $table->longText('pernyataan')->nullable(); // JSON pernyataan yang disetujui
                $table->text('catatan')->nullable();
                $table->string('dilaporkan_oleh', 100)->nullable();
                $table->string('peran', 20)->nullable();
                $table->string('status', 30)->default('diajukan')->index(); // diajukan, diverifikasi, ditolak
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pip_penggunaan_bukti')) {
            Schema::create('pip_penggunaan_bukti', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('penggunaan_dana_id')->index();
                $table->string('file_path', 255);
                $table->string('nama_asli', 255)->nullable();
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pip_penggunaan_bukti');
        Schema::dropIfExists('pip_penggunaan_dana');
    }
};
