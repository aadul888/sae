<?php

namespace App\Http\Controllers;

use App\Services\GtkFotoService;
use App\Services\ImageOptimizerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Unggah/hapus pasfoto GTK dari menu Manajemen Data (Guru, Tendik, Non-ASN).
 *
 * Otorisasi per menu ditangani middleware permission di routes.
 */
class GtkFotoController extends Controller
{
    public function __construct(protected GtkFotoService $fotoService) {}

    public function uploadFoto(Request $request, ImageOptimizerService $optimizer): JsonResponse
    {
        $res = $this->fotoService->uploadFoto($request, $optimizer);

        return response()->json(
            collect($res)->except('status_code')->all(),
            $res['status_code'] ?? 200
        );
    }

    public function deleteFoto(Request $request, ImageOptimizerService $optimizer): JsonResponse
    {
        $res = $this->fotoService->deleteFoto((string) $request->input('ptk_id', ''), $optimizer);

        return response()->json(
            collect($res)->except('status_code')->all(),
            $res['status_code'] ?? 200
        );
    }
}
