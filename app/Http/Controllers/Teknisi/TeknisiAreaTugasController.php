<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Indikator Tupoksi #1 — Administrasi & Jadwal Pembagian Area Tugas Kebersihan.
 *
 * Bukti fisik:
 *  - Jadwal area tugas kebersihan dan pengolahan sampah + dokumentasi kegiatan.
 *  - Penerimaan laporan siswa tidak disiplin dari Tim Keamanan.
 */
class TeknisiAreaTugasController extends TeknisiBaseController
{
    public const JENIS = [
        'tukang_kebersihan' => 'Tukang Kebersihan',
        'pengolahan_sampah' => 'Pengolahan Sampah',
        'laporan_siswa'     => 'Laporan Siswa Tidak Disiplin',
        'lainnya'           => 'Lainnya',
    ];

    public const PERIODE = [
        'harian'     => 'Harian',
        'mingguan'   => 'Mingguan',
        'bulanan'    => 'Bulanan',
        'insidental' => 'Insidental',
    ];

    public const SHIFT = [
        'pagi'  => 'Pagi',
        'siang' => 'Siang',
        'sore'  => 'Sore',
    ];

    public const STATUS = [
        'draf'    => 'Draf',
        'aktif'   => 'Aktif',
        'selesai' => 'Selesai',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search      = trim((string) $request->query('q', ''));
        $jenisFilter = trim((string) $request->query('jenis', ''));
        $perPage     = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage     = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $query = DB::table('teknisi_area_tugas as a')->select('a.*');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('a.area', 'like', "%{$search}%")
                  ->orWhere('a.petugas_nama', 'like', "%{$search}%")
                  ->orWhere('a.uraian', 'like', "%{$search}%")
                  ->orWhere('a.laporan_siswa', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '') $query->where('a.jenis_tugas', $jenisFilter);

        $statBase = DB::table('teknisi_area_tugas');
        $statTotal    = (clone $statBase)->count();
        $statAktif    = (clone $statBase)->where('status', 'aktif')->count();
        $statSampah   = (clone $statBase)->where('jenis_tugas', 'pengolahan_sampah')->count();
        $statLaporan  = (clone $statBase)->where('jenis_tugas', 'laporan_siswa')->count();

        $list = $query->orderByDesc('a.tanggal_mulai')->orderByDesc('a.id')
            ->paginate($perPage)->withQueryString();

        return view('dashboard.teknisi.area-tugas', array_merge(
            compact('list', 'search', 'jenisFilter', 'perPage',
                'statTotal', 'statAktif', 'statSampah', 'statLaporan'),
            $this->abilities(),
            [
                'jenisList'  => self::JENIS,
                'periodeList' => self::PERIODE,
                'shiftList'  => self::SHIFT,
                'statusList' => self::STATUS,
                'lokasiOptions' => $this->lokasiOptions(),
                'teknisiOptions' => $this->teknisiOptions(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('teknisi_area_tugas')->insert([
            'nomor_jadwal'   => $this->generateNomor('teknisi_area_tugas', 'nomor_jadwal', 'AT'),
            'area'           => trim($data['area']),
            'zona'           => $data['zona'] ?? null,
            'periode_jadwal' => $data['periode_jadwal'] ?? 'harian',
            'petugas_ptk_id' => $data['petugas_ptk_id'] ?? null,
            'petugas_nama'   => $data['petugas_nama'],
            'shift'          => $data['shift'] ?? 'pagi',
            'tanggal_mulai'  => $data['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            'jenis_tugas'    => $data['jenis_tugas'] ?? 'tukang_kebersihan',
            'laporan_siswa'  => $data['laporan_siswa'] ?? null,
            'sumber_laporan' => $data['sumber_laporan'] ?? null,
            'uraian'         => $data['uraian'] ?? null,
            'catatan'        => $data['catatan'] ?? null,
            'status'         => $data['status'] ?? 'aktif',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->respond($request, true, 'Jadwal area tugas berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_area_tugas', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);

        $data = $this->validated($request);

        DB::table('teknisi_area_tugas')->where('id', $id)->update([
            'area'           => trim($data['area']),
            'zona'           => $data['zona'] ?? null,
            'periode_jadwal' => $data['periode_jadwal'] ?? $row->periode_jadwal,
            'petugas_ptk_id' => $data['petugas_ptk_id'] ?? null,
            'petugas_nama'   => $data['petugas_nama'],
            'shift'          => $data['shift'] ?? $row->shift,
            'tanggal_mulai'  => $data['tanggal_mulai'] ?? null,
            'tanggal_selesai' => $data['tanggal_selesai'] ?? null,
            'jenis_tugas'    => $data['jenis_tugas'] ?? $row->jenis_tugas,
            'laporan_siswa'  => $data['laporan_siswa'] ?? null,
            'sumber_laporan' => $data['sumber_laporan'] ?? null,
            'uraian'         => $data['uraian'] ?? null,
            'catatan'        => $data['catatan'] ?? null,
            'status'         => $data['status'] ?? $row->status,
            'updated_at'     => now(),
        ]);

        return $this->respond($request, true, 'Jadwal area tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        DB::table('teknisi_area_tugas')->where('id', $id)->delete();

        return $this->respond($request, true, 'Jadwal area tugas berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'area'            => 'required|string|max:150',
            'zona'            => 'nullable|string|max:80',
            'periode_jadwal'  => 'nullable|in:harian,mingguan,bulanan,insidental',
            'petugas_ptk_id'  => 'nullable|string|max:50',
            'petugas_nama'    => 'required|string|max:150',
            'shift'           => 'nullable|in:pagi,siang,sore',
            'tanggal_mulai'   => 'nullable|date',
            'tanggal_selesai' => 'nullable|date',
            'jenis_tugas'     => 'nullable|in:tukang_kebersihan,pengolahan_sampah,laporan_siswa,lainnya',
            'laporan_siswa'   => 'nullable|string|max:2000',
            'sumber_laporan'  => 'nullable|string|max:100',
            'uraian'          => 'nullable|string|max:2000',
            'catatan'         => 'nullable|string|max:1000',
            'status'          => 'nullable|in:draf,aktif,selesai',
        ], [
            'area.required'         => 'Area tugas wajib diisi.',
            'petugas_nama.required' => 'Nama petugas wajib diisi.',
        ]);
    }
}
