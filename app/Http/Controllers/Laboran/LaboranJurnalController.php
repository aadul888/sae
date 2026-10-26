<?php

namespace App\Http\Controllers\Laboran;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Modul JURNAL KEGIATAN HARIAN LABORAN.
 *
 * Indikator Tupoksi #8 — bukti fisik: Jurnal Kegiatan.
 * Kategori kegiatan dipetakan langsung ke 9 indikator tupoksi sehingga
 * rekapitulasi dapat dipakai sebagai bukti supervisi.
 */
class LaboranJurnalController extends LaboranBaseController
{
    /**
     * Kategori jurnal dipetakan ke nomor indikator tupoksi.
     * Kunci => [label, nomor indikator]
     */
    public const KATEGORI = [
        'administrasi'      => ['Administrasi Jurusan', 1],
        'penyiapan'         => ['Penyiapan Alat & Bahan', 2],
        'pemeliharaan'      => ['Pemeliharaan & Penataan (K3)', 3],
        'inventarisasi'     => ['Inventarisasi Alat & Bahan', 4],
        'pemeriksaan'       => ['Pemeriksaan Kelayakan Alat', 5],
        'kebutuhan'         => ['Pendataan Kebutuhan', 6],
        'pengarsipan'       => ['Pengarsipan Dokumen', 7],
        'pelaporan'         => ['Penyusunan Laporan', 8],
        'kebersihan'        => ['Kebersihan Lingkungan Produktif', 9],
        'pelayanan_praktik' => ['Pelayanan Praktik Pembelajaran', 2],
        'lainnya'           => ['Kegiatan Lain', 0],
    ];

    public const SHIFT = [
        'pagi'  => 'Pagi',
        'siang' => 'Siang',
        'penuh' => 'Penuh Hari',
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

        $search        = trim((string) $request->query('q', ''));
        $kategoriFilter = trim((string) $request->query('kategori', ''));
        $dari          = trim((string) $request->query('dari', ''));
        $sampai        = trim((string) $request->query('sampai', ''));

        // Default: bulan berjalan
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfMonth()->toDateString();
            $sampai = now()->endOfMonth()->toDateString();
        }

        $query = DB::table('laboran_jurnal as j')
            ->leftJoin('jurusan_meta as jm', 'j.jurusan_id', '=', 'jm.jurusan_id')
            ->select('j.*', 'jm.nama_jurusan', 'jm.singkatan');

        LaboranScope::apply($query, 'j.jurusan_id', $context);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('j.kegiatan', 'like', "%{$search}%")
                  ->orWhere('j.uraian', 'like', "%{$search}%")
                  ->orWhere('j.hasil', 'like', "%{$search}%")
                  ->orWhere('j.guru_terkait', 'like', "%{$search}%");
            });
        }
        if ($kategoriFilter !== '') $query->where('j.kategori', $kategoriFilter);
        if ($dari !== '')   $query->where('j.tanggal', '>=', $dari);
        if ($sampai !== '') $query->where('j.tanggal', '<=', $sampai);

        // Statistik dalam rentang
        $statBase = DB::table('laboran_jurnal');
        LaboranScope::apply($statBase, 'jurusan_id', $context);
        if ($dari !== '')   $statBase->where('tanggal', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal', '<=', $sampai);

        $statTotal    = (clone $statBase)->count();
        $statMenit    = (int) (clone $statBase)->sum('durasi_menit');
        $statHari     = (clone $statBase)->distinct('tanggal')->count('tanggal');
        $statKategori = (clone $statBase)->distinct('kategori')->count('kategori');

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('j.tanggal')
            ->orderBy('j.jam_mulai')
            ->orderByDesc('j.id')
            ->paginate($perPage)
            ->withQueryString();

        // Rekap per kategori (untuk grafik & bukti tupoksi)
        $rekapKategoriQuery = DB::table('laboran_jurnal')
            ->select('kategori', DB::raw('count(*) as jumlah'), DB::raw('COALESCE(SUM(durasi_menit),0) as menit'));
        LaboranScope::apply($rekapKategoriQuery, 'jurusan_id', $context);
        if ($dari !== '')   $rekapKategoriQuery->where('tanggal', '>=', $dari);
        if ($sampai !== '') $rekapKategoriQuery->where('tanggal', '<=', $sampai);
        $rekapKategori = $rekapKategoriQuery->groupBy('kategori')->orderByDesc('jumlah')->get();

        return view('dashboard.laboran.jurnal', array_merge(
            compact('list', 'search', 'kategoriFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statMenit', 'statHari', 'statKategori', 'rekapKategori'),
            $this->abilities(),
            [
                'context'       => $context,
                'scopeBadge'    => LaboranScope::badge($context),
                'lokasiOptions' => $this->lokasiOptions(),
                'kategoriList'  => self::KATEGORI,
                'shiftList'     => self::SHIFT,
                'statusList'    => self::STATUS,
                'jurusanOptions' => LaboranScope::jurusanOptions($context),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);
        $menit = $this->hitungMenit($data['jam_mulai'] ?? null, $data['jam_selesai'] ?? null);

        DB::table('laboran_jurnal')->insert([
            'jurusan_id'     => LaboranScope::jurusanForWrite($data['jurusan_id'] ?? null, $context),
            'tanggal'        => $data['tanggal'],
            'ruang_lab_nama' => $data['ruang_lab_nama'] ?? null,
            'shift'          => $data['shift'] ?? 'pagi',
            'kegiatan'       => trim($data['kegiatan']),
            'kategori'       => $data['kategori'] ?? 'lainnya',
            'jam_mulai'      => $data['jam_mulai'] ?? null,
            'jam_selesai'    => $data['jam_selesai'] ?? null,
            'durasi_menit'   => $menit,
            'uraian'         => $data['uraian'] ?? null,
            'hasil'          => $data['hasil'] ?? null,
            'kendala'        => $data['kendala'] ?? null,
            'tindak_lanjut'  => $data['tindak_lanjut'] ?? null,
            'jumlah_siswa'   => $data['jumlah_siswa'] ?? null,
            'guru_terkait'   => $data['guru_terkait'] ?? null,
            'catatan'        => $data['catatan'] ?? null,
            'petugas_ptk_id' => $context['ptk_id'],
            'petugas_nama'   => $context['nama'],
            'status'         => $data['status'] ?? 'selesai',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->respond($request, true, 'Jurnal kegiatan berhasil dicatat.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_jurnal', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Jurnal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        $data = $this->validated($request);
        $menit = $this->hitungMenit($data['jam_mulai'] ?? null, $data['jam_selesai'] ?? null);

        DB::table('laboran_jurnal')->where('id', $id)->update([
            'tanggal'        => $data['tanggal'],
            'ruang_lab_nama' => $data['ruang_lab_nama'] ?? null,
            'shift'          => $data['shift'] ?? $row->shift,
            'kegiatan'       => trim($data['kegiatan']),
            'kategori'       => $data['kategori'] ?? $row->kategori,
            'jam_mulai'      => $data['jam_mulai'] ?? null,
            'jam_selesai'    => $data['jam_selesai'] ?? null,
            'durasi_menit'   => $menit,
            'uraian'         => $data['uraian'] ?? null,
            'hasil'          => $data['hasil'] ?? null,
            'kendala'        => $data['kendala'] ?? null,
            'tindak_lanjut'  => $data['tindak_lanjut'] ?? null,
            'jumlah_siswa'   => $data['jumlah_siswa'] ?? null,
            'guru_terkait'   => $data['guru_terkait'] ?? null,
            'catatan'        => $data['catatan'] ?? null,
            'status'         => $data['status'] ?? $row->status,
            'updated_at'     => now(),
        ]);

        return $this->respond($request, true, 'Jurnal kegiatan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findScoped('laboran_jurnal', $id, $context);
        if (!$row) {
            return $this->respond($request, false, 'Jurnal tidak ditemukan atau di luar cakupan jurusan Anda.', 404);
        }

        DB::table('laboran_jurnal')->where('id', $id)->delete();

        return $this->respond($request, true, 'Jurnal kegiatan berhasil dihapus.');
    }

    /**
     * Cetak Jurnal Kegiatan per periode (bukti fisik supervisi).
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $dari   = (string) $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = (string) $request->query('sampai', now()->endOfMonth()->toDateString());

        $query = DB::table('laboran_jurnal')
            ->whereBetween('tanggal', [$dari, $sampai]);
        LaboranScope::apply($query, 'jurusan_id', $context);
        $items = $query->orderBy('tanggal')->orderBy('jam_mulai')->get();

        $rekapKategori = $items->groupBy('kategori')->map(function ($g, $k) {
            $label = self::KATEGORI[$k][0] ?? ucfirst($k);
            $indikator = self::KATEGORI[$k][1] ?? 0;

            return (object) [
                'kategori'  => $k,
                'label'     => $label,
                'indikator' => $indikator,
                'jumlah'    => $g->count(),
                'menit'     => (int) $g->sum('durasi_menit'),
            ];
        })->sortByDesc('jumlah')->values();

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

        $totalMenit = (int) $items->sum('durasi_menit');

        return view('dashboard.laboran.jurnal-cetak', compact(
            'items', 'rekapKategori', 'jurusan', 'sekolah', 'sekolahMeta',
            'kepalaSekolah', 'kepalaLab', 'petugas', 'orientasi', 'range', 'totalMenit'
        ) + [
            'kategoriList' => self::KATEGORI,
            'shiftList'    => self::SHIFT,
            'statusList'   => self::STATUS,
        ]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal'        => 'required|date',
            'ruang_lab_nama' => 'nullable|string|max:100',
            'shift'          => 'nullable|in:pagi,siang,penuh',
            'kegiatan'       => 'required|string|max:255',
            'kategori'       => 'required|string|max:40',
            'jam_mulai'      => 'nullable|date_format:H:i',
            'jam_selesai'    => 'nullable|date_format:H:i',
            'uraian'         => 'nullable|string|max:2000',
            'hasil'          => 'nullable|string|max:1000',
            'kendala'        => 'nullable|string|max:1000',
            'tindak_lanjut'  => 'nullable|string|max:1000',
            'jumlah_siswa'   => 'nullable|integer|min:0|max:1000',
            'guru_terkait'   => 'nullable|string|max:150',
            'catatan'        => 'nullable|string|max:1000',
            'status'         => 'nullable|in:draf,selesai,terverifikasi',
            'jurusan_id'     => 'nullable|string|max:50',
        ], [
            'tanggal.required'  => 'Tanggal kegiatan wajib diisi.',
            'kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'kategori.required' => 'Kategori kegiatan wajib dipilih.',
        ]);
    }

    protected function hitungMenit(?string $mulai, ?string $selesai): int
    {
        if (!$mulai || !$selesai) {
            return 0;
        }

        try {
            $a = Carbon::createFromFormat('H:i', $mulai);
            $b = Carbon::createFromFormat('H:i', $selesai);
        } catch (\Throwable $e) {
            return 0;
        }

        $diff = (int) $a->diffInMinutes($b, false);

        return $diff > 0 ? $diff : 0;
    }
}
