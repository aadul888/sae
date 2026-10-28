<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel meta lokal GTK (di luar sinkronisasi Dapodik).
     *
     * Data `gtk` ditimpa total setiap tarik Dapodik (FeederReceiverController
     * melakukan delete + insert), sehingga kolom kustom seperti gelar depan /
     * belakang harus disimpan di tabel terpisah agar tidak hilang saat sync.
     */
    public function up(): void
    {
        if (!Schema::hasTable('gtk_meta')) {
            Schema::create('gtk_meta', function (Blueprint $table) {
                $table->id();
                $table->string('ptk_id', 50)->unique()->comment('FK logis ke gtk.ptk_id');
                $table->string('gelar_depan', 50)->nullable()->comment('Gelar depan, mis. Dr., Drs., H.');
                $table->string('gelar_belakang', 100)->nullable()->comment('Gelar belakang, mis. S.Pd., M.Pd.');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gtk_meta');
    }
};
