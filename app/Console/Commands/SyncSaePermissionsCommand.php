<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RolePermission;

class SyncSaePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sae:sync-permissions {--reset : Reset seluruh hak akses ke standar baku default per peran}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis mendeteksi, mengelompokkan, dan menyinkronkan seluruh modul sistem ke matriks RBAC (tanpa merubah RolePermission.php)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("==================================================");
        $this->info("   SINKRONISASI HAK AKSES & MODUL SISTEM SAE     ");
        $this->info("==================================================");

        if ($this->option('reset')) {
            $this->warn("! Mengembalikan seluruh hak akses peran (Admin, Guru, Tendik, Siswa) ke standar baku...");
            RolePermission::resetDefaultPermissions();
            $this->info("✓ Reset hak akses default berhasil diselesaikan.");
        } else {
            $this->line("• Memindai rute, controller, views, dan database...");
            $added = RolePermission::syncAvailablePermissions();
            $this->info("✓ Sinkronisasi selesai: {$added} modul baru/diperbarui.");
        }

        // Tampilkan ringkasan modul aktif per peran
        $this->newLine();
        $this->info("Ringkasan Matriks Modul Terdaftar:");
        $roles = ['admin' => 'Administrator', 'guru' => 'Guru', 'tendik' => 'Tenaga Kependidikan', 'peserta_didik' => 'Peserta Didik'];
        $tableData = [];

        foreach ($roles as $r => $label) {
            $count = RolePermission::where('role', $r)->where('is_allowed', true)->where('can_read', true)->count();
            $totalInRole = RolePermission::where('role', $r)->count();
            $tableData[] = [
                'Peran'           => $label,
                'Kode'            => $r,
                'Modul Terdaftar' => $totalInRole,
                'Modul Aktif (R)' => $count,
            ];
        }

        $this->table(['Peran', 'Kode', 'Modul Terdaftar', 'Modul Aktif (R)'], $tableData);

        $this->newLine();
        $this->info("✓ Seluruh modul baru siap digunakan tanpa perlu mengubah RolePermission.php.");

        return Command::SUCCESS;
    }
}
