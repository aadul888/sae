<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan bukti foto modul Teknisi & IT.
 *
 * Semua foto disimpan di disk `public` dengan folder per modul
 * (`teknisi/<folder>/...`) agar mudah dicadangkan. Foto lama dihapus saat
 * diganti atau barisnya dihapus supaya tidak menumpuk.
 */
final class TeknisiFoto
{
    /** Batas ukuran unggah (KB) — sama dengan aturan validasi `max`. */
    public const MAX_KB = 3072;

    /** Aturan validasi baku untuk input `foto`. */
    public const RULES = 'nullable|image|max:' . self::MAX_KB;

    /**
     * Simpan foto unggahan; kembalikan path baru, atau `$lama` bila kosong.
     */
    public static function simpan(?UploadedFile $file, string $folder, ?string $lama = null): ?string
    {
        if (!$file) {
            return $lama;
        }

        // Foto bukti dikompres ulang otomatis agar hemat penyimpanan.
        // Tulis lewat disk 'public' (bukan path absolut langsung) supaya tetap
        // menghormati Storage::fake() pada pengujian. Prefix acak agar foto
        // pengganti selalu jadi berkas baru (bukan menimpa bila isinya sama).
        $sub = 'teknisi/' . trim($folder, '/');
        $hasil = app(\App\Services\ImageOptimizerService::class)->optimizeAndSaveImage(
            $file,
            Storage::disk('public')->path($sub),
            'teknisi_' . \Illuminate\Support\Str::random(8)
        );
        $path = $sub . '/' . $hasil['filename'];

        if ($lama && $lama !== $path) {
            self::hapus($lama);
        }

        return $path;
    }

    /** Hapus satu berkas foto bila ada. */
    public static function hapus(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
