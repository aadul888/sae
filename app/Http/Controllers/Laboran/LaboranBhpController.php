<?php

namespace App\Http\Controllers\Laboran;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Http\Controllers\Concerns\DokumenCetakLaboran;
use App\Models\RolePermission;
use App\Services\LaboranInventarisImportService;
use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bahan Habis Pakai (BHP) — modul terpisah dari Inventaris Alat.
 *
 * Menampilkan khusus baris `laboran_bahan_alat` dengan jenis
 * `bahan_habis_pakai`, dengan fokus stok, pemakaian, dan kedaluwarsa.
 * Style mengikuti halaman Inventaris Alat (banner, stat grid, datatable).
 */
class LaboranBhpController extends Controller
{
    use ChecksAuthenticatedSession;
    use DokumenCetakLaboran;

    public const KONDISI = [
        'baik'         => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat'  => 'Rusak Berat',
        'kedaluwarsa'  => 'Kedaluwarsa',
    ];

    public const SUMBER_DANA = [
        'BOS Reguler'      => 'BOS Reguler',
        'BOS Kinerja'      => 'BOS Kinerja',
        'DAK Fisik'        => 'DAK Fisik',
        'Komite / Yayasan' => 'Komite / Yayasan',
        'Hibah / Bantuan'  => 'Hibah / Bantuan',
    ];

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $context = LaboranScope::resolve($user);

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_laboran', 'delete');

        $search        = trim($request->query('q', ''));
        $labFilter     = trim($request->query('lab', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));
        $sumberFilter  = trim($request->query('sumber_dana', ''));
        $expFilter     = trim($request->query('exp', ''));

        $query = DB::table('laboran_bahan_alat as lba')
            ->leftJoin('jurusan_meta as jm', 'lba.jurusan_id', '=', 'jm.jurusan_id')
            ->where('lba.jenis', 'bahan_habis_pakai')
            ->select('lba.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'lba.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lba.kode_item', 'like', "%{$search}%")
                  ->orWhere('lba.nama_item', 'like', "%{$search}%")
                  ->orWhere('lba.spesifikasi', 'like', "%{$search}%")
                  ->orWhere('lba.lokasi_lemari_rak', 'like', "%{$search}%");
            });
        }

        if ($labFilter !== '') {
            $query->where('lba.ruang_lab_nama', $labFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('lba.kondisi', $kondisiFilter);
        }

        if ($sumberFilter !== '') {
            $query->where('lba.sumber_dana', $sumberFilter);
        }

        if ($expFilter === 'segera') {
            $query->whereNotNull('lba.tgl_kedaluwarsa')
                ->whereBetween('lba.tgl_kedaluwarsa', [now()->toDateString(), now()->addDays(90)->toDateString()]);
        } elseif ($expFilter === 'kadaluarsa') {
            $query->whereNotNull('lba.tgl_kedaluwarsa')
                ->where('lba.tgl_kedaluwarsa', '<', now()->toDateString());
        }

        // Statistik
        $statBase = DB::table('laboran_bahan_alat')->where('jenis', 'bahan_habis_pakai');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal   = (clone $statBase)->count();
        $statStok    = (clone $statBase)->sum('stok_tersedia');
        $statMenipis = (clone $statBase)->where('stok_tersedia', '<=', 2)->count();
        $statExp     = (clone $statBase)->whereNotNull('tgl_kedaluwarsa')
            ->where('tgl_kedaluwarsa', '<', now()->toDateString())->count();

        // Master lokasi dari Sarpras + yang tercatat
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('nama_ruang', 'asc')
            ->pluck('nama_ruang')
            ->toArray();

        $existingQuery = DB::table('laboran_bahan_alat')
            ->where('jenis', 'bahan_habis_pakai')
            ->whereNotNull('ruang_lab_nama');
        LaboranScope::apply($existingQuery, 'jurusan_id', $context);
        $existingLabs = $existingQuery->distinct()->pluck('ruang_lab_nama')->toArray();

        $daftarLab = array_values(array_unique(array_merge($daftarRuangSarpras, $existingLabs)));

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderBy('lba.ruang_lab_nama', 'asc')
            ->orderBy('lba.nama_item', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.bhp', [
            'list'          => $list,
            'search'        => $search,
            'labFilter'     => $labFilter,
            'kondisiFilter' => $kondisiFilter,
            'sumberFilter'  => $sumberFilter,
            'expFilter'     => $expFilter,
            'daftarLab'     => $daftarLab,
            'statTotal'     => $statTotal,
            'statStok'      => $statStok,
            'statMenipis'   => $statMenipis,
            'statExp'       => $statExp,
            'canCreate'     => $canCreate,
            'canRead'       => $canRead,
            'canUpdate'     => $canUpdate,
            'canDelete'     => $canDelete,
            'perPage'       => $perPage,
            'context'       => $context,
            'scopeBadge'    => LaboranScope::badge($context),
            'kondisiList'   => self::KONDISI,
            'sumberDanaList' => self::SUMBER_DANA,
        ]);
    }

    public function store(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();

        $request->validate([
            'ruang_lab_nama'      => 'required|string|max:100',
            'kode_item'           => 'nullable|string|max:50|unique:laboran_bahan_alat,kode_item',
            'nama_item'           => 'required|string|max:255',
            'stok_total'          => 'required|numeric|min:0.01',
            'satuan'              => 'required|string|max:30',
            'kondisi'             => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'jumlah_baik'         => 'nullable|numeric|min:0',
            'jumlah_rusak_ringan' => 'nullable|numeric|min:0',
            'jumlah_rusak_berat'  => 'nullable|numeric|min:0',
            'jumlah_kedaluwarsa'  => 'nullable|numeric|min:0',
            'spesifikasi'         => 'nullable|string|max:1000',
            'lokasi_lemari_rak'   => 'nullable|string|max:100',
            'tgl_kedaluwarsa'     => 'nullable|date',
            'tanggal_pengadaan'   => 'nullable|date',
            'tahun_pengadaan'     => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'sumber_dana'         => 'nullable|string|max:50',
            'jurusan_id'          => 'nullable|string|max:50',
        ]);

        $kode = strtoupper(trim((string) $request->kode_item));
        if ($kode === '') {
            $kode = app(LaboranInventarisImportService::class)->generateKodeItem('bahan_habis_pakai');
        }

        $stokTotal = (float) $request->stok_total;
        $baik = (float) ($request->jumlah_baik ?? 0);
        $rr   = (float) ($request->jumlah_rusak_ringan ?? 0);
        $rb   = (float) ($request->jumlah_rusak_berat ?? 0);
        $kad  = (float) ($request->jumlah_kedaluwarsa ?? 0);

        if (($baik + $rr + $rb + $kad) <= 0) {
            $baik = $request->kondisi === 'baik' ? $stokTotal : 0;
            $rr   = $request->kondisi === 'rusak_ringan' ? $stokTotal : 0;
            $rb   = $request->kondisi === 'rusak_berat' ? $stokTotal : 0;
            $kad  = $request->kondisi === 'kedaluwarsa' ? $stokTotal : 0;
        }

        DB::table('laboran_bahan_alat')->insert([
            'jurusan_id'          => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'      => $request->ruang_lab_nama,
            'kode_item'           => $kode,
            'nama_item'           => trim($request->nama_item),
            'jenis'               => 'bahan_habis_pakai',
            'spesifikasi'         => $request->spesifikasi,
            'stok_total'          => $stokTotal,
            'stok_tersedia'       => $stokTotal,
            'jumlah_baik'         => $baik,
            'jumlah_rusak_ringan' => $rr,
            'jumlah_rusak_berat'  => $rb,
            'jumlah_kedaluwarsa'  => $kad,
            'satuan'              => $request->satuan,
            'kondisi'             => $request->kondisi,
            'tgl_kedaluwarsa'     => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak'   => $request->lokasi_lemari_rak,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan,
            'tahun_pengadaan'     => $request->tahun_pengadaan ?: ($request->tanggal_pengadaan ? (int) date('Y', strtotime($request->tanggal_pengadaan)) : null),
            'sumber_dana'         => $request->input('sumber_dana', 'BOS Reguler') ?: 'BOS Reguler',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $msg = "Bahan habis pakai berhasil ditambahkan. Kode item: {$kode}.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg, 'kode_item' => $kode]);
        }

        return redirect()->route('dashboard.laboran.bhp.index')->with('success', $msg);
    }

    public function update(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_bahan_alat')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan atau di luar cakupan jurusan Anda.'], 404);
        }

        $request->validate([
            'ruang_lab_nama'      => 'required|string|max:100',
            'kode_item'           => 'nullable|string|max:50|unique:laboran_bahan_alat,kode_item,' . $id,
            'nama_item'           => 'required|string|max:255',
            'stok_total'          => 'required|numeric|min:0.01',
            'stok_tersedia'       => 'required|numeric|min:0',
            'satuan'              => 'required|string|max:30',
            'kondisi'             => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'spesifikasi'         => 'nullable|string|max:1000',
            'lokasi_lemari_rak'   => 'nullable|string|max:100',
            'tgl_kedaluwarsa'     => 'nullable|date',
            'tanggal_pengadaan'   => 'nullable|date',
            'tahun_pengadaan'     => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'sumber_dana'         => 'nullable|string|max:50',
            'jurusan_id'          => 'nullable|string|max:50',
        ]);

        $kode = strtoupper(trim((string) $request->kode_item));
        if ($kode === '') {
            $kode = app(LaboranInventarisImportService::class)->generateKodeItem('bahan_habis_pakai');
        }

        DB::table('laboran_bahan_alat')->where('id', $id)->update([
            'jurusan_id'          => LaboranScope::jurusanForWrite($request->jurusan_id, $context),
            'ruang_lab_nama'      => $request->ruang_lab_nama,
            'kode_item'           => $kode,
            'nama_item'           => trim($request->nama_item),
            'spesifikasi'         => $request->spesifikasi,
            'stok_total'          => (float) $request->stok_total,
            'stok_tersedia'       => (float) $request->stok_tersedia,
            'satuan'              => $request->satuan,
            'kondisi'             => $request->kondisi,
            'tgl_kedaluwarsa'     => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak'   => $request->lokasi_lemari_rak,
            'tanggal_pengadaan'   => $request->tanggal_pengadaan,
            'tahun_pengadaan'     => $request->tahun_pengadaan ?: ($request->tanggal_pengadaan ? (int) date('Y', strtotime($request->tanggal_pengadaan)) : null),
            'sumber_dana'         => $request->input('sumber_dana', $row->sumber_dana ?? 'BOS Reguler') ?: 'BOS Reguler',
            'updated_at'          => now(),
        ]);

        return response()->json(['status' => 'success', 'message' => 'Bahan habis pakai berhasil diperbarui.']);
    }

    public function destroy(Request $request, $id)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $row = DB::table('laboran_bahan_alat')->where('id', $id)->first();

        if (!$row || !LaboranScope::allows($row->jurusan_id ?? null, $context)) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan atau di luar cakupan jurusan Anda.'], 404);
        }

        $dipinjam = DB::table('laboran_peminjaman_item as pi')
            ->join('laboran_peminjaman as p', 'pi.peminjaman_id', '=', 'p.id')
            ->where('pi.item_id', $id)
            ->whereIn('p.status', ['diajukan', 'disetujui', 'dipinjam', 'terlambat'])
            ->exists();

        if ($dipinjam) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Bahan tidak dapat dihapus karena masih tercatat dalam peminjaman aktif.',
            ], 422);
        }

        DB::table('laboran_bahan_alat')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Bahan habis pakai berhasil dihapus.']);
    }

    public function destroyBulk(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        if (empty($ids)) {
            return response()->json(['status' => 'error', 'message' => 'Pilih minimal satu bahan untuk dihapus.'], 422);
        }

        $allowed = DB::table('laboran_bahan_alat')
            ->whereIn('id', $ids)
            ->where('jenis', 'bahan_habis_pakai')
            ->get(['id', 'jurusan_id', 'nama_item']);

        $deleted = 0;
        $skipped = [];
        foreach ($allowed as $row) {
            if (!LaboranScope::allows($row->jurusan_id ?? null, $context)) {
                $skipped[] = $row->nama_item;
                continue;
            }

            $dipinjam = DB::table('laboran_peminjaman_item as pi')
                ->join('laboran_peminjaman as p', 'pi.peminjaman_id', '=', 'p.id')
                ->where('pi.item_id', $row->id)
                ->whereIn('p.status', ['diajukan', 'disetujui', 'dipinjam', 'terlambat'])
                ->exists();

            if ($dipinjam) {
                $skipped[] = $row->nama_item;
                continue;
            }

            DB::table('laboran_bahan_alat')->where('id', $row->id)->delete();
            $deleted++;
        }

        $msg = "{$deleted} bahan habis pakai berhasil dihapus.";
        if (!empty($skipped)) {
            $msg .= ' Dilewati (dipinjam / di luar cakupan jurusan): ' . implode(', ', array_slice($skipped, 0, 5))
                . (count($skipped) > 5 ? ' …' : '');
        }

        return response()->json([
            'status'  => 'success',
            'message' => $msg,
            'deleted' => $deleted,
            'skipped' => count($skipped),
        ]);
    }

    public function templateExcel(?LaboranInventarisImportService $service = null)
    {
        $service = $service ?? app(LaboranInventarisImportService::class);
        $path = $service->generateTemplateFileBhp();

        return response()->download($path, 'Format_Import_Bahan_Habis_Pakai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function importExcel(Request $request, LaboranInventarisImportService $service)
    {
        if ($res = $this->checkAuth()) return $res;

        $context = LaboranScope::resolve();

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $hasil = $service->import(
                $request->file('file'),
                $context,
                $request->input('jurusan_id'),
                'bahan_habis_pakai'
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => "Import selesai: {$hasil['imported']} bahan baru ditambahkan, {$hasil['updated']} diperbarui.",
            'count'   => $hasil['imported'] + $hasil['updated'],
        ]);
    }

    /**
     * Cetak Laporan Bahan Habis Pakai (BHP).
     */
    public function cetak(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $context = LaboranScope::resolve($user);

        $search        = trim($request->query('q', ''));
        $labFilter     = trim($request->query('lab', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));
        $expFilter     = trim($request->query('exp', ''));
        $sumberFilter  = trim($request->query('sumber_dana', ''));

        $query = DB::table('laboran_bahan_alat as lba')
            ->leftJoin('jurusan_meta as jm', 'lba.jurusan_id', '=', 'jm.jurusan_id')
            ->where('lba.jenis', 'bahan_habis_pakai')
            ->select('lba.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'lba.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('lba.kode_item', 'like', "%{$search}%")
                  ->orWhere('lba.nama_item', 'like', "%{$search}%")
                  ->orWhere('lba.spesifikasi', 'like', "%{$search}%")
                  ->orWhere('lba.lokasi_lemari_rak', 'like', "%{$search}%");
            });
        }
        if ($labFilter !== '') $query->where('lba.ruang_lab_nama', $labFilter);
        if ($kondisiFilter !== '') $query->where('lba.kondisi', $kondisiFilter);
        if ($sumberFilter !== '') $query->where('lba.sumber_dana', $sumberFilter);
        if ($expFilter === 'segera') {
            $query->whereNotNull('lba.tgl_kedaluwarsa')
                  ->whereBetween('lba.tgl_kedaluwarsa', [now()->toDateString(), now()->addDays(90)->toDateString()]);
        } elseif ($expFilter === 'kadaluarsa') {
            $query->where(function ($q) {
                $q->where('lba.kondisi', 'kedaluwarsa')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('lba.tgl_kedaluwarsa')
                         ->where('lba.tgl_kedaluwarsa', '<', now()->toDateString());
                  });
            });
        }

        $items = $query->orderBy('lba.ruang_lab_nama', 'asc')
            ->orderBy('lba.nama_item', 'asc')
            ->get();

        $sekolah = \App\Models\Sekolah::first();
        $sekolahMeta = \App\Models\SekolahMeta::first();

        // Pejabat penanda tangan terpusat. Query Kepala Lab lama memakai
        // `ptk_tugas_tambahan.jabatan_ptk_id_str` (kolom tidak ada) sehingga
        // halaman cetak BHP gagal dengan SQLSTATE 42S22.
        $pejabat       = $this->dokumenService()->pejabat($context);
        $kepalaSekolah = $pejabat['kepalaSekolah'];
        $kepalaLab     = $pejabat['kepalaLab'];
        $kaprog        = $pejabat['kaprog'];
        $petugas       = $pejabat['petugas'];

        $orientasi = $this->orientasiCetak($request, 'landscape');
        $mode      = $this->modeCetak($request, 'ringkasan');

        return view('dashboard.laboran.bhp-cetak', array_merge([
            'items'          => $items,
            'search'         => $search,
            'labFilter'      => $labFilter,
            'kondisiFilter'  => $kondisiFilter,
            'expFilter'      => $expFilter,
            'sumberFilter'   => $sumberFilter,
            'sekolah'        => $sekolah,
            'sekolahMeta'    => $sekolahMeta,
            'kepalaSekolah'  => $kepalaSekolah,
            'kepalaLab'      => $kepalaLab,
            'kaprog'         => $kaprog,
            'petugas'        => $petugas,
            'orientasi'      => $orientasi,
            'mode'           => $mode,
            'context'        => $context,
            'scopeBadge'     => LaboranScope::badge($context),
        ], $this->dokumenCetak('bhp', $context, [
            'mode'         => $mode,
            'orientasi'    => $orientasi,
            'jumlah_baris' => $items->count(),
            'filter'       => [
                'q'         => $search,
                'lab'       => $labFilter,
                'kondisi'   => $kondisiFilter,
                'exp'       => $expFilter,
                'sumber'    => $sumberFilter,
            ],
        ])));
    }
}
