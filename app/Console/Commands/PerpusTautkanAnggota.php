<?php

namespace App\Console\Commands;

use App\Services\PerpustakaanIdentitasService;
use Illuminate\Console\Command;

/**
 * perpus:tautkan-anggota
 * -------------------------------------------------------------------------
 * Menautkan transaksi sirkulasi lama (yang hanya punya `peminjam_id` mentah)
 * ke identitas kanonik `perpus_anggota.id`.
 *
 * Default = DRY RUN (tidak menulis apa pun). Tambahkan --apply untuk menulis.
 *
 * Contoh:
 *   php artisan perpus:tautkan-anggota
 *   php artisan perpus:tautkan-anggota --apply
 *   php artisan perpus:tautkan-anggota --apply --limit=100
 */
class PerpusTautkanAnggota extends Command
{
    protected $signature = 'perpus:tautkan-anggota
                            {--apply : Tulis perubahan ke database (default: dry-run)}
                            {--limit=0 : Batasi jumlah baris yang diperiksa (0 = semua)}';

    protected $description = 'Tautkan transaksi sirkulasi lama ke anggota kanonik (perpus_anggota.id)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $limit = (int) $this->option('limit');

        if (!PerpustakaanIdentitasService::hasAnggotaColumn('perpus_sirkulasi')) {
            $this->error('Kolom perpus_sirkulasi.anggota_id belum ada. Jalankan migrasi terlebih dahulu.');
            return self::FAILURE;
        }

        $this->info($apply
            ? 'Mode APPLY — perubahan akan ditulis ke database.'
            : 'Mode DRY-RUN — tidak ada perubahan ditulis. Gunakan --apply untuk menerapkan.');

        $stats = PerpustakaanIdentitasService::backfill($apply, $limit);

        $this->newLine();
        $this->table(
            ['Diperiksa', 'Cocok', 'Gagal Ditautkan'],
            [[$stats['diperiksa'], $stats['cocok'], $stats['gagal']]]
        );

        if (!$apply && $stats['cocok'] > 0) {
            $this->warn("{$stats['cocok']} baris siap ditautkan. Jalankan ulang dengan --apply untuk menerapkan.");
        }

        if ($apply && $stats['cocok'] > 0) {
            $this->info("{$stats['cocok']} baris berhasil ditautkan.");
        }

        if ($stats['gagal'] > 0) {
            $this->comment("{$stats['gagal']} baris tidak dapat ditautkan otomatis (anggota belum terdaftar atau nama ambigu).");
        }

        return self::SUCCESS;
    }
}
