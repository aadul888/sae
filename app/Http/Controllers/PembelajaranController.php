<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PembelajaranController extends Controller
{
    private const SORTABLE = ['nama_mata_pelajaran', 'nama_rombel', 'nama_guru', 'jam_mengajar_per_minggu', 'status_di_kurikulum_str'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!in_array($role, ['admin', 'guru'], true)) return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));

        $q        = trim($request->get('q', ''));
        $rombel   = trim($request->get('rombel', ''));
        $guru     = trim($request->get('guru', ''));
        $status   = trim($request->get('status', ''));
        $perPage  = (int) $request->get('perPage', 15);
        $sort     = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama_mata_pelajaran';
        $sortDir  = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('pembelajaran')) {
            return view('dashboard.pembelajaran', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['total' => 0, 'rombel' => 0, 'guru' => 0, 'jam' => 0],
                'filterRombel' => collect(),
                'filterGuru' => collect(),
                'filterStatus' => collect(),
                'q' => $q,
                'rombel' => $rombel,
                'guru' => $guru,
                'status' => $status,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        $baseQuery = DB::table('pembelajaran')
            ->leftJoin('rombongan_belajar', 'pembelajaran.rombongan_belajar_id', '=', 'rombongan_belajar.rombongan_belajar_id')
            ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
            ->select(
                'pembelajaran.pembelajaran_id',
                'pembelajaran.rombongan_belajar_id',
                'pembelajaran.mata_pelajaran_id',
                'pembelajaran.mata_pelajaran_id_str',
                DB::raw('COALESCE(pembelajaran.nama_mata_pelajaran, pembelajaran.mata_pelajaran_id_str, "-") as nama_mata_pelajaran'),
                'pembelajaran.ptk_id',
                'pembelajaran.jam_mengajar_per_minggu',
                'pembelajaran.status_di_kurikulum_str',
                'pembelajaran.induk_pembelajaran_id',
                'rombongan_belajar.nama as nama_rombel',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.jurusan_id_str as jurusan',
                'gtk.nama as nama_guru',
                'gtk.nuptk',
                'gtk.nip',
                'gtk.jenis_kelamin as guru_gender'
            );

        // Filter dropdown options
        $filterRombel = DB::table('rombongan_belajar')
            ->whereNotNull('nama')
            ->where('nama', '<>', '')
            ->distinct()
            ->pluck('nama')
            ->sort()
            ->values();

        $filterGuru = DB::table('pembelajaran')
            ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
            ->whereNotNull('gtk.nama')
            ->where('gtk.nama', '<>', '')
            ->distinct()
            ->pluck('gtk.nama')
            ->sort()
            ->values();

        $filterStatus = DB::table('pembelajaran')
            ->whereNotNull('status_di_kurikulum_str')
            ->where('status_di_kurikulum_str', '<>', '')
            ->distinct()
            ->pluck('status_di_kurikulum_str')
            ->sort()
            ->values();

        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('pembelajaran.nama_mata_pelajaran', 'LIKE', "%{$q}%")
                    ->orWhere('pembelajaran.mata_pelajaran_id_str', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.nama', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nama', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nuptk', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nip', 'LIKE', "%{$q}%");
            });
        }

        if ($rombel !== '') {
            $baseQuery->where('rombongan_belajar.nama', $rombel);
        }

        if ($guru !== '') {
            $baseQuery->where('gtk.nama', $guru);
        }

        if ($status !== '') {
            $baseQuery->where('pembelajaran.status_di_kurikulum_str', $status);
        }

        $allResults = $baseQuery->get();
        $total = $allResults->count();

        $summary = [
            'total' => $total,
            'rombel' => $allResults->pluck('rombongan_belajar_id')->filter()->unique()->count(),
            'guru' => $allResults->pluck('ptk_id')->filter()->unique()->count(),
            'jam' => $allResults->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
        ];

        $sorted = $allResults->sortBy(function ($item) use ($sort) {
            return $item->{$sort} ?? '';
        }, SORT_REGULAR, $sortDir === 'desc');

        $currentPage = (int) $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $itemsForPage = $sorted->slice($offset, $perPage)->values();

        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.pembelajaran', compact(
            'list',
            'total',
            'summary',
            'filterRombel',
            'filterGuru',
            'filterStatus',
            'q',
            'rombel',
            'guru',
            'status',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Detail Pembelajaran via JSON untuk modal
     */
    public function show(Request $request, string|int $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $pem = DB::table('pembelajaran')
            ->leftJoin('rombongan_belajar', 'pembelajaran.rombongan_belajar_id', '=', 'rombongan_belajar.rombongan_belajar_id')
            ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
            ->where('pembelajaran.pembelajaran_id', $id)
            ->select(
                'pembelajaran.*',
                'rombongan_belajar.nama as nama_rombel',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.jurusan_id_str as jurusan',
                'rombongan_belajar.kurikulum_id_str as kurikulum',
                'rombongan_belajar.id_ruang_str as ruang',
                'rombongan_belajar.ptk_id_str as wali_kelas',
                'gtk.nama as nama_guru',
                'gtk.nuptk',
                'gtk.nip',
                'gtk.jenis_kelamin as guru_gender',
                'gtk.status_kepegawaian_id_str as guru_status',
                'gtk.email as guru_email',
                'gtk.no_hp as guru_hp'
            )
            ->first();

        if (!$pem) {
            return response()->json(['status' => 'error', 'message' => 'Data pembelajaran tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pem,
        ]);
    }
}
