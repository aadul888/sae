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
     * Memastikan tabel pip_kuota_rombel, pip_usulan, dan pip_riwayat beserta kolomnya
     * sudah tercipta di database runtime tanpa memerlukan migrasi manual dari terminal.
     */
    private function ensurePipSchema(): void
    {
        try {
            if (!Schema::hasTable('pip_kuota_rombel')) {
                Schema::create('pip_kuota_rombel', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('rombongan_belajar_id', 50)->index();
                    $table->string('tahun', 20)->default('2026')->index();
                    $table->unsignedInteger('kuota')->default(5);
                    $table->text('catatan')->nullable();
                    $table->timestamps();
                    $table->unique(['rombongan_belajar_id', 'tahun'], 'uq_pip_kuota_rombel_tahun');
                });
            }

            if (!Schema::hasTable('pip_usulan')) {
                Schema::create('pip_usulan', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('peserta_didik_id', 50)->index();
                    $table->string('rombongan_belajar_id', 50)->index();
                    $table->string('tahun', 20)->default('2026')->index();
                    $table->unsignedInteger('prioritas')->default(1)->index();
                    $table->longText('alasan_usulan')->nullable();
                    $table->text('keterangan')->nullable();
                    $table->string('diusulkan_oleh', 100)->nullable();
                    $table->string('status_verifikasi', 30)->default('belum_diajukan')->index();
                    $table->string('diverifikasi_oleh', 100)->nullable();
                    $table->dateTime('verifikasi_admin_at')->nullable();
                    $table->text('catatan_admin')->nullable();
                    $table->timestamps();
                    $table->unique(['peserta_didik_id', 'tahun'], 'uq_pip_usulan_pd_tahun');
                });
            }

            if (!Schema::hasTable('pip_riwayat')) {
                Schema::create('pip_riwayat', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('peserta_didik_id', 50)->nullable()->index();
                    $table->string('nisn', 20)->nullable()->index();
                    $table->string('nama_pd', 150)->index();
                    $table->string('kelas', 10)->nullable();
                    $table->string('rombel', 50)->nullable()->index();
                    $table->string('rombongan_belajar_id', 50)->nullable()->index();
                    $table->string('nama_ibu_kandung', 100)->nullable();
                    $table->string('nama_ayah', 100)->nullable();
                    $table->string('tanggal_lahir', 30)->nullable();
                    $table->string('tempat_lahir', 100)->nullable();
                    $table->string('jenis_kelamin', 5)->nullable();
                    $table->decimal('nominal', 12, 2)->unsigned()->nullable();
                    $table->string('nomor_sk', 100)->nullable()->index();
                    $table->string('jenis_sk', 30)->default('pemberian')->index();
                    $table->string('tanggal_sk', 30)->nullable();
                    $table->string('tahap', 100)->nullable()->index();
                    $table->string('tahun', 20)->nullable()->index();
                    $table->string('status_cair', 50)->nullable()->index();
                    $table->string('tanggal_cair', 30)->nullable();
                    $table->string('tanggal_aktivasi', 30)->nullable();
                    $table->string('no_rekening', 50)->nullable();
                    $table->string('no_kip', 50)->nullable();
                    $table->string('no_kks', 50)->nullable();
                    $table->string('no_kps', 50)->nullable();
                    $table->string('virtual_acc', 50)->nullable();
                    $table->text('keterangan_pencairan')->nullable();
                    $table->longText('raw_data')->nullable();
                    $table->timestamps();
                });
            } else {
                Schema::table('pip_riwayat', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('pip_riwayat', 'jenis_sk')) {
                        $table->string('jenis_sk', 30)->default('pemberian')->after('nomor_sk')->index();
                    }
                    if (!Schema::hasColumn('pip_riwayat', 'tanggal_aktivasi')) {
                        $table->string('tanggal_aktivasi', 30)->nullable()->after('tanggal_cair');
                    }
                });
            }
        } catch (\Throwable $e) {
            // Biarkan lewat jika tabel sudah siap
        }
    }

    /**
     * Resolusi konteks pengguna (Administrator atau Wali Kelas)
     */
    private function resolveContext(Request $request): array
    {
        $this->ensurePipSchema();

        $user = session('user');
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');

        // Hak akses: Admin memiliki akses penuh; Wali Kelas harus memiliki tugas tambahan Wali Kelas atau izin menu_wali_kelas_pip
        $isWaliKelas = RolePermission::isWaliKelasOrAdmin($user);
        $canAccessPip = $isAdmin || (
            $isWaliKelas && (
                RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'read') ||
                RolePermission::canAccess($user, 'menu_wali_kelas', 'read') ||
                RolePermission::canAccess($user, 'menu_pip', 'read')
            )
        );

        if (!$canAccessPip) {
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
            if (!$activeRombel && Schema::hasTable('rombongan_belajar')) {
                $ptkId = is_array($user) ? ($user['ptk_id'] ?? null) : ($user->ptk_id ?? null);
                $userName = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? '')) : ($user->name ?? ($user->nama ?? ''));
                if ($ptkId) {
                    $activeRombel = DB::table('rombongan_belajar')->where('ptk_id', $ptkId)->first();
                }
                if (!$activeRombel && $userName) {
                    $activeRombel = DB::table('rombongan_belajar')->where('ptk_id_str', $userName)->first();
                }
            }
            if (!$activeRombel) {
                abort(403, 'Akses ditolak: Anda tidak memiliki rombongan belajar binaan aktif sebagai Wali Kelas.');
            }
        }

        $activeTahun = (string) $request->get('tahun', date('Y'));

        return [
            'user'         => $user,
            'role'         => $role,
            'isAdmin'      => $isAdmin,
            'isWaliKelas'  => $isWaliKelas,
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
        $jenisSkFilter = trim((string) $request->get('jenis_sk', ''));
        $perPageVal = $request->get('perPage', '15');
        $perPage = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 15;

        $hasUsulanTable = Schema::hasTable('pip_usulan');
        $hasKuotaTable = Schema::hasTable('pip_kuota_rombel');
        $hasRiwayatTable = Schema::hasTable('pip_riwayat');

        // 1. Data Tab: Usulan PIP
        $usulanList = null;
        if ($hasUsulanTable) {
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
        } else {
            $usulanList = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'usulan_page', 'query' => $request->query()]
            );
        }

        // Hitung Statistik Usulan
        $totalUsulan = 0;
        $totalBelumDiajukan = 0;
        $totalSudahDiajukan = 0;
        $totalDitolak = 0;

        if ($hasUsulanTable) {
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
        }

        // Kuota info untuk kelas yang aktif / sekolah
        $kuotaKelas = 5;
        $terpakaiKelas = 0;
        $sisaKuotaKelas = 5;
        if ($activeRombel) {
            $kuotaKelas = PipKuotaRombel::getKuotaForRombel($activeRombel->rombongan_belajar_id, $activeTahun);
            if ($hasUsulanTable) {
                $terpakaiKelas = DB::table('pip_usulan')
                    ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                    ->where('tahun', $activeTahun)
                    ->count();
            }
            $sisaKuotaKelas = max(0, $kuotaKelas - $terpakaiKelas);
        }

        // Total pagu kuota seluruh sekolah
        $totalPaguSekolah = $rombelList->count() * 5;
        if ($hasKuotaTable) {
            $targetPaguStored = PipKuotaRombel::getTargetTotalSekolah($activeTahun);
            $totalPaguSekolah = $targetPaguStored ?? (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $activeTahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            if ($totalPaguSekolah === 0 && $rombelList->isNotEmpty()) {
                $totalPaguSekolah = $rombelList->count() * 5;
            }
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
            'target_total'     => $totalPaguSekolah,
            'total_teralokasi' => 0,
            'selisih'          => 0,
            'status_selisih'   => 'pas',
            'selisih_label'    => 'Pas',
            'selisih_text'     => 'Alokasi kuota sesuai 100% target sekolah',
            'total_terpakai'   => 0,
            'total_sisa'       => 0,
            'total_rombel'     => $rombelList->count(),
            'rombel_penuh'     => 0,
            'rombel_tersedia'  => $rombelList->count(),
        ];

        if ($isAdmin && $tab === 'kuota') {
            $allRombelData = $rombelList;
            $existingKuota = [];
            $existingCount = [];

            if ($hasKuotaTable) {
                $existingKuota = DB::table('pip_kuota_rombel')
                    ->where('tahun', $activeTahun)
                    ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                    ->pluck('kuota', 'rombongan_belajar_id')
                    ->all();
            }

            if ($hasUsulanTable) {
                $existingCount = DB::table('pip_usulan')
                    ->where('tahun', $activeTahun)
                    ->select('rombongan_belajar_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('rombongan_belajar_id')
                    ->pluck('total', 'rombongan_belajar_id')
                    ->all();
            }

            $studentCounts = [];
            $totalSiswaSekolah = 0;
            if (Schema::hasTable('peserta_didik')) {
                $studentCounts = DB::table('peserta_didik')
                    ->select('rombongan_belajar_id', DB::raw('count(*) as total'))
                    ->groupBy('rombongan_belajar_id')
                    ->pluck('total', 'rombongan_belajar_id')
                    ->all();
                foreach ($allRombelData as $r) {
                    $totalSiswaSekolah += (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                }
            }

            $mapped = $allRombelData->map(function ($r) use ($existingKuota, $existingCount, $studentCounts, $totalSiswaSekolah) {
                $k = (int) ($existingKuota[$r->rombongan_belajar_id] ?? 5);
                $used = (int) ($existingCount[$r->rombongan_belajar_id] ?? 0);
                $jmlSiswa = (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                $persen = $totalSiswaSekolah > 0 ? round(($jmlSiswa / $totalSiswaSekolah) * 100, 1) : 0;

                $r->kuota = $k;
                $r->terpakai = $used;
                $r->sisa = max(0, $k - $used);
                $r->jumlah_siswa = $jmlSiswa;
                $r->persen_siswa = $persen;
                return $r;
            });

            $totalTeralokasi = (int) $mapped->sum('kuota');
            $targetSekolah = ($hasKuotaTable && isset($targetPaguStored)) ? $targetPaguStored : $totalTeralokasi;
            $selisih = $totalTeralokasi - $targetSekolah;

            $statusSelisih = 'pas';
            $selisihLabel = '0 (Pas)';
            $selisihText = 'Alokasi kuota pas sesuai target pagu sekolah (100%).';

            if ($selisih > 0) {
                $statusSelisih = 'kelebihan';
                $selisihLabel = '+' . $selisih . ' Siswa';
                $selisihText = "Kelebihan {$selisih} siswa dari target pagu sekolah ({$targetSekolah} siswa).";
            } elseif ($selisih < 0) {
                $statusSelisih = 'kurang';
                $selisihLabel = $selisih . ' Siswa';
                $selisihText = 'Kurang ' . abs($selisih) . " siswa untuk memenuhi target pagu sekolah ({$targetSekolah} siswa).";
            }

            $kuotaStats = [
                'target_total'     => $targetSekolah,
                'total_teralokasi' => $totalTeralokasi,
                'selisih'          => $selisih,
                'status_selisih'   => $statusSelisih,
                'selisih_label'    => $selisihLabel,
                'selisih_text'     => $selisihText,
                'total_terpakai'   => (int) $mapped->sum('terpakai'),
                'total_sisa'       => max(0, $targetSekolah - (int) $mapped->sum('terpakai')),
                'total_rombel'     => $mapped->count(),
                'rombel_penuh'     => $mapped->where('sisa', '<=', 0)->count(),
                'rombel_tersedia'  => $mapped->where('sisa', '>', 0)->count(),
            ];

            if ($q !== '') {
                $filtered = $mapped->filter(function ($r) use ($q) {
                    return stripos($r->nama, $q) !== false || stripos($r->tingkat_pendidikan_id_str, $q) !== false;
                })->values();
            } else {
                $filtered = $mapped->values();
            }

            // Datatable Global Pagination untuk Kuota per Kelas
            $currentPage = max(1, (int) $request->get('kuota_page', 1));
            $totalRows = $filtered->count();
            $slice = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $kuotaList = new \Illuminate\Pagination\LengthAwarePaginator(
                $slice,
                $totalRows,
                $perPage,
                $currentPage,
                [
                    'path'     => $request->url(),
                    'pageName' => 'kuota_page',
                    'query'    => $request->query(),
                ]
            );
        }

        // 3. Data Tab: Riwayat Penerima PIP (SiPintar / SK)
        $riwayatList = null;
        $riwayatTahapList = [];
        $riwayatStats = [
            'total_siswa'     => 0,
            'total_pemberian' => 0,
            'total_nominasi'  => 0,
            'total_nominal'   => 0,
        ];

        if ($tab === 'riwayat') {
            if ($hasRiwayatTable) {
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

                $hasJenisSk = Schema::hasColumn('pip_riwayat', 'jenis_sk');
                $jenisSkFilter = trim((string) $request->get('jenis_sk', ''));
                if ($hasJenisSk && $jenisSkFilter !== '' && in_array($jenisSkFilter, ['pemberian', 'nominasi'], true)) {
                    $riwayatQuery->where('r.jenis_sk', $jenisSkFilter);
                }

                // Hitung 4 statistik riwayat (genap: Total Siswa, SK Pemberian, SK Nominasi, Total Dana)
                $statRiwayatQuery = clone $riwayatQuery;
                $riwayatStats['total_siswa'] = (clone $statRiwayatQuery)->count();
                $riwayatStats['total_pemberian'] = $hasJenisSk
                    ? (clone $statRiwayatQuery)->where('r.jenis_sk', 'pemberian')->count()
                    : $riwayatStats['total_siswa'];
                $riwayatStats['total_nominasi'] = $hasJenisSk
                    ? (clone $statRiwayatQuery)->where('r.jenis_sk', 'nominasi')->count()
                    : 0;
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
            } else {
                $riwayatList = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    1,
                    ['path' => $request->url(), 'pageName' => 'riwayat_page', 'query' => $request->query()]
                );
            }
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
            'jenisSkFilter',
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

        if (!$isAdmin && !RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'create') && !RolePermission::canAccess($user, 'menu_wali_kelas', 'create')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin menambah usulan PIP.'], 403);
        }

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

        // Cek Kuota Usulan Kelas (Pengisian tidak boleh melebihi kuota)
        $kuota = PipKuotaRombel::getKuotaForRombel($rombelId, $tahun);
        $currentCount = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->count();

        if ($currentCount >= $kuota) {
            return response()->json([
                'success' => false,
                'message' => "Kuota usulan PIP kelas ini sudah penuh (Maksimal {$kuota} peserta didik). Pengusulan tidak boleh melebihi kuota. Hubungi Administrator jika memerlukan penyesuaian kuota.",
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
     * Khusus Wali Kelas (Admin Readonly)
     */
    public function updatePrioritas(Request $request): JsonResponse
    {
        $ctx = $this->resolveContext($request);

        // Administrator mode: readonly, tidak bisa mengubah urutan prioritas
        if ($ctx['isAdmin']) {
            return response()->json([
                'success' => false,
                'message' => 'Urutan prioritas hanya dapat diatur oleh Wali Kelas binaan.',
            ], 403);
        }

        if (!RolePermission::canAccess($ctx['user'], 'menu_wali_kelas_pip', 'update') && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengubah prioritas usulan PIP.'], 403);
        }

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

        // Ambil seluruh usulan rombel terurut
        $list = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->orderBy('prioritas', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->values();

        $currentIndex = $list->search(fn($r) => (int) $r->id === (int) $item->id);
        if ($currentIndex === false) {
            return response()->json(['success' => false, 'message' => 'Data usulan tidak ditemukan.'], 404);
        }

        if (!empty($data['direction'])) {
            $dir = $data['direction'];
            if ($dir === 'up' && $currentIndex > 0) {
                $other = $list[$currentIndex - 1];
                $list[$currentIndex - 1] = $item;
                $list[$currentIndex] = $other;
            } elseif ($dir === 'down' && $currentIndex < $list->count() - 1) {
                $other = $list[$currentIndex + 1];
                $list[$currentIndex + 1] = $item;
                $list[$currentIndex] = $other;
            }
        } elseif (!empty($data['new_rank'])) {
            $targetRank = max(1, min((int) $data['new_rank'], $list->count()));
            $targetIndex = $targetRank - 1;
            if ($targetIndex !== $currentIndex) {
                $itemToMove = $list->pull($currentIndex);
                $list->splice($targetIndex, 0, [$itemToMove]);
            }
        }

        // Terapkan nomor urut 1, 2, 3, ... secara sekuensial tanpa celah/bentrok
        foreach ($list->values() as $idx => $row) {
            $seq = $idx + 1;
            if ((int) $row->prioritas !== $seq) {
                PipUsulan::where('id', $row->id)->update(['prioritas' => $seq]);
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
        $ctx = $this->resolveContext($request);
        if (!$ctx['isAdmin'] && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas_pip', 'delete') && !RolePermission::canAccess($ctx['user'], 'menu_wali_kelas', 'delete')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin menghapus usulan PIP.'], 403);
        }

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
     * Hitung alokasi kuota per rombel berdasarkan persentase jumlah peserta didik aktif.
     * Menggunakan metode Hamilton / Largest Remainder Method agar distribusi 100% adil, proporsional, dan presisi tanpa sisa.
     */
    private function calculateProportionalQuota(int $totalTarget): array
    {
        $rombels = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc')
            ->get(['rombongan_belajar_id', 'nama']);

        if ($rombels->isEmpty()) {
            return [];
        }

        $studentCounts = [];
        if (Schema::hasTable('peserta_didik')) {
            $studentCounts = DB::table('peserta_didik')
                ->select('rombongan_belajar_id', DB::raw('count(*) as total'))
                ->groupBy('rombongan_belajar_id')
                ->pluck('total', 'rombongan_belajar_id')
                ->all();
        }

        $totalStudents = 0;
        foreach ($rombels as $r) {
            $totalStudents += (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
        }

        // Fallback jika belum ada data siswa: bagi rata
        if ($totalStudents === 0 || $totalTarget === 0) {
            $count = $rombels->count();
            $base = $count > 0 ? intdiv($totalTarget, $count) : 0;
            $rem = $count > 0 ? $totalTarget % $count : 0;
            $result = [];
            foreach ($rombels as $idx => $r) {
                $result[$r->rombongan_belajar_id] = $base + ($idx < $rem ? 1 : 0);
            }
            return $result;
        }

        // Largest Remainder Method (Hamilton Method)
        $allocations = [];
        $remainders = [];
        $allocatedSum = 0;

        foreach ($rombels as $r) {
            $c = (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
            $exactQuota = ($c / $totalStudents) * $totalTarget;
            $floorQuota = (int) floor($exactQuota);

            $allocations[$r->rombongan_belajar_id] = $floorQuota;
            $remainders[$r->rombongan_belajar_id] = $exactQuota - $floorQuota;
            $allocatedSum += $floorQuota;
        }

        // Bagikan sisa kursi ke rombel dengan sisa desimal terbesar
        $remainingSeats = $totalTarget - $allocatedSum;
        if ($remainingSeats > 0) {
            arsort($remainders);
            foreach ($remainders as $rId => $rem) {
                if ($remainingSeats <= 0) break;
                $allocations[$rId]++;
                $remainingSeats--;
            }
        }

        return $allocations;
    }

    /**
     * Atur Kuota Usulan PIP per Rombel (Admin Only)
     * Mendukung: 
     * 1. Penentuan Kuota Berdasarkan Persentase Jumlah Siswa Aktif (Proporsional & Adil)
     * 2. Simpan Semua Perubahan Kuota (1 Tombol Simpan Tunggal untuk Seluruh Kelas)
     * 3. Menyamakan Kuota Rata per Kelas (misal 5 siswa/kelas)
     */
    public function updateKuota(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $this->ensurePipSchema();

        $data = $request->validate([
            'kuota_map'            => 'nullable|array',
            'kuota_map.*'          => 'integer|min:0|max:1000',
            'rombongan_belajar_id' => 'nullable|string',
            'kuota'                => 'nullable|integer|min:0|max:1000',
            'total_kuota'          => 'nullable|integer|min:0|max:10000',
            'tahun'                => 'nullable|string|max:20',
            'mode'                 => 'nullable|string|in:single,batch,set_all,distribute_total,distribute_proportional',
            'set_all'              => 'nullable|boolean',
        ]);

        $tahun = ($data['tahun'] ?? null) ?: date('Y');
        $mode = $data['mode'] ?? (!empty($data['kuota_map']) ? 'batch' : (!empty($data['set_all']) ? 'set_all' : (!empty($data['total_kuota']) ? 'distribute_proportional' : 'single')));

        // 1. Simpan Seluruh Perubahan Kuota Sekaligus (1 Tombol Simpan Tunggal)
        if ($mode === 'batch' || !empty($data['kuota_map'])) {
            $map = (array) ($data['kuota_map'] ?? []);
            if (empty($map)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data kuota yang dikirim.'], 422);
            }

            $totalBatch = 0;
            foreach ($map as $rId => $val) {
                $kVal = max(0, (int) $val);
                $totalBatch += $kVal;
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => (string) $rId, 'tahun' => $tahun],
                    ['kuota' => $kVal]
                );
            }

            // Perbarui pagu total sekolah
            PipKuotaRombel::setTargetTotalSekolah($totalBatch, $tahun);

            $countSaved = count($map);
            return response()->json([
                'success' => true,
                'message' => "Semua perubahan kuota untuk {$countSaved} kelas berhasil disimpan (Total Pagu: {$totalBatch} siswa).",
            ]);
        }

        // 2. Penentuan Kuota Berdasarkan Persentase Jumlah Peserta Didik (Adil & Proporsional)
        if ($mode === 'distribute_proportional' || $mode === 'distribute_total' || isset($data['total_kuota'])) {
            $totalTarget = max(0, (int) ($data['total_kuota'] ?? 0));
            PipKuotaRombel::setTargetTotalSekolah($totalTarget, $tahun);

            $proportionalAllocations = $this->calculateProportionalQuota($totalTarget);
            $count = count($proportionalAllocations);

            if ($count === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada rombongan belajar aktif.'], 422);
            }

            foreach ($proportionalAllocations as $rId => $kVal) {
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => (int) $kVal]
                );
            }

            return response()->json([
                'success' => true,
                'message' => "Pagu {$totalTarget} siswa berhasil dibagikan secara adil ke {$count} kelas berdasarkan persentase jumlah peserta didik aktif.",
            ]);
        }

        // 3. Samakan Kuota untuk Semua Kelas (Bagi Rata, misal 5 siswa/kelas)
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
            PipKuotaRombel::setTargetTotalSekolah($totalSemua, $tahun);

            return response()->json([
                'success' => true,
                'message' => "Kuota seluruh ({$rombels->count()}) kelas berhasil disamakan menjadi {$kuotaVal} siswa/kelas (Total pagu: {$totalSemua} siswa).",
            ]);
        }

        // 4. Atur Kuota Per Kelas Tunggal
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
     * Import File Excel (.xlsx / .xls) / CSV Hasil Unduhan SiPintar ke Tabel Riwayat PIP
     * Mendukung 2 Jenis SK:
     * 1. SK Pemberian (Pencairan Dana)
     * 2. SK Nominasi (Aktivasi Rekening) -> Validasi: otomatis abaikan/skip siswa yang sudah terdaftar di SK Pemberian
     */
    public function importRiwayatExcel(Request $request): JsonResponse
    {
        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $this->ensurePipSchema();

        $request->validate([
            'file_excel' => 'required|file|max:30720', // Max 30MB
            'jenis_sk'   => 'required|in:pemberian,nominasi',
        ], [
            'file_excel.required' => 'Berkas Excel wajib diunggah.',
            'jenis_sk.required'   => 'Pilih jenis SK yang diunggah (SK Pemberian atau SK Nominasi).',
        ]);

        $jenisSk = $request->get('jenis_sk', 'pemberian');
        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return response()->json(['success' => false, 'message' => 'Format file tidak didukung. Unggah berkas .xlsx, .xls, atau .csv dari SiPintar.'], 422);
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
        $skippedPemberianCount = 0;

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

            // VALIDASI KHUSUS SK NOMINASI: Jangan simpan jika siswa SUDAH ADA pada SK Pemberian!
            if ($jenisSk === 'nominasi') {
                $hasPemberian = PipRiwayat::where('jenis_sk', 'pemberian')
                    ->where(function ($q) use ($pdId, $nisn, $nama, $rombel) {
                        if ($pdId !== '') {
                            $q->where('peserta_didik_id', $pdId);
                        } elseif ($nisn !== '') {
                            $q->where('nisn', $nisn);
                        } else {
                            $q->where('nama_pd', $nama);
                            if ($rombel !== '') {
                                $q->where('rombel', $rombel);
                            }
                        }
                    })
                    ->exists();

                if ($hasPemberian) {
                    $skippedPemberianCount++;
                    continue; // Lewati! Siswa sudah menerima SK Pemberian
                }
            }

            $nominal = $this->parseNominal($row['nominal'] ?? '');

            $nomorSk = trim((string) ($row['nomor_sk'] ?? ''));
            $tglSk = $this->parseTanggal($row['tanggal_sk'] ?? '');
            $tahap = trim((string) ($row['tahap_keterangan'] ?? ($row['tahap_id'] ?? ($row['tahap'] ?? ''))));
            $tahun = trim((string) ($row['semester_id'] ?? date('Y')));
            if (strlen($tahun) >= 4) {
                $tahun = substr($tahun, 0, 4);
            }

            $tglCair = $this->parseTanggal($row['tanggal_cair'] ?? '');
            $tglAktivasi = $this->parseTanggal($row['tanggal_aktivasi'] ?? '');

            if ($jenisSk === 'nominasi') {
                $statusCair = !empty($row['status_cair'])
                    ? trim((string) $row['status_cair'])
                    : ($tglAktivasi !== '' ? 'Sudah Aktivasi Rekening' : 'Belum Aktivasi Rekening');
            } else {
                $statusCair = trim((string) ($row['status_cair'] ?? ''));
            }

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

            $criteria = [
                'jenis_sk' => $jenisSk,
            ];
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
                'jenis_sk'             => $jenisSk,
                'tanggal_sk'           => $tglSk ?: null,
                'tahap'                => $tahap ?: null,
                'tahun'                => $tahun ?: date('Y'),
                'status_cair'          => $statusCair ?: null,
                'tanggal_cair'         => $tglCair ?: null,
                'tanggal_aktivasi'     => $tglAktivasi ?: null,
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

        $labelSk = $jenisSk === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian';
        $msg = "Import {$labelSk} selesai. Sebanyak {$imported} data baru berhasil ditambahkan, dan {$updated} data diperbarui.";
        if ($skippedPemberianCount > 0) {
            $msg .= " Sebanyak {$skippedPemberianCount} siswa dilewati karena sudah terdaftar di SK Pemberian.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
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

        $this->ensurePipSchema();

        $data = $request->validate([
            'peserta_didik_id' => 'nullable|string',
            'nisn'             => 'nullable|string|max:20',
            'nama_pd'          => 'required|string|max:150',
            'rombel'           => 'nullable|string|max:50',
            'nominal'          => 'nullable|numeric|min:0',
            'nomor_sk'         => 'nullable|string|max:100',
            'jenis_sk'         => 'required|in:pemberian,nominasi',
            'tahap'            => 'nullable|string|max:100',
            'tahun'            => 'nullable|string|max:20',
            'status_cair'      => 'nullable|string|max:50',
            'tanggal_aktivasi' => 'nullable|string|max:30',
        ]);

        // Validasi: jika jenis_sk nominasi, jangan simpan jika sudah ada pada sk pemberian!
        if ($data['jenis_sk'] === 'nominasi') {
            $hasPemberian = PipRiwayat::where('jenis_sk', 'pemberian')
                ->where(function ($q) use ($data) {
                    if (!empty($data['peserta_didik_id'])) {
                        $q->where('peserta_didik_id', $data['peserta_didik_id']);
                    } elseif (!empty($data['nisn'])) {
                        $q->where('nisn', $data['nisn']);
                    } else {
                        $q->where('nama_pd', $data['nama_pd']);
                    }
                })
                ->exists();

            if ($hasPemberian) {
                return response()->json([
                    'success' => false,
                    'message' => "Siswa {$data['nama_pd']} sudah memiliki SK Pemberian. SK Nominasi tidak dapat disimpan untuk siswa yang telah berstatus penerima SK Pemberian.",
                ], 422);
            }
        }

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
            'jenis_sk'             => $data['jenis_sk'],
            'tahap'                => $data['tahap'] ?: null,
            'tahun'                => $data['tahun'] ?: date('Y'),
            'status_cair'          => $data['status_cair'] ?: null,
            'tanggal_aktivasi'     => $data['tanggal_aktivasi'] ?: null,
        ]);

        $label = $data['jenis_sk'] === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian';
        return response()->json([
            'success' => true,
            'message' => "Catatan riwayat PIP ({$label}) berhasil ditambahkan.",
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
