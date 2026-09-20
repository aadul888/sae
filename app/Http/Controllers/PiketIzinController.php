<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PiketIzinController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'guru';

        $canCreate = RolePermission::canAccess($user, 'menu_piket', 'create') || RolePermission::canAccess($user, 'menu_e_izin', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_piket', 'read') || RolePermission::canAccess($user, 'menu_e_izin', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_piket', 'update') || RolePermission::canAccess($user, 'menu_e_izin', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_piket', 'delete') || RolePermission::canAccess($user, 'menu_e_izin', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'guru', 'tendik'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Piket Sekolah.');
        }

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $jenis = $request->get('jenis_izin', '');
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'rb.nama as nama_rombel',
                'piket.nama as nama_piket'
            );

        if ($tanggal) {
            $query->where('iz.tanggal', $tanggal);
        }

        if ($status) {
            $query->where('iz.status', $status);
        }

        if ($jenis) {
            $query->where('iz.jenis_izin', $jenis);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('iz.nomor_tiket', 'like', "%{$search}%")
                    ->orWhere('pd.nama', 'like', "%{$search}%")
                    ->orWhere('pd.nisn', 'like', "%{$search}%")
                    ->orWhere('iz.alasan', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('iz.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        // Daftar Rombel & Siswa untuk Modal Tambah Izin Piket
        $rombelList = DB::table('rombongan_belajar')->orderBy('nama')->get();
        $siswaList = DB::table('peserta_didik')
            ->select('peserta_didik_id', 'nama', 'nisn', 'rombongan_belajar_id')
            ->orderBy('nama')
            ->limit(200)
            ->get();

        $stats = [
            'total' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->count(),
            'keluar_sebentar' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('jenis_izin', 'keluar_sebentar')->count(),
            'pulang_cepat' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('jenis_izin', 'pulang_cepat')->count(),
            'menunggu_gerbang' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'menunggu_satpam')->count(),
            'di_luar' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'di_luar')->count(),
        ];

        return view('dashboard.piket.izin', compact(
            'list', 'search', 'status', 'jenis', 'tanggal', 'perPage', 'sort', 'sortDir',
            'rombelList', 'siswaList', 'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function searchSiswa(Request $request)
    {
        $q = trim($request->get('q', ''));
        $rombelId = $request->get('rombel_id', '');

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.rombongan_belajar_id', 'rb.nama as nama_rombel');

        if ($rombelId) {
            $query->where('pd.rombongan_belajar_id', $rombelId);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }

        $results = $query->limit(30)->get();
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'jenis_izin' => 'required|string',
            'alasan' => 'required|string|max:500',
            'jam_izin_keluar' => 'required',
        ]);

        $user = session('user');
        $ptkId = $user['ptk_id'] ?? null;
        $createdByName = $user['nama'] ?? 'Guru Piket';

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $request->peserta_didik_id)->first();
        $rombelId = $siswa->rombongan_belajar_id ?? $request->rombel_id ?? null;

        // Generate unique ticket number: IZN-YYYYMMDD-XXXX
        $todayStr = date('Ymd');
        $lastTicket = DB::table('peserta_didik_izin_keluar')
            ->where('nomor_tiket', 'like', "IZN-{$todayStr}-%")
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastTicket && preg_match('/IZN-\d+-(\d+)/', $lastTicket->nomor_tiket, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }
        $nomorTiket = sprintf("IZN-%s-%04d", $todayStr, $nextSeq);

        $id = DB::table('peserta_didik_izin_keluar')->insertGetId([
            'nomor_tiket' => $nomorTiket,
            'peserta_didik_id' => $request->peserta_didik_id,
            'rombel_id' => $rombelId,
            'tanggal' => $request->tanggal ?? date('Y-m-d'),
            'jenis_izin' => $request->jenis_izin,
            'alasan' => $request->alasan,
            'jam_izin_keluar' => $request->jam_izin_keluar,
            'jam_rencana_kembali' => $request->jam_rencana_kembali ?: null,
            'petugas_piket_ptk_id' => $ptkId,
            'status' => 'menunggu_satpam',
            'created_by' => $createdByName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.piket.izin.index')
            ->with('success', 'e-Izin berhasil diterbitkan dengan nomor tiket: ' . $nomorTiket);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jenis_izin' => 'required|string',
            'alasan' => 'required|string|max:500',
            'jam_izin_keluar' => 'required',
        ]);

        DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
            'jenis_izin' => $request->jenis_izin,
            'alasan' => $request->alasan,
            'jam_izin_keluar' => $request->jam_izin_keluar,
            'jam_rencana_kembali' => $request->jam_rencana_kembali ?: null,
            'status' => $request->status ?? 'menunggu_satpam',
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.piket.izin.index')->with('success', 'Data tiket e-Izin berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('peserta_didik_izin_keluar')->where('id', $id)->delete();
        return redirect()->route('dashboard.piket.izin.index')->with('success', 'Tiket e-Izin berhasil dihapus.');
    }

    public function cetakSlip($id)
    {
        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select('iz.*', 'pd.nama as nama_siswa', 'pd.nisn', 'pd.nik', 'rb.nama as nama_rombel', 'piket.nama as nama_piket')
            ->where('iz.id', $id)
            ->first();

        if (!$tiket) {
            return redirect()->route('dashboard.piket.izin.index')->with('error', 'Tiket tidak ditemukan.');
        }

        $sekolah = DB::table('sekolah')->first();

        return view('dashboard.piket.cetak-slip-izin', compact('tiket', 'sekolah'));
    }
}
