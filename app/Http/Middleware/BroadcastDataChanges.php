<?php

namespace App\Http\Middleware;

use App\Services\RealtimeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BroadcastDataChanges
{
    /**
     * Handle an incoming request and broadcast event on successful state mutation.
     *
     * ponytail: automatic broadcast on HTTP mutation. Upgrade to granular model observers if specific payload diffs are needed per entity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $path = $request->path();
            $statusCode = $response->getStatusCode();

            // Hanya mutasi sukses (< 400) dan di luar auth / streaming
            if (
                $statusCode < 400 &&
                !str_contains($path, 'login') &&
                !str_contains($path, 'logout') &&
                !str_contains($path, 'realtime') &&
                !str_contains($path, 'aktivitas') &&
                !str_contains($path, 'target-capaian') &&
                !str_contains($path, 'laporan')
            ) {
                try {
                    RealtimeService::trigger('data.changed', [
                        'path'   => $path,
                        'method' => $request->method(),
                    ]);
                } catch (\Throwable) {
                    // Jangan gagalkan response utama jika caching bermasalah
                }

                // Otomatis rekam aktivitas mutasi data (CRUD) tendik ke log sistem
                try {
                    $sessionUser = session('user');
                    if ($sessionUser && \Illuminate\Support\Facades\Schema::hasTable('tendik_aktivitas')) {
                        $method = $request->method();
                        $aksiLabel = match ($method) {
                            'POST' => 'Menambahkan data baru',
                            'PUT', 'PATCH' => 'Memperbarui data',
                            'DELETE' => 'Menghapus data',
                            default => 'Memproses data',
                        };

                        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
                        $moduleSlug = $segments[1] ?? ($segments[0] ?? 'sistem');
                        $subSegment = $segments[2] ?? '';

                        $bidang = match (true) {
                            str_contains($path, 'persuratan') => 'persuratan',
                            str_contains($path, 'kesiswaan') || str_contains($path, 'peserta-didik') => 'kesiswaan',
                            str_contains($path, 'kepegawaian') || str_contains($path, 'gtk') || str_contains($path, 'tendik') => 'kepegawaian',
                            str_contains($path, 'sarpras') || str_contains($path, 'rombel') => 'sarpras',
                            str_contains($path, 'presensi') => 'keamanan',
                            str_contains($path, 'jadwal') || str_contains($path, 'kalender') => 'umum',
                            str_contains($path, 'aktivitas') => 'umum',
                            default => 'umum',
                        };

                        $targetName = ucwords(str_replace(['-', '_'], ' ', $subSegment ?: $moduleSlug));
                        $judul = "{$aksiLabel} pada modul {$targetName}";
                        $uraian = "Operasi HTTP {$method} berhasil dieksekusi pada rute /{$path}";

                        \App\Models\TendikAktivitas::recordActivity($sessionUser, $judul, $bidang, $uraian, 'selesai', 'Data Berhasil Disimpan');
                    }
                } catch (\Throwable) {
                    // Jangan mengganggu response utama
                }
            }
        }

        return $response;
    }
}
