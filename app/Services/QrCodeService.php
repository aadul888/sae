<?php

namespace App\Services;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Generate an SVG QR code string.
     *
     * @param string $content
     * @param int $size
     * @param int $margin
     * @return string
     */
    public static function generateSvg(string $content, int $size = 120, int $margin = 0): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($content);

        // Strip xml declaration if present for seamless inline embedding in HTML
        return preg_replace('/<\?xml[^>]*\?>/i', '', $svg);
    }

    /**
     * Generate a Base64 Data URI of the SVG QR code.
     *
     * @param string $content
     * @param int $size
     * @param int $margin
     * @return string
     */
    public static function generateDataUri(string $content, int $size = 120, int $margin = 0): string
    {
        $svg = self::generateSvg($content, $size, $margin);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
