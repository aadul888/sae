<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CopyrightGuard;

/**
 * VerifyCopyright — Middleware proteksi hak cipta.
 *
 * Memvalidasi integritas CopyrightGuard di setiap request.
 * Jika file dihapus, dimodifikasi, atau data pengembang diganti,
 * middleware ini akan menghentikan aplikasi dan menampilkan
 * halaman error yang mengarahkan ke pengembang asli.
 *
 * @author Abdul Azis, SKom.
 */
class VerifyCopyright
{
    public function handle(Request $request, Closure $next)
    {
        // Izinkan akses ke route install (agar installer tetap berjalan)
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        try {
            // Cek apakah class CopyrightGuard masih ada
            if (!class_exists(\App\Services\CopyrightGuard::class)) {
                return $this->abortLicense();
            }

            // Validasi integritas data hak cipta
            if (!CopyrightGuard::verify()) {
                return $this->abortLicense();
            }
        } catch (\Throwable $e) {
            // Jika file dihapus atau corrupt, tangkap error
            return $this->abortLicense();
        }

        return $next($request);
    }

    /**
     * Tampilkan halaman pelanggaran lisensi.
     */
    private function abortLicense()
    {
        $developer = 'Abdul Azis, SKom.';
        $whatsapp = '085860605060';
        $waLink = 'https://wa.me/6285860605060';
        $address = 'Pagelaran, Cianjur, Jawa Barat, 43266';

        return response()->view('errors.license', compact(
            'developer',
            'whatsapp',
            'waLink',
            'address'
        ), 503);
    }
}
