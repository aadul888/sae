<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksAuthenticatedSession;
use App\Models\AdminAktivitas;
use App\Models\RolePermission;
use App\Services\SimpleXlsxExporter;
use App\Support\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AktivitasDatabaseController extends Controller
{
    use ChecksAuthenticatedSession;

    private const MENU = 'menu_aktivitas_database';

    private const SORTABLE = [
        'id', 'created_at', 'admin_name', 'modul', 'tipe', 'aktivitas',
    ];

    /**
     * Tampilkan halaman utama Log Aktivitas & Transaksi CRUD Database
     */
    public function index(Request $request)
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = session('user');
        $role = $this->sessionRole();

        if ($role !== 'admin' && !RolePermission::canAccess($user, self::MENU, 'read')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))
                ->with('error', 'Akses ke Log Aktivitas Database hanya untuk Administrator.');
        }

        $canDelete = $role === 'admin' || RolePermission::canAccess($user, self::MENU, 'delete');
        $canRead = true;

        $q = trim($request->get('q', ''));
        $crud = strtolower(trim($request->get('crud', 'all')));
        $bidang = trim($request->get('bidang', ''));
        $modul = trim($request->get('modul', ''));
        $tipe = trim($request->get('tipe', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $isExplicitAll = $request->has('all_time') || ($request->has('start_date') && $request->get('start_date') === '' && $request->get('end_date') === '');
        $range = $request->get('range', '');

        if ($isExplicitAll) {
            $startDate = '';
            $endDate = '';
            $range = 'all';
        } elseif ($request->has('start_date') || $request->has('end_date')) {
            $startDate = $request->get('start_date', '');
            $endDate = $request->get('end_date', '');
            $range = 'custom';
        } elseif ($range === '7days') {
            $startDate = now()->subDays(6)->toDateString();
            $endDate = now()->toDateString();
        } elseif ($range === '30days') {
            $startDate = now()->subDays(29)->toDateString();
            $endDate = now()->toDateString();
        } else {
            // Default: Hari ini
            $range = 'today';
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        }

        $perPage = (int) $request->get('perPage', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $sort = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'id';
        $sortDir = $request->get('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = AdminAktivitas::query();

        if ($crud !== 'all' && in_array($crud, ['create', 'read', 'update', 'delete'], true)) {
            $query->filterCrud($crud);
        }

        if (!empty($bidang)) {
            $query->where(function ($b) use ($bidang) {
                match ($bidang) {
                    'Kesiswaan' => $b->whereIn('modul', ['Kesiswaan', 'Siswa'])
                        ->orWhere('aktivitas', 'like', '%siswa%')
                        ->orWhere('aktivitas', 'like', '%peserta didik%'),
                    'Kepegawaian' => $b->whereIn('modul', ['Guru', 'Tendik', 'Kepegawaian'])
                        ->orWhere('aktivitas', 'like', '%guru%')
                        ->orWhere('aktivitas', 'like', '%tendik%')
                        ->orWhere('aktivitas', 'like', '%ptk%'),
                    'Kurikulum' => $b->whereIn('modul', ['Jadwal', 'Pembelajaran', 'Kurikulum', 'Rombel'])
                        ->orWhere('aktivitas', 'like', '%jadwal%')
                        ->orWhere('aktivitas', 'like', '%kbm%')
                        ->orWhere('aktivitas', 'like', '%rombel%'),
                    'Layanan Digital' => $b->whereIn('modul', ['Formulir', 'Pengumuman', 'Presensi', 'RFID', 'Kartu Pelajar'])
                        ->orWhere('aktivitas', 'like', '%presensi%')
                        ->orWhere('aktivitas', 'like', '%rfid%')
                        ->orWhere('aktivitas', 'like', '%pengumuman%')
                        ->orWhere('aktivitas', 'like', '%formulir%'),
                    'Keamanan & Akses' => $b->whereIn('modul', ['Autentikasi', 'Hak Akses', 'Pengguna', 'User'])
                        ->orWhere('aktivitas', 'like', '%login%')
                        ->orWhere('aktivitas', 'like', '%rbac%'),
                    'Infrastruktur & Sistem' => $b->whereIn('modul', ['Sistem', 'Backup', 'Dapodik'])
                        ->orWhere('aktivitas', 'like', '%sistem%')
                        ->orWhere('aktivitas', 'like', '%backup%')
                        ->orWhere('aktivitas', 'like', '%dapodik%')
                        ->orWhere('aktivitas', 'like', '%log%'),
                    default => null,
                };
            });
        }

        if (!empty($modul)) {
            $query->where('modul', $modul);
        }

        if (!empty($tipe)) {
            $query->where('tipe', $tipe);
        }

        $this->withinRange($query, $startDate, $endDate);

        if (!empty($q)) {
            $query->where(function ($b) use ($q) {
                $b->where('aktivitas', 'like', "%{$q}%")
                  ->orWhere('keterangan', 'like', "%{$q}%")
                  ->orWhere('admin_name', 'like', "%{$q}%")
                  ->orWhere('admin_username', 'like', "%{$q}%")
                  ->orWhere('modul', 'like', "%{$q}%")
                  ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        $list = $query->orderBy($sort, $sortDir)->paginate($perPage)->withQueryString();

        $periodQuery = $this->withinRange(AdminAktivitas::query(), $startDate, $endDate);

        $stats = [
            'total' => Table::exists('admin_aktivitas') ? AdminAktivitas::count() : 0,
            'today' => Table::exists('admin_aktivitas') ? $this->withinRange(AdminAktivitas::query(), now()->toDateString(), now()->toDateString())->count() : 0,
            'create' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('create')->count() : 0,
            'update' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('update')->count() : 0,
            'delete' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('delete')->count() : 0,
            'read'   => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('read')->count() : 0,
        ];

        $chartData = $this->getChartData($startDate, $endDate);

        $availableBidang = [
            'Kesiswaan',
            'Kepegawaian',
            'Kurikulum',
            'Layanan Digital',
            'Keamanan & Akses',
            'Infrastruktur & Sistem',
        ];

        $availableModules = Table::exists('admin_aktivitas')
            ? AdminAktivitas::distinct()->pluck('modul')->filter()->sort()->values()
            : collect(['Sistem', 'Autentikasi', 'Kesiswaan', 'Dapodik']);

        return view('dashboard.aktivitas-database', compact(
            'list',
            'stats',
            'chartData',
            'availableBidang',
            'availableModules',
            'crud',
            'range',
            'bidang',
            'modul',
            'tipe',
            'startDate',
            'endDate',
            'q',
            'perPage',
            'sort',
            'sortDir',
            'canDelete',
            'canRead'
        ));
    }

    /**
     * Dapatkan payload data realtime untuk pembaruan grafik & stat monitoring tanpa reload
     */
    public function live(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Cache 5 detik: banyak tab admin berbagi satu hasil query, bukan query ulang tiap tab.
        $payload = Cache::remember(
            'sae:monitoring:live:' . md5($startDate . '|' . $endDate),
            5,
            function () use ($startDate, $endDate) {
                $periodQuery = $this->withinRange(AdminAktivitas::query(), $startDate, $endDate);

                $stats = [
                    'total' => Table::exists('admin_aktivitas') ? AdminAktivitas::count() : 0,
                    'today' => Table::exists('admin_aktivitas') ? $this->withinRange(AdminAktivitas::query(), now()->toDateString(), now()->toDateString())->count() : 0,
                    'create' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('create')->count() : 0,
                    'update' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('update')->count() : 0,
                    'delete' => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('delete')->count() : 0,
                    'read'   => Table::exists('admin_aktivitas') ? (clone $periodQuery)->filterCrud('read')->count() : 0,
                ];

                $latestLogs = AdminAktivitas::orderBy('id', 'desc')->take(10)->get()->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'waktu' => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-',
                        'waktu_relatif' => $log->created_at ? $log->created_at->diffForHumans() : '-',
                        'admin_name' => $log->admin_name ?: 'Administrator',
                        'admin_username' => $log->admin_username ?: '-',
                        'ip_address' => $log->ip_address ?: '-',
                        'modul' => $log->modul ?: 'Sistem',
                        'aktivitas' => $log->aktivitas,
                        'keterangan' => $log->keterangan,
                        'crud_action' => $log->crud_action,
                        'crud_badge' => $log->crud_badge,
                        'konteks' => $log->konteks,
                    ];
                })->all();

                return [
                    'stats' => $stats,
                    'charts' => $this->getChartData($startDate, $endDate),
                    'latest_id' => AdminAktivitas::max('id') ?? 0,
                    'latest_logs' => $latestLogs,
                ];
            }
        );

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->format('H:i:s') . ' WIB',
        ] + $payload);
    }

    /**
     * Terapkan rentang tanggal dengan perbandingan kolom langsung.
     * whereDate() membungkus kolom dengan DATE() sehingga index created_at tidak terpakai.
     */
    private function withinRange($query, ?string $startDate, ?string $endDate)
    {
        if (!empty($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        return $query;
    }

    /**
     * Hitung dataset grafik interaktif untuk rentang yang dipilih
     */
    private function getChartData(?string $startDate, ?string $endDate): array
    {
        if (!Table::exists('admin_aktivitas')) {
            return [
                'hourly' => ['labels' => [], 'create' => [], 'update' => [], 'delete' => [], 'read' => []],
                'crud' => ['labels' => ['CREATE', 'UPDATE', 'DELETE', 'READ'], 'data' => [0, 0, 0, 0]],
                'modules' => ['labels' => [], 'data' => []],
                'bidang' => ['labels' => [], 'data' => []],
                'menu' => ['labels' => [], 'data' => []],
                'fitur' => ['labels' => [], 'data' => []],
                'seven_days' => ['labels' => [], 'data' => []],
            ];
        }

        $targetDate = !empty($startDate) ? $startDate : now()->toDateString();

        // 1. Distribusi per jam (00:00 s/d 23:00) untuk targetDate
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[sprintf('%02d:00', $i)] = ['create' => 0, 'update' => 0, 'delete' => 0, 'read' => 0];
        }

        $logsToday = $this->withinRange(AdminAktivitas::query(), $targetDate, $targetDate)
            ->select('id', 'modul', 'aktivitas', 'keterangan', 'tipe', 'created_at')
            ->get();

        $crudCounts = ['create' => 0, 'update' => 0, 'delete' => 0, 'read' => 0];
        $modulCounts = [];
        $bidangCounts = [];
        $menuCounts = [];
        $fiturCounts = [];

        foreach ($logsToday as $log) {
            $act = strtolower($log->crud_action);
            if (isset($crudCounts[$act])) {
                $crudCounts[$act]++;
            }
            $h = $log->created_at ? $log->created_at->format('H') . ':00' : '00:00';
            if (isset($hours[$h][$act])) {
                $hours[$h][$act]++;
            }

            $mod = $log->modul ?: 'Sistem';
            $modulCounts[$mod] = ($modulCounts[$mod] ?? 0) + 1;

            // Dimensi kontekstual (dihitung dari accessor yang sama dengan tabel)
            $k = $log->konteks;
            $bidangCounts[$k['bidang']] = ($bidangCounts[$k['bidang']] ?? 0) + 1;
            $menuCounts[$k['menu']] = ($menuCounts[$k['menu']] ?? 0) + 1;
            $fiturCounts[$k['fitur']] = ($fiturCounts[$k['fitur']] ?? 0) + 1;
        }

        arsort($modulCounts);
        $topModules = array_slice($modulCounts, 0, 6, true);

        arsort($bidangCounts);
        arsort($menuCounts);
        arsort($fiturCounts);
        $topMenu = array_slice($menuCounts, 0, 6, true);
        $topFitur = array_slice($fiturCounts, 0, 6, true);

        // 2. Tren 7 Hari Terakhir
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayKey = now()->subDays($i)->toDateString();
            $dayLabel = now()->subDays($i)->locale('id')->isoFormat('dd, D MMM');
            $sevenDays[$dayKey] = [
                'label' => $dayLabel,
                'count' => 0,
            ];
        }

        $historyData = AdminAktivitas::where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->select(DB::raw('DATE(created_at) as log_date'), DB::raw('count(*) as total'))
            ->groupBy('log_date')
            ->pluck('total', 'log_date');

        foreach ($historyData as $dateKey => $cnt) {
            if (isset($sevenDays[$dateKey])) {
                $sevenDays[$dateKey]['count'] = (int) $cnt;
            }
        }

        return [
            'hourly' => [
                'labels' => array_keys($hours),
                'create' => array_column($hours, 'create'),
                'update' => array_column($hours, 'update'),
                'delete' => array_column($hours, 'delete'),
                'read'   => array_column($hours, 'read'),
            ],
            'crud' => [
                'labels' => ['CREATE', 'UPDATE', 'DELETE', 'READ'],
                'data' => [
                    $crudCounts['create'],
                    $crudCounts['update'],
                    $crudCounts['delete'],
                    $crudCounts['read'],
                ],
            ],
            'modules' => [
                'labels' => array_keys($topModules),
                'data' => array_values($topModules),
            ],
            'bidang' => [
                'labels' => array_keys($bidangCounts),
                'data' => array_values($bidangCounts),
            ],
            'menu' => [
                'labels' => array_keys($topMenu),
                'data' => array_values($topMenu),
            ],
            'fitur' => [
                'labels' => array_keys($topFitur),
                'data' => array_values($topFitur),
            ],
            'seven_days' => [
                'labels' => array_column($sevenDays, 'label'),
                'data' => array_column($sevenDays, 'count'),
            ],
        ];
    }

    /**
     * Dapatkan detail satu log transaksi (JSON untuk modal / SweetAlert)
     */
    public function show($id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $log = AdminAktivitas::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'waktu' => $log->created_at ? $log->created_at->format('d/m/Y H:i:s') . ' WIB' : '-',
                'waktu_relatif' => $log->created_at ? $log->created_at->diffForHumans() : '-',
                'admin_name' => $log->admin_name ?: 'Administrator',
                'admin_username' => $log->admin_username ?: '-',
                'ip_address' => $log->ip_address ?: '-',
                'modul' => $log->modul ?: 'Sistem',
                'aktivitas' => $log->aktivitas,
                'keterangan' => $log->keterangan ?: 'Tidak ada keterangan tambahan.',
                'tipe' => $log->tipe,
                'crud_action' => $log->crud_action,
                'crud_badge' => $log->crud_badge,
                'konteks' => $log->konteks,
            ],
        ]);
    }

    /**
     * Ekspor rekap log aktivitas ke format Excel .xlsx
     */
    public function export(Request $request)
    {
        if ($res = $this->checkAuth()) {
            return $res;
        }

        $user = session('user');
        $role = $this->sessionRole();

        if ($role !== 'admin' && !RolePermission::canAccess($user, self::MENU, 'read')) {
            abort(403, 'Akses ditolak.');
        }

        $q = trim($request->get('q', ''));
        $crud = strtolower(trim($request->get('crud', 'all')));
        $bidang = trim($request->get('bidang', ''));
        $modul = trim($request->get('modul', ''));
        $tipe = trim($request->get('tipe', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = AdminAktivitas::query()->latest('id');

        if ($crud !== 'all' && in_array($crud, ['create', 'read', 'update', 'delete'], true)) {
            $query->filterCrud($crud);
        }
        if (!empty($bidang)) {
            $query->where(function ($b) use ($bidang) {
                match ($bidang) {
                    'Kesiswaan' => $b->whereIn('modul', ['Kesiswaan', 'Siswa'])
                        ->orWhere('aktivitas', 'like', '%siswa%')
                        ->orWhere('aktivitas', 'like', '%peserta didik%'),
                    'Kepegawaian' => $b->whereIn('modul', ['Guru', 'Tendik', 'Kepegawaian'])
                        ->orWhere('aktivitas', 'like', '%guru%')
                        ->orWhere('aktivitas', 'like', '%tendik%')
                        ->orWhere('aktivitas', 'like', '%ptk%'),
                    'Kurikulum' => $b->whereIn('modul', ['Jadwal', 'Pembelajaran', 'Kurikulum', 'Rombel'])
                        ->orWhere('aktivitas', 'like', '%jadwal%')
                        ->orWhere('aktivitas', 'like', '%kbm%')
                        ->orWhere('aktivitas', 'like', '%rombel%'),
                    'Layanan Digital' => $b->whereIn('modul', ['Formulir', 'Pengumuman', 'Presensi', 'RFID', 'Kartu Pelajar'])
                        ->orWhere('aktivitas', 'like', '%presensi%')
                        ->orWhere('aktivitas', 'like', '%rfid%')
                        ->orWhere('aktivitas', 'like', '%pengumuman%')
                        ->orWhere('aktivitas', 'like', '%formulir%'),
                    'Keamanan & Akses' => $b->whereIn('modul', ['Autentikasi', 'Hak Akses', 'Pengguna', 'User'])
                        ->orWhere('aktivitas', 'like', '%login%')
                        ->orWhere('aktivitas', 'like', '%rbac%'),
                    'Infrastruktur & Sistem' => $b->whereIn('modul', ['Sistem', 'Backup', 'Dapodik'])
                        ->orWhere('aktivitas', 'like', '%sistem%')
                        ->orWhere('aktivitas', 'like', '%backup%')
                        ->orWhere('aktivitas', 'like', '%dapodik%')
                        ->orWhere('aktivitas', 'like', '%log%'),
                    default => null,
                };
            });
        }
        if (!empty($modul)) {
            $query->where('modul', $modul);
        }
        if (!empty($tipe)) {
            $query->where('tipe', $tipe);
        }
        if (!empty($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if (!empty($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        if (!empty($q)) {
            $query->where(function ($b) use ($q) {
                $b->where('aktivitas', 'like', "%{$q}%")
                  ->orWhere('keterangan', 'like', "%{$q}%")
                  ->orWhere('admin_name', 'like', "%{$q}%")
                  ->orWhere('admin_username', 'like', "%{$q}%")
                  ->orWhere('modul', 'like', "%{$q}%")
                  ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        $logs = $query->limit(2000)->get();

        $headers = ['ID', 'Waktu (WIB)', 'Tipe CRUD', 'Bidang', 'Menu Dashboard', 'Fitur', 'Bagian / Target', 'Aktivitas Lengkap', 'Keterangan', 'Aktor / Pengguna', 'Username / ID', 'Alamat IP'];
        $rows = [];

        foreach ($logs as $log) {
            $k = $log->konteks;
            $rows[] = [
                $log->id,
                $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-',
                $log->crud_action,
                $k['bidang'],
                $k['menu'],
                $k['fitur'],
                $k['bagian'],
                $log->aktivitas,
                $log->keterangan ?? '-',
                $log->admin_name,
                $log->admin_username ?? '-',
                $log->ip_address ?? '-',
            ];
        }

        $fileName = 'SAE_Log_Aktivitas_Database_' . date('Ymd_His') . '.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        $adminName = is_array($user) ? ($user['name'] ?? 'Admin') : ($user->name ?? 'Admin');
        $meta = [
            'title' => 'LOG AKTIVITAS & TRANSAKSI CRUD DATABASE',
            'subtitle' => 'Sistem Aplikasi Edukasi (SAE) — Audit Trail Transaksi Sistem',
            'fields' => [
                'Dicetak Pada' => date('d/m/Y H:i:s') . ' WIB',
                'Oleh Pengguna' => $adminName,
                'Total Baris' => count($rows) . ' riwayat',
            ],
        ];

        $exported = SimpleXlsxExporter::export($tempPath, $meta, $headers, $rows, [
            'sheetName' => 'Log Transaksi CRUD',
            'headerBg' => 'FF1E3A8A',
        ]);

        if (!$exported || !file_exists($tempPath)) {
            return back()->with('error', 'Gagal membuat berkas ekspor excel.');
        }

        return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Hapus satu riwayat log aktivitas
     */
    public function destroy($id): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $user = session('user');
        $role = $this->sessionRole();

        if ($role !== 'admin' && !RolePermission::canAccess($user, self::MENU, 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses menghapus log aktivitas.'], 403);
        }

        $log = AdminAktivitas::findOrFail($id);
        $deletedDesc = "Hapus entri log #{$log->id}: [{$log->modul}] {$log->aktivitas}";
        $log->delete();

        AdminAktivitas::recordCrud('DELETE', 'Pembersihan Baris Log Database', 'Sistem', $deletedDesc, 'warning');

        return response()->json([
            'status' => 'success',
            'message' => 'Baris log aktivitas berhasil dihapus.',
        ]);
    }

    /**
     * Pembersihan massal log aktivitas berdasarkan periode
     */
    public function purge(Request $request): JsonResponse
    {
        if ($res = $this->checkAuth()) {
            return response()->json(['status' => 'error', 'message' => 'Sesi berakhir.'], 401);
        }

        $user = session('user');
        $role = $this->sessionRole();

        if ($role !== 'admin' && !RolePermission::canAccess($user, self::MENU, 'delete')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki wewenang untuk membersihkan log.'], 403);
        }

        $range = $request->input('range', '90');
        $days = in_array($range, ['30', '60', '90', 'all'], true) ? $range : '90';

        $deletedCount = 0;
        DB::transaction(function () use ($days, &$deletedCount) {
            $query = AdminAktivitas::query();
            if ($days !== 'all') {
                $cutoff = now()->subDays((int) $days);
                $query->where('created_at', '<', $cutoff);
            }
            $deletedCount = $query->delete();
        });

        $filterLabel = $days === 'all' ? 'seluruh riwayat' : "lebih dari {$days} hari yang lalu";
        AdminAktivitas::recordCrud(
            'DELETE',
            'Pembersihan Massal Log Database',
            'Sistem',
            "Menghapus {$deletedCount} entri riwayat transaksi ({$filterLabel}).",
            'warning'
        );

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil membersihkan {$deletedCount} baris riwayat aktivitas.",
            'deleted_count' => $deletedCount,
        ]);
    }
}
