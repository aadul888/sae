<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('persuratan_templates')) {
            Schema::create('persuratan_templates', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_surat')->index();
                $table->string('kode_indeks', 50)->nullable();
                $table->string('template_type', 50)->default('undangan'); // undangan, pemberitahuan, pengantar, rekomendasi, kustom
                $table->string('perihal');
                $table->string('lampiran', 100)->default('-');
                $table->string('tujuan_penerima');
                $table->string('tujuan_lokasi', 200)->nullable();
                $table->date('tanggal_surat');
                $table->string('salam_pembuka', 100)->default('Dengan hormat,');
                $table->text('paragraf_pembuka')->nullable();
                $table->json('rincian_kegiatan')->nullable();
                $table->text('isi_surat')->nullable();
                $table->text('paragraf_penutup')->nullable();
                $table->text('tembusan')->nullable();
                $table->string('pejabat_ptk_id', 50)->nullable();
                $table->string('pejabat_nama', 200)->nullable();
                $table->string('pejabat_nip', 50)->nullable();
                $table->string('pejabat_jabatan', 100)->default('Kepala Sekolah');
                $table->string('doc_id', 50)->unique();
                $table->string('status', 30)->default('selesai');
                $table->string('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('persuratan_templates');
    }
};
