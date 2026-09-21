<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PenjagaRondaController extends Controller
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
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Buku Jaga Malam.');
        }

        $search = $request->get('search', '');
        $tanggal = $request->get('tanggal', '');
        $situasi = $request->get('situasi_keamanan', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('penjaga_malam_log as pl')
            ->leftJoin('gtk', 'pl.petugas_ptk_id', '=', 'gtk.ptk_id')
            ->select('pl.*', 'gtk.nama as nama_petugas');

        if ($tanggal) {
            $query->where('pl.tanggal', $tanggal);
        }

        if ($situasi) {
            $query->where('pl.situasi_keamanan', $situasi);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('pl.zona_kontrol', 'like', "%{$search}%")
                    ->orWhere('pl.catatan_penjaga', 'like', "%{$search}%")
                    ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('pl.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total' => DB::table('penjaga_malam_log')->count(),
            'malam_ini' => DB::table('penjaga_malam_log')->where('tanggal', date('Y-m-d'))->count(),
            'kondusif' => DB::table('penjaga_malam_log')->where('situasi_keamanan', 'kondusif')->count(),
            'insiden_keamanan' => DB::table('penjaga_malam_log')->where('situasi_keamanan', '!=', 'kondusif')->count(),
        ];

        $daftarRuang = DB::table('sarpras_ruang')
            ->select('id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get();

        return view('dashboard.penjaga.ronda', compact(
            'list', 'search', 'tanggal', 'situasi', 'perPage', 'sort', 'sortDir',
            'daftarRuang',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_kontrol' => 'required',
            'zona_kontrol' => 'required|string|max:100',
            'status_pintu_jendela' => 'required|string',
            'status_lampu' => 'required|string',
            'situasi_keamanan' => 'required|string',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;

        DB::table('penjaga_malam_log')->insert([
            'tanggal' => $request->tanggal,
            'jam_kontrol' => $request->jam_kontrol,
            'zona_kontrol' => $request->zona_kontrol,
            'status_pintu_jendela' => $request->status_pintu_jendela,
            'status_lampu' => $request->status_lampu,
            'situasi_keamanan' => $request->situasi_keamanan,
            'catatan_penjaga' => $request->catatan_penjaga,
            'petugas_ptk_id' => $ptkId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.penjaga.ronda.index')->with('success', 'Log ronda malam berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jam_kontrol' => 'required',
            'zona_kontrol' => 'required|string|max:100',
            'status_pintu_jendela' => 'required|string',
            'status_lampu' => 'required|string',
            'situasi_keamanan' => 'required|string',
        ]);

        DB::table('penjaga_malam_log')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'jam_kontrol' => $request->jam_kontrol,
            'zona_kontrol' => $request->zona_kontrol,
            'status_pintu_jendela' => $request->status_pintu_jendela,
            'status_lampu' => $request->status_lampu,
            'situasi_keamanan' => $request->situasi_keamanan,
            'catatan_penjaga' => $request->catatan_penjaga,
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.penjaga.ronda.index')->with('success', 'Log ronda malam berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('penjaga_malam_log')->where('id', $id)->delete();
        return redirect()->route('dashboard.penjaga.ronda.index')->with('success', 'Data log ronda malam berhasil dihapus.');
    }
}
