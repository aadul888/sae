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
     */
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $role = is_array($user) ? ($user['role'] ?? 'siswa') : ($user->role ?? 'siswa');

        if (!RolePermission::canAccess($role, $permissionKey)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
            }

            return redirect()->route('dashboard.' . $role)->with('error', 'Akses ditolak: Peran Anda tidak memiliki izin untuk membuka modul tersebut.');
        }

        return $next($request);
    }
}
