<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiTransaksi;
use App\Models\PresensiIzin;
use App\Models\RolePermission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PesertaDidikIzinController extends Controller
{
    /**
     * Resolusi Data Siswa dari Sesi Login
     */
    protected function getSiswaFromSession()
    {
        $user = session('user');
        if (!$user) {
            return null;
        }

        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $nisn = is_array($user) ? ($user['nisn'] ?? null) : ($user->nisn ?? null);

        if ($pdId) {
            return DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
                ->where('pd.peserta_didik_id', $pdId)
                ->select(
                    'pd.*',
                    'rb.nama as nama_rombel',
                    'rb.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pdm.foto_path',
                    'wali.nama as wali_nama',
                    'wali.nip as wali_nip'
                )
                ->first();
        } elseif ($nisn) {
            return DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
                ->where('pd.nisn', $nisn)
                ->select(
                    'pd.*',
                    'rb.nama as nama_rombel',
                    'rb.tingkat_pendidikan_id',
                    'rb.jurusan_id_str',
                    'pdm.foto_path',
                    'wali.nama as wali_nama',
                    'wali.nip as wali_nip'
                )
                ->first();
        }

        // Fallback untuk akun Admin/Staf saat meninjau modul portal peserta didik
        return DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
            ->select(
                'pd.*',
                'rb.nama as nama_rombel',
                'rb.tingkat_pendidikan_id',
                'rb.jurusan_id_str',
                'pdm.foto_path',
                'wali.nama as wali_nama',
                'wali.nip as wali_nip'
            )
            ->first();
    }

    /**
     * Halaman Utama: Daftar Surat Izin & Sakit Peserta Didik
     */
    public function index(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Hak Akses CRUD RBAC
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_surat_izin_pd', 'create') || $role === 'peserta_didik';
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_surat_izin_pd', 'read') || $role === 'peserta_didik';
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_surat_izin_pd', 'delete') || $role === 'peserta_didik';

        $siswa = $this->getSiswaFromSession();
        if (!$siswa) {
            abort(403, 'Akses ditolak. Akun Anda tidak terhubung dengan data Peserta Didik aktif.');
        }

        // Base Query milik siswa login
        $baseQuery = PresensiIzin::query()
            ->where(function ($q) use ($siswa) {
                $q->where('peserta_didik_id', $siswa->peserta_didik_id);
                if (!empty($siswa->nisn)) {
                    $q->orWhere('nisn', $siswa->nisn);
                }
            });

        // 4 Stat Cards Ringkasan
        $statTotal = (clone $baseQuery)->count();
        $statMenunggu = (clone $baseQuery)->where('status', 'menunggu')->count();
        $statDisetujui = (clone $baseQuery)->where('status', 'disetujui')->count();
        $statDitolak = (clone $baseQuery)->where('status', 'ditolak')->count();

        // Filter & Pencarian
        $query = clone $baseQuery;

        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('alasan', 'like', "%{$search}%")
                  ->orWhere('catatan_petugas', 'like', "%{$search}%")
                  ->orWhere('disetujui_oleh', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $sortDir = strtolower($request->input('dir', $request->input('sortDir', 'desc')));
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $allowedSorts = ['created_at', 'tanggal_mulai', 'tanggal_selesai', 'jenis', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;

        $list = $query->paginate($perPage)->withQueryString();

        return view('dashboard.peserta-didik-izin', compact(
            'siswa',
            'list',
            'statTotal',
            'statMenunggu',
            'statDisetujui',
            'statDitolak',
            'canCreate',
            'canRead',
            'canDelete',
            'sort',
            'sortDir',
            'perPage'
        ));
    }

    /**
     * Simpan Pengajuan Surat Izin / Sakit / Dispensasi
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jenis' => 'required|string|in:izin,sakit,dispen',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        ], [
            'tanggal_mulai.required' => 'Tanggal mulai surat wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai surat wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'jenis.required' => 'Silakan pilih jenis permohonan.',
            'alasan.required' => 'Keterangan atau alasan permohonan wajib diisi.',
            'lampiran.max' => 'Ukuran berkas lampiran maksimal 4 MB.',
            'lampiran.mimes' => 'Format berkas lampiran harus berupa JPG, PNG, atau PDF.',
        ]);

        $siswa = $this->getSiswaFromSession();
        if (!$siswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data peserta didik tidak terdeteksi dari sesi aktif.',
            ], 403);
        }

        $path = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'izin_' . ($siswa->nisn ?: $siswa->peserta_didik_id) . '_' . date('Ymd_His') . '.' . $ext;
            $path = $file->storeAs('presensi/surat', $fileName, 'public');
        }

        $izin = PresensiIzin::create([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nisn' => $siswa->nisn,
            'rombongan_belajar_id' => $siswa->rombongan_belajar_id,
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'jenis' => $request->input('jenis'),
            'alasan' => $request->input('alasan'),
            'lampiran_path' => $path,
            'status' => 'menunggu',
        ]);

        // Buat notifikasi transaksi untuk murid
        try {
            NotifikasiTransaksi::create([
                'peserta_didik_id' => $siswa->peserta_didik_id,
                'judul' => 'Surat ' . $izin->jenis_label . ' Diajukan',
                'pesan' => 'Permohonan surat ' . strtolower($izin->jenis_label) . ' periode ' . Carbon::parse($izin->tanggal_mulai)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($izin->tanggal_selesai)->translatedFormat('d M Y') . ' berhasil dikirim ke Wali Kelas.',
                'tipe' => 'izin',
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            // Lanjutkan jika notifikasi gagal
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Permohonan surat ' . $izin->jenis_label . ' berhasil dikirim dan menunggu verifikasi Wali Kelas.',
            ]);
        }

        return redirect()->route('dashboard.peserta-didik.izin.index')
            ->with('success', 'Permohonan surat berhasil dikirim.');
    }

    /**
     * Batalkan / Hapus Pengajuan Izin yang Masih Menunggu
     */
    public function destroy(Request $request, $id)
    {
        $siswa = $this->getSiswaFromSession();
        if (!$siswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi peserta didik tidak sah.',
            ], 403);
        }

        $izin = PresensiIzin::where('id', $id)
            ->where(function ($q) use ($siswa) {
                $q->where('peserta_didik_id', $siswa->peserta_didik_id);
                if (!empty($siswa->nisn)) {
                    $q->orWhere('nisn', $siswa->nisn);
                }
            })
            ->first();

        if (!$izin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengajuan surat izin tidak ditemukan.',
            ], 404);
        }

        if ($izin->status !== 'menunggu') {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengajuan yang sudah diverifikasi (' . $izin->status . ') tidak dapat dibatalkan.',
            ], 422);
        }

        // Hapus file lampiran jika ada
        if ($izin->lampiran_path && Storage::disk('public')->exists($izin->lampiran_path)) {
            Storage::disk('public')->delete($izin->lampiran_path);
        }

        $izin->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan surat izin berhasil dibatalkan dan dihapus.',
            ]);
        }

        return redirect()->route('dashboard.peserta-didik.izin.index')
            ->with('success', 'Pengajuan surat izin berhasil dibatalkan.');
    }
}
