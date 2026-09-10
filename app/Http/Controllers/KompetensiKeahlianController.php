<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KompetensiKeahlianController extends Controller
{
    private const SORTABLE = ['kode', 'nama', 'total_rombel', 'total_peserta_didik'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_kompetensi_keahlian')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Kompetensi Keahlian dinonaktifkan oleh Administrator.');
        }

        $q       = trim($request->get('q', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('rombongan_belajar')) {
            return view('dashboard.kompetensi-keahlian', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['jurusan' => 0, 'rombel' => 0, 'peserta_didik' => 0],
                'q' => $q,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        $baseQuery = DB::table('rombongan_belajar')
            ->whereNotNull('rombongan_belajar.jurusan_id_str')
            ->where('rombongan_belajar.jurusan_id_str', '<>', '')
            ->select(
                'rombongan_belajar.jurusan_id as kode',
                'rombongan_belajar.jurusan_id_str as nama',
                DB::raw('COUNT(DISTINCT rombongan_belajar.rombongan_belajar_id) as total_rombel')
            )
            ->groupBy('rombongan_belajar.jurusan_id', 'rombongan_belajar.jurusan_id_str');

        // Total peserta didik jika tabel peserta_didik tersedia
        if (Schema::hasTable('peserta_didik')) {
            $baseQuery->leftJoin('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
                ->addSelect(DB::raw('COUNT(DISTINCT peserta_didik.peserta_didik_id) as total_peserta_didik'));
        } else {
            $baseQuery->addSelect(DB::raw('0 as total_peserta_didik'));
        }

        if ($q !== '') {
            $baseQuery->havingRaw('kode LIKE ? OR nama LIKE ?', ["%{$q}%", "%{$q}%"]);
        }

        // Hitung total data
        $allResults = $baseQuery->get();
        $total = $allResults->count();

        // Summary metrics
        $summary = [
            'jurusan' => $total,
            'rombel' => $allResults->sum('total_rombel'),
            'peserta_didik' => $allResults->sum('total_peserta_didik'),
        ];

        // Sorting collection
        $sorted = $allResults->sortBy(function ($item) use ($sort) {
            return $item->{$sort} ?? '';
        }, SORT_REGULAR, $sortDir === 'desc');

        // Manual pagination
        $currentPage = (int) $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $itemsForPage = $sorted->slice($offset, $perPage)->values();

        // Ambil info rombel dan kurikulum untuk baris yang tampil
        $jurusanIds = $itemsForPage->pluck('kode')->toArray();
        $detailMap = [];
        if (!empty($jurusanIds)) {
            $details = DB::table('rombongan_belajar')
                ->whereIn('jurusan_id', $jurusanIds)
                ->select('jurusan_id', 'nama', 'kurikulum_id_str', 'tingkat_pendidikan_id_str')
                ->distinct()
                ->get()
                ->groupBy('jurusan_id');

            foreach ($details as $jid => $rows) {
                $detailMap[$jid] = [
                    'rombel' => $rows->pluck('nama')->unique()->values()->all(),
                    'kurikulum' => $rows->pluck('kurikulum_id_str')->filter()->unique()->values()->all(),
                    'tingkat' => $rows->pluck('tingkat_pendidikan_id_str')->filter()->unique()->values()->all(),
                ];
            }
        }

        foreach ($itemsForPage as $item) {
            $item->rombel_list = $detailMap[$item->kode]['rombel'] ?? [];
            $item->kurikulum_list = $detailMap[$item->kode]['kurikulum'] ?? [];
            $item->tingkat_list = $detailMap[$item->kode]['tingkat'] ?? [];
        }

        $list = new \Illuminate\Pagination\LengthAwarePaginator(
            $itemsForPage,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.kompetensi-keahlian', compact(
            'list',
            'total',
            'summary',
            'q',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Detail rombel per kompetensi keahlian via JSON untuk preview modal
     */
    public function showRombel(Request $request, string|int $kode)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_kompetensi_keahlian')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak oleh Administrator.'], 403);
        }

        $baseRombel = DB::table('rombongan_belajar')
            ->where('jurusan_id', $kode)
            ->select(
                'rombongan_belajar_id',
                'nama as nama_rombel',
                'tingkat_pendidikan_id_str as tingkat',
                'kurikulum_id_str as kurikulum',
                'ptk_id_str as wali_kelas',
                'id_ruang_str as ruang',
                'jenis_rombel_str as jenis_rombel'
            )
            ->distinct()
            ->orderBy('nama', 'asc')
            ->get();

        // Calculate student counts per rombel
        $pdCounts = Schema::hasTable('peserta_didik') ? DB::table('peserta_didik')
            ->whereNotNull('rombongan_belajar_id')
            ->groupBy('rombongan_belajar_id')
            ->pluck(DB::raw('COUNT(DISTINCT peserta_didik_id)'), 'rombongan_belajar_id') : collect();

        $arCounts = Schema::hasTable('anggota_rombel') ? DB::table('anggota_rombel')
            ->whereNotNull('rombongan_belajar_id')
            ->groupBy('rombongan_belajar_id')
            ->pluck(DB::raw('COUNT(DISTINCT peserta_didik_id)'), 'rombongan_belajar_id') : collect();

        foreach ($baseRombel as $r) {
            $count = max(
                (int) ($pdCounts[$r->rombongan_belajar_id] ?? 0),
                (int) ($arCounts[$r->rombongan_belajar_id] ?? 0)
            );
            $r->total_peserta_didik = $count;
            $r->jumlah_peserta_didik = $count;
        }

        $jurusan = DB::table('rombongan_belajar')
            ->where('jurusan_id', $kode)
            ->value('jurusan_id_str') ?: $kode;

        return response()->json([
            'status' => 'success',
            'jurusan' => $jurusan,
            'kode' => $kode,
            'data' => $baseRombel,
        ]);
    }
}
