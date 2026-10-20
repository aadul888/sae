<?php

namespace App\Http\Controllers;

use App\Models\PipKuotaRombel;
use App\Models\PipPengaturan;
use App\Models\PipRiwayat;
use App\Models\PipUsulan;
use App\Models\PesertaDidik;
use App\Models\RolePermission;
use App\Services\SimpleXlsxExporter;
use App\Services\SimpleXlsxReader;
use App\Support\SemesterHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

            if (!Schema::hasTable('pip_pengaturan')) {
                Schema::create('pip_pengaturan', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('tahun', 20)->default('2026')->unique();
                    $table->string('fase', 50)->default('fase_1');
                    $table->string('tahun_pelajaran', 30)->nullable();
                    $table->string('judul_usulan', 150)->nullable();
                    $table->boolean('is_locked')->default(false);
                    $table->dateTime('batas_waktu')->nullable();
                    $table->boolean('kunci_otomatis')->default(true);
                    $table->text('pesan_pengumuman')->nullable();
                    $table->string('updated_by', 100)->nullable();
                    $table->timestamps();
                });
            } else {
                Schema::table('pip_pengaturan', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('pip_pengaturan', 'fase')) {
                        $table->string('fase', 50)->default('fase_1')->after('tahun');
                    }
                    if (!Schema::hasColumn('pip_pengaturan', 'tahun_pelajaran')) {
                        $table->string('tahun_pelajaran', 30)->nullable()->after('fase');
                    }
                    if (!Schema::hasColumn('pip_pengaturan', 'judul_usulan')) {
                        $table->string('judul_usulan', 150)->nullable()->after('tahun_pelajaran');
                    }
                });
            }

            if (Schema::hasTable('pip_usulan')) {
                Schema::table('pip_usulan', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('pip_usulan', 'fase')) {
                        $table->string('fase', 50)->default('fase_1')->after('tahun')->index();
                    }
                    if (!Schema::hasColumn('pip_usulan', 'tahun_pelajaran')) {
                        $table->string('tahun_pelajaran', 30)->nullable()->after('fase');
                    }
                    if (!Schema::hasColumn('pip_usulan', 'judul_usulan')) {
                        $table->string('judul_usulan', 150)->nullable()->after('tahun_pelajaran');
                    }
                    if (!Schema::hasColumn('pip_usulan', 'diubah_oleh')) {
                        $table->string('diubah_oleh', 100)->nullable()->after('catatan_admin');
                    }
                    if (!Schema::hasColumn('pip_usulan', 'diubah_at')) {
                        $table->dateTime('diubah_at')->nullable()->after('diubah_oleh')->index();
                    }
                    if (!Schema::hasColumn('pip_usulan', 'perubahan_terakhir')) {
                        $table->string('perubahan_terakhir', 255)->nullable()->after('diubah_at');
                    }
                    if (!Schema::hasColumn('pip_usulan', 'riwayat_perubahan')) {
                        $table->longText('riwayat_perubahan')->nullable()->after('perubahan_terakhir');
                    }
                });

                try {
                    $indexes = collect(DB::select("SHOW INDEX FROM pip_usulan"))->pluck('Key_name')->all();
                    if (in_array('uq_pip_usulan_pd_tahun', $indexes, true)) {
                        DB::statement("ALTER TABLE pip_usulan DROP INDEX uq_pip_usulan_pd_tahun");
                    }
                    if (!in_array('uq_pip_usulan_pd_th_fase', $indexes, true)) {
                        DB::statement("ALTER TABLE pip_usulan ADD UNIQUE KEY uq_pip_usulan_pd_th_fase (peserta_didik_id, tahun, fase)");
                    }
                } catch (\Throwable) {}
            }

            if (Schema::hasTable('pip_riwayat')) {
                Schema::table('pip_riwayat', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!Schema::hasColumn('pip_riwayat', 'fase')) {
                        $table->string('fase', 50)->nullable()->after('tahap')->index();
                    }
                    if (!Schema::hasColumn('pip_riwayat', 'tahun_pelajaran')) {
                        $table->string('tahun_pelajaran', 30)->nullable()->after('tahun');
                    }
                    if (!Schema::hasColumn('pip_riwayat', 'judul_usulan')) {
                        $table->string('judul_usulan', 150)->nullable()->after('tahun_pelajaran');
                    }
                });
            }

            // Sinkronkan peserta_didik_id dari NISN jika masih kosong
            if (Schema::hasTable('pip_riwayat') && Schema::hasTable('peserta_didik')) {
                $hasMissing = DB::table('pip_riwayat')
                    ->where(function ($q) {
                        $q->whereNull('peserta_didik_id')->orWhere('peserta_didik_id', '');
                    })
                    ->whereNotNull('nisn')
                    ->where('nisn', '!=', '')
                    ->exists();

                if ($hasMissing) {
                    DB::statement("
                        UPDATE pip_riwayat r
                        INNER JOIN peserta_didik pd ON r.nisn IS NOT NULL AND r.nisn != '' AND r.nisn = pd.nisn
                        SET r.peserta_didik_id = pd.peserta_didik_id
                        WHERE r.peserta_didik_id IS NULL OR r.peserta_didik_id = ''
                    ");
                }
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
        $isAdmin = ($role === 'admin') || RolePermission::canAccess($user, 'menu_pip', 'read');

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
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'tingkat_pendidikan_id_str', 'jurusan_id_str', 'ptk_id_str')
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
        return ($role === 'admin') || RolePermission::canAccess($user, 'menu_pip', 'read');
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

        $tab = $request->get('tab', 'dashboard'); // 'dashboard', 'usulan', 'kuota' (admin only), 'riwayat', 'riwayat_alumni' (admin only), 'pengaturan' (admin only)
        if (!$isAdmin && in_array($tab, ['kuota', 'riwayat_alumni', 'pengaturan'], true)) {
            $tab = ($tab === 'riwayat_alumni') ? 'riwayat' : 'dashboard';
        }
        if (!in_array($tab, ['dashboard', 'usulan', 'kuota', 'riwayat', 'riwayat_alumni', 'pengaturan'], true)) {
            $tab = 'dashboard';
        }

        $pipPengaturan = PipPengaturan::getSettings($activeTahun);
        $pipStatusInfo = PipPengaturan::getStatusInfo($activeTahun);
        $isWaliLocked  = !$isAdmin && $pipStatusInfo['is_closed'];

        $q = trim((string) $request->get('q', ''));
        $statusVerif = trim((string) $request->get('status_verifikasi', ''));
        $faseFilter = trim((string) $request->get('fase', ''));
        $statusPerubahanFilter = trim((string) $request->get('status_perubahan', ''));
        $jenisSkFilter = trim((string) $request->get('jenis_sk', ''));
        $statusPipFilter = trim((string) $request->get('status_pip', 'penerima'));
        if (!in_array($statusPipFilter, ['penerima', 'belum', 'semua'], true)) {
            $statusPipFilter = 'penerima';
        }
        $perPageVal = $request->get('perPage', '15');
        $perPage = in_array((int) $perPageVal, [10, 15, 25, 50, 100], true) ? (int) $perPageVal : 15;

        $hasUsulanTable = Schema::hasTable('pip_usulan');
        $hasKuotaTable = Schema::hasTable('pip_kuota_rombel');
        $hasRiwayatTable = Schema::hasTable('pip_riwayat');

        // 0. Data Tab: Dashboard Overview PIP
        $dashboardData = null;
        if ($tab === 'dashboard') {
            $dashboardData = $this->buildDashboardData($isAdmin, $activeRombel, $activeTahun, $rombelList, $hasUsulanTable, $hasKuotaTable, $hasRiwayatTable);
        }

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

            if ($faseFilter !== '' && in_array($faseFilter, ['fase_1', 'fase_2'], true)) {
                $usulanQuery->where('u.fase', $faseFilter);
            }

            if ($statusPerubahanFilter === 'diubah') {
                $usulanQuery->whereNotNull('u.diubah_at');
            } elseif ($statusPerubahanFilter === 'asli') {
                $usulanQuery->whereNull('u.diubah_at');
            }

            if ($q !== '') {
                $usulanQuery->where(function ($sub) use ($q) {
                    $sub->where('pd.nama', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                        ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                        ->orWhere('u.keterangan', 'LIKE', "%{$q}%")
                        ->orWhere('u.alasan_usulan', 'LIKE', "%{$q}%")
                        ->orWhere('u.perubahan_terakhir', 'LIKE', "%{$q}%")
                        ->orWhere('u.diubah_oleh', 'LIKE', "%{$q}%");
                });
            }

            // Urutan: Prioritas/Rank menaik per kelas
            $usulanList = $usulanQuery
                ->orderBy('u.rombongan_belajar_id', 'asc')
                ->orderBy('u.prioritas', 'asc')
                ->orderBy('pd.nama', 'asc')
                ->paginate($perPage, ['*'], 'usulan_page')
                ->withQueryString();

            // Transform foto_url dan decode alasan_usulan & riwayat_perubahan
            $usulanList->getCollection()->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                $item->alasan_list = is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []);
                $item->riwayat_perubahan_list = is_string($item->riwayat_perubahan) ? (json_decode($item->riwayat_perubahan, true) ?: []) : ($item->riwayat_perubahan ?: []);
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
        $totalUsulanDiubah = 0;

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
            $totalUsulanDiubah = (clone $statScope)->whereNotNull('diubah_at')->count();
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
            'total_usulan_diubah' => $totalUsulanDiubah,
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

        // 3. Data Tab: Riwayat Penerima PIP (Siswa Aktif)
        $riwayatList = null;
        $riwayatTahapList = [];
        $riwayatStats = [
            'total_siswa'     => 0,
            'total_pemberian' => 0,
            'total_nominasi'  => 0,
            'total_nominal'   => 0,
        ];

        // 4. Data Tab: Riwayat Penerima PIP (Kelas Lain & Alumni - Admin Only)
        $riwayatAlumniList = null;
        $riwayatAlumniTahapList = [];
        $riwayatAlumniStats = [
            'total_siswa'     => 0,
            'total_pemberian' => 0,
            'total_nominasi'  => 0,
            'total_nominal'   => 0,
        ];
        $alumniStatusList = [];
        $statusKeluarFilter = trim((string) $request->get('status_keluar', ''));

        if ($tab === 'riwayat') {
            if ($hasRiwayatTable) {
                // Scope dasar seluruh siswa aktif di rombel terpilih / seluruh sekolah
                $activeStudentsScope = DB::table('peserta_didik as pd')
                    ->join('rombongan_belajar as rb', function ($j) {
                        $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                          ->where('rb.jenis_rombel', '=', '1');
                    });

                if (!$isAdmin && $activeRombel) {
                    $activeStudentsScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                } elseif ($isAdmin && $activeRombel) {
                    $activeStudentsScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                }

                $totalSiswaAktif = (clone $activeStudentsScope)->count();

                // Hitung siswa aktif yang pernah menerima PIP
                $penerimaUnikCount = (clone $activeStudentsScope)
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    })
                    ->count();

                $totalBelumMenerima = max(0, $totalSiswaAktif - $penerimaUnikCount);

                // Scope dasar data riwayat transaksi PIP
                $baseRiwayatScope = DB::table('pip_riwayat as r')
                    ->join('peserta_didik as pd', function ($j) {
                        $j->on('r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                          ->orWhere(function ($q) {
                              $q->whereNull('r.peserta_didik_id')
                                ->whereNotNull('r.nisn')
                                ->where('r.nisn', '!=', '')
                                ->whereColumn('r.nisn', 'pd.nisn');
                          });
                    })
                    ->join('rombongan_belajar as rb', function ($j) {
                        $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                          ->where('rb.jenis_rombel', '=', '1');
                    });

                if (!$isAdmin && $activeRombel) {
                    $baseRiwayatScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                } elseif ($isAdmin && $activeRombel) {
                    $baseRiwayatScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
                }

                $hasJenisSk = Schema::hasColumn('pip_riwayat', 'jenis_sk');
                $totalPemberian = $hasJenisSk
                    ? (clone $baseRiwayatScope)->where('r.jenis_sk', 'pemberian')->count()
                    : (clone $baseRiwayatScope)->count();
                $totalNominasi = $hasJenisSk
                    ? (clone $baseRiwayatScope)->where('r.jenis_sk', 'nominasi')->count()
                    : 0;
                $totalNominal = (float) (clone $baseRiwayatScope)->sum('r.nominal');
                $totalTransaksi = (clone $baseRiwayatScope)->count();

                $riwayatStats = [
                    'total_siswa_aktif'   => $totalSiswaAktif,
                    'total_penerima_unik' => $penerimaUnikCount,
                    'total_belum'         => $totalBelumMenerima,
                    'total_siswa'         => $totalTransaksi,
                    'total_pemberian'     => $totalPemberian,
                    'total_nominasi'      => $totalNominasi,
                    'total_nominal'       => $totalNominal,
                ];

                $riwayatTahapList = DB::table('pip_riwayat')
                    ->whereNotNull('tahap')
                    ->where('tahap', '!=', '')
                    ->distinct()
                    ->pluck('tahap')
                    ->all();

                $hasUsulan = Schema::hasTable('pip_usulan');

                if ($statusPipFilter === 'belum') {
                    // Peserta Didik yang BELUM Menerima PIP
                    $belumQuery = (clone $activeStudentsScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $belumQuery->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $belumQuery->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    });

                    $belumQuery->select(
                        'pd.peserta_didik_id',
                        'pd.nama as nama_pd',
                        'pd.nama as nama_pd_aktif',
                        'pd.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_ibu as nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw('NULL as id'),
                        DB::raw('NULL as nomor_sk'),
                        DB::raw('NULL as jenis_sk'),
                        DB::raw('NULL as tahap'),
                        DB::raw('NULL as tanggal_sk'),
                        DB::raw('0 as nominal'),
                        DB::raw('NULL as status_cair'),
                        DB::raw('NULL as tanggal_cair'),
                        DB::raw('NULL as tanggal_aktivasi'),
                        DB::raw('NULL as rombel'),
                        DB::raw('NULL as tahun'),
                        DB::raw("'belum_menerima' as status_pip_record")
                    );

                    if ($q !== '') {
                        $belumQuery->where(function ($sub) use ($q) {
                            $sub->where('pd.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama_rombel', 'LIKE', "%{$q}%")
                                ->orWhere('rb.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama_ibu', 'LIKE', "%{$q}%");
                        });
                    }

                    $riwayatList = $belumQuery
                        ->orderBy('rb.nama', 'asc')
                        ->orderBy('pd.nama', 'asc')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                } elseif ($statusPipFilter === 'semua') {
                    // Semua Siswa: Union dari Riwayat Penerima + Siswa Belum Menerima
                    $qPenerima = (clone $baseRiwayatScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $qPenerima->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $qPenerima->select(
                        'pd.peserta_didik_id',
                        'r.nama_pd',
                        'pd.nama as nama_pd_aktif',
                        'r.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'r.nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        'r.id',
                        'r.nomor_sk',
                        'r.jenis_sk',
                        'r.tahap',
                        'r.tanggal_sk',
                        'r.nominal',
                        'r.status_cair',
                        'r.tanggal_cair',
                        'r.tanggal_aktivasi',
                        'r.rombel',
                        'r.tahun',
                        DB::raw("'penerima' as status_pip_record")
                    );

                    $qBelum = (clone $activeStudentsScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $qBelum->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $qBelum->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    });

                    $qBelum->select(
                        'pd.peserta_didik_id',
                        'pd.nama as nama_pd',
                        'pd.nama as nama_pd_aktif',
                        'pd.nisn',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd',
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_ibu as nama_ibu_kandung',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw('NULL as id'),
                        DB::raw('NULL as nomor_sk'),
                        DB::raw('NULL as jenis_sk'),
                        DB::raw('NULL as tahap'),
                        DB::raw('NULL as tanggal_sk'),
                        DB::raw('0 as nominal'),
                        DB::raw('NULL as status_cair'),
                        DB::raw('NULL as tanggal_cair'),
                        DB::raw('NULL as tanggal_aktivasi'),
                        DB::raw('NULL as rombel'),
                        DB::raw('NULL as tahun'),
                        DB::raw("'belum_menerima' as status_pip_record")
                    );

                    $union = $qPenerima->unionAll($qBelum);
                    $combinedQuery = DB::query()->fromSub($union, 'u_all');

                    if ($q !== '') {
                        $combinedQuery->where(function ($sub) use ($q) {
                            $sub->where('u_all.nama_pd', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_pd_aktif', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nipd', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nomor_sk', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_kelas_aktif', 'LIKE', "%{$q}%")
                                ->orWhere('u_all.nama_ibu_kandung', 'LIKE', "%{$q}%");
                        });
                    }

                    $riwayatList = $combinedQuery
                        ->orderBy('u_all.status_pip_record', 'asc')
                        ->orderBy('u_all.nama_pd_aktif', 'asc')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                } else {
                    // Default: 'penerima'
                    $riwayatQuery = (clone $baseRiwayatScope)
                        ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                    if ($hasUsulan) {
                        $riwayatQuery->leftJoin('pip_usulan as u', function ($j) use ($activeTahun) {
                            $j->on('pd.peserta_didik_id', '=', 'u.peserta_didik_id')
                              ->where('u.tahun', '=', $activeTahun);
                        });
                    }

                    $riwayatQuery->select(
                        'r.*',
                        'pd.nama as nama_pd_aktif',
                        'pd.nisn as nisn_pd_aktif',
                        'pd.nipd as nipd_pd_aktif',
                        'pd.nama_rombel as nama_rombel_aktif',
                        'pd.rombongan_belajar_id as rombel_id_aktif',
                        'rb.nama as nama_kelas_aktif',
                        'pdm.foto_path',
                        $hasUsulan ? 'u.id as usulan_id' : DB::raw('NULL as usulan_id'),
                        $hasUsulan ? 'u.status_verifikasi as status_usulan' : DB::raw('NULL as status_usulan'),
                        $hasUsulan ? 'u.prioritas as prioritas_usulan' : DB::raw('NULL as prioritas_usulan'),
                        $hasUsulan ? 'u.keterangan as keterangan_usulan' : DB::raw('NULL as keterangan_usulan'),
                        DB::raw("'penerima' as status_pip_record")
                    );

                    if ($q !== '') {
                        $riwayatQuery->where(function ($sub) use ($q) {
                            $sub->where('r.nama_pd', 'LIKE', "%{$q}%")
                                ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                                ->orWhere('r.nomor_sk', 'LIKE', "%{$q}%")
                                ->orWhere('r.rombel', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pd.nama_rombel', 'LIKE', "%{$q}%")
                                ->orWhere('rb.nama', 'LIKE', "%{$q}%")
                                ->orWhere('r.nama_ibu_kandung', 'LIKE', "%{$q}%");
                        });
                    }

                    $tahapFilter = trim((string) $request->get('tahap', ''));
                    if ($tahapFilter !== '') {
                        $riwayatQuery->where('r.tahap', $tahapFilter);
                    }

                    if ($hasJenisSk && $jenisSkFilter !== '' && in_array($jenisSkFilter, ['pemberian', 'nominasi'], true)) {
                        $riwayatQuery->where('r.jenis_sk', $jenisSkFilter);
                    }

                    $riwayatList = $riwayatQuery
                        ->orderByDesc('r.tahun')
                        ->orderByDesc('r.id')
                        ->paginate($perPage, ['*'], 'riwayat_page')
                        ->withQueryString();
                }

                $riwayatList->getCollection()->transform(function ($item) {
                    $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                    $item->is_belum_menerima = (($item->status_pip_record ?? '') === 'belum_menerima');
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
        } elseif ($tab === 'riwayat_alumni' && $isAdmin) {
            if ($hasRiwayatTable) {
                $hasPdta = Schema::hasTable('peserta_didik_tidak_aktif');

                $riwayatAlumniQuery = DB::table('pip_riwayat as r')
                    ->whereNotExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('peserta_didik as pd')
                            ->join('rombongan_belajar as rb', function ($j) {
                                $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                                  ->where('rb.jenis_rombel', '=', '1');
                            })
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'pd.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'pd.nisn');
                                      });
                            });
                    });

                if ($hasPdta) {
                    $riwayatAlumniQuery->leftJoin('peserta_didik_tidak_aktif as pdta', function ($j) {
                        $j->on('r.peserta_didik_id', '=', 'pdta.peserta_didik_id')
                          ->orWhere(function ($q) {
                              $q->whereNull('r.peserta_didik_id')
                                ->whereNotNull('r.nisn')
                                ->where('r.nisn', '!=', '')
                                ->whereColumn('r.nisn', 'pdta.nisn');
                          });
                    });
                }

                $riwayatAlumniQuery->leftJoin('peserta_didik_meta as pdm', 'r.peserta_didik_id', '=', 'pdm.peserta_didik_id');

                $selectFields = [
                    'r.*',
                    $hasPdta ? 'pdta.status_keluar' : DB::raw("NULL as status_keluar"),
                    $hasPdta ? 'pdta.tahun_lulus' : DB::raw("NULL as tahun_lulus"),
                    $hasPdta ? 'pdta.nama_rombel_terakhir' : DB::raw("NULL as nama_rombel_terakhir"),
                    $hasPdta ? 'pdta.alasan_keluar' : DB::raw("NULL as alasan_keluar"),
                    $hasPdta ? DB::raw('COALESCE(pdta.foto_path, pdm.foto_path) as foto_path') : 'pdm.foto_path',
                ];
                $riwayatAlumniQuery->select($selectFields);

                if ($q !== '') {
                    $riwayatAlumniQuery->where(function ($sub) use ($q, $hasPdta) {
                        $sub->where('r.nama_pd', 'LIKE', "%{$q}%")
                            ->orWhere('r.nisn', 'LIKE', "%{$q}%")
                            ->orWhere('r.nomor_sk', 'LIKE', "%{$q}%")
                            ->orWhere('r.rombel', 'LIKE', "%{$q}%")
                            ->orWhere('r.nama_ibu_kandung', 'LIKE', "%{$q}%");
                        if ($hasPdta) {
                            $sub->orWhere('pdta.nama', 'LIKE', "%{$q}%")
                                ->orWhere('pdta.status_keluar', 'LIKE', "%{$q}%")
                                ->orWhere('pdta.nama_rombel_terakhir', 'LIKE', "%{$q}%");
                        }
                    });
                }

                if ($hasPdta && $statusKeluarFilter !== '') {
                    if ($statusKeluarFilter === 'Lainnya') {
                        $riwayatAlumniQuery->where(function ($s) {
                            $s->whereNull('pdta.status_keluar')
                              ->orWhereNotIn('pdta.status_keluar', ['Alumni', 'Mutasi']);
                        });
                    } else {
                        $riwayatAlumniQuery->where('pdta.status_keluar', $statusKeluarFilter);
                    }
                }

                $tahapFilter = trim((string) $request->get('tahap', ''));
                if ($tahapFilter !== '') {
                    $riwayatAlumniQuery->where('r.tahap', $tahapFilter);
                }

                $hasJenisSk = Schema::hasColumn('pip_riwayat', 'jenis_sk');
                $jenisSkFilter = trim((string) $request->get('jenis_sk', ''));
                if ($hasJenisSk && $jenisSkFilter !== '' && in_array($jenisSkFilter, ['pemberian', 'nominasi'], true)) {
                    $riwayatAlumniQuery->where('r.jenis_sk', $jenisSkFilter);
                }

                // 4 statistik riwayat alumni / kelas lain
                $statAlumniQuery = clone $riwayatAlumniQuery;
                $riwayatAlumniStats['total_siswa'] = (clone $statAlumniQuery)->count();
                $riwayatAlumniStats['total_pemberian'] = $hasJenisSk
                    ? (clone $statAlumniQuery)->where('r.jenis_sk', 'pemberian')->count()
                    : $riwayatAlumniStats['total_siswa'];
                $riwayatAlumniStats['total_nominasi'] = $hasJenisSk
                    ? (clone $statAlumniQuery)->where('r.jenis_sk', 'nominasi')->count()
                    : 0;
                $riwayatAlumniStats['total_nominal'] = (float) (clone $statAlumniQuery)->sum('r.nominal');

                $riwayatAlumniTahapList = DB::table('pip_riwayat')
                    ->whereNotNull('tahap')
                    ->where('tahap', '!=', '')
                    ->distinct()
                    ->pluck('tahap')
                    ->all();

                if ($hasPdta) {
                    $alumniStatusList = DB::table('peserta_didik_tidak_aktif')
                        ->whereNotNull('status_keluar')
                        ->where('status_keluar', '!=', '')
                        ->distinct()
                        ->pluck('status_keluar')
                        ->all();
                }

                $riwayatAlumniList = $riwayatAlumniQuery
                    ->orderByDesc('r.tahun')
                    ->orderByDesc('r.id')
                    ->paginate($perPage, ['*'], 'alumni_page')
                    ->withQueryString();

                $riwayatAlumniList->getCollection()->transform(function ($item) {
                    $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                    return $item;
                });
            } else {
                $riwayatAlumniList = new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    $perPage,
                    1,
                    ['path' => $request->url(), 'pageName' => 'alumni_page', 'query' => $request->query()]
                );
            }
        }

        if (!$riwayatList) {
            $riwayatList = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'riwayat_page', 'query' => $request->query()]
            );
        }
        if (!$riwayatAlumniList) {
            $riwayatAlumniList = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'pageName' => 'alumni_page', 'query' => $request->query()]
            );
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
            'faseFilter',
            'statusPerubahanFilter',
            'perPage',
            'usulanList',
            'stats',
            'kuotaList',
            'kuotaStats',
            'riwayatList',
            'riwayatTahapList',
            'riwayatStats',
            'riwayatAlumniList',
            'riwayatAlumniTahapList',
            'riwayatAlumniStats',
            'alumniStatusList',
            'statusKeluarFilter',
            'jenisSkFilter',
            'statusPipFilter',
            'kriteriaAlasan',
            'statusVerifikasiList',
            'dashboardData',
            'pipPengaturan',
            'pipStatusInfo',
            'isWaliLocked'
        ));
    }

    /**
     * Build aggregated data for the Dashboard Overview tab
     */
    private function buildDashboardData(
        bool $isAdmin,
        $activeRombel,
        string $activeTahun,
        $rombelList,
        bool $hasUsulanTable,
        bool $hasKuotaTable,
        bool $hasRiwayatTable
    ): array {
        $hasPdTable = Schema::hasTable('peserta_didik');
        $hasPdta    = Schema::hasTable('peserta_didik_tidak_aktif');
        $hasJenisSk = $hasRiwayatTable && Schema::hasColumn('pip_riwayat', 'jenis_sk');

        // ── Siswa aktif ──────────────────────────────────────────────
        $totalSiswaAktif = 0;
        $totalSiswaL = 0;
        $totalSiswaP = 0;
        $siswaPerTingkat = [];  // [tingkat => count]

        if ($hasPdTable) {
            $pdScope = DB::table('peserta_didik as pd')
                ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('rb.jenis_rombel', '1');

            if (!$isAdmin && $activeRombel) {
                $pdScope->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalSiswaAktif = (clone $pdScope)->count();
            $totalSiswaL     = (clone $pdScope)->where('pd.jenis_kelamin', 'L')->count();
            $totalSiswaP     = (clone $pdScope)->where('pd.jenis_kelamin', 'P')->count();

            if ($isAdmin) {
                $siswaPerTingkat = DB::table('peserta_didik as pd')
                    ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('rb.jenis_rombel', '1')
                    ->select('rb.tingkat_pendidikan_id', DB::raw('count(pd.peserta_didik_id) as n'))
                    ->groupBy('rb.tingkat_pendidikan_id')
                    ->orderBy('rb.tingkat_pendidikan_id')
                    ->pluck('n', 'tingkat_pendidikan_id')
                    ->all();
            }
        }

        // ── Usulan ───────────────────────────────────────────────────
        $totalUsulan   = 0;
        $belumDiajukan = 0;
        $sudahDiajukan = 0;
        $ditolak       = 0;
        $usulanPerRombel = [];
        $usulanPerTingkat = [];
        $alasanDistribusi = [];
        $usulanTerbaru = collect();

        if ($hasUsulanTable) {
            $uScope = DB::table('pip_usulan')->where('tahun', $activeTahun);
            if (!$isAdmin && $activeRombel) {
                $uScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalUsulan   = (clone $uScope)->count();
            $belumDiajukan = (clone $uScope)->where('status_verifikasi', 'belum_diajukan')->count();
            $sudahDiajukan = (clone $uScope)->where('status_verifikasi', 'sudah_diajukan')->count();
            $ditolak       = (clone $uScope)->where('status_verifikasi', 'ditolak')->count();
            $totalUsulanDiubah = (clone $uScope)->whereNotNull('diubah_at')->count();

            // Usulan terbaru (5 data)
            $recentQuery = DB::table('pip_usulan as u')
                ->join('peserta_didik as pd', 'u.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
                ->where('u.tahun', $activeTahun)
                ->select(
                    'u.*',
                    'pd.nama as nama_pd',
                    'pd.nisn',
                    'rb.nama as nama_rombel',
                    'pdm.foto_path'
                );
            if (!$isAdmin && $activeRombel) {
                $recentQuery->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }
            $usulanTerbaru = $recentQuery->orderByDesc('u.id')->limit(6)->get();
            $usulanTerbaru->transform(function ($item) {
                $item->foto_url = !empty($item->foto_path) ? asset('storage/' . ltrim($item->foto_path, '/')) : null;
                $item->alasan_list = is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []);
                $item->riwayat_perubahan_list = is_string($item->riwayat_perubahan) ? (json_decode($item->riwayat_perubahan, true) ?: []) : ($item->riwayat_perubahan ?: []);
                return $item;
            });

            // Distribusi alasan diusulkan (untuk Admin & Wali Kelas)
            $alasanScope = DB::table('pip_usulan')->where('tahun', $activeTahun)->whereNotNull('alasan_usulan');
            if (!$isAdmin && $activeRombel) {
                $alasanScope->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }
            $allAlasan = $alasanScope->pluck('alasan_usulan')->all();
            foreach ($allAlasan as $raw) {
                $list = is_string($raw) ? (json_decode($raw, true) ?: []) : (array) $raw;
                foreach ($list as $a) {
                    $a = trim((string) $a);
                    if ($a !== '') {
                        $alasanDistribusi[$a] = ($alasanDistribusi[$a] ?? 0) + 1;
                    }
                }
            }
            arsort($alasanDistribusi);

            if ($isAdmin) {
                // Usulan per rombel (top 15)
                $usulanPerRombel = DB::table('pip_usulan as u')
                    ->join('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('u.tahun', $activeTahun)
                    ->select('rb.nama as nama_rombel', DB::raw('count(u.id) as n'), 'u.rombongan_belajar_id')
                    ->groupBy('u.rombongan_belajar_id', 'rb.nama')
                    ->orderByDesc('n')
                    ->limit(15)
                    ->get()
                    ->all();

                // Usulan per tingkat (X, XI, XII)
                $usulanPerTingkat = DB::table('pip_usulan as u')
                    ->join('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                    ->where('u.tahun', $activeTahun)
                    ->select('rb.tingkat_pendidikan_id', DB::raw('count(u.id) as n'))
                    ->groupBy('rb.tingkat_pendidikan_id')
                    ->orderBy('rb.tingkat_pendidikan_id')
                    ->pluck('n', 'tingkat_pendidikan_id')
                    ->all();
            }
        }

        // ── Kuota ────────────────────────────────────────────────────
        $paguSekolah   = $rombelList->count() * 5;
        $totalKuota    = $paguSekolah;
        $sisaKuota     = max(0, $paguSekolah - $totalUsulan);
        $pctTerpakai   = $paguSekolah > 0 ? min(100, round($totalUsulan / $paguSekolah * 100, 1)) : 0;

        if ($hasKuotaTable) {
            $stored = PipKuotaRombel::getTargetTotalSekolah($activeTahun);
            if ($stored !== null) {
                $paguSekolah = $stored;
                $totalKuota  = $stored;
            } else {
                $sumKuota = DB::table('pip_kuota_rombel')
                    ->where('tahun', $activeTahun)
                    ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                    ->sum('kuota');
                if ($sumKuota > 0) {
                    $paguSekolah = (int) $sumKuota;
                    $totalKuota  = (int) $sumKuota;
                }
            }
            $sisaKuota   = max(0, $paguSekolah - $totalUsulan);
            $pctTerpakai = $paguSekolah > 0 ? min(100, round($totalUsulan / $paguSekolah * 100, 1)) : 0;
        }

        // Kuota kelas aktif (wali kelas)
        $kuotaKelasBinaan   = 5;
        $terpakaiKelasBinaan = 0;
        if ($activeRombel) {
            $kuotaKelasBinaan = PipKuotaRombel::getKuotaForRombel($activeRombel->rombongan_belajar_id, $activeTahun);
            if ($hasUsulanTable) {
                $terpakaiKelasBinaan = DB::table('pip_usulan')
                    ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id)
                    ->where('tahun', $activeTahun)
                    ->count();
            }
        }

        // ── Riwayat ──────────────────────────────────────────────────
        $totalRiwayatAktif    = 0;
        $totalRiwayatPemberian = 0;
        $totalRiwayatNominasi  = 0;
        $totalNominalCair      = 0;
        $riwayatPerTahun       = [];  // [tahun => count]

        if ($hasRiwayatTable) {
            // hanya siswa aktif
            $rBase = DB::table('pip_riwayat as r')
                ->join('peserta_didik as pd', function ($j) {
                    $j->on('r.peserta_didik_id', '=', 'pd.peserta_didik_id')
                      ->orWhere(function ($q) {
                          $q->whereNull('r.peserta_didik_id')
                            ->whereNotNull('r.nisn')->where('r.nisn', '!=', '')
                            ->whereColumn('r.nisn', 'pd.nisn');
                      });
                })
                ->join('rombongan_belajar as rb', function ($j) {
                    $j->on('pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                      ->where('rb.jenis_rombel', '=', '1');
                });

            if (!$isAdmin && $activeRombel) {
                $rBase->where('pd.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            }

            $totalRiwayatAktif = (clone $rBase)->count();
            $totalRiwayatPemberian = $hasJenisSk ? (clone $rBase)->where('r.jenis_sk', 'pemberian')->count() : $totalRiwayatAktif;
            $totalRiwayatNominasi  = $hasJenisSk ? (clone $rBase)->where('r.jenis_sk', 'nominasi')->count()  : 0;
            $totalNominalCair = (float) (clone $rBase)->sum('r.nominal');

            if ($isAdmin) {
                $riwayatPerTahun = DB::table('pip_riwayat')
                    ->whereNotNull('tahun')
                    ->where('tahun', '!=', '')
                    ->select('tahun', DB::raw('count(*) as n'), DB::raw('sum(nominal) as total_nominal'))
                    ->groupBy('tahun')
                    ->orderBy('tahun')
                    ->get()
                    ->all();
            }
        }

        // ── Wali Kelas: status penerima siswa kelas binaan ───────────
        $waliSudahPip = 0;
        $waliBelumPip = 0;
        if (!$isAdmin && $activeRombel && $hasPdTable) {
            $waliPdScope = DB::table('peserta_didik')
                ->where('rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
            $totalWaliPd = (clone $waliPdScope)->count();
            if ($hasRiwayatTable) {
                $waliSudahPip = (clone $waliPdScope)
                    ->whereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('pip_riwayat as r')
                            ->where(function ($match) {
                                $match->whereColumn('r.peserta_didik_id', 'peserta_didik.peserta_didik_id')
                                      ->orWhere(function ($orMatch) {
                                          $orMatch->whereNull('r.peserta_didik_id')
                                                  ->whereNotNull('r.nisn')
                                                  ->where('r.nisn', '!=', '')
                                                  ->whereColumn('r.nisn', 'peserta_didik.nisn');
                                      });
                            });
                    })
                    ->count();
            }
            $waliBelumPip = max(0, $totalWaliPd - $waliSudahPip);
        }

        // ── Rombel summary (admin) ────────────────────────────────────
        $rombelSummary = collect();
        $rombelPenuhCount = 0;
        $rombelBelumUsulCount = 0;
        $rombelTersediaCount = 0;

        if ($isAdmin && $hasUsulanTable) {
            $existingKuota = $hasKuotaTable
                ? DB::table('pip_kuota_rombel')->where('tahun', $activeTahun)->where('rombongan_belajar_id', '!=', '__TOTAL__')->pluck('kuota', 'rombongan_belajar_id')->all()
                : [];
            $existingCount = DB::table('pip_usulan')->where('tahun', $activeTahun)->select('rombongan_belajar_id', DB::raw('COUNT(*) as n'))->groupBy('rombongan_belajar_id')->pluck('n', 'rombongan_belajar_id')->all();
            $studentCounts = $hasPdTable
                ? DB::table('peserta_didik')->select('rombongan_belajar_id', DB::raw('count(*) as n'))->groupBy('rombongan_belajar_id')->pluck('n', 'rombongan_belajar_id')->all()
                : [];

            $rombelSummary = $rombelList->map(function ($r) use ($existingKuota, $existingCount, $studentCounts) {
                $kuota  = (int) ($existingKuota[$r->rombongan_belajar_id] ?? 5);
                $used   = (int) ($existingCount[$r->rombongan_belajar_id] ?? 0);
                $siswa  = (int) ($studentCounts[$r->rombongan_belajar_id] ?? 0);
                $isAwal = in_array((string) ($r->tingkat_pendidikan_id ?? ''), ['10', '10.0', 'X'], true) || str_starts_with(strtoupper($r->nama), 'X ');

                $r->kuota                  = $kuota;
                $r->terpakai               = $used;
                $r->sisa                   = max(0, $kuota - $used);
                $r->jumlah_siswa           = $siswa;
                $r->pct                    = $kuota > 0 ? min(100, round($used / $kuota * 100)) : 0;
                $r->kategori_tingkat       = $isAwal ? 'kelas_awal' : 'kelas_berjalan';
                $r->kategori_tingkat_label = $isAwal ? 'Kelas Awal (X)' : 'Kelas Berjalan (' . ($r->tingkat_pendidikan_id_str ?: 'XI/XII') . ')';
                return $r;
            })->sortByDesc('terpakai')->values();

            $rombelPenuhCount = $rombelSummary->where('sisa', '<=', 0)->where('terpakai', '>', 0)->count();
            $rombelBelumUsulCount = $rombelSummary->where('terpakai', 0)->count();
            $rombelTersediaCount = $rombelSummary->where('sisa', '>', 0)->count();
            $rombelAwalCount = $rombelSummary->where('kategori_tingkat', 'kelas_awal')->count();
            $rombelBerjalanCount = $rombelSummary->where('kategori_tingkat', 'kelas_berjalan')->count();
        }

        return [
            // siswa
            'total_siswa_aktif'       => $totalSiswaAktif,
            'total_siswa_l'           => $totalSiswaL,
            'total_siswa_p'           => $totalSiswaP,
            'siswa_per_tingkat'       => $siswaPerTingkat,
            'wali_sudah_pip'          => $waliSudahPip,
            'wali_belum_pip'          => $waliBelumPip,
            // usulan
            'total_usulan'            => $totalUsulan,
            'belum_diajukan'          => $belumDiajukan,
            'sudah_diajukan'          => $sudahDiajukan,
            'ditolak'                 => $ditolak,
            'total_usulan_diubah'     => $totalUsulanDiubah,
            'usulan_per_rombel'       => $usulanPerRombel,
            'usulan_per_tingkat'      => $usulanPerTingkat,
            'alasan_distribusi'       => $alasanDistribusi,
            'usulan_terbaru'          => $usulanTerbaru,
            // kuota
            'pagu_sekolah'            => $paguSekolah,
            'total_kuota'             => $totalKuota,
            'sisa_kuota'              => $sisaKuota,
            'pct_terpakai'            => $pctTerpakai,
            'kuota_kelas'             => $kuotaKelasBinaan,
            'terpakai_kelas'          => $terpakaiKelasBinaan,
            'sisa_kuota_kelas'        => max(0, $kuotaKelasBinaan - $terpakaiKelasBinaan),
            // riwayat
            'total_riwayat_aktif'     => $totalRiwayatAktif,
            'total_pemberian'         => $totalRiwayatPemberian,
            'total_nominasi'          => $totalRiwayatNominasi,
            'total_nominal_cair'      => $totalNominalCair,
            'riwayat_per_tahun'       => $riwayatPerTahun,
            // rombel table & stats
            'rombel_summary'          => $rombelSummary,
            'rombel_penuh_count'      => $rombelPenuhCount,
            'rombel_belum_usul_count' => $rombelBelumUsulCount,
            'rombel_tersedia_count'   => $rombelTersediaCount,
            'rombel_awal_count'       => $rombelAwalCount ?? 0,
            'rombel_berjalan_count'   => $rombelBerjalanCount ?? 0,
        ];
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
     * Ekspor Data Usulan PIP Lengkap ke Microsoft Excel (.xlsx)
     */
    public function exportUsulan(Request $request)
    {
        $this->ensurePipSchema();

        $ctx = $this->resolveContext($request);
        $user = $ctx['user'];
        $role = $ctx['role'];
        $isAdmin = $ctx['isAdmin'];
        $activeRombel = $ctx['activeRombel'];
        $activeTahun = $ctx['activeTahun'];

        $q = trim((string) $request->get('q', ''));
        $statusVerif = trim((string) $request->get('status_verifikasi', ''));
        $faseFilter = trim((string) $request->get('fase', ''));
        $statusPerubahanFilter = trim((string) $request->get('status_perubahan', ''));

        if (!Schema::hasTable('pip_usulan')) {
            return back()->with('error', 'Tabel usulan PIP belum tersedia.');
        }

        $query = DB::table('pip_usulan as u')
            ->join('peserta_didik as pd', 'u.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'u.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->select(
                'u.*',
                'pd.nama as nama_pd',
                'pd.nisn',
                'pd.nipd',
                'pd.nik',
                'pd.jenis_kelamin',
                'pd.tempat_lahir',
                'pd.tanggal_lahir',
                'pd.agama_id_str as agama',
                'pd.nama_ibu',
                'pd.pekerjaan_ibu_id_str as pekerjaan_ibu',
                'pd.nama_ayah',
                'pd.pekerjaan_ayah_id_str as pekerjaan_ayah',
                'pd.nama_wali',
                'pd.pekerjaan_wali_id_str as pekerjaan_wali',
                'pd.alamat_jalan as alamat',
                'pd.nomor_telepon_seluler as no_hp',
                'pd.sekolah_asal',
                'rb.nama as nama_rombel',
                'rb.tingkat_pendidikan_id_str as tingkat_rombel',
                'rb.jurusan_id_str as jurusan_rombel',
                'rb.ptk_id_str as wali_kelas'
            )
            ->where('u.tahun', $activeTahun);

        if (!$isAdmin && $activeRombel) {
            $query->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        } elseif ($isAdmin && $activeRombel) {
            $query->where('u.rombongan_belajar_id', $activeRombel->rombongan_belajar_id);
        }

        if ($statusVerif !== '' && isset(PipUsulan::STATUS_VERIFIKASI[$statusVerif])) {
            $query->where('u.status_verifikasi', $statusVerif);
        }

        if ($faseFilter !== '' && in_array($faseFilter, ['fase_1', 'fase_2'], true)) {
            $query->where('u.fase', $faseFilter);
        }

        if ($statusPerubahanFilter === 'diubah') {
            $query->whereNotNull('u.diubah_at');
        } elseif ($statusPerubahanFilter === 'asli') {
            $query->whereNull('u.diubah_at');
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('pd.nama', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nisn', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nipd', 'LIKE', "%{$q}%")
                    ->orWhere('pd.nik', 'LIKE', "%{$q}%")
                    ->orWhere('u.keterangan', 'LIKE', "%{$q}%")
                    ->orWhere('u.alasan_usulan', 'LIKE', "%{$q}%");
            });
        }

        $items = $query
            ->orderBy('rb.tingkat_pendidikan_id', 'asc')
            ->orderBy('u.rombongan_belajar_id', 'asc')
            ->orderBy('u.prioritas', 'asc')
            ->orderBy('pd.nama', 'asc')
            ->get();

        // Riwayat PIP sebelumnya untuk konteks lengkap
        $riwayatMap = [];
        if (Schema::hasTable('pip_riwayat')) {
            $riwayatRows = DB::table('pip_riwayat')
                ->select('peserta_didik_id', 'nisn', 'tahun', 'jenis_sk', 'tahap', 'nominal', 'status_cair')
                ->whereNotNull('tahun')
                ->orderByDesc('tahun')
                ->orderByDesc('id')
                ->get();
            foreach ($riwayatRows as $rw) {
                $label = 'Th ' . ($rw->tahun ?: '-') . ' (' . ($rw->jenis_sk === 'nominasi' ? 'SK Nominasi' : 'SK Pemberian');
                if ($rw->nominal) {
                    $label .= ' - Rp ' . number_format($rw->nominal, 0, ',', '.');
                }
                if ($rw->status_cair) {
                    $label .= ' - ' . $rw->status_cair;
                }
                $label .= ')';

                if (!empty($rw->peserta_didik_id) && !isset($riwayatMap[$rw->peserta_didik_id])) {
                    $riwayatMap[$rw->peserta_didik_id] = $label;
                }
                if (!empty($rw->nisn) && !isset($riwayatMap[$rw->nisn])) {
                    $riwayatMap[$rw->nisn] = $label;
                }
            }
        }

        $headers = [
            'No',
            'Prioritas',
            'Judul Usulan',
            'Fase Usulan',
            'Tahun Usulan',
            'Tahun Pelajaran',
            'Status Usulan',
            'Kelas / Rombel',
            'Tingkat',
            'Jurusan',
            'Wali Kelas',
            'Nama Lengkap Siswa',
            'NISN',
            'NIPD',
            'NIK',
            'Jenis Kelamin',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Agama',
            'Nama Ibu Kandung',
            'Pekerjaan Ibu',
            'Nama Ayah',
            'Pekerjaan Ayah',
            'Nama Wali',
            'Pekerjaan Wali',
            'No. HP / WhatsApp',
            'Alamat Tempat Tinggal',
            'Sekolah Asal',
            'Kriteria Alasan Diusulkan',
            'Keterangan Tambahan Usulan',
            'Diusulkan Oleh',
            'Waktu Pengusulan',
            'Diverifikasi Oleh',
            'Waktu Verifikasi Admin',
            'Catatan Verifikasi Admin',
            'Riwayat PIP Terakhir',
            'Status Perubahan Data',
            'Diperbarui Oleh',
            'Waktu Perubahan Terakhir',
            'Catatan Perubahan Terakhir',
        ];

        $rows = [];
        $no = 1;
        foreach ($items as $r) {
            $alasanList = is_string($r->alasan_usulan) ? (json_decode($r->alasan_usulan, true) ?: []) : ($r->alasan_usulan ?: []);
            $alasanText = !empty($alasanList) ? implode('; ', (array) $alasanList) : '-';

            $statusLabel = PipUsulan::STATUS_VERIFIKASI[$r->status_verifikasi] ?? (ucfirst(str_replace('_', ' ', (string) $r->status_verifikasi)) ?: '-');

            $jkRaw = strtoupper(trim((string) $r->jenis_kelamin));
            $jkLabel = ($jkRaw === 'L') ? 'Laki-laki' : (($jkRaw === 'P') ? 'Perempuan' : ($r->jenis_kelamin ?: '-'));

            $tglLahir = !empty($r->tanggal_lahir) ? date('d/m/Y', strtotime($r->tanggal_lahir)) : '-';
            $tglUsul = !empty($r->created_at) ? date('d/m/Y H:i', strtotime($r->created_at)) : '-';
            $tglVerif = !empty($r->verifikasi_admin_at) ? date('d/m/Y H:i', strtotime($r->verifikasi_admin_at)) : '-';

            $riwayatTeks = $riwayatMap[$r->peserta_didik_id] ?? ($riwayatMap[$r->nisn] ?? 'Belum ada riwayat');
            $faseLabel = PipUsulan::getFaseLabel($r->fase ?? null);
            $judulUsulan = $r->judul_usulan ?: ("Usulan PIP {$r->tahun} — {$faseLabel}");

            $statusPerubahanTeks = !empty($r->diubah_at) ? 'Ada Perubahan' : 'Asli (Belum Diubah)';
            $diubahOleh = $r->diubah_oleh ?: '-';
            $diubahAt = !empty($r->diubah_at) ? date('d/m/Y H:i', strtotime($r->diubah_at)) : '-';
            $perubahanTerakhir = $r->perubahan_terakhir ?: '-';

            $rows[] = [
                $no++,
                '#' . $r->prioritas,
                $judulUsulan,
                $faseLabel,
                $r->tahun ?: $activeTahun,
                $r->tahun_pelajaran ?: '-',
                $statusLabel,
                $r->nama_rombel ?: '-',
                $r->tingkat_rombel ?: '-',
                $r->jurusan_rombel ?: '-',
                $r->wali_kelas ?: '-',
                $r->nama_pd ?: '-',
                $r->nisn ?: '-',
                $r->nipd ?: '-',
                $r->nik ?: '-',
                $jkLabel,
                $r->tempat_lahir ?: '-',
                $tglLahir,
                $r->agama ?: '-',
                $r->nama_ibu ?: '-',
                $r->pekerjaan_ibu ?: '-',
                $r->nama_ayah ?: '-',
                $r->pekerjaan_ayah ?: '-',
                $r->nama_wali ?: '-',
                $r->pekerjaan_wali ?: '-',
                $r->no_hp ?: '-',
                $r->alamat ?: '-',
                $r->sekolah_asal ?: '-',
                $alasanText,
                $r->keterangan ?: '-',
                $r->diusulkan_oleh ?: '-',
                $tglUsul,
                $r->diverifikasi_oleh ?: '-',
                $tglVerif,
                $r->catatan_admin ?: '-',
                $riwayatTeks,
                $statusPerubahanTeks,
                $diubahOleh,
                $diubahAt,
                $perubahanTerakhir,
            ];
        }

        $sekolah = Schema::hasTable('sekolah') ? DB::table('sekolah')->first() : null;
        $namaSekolah = $sekolah?->nama ?: 'SMK NEGERI 1 PAGELARAN';
        $npsn = $sekolah?->npsn ?: '';

        $userName = is_array($user) ? ($user['name'] ?? ($user['nama'] ?? 'User')) : ($user->name ?? ($user->nama ?? 'User'));
        $settings = PipPengaturan::getSettings($activeTahun);

        $meta = [
            'title'    => 'DATA REKAPITULASI ' . mb_strtoupper($settings->judul_usulan ?: 'USULAN PROGRAM INDONESIA PINTAR (PIP)'),
            'subtitle' => $namaSekolah . ($npsn ? ' (NPSN: ' . $npsn . ')' : '') . ' — ' . ($settings->tahun_pelajaran ? 'TP ' . $settings->tahun_pelajaran . ' ' : '') . 'TAHUN ' . $activeTahun,
            'fields'   => [
                'Judul Usulan'      => $settings->judul_usulan ?: "Usulan PIP Tahun {$activeTahun}",
                'Fase Pengusulan'   => PipUsulan::getFaseLabel($settings->fase),
                'Tahun Anggaran'    => $activeTahun,
                'Tahun Pelajaran'   => $settings->tahun_pelajaran ?: '-',
                'Rombongan Belajar' => $activeRombel ? $activeRombel->nama : 'Semua Rombongan Belajar',
                'Status Verifikasi' => $statusVerif !== '' ? (PipUsulan::STATUS_VERIFIKASI[$statusVerif] ?? $statusVerif) : 'Semua Status (Belum / Sudah / Ditolak)',
                'Total Usulan'      => count($rows) . ' Peserta Didik',
                'Waktu Ekspor'      => date('d/m/Y H:i:s') . ' WIB',
                'Diekspor Oleh'     => $userName . ' (' . ($isAdmin ? 'Administrator' : 'Wali Kelas') . ')',
            ],
        ];

        $sheetName = $activeRombel ? 'Usulan ' . $activeRombel->nama : 'Usulan PIP ' . $activeTahun;
        $rombelSlug = $activeRombel ? Str::slug($activeRombel->nama, '_') : 'Semua_Kelas';
        $filename = 'Usulan_PIP_' . $activeTahun . '_' . $rombelSlug . '_' . date('Ymd_His') . '.xlsx';

        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempPath = $tempDir . '/' . Str::random(16) . '.xlsx';

        SimpleXlsxExporter::export(
            $tempPath,
            $meta,
            $headers,
            $rows,
            [
                'sheetName'  => $sheetName,
                'headerBg'   => 'FF0F766E', // Emerald / Teal tema resmi PIP SAE
                'centerCols' => [0, 1, 3, 4, 5, 6, 8, 12, 13, 14, 15, 17, 31, 33],
            ]
        );

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

        // Cek penguncian untuk Wali Kelas
        if (!$isAdmin && PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengisian usulan PIP saat ini dikunci oleh Administrator atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $data['peserta_didik_id'])->first();
        if (!$pd) {
            return response()->json(['success' => false, 'message' => 'Peserta didik tidak ditemukan.'], 404);
        }

        $rombelId = $pd->rombongan_belajar_id ?: ($activeRombel?->rombongan_belajar_id ?? '');
        if (!$isAdmin && $activeRombel && $rombelId !== $activeRombel->rombongan_belajar_id) {
            return response()->json(['success' => false, 'message' => 'Anda hanya berhak mengusulkan peserta didik dari kelas binaan Anda.'], 403);
        }

        // Cek apakah siswa sudah diusulkan di tahun dan fase yang sama
        $settings = PipPengaturan::getSettings($tahun);
        $fase = $data['fase'] ?? ($settings->fase ?: (date('n') <= 6 ? 'fase_1' : 'fase_2'));
        $faseLabel = PipUsulan::getFaseLabel($fase);
        $tp = $data['tahun_pelajaran'] ?? ($settings->tahun_pelajaran ?: (class_exists(\App\Support\SemesterHelper::class) ? \App\Support\SemesterHelper::getActiveSemesterLabel() : null));
        $judul = $data['judul_usulan'] ?? ($settings->judul_usulan ?: ("Usulan PIP Tahun {$tahun} — {$faseLabel}"));

        $exists = PipUsulan::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => "Siswa {$pd->nama} sudah terdaftar dalam usulan PIP tahun {$tahun} ({$faseLabel})."], 422);
        }

        // Cek Kuota Usulan Kelas (Pengisian tidak boleh melebihi kuota)
        $kuota = PipKuotaRombel::getKuotaForRombel($rombelId, $tahun);
        $currentCount = PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->count();

        if ($currentCount >= $kuota) {
            return response()->json([
                'success' => false,
                'message' => "Kuota usulan PIP kelas ini sudah penuh (Maksimal {$kuota} peserta didik untuk {$faseLabel}). Pengusulan tidak boleh melebihi kuota. Hubungi Administrator jika memerlukan penyesuaian kuota.",
            ], 422);
        }

        // Hitung Prioritas Otomatis: Ranking terakhir + 1
        $maxPrioritas = (int) PipUsulan::where('rombongan_belajar_id', $rombelId)
            ->where('tahun', $tahun)
            ->where('fase', $fase)
            ->max('prioritas');
        $prioritasBaru = $maxPrioritas + 1;

        $pengusulNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Wali Kelas')) : ($user->nama ?? ($user->name ?? 'Wali Kelas'));

        PipUsulan::create([
            'peserta_didik_id'     => $pd->peserta_didik_id,
            'rombongan_belajar_id' => $rombelId,
            'tahun'                => $tahun,
            'fase'                 => $fase,
            'tahun_pelajaran'      => $tp,
            'judul_usulan'         => $judul,
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
     * Perbarui Kriteria Alasan & Keterangan Usulan PIP oleh Wali Kelas / Admin
     */
    public function updateUsulan(Request $request, int $id): JsonResponse
    {
        $this->ensurePipSchema();
        $ctx = $this->resolveContext($request);
        $isAdmin = $ctx['isAdmin'];
        $user = $ctx['user'];

        if (!$isAdmin && !RolePermission::canAccess($user, 'menu_wali_kelas_pip', 'update') && !RolePermission::canAccess($user, 'menu_wali_kelas', 'update')) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin mengubah usulan PIP.'], 403);
        }

        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }

        if (!$isAdmin && PipPengaturan::isLockedForWaliKelas($item->tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

        $data = $request->validate([
            'alasan_usulan'   => 'required|array|min:1',
            'alasan_usulan.*' => 'required|string',
            'keterangan'      => 'nullable|string|max:1000',
        ], [
            'alasan_usulan.required' => 'Minimal pilih satu alasan/kriteria usulan PIP.',
            'alasan_usulan.min'      => 'Minimal pilih satu alasan/kriteria usulan PIP.',
        ]);

        $oldAlasan = is_string($item->alasan_usulan)
            ? (json_decode($item->alasan_usulan, true) ?: [])
            : (array) ($item->alasan_usulan ?: []);
        $newAlasan = array_values($data['alasan_usulan']);

        $sortedOld = $oldAlasan;
        $sortedNew = $newAlasan;
        sort($sortedOld);
        sort($sortedNew);

        $oldKet = trim((string) ($item->keterangan ?? ''));
        $newKet = trim((string) ($data['keterangan'] ?? ''));

        $diffAlasan = ($sortedOld != $sortedNew);
        $diffKet = ($oldKet !== $newKet);

        if (!$diffAlasan && !$diffKet) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada perubahan pada data kriteria dan keterangan usulan.',
            ]);
        }

        $detailPerubahan = [];
        if ($diffAlasan) {
            $tambah = array_values(array_diff($newAlasan, $oldAlasan));
            $hapus = array_values(array_diff($oldAlasan, $newAlasan));
            $parts = [];
            if (!empty($tambah)) {
                $parts[] = 'Ditambah: ' . implode(', ', $tambah);
            }
            if (!empty($hapus)) {
                $parts[] = 'Dihapus: ' . implode(', ', $hapus);
            }
            $detailPerubahan[] = 'Kriteria alasan disesuaikan (' . implode('; ', $parts) . ')';
        }
        if ($diffKet) {
            $detailPerubahan[] = 'Keterangan diperbarui dari "' . ($oldKet ?: '-') . '" menjadi "' . ($newKet ?: '-') . '"';
        }

        $editorNama = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Pengguna')) : ($user->nama ?? ($user->name ?? 'Pengguna'));
        $roleLabel = $isAdmin ? 'Administrator' : 'Wali Kelas';
        $editorSignature = $editorNama . ' (' . $roleLabel . ')';

        $ringkasan = implode(' &bull; ', $detailPerubahan);

        $riwayat = is_array($item->riwayat_perubahan)
            ? $item->riwayat_perubahan
            : (json_decode($item->riwayat_perubahan, true) ?: []);

        array_unshift($riwayat, [
            'waktu'     => now()->format('Y-m-d H:i:s'),
            'editor'    => $editorSignature,
            'peran'     => $roleLabel,
            'ringkasan' => $ringkasan,
            'detail'    => $detailPerubahan,
        ]);

        $item->alasan_usulan = array_values($data['alasan_usulan']);
        $item->keterangan = $newKet;
        $item->diubah_oleh = $editorSignature;
        $item->diubah_at = now();
        $item->perubahan_terakhir = mb_substr($ringkasan, 0, 250);
        $item->riwayat_perubahan = $riwayat;
        $item->save();

        $pdNama = DB::table('peserta_didik')->where('peserta_didik_id', $item->peserta_didik_id)->value('nama') ?? 'Peserta Didik';

        return response()->json([
            'success' => true,
            'message' => "Usulan untuk {$pdNama} berhasil diperbarui. Perubahan tercatat pada riwayat log.",
            'data'    => $item,
        ]);
    }

    /**
     * Dapatkan Catatan Log Riwayat Perubahan Usulan PIP
     */
    public function getRiwayatPerubahan(Request $request, int $id): JsonResponse
    {
        $this->ensurePipSchema();
        $item = PipUsulan::findOrFail($id);
        if ($deny = $this->authorizeUsulan($request, $item)) {
            return $deny;
        }

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $item->peserta_didik_id)->first();
        $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $item->rombongan_belajar_id)->first();

        $riwayat = is_array($item->riwayat_perubahan)
            ? $item->riwayat_perubahan
            : (json_decode($item->riwayat_perubahan, true) ?: []);

        return response()->json([
            'success' => true,
            'usulan'  => [
                'id'                 => $item->id,
                'nama_pd'            => $pd?->nama ?: '-',
                'nisn'               => $pd?->nisn ?: '-',
                'rombel'             => $rombel?->nama ?: '-',
                'prioritas'          => $item->prioritas,
                'status_verifikasi'  => $item->status_verifikasi,
                'diusulkan_oleh'     => $item->diusulkan_oleh,
                'created_at'         => $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-',
                'diubah_oleh'        => $item->diubah_oleh,
                'diubah_at'          => $item->diubah_at ? $item->diubah_at->format('d/m/Y H:i') : null,
                'perubahan_terakhir' => $item->perubahan_terakhir,
                'alasan_list'        => is_string($item->alasan_usulan) ? (json_decode($item->alasan_usulan, true) ?: []) : ($item->alasan_usulan ?: []),
                'keterangan'         => $item->keterangan,
            ],
            'riwayat' => $riwayat,
        ]);
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

        if (PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

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

        $oldRank = (int) $item->prioritas;
        $newRankCalculated = null;

        // Terapkan nomor urut 1, 2, 3, ... secara sekuensial tanpa celah/bentrok
        foreach ($list->values() as $idx => $row) {
            $seq = $idx + 1;
            if ((int) $row->id === (int) $item->id) {
                $newRankCalculated = $seq;
            }
            if ((int) $row->prioritas !== $seq) {
                PipUsulan::where('id', $row->id)->update(['prioritas' => $seq]);
            }
        }

        if ($newRankCalculated !== null && $oldRank !== $newRankCalculated) {
            $editorNama = is_array($ctx['user']) ? ($ctx['user']['nama'] ?? ($ctx['user']['name'] ?? 'Wali Kelas')) : ($ctx['user']->nama ?? ($ctx['user']->name ?? 'Wali Kelas'));
            $editorSig = $editorNama . ' (Wali Kelas)';
            $ringkasan = "Urutan prioritas diubah dari #{$oldRank} menjadi #{$newRankCalculated}";

            $riwayat = is_array($item->riwayat_perubahan)
                ? $item->riwayat_perubahan
                : (json_decode($item->riwayat_perubahan, true) ?: []);

            array_unshift($riwayat, [
                'waktu'     => now()->format('Y-m-d H:i:s'),
                'editor'    => $editorSig,
                'peran'     => 'Wali Kelas',
                'ringkasan' => $ringkasan,
                'detail'    => ["Ranking prioritas bergeser dari urutan #{$oldRank} ke #{$newRankCalculated}"],
            ]);

            PipUsulan::where('id', $item->id)->update([
                'diubah_oleh'        => $editorSig,
                'diubah_at'          => now(),
                'perubahan_terakhir' => $ringkasan,
                'riwayat_perubahan'  => json_encode($riwayat),
            ]);
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

        if ($item->status_verifikasi === 'sudah_diajukan') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Usulan dengan status "Sudah Diusulkan" tidak dapat dihapus.',
            ], 422);
        }

        if (!$ctx['isAdmin'] && PipPengaturan::isLockedForWaliKelas($tahun)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Pengusulan PIP telah dikunci atau telah melewati batas waktu pengisian.',
            ], 403);
        }

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
     * Mendukung pemilahan kategori tingkat:
     * - 'semua'          : Seluruh tingkat (Kelas X, XI, XII)
     * - 'kelas_awal'     : Khusus Kelas Awal (Kelas X saja)
     * - 'kelas_berjalan' : Khusus Kelas Berjalan (Kelas XI dan XII)
     * Menggunakan metode Hamilton / Largest Remainder Method agar distribusi 100% adil, proporsional, dan presisi tanpa sisa.
     */
    private function calculateProportionalQuota(int $totalTarget, string $kategori = 'semua'): array
    {
        $query = DB::table('rombongan_belajar')
            ->where('jenis_rombel', '1')
            ->orderBy('tingkat_pendidikan_id', 'asc')
            ->orderBy('nama', 'asc');

        if ($kategori === 'kelas_awal') {
            $query->where(function ($q) {
                $q->where('tingkat_pendidikan_id', '10')
                    ->orWhere('tingkat_pendidikan_id', 10)
                    ->orWhere('nama', 'LIKE', 'X %')
                    ->orWhere('nama', 'LIKE', 'X-%');
            });
        } elseif ($kategori === 'kelas_berjalan') {
            $query->where(function ($q) {
                $q->whereIn('tingkat_pendidikan_id', ['11', '12', 11, 12])
                    ->orWhere('nama', 'LIKE', 'XI %')
                    ->orWhere('nama', 'LIKE', 'XII %');
            });
        }

        $rombels = $query->get(['rombongan_belajar_id', 'nama']);

        if ($rombels->isEmpty()) {
            return [];
        }

        $studentCounts = [];
        if (Schema::hasTable('peserta_didik')) {
            $studentCounts = DB::table('peserta_didik')
                ->whereIn('rombongan_belajar_id', $rombels->pluck('rombongan_belajar_id'))
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
     *    - Bisa dipilih untuk: Semua Tingkat, Kelas Awal (X saja), atau Kelas Berjalan (XI & XII)
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
            'kategori_tingkat'      => 'nullable|string|in:semua,kelas_awal,kelas_berjalan',
            'tahun'                => 'nullable|string|max:20',
            'mode'                 => 'nullable|string|in:single,batch,set_all,distribute_total,distribute_proportional',
            'set_all'              => 'nullable|boolean',
        ]);

        $tahun = ($data['tahun'] ?? null) ?: date('Y');
        $kategoriTingkat = $data['kategori_tingkat'] ?? 'semua';
        $mode = $data['mode'] ?? (!empty($data['kuota_map']) ? 'batch' : (!empty($data['set_all']) ? 'set_all' : (!empty($data['total_kuota']) ? 'distribute_proportional' : 'single')));

        // 1. Simpan Seluruh Perubahan Kuota Sekaligus (1 Tombol Simpan Tunggal)
        if ($mode === 'batch' || !empty($data['kuota_map'])) {
            $map = (array) ($data['kuota_map'] ?? []);
            if (empty($map)) {
                return response()->json(['success' => false, 'message' => 'Tidak ada data kuota yang dikirim.'], 422);
            }

            foreach ($map as $rId => $val) {
                $kVal = max(0, (int) $val);
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => (string) $rId, 'tahun' => $tahun],
                    ['kuota' => $kVal]
                );
            }

            // Perbarui pagu total sekolah berdasarkan jumlah kuota seluruh rombel
            $totalBatch = (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $tahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            PipKuotaRombel::setTargetTotalSekolah($totalBatch, $tahun);

            $countSaved = count($map);
            return response()->json([
                'success' => true,
                'message' => "Semua perubahan kuota untuk {$countSaved} kelas berhasil disimpan (Total Pagu: {$totalBatch} siswa).",
            ]);
        }

        // 2. Penentuan Kuota Berdasarkan Persentase Jumlah Peserta Didik (Adil & Proporsional)
        // Mendukung: Semua Tingkat, Kelas Awal (X saja), atau Kelas Berjalan (XI & XII)
        if ($mode === 'distribute_proportional' || $mode === 'distribute_total' || isset($data['total_kuota'])) {
            $totalTarget = max(0, (int) ($data['total_kuota'] ?? 0));

            $proportionalAllocations = $this->calculateProportionalQuota($totalTarget, $kategoriTingkat);
            $count = count($proportionalAllocations);

            if ($count === 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada rombongan belajar aktif pada kategori tingkat yang dipilih.'], 422);
            }

            foreach ($proportionalAllocations as $rId => $kVal) {
                PipKuotaRombel::updateOrCreate(
                    ['rombongan_belajar_id' => $rId, 'tahun' => $tahun],
                    ['kuota' => (int) $kVal]
                );
            }

            // Hitung total pagu baru sekolah
            $totalBaruSekolah = (int) DB::table('pip_kuota_rombel')
                ->where('tahun', $tahun)
                ->where('rombongan_belajar_id', '!=', '__TOTAL__')
                ->sum('kuota');
            PipKuotaRombel::setTargetTotalSekolah($totalBaruSekolah, $tahun);

            $labelKategori = match ($kategoriTingkat) {
                'kelas_awal'     => 'Kelas Awal (Khusus Kelas X)',
                'kelas_berjalan' => 'Kelas Berjalan (Khusus Kelas XI & XII)',
                default          => 'Seluruh Tingkat (Kelas X, XI, XII)',
            };

            return response()->json([
                'success' => true,
                'message' => "Pagu {$totalTarget} siswa berhasil dibagikan secara adil ke {$count} rombel {$labelKategori} berdasarkan persentase jumlah peserta didik aktif.",
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
        $pdIdByNisn = DB::table('peserta_didik')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn')->all();
        $pdtaIdByNisn = Schema::hasTable('peserta_didik_tidak_aktif')
            ? DB::table('peserta_didik_tidak_aktif')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn')->all()
            : [];

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
            if ($pdId === '' && $nisn !== '') {
                $pdId = $pdIdByNisn[$nisn] ?? ($pdtaIdByNisn[$nisn] ?? '');
            }
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

    /**
     * Simpan Pengaturan Kunci & Batas Waktu Usulan PIP (Admin Only)
     */
    public function updatePengaturan(Request $request): JsonResponse
    {
        $this->ensurePipSchema();

        if (!$this->isAdminUser()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Khusus Administrator.'], 403);
        }

        $data = $request->validate([
            'tahun'            => 'required|string|max:20',
            'fase'             => 'nullable|in:fase_1,fase_2',
            'tahun_pelajaran'  => 'nullable|string|max:30',
            'judul_usulan'     => 'nullable|string|max:150',
            'is_locked'        => 'nullable',
            'kunci_otomatis'   => 'nullable',
            'batas_waktu'      => 'nullable|string',
            'pesan_pengumuman' => 'nullable|string|max:1000',
        ]);

        $settings = PipPengaturan::getSettings($data['tahun']);

        $user = session('user');
        $updater = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Admin')) : ($user->nama ?? ($user->name ?? 'Admin'));

        $isLocked = filter_var($request->input('is_locked'), FILTER_VALIDATE_BOOLEAN);
        $kunciOtomatis = filter_var($request->input('kunci_otomatis', true), FILTER_VALIDATE_BOOLEAN);

        $batasWaktu = null;
        if (!empty($data['batas_waktu'])) {
            try {
                $batasWaktu = \Carbon\Carbon::parse($data['batas_waktu']);
            } catch (\Throwable) {}
        }

        $fase = $data['fase'] ?? ($settings->fase ?: (date('n') <= 6 ? 'fase_1' : 'fase_2'));
        $faseLabel = PipUsulan::getFaseLabel($fase);
        $judul = trim((string) ($data['judul_usulan'] ?? ''));
        if ($judul === '') {
            $judul = "Usulan PIP Tahun {$data['tahun']} — {$faseLabel}";
        }

        $settings->fase = $fase;
        $settings->tahun_pelajaran = !empty($data['tahun_pelajaran']) ? trim($data['tahun_pelajaran']) : $settings->tahun_pelajaran;
        $settings->judul_usulan = $judul;
        $settings->is_locked = $isLocked;
        $settings->kunci_otomatis = $kunciOtomatis;
        $settings->batas_waktu = $batasWaktu;
        $settings->pesan_pengumuman = $data['pesan_pengumuman'] ?? null;
        $settings->updated_by = $updater;
        $settings->save();

        $info = PipPengaturan::getStatusInfo($data['tahun']);

        $statusDesc = $info['is_closed'] ? 'Pengusulan saat ini TERKUNCI untuk Wali Kelas.' : 'Pengusulan saat ini DIBUKA untuk Wali Kelas.';

        return response()->json([
            'success'     => true,
            'message'     => "Pengaturan {$judul} berhasil disimpan. {$statusDesc}",
            'status_info' => $info,
        ]);
    }
}
