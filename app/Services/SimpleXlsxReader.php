<?php

namespace App\Services;

use ZipArchive;
use SimpleXMLElement;
use Exception;

/**
 * Native, zero-dependency OpenXML (.xlsx) & CSV Reader for SAE.
 * Membaca berkas Excel dari aplikasi SiPintar / Kemdikbud secara cepat dan efisien.
 */
class SimpleXlsxReader
{
    /**
     * Baca berkas (.xlsx atau .csv) dan kembalikan array baris asosiatif berdasarkan header
     */
    public static function readFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("Berkas tidak ditemukan: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return self::readCsv($filePath);
        }

        return self::readXlsx($filePath);
    }

    /**
     * Parse file OpenXML .xlsx / .xls
     */
    public static function readXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            // Cek apakah berkas .xls sebenarnya adalah HTML Table atau XML Spreadsheet 2003
            $sample = @file_get_contents($filePath, false, null, 0, 4096) ?: '';
            if (stripos($sample, '<table') !== false || stripos($sample, '<Workbook') !== false || stripos($sample, '<html') !== false) {
                return self::readHtmlOrXmlTable($filePath);
            }
            // Coba fallback baca sebagai CSV
            return self::readCsv($filePath);
        }

        // 1. Baca shared strings
        $sharedStrings = [];
        $sharedXmlContent = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXmlContent !== false) {
            $xml = @simplexml_load_string($sharedXmlContent);
            if ($xml) {
                foreach ($xml->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string) $val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca sheet pertama (xl/worksheets/sheet1.xml)
        $sheetXmlContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXmlContent === false) {
            // Coba cari nama sheet pertama dari workbook
            $wbXml = @simplexml_load_string($zip->getFromName('xl/workbook.xml') ?: '');
            $sheetPath = 'xl/worksheets/sheet1.xml';
            if ($wbXml && isset($wbXml->sheets->sheet[0])) {
                $sheetPath = 'xl/worksheets/sheet1.xml';
            }
            $sheetXmlContent = $zip->getFromName($sheetPath);
        }

        if ($sheetXmlContent === false) {
            $zip->close();
            throw new Exception("Lembar kerja Excel (sheet1.xml) tidak dapat dibaca dari berkas.");
        }

        $sheetXml = @simplexml_load_string($sheetXmlContent);
        $zip->close();

        if (!$sheetXml || !isset($sheetXml->sheetData->row)) {
            return [];
        }

        $rawRows = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $cell) {
                $coord = (string) $cell['r'];
                $colIndex = self::coordinateToColumnIndex($coord);
                $type = (string) $cell['t'];
                $val = (string) $cell->v;

                if ($type === 's') {
                    $stringIndex = (int) $val;
                    $cellValue = $sharedStrings[$stringIndex] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $cellValue = (string) $cell->is->t;
                } else {
                    $cellValue = $val;
                }

                $cells[$colIndex] = trim($cellValue);
            }

            // Normalisasi agar array berurutan tanpa gap
            if (!empty($cells)) {
                $maxCol = max(array_keys($cells));
                $orderedRow = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $orderedRow[$c] = $cells[$c] ?? '';
                }
                $rawRows[] = $orderedRow;
            }
        }

        return self::formatRowsWithHeader($rawRows);
    }

    /**
     * Parse file CSV dengan deteksi separator (, ; atau \t)
     */
    public static function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Gagal membuka file CSV.");
        }

        // Cek baris pertama untuk deteksi delimiter
        $firstLine = fgets($handle);
        rewind($handle);

        $delimiter = ',';
        if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $delimiter = ';';
        } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
            $delimiter = "\t";
        }

        $rawRows = [];
        while (($row = fgetcsv($handle, 8192, $delimiter)) !== false) {
            $rawRows[] = array_map('trim', $row);
        }
        fclose($handle);

        return self::formatRowsWithHeader($rawRows);
    }

    /**
     * Parse file HTML Table atau XML Spreadsheet 2003 yang disimpan berekstensi .xls
     */
    public static function readHtmlOrXmlTable(string $filePath): array
    {
        $content = @file_get_contents($filePath);
        if (!$content) {
            return [];
        }

        // XML Spreadsheet 2003 (<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet">)
        if (stripos($content, '<Table') !== false && stripos($content, '<Row') !== false) {
            $rawRows = [];
            if (preg_match_all('/<Row[^>]*>(.*?)<\/Row>/is', $content, $rowMatches)) {
                foreach ($rowMatches[1] as $rowXml) {
                    $cells = [];
                    if (preg_match_all('/<Cell[^>]*>.*?<Data[^>]*>(.*?)<\/Data>.*?<\/Cell>/is', $rowXml, $cellMatches)) {
                        $cells = array_map(fn($v) => trim(strip_tags(html_entity_decode($v))), $cellMatches[1]);
                    }
                    if (!empty($cells)) {
                        $rawRows[] = $cells;
                    }
                }
            }
            if (!empty($rawRows)) {
                return self::formatRowsWithHeader($rawRows);
            }
        }

        // HTML Table (<table><tr><td>...</td></tr></table>)
        if (stripos($content, '<table') !== false) {
            $rawRows = [];
            if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches)) {
                foreach ($trMatches[1] as $trHtml) {
                    $cells = [];
                    if (preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $trHtml, $tdMatches)) {
                        $cells = array_map(fn($v) => trim(strip_tags(html_entity_decode($v))), $tdMatches[1]);
                    }
                    if (!empty($cells)) {
                        $rawRows[] = $cells;
                    }
                }
            }
            if (!empty($rawRows)) {
                return self::formatRowsWithHeader($rawRows);
            }
        }

        return self::readCsv($filePath);
    }

    /**
     * Konversi koordinat cell (misal 'A1', 'AB12') menjadi 0-based column index
     */
    private static function coordinateToColumnIndex(string $coord): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($coord), $matches);
        if (empty($matches[1])) {
            return 0;
        }

        $colLetters = $matches[1];
        $index = 0;
        $len = strlen($colLetters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($colLetters[$i]) - ord('A') + 1);
        }

        return max(0, $index - 1);
    }

    /**
     * Format baris mentah menjadi asosiatif dengan mendeteksi baris header
     */
    private static function formatRowsWithHeader(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        // Cari baris header (biasanya baris yang mengandung 'peserta_didik_id' atau 'nama_pd' atau 'nisn')
        $headerIndex = -1;
        $headers = [];

        foreach ($rawRows as $idx => $row) {
            $nonEmptyCells = array_values(array_filter($row, fn($v) => trim((string)$v) !== ''));
            if (empty($nonEmptyCells)) {
                continue;
            }

            $rowLower = array_map(fn($v) => strtolower(trim((string)$v)), $row);

            // 1. Cek kecocokan nama kolom baku (prioritas utama)
            if (
                in_array('peserta_didik_id', $rowLower, true) ||
                in_array('nama_pd', $rowLower, true) ||
                in_array('nisn', $rowLower, true) ||
                (in_array('nama', $rowLower, true) && count($nonEmptyCells) >= 3)
            ) {
                $headerIndex = $idx;
                $headers = $rowLower;
                break;
            }

            // 2. Cek baris header yang memiliki imbuhan (contoh: "NISN (*)", "Nama Lengkap (*)")
            // Syarat: bukan paragraf penjelasan (setiap sel <= 50 karakter), memiliki kolom yang diawali "nisn" atau "nama", dan minimal 3 kolom
            $hasNisnCol = false;
            $hasNamaCol = false;
            $isNotParagraph = true;

            foreach ($rowLower as $h) {
                if ($h === '') continue;
                if (strlen($h) > 50) {
                    $isNotParagraph = false;
                    break;
                }
                if (preg_match('/^nisn\b/i', $h) || preg_match('/^nomor\s*induk\s*siswa/i', $h)) {
                    $hasNisnCol = true;
                }
                if (preg_match('/^nama\b/i', $h)) {
                    $hasNamaCol = true;
                }
            }

            if ($isNotParagraph && ($hasNisnCol || $hasNamaCol) && count($nonEmptyCells) >= 3) {
                $headerIndex = $idx;
                $headers = $rowLower;
                break;
            }
        }

        // Jika tidak ditemukan kata kunci khusus, gunakan baris pertama
        if ($headerIndex === -1 && isset($rawRows[0])) {
            $headerIndex = 0;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rawRows[0]);
        }

        $results = [];
        $totalRows = count($rawRows);
        for ($i = $headerIndex + 1; $i < $totalRows; $i++) {
            $row = $rawRows[$i];
            // Lewati baris kosong
            if (empty(array_filter($row, fn($v) => $v !== ''))) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $colIdx => $colKey) {
                if ($colKey !== '') {
                    $val = $row[$colIdx] ?? '';
                    $assoc[$colKey] = $val;
                    $cleanKey = trim(preg_replace('/[^a-z0-9_]/', '_', preg_replace('/\s+/', '_', $colKey)), '_');
                    $cleanKey = preg_replace('/_+/', '_', $cleanKey);
                    if ($cleanKey !== '' && $cleanKey !== $colKey && !isset($assoc[$cleanKey])) {
                        $assoc[$cleanKey] = $val;
                    }
                }
            }

            $results[] = $assoc;
        }

        return $results;
    }
}
