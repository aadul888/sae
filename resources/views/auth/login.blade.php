@extends('layouts.app')

@section('title', 'Login Portal — SAE (Sistem Aplikasi Edukasi)')

@section('content')
    <div class="container"
        style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
        <div
            style="width: 100%; max-width: 440px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 36px 30px; box-shadow: var(--card-shadow); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
            <div
                style="position: absolute; top: -50px; right: -50px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%); border-radius: 50%; pointer-events: none;">
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                @php
                    $loginLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
                    $loginLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
                @endphp
                <img id="loginLogo" src="{{ $loginLogoDark }}" data-dark="{{ $loginLogoDark }}"
                    data-light="{{ $loginLogoLight }}" alt="SAE Logo"
                    style="height: 48px; max-width: 180px; width: auto; object-fit: contain; margin-bottom: 12px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">Portal
                    Multi-User</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Sistem Aplikasi Edukasi (SAE)</p>
            </div>

            @if (session('success'))
                <div
                    style="background: rgba(16,185,129,0.12); border: 1px solid #10b981; color: #10b981; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-check"></i> <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('warning'))
                <div
                    style="background: rgba(245,158,11,0.12); border: 1px solid #f59e0b; color: #d97706; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation"></i> <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('info'))
                <div
                    style="background: rgba(6,182,212,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i> <span>{{ session('info') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation"></i> <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Segmented Switcher Peran: GTK/Siswa vs Orang Tua -->
            <div
                style="display: flex; background: var(--bg-hover); padding: 4px; border-radius: 12px; margin-bottom: 22px; border: 1px solid var(--border-color); gap: 4px;">
                <button type="button" id="tabBtnUmum" onclick="switchLoginTab('umum')"
                    style="flex: 1; padding: 9px 12px; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: var(--primary); color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i>
                    <span>GTK &amp; Siswa</span>
                </button>
                <button type="button" id="tabBtnOrtu" onclick="switchLoginTab('orang_tua')"
                    style="flex: 1; padding: 9px 12px; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; background: transparent; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-people-roof"></i>
                    <span>Orang Tua / Wali</span>
                </button>
            </div>

            <!-- Form 1: Login GTK & Siswa (Default) -->
            <form id="formLoginUmum" action="{{ route('login.post') }}" method="POST">
                @csrf
                <input type="hidden" name="login_type" value="umum">
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-user"></i> Username / NISN / NIP / Email
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="text" name="username" id="usernameInput" required
                            placeholder="Masukkan ID pengguna..."
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none; border-radius: 12px;">
                    </div>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="password" name="password" id="passwordInput" required placeholder="••••••••"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none; border-radius: 12px 0 0 12px;">
                        <button type="button" onclick="togglePass('passwordInput', 'eyeIcon')"
                            style="border: none; background: transparent; color: var(--text-muted); padding: 0 14px; cursor: pointer; height: 100%;">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35);">
                    <i class="fas fa-arrow-right-to-bracket"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Form 2: Login Khusus Orang Tua / Wali Murid -->
            <form id="formLoginOrtu" action="{{ route('login.post') }}" method="POST" style="display: none;">
                @csrf
                <input type="hidden" name="login_type" value="orang_tua">
                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-id-card"></i> NISN / NIK Siswa
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="text" name="nisn_anak" id="inputNisnAnak"
                            placeholder="Masukkan NISN atau NIK siswa..."
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none; border-radius: 12px;">
                    </div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; display: block;">
                        10 digit NISN atau 16 digit NIK putra/putri Anda.
                    </span>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-calendar-check"></i> Password / PIN (Tanggal Lahir Siswa)
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="date" name="tgl_lahir_anak" id="inputTglLahirAnak"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: 0.9rem; outline: none; border-radius: 12px;">
                    </div>
                    <span style="font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; display: block;">
                        Pilih tanggal lahir anak sesuai data sekolah sebagai password/PIN keamanan.
                    </span>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; background: #10b981; border-color: #10b981; box-shadow: 0 4px 16px rgba(16,185,129,0.35);">
                    <i class="fas fa-door-open"></i> Masuk Portal Orang Tua
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('home') }}" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/login.js') }}?v={{ file_exists(public_path('js/login.js')) ? filemtime(public_path('js/login.js')) : time() }}">
    </script>
@endsection
