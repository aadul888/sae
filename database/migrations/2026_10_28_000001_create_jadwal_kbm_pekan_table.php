<?php

use App\Support\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lapis 2 pemberlakuan jadwal: satu baris = satu pekan efektif per semester.
 *
 * Pola jadwal tetap disimpan di `jadwal_kbm` (tidak diubah). Tabel ini hanya
 * mempersempit KAPAN pola tersebut berlaku, sehingga `presensi_mengajar` dan
 * `agenda_kbm` yang mereferensikan `jadwal_kbm.id` tetap valid.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Table::exists('jadwal_kbm_pekan')) {
            return;
        }

        Schema::create('jadwal_kbm_pekan', function (Blueprint $table) {
            $table->id();

            $table->string('tahun_ajaran', 10)->comment('Format 2025/2026');
            $table->string('semester', 2)->comment('1 = Ganjil, 2 = Genap');
            $table->unsignedTinyInteger('pekan_ke');

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->string('sumber', 20)->nullable()
                ->comment('null = ikut mode_pemberlakuan global; otomatis/manual = pekan ini memakai sumber tersebut');

            $table->string('status', 20)->default('draft')
                ->comment('draft: belum dipublikasikan, aktif: diberlakukan, libur: KBM ditiadakan, ujian: pekan asesmen');

            $table->boolean('is_efektif')->default(true)
                ->comment('Hasil hitung Kalender Pendidikan, dapat di-override operator');

            $table->text('catatan')->nullable();

            $table->timestamp('diberlakukan_pada')->nullable();
            $table->string('diberlakukan_oleh', 100)->nullable();

            $table->timestamps();

            $table->unique(['tahun_ajaran', 'semester', 'pekan_ke'], 'jkp_periode_pekan_unique');
            $table->index(['tahun_ajaran', 'semester'], 'jkp_periode_index');
            $table->index(['tanggal_mulai', 'tanggal_selesai'], 'jkp_rentang_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_kbm_pekan');
    }
};
