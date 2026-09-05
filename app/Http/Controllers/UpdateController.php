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
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') return redirect()->route('dashboard.' . ($role ?: 'siswa'));

        $status = $this->updateService->checkUpdate();
        return view('dashboard.update', compact('status'));
    }

    /**
     * API Cek update realtime
     */
    public function check(): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

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
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $result = $this->updateService->runUpdate();
        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'data' => $result,
        ]);
    }
}
