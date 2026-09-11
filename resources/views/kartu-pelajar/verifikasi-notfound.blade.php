<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Tidak Ditemukan — Verifikasi Kartu Pelajar</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 36px 28px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
        }
        h1 { font-size: 1.25rem; font-weight: 800; margin-bottom: 8px; }
        p { font-size: 0.88rem; color: #64748b; line-height: 1.5; margin-bottom: 20px; }
        .btn {
            display: inline-block;
            background: #0284c7;
            color: #ffffff;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon"><i class="fas fa-triangle-exclamation"></i></div>
        <h1>DATA KARTU TIDAK DITEMUKAN</h1>
        <p>
            Kartu pelajar dengan nomor NISN <strong>{{ $nisn }}</strong> tidak terdaftar atau telah dinonaktifkan dari sistem resmi {{ $sekolah['nama'] }}.
        </p>
        <a href="{{ route('home') }}" class="btn"><i class="fas fa-home"></i> Kembali ke Beranda</a>
    </div>
</body>
</html>
