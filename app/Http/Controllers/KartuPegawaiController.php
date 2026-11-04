<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Services\KartuPegawaiPrintService;
use Illuminate\Http\Request;

class KartuPegawaiController extends Controller
{
    public function __construct(
        protected KartuPegawaiPrintService $printService
    ) {}

    /**
     * Halaman Publik Verifikasi Resmi Kartu Pegawai (Direct scan dari QR code).
     */
    public function verify(Request $request, string $id)
    {
        $id = trim($id);
        $card = $this->printService->loadEmployeeCardData($id);

        if (!$card) {
            return view('kartu-pegawai.verifikasi-notfound', [
                'id' => $id,
                'sekolah' => $this->printService->getSekolahInfo(),
            ]);
        }

        return view('kartu-pegawai.verifikasi', [
            'card' => $card,
            'verifiedAt' => now()->translatedFormat('d F Y, H:i:s') . ' WIB',
        ]);
    }

    /**
     * Data JSON / Preview untuk Modal Kartu Pegawai Interaktif.
     */
    public function preview(Request $request, string $id)
    {
        $id = trim($id);
        $user = session('user');
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $card = $this->printService->loadEmployeeCardData($id);
        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Data pegawai tidak ditemukan.'], 404);
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userPtkId = is_array($user) ? ($user['ptk_id'] ?? '') : ($user->ptk_id ?? '');
        $userPenggunaId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? ''));
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));

        $isAdmin = in_array($role, ['admin', 'superadmin', 'operator'], true);

        if (!$isAdmin) {
            $isOwnCard = false;
            if (!empty($userPtkId) && ($userPtkId === $card['identifier'] || $userPtkId === $card['raw_id'])) {
                $isOwnCard = true;
            }
            if (!empty($card['nip']) && $card['nip'] !== '-' && (session('user.nip') === $card['nip'])) {
                $isOwnCard = true;
            }
            if (!empty($card['pengguna_id']) && $card['pengguna_id'] === $userPenggunaId) {
                $isOwnCard = true;
            }
            if (strcasecmp($card['nama_dasar'], $userName) === 0) {
                $isOwnCard = true;
            }
            if ($card['sumber'] === 'non_asn') {
                if ($userPenggunaId === ($card['pengguna_id'] ?? null) || session('user.username') === ($card['username'] ?? null)) {
                    $isOwnCard = true;
                }
            }

            if (!$isOwnCard) {
                $canManage = RolePermission::canAccess($user, 'menu_guru_aktif') ||
                    RolePermission::canAccess($user, 'menu_tendik_aktif') ||
                    RolePermission::canAccess($user, 'menu_gtk_non_asn');
                if (!$canManage) {
                    return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda hanya berwenang melihat kartu milik Anda sendiri.'], 403);
                }
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'card' => $card,
                'html' => view('kartu-pegawai.template', ['card' => $card, 'isPrint' => false])->render(),
            ]);
        }

        return view('kartu-pegawai.template', ['card' => $card]);
    }

    /**
     * Cetak Kartu Pegawai Tunggal (A4 Landscape).
     */
    public function printSingle(Request $request, string $id)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $id = trim($id);
        $card = $this->printService->loadEmployeeCardData($id);
        if (!$card) {
            abort(404, 'Data pegawai tidak ditemukan.');
        }

        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userPtkId = is_array($user) ? ($user['ptk_id'] ?? '') : ($user->ptk_id ?? '');
        $userPenggunaId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? ''));
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? '')) : ($user->nama ?? ($user->name ?? ''));

        $isAdmin = in_array($role, ['admin', 'superadmin', 'operator'], true);

        if (!$isAdmin) {
            $isOwnCard = false;
            if (!empty($userPtkId) && ($userPtkId === $card['identifier'] || $userPtkId === $card['raw_id'])) {
                $isOwnCard = true;
            }
            if (!empty($card['nip']) && $card['nip'] !== '-' && (session('user.nip') === $card['nip'])) {
                $isOwnCard = true;
            }
            if (!empty($card['pengguna_id']) && $card['pengguna_id'] === $userPenggunaId) {
                $isOwnCard = true;
            }
            if (strcasecmp($card['nama_dasar'], $userName) === 0) {
                $isOwnCard = true;
            }
            if ($card['sumber'] === 'non_asn') {
                if ($userPenggunaId === ($card['pengguna_id'] ?? null) || session('user.username') === ($card['username'] ?? null)) {
                    $isOwnCard = true;
                }
            }

            if (!$isOwnCard) {
                $canManage = RolePermission::canAccess($user, 'menu_guru_aktif') ||
                    RolePermission::canAccess($user, 'menu_tendik_aktif') ||
                    RolePermission::canAccess($user, 'menu_gtk_non_asn');
                if (!$canManage) {
                    abort(403, 'Akses ditolak: Anda hanya dapat mencetak kartu milik Anda sendiri.');
                }
            }
        }

        return view('kartu-pegawai.cetak-single', [
            'card' => $card,
        ]);
    }
}
