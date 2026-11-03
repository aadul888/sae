<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\RolePermission;
use App\Services\MbgService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk penarikan dan ekspor Data Kebutuhan MBG (Makan Bergizi Gratis).
 */
class KebutuhanMbgController extends Controller
{
    use ChecksAuthenticatedSession;

    /**
     * Tampilkan halaman utama penarikan Data Kebutuhan MBG.
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $tab = $request->get('tab', 'guru_tendik');
        if (! in_array($tab, ['guru_tendik', 'kelas_10', 'kelas_11', 'kelas_12'], true)) {
            $tab = 'guru_tendik';
        }

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 5 || $perPage > 200) {
            $perPage = 25;
        }

        $q = trim((string) $request->get('q', ''));
        $jk = trim((string) $request->get('jk', ''));

        $stats = MbgService::getStats();
        $list = MbgService::getData($tab, [
            'q' => $q,
            'jk' => $jk,
        ], $perPage);

        $canExport = RolePermission::canAccess($user ?: $role, 'menu_kebutuhan_mbg', 'read');

        return view('dashboard.kebutuhan-mbg', compact(
            'stats',
            'list',
            'tab',
            'q',
            'jk',
            'perPage',
            'canExport'
        ));
    }

    /**
     * Unduh file Excel multi-sheet resmi Data Kebutuhan MBG.
     */
    public function export(Request $request): BinaryFileResponse
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (! RolePermission::canAccess($user ?: $role, 'menu_kebutuhan_mbg', 'read')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menarik data MBG.');
        }

        $filePath = MbgService::exportExcel();
        $fileName = 'Data_Kebutuhan_MBG_SMK_NEGERI_1_PAGELARAN_'.date('Ymd_His').'.xlsx';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
