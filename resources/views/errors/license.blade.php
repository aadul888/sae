<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelanggaran Lisensi — SAE</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            color: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            padding: 24px;
        }

        .license-card {
            max-width: 520px;
            width: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .shield-icon {
            width: 72px;
            height: 72px;
            background: rgba(239, 68, 68, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #ef4444;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ef4444;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #94a3b8;
            font-size: 0.88rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .info-box {
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: left;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
            font-size: 0.84rem;
        }

        .info-row i {
            width: 18px;
            text-align: center;
            color: #6366f1;
            flex-shrink: 0;
        }

        .info-row strong {
            color: #e2e8f0;
        }

        .info-row span {
            color: #cbd5e1;
        }

        .wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #22c55e;
            color: #fff;
            font-weight: 700;
            font-size: 0.92rem;
            padding: 12px 28px;
            border-radius: 10px;
            text-decoration: none;
            transition: background 0.2s, transform 0.1s;
        }

        .wa-btn:hover {
            background: #16a34a;
            transform: translateY(-1px);
        }

        .footer-note {
            margin-top: 20px;
            font-size: 0.72rem;
            color: #64748b;
            line-height: 1.5;
        }

        .error-code {
            display: inline-block;
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-family: monospace;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 8px;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
</head>

<body>
    <div class="license-card">
        <div class="shield-icon">
            <i class="fas fa-shield-halved"></i>
        </div>

        <h1>Pelanggaran Lisensi Terdeteksi</h1>
        <p class="subtitle">
            Sistem telah mendeteksi modifikasi ilegal pada komponen hak cipta aplikasi.
            Aplikasi tidak dapat berjalan sampai integritas lisensi dipulihkan oleh pengembang resmi.
        </p>

        <div class="info-box">
            <div class="info-row">
                <i class="fas fa-user-shield"></i>
                <div><strong>Pengembang:</strong> <span>{{ $developer ?? 'Abdul Azis, SKom.' }}</span></div>
            </div>
            <div class="info-row">
                <i class="fab fa-whatsapp"></i>
                <div><strong>WhatsApp:</strong> <span>{{ $whatsapp ?? '085860605060' }}</span></div>
            </div>
            <div class="info-row">
                <i class="fas fa-map-marker-alt"></i>
                <div><strong>Alamat:</strong> <span>{{ $address ?? 'Pagelaran, Cianjur, Jawa Barat, 43266' }}</span>
                </div>
            </div>
        </div>

        <a href="{{ $waLink ?? 'https://wa.me/6285860605060' }}" target="_blank" rel="noopener" class="wa-btn">
            <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i>
            Hubungi Pengembang via WhatsApp
        </a>

        <p class="footer-note">
            Silakan hubungi pengembang untuk mendapatkan lisensi resmi atau memulihkan sistem.
            <br>
            <span class="error-code">ERR_LICENSE_INTEGRITY_FAILED</span>
        </p>
    </div>
</body>

</html>
