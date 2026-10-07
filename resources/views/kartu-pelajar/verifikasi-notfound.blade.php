<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Tidak Ditemukan — Verifikasi Kartu Pelajar</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : time() }}">
</head>

<body class="verif-notfound-body">
    <div class="verif-notfound-box">
        <div class="verif-notfound-icon"><i class="fas fa-triangle-exclamation"></i></div>
        <h1>DATA KARTU TIDAK DITEMUKAN</h1>
        <p>
            Kartu pelajar dengan nomor NISN <strong>{{ $nisn }}</strong> tidak terdaftar atau telah
            dinonaktifkan dari sistem resmi {{ $sekolah['nama'] }}.
        </p>
        <a href="{{ route('home') }}" class="verif-notfound-btn"><i class="fas fa-home"></i> Kembali ke Beranda</a>
    </div>
</body>

</html>
