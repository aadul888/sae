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

// Kartu Pelajar Digital — Verifikasi Publik & Direct Scan (Privacy-by-Design)
Route::get('/v/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify-short');
Route::get('/verifikasi-pelajar/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'verify'])->name('kartu-pelajar.verify');
Route::get('/kartu-pelajar/preview/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'preview'])->name('kartu-pelajar.preview');

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
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/auth/update-password-wajib', [AuthController::class, 'showForceUpdatePassword'])->name('auth.force-update-password');
Route::post('/auth/update-password-wajib', [AuthController::class, 'processForceUpdatePassword'])->name('auth.force-update-password.post');
Route::get('/auth/cancel-force-update', [AuthController::class, 'cancelForceUpdate'])->name('auth.cancel-force-update');

// Dashboard Multi-User Routes (Protected)
Route::prefix('dashboard')->name('dashboard.')->group(function () {


    // Modul agenda-kbm (Auto-generated by sae:make-module)
    Route::get('/agenda-kbm', [\App\Http\Controllers\AgendaKbmController::class, 'index'])->name('agenda-kbm.index')->middleware('permission:menu_agenda_kbm,read');
    Route::get('/agenda-kbm/next-pertemuan', [\App\Http\Controllers\AgendaKbmController::class, 'getNextPertemuan'])->name('agenda-kbm.next-pertemuan')->middleware('permission:menu_agenda_kbm,read');
    Route::get('/agenda-kbm/cek-kalender', [\App\Http\Controllers\AgendaKbmController::class, 'cekKalenderTanggal'])->name('agenda-kbm.cek-kalender')->middleware('permission:menu_agenda_kbm,read');
    Route::get('/agenda-kbm/cetak', [\App\Http\Controllers\AgendaKbmController::class, 'cetak'])->name('agenda-kbm.cetak')->middleware('permission:menu_agenda_kbm,read');
    Route::post('/agenda-kbm', [\App\Http\Controllers\AgendaKbmController::class, 'store'])->name('agenda-kbm.store')->middleware('permission:menu_agenda_kbm,create');
    Route::get('/agenda-kbm/{id}', [\App\Http\Controllers\AgendaKbmController::class, 'show'])->name('agenda-kbm.show')->middleware('permission:menu_agenda_kbm,read');
    Route::put('/agenda-kbm/{id}', [\App\Http\Controllers\AgendaKbmController::class, 'update'])->name('agenda-kbm.update')->middleware('permission:menu_agenda_kbm,update');
    Route::delete('/agenda-kbm/{id}', [\App\Http\Controllers\AgendaKbmController::class, 'destroy'])->name('agenda-kbm.destroy')->middleware('permission:menu_agenda_kbm,delete');

    // Modul presensi-mengajar (Auto-generated by sae:make-module)
    Route::get('/presensi-mengajar', [\App\Http\Controllers\PresensiMengajarController::class, 'index'])->name('presensi-mengajar.index')->middleware('permission:menu_presensi_mengajar,read');
    Route::get('/presensi-mengajar/cek-kalender', [\App\Http\Controllers\PresensiMengajarController::class, 'cekKalenderTanggal'])->name('presensi-mengajar.cek-kalender')->middleware('permission:menu_presensi_mengajar,read');
    Route::post('/presensi-mengajar', [\App\Http\Controllers\PresensiMengajarController::class, 'store'])->name('presensi-mengajar.store')->middleware('permission:menu_presensi_mengajar,create');
    Route::get('/presensi-mengajar/{id}', [\App\Http\Controllers\PresensiMengajarController::class, 'show'])->name('presensi-mengajar.show')->middleware('permission:menu_presensi_mengajar,read');
    Route::put('/presensi-mengajar/{id}', [\App\Http\Controllers\PresensiMengajarController::class, 'update'])->name('presensi-mengajar.update')->middleware('permission:menu_presensi_mengajar,update');
    Route::delete('/presensi-mengajar/{id}', [\App\Http\Controllers\PresensiMengajarController::class, 'destroy'])->name('presensi-mengajar.destroy')->middleware('permission:menu_presensi_mengajar,delete');
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin');
    Route::get('/guru', [DashboardController::class, 'guru'])->name('guru');
    Route::get('/tendik', [DashboardController::class, 'tendik'])->name('tendik');
    Route::get('/peserta-didik', [DashboardController::class, 'pesertaDidik'])->name('peserta-didik');
    Route::get('/peserta_didik', [DashboardController::class, 'pesertaDidik'])->name('peserta_didik');

    // Profil Pengguna & Keamanan Akun
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile');
    Route::put('/profil/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profil/kontak', [\App\Http\Controllers\ProfileController::class, 'updateContact'])->name('profile.contact');
    Route::post('/profil/foto', [\App\Http\Controllers\ProfileController::class, 'uploadFoto'])->name('profile.foto.upload');
    Route::delete('/profil/foto', [\App\Http\Controllers\ProfileController::class, 'deleteFoto'])->name('profile.foto.delete');

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
    Route::get('/hak-akses', [PermissionController::class, 'index'])->name('hak-akses.index')->middleware('permission:menu_hak_akses,read');
    Route::post('/hak-akses/toggle', [PermissionController::class, 'toggle'])->name('hak-akses.toggle')->middleware('permission:menu_hak_akses,update');
    Route::post('/hak-akses/sync', [PermissionController::class, 'sync'])->name('hak-akses.sync')->middleware('permission:menu_hak_akses,update');
    Route::post('/hak-akses/reset', [PermissionController::class, 'resetDefault'])->name('hak-akses.reset')->middleware('permission:menu_hak_akses,update');
    Route::post('/hak-akses/add-module', [PermissionController::class, 'addModule'])->name('hak-akses.add-module')->middleware('permission:menu_hak_akses,create');
    Route::post('/hak-akses/remove-module', [PermissionController::class, 'removeModule'])->name('hak-akses.remove-module')->middleware('permission:menu_hak_akses,delete');
    Route::post('/hak-akses/tugas-tambahan/store', [PermissionController::class, 'storeTugasTambahan'])->name('hak-akses.tugas-tambahan.store')->middleware('permission:menu_hak_akses,create');
    Route::delete('/hak-akses/tugas-tambahan/{id}', [PermissionController::class, 'destroyTugasTambahan'])->name('hak-akses.tugas-tambahan.destroy')->middleware('permission:menu_hak_akses,delete');
    Route::post('/hak-akses/tugas-tambahan/sync-wali', [PermissionController::class, 'syncWaliKelas'])->name('hak-akses.tugas-tambahan.sync-wali')->middleware('permission:menu_hak_akses,update');

    // Pengaturan — Identitas Sekolah (Sumber: Sekolah Dapodik)
    Route::get('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'index'])->name('identitas-sekolah.index')->middleware('permission:menu_pengaturan,read');
    Route::put('/identitas-sekolah', [\App\Http\Controllers\IdentitasSekolahController::class, 'update'])->name('identitas-sekolah.update')->middleware('permission:menu_pengaturan,update');
    Route::post('/identitas-sekolah/upload-logo', [\App\Http\Controllers\IdentitasSekolahController::class, 'uploadLogo'])->name('identitas-sekolah.upload-logo')->middleware('permission:menu_pengaturan,update');
    Route::delete('/identitas-sekolah/delete-logo', [\App\Http\Controllers\IdentitasSekolahController::class, 'deleteLogo'])->name('identitas-sekolah.delete-logo')->middleware('permission:menu_pengaturan,delete');
    Route::post('/identitas-sekolah/upload-kop', [\App\Http\Controllers\IdentitasSekolahController::class, 'uploadKop'])->name('identitas-sekolah.upload-kop')->middleware('permission:menu_pengaturan,update');
    Route::delete('/identitas-sekolah/delete-kop', [\App\Http\Controllers\IdentitasSekolahController::class, 'deleteKop'])->name('identitas-sekolah.delete-kop')->middleware('permission:menu_pengaturan,delete');

    // Layanan Digital — Pengumuman & Broadcast
    Route::get('/informasi', [\App\Http\Controllers\PengumumanController::class, 'pengguna'])->name('informasi.index');
    Route::post('/informasi/mark-all-read', [\App\Http\Controllers\PengumumanController::class, 'markAllRead'])->name('informasi.mark-all-read');
    Route::post('/informasi/{id}/mark-read', [\App\Http\Controllers\PengumumanController::class, 'markSingleRead'])->name('informasi.mark-read');
    Route::get('/informasi/{id}/detail', [\App\Http\Controllers\PengumumanController::class, 'detail'])->name('informasi.detail');
    Route::get('/pengumuman', [\App\Http\Controllers\PengumumanController::class, 'index'])->name('pengumuman.index')->middleware('permission:menu_pengumuman,read');
    Route::post('/pengumuman', [\App\Http\Controllers\PengumumanController::class, 'store'])->name('pengumuman.store')->middleware('permission:menu_pengumuman,create');
    Route::put('/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class, 'update'])->name('pengumuman.update')->middleware('permission:menu_pengumuman,update');
    Route::delete('/pengumuman/{id}', [\App\Http\Controllers\PengumumanController::class, 'destroy'])->name('pengumuman.destroy')->middleware('permission:menu_pengumuman,delete');
    Route::post('/pengumuman/{id}/toggle', [\App\Http\Controllers\PengumumanController::class, 'toggle'])->name('pengumuman.toggle')->middleware('permission:menu_pengumuman,update');

    // Layanan Digital — Formulir & Survei (SAE Form / OpnForm)
    Route::get('/formulir', [\App\Http\Controllers\FormulirController::class, 'index'])->name('formulir.index')->middleware('permission:menu_formulir,read');
    Route::get('/formulir/buat', [\App\Http\Controllers\FormulirController::class, 'create'])->name('formulir.create')->middleware('permission:menu_formulir,create');
    Route::post('/formulir', [\App\Http\Controllers\FormulirController::class, 'store'])->name('formulir.store')->middleware('permission:menu_formulir,create');
    Route::get('/formulir/{id}/edit', [\App\Http\Controllers\FormulirController::class, 'edit'])->name('formulir.edit')->middleware('permission:menu_formulir,update');
    Route::put('/formulir/{id}', [\App\Http\Controllers\FormulirController::class, 'update'])->name('formulir.update')->middleware('permission:menu_formulir,update');
    Route::delete('/formulir/{id}', [\App\Http\Controllers\FormulirController::class, 'destroy'])->name('formulir.destroy')->middleware('permission:menu_formulir,delete');
    Route::post('/formulir/{id}/toggle', [\App\Http\Controllers\FormulirController::class, 'toggle'])->name('formulir.toggle')->middleware('permission:menu_formulir,update');
    Route::post('/formulir/{id}/duplikasi', [\App\Http\Controllers\FormulirController::class, 'duplicate'])->name('formulir.duplicate')->middleware('permission:menu_formulir,create');
    Route::get('/formulir/{id}/respon', [\App\Http\Controllers\FormulirController::class, 'responses'])->name('formulir.responses')->middleware('permission:menu_formulir,read');
    Route::get('/formulir/{id}/export-csv', [\App\Http\Controllers\FormulirController::class, 'exportCsv'])->name('formulir.export-csv')->middleware('permission:menu_formulir,read');
    Route::delete('/formulir/{id}/respon/{responId}', [\App\Http\Controllers\FormulirController::class, 'deleteResponse'])->name('formulir.delete-response')->middleware('permission:menu_formulir,delete');

    // Layanan Digital — Presensi & RFID Realtime
    Route::get('/presensi', [\App\Http\Controllers\PresensiController::class, 'index'])->name('presensi.index')->middleware('permission:menu_rfid,read');
    Route::get('/presensi/scan', [\App\Http\Controllers\PresensiController::class, 'scanKiosk'])->name('presensi.scan');
    Route::post('/presensi/scan/process', [\App\Http\Controllers\PresensiController::class, 'processScan'])->name('presensi.scan.process');
    Route::get('/presensi/live-log', [\App\Http\Controllers\PresensiController::class, 'getLiveLog'])->name('presensi.live-log')->middleware('permission:menu_rfid,read');
    Route::post('/presensi/rfid/assign', [\App\Http\Controllers\PresensiController::class, 'assignRfid'])->name('presensi.rfid.assign')->middleware('permission:menu_rfid,update');
    Route::post('/presensi/pengaturan', [\App\Http\Controllers\PresensiController::class, 'updatePengaturan'])->name('presensi.pengaturan.update')->middleware('permission:menu_rfid,update');
    Route::post('/presensi/pengaturan/kode-akses', [\App\Http\Controllers\PresensiController::class, 'updateKodeAkses'])->name('presensi.pengaturan.kode-akses')->middleware('permission:menu_rfid,update');
    Route::get('/presensi/export', [\App\Http\Controllers\PresensiController::class, 'export'])->name('presensi.export')->middleware('permission:menu_rfid,read');
    Route::post('/presensi/izin/{id}/verifikasi', [\App\Http\Controllers\PresensiController::class, 'verifikasiIzin'])->name('presensi.izin.verifikasi')->middleware('permission:menu_rfid,update');

    // Administrasi Guru — Presensi Kelas (Wali Kelas & Guru)
    Route::get('/presensi/kelas', [\App\Http\Controllers\PresensiController::class, 'kelas'])->name('presensi.kelas')->middleware('permission:menu_presensi_peserta_didik,read');
    Route::post('/presensi/kelas/status', [\App\Http\Controllers\PresensiController::class, 'updateStatusKelas'])->name('presensi.kelas.status')->middleware('permission:menu_presensi_peserta_didik,update');
    Route::post('/presensi/kelas/auto-alpha', [\App\Http\Controllers\PresensiController::class, 'tandaiAlphaRombel'])->name('presensi.kelas.auto-alpha')->middleware('permission:menu_presensi_peserta_didik,update');

    // Layanan Digital — Notifikasi Transaksi Pengguna
    Route::post('/notifikasi-transaksi/mark-all-read', [\App\Http\Controllers\PesertaDidikPresensiController::class, 'markAllTransactionsRead'])->name('notifikasi-transaksi.mark-all-read');
    Route::post('/notifikasi-transaksi/{id}/mark-read', [\App\Http\Controllers\PesertaDidikPresensiController::class, 'markTransactionRead'])->name('notifikasi-transaksi.mark-read');

    // Administrasi Tendik — Persuratan & Arsip Digital (Modular SAE)
    Route::get('/persuratan', fn() => redirect()->route('dashboard.persuratan.masuk.index'))->name('persuratan.index');

    // 1. Surat Masuk & Disposisi
    Route::get('/persuratan/masuk', [\App\Http\Controllers\SuratMasukController::class, 'index'])->name('persuratan.masuk.index')->middleware('permission:menu_surat_masuk,read');
    Route::post('/persuratan/masuk', [\App\Http\Controllers\SuratMasukController::class, 'store'])->name('persuratan.masuk.store')->middleware('permission:menu_surat_masuk,create');
    Route::get('/persuratan/masuk/{id}', [\App\Http\Controllers\SuratMasukController::class, 'show'])->name('persuratan.masuk.show')->middleware('permission:menu_surat_masuk,read');
    Route::put('/persuratan/masuk/{id}', [\App\Http\Controllers\SuratMasukController::class, 'update'])->name('persuratan.masuk.update')->middleware('permission:menu_surat_masuk,update');
    Route::delete('/persuratan/masuk/{id}', [\App\Http\Controllers\SuratMasukController::class, 'destroy'])->name('persuratan.masuk.destroy')->middleware('permission:menu_surat_masuk,delete');
    Route::post('/persuratan/masuk/{id}/disposisi', [\App\Http\Controllers\SuratMasukController::class, 'disposisi'])->name('persuratan.masuk.disposisi')->middleware('permission:menu_surat_masuk,update');
    Route::get('/persuratan/masuk/{id}/disposisi/cetak', [\App\Http\Controllers\SuratMasukController::class, 'cetakDisposisi'])->name('persuratan.masuk.disposisi.cetak')->middleware('permission:menu_surat_masuk,read');

    // 2. Surat Keluar & Surat Keterangan Siswa
    Route::get('/persuratan/keluar', [\App\Http\Controllers\SuratKeluarController::class, 'index'])->name('persuratan.keluar.index')->middleware('permission:menu_surat_keluar,read');
    Route::post('/persuratan/keluar', [\App\Http\Controllers\SuratKeluarController::class, 'store'])->name('persuratan.keluar.store')->middleware('permission:menu_surat_keluar,create');
    Route::get('/persuratan/keluar/next-number', [\App\Http\Controllers\SuratKeluarController::class, 'getNextNumber'])->name('persuratan.keluar.next-number')->middleware('permission:menu_surat_keluar,read');
    Route::get('/persuratan/keluar/search-siswa', [\App\Http\Controllers\SuratKeluarController::class, 'searchSiswa'])->name('persuratan.keluar.search-siswa')->middleware('permission:menu_surat_keluar,read');
    Route::get('/persuratan/keluar/{id}', [\App\Http\Controllers\SuratKeluarController::class, 'show'])->name('persuratan.keluar.show')->middleware('permission:menu_surat_keluar,read');
    Route::put('/persuratan/keluar/{id}', [\App\Http\Controllers\SuratKeluarController::class, 'update'])->name('persuratan.keluar.update')->middleware('permission:menu_surat_keluar,update');
    Route::delete('/persuratan/keluar/{id}', [\App\Http\Controllers\SuratKeluarController::class, 'destroy'])->name('persuratan.keluar.destroy')->middleware('permission:menu_surat_keluar,delete');
    Route::post('/persuratan/keluar/keterangan', [\App\Http\Controllers\SuratKeluarController::class, 'suratKeteranganStore'])->name('persuratan.keterangan.store')->middleware('permission:menu_surat_keluar,create');
    Route::get('/persuratan/keluar/keterangan/{id}/cetak', [\App\Http\Controllers\SuratKeluarController::class, 'suratKeteranganCetak'])->name('persuratan.keterangan.cetak')->middleware('permission:menu_surat_keluar,read');

    // 3. Pengaturan Sistem Persuratan & Harddisk (HDD)
    Route::get('/persuratan/pengaturan', [\App\Http\Controllers\PersuratanSettingController::class, 'index'])->name('persuratan.pengaturan.index')->middleware('permission:menu_pengaturan_persuratan,read');
    Route::post('/persuratan/pengaturan', [\App\Http\Controllers\PersuratanSettingController::class, 'updateSettings'])->name('persuratan.pengaturan.update')->middleware('permission:menu_pengaturan_persuratan,update');
    Route::post('/persuratan/pengaturan/test-hdd', [\App\Http\Controllers\PersuratanSettingController::class, 'testHdd'])->name('persuratan.pengaturan.test-hdd')->middleware('permission:menu_pengaturan_persuratan,read');
    Route::post('/persuratan/pengaturan/indeks', [\App\Http\Controllers\PersuratanSettingController::class, 'storeIndeks'])->name('persuratan.pengaturan.indeks.store')->middleware('permission:menu_pengaturan_persuratan,create');
    Route::put('/persuratan/pengaturan/indeks/{id}', [\App\Http\Controllers\PersuratanSettingController::class, 'updateIndeks'])->name('persuratan.pengaturan.indeks.update')->middleware('permission:menu_pengaturan_persuratan,update');
    Route::delete('/persuratan/pengaturan/indeks/{id}', [\App\Http\Controllers\PersuratanSettingController::class, 'destroyIndeks'])->name('persuratan.pengaturan.indeks.destroy')->middleware('permission:menu_pengaturan_persuratan,delete');

    // 4. Berkas Dokumen Persuratan (Render/Stream & Unduh dari HDD)
    Route::get('/persuratan/dokumen/{id}/view', [\App\Http\Controllers\SuratMasukController::class, 'viewDokumen'])->name('persuratan.dokumen.view');
    Route::get('/persuratan/dokumen/{id}/download', [\App\Http\Controllers\SuratMasukController::class, 'downloadDokumen'])->name('persuratan.dokumen.download');

    // Administrasi Tendik — Kesiswaan Terpadu (5 Kluster: Peserta Didik, Administrasi, Kedisiplinan, Kegiatan Siswa, Prestasi)
    Route::prefix('kesiswaan')->name('kesiswaan.')->group(function () {
        Route::get('/', fn() => redirect()->route('dashboard.kesiswaan.peserta-didik.index'))->name('index');

        // 1. Kluster Peserta Didik (Aktif, Tidak Aktif, Alumni, Berkas, Usulan Perubahan)
        Route::prefix('peserta-didik')->name('peserta-didik.')->group(function () {
            Route::get('/', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'index'])->name('index')->middleware('permission:menu_kesiswaan_peserta_didik,read');
            Route::post('/usulan', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'storeUsulan'])->name('usulan.store')->middleware('permission:menu_kesiswaan_peserta_didik,create');
            Route::post('/usulan/{id}/verifikasi', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'verifikasiUsulan'])->name('usulan.verifikasi')->middleware('permission:menu_kesiswaan_peserta_didik,update');
            Route::post('/berkas/{id}', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'updateBerkas'])->name('berkas.update')->middleware('permission:menu_kesiswaan_peserta_didik,update');
            Route::get('/{id}', [\App\Http\Controllers\KesiswaanPesertaDidikController::class, 'show'])->name('show')->middleware('permission:menu_kesiswaan_peserta_didik,read');
        });

        // 2. Kluster Administrasi (Buku Klaper, Mutasi, Kelulusan)
        Route::prefix('administrasi')->name('administrasi.')->group(function () {
            Route::get('/', [\App\Http\Controllers\KesiswaanController::class, 'index'])->name('index')->middleware('permission:menu_kesiswaan_administrasi,read');
            Route::post('/klaper/sync', [\App\Http\Controllers\KesiswaanController::class, 'syncKlaper'])->name('klaper.sync')->middleware('permission:menu_kesiswaan_administrasi,create');
            Route::put('/klaper/{id}', [\App\Http\Controllers\KesiswaanController::class, 'updateKlaper'])->name('klaper.update')->middleware('permission:menu_kesiswaan_administrasi,update');
            Route::post('/mutasi', [\App\Http\Controllers\KesiswaanController::class, 'storeMutasi'])->name('mutasi.store')->middleware('permission:menu_kesiswaan_administrasi,create');
            Route::get('/mutasi/{id}/cetak', [\App\Http\Controllers\KesiswaanController::class, 'cetakMutasi'])->name('mutasi.cetak')->middleware('permission:menu_kesiswaan_administrasi,read');
            Route::post('/kelulusan', [\App\Http\Controllers\KesiswaanController::class, 'storeKelulusan'])->name('kelulusan.store')->middleware('permission:menu_kesiswaan_administrasi,create');
            Route::get('/kelulusan/{id}/cetak-skl', [\App\Http\Controllers\KesiswaanController::class, 'cetakSkl'])->name('kelulusan.cetak-skl')->middleware('permission:menu_kesiswaan_administrasi,read');
        });

        // 3. Kluster Kedisiplinan (Tata Tertib, Poin, Riwayat, Pembinaan, Pemanggilan Wali, Tindak Lanjut, Rekap)
        Route::prefix('kedisiplinan')->name('kedisiplinan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\KedisiplinanController::class, 'index'])->name('index')->middleware('permission:menu_kesiswaan_kedisiplinan,read');
            Route::post('/tatib', [\App\Http\Controllers\KedisiplinanController::class, 'storeTatib'])->name('tatib.store')->middleware('permission:menu_kesiswaan_kedisiplinan,create');
            Route::put('/tatib/{id}', [\App\Http\Controllers\KedisiplinanController::class, 'updateTatib'])->name('tatib.update')->middleware('permission:menu_kesiswaan_kedisiplinan,update');
            Route::delete('/tatib/{id}', [\App\Http\Controllers\KedisiplinanController::class, 'destroyTatib'])->name('tatib.destroy')->middleware('permission:menu_kesiswaan_kedisiplinan,delete');
            Route::post('/pelanggaran', [\App\Http\Controllers\KedisiplinanController::class, 'storePelanggaran'])->name('pelanggaran.store')->middleware('permission:menu_kesiswaan_kedisiplinan,create');
            Route::delete('/pelanggaran/{id}', [\App\Http\Controllers\KedisiplinanController::class, 'destroyPelanggaran'])->name('pelanggaran.destroy')->middleware('permission:menu_kesiswaan_kedisiplinan,delete');
            Route::post('/pembinaan', [\App\Http\Controllers\KedisiplinanController::class, 'storePembinaan'])->name('pembinaan.store')->middleware('permission:menu_kesiswaan_kedisiplinan,create');
            Route::post('/panggilan-wali', [\App\Http\Controllers\KedisiplinanController::class, 'storePanggilanWali'])->name('panggilan-wali.store')->middleware('permission:menu_kesiswaan_kedisiplinan,create');
            Route::get('/panggilan-wali/{id}/cetak', [\App\Http\Controllers\KedisiplinanController::class, 'cetakPanggilanWali'])->name('panggilan-wali.cetak')->middleware('permission:menu_kesiswaan_kedisiplinan,read');
            Route::post('/pembinaan/{id}/tindak-lanjut', [\App\Http\Controllers\KedisiplinanController::class, 'updateTindakLanjut'])->name('pembinaan.tindak-lanjut')->middleware('permission:menu_kesiswaan_kedisiplinan,update');
        });

        // 4. Kluster Kegiatan Siswa (OSIS, Organisasi, Ekstrakurikuler, Agenda)
        Route::prefix('kegiatan')->name('kegiatan.')->group(function () {
            Route::get('/', [\App\Http\Controllers\KegiatanSiswaController::class, 'index'])->name('index')->middleware('permission:menu_kesiswaan_kegiatan,read');
            Route::post('/organisasi', [\App\Http\Controllers\KegiatanSiswaController::class, 'storeOrganisasi'])->name('organisasi.store')->middleware('permission:menu_kesiswaan_kegiatan,create');
            Route::delete('/organisasi/{id}', [\App\Http\Controllers\KegiatanSiswaController::class, 'destroyOrganisasi'])->name('organisasi.destroy')->middleware('permission:menu_kesiswaan_kegiatan,delete');
            Route::post('/ekskul', [\App\Http\Controllers\KegiatanSiswaController::class, 'storeEkskul'])->name('ekskul.store')->middleware('permission:menu_kesiswaan_kegiatan,create');
            Route::delete('/ekskul/{id}', [\App\Http\Controllers\KegiatanSiswaController::class, 'destroyEkskul'])->name('ekskul.destroy')->middleware('permission:menu_kesiswaan_kegiatan,delete');
            Route::post('/agenda', [\App\Http\Controllers\KegiatanSiswaController::class, 'storeAgenda'])->name('agenda.store')->middleware('permission:menu_kesiswaan_kegiatan,create');
            Route::delete('/agenda/{id}', [\App\Http\Controllers\KegiatanSiswaController::class, 'destroyAgenda'])->name('agenda.destroy')->middleware('permission:menu_kesiswaan_kegiatan,delete');
        });

        // 5. Kluster Prestasi (Akademik, Nonakademik, Rekap)
        Route::prefix('prestasi')->name('prestasi.')->group(function () {
            Route::get('/', [\App\Http\Controllers\PrestasiSiswaController::class, 'index'])->name('index')->middleware('permission:menu_kesiswaan_prestasi,read');
            Route::post('/', [\App\Http\Controllers\PrestasiSiswaController::class, 'storePrestasi'])->name('store')->middleware('permission:menu_kesiswaan_prestasi,create');
            Route::delete('/{id}', [\App\Http\Controllers\PrestasiSiswaController::class, 'destroyPrestasi'])->name('destroy')->middleware('permission:menu_kesiswaan_prestasi,delete');
            Route::get('/cetak', [\App\Http\Controllers\PrestasiSiswaController::class, 'cetakLaporan'])->name('cetak')->middleware('permission:menu_kesiswaan_prestasi,read');
        });
    });

    // Administrasi Tendik — Kepegawaian GTK (Berkas Digital, KGB Tracker, Cuti & SPT)
    Route::get('/kepegawaian', [\App\Http\Controllers\KepegawaianGtkController::class, 'index'])->name('kepegawaian.index')->middleware('permission:menu_kepegawaian,read');
    Route::post('/kepegawaian/berkas', [\App\Http\Controllers\KepegawaianGtkController::class, 'uploadBerkas'])->name('kepegawaian.berkas.upload')->middleware('permission:menu_kepegawaian,create');
    Route::delete('/kepegawaian/berkas/{id}', [\App\Http\Controllers\KepegawaianGtkController::class, 'deleteBerkas'])->name('kepegawaian.berkas.delete')->middleware('permission:menu_kepegawaian,delete');
    Route::post('/kepegawaian/kgb', [\App\Http\Controllers\KepegawaianGtkController::class, 'storeKgb'])->name('kepegawaian.kgb.store')->middleware('permission:menu_kepegawaian,create');
    Route::post('/kepegawaian/cuti', [\App\Http\Controllers\KepegawaianGtkController::class, 'storeCuti'])->name('kepegawaian.cuti.store')->middleware('permission:menu_kepegawaian,create');
    Route::get('/kepegawaian/cuti/{id}/cetak', [\App\Http\Controllers\KepegawaianGtkController::class, 'cetakCuti'])->name('kepegawaian.cuti.cetak')->middleware('permission:menu_kepegawaian,read');
    Route::post('/kepegawaian/spt', [\App\Http\Controllers\KepegawaianGtkController::class, 'storeSpt'])->name('kepegawaian.spt.store')->middleware('permission:menu_kepegawaian,create');
    Route::delete('/kepegawaian/spt/{id}', [\App\Http\Controllers\KepegawaianGtkController::class, 'deleteSpt'])->name('kepegawaian.spt.delete')->middleware('permission:menu_kepegawaian,delete');
    Route::get('/kepegawaian/spt/{id}/cetak', [\App\Http\Controllers\KepegawaianGtkController::class, 'cetakSpt'])->name('kepegawaian.spt.cetak')->middleware('permission:menu_kepegawaian,read');

    // Administrasi Tendik — Pencatatan Aktivitas & Pekerjaan Harian
    Route::get('/tendik/aktivitas', [\App\Http\Controllers\TendikAktivitasController::class, 'index'])->name('tendik.aktivitas.index')->middleware('permission:menu_aktivitas_tendik,read');
    Route::post('/tendik/aktivitas', [\App\Http\Controllers\TendikAktivitasController::class, 'store'])->name('tendik.aktivitas.store')->middleware('permission:menu_aktivitas_tendik,create');
    Route::put('/tendik/aktivitas/{id}', [\App\Http\Controllers\TendikAktivitasController::class, 'update'])->name('tendik.aktivitas.update')->middleware('permission:menu_aktivitas_tendik,update');
    Route::delete('/tendik/aktivitas/{id}', [\App\Http\Controllers\TendikAktivitasController::class, 'destroy'])->name('tendik.aktivitas.destroy')->middleware('permission:menu_aktivitas_tendik,delete');

    // Administrasi Tendik — Rekap & Cetak Laporan Kinerja Berbasis Aktivitas Harian (Bulan, Triwulan, Semester, Tahun Ajaran)
    Route::get('/tendik/laporan', [\App\Http\Controllers\TendikLaporanController::class, 'index'])->name('tendik.laporan.index')->middleware('permission:menu_laporan_tendik,read');
    Route::get('/tendik/laporan/cetak', [\App\Http\Controllers\TendikLaporanController::class, 'cetak'])->name('tendik.laporan.cetak')->middleware('permission:menu_laporan_tendik,read');
    Route::get('/tendik/presensi', fn() => redirect()->route('dashboard.tendik.laporan.index'))->name('tendik.presensi.index');
    Route::get('/tendik/presensi/cetak', fn(\Illuminate\Http\Request $r) => redirect()->route('dashboard.tendik.laporan.cetak', $r->all()))->name('tendik.presensi.cetak');

    // Portal Peserta Didik — Modul Surat Izin & Sakit Mandiri
    Route::get('/peserta-didik/izin', [\App\Http\Controllers\PesertaDidikIzinController::class, 'index'])->name('peserta-didik.izin.index')->middleware('permission:menu_surat_izin_pd,read');
    Route::post('/peserta-didik/izin', [\App\Http\Controllers\PesertaDidikIzinController::class, 'store'])->name('peserta-didik.izin.store')->middleware('permission:menu_surat_izin_pd,create');
    Route::delete('/peserta-didik/izin/{id}', [\App\Http\Controllers\PesertaDidikIzinController::class, 'destroy'])->name('peserta-didik.izin.destroy')->middleware('permission:menu_surat_izin_pd,delete');

    // Portal Peserta Didik — Modul Riwayat Presensi & Cetak Laporan (Bulan, Semester, Tahun)
    Route::get('/peserta-didik/presensi', [\App\Http\Controllers\PesertaDidikPresensiController::class, 'index'])->name('peserta-didik.presensi.index')->middleware('permission:menu_riwayat_rfid,read');
    Route::get('/peserta-didik/presensi/cetak', [\App\Http\Controllers\PesertaDidikPresensiController::class, 'cetak'])->name('peserta-didik.presensi.cetak')->middleware('permission:menu_riwayat_rfid,read');
    Route::get('/presensi/saya', [\App\Http\Controllers\PesertaDidikPresensiController::class, 'index'])->name('presensi.riwayat-saya')->middleware('permission:menu_riwayat_rfid,read');
    Route::post('/peserta-didik/saya/izin', [\App\Http\Controllers\PesertaDidikIzinController::class, 'store'])->name('peserta-didik.presensi.izin');

    // Master Data — Kompetensi Keahlian (Sumber: Rombongan Belajar)
    Route::get('/master-data/kompetensi-keahlian', [KompetensiKeahlianController::class, 'index'])->name('kompetensi-keahlian.index')->middleware('permission:menu_kompetensi_keahlian,read');
    Route::get('/master-data/kompetensi-keahlian/{kode}/rombel', [KompetensiKeahlianController::class, 'showRombel'])->name('kompetensi-keahlian.rombel')->middleware('permission:menu_kompetensi_keahlian,read');
    Route::post('/master-data/kompetensi-keahlian/{kode}/logo', [KompetensiKeahlianController::class, 'uploadLogo'])->name('kompetensi-keahlian.upload-logo')->middleware('permission:menu_kompetensi_keahlian,update');
    Route::delete('/master-data/kompetensi-keahlian/{kode}/logo', [KompetensiKeahlianController::class, 'deleteLogo'])->name('kompetensi-keahlian.delete-logo')->middleware('permission:menu_kompetensi_keahlian,delete');

    // Master Data — Rombel (Sumber: Rombongan Belajar & Peserta Didik)
    Route::get('/master-data/rombel', [\App\Http\Controllers\RombelController::class, 'index'])->name('rombel.index')->middleware('permission:menu_rombel,read');
    Route::get('/master-data/rombel/reguler', [\App\Http\Controllers\RombelController::class, 'reguler'])->name('rombel.reguler')->middleware('permission:menu_rombel,read');
    Route::get('/master-data/rombel/matpel', [\App\Http\Controllers\RombelController::class, 'matpel'])->name('rombel.matpel')->middleware('permission:menu_rombel,read');
    Route::get('/master-data/rombel/{id}/peserta-didik', [\App\Http\Controllers\RombelController::class, 'showPesertaDidik'])->name('rombel.peserta-didik')->middleware('permission:menu_rombel,read');

    // Master Data — Pembelajaran (Sumber: Pembelajaran, GTK, & Rombongan Belajar)
    Route::get('/master-data/pembelajaran', [\App\Http\Controllers\PembelajaranController::class, 'index'])->name('pembelajaran.index')->middleware('permission:menu_pembelajaran,read');
    Route::get('/master-data/pembelajaran/{id}', [\App\Http\Controllers\PembelajaranController::class, 'show'])->name('pembelajaran.show')->middleware('permission:menu_pembelajaran,read');

    // Master Data — Jadwal KBM (Anti-Bentrok & Integrasi Presensi/Jurnal Guru)
    Route::get('/master-data/jadwal-kbm', [\App\Http\Controllers\JadwalKbmController::class, 'index'])->name('jadwal-kbm.index')->middleware('permission:menu_jadwal_kbm,read');
    Route::get('/master-data/jadwal-kbm/pembelajaran-by-rombel', [\App\Http\Controllers\JadwalKbmController::class, 'getPembelajaranByRombel'])->name('jadwal-kbm.pembelajaran-by-rombel')->middleware('permission:menu_jadwal_kbm,read');
    Route::post('/master-data/jadwal-kbm/check-conflict', [\App\Http\Controllers\JadwalKbmController::class, 'checkConflictApi'])->name('jadwal-kbm.check-conflict')->middleware('permission:menu_jadwal_kbm,read');
    Route::post('/master-data/jadwal-kbm/pengaturan', [\App\Http\Controllers\JadwalKbmController::class, 'simpanPengaturan'])->name('jadwal-kbm.pengaturan')->middleware('permission:menu_jadwal_kbm,update');
    Route::post('/master-data/jadwal-kbm/auto-generate', [\App\Http\Controllers\JadwalKbmController::class, 'autoGenerate'])->name('jadwal-kbm.auto-generate')->middleware('permission:menu_jadwal_kbm,create');
    Route::get('/master-data/jadwal-kbm/guru-preferensi', [\App\Http\Controllers\JadwalKbmController::class, 'getGuruPreferensi'])->name('jadwal-kbm.guru-preferensi')->middleware('permission:menu_jadwal_kbm,read');
    Route::post('/master-data/jadwal-kbm/guru-preferensi', [\App\Http\Controllers\JadwalKbmController::class, 'simpanGuruPreferensi'])->name('jadwal-kbm.simpan-guru-preferensi')->middleware('permission:menu_jadwal_kbm,update');
    Route::get('/master-data/jadwal-kbm/cetak-induk', [\App\Http\Controllers\JadwalKbmController::class, 'cetakInduk'])->name('jadwal-kbm.cetak-induk')->middleware('permission:menu_jadwal_kbm,read');
    Route::get('/master-data/jadwal-kbm/cetak-guru', [\App\Http\Controllers\JadwalKbmController::class, 'cetakGuru'])->name('jadwal-kbm.cetak-guru')->middleware('permission:menu_jadwal_kbm,read');
    Route::get('/master-data/jadwal-kbm/cetak-rombel', [\App\Http\Controllers\JadwalKbmController::class, 'cetakRombel'])->name('jadwal-kbm.cetak-rombel')->middleware('permission:menu_jadwal_kbm,read');
    Route::post('/master-data/jadwal-kbm', [\App\Http\Controllers\JadwalKbmController::class, 'store'])->name('jadwal-kbm.store')->middleware('permission:menu_jadwal_kbm,create');
    Route::put('/master-data/jadwal-kbm/{id}', [\App\Http\Controllers\JadwalKbmController::class, 'update'])->name('jadwal-kbm.update')->middleware('permission:menu_jadwal_kbm,update');
    Route::delete('/master-data/jadwal-kbm/{id}', [\App\Http\Controllers\JadwalKbmController::class, 'destroy'])->name('jadwal-kbm.destroy')->middleware('permission:menu_jadwal_kbm,delete');

    // Master Data — Kalender Pendidikan
    Route::get('/master-data/kalender-pendidikan', [\App\Http\Controllers\KalenderPendidikanController::class, 'index'])->name('kalender-pendidikan.index')->middleware('permission:menu_kalender_pendidikan,read');
    Route::get('/master-data/kalender-pendidikan/events', [\App\Http\Controllers\KalenderPendidikanController::class, 'getEvents'])->name('kalender-pendidikan.events')->middleware('permission:menu_kalender_pendidikan,read');
    Route::post('/master-data/kalender-pendidikan', [\App\Http\Controllers\KalenderPendidikanController::class, 'store'])->name('kalender-pendidikan.store')->middleware('permission:menu_kalender_pendidikan,create');
    Route::get('/master-data/kalender-pendidikan/{id}', [\App\Http\Controllers\KalenderPendidikanController::class, 'show'])->name('kalender-pendidikan.show')->middleware('permission:menu_kalender_pendidikan,read');
    Route::put('/master-data/kalender-pendidikan/{id}', [\App\Http\Controllers\KalenderPendidikanController::class, 'update'])->name('kalender-pendidikan.update')->middleware('permission:menu_kalender_pendidikan,update');
    Route::delete('/master-data/kalender-pendidikan/{id}', [\App\Http\Controllers\KalenderPendidikanController::class, 'destroy'])->name('kalender-pendidikan.destroy')->middleware('permission:menu_kalender_pendidikan,delete');

    // Manajemen Data — Peserta Didik Aktif, Guru Aktif, & Tendik Aktif (Sumber: Peserta Didik & GTK Dapodik)
    Route::get('/manajemen-data/peserta-didik-aktif', [\App\Http\Controllers\PesertaDidikAktifController::class, 'index'])->name('peserta-didik-aktif.index')->middleware('permission:menu_peserta_didik_aktif,read');
    Route::get('/manajemen-data/peserta-didik-aktif/rombel-members', [\App\Http\Controllers\PesertaDidikAktifController::class, 'getRombelMembers'])->name('peserta-didik-aktif.rombel-members')->middleware('permission:menu_peserta_didik_aktif,read');
    Route::post('/manajemen-data/peserta-didik-aktif/upload-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'uploadFoto'])->name('peserta-didik-aktif.upload-foto')->middleware('permission:menu_peserta_didik_aktif,update');
    Route::post('/manajemen-data/peserta-didik-aktif/bulk-upload-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'bulkUploadFoto'])->name('peserta-didik-aktif.bulk-upload-foto')->middleware('permission:menu_peserta_didik_aktif,update');
    Route::post('/manajemen-data/peserta-didik-aktif/reset-password', [\App\Http\Controllers\PesertaDidikAktifController::class, 'resetPassword'])->name('peserta-didik-aktif.reset-password')->middleware('permission:menu_peserta_didik_aktif,update');
    Route::post('/manajemen-data/peserta-didik-aktif/toggle-koordinator', [\App\Http\Controllers\PesertaDidikAktifController::class, 'toggleKoordinator'])->name('peserta-didik-aktif.toggle-koordinator')->middleware('permission:menu_peserta_didik_aktif,update');
    Route::delete('/manajemen-data/peserta-didik-aktif/{id}/delete-foto', [\App\Http\Controllers\PesertaDidikAktifController::class, 'deleteFoto'])->name('peserta-didik-aktif.delete-foto')->middleware('permission:menu_peserta_didik_aktif,delete');
    Route::get('/manajemen-data/peserta-didik-aktif/{id}', [\App\Http\Controllers\PesertaDidikAktifController::class, 'show'])->name('peserta-didik-aktif.show')->middleware('permission:menu_peserta_didik_aktif,read');
    Route::get('/manajemen-data/peserta-didik-tidak-aktif', [\App\Http\Controllers\PesertaDidikTidakAktifController::class, 'index'])->name('peserta-didik-tidak-aktif.index')->middleware('permission:menu_peserta_didik_tidak_aktif,read');
    Route::post('/manajemen-data/peserta-didik-tidak-aktif/archive-grade12', [\App\Http\Controllers\PesertaDidikTidakAktifController::class, 'archiveGrade12'])->name('peserta-didik-tidak-aktif.archive-grade12')->middleware('permission:menu_peserta_didik_tidak_aktif,create');
    Route::get('/manajemen-data/peserta-didik-tidak-aktif/export', [\App\Http\Controllers\PesertaDidikTidakAktifController::class, 'exportExcel'])->name('peserta-didik-tidak-aktif.export')->middleware('permission:menu_peserta_didik_tidak_aktif,read');
    Route::get('/manajemen-data/peserta-didik-tidak-aktif/{id}', [\App\Http\Controllers\PesertaDidikTidakAktifController::class, 'show'])->name('peserta-didik-tidak-aktif.show')->middleware('permission:menu_peserta_didik_tidak_aktif,read');
    Route::get('/manajemen-data/guru-aktif', [\App\Http\Controllers\GuruAktifController::class, 'index'])->name('guru-aktif.index')->middleware('permission:menu_guru_aktif,read');
    Route::get('/manajemen-data/guru-aktif/{id}', [\App\Http\Controllers\GuruAktifController::class, 'show'])->name('guru-aktif.show')->middleware('permission:menu_guru_aktif,read');
    Route::get('/manajemen-data/tendik-aktif', [\App\Http\Controllers\TendikAktifController::class, 'index'])->name('tendik-aktif.index')->middleware('permission:menu_tendik_aktif,read');
    Route::get('/manajemen-data/tendik-aktif/{id}', [\App\Http\Controllers\TendikAktifController::class, 'show'])->name('tendik-aktif.show')->middleware('permission:menu_tendik_aktif,read');

    // Kartu Pelajar Digital — Layanan Cetak
    Route::get('/kartu-pelajar/cetak/{nisn}', [\App\Http\Controllers\KartuPelajarController::class, 'printSingle'])->name('kartu-pelajar.cetak-single');
    Route::get('/kartu-pelajar/cetak-rombel/{rombelId}', [\App\Http\Controllers\KartuPelajarController::class, 'printRombel'])->name('kartu-pelajar.cetak-rombel');

    // Arsip & Maintenance (Hanya Admin)
    Route::get('/maintenance', [\App\Http\Controllers\MaintenanceController::class, 'index'])->name('maintenance.index')->middleware('permission:menu_maintenance,read');
    Route::get('/maintenance/download', [\App\Http\Controllers\MaintenanceController::class, 'downloadArchive'])->name('maintenance.download')->middleware('permission:menu_maintenance,read');
    Route::post('/maintenance/clean', [\App\Http\Controllers\MaintenanceController::class, 'cleanOldData'])->name('maintenance.clean')->middleware('permission:menu_maintenance,delete');

    // Modul Wali Kelas (Peserta Didik Aktif & Tidak Aktif Terisolasi Per Kelas Binaan)
    Route::prefix('wali-kelas')->name('wali-kelas.')->group(function () {
        Route::get('/peserta-didik-aktif', [\App\Http\Controllers\WaliKelasController::class, 'pesertaDidikAktif'])->name('peserta-didik-aktif.index')->middleware('permission:menu_wali_kelas_aktif,read');
        Route::get('/peserta-didik-tidak-aktif', [\App\Http\Controllers\WaliKelasController::class, 'pesertaDidikTidakAktif'])->name('peserta-didik-tidak-aktif.index')->middleware('permission:menu_wali_kelas_tidak_aktif,read');
        Route::get('/peserta-didik/{id}', [\App\Http\Controllers\WaliKelasController::class, 'showPesertaDidik'])->name('peserta-didik.show')->middleware('permission:menu_wali_kelas_aktif,read');
        Route::get('/peserta-didik-tidak-aktif/{id}', [\App\Http\Controllers\WaliKelasController::class, 'showPesertaDidikTidakAktif'])->name('peserta-didik-tidak-aktif.show')->middleware('permission:menu_wali_kelas_tidak_aktif,read');

        // Presensi Kelas Binaan (Kontrol Kendala & Rekap PDF)
        Route::get('/presensi', [\App\Http\Controllers\WaliKelasController::class, 'presensi'])->name('presensi.index')->middleware('permission:menu_wali_kelas_presensi,read');
        Route::post('/presensi/manual', [\App\Http\Controllers\WaliKelasController::class, 'simpanPresensiManual'])->name('presensi.manual')->middleware('permission:menu_wali_kelas_presensi,update');
        Route::get('/presensi/pdf/{tipe}', [\App\Http\Controllers\WaliKelasController::class, 'downloadPdf'])->name('presensi.pdf')->middleware('permission:menu_wali_kelas_presensi,read');
        Route::post('/presensi/izin/{id}/verifikasi', [\App\Http\Controllers\WaliKelasController::class, 'verifikasiIzin'])->name('presensi.izin.verifikasi')->middleware('permission:menu_wali_kelas_presensi,update');
    });

    // Realtime Server-Sent Events (SSE) & Polling Fallback
    Route::get('/realtime/stream', [\App\Http\Controllers\RealtimeController::class, 'stream'])->name('realtime.stream');
    Route::get('/realtime/poll', [\App\Http\Controllers\RealtimeController::class, 'poll'])->name('realtime.poll');

    // =========================================================================
    // MODUL ADMINISTRASI BARU: SARPRAS, LABORAN, KEAMANAN, PENJAGA, PIKET
    // =========================================================================

    // 1. Sarpras & Aset
    Route::prefix('sarpras')->name('sarpras.')->group(function () {
        // Ruang & Gedung
        Route::get('/ruang', [\App\Http\Controllers\SarprasRuangController::class, 'index'])->name('ruang.index')->middleware('permission:menu_sarpras,read');
        Route::post('/ruang', [\App\Http\Controllers\SarprasRuangController::class, 'store'])->name('ruang.store')->middleware('permission:menu_sarpras,create');
        Route::put('/ruang/{id}', [\App\Http\Controllers\SarprasRuangController::class, 'update'])->name('ruang.update')->middleware('permission:menu_sarpras,update');
        Route::delete('/ruang/{id}', [\App\Http\Controllers\SarprasRuangController::class, 'destroy'])->name('ruang.destroy')->middleware('permission:menu_sarpras,delete');

        // Inventaris & Aset
        Route::get('/aset', [\App\Http\Controllers\SarprasAsetController::class, 'index'])->name('aset.index')->middleware('permission:menu_inventaris,read');
        Route::post('/aset', [\App\Http\Controllers\SarprasAsetController::class, 'store'])->name('aset.store')->middleware('permission:menu_inventaris,create');
        Route::put('/aset/{id}', [\App\Http\Controllers\SarprasAsetController::class, 'update'])->name('aset.update')->middleware('permission:menu_inventaris,update');
        Route::delete('/aset/{id}', [\App\Http\Controllers\SarprasAsetController::class, 'destroy'])->name('aset.destroy')->middleware('permission:menu_inventaris,delete');

        // Peminjaman Sarpras
        Route::get('/peminjaman', [\App\Http\Controllers\SarprasPeminjamanController::class, 'index'])->name('peminjaman.index')->middleware('permission:menu_sarpras,read');
        Route::post('/peminjaman', [\App\Http\Controllers\SarprasPeminjamanController::class, 'store'])->name('peminjaman.store')->middleware('permission:menu_sarpras,create');
        Route::put('/peminjaman/{id}', [\App\Http\Controllers\SarprasPeminjamanController::class, 'update'])->name('peminjaman.update')->middleware('permission:menu_sarpras,update');
        Route::post('/peminjaman/{id}/kembali', [\App\Http\Controllers\SarprasPeminjamanController::class, 'kembalikan'])->name('peminjaman.kembali')->middleware('permission:menu_sarpras,update');
        Route::delete('/peminjaman/{id}', [\App\Http\Controllers\SarprasPeminjamanController::class, 'destroy'])->name('peminjaman.destroy')->middleware('permission:menu_sarpras,delete');
    });

    // 2. Laboratorium & Laboran
    Route::prefix('laboran')->name('laboran.')->group(function () {
        // Alat & Bahan Lab
        Route::get('/inventaris', [\App\Http\Controllers\LaboranInventarisController::class, 'index'])->name('inventaris.index')->middleware('permission:menu_laboran,read');
        Route::post('/inventaris', [\App\Http\Controllers\LaboranInventarisController::class, 'store'])->name('inventaris.store')->middleware('permission:menu_laboran,create');
        Route::put('/inventaris/{id}', [\App\Http\Controllers\LaboranInventarisController::class, 'update'])->name('inventaris.update')->middleware('permission:menu_laboran,update');
        Route::delete('/inventaris/{id}', [\App\Http\Controllers\LaboranInventarisController::class, 'destroy'])->name('inventaris.destroy')->middleware('permission:menu_laboran,delete');

        // Jadwal & Penggunaan Lab
        Route::get('/jadwal', [\App\Http\Controllers\LaboranJadwalController::class, 'index'])->name('jadwal.index')->middleware('permission:menu_laboran,read');
        Route::post('/jadwal', [\App\Http\Controllers\LaboranJadwalController::class, 'store'])->name('jadwal.store')->middleware('permission:menu_laboran,create');
        Route::put('/jadwal/{id}', [\App\Http\Controllers\LaboranJadwalController::class, 'update'])->name('jadwal.update')->middleware('permission:menu_laboran,update');
        Route::delete('/jadwal/{id}', [\App\Http\Controllers\LaboranJadwalController::class, 'destroy'])->name('jadwal.destroy')->middleware('permission:menu_laboran,delete');
    });

    // 3. Keamanan / Satpam Pos Gerbang
    Route::prefix('keamanan')->name('keamanan.')->group(function () {
        // Verifikasi e-Izin Gerbang Siswa
        Route::get('/izin', [\App\Http\Controllers\KeamananIzinController::class, 'index'])->name('izin.index')->middleware('permission:menu_keamanan,read');
        Route::get('/izin/verifikasi', [\App\Http\Controllers\KeamananIzinController::class, 'verifikasiTiket'])->name('izin.verifikasi');
        Route::post('/izin/checkout/{id}', [\App\Http\Controllers\KeamananIzinController::class, 'checkout'])->name('izin.checkout')->middleware('permission:menu_keamanan,update');
        Route::post('/izin/checkin/{id}', [\App\Http\Controllers\KeamananIzinController::class, 'checkin'])->name('izin.checkin')->middleware('permission:menu_keamanan,update');

        // Monitoring Presensi Siswa Gerbang
        Route::get('/presensi', [\App\Http\Controllers\KeamananPresensiController::class, 'index'])->name('presensi.index')->middleware('permission:menu_keamanan,read');

        // Buku Tamu Pos Keamanan
        Route::get('/buku-tamu', [\App\Http\Controllers\KeamananBukuTamuController::class, 'index'])->name('buku-tamu.index')->middleware('permission:menu_buku_tamu,read');
        Route::post('/buku-tamu', [\App\Http\Controllers\KeamananBukuTamuController::class, 'store'])->name('buku-tamu.store')->middleware('permission:menu_buku_tamu,create');
        Route::put('/buku-tamu/{id}', [\App\Http\Controllers\KeamananBukuTamuController::class, 'update'])->name('buku-tamu.update')->middleware('permission:menu_buku_tamu,update');
        Route::post('/buku-tamu/{id}/checkout', [\App\Http\Controllers\KeamananBukuTamuController::class, 'checkout'])->name('buku-tamu.checkout')->middleware('permission:menu_buku_tamu,update');
        Route::delete('/buku-tamu/{id}', [\App\Http\Controllers\KeamananBukuTamuController::class, 'destroy'])->name('buku-tamu.destroy')->middleware('permission:menu_buku_tamu,delete');

        // Patroli & Insiden Keamanan
        Route::get('/patroli', [\App\Http\Controllers\KeamananPatroliController::class, 'index'])->name('patroli.index')->middleware('permission:menu_keamanan,read');
        Route::post('/patroli', [\App\Http\Controllers\KeamananPatroliController::class, 'storePatroli'])->name('patroli.store')->middleware('permission:menu_keamanan,create');
        Route::put('/patroli/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'updatePatroli'])->name('patroli.update')->middleware('permission:menu_keamanan,update');
        Route::delete('/patroli/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'destroyPatroli'])->name('patroli.destroy')->middleware('permission:menu_keamanan,delete');
        Route::post('/insiden', [\App\Http\Controllers\KeamananPatroliController::class, 'storeInsiden'])->name('insiden.store')->middleware('permission:menu_keamanan,create');
        Route::put('/insiden/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'updateInsiden'])->name('insiden.update')->middleware('permission:menu_keamanan,update');
        Route::delete('/insiden/{id}', [\App\Http\Controllers\KeamananPatroliController::class, 'destroyInsiden'])->name('insiden.destroy')->middleware('permission:menu_keamanan,delete');
    });

    // 4. Fasilitas & Penjaga Sekolah
    Route::prefix('penjaga')->name('penjaga.')->group(function () {
        // Checklist Kebersihan & Sanitasi
        Route::get('/kebersihan', [\App\Http\Controllers\PenjagaKebersihanController::class, 'index'])->name('kebersihan.index')->middleware('permission:menu_penjaga,read');
        Route::post('/kebersihan', [\App\Http\Controllers\PenjagaKebersihanController::class, 'store'])->name('kebersihan.store')->middleware('permission:menu_penjaga,create');
        Route::put('/kebersihan/{id}', [\App\Http\Controllers\PenjagaKebersihanController::class, 'update'])->name('kebersihan.update')->middleware('permission:menu_penjaga,update');
        Route::delete('/kebersihan/{id}', [\App\Http\Controllers\PenjagaKebersihanController::class, 'destroy'])->name('kebersihan.destroy')->middleware('permission:menu_penjaga,delete');

        // Buku Jaga Malam & Ronda
        Route::get('/ronda', [\App\Http\Controllers\PenjagaRondaController::class, 'index'])->name('ronda.index')->middleware('permission:menu_penjaga,read');
        Route::post('/ronda', [\App\Http\Controllers\PenjagaRondaController::class, 'store'])->name('ronda.store')->middleware('permission:menu_penjaga,create');
        Route::put('/ronda/{id}', [\App\Http\Controllers\PenjagaRondaController::class, 'update'])->name('ronda.update')->middleware('permission:menu_penjaga,update');
        Route::delete('/ronda/{id}', [\App\Http\Controllers\PenjagaRondaController::class, 'destroy'])->name('ronda.destroy')->middleware('permission:menu_penjaga,delete');
    });

    // 5. Piket Sekolah & e-Izin Keluar
    Route::prefix('piket')->name('piket.')->group(function () {
        // e-Izin Keluar-Masuk Siswa
        Route::get('/izin', [\App\Http\Controllers\PiketIzinController::class, 'index'])->name('izin.index')->middleware('permission:menu_piket,read');
        Route::get('/izin/search-siswa', [\App\Http\Controllers\PiketIzinController::class, 'searchSiswa'])->name('izin.search-siswa');
        Route::post('/izin', [\App\Http\Controllers\PiketIzinController::class, 'store'])->name('izin.store')->middleware('permission:menu_piket,create');
        Route::put('/izin/{id}', [\App\Http\Controllers\PiketIzinController::class, 'update'])->name('izin.update')->middleware('permission:menu_piket,update');
        Route::delete('/izin/{id}', [\App\Http\Controllers\PiketIzinController::class, 'destroy'])->name('izin.destroy')->middleware('permission:menu_piket,delete');
        Route::get('/izin/{id}/cetak', [\App\Http\Controllers\PiketIzinController::class, 'cetakSlip'])->name('izin.cetak')->middleware('permission:menu_piket,read');

        // Jurnal Guru Piket
        Route::get('/jurnal', [\App\Http\Controllers\PiketJurnalController::class, 'index'])->name('jurnal.index')->middleware('permission:menu_piket,read');
        Route::post('/jurnal', [\App\Http\Controllers\PiketJurnalController::class, 'store'])->name('jurnal.store')->middleware('permission:menu_piket,create');
        Route::put('/jurnal/{id}', [\App\Http\Controllers\PiketJurnalController::class, 'update'])->name('jurnal.update')->middleware('permission:menu_piket,update');
        Route::delete('/jurnal/{id}', [\App\Http\Controllers\PiketJurnalController::class, 'destroy'])->name('jurnal.destroy')->middleware('permission:menu_piket,delete');
    });

    // Pengajuan e-Izin Keluar Siswa Mandiri
    Route::post('/peserta-didik/izin/keluar', [\App\Http\Controllers\PesertaDidikIzinController::class, 'storeIzinKeluar'])->name('peserta-didik.izin.keluar.store');

    // 6. Perpustakaan (Koleksi, Sirkulasi, Kunjungan)
    Route::prefix('perpustakaan')->name('perpustakaan.')->group(function () {
        // Koleksi & Katalog Buku
        Route::get('/koleksi', [\App\Http\Controllers\PerpusKoleksiController::class, 'index'])->name('koleksi.index')->middleware('permission:menu_perpustakaan,read');
        Route::post('/koleksi', [\App\Http\Controllers\PerpusKoleksiController::class, 'store'])->name('koleksi.store')->middleware('permission:menu_perpustakaan,create');
        Route::put('/koleksi/{id}', [\App\Http\Controllers\PerpusKoleksiController::class, 'update'])->name('koleksi.update')->middleware('permission:menu_perpustakaan,update');
        Route::delete('/koleksi/{id}', [\App\Http\Controllers\PerpusKoleksiController::class, 'destroy'])->name('koleksi.destroy')->middleware('permission:menu_perpustakaan,delete');

        // Sirkulasi Peminjaman & Pengembalian
        Route::get('/sirkulasi', [\App\Http\Controllers\PerpusSirkulasiController::class, 'index'])->name('sirkulasi')->middleware('permission:menu_perpustakaan,read');
        Route::post('/sirkulasi', [\App\Http\Controllers\PerpusSirkulasiController::class, 'store'])->name('sirkulasi.store')->middleware('permission:menu_perpustakaan,create');
        Route::post('/sirkulasi/{id}/kembalikan', [\App\Http\Controllers\PerpusSirkulasiController::class, 'kembalikan'])->name('sirkulasi.kembalikan')->middleware('permission:menu_perpustakaan,update');
        Route::delete('/sirkulasi/{id}', [\App\Http\Controllers\PerpusSirkulasiController::class, 'destroy'])->name('sirkulasi.destroy')->middleware('permission:menu_perpustakaan,delete');

        // Buku Kunjungan
        Route::get('/kunjungan', [\App\Http\Controllers\PerpusKunjunganController::class, 'index'])->name('kunjungan')->middleware('permission:menu_perpustakaan,read');
        Route::post('/kunjungan', [\App\Http\Controllers\PerpusKunjunganController::class, 'store'])->name('kunjungan.store')->middleware('permission:menu_perpustakaan,create');
        Route::delete('/kunjungan/{id}', [\App\Http\Controllers\PerpusKunjunganController::class, 'destroy'])->name('kunjungan.destroy')->middleware('permission:menu_perpustakaan,delete');
    });

    // 7. Teknisi & Maintenance (Work Order & Pemeliharaan Preventif)
    Route::prefix('teknisi')->name('teknisi.')->group(function () {
        // Work Order & Tiket Perbaikan (Bangunan, Listrik, Air, AC, IT, Kebersihan)
        Route::get('/work-order', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'index'])->name('work-order')->middleware('permission:menu_teknisi,read');
        Route::post('/work-order', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'store'])->name('work-order.store')->middleware('permission:menu_teknisi,create');
        Route::post('/work-order/{id}/status', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'updateStatus'])->name('work-order.status')->middleware('permission:menu_teknisi,update');
        Route::delete('/work-order/{id}', [\App\Http\Controllers\TeknisiWorkOrderController::class, 'destroy'])->name('work-order.destroy')->middleware('permission:menu_teknisi,delete');

        // Pemeliharaan Preventif (Maintenance Routine)
        Route::get('/pemeliharaan', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'index'])->name('pemeliharaan')->middleware('permission:menu_teknisi,read');
        Route::post('/pemeliharaan', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'store'])->name('pemeliharaan.store')->middleware('permission:menu_teknisi,create');
        Route::post('/pemeliharaan/{id}/status', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'updateStatus'])->name('pemeliharaan.status')->middleware('permission:menu_teknisi,update');
        Route::delete('/pemeliharaan/{id}', [\App\Http\Controllers\TeknisiPemeliharaanController::class, 'destroy'])->name('pemeliharaan.destroy')->middleware('permission:menu_teknisi,delete');
    });
});

// Admin shortcut redirect
Route::get('/admin', function () {
    return redirect()->route('dashboard.admin');
});
