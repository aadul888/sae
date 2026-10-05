<?php

namespace App\Console\Commands;

use App\Services\MonitoringReporterService;
use Illuminate\Console\Command;

class ReportMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sae:report-monitoring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim agregat jumlah data angka tabel sync Dapodik ke sae-core';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Mengumpulkan data angka tabel sync & Dapodik...');
        $counts = MonitoringReporterService::collectCounts();

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['NPSN', $counts['npsn']],
                ['Nama Sekolah', $counts['nama_sekolah']],
                ['Tipe Instalasi', $counts['tipe_instalasi']],
                ['Versi Aplikasi', $counts['app_version']],
                ['Total Peserta Didik', number_format($counts['total_peserta_didik'])],
                ['Total GTK (Guru/Tendik)', number_format($counts['total_gtk']) . " ({$counts['total_guru']}/{$counts['total_tendik']})"],
                ['Total Rombel', number_format($counts['total_rombel'])],
                ['Total Pengguna/Admin', number_format($counts['total_pengguna']) . " ({$counts['total_admin']} admin)"],
                ['Tarik Dapodik Terakhir', $counts['last_sync_dapodik_at'] ?? 'Belum pernah'],
            ]
        );

        $this->info('Mengirim ke sae-core...');
        $result = MonitoringReporterService::send();

        if ($result['success']) {
            $this->info('SUKSES: ' . $result['message']);
            return Command::SUCCESS;
        }

        $this->warn('CATATAN: ' . $result['message']);
        return Command::FAILURE;
    }
}
