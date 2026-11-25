<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pagar pengaman ukuran unggahan global.
 *
 * Menolak (413) berkas yang melebihi `upload.absolute_max_kb` SEBELUM request
 * diproses controller, sehingga berkas raksasa tidak pernah mendarat di
 * penyimpanan. Ini jaring terakhir: aturan `max:` spesifik per controller tetap
 * yang menentukan batas sesungguhnya; pagar ini sengaja lebih longgar supaya
 * tidak pernah menolak unggahan yang sah.
 *
 * PHP sudah membuang berkas besar di tingkat `upload_max_filesize`, tetapi bila
 * server dikonfigurasi tinggi (mis. 2 GB), tanpa middleware ini berkas besar
 * lolos sampai controller. Middleware ini menutup celah itu untuk SEMUA route.
 */
class EnforceUploadSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxKb = (int) config('upload.absolute_max_kb', 32768);
        if ($maxKb <= 0) {
            return $next($request);
        }

        $maxBytes = $maxKb * 1024;
        $exempt = (array) config('upload.exempt_fields', []);

        foreach ($request->allFiles() as $field => $file) {
            if (in_array($field, $exempt, true)) {
                continue;
            }

            foreach (is_array($file) ? $file : [$file] as $single) {
                if (!$single instanceof \Illuminate\Http\UploadedFile) {
                    continue;
                }
                if ($single->getSize() > $maxBytes) {
                    $msg = 'Ukuran berkas "' . $field . '" terlalu besar (maksimal '
                        . number_format($maxKb / 1024, 0) . ' MB). Kompres dulu sebelum diunggah.';

                    return $request->expectsJson()
                        ? response()->json(['status' => 'error', 'message' => $msg], 413)
                        : back()->with('error', $msg);
                }
            }
        }

        return $next($request);
    }
}
