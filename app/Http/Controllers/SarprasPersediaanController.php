<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controller Manajemen Persediaan Barang Habis Pakai & ATK Sarpras.
 * Sesuai Indikator Kinerja Sarpras #15: Terkendalinya stok pemakaian barang habis pakai (ATK).
 */
class SarprasPersediaanController extends Controller
{
    use ChecksAuthenticatedSession;

    public const KATEGORI = [
        'ATK Kantor'             => 'Alat Tulis Kantor (ATK)',
        'Kertas & Cetak'         => 'Kertas & Bahan Cetak',
        'Tinta & Toner'          => 'Tinta & Toner Printer',
        'Perlengkapan KBM'       => 'Perlengkapan Kelas & KBM',
        'Alat Kebersihan'        => 'Alat & Bahan Kebersihan',
        'Kelistrikan & Utilitas' => 'Kelistrikan & Lampu/Kabel',
        'Lainnya'                => 'Lain-lain',
    ];

    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'delete');

        $tab = $request->query('tab', 'stok');
        if (!in_array($tab, ['stok', 'mutasi'], true)) {
            $tab = 'stok';
        }

        $search         = trim($request->query('q', ''));
        $kategoriFilter = trim($request->query('kategori', ''));
        $statusStok     = trim($request->query('status_stok', '')); // 'aman', 'menipis', 'habis'
        $jenisMutasi    = trim($request->query('jenis_mutasi', ''));

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        // Stats Global
        $statTotalJenis = DB::table('sarpras_persediaan')->count();
        $statTotalStok  = (int) (DB::table('sarpras_persediaan')->sum('stok_saat_ini') ?: 0);
        $statMenipis    = DB::table('sarpras_persediaan')
            ->whereRaw('stok_saat_ini <= stok_minimum AND stok_saat_ini > 0')
            ->count();
        $statHabis      = DB::table('sarpras_persediaan')
            ->where('stok_saat_ini', '<=', 0)
            ->count();
        $statMutasiBulanIni = DB::table('sarpras_persediaan_mutasi')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->count();

        $list = collect();
        $mutasiList = collect();

        if ($tab === 'stok') {
            $query = DB::table('sarpras_persediaan');

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_barang', 'like', "%{$search}%")
                      ->orWhere('nama_barang', 'like', "%{$search}%")
                      ->orWhere('spesifikasi', 'like', "%{$search}%")
                      ->orWhere('lokasi_simpan', 'like', "%{$search}%");
                });
            }

            if ($kategoriFilter !== '') {
                $query->where('kategori', $kategoriFilter);
            }

            if ($statusStok === 'habis') {
                $query->where('stok_saat_ini', '<=', 0);
            } elseif ($statusStok === 'menipis') {
                $query->whereRaw('stok_saat_ini <= stok_minimum AND stok_saat_ini > 0');
            } elseif ($statusStok === 'aman') {
                $query->whereRaw('stok_saat_ini > stok_minimum');
            }

            $list = $query->orderBy('nama_barang', 'asc')->paginate($perPage)->withQueryString();
        } else {
            $queryMutasi = DB::table('sarpras_persediaan_mutasi as spm')
                ->join('sarpras_persediaan as sp', 'spm.persediaan_id', '=', 'sp.id')
                ->select('spm.*', 'sp.nama_barang', 'sp.kode_barang', 'sp.satuan');

            if ($search !== '') {
                $queryMutasi->where(function ($q) use ($search) {
                    $q->where('sp.nama_barang', 'like', "%{$search}%")
                      ->orWhere('sp.kode_barang', 'like', "%{$search}%")
                      ->orWhere('spm.penerima_atau_sumber', 'like', "%{$search}%")
                      ->orWhere('spm.keperluan', 'like', "%{$search}%");
                });
            }

            if ($jenisMutasi !== '') {
                $queryMutasi->where('spm.jenis_mutasi', $jenisMutasi);
            }

            $mutasiList = $queryMutasi->orderByDesc('spm.tanggal')->orderByDesc('spm.id')->paginate($perPage)->withQueryString();
        }

        // Daftar semua barang untuk dropdown mutasi
        $barangOptions = DB::table('sarpras_persediaan')
            ->select('id', 'kode_barang', 'nama_barang', 'stok_saat_ini', 'satuan')
            ->orderBy('nama_barang')
            ->get();

        return view('dashboard.sarpras.persediaan', compact(
            'tab',
            'list',
            'mutasiList',
            'search',
            'kategoriFilter',
            'statusStok',
            'jenisMutasi',
            'barangOptions',
            'statTotalJenis',
            'statTotalStok',
            'statMenipis',
            'statHabis',
            'statMutasiBulanIni',
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
            'kode_barang'   => 'nullable|string|max:50|unique:sarpras_persediaan,kode_barang',
            'nama_barang'   => 'required|string|max:255',
            'kategori'      => 'required|string|max:100',
            'spesifikasi'   => 'nullable|string|max:255',
            'stok_saat_ini' => 'required|integer|min:0',
            'stok_minimum'  => 'required|integer|min:0',
            'satuan'        => 'required|string|max:30',
            'harga_satuan'  => 'nullable|numeric|min:0',
            'lokasi_simpan' => 'nullable|string|max:100',
            'sumber_dana'   => 'required|string|max:50',
            'keterangan'    => 'nullable|string|max:500',
        ], [
            'nama_barang.required' => 'Nama barang persediaan wajib diisi.',
            'kode_barang.unique'   => 'Kode barang sudah digunakan.',
        ]);

        $kode = strtoupper(trim((string) $request->kode_barang));
        if ($kode === '') {
            $base = 'BHP-' . now()->format('Ym') . '-';
            $last = DB::table('sarpras_persediaan')->where('kode_barang', 'like', $base . '%')->orderByDesc('kode_barang')->value('kode_barang');
            $seq = 1;
            if ($last) {
                $parts = explode('-', $last);
                $seq = ((int) end($parts)) + 1;
            }
            $kode = $base . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        DB::table('sarpras_persediaan')->insert([
            'kode_barang'   => $kode,
            'nama_barang'   => trim($request->nama_barang),
            'kategori'      => $request->kategori,
            'spesifikasi'   => $request->spesifikasi,
            'stok_saat_ini' => (int) $request->stok_saat_ini,
            'stok_minimum'  => (int) $request->stok_minimum,
            'satuan'        => trim($request->satuan),
            'harga_satuan'  => $request->harga_satuan ?? 0,
            'lokasi_simpan' => $request->lokasi_simpan,
            'sumber_dana'   => $request->sumber_dana,
            'keterangan'    => $request->keterangan,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Barang persediaan berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.sarpras.persediaan.index')->with('success', 'Barang persediaan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_barang'   => 'required|string|max:50|unique:sarpras_persediaan,kode_barang,' . $id,
            'nama_barang'   => 'required|string|max:255',
            'kategori'      => 'required|string|max:100',
            'spesifikasi'   => 'nullable|string|max:255',
            'stok_minimum'  => 'required|integer|min:0',
            'satuan'        => 'required|string|max:30',
            'harga_satuan'  => 'nullable|numeric|min:0',
            'lokasi_simpan' => 'nullable|string|max:100',
            'sumber_dana'   => 'required|string|max:50',
            'keterangan'    => 'nullable|string|max:500',
        ]);

        DB::table('sarpras_persediaan')->where('id', $id)->update([
            'kode_barang'   => strtoupper(trim($request->kode_barang)),
            'nama_barang'   => trim($request->nama_barang),
            'kategori'      => $request->kategori,
            'spesifikasi'   => $request->spesifikasi,
            'stok_minimum'  => (int) $request->stok_minimum,
            'satuan'        => trim($request->satuan),
            'harga_satuan'  => $request->harga_satuan ?? 0,
            'lokasi_simpan' => $request->lokasi_simpan,
            'sumber_dana'   => $request->sumber_dana,
            'keterangan'    => $request->keterangan,
            'updated_at'    => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data persediaan berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.sarpras.persediaan.index')->with('success', 'Data persediaan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('sarpras_persediaan')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Barang persediaan berhasil dihapus.']);
    }

    public function mutasi(Request $request, $id)
    {
        $request->validate([
            'tanggal'             => 'required|date',
            'jenis_mutasi'        => 'required|in:masuk,keluar',
            'jumlah'              => 'required|integer|min:1',
            'penerima_atau_sumber'=> 'required|string|max:255',
            'keperluan'           => 'nullable|string|max:255',
            'catatan'             => 'nullable|string|max:500',
        ], [
            'penerima_atau_sumber.required' => 'Penerima / Sumber barang wajib diisi.',
            'jumlah.min' => 'Jumlah mutasi minimal 1 unit.',
        ]);

        $barang = DB::table('sarpras_persediaan')->where('id', $id)->first();
        if (!$barang) {
            return response()->json(['status' => 'error', 'message' => 'Barang persediaan tidak ditemukan.'], 404);
        }

        $jumlah = (int) $request->jumlah;
        $jenis = $request->jenis_mutasi;

        if ($jenis === 'keluar' && $barang->stok_saat_ini < $jumlah) {
            return response()->json([
                'status'  => 'error',
                'message' => "Stok tidak mencukupi. Sisa stok saat ini: {$barang->stok_saat_ini} {$barang->satuan}.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('sarpras_persediaan_mutasi')->insert([
                'persediaan_id'       => $id,
                'tanggal'             => $request->tanggal,
                'jenis_mutasi'        => $jenis,
                'jumlah'              => $jumlah,
                'penerima_atau_sumber'=> trim($request->penerima_atau_sumber),
                'keperluan'           => $request->keperluan,
                'petugas_ptk_id'      => session('user.ptk_id') ?? null,
                'catatan'             => $request->catatan,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $stokBaru = $jenis === 'masuk'
                ? $barang->stok_saat_ini + $jumlah
                : $barang->stok_saat_ini - $jumlah;

            DB::table('sarpras_persediaan')->where('id', $id)->update([
                'stok_saat_ini' => $stokBaru,
                'updated_at'    => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal mencatat mutasi: ' . $e->getMessage()], 500);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => "Mutasi {$jenis} sebanyak {$jumlah} {$barang->satuan} berhasil dicatat.",
                'stok'    => $stokBaru,
            ]);
        }

        return redirect()->route('dashboard.sarpras.persediaan.index', ['tab' => 'mutasi'])
            ->with('success', "Mutasi {$jenis} berhasil dicatat.");
    }
}
