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
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa');

    // Tarik Data Dapodik
    Route::get('/tarik-data', [DapodikController::class, 'index'])->name('dapodik');
    Route::post('/tarik-data/apikey', [DapodikController::class, 'generateApiKey'])->name('dapodik.apikey');

    // Manajemen Pengguna (3 Tab: Admin, Guru/Tendik, Siswa)
    Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna.index');
    Route::put('/pengguna/{pengguna}', [UserController::class, 'update'])->name('pengguna.update');
    Route::delete('/pengguna/{pengguna}', [UserController::class, 'destroy'])->name('pengguna.destroy');
    Route::post('/pengguna/{pengguna}/reset-password', [UserController::class, 'resetPassword'])->name('pengguna.resetPassword');

    // Update Sistem
    Route::get('/update', [UpdateController::class, 'index'])->name('update');
    Route::get('/update/check', [UpdateController::class, 'check'])->name('update.check');
    Route::post('/update/execute', [UpdateController::class, 'execute'])->name('update.execute');
    Route::get('/update/diagnose', [UpdateController::class, 'diagnose'])->name('update.diagnose');

    // Pengaturan Hak Akses (Role & Permission)
    Route::get('/hak-akses', [PermissionController::class, 'index'])->name('hak-akses.index');
    Route::post('/hak-akses/toggle', [PermissionController::class, 'toggle'])->name('hak-akses.toggle');
    Route::post('/hak-akses/reset', [PermissionController::class, 'resetDefault'])->name('hak-akses.reset');

    // Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar)
    Route::get('/master-data/kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])->name('kompetensi-keahlian.index');
    Route::get('/master-data/kompetensi-keahlian/{kode}/rombel', [KompetensiKeahlianController::class, 'showRombel'])->name('kompetensi-keahlian.rombel');

    // Master Data — Rombel (Sumber: Rombongan Belajar & Peserta Didik)
    Route::get('/master-data/rombel', [\App\Http\Controllers\RombelController::class, 'index'])->name('rombel.index');
    Route::get('/master-data/rombel/{id}/siswa', [\App\Http\Controllers\RombelController::class, 'showSiswa'])->name('rombel.siswa');
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});
