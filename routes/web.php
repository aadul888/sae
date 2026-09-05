<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DapodikController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\UpdateController;

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

    // Update Sistem
    Route::get('/update', [UpdateController::class, 'index'])->name('update');
    Route::get('/update/check', [UpdateController::class, 'check'])->name('update.check');
    Route::post('/update/execute', [UpdateController::class, 'execute'])->name('update.execute');
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});


