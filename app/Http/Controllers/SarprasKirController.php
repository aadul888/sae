<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller Kartu Inventaris Ruangan (KIR) Sarpras.
 * Sesuai Indikator Kinerja Sarpras #14: Pembaruan Kartu Inventaris Ruangan (KIR).
 */
class SarprasKirController extends Controller
{
    use ChecksAuthenticatedSession;

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update');

        $search       = trim($request->query('q', ''));
        $gedungFilter = trim($request->query('gedung', ''));

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

        $gedungList = DB::table('sarpras_ruang')
            ->whereNotNull('gedung')
            ->where('gedung', '<>', '')
            ->distinct()
            ->pluck('gedung');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 12));
        $perPage = in_array($perPage, [8, 12, 16, 24, 48], true) ? $perPage : 12;

        $list = $query->orderBy('sr.gedung', 'asc')->orderBy('sr.nama_ruang', 'asc')->paginate($perPage)->withQueryString();

        // Ambil rekapitulasi aset per ruang_id untuk seluruh list yang tampil
        $ruangIds = $list->pluck('id')->filter()->all();
        $asetRekap = [];
        if (!empty($ruangIds) && Table::exists('sarpras_aset')) {
            $raw = DB::table('sarpras_aset')
                ->whereIn('ruang_id', $ruangIds)
                ->selectRaw('ruang_id, COUNT(*) as total_jenis, SUM(jumlah) as total_unit, SUM(jumlah_baik) as total_baik, SUM(jumlah_rusak_ringan) as total_rr, SUM(jumlah_rusak_berat) as total_rb')
                ->groupBy('ruang_id')
                ->get()
                ->keyBy('ruang_id');

            foreach ($raw as $rId => $item) {
                $asetRekap[$rId] = [
                    'jenis' => (int) $item->total_jenis,
                    'unit'  => (int) $item->total_unit,
                    'baik'  => (int) $item->total_baik,
                    'rr'    => (int) $item->total_rr,
                    'rb'    => (int) $item->total_rb,
                ];
            }
        }

        // Stats Global
        $statTotalRuang = DB::table('sarpras_ruang')->count();
        $statRuangTerisiKIR = Table::exists('sarpras_aset')
            ? DB::table('sarpras_aset')->whereNotNull('ruang_id')->distinct('ruang_id')->count('ruang_id')
            : 0;
        $statTotalAsetDitempatkan = Table::exists('sarpras_aset')
            ? (int) (DB::table('sarpras_aset')->whereNotNull('ruang_id')->sum('jumlah') ?: 0)
            : 0;
        $statRuangTanpaPJ = DB::table('sarpras_ruang')
            ->where(function ($q) {
                $q->whereNull('penanggung_jawab_ptk_id')->orWhere('penanggung_jawab_ptk_id', '');
            })->count();

        return view('dashboard.sarpras.kir', compact(
            'list',
            'asetRekap',
            'gedungList',
            'search',
            'gedungFilter',
            'statTotalRuang',
            'statRuangTerisiKIR',
            'statTotalAsetDitempatkan',
            'statRuangTanpaPJ',
            'canRead',
            'canUpdate',
            'perPage'
        ));
    }

    public function show($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $ruang = DB::table('sarpras_ruang as sr')
            ->leftJoin('gtk', 'sr.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
            ->where('sr.id', $id)
            ->select('sr.*', 'gtk.nama as pj_nama', 'gtk.nip as pj_nip', 'gtk.jabatan_ptk_id_str as pj_jabatan')
            ->first();

        if (!$ruang) {
            return response()->json(['status' => 'error', 'message' => 'Ruang tidak ditemukan.'], 404);
        }

        $items = DB::table('sarpras_aset as sa')
            ->leftJoin('jurusan_meta as jm', 'sa.jurusan_id', '=', 'jm.jurusan_id')
            ->where('sa.ruang_id', $id)
            ->select('sa.*', 'jm.singkatan as jurusan_singkatan', 'jm.nama_jurusan')
            ->orderBy('sa.nama_barang', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'ruang'  => $ruang,
            'items'  => $items,
        ]);
    }

    public function cetak($id)
    {
        if ($res = $this->checkAuth()) return $res;

        $ruang = DB::table('sarpras_ruang as sr')
            ->leftJoin('gtk', 'sr.penanggung_jawab_ptk_id', '=', 'gtk.ptk_id')
            ->where('sr.id', $id)
            ->select('sr.*', 'gtk.nama as pj_nama', 'gtk.nip as pj_nip', 'gtk.jabatan_ptk_id_str as pj_jabatan')
            ->first();

        if (!$ruang) {
            abort(404, 'Ruang tidak ditemukan.');
        }

        $items = DB::table('sarpras_aset as sa')
            ->leftJoin('jurusan_meta as jm', 'sa.jurusan_id', '=', 'jm.jurusan_id')
            ->where('sa.ruang_id', $id)
            ->select('sa.*', 'jm.singkatan as jurusan_singkatan')
            ->orderBy('sa.nama_barang', 'asc')
            ->get();

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

        return view('dashboard.sarpras.kir-cetak', compact(
            'ruang',
            'items',
            'kepalaSekolah',
            'wakaSarpras'
        ));
    }
}
