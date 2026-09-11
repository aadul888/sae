<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Generate Code 128B SVG barcode string.
     *
     * @param string $code
     * @param int $height
     * @param int $barWidth
     * @return string
     */
    public static function generateCode128Svg(string $code, int $height = 40, int $barWidth = 2): string
    {
        $code = trim($code);
        if (empty($code)) {
            $code = '0000000000';
        }

        // Code 128 patterns (Subset B)
        $patterns = [
            '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
            '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
            '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
            '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
            '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
            '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
            '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
            '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
            '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
            '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
            '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
        ];

        // Start B index is 104
        $startPattern = '211214';
        $stopPattern = '2331112';

        $checkSum = 104;
        $encodedString = $startPattern;

        for ($i = 0; $i < strlen($code); $i++) {
            $charVal = ord($code[$i]) - 32;
            if ($charVal < 0 || $charVal > 95) {
                $charVal = 0; // fallback to space
            }
            $checkSum += $charVal * ($i + 1);
            $encodedString .= $patterns[$charVal];
        }

        // Check character
        $checkCharVal = $checkSum % 103;
        $encodedString .= $patterns[$checkCharVal];
        $encodedString .= $stopPattern;

        // Render SVG bars
        $totalUnits = 0;
        for ($i = 0; $i < strlen($encodedString); $i++) {
            $totalUnits += (int)$encodedString[$i];
        }

        $svgWidth = $totalUnits * $barWidth;
        $x = 0;
        $rects = '';

        for ($i = 0; $i < strlen($encodedString); $i++) {
            $width = (int)$encodedString[$i] * $barWidth;
            if ($i % 2 === 0) {
                // Bar (black)
                $rects .= "<rect x=\"{$x}\" y=\"0\" width=\"{$width}\" height=\"{$height}\" fill=\"#1e293b\" />";
            }
            $x += $width;
        }

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$svgWidth} {$height}\" width=\"100%\" height=\"{$height}\" preserveAspectRatio=\"none\">{$rects}</svg>";
    }
}
