<?php

namespace App\Services;

use App\Models\PesertaDidikMeta;
use App\Models\User;
use App\Support\SemesterHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;


use App\Support\Table;

class PesertaDidikImportService
{
    /**
     * Unduh berkas template Microsoft Excel (.xlsx) untuk import peserta didik aktif sementara.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'tpl_pd_') . '.xlsx';

        $headers = [
            'NISN',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Rombel Kelas',
            'NIPD',
            'Tempat Lahir',
            'Tanggal Lahir',
            'NIK',
        ];

        $sampleRows = [
            ['0081234561', 'Ahmad Rizki Pratama', 'L', 'X PPLG 1', '2425001', 'Cianjur', '2008-05-17', '3203010101080001'],
            ['0081234562', 'Siti Nur Aisyah', 'P', 'X PPLG 1', '2425002', 'Bandung', '2008-08-20', '3203014101080002'],
            ['0081234563', 'Budi Santoso', 'L', 'XI TJKT 2', '2324015', 'Jakarta', '2007-11-12', '3203010101070003'],
        ];

        SimpleXlsxExporter::export($tempPath, [], $headers, $sampleRows, [
            'sheetName' => 'Peserta Didik Aktif',
            'rawTable' => true,
            'headerBg' => '#4338CA',
        ]);

        return response()->download($tempPath, 'Template_Import_Peserta_Didik_Aktif.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import berkas Excel / CSV untuk penambahan peserta didik aktif sementara.
     */
    public function importExcel(Request $request): array
    {
        $file = $request->file('file_excel');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv', 'txt'], true)) {
            return [
                'status' => 'error',
                'message' => 'Format berkas tidak didukung. Unggah berkas Excel (.xlsx, .xls) atau CSV (.csv).',
                'status_code' => 422,
            ];
        }

        $path = $file->getRealPath();

        try {
            $rows = SimpleXlsxReader::readFile($path);
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Gagal membaca berkas: ' . $e->getMessage(),
                'status_code' => 422,
            ];
        }

        if (empty($rows)) {
            return [
                'status' => 'error',
                'message' => 'Berkas Excel kosong atau baris data tidak terdeteksi.',
                'status_code' => 422,
            ];
        }

        if (!Table::exists('peserta_didik')) {
            return [
                'status' => 'error',
                'message' => 'Tabel peserta didik belum tersedia di database.',
                'status_code' => 500,
            ];
        }

        $sekolah = Table::exists('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahId = $sekolah?->sekolah_id ?? null;
        $activeSemesterId = SemesterHelper::getActiveSemesterId();

        $rombelMap = collect();
        if (Table::exists('rombongan_belajar')) {
            // Hanya rombel reguler: nama kelas tidak boleh tertimpa rombel mapil
            // yang namanya sama (mis. "XI TKJ 2" ada sebagai Kelas dan Matpel Pilihan).
            $rombelMap = \App\Support\RombelScope::query()->get()->keyBy(function ($r) {
                return strtolower(trim($r->nama));
            });
        }

        $extractCol = function (array $row, array $keywords, ?int $fallbackIndex = null): string {
            foreach ($keywords as $kw) {
                if (isset($row[$kw]) && trim((string)$row[$kw]) !== '') {
                    return trim((string)$row[$kw]);
                }
            }
            foreach ($row as $k => $v) {
                $cleanK = preg_replace('/[^a-z0-9]/', '', strtolower((string)$k));
                foreach ($keywords as $kw) {
                    $cleanKw = preg_replace('/[^a-z0-9]/', '', strtolower($kw));
                    if ($cleanK === $cleanKw && trim((string)$v) !== '') {
                        return trim((string)$v);
                    }
                }
            }
            foreach ($row as $k => $v) {
                $cleanK = preg_replace('/[^a-z0-9]/', '', strtolower((string)$k));
                foreach ($keywords as $kw) {
                    $cleanKw = preg_replace('/[^a-z0-9]/', '', strtolower($kw));
                    if (str_starts_with($cleanK, $cleanKw) && trim((string)$v) !== '') {
                        return trim((string)$v);
                    }
                }
            }
            if ($fallbackIndex !== null) {
                $vals = array_values($row);
                if (isset($vals[$fallbackIndex]) && trim((string)$vals[$fallbackIndex]) !== '') {
                    return trim((string)$vals[$fallbackIndex]);
                }
            }
            return '';
        };

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;

            $nisnRaw = $extractCol($row, ['nisn', 'nomor_induk_siswa_nasional', 'no_nisn', 'nomor_nisn', 'nisn_*', 'nisn___'], 0);
            $nisn = preg_replace('/[^0-9]/', '', $nisnRaw);

            $nama = $extractCol($row, ['nama_lengkap', 'nama', 'nama_siswa', 'nama_peserta_didik', 'nama_pd', 'nama_lengkap_*', 'nama_lengkap___'], 1);

            $jkRaw = strtoupper($extractCol($row, ['jenis_kelamin', 'l_p', 'jk', 'gender', 'kelamin', 'sex', 'l_p_*', 'l_p___'], 2));
            $jk = str_starts_with($jkRaw, 'P') ? 'P' : 'L';

            $rombel = $extractCol($row, ['rombel_kelas', 'nama_rombel', 'rombel', 'kelas', 'rombongan_belajar', 'rombel_kelas_*', 'rombel_kelas___'], 3);
            $nipd = $extractCol($row, ['nipd', 'no_induk', 'nis', 'nomor_induk', 'nipd_*'], 4);
            $tempatLahir = $extractCol($row, ['tempat_lahir', 'tmp_lahir', 'tempat'], 5);
            $tglLahirRaw = $extractCol($row, ['tanggal_lahir', 'tgl_lahir', 'tgl'], 6);
            $tglLahir = $this->parseTanggal($tglLahirRaw);
            $nikRaw = $extractCol($row, ['nik', 'no_nik', 'nik_ktp'], 7);
            $nik = preg_replace('/[^0-9]/', '', $nikRaw);

            if ($nama === '' && $nisn === '') {
                continue;
            }

            if ($nisn === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: NISN tidak boleh kosong (Nama: " . ($nama ?: 'Tanpa Nama') . ").";
                continue;
            }

            if ($nama === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: Nama lengkap tidak boleh kosong (NISN: {$nisn}).";
                continue;
            }

            if ($rombel === '') {
                $skippedCount++;
                $errors[] = "Baris {$rowNum}: Rombel/Kelas tidak boleh kosong ({$nama} - NISN: {$nisn}).";
                continue;
            }

            $rombelKey = strtolower($rombel);
            $rombelRecord = $rombelMap[$rombelKey] ?? null;
            $rombelId = $rombelRecord?->rombongan_belajar_id ?? null;
            $tingkat = $rombelRecord?->tingkat_pendidikan_id ?? null;
            $kurikulum = $rombelRecord?->kurikulum_id_str ?? 'Kurikulum Merdeka';

            if (!$tingkat) {
                if (preg_match('/^(?:kelas\s*)?(?:12|xii)\b/i', $rombel)) {
                    $tingkat = '12';
                } elseif (preg_match('/^(?:kelas\s*)?(?:11|xi)\b/i', $rombel)) {
                    $tingkat = '11';
                } elseif (preg_match('/^(?:kelas\s*)?(?:10|x)\b/i', $rombel)) {
                    $tingkat = '10';
                } else {
                    $tingkat = '10';
                }
            }

            try {
                $existing = DB::table('peserta_didik')->where('nisn', $nisn)->first();

                if ($existing) {
                    $pdId = $existing->peserta_didik_id;
                    $updatePayload = [
                        'nama' => $nama,
                        'nama_rombel' => $rombel,
                        'updated_at' => now(),
                    ];
                    if ($jk) $updatePayload['jenis_kelamin'] = $jk;
                    if ($nipd !== '') $updatePayload['nipd'] = $nipd;
                    if ($tempatLahir !== '') $updatePayload['tempat_lahir'] = $tempatLahir;
                    if ($tglLahir) $updatePayload['tanggal_lahir'] = $tglLahir;
                    if ($nik !== '') $updatePayload['nik'] = $nik;
                    if ($rombelId) $updatePayload['rombongan_belajar_id'] = $rombelId;
                    if ($tingkat) $updatePayload['tingkat_pendidikan_id'] = $tingkat;
                    if ($kurikulum && empty($existing->kurikulum_id_str)) $updatePayload['kurikulum_id_str'] = $kurikulum;

                    DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->update($updatePayload);
                    $updatedCount++;
                } else {
                    $pdId = (string) Str::uuid();
                    $insertPayload = [
                        'peserta_didik_id' => $pdId,
                        'registrasi_id' => (string) Str::uuid(),
                        'nama' => $nama,
                        'nisn' => $nisn,
                        'nipd' => $nipd ?: null,
                        'nik' => $nik ?: null,
                        'jenis_kelamin' => $jk,
                        'tempat_lahir' => $tempatLahir ?: null,
                        'tanggal_lahir' => $tglLahir ?: null,
                        'nama_rombel' => $rombel,
                        'rombongan_belajar_id' => $rombelId,
                        'tingkat_pendidikan_id' => $tingkat,
                        'kurikulum_id_str' => $kurikulum,
                        'jenis_pendaftaran_id_str' => 'Siswa Baru',
                        'tanggal_masuk_sekolah' => now()->format('Y-m-d'),
                        'semester_id' => $activeSemesterId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    DB::table('peserta_didik')->insert($insertPayload);
                    $importedCount++;
                }

                if (Table::exists('peserta_didik_meta')) {
                    try {
                        PesertaDidikMeta::firstOrCreate(
                            ['peserta_didik_id' => $pdId],
                            ['nisn' => $nisn]
                        );
                    } catch (\Throwable $metaErr) {}
                }

                if (Table::exists('pengguna')) {
                    try {
                        $hasUser = DB::table('pengguna')
                            ->where('peserta_didik_id', $pdId)
                            ->orWhere('username', $nisn)
                            ->exists();

                        if (!$hasUser) {
                            User::create([
                                'pengguna_id' => (string) Str::uuid(),
                                'sekolah_id' => $sekolahId,
                                'username' => $nisn,
                                'nama' => $nama,
                                'peran_id_str' => 'Peserta Didik',
                                'password' => Hash::make($nisn),
                                'peserta_didik_id' => $pdId,
                                'password_updated_at' => null,
                            ]);
                        }
                    } catch (\Throwable $userErr) {}
                }

                if (Table::exists('peserta_didik_tidak_aktif')) {
                    try {
                        DB::table('peserta_didik_tidak_aktif')
                            ->where('peserta_didik_id', $pdId)
                            ->orWhere('nisn', $nisn)
                            ->delete();
                    } catch (\Throwable $taErr) {}
                }
            } catch (\Throwable $err) {
                $skippedCount++;
                $errors[] = "Baris {$rowNum} ({$nama}): " . $err->getMessage();
            }
        }

        $totalProcessed = $importedCount + $updatedCount;

        if ($totalProcessed === 0) {
            $errDetail = !empty($errors) ? implode(' ', array_slice($errors, 0, 3)) : 'Pastikan berkas Excel memiliki baris data dengan kolom NISN, Nama Lengkap, dan Rombel yang terisi.';
            return [
                'status' => 'error',
                'message' => 'Tidak ada data peserta didik yang berhasil diimpor. ' . $errDetail,
                'imported_count' => 0,
                'updated_count' => 0,
                'skipped_count' => $skippedCount,
                'errors' => array_slice($errors, 0, 10),
                'status_code' => 422,
            ];
        }

        $message = "Import berhasil diproses: {$importedCount} peserta didik baru ditambahkan, {$updatedCount} data diperbarui.";
        if ($skippedCount > 0) {
            $message .= " ({$skippedCount} baris dilewati).";
        }

        return [
            'status' => 'success',
            'message' => $message,
            'imported_count' => $importedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'total_processed' => $totalProcessed,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * Parsing tanggal dari Excel (serial number, format Y-m-d, D-M-Y, atau text).
     */
    public function parseTanggal(mixed $value): ?string
    {
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        if (is_numeric($s) && (float) $s > 20000 && (float) $s < 80000) {
            return gmdate('Y-m-d', (int) (((float) $s - 25569) * 86400));
        }

        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $s)) {
            $parts = explode('-', $s);
            return sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        $time = strtotime($s);
        if ($time !== false && $time > 0) {
            $year = (int) date('Y', $time);
            if ($year >= 1950 && $year <= 2100) {
                return date('Y-m-d', $time);
            }
        }

        return null;
    }
}
