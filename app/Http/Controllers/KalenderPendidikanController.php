<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KalenderPendidikanController extends Controller
{
    private const SORTABLE = ['nama_kegiatan', 'tipe', 'mode_presensi', 'tanggal_mulai', 'tanggal_selesai'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }

        $role = is_array($user) ? ($user['role'] ?? 'peserta_didik') : ($user->role ?? 'peserta_didik');

        if (!RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'read')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Kalender Pendidikan dibatasi oleh Administrator.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'create');
        $canUpdate = RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'delete');

        $q            = trim($request->get('q', ''));
        $tipe         = trim($request->get('tipe', ''));
        $modePresensi = trim($request->get('mode_presensi', ''));
        $bulan        = trim($request->get('bulan', ''));
        $tahun        = trim($request->get('tahun', ''));
        $dampak       = trim($request->get('dampak', ''));
        $perPage      = (int) $request->get('perPage', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'tanggal_mulai';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $query = KalenderPendidikan::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_kegiatan', 'like', "%{$q}%")
                    ->orWhere('keterangan', 'like', "%{$q}%")
                    ->orWhere('created_by', 'like', "%{$q}%");
            });
        }

        if ($tipe !== '') {
            $query->where('tipe', $tipe);
        }

        if ($modePresensi !== '') {
            $query->where('mode_presensi', $modePresensi);
        }

        if ($bulan !== '') {
            $query->where(function ($sub) use ($bulan) {
                $sub->whereMonth('tanggal_mulai', $bulan)
                    ->orWhereMonth('tanggal_selesai', $bulan);
            });
        }

        if ($tahun !== '') {
            $query->where(function ($sub) use ($tahun) {
                $sub->whereYear('tanggal_mulai', $tahun)
                    ->orWhereYear('tanggal_selesai', $tahun);
            });
        }

        if ($dampak === 'libur_pd') {
            $query->where('libur_pd', true);
        } elseif ($dampak === 'libur_guru') {
            $query->where('libur_guru', true);
        } elseif ($dampak === 'libur_tendik') {
            $query->where('libur_tendik', true);
        } elseif ($dampak === 'libur_semua') {
            $query->where('libur_pd', true)->where('libur_guru', true)->where('libur_tendik', true);
        }

        // Summary counts
        $totalAgenda   = KalenderPendidikan::count();
        $totalLiburPd  = KalenderPendidikan::where('libur_pd', true)->count();
        $totalDaring   = KalenderPendidikan::where('mode_presensi', 'daring')->orWhere('tipe', 'pembelajaran_daring')->count();
        $totalLiburGtk = KalenderPendidikan::where(function ($q) {
            $q->where('libur_guru', true)->orWhere('libur_tendik', true);
        })->count();
        $totalUjian    = KalenderPendidikan::where('tipe', 'ujian_asesmen')->count();

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        return view('dashboard.kalender-pendidikan', compact(
            'list',
            'totalAgenda',
            'totalLiburPd',
            'totalDaring',
            'totalLiburGtk',
            'totalUjian',
            'canCreate',
            'canUpdate',
            'canDelete',
            'q',
            'tipe',
            'modePresensi',
            'bulan',
            'tahun',
            'dampak',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    public function getEvents(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$user || !RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $start = $request->get('start', now()->startOfMonth()->subMonth()->format('Y-m-d'));
        $end   = $request->get('end', now()->endOfMonth()->addMonth()->format('Y-m-d'));

        $events = KalenderPendidikan::betweenDates($start, $end)
            ->orderBy('tanggal_mulai')
            ->get()
            ->map(function ($ev) {
                return [
                    'id'                  => $ev->id,
                    'title'               => $ev->nama_kegiatan,
                    'start'               => $ev->tanggal_mulai->format('Y-m-d'),
                    'end'                 => $ev->tanggal_selesai->copy()->addDay()->format('Y-m-d'), // FullCalendar non-inclusive end
                    'raw_end'             => $ev->tanggal_selesai->format('Y-m-d'),
                    'color'               => $ev->warna ?: '#3b82f6',
                    'tipe'                => $ev->tipe,
                    'tipe_label'          => $ev->tipe_label,
                    'mode_presensi'       => $ev->mode_presensi,
                    'mode_presensi_label' => $ev->mode_presensi_label,
                    'mode_presensi_badge' => $ev->mode_presensi_badge,
                    'libur_pd'            => $ev->libur_pd,
                    'libur_guru'          => $ev->libur_guru,
                    'libur_tendik'        => $ev->libur_tendik,
                    'keterangan'          => $ev->keterangan,
                ];
            });

        return response()->json(['status' => 'success', 'data' => $events]);
    }

    public function store(Request $request)
    {
        $user = session('user');
        if (!$user || !RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Tidak memiliki izin menambah agenda kalender.'], 403);
            }
            return back()->with('error', 'Tidak memiliki izin menambah agenda kalender.');
        }

        $validated = $request->validate([
            'nama_kegiatan'   => 'required|string|max:200',
            'tipe'            => 'required|string|max:50',
            'mode_presensi'   => 'nullable|string|in:luring,daring,libur',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'warna'           => 'nullable|string|max:20',
            'keterangan'      => 'nullable|string|max:1000',
            'libur_pd'        => 'nullable|boolean',
            'libur_guru'      => 'nullable|boolean',
            'libur_tendik'    => 'nullable|boolean',
        ]);

        $userName = is_array($user) ? ($user['nama'] ?? ($user['username'] ?? 'User')) : ($user->nama ?? ($user->username ?? 'User'));

        $isLiburPd = (bool) ($request->input('libur_pd', false));
        $modePresensi = $validated['mode_presensi'] ?? ($isLiburPd ? 'libur' : ($validated['tipe'] === 'pembelajaran_daring' ? 'daring' : 'luring'));
        if ($isLiburPd && $modePresensi !== 'libur') {
            $modePresensi = 'libur';
        }

        $event = KalenderPendidikan::create([
            'nama_kegiatan'   => trim($validated['nama_kegiatan']),
            'tipe'            => $validated['tipe'],
            'mode_presensi'   => $modePresensi,
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'warna'           => !empty($validated['warna']) ? $validated['warna'] : '#3b82f6',
            'keterangan'      => $validated['keterangan'] ?? null,
            'libur_pd'        => $isLiburPd,
            'libur_guru'      => (bool) ($request->input('libur_guru', false)),
            'libur_tendik'    => (bool) ($request->input('libur_tendik', false)),
            'created_by'      => $userName,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Agenda kalender pendidikan berhasil ditambahkan.',
                'data'    => $event
            ]);
        }

        return back()->with('success', 'Agenda kalender pendidikan berhasil ditambahkan.');
    }

    public function show(Request $request, $id): JsonResponse
    {
        $user = session('user');
        if (!$user || !RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $event = KalenderPendidikan::find($id);
        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Data agenda tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                  => $event->id,
                'nama_kegiatan'       => $event->nama_kegiatan,
                'tipe'                => $event->tipe,
                'tipe_label'          => $event->tipe_label,
                'mode_presensi'       => $event->mode_presensi,
                'mode_presensi_label' => $event->mode_presensi_label,
                'mode_presensi_badge' => $event->mode_presensi_badge,
                'tanggal_mulai'       => $event->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai'     => $event->tanggal_selesai->format('Y-m-d'),
                'warna'               => $event->warna,
                'libur_pd'            => (bool) $event->libur_pd,
                'libur_guru'          => (bool) $event->libur_guru,
                'libur_tendik'        => (bool) $event->libur_tendik,
                'keterangan'          => $event->keterangan,
                'created_by'          => $event->created_by,
                'created_at'          => $event->created_at?->format('d M Y H:i'),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = session('user');
        if (!$user || !RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Tidak memiliki izin memperbarui agenda kalender.'], 403);
            }
            return back()->with('error', 'Tidak memiliki izin memperbarui agenda kalender.');
        }

        $event = KalenderPendidikan::find($id);
        if (!$event) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Data agenda tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Data agenda tidak ditemukan.');
        }

        $validated = $request->validate([
            'nama_kegiatan'   => 'required|string|max:200',
            'tipe'            => 'required|string|max:50',
            'mode_presensi'   => 'nullable|string|in:luring,daring,libur',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'warna'           => 'nullable|string|max:20',
            'keterangan'      => 'nullable|string|max:1000',
            'libur_pd'        => 'nullable|boolean',
            'libur_guru'      => 'nullable|boolean',
            'libur_tendik'    => 'nullable|boolean',
        ]);

        $isLiburPd = (bool) ($request->input('libur_pd', false));
        $modePresensi = $validated['mode_presensi'] ?? ($isLiburPd ? 'libur' : ($validated['tipe'] === 'pembelajaran_daring' ? 'daring' : 'luring'));
        if ($isLiburPd && $modePresensi !== 'libur') {
            $modePresensi = 'libur';
        }

        $event->update([
            'nama_kegiatan'   => trim($validated['nama_kegiatan']),
            'tipe'            => $validated['tipe'],
            'mode_presensi'   => $modePresensi,
            'tanggal_mulai'   => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'warna'           => !empty($validated['warna']) ? $validated['warna'] : $event->warna,
            'keterangan'      => $validated['keterangan'] ?? null,
            'libur_pd'        => $isLiburPd,
            'libur_guru'      => (bool) ($request->input('libur_guru', false)),
            'libur_tendik'    => (bool) ($request->input('libur_tendik', false)),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Agenda kalender pendidikan berhasil diperbarui.',
                'data'    => $event
            ]);
        }

        return back()->with('success', 'Agenda kalender pendidikan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $user = session('user');
        if (!$user || !RolePermission::canAccess($user, 'menu_kalender_pendidikan', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Tidak memiliki izin menghapus agenda kalender.'], 403);
            }
            return back()->with('error', 'Tidak memiliki izin menghapus agenda kalender.');
        }

        $event = KalenderPendidikan::find($id);
        if (!$event) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Data agenda tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Data agenda tidak ditemukan.');
        }

        $event->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Agenda kalender pendidikan berhasil dihapus.'
            ]);
        }

        return back()->with('success', 'Agenda kalender pendidikan berhasil dihapus.');
    }
}
