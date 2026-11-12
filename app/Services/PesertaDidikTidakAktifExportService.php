<?php

namespace App\Services;

use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Ekspor daftar peserta didik tidak aktif / alumni ke CSV.
 * Dipisah dari controller agar berkas controller tetap di bawah 1.000 baris.
 */
class PesertaDidikTidakAktifExportService
{
    public function stream(Request $request)
    {
        $user = session('user');
        if (!$user) return redirect()->route('login');

        $status = trim($request->get('status', ''));
        $tahun  = trim($request->get('tahun', ''));

        $query = DB::table('peserta_didik_tidak_aktif');
        if (Table::exists('peserta_didik')) {
            $activePdIds = DB::table('peserta_didik')->pluck('peserta_didik_id')->filter()->all();
            if (!empty($activePdIds)) {
                $query->whereNotIn('peserta_didik_id', $activePdIds);
            }
        }
        if ($status !== '') {
            if ($status === 'Alumni') {
                $query->where('status_keluar', 'Alumni');
            } else {
                $query->where('status_keluar', '<>', 'Alumni');
            }
        }
        if ($tahun !== '') {
            $query->where(function ($w) use ($tahun) {
                $w->whereYear('tanggal_keluar', $tahun)->orWhere('tahun_lulus', $tahun);
            });
        }

        $items = $query->orderByDesc('tahun_lulus')->orderBy('nama')->get();

        $filename = 'Data_Alumni_Siswa_Tidak_Aktif_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($items) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'No',
                'Status',
                'NIPD',
                'Nama',
                'JK',
                'NISN',
                'Tempat Lahir',
                'Tanggal Lahir',
                'NIK',
                'Agama',
                'Kebutuhan Khusus',
                'Alamat',
                'RT',
                'RW',
                'Dusun',
                'Kelurahan',
                'Kecamatan',
                'Kode Pos',
                'Jenis Tinggal',
                'Alat Transportasi',
                'Telepon',
                'HP',
                'E-Mail',
                'SKHUN',
                'Penerima KPS',
                'No. KPS',
                'Data Ayah - Nama',
                'Data Ayah - Tahun Lahir',
                'Data Ayah - Jenjang Pendidikan',
                'Data Ayah - Pekerjaan',
                'Data Ayah - Penghasilan',
                'Data Ayah - Kebutuhan Khusus',
                'Data Ibu - Nama',
                'Data Ibu - Tahun Lahir',
                'Data Ibu - Jenjang Pendidikan',
                'Data Ibu - Pekerjaan',
                'Data Ibu - Penghasilan',
                'Data Ibu - Kebutuhan Khusus',
                'Data Wali - Nama',
                'Data Wali - Tahun Lahir',
                'Data Wali - Jenjang Pendidikan',
                'Data Wali - Pekerjaan',
                'Data Wali - Penghasilan',
                'Keluar Karena',
                'Tanggal keluar',
                'Tahun Lulus',
                'Rombel Terakhir',
            ]);

            $no = 1;
            foreach ($items as $r) {
                fputcsv($output, [
                    $no++,
                    $r->status_keluar ?? 'Alumni',
                    $r->nipd ? "'" . $r->nipd : '-',
                    $r->nama ?? '-',
                    $r->jenis_kelamin ?? '-',
                    $r->nisn ? "'" . $r->nisn : '-',
                    $r->tempat_lahir ?? '-',
                    $r->tanggal_lahir ?? '-',
                    $r->nik ? "'" . $r->nik : '-',
                    $r->agama_id_str ?? '-',
                    $r->kebutuhan_khusus ?? 'Tidak ada',
                    $r->alamat_jalan ?? '-',
                    $r->rt ?? '-',
                    $r->rw ?? '-',
                    $r->dusun ?? '-',
                    $r->kelurahan ?? '-',
                    $r->kecamatan ?? '-',
                    $r->kode_pos ?? '-',
                    $r->jenis_tinggal ?? '-',
                    $r->alat_transportasi ?? '-',
                    $r->nomor_telepon_rumah ?? '-',
                    $r->nomor_telepon_seluler ?? '-',
                    $r->email ?? '-',
                    $r->skhun ?? '-',
                    $r->penerima_kps ?? '-',
                    $r->no_kps ?? '-',
                    $r->nama_ayah ?? '-',
                    $r->tahun_lahir_ayah ?? '-',
                    $r->jenjang_pendidikan_ayah ?? '-',
                    $r->pekerjaan_ayah ?? '-',
                    $r->penghasilan_ayah ?? '-',
                    $r->kebutuhan_khusus_ayah ?? '-',
                    $r->nama_ibu ?? '-',
                    $r->tahun_lahir_ibu ?? '-',
                    $r->jenjang_pendidikan_ibu ?? '-',
                    $r->pekerjaan_ibu ?? '-',
                    $r->penghasilan_ibu ?? '-',
                    $r->kebutuhan_khusus_ibu ?? '-',
                    $r->nama_wali ?? '-',
                    $r->tahun_lahir_wali ?? '-',
                    $r->jenjang_pendidikan_wali ?? '-',
                    $r->pekerjaan_wali ?? '-',
                    $r->penghasilan_wali ?? '-',
                    $r->alasan_keluar ?? 'Lulus',
                    $r->tanggal_keluar ?? '-',
                    $r->tahun_lulus ?? '-',
                    $r->nama_rombel_terakhir ?? '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
