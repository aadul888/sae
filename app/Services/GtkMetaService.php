<?php

namespace App\Services;

use App\Support\Table;
use Illuminate\Support\Facades\DB;

/**
 * Gelar depan/belakang GTK disimpan di tabel `gtk_meta`, terpisah dari `gtk`.
 *
 * Alasan: tarik data Dapodik (FeederReceiverController) menimpa total tabel `gtk`
 * (delete + insert), sehingga data kustom tidak boleh ditulis ke `gtk`.
 */
class GtkMetaService
{
    /**
     * Apakah penyimpanan gelar tersedia (migrasi sudah jalan)?
     */
    public function available(): bool
    {
        return Table::exists('gtk_meta');
    }

    /**
     * Ambil gelar untuk satu PTK.
     *
     * @return array{gelar_depan: ?string, gelar_belakang: ?string}
     */
    public function gelar(string|int|null $ptkId): array
    {
        if (empty($ptkId) || !$this->available()) {
            return ['gelar_depan' => null, 'gelar_belakang' => null];
        }

        $meta = DB::table('gtk_meta')->where('ptk_id', $ptkId)->first();

        return [
            'gelar_depan' => $meta->gelar_depan ?? null,
            'gelar_belakang' => $meta->gelar_belakang ?? null,
        ];
    }

    /**
     * Simpan gelar depan/belakang untuk satu PTK.
     *
     * @return array{gelar_depan: ?string, gelar_belakang: ?string}
     */
    public function simpan(string|int $ptkId, ?string $gelarDepan, ?string $gelarBelakang): array
    {
        $gelarDepan = trim((string) $gelarDepan) ?: null;
        $gelarBelakang = trim((string) $gelarBelakang) ?: null;

        DB::table('gtk_meta')->updateOrInsert(
            ['ptk_id' => $ptkId],
            [
                'gelar_depan' => $gelarDepan,
                'gelar_belakang' => $gelarBelakang,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return ['gelar_depan' => $gelarDepan, 'gelar_belakang' => $gelarBelakang];
    }

    /**
     * Susun nama lengkap dengan gelar, mis. "Drs. Budi Santoso, M.Pd.".
     */
    public static function namaLengkap(?string $nama, ?string $gelarDepan, ?string $gelarBelakang): string
    {
        return trim(
            trim((string) $gelarDepan . ' ' . (string) $nama)
            . ($gelarBelakang ? ', ' . trim($gelarBelakang) : '')
        );
    }
}
