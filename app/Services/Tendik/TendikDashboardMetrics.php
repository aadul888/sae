<?php

namespace App\Services\Tendik;

use App\Models\Pengumuman;
use App\Services\PersuratanHddService;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Pengumpul metrik ringkas per bidang tugas Tendik (stats + daftar + chart).
 * ponytail: agregat langsung per request; tambah cache bila dashboard jadi bottleneck.
 */
class TendikDashboardMetrics
{
    public static function stats(): array
    {
        $totalSuratMasuk = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'masuk')->count() : 0;
        $totalSuratKeluar = Table::exists('persuratan') ? DB::table('persuratan')->where('jenis_surat', 'keluar')->count() : 0;
        $totalSuratKeterangan = Table::exists('surat_keterangan_pd') ? DB::table('surat_keterangan_pd')->count() : 0;

        $totalSiswa = Table::exists('peserta_didik') ? DB::table('peserta_didik')->count() : 0;
        $siswaBerfoto = Table::exists('peserta_didik_meta') ? DB::table('peserta_didik_meta')->whereNotNull('foto_path')->count() : 0;

        return [
            'total_surat_masuk'        => $totalSuratMasuk,
            'total_surat_keluar'       => $totalSuratKeluar,
            'total_surat_ket'          => $totalSuratKeterangan,
            'total_persuratan'         => $totalSuratMasuk + $totalSuratKeluar,
            'no_surat_masuk_terakhir'  => Table::exists('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'masuk')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-',
            'no_surat_keluar_terakhir' => Table::exists('persuratan') ? (DB::table('persuratan')->where('jenis_surat', 'keluar')->orderByDesc('id')->value('nomor_surat') ?: '-') : '-',
            'hdd_status'               => PersuratanHddService::checkStatus(),
            'total_siswa'              => $totalSiswa,
            'siswa_laki'               => Table::exists('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'L')->count() : 0,
            'siswa_perempuan'          => Table::exists('peserta_didik') ? DB::table('peserta_didik')->where('jenis_kelamin', 'P')->count() : 0,
            'siswa_berfoto'            => $siswaBerfoto,
            'persen_foto'              => $totalSiswa > 0 ? round(($siswaBerfoto / $totalSiswa) * 100) . '%' : '0%',
            'total_rombel'             => Table::exists('rombongan_belajar') ? DB::table('rombongan_belajar')->count() : 0,
            'total_guru'               => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'like', '%guru%')->count() : 0,
            'total_tendik'             => Table::exists('gtk') ? DB::table('gtk')->where('jenis_ptk_id_str', 'not like', '%guru%')->count() : 0,
            'gtk_tugas_tambahan'       => Table::exists('ptk_tugas_tambahan') ? DB::table('ptk_tugas_tambahan')->where('is_active', true)->distinct('ptk_id')->count('ptk_id') : 0,
            'total_jam_kbm'            => Table::exists('pembelajaran') ? (int) (DB::table('pembelajaran')->sum('jam_mengajar_per_minggu') ?: 0) : 0,
            'total_ruangan'            => Table::exists('rombongan_belajar') ? (int) DB::table('rombongan_belajar')->distinct('id_ruang_str')->count('id_ruang_str') : 0,
            'jam_praktik'              => Table::exists('pembelajaran') ? (int) (DB::table('pembelajaran')->where(function ($q) {
                $q->where('nama_mata_pelajaran', 'like', '%praktik%')
                    ->orWhere('nama_mata_pelajaran', 'like', '%kejuruan%');
            })->sum('jam_mengajar_per_minggu') ?: 0) : 0,
            'total_pengguna'           => Table::exists('pengguna') ? DB::table('pengguna')->count() : 0,
        ];
    }

    /** Aktivitas harian pengguna tendik (hari ini + bulan ini + 10 terakhir). */
    public static function aktivitas(mixed $userId, mixed $ptkId): array
    {
        $out = [
            'hariIniCount' => 0, 'selesai' => 0, 'proses' => 0, 'tertunda' => 0,
            'bulanIniCount' => 0, 'list' => collect(),
        ];
        if (!Table::exists('tendik_aktivitas')) {
            return $out;
        }

        $today = now()->toDateString();
        $base = DB::table('tendik_aktivitas')->where(function ($q) use ($userId, $ptkId) {
            if ($userId) $q->where('user_id', $userId);
            if ($ptkId) $q->orWhere('ptk_id', $ptkId);
        });

        $todayQuery = (clone $base)->whereDate('tanggal', $today);
        $out['hariIniCount'] = (clone $todayQuery)->count();
        $out['selesai'] = (clone $todayQuery)->where('status', 'selesai')->count();
        $out['proses'] = (clone $todayQuery)->where('status', 'proses')->count();
        $out['tertunda'] = (clone $todayQuery)->where('status', 'tertunda')->count();
        $out['bulanIniCount'] = (clone $base)->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->count();
        $out['list'] = (clone $base)->orderByDesc('tanggal')->orderByDesc('jam_mulai')->limit(10)->get();

        return $out;
    }

    public static function pengumuman(): \Illuminate\Support\Collection
    {
        return Table::exists('pengumuman')
            ? Pengumuman::forUserRole('tendik')->orderByDesc('created_at')->limit(5)->get()
            : collect();
    }

    /** Daftar pendukung umum: staf, persuratan, rombel, siswa, GTK, ruang, jadwal lab. */
    public static function daftarUmum(): array
    {
        $stafTas = collect();
        if (Table::exists('gtk')) {
            $stafTas = DB::table('gtk')
                ->leftJoin('ptk_tugas_tambahan as ptt', function ($join) {
                    $join->on('gtk.ptk_id', '=', 'ptt.ptk_id')->where('ptt.is_active', true);
                })
                ->leftJoin('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->where('gtk.jenis_ptk_id_str', 'not like', '%guru%')
                ->select('gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str', DB::raw('GROUP_CONCAT(rtt.nama SEPARATOR ", ") as tugas_tambahan'))
                ->groupBy('gtk.ptk_id', 'gtk.nama', 'gtk.nip', 'gtk.jabatan_ptk_id_str')
                ->orderBy('gtk.nama')
                ->get();
        }

        $persuratanList = collect();
        if (Table::exists('persuratan')) {
            $persuratanList = DB::table('persuratan')->orderByDesc('id')->limit(8)->get();
        }

        $rombelRekap = collect();
        if (Table::exists('rombongan_belajar')) {
            $rombelRekap = DB::table('rombongan_belajar as rb')
                ->leftJoin('anggota_rombel as ar', 'rb.rombongan_belajar_id', '=', 'ar.rombongan_belajar_id')
                ->select('rb.nama', 'rb.tingkat_pendidikan_id_str as tingkat', 'rb.jurusan_id_str as jurusan', DB::raw('COUNT(ar.peserta_didik_id) as total_siswa'))
                ->groupBy('rb.rombongan_belajar_id', 'rb.nama', 'rb.tingkat_pendidikan_id_str', 'rb.jurusan_id_str')
                ->orderBy('rb.nama')
                ->limit(8)
                ->get();
        }

        $siswaTerbaru = Table::exists('peserta_didik')
            ? DB::table('peserta_didik')->select('nama', 'nisn', 'nama_rombel', 'jenis_kelamin')->orderByDesc('peserta_didik_id')->limit(6)->get()
            : collect();

        $gtkTugasList = collect();
        if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
            $gtkTugasList = DB::table('ptk_tugas_tambahan as ptt')
                ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                ->leftJoin('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
                ->select('gtk.nama as gtk_nama', 'gtk.nip', 'rtt.nama as duty_name', 'ptt.nomor_sk', 'ptt.tmt_tugas')
                ->where('ptt.is_active', true)
                ->orderBy('gtk.nama')
                ->limit(8)
                ->get();
        }

        $gtkList = Table::exists('gtk')
            ? DB::table('gtk')->select('nama', 'nip', 'jenis_ptk_id_str', 'status_kepegawaian_id_str')->orderBy('nama')->limit(6)->get()
            : collect();

        $ruangList = Table::exists('rombongan_belajar')
            ? DB::table('rombongan_belajar')->select('nama as nama_rombel', 'id_ruang_str as ruang')->whereNotNull('id_ruang_str')->distinct()->orderBy('nama_rombel')->limit(8)->get()
            : collect();

        $jadwalLab = collect();
        if (Table::exists('pembelajaran') && Table::exists('rombongan_belajar')) {
            $jadwalLab = DB::table('pembelajaran as p')
                ->join('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('gtk', 'p.ptk_id', '=', 'gtk.ptk_id')
                ->where(function ($q) {
                    $q->where('p.nama_mata_pelajaran', 'like', '%kejuruan%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%praktik%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%agribisnis%')
                        ->orWhere('p.nama_mata_pelajaran', 'like', '%kehutanan%');
                })
                ->select('p.nama_mata_pelajaran', 'rb.nama as nama_rombel', 'gtk.nama as nama_guru', 'p.jam_mengajar_per_minggu')
                ->orderByDesc('p.jam_mengajar_per_minggu')
                ->limit(6)
                ->get();
        }

        return compact('stafTas', 'persuratanList', 'rombelRekap', 'siswaTerbaru', 'gtkTugasList', 'gtkList', 'ruangList', 'jadwalLab');
    }

    /** Ringkasan administrasi persuratan terbaru. */
    public static function administrasiTugas(): array
    {
        if (!Table::exists('persuratan')) {
            return [];
        }
        return DB::table('persuratan')
            ->select('nomor_surat', 'jenis_surat', 'perihal', 'pengirim_asal', 'tujuan_penerima', 'tanggal_surat', 'status')
            ->orderByDesc('tanggal_surat')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'nomor'    => $row->nomor_surat ?: '-',
                'kategori' => ucfirst(str_replace('_', ' ', $row->jenis_surat ?: 'lainnya')),
                'perihal'  => $row->perihal ?: '-',
                'pengirim' => $row->pengirim_asal ?: ($row->tujuan_penerima ?: '-'),
                'tgl'      => $row->tanggal_surat ? \Illuminate\Support\Carbon::parse($row->tanggal_surat)->translatedFormat('d M Y') : '-',
                'status'   => ucfirst(str_replace('_', ' ', $row->status ?: '-')),
            ])
            ->all();
    }
}
