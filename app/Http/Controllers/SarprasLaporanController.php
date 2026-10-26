<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller Laporan & Buku Induk Inventaris Sarpras.
 * Sesuai Indikator Kinerja Sarpras #13: Tersusunnya Buku Inventaris Aset dan Sarana Sekolah.
 */
class SarprasLaporanController extends Controller
{
    use ChecksAuthenticatedSession;

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canRead = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read');

        $kategoriFilter = trim($request->query('kategori', ''));
        $sumberFilter   = trim($request->query('sumber_dana', ''));
        $kondisiFilter  = trim($request->query('kondisi', ''));
        $tahunFilter    = trim($request->query('tahun', ''));

        // Query Utama Buku Induk
        $query = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->leftJoin('jurusan_meta as jm', 'sa.jurusan_id', '=', 'jm.jurusan_id')
            ->select('sa.*', 'sr.nama_ruang', 'sr.gedung', 'jm.singkatan as jurusan_singkatan');

        if ($kategoriFilter !== '') $query->where('sa.kategori', $kategoriFilter);
        if ($sumberFilter !== '')   $query->where('sa.sumber_dana', $sumberFilter);
        if ($kondisiFilter !== '')  $query->where('sa.kondisi', $kondisiFilter);
        if ($tahunFilter !== '')    $query->where('sa.tahun_perolehan', $tahunFilter);

        $perPage = (int) $request->query('perPage', $request->query('per_page', 20));
        $perPage = in_array($perPage, [10, 20, 50, 100], true) ? $perPage : 20;

        $list = $query->orderBy('sa.kode_aset', 'asc')->paginate($perPage)->withQueryString();

        // Rekapitulasi Per Kategori
        $rekapKategori = DB::table('sarpras_aset')
            ->selectRaw('COALESCE(kategori, "Lainnya") as kategori, COUNT(*) as jenis, SUM(jumlah) as unit, SUM(COALESCE(harga_perolehan, 0) * jumlah) as nilai')
            ->groupBy('kategori')
            ->orderByDesc('unit')
            ->get();

        // Rekapitulasi Per Sumber Dana
        $rekapSumberDana = DB::table('sarpras_aset')
            ->selectRaw('COALESCE(sumber_dana, "Lainnya") as sumber_dana, COUNT(*) as jenis, SUM(jumlah) as unit, SUM(COALESCE(harga_perolehan, 0) * jumlah) as nilai')
            ->groupBy('sumber_dana')
            ->orderByDesc('nilai')
            ->get();

        // Rekapitulasi Per Kondisi
        $rekapKondisi = [
            'baik'         => (int) (DB::table('sarpras_aset')->where('kondisi', 'baik')->sum('jumlah') ?: 0),
            'rusak_ringan' => (int) (DB::table('sarpras_aset')->where('kondisi', 'rusak_ringan')->sum('jumlah') ?: 0),
            'rusak_berat'  => (int) (DB::table('sarpras_aset')->where('kondisi', 'rusak_berat')->sum('jumlah') ?: 0),
        ];

        // Stats Global
        $totalAsetUnit = (int) (DB::table('sarpras_aset')->sum('jumlah') ?: 0);
        $totalAsetJenis = DB::table('sarpras_aset')->count();
        $totalAsetNilai = (float) (DB::table('sarpras_aset')->sum(DB::raw('COALESCE(harga_perolehan, 0) * jumlah')) ?: 0);
        $totalRuang = DB::table('sarpras_ruang')->count();

        // Filter Dropdowns
        $kategoriOptions = DB::table('sarpras_aset')->distinct()->pluck('kategori')->filter()->all();
        $sumberOptions = DB::table('sarpras_aset')->distinct()->pluck('sumber_dana')->filter()->all();
        $tahunOptions = DB::table('sarpras_aset')->distinct()->orderByDesc('tahun_perolehan')->pluck('tahun_perolehan')->filter()->all();

        return view('dashboard.sarpras.laporan', compact(
            'list',
            'rekapKategori',
            'rekapSumberDana',
            'rekapKondisi',
            'totalAsetUnit',
            'totalAsetJenis',
            'totalAsetNilai',
            'totalRuang',
            'kategoriFilter',
            'sumberFilter',
            'kondisiFilter',
            'tahunFilter',
            'kategoriOptions',
            'sumberOptions',
            'tahunOptions',
            'canRead',
            'perPage'
        ));
    }

    public function cetak(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $kategoriFilter = trim($request->query('kategori', ''));
        $sumberFilter   = trim($request->query('sumber_dana', ''));
        $kondisiFilter  = trim($request->query('kondisi', ''));
        $tahunFilter    = trim($request->query('tahun', ''));

        $query = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->leftJoin('jurusan_meta as jm', 'sa.jurusan_id', '=', 'jm.jurusan_id')
            ->select('sa.*', 'sr.nama_ruang', 'sr.gedung', 'jm.singkatan as jurusan_singkatan');

        if ($kategoriFilter !== '') $query->where('sa.kategori', $kategoriFilter);
        if ($sumberFilter !== '')   $query->where('sa.sumber_dana', $sumberFilter);
        if ($kondisiFilter !== '')  $query->where('sa.kondisi', $kondisiFilter);
        if ($tahunFilter !== '')    $query->where('sa.tahun_perolehan', $tahunFilter);

        $items = $query->orderBy('sa.kode_aset', 'asc')->get();

        // Data Penandatangan
        $kepalaSekolah = DB::table('gtk')
            ->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%')
            ->first();

        $wakaSarpras = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'WAKA_SARPRAS')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip')
            ->first();

        $stafSarpras = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'STAF_SARPRAS')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip')
            ->first();

        return view('dashboard.sarpras.laporan-cetak', compact(
            'items',
            'kepalaSekolah',
            'wakaSarpras',
            'stafSarpras',
            'kategoriFilter',
            'sumberFilter',
            'kondisiFilter',
            'tahunFilter'
        ));
    }
}
