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

        // Daftar inventaris aktif untuk referensi auto-fill di form input kebutuhan
        $invQuery = DB::table('laboran_bahan_alat');
        LaboranScope::apply($invQuery, 'jurusan_id', $context);
        $inventarisList = $invQuery->orderBy('nama_item')->get([
            'id', 'kode_item', 'nama_item', 'jenis', 'spesifikasi',
            'stok_total', 'stok_tersedia', 'jumlah_baik', 'jumlah_rusak_ringan',
            'jumlah_rusak_berat', 'jumlah_kedaluwarsa', 'satuan', 'kondisi',
            'ruang_lab_nama', 'lokasi_lemari_rak', 'tgl_kedaluwarsa', 'jurusan_id'
        ]);

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
                'statTotal', 'statKritis', 'statBelum', 'statDisetujui', 'analisis', 'inventarisList', 'perPage'
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
     * Evaluasi kesehatan unit individual sebuah item inventaris.
     *
     * Ketersediaan laik pakai mengacu pada `jumlah_baik` (bukan sekadar stok_tersedia),
     * sehingga kasus stok "1/1" dengan kondisi rusak tetap terdeteksi tidak laik.
     *
     * @return array{0:string,1:string,2:float,3:float} [kategori, ringkasan, selisih, laik]
     */
    protected function evaluasiKesehatanItem(object $it): array
    {
        $total        = max(0, (float) $it->stok_total);
        $tersedia     = (float) $it->stok_tersedia;
        $jBaik        = (float) ($it->jumlah_baik ?? 0);
        $jRusakRingan = (float) ($it->jumlah_rusak_ringan ?? 0);
        $jRusakBerat  = (float) ($it->jumlah_rusak_berat ?? 0);
        $jKadaluwarsa = (float) ($it->jumlah_kedaluwarsa ?? 0);

        // Jika rincian kondisi belum pernah diisi, anggap semua unit tersedia laik (kompatibilitas data lama).
        $adaRincian = ($jBaik + $jRusakRingan + $jRusakBerat + $jKadaluwarsa) > 0;
        $laik = $adaRincian ? $jBaik : max(0, $tersedia - $jRusakBerat);

        $tidakLaik = max(0, $total - $laik);

        // KRITIS: tidak ada satu pun unit yang laik pakai walau kuantitas terisi penuh.
        if ($total > 0 && $laik <= 0 && $tersedia > 0) {
            return ['kritis', "{$total}/{$total} unit tidak laik pakai (kondisi rusak/kedaluwarsa)", $total, $laik];
        }

        // KRITIS: kondisi dominan rusak berat.
        if ($it->kondisi === 'rusak_berat' || $jRusakBerat > 0) {
            $qty = $jRusakBerat > 0 ? $jRusakBerat : max(1, $tidakLaik);
            return ['kritis', "{$qty} {$it->satuan} rusak berat", $qty, $laik];
        }

        // KRITIS: stok habis total.
        if ($tersedia <= 0 && $total <= 0) {
            return ['kritis', "Stok habis (0 {$it->satuan})", max(1, $total > 0 ? $total : 1), 0];
        }
        if ($tersedia <= 0) {
            return ['kritis', "Stok habis (0 {$it->satuan})", $total, 0];
        }

        // PERHATIAN: sebagian unit tidak laik walau kuantitas masih terisi penuh.
        if ($tidakLaik > 0) {
            $bagian = [];
            if ($jRusakRingan > 0) $bagian[] = "{$jRusakRingan} rusak ringan";
            if ($jKadaluwarsa > 0) $bagian[] = "{$jKadaluwarsa} kedaluwarsa";
            if ($jRusakBerat > 0)  $bagian[] = "{$jRusakBerat} rusak berat";
            $rincian = !empty($bagian) ? ' (' . implode(', ', $bagian) . ')' : '';
            return ['perhatian', "Hanya {$laik}/{$total} {$it->satuan} laik pakai{$rincian}", $tidakLaik, $laik];
        }

        // PERHATIAN: kondisi dominan rusak ringan.
        if ($it->kondisi === 'rusak_ringan' || $jRusakRingan > 0) {
            $qty = $jRusakRingan > 0 ? $jRusakRingan : 1;
            return ['perhatian', "{$qty} {$it->satuan} rusak ringan", $qty, $laik];
        }

        // PERHATIAN: stok menipis (<= 25%).
        if ($total > 0 && ($tersedia / $total) <= 0.25) {
            return ['perhatian', "Stok menipis ({$tersedia}/{$total} {$it->satuan})", max(1, $total - $tersedia), $laik];
        }

        return ['', '', 0, $laik];
    }

    /**
     * Analisis otomatis ketersediaan stok & kerusakan (item rusak, habis, menipis, kedaluwarsa, BA kerusakan, ceklist).
     */
    protected function buildAnalisisStok(array $context): array
    {
        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);

        $items = $q->get([
            'id', 'kode_item', 'nama_item', 'jenis', 'satuan',
            'stok_total', 'stok_tersedia', 'jumlah_baik', 'jumlah_rusak_ringan',
            'jumlah_rusak_berat', 'jumlah_kedaluwarsa', 'kondisi', 'ruang_lab_nama', 'tgl_kedaluwarsa'
        ]);

        $kritis = [];
        $perluPerhatian = [];
        $kadaluwarsa = [];

        foreach ($items as $it) {
            $kadaluwarsaJml = (float) ($it->jumlah_kedaluwarsa ?? 0);
            $tersedia       = (float) $it->stok_tersedia;
            $total          = (float) $it->stok_total;

            [$kategori, $ringkasan, $selisih, $laik] = $this->evaluasiKesehatanItem($it);

            if ($kategori !== '') {
                $it->ringkasan_masalah = $ringkasan;
                $it->rekomendasi_selisih = $selisih;

                if ($kategori === 'kritis') {
                    $kritis[] = $it;
                } else {
                    $perluPerhatian[] = $it;
                }
            }

            // Kategori Kedaluwarsa: ada jumlah kedaluwarsa atau tanggal expired sudah lewat / mendekati
            $isExp = false;
            if ($it->kondisi === 'kedaluwarsa' || $kadaluwarsaJml > 0) {
                $isExp = true;
            } elseif (!empty($it->tgl_kedaluwarsa)) {
                try {
                    $exp = \Carbon\Carbon::parse($it->tgl_kedaluwarsa);
                    if ($exp->isPast() || $exp->diffInDays(now(), false) >= -60) {
                        $isExp = true;
                    }
                } catch (\Throwable $e) {}
            }

            if ($isExp) {
                $it->ringkasan_masalah = $kadaluwarsaJml > 0
                    ? "{$kadaluwarsaJml} {$it->satuan} kedaluwarsa"
                    : (!empty($it->tgl_kedaluwarsa) ? \Carbon\Carbon::parse($it->tgl_kedaluwarsa)->format('d/m/Y') : 'Kedaluwarsa');
                $it->rekomendasi_selisih = $kadaluwarsaJml > 0 ? $kadaluwarsaJml : max(1, $total > 0 ? $total : 1);
                $kadaluwarsa[] = $it;
            }
        }

        // Temuan dari Berita Acara Kerusakan aktif
        $baQuery = DB::table('laboran_berita_acara_kerusakan as ba')
            ->whereIn('ba.status', ['diajukan', 'disetujui'])
            ->select('ba.id', 'ba.nomor_ba', 'ba.nama_alat', 'ba.jumlah_rusak', 'ba.satuan', 'ba.tingkat_kerusakan', 'ba.penyebab', 'ba.jurusan_id');
        LaboranScope::apply($baQuery, 'ba.jurusan_id', $context);
        $baList = $baQuery->orderByDesc('ba.id')->limit(10)->get();

        // Temuan dari Ceklist Pemeriksaan
        $ceklistQuery = DB::table('laboran_pemeriksaan_item as pi')
            ->join('laboran_pemeriksaan as p', 'pi.pemeriksaan_id', '=', 'p.id')
            ->whereIn('pi.hasil', ['tidak_sesuai', 'tidak_ada'])
            ->select('pi.id', 'pi.nama_item', 'pi.hasil', 'pi.keterangan', 'pi.tindak_lanjut', 'p.ruang_lab_nama', 'p.jurusan_id');
        LaboranScope::apply($ceklistQuery, 'p.jurusan_id', $context);
        $ceklistList = $ceklistQuery->orderByDesc('pi.id')->limit(10)->get();

        return [
            'total_item'         => $items->count(),
            'kritis'             => array_slice($kritis, 0, 10),
            'perlu_perhatian'    => array_slice($perluPerhatian, 0, 10),
            'kadaluwarsa'        => array_slice($kadaluwarsa, 0, 10),
            'jumlah_kritis'      => count($kritis),
            'jumlah_perhatian'   => count($perluPerhatian),
            'jumlah_kadaluwarsa' => count($kadaluwarsa),
            'ba_kerusakan'       => $baList,
            'jumlah_ba'          => $baList->count(),
            'ceklist_temuan'     => $ceklistList,
            'jumlah_ceklist'     => $ceklistList->count(),
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
     * Jalankan analisis kebutuhan otomatis dari kondisi fisik stok nyata, kerusakan alat, dan temuan pemeriksaan.
     */
    public function jalankanAnalisis(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $countNew = 0;
        $countUpdated = 0;

        // 1. ANALISIS DARI INVENTARIS ALAT & BAHAN
        $q = DB::table('laboran_bahan_alat');
        LaboranScope::apply($q, 'jurusan_id', $context);
        $items = $q->get();

        foreach ($items as $it) {
            $total = max(1, (float) $it->stok_total);
            $tersedia = (float) $it->stok_tersedia;

            [$kategori, $ringkasan, $selisihKategori, $laik] = $this->evaluasiKesehatanItem($it);

            $perluUsulan = $kategori !== '';
            $prioritas   = $kategori === 'kritis' ? 'kritis' : ($kategori === 'perhatian' ? 'tinggi' : 'sedang');
            $sumber      = str_contains($ringkasan, 'rusak') ? 'kerusakan' : 'praktik';
            $alasan      = $perluUsulan ? [$ringkasan . '. Memerlukan tindak lanjut pengadaan/perbaikan.'] : [];
            $kebutuhan   = $total;
            $tersediaJml = $laik;
            $selisih     = $selisihKategori;

            // Cek kedaluwarsa (lapisan prioritas di atas evaluasi kesehatan)
            $jKedaluwarsa = (float) ($it->jumlah_kedaluwarsa ?? 0);
            $isExp = false;
            if ($it->kondisi === 'kedaluwarsa' || $jKedaluwarsa > 0) {
                $isExp = true;
            } elseif (!empty($it->tgl_kedaluwarsa)) {
                try {
                    $exp = \Carbon\Carbon::parse($it->tgl_kedaluwarsa);
                    if ($exp->isPast() || $exp->diffInDays(now(), false) >= -60) {
                        $isExp = true;
                    }
                } catch (\Throwable $e) {}
            }

            if ($isExp) {
                $perluUsulan = true;
                if ($prioritas !== 'kritis') $prioritas = 'tinggi';
                $sumber = 'praktik';
                $qtyExp = $jKedaluwarsa > 0 ? $jKedaluwarsa : $total;
                $selisih = max($selisih, $qtyExp);
                $alasan[] = "Bahan telah melewati batas masa kedaluwarsa. Membutuhkan pengadaan bahan baru.";
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

                $selisihFinal = max(1, (float) $selisih);

                if ($existing) {
                    DB::table('laboran_kebutuhan')->where('id', $existing->id)->update([
                        'jumlah_kebutuhan'   => max((float)$existing->jumlah_kebutuhan, $kebutuhan),
                        'jumlah_tersedia'    => $tersediaJml,
                        'selisih'            => $selisihFinal,
                        'prioritas'          => $prioritas,
                        'sumber_analisis'    => $sumber,
                        'analisis_kebutuhan' => implode(' ', $alasan) . ' [Analisis Otomatis Inventaris]',
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
                        'jumlah_kebutuhan'   => $kebutuhan,
                        'jumlah_tersedia'    => $tersediaJml,
                        'satuan'             => $it->satuan ?: 'unit',
                        'selisih'            => $selisihFinal,
                        'prioritas'          => $prioritas,
                        'sumber_analisis'    => $sumber,
                        'analisis_kebutuhan' => implode(' ', $alasan) . ' [Analisis Otomatis Inventaris]',
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

        // 2. ANALISIS DARI BERITA ACARA KERUSAKAN ALAT
        $baQuery = DB::table('laboran_berita_acara_kerusakan')
            ->whereIn('status', ['diajukan', 'disetujui']);
        LaboranScope::apply($baQuery, 'jurusan_id', $context);
        $baList = $baQuery->get();

        foreach ($baList as $ba) {
            $existing = DB::table('laboran_kebutuhan')
                ->where('nama_item', $ba->nama_alat)
                ->whereIn('status', ['draf', 'diverifikasi', 'disetujui'])
                ->first();

            if (!$existing) {
                $jmlRusak = max(1, (float) $ba->jumlah_rusak);
                $prio = in_array($ba->tingkat_kerusakan, ['berat', 'total'], true) ? 'kritis' : 'tinggi';

                DB::table('laboran_kebutuhan')->insert([
                    'jurusan_id'         => $ba->jurusan_id ?: LaboranScope::jurusanForWrite(null, $context),
                    'ruang_lab_nama'     => null,
                    'nama_item'          => $ba->nama_alat,
                    'jenis'              => 'alat',
                    'jumlah_kebutuhan'   => $jmlRusak,
                    'jumlah_tersedia'    => 0,
                    'satuan'             => $ba->satuan ?: 'unit',
                    'selisih'            => $jmlRusak,
                    'prioritas'          => $prio,
                    'sumber_analisis'    => 'kerusakan',
                    'analisis_kebutuhan' => "Berdasarkan Berita Acara Kerusakan No. {$ba->nomor_ba}: {$jmlRusak} {$ba->satuan} rusak ({$ba->tingkat_kerusakan}). Penyebab: " . ($ba->penyebab ?: 'Kerusakan saat praktikum.') . " [Analisis Otomatis BA Kerusakan]",
                    'status'             => 'diverifikasi',
                    'tanggal_input'      => now()->toDateString(),
                    'dibuat_oleh_ptk_id' => $context['ptk_id'],
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
                $countNew++;
            }
        }

        // 3. ANALISIS DARI TEMUAN PEMERIKSAAN CEKLIST
        $ceklistQuery = DB::table('laboran_pemeriksaan_item as pi')
            ->join('laboran_pemeriksaan as p', 'pi.pemeriksaan_id', '=', 'p.id')
            ->whereIn('pi.hasil', ['tidak_sesuai', 'tidak_ada'])
            ->select('pi.*', 'p.ruang_lab_nama', 'p.jurusan_id', 'p.nomor_pemeriksaan');
        LaboranScope::apply($ceklistQuery, 'p.jurusan_id', $context);
        $ceklistList = $ceklistQuery->get();

        foreach ($ceklistList as $ck) {
            $existing = DB::table('laboran_kebutuhan')
                ->where('nama_item', $ck->nama_item)
                ->whereIn('status', ['draf', 'diverifikasi', 'disetujui'])
                ->first();

            if (!$existing) {
                $prio = $ck->hasil === 'tidak_ada' ? 'kritis' : 'sedang';

                DB::table('laboran_kebutuhan')->insert([
                    'jurusan_id'         => $ck->jurusan_id ?: LaboranScope::jurusanForWrite(null, $context),
                    'ruang_lab_nama'     => $ck->ruang_lab_nama,
                    'nama_item'          => $ck->nama_item,
                    'jenis'              => 'alat',
                    'jumlah_kebutuhan'   => 1,
                    'jumlah_tersedia'    => 0,
                    'satuan'             => 'unit',
                    'selisih'            => 1,
                    'prioritas'          => $prio,
                    'sumber_analisis'    => 'praktik',
                    'analisis_kebutuhan' => "Temuan pemeriksaan berkala (" . ($ck->hasil === 'tidak_ada' ? 'alat hilang/tidak ditemukan' : 'kondisi tidak sesuai standar kelayakan') . ") di {$ck->ruang_lab_nama}. Catatan: " . ($ck->keterangan ?: ($ck->tindak_lanjut ?: '-')) . " [Analisis Otomatis Ceklist]",
                    'status'             => 'diverifikasi',
                    'tanggal_input'      => now()->toDateString(),
                    'dibuat_oleh_ptk_id' => $context['ptk_id'],
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
                $countNew++;
            }
        }

        $totalProcessed = $countNew + $countUpdated;
        $msg = $totalProcessed > 0
            ? "Analisis kebutuhan berhasil dijalankan. {$countNew} item kebutuhan baru berhasil ditambahkan dan {$countUpdated} item diperbarui berdasarkan data inventaris, berita acara kerusakan, dan ceklist pemeriksaan."
            : "Analisis kebutuhan selesai. Semua alat & bahan laboratorium saat ini dalam kondisi lengkap, laik pakai, dan mencukupi.";

        return $this->respond($request, true, $msg, 200, [
            'count_new'     => $countNew,
            'count_updated' => $countUpdated,
        ]);
    }
}
