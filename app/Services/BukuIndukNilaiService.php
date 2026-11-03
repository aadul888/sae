<?php

namespace App\Services;

use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Logika nilai & daftar mata pelajaran Buku Induk.
 *
 * Satu sumber kebenaran untuk pemetaan mapel (per semester, dengan daftar global
 * sebagai cadangan) dan matriks nilai 6 semester (Kelas X: 1-2, XI: 3-4, XII: 5-6).
 * Dipakai oleh tab buku induk, maping mapel, impor nilai, dan lembar cetak A3.
 */
class BukuIndukNilaiService
{
    /**
     * Ambil data peserta didik untuk keperluan buku induk/nilai, baik dari
     * tabel aktif (`peserta_didik`) maupun arsip alumni (`peserta_didik_tidak_aktif`).
     * Alumni dicocokkan lewat `peserta_didik_id` yang sama.
     */
    public static function findPesertaDidik($id)
    {
        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $id)->first();
        if ($pd) {
            return $pd;
        }
        if (Table::exists('peserta_didik_tidak_aktif')) {
            return DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        return null;
    }

    /** Daftar mapel bawaan bila pengaturan belum diisi (ikuti format Kemdikbud). */
    public const MAPEL_UMUM = [
        'Pendidikan Agama Islam dan Budi Pekerti',
        'Pendidikan Pancasila',
        'Bahasa Indonesia',
        'Pendidikan Jasmani olahraga dan kesehatan',
        'Sejarah',
        'Seni Budaya (Seni Rupa)',
        'Muatan Lokal (Bahasa Sunda)',
    ];

    public const MAPEL_KEJURUAN = [
        'Matematika',
        'Bahasa Inggris',
        'Informatika',
        'Projek IPAS',
        'Dasar Dasar Keahlian',
        'Konsentrasi Keahlian',
        'Projek Kreatif dan Kewirausahaan',
        'Praktik Kerja Lapangan',
        'Mata Pelajaran Pilihan',
    ];

    /**
     * Referensi mata pelajaran default yang diambil dari tabel `pembelajaran`
     * (data pembelajaran Dapodik). Mapel berstatus "Wajib" masuk kelompok umum,
     * sisanya (pilihan/praktik/ekstensi) masuk kelompok kejuruan.
     * Bila tabel kosong, jatuh ke daftar bawaan Kemdikbud.
     *
     * @return array{umum: array<int,string>, kejuruan: array<int,string>}
     */
    public static function referensiMapel(): array
    {
        if (!Table::exists('pembelajaran')) {
            return ['umum' => self::MAPEL_UMUM, 'kejuruan' => self::MAPEL_KEJURUAN];
        }

        $rows = DB::table('pembelajaran')
            ->whereNotNull('nama_mata_pelajaran')
            ->where('nama_mata_pelajaran', '!=', '')
            ->select('nama_mata_pelajaran', 'status_di_kurikulum_str')
            ->get();

        $umum = [];
        $kejuruan = [];
        foreach ($rows as $r) {
            $nama = trim((string) $r->nama_mata_pelajaran);
            if ($nama === '') {
                continue;
            }
            $status = (string) ($r->status_di_kurikulum_str ?? '');
            $isUmum = stripos($status, 'Wajib') !== false;
            if ($isUmum) {
                if (!in_array($nama, $umum, true)) {
                    $umum[] = $nama;
                }
            } elseif (!in_array($nama, $kejuruan, true)) {
                $kejuruan[] = $nama;
            }
        }

        sort($umum, SORT_NATURAL | SORT_FLAG_CASE);
        sort($kejuruan, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'umum' => $umum ?: self::MAPEL_UMUM,
            'kejuruan' => $kejuruan ?: self::MAPEL_KEJURUAN,
        ];
    }

    /** Tahun ajaran aktif (pergantian tahun ajaran setiap Juli). */
    public static function tahunAjaranAktif(): string
    {
        $now = now();
        $y = (int) $now->format('Y');

        return $now->month >= 7 ? "{$y}/" . ($y + 1) : ($y - 1) . "/{$y}";
    }

    /**
     * Kepala sekolah dari data GTK.
     *
     * @return array{nama: ?string, nip: ?string}
     */
    public static function kepalaSekolah(): array
    {
        if (!Table::exists('gtk')) {
            return ['nama' => null, 'nip' => null];
        }

        $row = DB::table('gtk')
            ->where(function ($q) {
                $q->where('jenis_ptk_id_str', 'like', '%Kepala Sekolah%')
                  ->orWhere('jabatan_ptk_id_str', 'like', '%Kepala Sekolah%');
            })
            ->select('nama', 'nip')
            ->first();

        return ['nama' => $row->nama ?? null, 'nip' => $row->nip ?? null];
    }

    /**
     * Petugas buku induk dari tugas tambahan (Kesiswaan / Kepala TAS).
     *
     * @return array{nama: ?string, nip: ?string}
     */
    public static function petugasBukuInduk(): array
    {
        if (!Table::exists('ptk_tugas_tambahan') || !Table::exists('ref_tugas_tambahan') || !Table::exists('gtk')) {
            return ['nama' => null, 'nip' => null];
        }

        $row = DB::table('ptk_tugas_tambahan as ptt')
            ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
            ->join('gtk', 'ptt.ptk_id', '=', 'gtk.ptk_id')
            ->whereIn('rtt.kode', ['STAF_KESISWAAN', 'WAKA_KESISWAAN', 'KEPALA_TAS'])
            ->where('ptt.is_active', true)
            ->select('gtk.nama', 'gtk.nip')
            ->first();

        return ['nama' => $row->nama ?? null, 'nip' => $row->nip ?? null];
    }

    /** Pengaturan singleton; dibuat otomatis bila belum ada (terisi data sekolah/GTK). */
    public static function pengaturan(): object
    {
        $ref = self::referensiMapel();
        $defUmum = json_encode($ref['umum']);
        $defKejuruan = json_encode($ref['kejuruan']);
        $kepsek = self::kepalaSekolah();
        $petugas = self::petugasBukuInduk();
        $ta = self::tahunAjaranAktif();

        if (!Table::exists('buku_induk_pengaturan')) {
            return (object) [
                'tahun_ajaran' => $ta,
                'nama_kepala_sekolah' => $kepsek['nama'],
                'nip_kepala_sekolah' => $kepsek['nip'],
                'nama_petugas' => $petugas['nama'],
                'nip_petugas' => $petugas['nip'],
                'mapel_umum' => $defUmum,
                'mapel_kejuruan' => $defKejuruan,
                'mapel_semester' => null,
            ];
        }

        $row = DB::table('buku_induk_pengaturan')->orderBy('id')->first();
        if (!$row) {
            DB::table('buku_induk_pengaturan')->insert([
                'tahun_ajaran' => $ta,
                'nama_kepala_sekolah' => $kepsek['nama'],
                'nip_kepala_sekolah' => $kepsek['nip'],
                'nama_petugas' => $petugas['nama'],
                'nip_petugas' => $petugas['nip'],
                'mapel_umum' => $defUmum,
                'mapel_kejuruan' => $defKejuruan,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('buku_induk_pengaturan')->orderBy('id')->first();
        }

        return $row;
    }

    /**
     * Nilai pengaturan untuk form: pakai nilai tersimpan bila ada; bila kosong
     * ambil default dari data sekolah/GTK/tugas tambahan (tahun ajaran, kepsek, petugas).
     *
     * @return array<string, ?string>
     */
    public static function pengaturanForm(): array
    {
        $p = self::pengaturan();
        $kepsek = self::kepalaSekolah();
        $petugas = self::petugasBukuInduk();

        $pick = fn ($stored, $fallback) => ($stored !== null && trim((string) $stored) !== '') ? $stored : $fallback;

        return [
            'tahun_ajaran' => $pick($p->tahun_ajaran ?? null, self::tahunAjaranAktif()),
            'nama_kepala_sekolah' => $pick($p->nama_kepala_sekolah ?? null, $kepsek['nama']),
            'nip_kepala_sekolah' => $pick($p->nip_kepala_sekolah ?? null, $kepsek['nip']),
            'nama_petugas' => $pick($p->nama_petugas ?? null, $petugas['nama']),
            'nip_petugas' => $pick($p->nip_petugas ?? null, $petugas['nip']),
        ];
    }

    /** Daftar mapel terkelompok: ['umum' => [...], 'kejuruan' => [...]]. */
    public static function mapelList(): array
    {
        $p = self::pengaturan();
        $ref = self::referensiMapel();

        $decode = function ($val, array $fallback) {
            $arr = is_string($val) ? (json_decode($val, true) ?: []) : (is_array($val) ? $val : []);
            $arr = array_values(array_filter(array_map('trim', $arr), fn ($v) => $v !== ''));
            return $arr ?: $fallback;
        };

        return [
            'umum' => $decode($p->mapel_umum ?? null, $ref['umum']),
            'kejuruan' => $decode($p->mapel_kejuruan ?? null, $ref['kejuruan']),
        ];
    }

    /** JSON pemetaan mapel per semester yang tersimpan (mentah). */
    private static function mapelSemesterJson(): array
    {
        $raw = self::pengaturan()->mapel_semester ?? null;
        $arr = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);

        return is_array($arr) ? $arr : [];
    }

    /** Bersihkan daftar nama mapel dari input form/import. */
    private static function bersihMapel($v): array
    {
        $arr = is_array($v) ? $v : [];
        return array_values(array_filter(array_map(fn ($x) => trim((string) $x), $arr), fn ($x) => $x !== ''));
    }

    /**
     * Pemetaan mapel PER SEMESTER.
     *
     * Semester yang belum pernah dipetakan memakai daftar mapel global; semester
     * yang sudah dipetakan tetapi daftarnya kosong berarti tidak ada mapel diajarkan
     * (mis. semester ganjil tanpa mapel tertentu).
     *
     * @return array<int, array{umum: array<int,string>, kejuruan: array<int,string>}>
     */
    public static function mapelPerSemester(): array
    {
        $global = self::mapelList();
        $stored = self::mapelSemesterJson();

        $out = [];
        foreach (range(1, 6) as $sem) {
            $key = (string) $sem;
            $out[$sem] = array_key_exists($key, $stored)
                ? ['umum' => self::bersihMapel($stored[$key]['umum'] ?? []), 'kejuruan' => self::bersihMapel($stored[$key]['kejuruan'] ?? [])]
                : ['umum' => $global['umum'], 'kejuruan' => $global['kejuruan']];
        }

        return $out;
    }

    /** Semester yang sudah dipetakan eksplisit (mempunyai kunci di JSON). */
    public static function mapelSemesterTerpetakan(): array
    {
        $stored = self::mapelSemesterJson();
        $out = [];
        foreach (range(1, 6) as $sem) {
            $out[$sem] = array_key_exists((string) $sem, $stored);
        }

        return $out;
    }

    /** Peta nama mapel => kelompok dari seluruh pemetaan semester (dipakai saat simpan nilai). */
    public static function kelompokMapel(): array
    {
        $map = [];
        foreach (self::mapelPerSemester() as $lst) {
            foreach (['umum', 'kejuruan'] as $grup) {
                foreach ($lst[$grup] as $nama) {
                    $map[$nama] = $map[$nama] ?? $grup;
                }
            }
        }
        foreach (self::mapelList() as $grup => $list) {
            foreach ($list as $nama) {
                $map[$nama] = $map[$nama] ?? $grup;
            }
        }

        return $map;
    }

    /** Simpan pemetaan mapel satu semester (menandai semester sebagai sudah dipetakan). */
    public static function saveMapelSemester(int $semester, array $umum, array $kejuruan): bool
    {
        if ($semester < 1 || $semester > 6 || !Table::exists('buku_induk_pengaturan')) {
            return false;
        }

        $stored = self::mapelSemesterJson();
        $stored[(string) $semester] = [
            'umum' => self::bersihMapel($umum),
            'kejuruan' => self::bersihMapel($kejuruan),
        ];

        $p = self::pengaturan();
        DB::table('buku_induk_pengaturan')->where('id', $p->id)->update([
            'mapel_semester' => json_encode($stored),
            'updated_at' => now(),
        ]);

        return true;
    }

    /**
     * Impor pemetaan mapel PER SEMESTER dari Excel/CSV.
     *
     * Kolom: `Semester`, `Kelompok` (opsional: umum/kejuruan), `Nama Mata Pelajaran`.
     * Baris ditambahkan ke semester terkait (menandai semester itu sudah dipetakan)
     * atau menggantinya bila `$replace` true.
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>, semesters:array<int,int>}
     */
    public static function importMapelSemesterRows(array $rows, ?int $forcedSemester = null, bool $replace = true): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $lists = self::mapelPerSemester();
        $acc = []; // hasil berkas: sem => ['umum'=>[], 'kejuruan'=>[]]

        $get = function (array $row, array $keys, int $fallback) {
            foreach ($keys as $k) {
                if (isset($row[$k]) && trim((string) $row[$k]) !== '') {
                    return trim((string) $row[$k]);
                }
            }
            $vals = array_values($row);
            return isset($vals[$fallback]) ? trim((string) $vals[$fallback]) : '';
        };

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;

            $semRaw = $forcedSemester ?: (int) preg_replace('/[^0-9]/', '', $get($row, ['semester', 'smt', 'sem'], 0));
            $sem = (int) $semRaw;
            if ($sem < 1 || $sem > 6) {
                $skipped++;
                $errors[] = "Baris {$rowNum}: semester tidak valid.";
                continue;
            }

            $nama = $get($row, ['nama_mata_pelajaran', 'mata_pelajaran', 'nama mapel', 'mapel', 'nama'], -1);
            if ($nama === '') {
                $skipped++;
                $errors[] = "Baris {$rowNum}: nama mata pelajaran kosong.";
                continue;
            }

            $kelompokRaw = strtolower($get($row, ['kelompok', 'group', 'kategori'], -1));
            $kelompok = str_contains($kelompokRaw, 'kejuruan') ? 'kejuruan' : 'umum';

            $acc[$sem] = $acc[$sem] ?? ['umum' => [], 'kejuruan' => []];
            if (!in_array($nama, $acc[$sem][$kelompok], true)) {
                $acc[$sem][$kelompok][] = $nama;
                $imported++;
            } else {
                $skipped++;
            }
        }

        foreach ($acc as $sem => $lst) {
            $out = $replace
                ? $lst
                : [
                    'umum' => array_values(array_unique(array_merge($lists[$sem]['umum'], $lst['umum']))),
                    'kejuruan' => array_values(array_unique(array_merge($lists[$sem]['kejuruan'], $lst['kejuruan']))),
                ];
            self::saveMapelSemester((int) $sem, $out['umum'], $out['kejuruan']);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
            'semesters' => array_map('intval', array_keys($acc)),
        ];
    }

    /**
     * Matriks nilai satu siswa untuk lembar cetak / modal atur nilai.
     *
     * @param  string  $akademik  'aktif' (tampilkan semua 6 semester + dummy) atau
     *                            'alumni' (hanya semester kelas akhir 5–6, tanpa dummy).
     * @return array<int, array{nama:string, kelompok:string, nilai:array<int,float|null>, akhir:float|null}>
     */
    public static function matriks($pesertaDidikId, string $akademik = 'aktif'): array
    {
        $perSemester = self::mapelPerSemester();

        // nilai tersimpan: [nama_mapel][semester] => nilai
        $tersimpan = [];
        if (Table::exists('buku_induk_nilai')) {
            $rows = DB::table('buku_induk_nilai')
                ->where('peserta_didik_id', $pesertaDidikId)
                ->get();
            foreach ($rows as $r) {
                $tersimpan[$r->nama_mata_pelajaran][(int) $r->semester] = (float) $r->nilai;
            }
        }

        $isAlumni = ($akademik === 'alumni');
        $semesters = $isAlumni ? [5, 6] : [1, 2, 3, 4, 5, 6];

        // Urutan baris: kemunculan pertama sebuah mapel per kelompok (semester 1..6),
        // sehingga mapel yang hanya diajarkan di semester tertentu tetap tampil.
        $urutan = [];
        foreach (['umum', 'kejuruan'] as $kelompok) {
            foreach ($semesters as $sem) {
                foreach ($perSemester[$sem][$kelompok] as $nama) {
                    if (!isset($urutan[$nama])) {
                        $urutan[$nama] = $kelompok;
                    }
                }
            }
        }

        $hasil = [];
        foreach ($urutan as $nama => $kelompok) {
            $nilai = [];
            foreach ($semesters as $sem) {
                // Kosong bila mapel tidak diajarkan pada semester itu.
                $diajarkan = in_array($nama, $perSemester[$sem][$kelompok] ?? [], true);
                $nilai[$sem] = $diajarkan
                    ? ($tersimpan[$nama][$sem] ?? ($isAlumni ? null : self::dummy($pesertaDidikId, $nama, $sem)))
                    : null;
            }
            $terisi = array_filter($nilai, fn ($v) => $v !== null);
            $hasil[] = [
                'nama' => $nama,
                'kelompok' => $kelompok,
                'nilai' => $nilai,
                'akhir' => $terisi ? round(array_sum($terisi) / count($terisi), 2) : null,
            ];
        }

        return $hasil;
    }

    /**
     * Nilai dummy deterministik saat nilai asli belum tersedia.
     * ponytail: hapus saat impor nilai asli sudah rutin dipakai.
     */
    public static function dummy(string $pesertaDidikId, string $mapel, int $semester): float
    {
        $seed = crc32($pesertaDidikId . '|' . $mapel . '|' . $semester);
        return (float) (78 + ($seed % 15));
    }

    /**
     * Impor nilai dari baris Excel.
     *
     * Mendukung dua format:
     *  1. Lebar (template baru): NISN, Nama Mata Pelajaran, Semester 1..6 (satu baris per mapel).
     *  2. Panjang (format lama): NISN, Nama Mata Pelajaran, Semester, Nilai.
     *
     * @return array{imported:int, skipped:int, errors:array<int,string>}
     */
    public static function importRows(array $rows): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        if (!Table::exists('buku_induk_nilai') || !Table::exists('peserta_didik')) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Tabel nilai/peserta didik belum tersedia.']];
        }

        $nisnMap = DB::table('peserta_didik')
            ->whereNotNull('nisn')
            ->pluck('peserta_didik_id', 'nisn');

        $extract = function (array $row, array $keys, int $fallback) {
            foreach ($keys as $k) {
                if (isset($row[$k]) && trim((string) $row[$k]) !== '') {
                    return trim((string) $row[$k]);
                }
            }
            $vals = array_values($row);
            return isset($vals[$fallback]) ? trim((string) $vals[$fallback]) : '';
        };

        // Resolusi nama mapel: utamakan kolom header "Nama Mata Pelajaran"; jika tidak ada,
        // cari kolom yang mengandung kata 'pelajaran'/'mapel'/'mata' (tahan terhadap kolom
        // tambahan Nama & Kelas), baru terakhir fallback posisi.
        $mapelValue = function (array $row) use ($extract) {
            $val = $extract($row, ['nama_mata_pelajaran', 'mata_pelajaran', 'mapel'], -1);
            if ($val !== '') {
                return $val;
            }
            foreach ($row as $k => $v) {
                if (strpos($k, '__col_') === 0) {
                    continue;
                }
                if (preg_match('/(pelajaran|mapel|mata)/i', $k) && trim((string) $v) !== '') {
                    return trim((string) $v);
                }
            }
            return '';
        };

        // Ambil nilai sel berdasarkan nama kolom semester (mis. "semester 1" / "semester_1" / "s1").
        // Bila kolom bernama ada, nilainya dipercaya (kosong = belum diisi). Format lama
        // (Semester + Nilai tanpa kolom semester 1..6) ditangani pada cabang khusus di bawah.
        $semesterValue = function (array $row, int $sem) {
            $keys = [
                'semester_' . $sem, 'semester ' . $sem, 'semester' . $sem,
                'sem_' . $sem, 'sem ' . $sem, 'sem' . $sem, 's' . $sem,
            ];
            foreach ($keys as $k) {
                if (array_key_exists($k, $row)) {
                    return trim((string) $row[$k]);
                }
            }
            return '';
        };

        DB::transaction(function () use ($rows, $nisnMap, $extract, $mapelValue, $semesterValue, &$imported, &$skipped, &$errors) {
            foreach ($rows as $idx => $row) {
                $rowNum = $idx + 2;
                $nisn = preg_replace('/[^0-9]/', '', $extract($row, ['nisn'], 0));
                $mapel = $mapelValue($row);

                if ($nisn === '' || $mapel === '') {
                    $skipped++;
                    $errors[] = "Baris {$rowNum}: data tidak lengkap (NISN/Mapel).";
                    continue;
                }

                $pdId = $nisnMap[$nisn] ?? null;
                if (!$pdId) {
                    $skipped++;
                    $errors[] = "Baris {$rowNum}: NISN {$nisn} tidak ditemukan.";
                    continue;
                }

                // Kumpulkan nilai per semester. Format lebar (6 kolom) & panjang (kolom tunggal) sama-sama tertangani:
                // format panjang akan mengisi hanya satu semester (kolom semester + nilai).
                $values = [];
                for ($sem = 1; $sem <= 6; $sem++) {
                    $raw = $semesterValue($row, $sem);
                    if ($raw !== '') {
                        $values[$sem] = (float) str_replace(',', '.', $raw);
                    }
                }

                // Format panjang: "Semester" + "Nilai"
                if (empty($values)) {
                    $semester = (int) $extract($row, ['semester', 'smt'], 2);
                    $nilaiRaw = $extract($row, ['nilai', 'score'], 3);
                    if ($semester >= 1 && $semester <= 6 && $nilaiRaw !== '') {
                        $values[$semester] = (float) str_replace(',', '.', $nilaiRaw);
                    }
                }

                if (empty($values)) {
                    $skipped++;
                    $errors[] = "Baris {$rowNum}: tidak ada nilai semester yang terisi.";
                    continue;
                }

                foreach ($values as $semester => $nilai) {
                    DB::table('buku_induk_nilai')->updateOrInsert(
                        ['peserta_didik_id' => $pdId, 'nama_mata_pelajaran' => $mapel, 'semester' => $semester],
                        ['nilai' => $nilai, 'kelompok' => 'umum', 'tingkat' => (string) (($semester + 1) >> 1), 'updated_at' => now(), 'created_at' => now()]
                    );
                    $imported++;
                }
            }
        });

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => array_slice($errors, 0, 10)];
    }
}
