<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PengumumanController extends Controller
{
    /**
     * Halaman daftar & manajemen pengumuman (Admin & Staff Berwenang)
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        // Jika bukan admin dan tidak memiliki hak membuat/mengubah pengumuman, arahkan ke feed pengguna
        $canCreate = RolePermission::can($role, 'menu_pengumuman', 'create');
        $canUpdate = RolePermission::can($role, 'menu_pengumuman', 'update');
        $canDelete = RolePermission::can($role, 'menu_pengumuman', 'delete');

        if ($role !== 'admin' && !$canCreate && !$canUpdate && !$canDelete) {
            return redirect()->route('dashboard.informasi.index');
        }

        if (!RolePermission::canAccess($user ?: $role, 'menu_pengumuman')) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Akses dibatasi.');
        }

        $q = trim($request->get('q', ''));
        $targetFilter = $request->get('target', '');
        $targetPeranFilter = $request->get('target_peran', '');
        $statusFilter = $request->get('status', '');
        $perPage = (int) $request->get('perPage', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Pengumuman::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('judul', 'like', "%{$q}%")
                    ->orWhere('isi', 'like', "%{$q}%")
                    ->orWhere('penulis_nama', 'like', "%{$q}%");
            });
        }

        if ($targetFilter !== '' && in_array($targetFilter, ['publik', 'pengguna', 'semua'], true)) {
            $query->where('target', $targetFilter);
        }

        if ($targetPeranFilter !== '' && in_array($targetPeranFilter, ['semua', 'admin', 'guru', 'tendik', 'peserta_didik'], true)) {
            $query->where('target_peran', $targetPeranFilter);
        }

        if ($statusFilter !== '') {
            $query->where('is_active', $statusFilter === '1');
        }

        $query->orderByDesc('created_at');

        $list = $query->paginate($perPage)->appends($request->query());

        // Ringkasan statistik
        $summary = [
            'total'   => Pengumuman::count(),
            'aktif'   => Pengumuman::where('is_active', true)->count(),
            'publik'  => Pengumuman::whereIn('target', ['publik', 'semua'])->where('is_active', true)->count(),
            'lonceng' => Pengumuman::whereIn('target', ['pengguna', 'semua'])->where('is_active', true)->count(),
        ];

        return view('dashboard.pengumuman', compact(
            'list',
            'summary',
            'q',
            'targetFilter',
            'targetPeranFilter',
            'statusFilter',
            'perPage',
            'canCreate',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Halaman feed & portal informasi bagi pengguna (Guru, Tendik, Siswa, Admin)
     */
    public function pengguna(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');
        $userId = (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? '')));

        // Menandai semua dibaca jika ada parameter mark_read=1
        if ($request->boolean('mark_read') && $userId !== '') {
            Pengumuman::forUserRole($role)->get()->each(function (Pengumuman $pengumuman) use ($userId) {
                $pengumuman->tandaiDibacaOleh($userId);
            });
        }

        // Menandai pengumuman yang di-highlight jika ada
        $highlightId = $request->filled('highlight') ? (int) $request->get('highlight') : null;
        if ($highlightId && $userId !== '') {
            $hlItem = Pengumuman::find($highlightId);
            if ($hlItem) {
                $hlItem->tandaiDibacaOleh($userId);
            }
        }

        $q = trim($request->get('q', ''));
        $activeTab = $request->get('tab', 'semua');
        if (!in_array($activeTab, ['semua', 'unread', 'umum', 'sistem'], true)) {
            $activeTab = 'semua';
        }

        // Ambil seluruh pengumuman aktif yang ditujukan untuk peran pengguna
        $baseQuery = Pengumuman::forUserRole($role);

        // Ambil data untuk statistik tab sebelum filter diterapkan
        $allActive = (clone $baseQuery)->get();
        $totalCount = $allActive->count();
        $unreadCount = $allActive->filter(fn($item) => !$item->sudahDibacaOleh($userId))->count();
        $recentCount = $allActive->filter(fn($item) => $item->created_at && $item->created_at->gt(now()->subDays(7)))->count();

        $countSemua = $totalCount;
        $countUnread = $unreadCount;
        $countUmum = $allActive->filter(fn($item) => stripos($item->penulis_nama ?? '', 'sistem') === false)->count();
        $countSistem = $allActive->filter(fn($item) => stripos($item->penulis_nama ?? '', 'sistem') !== false)->count();

        // Terapkan pencarian kata kunci
        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('judul', 'like', "%{$q}%")
                    ->orWhere('isi', 'like', "%{$q}%")
                    ->orWhere('penulis_nama', 'like', "%{$q}%");
            });
        }

        // Terapkan filter tab kategori
        if ($activeTab === 'umum') {
            $baseQuery->where(function ($sub) {
                $sub->where('penulis_nama', 'not like', '%Sistem%')
                    ->orWhereNull('penulis_nama');
            });
        } elseif ($activeTab === 'sistem') {
            $baseQuery->where('penulis_nama', 'like', '%Sistem%');
        }

        $collection = $baseQuery->get();

        // Jika tab adalah unread, saring yang belum dibaca pengguna aktif
        if ($activeTab === 'unread') {
            $collection = $collection->filter(fn($item) => !$item->sudahDibacaOleh($userId))->values();
        }

        // Paginate secara konsisten menggunakan LengthAwarePaginator
        $perPage = 8;
        $currentPage = Paginator::resolveCurrentPage() ?: 1;
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $items = new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $canManage = $role === 'admin' || RolePermission::can($role, 'menu_pengumuman', 'create');

        return view('dashboard.informasi-pengguna', compact(
            'items',
            'totalCount',
            'unreadCount',
            'recentCount',
            'countSemua',
            'countUnread',
            'countUmum',
            'countSistem',
            'activeTab',
            'q',
            'userId',
            'role',
            'canManage',
            'highlightId'
        ));
    }

    /**
     * Tandai seluruh pengumuman pengguna sebagai sudah dibaca
     */
    public function markAllRead(Request $request)
    {
        $user = session('user');
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            return redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');
        $userId = (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? '')));

        if ($userId !== '') {
            Pengumuman::forUserRole($role)->get()->each(function (Pengumuman $p) use ($userId) {
                $p->tandaiDibacaOleh($userId);
            });
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Semua pengumuman berhasil ditandai sudah dibaca.',
                'unread_count' => 0,
            ]);
        }

        return back()->with('success', 'Semua pengumuman berhasil ditandai sudah dibaca.');
    }

    /**
     * Tandai satu pengumuman tertentu sebagai sudah dibaca (AJAX)
     */
    public function markSingleRead(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);
        }

        $userId = (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? '')));
        $pengumuman = Pengumuman::findOrFail($id);

        $pengumuman->tandaiDibacaOleh($userId);

        // Hitung sisa unread untuk role pengguna saat ini
        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');
        $remainingUnread = Pengumuman::forUserRole($role)->get()->filter(fn($p) => !$p->sudahDibacaOleh($userId))->count();

        return response()->json([
            'status' => 'success',
            'message' => 'Pengumuman telah ditandai sudah dibaca.',
            'unread_count' => $remainingUnread,
        ]);
    }

    /**
     * Ambil data detail pengumuman untuk modal pembaca & otomatis tandai terbaca
     */
    public function detail(Request $request, int $id)
    {
        $user = session('user');
        $userId = $user ? (string) (is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? '')) : ($user->pengguna_id ?? ($user->id ?? ''))) : null;

        $pengumuman = Pengumuman::findOrFail($id);

        if ($userId) {
            $pengumuman->tandaiDibacaOleh($userId);
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');
        $remainingUnread = $userId ? Pengumuman::forUserRole($role)->get()->filter(fn($p) => !$p->sudahDibacaOleh($userId))->count() : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $pengumuman->id,
                'judul' => $pengumuman->judul,
                'isi' => $pengumuman->isi,
                'target' => $pengumuman->target,
                'target_peran' => $pengumuman->target_peran,
                'penulis_nama' => $pengumuman->penulis_nama ?: 'Administrator',
                'created_at' => $pengumuman->created_at ? $pengumuman->created_at->format('d F Y, H:i') : '-',
                'time_diff' => $pengumuman->created_at ? $pengumuman->created_at->diffForHumans() : '-',
                'jumlah_pembaca' => $pengumuman->jumlah_pembaca,
                'is_active' => (bool) $pengumuman->is_active,
            ],
            'unread_count' => $remainingUnread,
        ]);
    }

    /**
     * Simpan pengumuman baru
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        if (!RolePermission::can($role, 'menu_pengumuman', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menambah pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk menambah pengumuman.');
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'target' => 'required|in:publik,pengguna,semua',
            'target_peran' => 'nullable|string|in:semua,admin,guru,tendik,peserta_didik',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $defaultAuthor = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? 'Staff')) : ($user->name ?? ($user->nama ?? 'Staff'));
        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $defaultAuthor;

        $pengumuman = Pengumuman::create([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'target' => $validated['target'],
            'target_peran' => $validated['target_peran'] ?? 'semua',
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
            'penulis_nama' => $authorName,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil diterbitkan.',
                'data' => $pengumuman,
            ]);
        }

        return back()->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    /**
     * Perbarui pengumuman
     */
    public function update(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        if (!RolePermission::can($role, 'menu_pengumuman', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk mengubah pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk mengubah pengumuman.');
        }

        $pengumuman = Pengumuman::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'target' => 'required|in:publik,pengguna,semua',
            'target_peran' => 'nullable|string|in:semua,admin,guru,tendik,peserta_didik',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $pengumuman->penulis_nama;

        $pengumuman->update([
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'target' => $validated['target'],
            'target_peran' => $validated['target_peran'] ?? 'semua',
            'penulis_nama' => $authorName,
            'is_active' => $request->has('is_active') ? (bool)$request->input('is_active') : true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil diperbarui.',
                'data' => $pengumuman,
            ]);
        }

        return back()->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Toggle status aktif pengumuman (AJAX)
     */
    public function toggle(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        if (!RolePermission::can($role, 'menu_pengumuman', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->is_active = !$pengumuman->is_active;
        $pengumuman->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Status pengumuman berhasil diubah.',
            'is_active' => (bool) $pengumuman->is_active,
        ]);
    }

    /**
     * Hapus pengumuman
     */
    public function destroy(Request $request, int $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        if (!RolePermission::can($role, 'menu_pengumuman', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menghapus pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus pengumuman.');
        }

        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }
}
