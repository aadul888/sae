<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kartu_pelajar_request')) {
            Schema::create('kartu_pelajar_request', function (Blueprint $table) {
                $table->id();
                $table->string('peserta_didik_id', 50)->index();
                $table->foreign('peserta_didik_id')
                    ->references('peserta_didik_id')
                    ->on('peserta_didik')
                    ->onDelete('cascade');
                $table->string('nisn', 20)->nullable();
                $table->string('jenis_request', 30)->default('cetak_baru'); // cetak_baru, ganti_kartu_full, cetak_stiker
                $table->text('alasan')->nullable();
                $table->string('status', 20)->default('menunggu')->index(); // menunggu, diproses, selesai, ditolak
                $table->date('request_date')->default(DB::raw('CURRENT_DATE'));
                $table->unique(['request_date', 'status', 'peserta_didik_id'], 'uq_request_date_status_pd');
                $table->string('metode_pembayaran', 30)->nullable(); // tunai, transfer, qris, ewallet, lainnya
                $table->text('catatan_admin')->nullable();
                $table->string('diminta_oleh', 100)->nullable();
                $table->dateTime('selesai_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('kartu_pelajar_request');
    }
};
