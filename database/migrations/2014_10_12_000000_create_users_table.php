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
        Schema::create('pengguna', function (Blueprint $table) {
            $table->string('pengguna_id', 50)->primary();
            $table->string('sekolah_id', 50)->nullable();
            $table->string('username', 100)->unique();
            $table->string('nama', 200)->nullable();
            $table->string('peran_id_str', 100)->nullable();
            $table->string('password', 255)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('ptk_id', 50)->nullable()->index();
            $table->string('peserta_didik_id', 50)->nullable()->index();
            $table->rememberToken();
            $table->longText('raw_data')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_pengguna', function (Blueprint $table) {
            $table->bigIncrements('backup_id');
            $table->string('pengguna_id', 50)->index();
            $table->string('sekolah_id', 50)->nullable();
            $table->string('username', 100);
            $table->string('nama', 200)->nullable();
            $table->string('peran_id_str', 100)->nullable();
            $table->string('password', 255)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 30)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->string('ptk_id', 50)->nullable();
            $table->string('peserta_didik_id', 50)->nullable();
            $table->longText('raw_data')->nullable();
            $table->timestamp('archived_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_pengguna');
        Schema::dropIfExists('pengguna');
    }
};

