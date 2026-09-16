<?php

namespace App\Console\Commands;

use App\Models\PresensiHarian;
use Illuminate\Console\Command;

class AutoPulangCepatPresensi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:auto-pulang-cepat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis menandai pulang cepat untuk siswa yang tidak melakukan tap pulang sampai berganti hari';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa presensi siswa tanpa scan pulang di hari-hari sebelumnya...');

        $count = PresensiHarian::autoCloseUncheckedOut();

        $this->info("Berhasil memperbarui {$count} data presensi siswa menjadi Pulang Cepat.");

        return Command::SUCCESS;
    }
}
