<?php

namespace App\Http\Controllers\Laboran;

use App\Support\LaboranScope;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Laboratorium per Jurusan.
 *
 * Menyajikan ringkasan informatif: kondisi inventaris, kebutuhan, perawatan
 * jatuh tempo, peminjaman aktif, kerusakan, pemeriksaan, dan pengadaan —
 * seluruhnya dibatasi pada cakupan jurusan pengguna.
 */
class LaboranDashboardController extends LaboranBaseController
{
    public function index(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) return $res;

        $jurusanId = $context['jurusan_id'];

        // ---------------------------------------------------------------
        // Identitas jurusan
        // ---------------------------------------------------------------
        $jurusan = null;
        if ($jurusanId) {
            $jurusan = DB::table('jurusan_meta')->where('jurusan_id', $jurusanId)->first();
        }
        if (!$jurusan && $context['unrestricted']) {
            $jurusan = DB::table('jurusan_meta')->orderBy('nama_jurusan')->first();
        }

        // ---------------------------------------------------------------
        // 1. Inventaris
        // ---------------------------------------------------------------
        $invBase = DB::table('laboran_bahan_alat');
        LaboranScope::apply($invBase, 'jurusan_id', $context);
        $inventaris = $invBase->get();

        $totalItem    = $inventaris->count();
        $totalAlat    = $inventaris->where('jenis', 'alat')->count();
        $totalBahan   = $inventaris->where('jenis', 'bahan_habis_pakai')->count();
        $kondisiBaik  = $inventaris->where('kondisi', 'baik')->count();
        $kondisiRusak = $inventaris->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count();
        $kondisiKedaluwarsa = $inventaris->where('kondisi', 'kedaluwarsa')->count();

        $persenLaik = $totalItem > 0 ? round(($kondisiBaik / $totalItem) * 100, 1) : 0;

        // Stok menipis
        $stokMenipis = $inventaris->filter(function ($it) {
            $tersedia = (float) $it->stok_tersedia;
            $total    = (float) $it->stok_total;

            return $tersedia <= 0 || ($total > 0 && ($tersedia / max($total, 1)) <= 0.25);
        })->take(8)->values();

        // ---------------------------------------------------------------
        // 2. Praktik / jadwal
        // ---------------------------------------------------------------
        $jadwalBase = DB::table('laboran_jadwal_penggunaan');
        LaboranScope::apply($jadwalBase, 'jurusan_id', $context);

        $jadwalHariIni = (clone $jadwalBase)->where('tanggal', now()->toDateString())->count();
        $jadwalMinggu  = (clone $jadwalBase)
            ->whereBetween('tanggal', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->count();
        $jadwalBulan   = (clone $jadwalBase)
            ->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $jadwalTotal   = (clone $jadwalBase)->count();

        $jadwalTerdekat = (clone $jadwalBase)
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')
            ->orderBy('jam_mulai')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 3. Peminjaman
        // ---------------------------------------------------------------
        $pinjamBase = DB::table('laboran_peminjaman');
        LaboranScope::apply($pinjamBase, 'jurusan_id', $context);

        $pinjamAktif = (clone $pinjamBase)->whereIn('status', ['dipinjam', 'terlambat'])->count();
        $pinjamMenunggu = (clone $pinjamBase)->where('status', 'diajukan')->count();
        $pinjamTerlambat = (clone $pinjamBase)
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString())
            ->count();
        $pinjamTotal = (clone $pinjamBase)->count();

        $pinjamTerbaru = (clone $pinjamBase)
            ->whereIn('status', ['diajukan', 'dipinjam', 'terlambat'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 4. Kerusakan
        // ---------------------------------------------------------------
        $rusakBase = DB::table('laboran_berita_acara_kerusakan');
        LaboranScope::apply($rusakBase, 'jurusan_id', $context);

        $rusakTotal    = (clone $rusakBase)->count();
        $rusakBerat    = (clone $rusakBase)->whereIn('tingkat_kerusakan', ['berat', 'total'])->count();
        $rusakBulanIni = (clone $rusakBase)
            ->whereBetween('tanggal_kejadian', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $rusakEstimasi = (float) (clone $rusakBase)->sum('estimasi_biaya');

        $rusakTerbaru = (clone $rusakBase)->orderByDesc('tanggal_kejadian')->limit(6)->get();

        // ---------------------------------------------------------------
        // 5. Perawatan
        // ---------------------------------------------------------------
        $rawatBase = DB::table('laboran_perawatan');
        LaboranScope::apply($rawatBase, 'jurusan_id', $context);

        $rawatTotal      = (clone $rawatBase)->count();
        $rawatDijadwalkan = (clone $rawatBase)->where('status', 'dijadwalkan')->count();
        $rawatProses     = (clone $rawatBase)->where('status', 'proses')->count();
        $rawatSelesai    = (clone $rawatBase)->where('status', 'selesai')->count();

        $kartuBase = DB::table('laboran_kartu_perawatan');
        LaboranScope::apply($kartuBase, 'jurusan_id', $context);
        $kartuAktif = (clone $kartuBase)->where('status', 'aktif')->count();
        $kartuJatuhTempo = (clone $kartuBase)
            ->where('status', 'aktif')
            ->whereNotNull('jadwal_berikutnya')
            ->where('jadwal_berikutnya', '<=', now()->addDays(7)->toDateString())
            ->orderBy('jadwal_berikutnya')
            ->limit(6)
            ->get();

        // ---------------------------------------------------------------
        // 6. Pemeriksaan & Kebutuhan & Pengadaan
        // ---------------------------------------------------------------
        $periksaBase = DB::table('laboran_pemeriksaan');
        LaboranScope::apply($periksaBase, 'jurusan_id', $context);
        $periksaTotal = (clone $periksaBase)->count();
        $periksaBulanIni = (clone $periksaBase)
            ->whereBetween('tanggal_pemeriksaan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->count();
        $periksaTerakhir = (clone $periksaBase)->orderByDesc('tanggal_pemeriksaan')->first();

        $kebutuhanBase = DB::table('laboran_kebutuhan');
        LaboranScope::apply($kebutuhanBase, 'jurusan_id', $context);
        $kebutuhanTotal   = (clone $kebutuhanBase)->count();
        $kebutuhanKritis  = (clone $kebutuhanBase)->where('prioritas', 'kritis')->count();
        $kebutuhanDraf    = (clone $kebutuhanBase)->where('status', 'draf')->count();

        $kebutuhanPrioritas = (clone $kebutuhanBase)
            ->whereIn('prioritas', ['tinggi', 'kritis'])
            ->orderByRaw("CASE prioritas WHEN 'kritis' THEN 1 ELSE 2 END")
            ->orderByDesc('selisih')
            ->limit(6)
            ->get();

        $pengadaanBase = DB::table('laboran_usulan_pengadaan');
        LaboranScope::apply($pengadaanBase, 'jurusan_id', $context);
        $pengadaanTotal     = (clone $pengadaanBase)->count();
        $pengadaanDiajukan  = (clone $pengadaanBase)->whereIn('status', ['diajukan', 'diverifikasi'])->count();
        $pengadaanDisetujui = (clone $pengadaanBase)->whereIn('status', ['disetujui', 'direalisasi'])->count();
        $pengadaanNilai     = (float) (clone $pengadaanBase)->sum('total_estimasi_biaya');

        // ---------------------------------------------------------------
        // 7. Tren 6 bulan terakhir (praktik & kerusakan)
        // ---------------------------------------------------------------
        $trenBulanan = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->copy()->subMonths($i);
            $awal = $m->copy()->startOfMonth()->toDateString();
            $akhir = $m->copy()->endOfMonth()->toDateString();

            $qJ = DB::table('laboran_jadwal_penggunaan')->whereBetween('tanggal', [$awal, $akhir]);
            LaboranScope::apply($qJ, 'jurusan_id', $context);

            $qR = DB::table('laboran_berita_acara_kerusakan')->whereBetween('tanggal_kejadian', [$awal, $akhir]);
            LaboranScope::apply($qR, 'jurusan_id', $context);

            $trenBulanan[] = [
                'label'   => $m->translatedFormat('M'),
                'praktik' => $qJ->count(),
                'rusak'   => $qR->count(),
            ];
        }

        $maxTren = max(1, max(array_column($trenBulanan, 'praktik') ?: [1]));

        // ---------------------------------------------------------------
        // 8. Sebaran per lokasi
        // ---------------------------------------------------------------
        $qLokasi = DB::table('laboran_bahan_alat')
            ->select('ruang_lab_nama', DB::raw('COUNT(*) as total'))
            ->whereNotNull('ruang_lab_nama')
            ->groupBy('ruang_lab_nama')
            ->orderByDesc('total')
            ->limit(6);
        LaboranScope::apply($qLokasi, 'jurusan_id', $context);
        $sebaranLokasi = $qLokasi->get();

        return view('dashboard.laboran.dashboard', array_merge(
            compact(
                'jurusan',

                'totalItem', 'totalAlat', 'totalBahan', 'kondisiBaik', 'kondisiRusak',
                'kondisiKedaluwarsa', 'persenLaik', 'stokMenipis',
                'jadwalHariIni', 'jadwalMinggu', 'jadwalBulan', 'jadwalTotal', 'jadwalTerdekat',
                'pinjamAktif', 'pinjamMenunggu', 'pinjamTerlambat', 'pinjamTotal', 'pinjamTerbaru',
                'rusakTotal', 'rusakBerat', 'rusakBulanIni', 'rusakEstimasi', 'rusakTerbaru',
                'rawatTotal', 'rawatDijadwalkan', 'rawatProses', 'rawatSelesai', 'kartuAktif', 'kartuJatuhTempo',
                'periksaTotal', 'periksaBulanIni', 'periksaTerakhir',
                'kebutuhanTotal', 'kebutuhanKritis', 'kebutuhanDraf', 'kebutuhanPrioritas',
                'pengadaanTotal', 'pengadaanDiajukan', 'pengadaanDisetujui', 'pengadaanNilai',
                'trenBulanan', 'maxTren', 'sebaranLokasi'
            ),
            $this->abilities(),
            [
                'context'       => $context,
                'scopeContext'  => $context,
                'scopeBadge'    => LaboranScope::badge($context),
            ]
        ));
    }

    /**
     * Rekapitulasi Supervisi Laboratorium (dokumen cetak A4).
     *
     * Mode `ringkasan` → kartu angka kunci + tabel rekap 9 indikator tupoksi.
     * Mode `lengkap`   → ditambah daftar catatan yang perlu tindak lanjut
     *                    (stok menipis, peminjaman terlambat, kartu jatuh
     *                    tempo, kerusakan, kebutuhan kritis).
     *
     * Seluruh angka diambil ulang (bukan dari query string) agar dokumen
     * selalu konsisten dengan kondisi basis data saat dicetak.
     */
    public function cetak(Request $request)
    {
        [$res, $context] = $this->boot($request);
        if ($res) {
            return $res;
        }

        $mode      = $this->modeCetak($request, 'ringkasan');
        $orientasi = $this->orientasiCetak($request, 'portrait');

        $angka = static function (string $table, array $context, ?callable $filter = null): int {
            $query = DB::table($table);
            LaboranScope::apply($query, 'jurusan_id', $context);
            if ($filter) {
                $filter($query);
            }

            return (int) $query->count();
        };

        // --- Inventaris -----------------------------------------------------
        $invBase = DB::table('laboran_bahan_alat');
        LaboranScope::apply($invBase, 'jurusan_id', $context);
        $inventaris = $invBase->get();

        $indikator = [
            (object) [
                'no'       => 1,
                'tupoksi'  => 'Pendataan inventaris alat & bahan praktik',
                'bukti'    => 'Buku induk inventaris + kartu stok',
                'jumlah'   => $inventaris->count(),
                'satuan'   => 'item',
                'catatan'  => $inventaris->where('kondisi', 'baik')->count() . ' item laik pakai, '
                    . $inventaris->whereIn('kondisi', ['rusak_ringan', 'rusak_berat'])->count() . ' item rusak',
            ],
            (object) [
                'no'       => 2,
                'tupoksi'  => 'Analisis kebutuhan alat & bahan praktik',
                'bukti'    => 'Dokumen analisis & usulan kebutuhan',
                'jumlah'   => $angka('laboran_kebutuhan', $context),
                'satuan'   => 'usulan',
                'catatan'  => $angka('laboran_kebutuhan', $context, fn ($q) => $q->where('prioritas', 'kritis')) . ' usulan prioritas kritis',
            ],
            (object) [
                'no'       => 3,
                'tupoksi'  => 'Daftar usulan pengadaan alat & bahan',
                'bukti'    => 'Surat usulan pengadaan',
                'jumlah'   => $angka('laboran_usulan_pengadaan', $context),
                'satuan'   => 'usulan',
                'catatan'  => $angka('laboran_usulan_pengadaan', $context, fn ($q) => $q->whereIn('status', ['disetujui', 'direalisasi'])) . ' usulan disetujui',
            ],
            (object) [
                'no'       => 4,
                'tupoksi'  => 'Pemeriksaan / daftar ceklist alat & bahan',
                'bukti'    => 'Checklist pemeriksaan berkala',
                'jumlah'   => $angka('laboran_pemeriksaan', $context),
                'satuan'   => 'berkas',
                'catatan'  => $angka('laboran_pemeriksaan', $context, fn ($q) => $q->whereBetween('tanggal_pemeriksaan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])) . ' berkas bulan ini',
            ],
            (object) [
                'no'       => 5,
                'tupoksi'  => 'Perawatan & penataan alat (K3)',
                'bukti'    => 'Kartu perawatan + catatan K3',
                'jumlah'   => $angka('laboran_kartu_perawatan', $context),
                'satuan'   => 'kartu',
                'catatan'  => $angka('laboran_kartu_perawatan', $context, fn ($q) => $q->where('status', 'aktif')) . ' kartu aktif',
            ],
            (object) [
                'no'       => 6,
                'tupoksi'  => 'Berita acara kerusakan alat',
                'bukti'    => 'BA kerusakan ditandatangani',
                'jumlah'   => $angka('laboran_berita_acara_kerusakan', $context),
                'satuan'   => 'berita acara',
                'catatan'  => $angka('laboran_berita_acara_kerusakan', $context, fn ($q) => $q->whereIn('tingkat_kerusakan', ['berat', 'total'])) . ' kerusakan berat/total',
            ],
            (object) [
                'no'       => 7,
                'tupoksi'  => 'Arsip dokumen administrasi (ordner digital)',
                'bukti'    => 'Ordner BA kerusakan, kartu peminjaman, dll.',
                'jumlah'   => $angka('laboran_arsip', $context),
                'satuan'   => 'dokumen',
                'catatan'  => $angka('laboran_arsip', $context, fn ($q) => $q->where('status', 'aktif')) . ' arsip aktif',
            ],
            (object) [
                'no'       => 8,
                'tupoksi'  => 'Jurnal kegiatan harian laboran',
                'bukti'    => 'Buku jurnal harian',
                'jumlah'   => $angka('laboran_jurnal', $context),
                'satuan'   => 'catatan',
                'catatan'  => $angka('laboran_jurnal', $context, fn ($q) => $q->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])) . ' catatan bulan ini',
            ],
            (object) [
                'no'       => 9,
                'tupoksi'  => 'Kebersihan lingkungan produktif',
                'bukti'    => 'Dokumentasi kebersihan lab/bengkel',
                'jumlah'   => $angka('laboran_kebersihan', $context),
                'satuan'   => 'catatan',
                'catatan'  => $angka('laboran_kebersihan', $context, fn ($q) => $q->whereBetween('tanggal_kegiatan', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])) . ' catatan bulan ini',
            ],
        ];

        $jumlahBukti = array_sum(array_map(fn ($i) => $i->jumlah, $indikator));
        $indikatorTerpenuhi = count(array_filter($indikator, fn ($i) => $i->jumlah > 0));
        $kondisiBaik = $inventaris->count() > 0
            ? round(($inventaris->where('kondisi', 'baik')->count() / $inventaris->count()) * 100, 1)
            : 0;

        $pinjamanAktif = $angka('laboran_peminjaman', $context, fn ($q) => $q->whereIn('status', ['dipinjam', 'terlambat']));
        $pinjamTerlambat = $angka('laboran_peminjaman', $context, fn ($q) => $q
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->whereNotNull('tanggal_kembali_rencana')
            ->where('tanggal_kembali_rencana', '<', now()->toDateString()));

        $kartuJatuhTempo = $angka('laboran_kartu_perawatan', $context, fn ($q) => $q
            ->where('status', 'aktif')
            ->whereNotNull('jadwal_berikutnya')
            ->where('jadwal_berikutnya', '<=', now()->addDays(30)->toDateString()));

        $kebutuhanKritis = $angka('laboran_kebutuhan', $context, fn ($q) => $q->where('prioritas', 'kritis'));

        $stokMenipis = $inventaris->filter(function ($it) {
            $tersedia = (float) $it->stok_tersedia;
            $total    = (float) $it->stok_total;

            return $tersedia <= 0 || ($total > 0 && ($tersedia / max($total, 1)) <= 0.25);
        })->values();

        $tindakLanjut = collect();
        if ($mode === 'lengkap') {
            $ambil = function (string $table, array $kolom, callable $saring, callable $petakan) use ($context) {
                $query = DB::table($table);
                LaboranScope::apply($query, 'jurusan_id', $context);
                $saring($query);

                return $query->limit(50)->get($kolom)->map($petakan);
            };

            $tindakLanjut = $inventaris->map(function ($it) {
                $tersedia = (float) $it->stok_tersedia;
                $total    = (float) $it->stok_total;
                if ($tersedia > 0 && ($total <= 0 || ($tersedia / max($total, 1)) > 0.25)) {
                    return null;
                }

                return (object) [
                    'kategori' => 'Stok menipis',
                    'uraian'   => $it->nama_item ?: '—',
                    'ruang'    => $it->ruang_lab_nama ?: '—',
                    'detail'   => rtrim(rtrim(number_format($tersedia, 2, ',', '.'), '0'), ',')
                        . ' dari ' . rtrim(rtrim(number_format($total, 2, ',', '.'), '0'), ','),
                ];
            })->filter()->merge(
                $ambil(
                    'laboran_peminjaman',
                    ['nomor_pinjam', 'peminjam_nama', 'peminjam_kelas', 'tanggal_kembali_rencana'],
                    fn ($q) => $q->whereIn('status', ['dipinjam', 'terlambat'])
                        ->whereNotNull('tanggal_kembali_rencana')
                        ->where('tanggal_kembali_rencana', '<', now()->toDateString())
                        ->orderBy('tanggal_kembali_rencana'),
                    fn ($p) => (object) [
                        'kategori' => 'Peminjaman terlambat',
                        'uraian'   => $p->nomor_pinjam . ' — ' . ($p->peminjam_nama ?: '—'),
                        'ruang'    => $p->peminjam_kelas ?: '—',
                        'detail'   => 'Tenggat ' . ($p->tanggal_kembali_rencana
                            ? Carbon::parse($p->tanggal_kembali_rencana)->format('d/m/Y')
                            : '—'),
                    ]
                )
            )->merge(
                $ambil(
                    'laboran_kartu_perawatan',
                    ['kode_kartu', 'nama_alat', 'ruang_lab_nama', 'jadwal_berikutnya'],
                    fn ($q) => $q->where('status', 'aktif')
                        ->whereNotNull('jadwal_berikutnya')
                        ->where('jadwal_berikutnya', '<=', now()->addDays(30)->toDateString())
                        ->orderBy('jadwal_berikutnya'),
                    fn ($k) => (object) [
                        'kategori' => 'Perawatan jatuh tempo',
                        'uraian'   => ($k->kode_kartu ?: '—') . ' — ' . ($k->nama_alat ?: '—'),
                        'ruang'    => $k->ruang_lab_nama ?: '—',
                        'detail'   => 'Jadwal ' . ($k->jadwal_berikutnya
                            ? Carbon::parse($k->jadwal_berikutnya)->format('d/m/Y')
                            : '—'),
                    ]
                )
            )->merge(
                $ambil(
                    'laboran_kebutuhan',
                    ['nama_item', 'ruang_lab_nama', 'jumlah_kebutuhan', 'jumlah_tersedia', 'satuan'],
                    fn ($q) => $q->where('prioritas', 'kritis')->orderByDesc('selisih'),
                    fn ($k) => (object) [
                        'kategori' => 'Kebutuhan kritis',
                        'uraian'   => $k->nama_item ?: '—',
                        'ruang'    => $k->ruang_lab_nama ?: '—',
                        'detail'   => 'Butuh ' . rtrim(rtrim(number_format((float) $k->jumlah_kebutuhan, 2, ',', '.'), '0'), ',')
                            . ' ' . ($k->satuan ?: 'unit') . ', tersedia '
                            . rtrim(rtrim(number_format((float) $k->jumlah_tersedia, 2, ',', '.'), '0'), ','),
                    ]
                )
            )->values();
        }

        return view('dashboard.laboran.dashboard-cetak', array_merge(
            [
                'indikator'       => $indikator,
                'indikatorTerpenuhi' => $indikatorTerpenuhi,
                'jumlahBukti'     => $jumlahBukti,
                'kondisiBaik'     => $kondisiBaik,
                'pinjamanAktif'   => $pinjamanAktif,
                'pinjamTerlambat' => $pinjamTerlambat,
                'kartuJatuhTempo' => $kartuJatuhTempo,
                'kebutuhanKritis' => $kebutuhanKritis,
                'stokMenipis'     => $stokMenipis,
                'tindakLanjut'    => $tindakLanjut,
                'backUrl'         => route('dashboard.laboran.dashboard'),
            ],
            $this->dokumenCetak('dashboard', $context, [
                'mode'         => $mode,
                'orientasi'    => $orientasi,
                'jumlah_baris' => $indikator === [] ? 0 : count($indikator),
                'periode_label' => now()->translatedFormat('F Y'),
            ])
        ));
    }
}
