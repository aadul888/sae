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

        return view('dashboard.teknisi.sarana-rapat', array_merge(
            compact('list', 'search', 'lengkapFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statLengkap', 'statKurang'),
            $this->abilities(),
            [
                'kelengkapanList' => self::KELENGKAPAN,
                'statusList'      => self::STATUS,
                'lokasiOptions'   => $this->lokasiOptions(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        DB::table('teknisi_sarana_rapat')->insert([
            'nomor_laporan'      => $this->generateNomor('teknisi_sarana_rapat', 'nomor_laporan', 'SRP'),
            'tanggal_rapat'      => $data['tanggal_rapat'],
            'nama_rapat'         => trim($data['nama_rapat']),
            'ruang_nama'         => $data['ruang_nama'] ?? null,
            'jumlah_peserta'     => $data['jumlah_peserta'] ?? null,
            'daftar_sarana'      => !empty($data['daftar_sarana'])
                ? json_encode(array_values($data['daftar_sarana']))
                : $this->parseDaftarSarana($data['daftar_sarana_text'] ?? null),
            'kelengkapan_sarana' => $data['kelengkapan_sarana'] ?? 'lengkap',
            'catatan_kebutuhan'  => $data['catatan_kebutuhan'] ?? null,
            'catatan'            => $data['catatan'] ?? null,
            'file_path'          => $data['file_path'] ?? null,
            'petugas_ptk_id'     => $context['ptk_id'],
            'petugas_nama'       => $context['nama'],
            'user_id'            => $context['user_id'],
            'status'             => $data['status'] ?? 'selesai',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Laporan sarana rapat berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_sarana_rapat', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah data milik sendiri.', 403);
        }

        $data = $this->validated($request);

        DB::table('teknisi_sarana_rapat')->where('id', $id)->update([
            'tanggal_rapat'      => $data['tanggal_rapat'],
            'nama_rapat'         => trim($data['nama_rapat']),
            'ruang_nama'         => $data['ruang_nama'] ?? null,
            'jumlah_peserta'     => $data['jumlah_peserta'] ?? null,
            'daftar_sarana'      => !empty($data['daftar_sarana'])
                ? json_encode(array_values($data['daftar_sarana']))
                : ($this->parseDaftarSarana($data['daftar_sarana_text'] ?? null) ?? $row->daftar_sarana),
            'kelengkapan_sarana' => $data['kelengkapan_sarana'] ?? $row->kelengkapan_sarana,
            'catatan_kebutuhan'  => $data['catatan_kebutuhan'] ?? null,
            'catatan'            => $data['catatan'] ?? null,
            'file_path'          => $data['file_path'] ?? $row->file_path,
            'status'             => $data['status'] ?? $row->status,
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Laporan sarana rapat berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_sarana_rapat', $id);
        if (!$row) return $this->respond($request, false, 'Data tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus data milik sendiri.', 403);
        }

        DB::table('teknisi_sarana_rapat')->where('id', $id)->delete();

        return $this->respond($request, true, 'Laporan sarana rapat berhasil dihapus.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_rapat'      => 'required|date',
            'nama_rapat'         => 'required|string|max:200',
            'ruang_nama'         => 'nullable|string|max:150',
            'jumlah_peserta'     => 'nullable|integer|min:0|max:5000',
            'daftar_sarana'      => 'nullable|array',
            'daftar_sarana.*.nama'    => 'nullable|string|max:150',
            'daftar_sarana.*.jumlah'  => 'nullable|integer|min:0',
            'daftar_sarana.*.kondisi' => 'nullable|string|max:50',
            'daftar_sarana_text' => 'nullable|string|max:4000',
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

    /**
     * Konversi teks bebas "Nama | Jumlah | Kondisi" (satu per baris) menjadi
     * JSON daftar sarana. Kosong -> null.
     */
    protected function parseDaftarSarana(?string $text): ?string
    {
        if (empty(trim((string) $text))) {
            return null;
        }

        $rows = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $parts = array_map('trim', explode('|', $line));
            $rows[] = [
                'nama'    => $parts[0] ?? '',
                'jumlah'  => isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : 0,
                'kondisi' => $parts[2] ?? '',
            ];
        }

        return empty($rows) ? null : json_encode($rows, JSON_UNESCAPED_UNICODE);
    }
}
