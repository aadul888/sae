<?php

namespace App\Services;

use App\Support\KelasAkhirScope;
use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Pemetaan mata pelajaran PER JURUSAN untuk Buku Induk.
 *
 * Setiap jurusan (konsentrasi XI/XII) punya daftar mapel per semester sendiri.
 * Kelas X memakai program keahlian pasangannya (dipetakan lewat awalan id jurusan).
 * Nama mapel generik kurikulum diganti nama konkret:
 *   - "Dasar Dasar Keahlian"   → "Dasar Dasar {program keahlian}" (semester 1–2)
 *   - "Konsentrasi Keahlian"   → "{konsentrasi keahlian}"         (semester 3–6)
 *
 * Cadangan: bila jurusan tidak diketahui / belum dipetakan, pakai pemetaan global
 * (`BukuIndukNilaiService::mapelPerSemester()`).
 */
class BukuIndukJurusanService
{
    /** Mapel kurikulum generik yang perlu diganti nama konkret per jurusan. */
    public const GENERIK_DASAR = 'Dasar Dasar Keahlian';
    public const GENERIK_KONSENTRASI = 'Konsentrasi Keahlian';

    /** Cache daftar jurusan per request. */
    private static ?array $cacheList = null;

    /**
     * Daftar jurusan (satu entri per konsentrasi XI/XII) beserta program keahlian (X).
     *
     * @return array<string, array{kunci:string, program:?string, program_id:?string, konsentrasi:string, singkatan:?string}>
     */
    public static function jurusanList(): array
    {
        if (self::$cacheList !== null) {
            return self::$cacheList;
        }

        if (!Table::exists('rombongan_belajar')) {
            return self::$cacheList = [];
        }

        $rows = DB::table('rombongan_belajar')
            ->whereNotNull('jurusan_id_str')
            ->where('jurusan_id_str', '!=', '')
            ->select('jurusan_id', 'jurusan_id_str', 'tingkat_pendidikan_id')
            ->distinct()
            ->get();

        $meta = Table::exists('jurusan_meta')
            ? DB::table('jurusan_meta')->get()->keyBy('jurusan_id')
            : collect();

        $programs = [];      // tingkat X  : id => ['nama','singkatan']
        $konsentrasis = [];  // tingkat XI+ : id => ['nama','singkatan']
        foreach ($rows as $r) {
            $id = (string) $r->jurusan_id;
            $nama = $meta[$id]->nama_jurusan ?? (string) $r->jurusan_id_str;
            $sing = $meta[$id]->singkatan ?? null;
            $entry = ['nama' => $nama, 'singkatan' => $sing];
            $tk = (int) $r->tingkat_pendidikan_id;
            if ($tk === 10) {
                $programs[$id] = $entry;
            } elseif ($tk >= 11) {
                $konsentrasis[$id] = $entry;
            }
        }

        $out = [];
        foreach ($konsentrasis as $id => $k) {
            $program = null;
            $programId = null;
            foreach ($programs as $pid => $p) {
                if (str_starts_with((string) $id, (string) $pid)) {
                    $program = $p['nama'];
                    $programId = $pid;
                    break;
                }
            }
            $out[$id] = [
                'kunci' => $id,
                'program' => $program ?: $k['nama'],
                'program_id' => $programId,
                'konsentrasi' => $k['nama'],
                'singkatan' => $k['singkatan'],
            ];
        }

        // Bila hanya ada rombel tingkat X (tanpa XI/XII), tampilkan sebagai jurusan apa adanya.
        if (!$out) {
            foreach ($programs as $id => $p) {
                $out[$id] = [
                    'kunci' => $id,
                    'program' => $p['nama'],
                    'program_id' => $id,
                    'konsentrasi' => $p['nama'],
                    'singkatan' => $p['singkatan'],
                ];
            }
        }

        return self::$cacheList = $out;
    }

    /** Satu entri jurusan berdasarkan kuncinya. */
    public static function jurusan(string $kunci): ?array
    {
        return self::jurusanList()[$kunci] ?? null;
    }

    /** Label jurusan (konsentrasi bila ada). */
    public static function label(array $jr): string
    {
        return $jr['konsentrasi'] ?: ($jr['program'] ?? $jr['kunci']);
    }

    /** Peta id jurusan (kunci konsentrasi ATAU program_id tingkat X) ke kunci jurusan. */
    private static function byIdMap(): array
    {
        $byId = [];
        foreach (self::jurusanList() as $kunci => $jr) {
            $byId[(string) $kunci] = $kunci;
            if (!empty($jr['program_id'])) {
                $byId[(string) $jr['program_id']] = $kunci;
            }
        }

        return $byId;
    }

    /** Kunci jurusan dari id jurusan rombel. */
    public static function idKeKunci(?string $jid): ?string
    {
        if ($jid === null || $jid === '') {
            return null;
        }

        return self::byIdMap()[(string) $jid] ?? null;
    }

    /**
     * Kunci jurusan (konsentrasi) untuk satu peserta didik.
     *
     * Diambil dari rombongan_belajar.jurusan_id; bila tidak ada, cocokkan
     * singkatan jurusan pada nama rombel (berguna untuk arsip alumni).
     */
    public static function kunciJurusan($pd): ?string
    {
        $list = self::jurusanList();
        if (!$list || !$pd) {
            return null;
        }

        $rombelId = $pd->rombongan_belajar_id ?? null;
        if ($rombelId) {
            $jid = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $rombelId)->value('jurusan_id');
            $kunci = self::idKeKunci($jid !== null ? (string) $jid : null);
            if ($kunci !== null) {
                return $kunci;
            }
        }

        $rombel = strtoupper((string) ($pd->nama_rombel ?? $pd->nama_rombel_terakhir ?? ''));
        if ($rombel !== '') {
            foreach ($list as $kunci => $jr) {
                $sing = strtoupper((string) ($jr['singkatan'] ?? ''));
                if ($sing !== '' && str_contains($rombel, $sing)) {
                    return $kunci;
                }
            }
        }

        return null;
    }

    /** Ganti nama mapel generik dengan nama konkret jurusan (null = buang dari semester itu). */
    public static function namaKonkret(string $nama, array $jr, int $semester): ?string
    {
        if ($nama === self::GENERIK_DASAR) {
            return $semester <= 2 ? ('Dasar Dasar ' . $jr['program']) : null;
        }
        if ($nama === self::GENERIK_KONSENTRASI) {
            return $semester >= 3 ? $jr['konsentrasi'] : null;
        }

        return $nama;
    }

    /** Terapkan resolusi nama generik ke satu daftar mapel. */
    private static function terapkanResolusi(array $list, array $jr, int $semester): array
    {
        $out = [];
        foreach ($list as $nama) {
            $baru = self::namaKonkret((string) $nama, $jr, $semester);
            if ($baru !== null && !in_array($baru, $out, true)) {
                $out[] = $baru;
            }
        }

        return $out;
    }

    /** JSON pemetaan mapel per jurusan (mentah). */
    private static function mapelJurusanJson(): array
    {
        $raw = BukuIndukNilaiService::pengaturan()->mapel_jurusan ?? null;
        $arr = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);

        return is_array($arr) ? $arr : [];
    }

    /**
     * Pemetaan mapel per semester untuk satu jurusan (sudah diresolusi nama konkretnya).
     *
     * @return array<int, array{umum: array<int,string>, kejuruan: array<int,string>}>
     */
    public static function mapelPerSemesterJurusan(?string $kunci): array
    {
        $base = BukuIndukNilaiService::mapelPerSemester();
        $jr = $kunci ? self::jurusan($kunci) : null;
        $stored = $kunci ? (self::mapelJurusanJson()[$kunci] ?? []) : [];

        $out = [];
        foreach (range(1, 6) as $sem) {
            $key = (string) $sem;
            $grup = array_key_exists($key, $stored)
                ? ['umum' => (array) ($stored[$key]['umum'] ?? []), 'kejuruan' => (array) ($stored[$key]['kejuruan'] ?? [])]
                : $base[$sem];

            $grup = [
                'umum' => array_values(array_filter(array_map('trim', $grup['umum']), fn ($v) => $v !== '')),
                'kejuruan' => array_values(array_filter(array_map('trim', $grup['kejuruan']), fn ($v) => $v !== '')),
            ];
            if ($jr) {
                $grup['umum'] = self::terapkanResolusi($grup['umum'], $jr, $sem);
                $grup['kejuruan'] = self::terapkanResolusi($grup['kejuruan'], $jr, $sem);
            }
            $out[$sem] = $grup;
        }

        return $out;
    }

    /** Semester yang sudah dipetakan eksplisit untuk jurusan (mempunyai kunci JSON). */
    public static function mapelSemesterTerpetakanJurusan(?string $kunci): array
    {
        $stored = $kunci ? (self::mapelJurusanJson()[$kunci] ?? []) : [];
        $out = [];
        foreach (range(1, 6) as $sem) {
            $out[$sem] = array_key_exists((string) $sem, $stored);
        }

        return $out;
    }

    /** Simpan pemetaan mapel satu semester untuk satu jurusan. */
    public static function saveMapelSemesterJurusan(string $kunci, int $semester, array $umum, array $kejuruan): bool
    {
        if ($kunci === '' || $semester < 1 || $semester > 6 || !Table::exists('buku_induk_pengaturan')) {
            return false;
        }

        $stored = self::mapelJurusanJson();
        $stored[$kunci][(string) $semester] = [
            'umum' => array_values(array_filter(array_map('trim', $umum), fn ($v) => $v !== '')),
            'kejuruan' => array_values(array_filter(array_map('trim', $kejuruan), fn ($v) => $v !== '')),
        ];

        $p = BukuIndukNilaiService::pengaturan();
        DB::table('buku_induk_pengaturan')->where('id', $p->id)->update([
            'mapel_jurusan' => json_encode($stored),
            'updated_at' => now(),
        ]);

        return true;
    }

    /** Mapel satu semester untuk jurusan: ['umum' => [...], 'kejuruan' => [...]] (nama konkret). */
    public static function mapelSatuSemester(?string $kunci, int $semester): array
    {
        // Semester pelaksanaan PKL dikunci: hanya mapel PKL yang berlaku
        // (dipakai template/impor agar berkas PKL hanya memuat kolom PKL).
        if (BukuIndukNilaiService::semesterPkl($semester)) {
            return ['umum' => [], 'kejuruan' => [BukuIndukNilaiService::MAPEL_PKL]];
        }

        $per = self::mapelPerSemesterJurusan($kunci);

        return $per[$semester] ?? ['umum' => [], 'kejuruan' => []];
    }

    /** Normalisasi nama kolom seperti SimpleXlsxReader (lowercase + underscore). */
    public static function kolomKey(string $teks): string
    {
        $k = strtolower(trim($teks));
        $k = preg_replace('/[^a-z0-9_]/', '_', preg_replace('/\s+/', '_', $k));

        return trim(preg_replace('/_+/', '_', $k), '_');
    }

    /**
     * Impor NILAI satu semester untuk satu jurusan ATAU satu kelas (rombel).
     *
     * Kolom berkas: `No`, `NISN`, `Nama`, `Kelas`, lalu satu atau beberapa kolom
     * mapel yang dikenal. Bila `$rombelId` diisi, NISN wajib milik kelas itu dan
     * kolom mapel tambahan dari format lain (mis. e-Rapor) diabaikan.
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>}
     */
    public static function importNilaiRows(array $rows, string $kunci, int $semester, ?string $rombelId = null): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (!Table::exists('buku_induk_nilai') || !Table::exists('peserta_didik')) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Tabel nilai/peserta didik belum tersedia.']];
        }

        $grup = self::mapelSatuSemester($kunci, $semester);
        $mapelKolom = [];
        $kelompok = [];
        foreach (['umum', 'kejuruan'] as $g) {
            foreach ($grup[$g] as $nama) {
                $mapelKolom[self::kolomKey($nama)] = $nama;
                $kelompok[$nama] = $g;
            }
        }

        if (!$mapelKolom) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Jurusan/semester ini belum punya pemetaan mapel.']];
        }

        // Kolom mapel berkas yang dikenali.
        $kolomBerkas = [];
        foreach (array_keys($rows[0] ?? []) as $k) {
            if (strpos((string) $k, '__col_') === 0) {
                continue;
            }
            $norm = self::kolomKey((string) $k);
            if ($norm === '' || in_array($norm, ['no', 'nisn', 'nama', 'kelas'], true)) {
                continue;
            }
            $kolomBerkas[$norm] = true;
        }
        $dikenali = array_intersect_key($kolomBerkas, $mapelKolom);
        if (!$dikenali) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Tidak ada kolom mapel yang dikenali pada berkas ini.']];
        }
        // Impor dari template terkunci (tanpa kelas): kolom harus persis sama.
        if ($rombelId === null && $kolomBerkas != array_fill_keys(array_keys($mapelKolom), true)) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Template tidak cocok dengan jurusan/semester terpilih. Unduh ulang template pada jurusan & semester ini.']];
        }

        // Kunci siswa: hanya NISN milik kelas (atau jurusan) terpilih yang diterima.
        $nisnMap = $rombelId !== null ? self::nisnKelas($rombelId) : self::nisnJurusan($kunci);
        if (!$nisnMap) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Tidak ada siswa terdaftar pada ' . ($rombelId !== null ? 'kelas' : 'jurusan') . ' ini.']];
        }
        $sumber = $rombelId !== null ? 'kelas' : 'jurusan';

        $tingkat = (string) (($semester + 1) >> 1);

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;
            $nisn = preg_replace('/[^0-9]/', '', (string) ($row['nisn'] ?? $row['__col_1'] ?? ''));
            if ($nisn === '') {
                $skipped++;
                $errors[] = "Baris {$rowNum}: NISN kosong.";
                continue;
            }

            $pdId = $nisnMap[$nisn] ?? null;
            if (!$pdId) {
                $skipped++;
                $errors[] = "Baris {$rowNum}: NISN {$nisn} bukan siswa {$sumber} ini.";
                continue;
            }

            $terisi = 0;
            foreach ($dikenali as $kolom => $_) {
                $nama = $mapelKolom[$kolom];
                $cek = BukuIndukNilaiService::nilaiSah($row[$kolom] ?? '');
                if (!$cek['ok']) {
                    $errors[] = "Baris {$rowNum}: {$nama} {$cek['pesan']} (wajib " . BukuIndukNilaiService::rentangNilai() . ').';
                    continue;
                }
                if ($cek['nilai'] === null) {
                    continue;
                }
                DB::table('buku_induk_nilai')->updateOrInsert(
                    ['peserta_didik_id' => $pdId, 'nama_mata_pelajaran' => $nama, 'semester' => $semester],
                    ['nilai' => $cek['nilai'], 'kelompok' => $kelompok[$nama] ?? 'umum', 'tingkat' => $tingkat, 'updated_at' => now(), 'created_at' => now()]
                );
                $imported++;
                $terisi++;
            }

            if ($terisi === 0) {
                $skipped++;
                $errors[] = "Baris {$rowNum}: tidak ada nilai terisi.";
            }
        }

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 10)];
    }

    /** Id jurusan (konsentrasi + program X) untuk filter siswa. */
    private static function idJurusan(string $kunci): array
    {
        $jr = self::jurusan($kunci);

        return array_values(array_filter([$kunci, $jr['program_id'] ?? null]));
    }

    /** Daftar PETA/aktif satu jurusan (dipakai untuk mengisi template nilai). */
    public static function siswaJurusan(string $kunci): array
    {
        if (!Table::exists('peserta_didik') || !Table::exists('rombongan_belajar')) {
            return [];
        }

        return DB::table('peserta_didik as pd')
            ->join('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->whereIn('rb.jurusan_id', self::idJurusan($kunci))
            ->orderBy('pd.nama')
            ->get(['pd.nama', 'pd.nisn', 'pd.nama_rombel'])
            ->all();
    }

    /** Peta NISN => peserta_didik_id untuk seluruh siswa (aktif + arsip) jurusan terpilih. */
    private static function nisnJurusan(string $kunci): array
    {
        $ids = self::idJurusan($kunci);
        $out = [];

        foreach (['peserta_didik', 'peserta_didik_tidak_aktif'] as $tabel) {
            if (!Table::exists($tabel) || !Table::exists('rombongan_belajar')) {
                continue;
            }
            $rows = DB::table($tabel . ' as p')
                ->join('rombongan_belajar as rb', 'p.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->whereIn('rb.jurusan_id', $ids)
                ->whereNotNull('p.nisn')
                ->select('p.nisn', 'p.peserta_didik_id')
                ->get();
            foreach ($rows as $r) {
                $n = preg_replace('/[^0-9]/', '', (string) $r->nisn);
                if ($n !== '') {
                    $out[$n] = $r->peserta_didik_id;
                }
            }
        }

        return $out;
    }

    /** Cache daftar kelas per request. */
    private static ?array $cacheKelas = null;

    /** Daftar kelas (rombel) untuk pemilih: id, nama, tingkat, kunci jurusan. */
    public static function kelasList(): array
    {
        if (self::$cacheKelas !== null) {
            return self::$cacheKelas;
        }
        if (!Table::exists('rombongan_belajar')) {
            return self::$cacheKelas = [];
        }

        $rows = \App\Support\RombelScope::query()
            ->whereIn('rombongan_belajar_id', function ($q) {
                $q->select('rombongan_belajar_id')->from('peserta_didik')
                    ->whereNotNull('rombongan_belajar_id')->where('rombongan_belajar_id', '!=', '');
            })
            ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id', 'jurusan_id')
            ->orderBy('tingkat_pendidikan_id')->orderBy('nama')->get();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (string) $r->rombongan_belajar_id,
                'nama' => (string) $r->nama,
                'tingkat' => (string) $r->tingkat_pendidikan_id,
                'kunci' => self::idKeKunci($r->jurusan_id !== null ? (string) $r->jurusan_id : null),
            ];
        }

        return self::$cacheKelas = $out;
    }

    /** Satu kelas (rombel) berdasarkan id. */
    public static function kelas(string $id): ?array
    {
        foreach (self::kelasList() as $k) {
            if ($k['id'] === $id) {
                return $k;
            }
        }

        return null;
    }

    /** Siswa aktif satu kelas (untuk input & template nilai per kelas). */
    public static function siswaKelas(string $rombelId): array
    {
        if ($rombelId === '' || !Table::exists('peserta_didik')) {
            return [];
        }

        return DB::table('peserta_didik')
            ->where('rombongan_belajar_id', $rombelId)
            ->orderBy('nama')
            ->get(['peserta_didik_id', 'nama', 'nisn', 'nipd', 'nama_rombel'])
            ->all();
    }

    /** Peta NISN => peserta_didik_id untuk siswa satu kelas (aktif + arsip). */
    private static function nisnKelas(string $rombelId): array
    {
        $out = [];
        foreach (['peserta_didik', 'peserta_didik_tidak_aktif'] as $tabel) {
            if (!Table::exists($tabel)) {
                continue;
            }
            $rows = DB::table($tabel)
                ->where(fn ($w) => $w->where('rombongan_belajar_id', $rombelId)->orWhere('nama_rombel_terakhir', $rombelId))
                ->whereNotNull('nisn')
                ->get(['nisn', 'peserta_didik_id']);
            foreach ($rows as $r) {
                $n = preg_replace('/[^0-9]/', '', (string) $r->nisn);
                if ($n !== '') {
                    $out[$n] = $r->peserta_didik_id;
                }
            }
        }

        return $out;
    }

    /** Resolusi cohort kelulusan buku induk (mengacu tahun ajaran di pengaturan). */
    public static function resolveCohort(?string $requestedTahunLulus = null): array
    {
        $tahunAjaranAktif     = BukuIndukNilaiService::tahunAjaranAktif();
        $tahunAjaranTerpilih  = (string) (BukuIndukNilaiService::pengaturan()->tahun_ajaran ?? '') ?: $tahunAjaranAktif;
        $tahunLulusAktif      = str_contains($tahunAjaranAktif, '/') ? explode('/', $tahunAjaranAktif)[1] : $tahunAjaranAktif;
        $tahunLulusPengaturan = str_contains($tahunAjaranTerpilih, '/') ? explode('/', $tahunAjaranTerpilih)[1] : $tahunAjaranTerpilih;

        $alumniTahunList = Table::exists('peserta_didik_tidak_aktif')
            ? DB::table('peserta_didik_tidak_aktif')
                ->where(fn ($b) => $b->where('status_keluar', 'Alumni')
                    ->orWhere('alasan_keluar', 'like', '%Lulus%')
                    ->orWhere('alasan_keluar', 'like', '%Tamat%'))
                ->selectRaw("COALESCE(NULLIF(YEAR(tanggal_keluar), 0), NULLIF(tahun_lulus, '')) AS th")
                ->pluck('th')
                ->filter(fn ($v) => !empty($v) && is_numeric($v))
                ->unique()
            : collect();

        $filterTahunList = collect([$tahunLulusAktif])->merge($alumniTahunList)->unique()->sortDesc()->values();

        $tahunLulus = trim((string) $requestedTahunLulus);
        if ($tahunLulus === '' || !$filterTahunList->contains($tahunLulus)) {
            $tahunLulus = $tahunLulusPengaturan ?: ($filterTahunList->first() ?? $tahunLulusAktif);
        }

        $isAktif = ((string) $tahunLulus === (string) $tahunLulusAktif);

        return [
            'tahunAjaranAktif' => $tahunAjaranAktif,
            'tahunAjaranTerpilih' => $tahunAjaranTerpilih,
            'tahunLulusAktif' => $tahunLulusAktif,
            'tahunLulusPengaturan' => $tahunLulusPengaturan,
            'filterTahunList' => $filterTahunList,
            'tahunLulus' => $tahunLulus,
            'isAktif' => $isAktif,
        ];
    }

    /** Daftar rombel kelas akhir sesuai cohort (aktif atau alumni). */
    public static function cohortRombelList(string $tahunLulus, bool $isAktif): \Illuminate\Support\Collection
    {
        if ($isAktif) {
            return \App\Support\RombelScope::query()
                ->whereIn('tingkat_pendidikan_id', KelasAkhirScope::TINGKAT)
                ->select('rombongan_belajar_id', 'nama', 'tingkat_pendidikan_id')
                ->orderBy('tingkat_pendidikan_id')->orderBy('nama')->get();
        }

        if (!Table::exists('peserta_didik_tidak_aktif')) {
            return collect();
        }

        return DB::table('peserta_didik_tidak_aktif')
            ->where(fn ($w) => $w->where('tahun_lulus', (string) $tahunLulus)
                ->orWhere('tahun_lulus', "{$tahunLulus}/" . ((int) $tahunLulus + 1))
                ->orWhere('tahun_lulus', ((int) $tahunLulus - 1) . "/{$tahunLulus}")
                ->orWhereYear('tanggal_keluar', (string) $tahunLulus))
            ->where(fn ($b) => $b->where('status_keluar', 'Alumni')
                ->orWhere('alasan_keluar', 'like', '%Lulus%')
                ->orWhere('alasan_keluar', 'like', '%Tamat%'))
            ->whereNotNull('nama_rombel_terakhir')
            ->where('nama_rombel_terakhir', '<>', '')
            ->distinct()
            ->orderBy('nama_rombel_terakhir')
            ->pluck('nama_rombel_terakhir')
            ->map(fn ($nama) => (object) [
                'rombongan_belajar_id' => $nama,
                'nama' => $nama,
                'tingkat_pendidikan_id' => '12',
            ]);
    }

    /** Daftar kelas akhir (dengan kunci kurikulum) sesuai cohort. */
    public static function cohortKelasList(string $tahunLulus, bool $isAktif): array
    {
        if ($isAktif) {
            return array_values(array_filter(
                self::kelasList(),
                fn ($k) => in_array($k['tingkat'], KelasAkhirScope::TINGKAT, true)
            ));
        }

        $rombel = self::cohortRombelList($tahunLulus, false);
        $out = [];
        foreach ($rombel as $r) {
            $nama = (string) $r->nama;
            $kunci = self::kunciJurusan((object) ['nama_rombel' => $nama]);
            $out[] = [
                'id' => $nama,
                'nama' => $nama,
                'tingkat' => '12',
                'kunci' => $kunci,
            ];
        }

        return $out;
    }

    /** Detail satu kelas cohort beserta kunci kurikulum. */
    public static function cohortKelas(string $id, string $tahunLulus, bool $isAktif): ?array
    {
        foreach (self::cohortKelasList($tahunLulus, $isAktif) as $k) {
            if ($k['id'] === $id) {
                return $k;
            }
        }

        return null;
    }

    /** Query builder daftar siswa cohort per kelas (untuk input nilai). */
    public static function cohortSiswaKelasQuery(string $kelasId, string $tahunLulus, bool $isAktif): \Illuminate\Database\Query\Builder
    {
        if ($isAktif) {
            return DB::table('peserta_didik as pd')
                ->where('pd.rombongan_belajar_id', $kelasId)
                ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel');
        }

        return DB::table('peserta_didik_tidak_aktif as pd')
            ->where(fn ($w) => $w->where('pd.nama_rombel_terakhir', $kelasId)->orWhere('pd.rombongan_belajar_id', $kelasId))
            ->where(fn ($w) => $w->where('pd.tahun_lulus', (string) $tahunLulus)
                ->orWhere('pd.tahun_lulus', "{$tahunLulus}/" . ((int) $tahunLulus + 1))
                ->orWhere('pd.tahun_lulus', ((int) $tahunLulus - 1) . "/{$tahunLulus}")
                ->orWhereYear('pd.tanggal_keluar', (string) $tahunLulus))
            ->where(fn ($b) => $b->where('pd.status_keluar', 'Alumni')
                ->orWhere('pd.alasan_keluar', 'like', '%Lulus%')
                ->orWhere('pd.alasan_keluar', 'like', '%Tamat%'))
            ->select('pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.nama_rombel_terakhir as nama_rombel');
    }

    /** Array data siswa satu kelas cohort (untuk template excel nilai). */
    public static function cohortSiswaKelas(string $kelasId, string $tahunLulus, bool $isAktif): array
    {
        return self::cohortSiswaKelasQuery($kelasId, $tahunLulus, $isAktif)
            ->orderBy('pd.nama')
            ->get()
            ->all();
    }

    /** Query builder daftar siswa cohort untuk PKL. */
    public static function cohortSiswaPklQuery(?string $rombelId, string $tahunLulus, bool $isAktif): \Illuminate\Database\Query\Builder
    {
        if ($isAktif) {
            $query = DB::table('peserta_didik as pd')
                ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
                ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
                ->leftJoin('buku_induk_pkl as pkl', 'pd.peserta_didik_id', '=', 'pkl.peserta_didik_id')
                ->where(fn ($sub) => $sub->whereIn('pd.tingkat_pendidikan_id', KelasAkhirScope::TINGKAT)->orWhereIn('rb.tingkat_pendidikan_id', KelasAkhirScope::TINGKAT))
                ->where(fn ($sub) => $sub->whereNull('kbk.status_klaper')->orWhere('kbk.status_klaper', 'aktif'));

            if ($rombelId !== null && $rombelId !== '') {
                $query->where('pd.rombongan_belajar_id', $rombelId);
            }

            return $query->select(
                'pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin',
                'pd.nama_rombel', 'rb.nama as rombel_nama', 'pkl.nama_dudi', 'pkl.alamat_dudi'
            );
        }

        $query = DB::table('peserta_didik_tidak_aktif as pd')
            ->leftJoin('kesiswaan_buku_klaper as kbk', 'pd.peserta_didik_id', '=', 'kbk.peserta_didik_id')
            ->leftJoin('buku_induk_pkl as pkl', 'pd.peserta_didik_id', '=', 'pkl.peserta_didik_id')
            ->where(fn ($w) => $w->where('pd.tahun_lulus', (string) $tahunLulus)
                ->orWhere('pd.tahun_lulus', "{$tahunLulus}/" . ((int) $tahunLulus + 1))
                ->orWhere('pd.tahun_lulus', ((int) $tahunLulus - 1) . "/{$tahunLulus}")
                ->orWhereYear('pd.tanggal_keluar', (string) $tahunLulus))
            ->where(fn ($b) => $b->where('pd.status_keluar', 'Alumni')
                ->orWhere('pd.alasan_keluar', 'like', '%Lulus%')
                ->orWhere('pd.alasan_keluar', 'like', '%Tamat%'));

        if ($rombelId !== null && $rombelId !== '') {
            $query->where(fn ($w) => $w->where('pd.nama_rombel_terakhir', $rombelId)->orWhere('pd.rombongan_belajar_id', $rombelId));
        }

        return $query->select(
            'pd.peserta_didik_id', 'pd.nama', 'pd.nisn', 'pd.nipd', 'pd.jenis_kelamin',
            'pd.nama_rombel_terakhir as nama_rombel', 'pd.nama_rombel_terakhir as rombel_nama',
            'pkl.nama_dudi', 'pkl.alamat_dudi'
        );
    }
}
