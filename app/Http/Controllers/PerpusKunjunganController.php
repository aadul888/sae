<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PerpusKunjunganController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_perpustakaan', 'create') || RolePermission::canAccess($user, 'menu_perpustakaan_kunjungan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_perpustakaan', 'read') || RolePermission::canAccess($user, 'menu_perpustakaan_kunjungan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_perpustakaan', 'update') || RolePermission::canAccess($user, 'menu_perpustakaan_kunjungan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_perpustakaan', 'delete') || RolePermission::canAccess($user, 'menu_perpustakaan_kunjungan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'guru', 'pustakawan'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Buku Kunjungan Perpustakaan.');
        }

        $q = $request->get('q', $request->get('search', ''));
        $search = $q;
        $tanggal = $request->get('tanggal', '');
        $tipe = $request->get('tipe', $request->get('pengunjung_tipe', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', $request->get('sort_dir', 'desc'));

        $allowedSort = ['id', 'tanggal', 'jam_kunjung', 'nama', 'pengunjung_tipe', 'rombel_atau_unit', 'keperluan'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = DB::table('perpus_kunjungan');

        if ($tanggal) {
            $query->where('tanggal', $tanggal);
        }

        if ($tipe) {
            $query->where('pengunjung_tipe', $tipe);
        }

        if (!empty($q)) {
            $query->where(function ($qb) use ($q) {
                $qb->where('nama', 'like', "%{$q}%")
                    ->orWhere('rombel_atau_unit', 'like', "%{$q}%")
                    ->orWhere('keperluan', 'like', "%{$q}%");
            });
        }

        $kunjungan = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total_kunjungan' => DB::table('perpus_kunjungan')->count(),
            'hari_ini'        => DB::table('perpus_kunjungan')->where('tanggal', date('Y-m-d'))->count(),
            'bulan_ini'       => DB::table('perpus_kunjungan')->whereYear('tanggal', date('Y'))->whereMonth('tanggal', date('m'))->count(),
            'siswa'           => DB::table('perpus_kunjungan')->where('pengunjung_tipe', 'siswa')->count(),
            'guru_staf'       => DB::table('perpus_kunjungan')->whereIn('pengunjung_tipe', ['guru', 'tendik', 'gtk'])->count(),
        ];

        return view('dashboard.perpus.kunjungan', compact(
            'kunjungan', 'q', 'search', 'tanggal', 'tipe', 'perPage', 'sort', 'sortDir',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_kunjung' => 'required',
            'pengunjung_tipe' => 'required|string',
            'keperluan' => 'required|string',
        ]);

        DB::table('perpus_kunjungan')->insert([
            'tanggal' => $request->tanggal,
            'jam_kunjung' => $request->jam_kunjung,
            'pengunjung_tipe' => $request->pengunjung_tipe,
            'pengunjung_id' => $request->pengunjung_id ?: null,
            'nama' => $request->nama,
            'rombel_atau_unit' => $request->rombel_atau_unit,
            'keperluan' => $request->keperluan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.perpustakaan.kunjungan')->with('success', 'Kunjungan perpustakaan berhasil dicatat.');
    }

    public function destroy($id)
    {
        DB::table('perpus_kunjungan')->where('id', $id)->delete();
        return redirect()->route('dashboard.perpustakaan.kunjungan')->with('success', 'Catatan kunjungan berhasil dihapus.');
    }
}
