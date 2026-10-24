<?php

namespace App\Services;

use App\Models\PipPengaturan;
use App\Models\PipUsulan;
use App\Services\SimpleXlsxExporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Service khusus pembuatan dan ekspor data usulan PIP ke Microsoft Excel.
 * ponytail: static-exporter; upgrade ke queued job (Chunked Export) jika usulan sekolah melebihi 10.000 siswa.
 */
class PipExportService
{
    /**
     * Ekspor Data Usulan PIP Lengkap ke Microsoft Excel (.xlsx)
     */
    public static function exportUsulan(array $ctx, array $filters): BinaryFileResponse
    {
        $user = $ctx['user'];
        $isAdmin = $ctx['isAdmin'];
        $activeRombel = $ctx['activeRombel'];
        $activeTahun = $ctx['activeTahun'];

        $q = trim((string) ($filters['q'] ?? ''));
        $statusVerif = trim((string) ($filters['status_verifikasi'] ?? ''));
        $faseFilter = trim((string) ($filters['fase'] ?? ''));
        $statusPerubahanFilter = trim((string) ($filters['status_perubahan'] ?? ''));

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
                'headerBg'   => 'FF0F766E',
                'centerCols' => [0, 1, 3, 4, 5, 6, 8, 12, 13, 14, 15, 17, 31, 33],
            ]
        );

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
