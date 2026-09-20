<?php

use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Jalankan sinkronisasi komprehensif untuk mendeteksi modul baru, submodul kesiswaan/persuratan,
        // serta melengkapi baris permission yang belum ada di setiap peran (Admin, Guru, Tendik, Peserta Didik).
        try {
            RolePermission::syncAvailablePermissions();
            RolePermission::clearRuntimeCache();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tidak perlu rollback destruktif untuk sinkronisasi izin
    }
};
