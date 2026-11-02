<?php

namespace App\Http\Controllers\Teknisi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Indikator Tupoksi #4 — Laporan Sarana Kegiatan Rapat.
 *
 * Bukti fisik: dokumen rekapitulasi dan laporan kelengkapan sarana rapat.
 */
class TeknisiSaranaRapatController extends TeknisiBaseController
{
    public const KELENGKAPAN = [
        'lengkap'       => 'Lengkap',
        'kurang'        => 'Kurang',
        'tidak_lengkap' => 'Tidak Lengkap',
    ];

    public const STATUS = [
        'draf'          => 'Draf',
        'selesai'       => 'Selesai',
        'terverifikasi' => 'Terverifikasi',
    ];

    /** Tahap persiapan kebersihan ruang rapat. */
    public const TAHAP = [
        'sebelum_acara' => 'Sebelum Acara',
        'saat_acara'    => 'Saat Acara',
        'sesudah_acara' => 'Sesudah Acara',
    ];

    /** Kondisi ruang rapat saat persiapan. */
    public const KONDISI = [
        'sangat_bersih'  => 'Sangat Bersih',
        'bersih'         => 'Bersih',
        'cukup'          => 'Cukup Bersih',
        'kotor'          => 'Perlu Dibersihkan',
        'perlu_tindakan' => 'Perlu Tindakan',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search   = trim((string) $request->query('q', ''));
        $lengkapFilter = trim((string) $request->query('kelengkapan', ''));
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('teknisi_sarana_rapat as s')->select('s.*');
        $this->scopeOwned($query, $context, 's');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('s.nama_rapat', 'like', "%{$search}%")
                  ->orWhere('s.ruang_nama', 'like', "%{$search}%")
                  ->orWhere('s.catatan', 'like', "%{$search}%");
            });
        }
        if ($lengkapFilter !== '') $query->where('s.kelengkapan_sarana', $lengkapFilter);
        if ($dari !== '')   $query->where('s.tanggal_rapat', '>=', $dari);
        if ($sampai !== '') $query->where('s.tanggal_rapat', '<=', $sampai);

        $statBase = DB::table('teknisi_sarana_rapat');
        $this->scopeOwned($statBase, $context);
        if ($dari !== '')   $statBase->where('tanggal_rapat', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal_rapat', '<=', $sampai);
        $statTotal   = (clone $statBase)->count();
        $statLengkap = (clone $statBase)->where('kelengkapan_sarana', 'lengkap')->count();
        $statKurang  = (clone $statBase)->whereIn('kelengkapan_sarana', ['kurang', 'tidak_lengkap'])->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('s.tanggal_rapat')->orderByDesc('s.id')
            ->paginate($perPage)->withQueryString();

        $sources = \App\Support\TeknisiModuleRegistry::sources();

        return view('dashboard.teknisi.sarana-rapat', array_merge(
            compact('list', 'search', 'lengkapFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statLengkap', 'statKurang'),
            $this->abilities(),
            [
                'kelengkapanList' => self::KELENGKAPAN,
                'statusList'      => self::STATUS,
                'tahapList'       => self::TAHAP,
                'kondisiList'     => self::KONDISI,
                'lokasiOptions'   => $this->lokasiOptions(),
                // Ruang diambil dari master Ruang & Gedung; sarana dari
                // inventaris aset (teknisi cukup menceklist kesiapan).
                'ruangOptions'    => $sources['ruang'] ?? [],
                'asetOptions'     => $sources['aset'] ?? [],
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);
        $ruangId = $data['ruang_id'] ?? null;
        $ruangNama = $ruangId ? DB::table('sarpras_ruang')->where('id', $ruangId)->value('nama_ruang') : null;
        $pj = $this->pjUntukRuang($ruangId, $context);

        $id = DB::table('teknisi_sarana_rapat')->insertGetId([
            'nomor_laporan'      => $this->generateNomor('teknisi_sarana_rapat', 'nomor_laporan', 'SRP'),
            'ruang_id'           => $ruangId,
            'tanggal_rapat'      => $data['tanggal_rapat'],
            'nama_rapat'         => trim($data['nama_rapat']),
            'ruang_nama'         => $ruangNama,
            'tahap'              => $data['tahap'] ?? 'sebelum_acara',
            'kondisi_ruang'      => $data['kondisi_ruang'] ?? 'bersih',
            'jumlah_peserta'     => $data['jumlah_peserta'] ?? null,
            'daftar_sarana'      => $this->daftarSaranaJson($data['siap'] ?? []),
            'kelengkapan_sarana' => $data['kelengkapan_sarana'] ?? 'lengkap',
            'catatan_kebutuhan'  => $data['catatan_kebutuhan'] ?? null,
            'catatan'            => $data['catatan'] ?? null,
            'file_path'          => $data['file_path'] ?? null,
            'petugas_ptk_id'     => $pj['ptk_id'],
            'petugas_nama'       => $pj['nama'],
            'user_id'            => $context['user_id'],
            'status'             => $data['status'] ?? 'selesai',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->syncPelaksanaan((int) $id, $data, $ruangId, $ruangNama, $pj, $context);

        return $this->respond($request, true, 'Ceklist persiapan rapat berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_sarana_rapat', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data milik Anda.', 403);
        }

        $data = $this->validated($request);
        $ruangId = $data['ruang_id'] ?? null;
        $ruangNama = $ruangId ? DB::table('sarpras_ruang')->where('id', $ruangId)->value('nama_ruang') : null;
        $pj = $this->pjUntukRuang($ruangId, $context);

        DB::table('teknisi_sarana_rapat')->where('id', $id)->update([
            'ruang_id'           => $ruangId,
            'tanggal_rapat'      => $data['tanggal_rapat'],
            'nama_rapat'         => trim($data['nama_rapat']),
            'ruang_nama'         => $ruangNama,
            'tahap'              => $data['tahap'] ?? $row->tahap ?? 'sebelum_acara',
            'kondisi_ruang'      => $data['kondisi_ruang'] ?? $row->kondisi_ruang ?? 'bersih',
            'jumlah_peserta'     => $data['jumlah_peserta'] ?? null,
            'daftar_sarana'      => $this->daftarSaranaJson($data['siap'] ?? [], $row->daftar_sarana),
            'kelengkapan_sarana' => $data['kelengkapan_sarana'] ?? $row->kelengkapan_sarana,
            'catatan_kebutuhan'  => $data['catatan_kebutuhan'] ?? null,
            'catatan'            => $data['catatan'] ?? null,
            'file_path'          => $data['file_path'] ?? $row->file_path,
            'status'             => $data['status'] ?? $row->status,
            'petugas_ptk_id'     => $pj['ptk_id'],
            'petugas_nama'       => $pj['nama'],
            'updated_at'         => now(),
        ]);

        $this->syncPelaksanaan((int) $id, $data, $ruangId, $ruangNama, $pj, $context);

        return $this->respond($request, true, 'Ceklist persiapan rapat berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_sarana_rapat', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data milik Anda.', 403);
        }

        DB::table('teknisi_sarana_rapat')->where('id', $id)->delete();
        DB::table('teknisi_pelaksanaan_harian')->where('sarana_rapat_id', $id)->delete();

        return $this->respond($request, true, 'Ceklist persiapan rapat berhasil dihapus.');
    }

    /**
     * Bangun JSON ceklist kesiapan dari pilihan `siap[]` (format "jenis:id").
     *
     * Nama sarana disimpan agar rekap/cetak tidak perlu join ulang.
     */
    protected function daftarSaranaJson(array $siap, ?string $existing = null): ?string
    {
        $rows = [];
        foreach ($siap as $val) {
            $val = trim((string) $val);
            if ($val === '' || !str_contains($val, ':')) {
                continue;
            }
            [$jenis, $refId] = explode(':', $val, 2);
            $nama = null;
            if ($jenis === 'ruang') {
                $nama = DB::table('sarpras_ruang')->where('id', $refId)->value('nama_ruang');
            } elseif ($jenis === 'aset') {
                $nama = DB::table('sarpras_aset')->where('id', $refId)->value('nama_barang');
            }
            if ($nama === null) {
                continue;
            }
            $rows[] = ['jenis' => $jenis, 'ref_id' => (int) $refId, 'nama' => $nama, 'jumlah' => 1, 'kondisi' => 'siap'];
        }

        if (empty($rows)) {
            return $existing;
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Catat ceklist persiapan rapat pada Jurnal Pelaksanaan Tugas.
     *
     * Satu baris pelaksanaan per laporan (idempoten via `sarana_rapat_id`),
     * sehingga persiapan rapat tampil & ikut cakupan PJ ruang di jurnal.
     */
    protected function syncPelaksanaan(int $saranaRapatId, array $data, ?int $ruangId, ?string $ruangNama, array $pj, array $context): void
    {
        if (!\App\Support\Table::exists('teknisi_pelaksanaan_harian')
            || !\Illuminate\Support\Facades\Schema::hasColumn('teknisi_pelaksanaan_harian', 'sarana_rapat_id')) {
            return;
        }

        $row = DB::table('teknisi_pelaksanaan_harian')->where('sarana_rapat_id', $saranaRapatId)->first();

        $payload = [
            'ruang_id'       => $ruangId,
            'tanggal'        => $data['tanggal_rapat'],
            'shift'          => 'pagi',
            'area'           => 'Rapat: ' . trim((string) $data['nama_rapat']) . ($ruangNama ? ' — ' . $ruangNama : ''),
            'periode_jadwal' => 'insidental',
            'petugas_ptk_id' => $pj['ptk_id'],
            'petugas_nama'   => $pj['nama'],
            'updated_at'     => now(),
        ];

        if ($row) {
            DB::table('teknisi_pelaksanaan_harian')->where('id', $row->id)->update($payload);

            return;
        }

        DB::table('teknisi_pelaksanaan_harian')->insert($payload + [
            'nomor_pelaksanaan' => $this->generateNomor('teknisi_pelaksanaan_harian', 'nomor_pelaksanaan', 'PLK'),
            'sarana_rapat_id'   => $saranaRapatId,
            'status'            => 'belum',
            'user_id'           => $context['user_id'],
            'created_at'        => now(),
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_rapat'      => 'required|date',
            'nama_rapat'         => 'required|string|max:200',
            'ruang_id'           => 'nullable|integer',
            'tahap'              => 'nullable|in:sebelum_acara,saat_acara,sesudah_acara',
            'kondisi_ruang'      => 'nullable|in:sangat_bersih,bersih,cukup,kotor,perlu_tindakan',
            'jumlah_peserta'     => 'nullable|integer|min:0|max:5000',
            'siap'               => 'nullable|array',
            'siap.*'             => 'nullable|string|max:60',
            'kelengkapan_sarana' => 'nullable|in:lengkap,kurang,tidak_lengkap',
            'catatan_kebutuhan'  => 'nullable|string|max:2000',
            'catatan'            => 'nullable|string|max:1000',
            'file_path'          => 'nullable|string|max:255',
            'status'             => 'nullable|in:draf,selesai,terverifikasi',
        ], [
            'tanggal_rapat.required' => 'Tanggal rapat wajib diisi.',
            'nama_rapat.required'    => 'Nama rapat wajib diisi.',
        ]);
    }
}
