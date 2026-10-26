<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul BUKU ADMINISTRASI JURUSAN.
 *
 * Indikator Tupoksi #1 — bukti fisik: Buku Administrasi Jurusan.
 * Mencatat surat masuk/keluar, SK, notulen rapat, daftar hadir,
 * program kerja, dan dokumen administrasi program keahlian lainnya.
 */
class LaboranAdministrasiController extends LaboranBaseController
{
    public const JENIS_DOKUMEN = [
        'surat_masuk'      => 'Surat Masuk',
        'surat_keluar'     => 'Surat Keluar',
        'sk'               => 'Surat Keputusan (SK)',
        'notulen_rapat'    => 'Notulen Rapat',
        'daftar_hadir'     => 'Daftar Hadir',
        'inventaris_ruang' => 'Inventaris Ruang',
        'program_kerja'    => 'Program Kerja Jurusan',
        'laporan'          => 'Laporan',
        'berita_acara'     => 'Berita Acara',
        'lainnya'          => 'Dokumen Lainnya',
    ];

    public const STATUS = [
        'aktif'      => 'Aktif',
        'arsip'      => 'Diarsipkan',
        'dibatalkan' => 'Dibatalkan',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search      = trim((string) $request->query('q', ''));
        $jenisFilter = trim((string) $request->query('jenis', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $taFilter    = trim((string) $request->query('tahun_ajaran', ''));

        $query = DB::table('laboran_administrasi as a')
            ->leftJoin('jurusan_meta as jm', 'a.jurusan_id', '=', 'jm.jurusan_id')
            ->select('a.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'a.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('a.perihal', 'like', "%{$search}%")
                  ->orWhere('a.nomor_dokumen', 'like', "%{$search}%")
                  ->orWhere('a.asal_tujuan', 'like', "%{$search}%")
                  ->orWhere('a.ringkasan', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '')  $query->where('a.jenis_dokumen', $jenisFilter);
        if ($statusFilter !== '') $query->where('a.status', $statusFilter);
        if ($taFilter !== '')     $query->where('a.tahun_ajaran', $taFilter);

        $statBase = DB::table('laboran_administrasi');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal = (clone $statBase)->count();
        $statAktif = (clone $statBase)->where('status', 'aktif')->count();
        $statArsip = (clone $statBase)->where('status', 'arsip')->count();
        $statBulanIni = (clone $statBase)
            ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('a.tanggal')->orderByDesc('a.id')
            ->paginate($perPage)->withQueryString();

        // Daftar tahun ajaran tersedia (untuk filter)
        $taQuery = DB::table('laboran_administrasi')
            ->whereNotNull('tahun_ajaran')
            ->distinct()
            ->orderByDesc('tahun_ajaran');
        LaboranScope::apply($taQuery, 'jurusan_id', $context);
        $tahunAjaranList = $taQuery->pluck('tahun_ajaran');

        return view('dashboard.laboran.administrasi', array_merge(
            compact('list', 'search', 'jenisFilter', 'statusFilter', 'taFilter', 'perPage',
                'statTotal', 'statAktif', 'statArsip', 'statBulanIni', 'tahunAjaranList'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'jenisList'      => self::JENIS_DOKUMEN,
                'statusList'     => self::STATUS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('laboran_administrasi')->insert([
            'jurusan_id'      => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'nomor_urut'      => $data['nomor_urut'] ?? null,
            'tanggal'         => $data['tanggal'],
            'tahun_ajaran'    => $data['tahun_ajaran'] ?? null,
            'semester'        => $data['semester'] ?? null,
            'jenis_dokumen'   => $data['jenis_dokumen'],
            'nomor_dokumen'   => $data['nomor_dokumen'] ?? null,
            'perihal'         => trim($data['perihal']),
            'asal_tujuan'     => $data['asal_tujuan'] ?? null,
            'tanggal_dokumen' => $data['tanggal_dokumen'] ?? null,
            'ringkasan'       => $data['ringkasan'] ?? null,
            'keterangan'      => $data['keterangan'] ?? null,
            'status'          => $data['status'] ?? 'aktif',
            'petugas_ptk_id'  => $context['ptk_id'],
            'petugas_nama'    => $context['nama'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return $this->respond($request, true, 'Dokumen administrasi berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_administrasi', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Dokumen tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);

        DB::table('laboran_administrasi')->where('id', $id)->update([
            'nomor_urut'      => $data['nomor_urut'] ?? null,
            'tanggal'         => $data['tanggal'],
            'tahun_ajaran'    => $data['tahun_ajaran'] ?? null,
            'semester'        => $data['semester'] ?? null,
            'jenis_dokumen'   => $data['jenis_dokumen'],
            'nomor_dokumen'   => $data['nomor_dokumen'] ?? null,
            'perihal'         => trim($data['perihal']),
            'asal_tujuan'     => $data['asal_tujuan'] ?? null,
            'tanggal_dokumen' => $data['tanggal_dokumen'] ?? null,
            'ringkasan'       => $data['ringkasan'] ?? null,
            'keterangan'      => $data['keterangan'] ?? null,
            'status'          => $data['status'] ?? $row->status,
            'updated_at'      => now(),
        ]);

        return $this->respond($request, true, 'Dokumen administrasi berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_administrasi', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Dokumen tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_administrasi')->where('id', $id)->delete();

        return $this->respond($request, true, 'Dokumen administrasi berhasil dihapus.');
    }

    /**
     * Cetak Buku Administrasi Jurusan (bukti fisik supervisi).
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $dari   = (string) $request->query('dari', now()->startOfYear()->toDateString());
        $sampai = (string) $request->query('sampai', now()->endOfYear()->toDateString());
        $jenisFilter = (string) $request->query('jenis', '');

        $query = DB::table('laboran_administrasi')->whereBetween('tanggal', [$dari, $sampai]);
        LaboranScope::apply($query, 'jurusan_id', $context);
        if ($jenisFilter !== '') $query->where('jenis_dokumen', $jenisFilter);
        $items = $query->orderBy('tanggal')->orderBy('id')->get();

        $jurusan = null;
        if ($context['jurusan_id']) {
            $jurusan = DB::table('jurusan_meta')->where('jurusan_id', $context['jurusan_id'])->first();
        }

        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        $kepalaSekolah = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first();

        $kepalaLab = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', 'KEPALA_LAB')
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip', 'gtk.nuptk')
            ->first();

        $petugas = [
            'nama' => $context['nama'] ?: 'Petugas Laboran',
            'nip'  => DB::table('gtk')->where('ptk_id', $context['ptk_id'])->value('nip') ?: '—',
        ];

        $orientasi = strtolower((string) $request->query('orientasi', 'landscape'));
        if (!in_array($orientasi, ['portrait', 'landscape'], true)) {
            $orientasi = 'landscape';
        }

        $range = [
            'start' => $dari,
            'end'   => $sampai,
            'label' => Carbon::parse($dari)->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($sampai)->translatedFormat('d F Y'),
        ];

        return view('dashboard.laboran.administrasi-cetak', compact(
            'items', 'jurusan', 'sekolah', 'sekolahMeta', 'kepalaSekolah',
            'kepalaLab', 'petugas', 'orientasi', 'range'
        ) + [
            'jenisList'  => self::JENIS_DOKUMEN,
            'statusList' => self::STATUS,
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal'         => 'required|date',
            'nomor_urut'      => 'nullable|string|max:30',
            'tahun_ajaran'    => 'nullable|string|max:20',
            'semester'        => 'nullable|string|max:10',
            'jenis_dokumen'   => 'required|in:surat_masuk,surat_keluar,sk,notulen_rapat,daftar_hadir,inventaris_ruang,program_kerja,laporan,berita_acara,lainnya',
            'nomor_dokumen'   => 'nullable|string|max:100',
            'perihal'         => 'required|string|max:255',
            'asal_tujuan'     => 'nullable|string|max:200',
            'tanggal_dokumen' => 'nullable|date',
            'ringkasan'       => 'nullable|string|max:2000',
            'keterangan'      => 'nullable|string|max:1000',
            'status'          => 'nullable|in:aktif,arsip,dibatalkan',
            'jurusan_id'      => 'nullable|string|max:50',
        ], [
            'tanggal.required'        => 'Tanggal pencatatan wajib diisi.',
            'perihal.required'        => 'Perihal dokumen wajib diisi.',
            'jenis_dokumen.required'  => 'Jenis dokumen wajib dipilih.',
        ]);
    }
}
