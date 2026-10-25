<?php

namespace App\Http\Controllers;

use App\Models\KartuPelajarRequest;
use App\Models\RolePermission;
use App\Services\KartuPelajarKeuanganService;
use App\Services\KartuPelajarManageService;
use App\Services\KartuPelajarPrintService;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KartuPelajarController extends Controller
{
    public function __construct(
        protected KartuPelajarManageService $manageService,
        protected KartuPelajarPrintService $printService,
        protected KartuPelajarKeuanganService $keuanganService
    ) {}

    /**
     * Halaman Manajemen Kartu Pelajar, Pembayaran, Status Cetak & Notifikasi WhatsApp (Admin Only).
     */
    public function manageIndex(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak: Menu ini khusus untuk Administrator.');
        }

        $sekolahInfo = $this->printService->getSekolahInfo();
        $data = $this->manageService->getManageIndexData($request, $sekolahInfo);

        return view('dashboard.kartu-pelajar.index', $data);
    }

    /**
     * Update Catatan Pembayaran Kartu Pelajar (Single).
     */
    public function updatePembayaran(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id'   => 'required|string',
            'status_pembayaran'  => 'required|in:belum_bayar,lunas',
            'nominal'            => 'nullable|numeric|min:0',
            'metode_pembayaran'  => 'nullable|string|max:50',
            'tanggal_bayar'      => 'nullable|date',
            'status_cetak'       => 'nullable|in:belum_cetak,sudah_cetak',
            'nomor_wa'           => 'nullable|string|max:30',
            'catatan'            => 'nullable|string|max:500',
        ]);

        $res = $this->manageService->updatePembayaran($validated, $user, $this->printService->getSekolahInfo());
        if (isset($res['error'])) {
            return response()->json(['success' => false, 'message' => $res['message']], 404);
        }

        return response()->json($res);
    }

    /**
     * Toggle Status Cetak Kartu Pelajar (Sudah Cetak / Belum Cetak).
     */
    public function toggleCetak(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string',
        ]);

        $res = $this->manageService->toggleCetak($request->peserta_didik_id);
        if (isset($res['error'])) {
            return response()->json(['success' => false, 'message' => $res['message']], 404);
        }

        return response()->json($res);
    }

    /**
     * Aksi Masal Status Pembayaran & Status Cetak Fisik.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'action'                 => 'required|in:set_lunas,set_belum_bayar,set_sudah_cetak,set_belum_cetak,custom_bulk',
            'ids'                    => 'nullable|array',
            'apply_all_filtered'     => 'nullable|boolean',
            'filter_q'               => 'nullable|string|max:255',
            'filter_rombel'          => 'nullable|string|max:100',
            'filter_status_bayar'    => 'nullable|string|max:50',
            'status_pembayaran'      => 'nullable|in:lunas,belum_bayar,keep',
            'bulk_status_pembayaran' => 'nullable|in:lunas,belum_bayar,keep',
            'nominal'                => 'nullable|numeric|min:0',
            'bulk_nominal'           => 'nullable|numeric|min:0',
            'metode_pembayaran'      => 'nullable|string|max:50',
            'bulk_metode_pembayaran' => 'nullable|string|max:50',
            'tanggal_bayar'          => 'nullable|date',
            'bulk_tanggal_bayar'     => 'nullable|date',
            'status_cetak'           => 'nullable|in:sudah_cetak,belum_cetak,keep',
            'bulk_status_cetak'      => 'nullable|in:sudah_cetak,belum_cetak,keep',
            'tanggal_cetak'          => 'nullable|date',
            'bulk_tanggal_cetak'     => 'nullable|date',
            'catatan'                => 'nullable|string|max:500',
            'bulk_catatan'           => 'nullable|string|max:500',
        ]);

        $res = $this->manageService->bulkAction($validated, $user);

        return response()->json($res);
    }

    /**
     * Dapatkan data WhatsApp & QR Code untuk modal kirim pesan WhatsApp.
     */
    public function getWaData(Request $request, string $nisn): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $res = $this->manageService->getWaData($nisn, $this->printService->getSekolahInfo());
        if (!$res) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        return response()->json($res);
    }

    /**
     * Log waktu pengiriman pesan WhatsApp ke riwayat pembayaran.
     */
    public function logWa(Request $request): JsonResponse
    {
        $request->validate([
            'peserta_didik_id' => 'required|string',
            'nomor_wa'         => 'nullable|string',
        ]);

        $res = $this->manageService->logWa($request->peserta_didik_id, $request->nomor_wa);

        return response()->json($res);
    }

    /**
     * Download langsung file QR Code Kartu Pelajar (SVG).
     */
    public function downloadQrSvg(string $nisn)
    {
        $verifyUrl = route('kartu-pelajar.verify-short', ['nisn' => $nisn]);
        $svg = QrCodeService::generateSvg($verifyUrl, 300, 2);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'attachment; filename="QR_Kartu_Pelajar_' . $nisn . '.svg"',
        ]);
    }

    /**
     * Update Pengaturan Kartu Pelajar.
     */
    public function updatePengaturan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            abort(403, 'Akses ditolak.');
        }

        $res = $this->manageService->updatePengaturan($request, $user);
        if ($res['tab']) {
            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => $res['tab']])
                ->with('success', $res['message']);
        }

        return redirect()->route('dashboard.kartu-pelajar.index');
    }

    /**
     * Catat Klaim Garansi Kartu Pelajar Siswa.
     */
    public function klaimGaransi(Request $request): JsonResponse
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'peserta_didik_id' => 'required|string',
            'jenis_klaim'      => 'required|in:ganti_kartu_full,cetak_stiker',
            'alasan'           => 'required|string|max:500',
        ]);

        $res = $this->manageService->klaimGaransi(
            $request->peserta_didik_id,
            $request->jenis_klaim,
            $request->alasan,
            $user
        );

        if (isset($res['error'])) {
            return response()->json(['success' => false, 'message' => $res['message']], $res['status_code'] ?? 422);
        }

        return response()->json($res);
    }

    /**
     * Halaman Publik Verifikasi Resmi Kartu Pelajar (Direct scan dari QR code).
     */
    public function verify(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $card = $this->printService->loadStudentCardData($nisn);

        if (!$card) {
            return view('kartu-pelajar.verifikasi-notfound', [
                'nisn' => $nisn,
                'sekolah' => $this->printService->getSekolahInfo(),
            ]);
        }

        return view('kartu-pelajar.verifikasi', [
            'card' => $card,
            'verifiedAt' => now()->translatedFormat('d F Y, H:i:s') . ' WIB',
        ]);
    }

    /**
     * Data JSON / Preview untuk Modal Kartu Pelajar Interaktif.
     */
    public function preview(Request $request, string $nisn)
    {
        $nisn = trim($nisn);
        $user = session('user');
        if ($user) {
            $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
            $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

            if (in_array($role, ['peserta_didik', 'orang_tua'], true) && $userNisn !== $nisn) {
                $isKoord = ($role === 'peserta_didik') && RolePermission::isKoordinator($user);
                if (!$isKoord) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya berhak melihat kartu milik Anda sendiri.'], 403);
                }
            }

            if (in_array($role, ['guru', 'tendik'], true) && !RolePermission::isWaliKelasOrAdmin($user)) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak: Hanya Administrator dan Wali Kelas yang berwenang membuka kartu pelajar.'], 403);
            }
        }

        $card = $this->printService->loadStudentCardData($nisn);

        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'card' => $card,
                'html' => view('kartu-pelajar.template', ['card' => $card, 'isPrint' => false])->render(),
            ]);
        }

        return view('kartu-pelajar.single-preview', ['card' => $card]);
    }

    /**
     * Cetak Kartu Pelajar Tunggal.
     */
    public function printSingle(Request $request, string $nisn)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $nisn = trim($nisn);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userNisn = is_array($user) ? ($user['nisn'] ?? '') : ($user->nisn ?? '');

        if ($role === 'peserta_didik') {
            $isKoord = RolePermission::isKoordinator($user);
            if ($userNisn !== $nisn && !$isKoord) {
                abort(403, 'Akses ditolak: Anda hanya dapat mencetak kartu pelajar milik Anda sendiri.');
            }
        } elseif (!RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang memiliki hak akses cetak kartu pelajar.');
        }

        $card = $this->printService->loadStudentCardData($nisn);

        if (!$card) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        return view('kartu-pelajar.cetak-single', [
            'card' => $card,
        ]);
    }

    /**
     * Cetak Masal Kartu Pelajar per Rombongan Belajar (Rombel).
     */
    public function printRombel(Request $request, string $rombelId)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        if (!RolePermission::isWaliKelasOrAdmin($user, $rombelId) && !RolePermission::isWaliKelasOrAdmin($user)) {
            abort(403, 'Akses ditolak: Hanya Administrator dan Wali Kelas yang diizinkan mencetak kartu pelajar masal.');
        }

        $filters = [];
        $statusReq = trim((string) $request->get('status_request', ''));
        if ($statusReq === '' && ($request->boolean('hanya_menunggu') || $request->get('filter') === 'menunggu')) {
            $statusReq = 'menunggu';
        }
        if ($statusReq !== '') {
            $filters['status_request'] = $statusReq;
        }

        // Validasi foto: aktif jika hanya_ada_foto dicentang ATAU otomatis saat filter request menunggu
        $hanyaAdaFoto = $request->has('hanya_ada_foto')
            ? $request->boolean('hanya_ada_foto')
            : ($statusReq !== '');
        if ($hanyaAdaFoto) {
            $filters['hanya_ada_foto'] = true;
        }

        $data = $this->printService->getRombelCardsData($rombelId, $filters);
        if (isset($data['error'])) {
            abort(404, 'Rombongan belajar tidak ditemukan.');
        }

        return view('kartu-pelajar.cetak-rombel', $data);
    }

    /**
     * Simpan Catatan Pengeluaran Baru.
     */
    public function storePengeluaran(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'create')) {
            abort(403, 'Akses ditolak.');
        }

        if ($request->has('nominal')) {
            $rawNominal = preg_replace('/[^0-9]/', '', (string) $request->nominal);
            $request->merge(['nominal' => $rawNominal !== '' ? (int) $rawNominal : null]);
        }
        if ($request->has('jumlah')) {
            $rawJumlah = preg_replace('/[^0-9]/', '', (string) $request->jumlah);
            $request->merge(['jumlah' => $rawJumlah !== '' ? max(1, (int) $rawJumlah) : 1]);
        }

        $request->validate([
            'judul'           => 'required|string|max:255',
            'kategori'        => 'required|string|max:50',
            'jumlah'          => 'nullable|integer|min:1|max:10000000',
            'nominal'         => 'required|numeric|min:1',
            'tanggal'         => 'required|date',
            'keterangan'      => 'nullable|string|max:1000',
            'bukti_nota'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ], [
            'judul.required'    => 'Uraian / judul pembelanjaan wajib diisi.',
            'kategori.required' => 'Kategori pengeluaran wajib dipilih.',
            'nominal.required'  => 'Nominal pengeluaran wajib diisi.',
            'nominal.numeric'   => 'Nominal pengeluaran harus berupa angka.',
            'nominal.min'       => 'Nominal pengeluaran minimal Rp 1.',
            'tanggal.required'  => 'Tanggal pengeluaran wajib diisi.',
            'bukti_nota.mimes'  => 'Bukti nota harus berupa berkas gambar (JPG, PNG, WebP) atau PDF.',
            'bukti_nota.max'    => 'Ukuran berkas bukti nota maksimal 5 MB.',
        ]);

        $res = $this->keuanganService->storePengeluaran($request, $user);

        if ($request->expectsJson()) {
            return response()->json($res);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan'])
            ->with('success', $res['message']);
    }

    /**
     * Update Catatan Pengeluaran.
     */
    public function updatePengeluaran(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
            }
            abort(403, 'Akses ditolak.');
        }

        if ($request->has('nominal')) {
            $rawNominal = preg_replace('/[^0-9]/', '', (string) $request->nominal);
            $request->merge(['nominal' => $rawNominal !== '' ? (int) $rawNominal : null]);
        }
        if ($request->has('jumlah')) {
            $rawJumlah = preg_replace('/[^0-9]/', '', (string) $request->jumlah);
            $request->merge(['jumlah' => $rawJumlah !== '' ? max(1, (int) $rawJumlah) : 1]);
        }

        $request->validate([
            'judul'           => 'required|string|max:255',
            'kategori'        => 'required|string|max:50',
            'jumlah'          => 'nullable|integer|min:1|max:10000000',
            'nominal'         => 'required|numeric|min:1',
            'tanggal'         => 'required|date',
            'keterangan'      => 'nullable|string|max:1000',
            'bukti_nota'      => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ], [
            'judul.required'    => 'Uraian / judul pembelanjaan wajib diisi.',
            'kategori.required' => 'Kategori pengeluaran wajib dipilih.',
            'nominal.required'  => 'Nominal pengeluaran wajib diisi.',
            'nominal.numeric'   => 'Nominal pengeluaran harus berupa angka.',
            'nominal.min'       => 'Nominal pengeluaran minimal Rp 1.',
            'tanggal.required'  => 'Tanggal pengeluaran wajib diisi.',
            'bukti_nota.mimes'  => 'Bukti nota harus berupa berkas gambar (JPG, PNG, WebP) atau PDF.',
            'bukti_nota.max'    => 'Ukuran berkas bukti nota maksimal 5 MB.',
        ]);

        $res = $this->keuanganService->updatePengeluaran($request, $id);

        if ($request->expectsJson()) {
            return response()->json($res);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan'])
            ->with('success', $res['message']);
    }

    /**
     * Hapus Catatan Pengeluaran.
     */
    public function destroyPengeluaran(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'delete')) {
            abort(403, 'Akses ditolak.');
        }

        $res = $this->keuanganService->destroyPengeluaran($id);

        if ($request->expectsJson()) {
            return response()->json($res);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan'])
            ->with('success', $res['message']);
    }

    /**
     * Cetak Laporan Keuangan Resmi Kartu Pelajar.
     */
    public function printLaporanKeuangan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $sekolahInfo = $this->printService->getSekolahInfo();
        $data = $this->keuanganService->getLaporanKeuanganData($request, $sekolahInfo);

        return view('kartu-pelajar.cetak-keuangan', $data);
    }

    private function canKartuPelajar(string $perm): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        return $role === 'admin' || RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', $perm);
    }

    /**
     * Autocomplete peserta didik untuk form request cetak.
     */
    public function cariSiswa(Request $request): JsonResponse
    {
        if (!$this->canKartuPelajar('read')) {
            return response()->json([], 403);
        }

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json($this->keuanganService->cariSiswa($q));
    }

    /**
     * Simpan request cetak kartu pelajar.
     */
    public function storeRequest(Request $request)
    {
        if (!$this->canKartuPelajar('create')) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'peserta_didik_id'   => 'required|string|exists:peserta_didik,peserta_didik_id',
            'jenis_request'      => 'required|in:' . implode(',', array_keys(KartuPelajarRequest::JENIS)),
            'status_pembayaran'  => 'nullable|in:belum_bayar,lunas',
            'metode_pembayaran'  => 'nullable|in:' . implode(',', array_keys(KartuPelajarRequest::METODE_PEMBAYARAN)),
            'alasan'             => 'nullable|string|max:500',
        ], [
            'peserta_didik_id.required' => 'Peserta didik wajib dipilih.',
            'peserta_didik_id.exists'   => 'Peserta didik tidak ditemukan.',
            'jenis_request.required'    => 'Jenis request wajib dipilih.',
            'jenis_request.in'          => 'Pilihan jenis request tidak valid.',
            'status_pembayaran.in'      => 'Status pembayaran tidak valid.',
            'metode_pembayaran.in'      => 'Metode pembayaran tidak valid.',
        ]);

        $res = $this->keuanganService->storeRequest($data, session('user'));
        if (!$res['success']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $res['message']], $res['status_code'] ?? 422);
            }
            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
                ->with('error', $res['message']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $res['message']]);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', $res['message']);
    }

    /**
     * Ubah status request. Status 'selesai' otomatis menandai kartu sudah dicetak.
     */
    public function updateStatusRequest(Request $request, int $id)
    {
        if (!$this->canKartuPelajar('update')) {
            abort(403, 'Akses ditolak.');
        }

        $data = $request->validate([
            'status'        => 'required|in:' . implode(',', array_keys(KartuPelajarRequest::STATUS)),
            'catatan_admin' => 'nullable|string|max:500',
        ], [
            'status.required' => 'Status request wajib dipilih.',
            'status.in'       => 'Pilihan status request tidak valid.',
        ]);

        $res = $this->keuanganService->updateStatusRequest($id, $data);
        if (!$res['success']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $res['message']], $res['status_code'] ?? 422);
            }
            return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
                ->with('error', $res['message']);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $res['message']]);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', $res['message']);
    }

    /**
     * Aksi massal antrean request cetak kartu (Ubah status / hapus massal).
     */
    public function bulkActionRequest(Request $request): JsonResponse
    {
        if (!$this->canKartuPelajar('update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'action'              => 'required|in:set_diproses,set_selesai,set_ditolak,set_menunggu,custom_status,delete',
            'ids'                 => 'nullable|array',
            'apply_all_filtered'  => 'nullable|boolean',
            'target_status'       => 'nullable|in:' . implode(',', array_keys(KartuPelajarRequest::STATUS)),
            'catatan_admin'       => 'nullable|string|max:500',
            'filter_q'            => 'nullable|string|max:255',
            'filter_rombel'       => 'nullable|string|max:100',
            'filter_status_bayar' => 'nullable|string|max:50',
            'filter_req_status'   => 'nullable|string|max:50',
        ]);

        $res = $this->keuanganService->bulkActionRequest($validated);

        return response()->json($res);
    }

    /**
     * Hapus request cetak.
     */
    public function destroyRequest(Request $request, int $id)
    {
        if (!$this->canKartuPelajar('delete')) {
            abort(403, 'Akses ditolak.');
        }

        $res = $this->keuanganService->destroyRequest($id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $res['message']]);
        }

        return redirect()->route('dashboard.kartu-pelajar.index', ['tab' => 'request'])
            ->with('success', $res['message']);
    }

    /**
     * Cetak Lembar Pengambilan / Serah Terima Kartu Pelajar Per Tanggal Cetak.
     */
    public function printPengambilan(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin' && !RolePermission::canAccess($user ?: $role, 'menu_kartu_pelajar', 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $sekolahInfo = $this->printService->getSekolahInfo();
        $data = $this->keuanganService->getPengambilanData($request, $sekolahInfo);

        return view('kartu-pelajar.cetak-pengambilan', $data);
    }
}
