<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\SekolahMeta;
use App\Support\LaboranScope;
use App\Support\Table;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Layanan bersama DOKUMEN CETAK modul Laboratorium.
 *
 * Menyatukan tiga kebutuhan yang sebelumnya tersebar/duplikatif:
 *
 *  1. `pejabat()` — resolusi penanda tangan dokumen: Kepala Sekolah,
 *     Kepala Laboratorium (`KEPALA_LAB`), dan **Kepala Program Keahlian
 *     (`KAPROG`) yang dibatasi/dicocokkan dengan jurusan dokumen**, serta
 *     identitas petugas laboran.
 *  2. `catatCetak()` — pencatatan setiap dokumen yang dicetak ke
 *     `laboran_log_cetak` sekaligus mendaftarkannya ke `laboran_arsip`
 *     (ordner digital) agar bukti fisik supervisi dapat ditelusuri kapan saja.
 *  3. `dokumen()` — bundel data standar (sekolah, kop, pejabat, mode,
 *     orientasi, riwayat) yang dipakai seluruh view `*-cetak`.
 *
 * Semua query memakai `Table::exists()` agar modul tetap berjalan pada
 * basis data yang belum dimigrasi penuh.
 */
class LaboranDokumenService
{
    /** Orientasi kertas yang didukung. */
    public const ORIENTASI = ['portrait', 'landscape'];

    /** Mode penyajian laporan. */
    public const MODE = ['ringkasan', 'lengkap'];

    /**
     * Registri dokumen cetak: label manusiawi + kategori arsip tujuan.
     *
     * Kategori mengikuti `LaboranArsipController::KATEGORI` sehingga dokumen
     * hasil cetak langsung masuk ke ordner yang tepat.
     */
    public const DOKUMEN = [
        'dashboard'          => ['label' => 'Rekapitulasi Supervisi Laboratorium', 'kategori' => 'administrasi'],
        'peserta-didik'      => ['label' => 'Data Peserta Didik Jurusan', 'kategori' => 'administrasi'],
        'inventaris'         => ['label' => 'Buku Induk Inventaris Alat Praktik', 'kategori' => 'buku_inventaris'],
        'bhp'                => ['label' => 'Buku Daftar Bahan Habis Pakai', 'kategori' => 'buku_inventaris'],
        'jadwal'             => ['label' => 'Jadwal Penggunaan Lokasi Praktik', 'kategori' => 'administrasi'],
        'jadwal-per-guru'    => ['label' => 'Jadwal Praktik per Guru', 'kategori' => 'administrasi'],
        'laporan-penggunaan' => ['label' => 'Laporan Penggunaan Lokasi Praktik', 'kategori' => 'laporan'],
        'kebutuhan'          => ['label' => 'Analisis & Kebutuhan Alat/Bahan', 'kategori' => 'laporan'],
        'perawatan'          => ['label' => 'Kartu Perawatan & Penataan K3', 'kategori' => 'kartu_perawatan'],
        'pemeriksaan'        => ['label' => 'Checklist Pemeriksaan Alat & Bahan', 'kategori' => 'checklist_pemeriksaan'],
        'pengadaan'          => ['label' => 'Daftar Usulan Pengadaan', 'kategori' => 'usulan_pengadaan'],
        'kerusakan'          => ['label' => 'Berita Acara Kerusakan Alat', 'kategori' => 'berita_acara_kerusakan'],
        'peminjaman'         => ['label' => 'Kartu Peminjaman Alat', 'kategori' => 'kartu_peminjaman'],
        'jurnal'             => ['label' => 'Jurnal Kegiatan Harian Laboran', 'kategori' => 'administrasi'],
        'kebersihan'         => ['label' => 'Dokumentasi Kebersihan Lingkungan Produktif', 'kategori' => 'dokumentasi'],
        'laporan-lengkap'    => ['label' => 'Laporan Lengkap Penggunaan & Kerusakan', 'kategori' => 'laporan'],
        'arsip'              => ['label' => 'Daftar Arsip Dokumen', 'kategori' => 'administrasi'],
    ];

    // =========================================================================
    // 1. PEJABAT (PENANDA TANGAN)
    // =========================================================================

    /**
     * Resolusi seluruh penanda tangan dokumen.
     *
     * @param  array    $context  Konteks `LaboranScope::resolve()`
     * @param  ?string  $jurusanId  Jurusan dokumen (default: jurusan konteks)
     * @return array{
     *   kepalaSekolah:?object, kepalaLab:?object, kaprog:?object, petugas:array
     * }
     */
    public function pejabat(array $context, ?string $jurusanId = null): array
    {
        $jurusanId = $jurusanId ?: ($context['jurusan_id'] ?? null);

        return [
            'kepalaSekolah' => $this->kepalaSekolah(),
            'kepalaLab'     => $this->kepalaLab(),
            'kaprog'        => $this->kaprog($jurusanId),
            'petugas'       => $this->petugas($context),
        ];
    }

    /**
     * Kepala Satuan Pendidikan.
     */
    public function kepalaSekolah(): ?object
    {
        if (!Table::exists('gtk')) {
            return null;
        }

        return DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->first(['ptk_id', 'nama', 'nip', 'nuptk']);
    }

    /**
     * Kepala Laboratorium / Bengkel (`KEPALA_LAB`).
     *
     * Catatan: `ptk_tugas_tambahan` TIDAK memiliki kolom `jabatan_ptk_id_str`;
     * jabatan selalu diresolusi lewat `ref_tugas_tambahan.kode`.
     */
    public function kepalaLab(?string $jurusanId = null): ?object
    {
        return $this->pejabatDuty('KEPALA_LAB', $jurusanId);
    }

    /**
     * Pejabat dari tugas tambahan mana pun berdasarkan kode `ref_tugas_tambahan`.
     *
     * Dipakai modul Tendik lain (mis. Teknisi) agar tidak menduplikasi resolusi
     * pejabat: cukup sebut kode tugasnya (mis. `KEPALA_TAS`, `TEKNISI_IT`).
     */
    public function jabatan(string $kode, ?string $jurusanId = null): ?object
    {
        return $this->pejabatDuty($kode, $jurusanId);
    }

    /**
     * Pejabat di luar daftar tugas tambahan, dicari dari jabatan GTK (`gtk`).
     *
     * Dipakai modul Tendik yang penanda tangannya bukan pemegang tugas tambahan
     * (mis. Waka Sarpras), dengan fallback ke pencocokan nama jabatan.
     */
    public function pejabatGtkFrasa(string $frasa): ?object
    {
        if (!Table::exists('gtk')) {
            return null;
        }

        return DB::table('gtk')
            ->where(function ($q) use ($frasa) {
                $q->where('jenis_ptk_id_str', 'like', "%{$frasa}%")
                  ->orWhere('jabatan_ptk_id_str', 'like', "%{$frasa}%");
            })
            ->first(['ptk_id', 'nama', 'nip', 'nuptk']);
    }

    /**
     * Kepala Program Keahlian (`KAPROG`) untuk jurusan dokumen.
     *
     * Bila tidak ada penugasan KAPROG, hasil `null` dan view menampilkan
     * titik-titik agar dokumen tetap sah untuk ditandatangani manual.
     */
    public function kaprog(?string $jurusanId = null): ?object
    {
        return $this->pejabatDuty('KAPROG', $jurusanId);
    }

    /**
     * Daftar KAPROG yang dapat dipilih sebagai penanda tangan dokumen.
     *
     * Dipakai ketika petugas ditugaskan pada **lebih dari satu jurusan**: dokumen
     * resmi hanya boleh ditandatangani Kaprog jurusan yang bersangkutan, sehingga
     * pengguna diberi pilihan alih-alih sistem menebak.
     *
     * Hanya jurusan yang benar-benar menjadi cakupan petugas (`$context['jurusan_ids']`)
     * yang ditawarkan. Tiap entri = satu jurusan + pejabat KAPROG-nya.
     *
     * @return array<int, array{jurusan_id:string, nama:string, kaprog:?object}>
     */
    public function kaprogOpsi(array $context): array
    {
        $ids = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), (array) ($context['jurusan_ids'] ?? [])),
            fn ($v) => $v !== ''
        ));

        // Satu jurusan saja → tidak perlu pilihan; dokumen memakai jurusan itu.
        if (count($ids) < 2) {
            return [];
        }

        $nama = Table::exists('jurusan_meta')
            ? DB::table('jurusan_meta')->whereIn('jurusan_id', $ids)
                ->pluck('nama_jurusan', 'jurusan_id')->all()
            : [];

        // Kode jurusan INDUK & anak (mis. `47012` & `47012650`) menunjuk Kaprog
        // yang sama, jadi cukup tampilkan SATU pilihan per pejabat. Bila Kaprog
        // belum ditetapkan, gunakan kode jurusan sebagai kunci agar tetap muncul.
        $opsi = [];
        $seen = [];
        foreach ($ids as $id) {
            $kaprog = $this->kaprog($id);
            $kunci  = $kaprog?->ptk_id ?: 'jurusan:' . $id;

            if (isset($seen[$kunci])) {
                continue;
            }
            $seen[$kunci] = true;

            $opsi[] = [
                'jurusan_id' => $id,
                'nama'       => $nama[$id] ?? $id,
                'kaprog'     => $kaprog,
            ];
        }

        return $opsi;
    }

    /**
     * Ambil pejabat dari tugas tambahan berdasarkan kode.
     *
     * `ptk_tugas_tambahan.jurusan_id` dapat berisi multi-jurusan (dipisah koma
     * atau JSON) dan dapat memakai kode jurusan INDUK (mis. `26010`) sementara
     * dokumen memakai kode anak (`26010475`), sehingga pencocokan dilakukan
     * dengan relasi awalan dua arah.
     */
    protected function pejabatDuty(string $kode, ?string $jurusanId = null): ?object
    {
        if (!Table::existsAll('ptk_tugas_tambahan', 'ref_tugas_tambahan', 'gtk')) {
            return null;
        }

        $rows = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->where('rtt.kode', $kode)
            ->where('ptt.is_active', true)
            ->orderByDesc('ptt.tmt_tugas')
            ->orderByDesc('ptt.id')
            ->get([
                'gtk.ptk_id', 'gtk.nama', 'gtk.nip', 'gtk.nuptk',
                'rtt.nama as jabatan', 'ptt.jurusan_id',
            ]);

        if ($rows->isEmpty()) {
            return null;
        }

        if (!$jurusanId) {
            return $rows->first();
        }

        $cocok = $rows->first(fn ($r) => $this->jurusanCocok($r->jurusan_id, $jurusanId));

        // Fallback: penugasan tanpa jurusan berlaku untuk semua jurusan.
        if (!$cocok) {
            $cocok = $rows->first(fn ($r) => trim((string) $r->jurusan_id) === '');
        }

        // Penugasan milik jurusan LAIN tidak boleh dipakai: dokumen resmi tidak
        // boleh mencantumkan nama pejabat dari program keahlian yang berbeda.
        return $cocok;
    }

    /**
     * Cocokkan kolom `jurusan_id` penugasan (bisa multi-nilai) dengan jurusan
     * dokumen. Relasi awalan dua arah dipakai agar kode induk (mis. `26010`)
     * tetap cocok dengan kode anak (`26010475`).
     */
    protected function jurusanCocok(?string $penugasan, ?string $jurusanId): bool
    {
        $jurusanId = trim((string) $jurusanId);
        if ($jurusanId === '') {
            return false;
        }

        foreach ($this->pecahJurusan($penugasan) as $token) {
            if ($token === $jurusanId) {
                return true;
            }
            if (str_starts_with($jurusanId, $token) || str_starts_with($token, $jurusanId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pecah nilai `jurusan_id` penugasan menjadi daftar kode jurusan.
     *
     * @return array<int, string>
     */
    protected function pecahJurusan(?string $raw): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    fn ($v) => trim((string) $v),
                    $decoded
                ), fn ($v) => $v !== ''));
            }
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn ($v) => $v !== ''
        ));
    }

    /**
     * Identitas petugas laboran (pengguna aktif).
     *
     * @return array{nama:string, nip:string, ptk_id:?string}
     */
    public function petugas(array $context): array
    {
        $ptkId = $context['ptk_id'] ?? null;
        $nip   = '—';

        if ($ptkId && Table::exists('gtk')) {
            $nip = DB::table('gtk')->where('ptk_id', $ptkId)->value('nip') ?: '—';
        }

        return [
            'nama'   => ($context['nama'] ?? '') ?: 'Petugas Laboran',
            'nip'    => $nip,
            'ptk_id' => $ptkId,
        ];
    }

    // =========================================================================
    // 2. BUNDEL DATA VIEW CETAK
    // =========================================================================

    /**
     * Bundel data standar untuk seluruh view `*-cetak` Laboran.
     *
     * @param  string  $jenis      Kunci dokumen (lihat `DOKUMEN`)
     * @param  array   $context    Konteks `LaboranScope::resolve()`
     * @param  array{
     *   judul?:string, mode?:string, orientasi?:string, periode_label?:string,
     *   periode_dari?:?string, periode_sampai?:?string, jumlah_baris?:int,
     *   sumber_tabel?:?string, sumber_id?:?int|string, filter?:array,
     *   jurusan_id?:?string, catat?:bool, request?:?Request
     * } $opsi
     */
    public function dokumen(string $jenis, array $context, array $opsi = []): array
    {
        // Pilihan KAPROG (bila petugas ditugaskan >1 jurusan) dihitung lebih
        // dulu, lalu jurusan dokumen mengikuti pilihan pengguna bila sah.
        $kaprogOpsi = $this->kaprogOpsi($context);

        $jurusanId = $opsi['jurusan_id'] ?? ($context['jurusan_id'] ?? null);
        if (!empty($kaprogOpsi)) {
            $req   = $opsi['request'] ?? null;
            $pilih = $req instanceof Request
                ? trim((string) $req->query('jurusan_id', ''))
                : trim((string) ($opsi['pilih_jurusan_id'] ?? ''));

            $sah = collect($kaprogOpsi)->pluck('jurusan_id')->contains($pilih);
            $jurusanId = $sah ? $pilih : $jurusanId;
        }
        $opsi['jurusan_id'] = $jurusanId;

        $mode = strtolower((string) ($opsi['mode'] ?? 'ringkasan'));
        if (!in_array($mode, self::MODE, true)) {
            $mode = 'ringkasan';
        }

        $orientasi = strtolower((string) ($opsi['orientasi'] ?? 'portrait'));
        if (!in_array($orientasi, self::ORIENTASI, true)) {
            $orientasi = 'portrait';
        }

        $pejabat = $this->pejabat($context, $jurusanId);
        $judul   = $opsi['judul'] ?? (self::DOKUMEN[$jenis]['label'] ?? Str::title($jenis));

        $data = [
            'jenisDokumen'  => $jenis,
            'judulDokumen'  => $judul,
            'mode'          => $mode,
            'orientasi'     => $orientasi,
            'sekolah'       => $this->sekolah(),
            'sekolahMeta'   => $this->sekolahMeta(),
            'jurusan'       => $this->jurusan($jurusanId),
            'jurusanId'     => $jurusanId,
            'kaprogOpsi'    => $kaprogOpsi,
            'kepalaSekolah' => $pejabat['kepalaSekolah'],
            'kepalaLab'     => $pejabat['kepalaLab'],
            'kaprog'        => $pejabat['kaprog'],
            'petugas'       => $pejabat['petugas'],
            'context'       => $context,
            'scopeBadge'    => LaboranScope::badge($context),
            'dicetakPada'   => Carbon::now(),
        ];

        // Pencatatan cetak bersifat opt-out agar setiap halaman cetak otomatis
        // tercatat, namun tetap bisa dinonaktifkan (mis. dari dalam pengujian).
        if ($opsi['catat'] ?? true) {
            $data['logCetak'] = $this->catatCetak($jenis, $context, array_merge($opsi, [
                'judul'     => $judul,
                'mode'      => $mode,
                'orientasi' => $orientasi,
                'jurusan_id' => $jurusanId,
            ]));
        }

        return $data;
    }

    public function sekolah(): ?object
    {
        return class_exists(Sekolah::class) ? Sekolah::first() : null;
    }

    public function sekolahMeta(): ?object
    {
        return class_exists(SekolahMeta::class) ? SekolahMeta::first() : null;
    }

    public function jurusan(?string $jurusanId): ?object
    {
        if (!$jurusanId || !Table::exists('jurusan_meta')) {
            return null;
        }

        return DB::table('jurusan_meta')->where('jurusan_id', $jurusanId)->first();
    }

    // =========================================================================
    // 3. PENCATATAN CETAK + ARSIP OTOMATIS
    // =========================================================================

    /**
     * Catat dokumen yang dicetak & daftarkan ke arsip digital.
     *
     * Idempoten dalam jendela singkat (10 detik) untuk tanda tangan filter yang
     * sama, sehingga pemuatan ulang aset tidak menggandakan riwayat.
     *
     * @return object|null Baris `laboran_log_cetak`
     */
    public function catatCetak(string $jenis, array $context, array $opsi = []): ?object
    {
        if (!Table::exists('laboran_log_cetak')) {
            return null;
        }

        $mode      = in_array(($opsi['mode'] ?? 'ringkasan'), self::MODE, true) ? $opsi['mode'] : 'ringkasan';
        $orientasi = in_array(($opsi['orientasi'] ?? 'portrait'), self::ORIENTASI, true) ? $opsi['orientasi'] : 'portrait';
        $filter    = $this->normalisasiFilter($opsi['filter'] ?? []);

        $jurusanId = $opsi['jurusan_id'] ?? ($context['jurusan_id'] ?? null);
        $judul     = $opsi['judul'] ?? (self::DOKUMEN[$jenis]['label'] ?? Str::title($jenis));
        $ptkId     = $context['ptk_id'] ?? null;
        $petugas   = $context['nama'] ?? null;

        $sidik = sha1(implode('|', [
            $jenis, $mode, $orientasi, (string) $jurusanId, (string) $ptkId, json_encode($filter),
        ]));

        $existing = DB::table('laboran_log_cetak')
            ->where('jenis_dokumen', $jenis)
            ->where('mode', $mode)
            ->where('orientasi', $orientasi)
            ->where('jurusan_id', $jurusanId)
            ->where('petugas_ptk_id', $ptkId)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->where('filter_signature', $sidik)
            ->first();

        if ($existing) {
            return $existing;
        }

        $pejabat = $this->pejabat($context, $jurusanId);

        $id = DB::table('laboran_log_cetak')->insertGetId([
            'jurusan_id'      => $jurusanId,
            'nomor_cetak'     => $this->generateNomor('laboran_log_cetak', 'nomor_cetak', 'CET'),
            'jenis_dokumen'   => $jenis,
            'judul_dokumen'   => $judul,
            'mode'            => $mode,
            'periode_label'   => $opsi['periode_label'] ?? null,
            'periode_dari'    => $this->tanggal($opsi['periode_dari'] ?? null),
            'periode_sampai'  => $this->tanggal($opsi['periode_sampai'] ?? null),
            'sumber_tabel'    => $opsi['sumber_tabel'] ?? null,
            'sumber_id'       => $opsi['sumber_id'] ?? null,
            'orientasi'       => $orientasi,
            'jumlah_baris'    => (int) ($opsi['jumlah_baris'] ?? 0),
            'filter'          => $filter ? json_encode($filter, JSON_UNESCAPED_UNICODE) : null,
            'filter_signature' => $sidik,
            'kepala_lab_nama' => $pejabat['kepalaLab']->nama ?? null,
            'kaprog_nama'     => $pejabat['kaprog']->nama ?? null,
            'petugas_ptk_id'  => $ptkId,
            'petugas_nama'    => $petugas,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Pengarsipan bersifat pelengkap: kegagalan menyimpan ke Arsip Dokumen
        // TIDAK boleh menggagalkan pencetakan. Baris `laboran_log_cetak` sudah
        // tersimpan, sehingga dokumen tetap tercatat pada Riwayat Cetak.
        try {
            $arsipId = $this->arsipkan($jenis, $judul, $mode, $jurusanId, $context, $opsi, $id);

            if ($arsipId) {
                DB::table('laboran_log_cetak')->where('id', $id)->update(['arsip_id' => $arsipId]);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return DB::table('laboran_log_cetak')->where('id', $id)->first();
    }

    /**
     * Daftarkan dokumen cetak sebagai arsip digital (ordner).
     */
    protected function arsipkan(
        string $jenis,
        string $judul,
        string $mode,
        ?string $jurusanId,
        array $context,
        array $opsi,
        int $logId
    ): ?int {
        if (!Table::exists('laboran_arsip')) {
            return null;
        }

        $kategori = self::DOKUMEN[$jenis]['kategori'] ?? 'lainnya';
        $waktu    = Carbon::now();

        return (int) DB::table('laboran_arsip')->insertGetId([
            'jurusan_id'      => $jurusanId,
            'kode_arsip'      => $this->generateNomor('laboran_arsip', 'kode_arsip', 'ARS'),
            'nama_dokumen'    => $judul . ' (' . ucfirst($mode) . ')',
            'kategori'        => $kategori,
            'nomor_dokumen'   => $opsi['nomor_dokumen'] ?? null,
            'tanggal_dokumen' => $waktu->toDateString(),
            'sumber_tabel'    => 'laboran_log_cetak',
            'sumber_id'       => $logId,
            'deskripsi'       => $this->deskripsiArsip($jenis, $mode, $opsi),
            'status'          => 'aktif',
            'tingkat_akses'   => 'internal',
            'petugas_ptk_id'  => $context['ptk_id'] ?? null,
            'petugas_nama'    => $context['nama'] ?? null,
            'created_at'      => $waktu,
            'updated_at'      => $waktu,
        ]);
    }

    protected function deskripsiArsip(string $jenis, string $mode, array $opsi): string
    {
        $bagian = ['Dokumen hasil cetak modul Laboratorium (SAE).', 'Jenis: ' . $jenis, 'Mode: ' . $mode];

        if (!empty($opsi['periode_label'])) {
            $bagian[] = 'Periode: ' . $opsi['periode_label'];
        }

        if (!empty($opsi['filter']) && is_array($opsi['filter'])) {
            $pasangan = [];
            foreach ($opsi['filter'] as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                $pasangan[] = $k . '=' . (is_array($v) ? implode(',', $v) : $v);
            }
            if ($pasangan) {
                $bagian[] = 'Filter: ' . implode('; ', $pasangan);
            }
        }

        return implode(' | ', $bagian);
    }

    /**
     * Normalisasi filter agar tanda tangan konsisten (urutan kunci stabil).
     */
    protected function normalisasiFilter(array $filter): array
    {
        $bersih = [];
        foreach ($filter as $k => $v) {
            if ($v === null || $v === '' || $v === []) {
                continue;
            }
            $bersih[(string) $k] = is_array($v) ? array_values($v) : (string) $v;
        }

        ksort($bersih);

        return $bersih;
    }

    protected function tanggal(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Nomor dokumen berurutan: PREFIX/YYYYMM/NNNN (unik per tabel+kolom).
     */
    public function generateNomor(string $table, string $column, string $prefix): string
    {
        $periode = now()->format('Ym');
        $like    = "{$prefix}/{$periode}/%";

        $last = DB::table($table)->where($column, 'like', $like)->orderByDesc('id')->value($column);

        $urut = 1;
        if ($last) {
            $parts = explode('/', (string) $last);
            $urut = ((int) end($parts)) + 1;
        }

        $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
        $guard = 0;

        while (DB::table($table)->where($column, $nomor)->exists() && $guard < 50) {
            $urut++;
            $nomor = sprintf('%s/%s/%04d', $prefix, $periode, $urut);
            $guard++;
        }

        return $nomor;
    }

    /**
     * Riwayat cetak terbaru dalam cakupan jurusan (untuk tab Arsip).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function riwayat(array $context, int $limit = 30)
    {
        if (!Table::exists('laboran_log_cetak')) {
            return collect();
        }

        $query = DB::table('laboran_log_cetak');
        LaboranScope::apply($query, 'jurusan_id', $context);

        return $query->orderByDesc('id')->limit($limit)->get();
    }
}
