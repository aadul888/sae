<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;

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

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/api/check-nisn', [HomeController::class, 'checkNisn'])->name('api.check-nisn');

// Auth Routes (Multi-User)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);

// Dashboard Multi-User Routes
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru');
    Route::get('/siswa', [DashboardController::class, 'siswa'])->name('siswa');
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});


