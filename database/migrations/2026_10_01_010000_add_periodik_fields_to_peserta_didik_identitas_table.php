<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        Schema::table('peserta_didik_identitas', function (Blueprint $table) {
            $table->unsignedSmallInteger('lingkar_kepala')->nullable();
            $table->string('jarak_rumah_sekolah', 30)->nullable();
            $table->decimal('jarak_rumah_sekolah_km', 5, 2)->nullable();
            $table->unsignedTinyInteger('waktu_tempuh_jam')->nullable();
            $table->unsignedTinyInteger('waktu_tempuh_menit')->nullable();
            $table->unsignedTinyInteger('jumlah_saudara_kandung')->nullable();
            $table->string('program_keahlian', 150)->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('peserta_didik_identitas')) {
            return;
        }

        Schema::table('peserta_didik_identitas', function (Blueprint $table) {
            $table->dropColumn([
                'lingkar_kepala',
                'jarak_rumah_sekolah',
                'jarak_rumah_sekolah_km',
                'waktu_tempuh_jam',
                'waktu_tempuh_menit',
                'jumlah_saudara_kandung',
                'program_keahlian',
            ]);
        });
    }
};