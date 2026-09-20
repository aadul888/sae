<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PerpusKoleksiController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $canCreate = RolePermission::canAccess($user, 'menu_perpustakaan', 'create');
        $canRead = RolePermission::canAccess($user, 'menu_perpustakaan', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_perpustakaan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_perpustakaan', 'delete');

        if (!$canRead && !in_array($role, ['admin', 'tendik', 'guru', 'pustakawan'])) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke modul Perpustakaan.');
        }

        $search = $request->get('search', '');
        $kategori = $request->get('kategori', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('perpus_koleksi_buku');

        if ($kategori) {
            $query->where('kategori', $kategori);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('kode_buku', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%")
                    ->orWhere('penerbit', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('lokasi_rak', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        // Kategori List
        $kategoriList = DB::table('perpus_koleksi_buku')
            ->select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->pluck('kategori');

        // Statistik Koleksi
        $stats = [
            'total_judul' => DB::table('perpus_koleksi_buku')->count(),
            'total_eksemplar' => DB::table('perpus_koleksi_buku')->sum('jumlah_eksemplar') ?: 0,
            'tersedia' => DB::table('perpus_koleksi_buku')->sum('eksemplar_tersedia') ?: 0,
            'dipinjam' => DB::table('perpus_sirkulasi')->where('status', 'dipinjam')->count(),
        ];

        return view('dashboard.perpus.koleksi', compact(
            'list', 'search', 'kategori', 'kategoriList', 'perPage', 'sort', 'sortDir',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_buku' => 'required|string|max:50|unique:perpus_koleksi_buku,kode_buku',
            'judul' => 'required|string|max:255',
            'jumlah_eksemplar' => 'required|integer|min:1',
        ]);

        $eksemplar = (int) $request->jumlah_eksemplar;

        DB::table('perpus_koleksi_buku')->insert([
            'kode_buku' => $request->kode_buku,
            'isbn' => $request->isbn,
            'judul' => $request->judul,
            'penulis' => $request->penulis,
            'penerbit' => $request->penerbit,
            'tahun_terbit' => $request->tahun_terbit ?: null,
            'klasifikasi_ddc' => $request->klasifikasi_ddc,
            'kategori' => $request->kategori ?: 'Umum',
            'jumlah_eksemplar' => $eksemplar,
            'eksemplar_tersedia' => $eksemplar,
            'lokasi_rak' => $request->lokasi_rak,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.perpustakaan.koleksi.index')->with('success', 'Buku berhasil ditambahkan ke katalog.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'kode_buku' => 'required|string|max:50|unique:perpus_koleksi_buku,kode_buku,' . $id,
            'judul' => 'required|string|max:255',
            'jumlah_eksemplar' => 'required|integer|min:1',
        ]);

        $buku = DB::table('perpus_koleksi_buku')->where('id', $id)->first();
        if (!$buku) {
            return redirect()->back()->with('error', 'Data buku tidak ditemukan.');
        }

        $eksemplarBaru = (int) $request->jumlah_eksemplar;
        $selisih = $eksemplarBaru - (int) $buku->jumlah_eksemplar;
        $tersediaBaru = max(0, (int) $buku->eksemplar_tersedia + $selisih);

        DB::table('perpus_koleksi_buku')->where('id', $id)->update([
            'kode_buku' => $request->kode_buku,
            'isbn' => $request->isbn,
            'judul' => $request->judul,
            'penulis' => $request->penulis,
            'penerbit' => $request->penerbit,
            'tahun_terbit' => $request->tahun_terbit ?: null,
            'klasifikasi_ddc' => $request->klasifikasi_ddc,
            'kategori' => $request->kategori ?: 'Umum',
            'jumlah_eksemplar' => $eksemplarBaru,
            'eksemplar_tersedia' => $tersediaBaru,
            'lokasi_rak' => $request->lokasi_rak,
            'updated_at' => now(),
        ]);

        return redirect()->route('dashboard.perpustakaan.koleksi.index')->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $cekDipinjam = DB::table('perpus_sirkulasi')->where('buku_id', $id)->where('status', 'dipinjam')->count();
        if ($cekDipinjam > 0) {
            return redirect()->back()->with('error', 'Buku tidak dapat dihapus karena masih ada eksemplar yang sedang dipinjam.');
        }

        DB::table('perpus_koleksi_buku')->where('id', $id)->delete();
        return redirect()->route('dashboard.perpustakaan.koleksi.index')->with('success', 'Buku berhasil dihapus dari katalog.');
    }
}
