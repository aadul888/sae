<?php

namespace App\Services;

use App\Models\Gtk;
use App\Models\PesertaDidik;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use ZipArchive;

/**
 * Service penarikan dan ekspor Data Kebutuhan MBG (Makan Bergizi Gratis).
 * 100% Native PHP ZipArchive OpenXML - zero external dependency.
 */
class MbgService
{
    private static ?array $gtkParentCache = null;

    /**
     * Cari jalur file template MBG (resources/templates atau storage/app/templates).
     */
    public static function getTemplatePath(): string
    {
        $resPath = resource_path('templates/template_mbg.xlsx');
        if (file_exists($resPath)) {
            return $resPath;
        }

        return storage_path('app/templates/template_mbg.xlsx');
    }

    /**
     * Ambil pemetaan nama orang tua GTK dari file cache JSON lokal.
     */
    public static function getGtkParentMap(): array
    {
        if (self::$gtkParentCache !== null) {
            return self::$gtkParentCache;
        }

        $map = [];
        $candidates = [
            resource_path('templates/gtk_parents_map.json'),
            storage_path('app/templates/gtk_parents_map.json'),
        ];

        foreach ($candidates as $mapFile) {
            if (file_exists($mapFile)) {
                $decoded = json_decode((string) file_get_contents($mapFile), true);
                if (is_array($decoded)) {
                    $map = $decoded;
                    break;
                }
            }
        }

        self::$gtkParentCache = $map;

        return $map;
    }

    /**
     * Hitung ringkasan statistik penerima MBG.
     */
    public static function getStats(): array
    {
        $k10 = PesertaDidik::where('tingkat_pendidikan_id', '10')->count();
        $k11 = PesertaDidik::where('tingkat_pendidikan_id', '11')->count();
        $k12 = PesertaDidik::where('tingkat_pendidikan_id', '12')->count();
        $totalSiswa = $k10 + $k11 + $k12;

        $guru = Gtk::where(function ($q) {
            $q->where('jenis_ptk_id_str', 'like', '%Guru%')
                ->orWhere('jenis_ptk_id_str', 'like', '%Kepala Sekolah%');
        })->count();

        $tendik = Gtk::where('jenis_ptk_id_str', 'not like', '%Guru%')
            ->where('jenis_ptk_id_str', 'not like', '%Kepala Sekolah%')
            ->count();

        $totalGtk = $guru + $tendik;

        return [
            'total_penerima' => $totalSiswa + $totalGtk,
            'total_siswa' => $totalSiswa,
            'kelas_10' => $k10,
            'kelas_11' => $k11,
            'kelas_12' => $k12,
            'gtk_total' => $totalGtk,
            'guru' => $guru,
            'tendik' => $tendik,
        ];
    }

    /**
     * Ambil data paginasi berdasarkan tab yang aktif.
     */
    public static function getData(string $tab, array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $jkFilter = trim((string) ($filters['jk'] ?? ''));

        if ($tab === 'guru_tendik') {
            $query = Gtk::query();

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nama', 'like', "%{$q}%")
                        ->orWhere('nik', 'like', "%{$q}%")
                        ->orWhere('nip', 'like', "%{$q}%");
                });
            }

            if ($jkFilter === '392') {
                $query->where('jenis_kelamin', 'L');
            } elseif ($jkFilter === '64') {
                $query->where('jenis_kelamin', 'P');
            }

            $query->orderBy('nama', 'asc');

            $paginated = $query->paginate($perPage)->withQueryString();
            $parentMap = self::getGtkParentMap();

            $paginated->getCollection()->transform(function ($item) use ($parentMap) {
                $isGuru = str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru')
                    || str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'kepala');
                $posisiId = $isGuru ? 2 : 3;
                $posisiLabel = $isGuru ? 'Guru' : 'Tenaga Kependidikan';
                $nik = trim((string) $item->nik);
                $ortu = $parentMap[$nik] ?? '-';

                $tglFormatted = '-';
                if (! empty($item->tanggal_lahir)) {
                    try {
                        $tglFormatted = Carbon::parse($item->tanggal_lahir)->format('d-m-Y');
                    } catch (\Throwable $e) {
                        $tglFormatted = (string) $item->tanggal_lahir;
                    }
                }

                return (object) [
                    'nisn' => '-',
                    'nik' => $nik ?: '-',
                    'nama' => $item->nama,
                    'tanggal_lahir' => $tglFormatted,
                    'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                    'jenis_kelamin' => $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                    'nama_orang_tua' => $ortu,
                    'posisi' => $posisiId,
                    'posisi_label' => $posisiLabel,
                    'status_menerima' => true,
                    'status_label' => 'Aktif',
                ];
            });

            return $paginated;
        }

        // Siswa (kelas_10, kelas_11, kelas_12)
        $tingkat = match ($tab) {
            'kelas_10' => '10',
            'kelas_11' => '11',
            'kelas_12' => '12',
            default => '10',
        };

        $query = PesertaDidik::where('tingkat_pendidikan_id', $tingkat);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nisn', 'like', "%{$q}%")
                    ->orWhere('nik', 'like', "%{$q}%");
            });
        }

        if ($jkFilter === '392') {
            $query->where('jenis_kelamin', 'L');
        } elseif ($jkFilter === '64') {
            $query->where('jenis_kelamin', 'P');
        }

        $query->orderBy('nama', 'asc');

        $paginated = $query->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function ($item) {
            $ortu = $item->nama_ibu ?: ($item->nama_ayah ?: ($item->nama_wali ?: '-'));

            $tglFormatted = '-';
            if (! empty($item->tanggal_lahir)) {
                try {
                    $tglFormatted = Carbon::parse($item->tanggal_lahir)->format('d-m-Y');
                } catch (\Throwable $e) {
                    $tglFormatted = (string) $item->tanggal_lahir;
                }
            }

            return (object) [
                'nisn' => $item->nisn ?: '-',
                'nik' => $item->nik ?: '-',
                'nama' => $item->nama,
                'tanggal_lahir' => $tglFormatted,
                'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                'jenis_kelamin' => $item->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
                'nama_orang_tua' => $ortu,
                'posisi' => 1,
                'posisi_label' => 'Penerima Utama',
                'status_menerima' => true,
                'status_label' => 'Aktif',
            ];
        });

        return $paginated;
    }

    /**
     * Konversi tanggal 'Y-m-d' ke serial number Excel.
     */
    private static function dateToExcelSerial(?string $dateStr): ?int
    {
        if (empty($dateStr)) {
            return null;
        }

        try {
            $c = Carbon::parse($dateStr);
            $base = Carbon::create(1899, 12, 30, 0, 0, 0);

            return (int) $base->diffInDays($c, false);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Escape teks XML agar aman ditulis ke OpenXML.
     */
    private static function escapeXml(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Buat XML baris data untuk satu sheet.
     */
    private static function buildSheetXml(string $originalXml, array $rows): string
    {
        // 1. Ambil baris 1 s.d 3 (Header, Subheader, dan Contoh)
        $posRow4 = strpos($originalXml, '<row r="4"');
        if ($posRow4 === false) {
            $posEndData = strpos($originalXml, '</sheetData>');
            $headerPart = substr($originalXml, 0, $posEndData);
        } else {
            $headerPart = substr($originalXml, 0, $posRow4);
        }

        // 2. Generate baris data mulai dari baris 4
        $curRow = 4;
        $dataXml = '';

        foreach ($rows as $item) {
            $dataXml .= '<row r="' . $curRow . '" spans="1:8" x14ac:dyDescent="0.25">';

            // Col 1 (A): NISN
            $nisn = trim((string) ($item['nisn'] ?? ''));
            if ($nisn !== '') {
                $dataXml .= '<c r="A' . $curRow . '" s="4" t="inlineStr"><is><t>' . self::escapeXml($nisn) . '</t></is></c>';
            } else {
                $dataXml .= '<c r="A' . $curRow . '" s="4"/>';
            }

            // Col 2 (B): NIK
            $nik = trim((string) ($item['nik'] ?? ''));
            if ($nik !== '') {
                $dataXml .= '<c r="B' . $curRow . '" s="4" t="inlineStr"><is><t>' . self::escapeXml($nik) . '</t></is></c>';
            } else {
                $dataXml .= '<c r="B' . $curRow . '" s="4"/>';
            }

            // Col 3 (C): Nama Penerima
            $nama = trim((string) ($item['nama'] ?? ''));
            $dataXml .= '<c r="C' . $curRow . '" s="12" t="inlineStr"><is><t>' . self::escapeXml($nama) . '</t></is></c>';

            // Col 4 (D): Tanggal Lahir (Excel serial date atau inlineStr)
            $serialDate = self::dateToExcelSerial($item['tanggal_lahir_raw'] ?? null);
            if ($serialDate !== null) {
                $dataXml .= '<c r="D' . $curRow . '" s="5"><v>' . $serialDate . '</v></c>';
            } else {
                $tgl = trim((string) ($item['tanggal_lahir'] ?? ''));
                $dataXml .= '<c r="D' . $curRow . '" s="5" t="inlineStr"><is><t>' . self::escapeXml($tgl) . '</t></is></c>';
            }

            // Col 5 (E): Jenis Kelamin ID (392 / 64)
            $jkId = (int) ($item['jenis_kelamin_id'] ?? 392);
            $dataXml .= '<c r="E' . $curRow . '" s="12"><v>' . $jkId . '</v></c>';

            // Col 6 (F): Nama Orang Tua
            $ortu = trim((string) ($item['nama_orang_tua'] ?? '-'));
            $dataXml .= '<c r="F' . $curRow . '" s="12" t="inlineStr"><is><t>' . self::escapeXml($ortu ?: '-') . '</t></is></c>';

            // Col 7 (G): Posisi ID (1, 2, 3)
            $posisi = (int) ($item['posisi'] ?? 1);
            $dataXml .= '<c r="G' . $curRow . '" s="12"><v>' . $posisi . '</v></c>';

            // Col 8 (H): Status Menerima (boolean true = 1)
            $dataXml .= '<c r="H' . $curRow . '" s="12" t="b"><v>1</v></c>';

            $dataXml .= '</row>';
            $curRow++;
        }

        // Baris kosong sebelum catatan
        $curRow++;

        // 3. Catatan kaki (menggunakan style dan shared strings bawaan template)
        $r_c1 = $curRow++;
        $r_c2 = $curRow++;
        $r_c3 = $curRow++;
        $r_c4 = $curRow++;
        $r_c5 = $curRow++;
        $r_c6 = $curRow++;
        $r_c7 = $curRow++;
        $r_c8 = $curRow++;

        $notesXml = '<row r="' . $r_c1 . '" spans="1:8" x14ac:dyDescent="0.25"><c r="A' . $r_c1 . '" s="2" t="s"><v>16</v></c></row>'
            . '<row r="' . $r_c2 . '" spans="1:8" x14ac:dyDescent="0.25"><c r="A' . $r_c2 . '" s="9" t="s"><v>17</v></c></row>'
            . '<row r="' . $r_c3 . '" spans="1:8" x14ac:dyDescent="0.25"><c r="A' . $r_c3 . '" t="s"><v>18</v></c></row>'
            . '<row r="' . $r_c4 . '" spans="1:8" x14ac:dyDescent="0.25"><c r="A' . $r_c4 . '" t="s"><v>19</v></c></row>'
            . '<row r="' . $r_c5 . '" spans="1:8" x14ac:dyDescent="0.25"><c r="A' . $r_c5 . '" t="s"><v>20</v></c></row>'
            . '<row r="' . $r_c6 . '" spans="1:1" x14ac:dyDescent="0.25"><c r="A' . $r_c6 . '" t="s"><v>21</v></c></row>'
            . '<row r="' . $r_c7 . '" spans="1:1" x14ac:dyDescent="0.25"><c r="A' . $r_c7 . '" t="s"><v>22</v></c></row>'
            . '<row r="' . $r_c8 . '" spans="1:1" x14ac:dyDescent="0.25"><c r="A' . $r_c8 . '" t="s"><v>23</v></c></row>';

        // 4. Update dimension ref (A1:H{maxRow})
        $finalXml = $headerPart . $dataXml . $notesXml . '</sheetData><pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>';
        $finalXml = preg_replace('/<dimension ref="[^"]+"/', '<dimension ref="A1:H' . $r_c8 . '"', $finalXml, 1);

        return $finalXml;
    }

    /**
     * Hasilkan file Excel (.xlsx) resmi MBG berisi seluruh 6 sheet via native ZipArchive OpenXML.
     */
    public static function exportExcel(): string
    {
        $templatePath = self::getTemplatePath();
        if (! file_exists($templatePath)) {
            throw new \RuntimeException('Berkas template MBG tidak ditemukan di server: ' . $templatePath);
        }

        $parentMap = self::getGtkParentMap();

        // 1. Data GTK
        $gtkRows = Gtk::orderBy('nama', 'asc')->get()->map(function ($item) use ($parentMap) {
            $isGuru = str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'guru')
                || str_contains(strtolower($item->jenis_ptk_id_str ?? ''), 'kepala');
            $nik = trim((string) $item->nik);

            return [
                'nisn' => '',
                'nik' => $nik,
                'nama' => $item->nama,
                'tanggal_lahir_raw' => $item->tanggal_lahir,
                'tanggal_lahir' => $item->tanggal_lahir ? Carbon::parse($item->tanggal_lahir)->format('d-m-Y') : '',
                'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                'nama_orang_tua' => $parentMap[$nik] ?? '-',
                'posisi' => $isGuru ? 2 : 3,
                'status_menerima' => true,
            ];
        })->values()->toArray();

        // 2. Data Siswa per Tingkat
        $fetchSiswa = function (string $tingkat) {
            return PesertaDidik::where('tingkat_pendidikan_id', $tingkat)
                ->orderBy('nama', 'asc')
                ->get()
                ->map(function ($item) {
                    $ortu = $item->nama_ibu ?: ($item->nama_ayah ?: ($item->nama_wali ?: '-'));

                    return [
                        'nisn' => (string) ($item->nisn ?? ''),
                        'nik' => (string) ($item->nik ?? ''),
                        'nama' => (string) ($item->nama ?? ''),
                        'tanggal_lahir_raw' => $item->tanggal_lahir,
                        'tanggal_lahir' => $item->tanggal_lahir ? Carbon::parse($item->tanggal_lahir)->format('d-m-Y') : '',
                        'jenis_kelamin_id' => $item->jenis_kelamin === 'L' ? 392 : 64,
                        'nama_orang_tua' => (string) $ortu,
                        'posisi' => 1,
                        'status_menerima' => true,
                    ];
                })->values()->toArray();
        };

        $sheetDataMap = [
            'xl/worksheets/sheet1.xml' => $gtkRows,
            'xl/worksheets/sheet2.xml' => $fetchSiswa('10'),
            'xl/worksheets/sheet3.xml' => $fetchSiswa('11'),
            'xl/worksheets/sheet4.xml' => $fetchSiswa('12'),
        ];

        // 3. Salin template ke berkas temporer
        $tempExcel = tempnam(sys_get_temp_dir(), 'mbg_out_') . '.xlsx';
        if (! copy($templatePath, $tempExcel)) {
            throw new \RuntimeException('Gagal menyalin template MBG ke berkas sementara.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tempExcel) !== true) {
            throw new \RuntimeException('Gagal membuka paket OpenXML template MBG.');
        }

        foreach ($sheetDataMap as $entryName => $rows) {
            $origXml = $zip->getFromName($entryName);
            if ($origXml !== false) {
                $newXml = self::buildSheetXml($origXml, $rows);
                $zip->deleteName($entryName);
                $zip->addFromString($entryName, $newXml);
            }
        }

        $zip->close();

        return $tempExcel;
    }
}
