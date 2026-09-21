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

        $canCreate = RolePermission::canAccess($user, 'menu_perpustakaan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_perpustakaan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_perpustakaan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_perpustakaan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'guru', 'pustakawan'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Buku Kunjungan Perpustakaan.');
        }

        $search = $request->get('search', '');
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $tipe = $request->get('pengunjung_tipe', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('perpus_kunjungan');

        if ($tanggal) {
            $query->where('tanggal', $tanggal);
        }

        if ($tipe) {
            $query->where('pengunjung_tipe', $tipe);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('rombel_atau_unit', 'like', "%{$search}%")
                    ->orWhere('keperluan', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total_kunjungan' => DB::table('perpus_kunjungan')->count(),
            'hari_ini' => DB::table('perpus_kunjungan')->where('tanggal', date('Y-m-d'))->count(),
            'siswa_hari_ini' => DB::table('perpus_kunjungan')->where('tanggal', date('Y-m-d'))->where('pengunjung_tipe', 'siswa')->count(),
            'gtk_hari_ini' => DB::table('perpus_kunjungan')->where('tanggal', date('Y-m-d'))->where('pengunjung_tipe', 'gtk')->count(),
        ];

        $siswaList = DB::table('peserta_didik as pd')
            ->leftJoin('anggota_rombel as ar', 'pd.peserta_didik_id', '=', 'ar.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ar.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'rb.nama as nama_rombel')
            ->orderBy('pd.nama')
            ->limit(500)
            ->get();
        $gtkList = DB::table('gtk')
            ->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str')
            ->orderBy('nama')
            ->get();

        return view('dashboard.perpus.kunjungan', compact(
            'list', 'search', 'tanggal', 'tipe', 'perPage', 'sort', 'sortDir',
            'siswaList', 'gtkList',
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

        return redirect()->route('dashboard.perpustakaan.kunjungan.index')->with('success', 'Kunjungan perpustakaan berhasil dicatat.');
    }

    public function destroy($id)
    {
        DB::table('perpus_kunjungan')->where('id', $id)->delete();
        return redirect()->route('dashboard.perpustakaan.kunjungan.index')->with('success', 'Catatan kunjungan berhasil dihapus.');
    }
}
