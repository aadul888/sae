<?php
// Self-check pemetaan konsentrasi keahlian → program keahlian.
// Jalankan: php tests/program-keahlian.check.php  (dari root proyek)

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/');
$app->instance('request', $request);
$kernel->bootstrap();

use App\Support\ProgramKeahlian;

$fail = 0;
$check = function (string $label, bool $ok) use (&$fail) {
    echo ($ok ? 'OK   ' : 'FAIL ') . $label . PHP_EOL;
    if (!$ok) $fail++;
};

// Konsentrasi XI/XII harus memetakan ke program induk kelas X.
$check('Teknik Kendaraan Ringan → Teknik Otomotif', ProgramKeahlian::resolve('Teknik Kendaraan Ringan') === 'Teknik Otomotif');
$check('Teknik Komputer dan Jaringan → TJKT', ProgramKeahlian::resolve('Teknik Komputer dan Jaringan') === 'Teknik Jaringan Komputer dan Telekomunikasi');
$check('Nama tak dikenal tetap apa adanya', ProgramKeahlian::resolve('Jurusan Antah Berantah') === 'Jurusan Antah Berantah');
$check('Nama kosong → string kosong', ProgramKeahlian::resolve('') === '');

$list = ProgramKeahlian::listFrom(['Teknik Otomotif', 'Teknik Kendaraan Ringan', 'Teknik Otomotif']);
$check('listFrom unik & terpetakan', $list === ['Teknik Otomotif']);

$names = ProgramKeahlian::jurusanNamesOf('Teknik Otomotif');
$check('jurusanNamesOf memuat konsentrasi', in_array('Teknik Kendaraan Ringan', $names, true) && in_array('Teknik Otomotif', $names, true));

// Penggabungan baris rekap: 10 jurusan_id mentah harus jadi 5 program keahlian.
$rows = Illuminate\Support\Facades\DB::table('rombongan_belajar')
    ->whereNotNull('jurusan_id_str')->where('jurusan_id_str', '<>', '')
    ->select('jurusan_id as kode', 'jurusan_id_str as nama')->distinct()->get();

$merged = ProgramKeahlian::mergeRows($rows);
$kodes = array_column($merged, 'kode');
sort($kodes);

$check('10 jurusan_id digabung jadi 5 program', count($merged) === 5);
$check('kode program = id level program (5 digit)', $kodes === ['12012', '16012', '26010', '26020', '47012']);
$check('idsFor memuat id konsentrasi', in_array('12012175', ProgramKeahlian::idsFor('Teknik Otomotif'), true));
$check('idsFor program tak dikenal → kosong', ProgramKeahlian::idsFor('Jurusan Antah Berantah') === []);
$check('programOfId: konsentrasi → program', ProgramKeahlian::programOfId('12012175') === 'Teknik Otomotif');

// Cakupan presensi: id program & id konsentrasi harus dianggap jurusan yang sama.
$pengaturan = new App\Models\PresensiPengaturan();
$pengaturan->jurusan_aktif = ['12012'];
$check('isJurusanAktif: id program aktif', $pengaturan->isJurusanAktif('12012') === true);
$check('isJurusanAktif: konsentrasi satu program ikut aktif', $pengaturan->isJurusanAktif('12012175') === true);
$check('isJurusanAktif: jurusan lain tetap nonaktif', $pengaturan->isJurusanAktif('16012') === false);

exit($fail === 0 ? 0 : 1);
