<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RolePermission;

class CheckRolePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissionKey
     * @param  string  $action
     */
    public function handle(Request $request, Closure $next, string $permissionKey, string $action = 'read'): Response
    {
        $user = session('user');
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Silakan masuk terlebih dahulu.',
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        // Evaluasi gabungan hak akses: role dasar + tugas tambahan aktif pengguna
        if (!RolePermission::canAccess($user, $permissionKey, $action)) {
            $actionLabel = match (strtolower($action)) {
                'create' => 'Menambah / Mengunggah Data',
                'update' => 'Mengubah / Memperbarui Data',
                'delete' => 'Menghapus Data',
                default  => 'Melihat / Mengakses Data',
            };

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Akses ditolak: Peran Anda tidak memiliki izin untuk {$actionLabel} pada modul ini.",
                ], 403);
            }

            return redirect()->route('dashboard.' . $role)->with('error', "Akses ditolak: Peran atau penugasan Anda tidak memiliki izin untuk {$actionLabel} pada modul tersebut.");
        }

        return $next($request);
    }
}
