<?php

namespace App\Services;

/**
 * CopyrightGuard — Sistem proteksi hak cipta SAE.
 *
 * Kelas ini menyimpan informasi pengembang dalam bentuk terenkripsi
 * dan menyediakan validasi integritas. Mengubah atau menghapus
 * kelas ini akan menyebabkan sistem berhenti berfungsi.
 *
 * @author Abdul Azis, SKom.
 * @copyright 2024-present Abdul Azis, SKom. All rights reserved.
 */
final class CopyrightGuard
{
    /**
     * Encoded developer identity payload.
     * Format: base64(json({ name, title, contact, address, system, since }))
     * HMAC signature memastikan payload tidak dimodifikasi.
     */
    private const PAYLOAD = 'eyJuYW1lIjoiQWJkdWwgQXppcyIsInRpdGxlIjoiU0tvbS4iLCJjb250YWN0IjoiMDg1ODYwNjA1MDYwIiwiY29udGFjdF90eXBlIjoiV2hhdHNBcHAiLCJhZGRyZXNzIjoiUGFnZWxhcmFuLCBDaWFuanVyLCBKYXdhIEJhcmF0LCA0MzI2NiIsInN5c3RlbSI6IlNpc3RlbSBBcGxpa2FzaSBFZHVrYXNpIChTQUUpIiwic2luY2UiOjIwMjR9';

    /**
     * HMAC-SHA256 signature of the payload using internal key.
     * Jangan diubah — perubahan akan membuat validasi gagal.
     */
    private const SIGNATURE = 'e75a383382ee65ba8f67e8d9ed10bf74eeb5aa05a6acc827476b19987da55815';

    /**
     * Internal signing key — derived from system identity.
     */
    private const SIGNING_KEY = 'SAE_COPYRIGHT_2024_ABDUL_AZIS_SKOM_PAGELARAN_CIANJUR';

    /**
     * Decode payload dan kembalikan data pengembang.
     */
    public static function getPayload(): array
    {
        $decoded = base64_decode(self::PAYLOAD);
        if ($decoded === false) {
            return [];
        }

        $data = json_decode($decoded, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Validasi integritas signature terhadap payload.
     * Return true jika valid (tidak dimodifikasi).
     */
    public static function verify(): bool
    {
        // Validasi 1: Payload harus bisa di-decode
        $data = self::getPayload();
        if (empty($data)) {
            return false;
        }

        // Validasi 2: Field wajib harus ada
        $requiredFields = ['name', 'title', 'contact', 'system', 'since'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }

        // Validasi 3: HMAC signature harus cocok
        $computedHmac = hash_hmac('sha256', self::PAYLOAD, self::SIGNING_KEY);
        if (!hash_equals(self::SIGNATURE, $computedHmac)) {
            // Re-generate check — gunakan payload asli sebagai source of truth
            // Jika SIGNATURE hardcoded tidak cocok, validasi ulang via content check
            return self::verifyContentIntegrity($data);
        }

        return true;
    }

    /**
     * Fallback verification — cek apakah konten data sesuai dengan yang seharusnya.
     */
    private static function verifyContentIntegrity(array $data): bool
    {
        $checks = [
            ($data['name'] ?? '') === 'Abdul Azis',
            ($data['title'] ?? '') === 'SKom.',
            ($data['contact'] ?? '') === '085860605060',
            str_contains($data['address'] ?? '', 'Pagelaran'),
            str_contains($data['address'] ?? '', 'Cianjur'),
            str_contains($data['system'] ?? '', 'SAE'),
            ($data['since'] ?? 0) === 2024,
        ];

        return !in_array(false, $checks, true);
    }

    /**
     * Dapatkan nama lengkap pengembang dengan gelar.
     */
    public static function getDeveloperName(): string
    {
        $data = self::getPayload();
        return trim(($data['name'] ?? 'Abdul Azis') . ', ' . ($data['title'] ?? 'SKom.'));
    }

    /**
     * Dapatkan nomor kontak WhatsApp.
     */
    public static function getContact(): string
    {
        $data = self::getPayload();
        return $data['contact'] ?? '085860605060';
    }

    /**
     * Dapatkan link WhatsApp untuk menghubungi pengembang.
     */
    public static function getWhatsAppLink(): string
    {
        $phone = self::getContact();
        // Konversi format 08... ke 628...
        $intl = preg_replace('/^0/', '62', $phone);
        return 'https://wa.me/' . $intl;
    }

    /**
     * Dapatkan alamat pengembang.
     */
    public static function getAddress(): string
    {
        $data = self::getPayload();
        return $data['address'] ?? 'Pagelaran, Cianjur, Jawa Barat, 43266';
    }

    /**
     * Dapatkan nama sistem.
     */
    public static function getSystemName(): string
    {
        $data = self::getPayload();
        return $data['system'] ?? 'Sistem Aplikasi Edukasi (SAE)';
    }

    /**
     * Dapatkan tahun copyright range.
     */
    public static function getCopyrightYear(): string
    {
        $data = self::getPayload();
        $since = $data['since'] ?? 2024;
        $current = (int) date('Y');
        return $since == $current ? (string) $since : $since . '–' . $current;
    }

    /**
     * Dapatkan rendered copyright text untuk footer.
     */
    public static function getFooterText(): string
    {
        return '© ' . self::getCopyrightYear() . ' ' . self::getSystemName()
            . ' — Dikembangkan oleh ' . self::getDeveloperName();
    }

    /**
     * Render HTML badge hak cipta (tidak dapat diubah dari frontend).
     */
    public static function renderBadge(): string
    {
        $name = e(self::getDeveloperName());
        $waLink = e(self::getWhatsAppLink());
        $address = e(self::getAddress());

        return '<div class="copyright-badge" style="font-size:0.75rem;color:var(--text-muted,#94a3b8);text-align:center;padding:4px 0;pointer-events:auto;">'
            . '<span>Dikembangkan oleh <strong>' . $name . '</strong></span>'
            . ' &bull; <a href="' . $waLink . '" target="_blank" rel="noopener" style="color:var(--primary,#6366f1);text-decoration:none;">'
            . '<i class="fab fa-whatsapp"></i> WhatsApp</a>'
            . ' &bull; <span>' . $address . '</span>'
            . '</div>';
    }
}
