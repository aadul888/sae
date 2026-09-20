<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\RolePermission;
use App\Models\Persuratan;

class PersuratanController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Tampilkan halaman utama modul Persuratan & Arsip Digital.
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        // Evaluasi granular hak akses CRUD
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'delete');

        // Statistik real-time dari database
        $stats = [
            'total'     => Persuratan::count(),
            'masuk'     => Persuratan::where('jenis_surat', 'masuk')->count(),
            'keluar'    => Persuratan::where('jenis_surat', 'keluar')->count(),
            'pending'   => Persuratan::where('status', 'menunggu_disposisi')->count(),
        ];

        $q           = trim($request->get('q', ''));
        $status      = $request->get('status', '');
        $jenisSurat  = $request->get('jenis_surat', '');
        $sort        = $request->get('sort', 'tanggal_surat');
        $sortDir     = strtolower($request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPageVal  = $request->get('per_page', '25');
        $perPage     = in_array($perPageVal, ['10', '25', '50', '100']) ? (int)$perPageVal : 25;

        $query = Persuratan::query();

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('nomor_surat', 'like', "%{$q}%")
                  ->orWhere('perihal', 'like', "%{$q}%")
                  ->orWhere('pengirim_asal', 'like', "%{$q}%")
                  ->orWhere('tujuan_penerima', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($jenisSurat !== '') {
            $query->where('jenis_surat', $jenisSurat);
        }

        $allowedSorts = ['nomor_surat', 'jenis_surat', 'tanggal_surat', 'status', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $sortDir);
        } else {
            $query->orderBy('tanggal_surat', 'desc');
        }

        $items = $query->paginate($perPage)->withQueryString();

        return view('dashboard.persuratan', compact(
            'stats',
            'items',
            'q',
            'status',
            'jenisSurat',
            'sort',
            'sortDir',
            'perPage',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Simpan surat baru (Create).
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah data surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menambah data.');
        }

        $validated = $request->validate([
            'nomor_surat'      => 'required|string|max:190',
            'jenis_surat'      => 'required|string|in:masuk,keluar,disposisi,keputusan,tugas',
            'perihal'          => 'required|string|max:255',
            'pengirim_asal'    => 'nullable|string|max:190',
            'tujuan_penerima'  => 'nullable|string|max:190',
            'tanggal_surat'    => 'required|date',
            'tanggal_diterima'  => 'nullable|date',
            'status'           => 'required|string|in:draf,menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan'       => 'nullable|string',
        ]);

        $userName = is_array($user)
            ? ($user['nama'] ?? ($user['name'] ?? 'Staf'))
            : ($user->nama ?? ($user->name ?? 'Staf'));

        $validated['created_by'] = $userName;

        $item = Persuratan::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Surat berhasil dicatat ke sistem persuratan.',
                'data' => $item,
            ]);
        }

        return back()->with('success', "Surat nomor {$item->nomor_surat} berhasil disimpan.");
    }

    /**
     * Tampilkan detail surat (Read / Detail).
     */
    public function show(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $item = Persuratan::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $item,
            'status_badge' => $item->status_badge,
            'jenis_badge' => $item->jenis_badge,
        ]);
    }

    /**
     * Perbarui data surat (Update).
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengubah data surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk mengubah data.');
        }

        $item = Persuratan::findOrFail($id);

        $validated = $request->validate([
            'nomor_surat'      => 'required|string|max:190',
            'jenis_surat'      => 'required|string|in:masuk,keluar,disposisi,keputusan,tugas',
            'perihal'          => 'required|string|max:255',
            'pengirim_asal'    => 'nullable|string|max:190',
            'tujuan_penerima'  => 'nullable|string|max:190',
            'tanggal_surat'    => 'required|date',
            'tanggal_diterima'  => 'nullable|date',
            'status'           => 'required|string|in:draf,menunggu_disposisi,diproses,selesai,diarsipkan',
            'keterangan'       => 'nullable|string',
        ]);

        $item->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data surat berhasil diperbarui.',
                'data' => $item,
            ]);
        }

        return back()->with('success', "Surat nomor {$item->nomor_surat} berhasil diperbarui.");
    }

    /**
     * Hapus arsip surat (Delete).
     */
    public function destroy(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_persuratan', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menghapus arsip surat.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk menghapus arsip surat.');
        }

        $item = Persuratan::findOrFail($id);
        $nomor = $item->nomor_surat;
        $item->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Arsip surat nomor {$nomor} berhasil dihapus.",
            ]);
        }

        return back()->with('success', "Arsip surat nomor {$nomor} berhasil dihapus.");
    }
}