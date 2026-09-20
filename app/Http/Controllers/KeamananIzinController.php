<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class KeamananIzinController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canRead = RolePermission::canAccess($user, 'menu_keamanan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_keamanan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_keamanan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'satpam'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Keamanan.');
        }

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'rb.nama as nama_rombel'
            );

        if ($tanggal) {
            $query->where('iz.tanggal', $tanggal);
        }

        if ($status) {
            $query->where('iz.status', $status);
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

        // Stats summary for today
        $stats = [
            'total' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->count(),
            'menunggu' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'menunggu_satpam')->count(),
            'di_luar' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'di_luar')->count(),
            'kembali' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'kembali')->count(),
            'pulang' => DB::table('peserta_didik_izin_keluar')->where('tanggal', $tanggal)->where('status', 'pulang_selesai')->count(),
        ];

        return view('dashboard.keamanan.izin', compact(
            'list', 'search', 'status', 'tanggal', 'perPage', 'sort', 'sortDir',
            'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function verifikasiTiket(Request $request)
    {
        $nomorTiket = trim($request->input('nomor_tiket', ''));
        if (empty($nomorTiket)) {
            return response()->json(['success' => false, 'message' => 'Nomor tiket tidak boleh kosong.'], 422);
        }

        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->select('iz.*', 'pd.nama as nama_siswa', 'pd.nisn', 'rb.nama as nama_rombel')
            ->where('iz.nomor_tiket', $nomorTiket)
            ->first();

        if (!$tiket) {
            return response()->json(['success' => false, 'message' => 'Tiket e-Izin dengan nomor "' . $nomorTiket . '" tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $tiket]);
    }

    public function checkout(Request $request, $id)
    {
        $user = session('user');
        $namaSatpam = $user['nama'] ?? 'Petugas Satpam';

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return redirect()->back()->with('error', 'Data tiket tidak ditemukan.');
        }

        $newStatus = ($tiket->jenis_izin === 'pulang_cepat') ? 'pulang_selesai' : 'di_luar';

        DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
            'status' => $newStatus,
            'satpam_checkout_by' => $namaSatpam,
            'satpam_checkout_at' => now(),
            'catatan_satpam' => $request->input('catatan_satpam', $tiket->catatan_satpam),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Siswa berhasil diverifikasi KELUAR gerbang (Tiket: ' . $tiket->nomor_tiket . ').');
    }

    public function checkin(Request $request, $id)
    {
        $user = session('user');
        $namaSatpam = $user['nama'] ?? 'Petugas Satpam';

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return redirect()->back()->with('error', 'Data tiket tidak ditemukan.');
        }

        DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
            'status' => 'kembali',
            'jam_kembali_aktual' => now()->format('H:i:s'),
            'satpam_checkin_by' => $namaSatpam,
            'satpam_checkin_at' => now(),
            'catatan_satpam' => $request->input('catatan_satpam', $tiket->catatan_satpam),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Siswa berhasil diverifikasi KEMBALI ke sekolah (Tiket: ' . $tiket->nomor_tiket . ').');
    }
}
