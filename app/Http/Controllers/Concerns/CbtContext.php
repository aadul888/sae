<?php

namespace App\Http\Controllers\Concerns;

use App\Models\RolePermission;

/**
 * Helper akses bersama modul CBT (bank soal & definisi ujian).
 *
 * Dipisah dari `ElearningContext` agar domain izin tidak bercampur — CBT memakai
 * permission key `menu_cbt`, E-Learning memakai `menu_elearning`.
 */
trait CbtContext
{
    /** Izin kelola CBT untuk peran ini. */
    protected function canManageCbt(mixed $userOrRole, string $role, string $action): bool
    {
        return RolePermission::canAccess($userOrRole, 'menu_cbt', $action);
    }
}
