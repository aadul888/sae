<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Area Member Perpustakaan Digital — skema lanjutan.
 *
 * Menambah:
 *  - Identitas kanonik anggota pada sirkulasi & kunjungan (anggota_id)
 *  - Poin literasi & metadata login pada perpus_anggota
 *  - Reservasi/antrean buku
 *  - Notifikasi in-app khusus anggota
 *  - Ulasan/rating buku
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- 1. Poin literasi & metadata anggota ----
        if (Schema::hasTable('perpus_anggota')) {
            Schema::table('perpus_anggota', function (Blueprint $table) {
                if (!Schema::hasColumn('perpus_anggota', 'poin')) {
                    $table->integer('poin')->default(0)->after('is_active');
                }
                if (!Schema::hasColumn('perpus_anggota', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('poin');
                }
                if (!Schema::hasColumn('perpus_anggota', 'auto_provisioned')) {
                    $table->boolean('auto_provisioned')->default(false)->after('last_login_at');
                }
            });
        }

        // ---- 2. Reservasi / antrean buku ----
        if (!Schema::hasTable('perpus_reservasi')) {
            Schema::create('perpus_reservasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anggota_id')->index();
                $table->unsignedBigInteger('buku_id')->index();
                $table->unsignedInteger('antrean')->default(1);
                $table->string('status', 20)->default('menunggu')->index(); // menunggu, siap, diambil, batal, kedaluwarsa
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['anggota_id', 'status']);
            });
        }

        // ---- 3. Notifikasi in-app anggota ----
        if (!Schema::hasTable('perpus_notifikasi')) {
            Schema::create('perpus_notifikasi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anggota_id')->index();
                $table->string('kategori', 40)->default('umum');
                $table->string('judul');
                $table->text('pesan')->nullable();
                $table->string('tipe', 20)->default('info'); // info, success, warning, danger
                $table->string('icon', 60)->nullable();
                $table->string('url')->nullable();
                $table->boolean('is_read')->default(false)->index();
                $table->timestamps();

                $table->index(['anggota_id', 'is_read']);
            });
        }

        // ---- 4. Ulasan / rating buku ----
        if (!Schema::hasTable('perpus_ulasan')) {
            Schema::create('perpus_ulasan', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anggota_id')->index();
                $table->unsignedBigInteger('buku_id')->index();
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('komentar')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->timestamps();

                $table->unique(['anggota_id', 'buku_id']);
            });
        }

        // ---- 5. Tautan identitas kanonik pada sirkulasi & kunjungan ----
        if (Schema::hasTable('perpus_sirkulasi') && !Schema::hasColumn('perpus_sirkulasi', 'anggota_id')) {
            Schema::table('perpus_sirkulasi', function (Blueprint $table) {
                $table->unsignedBigInteger('anggota_id')->nullable()->index()->after('peminjam_id');
            });
        }

        if (Schema::hasTable('perpus_kunjungan') && !Schema::hasColumn('perpus_kunjungan', 'anggota_id')) {
            Schema::table('perpus_kunjungan', function (Blueprint $table) {
                $table->unsignedBigInteger('anggota_id')->nullable()->index()->after('pengunjung_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('perpus_kunjungan') && Schema::hasColumn('perpus_kunjungan', 'anggota_id')) {
            Schema::table('perpus_kunjungan', fn(Blueprint $t) => $t->dropColumn('anggota_id'));
        }
        if (Schema::hasTable('perpus_sirkulasi') && Schema::hasColumn('perpus_sirkulasi', 'anggota_id')) {
            Schema::table('perpus_sirkulasi', fn(Blueprint $t) => $t->dropColumn('anggota_id'));
        }

        Schema::dropIfExists('perpus_ulasan');
        Schema::dropIfExists('perpus_notifikasi');
        Schema::dropIfExists('perpus_reservasi');

        if (Schema::hasTable('perpus_anggota')) {
            Schema::table('perpus_anggota', function (Blueprint $table) {
                foreach (['auto_provisioned', 'last_login_at', 'poin'] as $col) {
                    if (Schema::hasColumn('perpus_anggota', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
