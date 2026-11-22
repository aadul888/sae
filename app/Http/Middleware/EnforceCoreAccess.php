<?php

namespace App\Http\Middleware;

use App\Services\CoreControlService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang akses global dari sae-core (pusat kontrol).
 *
 * Berjalan di setiap request web. Bila pengguna sedang login dengan peran
 * yang ditutup admin pusat (atau akses aplikasi ditutup total), sesi diputus
 * dan pengguna diarahkan kembali ke halaman login — di mana pun ia berada,
 * tanpa bergantung pada apakah route tujuan sudah memasang middleware izin.
 *
 * Fail-open: bila sae-core tak terjangkau, CoreControlService mengembalikan
 * status longgar sehingga aplikasi tetap berjalan normal.
 */
class EnforceCoreAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = session('user');

        if (!$user) {
            return $next($request);
        }

        $role = is_array($user)
            ? ($user['role'] ?? ($user['peran_id_str'] ?? null))
            : ($user->role ?? ($user->peran_id_str ?? null));

        if ($role !== null && !CoreControlService::roleAllowed($role)) {
            session()->forget(['user', 'force_update_password']);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses portal untuk peran ini sedang ditutup oleh administrator pusat.',
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Akses portal untuk peran ini sedang ditutup oleh administrator pusat.');
        }

        return $next($request);
    }
}
