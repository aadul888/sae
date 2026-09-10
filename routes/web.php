<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DapodikController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\KompetensiKeahlianController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Installer Routes
Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'process'])->name('install.process');

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/api/check-nisn', [HomeController::class, 'checkNisn'])->name('api.check-nisn');

// Auth Routes (Multi-User)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);

// Dashboard Multi-User Routes (Protected)
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru');
    Route::get('/tendik', [DashboardController::class, 'tendik'])->name('tendik');
    Route::get('/peserta-didik', [DashboardController::class, 'pesertaDidik'])->name('peserta-didik');
    Route::get('/peserta_didik', [DashboardController::class, 'pesertaDidik'])->name('peserta_didik');

    // Tarik Data Dapodik
    Route::get('/tarik-data', [DapodikController::class, 'index'])->name('dapodik')->middleware('permission:menu_dapodik');
    Route::post('/tarik-data/apikey', [DapodikController::class, 'generateApiKey'])->name('dapodik.apikey')->middleware('permission:fitur_dapodik_sync');

    // Manajemen Pengguna (4 Tab: Admin, Guru, Tendik, Peserta Didik)
    Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna.index')->middleware('permission:menu_pengguna');
    Route::put('/pengguna/{pengguna}', [UserController::class, 'update'])->name('pengguna.update')->middleware('permission:fitur_pengguna_edit');
    Route::delete('/pengguna/{pengguna}', [UserController::class, 'destroy'])->name('pengguna.destroy')->middleware('permission:fitur_pengguna_hapus');
    Route::post('/pengguna/{pengguna}/reset-password', [UserController::class, 'resetPassword'])->name('pengguna.resetPassword')->middleware('permission:fitur_pengguna_reset');

    // Update Sistem
    Route::get('/update', [UpdateController::class, 'index'])->name('update')->middleware('permission:menu_update');
    Route::get('/update/check', [UpdateController::class, 'check'])->name('update.check')->middleware('permission:menu_update');
    Route::post('/update/execute', [UpdateController::class, 'execute'])->name('update.execute')->middleware('permission:fitur_system_update');
    Route::get('/update/diagnose', [UpdateController::class, 'diagnose'])->name('update.diagnose')->middleware('permission:menu_update');

    // Pengaturan Hak Akses (Role & Permission)
    Route::get('/hak-akses', [PermissionController::class, 'index'])->name('hak-akses.index')->middleware('permission:menu_hak_akses');
    Route::post('/hak-akses/toggle', [PermissionController::class, 'toggle'])->name('hak-akses.toggle')->middleware('permission:menu_hak_akses');
    Route::post('/hak-akses/sync', [PermissionController::class, 'sync'])->name('hak-akses.sync')->middleware('permission:menu_hak_akses');
    Route::post('/hak-akses/reset', [PermissionController::class, 'resetDefault'])->name('hak-akses.reset')->middleware('permission:menu_hak_akses');

    // Pengaturan — Identitas Sekolah (Sumber: Sekolah Dapodik)
    Route::get('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'index'])->name('identitas-sekolah.index')->middleware('permission:menu_pengaturan');
    Route::put('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'update'])->name('identitas-sekolah.update')->middleware('permission:menu_pengaturan');

    // Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar)
    Route::get('/master-data/kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])->name('kompetensi-keahlian.index')->middleware('permission:menu_kompetensi_keahlian');
    Route::get('/master-data/kompetensi-keahlian/{kode}/rombel', [KompetensiKeahlianController::class, 'showRombel'])->name('kompetensi-keahlian.rombel')->middleware('permission:menu_kompetensi_keahlian');

    // Master Data — Rombel (Sumber: Rombongan Belajar & Peserta Didik)
    Route::get('/master-data/rombel', [\App\Http\Controllers\RombelController::class, 'index'])->name('rombel.index')->middleware('permission:menu_rombel');
    Route::get('/master-data/rombel/reguler', [\App\Http\Controllers\RombelController::class, 'reguler'])->name('rombel.reguler')->middleware('permission:menu_rombel');
    Route::get('/master-data/rombel/matpel', [\App\Http\Controllers\RombelController::class, 'matpel'])->name('rombel.matpel')->middleware('permission:menu_rombel');
    Route::get('/master-data/rombel/{id}/peserta-didik', [\App\Http\Controllers\RombelController::class, 'showPesertaDidik'])->name('rombel.peserta-didik')->middleware('permission:menu_rombel');

    // Master Data — Pembelajaran (Sumber: Pembelajaran, GTK, & Rombongan Belajar)
    Route::get('/master-data/pembelajaran', [\App\Http\Controllers\PembelajaranController::class, 'index'])->name('pembelajaran.index')->middleware('permission:menu_pembelajaran');
    Route::get('/master-data/pembelajaran/{id}', [\App\Http\Controllers\PembelajaranController::class, 'show'])->name('pembelajaran.show')->middleware('permission:menu_pembelajaran');

    // Manajemen Data — Peserta Didik Aktif, Guru Aktif, & Tendik Aktif (Sumber: Peserta Didik & GTK Dapodik)
    Route::get('/manajemen-data/peserta-didik-aktif', [\App\Http\Controllers\PesertaDidikAktifController::class, 'index'])->name('peserta-didik-aktif.index')->middleware('permission:menu_peserta_didik_aktif');
    Route::get('/manajemen-data/peserta-didik-aktif/{id}', [\App\Http\Controllers\PesertaDidikAktifController::class, 'show'])->name('peserta-didik-aktif.show')->middleware('permission:menu_peserta_didik_aktif');
    Route::get('/manajemen-data/guru-aktif', [\App\Http\Controllers\GuruAktifController::class, 'index'])->name('guru-aktif.index')->middleware('permission:menu_guru_aktif');
    Route::get('/manajemen-data/guru-aktif/{id}', [\App\Http\Controllers\GuruAktifController::class, 'show'])->name('guru-aktif.show')->middleware('permission:menu_guru_aktif');
    Route::get('/manajemen-data/tendik-aktif', [\App\Http\Controllers\TendikAktifController::class, 'index'])->name('tendik-aktif.index')->middleware('permission:menu_tendik_aktif');
    Route::get('/manajemen-data/tendik-aktif/{id}', [\App\Http\Controllers\TendikAktifController::class, 'show'])->name('tendik-aktif.show')->middleware('permission:menu_tendik_aktif');

    // Arsip & Maintenance (Hanya Admin)
    Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance.index')->middleware('permission:menu_maintenance');
    Route::get('/maintenance/download', [\App\Http\Controllers\MaintenanceController::class, 'downloadArchive'])->name('maintenance.download')->middleware('permission:menu_maintenance');
    Route::post('/maintenance/clean', [\App\Http\Controllers\MaintenanceController::class, 'cleanOldData'])->name('maintenance.clean')->middleware('permission:menu_maintenance');
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});
