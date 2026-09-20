<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;

class PerpusSirkulasiController extends Controller
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
            return redirect()->route('dashboard.' . $role)->with('error', 'Anda tidak memiliki hak akses ke Sirkulasi Perpustakaan.');
        }

        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $tanggal = $request->get('tanggal', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        $query = DB::table('perpus_sirkulasi as s')
            ->leftJoin('perpus_koleksi_buku as b', 's.buku_id', '=', 'b.id')
            ->select(
                's.*',
                'b.judul as judul_buku',
                'b.kode_buku',
                'b.lokasi_rak'
            );

        if ($status) {
            $query->where('s.status', $status);
        }

        if ($tanggal) {
            $query->where('s.tgl_pinjam', $tanggal);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('s.kode_transaksi', 'like', "%{$search}%")
                    ->orWhere('s.peminjam_nama', 'like', "%{$search}%")
                    ->orWhere('b.judul', 'like', "%{$search}%")
                    ->orWhere('b.kode_buku', 'like', "%{$search}%");
            });
        }

        $list = $query->orderBy('s.' . $sort, $sortDir)->paginate($perPage)->withQueryString();

        // Daftar Buku Tersedia untuk Modal Pinjam
        $bukuTersedia = DB::table('perpus_koleksi_buku')
            ->where('eksemplar_tersedia', '>', 0)
            ->orderBy('judul')
            ->get();

        // Siswa & GTK untuk peminjam
        $siswaList = DB::table('peserta_didik')->select('peserta_didik_id', 'nama', 'nisn')->orderBy('nama')->limit(200)->get();
        $gtkList = DB::table('gtk')->select('ptk_id', 'nama', 'nip')->orderBy('nama')->get();

        // Statistik Sirkulasi
        $stats = [
            'total_pinjam' => DB::table('perpus_sirkulasi')->count(),
            'sedang_dipinjam' => DB::table('perpus_sirkulasi')->where('status', 'dipinjam')->count(),
            'terlambat' => DB::table('perpus_sirkulasi')->where('status', 'dipinjam')->where('tgl_jatuh_tempo', '<', date('Y-m-d'))->count(),
            'sudah_kembali' => DB::table('perpus_sirkulasi')->where('status', 'kembali')->count(),
        ];

        return view('dashboard.perpus.sirkulasi', compact(
            'list', 'search', 'status', 'tanggal', 'perPage', 'sort', 'sortDir',
            'bukuTersedia', 'siswaList', 'gtkList',
            'canCreate', 'canRead', 'canUpdate', 'canDelete', 'stats'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'buku_id' => 'required|exists:perpus_koleksi_buku,id',
            'peminjam_tipe' => 'required|string',
            'peminjam_id' => 'required|string',
            'peminjam_nama' => 'required|string',
            'tgl_pinjam' => 'required|date',
            'tgl_jatuh_tempo' => 'required|date|after_or_equal:tgl_pinjam',
        ]);

        $buku = DB::table('perpus_koleksi_buku')->where('id', $request->buku_id)->first();
        if ($buku->eksemplar_tersedia <= 0) {
            return redirect()->back()->with('error', 'Stok eksemplar buku "' . $buku->judul . '" sedang kosong/habis dipinjam.');
        }

        $user = session('user');
        $petugasPtkId = $user['ptk_id'] ?? null;

        $kodeTransaksi = 'PJM-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::transaction(function () use ($request, $buku, $kodeTransaksi, $petugasPtkId) {
            DB::table('perpus_sirkulasi')->insert([
                'kode_transaksi' => $kodeTransaksi,
                'buku_id' => $request->buku_id,
                'peminjam_tipe' => $request->peminjam_tipe,
                'peminjam_id' => $request->peminjam_id,
                'peminjam_nama' => $request->peminjam_nama,
                'tgl_pinjam' => $request->tgl_pinjam,
                'tgl_jatuh_tempo' => $request->tgl_jatuh_tempo,
                'status' => 'dipinjam',
                'denda' => 0,
                'status_denda' => 'lunas',
                'petugas_ptk_id' => $petugasPtkId,
                'catatan' => $request->catatan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Kurangi stok tersedia
            DB::table('perpus_koleksi_buku')->where('id', $buku->id)->decrement('eksemplar_tersedia', 1);
        });

        return redirect()->route('dashboard.perpustakaan.sirkulasi.index')->with('success', 'Peminjaman buku berhasil dicatat (' . $kodeTransaksi . ').');
    }

    public function kembalikan(Request $request, $id)
    {
        $sirkulasi = DB::table('perpus_sirkulasi')->where('id', $id)->first();
        if (!$sirkulasi || $sirkulasi->status !== 'dipinjam') {
            return redirect()->back()->with('error', 'Transaksi sirkulasi tidak valid atau buku sudah dikembalikan.');
        }

        $tglKembali = $request->tgl_kembali ?: date('Y-m-d');
        $jatuhTempo = $sirkulasi->tgl_jatuh_tempo;

        // Hitung denda Rp 1.000 / hari keterlambatan
        $denda = 0;
        if ($tglKembali > $jatuhTempo) {
            $diffDays = (strtotime($tglKembali) - strtotime($jatuhTempo)) / 86400;
            $denda = max(0, $diffDays * 1000);
        }

        DB::transaction(function () use ($sirkulasi, $tglKembali, $denda, $request) {
            DB::table('perpus_sirkulasi')->where('id', $sirkulasi->id)->update([
                'tgl_kembali' => $tglKembali,
                'status' => 'kembali',
                'denda' => $denda,
                'status_denda' => $denda > 0 ? ($request->status_denda ?? 'lunas') : 'lunas',
                'catatan' => $request->catatan ?: $sirkulasi->catatan,
                'updated_at' => now(),
            ]);

            // Tambah kembali stok tersedia buku
            DB::table('perpus_koleksi_buku')->where('id', $sirkulasi->buku_id)->increment('eksemplar_tersedia', 1);
        });

        $msg = 'Buku berhasil dikembalikan.';
        if ($denda > 0) {
            $msg .= ' Denda keterlambatan: Rp ' . number_format($denda, 0, ',', '.') . '.';
        }

        return redirect()->route('dashboard.perpustakaan.sirkulasi.index')->with('success', $msg);
    }

    public function destroy($id)
    {
        $sirkulasi = DB::table('perpus_sirkulasi')->where('id', $id)->first();
        if (!$sirkulasi) {
            return redirect()->back()->with('error', 'Data sirkulasi tidak ditemukan.');
        }

        DB::transaction(function () use ($sirkulasi) {
            if ($sirkulasi->status === 'dipinjam') {
                DB::table('perpus_koleksi_buku')->where('id', $sirkulasi->buku_id)->increment('eksemplar_tersedia', 1);
            }
            DB::table('perpus_sirkulasi')->where('id', $sirkulasi->id)->delete();
        });

        return redirect()->route('dashboard.perpustakaan.sirkulasi.index')->with('success', 'Data transaksi sirkulasi berhasil dihapus.');
    }
}
