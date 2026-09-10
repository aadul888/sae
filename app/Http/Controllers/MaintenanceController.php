<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));
        }

        return view('dashboard.maintenance');
    }

    public function downloadArchive()
    {
        // Dummy logic untuk mengunduh file zip kosong / dummy string archive
        $filename = 'SAE_Arsip_Backup_' . date('Ymd_His') . '.zip';
        $dummyContent = "This is a dummy archive file for demonstration purposes.\n"
            . "Generated at: " . date('Y-m-d H:i:s');

        return response($dummyContent)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function cleanOldData(Request $request)
    {
        // Dummy logic untuk membersihkan data lama
        return response()->json([
            'status' => 'success',
            'message' => 'Simulasi pembersihan data lama (log & cache) berhasil diselesaikan.',
        ]);
    }
}
