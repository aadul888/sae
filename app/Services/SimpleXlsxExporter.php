<?php

namespace App\Services;

use ZipArchive;

/**
 * Simple, high-performance native OpenXML (.xlsx) Exporter for SAE.
 * Generates valid, styled Microsoft Excel files without external composer dependencies.
 */
class SimpleXlsxExporter
{
    /**
     * Generate an .xlsx spreadsheet file with professional title, metadata, styled headers, and data grid.
     *
     * @param string $filePath Absolute path where the .xlsx file should be written.
     * @param array $meta [title, subtitle, fields => [label => value]]
     * @param array $headers List of column header names
     * @param array $rows List of data rows (arrays)
     * @param array $options [sheetName, headerBg]
     * @return bool
     */
    public static function export(string $filePath, array $meta, array $headers, array $rows, array $options = []): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sheetName = $options['sheetName'] ?? 'Data Respon';
        // Sanitize sheet name (max 31 chars, no invalid chars : \ / ? * [ ])
        $sheetName = mb_substr(preg_replace('/[\\\\\/\?\*\[\]\:]/', ' ', $sheetName), 0, 31);
        $headerBgHex = $options['headerBg'] ?? 'FF1E3A8A'; // Deep Navy / SAE Blue

        $xmlEscape = function ($str) {
            if ($str === null || $str === false) {
                return '';
            }
            // Strip any illegal XML control characters except tab, LF, CR
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $str);
            return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };

        // Number to Column Letter (1 => A, 26 => Z, 27 => AA, etc.)
        $colLetter = function ($n) {
            $letters = '';
            while ($n > 0) {
                $code = ($n - 1) % 26;
                $letters = chr(65 + $code) . $letters;
                $n = intval(($n - $code) / 26);
            }
            return $letters;
        };

        // Calculate dynamic column widths based on content
        $colWidths = [];
        foreach ($headers as $cIdx => $hdr) {
            $colWidths[$cIdx] = max(10, mb_strlen((string) $hdr) + 4);
        }
        $sampleRows = array_slice($rows, 0, 150);
        foreach ($sampleRows as $r) {
            foreach ($r as $cIdx => $cell) {
                $len = mb_strlen((string) $cell);
                if ($len > ($colWidths[$cIdx] ?? 10)) {
                    $colWidths[$cIdx] = min(55, max($colWidths[$cIdx] ?? 10, $len + 3));
                }
            }
        }

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

        // 4. xl/workbook.xml
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="' . $xmlEscape($sheetName) . '" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        // 5. xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="5">
    <font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>
    <font><b/><sz val="15"/><color rgb="FF1E3A8A"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FF334155"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FF1E293B"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="' . $headerBgHex . '"/></fill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/></fill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFCBD5E1"/></left>
      <right style="thin"><color rgb="FFCBD5E1"/></right>
      <top style="thin"><color rgb="FFCBD5E1"/></top>
      <bottom style="thin"><color rgb="FFCBD5E1"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="10">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="4" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>
  </cellXfs>
</styleSheet>';

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Determine table header row position
        $metaFields = $meta['fields'] ?? [];
        $headerRowNum = 3 + count($metaFields) + 2; // Row after title, subtitle, meta items, and 1 blank separator

        // Freeze pane below table header
        $sheetXml .= '  <sheetViews>' . "\n";
        $sheetXml .= '    <sheetView tabSelected="1" workbookViewId="0">' . "\n";
        $sheetXml .= '      <pane ySplit="' . $headerRowNum . '" topLeftCell="A' . ($headerRowNum + 1) . '" activePane="bottomLeft" state="frozen"/>' . "\n";
        $sheetXml .= '    </sheetView>' . "\n";
        $sheetXml .= '  </sheetViews>' . "\n";

        // Column widths
        $sheetXml .= '  <cols>' . "\n";
        foreach ($colWidths as $idx => $w) {
            $colNum = $idx + 1;
            $sheetXml .= '    <col min="' . $colNum . '" max="' . $colNum . '" width="' . round($w, 1) . '" customWidth="1"/>' . "\n";
        }
        $sheetXml .= '  </cols>' . "\n";

        $sheetXml .= '  <sheetData>' . "\n";

        $curRow = 1;
        // Row 1: Document Main Title
        $sheetXml .= '    <row r="' . $curRow . '" ht="26" customHeight="1">' . "\n";
        $sheetXml .= '      <c r="A' . $curRow . '" t="inlineStr" s="1"><is><t>' . $xmlEscape($meta['title'] ?? 'DATA REKAPITULASI') . '</t></is></c>' . "\n";
        $sheetXml .= '    </row>' . "\n";
        $curRow++;

        // Row 2: Subtitle (Form Title & School Unit)
        if (!empty($meta['subtitle'])) {
            $sheetXml .= '    <row r="' . $curRow . '" ht="20" customHeight="1">' . "\n";
            $sheetXml .= '      <c r="A' . $curRow . '" t="inlineStr" s="2"><is><t>' . $xmlEscape($meta['subtitle']) . '</t></is></c>' . "\n";
            $sheetXml .= '    </row>' . "\n";
            $curRow++;
        }

        // Metadata Key-Value List
        foreach ($metaFields as $mLabel => $mVal) {
            $sheetXml .= '    <row r="' . $curRow . '" ht="18" customHeight="1">' . "\n";
            $sheetXml .= '      <c r="A' . $curRow . '" t="inlineStr" s="3"><is><t>' . $xmlEscape($mLabel) . '</t></is></c>' . "\n";
            $sheetXml .= '      <c r="B' . $curRow . '" t="inlineStr" s="4"><is><t>' . $xmlEscape($mVal) . '</t></is></c>' . "\n";
            $sheetXml .= '    </row>' . "\n";
            $curRow++;
        }

        // Blank separator row
        $curRow++;

        // Table Header Row
        $sheetXml .= '    <row r="' . $headerRowNum . '" ht="28" customHeight="1">' . "\n";
        foreach ($headers as $cIdx => $hdr) {
            $cellRef = $colLetter($cIdx + 1) . $headerRowNum;
            $sheetXml .= '      <c r="' . $cellRef . '" t="inlineStr" s="5"><is><t>' . $xmlEscape($hdr) . '</t></is></c>' . "\n";
        }
        $sheetXml .= '    </row>' . "\n";
        $curRow = $headerRowNum + 1;

        // Table Data Rows
        foreach ($rows as $rIdx => $row) {
            $isZebra = ($rIdx % 2 === 1);
            $sheetXml .= '    <row r="' . $curRow . '" ht="20" customHeight="1">' . "\n";
            foreach ($headers as $cIdx => $hdr) {
                $val = $row[$cIdx] ?? '';
                $cellRef = $colLetter($cIdx + 1) . $curRow;

                // Center columns: No, Waktu, Identitas, Tipe Akun, IP Address
                $isCenter = in_array($cIdx, [0, 1, 4, 5]);
                $styleId = $isZebra ? ($isCenter ? 9 : 8) : ($isCenter ? 7 : 6);

                $sheetXml .= '      <c r="' . $cellRef . '" t="inlineStr" s="' . $styleId . '"><is><t>' . $xmlEscape($val) . '</t></is></c>' . "\n";
            }
            $sheetXml .= '    </row>' . "\n";
            $curRow++;
        }

        $sheetXml .= '  </sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return true;
    }
}
