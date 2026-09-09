<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuruAktifController extends Controller
{
    private const SORTABLE = ['nama', 'nuptk', 'nip', 'jenis_kelamin', 'jenis_ptk_id_str', 'status_kepegawaian_id_str'];

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!in_array($role, ['admin', 'guru', 'tendik'], true)) return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'));

        $q       = trim($request->get('q', ''));
        $jenis   = trim($request->get('jenis', ''));
        $status  = trim($request->get('status', ''));
        $gender  = trim($request->get('gender', ''));
        $perPage = (int) $request->get('perPage', 15);
        $sort    = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'nama';
        $sortDir = $request->get('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!Schema::hasTable('gtk')) {
            return view('dashboard.guru-aktif', [
                'list' => collect(),
                'total' => 0,
                'summary' => ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'pns' => 0, 'non_pns' => 0],
                'filterJenis' => collect(),
                'filterStatus' => collect(),
                'q' => $q,
                'jenis' => $jenis,
                'status' => $status,
                'gender' => $gender,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        // Filter khusus Guru & Kepala Sekolah (mengecualikan Tenaga Kependidikan / Tendik murni)
        $baseFilterGuru = function ($query) {
            $query->where(function ($sub) {
                $sub->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%')
                    ->orWhereNull('jenis_ptk_id_str');
            })->where(function ($sub) {
                $sub->where('jenis_ptk_id_str', 'NOT LIKE', '%Tenaga Kependidikan%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Guru%');
            });
        };

        // Daftar jenis PTK & status kepegawaian untuk filter
        $filterJenis = DB::table('gtk')
            ->whereNotNull('jenis_ptk_id_str')
            ->where('jenis_ptk_id_str', '<>', '')
            ->where(function ($query) {
                $query->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })
            ->distinct()
            ->pluck('jenis_ptk_id_str')
            ->sort()
            ->values();

        $filterStatus = DB::table('gtk')
            ->whereNotNull('status_kepegawaian_id_str')
            ->where('status_kepegawaian_id_str', '<>', '')
            ->where(function ($query) {
                $query->where('jenis_ptk_id_str', 'LIKE', '%Guru%')
                    ->orWhere('jenis_ptk_id_str', 'LIKE', '%Kepala Sekolah%');
            })
            ->distinct()
            ->pluck('status_kepegawaian_id_str')
            ->sort()
            ->values();

        $baseQuery = DB::table('gtk')
            ->select(
                'ptk_id',
                'nama',
                'nuptk',
                'nip',
                'nik',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'agama_id_str as agama',
                'jenis_ptk_id_str as jenis_ptk',
                'jabatan_ptk_id_str as jabatan_ptk',
                'status_kepegawaian_id_str as status_kepegawaian',
                'pendidikan_terakhir',
                'bidang_studi_terakhir',
                'pangkat_golongan_terakhir',
                'email',
                'no_hp',
                'alamat_jalan'
            );

        $baseFilterGuru($baseQuery);

        if ($q !== '') {
            $baseQuery->where(function ($sub) use ($q) {
                $sub->where('nama', 'LIKE', "%{$q}%")
                    ->orWhere('nuptk', 'LIKE', "%{$q}%")
                    ->orWhere('nip', 'LIKE', "%{$q}%")
                    ->orWhere('nik', 'LIKE', "%{$q}%")
                    ->orWhere('jenis_ptk_id_str', 'LIKE', "%{$q}%")
                    ->orWhere('status_kepegawaian_id_str', 'LIKE', "%{$q}%");
            });
        }

        if ($jenis !== '') {
            $baseQuery->where('jenis_ptk_id_str', $jenis);
        }

        if ($status !== '') {
            $baseQuery->where('status_kepegawaian_id_str', $status);
        }

        if ($gender !== '') {
            $baseQuery->where('jenis_kelamin', $gender);
        }

        $allResults = $baseQuery->get();
        $total = $allResults->count();

        $summary = [
            'total' => $total,
            'laki' => $allResults->where('jenis_kelamin', 'L')->count(),
            'perempuan' => $allResults->where('jenis_kelamin', 'P')->count(),
            'pns' => $allResults->filter(fn($g) => str_contains(strtoupper($g->status_kepegawaian ?? ''), 'PNS'))->count(),
            'non_pns' => $allResults->filter(fn($g) => !str_contains(strtoupper($g->status_kepegawaian ?? ''), 'PNS'))->count(),
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

        return view('dashboard.guru-aktif', compact(
            'list',
            'total',
            'summary',
            'filterJenis',
            'filterStatus',
            'q',
            'jenis',
            'status',
            'gender',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Detail GTK via JSON untuk modal
     */
    public function show(Request $request, $id)
    {
        $user = session('user');
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $gtk = DB::table('gtk')->where('ptk_id', $id)->first();
        if (!$gtk) {
            return response()->json(['status' => 'error', 'message' => 'Data GTK tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $gtk,
        ]);
    }
}
