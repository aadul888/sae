<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan pemberlakuan jadwal praktik laboran (singleton row id = 1).
 *
 * Meniru pola pemberlakuan eksklusif Jadwal KBM (`jadwal_pengaturan`):
 * laboran memilih salah satu mode — `mingguan` atau `semester` — untuk
 * diberlakukan sebagai acuan penentuan jadwal praktik.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('laboran_jadwal_pengaturan')) {
            Schema::create('laboran_jadwal_pengaturan', function (Blueprint $table) {
                $table->id();
                $table->string('mode_pemberlakuan', 20)->default('mingguan');
                $table->string('status', 20)->default('draft');
                $table->string('diberlakukan_oleh_ptk_id', 100)->nullable();
                $table->timestamps();
            });

            DB::table('laboran_jadwal_pengaturan')->insert([
                'mode_pemberlakuan' => 'mingguan',
                'status'            => 'draft',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('laboran_jadwal_pengaturan');
    }
};
