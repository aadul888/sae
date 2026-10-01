<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_berkas')) {
            Schema::create('peserta_didik_berkas', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 36)->index();
                $table->string('jenis_berkas', 50)->index(); // akta_kelahiran, kartu_keluarga, ijazah_smp, ktp_orang_tua, kip_pip, lainnya
                $table->string('nama_berkas', 150);
                $table->string('file_path', 255);
                $table->string('file_name', 255);
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type', 50)->default('application/pdf');
                $table->enum('status', ['menunggu', 'valid', 'tidak_valid'])->default('menunggu')->index();
                $table->text('catatan_penolakan')->nullable();
                $table->string('rekomendasi_penolakan', 255)->nullable();
                $table->string('verified_by', 100)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->string('uploaded_by', 100)->nullable();
                $table->timestamps();

                $table->unique(['peserta_didik_id', 'jenis_berkas']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peserta_didik_berkas');
    }
};
