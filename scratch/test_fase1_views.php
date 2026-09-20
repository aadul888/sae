<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Gtk;
use App\Models\Sekolah;
use App\Models\RolePermission;
use App\Models\PersuratanDisposisi;
use App\Models\SuratKeteranganPd;
use App\Models\KesiswaanMutasi;
use App\Models\GtkCutiIzin;

echo "=== 1. TEST CEK USER DAN TUGAS TAMBAHAN ===\n";
$users = [
    'Titin' => 'titin',
    'Perina' => 'perina',
    'Anis' => 'anis',
    'Jalaludin' => 'jalaludin',
    'Euis' => 'euis'
];

foreach ($users as $label => $uname) {
    $u = User::where('username', 'like', "%{$uname}%")->first();
    if ($u) {
        $canSurat = RolePermission::canAccess($u, 'menu_persuratan') ? 'YES' : 'NO';
        $canKesiswaan = RolePermission::canAccess($u, 'menu_kesiswaan') ? 'YES' : 'NO';
        $canKepegawaian = RolePermission::canAccess($u, 'menu_kepegawaian') ? 'YES' : 'NO';
        echo "User {$label} ({$u->username}): Surat={$canSurat}, Kesiswaan={$canKesiswaan}, Kepegawaian={$canKepegawaian}\n";
    } else {
        echo "User {$label} ({$uname}) not found!\n";
    }
}

echo "\n=== 2. TEST RENDER VIEW CONTROLLER ===\n";
try {
    // Test KesiswaanController
    $req = \Illuminate\Http\Request::create('/dashboard/kesiswaan', 'GET');
    session(['user' => User::where('username', 'like', '%anis%')->first()?->toArray()]);
    $ctrlKesiswaan = new \App\Http\Controllers\KesiswaanController();
    $resKesiswaan = $ctrlKesiswaan->index($req);
    echo "KesiswaanController@index: " . ($resKesiswaan ? "OK" : "FAILED") . "\n";
} catch (\Throwable $e) {
    echo "KesiswaanController Error: " . $e->getMessage() . "\n";
}

try {
    // Test KepegawaianGtkController
    $req = \Illuminate\Http\Request::create('/dashboard/kepegawaian', 'GET');
    session(['user' => User::where('username', 'like', '%jalaludin%')->first()?->toArray()]);
    $ctrlKepegawaian = new \App\Http\Controllers\KepegawaianGtkController();
    $resKepegawaian = $ctrlKepegawaian->index($req);
    echo "KepegawaianGtkController@index: " . ($resKepegawaian ? "OK" : "FAILED") . "\n";
} catch (\Throwable $e) {
    echo "KepegawaianGtkController Error: " . $e->getMessage() . "\n";
}

try {
    // Test PersuratanController
    $req = \Illuminate\Http\Request::create('/dashboard/persuratan', 'GET');
    session(['user' => User::where('username', 'like', '%perina%')->first()?->toArray()]);
    $ctrlSurat = new \App\Http\Controllers\PersuratanController();
    $resSurat = $ctrlSurat->index($req);
    echo "PersuratanController@index: " . ($resSurat ? "OK" : "FAILED") . "\n";
} catch (\Throwable $e) {
    echo "PersuratanController Error: " . $e->getMessage() . "\n";
}

echo "\n=== 3. TEST RENDER SEMUA VIEW CETAK RESMI A4 ===\n";
// Dummy data jika belum ada di database untuk test cetak
try {
    $sekolah = Sekolah::first();
    $sekolahMeta = \App\Models\SekolahMeta::first();
    $kepsek = Gtk::first();

    // 1. Cetak Cuti
    $dummyCuti = new GtkCutiIzin([
        'id' => 1,
        'ptk_id' => $kepsek?->ptk_id,
        'tipe' => 'Tugas Dinas',
        'jenis_cuti' => 'Tugas Luar',
        'nomor_surat' => '090/001/SPT/2026',
        'tanggal_mulai' => '2026-09-21',
        'tanggal_selesai' => '2026-09-22',
        'durasi_hari' => 2,
        'keperluan' => 'Menghadiri Rapat Koordinasi Dapodik di Dinas Pendidikan Provinsi',
        'tujuan_lokasi' => 'Aula Sasana Budaya',
        'status' => 'Disetujui',
        'created_at' => now(),
    ]);
    $dummyCuti->setRelation('gtk', $kepsek);

    $htmlCuti = view('dashboard.kepegawaian.cetak-cuti', [
        'cuti' => $dummyCuti,
        'sekolah' => $sekolah,
        'sekolahMeta' => $sekolahMeta,
        'kepsek' => $kepsek
    ])->render();
    echo "View cetak-cuti: OK (" . strlen($htmlCuti) . " bytes)\n";

    // 2. Cetak Mutasi
    $dummyMutasi = new KesiswaanMutasi([
        'id' => 1,
        'peserta_didik_id' => 'dummy-pd',
        'jenis_mutasi' => 'Mutasi Keluar',
        'nomor_surat' => '421.3/042/SMK/2026',
        'tanggal_surat' => '2026-09-20',
        'sekolah_tujuan' => 'SMK Negeri 1 Kota Bandung',
        'alasan' => 'Mengikuti perpindahan tugas orang tua',
        'status' => 'Selesai',
        'created_at' => now(),
    ]);
    $dummyPd = new \App\Models\PesertaDidik([
        'nama' => 'Ahmad Fajar',
        'nisn' => '0051234567',
        'nipd' => '22231001',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Bandung',
        'tanggal_lahir' => '2007-05-15'
    ]);
    $dummyMutasi->setRelation('pesertaDidik', $dummyPd);

    $htmlMutasi = view('dashboard.kesiswaan.cetak-mutasi', [
        'mutasi' => $dummyMutasi,
        'siswa' => $dummyPd,
        'sekolah' => $sekolah,
        'sekolahMeta' => $sekolahMeta,
        'kepsek' => $kepsek
    ])->render();
    echo "View cetak-mutasi: OK (" . strlen($htmlMutasi) . " bytes)\n";

    // 3. Cetak Surat Keterangan Siswa Aktif
    $dummyKet = new SuratKeteranganPd([
        'id' => 1,
        'peserta_didik_id' => 'dummy-pd',
        'nomor_surat' => '421.5/088/SMK/2026',
        'keperluan' => 'Pengajuan Beasiswa Program Indonesia Pintar (PIP)',
        'nomor_induk' => '22231001',
        'nisn' => '0051234567',
        'status_pd' => 'Aktif',
        'verifikasi_token' => 'SAE-TEST-TOKEN-12345',
        'created_at' => now(),
    ]);
    $dummyKet->setRelation('pesertaDidik', $dummyPd);

    $htmlKet = view('dashboard.persuratan.cetak-surat-keterangan', [
        'surat' => $dummyKet,
        'sekolah' => $sekolah,
        'sekolahMeta' => $sekolahMeta,
        'kepsek' => $kepsek
    ])->render();
    echo "View cetak-surat-keterangan: OK (" . strlen($htmlKet) . " bytes)\n";

    // 4. Cetak Lembar Disposisi
    $dummyPersuratan = new \App\Models\Persuratan([
        'id' => 1,
        'jenis' => 'masuk',
        'nomor_surat' => '005/123/DISDIK/2026',
        'asal_surat' => 'Dinas Pendidikan Provinsi Jawa Barat',
        'perihal' => 'Undangan Rapat Koordinasi Evaluasi Penyaluran BOS',
        'tanggal_surat' => '2026-09-18',
        'tanggal_diterima' => '2026-09-19',
        'sifat' => 'Penting',
        'nomor_agenda' => '045/AGD/2026',
    ]);
    $dummyDisposisi = new PersuratanDisposisi([
        'persuratan_id' => 1,
        'instruksi' => 'Harap ditindaklanjuti dan siapkan berkas laporan BOS semester berjalan.',
        'diteruskan_kepada' => 'Kepala TAS, Bendahara BOS',
        'batas_waktu' => '2026-09-25',
        'catatan' => 'Koordinasikan dengan bidang keuangan',
        'disposisi_oleh' => 'Kepala Sekolah'
    ]);
    $htmlDisposisi = view('dashboard.persuratan.cetak-disposisi', [
        'surat' => $dummyPersuratan,
        'disposisi' => $dummyDisposisi,
        'sekolah' => $sekolah,
        'sekolahMeta' => $sekolahMeta,
        'kepsek' => $kepsek
    ])->render();
    echo "View cetak-disposisi: OK (" . strlen($htmlDisposisi) . " bytes)\n";

} catch (\Throwable $e) {
    echo "Cetak View Error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
}

echo "\n=== ALL TESTS FINISHED ===\n";
