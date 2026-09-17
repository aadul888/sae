<?php

namespace App\Http\Controllers;

use App\Models\AgendaKbm;
use App\Models\JadwalKbm;
use App\Models\PresensiMengajar;
use App\Models\RolePermission;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaKbmController extends Controller
{
    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    private function getIndoDayName($date = null): string
    {
        $c = $date ? Carbon::parse($date) : Carbon::now();
        $map = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        return $map[$c->format('l')] ?? 'Senin';
    }

    /**
     * Tampilkan halaman utama modul Jurnal & Agenda KBM.
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isGuru = ($role === 'guru');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        // Evaluasi granular 4 hak akses CRUD
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'delete');

        // Query Jurnal & Agenda KBM
        $query = AgendaKbm::query()->with(['jadwal', 'presensiMengajar']);

        if ($isGuru && $ptkId) {
            $query->where('ptk_id', $ptkId);
        } elseif ($request->filled('filter_ptk_id')) {
            $query->where('ptk_id', $request->filter_ptk_id);
        }

        // Filter Rombel
        if ($request->filled('rombongan_belajar_id')) {
            $query->where('rombongan_belajar_id', $request->rombongan_belajar_id);
        }

        // Filter Status KBM
        if ($request->filled('status_kbm')) {
            $query->where('status_kbm', $request->status_kbm);
        }

        // Filter Tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        // Filter Search
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('materi_pokok', 'like', "%{$q}%")
                    ->orWhere('nama_mata_pelajaran', 'like', "%{$q}%")
                    ->orWhere('uraian_kegiatan', 'like', "%{$q}%")
                    ->orWhere('penugasan', 'like', "%{$q}%")
                    ->orWhereIn('rombongan_belajar_id', function ($rQuery) use ($q) {
                        $rQuery->select('rombongan_belajar_id')
                            ->from('rombongan_belajar')
                            ->where('nama', 'like', "%{$q}%");
                    });
            });
        }

        // Sorting
        $sort = $request->input('sort', 'tanggal');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, ['tanggal', 'pertemuan_ke', 'nama_mata_pelajaran', 'status_kbm'])) {
            $query->orderBy($sort, $sortDir);
            if ($sort === 'tanggal') {
                $query->orderBy('pertemuan_ke', 'desc');
            }
        } else {
            $query->orderBy('tanggal', 'desc')->orderBy('pertemuan_ke', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
        $items = $query->paginate($perPage)->withQueryString();

        // Statistik Counter
        $statsBase = AgendaKbm::query();
        if ($isGuru && $ptkId) {
            $statsBase->where('ptk_id', $ptkId);
        }
        $stats = [
            'total'      => (clone $statsBase)->count(),
            'terlaksana' => (clone $statsBase)->where('status_kbm', 'Terlaksana')->count(),
            'sebagian'   => (clone $statsBase)->where('status_kbm', 'Sebagian')->count(),
            'tertunda'   => (clone $statsBase)->whereIn('status_kbm', ['Tertunda', 'Digantikan'])->count(),
        ];

        // Daftar Jadwal Guru untuk Form Modal (Kecualikan PKL untuk guru)
        $jadwalQuery = JadwalKbm::where('is_active', true);
        if ($isGuru) {
            $jadwalQuery->excludePkl();
            if ($ptkId) {
                $jadwalQuery->where('ptk_id', $ptkId);
            }
        }
        $jadwalList = $jadwalQuery->orderBy('hari')->orderBy('jam_ke_mulai')->get()->map(function ($j) {
            $rombelNama = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
            return [
                'id' => $j->id,
                'hari' => $j->hari,
                'jam_ke_mulai' => $j->jam_ke_mulai,
                'jam_ke_selesai' => $j->jam_ke_selesai,
                'rombongan_belajar_id' => $j->rombongan_belajar_id,
                'rombel_nama' => $rombelNama,
                'nama_mata_pelajaran' => $j->nama_mata_pelajaran,
                'pembelajaran_id' => $j->pembelajaran_id,
                'mata_pelajaran_id' => $j->mata_pelajaran_id,
                'ptk_id' => $j->ptk_id,
            ];
        });

        $rombelList = DB::table('rombongan_belajar')
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
            ->orderBy('tingkat_pendidikan_id')
            ->orderBy('nama')
            ->get();

        $guruList = [];
        if (!$isGuru) {
            $guruList = DB::table('gtk')
                ->select('ptk_id', 'nama', 'nip')
                ->orderBy('nama')
                ->get();
        }

        // Response AJAX partial datatable
        if ($request->ajax() && $request->has('ajax_table')) {
            return view('dashboard.agenda-kbm-table', compact(
                'items', 'canUpdate', 'canDelete', 'sort', 'sortDir'
            ));
        }

        return view('dashboard.agenda-kbm', compact(
            'stats',
            'items',
            'jadwalList',
            'rombelList',
            'guruList',
            'isGuru',
            'ptkId',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Hitung Pertemuan Ke Berikutnya via AJAX
     */
    public function getNextPertemuan(Request $request): JsonResponse
    {
        $rombelId = $request->input('rombongan_belajar_id');
        $pembelajaranId = $request->input('pembelajaran_id');

        if (!$rombelId) {
            return response()->json(['status' => 'error', 'next_pertemuan' => 1]);
        }

        $next = AgendaKbm::getNextPertemuanKe($rombelId, $pembelajaranId);
        return response()->json(['status' => 'success', 'next_pertemuan' => $next]);
    }

    /**
     * Simpan data Agenda KBM baru (Create).
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isGuru = ($role === 'guru');
        $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        if (!RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah agenda KBM.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $validated = $request->validate([
            'jadwal_kbm_id'         => 'nullable|integer',
            'presensi_mengajar_id'  => 'nullable|integer',
            'ptk_id'                => 'nullable|string|max:50',
            'rombongan_belajar_id'  => 'required|string|max:50',
            'nama_mata_pelajaran'   => 'required|string|max:150',
            'pembelajaran_id'       => 'nullable|string|max:50',
            'mata_pelajaran_id'     => 'nullable|string|max:50',
            'tanggal'               => 'required|date',
            'hari'                  => 'nullable|string|max:20',
            'jam_ke_mulai'          => 'required|integer|min:0|max:20',
            'jam_ke_selesai'        => 'required|integer|min:0|max:20',
            'pertemuan_ke'          => 'required|integer|min:1|max:100',
            'materi_pokok'          => 'required|string|max:255',
            'uraian_kegiatan'       => 'required|string',
            'penugasan'             => 'nullable|string',
            'status_kbm'            => 'required|string|in:Terlaksana,Sebagian,Tertunda,Digantikan',
            'hambatan_catatan'      => 'nullable|string',
        ]);

        $finalPtkId = ($isGuru && $sessionPtkId) ? $sessionPtkId : ($validated['ptk_id'] ?? $sessionPtkId);
        if (!$finalPtkId) {
            return back()->with('error', 'PTK / Guru pengampu KBM tidak valid atau belum terikat.');
        }

        $hari = $validated['hari'] ?: $this->getIndoDayName($validated['tanggal']);

        // Jika tidak ada presensi_mengajar_id yang dikirim, cari apakah ada presensi mengajar guru yang cocok pada tanggal & rombel tersebut
        $presensiId = $validated['presensi_mengajar_id'] ?? null;
        if (!$presensiId) {
            $matchedPresensi = PresensiMengajar::where('ptk_id', $finalPtkId)
                ->where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
                ->where('tanggal', $validated['tanggal'])
                ->first();
            if ($matchedPresensi) {
                $presensiId = $matchedPresensi->id;
            }
        }

        $agenda = AgendaKbm::create([
            'presensi_mengajar_id' => $presensiId,
            'jadwal_kbm_id'        => $validated['jadwal_kbm_id'] ?? null,
            'ptk_id'               => $finalPtkId,
            'rombongan_belajar_id' => $validated['rombongan_belajar_id'],
            'pembelajaran_id'      => $validated['pembelajaran_id'] ?? null,
            'mata_pelajaran_id'    => $validated['mata_pelajaran_id'] ?? null,
            'nama_mata_pelajaran'  => $validated['nama_mata_pelajaran'],
            'tanggal'              => $validated['tanggal'],
            'hari'                 => $hari,
            'jam_ke_mulai'         => $validated['jam_ke_mulai'],
            'jam_ke_selesai'       => $validated['jam_ke_selesai'],
            'pertemuan_ke'         => $validated['pertemuan_ke'],
            'materi_pokok'         => $validated['materi_pokok'],
            'uraian_kegiatan'      => $validated['uraian_kegiatan'],
            'penugasan'            => $validated['penugasan'] ?? null,
            'status_kbm'           => $validated['status_kbm'],
            'hambatan_catatan'     => $validated['hambatan_catatan'] ?? null,
            'created_by'           => is_array($user) ? ($user['username'] ?? 'guru') : ($user->username ?? 'guru'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Jurnal & Agenda KBM berhasil disimpan.',
                'data'    => $agenda,
            ]);
        }

        return back()->with('success', 'Jurnal & Agenda KBM berhasil disimpan.');
    }

    /**
     * Tampilkan detail data (Read / Detail).
     */
    public function show(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $agenda = AgendaKbm::with(['jadwal', 'presensiMengajar'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                   => $agenda->id,
                'jadwal_kbm_id'        => $agenda->jadwal_kbm_id,
                'presensi_mengajar_id' => $agenda->presensi_mengajar_id,
                'ptk_id'               => $agenda->ptk_id,
                'nama_guru'            => $agenda->nama_guru,
                'rombongan_belajar_id' => $agenda->rombongan_belajar_id,
                'nama_rombel'          => $agenda->nama_rombel,
                'nama_mata_pelajaran'  => $agenda->nama_mata_pelajaran,
                'tanggal'              => $agenda->tanggal ? $agenda->tanggal->format('Y-m-d') : null,
                'tanggal_formatted'    => $agenda->tanggal ? $agenda->tanggal->translatedFormat('d F Y') : '-',
                'hari'                 => $agenda->hari,
                'jam_ke_mulai'         => $agenda->jam_ke_mulai,
                'jam_ke_selesai'       => $agenda->jam_ke_selesai,
                'pertemuan_ke'         => $agenda->pertemuan_ke,
                'materi_pokok'         => $agenda->materi_pokok,
                'uraian_kegiatan'      => $agenda->uraian_kegiatan,
                'penugasan'            => $agenda->penugasan,
                'status_kbm'           => $agenda->status_kbm,
                'status_badge'         => $agenda->status_badge,
                'hambatan_catatan'     => $agenda->hambatan_catatan,
            ],
        ]);
    }

    /**
     * Update data Agenda KBM.
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $agenda = AgendaKbm::findOrFail($id);

        $validated = $request->validate([
            'pertemuan_ke'     => 'required|integer|min:1|max:100',
            'materi_pokok'     => 'required|string|max:255',
            'uraian_kegiatan'  => 'required|string',
            'penugasan'        => 'nullable|string',
            'status_kbm'       => 'required|string|in:Terlaksana,Sebagian,Tertunda,Digantikan',
            'hambatan_catatan' => 'nullable|string',
        ]);

        $agenda->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Jurnal & Agenda KBM berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Jurnal & Agenda KBM berhasil diperbarui.');
    }

    /**
     * Hapus data Agenda KBM.
     */
    public function destroy(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $agenda = AgendaKbm::findOrFail($id);
        $agenda->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Data agenda KBM berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data agenda KBM berhasil dihapus.');
    }

    /**
     * Cetak Jurnal Agenda KBM Guru
     */
    public function cetak(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isGuru = ($role === 'guru');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        if (!RolePermission::canAccess($user ?: $role, 'menu_agenda_kbm', 'read')) {
            abort(403, 'Akses ditolak');
        }

        $query = AgendaKbm::query();
        if ($isGuru && $ptkId) {
            $query->where('ptk_id', $ptkId);
        } elseif ($request->filled('filter_ptk_id')) {
            $query->where('ptk_id', $request->filter_ptk_id);
        }

        if ($request->filled('rombongan_belajar_id')) {
            $query->where('rombongan_belajar_id', $request->rombongan_belajar_id);
        }

        $items = $query->orderBy('tanggal', 'asc')->orderBy('pertemuan_ke', 'asc')->get();

        $sekolah = DB::table('sekolah')->first();
        $kepalaSekolah = DB::table('gtk')
            ->where(function ($w) {
                $w->where('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })->first();
        $targetPtkId = ($isGuru && $ptkId) ? $ptkId : $request->filter_ptk_id;
        $guru = $targetPtkId ? DB::table('gtk')->where('ptk_id', $targetPtkId)->first() : null;
        $rombel = $request->filled('rombongan_belajar_id') ? DB::table('rombongan_belajar')->where('rombongan_belajar_id', $request->rombongan_belajar_id)->first() : null;

        return view('dashboard.agenda-kbm-cetak', compact('items', 'sekolah', 'guru', 'rombel', 'kepalaSekolah'));
    }
}