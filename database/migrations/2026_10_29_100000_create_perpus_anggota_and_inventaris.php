<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Anggota Perpustakaan (siswa, gtk, umum) — Gerbang akses + ID card digital
        if (!Schema::hasTable('perpus_anggota')) {
            Schema::create('perpus_anggota', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_anggota', 40)->unique();
                $table->string('token_kartu', 64)->unique(); // token QR kartu digital
                $table->string('tipe', 20)->default('siswa'); // siswa, gtk, umum
                $table->string('siswa_id', 50)->nullable()->index(); // peserta_didik_id
                $table->string('ptk_id', 50)->nullable()->index();
                $table->string('nisn_nip', 40)->nullable()->index();
                $table->string('nama');
                $table->string('rombel_atau_unit', 100)->nullable();
                $table->string('jenis_kelamin', 2)->nullable();
                $table->string('telepon', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('foto')->nullable();
                $table->string('password')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->date('tgl_daftar')->nullable();
                $table->date('tgl_kadaluarsa')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }

        // 2. Tambah field inventaris buku: barcode/QR, tanggal pembelian, harga aset
        if (Schema::hasTable('perpus_koleksi_buku')) {
            Schema::table('perpus_koleksi_buku', function (Blueprint $table) {
                if (!Schema::hasColumn('perpus_koleksi_buku', 'barcode')) {
                    $table->string('barcode', 80)->nullable()->index()->after('kode_buku');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'tgl_pembelian')) {
                    $table->date('tgl_pembelian')->nullable()->after('tahun_terbit');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'harga_perolehan')) {
                    $table->decimal('harga_perolehan', 14, 2)->default(0)->after('tgl_pembelian');
                }
                if (!Schema::hasColumn('perpus_koleksi_buku', 'sumber_dana')) {
                    $table->string('sumber_dana', 60)->nullable()->after('harga_perolehan');
                }
            });
        }

        // 3. Portal akun anggota (sesi login gerbang)
        if (!Schema::hasTable('perpus_anggota_session')) {
            Schema::create('perpus_anggota_session', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('anggota_id')->index();
                $table->string('token', 64)->unique();
                $table->string('ip', 45)->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perpus_anggota_session');
        if (Schema::hasTable('perpus_koleksi_buku')) {
            Schema::table('perpus_koleksi_buku', function (Blueprint $table) {
                foreach (['barcode', 'tgl_pembelian', 'harga_perolehan', 'sumber_dana'] as $col) {
                    if (Schema::hasColumn('perpus_koleksi_buku', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('perpus_anggota');
    }
};
