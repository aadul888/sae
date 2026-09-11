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

// Kartu Pelajar Digital — Verifikasi Publik & Direct Scan (Privacy-by-Design)
Route::get('/v/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify-short');
Route::get('/verifikasi-pelajar/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify');
Route::get('/kartu-pelajar/preview/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'preview'])->name('kartu-pelajar.preview');

// Auth Routes (Multi-User)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/auth/update-password-wajib', [AuthController::class, 'showForceUpdatePassword'])->name('auth.force-update-password');
Route::post('/auth/update-password-wajib', [AuthController::class, 'processForceUpdatePassword'])->name('auth.force-update-password.post');
Route::get('/auth/cancel-force-update', [AuthController::class, 'cancelForceUpdate'])->name('auth.cancel-force-update');

// Dashboard Multi-User Routes (Protected)
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru');
    Route::get('/tendik', [DashboardController::class, 'tendik'])->name('tendik');
    Route::get('/peserta-didik', [DashboardController::class, 'pesertaDidik'])->name('peserta-didik');
    Route::get('/peserta_didik', [DashboardController::class, 'pesertaDidik'])->name('peserta_didik');

    // Profil Pengguna & Keamanan Akun
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::put('/profil/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profil/kontak', [\App\Http\Controllers\ProfileController::class, 'updateContact'])->name('profile.contact');

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
    Route::post('/hak-akses/tugas-tambahan/store', [PermissionController::class, 'storeTugasTambahan'])->name('hak-akses.tugas-tambahan.store')->middleware('permission:menu_hak_akses');
    Route::delete('/hak-akses/tugas-tambahan/{id}', [PermissionController::class, 'destroyTugasTambahan'])->name('hak-akses.tugas-tambahan.destroy')->middleware('permission:menu_hak_akses');
    Route::post('/hak-akses/tugas-tambahan/sync-wali', [PermissionController::class, 'syncWaliKelas'])->name('hak-akses.tugas-tambahan.sync-wali')->middleware('permission:menu_hak_akses');

    // Pengaturan — Identitas Sekolah (Sumber: Sekolah Dapodik)
    Route::get('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'index'])->name('identitas-sekolah.index')->middleware('permission:menu_pengaturan');
    Route::put('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'update'])->name('identitas-sekolah.update')->middleware('permission:menu_pengaturan');
    Route::post('/identitas-sekolah/upload-logo', [\App\Http\Controllers\IdentitasSekolahController::class, 'uploadLogo'])->name('identitas-sekolah.upload-logo')->middleware('permission:menu_pengaturan');
    Route::delete('/identitas-sekolah/delete-logo', [\App\Http\Controllers\IdentitasSekolahController::class, 'deleteLogo'])->name('identitas-sekolah.delete-logo')->middleware('permission:menu_pengaturan');
    Route::post('/identitas-sekolah/upload-kop', [\App\Http\Controllers\IdentitasSekolahController::class, 'uploadKop'])->name('identitas-sekolah.upload-kop')->middleware('permission:menu_pengaturan');
    Route::delete('/identitas-sekolah/delete-kop', [\App\Http\Controllers\IdentitasSekolahController::class, 'deleteKop'])->name('identitas-sekolah.delete-kop')->middleware('permission:menu_pengaturan');

    // Layanan Digital — Pengumuman & Broadcast
    Route::get('/informasi', [\App\Http\Controllers\PengumumanController::class, 'pengguna'])->name('informasi.index');
    Route::post('/informasi/mark-all-read', [\App\Http\Controllers\PengumumanController::class, 'markAllRead'])->name('informasi.mark-all-read');
    Route::post('/informasi/{id}/mark-read', [\App\Http\Controllers\PengumumanController::class, 'markSingleRead'])->name('informasi.mark-read');
    Route::get('/informasi/{id}/detail', [\App\Http\Controllers\PengumumanController::class, 'detail'])->name('informasi.detail');
    Route::get('/pengumuman', [\App\Http\Controllers\PengumumanController::class, 'index'])->name('pengumuman.index')->middleware('permission:menu_pengumuman');
    Route::post('/pengumuman', [\App\Http\Controllers\PengumumanController::class, 'store'])->name('pengumuman.store')->middleware('permission:menu_pengumuman');
    Route::put('/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class, 'update'])->name('pengumuman.update')->middleware('permission:menu_pengumuman');
    Route::delete('/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class, 'destroy'])->name('pengumuman.destroy')->middleware('permission:menu_pengumuman');
    Route::post('/pengumuman/{id}/toggle', [\App\Http\Controllers\PengumumanController::class, 'toggle'])->name('pengumuman.toggle')->middleware('permission:menu_pengumuman');

    // Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar)
    Route::get('/master-data/kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])->name('kompetensi-keahlian.index')->middleware('permission:menu_kompetensi_keahlian');
    Route::get('/master-data/kompetensi-keahlian/{kode}/rombel', [KompetensiKeahlianController::class, 'showRombel'])->name('kompetensi-keahlian.rombel')->middleware('permission:menu_kompetensi_keahlian');
    Route::post('/master-data/kompetensi-keahlian/{kode}/logo', [KompetensiKeahlianController::class, 'uploadLogo'])->name('kompetensi-keahlian.upload-logo')->middleware('permission:menu_kompetensi_keahlian');
    Route::delete('/master-data/kompetensi-keahlian/{kode}/logo', [KompetensiKeahlianController::class, 'deleteLogo'])->name('kompetensi-keahlian.delete-logo')->middleware('permission:menu_kompetensi_keahlian');

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
    Route::get('/manajemen-data/peserta-didik-aktif/rombel-members', [\App\Http\Controllers\PesertaDidikAktifController::class, 'getRombelMembers'])->name('peserta-didik-aktif.rombel-members')->middleware('permission:menu_peserta_didik_aktif');
    Route::post('/manajemen-data/peserta-didik-aktif/upload-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'uploadFoto'])->name('peserta-didik-aktif.upload-foto')->middleware('permission:menu_peserta_didik_aktif');
    Route::post('/manajemen-data/peserta-didik-aktif/bulk-upload-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'bulkUploadFoto'])->name('peserta-didik-aktif.bulk-upload-foto')->middleware('permission:menu_peserta_didik_aktif');
    Route::delete('/manajemen-data/peserta-didik-aktif/{id}/delete-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'deleteFoto'])->name('peserta-didik-aktif.delete-foto')->middleware('permission:menu_peserta_didik_aktif');
    Route::get('/manajemen-data/peserta-didik-aktif/{id}', [\App\Http\Controllers\PesertaDidikAktifController::class, 'show'])->name('peserta-didik-aktif.show')->middleware('permission:menu_peserta_didik_aktif');
    Route::get('/manajemen-data/guru-aktif', [\App\Http\Controllers\GuruAktifController::class, 'index'])->name('guru-aktif.index')->middleware('permission:menu_guru_aktif');
    Route::get('/manajemen-data/guru-aktif/{id}', [\App\Http\Controllers\GuruAktifController::class, 'show'])->name('guru-aktif.show')->middleware('permission:menu_guru_aktif');
    Route::get('/manajemen-data/tendik-aktif', [\App\Http\Controllers\TendikAktifController::class, 'index'])->name('tendik-aktif.index')->middleware('permission:menu_tendik_aktif');
    Route::get('/manajemen-data/tendik-aktif/{id}', [\App\Http\Controllers\TendikAktifController::class, 'show'])->name('tendik-aktif.show')->middleware('permission:menu_tendik_aktif');

    // Kartu Pelajar Digital — Layanan Cetak
    Route::get('/kartu-pelajar/cetak/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'printSingle'])->name('kartu-pelajar.cetak-single');
    Route::get('/kartu-pelajar/cetak-rombel/{rombelId}', [\App\Http\Controllers\KartuPelajarController::class, 'printRombel'])->name('kartu-pelajar.cetak-rombel');

    // Arsip & Maintenance (Hanya Admin)
    Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance.index')->middleware('permission:menu_maintenance');
    Route::get('/maintenance/download', [\App\Http\Controllers\MaintenanceController::class, 'downloadArchive'])->name('maintenance.download')->middleware('permission:menu_maintenance');
    Route::post('/maintenance/clean', [\App\Http\Controllers\MaintenanceController::class, 'cleanOldData'])->name('maintenance.clean')->middleware('permission:menu_maintenance');
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});
