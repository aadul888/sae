<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Perawatan / Pemeliharaan / Penataan Alat sesuai K3
 * beserta Kartu Perawatan (riwayat per alat).
 */
class LaboranPerawatanController extends LaboranBaseController
{
    public const JENIS_PERAWATAN = [
        'rutin'      => 'Perawatan Rutin',
        'berkala'    => 'Perawatan Berkala',
        'kalibrasi'  => 'Kalibrasi',
        'perbaikan'  => 'Perbaikan',
        'penataan'   => 'Penataan Alat',
    ];

    public const KATEGORI_K3 = [
        'listrik'       => 'Risiko Listrik',
        'bahan_kimia'   => 'Bahan Kimia Berbahaya',
        'mekanik'       => 'Risiko Mekanik',
        'ergonomi'      => 'Ergonomi',
        'kebakaran'     => 'Risiko Kebakaran',
        'biologis'      => 'Risiko Biologis',
        'umum'          => 'Umum',
    ];

    public const STATUS = [
        'dijadwalkan' => 'Dijadwalkan',
        'proses'      => 'Dalam Proses',
        'selesai'     => 'Selesai',
        'ditunda'     => 'Ditunda',
    ];

    public const KONDISI_AKHIR = [
        'baik'          => 'Baik / Laik Pakai',
        'rusak_ringan'  => 'Rusak Ringan',
        'rusak_berat'   => 'Rusak Berat',
        'tidak_laik'    => 'Tidak Laik Pakai',
    ];

    /** APD standar K3 laboratorium. */
    public const APD_OPTIONS = [
        'Jas Laboratorium',
        'Sarung Tangan',
        'Kacamata Pelindung',
        'Masker',
        'Sepatu Safety',
        'Helm',
        'Pelindung Telinga',
        'Celemek Anti Bahan Kimia',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $tab = (string) $request->query('tab', 'perawatan');
        if (!in_array($tab, ['perawatan', 'kartu'], true)) {
            $tab = 'perawatan';
        }

        $search          = trim((string) $request->query('q', ''));
        $jenisFilter     = trim((string) $request->query('jenis', ''));
        $statusFilter    = trim((string) $request->query('status', ''));
        $k3Filter        = trim((string) $request->query('k3', ''));

        // ---- Statistik ----
        $statBase = DB::table('laboran_perawatan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal       = (clone $statBase)->count();
        $statDijadwalkan = (clone $statBase)->where('status', 'dijadwalkan')->count();
        $statProses      = (clone $statBase)->where('status', 'proses')->count();
        $statSelesai     = (clone $statBase)->where('status', 'selesai')->count();

        $statKartu = DB::table('laboran_kartu_perawatan');
        LaboranScope::apply($statKartu, 'jurusan_id', $context);
        $statKartuAktif = (clone $statKartu)->where('status', 'aktif')->count();
        $statJatuhTempo = (clone $statKartu)->where('status', 'aktif')
            ->whereNotNull('jadwal_berikutnya')
            ->where('jadwal_berikutnya', '<=', now()->addDays(7)->toDateString())
            ->count();

        // ---- Data per tab ----
        if ($tab === 'kartu') {
            $query = DB::table('laboran_kartu_perawatan as kp')
                ->leftJoin('jurusan_meta as jm', 'kp.jurusan_id', '=', 'jm.jurusan_id')
                ->select('kp.*', 'jm.nama_jurusan', 'jm.singkatan');

            LaboranScope::apply($query, 'kp.jurusan_id', $context);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('kp.nama_alat', 'like', "%{$search}%")
                      ->orWhere('kp.kode_kartu', 'like', "%{$search}%")
                      ->orWhere('kp.kode_alat', 'like', "%{$search}%")
                      ->orWhere('kp.ruang_lab_nama', 'like', "%{$search}%");
                });
            }

            $perPage = $this->perPage($request);
            $list = $query->orderByRaw('CASE WHEN kp.jadwal_berikutnya IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('kp.jadwal_berikutnya', 'asc')
                ->orderBy('kp.nama_alat', 'asc')
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $query = DB::table('laboran_perawatan as p')
                ->leftJoin('jurusan_meta as jm', 'p.jurusan_id', '=', 'jm.jurusan_id')
                ->select('p.*', 'jm.nama_jurusan', 'jm.singkatan');

            LaboranScope::apply($query, 'p.jurusan_id', $context);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('p.nama_alat', 'like', "%{$search}%")
                      ->orWhere('p.kode_perawatan', 'like', "%{$search}%")
                      ->orWhere('p.uraian_pekerjaan', 'like', "%{$search}%")
                      ->orWhere('p.pelaksana_nama', 'like', "%{$search}%");
                });
            }
            if ($jenisFilter !== '')  $query->where('p.jenis_perawatan', $jenisFilter);
            if ($statusFilter !== '') $query->where('p.status', $statusFilter);
            if ($k3Filter !== '')     $query->where('p.kategori_k3', $k3Filter);

            $perPage = $this->perPage($request);
            $list = $query->orderByRaw("CASE p.status
                    WHEN 'proses' THEN 1
                    WHEN 'dijadwalkan' THEN 2
                    WHEN 'ditunda' THEN 3
                    ELSE 4 END ASC")
                ->orderByDesc('p.tanggal_perawatan')
                ->orderByDesc('p.id')
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('dashboard.laboran.perawatan', array_merge(
            compact(
                'tab', 'list', 'search', 'jenisFilter', 'statusFilter', 'k3Filter', 'perPage',
                'statTotal', 'statDijadwalkan', 'statProses', 'statSelesai',
                'statKartuAktif', 'statJatuhTempo'
            ),
            $this->abilities(),
            [
                'context'        => $context,
                'scopeBadge'     => LaboranScope::badge($context),
                'itemOptions'    => $this->itemOptions($context),
                'lokasiOptions'  => $this->lokasiOptions(),
                'jenisList'      => self::JENIS_PERAWATAN,
                'k3List'         => self::KATEGORI_K3,
                'statusList'     => self::STATUS,
                'kondisiList'    => self::KONDISI_AKHIR,
                'apdList'        => self::APD_OPTIONS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    protected function perPage(Request $request): int
    {
        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));

        return in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
    }

    // =========================================================================
    // PERAWATAN
    // =========================================================================

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validatedPerawatan($request);

        $item = $this->resolveItem($data['item_id'] ?? null, $context);

        DB::table('laboran_perawatan')->insert([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? ($item->ruang_lab_nama ?? null),
            'kode_perawatan'     => $this->generateNomor('laboran_perawatan', 'kode_perawatan', 'PRW'),
            'item_id'            => $item->id ?? null,
            'nama_alat'          => ($data['nama_alat'] ?? '') ?: ($item->nama_item ?? null),
            'kode_alat'          => $data['kode_alat'] ?? ($item->kode_item ?? null),
            'jenis_perawatan'    => $data['jenis_perawatan'],
            'kategori_k3'        => $data['kategori_k3'] ?? 'umum',
            'tanggal_perawatan'  => $data['tanggal_perawatan'],
            'periode_berikutnya' => $data['periode_berikutnya'] ?? null,
            'pelaksana_ptk_id'   => $data['pelaksana_ptk_id'] ?? $context['ptk_id'],
            'pelaksana_nama'     => $data['pelaksana_nama'] ?? $context['nama'],
            'uraian_pekerjaan'   => $data['uraian_pekerjaan'] ?? null,
            'prosedur_k3'        => $data['prosedur_k3'] ?? null,
            'apd_digunakan'      => !empty($data['apd_digunakan']) ? json_encode(array_values($data['apd_digunakan'])) : null,
            'hasil'              => $data['hasil'] ?? null,
            'kondisi_akhir'      => $data['kondisi_akhir'] ?? null,
            'biaya'              => $data['biaya'] ?? null,
            'status'             => $data['status'] ?? 'dijadwalkan',
            'catatan'            => $data['catatan'] ?? null,
            'dibuat_oleh_ptk_id' => $context['ptk_id'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        // Sinkronkan kondisi alat pada inventaris bila perawatan selesai
        if (($data['status'] ?? '') === 'selesai' && !empty($data['kondisi_akhir']) && $item) {
            $this->syncKondisiItem($item->id, $data['kondisi_akhir']);
        }

        return $this->respond($request, true, 'Catatan perawatan alat berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_perawatan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data perawatan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validatedPerawatan($request);
        $item = $this->resolveItem($data['item_id'] ?? null, $context);

        DB::table('laboran_perawatan')->where('id', $id)->update([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? $row->jurusan_id, $context),
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? ($item->ruang_lab_nama ?? $row->ruang_lab_nama),
            'item_id'            => $item->id ?? $row->item_id,
            'nama_alat'          => ($data['nama_alat'] ?? '') ?: ($item->nama_item ?? $row->nama_alat),
            'kode_alat'          => $data['kode_alat'] ?? ($item->kode_item ?? $row->kode_alat),
            'jenis_perawatan'    => $data['jenis_perawatan'],
            'kategori_k3'        => $data['kategori_k3'] ?? $row->kategori_k3,
            'tanggal_perawatan'  => $data['tanggal_perawatan'],
            'periode_berikutnya' => $data['periode_berikutnya'] ?? null,
            'pelaksana_ptk_id'   => $data['pelaksana_ptk_id'] ?? $row->pelaksana_ptk_id,
            'pelaksana_nama'     => $data['pelaksana_nama'] ?? $row->pelaksana_nama,
            'uraian_pekerjaan'   => $data['uraian_pekerjaan'] ?? null,
            'prosedur_k3'        => $data['prosedur_k3'] ?? null,
            'apd_digunakan'      => !empty($data['apd_digunakan']) ? json_encode(array_values($data['apd_digunakan'])) : null,
            'hasil'              => $data['hasil'] ?? null,
            'kondisi_akhir'      => $data['kondisi_akhir'] ?? null,
            'biaya'              => $data['biaya'] ?? null,
            'status'             => $data['status'] ?? $row->status,
            'catatan'            => $data['catatan'] ?? null,
            'updated_at'         => now(),
        ]);

        if (($data['status'] ?? '') === 'selesai' && !empty($data['kondisi_akhir']) && $item) {
            $this->syncKondisiItem($item->id, $data['kondisi_akhir']);
        }

        return $this->respond($request, true, 'Catatan perawatan alat berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_perawatan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data perawatan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_perawatan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Catatan perawatan berhasil dihapus.');
    }

    // =========================================================================
    // KARTU PERAWATAN
    // =========================================================================

    public function storeKartu(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'item_id'                 => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_alat'               => 'nullable|string|max:255',
            'kode_alat'               => 'nullable|string|max:50',
            'ruang_lab_nama'          => 'nullable|string|max:100',
            'merk_tipe'               => 'nullable|string|max:150',
            'tanggal_mulai'           => 'nullable|date',
            'interval_hari'           => 'required|integer|min:1|max:3650',
            'penanggung_jawab_ptk_id' => 'nullable|string|max:50',
            'penanggung_jawab_nama'   => 'nullable|string|max:150',
            'instruksi_perawatan'     => 'nullable|string|max:2000',
            'apd_wajib'               => 'nullable|array',
            'status'                  => 'nullable|in:aktif,nonaktif',
            'catatan'                 => 'nullable|string|max:1000',
            'jurusan_id'              => 'nullable|string|max:50',
        ]);

        $item = $this->resolveItem($data['item_id'] ?? null, $context);

        $namaAlat = trim((string) ($data['nama_alat'] ?? '')) ?: ($item->nama_item ?? '');
        if ($namaAlat === '') {
            return $this->respond($request, false, 'Nama alat wajib diisi atau pilih dari daftar inventaris.', 422);
        }

        $mulai = !empty($data['tanggal_mulai']) ? Carbon::parse($data['tanggal_mulai']) : now();
        $interval = (int) $data['interval_hari'];

        DB::table('laboran_kartu_perawatan')->insert([
            'jurusan_id'              => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'kode_kartu'              => $this->generateNomor('laboran_kartu_perawatan', 'kode_kartu', 'KRT'),
            'item_id'                 => $item->id ?? null,
            'nama_alat'               => $namaAlat,
            'kode_alat'               => $data['kode_alat'] ?? ($item->kode_item ?? null),
            'ruang_lab_nama'          => $data['ruang_lab_nama'] ?? ($item->ruang_lab_nama ?? null),
            'merk_tipe'               => $data['merk_tipe'] ?? null,
            'tanggal_mulai'           => $mulai->toDateString(),
            'interval_hari'           => $interval,
            'jadwal_berikutnya'       => $mulai->copy()->addDays($interval)->toDateString(),
            'penanggung_jawab_ptk_id' => $data['penanggung_jawab_ptk_id'] ?? $context['ptk_id'],
            'penanggung_jawab_nama'   => $data['penanggung_jawab_nama'] ?? $context['nama'],
            'instruksi_perawatan'     => $data['instruksi_perawatan'] ?? null,
            'apd_wajib'               => !empty($data['apd_wajib']) ? json_encode(array_values($data['apd_wajib'])) : null,
            'status'                  => $data['status'] ?? 'aktif',
            'catatan'                 => $data['catatan'] ?? null,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        return $this->respond($request, true, 'Kartu perawatan alat berhasil dibuat.');
    }

    public function updateKartu(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kartu_perawatan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Kartu perawatan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'item_id'                 => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_alat'               => 'required|string|max:255',
            'kode_alat'               => 'nullable|string|max:50',
            'ruang_lab_nama'          => 'nullable|string|max:100',
            'merk_tipe'               => 'nullable|string|max:150',
            'tanggal_mulai'           => 'nullable|date',
            'interval_hari'           => 'required|integer|min:1|max:3650',
            'penanggung_jawab_ptk_id' => 'nullable|string|max:50',
            'penanggung_jawab_nama'   => 'nullable|string|max:150',
            'instruksi_perawatan'     => 'nullable|string|max:2000',
            'apd_wajib'               => 'nullable|array',
            'status'                  => 'nullable|in:aktif,nonaktif',
            'catatan'                 => 'nullable|string|max:1000',
        ]);

        $mulai = !empty($data['tanggal_mulai']) ? Carbon::parse($data['tanggal_mulai']) : Carbon::parse($row->tanggal_mulai ?: now());
        $interval = (int) $data['interval_hari'];

        DB::table('laboran_kartu_perawatan')->where('id', $id)->update([
            'nama_alat'               => trim($data['nama_alat']),
            'kode_alat'               => $data['kode_alat'] ?? $row->kode_alat,
            'ruang_lab_nama'          => $data['ruang_lab_nama'] ?? $row->ruang_lab_nama,
            'merk_tipe'               => $data['merk_tipe'] ?? null,
            'tanggal_mulai'           => $mulai->toDateString(),
            'interval_hari'           => $interval,
            'jadwal_berikutnya'       => $mulai->copy()->addDays($interval)->toDateString(),
            'penanggung_jawab_ptk_id' => $data['penanggung_jawab_ptk_id'] ?? $row->penanggung_jawab_ptk_id,
            'penanggung_jawab_nama'   => $data['penanggung_jawab_nama'] ?? $row->penanggung_jawab_nama,
            'instruksi_perawatan'     => $data['instruksi_perawatan'] ?? null,
            'apd_wajib'               => !empty($data['apd_wajib']) ? json_encode(array_values($data['apd_wajib'])) : null,
            'status'                  => $data['status'] ?? $row->status,
            'catatan'                 => $data['catatan'] ?? null,
            'updated_at'              => now(),
        ]);

        return $this->respond($request, true, 'Kartu perawatan berhasil diperbarui.');
    }

    public function destroyKartu(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kartu_perawatan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Kartu perawatan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_kartu_perawatan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Kartu perawatan berhasil dihapus.');
    }

    /**
     * Tandai kartu sudah dirawat -> jadwal berikutnya bergeser satu interval.
     */
    public function tandaiKartuSelesai(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kartu_perawatan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Kartu perawatan tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $interval = max(1, (int) $row->interval_hari);
        $berikutnya = now()->addDays($interval)->toDateString();

        DB::table('laboran_kartu_perawatan')->where('id', $id)->update([
            'jadwal_berikutnya' => $berikutnya,
            'updated_at'        => now(),
        ]);

        return $this->respond($request, true, 'Perawatan ditandai selesai. Jadwal berikutnya: '
            . Carbon::parse($berikutnya)->translatedFormat('d F Y') . '.');
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    protected function validatedPerawatan(Request $request): array
    {
        return $request->validate([
            'item_id'            => 'nullable|integer|exists:laboran_bahan_alat,id',
            'nama_alat'          => 'nullable|string|max:255',
            'kode_alat'          => 'nullable|string|max:50',
            'ruang_lab_nama'     => 'nullable|string|max:100',
            'jenis_perawatan'    => 'required|in:rutin,berkala,kalibrasi,perbaikan,penataan',
            'kategori_k3'        => 'nullable|in:listrik,bahan_kimia,mekanik,ergonomi,kebakaran,biologis,umum',
            'tanggal_perawatan'  => 'required|date',
            'periode_berikutnya' => 'nullable|date',
            'pelaksana_ptk_id'   => 'nullable|string|max:50',
            'pelaksana_nama'     => 'nullable|string|max:150',
            'uraian_pekerjaan'   => 'nullable|string|max:2000',
            'prosedur_k3'        => 'nullable|string|max:2000',
            'apd_digunakan'      => 'nullable|array',
            'hasil'              => 'nullable|string|max:1000',
            'kondisi_akhir'      => 'nullable|in:baik,rusak_ringan,rusak_berat,tidak_laik',
            'biaya'              => 'nullable|numeric|min:0',
            'status'             => 'nullable|in:dijadwalkan,proses,selesai,ditunda',
            'catatan'            => 'nullable|string|max:1000',
            'jurusan_id'         => 'nullable|string|max:50',
        ], [
            'jenis_perawatan.required' => 'Jenis perawatan wajib dipilih.',
            'tanggal_perawatan.required' => 'Tanggal perawatan wajib diisi.',
        ]);
    }

    /**
     * Ambil item inventaris bila berada dalam cakupan jurusan.
     */
    protected function resolveItem($itemId, array $context)
    {
        if (!$itemId) {
            return null;
        }

        $item = DB::table('laboran_bahan_alat')->where('id', $itemId)->first();

        if (!$item) {
            return null;
        }

        if (!LaboranScope::allows($item->jurusan_id ?? null, $context)) {
            return null;
        }

        return $item;
    }

    /**
     * Sinkronkan kondisi inventaris setelah perawatan selesai.
     */
    protected function syncKondisiItem($itemId, string $kondisiAkhir): void
    {
        $kondisi = $kondisiAkhir === 'tidak_laik' ? 'rusak_berat' : $kondisiAkhir;

        if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat'], true)) {
            return;
        }

        DB::table('laboran_bahan_alat')->where('id', $itemId)->update([
            'kondisi'    => $kondisi,
            'updated_at' => now(),
        ]);
    }
}
