<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class KeamananPresensiController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canRead = RolePermission::canAccess($user, 'menu_keamanan', 'read');
        if (!$canRead && !in_array($role, ['admin', 'tendik', 'satpam'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Keamanan.');
        }

        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $status = $request->get('status', '');
        $rombelId = $request->get('rombel_id', '');
        $search = $request->get('search', '');
        $perPage = (int) $request->get('perPage', 20);
        $sort = $request->get('sort', 'jam_masuk');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('presensi_harian as ph')
            ->leftJoin('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'ph.*',
                'pd.nama as nama_siswa',
                'pd.nisn as pd_nisn',
                'rb.nama as nama_rombel'
            )
            ->where('ph.tanggal', $tanggal);

        if ($status) {
            $query->where('ph.status', $status);
        }

        if ($rombelId) {
            $query->where('ph.rombongan_belajar_id', $rombelId);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pd.nama', 'like', "%{$search}%")
                    ->orWhere('ph.nisn', 'like', "%{$search}%")
                    ->orWhere('rb.nama', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('ph.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        // Rombel dropdown
        $rombelList = DB::table('rombongan_belajar')->orderBy('nama')->get();

        // Statistik Presensi Gerbang Hari Ini
        $totalSiswa = DB::table('peserta_didik')->count();
        $hadir = DB::table('presensi_harian')->where('tanggal', $tanggal)->where('status', 'H')->count();
        $terlambat = DB::table('presensi_harian')->where('tanggal', $tanggal)->where('status', 'T')->count();
        $izin = DB::table('presensi_harian')->where('tanggal', $tanggal)->whereIn('status', ['I', 'S', 'D'])->count();
        $pulang = DB::table('presensi_harian')->where('tanggal', $tanggal)->whereNotNull('jam_pulang')->count();

        $stats = compact('totalSiswa', 'hadir', 'terlambat', 'izin', 'pulang');

        return view('dashboard.keamanan.presensi', compact(
            'list', 'tanggal', 'status', 'rombelId', 'search', 'perPage', 'sort', 'sortDir',
            'rombelList', 'stats'
        ));
    }
}
