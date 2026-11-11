<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Persuratan;
use App\Models\Gtk;
use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\Table;

class SuratSptService
{
    /**
     * Peta standar kode pangkat/golongan PNS & PPPK ke nama resmi tata naskah dinas.
     */
    public static array $pangkatMap = [
        'I/a'   => 'Juru Muda/ I.a',
        'I/b'   => 'Juru Muda Tk.I/ I.b',
        'I/c'   => 'Juru/ I.c',
        'I/d'   => 'Juru Tk.I/ I.d',
        'II/a'  => 'Pengatur Muda/ II.a',
        'II/b'  => 'Pengatur Muda Tk.I/ II.b',
        'II/c'  => 'Pengatur/ II.c',
        'II/d'  => 'Pengatur Tk.I/ II.d',
        'III/a' => 'Penata Muda/ III.a',
        'III/b' => 'Penata Muda Tk.I/ III.b',
        'III/c' => 'Penata/ III.c',
        'III/d' => 'Penata Tk.I/ III.d',
        'IV/a'  => 'Pembina/ IV.a',
        'IV/b'  => 'Pembina Tk.I/ IV.b',
        'IV/c'  => 'Pembina Utama Muda/ IV.c',
        'IV/d'  => 'Pembina Utama Madya/ IV.d',
        'IV/e'  => 'Pembina Utama/ IV.e',
        'IX'    => 'Ahli Pertama/ IX',
        'X'     => 'Ahli Muda/ X',
        'XI'    => 'Ahli Madya/ XI',
    ];

    /**
     * Resolusi objek Kepala Sekolah dari berbagai sumber (tabel gtk, ptk_tugas_tambahan, pengguna, pengaturan).
     */
    public static function resolveKepalaSekolah(): ?object
    {
        if (Table::exists('gtk')) {
            // 1. Cek langsung di tabel gtk
            $kepsek = Gtk::where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%kepala sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%kepala sekolah%');
            })->first();

            if ($kepsek) {
                return $kepsek;
            }

            // 2. Cek tugas tambahan aktif KEPALA_SEKOLAH
            if (Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
                $ptkId = DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where(function ($q) {
                        $q->where('rtt.kode', 'KEPALA_SEKOLAH')
                          ->orWhere('rtt.nama', 'like', '%kepala sekolah%');
                    })
                    ->where('ptt.is_active', true)
                    ->value('ptt.ptk_id');

                if ($ptkId) {
                    $kepsek = Gtk::where('ptk_id', $ptkId)->first();
                    if ($kepsek) {
                        return $kepsek;
                    }
                }
            }

            // 3. Cek akun pengguna role kepala_sekolah
            if (Table::exists('pengguna')) {
                $ptkId = DB::table('pengguna')
                    ->where(function ($q) {
                        $q->where('role', 'kepala_sekolah')
                          ->orWhere('peran_id_str', 'like', '%kepala sekolah%');
                    })
                    ->whereNotNull('ptk_id')
                    ->value('ptk_id');

                if ($ptkId) {
                    $kepsek = Gtk::where('ptk_id', $ptkId)->first();
                    if ($kepsek) {
                        return $kepsek;
                    }
                }
            }
        }

        // 4. Fallback dari buku_induk_pengaturan atau tabel sekolah
        $sekolah = Table::exists('sekolah') ? Sekolah::first() : null;
        $bukuInduk = Table::exists('buku_induk_pengaturan') ? DB::table('buku_induk_pengaturan')->first() : null;
        $nama = $bukuInduk->nama_kepala_sekolah ?? ($sekolah->nama_kepsek ?? null);
        $nip = $bukuInduk->nip_kepala_sekolah ?? ($sekolah->nip_kepsek ?? null);

        if ($nama || $nip) {
            return (object) [
                'ptk_id' => null,
                'nama' => $nama ?: 'Kepala Sekolah',
                'nip' => $nip ?: null,
                'nuptk' => null,
                'pangkat_golongan_terakhir' => null,
                'jenis_ptk_id_str' => 'Kepala Sekolah',
                'jabatan_ptk_id_str' => 'Kepala Sekolah',
            ];
        }

        return null;
    }

    /**
     * Format teks mentah pangkat/golongan ke format resmi naskah dinas.
     */
    public static function formatPangkatGolongan(?string $rawGol): string
    {
        if (empty($rawGol) || trim($rawGol) === '-' || trim($rawGol) === '') {
            return '-';
        }

        $rawGol = trim($rawGol);

        if (isset(self::$pangkatMap[$rawGol])) {
            return self::$pangkatMap[$rawGol];
        }

        $clean = strtoupper(str_replace(' ', '', $rawGol));
        if (isset(self::$pangkatMap[$clean])) {
            return self::$pangkatMap[$clean];
        }

        foreach (self::$pangkatMap as $code => $full) {
            if (stripos($rawGol, $code) !== false) {
                return $full;
            }
        }

        return $rawGol;
    }

    /**
     * Resolusi pangkat & golongan seorang GTK dengan fallback ke riwayat kepangkatan dan status kepegawaian.
     */
    public static function resolveGtkPangkat(?object $gtk): string
    {
        if (!$gtk) {
            return '-';
        }

        $gol = trim($gtk->pangkat_golongan_terakhir ?? '');
        if ($gol !== '' && $gol !== '-') {
            return self::formatPangkatGolongan($gol);
        }

        if (!empty($gtk->rwy_kepangkatan)) {
            $rwy = is_string($gtk->rwy_kepangkatan) ? json_decode($gtk->rwy_kepangkatan, true) : $gtk->rwy_kepangkatan;
            if (is_array($rwy) && !empty($rwy)) {
                usort($rwy, function ($a, $b) {
                    return strcmp($b['tmt_pangkat'] ?? '', $a['tmt_pangkat'] ?? '');
                });
                $latest = $rwy[0] ?? [];
                $val = $latest['pangkat_golongan_id_str']
                    ?? ($latest['pangkat_golongan']
                    ?? ($latest['nama_pangkat']
                    ?? ($latest['golongan'] ?? null)));
                if (!empty($val)) {
                    return self::formatPangkatGolongan((string) $val);
                }
            }
        }

        $statusKep = trim($gtk->status_kepegawaian_id_str ?? ($gtk->status_kepegawaian ?? ''));
        if ($statusKep !== '' && $statusKep !== '-') {
            return $statusKep;
        }

        return '-';
    }

    /**
     * Simpan Surat Perintah Tugas (SPT) dan Catat ke Persuratan Keluar
     */
    public static function store(Request $request, $user): array
    {
        $start = Carbon::parse($request->tanggal_berangkat);
        $end = Carbon::parse($request->tanggal_kembali);
        $lamaHari = $start->diffInDays($end) + 1;

        $kodeIndeks = trim($request->kode_indeks ?: 'KPG.11.01');
        $manualNomor = trim($request->nomor_spt ?? '');

        if (!empty($manualNomor)) {
            $nomorSpt = $manualNomor;
        } else {
            $nomorSpt = PersuratanHddService::generateNomorSuratKeluar($kodeIndeks);
            PersuratanHddService::incrementNomorCounter('keluar');
        }

        $kepsek = self::resolveKepalaSekolah();

        $pejabatPtkId = $request->pejabat_penandatangan_ptk_id ?: $kepsek?->ptk_id;
        $pejabatPtk = ($pejabatPtkId && Table::exists('gtk')) ? Gtk::where('ptk_id', $pejabatPtkId)->first() : null;

        $pejabatNama = trim((string) ($request->pejabat_nama ?? ''));
        if ($pejabatNama === '') {
            $pejabatNama = $pejabatPtk?->nama ?? ($kepsek?->nama ?? 'Kepala Sekolah');
        }

        $pejabatNip = trim((string) ($request->pejabat_nip ?? ''));
        if ($pejabatNip === '') {
            $pejabatNip = $pejabatPtk?->nip ?: ($kepsek?->nip ?: ($pejabatPtk?->nuptk ?: ($kepsek?->nuptk ?? null)));
        }

        $pejabatPangkat = trim((string) ($request->pejabat_pangkat_golongan ?? ''));
        if ($pejabatPangkat === '') {
            $pejabatPangkat = self::resolveGtkPangkat($pejabatPtk ?: $kepsek);
            if ($pejabatPangkat === '-') {
                $pejabatPangkat = null;
            }
        }

        $createdBy = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Tendik Persuratan')) : ($user->nama ?? ($user->name ?? 'Tendik Persuratan'));
        $satuan = Table::exists('sekolah') ? Sekolah::first() : null;
        $namaSatuan = $satuan?->nama ?? 'Sekolah';

        $sptPayload = [
            'nomor_spt'            => $nomorSpt,
            'dasar_penugasan'      => $request->dasar_penugasan,
            'nama_kegiatan'        => $request->nama_kegiatan,
            'lokasi_tujuan'        => $request->lokasi_tujuan,
            'waktu_pelaksanaan'    => $request->waktu_pelaksanaan ?: null,
            'alamat_tujuan'        => $request->alamat_tujuan ?: null,
            'alat_angkutan'        => $request->alat_angkutan ?: 'Kendaraan Darat',
            'tempat_berangkat'     => $request->tempat_berangkat ?: $namaSatuan,
            'pengikut'             => $request->pengikut ?: null,
            'instansi_pembebanan'  => $request->instansi_pembebanan ?: $namaSatuan,
            'akun_kodering'        => $request->akun_kodering ?: null,
            'tingkat_biaya'        => $request->tingkat_biaya ?: null,
            'tanggal_berangkat'    => $request->tanggal_berangkat,
            'tanggal_kembali'      => $request->tanggal_kembali,
            'lama_hari'            => $lamaHari,
            'beban_anggaran'       => $request->beban_anggaran,
            'pejabat_penandatangan_ptk_id' => $pejabatPtkId,
            'pejabat_nama'         => $pejabatNama,
            'pejabat_jabatan'      => $request->pejabat_jabatan ?: 'Kepala Sekolah',
            'daftar_ptk_id'        => json_encode($request->daftar_ptk_id, JSON_UNESCAPED_UNICODE),
            'status'               => 'disetujui',
            'created_by'           => $createdBy,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];

        // Tambah kolom opsional jika tabel sudah memiliki kolom bersangkutan
        if (Table::hasColumn('gtk_spt', 'pejabat_nip')) {
            $sptPayload['pejabat_nip'] = $pejabatNip;
        }
        if (Table::hasColumn('gtk_spt', 'pejabat_pangkat_golongan')) {
            $sptPayload['pejabat_pangkat_golongan'] = $pejabatPangkat;
        }

        $sptId = DB::table('gtk_spt')->insertGetId($sptPayload);

        // Catat otomatis ke buku agenda persuratan keluar agar terkoordinir penuh
        Persuratan::create([
            'nomor_surat'     => $nomorSpt,
            'kode_indeks'     => $kodeIndeks,
            'jenis_surat'     => 'keluar',
            'perihal'         => "Surat Perintah Tugas (SPT): {$request->nama_kegiatan}",
            'pengirim_asal'   => $pejabatNama,
            'tujuan_penerima' => trim($request->lokasi_tujuan),
            'tanggal_surat'   => $request->tanggal_berangkat,
            'status'          => 'selesai',
            'keterangan'      => "SPT Kegiatan {$request->nama_kegiatan}. Dasar: {$request->dasar_penugasan}. Beban: {$request->beban_anggaran}. Doc ID: SAE-SPT-{$sptId}",
            'created_by'      => $createdBy,
        ]);

        return [
            'sptId'    => $sptId,
            'nomorSpt' => $nomorSpt,
        ];
    }

    /**
     * Hapus SPT beserta relasinya di persuratan keluar
     */
    public static function delete($id): bool
    {
        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if ($spt) {
            Persuratan::where('nomor_surat', $spt->nomor_spt)->delete();
            DB::table('gtk_spt')->where('id', $id)->delete();
            SuratKeluarService::syncCounterWithDatabase();
            return true;
        }

        return false;
    }

    /**
     * Siapkan Data Cetak Lembar SPT Resmi Ber-Kop Sekolah
     */
    public static function buildCetakData($id): array
    {
        $spt = DB::table('gtk_spt')->where('id', $id)->first();
        if (!$spt) {
            abort(404, 'Data SPT tidak ditemukan.');
        }

        $ptkIds = json_decode($spt->daftar_ptk_id ?? '[]', true) ?: [];
        $gtkList = collect();
        if (Table::exists('gtk')) {
            $gtkList = Gtk::whereIn('ptk_id', $ptkIds)->orderBy('nama', 'asc')->get();
        }

        // Dukung personil Non-ASN lokal
        $hasNonAsnIds = collect($ptkIds)->filter(fn ($item) => str_starts_with((string) $item, 'nonasn:'));
        if ($hasNonAsnIds->isNotEmpty() && class_exists(GtkGabunganService::class)) {
            $gabunganService = app(GtkGabunganService::class);
            $nonAsnList = $gabunganService->nonAsn(false)->whereIn('ptk_id', $hasNonAsnIds->values()->all());
            $gtkList = $gtkList->concat($nonAsnList)->sortBy('nama')->values();
        }

        $sekolah = Table::exists('sekolah') ? Sekolah::first() : null;
        $sekolahMeta = Table::exists('sekolah_meta') ? SekolahMeta::first() : null;

        $kepsek = self::resolveKepalaSekolah();

        $pejabatPtk = null;
        if (!empty($spt->pejabat_penandatangan_ptk_id) && Table::exists('gtk')) {
            $pejabatPtk = Gtk::where('ptk_id', $spt->pejabat_penandatangan_ptk_id)->first();
        }
        if (!$pejabatPtk) {
            $pejabatPtk = $kepsek;
        }

        $namaKepsek = !empty($spt->pejabat_nama) && $spt->pejabat_nama !== 'Kepala Sekolah'
            ? $spt->pejabat_nama
            : ($pejabatPtk?->nama ?? ($kepsek?->nama ?? 'Kepala Sekolah'));

        $nipKepsek = !empty($spt->pejabat_nip)
            ? $spt->pejabat_nip
            : ($pejabatPtk?->nip ?: ($kepsek?->nip ?: ($pejabatPtk?->nuptk ?: ($kepsek?->nuptk ?? '-'))));

        $rawGol = trim($spt->pejabat_pangkat_golongan ?? '');
        if ($rawGol === '' || $rawGol === '-') {
            $rawGol = self::resolveGtkPangkat($pejabatPtk ?: $kepsek);
        }
        $pangkatGolKepsek = self::formatPangkatGolongan($rawGol);
        if ($pangkatGolKepsek === '-') {
            $pangkatGolKepsek = '';
        }

        $docId = 'SAE-SPT-' . $spt->id;
        $qrVerifyUrl = url('/v/doc/' . $docId);
        $qrUri = QrCodeService::generateDataUri($qrVerifyUrl, 140, 1);

        return compact(
            'spt',
            'gtkList',
            'sekolah',
            'sekolahMeta',
            'kepsek',
            'pejabatPtk',
            'namaKepsek',
            'nipKepsek',
            'pangkatGolKepsek',
            'docId',
            'qrVerifyUrl',
            'qrUri'
        );
    }
}
