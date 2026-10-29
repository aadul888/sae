<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Gelar depan/belakang GTK harus tersimpan di tabel terpisah (gtk_meta),
 * bukan di tabel `gtk`.
 *
 * Regresi yang dicegah: tarik data Dapodik (FeederReceiverController) melakukan
 * `DB::table('gtk')->delete()` lalu insert ulang seluruh baris. Jika gelar
 * disimpan sebagai kolom `gtk`, nilai kustom akan hilang setiap kali sync.
 */
class GtkGelarPersistenceTest extends TestCase
{
    private function routesSource(): string
    {
        // Rute dipecah ke beberapa file di routes/ (lihat routes/web.php).
        $src = '';
        foreach (glob(base_path('routes/*.php')) as $file) {
            $src .= file_get_contents($file) . "\n";
        }
        return $src;
    }

    private function migrationSource(): string
    {
        return file_get_contents(
            database_path('migrations/2026_10_29_110000_create_gtk_meta_table.php')
        );
    }

    public function test_gtk_meta_migration_exists(): void
    {
        $this->assertFileExists(
            database_path('migrations/2026_10_29_110000_create_gtk_meta_table.php'),
            'Migrasi gtk_meta diperlukan untuk menyimpan gelar di luar data Dapodik.'
        );
    }

    public function test_gtk_meta_stores_gelar_columns(): void
    {
        $src = $this->migrationSource();

        $this->assertStringContainsString('gtk_meta', $src);
        $this->assertStringContainsString('gelar_depan', $src);
        $this->assertStringContainsString('gelar_belakang', $src);
        $this->assertStringContainsString("string('ptk_id', 50)->unique()", $src);
    }

    public function test_gelar_columns_are_not_added_to_dapodik_gtk_table(): void
    {
        // Tabel gtk ditimpa total saat sync — gelar tidak boleh ditaruh di sana.
        $src = file_get_contents(
            database_path('migrations/2024_01_01_000001_create_dapodik_tables.php')
        );

        $this->assertStringNotContainsString('gelar_depan', $src);
        $this->assertStringNotContainsString('gelar_belakang', $src);
    }

    public function test_controller_persists_gelar_to_gtk_meta(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/GuruAktifController.php'));
        $service = file_get_contents(app_path('Services/GtkMetaService.php'));

        $this->assertStringContainsString('function updateGelar', $controller);
        $this->assertStringContainsString('GtkMetaService', $controller);
        $this->assertStringContainsString(
            "DB::table('gtk_meta')->updateOrInsert",
            $service,
            'Penyimpanan gelar harus lewat GtkMetaService ke tabel gtk_meta.'
        );
        $this->assertStringNotContainsString(
            "DB::table('gtk')->where('ptk_id', \$ptkId)->update(['gelar",
            $controller,
            'Gelar tidak boleh ditulis ke tabel gtk karena akan hilang saat sync Dapodik.'
        );
    }

    public function test_route_for_gelar_update_is_registered(): void
    {
        $src = $this->routesSource();

        $this->assertStringContainsString('guru-aktif.update-gelar', $src);
        $this->assertStringContainsString(
            '/manajemen-data/guru-aktif/{id}/gelar',
            $src,
            'Endpoint simpan gelar harus berada di bawah manajemen-data/guru-aktif.'
        );
    }

    public function test_dapodik_sync_still_replaces_gtk_table(): void
    {
        // Kontrak: sync Dapodik memang menimpa gtk, sehingga gtk_meta wajib terpisah.
        $src = file_get_contents(app_path('Http/Controllers/Api/FeederReceiverController.php'));

        $this->assertStringContainsString("DB::table('gtk')->delete()", $src);
        $this->assertStringNotContainsString("DB::table('gtk_meta')->delete()", $src);
    }

    public function test_ui_exposes_gelar_inputs(): void
    {
        $view = file_get_contents(resource_path('views/dashboard/guru-aktif.blade.php'));

        $this->assertStringContainsString('gtkGelarDepan', $view);
        $this->assertStringContainsString('gtkGelarBelakang', $view);
        $this->assertStringContainsString('saveGelarGuru()', $view);
    }

    public function test_guru_dan_tendik_bisa_ubah_gelar_dari_profil(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ProfileController.php'));

        $this->assertStringContainsString('function updateGelar', $controller);
        $this->assertStringContainsString("route('dashboard.profile.gelar')", $controller . file_get_contents(resource_path('views/dashboard/profile.blade.php')));

        // Hanya guru & tendik, dan wajib punya ptk_id (tanpa menulis ke tabel gtk).
        $this->assertStringContainsString("\$user->role !== 'guru'", $controller);
        $this->assertStringContainsString("\$user->role !== 'tendik'", $controller);
        $this->assertStringContainsString('empty($user->ptk_id)', $controller);
        $this->assertStringContainsString('GtkMetaService', $controller);
    }

    public function test_rute_gelar_profil_terdaftar(): void
    {
        $src = $this->routesSource();

        $this->assertStringContainsString("put('/profil/gelar'", $src);
        $this->assertStringContainsString('profile.gelar', $src);
    }

    public function test_service_gtk_meta_menyimpan_ke_tabel_terpisah(): void
    {
        $src = file_get_contents(app_path('Services/GtkMetaService.php'));

        $this->assertStringContainsString("DB::table('gtk_meta')->updateOrInsert", $src);
        $this->assertStringNotContainsString("DB::table('gtk')->update", $src);
        $this->assertStringContainsString('function namaLengkap', $src);
    }

    public function test_profil_menampilkan_gelar_pada_biodata(): void
    {
        $view = file_get_contents(resource_path('views/dashboard/profile.blade.php'));

        $this->assertStringContainsString('gelar_depan', $view);
        $this->assertStringContainsString('gelar_belakang', $view);
        $this->assertStringContainsString('nama_lengkap', $view);
        // Card hanya tampil bila pengguna berhak mengubah gelar.
        $this->assertStringContainsString("can_edit_gelar", $view);
    }
}
