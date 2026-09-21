<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PembelajaranController extends Controller
{
    private const SORTABLE = ['nama_mata_pelajaran', 'nama_rombel', 'nama_guru', 'jam_mengajar_per_minggu', 'status_di_kurikulum_str'];

    /**
     * Petakan rombel reguler dan rombel pilihan berdasarkan id_ruang
     */
    private function getRombelRoomMapping(): array
    {
        $regularRombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', 1)
            ->get(['rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id_str as tingkat', 'jurusan_id_str as jurusan', 'id_ruang', 'id_ruang_str'])
            ->keyBy('rombongan_belajar_id');

        $pilihanRombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '!=', 1)
            ->get(['rombongan_belajar_id', 'nama', 'jenis_rombel', 'jenis_rombel_str', 'id_ruang', 'id_ruang_str']);

        $rombelMap = []; // [any_rombel_id] => regular_rombel_object
        $regNameToRombelIds = []; // [reg_nama] => [rombel_id1, rombel_id2, ...]

        foreach ($regularRombels as $rId => $reg) {
            $rombelMap[$rId] = $reg;
            $regNameToRombelIds[$reg->nama] = [$rId];
        }

        foreach ($pilihanRombels as $pil) {
            $matched = null;
            if (!empty($pil->id_ruang)) {
                $matches = $regularRombels->where('id_ruang', $pil->id_ruang);
                if ($matches->count() === 1) {
                    $matched = $matches->first();
                } elseif ($matches->count() > 1) {
                    $matched = $matches->first(function ($r) use ($pil) {
                        return trim($r->nama) === trim($pil->nama)
                            || str_starts_with(trim($pil->nama), trim($r->nama))
                            || str_starts_with(trim($r->nama), trim($pil->nama));
                    }) ?: $matches->first();
                }
            }

            if (!$matched) {
                $matched = $regularRombels->first(function ($r) use ($pil) {
                    return trim($r->nama) === trim($pil->nama)
                        || str_starts_with(trim($pil->nama), trim($r->nama))
                        || str_starts_with(trim($r->nama), trim($pil->nama));
                });
            }

            if ($matched) {
                $rombelMap[$pil->rombongan_belajar_id] = $matched;
                $regNameToRombelIds[$matched->nama][] = $pil->rombongan_belajar_id;
            }
        }

        return [
            'rombelMap'          => $rombelMap,
            'regNameToRombelIds' => $regNameToRombelIds,
            'filterRombel'       => $regularRombels->pluck('nama')->sort()->values(),
        ];
    }

    public function index(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pembelajaran')) {
            return redirect()->route('dashboard.' . ($role ?: 'peserta_didik'))->with('error', 'Akses ke menu Pembelajaran dinonaktifkan oleh Administrator.');
        }

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

        $mapping = $this->getRombelRoomMapping();
        $filterRombel = $mapping['filterRombel'];

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
                'rombongan_belajar.nama as raw_nama_rombel',
                'rombongan_belajar.jenis_rombel',
                'rombongan_belajar.id_ruang',
                'rombongan_belajar.id_ruang_str',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.jurusan_id_str as jurusan',
                'gtk.nama as nama_guru',
                'gtk.nuptk',
                'gtk.nip',
                'gtk.jenis_kelamin as guru_gender'
            );

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

        // Filter Rombel: Menggabungkan kelas reguler & mapel pilihan berdasarkan id_ruang
        if ($rombel !== '') {
            $targetIds = $mapping['regNameToRombelIds'][$rombel] ?? [];
            if (!empty($targetIds)) {
                $baseQuery->whereIn('pembelajaran.rombongan_belajar_id', $targetIds);
            } else {
                $baseQuery->where('rombongan_belajar.nama', $rombel);
            }
        }

        if ($guru !== '') {
            $baseQuery->where('gtk.nama', $guru);
        }

        if ($status !== '') {
            $baseQuery->where('pembelajaran.status_di_kurikulum_str', $status);
        }

        if ($q !== '') {
            $matchedRombelIds = [];
            foreach ($mapping['regNameToRombelIds'] as $regName => $ids) {
                if (stripos($regName, $q) !== false) {
                    $matchedRombelIds = array_merge($matchedRombelIds, $ids);
                }
            }

            $baseQuery->where(function ($sub) use ($q, $matchedRombelIds) {
                $sub->where('pembelajaran.nama_mata_pelajaran', 'LIKE', "%{$q}%")
                    ->orWhere('pembelajaran.mata_pelajaran_id_str', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.nama', 'LIKE', "%{$q}%")
                    ->orWhere('rombongan_belajar.id_ruang_str', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nama', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nuptk', 'LIKE', "%{$q}%")
                    ->orWhere('gtk.nip', 'LIKE', "%{$q}%");

                if (!empty($matchedRombelIds)) {
                    $sub->orWhereIn('pembelajaran.rombongan_belajar_id', array_unique($matchedRombelIds));
                }
            });
        }

        $allResults = $baseQuery->get();

        // Transformasi nama rombel, tingkat, dan ruang agar mapel pilihan terpadu ke rombel reguler ruangannya
        $allResults->transform(function ($item) use ($mapping) {
            $reg = $mapping['rombelMap'][$item->rombongan_belajar_id] ?? null;
            $item->is_pilihan = ($item->jenis_rombel != 1);
            if ($reg) {
                $item->nama_rombel = $reg->nama;
                $item->tingkat     = $reg->tingkat ?: $item->tingkat;
                $item->jurusan     = $reg->jurusan ?: $item->jurusan;
                $item->ruang       = $reg->id_ruang_str ?: $item->id_ruang_str;
            } else {
                $item->nama_rombel = $item->raw_nama_rombel ?: '-';
                $item->ruang       = $item->id_ruang_str;
            }
            return $item;
        });

        $total = $allResults->count();

        $summary = [
            'total'  => $total,
            'rombel' => $allResults->pluck('nama_rombel')->filter()->unique()->count(),
            'guru'   => $allResults->pluck('ptk_id')->filter()->unique()->count(),
            'jam'    => $allResults->sum(fn($p) => (int) ($p->jam_mengajar_per_minggu ?? 0)),
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
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        if (!\App\Models\RolePermission::canAccess($role, 'menu_pembelajaran')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak oleh Administrator.'], 403);
        }

        $pem = DB::table('pembelajaran')
            ->leftJoin('rombongan_belajar', 'pembelajaran.rombongan_belajar_id', '=', 'rombongan_belajar.rombongan_belajar_id')
            ->leftJoin('gtk', 'pembelajaran.ptk_id', '=', 'gtk.ptk_id')
            ->where('pembelajaran.pembelajaran_id', $id)
            ->select(
                'pembelajaran.*',
                'rombongan_belajar.nama as raw_nama_rombel',
                'rombongan_belajar.jenis_rombel',
                'rombongan_belajar.jenis_rombel_str',
                'rombongan_belajar.tingkat_pendidikan_id_str as tingkat',
                'rombongan_belajar.jurusan_id_str as jurusan',
                'rombongan_belajar.kurikulum_id_str as kurikulum',
                'rombongan_belajar.id_ruang',
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

        $mapping = $this->getRombelRoomMapping();
        $reg = $mapping['rombelMap'][$pem->rombongan_belajar_id] ?? null;
        $isPilihan = ($pem->jenis_rombel != 1);

        $pem->is_pilihan = $isPilihan;
        if ($reg) {
            $pem->nama_rombel = $reg->nama . ($isPilihan ? ' (Mapel Pilihan Tergabung)' : '');
            $pem->tingkat     = $reg->tingkat ?: $pem->tingkat;
            $pem->jurusan     = $reg->jurusan ?: $pem->jurusan;
            $pem->ruang       = $reg->id_ruang_str ?: $pem->ruang;
        } else {
            $pem->nama_rombel = $pem->raw_nama_rombel ?: '-';
        }

        return response()->json([
            'status' => 'success',
            'data'   => $pem,
        ]);
    }
}
