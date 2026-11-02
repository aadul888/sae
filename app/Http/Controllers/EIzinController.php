<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\NotifikasiTransaksi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikIzinKeluar;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EIzinController extends Controller
{
    use ChecksAuthenticatedSession;

    /**
     * Halaman Utama e-Izin Siswa Terpadu
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $canRead = RolePermission::canAccess($user ?: $role, 'menu_e_izin', 'read')
            || in_array($role, ['admin', 'guru', 'tendik', 'peserta_didik']);

        if (!$canRead) {
            return redirect()->route('dashboard.' . ($role ?: 'login'))
                ->with('error', 'Anda tidak memiliki hak akses ke modul e-Izin.');
        }

        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_e_izin', 'create')
            || in_array($role, ['admin', 'peserta_didik']);
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_e_izin', 'update')
            || in_array($role, ['admin', 'guru', 'tendik']);
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_e_izin', 'delete')
            || $role === 'admin';

        $isSuperAdmin = ($role === 'admin');
        $isStudent = ($role === 'peserta_didik');
        $waliRombel = RolePermission::getWaliKelasRombelInfo($user);
        $isWaliKelas = $isSuperAdmin || ($waliRombel !== null);
        $isGuruPiket = $isSuperAdmin
            || RolePermission::canAccess($user, 'menu_piket', 'read')
            || RolePermission::canAccess($user, 'menu_piket_agenda', 'read');
        $isSecurity = $isSuperAdmin
            || RolePermission::canAccess($user, 'menu_keamanan', 'read')
            || in_array($role, ['satpam', 'keamanan']);

        // Siswa aktif dari sesi
        $siswaLogin = null;
        if ($isStudent) {
            $siswaLogin = $this->getSiswaFromSession();
        }

        // Tentukan Tab Aktif default
        $tab = $request->get('tab');
        if (!$tab) {
            if ($isStudent) {
                $tab = 'saya';
            } elseif ($isWaliKelas && !$isGuruPiket && !$isSecurity && !$isSuperAdmin) {
                $tab = 'wali';
            } elseif ($isGuruPiket && !$isSecurity && !$isSuperAdmin) {
                $tab = 'piket';
            } elseif ($isSecurity && !$isSuperAdmin) {
                $tab = 'security';
            } else {
                $tab = 'semua';
            }
        }

        // Filter parameter
        $search = trim($request->get('q', $request->get('search', '')));
        $status = $request->get('status', '');
        $jenis = $request->get('jenis_izin', '');
        $tanggal = $request->get('tanggal', ($tab === 'saya' ? '' : date('Y-m-d')));
        $rombelId = $request->get('rombel_id', '');
        $perPage = (int) $request->get('perPage', 15);
        $sort = $request->get('sort', 'id');
        $sortDir = $request->get('sortDir', 'desc');

        // Query utama
        $query = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'rb.nama as nama_rombel',
                'wali.nama as nama_wali',
                'piket.nama as nama_piket'
            );

        // Filter scoping berdasarkan tab
        if ($tab === 'saya') {
            if ($siswaLogin) {
                $query->where('iz.peserta_didik_id', $siswaLogin->peserta_didik_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($tab === 'wali') {
            if (!$isSuperAdmin && $waliRombel) {
                $query->where('iz.rombel_id', $waliRombel->rombongan_belajar_id);
            }
            if ($status === '') {
                $query->where('iz.status', 'menunggu_wali');
            }
        } elseif ($tab === 'piket') {
            if ($status === '') {
                $query->where('iz.status', 'menunggu_piket');
            }
        } elseif ($tab === 'security') {
            if ($status === '') {
                $query->whereIn('iz.status', ['menunggu_satpam', 'di_luar']);
            }
        }

        // Terapkan filter eksplisit jika ada
        if (!empty($tanggal)) {
            $query->where('iz.tanggal', $tanggal);
        }
        if (!empty($status)) {
            $query->where('iz.status', $status);
        }
        if (!empty($jenis)) {
            $query->where('iz.jenis_izin', $jenis);
        }
        if (!empty($rombelId)) {
            $query->where('iz.rombel_id', $rombelId);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('iz.nomor_tiket', 'like', "%{$search}%")
                    ->orWhere('pd.nama', 'like', "%{$search}%")
                    ->orWhere('pd.nisn', 'like', "%{$search}%")
                    ->orWhere('rb.nama', 'like', "%{$search}%")
                    ->orWhere('iz.alasan', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('iz.' . $sort, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        // Statistik ringkasan hari ini
        $statDate = !empty($tanggal) ? $tanggal : date('Y-m-d');
        $statBase = DB::table('peserta_didik_izin_keluar')->where('tanggal', $statDate);

        if ($tab === 'saya' && $siswaLogin) {
            $statBase->where('peserta_didik_id', $siswaLogin->peserta_didik_id);
        } elseif ($tab === 'wali' && !$isSuperAdmin && $waliRombel) {
            $statBase->where('rombel_id', $waliRombel->rombongan_belajar_id);
        }

        $stats = [
            'total' => (clone $statBase)->count(),
            'menunggu_wali' => (clone $statBase)->where('status', 'menunggu_wali')->count(),
            'menunggu_piket' => (clone $statBase)->where('status', 'menunggu_piket')->count(),
            'menunggu_satpam' => (clone $statBase)->where('status', 'menunggu_satpam')->count(),
            'di_luar' => (clone $statBase)->where('status', 'di_luar')->count(),
            'kembali' => (clone $statBase)->where('status', 'kembali')->count(),
            'pulang' => (clone $statBase)->where('status', 'pulang_selesai')->count(),
        ];

        // Daftar rombel untuk filter dropdown
        $rombelList = DB::table('rombongan_belajar')->orderBy('nama')->get();

        return view('dashboard.e-izin', compact(
            'items', 'stats', 'tab', 'search', 'status', 'jenis', 'tanggal',
            'rombelId', 'perPage', 'sort', 'sortDir', 'canCreate', 'canRead',
            'canUpdate', 'canDelete', 'isSuperAdmin', 'isStudent', 'isWaliKelas',
            'isGuruPiket', 'isSecurity', 'siswaLogin', 'waliRombel', 'rombelList'
        ));
    }

    /**
     * Ajukan e-Izin Baru (Siswa Mandiri / Petugas Piket / Admin)
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isStudent = ($role === 'peserta_didik');
        $isSuperAdmin = ($role === 'admin');

        $validated = $request->validate([
            'jenis_izin' => 'required|string|in:keluar_sebentar,pulang_cepat',
            'alasan' => 'required|string|max:500',
            'jam_izin_keluar' => 'required',
            'jam_rencana_kembali' => 'nullable',
            'peserta_didik_id' => $isStudent ? 'nullable' : 'required|string',
        ]);

        $siswa = null;
        if ($isStudent) {
            $siswa = $this->getSiswaFromSession();
            if (!$siswa) {
                return $this->errorResponse($request, 'Sesi peserta didik Anda tidak ditemukan.');
            }
        } else {
            $siswa = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('pd.peserta_didik_id', $validated['peserta_didik_id'])
                ->select('pd.*', 'rb.ptk_id as wali_ptk_id')
                ->first();

            if (!$siswa) {
                return $this->errorResponse($request, 'Data peserta didik tidak ditemukan.');
            }
        }

        // Tentukan PTK ID Wali Kelas dari rombel siswa
        $waliPtkId = null;
        if (!empty($siswa->rombongan_belajar_id)) {
            $rombel = DB::table('rombongan_belajar')
                ->where('rombongan_belajar_id', $siswa->rombongan_belajar_id)
                ->first();
            $waliPtkId = $rombel?->ptk_id;
        }

        // Alur status:
        // Jika siswa mengajukan -> menunggu_wali
        // Jika piket/admin membuatkan izin langsung -> langsung disetujui piket & menunggu_satpam
        $initialStatus = 'menunggu_wali';
        $waliStatus = 'menunggu';
        $piketStatus = 'menunggu';
        $piketPtkId = null;
        $piketApprovedAt = null;

        if (!$isStudent) {
            $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
            $initialStatus = 'menunggu_satpam';
            $waliStatus = 'disetujui';
            $piketStatus = 'disetujui';
            $piketPtkId = $ptkId;
            $piketApprovedAt = now();
        }

        $nomorTiket = PesertaDidikIzinKeluar::generateNomorTiket();
        $namaCreator = is_array($user) ? ($user['nama'] ?? 'Pengguna') : ($user->nama ?? 'Pengguna');

        $recordId = DB::transaction(function () use (
            $nomorTiket, $siswa, $validated, $initialStatus,
            $waliPtkId, $waliStatus, $piketPtkId, $piketStatus,
            $piketApprovedAt, $namaCreator, $isStudent
        ) {
            return DB::table('peserta_didik_izin_keluar')->insertGetId([
                'nomor_tiket' => $nomorTiket,
                'peserta_didik_id' => $siswa->peserta_didik_id,
                'rombel_id' => $siswa->rombongan_belajar_id ?? null,
                'tanggal' => date('Y-m-d'),
                'jenis_izin' => $validated['jenis_izin'],
                'alasan' => $validated['alasan'],
                'jam_izin_keluar' => $validated['jam_izin_keluar'],
                'jam_rencana_kembali' => ($validated['jenis_izin'] === 'keluar_sebentar') ? ($validated['jam_rencana_kembali'] ?? null) : null,
                'status' => $initialStatus,
                'wali_kelas_ptk_id' => $waliPtkId,
                'wali_status' => $waliStatus,
                'petugas_piket_ptk_id' => $piketPtkId,
                'piket_status' => $piketStatus,
                'piket_approved_at' => $piketApprovedAt,
                'created_by' => $namaCreator . ($isStudent ? ' (Siswa)' : ' (Petugas/Admin)'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Kirim notifikasi sistem
        $jenisLabel = ($validated['jenis_izin'] === 'pulang_cepat') ? 'Izin Pulang' : 'Izin Keluar-Masuk KBM';
        NotifikasiTransaksi::kirimNotifikasiIzin(
            $siswa->peserta_didik_id,
            $siswa->rombongan_belajar_id,
            "Pengajuan {$jenisLabel}: {$siswa->nama}",
            "Tiket {$nomorTiket} telah diajukan. Alasan: {$validated['alasan']}.",
            'info',
            'fa-solid fa-file-signature'
        );

        $msg = "Pengajuan e-Izin berhasil diajukan dengan Nomor Tiket: {$nomorTiket}.";
        if ($isStudent) {
            $msg .= ' Menunggu konfirmasi dan persetujuan Wali Kelas.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $msg,
                'nomor_tiket' => $nomorTiket,
                'id' => $recordId,
            ]);
        }

        return redirect()->route('dashboard.e-izin.index', ['tab' => $isStudent ? 'saya' : 'semua'])
            ->with('success', $msg);
    }

    /**
     * Persetujuan oleh Wali Kelas
     */
    public function approveWali(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data e-Izin tidak ditemukan.');
        }

        $catatan = trim($request->input('catatan', 'Disetujui oleh Wali Kelas'));

        DB::transaction(function () use ($id, $ptkId, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => 'menunggu_piket',
                'wali_status' => 'disetujui',
                'wali_approved_at' => now(),
                'wali_kelas_ptk_id' => $ptkId,
                'wali_catatan' => $catatan,
                'updated_at' => now(),
            ]);
        });

        // Notifikasi ke guru piket & siswa
        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $tiket->peserta_didik_id)->first();
        $siswaNama = $siswa?->nama ?? 'Siswa';

        NotifikasiTransaksi::kirimNotifikasiIzin(
            $tiket->peserta_didik_id,
            $tiket->rombel_id,
            "e-Izin Disetujui Wali Kelas: {$siswaNama}",
            "Tiket {$tiket->nomor_tiket} telah disetujui Wali Kelas dan diteruskan ke Petugas Guru Piket.",
            'success',
            'fa-solid fa-user-check'
        );

        $msg = "e-Izin ({$tiket->nomor_tiket}) telah DISETUJUI oleh Wali Kelas. Alur kini diteruskan ke Guru Piket.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Penolakan oleh Wali Kelas
     */
    public function rejectWali(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data e-Izin tidak ditemukan.');
        }

        $catatan = trim($request->input('catatan', 'Pengajuan izin ditolak oleh Wali Kelas.'));

        DB::transaction(function () use ($id, $ptkId, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => 'ditolak_wali',
                'wali_status' => 'ditolak',
                'wali_approved_at' => now(),
                'wali_kelas_ptk_id' => $ptkId,
                'wali_catatan' => $catatan,
                'updated_at' => now(),
            ]);
        });

        $msg = "e-Izin ({$tiket->nomor_tiket}) telah DITOLAK oleh Wali Kelas.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Persetujuan oleh Guru Piket
     */
    public function approvePiket(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data e-Izin tidak ditemukan.');
        }

        $catatan = trim($request->input('catatan', 'Disetujui oleh Guru Piket'));

        DB::transaction(function () use ($id, $ptkId, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => 'menunggu_satpam',
                'piket_status' => 'disetujui',
                'piket_approved_at' => now(),
                'petugas_piket_ptk_id' => $ptkId,
                'piket_catatan' => $catatan,
                'updated_at' => now(),
            ]);
        });

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $tiket->peserta_didik_id)->first();
        $siswaNama = $siswa?->nama ?? 'Siswa';

        NotifikasiTransaksi::kirimNotifikasiIzin(
            $tiket->peserta_didik_id,
            $tiket->rombel_id,
            "e-Izin Disetujui Guru Piket: {$siswaNama}",
            "Tiket {$tiket->nomor_tiket} telah disetujui Guru Piket. Siswa dapat menunjukkan slip/barcode ke Security Pos Gerbang.",
            'success',
            'fa-solid fa-clipboard-check'
        );

        $msg = "e-Izin ({$tiket->nomor_tiket}) telah DISETUJUI Guru Piket. Tiket siap diverifikasi di Pos Satpam.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Penolakan oleh Guru Piket
     */
    public function rejectPiket(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data e-Izin tidak ditemukan.');
        }

        $catatan = trim($request->input('catatan', 'Pengajuan izin ditolak oleh Guru Piket.'));

        DB::transaction(function () use ($id, $ptkId, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => 'ditolak_piket',
                'piket_status' => 'ditolak',
                'piket_approved_at' => now(),
                'petugas_piket_ptk_id' => $ptkId,
                'piket_catatan' => $catatan,
                'updated_at' => now(),
            ]);
        });

        $msg = "e-Izin ({$tiket->nomor_tiket}) telah DITOLAK oleh Guru Piket.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Verifikasi Keluar Gerbang oleh Security (Checkout)
     */
    public function checkoutSecurity(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $namaSatpam = is_array($user) ? ($user['nama'] ?? 'Petugas Satpam') : ($user->nama ?? 'Petugas Satpam');

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data tiket tidak ditemukan.');
        }

        $newStatus = ($tiket->jenis_izin === 'pulang_cepat') ? 'pulang_selesai' : 'di_luar';
        $catatan = $request->input('catatan_satpam', $tiket->catatan_satpam);

        DB::transaction(function () use ($id, $newStatus, $namaSatpam, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => $newStatus,
                'satpam_checkout_by' => $namaSatpam,
                'satpam_checkout_at' => now(),
                'catatan_satpam' => $catatan,
                'updated_at' => now(),
            ]);
        });

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $tiket->peserta_didik_id)->first();
        $siswaNama = $siswa?->nama ?? 'Siswa';
        $waktuStr = now()->format('H:i') . ' WIB';

        NotifikasiTransaksi::kirimNotifikasiIzin(
            $tiket->peserta_didik_id,
            $tiket->rombel_id,
            "Siswa Keluar Gerbang: {$siswaNama}",
            "Siswa {$siswaNama} diverifikasi KELUAR gerbang sekolah pada {$waktuStr}.",
            'warning',
            'fa-solid fa-door-open'
        );

        $msg = "Siswa berhasil diverifikasi KELUAR gerbang (Tiket: {$tiket->nomor_tiket}).";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Verifikasi Masuk Kembali oleh Security (Checkin)
     */
    public function checkinSecurity(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $namaSatpam = is_array($user) ? ($user['nama'] ?? 'Petugas Satpam') : ($user->nama ?? 'Petugas Satpam');

        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data tiket tidak ditemukan.');
        }

        $catatan = $request->input('catatan_satpam', $tiket->catatan_satpam);

        DB::transaction(function () use ($id, $namaSatpam, $catatan) {
            DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
                'status' => 'kembali',
                'satpam_checkin_by' => $namaSatpam,
                'satpam_checkin_at' => now(),
                'jam_kembali_aktual' => now()->format('H:i:s'),
                'catatan_satpam' => $catatan,
                'updated_at' => now(),
            ]);
        });

        $siswa = DB::table('peserta_didik')->where('peserta_didik_id', $tiket->peserta_didik_id)->first();
        $siswaNama = $siswa?->nama ?? 'Siswa';
        $waktuStr = now()->format('H:i') . ' WIB';

        NotifikasiTransaksi::kirimNotifikasiIzin(
            $tiket->peserta_didik_id,
            $tiket->rombel_id,
            "Siswa Masuk Kembali: {$siswaNama}",
            "Siswa {$siswaNama} telah KEMBALI masuk ke sekolah pada {$waktuStr}.",
            'success',
            'fa-solid fa-right-to-bracket'
        );

        $msg = "Siswa berhasil diverifikasi KEMBALI ke sekolah (Tiket: {$tiket->nomor_tiket}).";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Batalkan Pengajuan e-Izin (Oleh Siswa / Admin)
     */
    public function batalkan(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $tiket = DB::table('peserta_didik_izin_keluar')->where('id', $id)->first();
        if (!$tiket) {
            return $this->errorResponse($request, 'Data tiket tidak ditemukan.');
        }

        if (in_array($tiket->status, ['di_luar', 'kembali', 'pulang_selesai'])) {
            return $this->errorResponse($request, 'Tiket yang sudah diverifikasi keluar tidak dapat dibatalkan.');
        }

        DB::table('peserta_didik_izin_keluar')->where('id', $id)->update([
            'status' => 'dibatalkan',
            'updated_at' => now(),
        ]);

        $msg = "Pengajuan e-Izin ({$tiket->nomor_tiket}) telah dibatalkan.";
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Scan / Cari Tiket untuk Security Pos Gerbang
     */
    public function scanTiket(Request $request): JsonResponse
    {
        $keyword = trim($request->input('keyword', $request->input('nomor_tiket', '')));
        if (empty($keyword)) {
            return response()->json(['status' => 'error', 'message' => 'Masukkan nomor tiket atau NISN siswa.'], 422);
        }

        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'pd.jenis_kelamin',
                'rb.nama as nama_rombel',
                'wali.nama as nama_wali',
                'piket.nama as nama_piket'
            )
            ->where(function ($q) use ($keyword) {
                $q->where('iz.nomor_tiket', $keyword)
                    ->orWhere('pd.nisn', $keyword);
            })
            ->latest('iz.id')
            ->first();

        if (!$tiket) {
            return response()->json(['status' => 'error', 'message' => 'Tiket e-Izin tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tiket,
        ]);
    }

    /**
     * Detail Tiket e-Izin (JSON)
     */
    public function show(int|string $id): JsonResponse
    {
        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'rb.nama as nama_rombel',
                'wali.nama as nama_wali',
                'piket.nama as nama_piket'
            )
            ->where('iz.id', $id)
            ->first();

        if (!$tiket) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $tiket,
        ]);
    }

    /**
     * Cetak Slip Tiket e-Izin Keluar
     */
    public function cetakSlip(int|string $id): View|RedirectResponse
    {
        $tiket = DB::table('peserta_didik_izin_keluar as iz')
            ->leftJoin('peserta_didik as pd', 'iz.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'iz.rombel_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'iz.wali_kelas_ptk_id', '=', 'wali.ptk_id')
            ->leftJoin('gtk as piket', 'iz.petugas_piket_ptk_id', '=', 'piket.ptk_id')
            ->select(
                'iz.*',
                'pd.nama as nama_siswa',
                'pd.nisn',
                'rb.nama as nama_rombel',
                'wali.nama as nama_wali',
                'piket.nama as nama_piket'
            )
            ->where('iz.id', $id)
            ->first();

        if (!$tiket) {
            return redirect()->back()->with('error', 'Data tiket e-Izin tidak ditemukan.');
        }

        $sekolah = DB::table('sekolah')->first();

        return view('dashboard.e-izin-slip', compact('tiket', 'sekolah'));
    }

    /**
     * Pencarian Siswa Autocomplete
     */
    public function searchSiswa(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $rombelId = $request->input('rombel_id', '');

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'rb.nama as nama_rombel', 'pd.rombongan_belajar_id')
            ->limit(20);

        if (!empty($rombelId)) {
            $query->where('pd.rombongan_belajar_id', $rombelId);
        }

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('pd.nama', 'like', "%{$q}%")
                    ->orWhere('pd.nisn', 'like', "%{$q}%");
            });
        }

        $siswa = $query->get();

        return response()->json($siswa);
    }

    /**
     * Hapus Data e-Izin (Khusus Admin)
     */
    public function destroy(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if ($role !== 'admin') {
            return $this->errorResponse($request, 'Akses ditolak: Hanya Administrator yang dapat menghapus data.', 403);
        }

        DB::table('peserta_didik_izin_keluar')->where('id', $id)->delete();

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Data e-Izin berhasil dihapus.']);
        }

        return redirect()->back()->with('success', 'Data e-Izin berhasil dihapus.');
    }

    /**
     * Dapatkan data Siswa dari sesi login
     */
    protected function getSiswaFromSession(): ?object
    {
        $user = session('user');
        if (!$user) {
            return null;
        }

        $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
        $nisn = is_array($user) ? ($user['nisn'] ?? null) : ($user->nisn ?? null);
        $username = is_array($user) ? ($user['username'] ?? null) : ($user->username ?? null);

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('gtk as wali', 'rb.ptk_id', '=', 'wali.ptk_id')
            ->select(
                'pd.*',
                'rb.nama as nama_rombel',
                'rb.ptk_id as wali_ptk_id',
                'wali.nama as wali_nama'
            );

        if (!empty($pdId)) {
            $siswa = (clone $query)->where('pd.peserta_didik_id', $pdId)->first();
            if ($siswa) {
                return $siswa;
            }
        }
        if (!empty($nisn)) {
            $siswa = (clone $query)->where('pd.nisn', $nisn)->first();
            if ($siswa) {
                return $siswa;
            }
        }
        if (!empty($username)) {
            $siswa = (clone $query)->where(function ($q) use ($username) {
                $q->where('pd.nisn', $username)->orWhere('pd.nik', $username);
            })->first();
            if ($siswa) {
                return $siswa;
            }
        }

        return null;
    }

    /**
     * Helper Respon Error seragam
     */
    protected function errorResponse(Request $request, string $message, int $code = 400): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $code);
        }
        return redirect()->back()->with('error', $message);
    }
}
