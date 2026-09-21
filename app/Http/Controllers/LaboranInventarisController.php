<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaboranInventarisController extends Controller
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
        $jenisFilter = trim($request->query('jenis', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));

        $query = DB::table('laboran_bahan_alat');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_item', 'like', "%{$search}%")
                  ->orWhere('nama_item', 'like', "%{$search}%")
                  ->orWhere('spesifikasi', 'like', "%{$search}%")
                  ->orWhere('lokasi_lemari_rak', 'like', "%{$search}%");
            });
        }

        if ($labFilter !== '') {
            $query->where('ruang_lab_nama', $labFilter);
        }

        if ($jenisFilter !== '') {
            $query->where('jenis', $jenisFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('kondisi', $kondisiFilter);
        }

        // Stats
        $statTotal = DB::table('laboran_bahan_alat')->count();
        $statAlat = DB::table('laboran_bahan_alat')->where('jenis', 'alat')->count();
        $statBahan = DB::table('laboran_bahan_alat')->where('jenis', 'bahan_habis_pakai')->count();
        $statKritis = DB::table('laboran_bahan_alat')
            ->where(function ($q) {
                $q->where('stok_tersedia', '<=', 2)
                  ->orWhere('kondisi', '!=', 'baik');
            })->count();

        // Ambil daftar ruang & bengkel praktik dari Master Sarpras & Aset
        $daftarRuangSarpras = DB::table('sarpras_ruang')
            ->orderBy('gedung', 'asc')
            ->orderBy('nama_ruang', 'asc')
            ->get(['id', 'kode_ruang', 'nama_ruang', 'gedung', 'lantai', 'kondisi']);

        // Ambil nama-nama ruang unik untuk dropdown / filter
        $daftarLab = $daftarRuangSarpras->pluck('nama_ruang')->toArray();

        // Gabungkan dengan ruang yang mungkin sudah tercatat sebelumnya di laboran_bahan_alat
        $existingLabs = DB::table('laboran_bahan_alat')->distinct()->pluck('ruang_lab_nama')->toArray();
        if (!empty($existingLabs)) {
            $daftarLab = array_values(array_unique(array_merge($daftarLab, $existingLabs)));
        }

        // Ambil daftar aset dari Master Sarpras untuk opsi referensi/salin
        $daftarAsetSarpras = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->select('sa.id', 'sa.kode_aset', 'sa.nama_barang', 'sa.kategori', 'sa.merk_tipe', 'sa.kondisi', 'sa.jumlah', 'sa.satuan', 'sr.nama_ruang as ruang_nama')
            ->orderBy('sa.nama_barang', 'asc')
            ->get();

        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;

        $list = $query->orderBy('ruang_lab_nama', 'asc')->orderBy('nama_item', 'asc')->paginate($perPage)->withQueryString();

        return view('dashboard.laboran.inventaris', compact(
            'list',
            'search',
            'labFilter',
            'jenisFilter',
            'kondisiFilter',
            'daftarLab',
            'daftarRuangSarpras',
            'daftarAsetSarpras',
            'statTotal',
            'statAlat',
            'statBahan',
            'statKritis',
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
            'kode_item' => 'required|string|max:50|unique:laboran_bahan_alat,kode_item',
            'nama_item' => 'required|string|max:255',
            'jenis' => 'required|in:alat,bahan_habis_pakai',
            'stok_total' => 'required|numeric|min:0.01',
            'satuan' => 'required|string|max:30',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'spesifikasi' => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa' => 'nullable|date',
        ]);

        DB::table('laboran_bahan_alat')->insert([
            'ruang_lab_nama' => $request->ruang_lab_nama,
            'kode_item' => strtoupper(trim($request->kode_item)),
            'nama_item' => trim($request->nama_item),
            'jenis' => $request->jenis,
            'spesifikasi' => $request->spesifikasi,
            'stok_total' => $request->stok_total,
            'stok_tersedia' => $request->stok_total,
            'satuan' => $request->satuan,
            'kondisi' => $request->kondisi,
            'tgl_kedaluwarsa' => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak' => $request->lokasi_lemari_rak,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Alat/bahan lab berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.laboran.inventaris.index')->with('success', 'Alat/bahan lab berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'ruang_lab_nama' => 'required|string|max:100',
            'kode_item' => 'required|string|max:50|unique:laboran_bahan_alat,kode_item,' . $id,
            'nama_item' => 'required|string|max:255',
            'jenis' => 'required|in:alat,bahan_habis_pakai',
            'stok_total' => 'required|numeric|min:0.01',
            'stok_tersedia' => 'required|numeric|min:0',
            'satuan' => 'required|string|max:30',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat,kedaluwarsa',
            'spesifikasi' => 'nullable|string|max:1000',
            'lokasi_lemari_rak' => 'nullable|string|max:100',
            'tgl_kedaluwarsa' => 'nullable|date',
        ]);

        DB::table('laboran_bahan_alat')->where('id', $id)->update([
            'ruang_lab_nama' => $request->ruang_lab_nama,
            'kode_item' => strtoupper(trim($request->kode_item)),
            'nama_item' => trim($request->nama_item),
            'jenis' => $request->jenis,
            'spesifikasi' => $request->spesifikasi,
            'stok_total' => $request->stok_total,
            'stok_tersedia' => $request->stok_tersedia,
            'satuan' => $request->satuan,
            'kondisi' => $request->kondisi,
            'tgl_kedaluwarsa' => $request->tgl_kedaluwarsa,
            'lokasi_lemari_rak' => $request->lokasi_lemari_rak,
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data alat/bahan lab berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.laboran.inventaris.index')->with('success', 'Data alat/bahan lab berhasil diperbarui.');
    }

    public function destroy($id)
    {
        DB::table('laboran_bahan_alat')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Data alat/bahan berhasil dihapus.']);
    }
}
