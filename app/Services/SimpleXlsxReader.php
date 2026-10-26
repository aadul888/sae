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
            $rawRows[] = array_map(fn($v) => trim((string)$v), $row);
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
            $rawRows = self::parseHtmlTableGrid($content);
            if (!empty($rawRows)) {
                return self::formatRowsWithHeader($rawRows);
            }
        }

        return self::readCsv($filePath);
    }

    /**
     * Parse tabel HTML dengan penanganan colspan dan rowspan yang presisi
     */
    private static function parseHtmlTableGrid(string $html): array
    {
        if (class_exists(\DOMDocument::class)) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $table = $dom->getElementsByTagName('table')->item(0);
            if ($table) {
                $grid = [];
                $rows = $table->getElementsByTagName('tr');
                $rIdx = 0;

                foreach ($rows as $tr) {
                    $cIdx = 0;
                    foreach ($tr->childNodes as $node) {
                        if ($node->nodeType !== XML_ELEMENT_NODE) continue;
                        $tagName = strtolower($node->nodeName);
                        if ($tagName !== 'td' && $tagName !== 'th') continue;

                        while (isset($grid[$rIdx][$cIdx])) {
                            $cIdx++;
                        }

                        $colspan = max(1, (int) $node->getAttribute('colspan'));
                        $rowspan = max(1, (int) $node->getAttribute('rowspan'));
                        $text = trim(html_entity_decode($node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                        for ($i = 0; $i < $rowspan; $i++) {
                            for ($j = 0; $j < $colspan; $j++) {
                                $grid[$rIdx + $i][$cIdx + $j] = $text;
                            }
                        }

                        $cIdx += $colspan;
                    }
                    $rIdx++;
                }

                $maxCols = 0;
                foreach ($grid as $r => $cols) {
                    if (!empty($cols)) {
                        $maxCols = max($maxCols, max(array_keys($cols)) + 1);
                    }
                }

                $cleanRows = [];
                foreach ($grid as $r => $cols) {
                    $row = [];
                    for ($c = 0; $c < $maxCols; $c++) {
                        $row[$c] = $cols[$c] ?? '';
                    }
                    $cleanRows[] = $row;
                }

                return $cleanRows;
            }
        }

        // Fallback regex jika DOMDocument tidak tersedia
        $rawRows = [];
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $trMatches)) {
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

        return $rawRows;
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

            // Abaikan baris judul / kop (seluruh sel identik atau kurang dari 3 nilai unik)
            if (count(array_unique($nonEmptyCells)) < 3) {
                continue;
            }

            $rowLower = array_map(fn($v) => strtolower(trim((string)$v)), $row);

            // 1. Cek kecocokan nama kolom baku (prioritas utama)
            if (
                in_array('peserta_didik_id', $rowLower, true) ||
                in_array('nama_pd', $rowLower, true) ||
                in_array('nisn', $rowLower, true) ||
                (in_array('nama', $rowLower, true) && (in_array('nipd', $rowLower, true) || in_array('jk', $rowLower, true) || in_array('tempat lahir', $rowLower, true)))
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
                if (strlen($h) > 50 || str_starts_with($h, 'nama sekolah') || str_starts_with($h, 'daftar peserta')) {
                    $isNotParagraph = false;
                    break;
                }
                if (preg_match('/^nisn\b/i', $h) || preg_match('/^nomor\s*induk\s*siswa/i', $h)) {
                    $hasNisnCol = true;
                }
                if (preg_match('/^nama\b/i', $h) && !str_starts_with($h, 'nama sekolah')) {
                    $hasNamaCol = true;
                }
            }

            if ($isNotParagraph && ($hasNisnCol || $hasNamaCol) && count($nonEmptyCells) >= 3) {
                $headerIndex = $idx;
                $headers = $rowLower;
                break;
            }
        }

        // 3. Deteksi header GENERIK (template SAE non-Dapodik: inventaris, aset, dsb).
        //    Ciri baris header: banyak sel berupa pengenal kolom (snake_case / tanpa spasi)
        //    dan TIDAK ada sel yang mengandung angka panjang / tanggal / kalimat panjang.
        if ($headerIndex === -1) {
            $bestIdx = -1;
            $bestScore = 0;

            foreach ($rawRows as $idx => $row) {
                $nonEmpty = array_values(array_filter($row, fn($v) => trim((string) $v) !== ''));
                if (count($nonEmpty) < 2) {
                    continue;
                }

                $identifierCount = 0;
                $looksLikeData = false;

                foreach ($nonEmpty as $cell) {
                    $cell = trim((string) $cell);
                    if ($cell === '') continue;

                    // Sel data: angka, tanggal, atau kalimat panjang
                    if (preg_match('/^\d+([.,]\d+)?$/', $cell)) { $looksLikeData = true; continue; }
                    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $cell)) { $looksLikeData = true; continue; }
                    if (mb_strlen($cell) > 60) { $looksLikeData = true; continue; }

                    // Pengenal kolom: snake_case, tanpa spasi, atau satu kata
                    if (preg_match('/^[a-z0-9]+(_[a-z0-9]+)+$/i', $cell) || preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $cell)) {
                        $identifierCount++;
                    }
                }

                if ($looksLikeData) {
                    continue;
                }

                $score = $identifierCount;
                if ($score > $bestScore && $identifierCount >= 2) {
                    $bestScore = $score;
                    $bestIdx = $idx;
                }
            }

            if ($bestIdx !== -1) {
                $headerIndex = $bestIdx;
                $headers = array_map(fn($v) => strtolower(trim((string) $v)), $rawRows[$bestIdx]);
            }
        }

        // Jika tidak ditemukan kata kunci khusus, gunakan baris pertama
        if ($headerIndex === -1 && isset($rawRows[0])) {
            $headerIndex = 0;
            $headers = array_map(fn($v) => strtolower(trim((string)$v)), $rawRows[0]);
        }

        // Cek apakah ada baris sub-header tepat di bawah baris header (multi-line header di Dapodik)
        if (isset($rawRows[$headerIndex + 1])) {
            $nextRowLower = array_map(fn($v) => strtolower(trim((string)$v)), $rawRows[$headerIndex + 1]);
            $isSubheader = in_array('tahun lahir', $nextRowLower, true)
                || in_array('jenjang pendidikan', $nextRowLower, true)
                || (in_array('pekerjaan', $nextRowLower, true) && in_array('penghasilan', $nextRowLower, true));

            if ($isSubheader) {
                $combinedHeaders = [];
                $maxLen = max(count($headers), count($nextRowLower));
                for ($c = 0; $c < $maxLen; $c++) {
                    $h1 = $headers[$c] ?? '';
                    $h2 = $nextRowLower[$c] ?? '';
                    if ($h1 !== '' && $h2 !== '' && $h1 !== $h2) {
                        $combinedHeaders[$c] = $h1 . ' - ' . $h2;
                    } elseif ($h2 !== '') {
                        $combinedHeaders[$c] = $h2;
                    } else {
                        $combinedHeaders[$c] = $h1;
                    }
                }
                $headers = $combinedHeaders;
                $headerIndex++; // Lewati baris subheader agar tidak terbaca sebagai data
            }
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
            // Simpan indeks kolom mentah agar ekstraksi berbasis posisi kolom selalu akurat 100%
            foreach ($row as $rIdx => $rVal) {
                $assoc['__col_' . $rIdx] = $rVal;
            }
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
