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

class PresensiMengajarController extends Controller
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
     * Dapatkan nama hari bahasa Indonesia dari objek Carbon / date string
     */
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
     * Halaman Utama Presensi Mengajar
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isGuru = ($role === 'guru');
        $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        // Evaluasi granular CRUD
        $canCreate = RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'create');
        $canRead   = RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'read');
        $canUpdate = RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'update');
        $canDelete = RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'delete');

        $hariIni = $this->getIndoDayName();
        $tanggalHariIni = Carbon::today()->toDateString();

        // 1. Ambil Jadwal Mengajar Hari Ini untuk Widget Cepat Guru
        $jadwalHariIniQuery = JadwalKbm::query()
            ->where('is_active', true)
            ->where('hari', $hariIni);

        if ($isGuru && $ptkId) {
            $jadwalHariIniQuery->where('ptk_id', $ptkId);
        }

        $jadwalHariIni = $jadwalHariIniQuery->orderBy('jam_ke_mulai', 'asc')->get();

        // Cari presensi yang sudah dicatat hari ini
        $presensiHariIniKeyed = PresensiMengajar::where('tanggal', $tanggalHariIni)
            ->when($isGuru && $ptkId, fn($q) => $q->where('ptk_id', $ptkId))
            ->get()
            ->keyBy(function ($item) {
                return $item->jadwal_kbm_id ? 'jadwal_' . $item->jadwal_kbm_id : 'rombel_' . $item->rombongan_belajar_id . '_' . $item->jam_ke_mulai;
            });

        // 2. Query Riwayat Presensi Mengajar dengan Filter & Sort
        $query = PresensiMengajar::query()->with('jadwal');

        if ($isGuru && $ptkId) {
            $query->where('ptk_id', $ptkId);
        } elseif ($request->filled('filter_ptk_id')) {
            $query->where('ptk_id', $request->filter_ptk_id);
        }

        // Filter tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        // Filter Rombel
        if ($request->filled('rombongan_belajar_id')) {
            $query->where('rombongan_belajar_id', $request->rombongan_belajar_id);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pencarian Kata Kunci
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_mata_pelajaran', 'like', "%{$q}%")
                    ->orWhere('keterangan', 'like', "%{$q}%")
                    ->orWhere('nama_guru_pengganti', 'like', "%{$q}%")
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

        if (in_array($sort, ['tanggal', 'jam_ke_mulai', 'status', 'nama_mata_pelajaran', 'total_jp'])) {
            $query->orderBy($sort, $sortDir);
            if ($sort === 'tanggal') {
                $query->orderBy('jam_ke_mulai', 'asc');
            }
        } else {
            $query->orderBy('tanggal', 'desc')->orderBy('jam_ke_mulai', 'asc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
        $items = $query->paginate($perPage)->withQueryString();

        // 3. Ringkasan Statistik
        $statsBaseQuery = PresensiMengajar::query();
        if ($isGuru && $ptkId) {
            $statsBaseQuery->where('ptk_id', $ptkId);
        }
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $stats = [
            'total_sesi'    => (clone $statsBaseQuery)->whereMonth('tanggal', $currentMonth)->whereYear('tanggal', $currentYear)->count(),
            'total_hadir'   => (clone $statsBaseQuery)->whereMonth('tanggal', $currentMonth)->whereYear('tanggal', $currentYear)->where('status', 'H')->count(),
            'total_izin'    => (clone $statsBaseQuery)->whereMonth('tanggal', $currentMonth)->whereYear('tanggal', $currentYear)->whereIn('status', ['I', 'S'])->count(),
            'total_inval'   => (clone $statsBaseQuery)->whereMonth('tanggal', $currentMonth)->whereYear('tanggal', $currentYear)->whereIn('status', ['T', 'D'])->count(),
            'total_jp'      => (clone $statsBaseQuery)->whereMonth('tanggal', $currentMonth)->whereYear('tanggal', $currentYear)->where('status', 'H')->sum('total_jp'),
        ];

        // 4. Data Master untuk Dropdown Modal & Filter
        $jadwalQuery = JadwalKbm::where('is_active', true);
        if ($isGuru && $ptkId) {
            $jadwalQuery->where('ptk_id', $ptkId);
        }
        $jadwalList = $jadwalQuery->orderBy('hari')->orderBy('jam_ke_mulai')->get()->map(function ($j) {
            $rombelNama = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $j->rombongan_belajar_id)->value('nama') ?? $j->rombongan_belajar_id;
            return [
                'id' => $j->id,
                'hari' => $j->hari,
                'jam_ke_mulai' => $j->jam_ke_mulai,
                'jam_ke_selesai' => $j->jam_ke_selesai,
                'jam_mulai' => $j->jam_mulai,
                'jam_selesai' => $j->jam_selesai,
                'durasi_jp' => $j->durasi_jp,
                'rombongan_belajar_id' => $j->rombongan_belajar_id,
                'rombel_nama' => $rombelNama,
                'nama_mata_pelajaran' => $j->nama_mata_pelajaran,
                'pembelajaran_id' => $j->pembelajaran_id,
                'mata_pelajaran_id' => $j->mata_pelajaran_id,
                'ptk_id' => $j->ptk_id,
                'ruangan' => $j->ruangan,
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

        // Jika request asinkron AJAX (Live Search / Filter / Sorting Table)
        if ($request->ajax() && $request->has('ajax_table')) {
            return view('dashboard.presensi-mengajar-table', compact(
                'items', 'canUpdate', 'canDelete', 'sort', 'sortDir'
            ));
        }

        return view('dashboard.presensi-mengajar', compact(
            'stats',
            'items',
            'jadwalHariIni',
            'presensiHariIniKeyed',
            'jadwalList',
            'rombelList',
            'guruList',
            'isGuru',
            'ptkId',
            'hariIni',
            'tanggalHariIni',
            'canCreate',
            'canRead',
            'canUpdate',
            'canDelete',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Simpan Presensi Mengajar Baru (Create)
     */
    public function store(Request $request)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isGuru = ($role === 'guru');
        $sessionPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);

        if (!RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'create')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mencatat presensi.'], 403);
            }
            return back()->with('error', 'Akses ditolak: Anda tidak memiliki hak akses untuk mencatat presensi.');
        }

        $validated = $request->validate([
            'jadwal_kbm_id'         => 'nullable|integer',
            'ptk_id'                => 'nullable|string|max:50',
            'rombongan_belajar_id'  => 'required|string|max:50',
            'nama_mata_pelajaran'   => 'required|string|max:150',
            'pembelajaran_id'       => 'nullable|string|max:50',
            'mata_pelajaran_id'     => 'nullable|string|max:50',
            'tanggal'               => 'required|date',
            'hari'                  => 'nullable|string|max:20',
            'jam_ke_mulai'          => 'required|integer|min:0|max:20',
            'jam_ke_selesai'        => 'required|integer|min:0|max:20',
            'status'                => 'required|string|in:H,I,S,T,D',
            'jam_masuk'             => 'nullable|string',
            'jam_keluar'            => 'nullable|string',
            'jumlah_siswa_hadir'    => 'nullable|integer|min:0',
            'jumlah_siswa_tidak_hadir' => 'nullable|integer|min:0',
            'guru_pengganti_ptk_id' => 'nullable|string|max:50',
            'nama_guru_pengganti'   => 'nullable|string|max:150',
            'keterangan'            => 'nullable|string|max:500',
        ]);

        $finalPtkId = ($isGuru && $sessionPtkId) ? $sessionPtkId : ($validated['ptk_id'] ?? $sessionPtkId);
        if (!$finalPtkId) {
            return back()->with('error', 'PTK / Guru pengampu KBM tidak valid atau belum terikat.');
        }

        $hari = $validated['hari'] ?: $this->getIndoDayName($validated['tanggal']);
        $totalJp = max(1, (int) $validated['jam_ke_selesai'] - (int) $validated['jam_ke_mulai'] + 1);

        // Cek duplikasi presensi untuk guru, rombel, tanggal, dan jam_ke_mulai yang sama
        $exists = PresensiMengajar::where('ptk_id', $finalPtkId)
            ->where('rombongan_belajar_id', $validated['rombongan_belajar_id'])
            ->where('tanggal', $validated['tanggal'])
            ->where('jam_ke_mulai', $validated['jam_ke_mulai'])
            ->first();

        if ($exists) {
            $msg = 'Presensi mengajar untuk kelas dan jam tersebut pada tanggal ini sudah pernah dicatat.';
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $presensi = PresensiMengajar::create([
            'jadwal_kbm_id'         => $validated['jadwal_kbm_id'] ?? null,
            'ptk_id'                => $finalPtkId,
            'rombongan_belajar_id'  => $validated['rombongan_belajar_id'],
            'pembelajaran_id'       => $validated['pembelajaran_id'] ?? null,
            'mata_pelajaran_id'     => $validated['mata_pelajaran_id'] ?? null,
            'nama_mata_pelajaran'   => $validated['nama_mata_pelajaran'],
            'tanggal'               => $validated['tanggal'],
            'hari'                  => $hari,
            'jam_ke_mulai'          => $validated['jam_ke_mulai'],
            'jam_ke_selesai'        => $validated['jam_ke_selesai'],
            'total_jp'              => $totalJp,
            'status'                => $validated['status'],
            'jam_masuk'             => !empty($validated['jam_masuk']) ? $validated['jam_masuk'] : Carbon::now()->format('H:i:s'),
            'jam_keluar'            => $validated['jam_keluar'] ?? null,
            'jumlah_siswa_hadir'    => $validated['jumlah_siswa_hadir'] ?? null,
            'jumlah_siswa_tidak_hadir' => $validated['jumlah_siswa_tidak_hadir'] ?? null,
            'guru_pengganti_ptk_id' => $validated['guru_pengganti_ptk_id'] ?? null,
            'nama_guru_pengganti'   => $validated['nama_guru_pengganti'] ?? null,
            'keterangan'            => $validated['keterangan'] ?? null,
            'created_by'            => is_array($user) ? ($user['username'] ?? 'guru') : ($user->username ?? 'guru'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Presensi mengajar berhasil dicatat.',
                'data'    => $presensi,
            ]);
        }

        return back()->with('success', 'Presensi mengajar berhasil disimpan.');
    }

    /**
     * Tampilkan Detail Presensi Mengajar (Modal Detail)
     */
    public function show(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'read')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $presensi = PresensiMengajar::with(['jadwal', 'agenda'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                   => $presensi->id,
                'jadwal_kbm_id'        => $presensi->jadwal_kbm_id,
                'ptk_id'               => $presensi->ptk_id,
                'nama_guru'            => $presensi->nama_guru,
                'rombongan_belajar_id' => $presensi->rombongan_belajar_id,
                'nama_rombel'          => $presensi->nama_rombel,
                'nama_mata_pelajaran'  => $presensi->nama_mata_pelajaran,
                'pembelajaran_id'      => $presensi->pembelajaran_id,
                'tanggal'              => $presensi->tanggal ? $presensi->tanggal->format('Y-m-d') : null,
                'tanggal_formatted'    => $presensi->tanggal ? $presensi->tanggal->translatedFormat('d F Y') : '-',
                'hari'                 => $presensi->hari,
                'jam_ke_mulai'         => $presensi->jam_ke_mulai,
                'jam_ke_selesai'       => $presensi->jam_ke_selesai,
                'total_jp'             => $presensi->total_jp,
                'status'               => $presensi->status,
                'status_label'         => $presensi->status_label,
                'status_badge'         => $presensi->status_badge,
                'jam_masuk'            => $presensi->jam_masuk,
                'jam_keluar'           => $presensi->jam_keluar,
                'jumlah_siswa_hadir'   => $presensi->jumlah_siswa_hadir,
                'jumlah_siswa_tidak_hadir' => $presensi->jumlah_siswa_tidak_hadir,
                'nama_guru_pengganti'  => $presensi->nama_guru_pengganti,
                'keterangan'           => $presensi->keterangan,
                'agenda'               => $presensi->agenda ? [
                    'id'              => $presensi->agenda->id,
                    'pertemuan_ke'    => $presensi->agenda->pertemuan_ke,
                    'materi_pokok'    => $presensi->agenda->materi_pokok,
                    'status_kbm'      => $presensi->agenda->status_kbm,
                ] : null,
            ],
        ]);
    }

    /**
     * Update Presensi Mengajar
     */
    public function update(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'update')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $presensi = PresensiMengajar::findOrFail($id);

        $validated = $request->validate([
            'status'                   => 'required|string|in:H,I,S,T,D',
            'jam_masuk'                => 'nullable|string',
            'jam_keluar'               => 'nullable|string',
            'jumlah_siswa_hadir'       => 'nullable|integer|min:0',
            'jumlah_siswa_tidak_hadir' => 'nullable|integer|min:0',
            'guru_pengganti_ptk_id'    => 'nullable|string|max:50',
            'nama_guru_pengganti'      => 'nullable|string|max:150',
            'keterangan'               => 'nullable|string|max:500',
        ]);

        $presensi->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Presensi mengajar berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Presensi mengajar berhasil diperbarui.');
    }

    /**
     * Hapus Presensi Mengajar
     */
    public function destroy(Request $request, $id)
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::canAccess($user ?: $role, 'menu_presensi_mengajar', 'delete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
            }
            return back()->with('error', 'Akses ditolak.');
        }

        $presensi = PresensiMengajar::findOrFail($id);

        // Jika ada agenda KBM yang terhubung, lepaskan relasi
        AgendaKbm::where('presensi_mengajar_id', $presensi->id)->update(['presensi_mengajar_id' => null]);

        $presensi->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi mengajar berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Data presensi mengajar berhasil dihapus.');
    }
}