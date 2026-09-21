<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PenjagaKebersihanController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_penjaga', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_penjaga', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_penjaga', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_penjaga', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'penjaga'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Fasilitas & Kebersihan.');
        }

        $search = $request->get('search', '');
        $tanggal = $request->get('tanggal', '');
        $shift = $request->get('shift', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('kebersihan_checklist as kc')
            ->leftJoin('gtk', 'kc.petugas_ptk_id', '=', 'gtk.ptk_id')
            ->select('kc.*', 'gtk.nama as nama_petugas');

        if ($tanggal) {
            $query->where('kc.tanggal', $tanggal);
        }

        if ($shift) {
            $query->where('kc.shift', $shift);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('kc.area_zona', 'like', "%{$search}%")
                    ->orWhere('kc.catatan_temuan', 'like', "%{$search}%")
                    ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('kc.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total' => DB::table('kebersihan_checklist')->count(),
            'hari_ini' => DB::table('kebersihan_checklist')->where('tanggal', date('Y-m-d'))->count(),
            'perlu_tindakan' => DB::table('kebersihan_checklist')->where('kondisi_kebersihan', 'perlu_tindakan')->count(),
            'air_mati' => DB::table('kebersihan_checklist')->where('ketersediaan_air_sabun', 'air_mati')->count(),
        ];

        $daftarRuang = DB::table('sarpras_ruang')
            ->select('id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get();

        return view('dashboard.penjaga.kebersihan', compact(
            'list', 'search', 'tanggal', 'shift', 'perPage', 'sort', 'sortDir',
            'daftarRuang',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'shift' => 'required|string',
            'area_zona' => 'required|string|max:100',
            'kondisi_kebersihan' => 'required|string',
            'ketersediaan_air_sabun' => 'required|string',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;

        DB::table('kebersihan_checklist')->insert([
            'tanggal' => $request->tanggal,
            'shift' => $request->shift,
            'area_zona' => $request->area_zona,
            'kondisi_kebersihan' => $request->kondisi_kebersihan,
            'ketersediaan_air_sabun' => $request->ketersediaan_air_sabun,
            'catatan_temuan' => $request->catatan_temuan,
            'petugas_ptk_id' => $ptkId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.penjaga.kebersihan.index')->with('success', 'Checklist kebersihan berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'shift' => 'required|string',
            'area_zona' => 'required|string|max:100',
            'kondisi_kebersihan' => 'required|string',
            'ketersediaan_air_sabun' => 'required|string',
        ]);

        DB::table('kebersihan_checklist')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'shift' => $request->shift,
            'area_zona' => $request->area_zona,
            'kondisi_kebersihan' => $request->kondisi_kebersihan,
            'ketersediaan_air_sabun' => $request->ketersediaan_air_sabun,
            'catatan_temuan' => $request->catatan_temuan,
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.penjaga.kebersihan.index')->with('success', 'Checklist kebersihan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('kebersihan_checklist')->where('id', $id)->delete();
        return redirect()->route('dashboard.penjaga.kebersihan.index')->with('success', 'Data checklist kebersihan berhasil dihapus.');
    }
}
