<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('jadwal_pengaturan')) {
            Schema::create('jadwal_pengaturan', function (Blueprint $table) {
                $table->increments('id');
                $table->time('jam_mulai_kbm')->default('07:15:00');
                $table->unsignedSmallInteger('durasi_per_jp')->default(45)->comment('Durasi dalam menit');
                $table->unsignedTinyInteger('total_slot_jp')->default(10)->comment('Total slot JP per hari');
                $table->json('hari_aktif')->nullable()->comment('Daftar hari aktif belajar');
                $table->json('istirahat')->nullable()->comment('Daftar jeda waktu istirahat');
                $table->timestamps();
            });

            // Insert default row (ID: 1)
            DB::table('jadwal_pengaturan')->insert([
                'id' => 1,
                'jam_mulai_kbm' => '07:15:00',
                'durasi_per_jp' => 45,
                'total_slot_jp' => 10,
                'hari_aktif' => json_encode(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
                'istirahat' => json_encode([
                    ['setelah_jp' => 3, 'durasi_menit' => 20, 'nama' => 'Istirahat 1'],
                    ['setelah_jp' => 6, 'durasi_menit' => 40, 'nama' => 'Istirahat 2 (Dhuhur/Jumat)'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_pengaturan');
    }
};
