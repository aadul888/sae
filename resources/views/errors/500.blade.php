<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 &mdash; Server Error | SAE</title>
    <meta name="description" content="Terjadi kesalahan server internal pada SAE.">
    <script>
        (function() {
            try {
                var t = localStorage.getItem('sae_theme') || (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
                if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
                else document.documentElement.removeAttribute('data-theme');
            } catch (e) {}
        })();
    </script>
    @php $dashLogoIconVer = @filemtime(public_path('img/logo-icon.png')) ?: '1'; @endphp
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}?v={{ $dashLogoIconVer }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sae.css') }}?v={{ file_exists(public_path('css/sae.css')) ? filemtime(public_path('css/sae.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: var(--bg-main, #0f172a);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px; color: var(--text-color, #f1f5f9);
        }
        .error-container { max-width: 640px; width: 100%; text-align: center; }
        .error-logo {
            margin-bottom: 28px; display: flex; align-items: center;
            justify-content: center; gap: 10px;
        }
        .error-logo img { width: 38px; height: 38px; object-fit: contain; }
        .error-logo span { font-size: 1.05rem; font-weight: 800; color: var(--text-color, #f1f5f9); }
        .error-card {
            background: var(--bg-card, #1e293b);
            border: 1px solid var(--border-color, rgba(255,255,255,0.07));
            border-radius: 20px; padding: 48px 40px;
            position: relative; overflow: hidden;
        }
        .error-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #6366f1);
        }
        .error-icon-wrap {
            width: 90px; height: 90px; margin: 0 auto 24px;
            background: radial-gradient(circle, rgba(239,68,68,0.15) 0%, rgba(239,68,68,0.04) 70%);
            border: 2px solid rgba(239,68,68,0.25); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            animation: pulse-ring 2.5s ease-in-out infinite;
        }
        @keyframes pulse-ring {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.22); }
            50% { box-shadow: 0 0 0 14px rgba(239,68,68,0); }
        }
        .error-icon-wrap i { font-size: 2.4rem; color: #ef4444; }
        .error-code {
            font-size: 4.5rem; font-weight: 900;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; line-height: 1; margin-bottom: 8px; letter-spacing: -3px;
        }
        .error-title { font-size: 1.35rem; font-weight: 800; color: var(--text-color, #f1f5f9); margin-bottom: 12px; }
        .error-desc {
            font-size: 0.92rem; color: var(--text-muted, #94a3b8);
            line-height: 1.7; margin-bottom: 32px;
            max-width: 480px; margin-left: auto; margin-right: auto;
        }
        .error-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .btn-err {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 22px; border-radius: 10px;
            font-weight: 600; font-size: 0.88rem; cursor: pointer;
            border: none; text-decoration: none; transition: all 0.2s ease;
        }
        .btn-back {
            background: rgba(239,68,68,0.1); color: #ef4444;
            border: 1.5px solid rgba(239,68,68,0.2);
        }
        .btn-back:hover { background: rgba(239,68,68,0.18); transform: translateY(-2px); }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff;
            box-shadow: 0 4px 14px rgba(99,102,241,0.35);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.45); }
        .btn-outline {
            background: transparent; color: var(--text-color, #f1f5f9);
            border: 1.5px solid var(--border-color, rgba(255,255,255,0.12));
        }
        .btn-outline:hover { background: rgba(255,255,255,0.05); transform: translateY(-2px); }
        .divider { display: flex; align-items: center; gap: 12px; margin: 28px 0; color: var(--text-muted, #94a3b8); font-size: 0.78rem; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border-color, rgba(255,255,255,0.07)); }
        .debug-box {
            background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.15);
            border-radius: 10px; padding: 14px 18px;
            font-size: 0.78rem; color: var(--text-muted, #94a3b8);
            text-align: left; font-family: monospace; line-height: 1.6; word-break: break-all;
        }
        .error-footer { margin-top: 24px; font-size: 0.8rem; color: var(--text-muted, #94a3b8); }
        .error-footer a { color: #6366f1; text-decoration: none; }
        .error-footer a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .error-card { padding: 36px 20px; }
            .error-code { font-size: 3.2rem; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-logo">
            <img src="{{ asset('img/logo-icon.png') }}" alt="SAE" onerror="this.style.display='none'">
            <span>SAE &mdash; Sistem Aplikasi Edukasi</span>
        </div>

        <div class="error-card">
            <div class="error-icon-wrap">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div class="error-code">500</div>
            <h1 class="error-title">Terjadi Kesalahan Server Internal</h1>
            <p class="error-desc">
                Maaf, server tidak dapat memproses permintaan Anda saat ini karena terjadi kesalahan internal.
                Silakan kembali ke halaman sebelumnya, atau coba beberapa saat lagi.
            </p>

            <div class="error-actions">
                <button type="button" class="btn-err btn-back" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Halaman Sebelumnya
                </button>

                @if (session()->has('user'))
                    @php $errRole = is_array(session('user')) ? (session('user')['role'] ?? 'admin') : (session('user')->role ?? 'admin'); @endphp
                    <a href="{{ route('dashboard.' . $errRole) }}" class="btn-err btn-primary">
                        <i class="fas fa-house"></i> Ke Dashboard Saya
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn-err btn-primary">
                        <i class="fas fa-right-to-bracket"></i> Login Kembali
                    </a>
                @endif

                <button type="button" class="btn-err btn-outline" onclick="window.location.reload()">
                    <i class="fas fa-rotate-right"></i> Muat Ulang
                </button>
            </div>

            @if (config('app.debug') && isset($exception))
                <div class="divider">Detail Error (Mode Debug Aktif)</div>
                <div class="debug-box">
                    <strong style="color:#ef4444;">{{ get_class($exception) }}</strong><br>
                    {{ $exception->getMessage() }}<br>
                    <span style="opacity:0.6;">{{ $exception->getFile() }}:{{ $exception->getLine() }}</span>
                </div>
            @endif
        </div>

        <div class="error-footer">
            Kode referensi: <code style="background:rgba(255,255,255,0.05);padding:2px 6px;border-radius:4px;">SAE-500-{{ date('YmdHis') }}</code>
            &nbsp;|&nbsp;
            @if (session()->has('user'))
                <a href="{{ route('logout') }}">Keluar &amp; Login Ulang</a>
            @else
                <a href="{{ route('login') }}">Kembali ke Login</a>
            @endif
        </div>
    </div>
</body>
</html>
