<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class KeamananBukuTamuController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_buku_tamu', 'create') || RolePermission::canAccess($user, 'menu_keamanan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_buku_tamu', 'read') || RolePermission::canAccess($user, 'menu_keamanan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_buku_tamu', 'update') || RolePermission::canAccess($user, 'menu_keamanan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_buku_tamu', 'delete') || RolePermission::canAccess($user, 'menu_keamanan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'satpam'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Buku Tamu.');
        }

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $tanggal = $request->get('tanggal', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('keamanan_buku_tamu');

        if ($tanggal) {
            $query->where('tanggal', $tanggal);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_tamu', 'like', "%{$search}%")
                    ->orWhere('instansi_asal', 'like', "%{$search}%")
                    ->orWhere('tujuan_bertemu', 'like', "%{$search}%")
                    ->orWhere('keperluan', 'like', "%{$search}%")
                    ->orWhere('nomor_kartu_visitor', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        $stats = [
            'total' => DB::table('keamanan_buku_tamu')->count(),
            'hari_ini' => DB::table('keamanan_buku_tamu')->where('tanggal', date('Y-m-d'))->count(),
            'di_lokasi' => DB::table('keamanan_buku_tamu')->where('status', 'berada_di_lokasi')->count(),
            'sudah_keluar' => DB::table('keamanan_buku_tamu')->where('status', 'sudah_keluar')->count(),
        ];

        $daftarGtk = DB::table('gtk')->select('ptk_id', 'nama', 'nip', 'jenis_ptk_id_str')->orderBy('nama')->get();
        $daftarRuang = DB::table('sarpras_ruang')->select('id', 'kode_ruang', 'nama_ruang', 'gedung')->orderBy('nama_ruang')->get();

        return view('dashboard.keamanan.buku-tamu', compact(
            'list', 'search', 'status', 'tanggal', 'perPage', 'sort', 'sortDir',
            'daftarGtk', 'daftarRuang',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
            'tujuan_bertemu' => 'required|string|max:255',
            'keperluan' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_masuk' => 'required',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;

        DB::table('keamanan_buku_tamu')->insert([
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar ?: null,
            'nama_tamu' => $request->nama_tamu,
            'instansi_asal' => $request->instansi_asal,
            'nomor_kontak' => $request->nomor_kontak,
            'tujuan_bertemu' => $request->tujuan_bertemu,
            'keperluan' => $request->keperluan,
            'nomor_kartu_visitor' => $request->nomor_kartu_visitor,
            'nomor_polisi_kendaraan' => $request->nomor_polisi_kendaraan,
            'petugas_satpam_ptk_id' => $ptkId,
            'status' => $request->status ?? 'berada_di_lokasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Buku tamu berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_tamu' => 'required|string|max:255',
            'tujuan_bertemu' => 'required|string|max:255',
            'keperluan' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_masuk' => 'required',
        ]);

        DB::table('keamanan_buku_tamu')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk,
            'jam_keluar' => $request->jam_keluar ?: null,
            'nama_tamu' => $request->nama_tamu,
            'instansi_asal' => $request->instansi_asal,
            'nomor_kontak' => $request->nomor_kontak,
            'tujuan_bertemu' => $request->tujuan_bertemu,
            'keperluan' => $request->keperluan,
            'nomor_kartu_visitor' => $request->nomor_kartu_visitor,
            'nomor_polisi_kendaraan' => $request->nomor_polisi_kendaraan,
            'status' => $request->status ?? 'berada_di_lokasi',
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Data buku tamu berhasil diperbarui.');
    }

    public function checkout($id)
    {
        DB::table('keamanan_buku_tamu')->where('id', $id)->update([
            'jam_keluar' => now()->format('H:i:s'),
            'status' => 'sudah_keluar',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Tamu berhasil di-checkout.');
    }

    public function destroy($id)
    {
        DB::table('keamanan_buku_tamu')->where('id', $id)->delete();
        return redirect()->route('dashboard.keamanan.buku-tamu.index')->with('success', 'Data tamu berhasil dihapus.');
    }
}
