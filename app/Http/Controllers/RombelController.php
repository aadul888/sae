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
        $type = $request->get('type', 'reguler');
        if ($type === 'matpel') {
            return $this->matpel($request);
        }
        return $this->reguler($request);
    }

    public function reguler(Request $request)
    {
        return $this->renderRombel($request, 'reguler');
    }

    public function matpel(Request $request)
    {
        return $this->renderRombel($request, 'matpel');
    }

    private function renderRombel(Request $request, string $currentType)
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
                'currentType' => $currentType,
                'q' => $q,
                'tingkat' => $tingkat,
                'jurusan' => $jurusan,
                'perPage' => $perPage,
                'sort' => $sort,
                'sortDir' => $sortDir,
            ]);
        }

        // Base Query untuk tipe rombel
        $typeFilter = function ($query) use ($currentType) {
            if ($currentType === 'matpel') {
                $query->where(function ($q) {
                    $q->where('rombongan_belajar.jenis_rombel', '16')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', 'LIKE', '%Pilihan%')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', 'LIKE', '%Matpel%')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', 'LIKE', '%Mata Pelajaran%');
                });
            } else {
                $query->where(function ($q) {
                    $q->where('rombongan_belajar.jenis_rombel', '1')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', 'LIKE', '%Kelas%')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', 'LIKE', '%Reguler%')
                        ->orWhereNull('rombongan_belajar.jenis_rombel_str')
                        ->orWhere('rombongan_belajar.jenis_rombel_str', '');
                })->where(function ($q) {
                    $q->where('rombongan_belajar.jenis_rombel', '!=', '16')
                        ->where('rombongan_belajar.jenis_rombel_str', 'NOT LIKE', '%Pilihan%')
                        ->where('rombongan_belajar.jenis_rombel_str', 'NOT LIKE', '%Matpel%');
                });
            }
        };

        // Filter dropdown scoped by current type
        $filterTingkat = DB::table('rombongan_belajar')
            ->where($typeFilter)
            ->whereNotNull('tingkat_pendidikan_id_str')
            ->where('tingkat_pendidikan_id_str', '<>', '')
            ->distinct()
            ->pluck('tingkat_pendidikan_id_str')
            ->sort()
            ->values();

        $filterJurusan = DB::table('rombongan_belajar')
            ->where($typeFilter)
            ->whereNotNull('jurusan_id_str')
            ->where('jurusan_id_str', '<>', '')
            ->distinct()
            ->pluck('jurusan_id_str')
            ->sort()
            ->values();

        // Hitung peserta didik per rombel dari tabel peserta_didik dan anggota_rombel
        $pdCounts = Schema::hasTable('peserta_didik')
            ? DB::table('peserta_didik')
            ->whereNotNull('rombongan_belajar_id')
            ->where('rombongan_belajar_id', '<>', '')
            ->groupBy('rombongan_belajar_id')
            ->pluck(DB::raw('COUNT(*) as total'), 'rombongan_belajar_id')
            : collect();

        $arCounts = Schema::hasTable('anggota_rombel')
            ? DB::table('anggota_rombel')
            ->whereNotNull('rombongan_belajar_id')
            ->where('rombongan_belajar_id', '<>', '')
            ->groupBy('rombongan_belajar_id')
            ->pluck(DB::raw('COUNT(DISTINCT peserta_didik_id) as total'), 'rombongan_belajar_id')
            : collect();

        $baseQuery = DB::table('rombongan_belajar')
            ->where($typeFilter)
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

        $allResults = $baseQuery->get()->map(function ($item) use ($pdCounts, $arCounts) {
            $id = $item->rombongan_belajar_id;
            $c1 = (int) ($pdCounts[$id] ?? 0);
            $c2 = (int) ($arCounts[$id] ?? 0);
            $item->total_peserta_didik = max($c1, $c2);
            return $item;
        });

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
            'currentType',
            'q',
            'tingkat',
            'jurusan',
            'perPage',
            'sort',
            'sortDir'
        ));
    }

    /**
     * Detail peserta didik dan pembelajaran dalam suatu rombel via JSON untuk modal preview
     */
    public function showPesertaDidik(Request $request, string|int $id)
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
            $arStudentIds = Schema::hasTable('anggota_rombel')
                ? DB::table('anggota_rombel')
                ->where('rombongan_belajar_id', $id)
                ->pluck('peserta_didik_id')
                ->filter()
                ->toArray()
                : [];

            $pdQuery = DB::table('peserta_didik')
                ->where(function ($q) use ($id, $arStudentIds) {
                    $q->where('peserta_didik.rombongan_belajar_id', $id);
                    if (!empty($arStudentIds)) {
                        $q->orWhereIn('peserta_didik.peserta_didik_id', $arStudentIds);
                    }
                })
                ->select(
                    'peserta_didik.peserta_didik_id',
                    'peserta_didik.nama',
                    'peserta_didik.nisn',
                    'peserta_didik.nipd',
                    'peserta_didik.jenis_kelamin',
                    'peserta_didik.tempat_lahir',
                    'peserta_didik.tanggal_lahir',
                    'peserta_didik.jenis_pendaftaran_id_str as jenis_pendaftaran'
                )
                ->distinct();

            $pesertaDidik = $pdQuery->orderBy('peserta_didik.nama', 'asc')->get();
        }

        $pembelajaran = collect();
        if (Schema::hasTable('pembelajaran')) {
            $pembelajaran = DB::table('pembelajaran')
                ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
                ->where('pembelajaran.rombongan_belajar_id', $id)
                ->select(
                    'pembelajaran.pembelajaran_id',
                    'pembelajaran.nama_mata_pelajaran',
                    'pembelajaran.mata_pelajaran_id_str',
                    'pembelajaran.jam_mengajar_per_minggu',
                    'pembelajaran.status_di_kurikulum_str',
                    'gtk.nama as nama_guru',
                    'gtk.nuptk',
                    'gtk.nip',
                    'gtk.jenis_kelamin as guru_gender'
                )
                ->orderBy('pembelajaran.nama_mata_pelajaran', 'asc')
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
                'jenis_rombel' => $rombel->jenis_rombel_str ?: ($rombel->jenis_rombel == '16' ? 'Mata Pelajaran Pilihan' : 'Kelas (Reguler)'),
                'total_peserta_didik' => $pesertaDidik->count(),
                'total_mapel' => $pembelajaran->count(),
                'total_jam' => $pembelajaran->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
            ],
            'data' => $pesertaDidik,
            'pembelajaran' => $pembelajaran,
        ]);
    }
}
