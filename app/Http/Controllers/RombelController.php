<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RombelController extends Controller
{
    private const SORTABLE = ['nama', 'tingkat', 'jurusan', 'wali_kelas', 'ruang', 'total_peserta_didik'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if ($role !== 'admin') return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));

        $q       = trim($request->get('q', ''));
        $tingkat = trim($request->get('tingkat', ''));
        $jurusan = trim($request->get('jurusan', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('rombongan_belajar')) {
            return view('dashboard.rombel', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['rombel' => 0, 'peserta_didik' => 0, 'jurusan' => 0, 'wali' => 0],
                'filterTingkat' => collect(),
                'filterJurusan' => collect(),
                'q' => $q,
                'tingkat' => $tingkat,
                'jurusan' => $jurusan,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        // Ambil daftar filter tingkat dan jurusan untuk dropdown filter
        $filterTingkat = DB::table('rombongan_belajar')
            ->whereNotNull('tingkat_pendidikan_id_str')
            ->where('tingkat_pendidikan_id_str', '<>', '')
            ->distinct()
            ->pluck('tingkat_pendidikan_id_str')
            ->sort()
            ->values();

        $filterJurusan = DB::table('rombongan_belajar')
            ->whereNotNull('jurusan_id_str')
            ->where('jurusan_id_str', '<>', '')
            ->distinct()
            ->pluck('jurusan_id_str')
            ->sort()
            ->values();

        $baseQuery = DB::table('rombongan_belajar')
            ->select(
                'rombongan_belajar.rombongan_belajar_id',
                'rombongan_belajar.nama',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.jurusan_id_str as jurusan',
                'rombongan_belajar.jurusan_id',
                'rombongan_belajar.kurikulum_id_str as kurikulum',
                'rombongan_belajar.ptk_id_str as wali_kelas',
                'rombongan_belajar.id_ruang_str as ruang',
                'rombongan_belajar.jenis_rombel_str as jenis_rombel'
            );

        if (Schema::hasTable('peserta_didik')) {
            $baseQuery->leftJoin('peserta_didik', 'rombongan_belajar.rombongan_belajar_id', '=', 'peserta_didik.rombongan_belajar_id')
                ->addSelect(DB::raw('COUNT(DISTINCT peserta_didik.peserta_didik_id) as total_peserta_didik'))
                ->groupBy(
                    'rombongan_belajar.rombongan_belajar_id',
                    'rombongan_belajar.nama',
                    'rombongan_belajar.tingkat_pendidikan_id_str',
                    'rombongan_belajar.jurusan_id_str',
                    'rombongan_belajar.jurusan_id',
                    'rombongan_belajar.kurikulum_id_str',
                    'rombongan_belajar.ptk_id_str',
                    'rombongan_belajar.id_ruang_str',
                    'rombongan_belajar.jenis_rombel_str'
                );
        } else {
            $baseQuery->addSelect(DB::raw('0 as total_peserta_didik'));
        }

        // Global search
        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('rombongan_belajar.nama', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.jurusan_id_str', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.ptk_id_str', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.id_ruang_str', 'LIKE', "%{$q}%");
            });
        }

        // Filter dropdown
        if ($tingkat !== '') {
            $baseQuery->where('rombongan_belajar.tingkat_pendidikan_id_str', $tingkat);
        }
        if ($jurusan !== '') {
            $baseQuery->where('rombongan_belajar.jurusan_id_str', $jurusan);
        }

        $allResults = $baseQuery->get();
        $total = $allResults->count();

        // Summary metrics
        $summary = [
            'rombel' => $total,
            'peserta_didik' => $allResults->sum('total_peserta_didik'),
            'jurusan' => $allResults->pluck('jurusan')->filter()->unique()->count(),
            'wali' => $allResults->pluck('wali_kelas')->filter()->unique()->count(),
        ];

        // Sorting collection
        $sorted = $allResults->sortBy(function ($item) use ($sort) {
            return $item->{$sort} ?? '';
        }, SORT_REGULAR, $sortDir === 'desc');

        // Pagination
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

        return view('dashboard.rombel', compact(
            'list',
            'total',
            'summary',
            'filterTingkat',
            'filterJurusan',
            'q',
            'tingkat',
            'jurusan',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Detail peserta didik dalam suatu rombel via JSON untuk modal preview
     */
    public function showPesertaDidik(Request $request, $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $rombel = DB::table('rombongan_belajar')
            ->where('rombongan_belajar_id', $id)
            ->first();

        if (!$rombel) {
            return response()->json(['status' => 'error', 'message' => 'Rombongan belajar tidak ditemukan'], 404);
        }

        $pesertaDidik = collect();
        if (Schema::hasTable('peserta_didik')) {
            $pesertaDidik = DB::table('peserta_didik')
                ->where('rombongan_belajar_id', $id)
                ->select(
                    'peserta_didik_id',
                    'nama',
                    'nisn',
                    'nipd',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir'
                )
                ->orderBy('nama', 'asc')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'rombel' => [
                'id' => $rombel->rombongan_belajar_id,
                'nama' => $rombel->nama,
                'tingkat' => $rombel->tingkat_pendidikan_id_str,
                'jurusan' => $rombel->jurusan_id_str,
                'wali_kelas' => $rombel->ptk_id_str,
                'ruang' => $rombel->id_ruang_str,
                'kurikulum' => $rombel->kurikulum_id_str,
                'total_peserta_didik' => $pesertaDidik->count(),
            ],
            'data' => $pesertaDidik,
        ]);
    }
}
