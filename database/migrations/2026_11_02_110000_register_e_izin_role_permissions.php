<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['role' => 'admin', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
            ['role' => 'guru', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 0],
            ['role' => 'tendik', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 0],
            ['role' => 'peserta_didik', 'can_create' => 1, 'can_read' => 1, 'can_update' => 0, 'can_delete' => 1],
            ['role' => 'tugas_tambahan', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
        ];

        foreach ($permissions as $p) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => $p['role'], 'permission_key' => 'menu_e_izin'],
                [
                    'is_allowed' => 1,
                    'can_create' => $p['can_create'],
                    'can_read' => $p['can_read'],
                    'can_update' => $p['can_update'],
                    'can_delete' => $p['can_delete'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission_key', 'menu_e_izin')->where('role', '!=', 'tugas_tambahan')->delete();
    }
};
