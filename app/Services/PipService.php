<?php

namespace App\Services;

use App\Models\PipKuotaRombel;
use App\Models\PipRiwayat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service pemrosesan logika bisnis modul Program Indonesia Pintar (PIP).
 * ponytail: static-orchestrator; upgrade ke dynamic repository/queue worker bila import/rekapitulasi melebihi 50.000 baris.
 */
class PipService
{
    /**
     * Pastikan tabel dan kolom runtime PIP tersedia.
     */
    public static function ensureSchema(): void
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
     * Hitung alokasi kuota per rombel berdasarkan persentase jumlah peserta didik aktif.
     * Menggunakan Largest Remainder Method (Hamilton) agar pembagian presisi tanpa sisa.
     */
    public static function calculateProportionalQuota(int $totalTarget, string $kategori = 'semua'): array
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
     * Konversi nilai nominal dari format teks/Excel ke float murni.
     */
    public static function parseNominal(mixed $value): ?float
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
     * Konversi serial date Excel atau teks tanggal ke string Y-m-d jika terdeteksi.
     */
    public static function parseTanggal(mixed $value): string
    {
        $s = trim((string) $value);
        if ($s !== '' && is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return gmdate('Y-m-d', (int) (((float) $s - 25569) * 86400));
        }
        return $s;
    }

    /**
     * Proses impor baris transaksi riwayat PIP dari berkas SiPintar.
     */
    public static function importRiwayatRows(array $rows, string $jenisSk): array
    {
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
            $tglLahir = self::parseTanggal($row['tanggal_lahir'] ?? '');
            $tempatLahir = trim((string) ($row['tempat_lahir'] ?? ''));
            $jk = strtoupper(trim((string) ($row['jenis_kelamin'] ?? '')));

            // Validasi khusus SK Nominasi: lewati jika siswa sudah ada di SK Pemberian
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
                    continue;
                }
            }

            $nominal = self::parseNominal($row['nominal'] ?? '');
            $nomorSk = trim((string) ($row['nomor_sk'] ?? ''));
            $tglSk = self::parseTanggal($row['tanggal_sk'] ?? '');
            $tahap = trim((string) ($row['tahap_keterangan'] ?? ($row['tahap_id'] ?? ($row['tahap'] ?? ''))));
            $tahun = trim((string) ($row['semester_id'] ?? date('Y')));
            if (strlen($tahun) >= 4) {
                $tahun = substr($tahun, 0, 4);
            }

            $tglCair = self::parseTanggal($row['tanggal_cair'] ?? '');
            $tglAktivasi = self::parseTanggal($row['tanggal_aktivasi'] ?? '');

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

        return [
            'imported'          => $imported,
            'updated'           => $updated,
            'skipped_pemberian' => $skippedPemberianCount,
            'message'           => $msg,
        ];
    }

    /**
     * Agregasi metrik untuk tab Dashboard Overview PIP.
     */
    public static function buildDashboardData(
        bool $isAdmin,
        $activeRombel,
        string $activeTahun,
        $rombelList,
        bool $hasUsulanTable,
        bool $hasKuotaTable,
        bool $hasRiwayatTable
    ): array {
        return PipQueryService::buildDashboardData(
            $isAdmin,
            $activeRombel,
            $activeTahun,
            $rombelList,
            $hasUsulanTable,
            $hasKuotaTable,
            $hasRiwayatTable
        );
    }
}
