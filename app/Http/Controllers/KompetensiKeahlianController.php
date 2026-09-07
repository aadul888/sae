<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KompetensiKeahlianController extends Controller
{
    private const SORTABLE = ['kode', 'nama', 'total_rombel', 'total_siswa'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') return redirect()->route('dashboard.' . ($role ?: 'siswa'));

        $q       = trim($request->get('q', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('rombongan_belajar')) {
            return view('dashboard.kompetensi-keahlian', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['jurusan' => 0, 'rombel' => 0, 'siswa' => 0],
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

        // Total siswa jika tabel peserta_didik tersedia
        if (Schema::hasTable('peserta_didik')) {
            $baseQuery->leftJoin('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
                ->addSelect(DB::raw('COUNT(DISTINCT peserta_didik.peserta_didik_id) as total_siswa'));
        } else {
            $baseQuery->addSelect(DB::raw('0 as total_siswa'));
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
            'siswa' => $allResults->sum('total_siswa'),
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
    public function showRombel(Request $request, $kode)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $rombel = DB::table('rombongan_belajar')
            ->leftJoin('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
            ->where('rombongan_belajar.jurusan_id', $kode)
            ->select(
                'rombongan_belajar.rombongan_belajar_id',
                'rombongan_belajar.nama as nama_rombel',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.kurikulum_id_str as kurikulum',
                'rombongan_belajar.ptk_id_str as wali_kelas',
                'rombongan_belajar.id_ruang_str as ruang',
                DB::raw('COUNT(DISTINCT peserta_didik.peserta_didik_id) as total_siswa')
            )
            ->groupBy(
                'rombongan_belajar.rombongan_belajar_id',
                'rombongan_belajar.nama',
                'rombongan_belajar.tingkat_pendidikan_id_str',
                'rombongan_belajar.kurikulum_id_str',
                'rombongan_belajar.ptk_id_str',
                'rombongan_belajar.id_ruang_str'
            )
            ->orderBy('rombongan_belajar.nama', 'asc')
            ->get();

        $jurusan = DB::table('rombongan_belajar')
            ->where('jurusan_id', $kode)
            ->value('jurusan_id_str') ?: $kode;

        return response()->json([
            'status' => 'success',
            'jurusan' => $jurusan,
            'kode' => $kode,
            'data' => $rombel,
        ]);
    }
}
