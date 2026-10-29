<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallController;

// Installer Routes
Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'process'])->name('install.process');
Route::match(['get', 'post'], '/install/finish', [InstallController::class, 'finish'])->name('install.finish');

// Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/offline', function () {
    return response()->view('offline');
})->name('pwa.offline');

// PWA Dynamic Web App Manifest (Universal 1-Jenis: Ikon Berwarna Latar Putih)
$pwaManifestHandler = function () {
    return response()->json([
        'id' => '/?app=sae-v8',
        'name' => 'SAE - Sistem Aplikasi Edukasi',
        'short_name' => 'SAE',
        'description' => 'Platform sistem informasi edukasi terpadu: absensi cerdas RFID/webcam, manajemen GTK, siswa, dan layanan administrasi sekolah.',
        'start_url' => '/?source=pwa',
        'scope' => '/',
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#FFFFFF',
        'theme_color' => '#FFFFFF',
        'lang' => 'id',
        'dir' => 'ltr',
        'categories' => ['education', 'productivity'],
        'icons' => [
            ['src' => '/img/icons/sae-icon-72x72.png?v=8', 'sizes' => '72x72', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-96x96.png?v=8', 'sizes' => '96x96', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-128x128.png?v=8', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-144x144.png?v=8', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-152x152.png?v=8', 'sizes' => '152x152', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-192x192.png?v=8', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-192x192.png?v=8', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => '/img/icons/sae-icon-384x384.png?v=8', 'sizes' => '384x384', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-icon-512x512.png?v=8', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/img/icons/sae-maskable-512x512.png?v=8', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            [
                'name' => 'Terminal Presensi',
                'short_name' => 'Presensi',
                'description' => 'Terminal pemindaian RFID & visual scanner live',
                'url' => '/presensi/scan?source=pwa_shortcut',
                'icons' => [['src' => '/img/icons/sae-icon-96x96.png?v=8', 'sizes' => '96x96']],
            ],
            [
                'name' => 'Portal Masuk',
                'short_name' => 'Login',
                'description' => 'Login portal akun sekolah terintegrasi',
                'url' => '/login?source=pwa_shortcut',
                'icons' => [['src' => '/img/icons/sae-icon-96x96.png?v=8', 'sizes' => '96x96']],
            ],
            [
                'name' => 'Pengecekan NISN',
                'short_name' => 'Cek NISN',
                'description' => 'Validasi status keaktifan peserta didik',
                'url' => '/#nisn',
                'icons' => [['src' => '/img/icons/sae-icon-96x96.png?v=8', 'sizes' => '96x96']],
            ],
        ],
    ], 200, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
};
Route::get('/manifest.webmanifest', $pwaManifestHandler)->name('pwa.manifest');
Route::get('/manifest.json', $pwaManifestHandler);
Route::post('/api/check-nisn', [HomeController::class, 'checkNisn'])->name('api.check-nisn');

// Persuratan Digital — Verifikasi Publik Keaslian Surat & Direct Scan via QR Code
Route::get('/v/doc/{doc_id}', [\App\Http\Controllers\SuratPublicVerifyController::class, 'verifyDoc'])->name('surat.public-verify');
Route::get('/verifikasi-surat/{doc_id}', [\App\Http\Controllers\SuratPublicVerifyController::class, 'verifyDoc'])->name('surat.public-verify-long');

// Kartu Pelajar Digital — Verifikasi Publik & Direct Scan (Mendukung URL Baru /v/{nisn} dan Format Lama /{nisn})
Route::get('/v/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify-short');
Route::get('/verifikasi-pelajar/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify');
Route::get('/kartu-pelajar/preview/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'preview'])->name('kartu-pelajar.preview');
Route::get('/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->where('nisn', '[0-9]{10}')->name('kartu-pelajar.verify-legacy');

// Perpustakaan Publik — Katalog, Gerbang Anggota & Area Member
Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PerpusPublicController::class, 'index'])->name('index');
    Route::get('/login', [\App\Http\Controllers\PerpusPublicController::class, 'loginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\PerpusPublicController::class, 'login'])->name('login.post');
    Route::post('/aktifkan', [\App\Http\Controllers\PerpusPublicController::class, 'aktifkan'])->name('aktifkan');
    Route::post('/logout', [\App\Http\Controllers\PerpusPublicController::class, 'logout'])->name('logout');
    Route::get('/verifikasi/{token}', [\App\Http\Controllers\PerpusPublicController::class, 'verifikasi'])->name('verifikasi');

    // Area member (butuh sesi anggota; dijaga di controller).
    Route::get('/portal', [\App\Http\Controllers\PerpusPublicController::class, 'portal'])->name('portal');
    Route::get('/katalog', [\App\Http\Controllers\PerpusPublicController::class, 'katalog'])->name('katalog');
    Route::get('/buku/{id}', [\App\Http\Controllers\PerpusPublicController::class, 'detail'])->name('buku.detail');
    Route::post('/buku/{id}/reservasi', [\App\Http\Controllers\PerpusPublicController::class, 'reservasi'])->name('buku.reservasi');
    Route::post('/buku/{id}/ulasan', [\App\Http\Controllers\PerpusPublicController::class, 'simpanUlasan'])->name('buku.ulasan');
    Route::post('/reservasi/{id}/batal', [\App\Http\Controllers\PerpusPublicController::class, 'batalkanReservasi'])->name('reservasi.batal');
    Route::get('/notifikasi', [\App\Http\Controllers\PerpusPublicController::class, 'notifikasi'])->name('notifikasi');
    Route::post('/notifikasi/baca', [\App\Http\Controllers\PerpusPublicController::class, 'tandaiNotifikasi'])->name('notifikasi.baca');
    Route::post('/notifikasi/{id}/baca', [\App\Http\Controllers\PerpusPublicController::class, 'tandaiNotifikasi'])->name('notifikasi.baca.satu');
    Route::get('/profil', [\App\Http\Controllers\PerpusPublicController::class, 'profil'])->name('profil');
    Route::get('/kartu', [\App\Http\Controllers\PerpusPublicController::class, 'kartuSaya'])->name('kartu');
    Route::post('/profil', [\App\Http\Controllers\PerpusPublicController::class, 'updateProfil'])->name('profil.update');
    Route::post('/profil/password', [\App\Http\Controllers\PerpusPublicController::class, 'updatePassword'])->name('profil.password');
    Route::post('/checkin', [\App\Http\Controllers\PerpusPublicController::class, 'checkin'])->name('checkin');
});
