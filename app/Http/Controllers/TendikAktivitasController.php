<?php

namespace App\Http\Controllers;

use App\Models\TendikAktivitas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class TendikAktivitasController extends Controller
{
    /**
     * Tampilan Utama Log & Input Aktivitas Harian Tendik
     */
    public function index(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $ptkId = is_array($sessionUser) ? ($sessionUser['ptk_id'] ?? null) : ($sessionUser->ptk_id ?? null);
        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : ($sessionUser->role ?? '');
        $userName = is_array($sessionUser) ? ($sessionUser['nama'] ?? ($sessionUser['name'] ?? 'Tenaga Kependidikan')) : ($sessionUser->nama ?? ($sessionUser->name ?? 'Tenaga Kependidikan'));

        // Cek tugas tambahan aktif
        $isKepalaTas = false;
        $activeBidang = 'umum';
        if (Schema::hasTable('ptk_tugas_tambahan') && Schema::hasTable('ref_tugas_tambahan')) {
            $duties = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('ptt.is_active', true)
                ->where('rtt.is_active', true)
                ->where(function ($q) use ($userId, $ptkId) {
                    if ($userId) $q->where('ptt.user_id', $userId);
                    if ($ptkId) $q->orWhere('ptt.ptk_id', $ptkId);
                })
                ->pluck('rtt.kode');

            $isKepalaTas = $duties->contains('KEPALA_TAS') || str_contains(strtolower($role), 'admin');

            $codeMap = [
                'KEPALA_TAS' => 'kepala_tas',
                'STAF_KESISWAAN' => 'kesiswaan',
                'STAF_KEPEGAWAIAN' => 'kepegawaian',
                'STAF_SARPRAS' => 'sarpras',
                'LABORAN' => 'laboran',
                'PUSTAKAWAN' => 'perpustakaan',
                'TEKNISI_IT' => 'teknisi',
                'SATPAM' => 'keamanan',
                'PENJAGA_SEKOLAH' => 'penjaga',
                'STAF_PERSURATAN' => 'persuratan',
            ];

            foreach ($duties as $d) {
                if (isset($codeMap[$d])) {
                    $activeBidang = $codeMap[$d];
                    break;
                }
            }
        }

        // Filter Parameter
        $bulan = $request->input('bulan', date('m'));
        $tahun = $request->input('tahun', date('Y'));
        $filterStatus = $request->input('status');
        $filterBidang = $request->input('bidang');
        $q = trim((string) $request->input('q', ''));

        $query = TendikAktivitas::query();

        // Hak akses data: jika bukan admin/kepala_tas, hanya melihat miliknya sendiri
        if (!$isKepalaTas && !str_contains(strtolower($role), 'admin')) {
            $query->where(function ($sq) use ($userId, $ptkId) {
                if ($userId) $sq->where('user_id', $userId);
                if ($ptkId) $sq->orWhere('ptk_id', $ptkId);
            });
        }

        // Filter Bulan & Tahun
        if ($bulan && $tahun) {
            $query->whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan);
        }

        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        if ($filterBidang) {
            $query->where('bidang', $filterBidang);
        }

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('judul_aktivitas', 'like', "%{$q}%")
                   ->orWhere('uraian_pekerjaan', 'like', "%{$q}%")
                   ->orWhere('nama_pegawai', 'like', "%{$q}%")
                   ->orWhere('output_hasil', 'like', "%{$q}%");
            });
        }

        // Statistik Ringkasan
        $statQuery = clone $query;
        $totalAktivitas = $statQuery->count();
        $totalSelesai = (clone $query)->where('status', 'selesai')->count();
        $totalProses = (clone $query)->where('status', 'proses')->count();
        $totalTertunda = (clone $query)->where('status', 'tertunda')->count();

        $perPage = (int) $request->input('per_page', 15);
        $aktivitasList = $query->orderBy('tanggal', 'desc')->orderBy('jam_mulai', 'desc')->paginate($perPage)->withQueryString();

        $bidangOptions = TendikAktivitas::BIDANG_LABELS;

        return view('dashboard.tendik.aktivitas', compact(
            'aktivitasList',
            'totalAktivitas',
            'totalSelesai',
            'totalProses',
            'totalTertunda',
            'bulan',
            'tahun',
            'filterStatus',
            'filterBidang',
            'q',
            'perPage',
            'isKepalaTas',
            'activeBidang',
            'userName',
            'bidangOptions'
        ));
    }

    /**
     * Simpan Aktivitas Harian Baru
     */
    public function store(Request $request)
    {
        $sessionUser = session('user');
        if (!$sessionUser) {
            return redirect()->route('login');
        }

        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $ptkId = is_array($sessionUser) ? ($sessionUser['ptk_id'] ?? null) : ($sessionUser->ptk_id ?? null);
        $userName = is_array($sessionUser) ? ($sessionUser['nama'] ?? ($sessionUser['name'] ?? 'Tenaga Kependidikan')) : ($sessionUser->nama ?? ($sessionUser->name ?? 'Tenaga Kependidikan'));

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'nullable',
            'judul_aktivitas' => 'required|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'output_hasil' => 'nullable|string|max:255',
            'bidang' => 'required|string',
            'status' => 'required|in:selesai,proses,tertunda',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $lampiranPath = null;
        if ($request->hasFile('lampiran')) {
            $lampiranPath = $request->file('lampiran')->store('tendik/aktivitas', 'public');
        }

        TendikAktivitas::create([
            'user_id' => $userId,
            'ptk_id' => $ptkId,
            'nama_pegawai' => $userName,
            'bidang' => $validated['bidang'],
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'judul_aktivitas' => $validated['judul_aktivitas'],
            'uraian_pekerjaan' => $validated['uraian_pekerjaan'],
            'output_hasil' => $validated['output_hasil'] ?? null,
            'status' => $validated['status'],
            'lampiran_path' => $lampiranPath,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aktivitas pekerjaan harian berhasil disimpan.',
            ]);
        }

        return redirect()->route('dashboard.tendik.aktivitas.index')->with('success', 'Aktivitas pekerjaan harian berhasil dicatat.');
    }

    /**
     * Update Aktivitas Harian
     */
    public function update(Request $request, $id)
    {
        $sessionUser = session('user');
        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : ($sessionUser->role ?? '');

        $aktivitas = TendikAktivitas::findOrFail($id);

        // Otorisasi: hanya pemilik atau admin yang bisa mengubah
        if ($aktivitas->user_id !== $userId && !str_contains(strtolower($role), 'admin')) {
            abort(403, 'Anda tidak memiliki hak untuk mengubah rekaman ini.');
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'nullable',
            'judul_aktivitas' => 'required|string|max:255',
            'uraian_pekerjaan' => 'required|string',
            'output_hasil' => 'nullable|string|max:255',
            'bidang' => 'required|string',
            'status' => 'required|in:selesai,proses,tertunda',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        if ($request->hasFile('lampiran')) {
            if ($aktivitas->lampiran_path && Storage::disk('public')->exists($aktivitas->lampiran_path)) {
                Storage::disk('public')->delete($aktivitas->lampiran_path);
            }
            $aktivitas->lampiran_path = $request->file('lampiran')->store('tendik/aktivitas', 'public');
        }

        $aktivitas->update([
            'tanggal' => $validated['tanggal'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'] ?? null,
            'judul_aktivitas' => $validated['judul_aktivitas'],
            'uraian_pekerjaan' => $validated['uraian_pekerjaan'],
            'output_hasil' => $validated['output_hasil'] ?? null,
            'bidang' => $validated['bidang'],
            'status' => $validated['status'],
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aktivitas pekerjaan berhasil diperbarui.',
            ]);
        }

        return redirect()->route('dashboard.tendik.aktivitas.index')->with('success', 'Aktivitas pekerjaan berhasil diperbarui.');
    }

    /**
     * Hapus Aktivitas Harian
     */
    public function destroy($id)
    {
        $sessionUser = session('user');
        $userId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
        $role = is_array($sessionUser) ? ($sessionUser['role'] ?? '') : ($sessionUser->role ?? '');

        $aktivitas = TendikAktivitas::findOrFail($id);

        if ($aktivitas->user_id !== $userId && !str_contains(strtolower($role), 'admin')) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus rekaman ini.');
        }

        if ($aktivitas->lampiran_path && Storage::disk('public')->exists($aktivitas->lampiran_path)) {
            Storage::disk('public')->delete($aktivitas->lampiran_path);
        }

        $aktivitas->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aktivitas berhasil dihapus.',
            ]);
        }

        return redirect()->route('dashboard.tendik.aktivitas.index')->with('success', 'Aktivitas berhasil dihapus.');
    }
}
