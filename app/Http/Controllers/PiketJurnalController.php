<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PiketJurnalController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'guru';

        $canCreate = RolePermission::canAccess($user, 'menu_piket', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_piket', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_piket', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_piket', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'guru', 'tendik'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Jurnal Piket.');
        }

        $search = $request->get('search', '');
        $tanggal = $request->get('tanggal', '');
        $shift = $request->get('shift', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'tanggal');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('guru_piket_jurnal as j')
            ->leftJoin('gtk', 'j.ptk_id', '=', 'gtk.ptk_id')
            ->select('j.*', 'gtk.nama as nama_guru_piket');

        if ($tanggal) {
            $query->where('j.tanggal', $tanggal);
        }

        if ($shift) {
            $query->where('j.shift_jam', $shift);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('j.catatan_kejadian', 'like', "%{$search}%")
                    ->orWhere('gtk.nama', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('j.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        // GTK List untuk Guru Berhalangan / Pengganti
        $gtkList = DB::table('gtk')->orderBy('nama')->get();

        $stats = [
            'total' => DB::table('guru_piket_jurnal')->count(),
            'bulan_ini' => DB::table('guru_piket_jurnal')->whereMonth('tanggal', date('m'))->whereYear('tanggal', date('Y'))->count(),
            'hari_ini' => DB::table('guru_piket_jurnal')->where('tanggal', date('Y-m-d'))->count(),
        ];

        return view('dashboard.piket.jurnal', compact(
            'list', 'search', 'tanggal', 'shift', 'perPage', 'sort', 'sortDir',
            'gtkList', 'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'shift_jam' => 'required|string',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? $request->ptk_id ?? null;

        // Auto hitung siswa terlambat & izin dari database jika tidak diinput manual
        $countTerlambat = $request->filled('jumlah_siswa_terlambat')
            ? (int) $request->jumlah_siswa_terlambat
            : DB::table('presensi_harian')->where('tanggal', $request->tanggal)->where('status', 'T')->count();

        $countIzin = $request->filled('jumlah_siswa_izin')
            ? (int) $request->jumlah_siswa_izin
            : DB::table('peserta_didik_izin_keluar')->where('tanggal', $request->tanggal)->count();

        DB::table('guru_piket_jurnal')->insert([
            'tanggal' => $request->tanggal,
            'ptk_id' => $ptkId,
            'shift_jam' => $request->shift_jam,
            'catatan_kejadian' => $request->catatan_kejadian,
            'jumlah_siswa_terlambat' => $countTerlambat,
            'jumlah_siswa_izin' => $countIzin,
            'guru_tidak_hadir' => $request->guru_tidak_hadir ? json_encode($request->guru_tidak_hadir, JSON_UNESCAPED_UNICODE) : null,
            'status' => $request->status ?? 'berjalan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.piket.jurnal.index')->with('success', 'Jurnal piket berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'shift_jam' => 'required|string',
        ]);

        DB::table('guru_piket_jurnal')->where('id', $id)->update([
            'tanggal' => $request->tanggal,
            'shift_jam' => $request->shift_jam,
            'catatan_kejadian' => $request->catatan_kejadian,
            'jumlah_siswa_terlambat' => (int) $request->jumlah_siswa_terlambat,
            'jumlah_siswa_izin' => (int) $request->jumlah_siswa_izin,
            'guru_tidak_hadir' => $request->guru_tidak_hadir ? json_encode($request->guru_tidak_hadir, JSON_UNESCAPED_UNICODE) : null,
            'status' => $request->status ?? 'berjalan',
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.piket.jurnal.index')->with('success', 'Jurnal piket berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('guru_piket_jurnal')->where('id', $id)->delete();
        return redirect()->route('dashboard.piket.jurnal.index')->with('success', 'Jurnal piket berhasil dihapus.');
    }
}
