<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RolePermission;
use App\Models\PesertaDidikMeta;
use App\Models\PresensiHarian;
use App\Models\PresensiPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\PresensiIzin;
use App\Models\NotifikasiTransaksi;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikIdentitas;
use App\Models\AgendaKbm;
use App\Models\JadwalKbm;
use App\Models\JadwalPengaturan;
use App\Models\PresensiMengajar;
use App\Services\WaliKelasAgendaService;
use App\Services\WaliKelasPresensiService;
use App\Services\WaliKelasValidasiService;
use Carbon\Carbon;


use App\Support\Table;

class WaliKelasController extends Controller
{
    private const SORTABLE_AKTIF = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel', 'tingkat_pendidikan_id'];
    private const SORTABLE_TIDAK_AKTIF = ['nama', 'nisn', 'nipd', 'jenis_kelamin', 'nama_rombel_terakhir', 'tingkat_pendidikan_terakhir', 'tahun_lulus', 'status_keluar'];

    private function getIndoDayName($date = null): string
    {
        $c = $date ? Carbon::parse($date) : Carbon::now();
        return match ($c->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };
    }

    /**
     * Resolusi konteks Rombel Binaan Wali Kelas
     */
    private function resolveWaliKelasContext(Request $request, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');
        $isPiket = RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_agenda');
        $isKoordinator = ($role === 'peserta_didik') && RolePermission::isKoordinator($user);
        $canSwitchRombel = $isAdmin || $isPiket;

        if ($canSwitchRombel) {
            $rombelList = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->orderBy('tingkat_pendidikan_id', 'asc')
                ->orderBy('nama', 'asc')
                ->select(
                    'rombongan_belajar_id',
                    'nama',
                    'tingkat_pendidikan_id_str',
                    'jurusan_id_str',
                    'ptk_id',
                    'ptk_id_str'
                )
                ->get();

            $myRombelInfo = RolePermission::getWaliKelasRombelInfo($user);
            $defaultRombelId = $myRombelInfo?->rombongan_belajar_id ?? ($rombelList->first()?->rombongan_belajar_id ?? '');
            $selectedId = $request->get('rombel_id', $defaultRombelId);
            $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedId) ?: $rombelList->first();

            $waliNama = $activeRombel?->ptk_id_str ?: ($isAdmin ? 'Administrator' : 'Petugas Piket');
            $waliGtk = null;
            if ($activeRombel?->ptk_id) {
                $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombel->ptk_id)->first();
            }
            if (!$waliGtk && $activeRombel?->ptk_id_str) {
                $waliGtk = DB::table('gtk')->where('nama', $activeRombel->ptk_id_str)->first();
            }
            $waliNip = $waliGtk?->nip ?: ($waliGtk?->nuptk ?: '—');

            return [
                'isAdmin'         => $isAdmin,
                'isPiket'         => $isPiket,
                'isKoordinator'   => $isKoordinator,
                'isGuru'          => ($role === 'guru'),
                'canSwitchRombel' => true,
                'rombelList'      => $rombelList,
                'activeRombel'    => $activeRombel,
                'waliNama'        => $waliNama,
                'waliNip'         => $waliNip,
            ];
        }

        // Pengguna Peran Guru (Wali Kelas) atau Peserta Didik (Koordinator Kelas)
        $activeRombel = RolePermission::getWaliKelasRombelInfo($user);
        $waliNama = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? 'Guru')) : ($user->name ?? ($user->nama ?? 'Guru'));

        $waliGtk = null;
        if (!empty($activeRombel?->ptk_id)) {
            $waliGtk = DB::table('gtk')->where('ptk_id', $activeRombel->ptk_id)->first();
        }
        $userPtkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
        if (!$waliGtk && $userPtkId) {
            $waliGtk = DB::table('gtk')->where('ptk_id', $userPtkId)->first();
        }
        if (!$waliGtk && $activeRombel?->ptk_id_str) {
            $waliGtk = DB::table('gtk')->where('nama', $activeRombel->ptk_id_str)->first();
        }
        $waliNip = $waliGtk?->nip ?: ($waliGtk?->nuptk ?: (is_array($user) ? ($user['nip'] ?? ($user['nuptk'] ?? '—')) : ($user->nip ?? ($user->nuptk ?? '—'))));

        return [
            'isAdmin'         => false,
            'isPiket'         => false,
            'isKoordinator'   => $isKoordinator,
            'isGuru'          => ($role === 'guru'),
            'canSwitchRombel' => false,
            'rombelList'      => $activeRombel ? collect([$activeRombel]) : collect(),
            'activeRombel'    => $activeRombel,
            'waliNama'        => $activeRombel?->ptk_id_str ?: $waliNama,
            'waliNip'         => $waliNip,
        ];
    }

    /**
     * Tampilan Peserta Didik Aktif Kelas Binaan
     */
    public function pesertaDidikAktif(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Wali Kelas tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_wali_kelas_aktif', 'delete');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $q       = trim($request->get('q', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE_AKTIF, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        // Jika guru tidak memiliki rombel binaan aktif
        if (!$activeRombel) {
            return view('dashboard.wali-kelas-aktif', [
                'hasRombel'    => false,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => null,
                'waliNama'     => $ctx['waliNama'],
                'list'         => collect(),
                'total'        => 0,
                'summary'      => ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'berfoto' => 0],
                'q'            => $q,
                'gender'       => $gender,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canCreate'    => $canCreate,
                'canRead'      => $canRead,
                'canUpdate'    => $canUpdate,
                'canDelete'    => $canDelete,
            ]);
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;
        $targetRombelName = $activeRombel->nama ?? null;

        $baseQuery = DB::table('peserta_didik as pd')
            ->where(function ($w) use ($targetRombelId, $targetRombelName) {
                if ($targetRombelId) {
                    $w->where('pd.rombongan_belajar_id', $targetRombelId);
                }
                if ($targetRombelName) {
                    $w->orWhere('pd.nama_rombel', $targetRombelName);
                }
            });

        // Hitung Statistik Kelas Binaan
        $totalAll = (clone $baseQuery)->count();
        $totalLaki = (clone $baseQuery)->where('pd.jenis_kelamin', 'L')->count();
        $totalPerempuan = (clone $baseQuery)->where('pd.jenis_kelamin', 'P')->count();

        // Hitung siswa yang sudah memiliki foto
        $activePdIds = (clone $baseQuery)->pluck('pd.peserta_didik_id')->toArray();
        $totalBerfoto = 0;
        if (!empty($activePdIds) && Table::exists('peserta_didik_meta')) {
            $totalBerfoto = DB::table('peserta_didik_meta')
                ->whereIn('peserta_didik_id', $activePdIds)
                ->whereNotNull('foto_path')
                ->where('foto_path', '<>', '')
                ->count();
        }

        $summary = [
            'total'     => $totalAll,
            'laki'      => $totalLaki,
            'perempuan' => $totalPerempuan,
            'berfoto'   => $totalBerfoto,
        ];

        // Query tabel dengan filter
        $query = clone $baseQuery;
        $query->select(
            'pd.peserta_didik_id',
            'pd.nama',
            'pd.nisn',
            'pd.nipd',
            'pd.nik',
            'pd.jenis_kelamin',
            'pd.tempat_lahir',
            'pd.tanggal_lahir',
            'pd.agama_id_str as agama',
            'pd.nama_rombel',
            'pd.rombongan_belajar_id',
            'pd.tingkat_pendidikan_id',
            'pd.nama_ayah',
            'pd.nama_ibu',
            'pd.nama_wali',
            'pd.alamat_jalan',
            'pd.nomor_telepon_seluler as no_hp',
            'pd.email'
        );

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });
        }

        if ($gender !== '') {
            $query->where('pd.jenis_kelamin', $gender);
        }

        // Urutkan nama secara case-insensitive & trimmed
        if ($sort === 'nama') {
            $query->orderByRaw("LOWER(TRIM(pd.nama)) {$sortDir}")->orderBy('pd.nisn', 'asc');
        } else {
            $query->orderBy($sort, $sortDir)->orderByRaw("LOWER(TRIM(pd.nama)) asc");
        }
        $list = $query->paginate($perPage)->appends($request->query());

        // Hubungkan metadata foto persisten
        if (Table::exists('peserta_didik_meta') && $list->isNotEmpty()) {
            $pageIds = $list->pluck('peserta_didik_id')->toArray();
            $metaMap = PesertaDidikMeta::whereIn('peserta_didik_id', $pageIds)
                ->get()
                ->keyBy('peserta_didik_id');

            foreach ($list as $item) {
                $meta = $metaMap->get($item->peserta_didik_id);
                $item->foto_url = $meta?->foto_url;
                $item->foto_size = $meta?->formatted_foto_size;
                $item->is_koordinator = (bool) ($meta?->is_koordinator ?? false);
                $item->jabatan_koordinator = $meta?->jabatan_koordinator ?? 'Koordinator Kelas';
            }
        }

        return view('dashboard.wali-kelas-aktif', [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'list'         => $list,
            'total'        => $list->total(),
            'summary'      => $summary,
            'q'            => $q,
            'gender'       => $gender,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canCreate'    => $canCreate,
            'canRead'      => $canRead,
            'canUpdate'    => $canUpdate,
            'canDelete'    => $canDelete,
        ]);
    }

    /**
     * Tampilan Modul Validasi Data Peserta Didik (Konfirmasi & Status Berkas)
     */
    public function validasiData(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || (!RolePermission::canAccess($user, 'menu_wali_kelas_validasi') && !RolePermission::canAccess($user, 'menu_wali_kelas'))) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Validasi Data Wali Kelas tidak diizinkan.');
        }

        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_validasi', 'update')
            || RolePermission::canAccess($user, 'menu_wali_kelas', 'update')
            || in_array($role, ['admin', 'guru'], true);

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $viewData = WaliKelasValidasiService::getValidasiData($ctx, $request, $canUpdate);

        return view('dashboard.wali-kelas-validasi-data', $viewData);
    }

    /**
     * Tampilan Agenda Kelas Binaan (Data Input Guru Mapel & Fasilitas Input Bantuan Wali/Koordinator/Piket)
     */
    public function agendaKelas(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);
        $isPiket = RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_agenda');
        $hasWaliAgenda = RolePermission::canAccess($user, 'menu_wali_kelas_agenda') || RolePermission::canAccess($user, 'menu_wali_kelas');

        if (!$isWaliOrAdmin && !$isPiket && !$hasWaliAgenda) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Agenda Kelas tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'create')
            || RolePermission::canAccess($user, 'menu_wali_kelas', 'create')
            || RolePermission::canAccess($user, 'menu_agenda_kbm', 'create')
            || in_array($role, ['admin', 'guru'], true)
            || $isPiket
            || ($role === 'peserta_didik' && RolePermission::isKoordinator($user));

        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'update')
            || RolePermission::canAccess($user, 'menu_wali_kelas', 'update')
            || RolePermission::canAccess($user, 'menu_agenda_kbm', 'update')
            || in_array($role, ['admin', 'guru'], true)
            || $isPiket
            || ($role === 'peserta_didik' && RolePermission::isKoordinator($user));

        $canDelete = RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'delete')
            || RolePermission::canAccess($user, 'menu_wali_kelas', 'delete')
            || in_array($role, ['admin'], true)
            || $isPiket;

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $viewData = WaliKelasAgendaService::getAgendaData($ctx, $request, compact('canCreate', 'canUpdate', 'canDelete'));

        return view('dashboard.wali-kelas-agenda', $viewData);
    }

    /**
     * Simpan data Agenda Kelas Binaan Baru (Wali Kelas / Koordinator / Piket / Guru Mapel).
     */
    public function storeAgendaKelas(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isWaliKelas = RolePermission::isWaliKelasOrAdmin($user);
        $isKoordinator = ($role === 'peserta_didik') && RolePermission::isKoordinator($user);
        $isPiket = RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_agenda');
        $isAdmin = ($role === 'admin');
        $isGuru = ($role === 'guru');

        $canInput = $isAdmin || $isWaliKelas || $isKoordinator || $isPiket || $isGuru
            || RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'create')
            || RolePermission::canAccess($user, 'menu_agenda_kbm', 'create');

        if (!$canInput) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah agenda kelas.'], 403);
        }

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];
        if (!$activeRombel) {
            return response()->json(['status' => 'error', 'message' => 'Rombel kelas binaan tidak ditemukan.'], 422);
        }

        $validated = $request->validate([
            'jadwal_kbm_id'         => 'nullable|integer',
            'ptk_id'                => 'required|string|max:50',
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

        $res = WaliKelasAgendaService::store($ctx, $validated, $user);

        return response()->json($res);
    }

    /**
     * Detail Agenda Kelas (Read).
     */
    public function showAgendaKelas(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $data = WaliKelasAgendaService::show($id);
        if (!$data) {
            return response()->json(['status' => 'error', 'message' => 'Data agenda kelas tidak ditemukan.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Update data Agenda Kelas Binaan.
     */
    public function updateAgendaKelas(Request $request, string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isWaliKelas = RolePermission::isWaliKelasOrAdmin($user);
        $isKoordinator = ($role === 'peserta_didik') && RolePermission::isKoordinator($user);
        $isPiket = RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_agenda');
        $isAdmin = ($role === 'admin');
        $isGuru = ($role === 'guru');

        $canUpdate = $isAdmin || $isWaliKelas || $isKoordinator || $isPiket || $isGuru
            || RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'update')
            || RolePermission::canAccess($user, 'menu_agenda_kbm', 'update');

        if (!$canUpdate) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $agenda = AgendaKbm::findOrFail($id);

        if (!$ctx['isAdmin'] && !$isPiket && $activeRombel && $agenda->rombongan_belajar_id !== $activeRombel->rombongan_belajar_id) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak mengubah agenda di luar kelas binaan Anda.'], 403);
        }

        $validated = $request->validate([
            'jadwal_kbm_id'         => 'nullable|integer',
            'ptk_id'                => 'required|string|max:50',
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

        $updated = WaliKelasAgendaService::update($id, $validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Agenda kelas berhasil diperbarui.',
            'data'    => $updated,
        ]);
    }

    /**
     * Hapus data Agenda Kelas.
     */
    public function destroyAgendaKelas(Request $request, string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        $isWaliKelas = RolePermission::isWaliKelasOrAdmin($user);
        $isPiket = RolePermission::canAccess($user, 'menu_piket') || RolePermission::canAccess($user, 'menu_piket_agenda');
        $isAdmin = ($role === 'admin');

        $canDelete = $isAdmin || $isWaliKelas || $isPiket
            || RolePermission::canAccess($user, 'menu_wali_kelas_agenda', 'delete')
            || RolePermission::canAccess($user, 'menu_agenda_kbm', 'delete');

        if (!$canDelete) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $agenda = AgendaKbm::findOrFail($id);
        if (!$ctx['isAdmin'] && !$isPiket && $activeRombel && $agenda->rombongan_belajar_id !== $activeRombel->rombongan_belajar_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $agenda->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Agenda kelas berhasil dihapus.',
        ]);
    }

    /**
     * Hitung Pertemuan Ke Berikutnya untuk Mapel Kelas Binaan via AJAX.
     */
    public function nextPertemuanAgendaKelas(Request $request)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error'], 401);

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];
        if (!$activeRombel) return response()->json(['status' => 'error', 'next_pertemuan' => 1]);

        $next = WaliKelasAgendaService::getNextPertemuan(
            $activeRombel->rombongan_belajar_id,
            $request->input('pembelajaran_id'),
            $request->input('nama_mata_pelajaran')
        );

        return response()->json(['status' => 'success', 'next_pertemuan' => $next]);
    }

    /**
     * Cetak Jurnal & Agenda Kelas Binaan
     */
    public function cetakAgendaKelas(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        if (!$ctx['activeRombel']) {
            return back()->with('error', 'Rombel binaan tidak ditemukan.');
        }

        $viewData = WaliKelasAgendaService::getCetakData($ctx, $request);

        return view('dashboard.wali-kelas-agenda-cetak', $viewData);
    }

    /**
     * Tampilan Peserta Didik Tidak Aktif (Alumni / Mutasi Kelas Terkait)
     */
    public function pesertaDidikTidakAktif(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Wali Kelas tidak diizinkan.');
        }

        $canCreate = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'create');
        $canRead   = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'read');
        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'update');
        $canDelete = RolePermission::canAccess($user, 'menu_wali_kelas_tidak_aktif', 'delete');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $q       = trim($request->get('q', ''));
        $status  = trim($request->get('status', ''));
        $tahun   = trim($request->get('tahun', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE_TIDAK_AKTIF, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!$activeRombel || !Table::exists('peserta_didik_tidak_aktif')) {
            return view('dashboard.wali-kelas-tidak-aktif', [
                'hasRombel'    => (bool) $activeRombel,
                'isAdmin'      => $ctx['isAdmin'],
                'rombelList'   => $ctx['rombelList'],
                'activeRombel' => $activeRombel,
                'waliNama'     => $ctx['waliNama'],
                'list'         => collect(),
                'total'        => 0,
                'summary'      => ['total' => 0, 'alumni' => 0, 'mutasi' => 0, 'berfoto' => 0],
                'filterTahun'  => collect(),
                'q'            => $q,
                'status'       => $status,
                'tahun'        => $tahun,
                'perPage'      => $perPage,
                'sort'         => $sort,
                'sortDir'      => $sortDir,
                'canCreate'    => $canCreate,
                'canRead'      => $canRead,
                'canUpdate'    => $canUpdate,
                'canDelete'    => $canDelete,
            ]);
        }

        $targetRombelId = $activeRombel->rombongan_belajar_id ?? null;
        $targetRombelName = $activeRombel->nama ?? null;

        // Siswa aktif tidak boleh muncul di tabel tidak aktif
        $activePdIds = Table::exists('peserta_didik')
            ? DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all()
            : [];

        $baseQuery = DB::table('peserta_didik_tidak_aktif')
            ->where(function ($w) use ($targetRombelId, $targetRombelName) {
                if ($targetRombelId) {
                    $w->where('rombongan_belajar_id', $targetRombelId);
                }
                if ($targetRombelName) {
                    $w->orWhere('nama_rombel_terakhir', $targetRombelName);
                }
            });

        if (!empty($activePdIds)) {
            $baseQuery->whereNotIn('peserta_didik_id', $activePdIds);
        }

        // Summary counts
        $totalAll    = (clone $baseQuery)->count();
        $totalAlumni = (clone $baseQuery)->where('status_keluar', 'Alumni')->count();
        $totalMutasi = (clone $baseQuery)->where('status_keluar', '<>', 'Alumni')->count();
        $totalBerfoto = (clone $baseQuery)->whereNotNull('foto_path')->where('foto_path', '<>', '')->count();

        $summary = [
            'total'   => $totalAll,
            'alumni'  => $totalAlumni,
            'mutasi'  => $totalMutasi,
            'berfoto' => $totalBerfoto,
        ];

        // Daftar tahun keluar
        $filterTahun = (clone $baseQuery)
            ->whereNotNull('tahun_lulus')
            ->where('tahun_lulus', '<>', '')
            ->distinct()
            ->pluck('tahun_lulus')
            ->sortDesc()
            ->values();

        $query = clone $baseQuery;

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nisn', 'LIKE', "%{$q}%")
                    ->orWhere('nipd', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%");
            });
        }

        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }

        if ($tahun !== '') {
            $query->where('tahun_lulus', $tahun);
        }

        if ($sort === 'nama') {
            $query->orderByRaw("LOWER(TRIM(nama)) {$sortDir}")->orderBy('nisn', 'asc');
        } else {
            $query->orderBy($sort, $sortDir)->orderByRaw("LOWER(TRIM(nama)) asc");
        }
        $list = $query->paginate($perPage)->appends($request->query());

        $taPdIds = $list->pluck('peserta_didik_id')->filter()->values()->all();
        $metaMapTa = collect();
        if (!empty($taPdIds) && Table::exists('peserta_didik_meta')) {
            $metaMapTa = PesertaDidikMeta::whereIn('peserta_didik_id', $taPdIds)->get()->keyBy('peserta_didik_id');
        }
        foreach ($list as $item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : ($metaMapTa[$item->peserta_didik_id]?->foto_url ?? null);
        }

        return view('dashboard.wali-kelas-tidak-aktif', [
            'hasRombel'    => true,
            'isAdmin'      => $ctx['isAdmin'],
            'rombelList'   => $ctx['rombelList'],
            'activeRombel' => $activeRombel,
            'waliNama'     => $ctx['waliNama'],
            'list'         => $list,
            'total'        => $list->total(),
            'summary'      => $summary,
            'filterTahun'  => $filterTahun,
            'q'            => $q,
            'status'       => $status,
            'tahun'        => $tahun,
            'perPage'      => $perPage,
            'sort'         => $sort,
            'sortDir'      => $sortDir,
            'canCreate'    => $canCreate,
            'canRead'      => $canRead,
            'canUpdate'    => $canUpdate,
            'canDelete'    => $canDelete,
        ]);
    }

    /**
     * API JSON Detail Peserta Didik Aktif (Lengkap seperti di Admin, Dibatasi per kelas binaan)
     */
    public function showPesertaDidik(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik')
            ->where('peserta_didik_id', $id)
            ->orWhere('nisn', $id)
            ->orWhere('nipd', $id)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi siswa berada di kelas binaan miliknya
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $studentRombelId = $student->rombongan_belajar_id;
            if (!$studentRombelId && Table::exists('anggota_rombel')) {
                $studentRombelId = DB::table('anggota_rombel')
                    ->where('peserta_didik_id', $student->peserta_didik_id)
                    ->value('rombongan_belajar_id');
            }

            $isMatch = false;
            if ($myRombelId && $studentRombelId && $studentRombelId === $myRombelId) {
                $isMatch = true;
            } elseif ($myRombelName && !empty($student->nama_rombel) && strcasecmp(trim($student->nama_rombel), trim($myRombelName)) === 0) {
                $isMatch = true;
            } elseif ($myRombelId && Table::exists('anggota_rombel')) {
                $isMatch = DB::table('anggota_rombel')
                    ->where('rombongan_belajar_id', $myRombelId)
                    ->where('peserta_didik_id', $student->peserta_didik_id)
                    ->exists();
            }

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data peserta didik di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $anggota = null;
        if (Table::exists('anggota_rombel')) {
            $anggota = DB::table('anggota_rombel')
                ->where('peserta_didik_id', $student->peserta_didik_id)
                ->first();
        }

        $meta = null;
        if (Table::exists('peserta_didik_meta')) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $student->peserta_didik_id)->first();
        }

        $rombelId = $student->rombongan_belajar_id ?? ($anggota->rombongan_belajar_id ?? null);
        $pembelajaran = collect();
        if (!empty($rombelId) && Table::exists('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
                ->where('pembelajaran.rombongan_belajar_id', $rombelId)
                ->select(
                    'pembelajaran.pembelajaran_id',
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.mata_pelajaran_id_str',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'pembelajaran.status_di_kurikulum_str',
                    'gtk.nama as nama_guru',
                    'gtk.nuptk',
                    'gtk.nip'
                )
                ->orderBy('pembelajaran.nama_mata_pelajaran', 'asc')
                ->get();
        }

        $student->foto_url = $meta?->foto_url;
        $student->formatted_foto_size = $meta?->formatted_foto_size;

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $student,
            'anggota' => $anggota,
            'meta' => $meta,
            'foto_url' => $meta?->foto_url,
            'foto_size' => $meta?->formatted_foto_size,
            'pembelajaran' => $pembelajaran,
            'total_mapel' => $pembelajaran->count(),
            'total_jam' => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ]);
    }

    /**
     * API JSON Detail Peserta Didik Tidak Aktif (Dibatasi per kelas binaan)
     */
    public function showPesertaDidikTidakAktif(string $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'success' => false, 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user)) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $student = DB::table('peserta_didik_tidak_aktif')
            ->where('id', $id)
            ->orWhere('peserta_didik_id', $id)
            ->first();

        if (!$student) {
            return response()->json(['status' => 'error', 'success' => false, 'message' => 'Data peserta didik tidak ditemukan.'], 404);
        }

        // Keamanan Ketat: Jika guru, verifikasi riwayat rombel siswa
        if ($role === 'guru') {
            $myRombel = RolePermission::getWaliKelasRombelInfo($user);
            $myRombelId = $myRombel?->rombongan_belajar_id;
            $myRombelName = $myRombel?->nama;

            $isMatch = ($myRombelId && !empty($student->rombongan_belajar_id) && $student->rombongan_belajar_id === $myRombelId)
                    || ($myRombelName && !empty($student->nama_rombel_terakhir) && strcasecmp(trim($student->nama_rombel_terakhir), trim($myRombelName)) === 0);

            if (!$isMatch) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Anda tidak memiliki wewenang mengakses data peserta didik di luar kelas binaan Anda.'
                ], 403);
            }
        }

        $student->foto_url = !empty($student->foto_path) ? asset('storage/' . ltrim($student->foto_path, '/')) : null;

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $student,
        ]);
    }

    /**
     * Tampilan Presensi Kelas Binaan (Wali Kelas)
     */
    public function presensi(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi')) {
            return redirect()->route('dashboard.' . ($role ?: 'guru'))->with('error', 'Akses ke modul Presensi Kelas tidak diizinkan.');
        }

        $canUpdate = RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update');
        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        $viewData = WaliKelasPresensiService::getPresensiData($ctx, $request, $canUpdate);

        return view('dashboard.wali-kelas.presensi', $viewData);
    }

    /**
     * API Simpan Presensi Manual oleh Wali Kelas
     * Membantu pencatatan kehadiran harian siswa selain RFID / QR Code
     */
    public function simpanPresensiManual(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses mencatat presensi kelas.'], 403);
        }

        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'tanggal'          => 'required|date',
            'action'           => 'required|string',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        $ctx = $this->resolveWaliKelasContext($request, $user);
        if (!$ctx['activeRombel']) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki rombel binaan aktif.'], 422);
        }

        $res = WaliKelasPresensiService::simpanManual($ctx, $validated, $user);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * API: Reset / Kosongkan Presensi Manual Rombel untuk Tanggal Tertentu (Wali Kelas)
     */
    public function resetPresensiKelas(Request $request)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses mereset presensi kelas.'], 403);
        }

        $request->validate([
            'tanggal'              => 'required|date',
            'rombongan_belajar_id' => 'required|string',
        ]);

        $tanggal  = $request->input('tanggal');
        $rombelId = $request->input('rombongan_belajar_id');

        $ctx = $this->resolveWaliKelasContext($request, $user);
        if (!$ctx['isAdmin'] && ($ctx['activeRombel']?->rombongan_belajar_id !== $rombelId)) {
            return response()->json(['status' => 'error', 'message' => 'Rombongan belajar di luar binaan Anda.'], 403);
        }

        $deleted = WaliKelasPresensiService::resetPresensi($ctx, $tanggal, $rombelId);

        return response()->json([
            'status'  => 'success',
            'message' => "Sebanyak {$deleted} catatan presensi manual berhasil dikosongkan kembali untuk diulangi.",
        ]);
    }

    /**
     * Verifikasi / Approval Pengajuan Surat Izin Siswa Kelas Binaan
     */
    public function verifikasiIzin(Request $request, $id)
    {
        $user = session('user');
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Sesi login telah berakhir.'], 401);
        }

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'update')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses verifikasi izin.'], 403);
        }

        $validated = $request->validate([
            'status'  => 'required|string|in:disetujui,ditolak',
            'catatan' => 'nullable|string|max:500',
        ]);

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $res = WaliKelasPresensiService::verifikasiIzin($ctx, $id, $validated, $user);

        return response()->json($res, $res['code'] ?? 200);
    }

    /**
     * Unduh Laporan Presensi PDF Multi-Periode
     * Tipe yang didukung:
     * 1. 'siswa'    -> Rekap Kartu Presensi Individual Siswa
     * 2. 'hari'     -> Rekap Lembar Kehadiran Harian Rombel
     * 3. 'bulan'    -> Matriks Presensi Bulanan (Tanggal 1 s.d. 31)
     * 4. 'semester' -> Rekapitulasi Presensi Per Semester
     * 5. 'tahun'    -> Rekapitulasi Presensi Per Tahun Ajaran
     */
    public function downloadPdf(Request $request, string $tipe)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        if (!RolePermission::isWaliKelasOrAdmin($user) || !RolePermission::canAccess($user, 'menu_wali_kelas_presensi', 'read')) {
            abort(403, 'Akses unduh laporan presensi tidak diizinkan.');
        }

        $ctx = $this->resolveWaliKelasContext($request, $user);
        $activeRombel = $ctx['activeRombel'];

        if (!$activeRombel) {
            return back()->with('error', 'Rombongan belajar tidak ditemukan.');
        }

        $viewData = WaliKelasPresensiService::getPdfData($ctx, $tipe, $request);

        return view('dashboard.wali-kelas.pdf-rekap', $viewData);
    }

}
