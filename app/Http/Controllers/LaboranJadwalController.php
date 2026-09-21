<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboranJadwalController extends Controller
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

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'delete');

        $search = trim($request->query('q', ''));
        $labFilter = trim($request->query('lab', ''));
        $statusFilter = trim($request->query('status', ''));
        $tanggalFilter = trim($request->query('tanggal', ''));

        $query = DB::table('laboran_jadwal_penggunaan as ljp')
            ->leftJoin('gtk', 'ljp.ptk_id', '=', 'gtk.ptk_id')
            ->leftJoin('rombongan_belajar as rb', 'ljp.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->select('ljp.*', 'gtk.nama as guru_nama', 'gtk.nip as guru_nip', 'rb.nama as rombel_nama');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ljp.mata_pelajaran', 'like', "%{$search}%")
                  ->orWhere('ljp.topik_praktik', 'like', "%{$search}%")
                  ->orWhere('gtk.nama', 'like', "%{$search}%")
                  ->orWhere('rb.nama', 'like', "%{$search}%");
            });
        }

        if ($labFilter !== '') {
            $query->where('ljp.ruang_lab_nama', $labFilter);
        }

        if ($statusFilter !== '') {
            $query->where('ljp.status', $statusFilter);
        }

        if ($tanggalFilter !== '') {
            $query->where('ljp.tanggal', $tanggalFilter);
        }

        // Stats
        $statTotal = DB::table('laboran_jadwal_penggunaan')->count();
        $statHariIni = DB::table('laboran_jadwal_penggunaan')->where('tanggal', now()->toDateString())->count();
        $statBerlangsung = DB::table('laboran_jadwal_penggunaan')->where('status', 'berlangsung')->count();
        $statSelesai = DB::table('laboran_jadwal_penggunaan')->where('status', 'selesai')->count();

        // Ambil daftar ruang & bengkel praktik dari Master Sarpras & Aset
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get(['id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai', 'kondisi']);

        // Ambil nama-nama ruang unik untuk dropdown / filter
        $daftarLab = $daftarRuangSarpras->pluck('nama_ruang')->toArray();

        // Gabungkan dengan ruang yang mungkin sudah tercatat sebelumnya di jadwal
        $existingLabs = DB::table('laboran_jadwal_penggunaan')->distinct()->pluck('ruang_lab_nama')->toArray();
        if (!empty($existingLabs)) {
            $daftarLab = array_values(array_unique(array_merge($daftarLab, $existingLabs)));
        }

        $allGtk = DB::table('gtk')->orderBy('nama', 'asc')->get(['ptk_id', 'nama', 'nip']);
        $allRombel = DB::table('rombongan_belajar')->orderBy('nama', 'asc')->get(['rombongan_belajar_id', 'nama']);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;

        $list = $query->orderBy('ljp.tanggal', 'desc')->orderBy('ljp.jam_mulai', 'desc')->paginate($perPage)->withQueryString();

        return view('dashboard.laboran.jadwal', compact(
            'list',
            'search',
            'labFilter',
            'statusFilter',
            'tanggalFilter',
            'daftarLab',
            'daftarRuangSarpras',
            'allGtk',
            'allRombel',
            'statTotal',
            'statHariIni',
            'statBerlangsung',
            'statSelesai',
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
            'ruang_lab_nama' => 'required|string|max:100',
            'ptk_id' => 'required|string|max:100',
            'rombel_id' => 'nullable|string|max:100',
            'mata_pelajaran' => 'required|string|max:255',
            'topik_praktik' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'alat_bahan_digunakan' => 'nullable|string|max:1000',
        ]);

        DB::table('laboran_jadwal_penggunaan')->insert([
            'ruang_lab_nama' => $request->ruang_lab_nama,
            'ptk_id' => $request->ptk_id,
            'rombel_id' => $request->rombel_id,
            'mata_pelajaran' => trim($request->mata_pelajaran),
            'topik_praktik' => trim($request->topik_praktik),
            'tanggal' => $request->tanggal,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'alat_bahan_digunakan' => $request->alat_bahan_digunakan,
            'status' => 'dijadwalkan',
            'laboran_petugas_ptk_id' => session('user')['ptk_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Jadwal penggunaan lab berhasil disimpan.']);
        }

        return redirect()->route('dashboard.laboran.jadwal.index')->with('success', 'Jadwal penggunaan lab berhasil disimpan.');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:dijadwalkan,berlangsung,selesai,batal',
            'laporan_kerusakan' => 'nullable|string|max:1000',
        ]);

        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->update([
            'status' => $request->status,
            'laporan_kerusakan' => $request->laporan_kerusakan,
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Status penggunaan lab berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.laboran.jadwal.index')->with('success', 'Status penggunaan lab berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('laboran_jadwal_penggunaan')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Jadwal lab berhasil dihapus.']);
    }
}
