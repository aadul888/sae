<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul Analisis & Input Kebutuhan Alat/Bahan Praktik.
 *
 * Menyajikan analisis kekurangan alat/bahan berdasarkan:
 *  - Kebutuhan ideal (input manual / permintaan guru)
 *  - Ketersediaan stok nyata pada inventaris
 *  - Referensi kerusakan alat (berita acara)
 */
class LaboranKebutuhanController extends LaboranBaseController
{
    public const PRIORITAS = [
        'rendah'  => 'Rendah',
        'sedang'  => 'Sedang',
        'tinggi'  => 'Tinggi',
        'kritis'  => 'Kritis',
    ];

    public const STATUS = [
        'draf'        => 'Draf',
        'diverifikasi' => 'Diverifikasi',
        'disetujui'   => 'Disetujui',
        'ditolak'     => 'Ditolak',
        'dipenuhi'    => 'Dipenuhi',
    ];

    public const JENIS = [
        'alat'             => 'Alat',
        'bahan_habis_pakai' => 'Bahan Habis Pakai',
    ];

    public const SUMBER_ANALISIS = [
        'praktik'         => 'Kebutuhan Praktik',
        'jadwal'          => 'Jadwal Praktik',
        'permintaan_guru' => 'Permintaan Guru',
        'kerusakan'       => 'Kerusakan Alat',
        'lainnya'         => 'Lainnya',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search         = trim((string) $request->query('q', ''));
        $jenisFilter    = trim((string) $request->query('jenis', ''));
        $prioritasFilter = trim((string) $request->query('prioritas', ''));
        $statusFilter   = trim((string) $request->query('status', ''));
        $labFilter      = trim((string) $request->query('lab', ''));

        $query = DB::table('laboran_kebutuhan as k')
            ->leftJoin('jurusan_meta as jm', 'k.jurusan_id', '=', 'jm.jurusan_id')
            ->select('k.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'k.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('k.nama_item', 'like', "%{$search}%")
                  ->orWhere('k.spesifikasi', 'like', "%{$search}%")
                  ->orWhere('k.analisis_kebutuhan', 'like', "%{$search}%")
                  ->orWhere('k.referensi_mapel', 'like', "%{$search}%");
            });
        }
        if ($jenisFilter !== '')     $query->where('k.jenis', $jenisFilter);
        if ($prioritasFilter !== '') $query->where('k.prioritas', $prioritasFilter);
        if ($statusFilter !== '')    $query->where('k.status', $statusFilter);
        if ($labFilter !== '')       $query->where('k.ruang_lab_nama', $labFilter);

        // Statistik (dalam cakupan)
        $statBase = DB::table('laboran_kebutuhan');
        LaboranScope::apply($statBase, 'jurusan_id', $context);

        $statTotal       = (clone $statBase)->count();
        $statKritis      = (clone $statBase)->where('prioritas', 'kritis')->count();
        $statBelum       = (clone $statBase)->whereIn('status', ['draf'])->count();
        $statDisetujui   = (clone $statBase)->where('status', 'disetujui')->count();

        // Analisis otomatis: item yang stoknya menipis / rusak
        $analisis = $this->buildAnalisisStok($context);

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByRaw("CASE k.prioritas
                WHEN 'kritis' THEN 1
                WHEN 'tinggi' THEN 2
                WHEN 'sedang' THEN 3
                ELSE 4 END ASC")
            ->orderByDesc('k.tanggal_input')
            ->orderByDesc('k.id')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.laboran.kebutuhan', array_merge(
            compact(
                'list', 'search', 'jenisFilter', 'prioritasFilter', 'statusFilter', 'labFilter',
                'statTotal', 'statKritis', 'statBelum', 'statDisetujui', 'analisis', 'perPage'
            ),
            $this->abilities(),
            [
                'context'       => $context,
                'scopeBadge'    => LaboranScope::badge($context),
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
                'lokasiOptions' => $this->lokasiOptions(),
                'prioritasList' => self::PRIORITAS,
                'statusList'    => self::STATUS,
                'jenisList'     => self::JENIS,
                'sumberList'    => self::SUMBER_ANALISIS,
            ]
        ));
    }

    /**
     * Analisis otomatis ketersediaan stok (item tipis / rusak / kedaluwarsa).
     */
    protected function buildAnalisisStok(array $context): array
    {
        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);

        $items = $q->get(['id', 'kode_item', 'nama_item', 'jenis', 'satuan', 'stok_total', 'stok_tersedia', 'kondisi', 'ruang_lab_nama', 'tgl_kedaluwarsa']);

        $kritis = [];
        $perluPerhatian = [];
        $kadaluwarsa = [];

        foreach ($items as $it) {
            $tersedia = (float) $it->stok_tersedia;
            $total    = (float) $it->stok_total;

            if ($it->kondisi === 'rusak_berat' || $tersedia <= 0) {
                $kritis[] = $it;
                continue;
            }

            if ($it->kondisi !== 'baik' || ($total > 0 && $tersedia / max($total, 1) <= 0.25)) {
                $perluPerhatian[] = $it;
            }

            if (!empty($it->tgl_kedaluwarsa)) {
                try {
                    $exp = \Carbon\Carbon::parse($it->tgl_kedaluwarsa);
                    if ($exp->isPast() || $exp->diffInDays(now(), false) >= -60) {
                        $kadaluwarsa[] = $it;
                    }
                } catch (\Throwable $e) {
                    // abaikan tanggal tidak valid
                }
            }
        }

        return [
            'total_item'      => $items->count(),
            'kritis'          => array_slice($kritis, 0, 10),
            'perlu_perhatian' => array_slice($perluPerhatian, 0, 10),
            'kadaluwarsa'     => array_slice($kadaluwarsa, 0, 10),
            'jumlah_kritis'   => count($kritis),
            'jumlah_perhatian' => count($perluPerhatian),
            'jumlah_kadaluwarsa' => count($kadaluwarsa),
        ];
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $request->validate([
            'nama_item'          => 'required|string|max:255',
            'jenis'              => 'required|in:alat,bahan_habis_pakai',
            'kategori'           => 'nullable|string|max:100',
            'spesifikasi'        => 'nullable|string|max:1000',
            'jumlah_kebutuhan'   => 'required|numeric|min:0',
            'jumlah_tersedia'    => 'nullable|numeric|min:0',
            'satuan'             => 'required|string|max:30',
            'prioritas'          => 'required|in:rendah,sedang,tinggi,kritis',
            'sumber_analisis'    => 'nullable|in:praktik,jadwal,permintaan_guru,kerusakan,lainnya',
            'analisis_kebutuhan' => 'nullable|string|max:2000',
            'referensi_mapel'    => 'nullable|string|max:255',
            'ruang_lab_nama'     => 'nullable|string|max:100',
            'tahun_ajaran'       => 'nullable|string|max:20',
            'semester'           => 'nullable|string|max:10',
            'tanggal_input'      => 'nullable|date',
            'status'             => 'nullable|in:draf,diverifikasi,disetujui,ditolak,dipenuhi',
            'jurusan_id'         => 'nullable|string|max:50',
        ]);

        $kebutuhan = (float) $data['jumlah_kebutuhan'];
        $tersedia  = (float) ($data['jumlah_tersedia'] ?? 0);

        DB::table('laboran_kebutuhan')->insert([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? null,
            'tahun_ajaran'       => $data['tahun_ajaran'] ?? null,
            'semester'           => $data['semester'] ?? null,
            'nama_item'          => trim($data['nama_item']),
            'jenis'              => $data['jenis'],
            'kategori'           => $data['kategori'] ?? null,
            'spesifikasi'        => $data['spesifikasi'] ?? null,
            'jumlah_kebutuhan'   => $kebutuhan,
            'jumlah_tersedia'    => $tersedia,
            'satuan'             => $data['satuan'],
            'selisih'            => max(0, $kebutuhan - $tersedia),
            'prioritas'          => $data['prioritas'],
            'sumber_analisis'    => $data['sumber_analisis'] ?? 'praktik',
            'analisis_kebutuhan' => $data['analisis_kebutuhan'] ?? null,
            'referensi_mapel'    => $data['referensi_mapel'] ?? null,
            'status'             => $data['status'] ?? 'draf',
            'tanggal_input'      => $data['tanggal_input'] ?? now()->toDateString(),
            'dibuat_oleh_ptk_id' => $context['ptk_id'],
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Data kebutuhan alat/bahan berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kebutuhan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $request->validate([
            'nama_item'          => 'required|string|max:255',
            'jenis'              => 'required|in:alat,bahan_habis_pakai',
            'kategori'           => 'nullable|string|max:100',
            'spesifikasi'        => 'nullable|string|max:1000',
            'jumlah_kebutuhan'   => 'required|numeric|min:0',
            'jumlah_tersedia'    => 'nullable|numeric|min:0',
            'satuan'             => 'required|string|max:30',
            'prioritas'          => 'required|in:rendah,sedang,tinggi,kritis',
            'sumber_analisis'    => 'nullable|in:praktik,jadwal,permintaan_guru,kerusakan,lainnya',
            'analisis_kebutuhan' => 'nullable|string|max:2000',
            'referensi_mapel'    => 'nullable|string|max:255',
            'ruang_lab_nama'     => 'nullable|string|max:100',
            'tahun_ajaran'       => 'nullable|string|max:20',
            'semester'           => 'nullable|string|max:10',
            'tanggal_input'      => 'nullable|date',
            'status'             => 'nullable|in:draf,diverifikasi,disetujui,ditolak,dipenuhi',
            'catatan_verifikasi' => 'nullable|string|max:1000',
            'jurusan_id'         => 'nullable|string|max:50',
        ]);

        $kebutuhan = (float) $data['jumlah_kebutuhan'];
        $tersedia  = (float) ($data['jumlah_tersedia'] ?? 0);

        DB::table('laboran_kebutuhan')->where('id', $id)->update([
            'jurusan_id'         => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? $row->jurusan_id, $context),
            'ruang_lab_nama'     => $data['ruang_lab_nama'] ?? null,
            'tahun_ajaran'       => $data['tahun_ajaran'] ?? null,
            'semester'           => $data['semester'] ?? null,
            'nama_item'          => trim($data['nama_item']),
            'jenis'              => $data['jenis'],
            'kategori'           => $data['kategori'] ?? null,
            'spesifikasi'        => $data['spesifikasi'] ?? null,
            'jumlah_kebutuhan'   => $kebutuhan,
            'jumlah_tersedia'    => $tersedia,
            'satuan'             => $data['satuan'],
            'selisih'            => max(0, $kebutuhan - $tersedia),
            'prioritas'          => $data['prioritas'],
            'sumber_analisis'    => $data['sumber_analisis'] ?? 'praktik',
            'analisis_kebutuhan' => $data['analisis_kebutuhan'] ?? null,
            'referensi_mapel'    => $data['referensi_mapel'] ?? null,
            'status'             => $data['status'] ?? $row->status,
            'tanggal_input'      => $data['tanggal_input'] ?? $row->tanggal_input,
            'catatan_verifikasi' => $data['catatan_verifikasi'] ?? $row->catatan_verifikasi,
            'updated_at'         => now(),
        ]);

        return $this->respond($request, true, 'Data kebutuhan alat/bahan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_kebutuhan', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Data tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_kebutuhan')->where('id', $id)->delete();

        return $this->respond($request, true, 'Data kebutuhan berhasil dihapus.');
    }

    /**
     * Jalankan analisis kebutuhan otomatis dari kondisi stok nyata dan kerusakan alat (Point 3).
     */
    public function jalankanAnalisis(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);
        $items = $q->get();

        $countNew = 0;
        $countUpdated = 0;

        foreach ($items as $it) {
            $tersedia = (float) $it->stok_tersedia;
            $total    = (float) $it->stok_total;

            $perluUsulan = false;
            $prioritas = 'sedang';
            $alasan = [];

            if ($it->kondisi === 'rusak_berat') {
                $perluUsulan = true;
                $prioritas = 'kritis';
                $alasan[] = 'Kondisi alat rusak berat';
            } elseif ($it->kondisi === 'rusak_ringan') {
                $perluUsulan = true;
                $prioritas = 'tinggi';
                $alasan[] = 'Kondisi alat rusak ringan';
            }

            if ($tersedia <= 0) {
                $perluUsulan = true;
                $prioritas = 'kritis';
                $alasan[] = 'Stok habis (0 ' . $it->satuan . ')';
            } elseif ($total > 0 && ($tersedia / $total) <= 0.25) {
                $perluUsulan = true;
                if ($prioritas !== 'kritis') $prioritas = 'tinggi';
                $alasan[] = 'Stok tersisa <= 25% (' . $tersedia . '/' . $total . ' ' . $it->satuan . ')';
            }

            if ($perluUsulan) {
                $existing = DB::table('laboran_kebutuhan')
                    ->where('nama_item', $it->nama_item)
                    ->where(function ($w) use ($it) {
                        if (!empty($it->jurusan_id)) {
                            $w->where('jurusan_id', $it->jurusan_id);
                        }
                    })
                    ->whereIn('status', ['draf', 'diverifikasi', 'disetujui'])
                    ->first();

                $kebutuhanIdeal = max(1, $total > 0 ? $total : 1);
                $selisih = max(0, $kebutuhanIdeal - $tersedia);

                if ($existing) {
                    DB::table('laboran_kebutuhan')->where('id', $existing->id)->update([
                        'jumlah_tersedia'    => $tersedia,
                        'selisih'            => $selisih,
                        'prioritas'          => $prioritas,
                        'analisis_kebutuhan' => implode(', ', $alasan) . ' (Analisis Otomatis Stok)',
                        'updated_at'         => now(),
                    ]);
                    $countUpdated++;
                } else {
                    DB::table('laboran_kebutuhan')->insert([
                        'jurusan_id'         => $it->jurusan_id ?: LaboranScope::jurusanForWrite(null, $context),
                        'ruang_lab_nama'     => $it->ruang_lab_nama,
                        'nama_item'          => $it->nama_item,
                        'jenis'              => $it->jenis ?: 'alat',
                        'kategori'           => $it->kategori ?? null,
                        'spesifikasi'        => $it->spesifikasi ?? null,
                        'jumlah_kebutuhan'   => $kebutuhanIdeal,
                        'jumlah_tersedia'    => $tersedia,
                        'satuan'             => $it->satuan ?: 'unit',
                        'selisih'            => $selisih,
                        'prioritas'          => $prioritas,
                        'sumber_analisis'    => 'kerusakan',
                        'analisis_kebutuhan' => implode(', ', $alasan) . ' (Analisis Otomatis Stok)',
                        'status'             => 'diverifikasi',
                        'tanggal_input'      => now()->toDateString(),
                        'dibuat_oleh_ptk_id' => $context['ptk_id'],
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                    $countNew++;
                }
            }
        }

        $totalProcessed = $countNew + $countUpdated;
        $msg = $totalProcessed > 0
            ? "Analisis kebutuhan selesai. {$countNew} item kebutuhan baru ditambahkan dan {$countUpdated} item diperbarui berdasarkan kondisi stok nyata."
            : "Analisis kebutuhan selesai. Semua stok inventaris saat ini dalam kondisi baik dan mencukupi.";

        return $this->respond($request, true, $msg, 200, [
            'count_new'     => $countNew,
            'count_updated' => $countUpdated,
        ]);
    }
}
