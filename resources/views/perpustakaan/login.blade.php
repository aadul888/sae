@extends('layouts.app')

@section('title', 'Masuk Anggota — Perpustakaan Digital')
@section('meta_description', 'Gerbang akses anggota Perpustakaan Digital sekolah. Masuk dengan memindai kartu anggota QR, NISN, atau NIP.')

@section('content')
    <div class="container">
        <section class="hero" style="grid-template-columns:1fr;max-width:560px;margin:0 auto;">
            <div class="card-nisn">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-id-card text-primary"></i> Masuk Anggota
                        Perpustakaan</span>
                    <a href="{{ route('perpustakaan.index') }}"
                        style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);text-decoration:none;">
                        <i class="fas fa-arrow-left"></i> Katalog
                    </a>
                </div>
                <p style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-bottom:1.25rem;">
                    Pindai kartu anggota QR Anda, atau masuk menggunakan NISN/NIP bila belum memegang kartu.
                </p>

                @if (session('error'))
                    <div class="alert alert-danger" style="margin-bottom:1rem;">
                        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
                    </div>
                @endif

                {{-- Prioritas: Scan QR kartu --}}
                <form method="POST" action="{{ route('perpustakaan.login.post') }}" id="ppScanForm">
                    @csrf
                    <div class="input-group">
                        <label class="input-label" for="ppTokenInput">Scan Kartu Anggota (QR)</label>
                        <input type="text" id="ppTokenInput" name="token" class="input-field"
                            placeholder="Arahkan scanner ke QR kartu..." autocomplete="off" autofocus
                            style="font-family:var(--font-family-mono,monospace);letter-spacing:1.2px;font-weight:600;">
                        <div id="ppScanHint" style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:6px;">
                            <i class="fas fa-circle-info"></i> Kolom siap menerima hasil pindai otomatis.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;">
                        <i class="fas fa-qrcode"></i> Masuk via Kartu
                    </button>
                </form>

                <div
                    style="display:flex;align-items:center;gap:10px;margin:18px 0;color:var(--text-muted);font-size:var(--text-xs,0.75rem);">
                    <span style="flex:1;height:1px;background:var(--border-glass);"></span>
                    atau gunakan NISN / NIP
                    <span style="flex:1;height:1px;background:var(--border-glass);"></span>
                </div>

                <form method="POST" action="{{ route('perpustakaan.login.post') }}">
                    @csrf
                    <div class="input-group">
                        <label class="input-label" for="ppIdentifier">NISN / NIP / Nomor Anggota</label>
                        <input type="text" id="ppIdentifier" name="identifier" value="{{ old('identifier') }}"
                            class="input-field" placeholder="Contoh: 0071234567" autocomplete="username">
                        @error('identifier')
                            <div style="font-size:var(--text-xs,0.75rem);color:var(--danger,#ef4444);margin-top:5px;">
                                {{ $message }}</div>
                        @enderror
                    </div>
                    <div class="input-group">
                        <label class="input-label" for="ppPassword">Password</label>
                        <input type="password" id="ppPassword" name="password" class="input-field"
                            placeholder="Kosongkan bila belum pernah diubah" autocomplete="current-password">
                        <div style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:5px;">
                            Password awal = NISN/NIP Anda.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-right-to-bracket"></i> Masuk dengan NISN / NIP
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var input = document.getElementById('ppTokenInput');
            var form = document.getElementById('ppScanForm');
            var hint = document.getElementById('ppScanHint');
            if (!input || !form) return;

            setInterval(function () {
                if (document.activeElement !== input && document.activeElement.tagName !== 'SELECT') input.focus();
            }, 2500);

            var timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                hint.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membaca kartu...';
                timer = setTimeout(function () {
                    if (input.value.trim().length >= 16) { form.submit(); }
                    else { hint.innerHTML = '<i class="fas fa-circle-info"></i> Kolom siap menerima hasil pindai otomatis.'; }
                }, 200);
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
            });
        })();
    </script>
@endsection
