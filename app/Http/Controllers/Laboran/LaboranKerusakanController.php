<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Modul Pengelolaan Berita Acara Kerusakan Alat.
 *
 * Alur: lapor kerusakan -> berita acara -> verifikasi -> tindak lanjut
 * (perbaikan / usulan penggantian).
 */
class LaboranKerusakanController extends LaboranBaseController
{
    public const TINGKAT = [
        'ringan' => 'Rusak Ringan',
        'sedang' => 'Rusak Sedang',
        'berat'  => 'Rusak Berat',
        'total'  => 'Rusak Total',
    ];

    public const STATUS = [
        'draf'        => 'Draf',
        'diajukan'    => 'Diajukan',
        'diverifikasi' => 'Diverifikasi',
        'disetujui'   => 'Disetujui',
        'selesai'     => 'Selesai',
    ];

    public const PELAKU_TIPE = [
        'siswa'           => 'Peserta Didik',
        'guru'            => 'Guru',
        'tendik'          => 'Tenaga Kependidikan',
        'eksternal'       => 'Pihak Eksternal',
        'tidak_diketahui' => 'Tidak Diketahui',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search        = trim((string) $request->query('q', ''));
        $tingkatFilter = trim((string) $request->query('tingkat', ''));
        $statusFilter  = trim((string) $request->query('status', ''));

        $query = DB::table('laboran_berita_acara_kerusakan as ba')
            ->leftJoin('jurusan_meta as jm', 'ba.jurusan_id', '=', 'jm.jurusan_id')
            ->select('ba.*', 'jm.nama_jurusan', 'jm.singkatan')
            ->selectSub(function ($q) {
                $q->from('laboran_berita_acara_kerusakan_item as ti')
                    ->whereColumn('ti.kerusakan_id', 'ba.id')
                    ->selectRaw('COUNT(*)');
            }, 'jumlah_alat')
            ->selectSub(function ($q) {
                $q->from('laboran_berita_acara_kerusakan_item as ti')
                    ->whereColumn('ti.kerusakan_id', 'ba.id')
                    ->orderBy('ti.urutan')
                    ->orderBy('ti.id')
                    ->limit(1)
                    ->select('ti.nama_alat');
            }, 'nama_alat_multi');

        LaboranScope::apply($query, 'ba.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ba.nomor_ba', 'like', "%{$search}%")
                  ->orWhere('ba.nama_alat', 'like', "%{$search}%")
                  ->orWhere('ba.kronologi', 'like', "%{$search}%")
                  ->orWhere('ba.pelaku_nama', 'like', "%{$search}%")
                  ->orWhereExists(function ($sub) use ($search) {
                      $sub->select(DB::raw(1))
                          ->from('laboran_berita_acara_kerusakan_item as si')
                          ->whereColumn('si.kerusakan_id', 'ba.id')
                          ->where('si.nama_alat', 'like', "%{$search}%");
                  });
            });
        }
        if ($tingkatFilter !== '') $query->where('ba.tingkat_kerusakan', $tingkatFilter);
        if ($statusFilter !== '')  $query->where('ba.status', $statusFilter);

        $statBase = DB::table('laboran_berita_acara_kerusakan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal   = (clone $statBase)->count();
        $statDiajukan = (clone $statBase)->whereIn('status', ['diajukan', 'diverifikasi'])->count();
        $statBerat   = (clone $statBase)->whereIn('tingkat_kerusakan', ['berat', 'total'])->count();
        $statBiaya   = (clone $statBase)->sum('estimasi_biaya');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE ba.status
                WHEN 'diajukan' THEN 1
                WHEN 'diverifikasi' THEN 2
                WHEN 'disetujui' THEN 3
                WHEN 'draf' THEN 4
                ELSE 5 END ASC")
            ->orderByDesc('ba.tanggal_kejadian')
            ->orderByDesc('ba.id')
            ->paginate($perPage)
            ->withQueryString();

        $this->lampirkanRincian($list->getCollection());

        return view('dashboard.laboran.kerusakan', array_merge(
            compact('list', 'search', 'tingkatFilter', 'statusFilter', 'perPage',
                'statTotal', 'statDiajukan', 'statBerat', 'statBiaya'),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'itemOptions'    => $this->itemOptions($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'tingkatList'    => self::TINGKAT,
                'statusList'     => self::STATUS,
                'pelakuList'     => self::PELAKU_TIPE,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);

        $alat = $this->normalisasiAlat($data, $context);

        $item = $alat[0]['item'];

        $id = DB::table('laboran_berita_acara_kerusakan')->insertGetId([
            'jurusan_id'               => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'           => ($data['ruang_lab_nama'] ?? null) ?: ($item->ruang_lab_nama ?? null),
            'nomor_ba'                 => $this->generateNomor('laboran_berita_acara_kerusakan', 'nomor_ba', 'BAK'),
            'tanggal_kejadian'         => $data['tanggal_kejadian'],
            'tanggal_ba'               => $data['tanggal_ba'] ?? now()->toDateString(),
            'item_id'                  => $item->id ?? null,
            'nama_alat'                => $alat[0]['nama_alat'],
            'kode_alat'                => $alat[0]['kode_alat'],
            'jumlah_rusak'             => $alat[0]['jumlah_rusak'],
            'satuan'                   => $alat[0]['satuan'],
            'tingkat_kerusakan'        => $data['tingkat_kerusakan'],
            'kronologi'                => $data['kronologi'] ?? null,
            'penyebab'                 => $data['penyebab'] ?? null,
            'pelaku_tipe'              => $data['pelaku_tipe'] ?? 'tidak_diketahui',
            'pelaku_id'                => $data['pelaku_id'] ?? null,
            'pelaku_nama'              => $data['pelaku_nama'] ?? null,
            'saksi_1_nama'             => $data['saksi_1_nama'] ?? null,
            'saksi_2_nama'             => $data['saksi_2_nama'] ?? null,
            'tindakan_lanjut'          => $data['tindakan_lanjut'] ?? null,
            'estimasi_biaya'           => $data['estimasi_biaya'] ?? null,
            'perlu_usulan_penggantian' => (bool) ($data['perlu_usulan_penggantian'] ?? false),
            'status'                   => $data['status'] ?? 'draf',
            'pelapor_ptk_id'           => $context['ptk_id'],
            'pelapor_nama'             => $context['nama'],
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);

        $this->simpanAlat($id, $alat, $data['tingkat_kerusakan']);

        // Sinkronkan kondisi inventaris sesuai tingkat kerusakan
        $this->sinkronKondisi($alat, $data['tingkat_kerusakan']);

        return $this->respond($request, true, 'Berita acara kerusakan berhasil dibuat.', 200, [
            'redirect' => route('dashboard.laboran.kerusakan.index'),
            'id'       => $id,
        ]);
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);

        $alat = $this->normalisasiAlat($data, $context);
        $item = $alat[0]['item'];

        $update = [
            'ruang_lab_nama'           => ($data['ruang_lab_nama'] ?? null) ?: ($item->ruang_lab_nama ?? $row->ruang_lab_nama),
            'tanggal_kejadian'         => $data['tanggal_kejadian'],
            'tanggal_ba'               => $data['tanggal_ba'] ?? $row->tanggal_ba,
            'item_id'                  => $item->id ?? $row->item_id,
            'nama_alat'                => $alat[0]['nama_alat'],
            'kode_alat'                => $alat[0]['kode_alat'] ?? $row->kode_alat,
            'jumlah_rusak'             => $alat[0]['jumlah_rusak'],
            'satuan'                   => $alat[0]['satuan'],
            'tingkat_kerusakan'        => $data['tingkat_kerusakan'],
            'kronologi'                => $data['kronologi'] ?? null,
            'penyebab'                 => $data['penyebab'] ?? null,
            'pelaku_tipe'              => $data['pelaku_tipe'] ?? $row->pelaku_tipe,
            'pelaku_id'                => $data['pelaku_id'] ?? null,
            'pelaku_nama'              => $data['pelaku_nama'] ?? null,
            'saksi_1_nama'             => $data['saksi_1_nama'] ?? null,
            'saksi_2_nama'             => $data['saksi_2_nama'] ?? null,
            'tindakan_lanjut'          => $data['tindakan_lanjut'] ?? null,
            'estimasi_biaya'           => $data['estimasi_biaya'] ?? null,
            'perlu_usulan_penggantian' => (bool) ($data['perlu_usulan_penggantian'] ?? false),
            'status'                   => $data['status'] ?? $row->status,
            'updated_at'               => now(),
        ];

        if (in_array($update['status'], ['diverifikasi', 'disetujui', 'selesai'], true)
            && $update['status'] !== $row->status) {
            $update['verifikator_ptk_id'] = $context['ptk_id'];
            $update['tanggal_verifikasi'] = now();
            $update['catatan_verifikasi'] = $data['catatan_verifikasi'] ?? null;
        }

        DB::table('laboran_berita_acara_kerusakan')->where('id', $id)->update($update);

        DB::table('laboran_berita_acara_kerusakan_item')->where('kerusakan_id', $id)->delete();
        $this->simpanAlat($id, $alat, $data['tingkat_kerusakan']);
        $this->sinkronKondisi($alat, $data['tingkat_kerusakan']);

        return $this->respond($request, true, 'Berita acara kerusakan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_berita_acara_kerusakan_item')->where('kerusakan_id', $id)->delete();
        DB::table('laboran_berita_acara_kerusakan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Berita acara kerusakan berhasil dihapus.');
    }

    /**
     * Cetak Berita Acara Kerusakan (dokumen resmi A4).
     */
    public function cetak(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $ba = $this->findScoped('laboran_berita_acara_kerusakan', $id, $context);
        if (!$ba) {
            abort(404, 'Berita acara tidak ditemukan atau di luar cakupan jurusan Anda.');
        }

        $jurusan = $ba->jurusan_id
            ? DB::table('jurusan_meta')->where('jurusan_id', $ba->jurusan_id)->first()
            : null;

        $sekolah     = Sekolah::first();
        $sekolahMeta = SekolahMeta::first();

        // Pejabat penanda tangan terpusat (Kepala Sekolah, Kepala Lab, KAPROG).
        $pejabat       = $this->dokumenService()->pejabat($context, $ba->jurusan_id);
        $kepalaSekolah = $pejabat['kepalaSekolah'];
        $kepalaLab     = $pejabat['kepalaLab'];
        $petugas       = $pejabat['petugas'];

        $orientasi = $this->orientasiCetak($request, 'portrait');
        $mode      = $this->modeCetak($request, 'lengkap');

        $alat = $this->rincianAlat($ba);

        return view('dashboard.laboran.kerusakan-cetak', array_merge(
            compact(
                'ba', 'jurusan', 'sekolah', 'sekolahMeta',
                'kepalaSekolah', 'kepalaLab', 'petugas', 'orientasi', 'mode', 'alat'
            ),
            [
                'tingkatList' => self::TINGKAT,
                'statusList'  => self::STATUS,
                'pelakuList'  => self::PELAKU_TIPE,
            ],
            $this->dokumenCetak('kerusakan', $context, [
                'mode'           => $mode,
                'orientasi'      => $orientasi,
                'judul'          => 'Berita Acara Kerusakan Alat — ' . $ba->nomor_ba,
                'nomor_dokumen'  => $ba->nomor_ba,
                'periode_label'  => Carbon::parse($ba->tanggal_ba ?: $ba->tanggal_kejadian)->translatedFormat('d F Y'),
                'periode_dari'   => $ba->tanggal_ba ?: $ba->tanggal_kejadian,
                'sumber_tabel'   => 'laboran_berita_acara_kerusakan',
                'sumber_id'      => $ba->id,
                'jumlah_baris'   => max(1, $alat->count()),
                'jurusan_id'     => $ba->jurusan_id,
                'filter'         => ['nomor_ba' => $ba->nomor_ba],
            ])
        ));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'item_id'                  => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_alat'                => 'nullable|string|max:255',
            'kode_alat'                => 'nullable|string|max:50',
            'ruang_lab_nama'           => 'nullable|string|max:100',
            'tanggal_kejadian'         => 'required|date',
            'tanggal_ba'               => 'nullable|date',
            'jumlah_rusak'             => 'nullable|numeric|min:0.01',
            'satuan'                   => 'nullable|string|max:30',
            'tingkat_kerusakan'        => 'required|in:ringan,sedang,berat,total',
            'kronologi'                => 'nullable|string|max:3000',
            'penyebab'                 => 'nullable|string|max:2000',
            'pelaku_tipe'              => 'nullable|in:siswa,guru,tendik,eksternal,tidak_diketahui',
            'pelaku_id'                => 'nullable|string|max:50',
            'pelaku_nama'              => 'nullable|string|max:150',
            'saksi_1_nama'             => 'nullable|string|max:150',
            'saksi_2_nama'             => 'nullable|string|max:150',
            'tindakan_lanjut'          => 'nullable|string|max:2000',
            'estimasi_biaya'           => 'nullable|numeric|min:0',
            'perlu_usulan_penggantian' => 'nullable|boolean',
            'status'                   => 'nullable|in:draf,diajukan,diverifikasi,disetujui,selesai',
            'catatan_verifikasi'       => 'nullable|string|max:1000',
            'jurusan_id'               => 'nullable|string|max:50',

            // Rincian alat (boleh lebih dari satu). Bila kosong, form lama
            // (item_id + nama_alat) tetap didukung.
            'alat'                     => 'nullable|array|max:100',
            'alat.*.item_id'           => 'nullable|integer|exists:laboran_bahan_alat,id',
            'alat.*.nama_alat'         => 'nullable|string|max:255',
            'alat.*.kode_alat'         => 'nullable|string|max:50',
            'alat.*.ruang_lab_nama'    => 'nullable|string|max:100',
            'alat.*.jumlah_rusak'      => 'nullable|numeric|min:0.01',
            'alat.*.satuan'            => 'nullable|string|max:30',
            'alat.*.tingkat_kerusakan' => 'nullable|in:ringan,sedang,berat,total',
            'alat.*.keterangan'        => 'nullable|string|max:500',
        ], [
            'tanggal_kejadian.required'  => 'Tanggal kejadian wajib diisi.',
            'tingkat_kerusakan.required' => 'Tingkat kerusakan wajib dipilih.',
            'alat.required'              => 'Pilih minimal satu alat yang dilaporkan.',
            'alat.min'                   => 'Pilih minimal satu alat yang dilaporkan.',
        ]);

        // Minimal satu alat harus terisi (nama atau item inventaris).
        $adaAlat = collect($data['alat'] ?? [])->contains(function ($row) {
            return trim((string) ($row['nama_alat'] ?? '')) !== ''
                || !empty($row['item_id']);
        });
        if (!$adaAlat && trim((string) ($data['nama_alat'] ?? '')) === '' && empty($data['item_id'])) {
            throw ValidationException::withMessages([
                'alat' => 'Pilih minimal satu alat yang dilaporkan.',
            ]);
        }

        return $data;
    }

    /**
     * Seragamkan daftar alat dari input form menjadi struktur siap simpan.
     *
     * Mendukung dua bentuk: `alat[]` (multi alat) dan field tunggal lama
     * (`item_id` + `nama_alat`).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function normalisasiAlat(array $data, array $context): array
    {
        $rows = $data['alat'] ?? [];

        if (empty($rows)) {
            $rows = [[
                'item_id'           => $data['item_id'] ?? null,
                'nama_alat'         => $data['nama_alat'] ?? '',
                'kode_alat'         => $data['kode_alat'] ?? null,
                'ruang_lab_nama'    => $data['ruang_lab_nama'] ?? null,
                'jumlah_rusak'      => $data['jumlah_rusak'] ?? 1,
                'satuan'            => $data['satuan'] ?? null,
                'tingkat_kerusakan' => $data['tingkat_kerusakan'],
                'keterangan'        => null,
            ]];
        }

        $hasil = [];
        foreach ($rows as $row) {
            $item = $this->resolveItem($row['item_id'] ?? null, $context);

            $nama = trim((string) ($row['nama_alat'] ?? ''));
            if ($nama === '') {
                $nama = (string) ($item->nama_item ?? '');
            }
            if ($nama === '') {
                continue;
            }

            $hasil[] = [
                'item'              => $item,
                'item_id'           => $item->id ?? null,
                'nama_alat'         => $nama,
                'kode_alat'         => ($row['kode_alat'] ?? null) ?: ($item->kode_item ?? null),
                'ruang_lab_nama'    => ($row['ruang_lab_nama'] ?? null) ?: ($item->ruang_lab_nama ?? null),
                'jumlah_rusak'      => (float) ($row['jumlah_rusak'] ?? 1) ?: 1,
                'satuan'            => ($row['satuan'] ?? null) ?: ($item->satuan ?? 'unit'),
                'tingkat_kerusakan' => $row['tingkat_kerusakan'] ?? $data['tingkat_kerusakan'],
                'keterangan'        => $row['keterangan'] ?? null,
            ];
        }

        // Jaga-jaga: rincian kosong → pakai nilai header agar dokumen tetap utuh.
        if (empty($hasil)) {
            $hasil[] = [
                'item'              => null,
                'item_id'           => null,
                'nama_alat'         => trim((string) ($data['nama_alat'] ?? '')) ?: 'Alat tidak diketahui',
                'kode_alat'         => $data['kode_alat'] ?? null,
                'ruang_lab_nama'    => $data['ruang_lab_nama'] ?? null,
                'jumlah_rusak'      => (float) ($data['jumlah_rusak'] ?? 1) ?: 1,
                'satuan'            => $data['satuan'] ?? 'unit',
                'tingkat_kerusakan' => $data['tingkat_kerusakan'],
                'keterangan'        => null,
            ];
        }

        return $hasil;
    }

    /** Simpan rincian alat berurutan untuk sebuah berita acara. */
    protected function simpanAlat($kerusakanId, array $alat, string $tingkatDefault): void
    {
        $urutan = 0;
        $now    = now();

        $insert = [];
        foreach ($alat as $a) {
            $insert[] = [
                'kerusakan_id'      => $kerusakanId,
                'item_id'           => $a['item_id'] ?? null,
                'nama_alat'         => $a['nama_alat'],
                'kode_alat'         => $a['kode_alat'] ?? null,
                'ruang_lab_nama'    => $a['ruang_lab_nama'] ?? null,
                'jumlah_rusak'      => $a['jumlah_rusak'] ?? 1,
                'satuan'            => $a['satuan'] ?? 'unit',
                'tingkat_kerusakan' => $a['tingkat_kerusakan'] ?? $tingkatDefault,
                'keterangan'        => $a['keterangan'] ?? null,
                'urutan'            => ++$urutan,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        if ($insert) {
            DB::table('laboran_berita_acara_kerusakan_item')->insert($insert);
        }
    }

    /** Sesuaikan kondisi inventaris untuk SETIAP alat yang dilaporkan. */
    protected function sinkronKondisi(array $alat, string $tingkatDefault): void
    {
        foreach ($alat as $a) {
            if (empty($a['item_id'])) {
                continue;
            }

            $kondisi = match ($a['tingkat_kerusakan'] ?? $tingkatDefault) {
                'ringan' => 'rusak_ringan',
                'sedang', 'berat', 'total' => 'rusak_berat',
                default => null,
            };
            if (!$kondisi) {
                continue;
            }

            DB::table('laboran_bahan_alat')->where('id', $a['item_id'])->update([
                'kondisi'    => $kondisi,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Rincian alat sebuah berita acara (fallback ke kolom tunggal lama).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    protected function rincianAlat(object $ba)
    {
        $rows = DB::table('laboran_berita_acara_kerusakan_item')
            ->where('kerusakan_id', $ba->id)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        return collect([(object) [
            'item_id'           => $ba->item_id,
            'nama_alat'         => $ba->nama_alat,
            'kode_alat'         => $ba->kode_alat,
            'ruang_lab_nama'    => $ba->ruang_lab_nama,
            'jumlah_rusak'      => $ba->jumlah_rusak,
            'satuan'            => $ba->satuan,
            'tingkat_kerusakan' => $ba->tingkat_kerusakan,
            'keterangan'        => null,
        ]]);
    }

    /** Lampirkan rincian alat ke tiap baris daftar (hindari query di view). */
    protected function lampirkanRincian($rows): void
    {
        $ids = collect($rows)->pluck('id')->all();
        if (empty($ids)) {
            return;
        }

        $items = DB::table('laboran_berita_acara_kerusakan_item')
            ->whereIn('kerusakan_id', $ids)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get()
            ->groupBy('kerusakan_id');

        foreach ($rows as $row) {
            $row->alat = $items->get($row->id, collect());
        }
    }

    protected function resolveItem($itemId, array $context)
    {
        if (!$itemId) {
            return null;
        }

        $item = DB::table('laboran_bahan_alat')->where('id', $itemId)->first();
        if (!$item) {
            return null;
        }

        return LaboranScope::allows($item->jurusan_id ?? null, $context) ? $item : null;
    }
}
