<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\PerpustakaanEbookService;
use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controller Pengelolaan & Penarikan E-Book Kemendikbud & Kejuruan SMK.
 */
class PerpusEbookController extends Controller
{
    private function guard(string $action = 'read')
    {
        $user = session('user');
        $role = $user['role'] ?? 'tendik';

        $can = RolePermission::canAccess($user, 'menu_perpustakaan', $action)
            || RolePermission::canAccess($user, 'menu_perpustakaan_koleksi', $action);

        if (!$can && !in_array($role, ['admin', 'tendik', 'guru', 'pustakawan'], true)) {
            return redirect()->route('dashboard.' . $role)
                ->with('error', 'Anda tidak memiliki hak akses ke modul koleksi perpustakaan.');
        }

        return null;
    }

    public function index(Request $request)
    {
        if ($r = $this->guard('read')) return $r;

        $q = trim((string) $request->query('q', ''));
        $kelas = trim((string) $request->query('kelas', ''));
        $kategori = trim((string) $request->query('kategori', ''));
        $sumber = trim((string) $request->query('sumber', ''));

        $items = PerpustakaanEbookService::cari([
            'q'        => $q,
            'kelas'    => $kelas,
            'kategori' => $kategori,
            'sumber'   => $sumber,
        ]);

        $daftarKategori = PerpustakaanEbookService::daftarKategori();
        $daftarSumber = PerpustakaanEbookService::daftarSumber();

        // Statistik Koleksi E-Book di Perpustakaan
        $totalEbookKatalog = Table::exists('perpus_koleksi_buku')
            ? DB::table('perpus_koleksi_buku')->where('format', 'ebook')->count()
            : 0;

        $totalDibaca = Table::exists('perpus_koleksi_buku') && Table::hasColumn('perpus_koleksi_buku', 'total_dibaca')
            ? (int) DB::table('perpus_koleksi_buku')->where('format', 'ebook')->sum('total_dibaca')
            : 0;

        return view('dashboard.perpus.ebook-tarik', compact(
            'items', 'q', 'kelas', 'kategori', 'sumber', 'daftarKategori', 'daftarSumber',
            'totalEbookKatalog', 'totalDibaca'
        ));
    }

    public function tarik(Request $request)
    {
        if ($r = $this->guard('create')) return $r;

        $kodeBuku = trim((string) $request->input('kode_buku', ''));
        if ($kodeBuku === '') {
            return redirect()->back()->with('error', 'Kode buku tidak valid.');
        }

        $katalog = collect(PerpustakaanEbookService::katalogMaster())->keyBy('kode_buku');
        $item = $katalog->get($kodeBuku);

        if (!$item) {
            return redirect()->back()->with('error', 'E-Book tidak ditemukan dalam master repositori terbuka.');
        }

        $id = PerpustakaanEbookService::imporKeKatalog($item);

        return redirect()->back()->with('success', "E-Book \"{$item['judul']}\" berhasil ditarik ke katalog perpustakaan.");
    }

    public function tarikBanyak(Request $request)
    {
        if ($r = $this->guard('create')) return $r;

        $kodeList = $request->input('kode_buku', []);
        if (!is_array($kodeList) || empty($kodeList)) {
            return redirect()->back()->with('error', 'Pilih minimal satu e-book untuk ditarik ke katalog.');
        }

        $hasil = PerpustakaanEbookService::imporBanyak($kodeList);

        return redirect()->back()->with('success', "Berhasil menarik {$hasil['sukses']} e-book ke katalog perpustakaan.");
    }

    public function storeManual(Request $request)
    {
        if ($r = $this->guard('create')) return $r;

        $request->validate([
            'judul'       => 'required|string|max:255',
            'penulis'     => 'nullable|string|max:255',
            'kategori'    => 'required|string|max:50',
            'tautan_baca' => 'nullable|url|max:500',
            'file_pdf'    => 'nullable|file|mimes:pdf|max:20480', // maks 20MB
        ], [
            'judul.required'    => 'Judul e-book wajib diisi.',
            'tautan_baca.url'   => 'Tautan baca harus berupa format URL valid (https://...).',
            'file_pdf.mimes'    => 'Berkas e-book harus berformat PDF.',
            'file_pdf.max'      => 'Ukuran berkas PDF maksimal 20 MB.',
        ]);

        if (!$request->filled('tautan_baca') && !$request->hasFile('file_pdf')) {
            return redirect()->back()->withInput()->with('error', 'Harap isi tautan baca online ATAU unggah berkas PDF e-book.');
        }

        $filePath = null;
        if ($request->hasFile('file_pdf')) {
            $file = $request->file('file_pdf');
            $namaFile = 'ebook_' . time() . '_' . Str::slug(Str::limit($request->judul, 30)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('ebooks', $namaFile, 'public');
        }

        $kode = 'EB-MND-' . strtoupper(Str::random(6));

        DB::table('perpus_koleksi_buku')->insert([
            'kode_buku'          => $kode,
            'isbn'               => $request->isbn ?: null,
            'judul'              => $request->judul,
            'penulis'            => $request->penulis ?: 'Guru/Sekolah',
            'penerbit'           => $request->penerbit ?: config('app.name', 'SMK SAE'),
            'tahun_terbit'       => $request->tahun_terbit ?: date('Y'),
            'klasifikasi_ddc'    => $request->klasifikasi_ddc ?: null,
            'kategori'           => $request->kategori,
            'format'             => 'ebook',
            'jumlah_eksemplar'   => 999,
            'eksemplar_tersedia' => 999,
            'lokasi_rak'         => 'Rak Digital',
            'sumber_dana'        => 'Sekolah Mandiri',
            'harga_perolehan'    => 0,
            'tgl_pembelian'      => now()->toDateString(),
            'tautan_baca'        => $request->tautan_baca ?: null,
            'file_ebook'         => $filePath,
            'sumber_ebook'       => 'mandiri',
            'sinopsis'           => $request->sinopsis ?: null,
            'jenjang'            => $request->jenjang ?: 'SMK',
            'kelas'              => $request->kelas ?: 'Semua Kelas',
            'jurusan'            => $request->jurusan ?: 'Umum',
            'total_dibaca'       => 0,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return redirect()->back()->with('success', "E-Book \"{$request->judul}\" berhasil ditambahkan ke koleksi digital sekolah.");
    }
}
