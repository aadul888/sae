<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GTK Non-ASN — data diinput manual di SAE, di luar Dapodik.
 *
 * SENGAJA dipisah dari tabel `gtk`: tarik data Dapodik melakukan
 * `DB::table('gtk')->delete()` + insert ulang (FeederReceiverController),
 * sehingga data Non-ASN akan hilang bila disimpan di sana.
 *
 * Konsekuensi: setiap pembacaan daftar guru/tendik harus menggabungkan
 * `gtk` (Dapodik) + `gtk_non_asn` (input lokal).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gtk_non_asn')) {
            return;
        }

        Schema::create('gtk_non_asn', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identitas lokal (bukan ptk_id Dapodik)
            $table->string('kode', 40)->unique()->comment('Kode lokal unik, mis. NONASN-0001');

            $table->string('nama', 200);
            $table->string('gelar_depan', 50)->nullable();
            $table->string('gelar_belakang', 100)->nullable();
            $table->string('jenis_kelamin', 1)->nullable()->comment('L = Laki-laki, P = Perempuan');
            $table->string('nik', 30)->nullable()->index();
            $table->string('nuptk', 30)->nullable()->index();
            $table->string('nip', 30)->nullable();

            $table->string('jenis_ptk', 100)->nullable()->comment('Guru / Tenaga Kependidikan / dst (bebas teks)');
            $table->string('jabatan', 200)->nullable();
            $table->string('status_kepegawaian', 100)->nullable()->default('Non-ASN');
            $table->string('pendidikan_terakhir', 50)->nullable();
            $table->string('bidang_studi', 200)->nullable();

            $table->string('tempat_lahir', 100)->nullable();
            $table->string('tanggal_lahir', 30)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('no_hp', 30)->nullable();
            $table->text('alamat')->nullable();

            $table->string('tmt', 30)->nullable()->comment('Tanggal mulai tugas');
            $table->string('status', 20)->default('aktif')->index()->comment('aktif | tidak_aktif');
            $table->string('keterangan', 500)->nullable();

            // Penautan ke akun login (tabel pengguna)
            $table->string('pengguna_id', 50)->nullable()->index();
            $table->string('username', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_non_asn');
    }
};
