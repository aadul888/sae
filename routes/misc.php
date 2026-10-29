<?php

use Illuminate\Support\Facades\Route;

// SAE Forms — Formulir & Survei Publik
Route::get('/f/{slug}', [\App\Http\Controllers\FormulirController::class, 'showPublic'])->name('formulir.public');
Route::post('/f/{slug}', [\App\Http\Controllers\FormulirController::class, 'submitPublic'])->name('formulir.submit');
Route::get('/f/{slug}/sukses/{respon}', [\App\Http\Controllers\FormulirController::class, 'successPublic'])->name('formulir.success');
Route::get('/formulir/{slug}', [\App\Http\Controllers\FormulirController::class, 'showPublic']);

// Terminal Kiosk Presensi Publik (Proteksi Kode Akses & Direct Scan)
Route::get('/scan', [\App\Http\Controllers\PresensiController::class, 'scanKiosk'])->name('scan');
Route::get('/presensi/scan', [\App\Http\Controllers\PresensiController::class, 'scanKiosk'])->name('presensi.scan');
Route::get('/presensi/scan/auth', [\App\Http\Controllers\PresensiController::class, 'kioskAuth'])->name('presensi.kiosk.auth');
Route::post('/presensi/scan/unlock', [\App\Http\Controllers\PresensiController::class, 'kioskUnlock'])->name('presensi.kiosk.unlock');
Route::get('/presensi/scan/lock', [\App\Http\Controllers\PresensiController::class, 'kioskLock'])->name('presensi.kiosk.lock');
Route::post('/presensi/scan/process', [\App\Http\Controllers\PresensiController::class, 'processScan'])->name('presensi.scan.process');

// Auth Routes (Multi-User)
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login.post');
Route::get('/auth/google', [\App\Http\Controllers\AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
Route::get('/auth/update-password-wajib', [\App\Http\Controllers\AuthController::class, 'showForceUpdatePassword'])->name('auth.force-update-password');
Route::post('/auth/update-password-wajib', [\App\Http\Controllers\AuthController::class, 'processForceUpdatePassword'])->name('auth.force-update-password.post');
Route::get('/auth/cancel-force-update', [\App\Http\Controllers\AuthController::class, 'cancelForceUpdate'])->name('auth.cancel-force-update');

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});

// Backward-compatible alias untuk rute API/AJAX legacy tanpa awalan /dashboard
Route::middleware(['web'])->group(function () {
    Route::get('/manajemen-data/guru-aktif/{id}', [\App\Http\Controllers\GuruAktifController::class, 'show']);
    Route::delete('/peserta-didik/izin/{id}', [\App\Http\Controllers\PesertaDidikIzinController::class, 'destroy']);
    Route::post('/kesiswaan/berkas/{id}', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'updateBerkas']);
    Route::post('/kesiswaan/klaper/sync', [\App\Http\Controllers\KesiswaanController::class, 'syncKlaper']);
    Route::put('/kesiswaan/klaper/{id}', [\App\Http\Controllers\KesiswaanController::class, 'updateKlaper']);
});
