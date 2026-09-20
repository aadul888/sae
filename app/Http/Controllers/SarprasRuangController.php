<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SarprasRuangController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'delete');

        $search = trim($request->query('q', ''));
        $gedungFilter = trim($request->query('gedung', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));

        $query = DB::table('sarpras_ruang as sr')
            ->leftJoin('gtk', 'sr.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
            ->select('sr.*', 'gtk.nama as pj_nama', 'gtk.nip as pj_nip');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sr.kode_ruang', 'like', "%{$search}%")
                  ->orWhere('sr.nama_ruang', 'like', "%{$search}%")
                  ->orWhere('sr.gedung', 'like', "%{$search}%")
                  ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }

        if ($gedungFilter !== '') {
            $query->where('sr.gedung', $gedungFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('sr.kondisi', $kondisiFilter);
        }

        // Stats
        $statTotal = DB::table('sarpras_ruang')->count();
        $statBaik = DB::table('sarpras_ruang')->where('kondisi', 'baik')->count();
        $statRusakRingan = DB::table('sarpras_ruang')->where('kondisi', 'rusak_ringan')->count();
        $statRusakBerat = DB::table('sarpras_ruang')->where('kondisi', 'rusak_berat')->count();

        // Distinct gedung untuk filter
        $gedungList = DB::table('sarpras_ruang')
            ->whereNotNull('gedung')
            ->where('gedung', '<>', '')
            ->distinct()
            ->pluck('gedung');

        // Master GTK untuk penanggung jawab
        $allGtk = DB::table('gtk')->orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nip']);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;

        $list = $query->orderBy('sr.gedung', 'asc')->orderBy('sr.nama_ruang', 'asc')->paginate($perPage)->withQueryString();

        if ($request->ajax() && $request->has('ajax_table')) {
            return view('dashboard.sarpras.ruang-table', compact(
                'list', 'canCreate', 'canRead', 'canUpdate', 'canDelete'
            ));
        }

        return view('dashboard.sarpras.ruang', compact(
            'list',
            'search',
            'gedungFilter',
            'kondisiFilter',
            'gedungList',
            'allGtk',
            'statTotal',
            'statBaik',
            'statRusakRingan',
            'statRusakBerat',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_ruang' => 'required|string|max:50|unique:sarpras_ruang,kode_ruang',
            'nama_ruang' => 'required|string|max:255',
            'gedung' => 'nullable|string|max:100',
            'lantai' => 'nullable|string|max:20',
            'penanggung_jawab_ptk_id' => 'nullable|string|max:100',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'keterangan' => 'nullable|string|max:500',
        ], [
            'kode_ruang.unique' => 'Kode ruang sudah digunakan.',
            'nama_ruang.required' => 'Nama ruang wajib diisi.',
        ]);

        DB::table('sarpras_ruang')->insert([
            'kode_ruang' => strtoupper(trim($request->kode_ruang)),
            'nama_ruang' => trim($request->nama_ruang),
            'gedung' => $request->gedung,
            'lantai' => $request->lantai,
            'penanggung_jawab_ptk_id' => $request->penanggung_jawab_ptk_id,
            'kondisi' => $request->kondisi,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Ruang berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.sarpras.ruang.index')->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_ruang' => 'required|string|max:50|unique:sarpras_ruang,kode_ruang,' . $id,
            'nama_ruang' => 'required|string|max:255',
            'gedung' => 'nullable|string|max:100',
            'lantai' => 'nullable|string|max:20',
            'penanggung_jawab_ptk_id' => 'nullable|string|max:100',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'keterangan' => 'nullable|string|max:500',
        ]);

        DB::table('sarpras_ruang')->where('id', $id)->update([
            'kode_ruang' => strtoupper(trim($request->kode_ruang)),
            'nama_ruang' => trim($request->nama_ruang),
            'gedung' => $request->gedung,
            'lantai' => $request->lantai,
            'penanggung_jawab_ptk_id' => $request->penanggung_jawab_ptk_id,
            'kondisi' => $request->kondisi,
            'keterangan' => $request->keterangan,
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data ruang berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.sarpras.ruang.index')->with('success', 'Data ruang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        // Cek apakah ada aset di ruang ini
        $asetCount = DB::table('sarpras_aset')->where('ruang_id', $id)->count();
        if ($asetCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Ruang tidak dapat dihapus karena masih menampung {$asetCount} data aset/barang."
            ], 422);
        }

        DB::table('sarpras_ruang')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Ruang berhasil dihapus.']);
    }
}
