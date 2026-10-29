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

        // Segarkan cermin master Sarpras (nama, gedung, petugas/PJ) lebih dulu,
        // lalu turunkan jadwal hanya untuk ruang milik PJ yang login.
        \App\Support\TeknisiAreaMirror::sync();
        $this->syncAreaTugas($context);

        $query = DB::table('teknisi_area_tugas as a')->select('a.*');
        $this->scopeOwned($query, $context, 'a');
        // Filter live: hanya ruang yang PJ-nya akun login (master Sarpras).
        // Jadwal lama tanpa `ruang_id` tetap tampil bagi pemiliknya.
        $this->scopeRuangPj($query, $context, 'ruang_id', 'a', true);

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
        $this->scopeRuangPj($statBase, $context, 'ruang_id', null, true);
        $statTotal    = (clone $statBase)->count();
        $statAktif    = (clone $statBase)->where('status', 'aktif')->count();
        $statSampah   = (clone $statBase)->where('jenis_tugas', 'pengolahan_sampah')->count();
        $statLaporan  = (clone $statBase)->where('jenis_tugas', 'laporan_siswa')->count();

        $list = $query->orderByDesc('a.tanggal_mulai')->orderByDesc('a.id')
            ->paginate($perPage)->withQueryString();

        // Nama PJ ditampilkan dari master Sarpras (live), bukan nilai tersimpan
        // yang bisa basi setelah pergantian PJ.
        $pjRuang = $this->pjNamaPerRuang($list->pluck('ruang_id')->all());

        return view('dashboard.teknisi.area-tugas', array_merge(
            compact('list', 'search', 'jenisFilter', 'perPage', 'pjRuang',
                'statTotal', 'statAktif', 'statSampah', 'statLaporan'),
            $this->abilities(),
            [
                'jenisList'  => self::JENIS,
                'periodeList' => self::PERIODE,
                'shiftList'  => self::SHIFT,
                'statusList' => self::STATUS,
                'lokasiOptions' => $this->lokasiOptions($context),
                'teknisiOptions' => $this->teknisiOptions(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);
        $petugas = $this->petugasUntukArea($data['area'], $data['petugas_ptk_id'] ?? null, $data['petugas_nama']);

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
        $petugas = $this->petugasUntukArea($data['area'], $data['petugas_ptk_id'] ?? null, $data['petugas_nama']);

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

    /**
     * Turunkan jadwal area tugas dari seluruh area master.
     *
     * Sumbernya `teknisi_area_master`, dibatasi ruang yang PJ-nya akun login
     * (dibaca live dari `sarpras_ruang`), sehingga jadwal selalu mengikuti
     * penunjukan PJ terbaru. Idempoten lewat `ruang_id` + pemilik akun.
     */
    private function syncAreaTugas(array $context): void
    {
        // Pemilik jadwal = akun login. `ptk_id` opsional: pengguna yang diinput
        // manual (operator, staf non-PTK) tidak punya baris GTK.
        $userId = $context['user_id'] ?? null;
        $ptkId  = $context['ptk_id'] ?? null;
        if (!$userId || !\App\Support\Table::existsAll('teknisi_area_master', 'teknisi_area_tugas')) {
            return;
        }

        $ruangIds = $this->ruangMilikPj($context);
        // Koordinator (null) melihat seluruh jadwal apa adanya dan tidak
        // menurunkan jadwal baru atas namanya sendiri.
        if ($ruangIds === null || empty($ruangIds)) {
            return;
        }

        $sumber = DB::table('teknisi_area_master')
            ->whereIn('ruang_id', $ruangIds)
            ->orderBy('nama_area')
            ->get(['ruang_id', 'nama_area', 'zona', 'gedung', 'frekuensi', 'kategori', 'status']);

        if ($sumber->isEmpty()) {
            return;
        }

        // Idempoten lewat `ruang_id` + pemilik akun; baris lama tanpa `ruang_id`
        // (sebelum migrasi) dicocokkan lewat nama area.
        $ada = DB::table('teknisi_area_tugas')
            ->where('user_id', $userId)
            ->where(fn ($q) => $q
                ->whereIn('ruang_id', $sumber->pluck('ruang_id')->filter()->all())
                ->orWhereIn('area', $sumber->pluck('nama_area')->all()))
            ->get(['ruang_id', 'area']);

        $sudahRuang = $ada->pluck('ruang_id')->filter()->flip();
        $sudahNama  = $ada->pluck('area')->flip();

        $baru = [];
        $urut = $this->nomorTerakhir('teknisi_area_tugas', 'nomor_jadwal', 'AT');
        foreach ($sumber as $a) {
            if (isset($sudahRuang[$a->ruang_id]) || isset($sudahNama[$a->nama_area])) {
                continue;
            }
            $urut++;
            $baru[] = [
                'ruang_id'       => $a->ruang_id,
                'nomor_jadwal'   => sprintf('AT/%s/%04d', now()->format('Ym'), $urut),
                'user_id'        => $userId,
                'area'           => $a->nama_area,
                'zona'           => $a->zona ?: $a->gedung,
                'periode_jadwal' => $a->frekuensi ?: 'harian',
                'petugas_ptk_id' => $ptkId,
                'petugas_nama'   => $context['nama'] ?? null,
                'shift'          => 'pagi',
                'jenis_tugas'    => 'tukang_kebersihan',
                'uraian'         => 'Kebersihan ' . $a->nama_area
                    . ($a->gedung ? ' (' . $a->gedung . ')' : ''),
                'status'         => $a->status === 'aktif' ? 'aktif' : 'draf',
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        if (!empty($baru)) {
            DB::table('teknisi_area_tugas')->insert($baru);
        }
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
     * Petugas jadwal untuk sebuah area.
     *
     * Bila area adalah ruang master Sarpras, petugas mengikuti penanggung
     * jawab ruang tersebut (supaya tidak ada "PJ A tapi petugas B"). Area
     * bebas (non-ruang) tetap memakai isian form.
     *
     * @return array{ptk_id: ?string, nama: ?string}
     */
    private function petugasUntukArea(?string $nama, ?string $ptkId, ?string $namaPetugas): array
    {
        $ruangId = $this->ruangIdDariNama($nama);
        if (!$ruangId) {
            return ['ptk_id' => $ptkId, 'nama' => $namaPetugas];
        }

        $ptkPj = DB::table('sarpras_ruang')->where('id', $ruangId)->value('penanggung_jawab_ptk_id');
        $namaPj = $this->pjNamaPerRuang([$ruangId])[$ruangId] ?? null;

        return [
            'ptk_id' => $ptkPj ?: $ptkId,
            'nama'   => $namaPj ?: $namaPetugas,
        ];
    }

    /**
     * Nomor urut terakhir dokumen `PREFIX/YYYYMM/NNNN` bulan ini.
     */
    private function nomorTerakhir(string $table, string $column, string $prefix): int
    {
        $last = DB::table($table)
            ->where($column, 'like', "{$prefix}/" . now()->format('Ym') . '/%')
            ->orderByDesc('id')
            ->value($column);

        if (!$last) {
            return 0;
        }

        $parts = explode('/', (string) $last);

        return (int) end($parts);
    }

    protected function validated(Request $request): array
    {        return $request->validate([
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
