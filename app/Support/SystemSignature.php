<?php

namespace App\Support;

final class SystemSignature
{
    private const KEY = 90;

    private const RAW_MAP = [
        33,120,52,120,96,120,27,56,62,47,54,122,27,32,51,41,118,122,9,17,53,55,116,120,118,120,
        45,120,96,120,106,98,111,98,108,106,108,106,111,106,108,106,120,118,120,59,120,96,120,
        10,59,61,63,54,59,40,59,52,118,122,25,51,59,52,48,47,40,118,122,16,59,45,59,122,24,59,
        40,59,46,118,122,110,105,104,108,108,120,118,120,41,120,96,120,9,51,41,46,63,55,122,27,
        42,54,51,49,59,41,51,122,31,62,47,49,59,41,51,122,114,9,27,31,115,120,39
    ];

    private const DIGEST = 'ce20156fa40cf3fb7e6992ec3a575483a73c058e01ca5eeae86df0661be9ae15';

    public static function verify(): bool
    {
        if (hash('sha256', implode(',', self::RAW_MAP)) !== self::DIGEST) {
            return false;
        }

        $meta = self::resolve();
        return !empty($meta['n']) && !empty($meta['w']) && !empty($meta['a']);
    }

    public static function resolve(): array
    {
        $buffer = '';
        foreach (self::RAW_MAP as $byte) {
            $buffer .= chr($byte ^ self::KEY);
        }

        $decoded = json_decode($buffer, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function badge(): string
    {
        $m = self::resolve();
        if (empty($m['n'])) {
            return '';
        }

        $author  = htmlspecialchars($m['n'], ENT_QUOTES, 'UTF-8');
        $contact = htmlspecialchars($m['w'] ?? '', ENT_QUOTES, 'UTF-8');
        $intl    = preg_replace('/^0/', '62', $contact);
        $addr    = htmlspecialchars($m['a'] ?? '', ENT_QUOTES, 'UTF-8');

        return '<div class="copyright-badge" style="font-size:0.75rem;color:var(--text-muted,#94a3b8);text-align:center;padding:4px 0;">'
            . '<span>Dikembangkan oleh <strong>' . $author . '</strong></span>'
            . ' &bull; <a href="https://wa.me/' . $intl . '" target="_blank" rel="noopener" style="color:var(--primary,#6366f1);text-decoration:none;">'
            . '<i class="fab fa-whatsapp"></i> WhatsApp</a>'
            . ' &bull; <span>' . $addr . '</span>'
            . '</div>';
    }
}
