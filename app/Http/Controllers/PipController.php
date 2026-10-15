<?php

namespace App\Http\Controllers;

use App\Models\PipKuotaRombel;
use App\Models\PipRiwayat;
use App\Models\PipUsulan;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\SimpleXlsxReader;
use App\Support\SemesterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PipController extends Controller
{
    /**
     * Resolusi konteks pengguna (Administrator atau Wali Kelas)
     */
    private function resolveContext(Request $request): array
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');

        // Hanya Administrator dan Guru (Wali Kelas) yang berhak mengakses modul PIP
        if (!$isAdmin && $role !== 'guru') {
            abort(403, 'Akses ditolak: Menu Usulan PIP khusus untuk Wali Kelas dan Administrator.');
        }

        $rombelList = collect();
        if (Schema::hasTable('rombongan_belajar')) {
            $rombelList = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->orderBy('tingkat_pendidikan_id', 'asc')
                ->orderBy('nama', 'asc')
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id_str', 'jurusan_id_str', 'ptk_id_str')
                ->get();
        }

        $activeRombel = null;
        if ($isAdmin) {
            $selectedRombelId = trim((string) $request->get('rombel_id', ''));
            if ($selectedRombelId !== '') {
                $activeRombel = $rombelList->firstWhere('rombongan_belajar_id', $selectedRombelId);
            }
        } else {
            $activeRombel = RolePermission::getWaliKelasRombelInfo($user);
        }

        $activeTahun = (string) $request->get('tahun', date('Y'));

        return [
            'user'         => $user,
            'role'         => $role,
            'isAdmin'      => $isAdmin,
            'rombelList'   => $rombelList,
            'activeRombel' => $activeRombel,
            'activeTahun'  => $activeTahun,
        ];
    }

    /**
     * Pastikan aksi pada usulan hanya dilakukan Admin atau Wali Kelas pemilik rombel usulan
     */
    private function authorizeUsulan(Request $request, PipUsulan $item): ?JsonResponse
    {
        $ctx = $this->resolveContext($request);
        if ($ctx['isAdmin']) {
            return null;
        }
        $rombel = $ctx['activeRombel'];
        if (!$rombel || $rombel->rombongan_belajar_id !== $item->rombongan_belajar_id) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: usulan ini bukan dari kelas binaan Anda.'], 403);
        }
        return null;
    }

    private function isAdminUser(): bool
    {
        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        return $role === 'admin';
    }

    /**
     * Halaman Utama Pengelolaan Usulan & Riwayat PIP
     */
    public function index(Request $request)
    {
        $ctx = $this->resolveContext($request);
        $user = $ctx['user'];
        $role = $ctx['role'];
        $isAdmin = $ctx['isAdmin'];
        $rombelList = $ctx['rombelList'];
        $activeRombel = $ctx['activeRombel'];
        $activeTahun = $ctx['activeTahun'];

        if (!$isAdmin && !$activeRombel) {
            abort(403, 'Akses ditolak: Menu Usulan PIP khusus untuk Wali Kelas dan Administrator.');
        }

        $tab = $request->get('tab', 'usulan'); // 'usulan', 'kuota' (admin only), 'riwayat'
        if (!$isAdmin && $tab === 'kuota') {
            $tab = 'usulan';
        }

        $q = trim((string) $request->get('q', ''));
        $statusVerif = trim((string) $request->get('status_verifikasi', ''));
        $perPageVal = $request->get('perPage', '15');
        $perPage = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 15;

        // 1. Data Tab: Usulan PIP
        $usulanList = null;
        $usulanQuery = DB::table('pip_usulan as u')
            ->join('peserta_didik as pd', 'u.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'u.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'u.*',
                'pd.nama as nama_pd',
                'pd.nisn',
                'pd.nipd',
                'pd.jenis_kelamin',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            )
            ->where('u.tahun', $activeTahun);

        if (!$isAdmin && $activeRombel) {
            $usulanQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        } elseif ($isAdmin && $activeRombel) {
            $usulanQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        }

        if ($statusVerif !== '' && isset(PipUsulan::STATUS_VERIFIKASI[$statusVerif])) {
            $usulanQuery->where('u.status_verifikasi', $statusVerif);
        }

        if ($q !== '') {
            $usulanQuery->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                    ->orWhere('u.keterangan', 'LIKE', "%{$q}%")
                    ->orWhere('u.alasan_usulan', 'LIKE', "%{$q}%");
            });
        }

        // Urutan: Prioritas/Rank menaik per kelas
        $usulanList = $usulanQuery
            ->orderBy('u.rombongan_belajar_id', 'asc')
            ->orderBy('u.prioritas', 'asc')
            ->orderBy('pd.nama', 'asc')
            ->paginate($perPage, ['*'], 'usulan_page')
            ->withQueryString();

        // Transform foto_url dan decode alasan_usulan
        $usulanList->getCollection()->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            $item->alasan_list = is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []);
            return $item;
        });

        // Hitung Statistik Usulan
        $statScope = DB::table('pip_usulan')->where('tahun', $activeTahun);
        if (!$isAdmin && $activeRombel) {
            $statScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        } elseif ($isAdmin && $activeRombel) {
            $statScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        }

        $totalUsulan = (clone $statScope)->count();
        $totalBelumDiajukan = (clone $statScope)->where('status_verifikasi', 'belum_diajukan')->count();
        $totalSudahDiajukan = (clone $statScope)->where('status_verifikasi', 'sudah_diajukan')->count();
        $totalDitolak = (clone $statScope)->where('status_verifikasi', 'ditolak')->count();

        // Kuota info untuk kelas yang aktif / sekolah
        $kuotaKelas = 5;
        $terpakaiKelas = 0;
        $sisaKuotaKelas = 5;
        if ($activeRombel) {
            $kuotaKelas = PipKuotaRombel::getKuotaForRombel($activeRombel->rombongan_belajar_id, $activeTahun);
            $terpakaiKelas = DB::table('pip_usulan')
                ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                ->where('tahun', $activeTahun)
                ->count();
            $sisaKuotaKelas = max(0, $kuotaKelas - $terpakaiKelas);
        }

        // Total pagu kuota seluruh sekolah
        $totalPaguSekolah = (int) DB::table('pip_kuota_rombel')->where('tahun', $activeTahun)->sum('kuota');
        if ($totalPaguSekolah === 0 && $rombelList->isNotEmpty()) {
            $totalPaguSekolah = $rombelList->count() * 5;
        }

        $stats = [
            'total_usulan'        => $totalUsulan,
            'belum_diajukan'      => $totalBelumDiajukan,
            'sudah_diajukan'      => $totalSudahDiajukan,
            'ditolak'             => $totalDitolak,
            'kuota_kelas'         => $kuotaKelas,
            'terpakai_kelas'      => $terpakaiKelas,
            'sisa_kuota_kelas'    => $sisaKuotaKelas,
            'total_pagu_sekolah'  => $totalPaguSekolah,
        ];

        // 2. Data Tab: Kuota per Rombel (Admin Only)
        $kuotaList = null;
        $kuotaStats = [
            'total_kuota'     => 0,
            'total_terpakai'  => 0,
            'total_sisa'      => 0,
            'total_rombel'    => 0,
            'rombel_penuh'    => 0,
            'rombel_tersedia' => 0,
        ];

        if ($isAdmin && $tab === 'kuota') {
            $allRombelData = $rombelList;
            $existingKuota = DB::table('pip_kuota_rombel')
                ->where('tahun', $activeTahun)
                ->pluck('kuota', 'rombongan_belajar_id')
                ->all();

            $existingCount = DB::table('pip_usulan')
                ->where('tahun', $activeTahun)
                ->select('rombongan_belajar_id', DB::raw('COUNT(*) as total'))
                ->groupBy('rombongan_belajar_id')
                ->pluck('total', 'rombongan_belajar_id')
                ->all();

            $mapped = $allRombelData->map(function ($r) use ($existingKuota, $existingCount) {
                $k = (int) ($existingKuota[$r->rombongan_belajar_id] ?? 5);
                $used = (int) ($existingCount[$r->rombongan_belajar_id] ?? 0);
                $r->kuota = $k;
                $r->terpakai = $used;
                $r->sisa = max(0, $k - $used);
                return $r;
            });

            $kuotaStats['total_kuota'] = (int) $mapped->sum('kuota');
            $kuotaStats['total_terpakai'] = (int) $mapped->sum('terpakai');
            $kuotaStats['total_sisa'] = max(0, $kuotaStats['total_kuota'] - $kuotaStats['total_terpakai']);
            $kuotaStats['total_rombel'] = $mapped->count();
            $kuotaStats['rombel_penuh'] = $mapped->where('sisa', '<=', 0)->count();
            $kuotaStats['rombel_tersedia'] = $mapped->where('sisa', '>', 0)->count();

            if ($q !== '') {
                $kuotaList = $mapped->filter(function ($r) use ($q) {
                    return stripos($r->nama, $q) !== false || stripos($r->tingkat_pendidikan_id_str, $q) !== false;
                });
            } else {
                $kuotaList = $mapped;
            }
        }

        // 3. Data Tab: Riwayat Penerima PIP (SiPintar / SK)
        $riwayatList = null;
        $riwayatTahapList = [];
        $riwayatStats = [
            'total_siswa'   => 0,
            'sudah_cair'    => 0,
            'belum_cair'    => 0,
            'total_nominal' => 0,
        ];

        if ($tab === 'riwayat') {
            $riwayatQuery = DB::table('pip_riwayat as r')
                ->leftJoin('peserta_didik as pd', 'r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->select(
                    'r.*',
                    'pdm.foto_path'
                );

            if (!$isAdmin && $activeRombel) {
                $riwayatQuery->where(function ($sub) use ($activeRombel) {
                    $sub->where('r.rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                        ->orWhere('r.rombel', $activeRombel->nama);
                });
            } elseif ($isAdmin && $activeRombel) {
                $riwayatQuery->where(function ($sub) use ($activeRombel) {
                    $sub->where('r.rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                        ->orWhere('r.rombel', $activeRombel->nama);
                });
            }

            if ($q !== '') {
                $riwayatQuery->where(function ($sub) use ($q) {
                    $sub->where('r.nama_pd', 'LIKE', "%{$q}%")
                        ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('r.nomor_sk', 'LIKE', "%{$q}%")
                        ->orWhere('r.rombel', 'LIKE', "%{$q}%")
                        ->orWhere('r.nama_ibu_kandung', 'LIKE', "%{$q}%");
                });
            }

            $tahapFilter = trim((string) $request->get('tahap', ''));
            if ($tahapFilter !== '') {
                $riwayatQuery->where('r.tahap', $tahapFilter);
            }

            // Hitung 4 statistik riwayat (genap)
            $statRiwayatQuery = clone $riwayatQuery;
            $riwayatStats['total_siswa'] = (clone $statRiwayatQuery)->count();
            $riwayatStats['sudah_cair'] = (clone $statRiwayatQuery)->where(function ($sq) {
                $sq->where('r.status_cair', 'LIKE', '%cair%')
                    ->where('r.status_cair', 'NOT LIKE', '%belum%');
            })->count();
            $riwayatStats['belum_cair'] = max(0, $riwayatStats['total_siswa'] - $riwayatStats['sudah_cair']);
            $riwayatStats['total_nominal'] = (float) (clone $statRiwayatQuery)->sum('r.nominal');

            $riwayatTahapList = DB::table('pip_riwayat')
                ->whereNotNull('tahap')
                ->where('tahap', '!=', '')
                ->distinct()
                ->pluck('tahap')
                ->all();

            $riwayatList = $riwayatQuery
                ->orderByDesc('r.tahun')
                ->orderByDesc('r.id')
                ->paginate($perPage, ['*'], 'riwayat_page')
                ->withQueryString();

            $riwayatList->getCollection()->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                return $item;
            });
        }

        $kriteriaAlasan = PipUsulan::KRITERIA_ALASAN;
        $statusVerifikasiList = PipUsulan::STATUS_VERIFIKASI;

        return view('dashboard.pip.index', compact(
            'isAdmin',
            'user',
            'role',
            'activeRombel',
            'rombelList',
            'activeTahun',
            'tab',
            'q',
            'statusVerif',
            'perPage',
            'usulanList',
            'stats',
            'kuotaList',
            'kuotaStats',
            'riwayatList',
            'riwayatTahapList',
            'riwayatStats',
            'kriteriaAlasan',
            'statusVerifikasiList'
        ));
    }

    /**
     * Autocomplete peserta didik untuk modal tambah usulan
     */
    public function cariSiswa(Request $request): JsonResponse
    {
        $ctx = $this->resolveContext($request);
        $activeRombel = $ctx['activeRombel'];
        $isAdmin = $ctx['isAdmin'];
        $activeTahun = $ctx['activeTahun'];

        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $query = DB::table('peserta_didik as pd')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel', 'pd.rombongan_belajar_id', 'pdm.foto_path')
            ->where(function ($s) use ($q) {
                $s->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%");
            });

        // Mode pencarian bebas (khusus Admin, untuk input riwayat): tanpa batas kelas & tanpa filter usulan
        $freeSearch = $isAdmin && $request->boolean('all_years');

        if (!$freeSearch) {
            // Untuk Wali Kelas, batasi hanya peserta didik di kelas binaannya
            if ($activeRombel) {
                $query->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            // Keluarkan siswa yang sudah pernah diusulkan di tahun aktif ini
            $alreadyProposed = DB::table('pip_usulan')
                ->where('tahun', $activeTahun)
                ->pluck('peserta_didik_id')
                ->all();

            if (!empty($alreadyProposed)) {
                $query->whereNotIn('pd.peserta_didik_id', $alreadyProposed);
            }
        }

        $rows = $query->orderBy('pd.nama')->limit(15)->get();

        $rows->transform(function ($item) {
            $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
            return $item;
        });

        return response()->json($rows);
    }

    /**
     * Simpan Usulan PIP Baru oleh Wali Kelas atau Admin
     */
    public function storeUsulan(Request $request)
    {
        $ctx = $this->resolveContext($request);
        $isAdmin = $ctx['isAdmin'];
        $activeRombel = $ctx['activeRombel'];
        $user = $ctx['user'];

        $data = $request->validate([
            'peserta_didik_id' => 'required|string|exists:peserta_didik,peserta_didik_id',
            'alasan_usulan'    => 'required|array|min:1',
            'alasan_usulan.*'  => 'required|string',
            'keterangan'       => 'nullable|string|max:1000',
            'tahun'            => 'nullable|string|max:20',
        ], [
            'peserta_didik_id.required' => 'Peserta didik wajib dipilih.',
            'alasan_usulan.required'    => 'Minimal pilih satu alasan/kriteria usulan PIP.',
            'alasan_usulan.min'         => 'Minimal pilih satu alasan/kriteria usulan PIP.',
        ]);

        $tahun = $data['tahun'] ?? ($ctx['activeTahun'] ?: date('Y'));
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $rombelId = $pd->rombongan_belajar_id ?: ($activeRombel?->rombongan_belajar_id ?? '');
        if (!$isAdmin && $activeRombel && $rombelId !== $activeRombel->rombongan_belajar_id) {
            return response()->json(['success' => false, 'message' => 'Anda hanya berhak mengusulkan peserta didik dari kelas binaan Anda.'], 403);
        }

        // Cek apakah siswa sudah diusulkan di tahun yang sama
        $exists = PipUsulan::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('tahun', $tahun)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => "Siswa {$pd->nama} sudah terdaftar dalam usulan PIP tahun {$tahun}."], 422);
        }

        // Cek Kuota Usulan Kelas
        $kuota = PipKuotaRombel::getKuotaForRombel($rombelId, $tahun);
        $currentCount = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->count();

        if ($currentCount >= $kuota && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => "Kuota usulan PIP kelas ini sudah penuh (Maksimal {$kuota} peserta didik). Hubungi Administrator jika membutuhkan penambahan kuota.",
            ], 422);
        }

        // Hitung Prioritas Otomatis: Ranking terakhir + 1
        $maxPrioritas = (int) PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->max('prioritas');
        $prioritasBaru = $maxPrioritas + 1;

        $pengusulNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Wali Kelas')) : ($user->nama ?? ($user->name ?? 'Wali Kelas'));

        PipUsulan::create([
            'peserta_didik_id'     => $pd->peserta_didik_id,
            'rombongan_belajar_id' => $rombelId,
            'tahun'                => $tahun,
            'prioritas'            => $prioritasBaru,
            'alasan_usulan'        => array_values($data['alasan_usulan']),
            'keterangan'           => trim((string) ($data['keterangan'] ?? '')),
            'diusulkan_oleh'       => $pengusulNama,
            'status_verifikasi'    => 'belum_diajukan',
        ]);

        $msg = "Peserta didik {$pd->nama} berhasil diusulkan dengan prioritas urutan ke-{$prioritasBaru}.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Ubah Urutan Prioritas / Ranking Usulan PIP (Naik/Turun atau Input Langsung)
     */
    public function updatePrioritas(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'        => 'required|integer|exists:pip_usulan,id',
            'direction' => 'nullable|in:up,down',
            'new_rank'  => 'nullable|integer|min:1',
        ]);

        $item = PipUsulan::findOrFail($data['id']);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }
        $rombelId = $item->rombongan_belajar_id;
        $tahun = $item->tahun;
        $currentRank = (int) $item->prioritas;

        // Ambil semua usulan di rombel yang sama diurutkan prioritas
        $all = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->orderBy('prioritas', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if (isset($data['direction'])) {
            if ($data['direction'] === 'up' && $currentRank > 1) {
                // Tukar dengan item di atasnya
                $target = $all->where('prioritas', '<', $currentRank)->last();
                if ($target) {
                    $item->prioritas = $target->prioritas;
                    $target->prioritas = $currentRank;
                    $target->save();
                    $item->save();
                }
            } elseif ($data['direction'] === 'down') {
                // Tukar dengan item di bawahnya
                $target = $all->where('prioritas', '>', $currentRank)->first();
                if ($target) {
                    $item->prioritas = $target->prioritas;
                    $target->prioritas = $currentRank;
                    $target->save();
                    $item->save();
                }
            }
        } elseif (isset($data['new_rank'])) {
            $newRank = max(1, (int) $data['new_rank']);
            $item->prioritas = $newRank;
            $item->save();

            // Re-sequence ranking agar urut tanpa bentrok
            $reordered = PipUsulan::where('rombongan_belajar_id', $rombelId)
                ->where('tahun', $tahun)
                ->orderBy('prioritas', 'asc')
                ->orderBy('updated_at', 'desc')
                ->get();

            $seq = 1;
            foreach ($reordered as $row) {
                $row->prioritas = $seq++;
                $row->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan prioritas usulan berhasil diperbarui.',
        ]);
    }

    /**
     * Hapus Data Usulan PIP
     */
    public function destroyUsulan(Request $request, int $id): JsonResponse
    {
        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }
        $rombelId = $item->rombongan_belajar_id;
        $tahun = $item->tahun;

        $item->delete();

        // Normalisasi urutan prioritas yang tersisa
        $remaining = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->orderBy('prioritas', 'asc')
            ->get();

        $seq = 1;
        foreach ($remaining as $r) {
            $r->prioritas = $seq++;
            $r->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Usulan PIP peserta didik berhasil dihapus.',
        ]);
    }

    /**
     * Verifikasi Usulan oleh Admin (Tandai sudah diajukan ke Web PIP atau belum)
     */
    public function updateVerifikasi(Request $request, int $id): JsonResponse
    {
        $user = session('user');
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'status_verifikasi' => 'required|in:belum_diajukan,sudah_diajukan,ditolak',
            'catatan_admin'     => 'nullable|string|max:500',
        ]);

        $item = PipUsulan::findOrFail($id);
        $item->status_verifikasi = $data['status_verifikasi'];
        if (array_key_exists('catatan_admin', $data)) {
            $item->catatan_admin = $data['catatan_admin'];
        }

        $adminNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));
        $item->diverifikasi_oleh = $adminNama;
        $item->verifikasi_admin_at = now();
        $item->save();

        $statusLabel = PipUsulan::STATUS_VERIFIKASI[$data['status_verifikasi']] ?? $data['status_verifikasi'];
        return response()->json([
            'success' => true,
            'message' => "Status usulan berhasil diperbarui menjadi {$statusLabel}.",
        ]);
    }

    /**
     * Bulk Verifikasi Usulan oleh Admin
     */
    public function bulkVerifikasi(Request $request): JsonResponse
    {
        $user = session('user');
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'integer|exists:pip_usulan,id',
            'action' => 'required|in:set_sudah_diajukan,set_belum_diajukan,set_ditolak',
        ]);

        $statusTarget = match ($data['action']) {
            'set_sudah_diajukan' => 'sudah_diajukan',
            'set_ditolak'        => 'ditolak',
            default              => 'belum_diajukan',
        };

        $adminNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));

        $count = PipUsulan::whereIn('id', $data['ids'])->update([
            'status_verifikasi'   => $statusTarget,
            'diverifikasi_oleh'   => $adminNama,
            'verifikasi_admin_at' => now(),
        ]);

        $label = PipUsulan::STATUS_VERIFIKASI[$statusTarget] ?? $statusTarget;
        return response()->json([
            'success' => true,
            'message' => "Sebanyak {$count} usulan berhasil diperbarui menjadi '{$label}'.",
        ]);
    }

    /**
     * Atur Kuota Usulan PIP per Rombel (Admin Only)
     * Mendukung: 
     * 1. Penentuan Kuota Berdasarkan Jumlah Total Sekolah (didistribusikan proporsional ke semua kelas)
     * 2. Menyamakan Kuota per Kelas (misal 5 siswa/kelas)
     * 3. Penyesuaian Kuota Per Kelas Spesifik
     */
    public function updateKuota(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'rombongan_belajar_id' => 'nullable|string',
            'kuota'                => 'nullable|integer|min:0|max:1000',
            'total_kuota'          => 'nullable|integer|min:0|max:10000',
            'tahun'                => 'nullable|string|max:20',
            'mode'                 => 'nullable|string|in:single,set_all,distribute_total',
            'set_all'              => 'nullable|boolean',
        ]);

        $tahun = ($data['tahun'] ?? null) ?: date('Y');
        $mode = $data['mode'] ?? (!empty($data['set_all']) ? 'set_all' : (!empty($data['total_kuota']) ? 'distribute_total' : 'single'));

        // 1. Penentuan secara Jumlah Total Sekolah (membagi total pagu ke seluruh rombel aktif)
        if ($mode === 'distribute_total' || isset($data['total_kuota'])) {
            $totalTarget = max(0, (int) ($data['total_kuota'] ?? 0));
            $rombels = DB::table('rombongan_belajar')
                ->where('jenis_rombel', '1')
                ->orderBy('tingkat_pendidikan_id', 'asc')
                ->orderBy('nama', 'asc')
                ->pluck('rombongan_belajar_id');

            $count = $rombels->count();
            if ($count === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada rombongan belajar aktif.'], 422);
            }

            $base = intdiv($totalTarget, $count);
            $rem = $totalTarget % $count;

            foreach ($rombels as $idx => $rId) {
                $allocated = $base + ($idx < $rem ? 1 : 0);
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => $allocated]
                );
            }

            return response()->json([
                'success' => true,
                'message' => "Total kuota {$totalTarget} siswa berhasil dibagikan ke {$count} kelas (rata-rata {$base}" . ($rem > 0 ? " hingga " . ($base + 1) : "") . " siswa/kelas).",
            ]);
        }

        // 2. Samakan Kuota untuk Semua Kelas (misal 5 siswa/kelas)
        if ($mode === 'set_all' || !empty($data['set_all'])) {
            $kuotaVal = (int) ($data['kuota'] ?? 5);
            $rombels = DB::table('rombongan_belajar')->where('jenis_rombel', '1')->pluck('rombongan_belajar_id');
            foreach ($rombels as $rId) {
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => $kuotaVal]
                );
            }
            $totalSemua = $rombels->count() * $kuotaVal;
            return response()->json([
                'success' => true,
                'message' => "Kuota seluruh ({$rombels->count()}) kelas berhasil disamakan menjadi {$kuotaVal} siswa/kelas (Total: {$totalSemua} siswa).",
            ]);
        }

        // 3. Atur Kuota Per Kelas Spesifik
        if (empty($data['rombongan_belajar_id'])) {
            return response()->json(['success' => false, 'message' => 'Rombel wajib dipilih.'], 422);
        }

        $kuotaVal = (int) ($data['kuota'] ?? 0);
        PipKuotaRombel::updateOrCreate(
            ['rombongan_belajar_id' => $data['rombongan_belajar_id'], 'tahun' => $tahun],
            ['kuota' => $kuotaVal]
        );

        return response()->json([
            'success' => true,
            'message' => "Kuota usulan kelas berhasil diperbarui menjadi {$kuotaVal} siswa.",
        ]);
    }

    /**
     * Nominal dari Excel: angka murni (250000 / 250000.00) atau teks berformat (Rp 1.800.000)
     */
    private function parseNominal(mixed $value): ?float
    {
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        if (is_numeric($s)) {
            return (float) $s;
        }
        $digits = preg_replace('/[^0-9]/', '', preg_replace('/[.,]\d{1,2}$/', '', $s));
        return $digits !== '' ? (float) $digits : null;
    }

    /**
     * Tanggal dari Excel: serial number (45123) dikonversi ke Y-m-d, teks dibiarkan apa adanya
     */
    private function parseTanggal(mixed $value): string
    {
        $s = trim((string) $value);
        if ($s !== '' && is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return gmdate('Y-m-d', (int) (((float) $s - 25569) * 86400));
        }
        return $s;
    }

    /**
     * Import File Excel / CSV Hasil Unduhan SiPintar ke Tabel Riwayat PIP
     */
    public function importRiwayatExcel(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $request->validate([
            'file_excel' => 'required|file|max:20480', // Max 20MB
        ]);

        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return response()->json(['success' => false, 'message' => 'Format file tidak didukung. Unggah berkas .xlsx atau .csv dari SiPintar (format .xls lama: simpan ulang sebagai .xlsx).'], 422);
        }

        $path = $file->getRealPath();

        try {
            $rows = SimpleXlsxReader::readFile($path);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Gagal membaca berkas Excel: ' . $e->getMessage()], 422);
        }

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'Berkas Excel kosong atau baris data tidak terdeteksi.'], 422);
        }

        // Cache pemetaan peserta_didik untuk menghubungkan ke rombongan_belajar_id
        $pdMapByPdId = DB::table('peserta_didik')->pluck('rombongan_belajar_id', 'peserta_didik_id')->all();
        $pdMapByNisn = DB::table('peserta_didik')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('rombongan_belajar_id', 'nisn')->all();

        $imported = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $nama = trim((string) ($row['nama_pd'] ?? ($row['nama'] ?? '')));
            if ($nama === '') {
                continue;
            }

            $pdId = trim((string) ($row['peserta_didik_id'] ?? ''));
            $nisn = trim((string) ($row['nisn'] ?? ''));
            $rombel = trim((string) ($row['rombel'] ?? ($row['nama_rombel'] ?? '')));
            $kelas = trim((string) ($row['kelas'] ?? ''));
            $ibu = trim((string) ($row['nama_ibu_kandung'] ?? ''));
            $ayah = trim((string) ($row['nama_ayah'] ?? ''));
            $tglLahir = $this->parseTanggal($row['tanggal_lahir'] ?? '');
            $tempatLahir = trim((string) ($row['tempat_lahir'] ?? ''));
            $jk = strtoupper(trim((string) ($row['jenis_kelamin'] ?? '')));

            $nominal = $this->parseNominal($row['nominal'] ?? '');

            $nomorSk = trim((string) ($row['nomor_sk'] ?? ''));
            $tglSk = $this->parseTanggal($row['tanggal_sk'] ?? '');
            $tahap = trim((string) ($row['tahap_keterangan'] ?? ($row['tahap_id'] ?? ($row['tahap'] ?? ''))));
            $tahun = trim((string) ($row['semester_id'] ?? date('Y')));
            if (strlen($tahun) >= 4) {
                $tahun = substr($tahun, 0, 4);
            }

            $statusCair = trim((string) ($row['status_cair'] ?? ''));
            $tglCair = $this->parseTanggal($row['tanggal_cair'] ?? '');
            $rekening = trim((string) ($row['no_rekening'] ?? ''));
            $noKip = trim((string) ($row['no_kip'] ?? ''));
            $noKks = trim((string) ($row['no_kks'] ?? ''));
            $noKps = trim((string) ($row['no_kps'] ?? ''));
            $virtualAcc = trim((string) ($row['virtual_acc'] ?? ''));
            $ketCair = trim((string) ($row['keterangan_pencairan'] ?? ''));

            // Hubungkan rombongan_belajar_id dari database lokal
            $rombelId = null;
            if ($pdId && isset($pdMapByPdId[$pdId])) {
                $rombelId = $pdMapByPdId[$pdId];
            } elseif ($nisn && isset($pdMapByNisn[$nisn])) {
                $rombelId = $pdMapByNisn[$nisn];
            }

            $criteria = [];
            if ($pdId !== '') {
                $criteria['peserta_didik_id'] = $pdId;
            } elseif ($nisn !== '') {
                $criteria['nisn'] = $nisn;
            } else {
                $criteria['nama_pd'] = $nama;
            }
            if ($nomorSk !== '') {
                $criteria['nomor_sk'] = $nomorSk;
            }
            if ($tahap !== '') {
                $criteria['tahap'] = $tahap;
            }

            $recordData = [
                'peserta_didik_id'     => $pdId ?: null,
                'nisn'                 => $nisn ?: null,
                'nama_pd'              => $nama,
                'kelas'                => $kelas ?: null,
                'rombel'               => $rombel ?: null,
                'rombongan_belajar_id' => $rombelId,
                'nama_ibu_kandung'     => $ibu ?: null,
                'nama_ayah'            => $ayah ?: null,
                'tanggal_lahir'        => $tglLahir ?: null,
                'tempat_lahir'         => $tempatLahir ?: null,
                'jenis_kelamin'        => $jk ?: null,
                'nominal'              => $nominal,
                'nomor_sk'             => $nomorSk ?: null,
                'tanggal_sk'           => $tglSk ?: null,
                'tahap'                => $tahap ?: null,
                'tahun'                => $tahun ?: date('Y'),
                'status_cair'          => $statusCair ?: null,
                'tanggal_cair'         => $tglCair ?: null,
                'no_rekening'          => $rekening ?: null,
                'no_kip'               => $noKip ?: null,
                'no_kks'               => $noKks ?: null,
                'no_kps'               => $noKps ?: null,
                'virtual_acc'          => $virtualAcc ?: null,
                'keterangan_pencairan' => $ketCair ?: null,
                'raw_data'             => $row,
            ];

            $existing = PipRiwayat::where($criteria)->first();
            if ($existing) {
                $existing->update($recordData);
                $updated++;
            } else {
                PipRiwayat::create($recordData);
                $imported++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Proses import selesai. Sebanyak {$imported} data baru berhasil ditambahkan, dan {$updated} data diperbarui.",
        ]);
    }

    /**
     * Tambah Riwayat PIP Manual (Admin Only)
     */
    public function storeRiwayatManual(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'peserta_didik_id' => 'nullable|string',
            'nisn'             => 'nullable|string|max:20',
            'nama_pd'          => 'required|string|max:150',
            'rombel'           => 'nullable|string|max:50',
            'nominal'          => 'nullable|numeric|min:0',
            'nomor_sk'         => 'nullable|string|max:100',
            'tahap'            => 'nullable|string|max:100',
            'tahun'            => 'nullable|string|max:20',
            'status_cair'      => 'nullable|string|max:50',
        ]);

        $rombelId = null;
        if (!empty($data['peserta_didik_id'])) {
            $rombelId = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->value('rombongan_belajar_id');
        }

        PipRiwayat::create([
            'peserta_didik_id'     => $data['peserta_didik_id'] ?: null,
            'nisn'                 => $data['nisn'] ?: null,
            'nama_pd'              => $data['nama_pd'],
            'rombel'               => $data['rombel'] ?: null,
            'rombongan_belajar_id' => $rombelId,
            'nominal'              => $data['nominal'] ?: null,
            'nomor_sk'             => $data['nomor_sk'] ?: null,
            'tahap'                => $data['tahap'] ?: null,
            'tahun'                => $data['tahun'] ?: date('Y'),
            'status_cair'          => $data['status_cair'] ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan riwayat PIP berhasil ditambahkan.',
        ]);
    }

    /**
     * Hapus Catatan Riwayat PIP (Admin Only)
     */
    public function destroyRiwayat(int $id): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        PipRiwayat::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catatan riwayat PIP berhasil dihapus.',
        ]);
    }
}
