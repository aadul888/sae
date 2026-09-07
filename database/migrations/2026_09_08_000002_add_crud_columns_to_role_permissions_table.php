<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('role_permissions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('role_permissions', 'can_create')) {
                    $table->boolean('can_create')->default(false)->after('is_allowed');
                }
                if (!Schema::hasColumn('role_permissions', 'can_read')) {
                    $table->boolean('can_read')->default(true)->after('can_create');
                }
                if (!Schema::hasColumn('role_permissions', 'can_update')) {
                    $table->boolean('can_update')->default(false)->after('can_read');
                }
                if (!Schema::hasColumn('role_permissions', 'can_delete')) {
                    $table->boolean('can_delete')->default(false)->after('can_update');
                }
            });

            // Set default CRUD untuk Admin menjadi true semua
            DB::table('role_permissions')->where('role', 'admin')->update([
                'can_create' => true,
                'can_read' => true,
                'can_update' => true,
                'can_delete' => true,
            ]);

            // Set default CRUD untuk Guru pada menu tertentu
            DB::table('role_permissions')->where('role', 'guru')
                ->whereIn('permission_key', ['menu_presensi_mengajar', 'menu_agenda_kbm', 'menu_penilaian', 'menu_presensi_siswa'])
                ->update([
                    'can_create' => true,
                    'can_read' => true,
                    'can_update' => true,
                    'can_delete' => false,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_permissions')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropColumn(['can_create', 'can_read', 'can_update', 'can_delete']);
            });
        }
    }
};
