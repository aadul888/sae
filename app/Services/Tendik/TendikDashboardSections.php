<?php

namespace App\Services\Tendik;

use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Angka ringkas modul per bidang tugas (piket, teknisi, keamanan, penjaga, perpus, laboran).
 */
class TendikDashboardSections
{
    public static function piket(int $totalGuru): array
    {
        $today = date('Y-m-d');
        $stats = [
            'total_guru'    => $totalGuru ?: 0,
            'guru_hadir'    => 0,
            'jurnal_terisi' => 0,
            'izin_hari_ini' => 0,
            'izin_menunggu' => 0,
        ];
        $recentIzinSiswa = collect();
        $recentAgendaKbm = collect();
        $recentPresensiGuru = collect();

        if (Table::exists('presensi_mengajar')) {
            $stats['guru_hadir'] = DB::table('presensi_mengajar')->where('tanggal', $today)->where('status', 'hadir')->distinct('ptk_id')->count('ptk_id');
            $recentPresensiGuru = DB::table('presensi_mengajar as pm')
                ->leftJoin('gtk', 'pm.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'pm.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('pm.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'pm.nama_mata_pelajaran', 'pm.status', 'pm.created_at')
                ->orderByDesc('pm.created_at')
                ->limit(6)
                ->get();
        } elseif (Table::exists('presensi_harian')) {
            $stats['guru_hadir'] = DB::table('presensi_harian')->where('tanggal', $today)->where('status', 'hadir')->count();
        }

        if (Table::exists('agenda_kbm')) {
            $stats['jurnal_terisi'] = DB::table('agenda_kbm')->where('tanggal', $today)->count();
            $recentAgendaKbm = DB::table('agenda_kbm as ak')
                ->leftJoin('gtk', 'ak.ptk_id', '=', 'gtk.ptk_id')
                ->leftJoin('rombongan_belajar as rb', 'ak.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->where('ak.tanggal', $today)
                ->select('gtk.nama as guru_nama', 'rb.nama as rombel_nama', 'ak.nama_mata_pelajaran', 'ak.jam_ke_mulai', 'ak.jam_ke_selesai', 'ak.materi_pokok', 'ak.uraian_kegiatan', 'ak.status_kbm', 'ak.created_at')
                ->orderByDesc('ak.created_at')
                ->limit(6)
                ->get();
        }

        if (Table::exists('presensi_izin')) {
            $inRange = fn () => DB::table('presensi_izin')
                ->whereDate('tanggal_mulai', '<=', $today)
                ->whereDate('tanggal_selesai', '>=', $today);

            $stats['izin_hari_ini'] = $inRange()->count();
            $stats['izin_menunggu'] = $inRange()->where('status', 'menunggu')->count();

            $recentIzinSiswa = DB::table('presensi_izin as pi')
                ->leftJoin('peserta_didik as pd', 'pi.peserta_didik_id', '=', 'pd.peserta_didik_id')
                ->leftJoin('rombongan_belajar as rb', 'pi.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->whereDate('pi.tanggal_mulai', '<=', $today)
                ->whereDate('pi.tanggal_selesai', '>=', $today)
                ->select('pd.nama as siswa_nama', 'pd.nisn', 'rb.nama as rombel_nama', 'pi.jenis', 'pi.alasan', 'pi.status', 'pi.created_at')
                ->orderByDesc('pi.created_at')
                ->limit(6)
                ->get();
        }

        return compact('stats', 'recentIzinSiswa', 'recentAgendaKbm', 'recentPresensiGuru');
    }

    public static function teknisi(): array
    {
        $stats = ['wo_total' => 0, 'wo_antrean' => 0, 'wo_proses' => 0, 'wo_selesai' => 0, 'wo_menunggu_part' => 0];
        if (Table::exists('teknisi_work_order')) {
            $stats = [
                'wo_total'         => (int) DB::table('teknisi_work_order')->count(),
                'wo_antrean'       => (int) DB::table('teknisi_work_order')->where('status', 'antrean')->count(),
                'wo_proses'        => (int) DB::table('teknisi_work_order')->where('status', 'proses')->count(),
                'wo_selesai'       => (int) DB::table('teknisi_work_order')->where('status', 'selesai')->count(),
                'wo_menunggu_part' => (int) DB::table('teknisi_work_order')->where('status', 'menunggu_sparepart')->count(),
            ];
        }

        $tupoksiMap = [
            1 => ['label' => 'Jadwal Area Tugas Kebersihan', 'tabel' => 'teknisi_area_tugas'],
            2 => ['label' => 'Kebersihan Lingkungan & Ruang Kerja', 'tabel' => 'teknisi_kebersihan_ruang'],
            3 => ['label' => 'Kebersihan Lingkungan Ruang Rapat', 'tabel' => 'teknisi_kebersihan_rapat'],
            4 => ['label' => 'Laporan Sarana Kegiatan Rapat', 'tabel' => 'teknisi_sarana_rapat'],
            5 => ['label' => 'Pengarsipan & Laporan Perbaikan Sarpras', 'tabel' => 'teknisi_jurnal_perbaikan'],
        ];
        $tupoksiRekap = [];
        foreach ($tupoksiMap as $no => $meta) {
            $tupoksiRekap[$no] = (object) [
                'no'     => $no,
                'label'  => $meta['label'],
                'jumlah' => Table::exists($meta['tabel']) ? (int) DB::table($meta['tabel'])->count() : 0,
            ];
        }

        $statistik = [
            'indikator_terisi' => count(array_filter($tupoksiRekap, fn ($r) => $r->jumlah > 0)),
        ];

        return ['stats' => $stats, 'tupoksiRekap' => $tupoksiRekap, 'statistik' => $statistik];
    }

    public static function keamanan(): array
    {
        $today = date('Y-m-d');
        $stats = ['tamu_hari_ini' => 0, 'tamu_di_lokasi' => 0, 'patroli_hari_ini' => 0, 'insiden_aktif' => 0];
        if (Table::exists('keamanan_buku_tamu')) {
            $stats['tamu_hari_ini'] = (int) DB::table('keamanan_buku_tamu')->whereDate('tanggal', $today)->count();
            $stats['tamu_di_lokasi'] = (int) DB::table('keamanan_buku_tamu')->where('status', 'berada_di_lokasi')->count();
        }
        if (Table::exists('keamanan_patroli')) {
            $stats['patroli_hari_ini'] = (int) DB::table('keamanan_patroli')->whereDate('tanggal', $today)->count();
        }
        if (Table::exists('keamanan_insiden')) {
            $stats['insiden_aktif'] = (int) DB::table('keamanan_insiden')->where('status_penyelesaian', 'dalam_penanganan')->count();
        }
        return $stats;
    }

    public static function penjaga(): array
    {
        $today = date('Y-m-d');
        $stats = ['checklist_hari_ini' => 0, 'area_perlu_tindakan' => 0, 'ronda_hari_ini' => 0, 'temuan_malam' => 0];
        if (Table::exists('kebersihan_checklist')) {
            $stats['checklist_hari_ini'] = (int) DB::table('kebersihan_checklist')->whereDate('tanggal', $today)->count();
            $stats['area_perlu_tindakan'] = (int) DB::table('kebersihan_checklist')
                ->where(function ($q) {
                    $q->where('kondisi_kebersihan', 'perlu_tindakan')->orWhere('ketersediaan_air_sabun', 'air_mati');
                })->count();
        }
        if (Table::exists('penjaga_malam_log')) {
            $stats['ronda_hari_ini'] = (int) DB::table('penjaga_malam_log')->whereDate('tanggal', $today)->count();
            $stats['temuan_malam'] = (int) DB::table('penjaga_malam_log')->where('situasi_keamanan', '<>', 'kondusif')->count();
        }
        return $stats;
    }

    public static function perpus(): array
    {
        $today = date('Y-m-d');
        $stats = ['total_judul' => 0, 'total_eksemplar' => 0, 'kunjungan_hari_ini' => 0, 'pinjam_aktif' => 0];
        if (Table::exists('perpus_koleksi_buku')) {
            $stats['total_judul'] = (int) DB::table('perpus_koleksi_buku')->count();
            $stats['total_eksemplar'] = (int) (DB::table('perpus_koleksi_buku')->sum('jumlah_eksemplar') ?: 0);
        }
        if (Table::exists('perpus_kunjungan')) {
            $stats['kunjungan_hari_ini'] = (int) DB::table('perpus_kunjungan')->whereDate('tanggal', $today)->count();
        }
        if (Table::exists('perpus_sirkulasi')) {
            $stats['pinjam_aktif'] = (int) DB::table('perpus_sirkulasi')->where('status', 'dipinjam')->count();
        }
        return $stats;
    }

    public static function laboran(): array
    {
        $stats = ['total_ruang_lab' => 0, 'item_total' => 0, 'item_baik' => 0, 'item_rusak' => 0];
        if (Table::exists('laboran_bahan_alat')) {
            $stats['total_ruang_lab'] = (int) DB::table('laboran_bahan_alat')
                ->whereNotNull('ruang_lab_nama')->where('ruang_lab_nama', '<>', '')
                ->distinct('ruang_lab_nama')->count('ruang_lab_nama');
            $stats['item_total'] = (int) DB::table('laboran_bahan_alat')->count();
            $stats['item_baik'] = (int) (DB::table('laboran_bahan_alat')->sum('jumlah_baik') ?: 0);
            $stats['item_rusak'] = (int) ((DB::table('laboran_bahan_alat')->sum('jumlah_rusak_ringan') ?: 0)
                + (DB::table('laboran_bahan_alat')->sum('jumlah_rusak_berat') ?: 0));
        }
        return $stats;
    }
}
