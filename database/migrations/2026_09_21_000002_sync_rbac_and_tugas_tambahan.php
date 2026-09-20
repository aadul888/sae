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
        // Sinkronisasi otomatis modul sistem baru ke matriks RBAC dan referensi tugas tambahan
        try {
            RolePermission::syncAvailablePermissions();
            RolePermission::clearRuntimeCache();
        } catch (\Throwable $e) {
            // Silently log or continue to ensure migration succeeds even in edge environments
            report($e);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed for RBAC sync
    }
};
