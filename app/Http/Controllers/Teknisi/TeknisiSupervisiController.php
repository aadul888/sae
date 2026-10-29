<?php

namespace App\Http\Controllers\Teknisi;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Penilaian SUPERVISI Teknisi & IT.
 *
 * Lembar observasi supervisi memuat 5 indikator tupoksi. Untuk tiap indikator
 * supervisor memberi skor 1–4 beserta catatan/rekomendasi. Nilai akhir dihitung
 * dari rata-rata skor tiap indikator, lalu direkap sebagai bukti fisik kelima
 * indikator yang diambil langsung dari data modul (bukan input ulang).
 */
class TeknisiSupervisiController extends TeknisiBaseController
{
    /**
     * Lima indikator tupoksi sesuai lembar observasi supervisi teknisi.
     * Kunci => [nomor, indikator, rincian bukti fisik]
     */
    public const INDIKATOR = [
        1 => [
            'indikator' => 'Administrasi & Jadwal Pembagian Area Tugas Kebersihan',
            'bukti'     => "1. Jadwal Area Tugas Kebersihan dan Pengolahan Sampah, Dokumentasi Kegiatan.\n"
                . '2. Menerima Laporan Siswa Tidak Disiplin dari Tim Keamanan.',
            'tabel'     => 'teknisi_area_tugas',
        ],
        2 => [
            'indikator' => 'Kebersihan Lingkungan & Ruang Kerja',
            'bukti'     => "1. Kebersihan Ruang Kepala Sekolah, Ruang Wakasek Kurikulum, Ruang Operator, "
                . "Ruang Bendahara, Ruang Rapat, Dapur & WC, Teras Halaman Ruang Kepala Sekolah.\n"
                . "2. Kebersihan Ruang TU, Ruang Guru, Ruang Wakasek Kesiswaan, Ruang Humas/Hubin, "
                . "Jamban TU dan Guru, dan Teras Halaman Ruang TU, Ruang Wakasek Kesiswaan, "
                . "Ruang Humas/Hubin, Koperasi, Ruang Guru.\n"
                . "3. Kebersihan Lingkungan Lapangan Upacara, Gor Sekolah, Ruang Aula Balarea, "
                . "Ruang Sarana dan Prasarana.\n"
                . '4. Kebersihan Jamban Siswa.',
            'tabel'     => 'teknisi_kebersihan_ruang',
        ],
        3 => [
            'indikator' => 'Kebersihan Lingkungan Ruang Rapat',
            'bukti'     => 'Kebersihan Teras, Halaman, Ruang Rapat.',
            'tabel'     => 'teknisi_kebersihan_rapat',
        ],
        4 => [
            'indikator' => 'Laporan Sarana Kegiatan Rapat',
            'bukti'     => 'Dokumen Rekapitulasi dan Laporan Kelengkapan Sarana Rapat.',
            'tabel'     => 'teknisi_sarana_rapat',
        ],
        5 => [
            'indikator' => 'Pengarsipan & Laporan Perbaikan Sarana Prasarana',
            'bukti'     => 'Jurnal Kegiatan Perbaikan dan Kebutuhan Sarana Prasarana.',
            'tabel'     => 'teknisi_jurnal_perbaikan',
        ],
    ];

    public const STATUS = [
        'draf'  => 'Draf',
        'final' => 'Final',
    ];

    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $search = trim((string) $request->query('q', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $dari   = trim((string) $request->query('dari', ''));
        $sampai = trim((string) $request->query('sampai', ''));
        if ($dari === '' && $sampai === '') {
            $dari   = now()->startOfYear()->toDateString();
            $sampai = now()->endOfYear()->toDateString();
        }

        $query = DB::table('teknisi_supervisi as s')->select('s.*');
        // Teknisi melihat lembar supervisi atas dirinya; koordinator melihat semua.
        $this->scopeOwned($query, $context, 's', 'teknisi_ptk_id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('s.teknisi_nama', 'like', "%{$search}%")
                  ->orWhere('s.supervisor_nama', 'like', "%{$search}%")
                  ->orWhere('s.nomor_supervisi', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') $query->where('s.status', $statusFilter);
        if ($dari !== '')   $query->where('s.tanggal_supervisi', '>=', $dari);
        if ($sampai !== '') $query->where('s.tanggal_supervisi', '<=', $sampai);

        $statBase = DB::table('teknisi_supervisi');
        $this->scopeOwned($statBase, $context, null, 'teknisi_ptk_id');
        if ($dari !== '')   $statBase->where('tanggal_supervisi', '>=', $dari);
        if ($sampai !== '') $statBase->where('tanggal_supervisi', '<=', $sampai);
        $statTotal   = (clone $statBase)->count();
        $statFinal   = (clone $statBase)->where('status', 'final')->count();
        $statNilai   = (clone $statBase)->whereNotNull('nilai_akhir')->avg('nilai_akhir');
        $statBaik    = (clone $statBase)->whereIn('kualifikasi', ['Sangat Baik', 'Baik'])->count();

        $perPage = (int) $request->query('perPage', $request->query('per_page', 15));
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $list = $query->orderByDesc('s.tanggal_supervisi')->orderByDesc('s.id')
            ->paginate($perPage)->withQueryString();

        // Item per lembar supervisi (untuk tombol ubah / modal edit).
        $itemsBySupervisi = collect();
        $ids = collect($list->items())->pluck('id')->all();
        if (!empty($ids)) {
            $itemsBySupervisi = DB::table('teknisi_supervisi_item')
                ->whereIn('supervisi_id', $ids)
                ->orderBy('no_indikator')
                ->get()
                ->groupBy('supervisi_id');
        }

        return view('dashboard.teknisi.supervisi', array_merge(
            compact('list', 'search', 'statusFilter', 'dari', 'sampai', 'perPage',
                'statTotal', 'statFinal', 'statNilai', 'statBaik', 'itemsBySupervisi'),
            $this->abilities(),
            [
                'indikatorList'  => self::INDIKATOR,
                'statusList'     => self::STATUS,
                'teknisiOptions' => $this->teknisiOptions(),
            ]
        ));
    }

    public function store(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $data = $this->validated($request);
        $items = $this->susunItem($data);

        [$rata, $kualifikasi] = $this->hitungNilai($items);

        DB::transaction(function () use ($data, $items, $context, $rata, $kualifikasi) {
            $id = DB::table('teknisi_supervisi')->insertGetId([
                'nomor_supervisi'    => $this->generateNomor('teknisi_supervisi', 'nomor_supervisi', 'SPV'),
                'tanggal_supervisi'  => $data['tanggal_supervisi'],
                'periode_label'      => $data['periode_label'] ?? null,
                'tahun_ajaran'       => $data['tahun_ajaran'] ?? null,
                'semester'           => $data['semester'] ?? null,
                'teknisi_ptk_id'     => $data['teknisi_ptk_id'] ?? null,
                'teknisi_nama'       => $data['teknisi_nama'],
                'supervisor_ptk_id'  => $data['supervisor_ptk_id'] ?? null,
                'supervisor_nama'    => $data['supervisor_nama'],
                'supervisor_jabatan' => $data['supervisor_jabatan'] ?? null,
                'nilai_rata'         => $rata,
                'nilai_akhir'        => $rata,
                'kualifikasi'        => $kualifikasi,
                'catatan_umum'       => $data['catatan_umum'] ?? null,
                'rekomendasi'        => $data['rekomendasi'] ?? null,
                'status'             => $data['status'] ?? 'draf',
                'petugas_ptk_id'     => $context['ptk_id'],
                'petugas_nama'       => $context['nama'],
                'user_id'            => $context['user_id'],
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            foreach ($items as $it) {
                DB::table('teknisi_supervisi_item')->insert([
                    'supervisi_id'      => $id,
                    'no_indikator'      => $it['no_indikator'],
                    'indikator_tupoksi' => $it['indikator_tupoksi'],
                    'bukti_fisik'       => $it['bukti_fisik'],
                    'skor'              => $it['skor'],
                    'catatan_supervisor' => $it['catatan_supervisor'],
                    'urutan'            => $it['no_indikator'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        });

        return $this->respond($request, true, 'Lembar supervisi berhasil disimpan.');
    }

    public function update(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_supervisi', $id);
        if (!$row) return $this->respond($request, false, 'Data supervisi tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context)) {
            return $this->respond($request, false, 'Anda hanya dapat mengubah supervisi yang Anda susun.', 403);
        }

        $data = $this->validated($request);
        $items = $this->susunItem($data);
        [$rata, $kualifikasi] = $this->hitungNilai($items);

        DB::transaction(function () use ($id, $data, $items, $rata, $kualifikasi) {
            DB::table('teknisi_supervisi')->where('id', $id)->update([
                'tanggal_supervisi'  => $data['tanggal_supervisi'],
                'periode_label'      => $data['periode_label'] ?? null,
                'tahun_ajaran'       => $data['tahun_ajaran'] ?? null,
                'semester'           => $data['semester'] ?? null,
                'teknisi_ptk_id'     => $data['teknisi_ptk_id'] ?? null,
                'teknisi_nama'       => $data['teknisi_nama'],
                'supervisor_ptk_id'  => $data['supervisor_ptk_id'] ?? null,
                'supervisor_nama'    => $data['supervisor_nama'],
                'supervisor_jabatan' => $data['supervisor_jabatan'] ?? null,
                'nilai_rata'         => $rata,
                'nilai_akhir'        => $rata,
                'kualifikasi'        => $kualifikasi,
                'catatan_umum'       => $data['catatan_umum'] ?? null,
                'rekomendasi'        => $data['rekomendasi'] ?? null,
                'status'             => $data['status'] ?? $row->status,
                'updated_at'         => now(),
            ]);

            DB::table('teknisi_supervisi_item')->where('supervisi_id', $id)->delete();
            foreach ($items as $it) {
                DB::table('teknisi_supervisi_item')->insert([
                    'supervisi_id'      => $id,
                    'no_indikator'      => $it['no_indikator'],
                    'indikator_tupoksi' => $it['indikator_tupoksi'],
                    'bukti_fisik'       => $it['bukti_fisik'],
                    'skor'              => $it['skor'],
                    'catatan_supervisor' => $it['catatan_supervisor'],
                    'urutan'            => $it['no_indikator'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }
        });

        return $this->respond($request, true, 'Lembar supervisi berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $row = $this->findRow('teknisi_supervisi', $id);
        if (!$row) return $this->respond($request, false, 'Data supervisi tidak ditemukan.', 404);
        if (!$this->ownsRow($row, $context, 'teknisi_ptk_id')) {
            return $this->respond($request, false, 'Anda hanya dapat menghapus lembar supervisi milik sendiri.', 403);
        }

        DB::transaction(function () use ($id) {
            DB::table('teknisi_supervisi_item')->where('supervisi_id', $id)->delete();
            DB::table('teknisi_supervisi')->where('id', $id)->delete();
        });

        return $this->respond($request, true, 'Lembar supervisi berhasil dihapus.');
    }

    /**
     * Cetak lembar observasi supervisi (A4) lengkap dengan bukti fisik otomatis.
     */
    public function cetak(Request $request, $id)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $supervisi = DB::table('teknisi_supervisi')->where('id', $id)->first();
        if (!$supervisi) {
            abort(404, 'Data supervisi tidak ditemukan.');
        }
        if (!$this->ownsRow($supervisi, $context, 'teknisi_ptk_id')) {
            abort(403, 'Anda hanya dapat mencetak lembar supervisi milik sendiri.');
        }

        $items = DB::table('teknisi_supervisi_item')
            ->where('supervisi_id', $id)
            ->orderBy('no_indikator')
            ->get();

        // Bila item belum tersimpan, bangun dari definisi indikator.
        if ($items->isEmpty()) {
            $items = collect($this->susunItem([
                'skor'    => [],
                'catatan' => [],
            ]))->map(fn ($it) => (object) $it);
        }

        return view('dashboard.teknisi.supervisi-cetak', [
            'supervisi'      => $supervisi,
            'items'          => $items,
            'sekolah'        => \App\Models\Sekolah::first(),
            'dicetakPada'    => now(),
            'indikatorList'  => self::INDIKATOR,
        ]);
    }

    /**
     * Susun daftar item indikator dari input form.
     * Selalu memuat 5 indikator agar lembar observasi lengkap.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function susunItem(array $data): array
    {
        $items = [];
        foreach (self::INDIKATOR as $no => $def) {
            $skor = $data['skor'][$no] ?? null;
            $items[] = [
                'no_indikator'      => $no,
                'indikator_tupoksi' => $def['indikator'],
                'bukti_fisik'       => $def['bukti'],
                'skor'              => $skor !== null && $skor !== '' ? (int) $skor : null,
                'catatan_supervisor' => $data['catatan'][$no] ?? null,
            ];
        }

        return $items;
    }

    /**
     * Hitung rata-rata skor & kualifikasi (skala 1–4).
     *
     * @return array{0: ?float, 1: ?string}
     */
    protected function hitungNilai(array $items): array
    {
        $skor = array_filter(array_column($items, 'skor'), fn ($s) => $s !== null);
        if (empty($skor)) {
            return [null, null];
        }

        $rata = round(array_sum($skor) / count($skor), 2);

        $kualifikasi = match (true) {
            $rata >= 3.5 => 'Sangat Baik',
            $rata >= 2.75 => 'Baik',
            $rata >= 2.0 => 'Cukup',
            default => 'Perlu Pembinaan',
        };

        return [$rata, $kualifikasi];
    }

    /**
     * Rekapitulasi bukti fisik tiap indikator dari data modul (untuk kartu & cetak).
     *
     * @return array<int, object{no:int, indikator:string, jumlah:int, satuan:string, catatan:string}>
     */
    public function rekapBukti(array $context = []): array
    {
        $rekap = [];
        $jumlahFn = function (string $tabel, ?callable $filter = null) use ($context): int {
            if (!\App\Support\Table::exists($tabel)) {
                return 0;
            }
            $q = DB::table($tabel);
            // Cakupan per akun: hitungan bukti fisik mengikuti data yang terlihat.
            $this->scopeOwned($q, $context, null, $tabel === 'teknisi_supervisi' ? 'teknisi_ptk_id' : 'petugas_ptk_id');
            if ($filter) $filter($q);
            return (int) $q->count();
        };

        foreach (self::INDIKATOR as $no => $def) {
            $tabel = $def['tabel'];
            $rekap[$no] = (object) [
                'no'        => $no,
                'indikator' => $def['indikator'],
                'jumlah'    => $jumlahFn($tabel),
                'satuan'    => 'catatan',
                'catatan'   => $jumlahFn($tabel, fn ($q) => $q->where('status', 'terverifikasi')) . ' terverifikasi',
            ];
        }

        return $rekap;
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'tanggal_supervisi'  => 'required|date',
            'periode_label'      => 'nullable|string|max:60',
            'tahun_ajaran'       => 'nullable|string|max:20',
            'semester'           => 'nullable|string|max:10',
            'teknisi_ptk_id'     => 'nullable|string|max:50',
            'teknisi_nama'       => 'required|string|max:150',
            'supervisor_ptk_id'  => 'nullable|string|max:50',
            'supervisor_nama'    => 'required|string|max:150',
            'supervisor_jabatan' => 'nullable|string|max:100',
            'skor'               => 'nullable|array',
            'skor.*'             => 'nullable|integer|min:1|max:4',
            'catatan'            => 'nullable|array',
            'catatan.*'          => 'nullable|string|max:2000',
            'catatan_umum'       => 'nullable|string|max:2000',
            'rekomendasi'        => 'nullable|string|max:2000',
            'status'             => 'nullable|in:draf,final',
        ], [
            'tanggal_supervisi.required' => 'Tanggal supervisi wajib diisi.',
            'teknisi_nama.required'      => 'Nama teknisi wajib diisi.',
            'supervisor_nama.required'   => 'Nama supervisor wajib diisi.',
            'skor.*.min'                 => 'Skor minimal 1.',
            'skor.*.max'                 => 'Skor maksimal 4.',
        ]);
    }
}
