<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom mode_pemberlakuan pada tabel jadwal_pengaturan
        if (Schema::hasTable('jadwal_pengaturan')) {
            Schema::table('jadwal_pengaturan', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_pengaturan', 'mode_pemberlakuan')) {
                    $table->string('mode_pemberlakuan', 20)->default('otomatis')->after('status_jadwal')
                        ->comment('otomatis: Jadwal Generate Otomatis Aktif, manual: Jadwal Input Manual Aktif, draft: Semua Nonaktif');
                }
            });

            // Set default data lama
            $settings = DB::table('jadwal_pengaturan')->first();
            if ($settings) {
                $isAktif = !empty($settings->is_diberlakukan) && ($settings->status_jadwal ?? '') === 'aktif';
                DB::table('jadwal_pengaturan')->where('id', $settings->id)->update([
                    'mode_pemberlakuan' => $isAktif ? 'otomatis' : 'draft',
                ]);
            }
        }

        // 2. Tambah kolom sumber pada tabel jadwal_kbm (otomatis vs manual)
        if (Schema::hasTable('jadwal_kbm')) {
            Schema::table('jadwal_kbm', function (Blueprint $table) {
                if (!Schema::hasColumn('jadwal_kbm', 'sumber')) {
                    $table->string('sumber', 20)->default('otomatis')->after('semester_id')
                        ->comment('otomatis: Hasil engine auto-scheduler, manual: Hasil input koordinator/wali kelas');
                    $table->index('sumber');
                }
            });

            // Tandai seluruh jadwal yang sudah ada sebagai sumber 'otomatis'
            DB::table('jadwal_kbm')->whereNull('sumber')->orWhere('sumber', '')->update([
                'sumber' => 'otomatis',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('jadwal_pengaturan')) {
            Schema::table('jadwal_pengaturan', function (Blueprint $table) {
                if (Schema::hasColumn('jadwal_pengaturan', 'mode_pemberlakuan')) {
                    $table->dropColumn('mode_pemberlakuan');
                }
            });
        }

        if (Schema::hasTable('jadwal_kbm')) {
            Schema::table('jadwal_kbm', function (Blueprint $table) {
                if (Schema::hasColumn('jadwal_kbm', 'sumber')) {
                    $table->dropIndex(['sumber']);
                    $table->dropColumn('sumber');
                }
            });
        }
    }
};
