<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;

class SarprasPeminjamanController extends Controller
{
    use ChecksAuthenticatedSession;

    
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        // Modul peminjaman sarpras dan laboran digabung menjadi satu peminjaman alat terpadu (Point 6)
        return redirect()->route('dashboard.laboran.peminjaman-alat.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'aset_id' => 'required|exists:sarpras_aset,id',
            'peminjam_tipe' => 'required|in:gtk,siswa,umum',
            'peminjam_nama' => 'required|string|max:255',
            'keperluan' => 'required|string|max:500',
            'tanggal_pinjam' => 'required|date',
            'tanggal_kembali_rencana' => 'required|date|after_or_equal:tanggal_pinjam',
            'catatan' => 'nullable|string|max:500',
        ], [
            'aset_id.required' => 'Pilih barang/aset yang dipinjam.',
            'peminjam_nama.required' => 'Nama peminjam wajib diisi.',
            'tanggal_kembali_rencana.after_or_equal' => 'Tanggal kembali rencana tidak boleh sebelum tanggal pinjam.',
        ]);

        $aset = DB::table('sarpras_aset')->where('id', $request->aset_id)->first();
        if (!$aset) {
            return response()->json(['status' => 'error', 'message' => 'Barang tidak ditemukan.'], 404);
        }

        $countThisYear = DB::table('sarpras_peminjaman')->whereYear('tanggal_pinjam', date('Y'))->count() + 1;
        $nomorPinjam = sprintf('PINJAM-%s-%04d', date('Ymd'), $countThisYear);

        DB::table('sarpras_peminjaman')->insert([
            'nomor_pinjam' => $nomorPinjam,
            'aset_id' => $request->aset_id,
            'peminjam_tipe' => $request->peminjam_tipe,
            'peminjam_id' => $request->peminjam_id,
            'peminjam_nama' => trim($request->peminjam_nama),
            'keperluan' => trim($request->keperluan),
            'tanggal_pinjam' => $request->tanggal_pinjam,
            'tanggal_kembali_rencana' => $request->tanggal_kembali_rencana,
            'kondisi_sebelum' => $aset->kondisi ?? 'baik',
            'status' => 'dipinjam',
            'petugas_ptk_id' => session('user')['ptk_id'] ?? null,
            'catatan' => $request->catatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update status aset jadi dipinjam
        DB::table('sarpras_aset')->where('id', $request->aset_id)->update(['status_ketersediaan' => 'dipinjam']);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => "Peminjaman {$nomorPinjam} berhasil dicatat."]);
        }

        return redirect()->route('dashboard.sarpras.peminjaman.index')->with('success', "Peminjaman {$nomorPinjam} berhasil dicatat.");
    }

    public function kembalikan(Request $request, $id)
    {
        $request->validate([
            'kondisi_sesudah' => 'required|in:baik,rusak_ringan,rusak_berat',
            'catatan' => 'nullable|string|max:500',
        ]);

        $pinjam = DB::table('sarpras_peminjaman')->where('id', $id)->first();
        if (!$pinjam) {
            return response()->json(['status' => 'error', 'message' => 'Data peminjaman tidak ditemukan.'], 404);
        }

        DB::table('sarpras_peminjaman')->where('id', $id)->update([
            'status' => 'kembali',
            'tanggal_kembali_aktual' => now()->toDateString(),
            'kondisi_sesudah' => $request->kondisi_sesudah,
            'catatan' => $request->catatan ?: $pinjam->catatan,
            'updated_at' => now(),
        ]);

        // Kembalikan ketersediaan aset dan sesuaikan kondisi
        $statusAset = ($request->kondisi_sesudah === 'rusak_berat') ? 'perbaikan' : 'tersedia';
        DB::table('sarpras_aset')->where('id', $pinjam->aset_id)->update([
            'status_ketersediaan' => $statusAset,
            'kondisi' => $request->kondisi_sesudah,
            'updated_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Pengembalian barang berhasil dicatat.']);
        }

        return redirect()->route('dashboard.sarpras.peminjaman.index')->with('success', 'Pengembalian barang berhasil dicatat.');
    }

    public function destroy($id)
    {
        $pinjam = DB::table('sarpras_peminjaman')->where('id', $id)->first();
        if ($pinjam && $pinjam->status === 'dipinjam') {
            DB::table('sarpras_aset')->where('id', $pinjam->aset_id)->update(['status_ketersediaan' => 'tersedia']);
        }

        DB::table('sarpras_peminjaman')->where('id', $id)->delete();

        return response()->json(['status' => 'success', 'message' => 'Catatan peminjaman berhasil dihapus.']);
    }
}
