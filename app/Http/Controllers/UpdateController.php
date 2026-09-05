<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UpdateController extends Controller
{
    protected UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->updateService = $updateService;
    }

    /**
     * Halaman update sistem untuk Operator / Admin
     */
    public function index()
    {
        $status = $this->updateService->checkUpdate();
        return view('dashboard.update', compact('status'));
    }

    /**
     * API Cek update realtime
     */
    public function check(): JsonResponse
    {
        $status = $this->updateService->checkUpdate();
        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }

    /**
     * Proses eksekusi update otomatis (File, Kode, Database)
     */
    public function execute(): JsonResponse
    {
        $result = $this->updateService->runUpdate();
        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result,
        ]);
    }
}
