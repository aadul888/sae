<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DapodikController extends Controller
{
    private function checkAdmin()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = is_array($user) ? ($user['role'] ?? null) : ($user->role ?? null);
        if ($userRole !== 'admin') {
            return redirect()->route('dashboard.' . ($userRole ?: 'admin'));
        }
        return null;
    }

    public function index()
    {
        if ($res = $this->checkAdmin()) return $res;

        $sekolah = DB::table('sekolah')->first();
        $totalGtk = DB::table('gtk')->count();
        $totalSiswa = DB::table('peserta_didik')->count();
        $totalRombel = DB::table('rombongan_belajar')->count();
        
        $setting = DB::table('settings')->where('id', 1)->first();
        $apiKey = $setting->api_key ?? 'sae_secret_live_key_2026';
        $lastSync = $setting->last_sync ?? ($sekolah->updated_at ?? '-');

        return view('dashboard.tarik-data', compact('sekolah', 'totalGtk', 'totalSiswa', 'totalRombel', 'apiKey', 'lastSync'));
    }

    public function generateApiKey()
    {
        if ($res = $this->checkAdmin()) return $res;

        $newKey = 'SAE_' . bin2hex(random_bytes(16));
        DB::table('settings')->updateOrInsert(
            ['id' => 1],
            ['api_key' => $newKey, 'updated_at' => now()]
        );

        return back()->with('success', 'API Key berhasil diperbarui.');
    }
}
