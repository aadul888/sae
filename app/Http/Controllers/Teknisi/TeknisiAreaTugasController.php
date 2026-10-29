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

        // Data jadwal area tugas hanya milik akun yang login (admin/koordinator
        // melihat semua). Tidak lagi diturunkan dari PJ ruang Sarpras.
        $query = DB::table('teknisi_area_tugas as a')->select('a.*');
        $this->scopeOwned($query, $context, 'a');

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
        $this->scopeOwned($statBase, $context);
        $statTotal    = (clone $statBase)->count();
        $statAktif    = (clone $statBase)->where('status', 'aktif')->count();
        $statSampah   = (clone $statBase)->where('jenis_tugas', 'pengolahan_sampah')->count();
        $statLaporan  = (clone $statBase)->where('jenis_tugas', 'laporan_siswa')->count();

        $list = $query->orderByDesc('a.tanggal_mulai')->orderByDesc('a.id')
            ->paginate($perPage)->withQueryString();

        // Kolom Petugas = penanggung jawab ruang (live dari master Sarpras),
        // agar pergantian PJ langsung terlihat. Area bebas tanpa ruang tetap
        // memakai nama petugas tersimpan.
        $pjRuang = $this->pjRuang($list->pluck('ruang_id')->all());

        return view('dashboard.teknisi.area-tugas', array_merge(
            compact('list', 'search', 'jenisFilter', 'perPage', 'pjRuang',
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
        $petugas = $this->petugasUntukArea($data['area'], $context['ptk_id'], $context['nama']);

        DB::table('teknisi_area_tugas')->insert([
            'ruang_id'       => $this->ruangIdDariNama($data['area']),
            'nomor_jadwal'   => $this->generateNomor('teknisi_area_tugas', 'nomor_jadwal', 'AT'),
            'user_id'        => $context['user_id'],
            'area'           => trim($data['area']),
            'zona'           => $data['zona'] ?? null,
            'periode_jadwal' => $data['periode_jadwal'] ?? 'harian',
            'petugas_ptk_id' => $petugas['ptk_id'],
            'petugas_nama'   => $petugas['nama'],
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
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data milik sendiri.', 403);
        }

        $data = $this->validated($request);
        $petugas = $this->petugasUntukArea($data['area'], $context['ptk_id'], $context['nama']);

        DB::table('teknisi_area_tugas')->where('id', $id)->update([
            'ruang_id'       => $this->ruangIdDariNama($data['area']),
            'area'           => trim($data['area']),
            'zona'           => $data['zona'] ?? null,
            'periode_jadwal' => $data['periode_jadwal'] ?? $row->periode_jadwal,
            'petugas_ptk_id' => $petugas['ptk_id'],
            'petugas_nama'   => $petugas['nama'],
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

        $row = $this->findRow('teknisi_area_tugas', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data milik sendiri.', 403);
        }

        DB::table('teknisi_area_tugas')->where('id', $id)->delete();

        return $this->respond($request, true, 'Jadwal area tugas berhasil dihapus.');
    }

    /** Id ruang dari nama area (null bila bukan nama ruang master). */
    private function ruangIdDariNama(?string $nama): ?int
    {
        $nama = trim((string) $nama);
        if ($nama === '' || !\App\Support\Table::exists('sarpras_ruang')) {
            return null;
        }

        $id = DB::table('sarpras_ruang')->where('nama_ruang', $nama)->value('id');

        return $id ? (int) $id : null;
    }

    /**
     * Petugas jadwal = penanggung jawab ruang master (bila area = ruang).
     *
     * Area bebas (bukan ruang Sarpras) memakai akun yang login sebagai
     * petugas. Pemilik baris (`user_id`) tetap akun login — lihat store().
     *
     * @return array{ptk_id: ?string, nama: ?string}
     */
    private function petugasUntukArea(?string $nama, ?string $fallbackPtk, ?string $fallbackNama): array
    {
        $ruangId = $this->ruangIdDariNama($nama);
        if (!$ruangId) {
            return ['ptk_id' => $fallbackPtk, 'nama' => $fallbackNama];
        }

        $pj = $this->pjRuang([$ruangId]);

        return [
            'ptk_id' => DB::table('sarpras_ruang')->where('id', $ruangId)->value('penanggung_jawab_ptk_id') ?: $fallbackPtk,
            'nama'   => $pj[$ruangId] ?? $fallbackNama,
        ];
    }

    /**
     * Peta `sarpras_ruang.id` => nama penanggung jawab (terbaru).
     *
     * @param  array<int, int|string|null>  $ruangIds
     * @return array<int, string>
     */
    private function pjRuang(array $ruangIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $ruangIds)));
        if (empty($ids) || !\App\Support\Table::exists('sarpras_ruang')) {
            return [];
        }

        return DB::table('sarpras_ruang as r')
            ->leftJoin('gtk as g', 'r.penanggung_jawab_ptk_id', '=', 'g.ptk_id')
            ->leftJoin('pengguna as p', 'r.penanggung_jawab_ptk_id', '=', 'p.pengguna_id')
            ->whereIn('r.id', $ids)
            ->get(['r.id', DB::raw('COALESCE(g.nama, p.nama) as pj_nama')])
            ->filter(fn ($row) => filled($row->pj_nama))
            ->mapWithKeys(fn ($row) => [(int) $row->id => (string) $row->pj_nama])
            ->all();
    }

    protected function validated(Request $request): array
    {        return $request->validate([
            'area'            => 'required|string|max:150',
            'zona'            => 'nullable|string|max:80',
            'periode_jadwal'  => 'nullable|in:harian,mingguan,bulanan,insidental',
            'petugas_ptk_id'  => 'nullable|string|max:50',
            'petugas_nama'    => 'nullable|string|max:150',
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
        ]);
    }
}
