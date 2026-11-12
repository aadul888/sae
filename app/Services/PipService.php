<?php

namespace App\Services;

use App\Models\PipKuotaRombel;
use App\Models\PipRiwayat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Support\Table;
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
            if (!Table::exists('pip_kuota_rombel')) {
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

            if (!Table::exists('pip_usulan')) {
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

            if (!Table::exists('pip_riwayat')) {
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
                    $table->string('sekolah_id', 50)->nullable();
                    $table->string('npsn', 20)->nullable();
                    $table->string('nomenklatur', 150)->nullable();
                    $table->string('nik', 20)->nullable();
                    $table->string('tahap_id', 50)->nullable()->index();
                    $table->string('tahap_keterangan', 150)->nullable();
                    $table->string('nama_rekening', 150)->nullable();
                    $table->string('nama_kartu', 100)->nullable();
                    $table->string('semester_id', 20)->nullable();
                    $table->string('layak_pip', 20)->nullable();
                    $table->text('confirmation_text')->nullable();
                    $table->string('nama_pengusul', 150)->nullable();
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
                    foreach ([
                        'sekolah_id'       => 50,
                        'npsn'             => 20,
                        'nomenklatur'      => 150,
                        'nik'              => 20,
                        'tahap_id'         => 50,
                        'tahap_keterangan' => 150,
                        'nama_rekening'    => 150,
                        'nama_kartu'       => 100,
                        'semester_id'      => 20,
                        'layak_pip'        => 20,
                        'nama_pengusul'    => 150,
                    ] as $col => $len) {
                        if (!Schema::hasColumn('pip_riwayat', $col)) {
                            $table->string($col, $len)->nullable();
                        }
                    }
                    if (!Schema::hasColumn('pip_riwayat', 'confirmation_text')) {
                        $table->text('confirmation_text')->nullable();
                    }
                });
            }

            if (!Table::exists('pip_pengaturan')) {
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

            if (Table::exists('pip_usulan')) {
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

            if (Table::exists('pip_riwayat')) {
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
            if (Table::exists('pip_riwayat') && Table::exists('peserta_didik')) {
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
        if (Table::exists('peserta_didik')) {
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
     * Deteksi jenis SK dari isi berkas impor (header & nilai).
     *
     * Kolom SK Pemberian dan SK Nominasi berbeda:
     * - Pemberian: tanggal_cair, virtual_acc, nama_rekening, keterangan_pencairan.
     * - Nominasi : tanggal_aktivasi (+ status "aktivasi").
     *
     * Deteksi memakai signature kolom lebih dulu, bukan sekadar skor nilai.
     * Kembalikan null bila tidak dapat dipastikan.
     */
    public static function detectJenisSk(array $rows): ?string
    {
        $headers = self::normalizedHeaderMap($rows);
        $keys = array_keys($headers);

        $hasNominasiCol = false;
        foreach ($keys as $key) {
            if (self::isHeaderAktivasi($key)) {
                $hasNominasiCol = true;
                break;
            }
        }

        $hasPemberianCol = false;
        foreach ($keys as $key) {
            if (self::isHeaderPencairan($key)) {
                $hasPemberianCol = true;
                break;
            }
        }

        $aktivasiRows = 0;
        $cairRows = 0;
        $virtualRows = 0;

        foreach ($rows as $row) {
            $status = strtolower(trim((string) ($row[$headers['statuscair'] ?? ''] ?? '')));
            if (self::rowHasAktivasi($row, $headers) || str_contains($status, 'aktivasi')) {
                $aktivasiRows++;
            }
            if (self::rowHasTanggalCair($row, $headers)) {
                $cairRows++;
            }
            if (trim((string) ($row[$headers['virtualacc'] ?? ''] ?? '')) !== '') {
                $virtualRows++;
            }
        }

        $pencairanRows = $cairRows + $virtualRows;

        if ($hasNominasiCol && !$hasPemberianCol) {
            return 'nominasi';
        }
        if ($hasPemberianCol && !$hasNominasiCol) {
            return 'pemberian';
        }

        if ($hasNominasiCol && $hasPemberianCol) {
            if ($aktivasiRows > 0 && $pencairanRows === 0) {
                return 'nominasi';
            }
            if ($pencairanRows > 0 && $aktivasiRows === 0) {
                return 'pemberian';
            }
            if ($aktivasiRows > $pencairanRows) {
                return 'nominasi';
            }
            if ($pencairanRows > $aktivasiRows) {
                return 'pemberian';
            }
            return null;
        }

        if ($aktivasiRows > 0 && $pencairanRows === 0) {
            return 'nominasi';
        }
        if ($pencairanRows > 0 && $aktivasiRows === 0) {
            return 'pemberian';
        }

        return null;
    }

    /**
     * Validasi ketat kolom wajib sesuai jenis SK yang dipilih.
     * Mengembalikan pesan error bila berkas tidak memiliki kolom khas jenis SK tsb.
     */
    public static function jenisSkKolomError(array $rows, string $jenisSk): ?string
    {
        $keys = array_keys(self::normalizedHeaderMap($rows));

        $hasPemberianCol = false;
        $hasNominasiCol = false;
        foreach ($keys as $key) {
            if (!$hasPemberianCol && self::isHeaderPencairan($key)) {
                $hasPemberianCol = true;
            }
            if (!$hasNominasiCol && self::isHeaderAktivasi($key)) {
                $hasNominasiCol = true;
            }
        }

        if ($jenisSk === 'pemberian' && !$hasPemberianCol) {
            return 'Berkas tidak memiliki kolom pencairan dana (tanggal_cair / virtual_acc / keterangan_pencairan). Berkas ini terindikasi SK Nominasi; pilih SK Nominasi atau unggah berkas SK Pemberian.';
        }
        if ($jenisSk === 'nominasi' && !$hasNominasiCol) {
            return 'Berkas tidak memiliki kolom aktivasi rekening (tanggal_aktivasi). Berkas ini terindikasi SK Pemberian; pilih SK Pemberian atau unggah berkas SK Nominasi.';
        }

        return null;
    }

    /**
     * Normalisasi nama kolom berkas impor ke key baku yang dipakai importRiwayatRows.
     * Menangani variasi spasi, titik, huruf besar, dan BOM dari ekspor SiPintar.
     */
    public static function normalizeImportRows(array $rows): array
    {
        $canonical = [
            'namapd'            => 'nama_pd',
            'namarombel'        => 'nama_rombel',
            'pesertadidikid'    => 'peserta_didik_id',
            'namaibukandung'    => 'nama_ibu_kandung',
            'namaayah'          => 'nama_ayah',
            'tanggallahir'      => 'tanggal_lahir',
            'tempatlahir'       => 'tempat_lahir',
            'jeniskelamin'      => 'jenis_kelamin',
            'nomorsk'           => 'nomor_sk',
            'tanggalsk'         => 'tanggal_sk',
            'tahapid'           => 'tahap_id',
            'tahapketerangan'   => 'tahap_keterangan',
            'semesterid'        => 'semester_id',
            'statuscair'        => 'status_cair',
            'tanggalcair'       => 'tanggal_cair',
            'tglcair'           => 'tanggal_cair',
            'tanggalpencairan'  => 'tanggal_cair',
            'tglpencairan'      => 'tanggal_cair',
            'tanggalaktivasi'   => 'tanggal_aktivasi',
            'tglaktivasi'       => 'tanggal_aktivasi',
            'virtualacc'        => 'virtual_acc',
            'nomorvirtualacc'   => 'virtual_acc',
            'namarekening'      => 'nama_rekening',
            'keteranganpencairan' => 'keterangan_pencairan',
        ];

        $normalizedRows = [];
        foreach ($rows as $row) {
            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $raw = (string) $key;
                $normalized = strtolower(trim($raw));
                $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized) ?? $normalized;

                $normalizedRow[$canonical[$normalized] ?? $raw] = $value;
            }
            $normalizedRows[] = $normalizedRow;
        }

        return $normalizedRows;
    }

    /**
     * Peta header ternormalisasi => nama kolom asli.
     */
    private static function normalizedHeaderMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            foreach ($row as $key => $value) {
                $raw = (string) $key;
                if ($raw === '' || str_starts_with(strtolower($raw), '__col_')) {
                    continue;
                }
                $normalized = strtolower(trim($raw));
                $normalized = preg_replace('/^\xEF\xBB\xBF/', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/[^a-z0-9]+/', '', $normalized) ?? $normalized;
                if ($normalized === '') {
                    continue;
                }
                if (!isset($map[$normalized])) {
                    $map[$normalized] = $raw;
                }
            }
        }

        return $map;
    }

    private static function isHeaderAktivasi(string $key): bool
    {
        return (bool) preg_match('/^(tanggal|tgl)aktivasi/', $key);
    }

    private static function isHeaderPencairan(string $key): bool
    {
        return in_array($key, [
            'tanggalcair',
            'tglcair',
            'tanggalpencairan',
            'tglpencairan',
            'virtualacc',
            'nomorvirtualacc',
            'keteranganpencairan',
        ], true);
    }

    private static function rowHasAktivasi(array $row, array $headers): bool
    {
        foreach ($headers as $normalized => $original) {
            if (self::isHeaderAktivasi($normalized) && trim((string) ($row[$original] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function rowHasTanggalCair(array $row, array $headers): bool
    {
        foreach (['tanggalcair', 'tglcair', 'tanggalpencairan', 'tglpencairan'] as $key) {
            if (!empty($headers[$key]) && trim((string) ($row[$headers[$key]] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Proses impor baris transaksi riwayat PIP dari berkas SiPintar.
     */
    public static function importRiwayatRows(array $rows, string $jenisSk): array
    {
        $pdMapByPdId = DB::table('peserta_didik')->pluck('rombongan_belajar_id', 'peserta_didik_id')->all();
        $pdMapByNisn = DB::table('peserta_didik')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('rombongan_belajar_id', 'nisn')->all();
        $pdIdByNisn = DB::table('peserta_didik')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn')->all();
        $pdtaIdByNisn = Table::exists('peserta_didik_tidak_aktif')
            ? DB::table('peserta_didik_tidak_aktif')->whereNotNull('nisn')->where('nisn', '!=', '')->pluck('peserta_didik_id', 'nisn')->all()
            : [];

        $imported = 0;
        $updated = 0;
        $skippedPemberianCount = 0;
        $skippedDuplicateCount = 0;
        $seen = [];

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

            $nominal = self::parseNominal($row['nominal'] ?? '');
            $nomorSk = trim((string) ($row['nomor_sk'] ?? ''));
            $tglSk = self::parseTanggal($row['tanggal_sk'] ?? '');
            $tahapId = trim((string) ($row['tahap_id'] ?? ''));
            $tahapKet = trim((string) ($row['tahap_keterangan'] ?? ''));
            $tahap = $tahapKet !== '' ? $tahapKet : ($tahapId !== '' ? $tahapId : trim((string) ($row['tahap'] ?? '')));
            $semesterId = trim((string) ($row['semester_id'] ?? ''));
            $tahun = $semesterId !== '' ? $semesterId : trim((string) ($row['tahun'] ?? date('Y')));
            if (strlen($tahun) >= 4) {
                $tahun = substr($tahun, 0, 4);
            }

            // Kunci unik: 1 NISN per jenis SK + tahap + tahun. Duplikat dalam berkas dilewati.
            $identitas = $nisn !== ''
                ? 'nisn:' . $nisn
                : ($pdId !== '' ? 'pd:' . $pdId : 'nama:' . mb_strtolower($nama) . '|' . $rombel);
            $tahapKey = $tahapId !== '' ? 'tid:' . $tahapId : 't:' . $tahap;
            $uniqueKey = $jenisSk . '|' . $identitas . '|' . $tahapKey . '|' . $tahun;

            if (isset($seen[$uniqueKey])) {
                $skippedDuplicateCount++;
                continue;
            }
            $seen[$uniqueKey] = true;

            $riwayatQuery = function (string $jenis) use ($pdId, $nisn, $nama, $rombel) {
                return PipRiwayat::where('jenis_sk', $jenis)->where(function ($q) use ($pdId, $nisn, $nama, $rombel) {
                    if ($nisn !== '') {
                        $q->where('nisn', $nisn);
                    } elseif ($pdId !== '') {
                        $q->where('peserta_didik_id', $pdId);
                    } else {
                        $q->where('nama_pd', $nama);
                        if ($rombel !== '') {
                            $q->where('rombel', $rombel);
                        }
                    }
                });
            };

            $periodeFilter = function ($q) use ($tahapId, $tahap, $tahun) {
                if ($tahapId !== '') {
                    $q->where(function ($sub) use ($tahapId, $tahap) {
                        $sub->where('tahap_id', $tahapId)->orWhere('tahap', $tahapId);
                        if ($tahap !== '' && $tahap !== $tahapId) {
                            $sub->orWhere('tahap', $tahap);
                        }
                    });
                } elseif ($tahap !== '') {
                    $q->where('tahap', $tahap);
                }
                if ($tahun !== '') {
                    $q->where('tahun', $tahun);
                }
            };

            // SK Nominasi dilewati bila sudah ada SK Pemberian pada tahap & tahun yang sama.
            if ($jenisSk === 'nominasi' && $riwayatQuery('pemberian')->where($periodeFilter)->exists()) {
                $skippedPemberianCount++;
                continue;
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

            $recordData = [
                'peserta_didik_id'     => $pdId ?: null,
                'nisn'                 => $nisn ?: null,
                'nik'                  => trim((string) ($row['nik'] ?? '')) ?: null,
                'nama_pd'              => $nama,
                'kelas'                => $kelas ?: null,
                'rombel'               => $rombel ?: null,
                'rombongan_belajar_id' => $rombelId,
                'sekolah_id'           => trim((string) ($row['sekolah_id'] ?? '')) ?: null,
                'npsn'                 => trim((string) ($row['npsn'] ?? '')) ?: null,
                'nomenklatur'          => trim((string) ($row['nomenklatur'] ?? '')) ?: null,
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
                'tahap_id'             => $tahapId ?: null,
                'tahap_keterangan'     => $tahapKet ?: null,
                'tahun'                => $tahun ?: date('Y'),
                'semester_id'          => $semesterId ?: null,
                'layak_pip'            => trim((string) ($row['layak_pip'] ?? '')) ?: null,
                'status_cair'          => $statusCair ?: null,
                'tanggal_cair'         => $tglCair ?: null,
                'tanggal_aktivasi'     => $tglAktivasi ?: null,
                'no_rekening'          => $rekening ?: null,
                'nama_rekening'        => trim((string) ($row['nama_rekening'] ?? '')) ?: null,
                'no_kip'               => $noKip ?: null,
                'no_kks'               => $noKks ?: null,
                'no_kps'               => $noKps ?: null,
                'virtual_acc'          => $virtualAcc ?: null,
                'nama_kartu'           => trim((string) ($row['nama_kartu'] ?? '')) ?: null,
                'keterangan_pencairan' => $ketCair ?: null,
                'confirmation_text'    => trim((string) ($row['confirmation_text'] ?? '')) ?: null,
                'nama_pengusul'        => trim((string) ($row['nama_pengusul'] ?? '')) ?: null,
                'raw_data'             => $row,
            ];

            $existing = $riwayatQuery($jenisSk)->where($periodeFilter)->first();
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
            $msg .= " Sebanyak {$skippedPemberianCount} siswa dilewati karena sudah terdaftar di SK Pemberian pada tahap & tahun yang sama.";
        }
        if ($skippedDuplicateCount > 0) {
            $msg .= " Sebanyak {$skippedDuplicateCount} baris duplikat (NISN sama pada tahap & tahun yang sama) dilewati.";
        }

        return [
            'imported'          => $imported,
            'updated'           => $updated,
            'skipped_pemberian' => $skippedPemberianCount,
            'skipped_duplikat'  => $skippedDuplicateCount,
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
