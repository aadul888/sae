<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\Berita;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PengumumanController extends Controller
{
    /**
     * Halaman daftar & manajemen pengumuman, broadcast, dan berita (Admin & Staff Berwenang)
     */
    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        // Jika bukan admin dan tidak memiliki hak membuat/mengubah pengumuman, arahkan ke feed pengguna
        $canCreate = RolePermission::canAccess($user, 'menu_pengumuman', 'create');
        $canUpdate = RolePermission::canAccess($user, 'menu_pengumuman', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_pengumuman', 'delete');

        if ($role !== 'admin' && !$canCreate && !$canUpdate && !$canDelete) {
            return redirect()->route('dashboard.informasi.index');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'read')) {
            return redirect()->route('dashboard.' . $role)->with('error', 'Akses dibatasi.');
        }

        $activeTab = $request->get('tab', 'pengumuman');
        if (!in_array($activeTab, ['pengumuman', 'broadcast', 'berita'], true)) {
            $activeTab = 'pengumuman';
        }

        $q = trim($request->get('q', ''));
        $targetFilter = $request->get('target', '');
        $targetPeranFilter = $request->get('target_peran', '');
        $kategoriFilter = $request->get('kategori', '');
        $statusFilter = $request->get('status', '');
        $perPage = (int) $request->get('perPage', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $sort = $request->get('sort', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if ($activeTab === 'berita') {
            $allowedSorts = ['judul', 'kategori', 'views', 'is_active', 'created_at'];
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'created_at';
            }

            $query = Berita::query();

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('judul', 'like', "%{$q}%")
                        ->orWhere('ringkasan', 'like', "%{$q}%")
                        ->orWhere('isi', 'like', "%{$q}%")
                        ->orWhere('penulis_nama', 'like', "%{$q}%");
                });
            }

            if ($kategoriFilter !== '' && $kategoriFilter !== 'Semua') {
                $query->where('kategori', $kategoriFilter);
            }

            if ($statusFilter !== '') {
                $query->where('is_active', $statusFilter === '1');
            }

            $query->orderBy($sort, $sortDir);
            $list = $query->paginate($perPage)->appends($request->query());
        } elseif ($activeTab === 'broadcast') {
            $allowedSorts = ['judul', 'isi', 'is_active', 'created_at'];
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'created_at';
            }

            $query = Pengumuman::broadcast();

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('judul', 'like', "%{$q}%")
                        ->orWhere('isi', 'like', "%{$q}%")
                        ->orWhere('penulis_nama', 'like', "%{$q}%");
                });
            }

            if ($statusFilter !== '') {
                $query->where('is_active', $statusFilter === '1');
            }

            $query->orderBy($sort, $sortDir);
            $list = $query->paginate($perPage)->appends($request->query());
        } else {
            // Tab 'pengumuman'
            $allowedSorts = ['judul', 'target', 'target_peran', 'is_active', 'created_at'];
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'created_at';
            }

            $query = Pengumuman::pengumuman();

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

            $query->orderBy($sort, $sortDir);
            $list = $query->paginate($perPage)->appends($request->query());
        }

        // Ringkasan statistik
        $summary = [
            'total_pengumuman' => Pengumuman::pengumuman()->count(),
            'aktif_pengumuman' => Pengumuman::pengumuman()->where('is_active', true)->count(),
            'total_broadcast'  => Pengumuman::broadcast()->count(),
            'aktif_broadcast'  => Pengumuman::broadcast()->where('is_active', true)->count(),
            'total_berita'     => Berita::count(),
            'aktif_berita'     => Berita::where('is_active', true)->count(),
        ];

        $kategoriList = ['Umum', 'Akademik', 'Kegiatan', 'Prestasi', 'Pengumuman'];

        return view('dashboard.pengumuman', compact(
            'list',
            'summary',
            'activeTab',
            'q',
            'targetFilter',
            'targetPeranFilter',
            'kategoriFilter',
            'kategoriList',
            'statusFilter',
            'perPage',
            'sort',
            'sortDir',
            'canCreate',
            'canUpdate',
            'canDelete'
        ));
    }

    /**
     * Halaman feed & portal informasi bagi pengguna (Guru, Tendik, Peserta Didik, Admin)
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

        $role = (string) data_get($user, 'role', 'peserta_didik');
        $userId = (string) data_get($user, 'pengguna_id', data_get($user, 'id', ''));

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

        $userId = (string) data_get($user, 'pengguna_id', data_get($user, 'id', ''));
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            return response()->json(['status' => 'error', 'message' => 'Pengumuman tidak ditemukan.'], 404);
        }

        $pengumuman->tandaiDibacaOleh($userId);

        // Hitung sisa unread untuk role pengguna saat ini
        $role = (string) data_get($user, 'role', 'peserta_didik');
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
        $userId = $user ? (string) data_get($user, 'pengguna_id', data_get($user, 'id', '')) : null;
        $role = $user ? (string) data_get($user, 'role', 'peserta_didik') : 'peserta_didik';

        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            return response()->json(['status' => 'error', 'message' => 'Pengumuman tidak ditemukan.'], 404);
        }

        // Pengguna non-admin hanya boleh melihat pengumuman aktif yang sesuai perannya
        if ($role !== 'admin') {
            if (!$pengumuman->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Pengumuman sudah tidak aktif.'], 404);
            }
            if ($pengumuman->target_peran !== 'semua' && $pengumuman->target_peran !== $role) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk melihat pengumuman ini.'], 403);
            }
        }

        if ($userId) {
            $pengumuman->tandaiDibacaOleh($userId);
        }

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
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401);
            }
            return redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menambah pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk menambah pengumuman.');
        }

        $tipe = $request->get('tipe', 'pengumuman');
        if (!in_array($tipe, ['pengumuman', 'broadcast'], true)) {
            $tipe = 'pengumuman';
        }

        $validated = $request->validate([
            'tipe' => 'nullable|string|in:pengumuman,broadcast',
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'target' => 'nullable|in:publik,pengguna,semua',
            'target_peran' => 'nullable|string|in:semua,admin,guru,tendik,peserta_didik',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $defaultAuthor = (string) data_get($user, 'name', data_get($user, 'nama', 'Staff'));
        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $defaultAuthor;

        $target = $tipe === 'broadcast' ? 'publik' : ($validated['target'] ?? 'pengguna');
        $targetPeran = $tipe === 'broadcast' ? 'semua' : ($validated['target_peran'] ?? 'semua');

        $pengumuman = Pengumuman::create([
            'tipe' => $tipe,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'target' => $target,
            'target_peran' => $targetPeran,
            'is_active' => $request->boolean('is_active', true),
            'penulis_nama' => $authorName,
        ]);

        \App\Services\RealtimeService::trigger('pengumuman.created', [
            'id'           => $pengumuman->id,
            'judul'        => $pengumuman->judul,
            'target'       => $pengumuman->target,
            'target_peran' => $pengumuman->target_peran,
            'penulis_nama' => $pengumuman->penulis_nama,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $tipe === 'broadcast' ? 'Info broadcast berhasil diterbitkan.' : 'Pengumuman berhasil diterbitkan.',
                'data' => $pengumuman,
            ]);
        }

        $redirectTab = $tipe === 'broadcast' ? 'broadcast' : 'pengumuman';
        return redirect()->route('dashboard.pengumuman.index', ['tab' => $redirectTab])
            ->with('success', $tipe === 'broadcast' ? 'Info broadcast berhasil diterbitkan.' : 'Pengumuman berhasil diterbitkan.');
    }

    /**
     * Perbarui pengumuman
     */
    public function update(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401);
            }
            return redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk mengubah pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk mengubah pengumuman.');
        }

        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Pengumuman tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Pengumuman tidak ditemukan.');
        }

        $tipe = $request->get('tipe', $pengumuman->tipe ?: 'pengumuman');
        if (!in_array($tipe, ['pengumuman', 'broadcast'], true)) {
            $tipe = 'pengumuman';
        }

        $validated = $request->validate([
            'tipe' => 'nullable|string|in:pengumuman,broadcast',
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'target' => 'nullable|in:publik,pengguna,semua',
            'target_peran' => 'nullable|string|in:semua,admin,guru,tendik,peserta_didik',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $pengumuman->penulis_nama;
        $target = $tipe === 'broadcast' ? 'publik' : ($validated['target'] ?? $pengumuman->target);
        $targetPeran = $tipe === 'broadcast' ? 'semua' : ($validated['target_peran'] ?? $pengumuman->target_peran);

        $pengumuman->update([
            'tipe' => $tipe,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'target' => $target,
            'target_peran' => $targetPeran,
            'penulis_nama' => $authorName,
            'is_active' => $request->boolean('is_active'),
        ]);

        \App\Services\RealtimeService::trigger('pengumuman.updated', [
            'id'    => $pengumuman->id,
            'judul' => $pengumuman->judul,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $tipe === 'broadcast' ? 'Info broadcast berhasil diperbarui.' : 'Pengumuman berhasil diperbarui.',
                'data' => $pengumuman,
            ]);
        }

        $redirectTab = $tipe === 'broadcast' ? 'broadcast' : 'pengumuman';
        return redirect()->route('dashboard.pengumuman.index', ['tab' => $redirectTab])
            ->with('success', $tipe === 'broadcast' ? 'Info broadcast berhasil diperbarui.' : 'Pengumuman berhasil diperbarui.');
    }

    /**
     * Toggle status aktif pengumuman (AJAX)
     */
    public function toggle(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401);
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            return response()->json(['status' => 'error', 'message' => 'Pengumuman tidak ditemukan.'], 404);
        }

        $pengumuman->is_active = !$pengumuman->is_active;
        $pengumuman->save();

        \App\Services\RealtimeService::trigger('pengumuman.updated', [
            'id'        => $pengumuman->id,
            'is_active' => (bool) $pengumuman->is_active,
        ]);

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
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401);
            }
            return redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menghapus pengumuman.'], 403);
            }
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus pengumuman.');
        }

        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Pengumuman tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Pengumuman tidak ditemukan.');
        }

        $deletedId = $pengumuman->id;
        $pengumuman->delete();

        \App\Services\RealtimeService::trigger('pengumuman.updated', [
            'id'      => $deletedId,
            'deleted' => true,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengumuman berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Pengumuman berhasil dihapus.');
    }

    /**
     * Simpan berita baru (Admin & Staff)
     */
    public function storeBerita(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401)
                : redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'create')) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menambah berita.'], 403)
                : back()->with('error', 'Anda tidak memiliki hak untuk menambah berita.');
        }

        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'kategori'     => 'required|string|max:100',
            'ringkasan'    => 'nullable|string',
            'isi'          => 'required|string',
            'gambar'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        $defaultAuthor = (string) data_get($user, 'name', data_get($user, 'nama', 'Humas Sekolah'));
        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $defaultAuthor;

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $uploadDir = public_path('uploads/berita');
            File::ensureDirectoryExists($uploadDir);
            $fileName = 'berita_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);
            $gambarPath = 'uploads/berita/' . $fileName;
        }

        $isActive = $request->boolean('is_active', false);
        $berita = Berita::create([
            'judul'        => $validated['judul'],
            'slug'         => Berita::createUniqueSlug($validated['judul']),
            'kategori'     => $validated['kategori'],
            'ringkasan'    => $validated['ringkasan'] ?? null,
            'isi'          => $validated['isi'],
            'gambar'       => $gambarPath,
            'penulis_nama' => $authorName,
            'is_active'    => $isActive,
            'published_at' => $isActive ? now() : null,
        ]);

        $statusMsg = $isActive ? 'Berita berhasil diterbitkan.' : 'Berita berhasil disimpan sebagai draft.';

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => $statusMsg,
                'data'    => $berita,
            ]);
        }

        return redirect()->route('dashboard.pengumuman.index', ['tab' => 'berita'])
            ->with('success', $statusMsg);
    }

    /**
     * Perbarui berita
     */
    public function updateBerita(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401)
                : redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'update')) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk mengubah berita.'], 403)
                : back()->with('error', 'Anda tidak memiliki hak untuk mengubah berita.');
        }

        $berita = Berita::find($id);
        if (!$berita) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404)
                : back()->with('error', 'Berita tidak ditemukan.');
        }

        $validated = $request->validate([
            'judul'        => 'required|string|max:255',
            'kategori'     => 'required|string|max:100',
            'ringkasan'    => 'nullable|string',
            'isi'          => 'required|string',
            'gambar'       => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:3072',
            'penulis_nama' => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
        ]);

        $authorName = !empty($validated['penulis_nama']) ? trim($validated['penulis_nama']) : $berita->penulis_nama;

        $gambarPath = $berita->gambar;
        if ($request->hasFile('gambar')) {
            $file = $request->file('gambar');
            $uploadDir = public_path('uploads/berita');
            File::ensureDirectoryExists($uploadDir);
            $fileName = 'berita_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $fileName);

            if ($gambarPath && File::exists(public_path($gambarPath))) {
                @unlink(public_path($gambarPath));
            }
            $gambarPath = 'uploads/berita/' . $fileName;
        } elseif ($request->boolean('hapus_gambar')) {
            if ($gambarPath && File::exists(public_path($gambarPath))) {
                @unlink(public_path($gambarPath));
            }
            $gambarPath = null;
        }

        $slug = $berita->slug;
        if ($berita->judul !== $validated['judul']) {
            $slug = Berita::createUniqueSlug($validated['judul'], $berita->id);
        }

        $isNowActive = $request->boolean('is_active');
        $publishedAt = $berita->published_at;
        if ($isNowActive && !$publishedAt) {
            $publishedAt = now();
        }

        $berita->update([
            'judul'        => $validated['judul'],
            'slug'         => $slug,
            'kategori'     => $validated['kategori'],
            'ringkasan'    => $validated['ringkasan'] ?? null,
            'isi'          => $validated['isi'],
            'gambar'       => $gambarPath,
            'penulis_nama' => $authorName,
            'is_active'    => $isNowActive,
            'published_at' => $publishedAt,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Berita berhasil diperbarui.',
                'data'    => $berita,
            ]);
        }

        return redirect()->route('dashboard.pengumuman.index', ['tab' => 'berita'])
            ->with('success', 'Berita berhasil diperbarui.');
    }

    /**
     * Toggle status aktif berita (AJAX)
     */
    public function toggleBerita(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401);
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $berita = Berita::find($id);
        if (!$berita) {
            return response()->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404);
        }

        $berita->is_active = !$berita->is_active;
        $berita->save();

        return response()->json([
            'status'    => 'success',
            'message'   => 'Status berita berhasil diubah.',
            'is_active' => (bool) $berita->is_active,
        ]);
    }

    /**
     * Hapus berita
     */
    public function destroyBerita(Request $request, int $id)
    {
        $user = session('user');
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Silakan masuk terlebih dahulu.'], 401)
                : redirect()->route('login');
        }

        if (!RolePermission::canAccess($user, 'menu_pengumuman', 'delete')) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menghapus berita.'], 403)
                : back()->with('error', 'Anda tidak memiliki hak untuk menghapus berita.');
        }

        $berita = Berita::find($id);
        if (!$berita) {
            return $request->expectsJson()
                ? response()->json(['status' => 'error', 'message' => 'Berita tidak ditemukan.'], 404)
                : back()->with('error', 'Berita tidak ditemukan.');
        }

        if ($berita->gambar && File::exists(public_path($berita->gambar))) {
            @unlink(public_path($berita->gambar));
        }

        $berita->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Berita berhasil dihapus.',
            ]);
        }

        return redirect()->route('dashboard.pengumuman.index', ['tab' => 'berita'])
            ->with('success', 'Berita berhasil dihapus.');
    }
}
