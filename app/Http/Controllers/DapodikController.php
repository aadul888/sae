<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

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
        $totalPesertaDidik = DB::table('peserta_didik')->count();
        $totalRombel = DB::table('rombongan_belajar')->count();

        $setting = DB::table('settings')->where('id', 1)->first();
        $apiKey = $setting->api_key ?? 'sae_secret_live_key_2026';
        $lastSync = $setting->last_sync ?? ($sekolah->updated_at ?? '-');

        $hasActiveData = ($totalPesertaDidik > 0 || $totalGtk > 0 || $totalRombel > 0);
        $syncAllowed = $hasActiveData ? (bool)($setting->sync_allowed ?? false) : true;
        $archiveDownloadedAt = $setting->archive_downloaded_at ?? null;
        $feederFile = public_path('downloads/SAE-Feeder-Setup.zip');
        $feederDownload = File::exists($feederFile) ? [
            'url' => asset('downloads/SAE-Feeder-Setup.zip'),
            'size' => File::size($feederFile),
            'updated_at' => File::lastModified($feederFile),
        ] : null;

        return view('dashboard.tarik-data', compact(
            'sekolah',
            'totalGtk',
            'totalPesertaDidik',
            'totalRombel',
            'apiKey',
            'lastSync',
            'hasActiveData',
            'syncAllowed',
            'archiveDownloadedAt',
            'feederDownload'
        ));
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
