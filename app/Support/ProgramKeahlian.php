<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pemetaan konsentrasi keahlian → program keahlian.
 *
 * Dapodik/Spektrum SMK: kelas X memakai nama program keahlian
 * (mis. "Teknik Otomotif"), kelas XI/XII memakai nama konsentrasi keahlian
 * (mis. "Teknik Kendaraan Ringan"). Tanpa pemetaan ini, rekap per jurusan
 * terpecah dan jumlahnya tidak konsisten antar tingkat.
 *
 * Sumber pemetaan: tabel `referensi_jurusan` (nama_program ↔ nama_konsentrasi).
 */
class ProgramKeahlian
{
    /** @var array<string,string>|null  nama (lowercase) → nama program */
    private static ?array $map = null;

    /**
     * Peta nama konsentrasi/program (lowercase) ke nama program keahlian.
     */
    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = [];
        if (Table::exists('referensi_jurusan')) {
            foreach (DB::table('referensi_jurusan')->get(['nama_program', 'nama_konsentrasi']) as $ref) {
                $program = trim((string) $ref->nama_program);
                if ($program === '') continue;
                $map[mb_strtolower($program)] = $program;
                $konsentrasi = trim((string) $ref->nama_konsentrasi);
                if ($konsentrasi !== '') {
                    $map[mb_strtolower($konsentrasi)] = $program;
                }
            }
        }

        return self::$map = $map;
    }

    /**
     * Nama program keahlian dari nama jurusan/konsentrasi apa adanya.
     * Jika tidak ada di referensi, nama asli dikembalikan.
     */
    public static function resolve(?string $nama): string
    {
        $nama = trim((string) $nama);
        if ($nama === '') return '';

        return self::map()[mb_strtolower($nama)] ?? $nama;
    }

    /**
     * Daftar nama program keahlian (unik, terurut) dari sekumpulan nama jurusan.
     *
     * @param  iterable<string|null>  $namaJurusan
     * @return array<int,string>
     */
    public static function listFrom(iterable $namaJurusan): array
    {
        $program = [];
        foreach ($namaJurusan as $nama) {
            $p = self::resolve($nama);
            if ($p !== '') $program[$p] = true;
        }
        $program = array_keys($program);
        sort($program);

        return $program;
    }

    /**
     * Nama jurusan mentah (jurusan_id_str) yang benar-benar ada di Dapodik
     * dan termasuk satu program keahlian.
     *
     * @return array<int,string>
     */
    public static function jurusanNamesOf(string $program): array
    {
        if (!Table::exists('rombongan_belajar')) return [];

        $names = [];
        $raw = DB::table('rombongan_belajar')
            ->whereNotNull('jurusan_id_str')
            ->where('jurusan_id_str', '<>', '')
            ->distinct()
            ->pluck('jurusan_id_str');

        foreach ($raw as $nama) {
            if (self::resolve($nama) === $program) {
                $names[mb_strtolower($nama)] = $nama; // dedupe, pertahankan case asli DB
            }
        }

        return array_values($names);
    }

    /**
     * Gabungkan baris rekap per `jurusan_id` mentah menjadi rekap per PROGRAM keahlian.
     *
     * Dapodik mencatat kelas X dengan nama program (mis. "Teknik Otomotif") dan
     * kelas XI/XII dengan nama konsentrasi (mis. "Teknik Kendaraan Ringan") sehingga
     * satu jurusan nyata terpecah menjadi 2 id. Helper ini menyatukannya kembali.
     *
     * Kolom numerik dijumlahkan, kolom teks diambil dari baris level program.
     *
     * @param  iterable<object|array>  $rows  tiap baris wajib punya `kode` & `nama`;
     *                                        kolom lain opsional dan ikut dijumlah/disalin
     * @return array<int,array<string,mixed>>  tiap baris: kode, nama, ids, + kolom lain
     */
    public static function mergeRows(iterable $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $data = (array) $row;
            $nama = trim((string) ($data['nama'] ?? ''));
            $canon = self::resolve($nama);
            if ($canon === '') continue;

            $key = mb_strtolower($canon);
            $kode = (string) ($data['kode'] ?? '');
            unset($data['kode'], $data['nama']);

            if (!isset($out[$key])) {
                $out[$key] = ['kode' => $kode, 'nama' => $canon, 'ids' => []];
                foreach ($data as $field => $value) {
                    $out[$key][$field] = is_numeric($value) ? 0 : null;
                }
            }

            // Id induk = id level PROGRAM (5 digit), konsentrasi selalu 8 digit.
            // Nama program & konsentrasi bisa sama (mis. Kehutanan), jadi panjang kode
            // dipakai sebagai penentu, bukan kecocokan nama.
            $isProgramRow = $kode !== '' && strlen($kode) <= strlen($out[$key]['kode']);
            if ($isProgramRow) {
                $out[$key]['kode'] = $kode;
            }

            if ($kode !== '') $out[$key]['ids'][] = $kode;

            foreach ($data as $field => $value) {
                if (is_numeric($value)) {
                    $out[$key][$field] = ($out[$key][$field] ?? 0) + $value;
                } elseif (($isProgramRow || $out[$key][$field] === null) && $value !== null && $value !== '') {
                    $out[$key][$field] = $value;
                }
            }
        }

        foreach ($out as $key => $row) {
            $out[$key]['ids'] = array_values(array_unique($row['ids']));
        }

        return array_values($out);
    }

    /**
     * Nama program keahlian dari sebuah `jurusan_id` mentah.
     * Hasil di-cache per request agar tidak query berulang.
     */
    public static function programOfId(string $jurusanId): string
    {
        static $byId = null;

        if ($byId === null) {
            $byId = [];
            if (Table::exists('rombongan_belajar')) {
                foreach (DB::table('rombongan_belajar')
                    ->whereNotNull('jurusan_id')
                    ->where('jurusan_id', '<>', '')
                    ->select('jurusan_id', 'jurusan_id_str')
                    ->distinct()
                    ->get() as $r) {
                    $byId[(string) $r->jurusan_id] = self::resolve($r->jurusan_id_str);
                }
            }
        }

        return $byId[$jurusanId] ?? '';
    }

    /**
     * Semua `jurusan_id` mentah (program + konsentrasi) milik satu program keahlian.
     *
     * @return array<int,string>
     */
    public static function idsFor(?string $nama): array
    {
        $program = self::resolve($nama);
        if ($program === '' || !Table::exists('rombongan_belajar')) return [];

        $names = array_map('mb_strtolower', self::jurusanNamesOf($program));

        return DB::table('rombongan_belajar')
            ->whereNotNull('jurusan_id')
            ->where('jurusan_id', '<>', '')
            ->whereIn(DB::raw('LOWER(jurusan_id_str)'), $names)
            ->distinct()
            ->pluck('jurusan_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }
}
