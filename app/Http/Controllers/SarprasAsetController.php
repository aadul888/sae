<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SarprasAsetController extends Controller
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

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'create') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'read') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'update') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_sarpras', 'delete') || RolePermission::canAccess($user ?: $role, 'menu_inventaris', 'delete');

        $search = trim($request->query('q', ''));
        $kategoriFilter = trim($request->query('kategori', ''));
        $kondisiFilter = trim($request->query('kondisi', ''));
        $ruangFilter = trim($request->query('ruang_id', ''));

        $query = DB::table('sarpras_aset as sa')
            ->leftJoin('sarpras_ruang as sr', 'sa.ruang_id', '=', 'sr.id')
            ->select('sa.*', 'sr.nama_ruang', 'sr.gedung');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sa.kode_aset', 'like', "%{$search}%")
                  ->orWhere('sa.nama_barang', 'like', "%{$search}%")
                  ->orWhere('sa.merk_tipe', 'like', "%{$search}%")
                  ->orWhere('sr.nama_ruang', 'like', "%{$search}%");
            });
        }

        if ($kategoriFilter !== '') {
            $query->where('sa.kategori', $kategoriFilter);
        }

        if ($kondisiFilter !== '') {
            $query->where('sa.kondisi', $kondisiFilter);
        }

        if ($ruangFilter !== '') {
            $query->where('sa.ruang_id', $ruangFilter);
        }

        // Stats
        $statTotal = DB::table('sarpras_aset')->sum('jumlah') ?: 0;
        $statTersedia = DB::table('sarpras_aset')->where('status_ketersediaan', 'tersedia')->sum('jumlah') ?: 0;
        $statDipinjam = DB::table('sarpras_aset')->where('status_ketersediaan', 'dipinjam')->sum('jumlah') ?: 0;
        $statPerbaikan = DB::table('sarpras_aset')->whereIn('status_ketersediaan', ['perbaikan', 'rusak_berat'])->sum('jumlah') ?: 0;

        $kategoriList = ['Elektronik', 'Mebel/Furnitur', 'Kendaraan', 'Alat Peraga', 'Mesin', 'Lainnya'];
        $ruangList = DB::table('sarpras_ruang')->orderBy('nama_ruang', 'asc')->get(['id', 'nama_ruang', 'gedung']);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;

        $list = $query->orderBy('sa.created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('dashboard.sarpras.aset', compact(
            'list',
            'search',
            'kategoriFilter',
            'kondisiFilter',
            'ruangFilter',
            'kategoriList',
            'ruangList',
            'statTotal',
            'statTersedia',
            'statDipinjam',
            'statPerbaikan',
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
            'kode_aset' => 'required|string|max:50|unique:sarpras_aset,kode_aset',
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'merk_tipe' => 'nullable|string|max:100',
            'tahun_perolehan' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'sumber_dana' => 'required|string|max:50',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'ruang_id' => 'nullable|exists:sarpras_ruang,id',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:30',
            'foto' => 'nullable|image|max:3072',
        ], [
            'kode_aset.unique' => 'Kode aset sudah digunakan.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('sarpras/aset', 'public');
        }

        DB::table('sarpras_aset')->insert([
            'kode_aset' => strtoupper(trim($request->kode_aset)),
            'nama_barang' => trim($request->nama_barang),
            'kategori' => $request->kategori,
            'merk_tipe' => $request->merk_tipe,
            'no_seri_pabrik' => $request->no_seri_pabrik,
            'tahun_perolehan' => $request->tahun_perolehan,
            'sumber_dana' => $request->sumber_dana,
            'harga_perolehan' => $request->harga_perolehan ?? 0,
            'kondisi' => $request->kondisi,
            'ruang_id' => $request->ruang_id,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'status_ketersediaan' => 'tersedia',
            'foto' => $fotoPath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Barang aset berhasil ditambahkan.']);
        }

        return redirect()->route('dashboard.sarpras.aset.index')->with('success', 'Barang aset berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_aset' => 'required|string|max:50|unique:sarpras_aset,kode_aset,' . $id,
            'nama_barang' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'merk_tipe' => 'nullable|string|max:100',
            'tahun_perolehan' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'sumber_dana' => 'required|string|max:50',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'kondisi' => 'required|in:baik,rusak_ringan,rusak_berat',
            'ruang_id' => 'nullable|exists:sarpras_ruang,id',
            'jumlah' => 'required|integer|min:1',
            'satuan' => 'required|string|max:30',
            'status_ketersediaan' => 'required|in:tersedia,dipinjam,perbaikan,dihapuskan',
            'foto' => 'nullable|image|max:3072',
        ]);

        $data = [
            'kode_aset' => strtoupper(trim($request->kode_aset)),
            'nama_barang' => trim($request->nama_barang),
            'kategori' => $request->kategori,
            'merk_tipe' => $request->merk_tipe,
            'no_seri_pabrik' => $request->no_seri_pabrik,
            'tahun_perolehan' => $request->tahun_perolehan,
            'sumber_dana' => $request->sumber_dana,
            'harga_perolehan' => $request->harga_perolehan ?? 0,
            'kondisi' => $request->kondisi,
            'ruang_id' => $request->ruang_id,
            'jumlah' => $request->jumlah,
            'satuan' => $request->satuan,
            'status_ketersediaan' => $request->status_ketersediaan,
            'updated_at' => now(),
        ];

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('sarpras/aset', 'public');
        }

        DB::table('sarpras_aset')->where('id', $id)->update($data);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data aset berhasil diperbarui.']);
        }

        return redirect()->route('dashboard.sarpras.aset.index')->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $aset = DB::table('sarpras_aset')->where('id', $id)->first();
        if ($aset && $aset->foto) {
            Storage::disk('public')->delete($aset->foto);
        }

        DB::table('sarpras_aset')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Data aset berhasil dihapus.']);
    }
}
