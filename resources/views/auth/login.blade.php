@extends('layouts.app')

@section('title', 'Login Portal — SAE (Sistem Aplikasi Edukasi)')

@section('content')
    <div class="container"
        style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
        <div
            style="width: 100%; max-width: 440px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-2xl, 20px); padding: 36px 30px; box-shadow: var(--card-shadow); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
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
                <h2 style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 6px;">Portal
                    Multi-User</h2>
                <p style="font-size: var(--text-base, 0.875rem); color: var(--text-muted);">Sistem Aplikasi Edukasi (SAE)</p>
            </div>

            @if (session('success'))
                <div
                    style="background: rgba(16,185,129,0.12); border: 1px solid var(--success, #10b981); color: var(--success, #10b981); padding: 10px 14px; border-radius: var(--radius-lg, 12px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-check"></i> <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('warning'))
                <div
                    style="background: rgba(245,158,11,0.12); border: 1px solid var(--warning, #f59e0b); color: var(--warning, #d97706); padding: 10px 14px; border-radius: var(--radius-lg, 12px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation"></i> <span>{{ session('warning') }}</span>
                </div>
            @endif

            @if (session('info'))
                <div
                    style="background: rgba(6,182,212,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 10px 14px; border-radius: var(--radius-lg, 12px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle"></i> <span>{{ session('info') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid var(--danger, #ef4444); color: var(--danger, #ef4444); padding: 10px 14px; border-radius: var(--radius-lg, 12px); font-size: var(--text-sm, 0.8125rem); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-triangle-exclamation"></i> <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Segmented Switcher Peran: GTK/Siswa vs Orang Tua -->
            <div
                style="display: flex; background: var(--bg-hover); padding: 4px; border-radius: var(--radius-lg, 12px); margin-bottom: 22px; border: 1px solid var(--border-color); gap: 4px;">
                <button type="button" id="tabBtnUmum" onclick="switchLoginTab('umum')"
                    style="flex: 1; padding: 9px 12px; border: none; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); font-weight: 700; cursor: pointer; transition: all 0.2s ease; background: var(--primary); color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i>
                    <span>GTK &amp; Siswa</span>
                </button>
                <button type="button" id="tabBtnOrtu" onclick="switchLoginTab('orang_tua')"
                    style="flex: 1; padding: 9px 12px; border: none; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); font-weight: 600; cursor: pointer; transition: all 0.2s ease; background: transparent; color: var(--text-muted); display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
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
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-user"></i> Username / NISN / NIP / Email
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="text" name="username" id="usernameInput" required
                            placeholder="Masukkan ID pengguna..."
                            value="{{ old('username') }}"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: var(--text-base, 0.875rem); outline: none; border-radius: var(--radius-lg, 12px);">
                    </div>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="password" name="password" id="passwordInput" required placeholder="••••••••"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: var(--text-base, 0.875rem); outline: none; border-radius: 12px 0 0 12px;">
                        <button type="button" onclick="togglePass('passwordInput', 'eyeIcon')"
                            style="border: none; background: transparent; color: var(--text-muted); padding: 0 14px; cursor: pointer; height: 100%;">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: var(--text-md, 1rem); justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35);">
                    <i class="fas fa-arrow-right-to-bracket"></i> Masuk ke Sistem
                </button>

                <div style="display: flex; align-items: center; margin: 20px 0 16px; gap: 12px;">
                    <div style="flex: 1; height: 1px; background: var(--border-color);"></div>
                    <span style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">atau masuk dengan</span>
                    <div style="flex: 1; height: 1px; background: var(--border-color);"></div>
                </div>

                <a href="{{ route('auth.google') }}" class="btn btn-google"
                    style="width: 100%; padding: 11px 16px; font-weight: 700; font-size: var(--text-base, 0.875rem); justify-content: center; gap: 10px; border-radius: var(--radius-lg, 12px);">
                    <svg style="width: 18px; height: 18px; flex-shrink: 0;" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Masuk dengan Akun Belajar.id</span>
                </a>
                <div style="text-align: center; margin-top: 8px;">
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Admin, Guru, Tendik &amp; Peserta Didik (Domain <strong>smk.belajar.id</strong>)
                    </span>
                </div>
            </form>

            <!-- Form 2: Login Khusus Orang Tua / Wali Murid -->
            <form id="formLoginOrtu" action="{{ route('login.post') }}" method="POST"
                data-active="{{ old('login_type') === 'orang_tua' || old('nisn_anak') || $errors->has('nisn_anak') || $errors->has('tgl_lahir_anak') ? '1' : '0' }}"
                style="display: none;">
                @csrf
                <input type="hidden" name="login_type" value="orang_tua">
                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-id-card"></i> NISN / NIK Siswa
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; display: flex; align-items: center; overflow: hidden;">
                        <input type="tel" inputmode="numeric" pattern="[0-9]*" name="nisn_anak" id="inputNisnAnak"
                            maxlength="16" autocomplete="username"
                            value="{{ old('nisn_anak') }}"
                            placeholder="Masukkan 10 digit NISN atau 16 digit NIK..."
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: var(--text-base, 0.875rem); outline: none; border-radius: var(--radius-lg, 12px);">
                    </div>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px; display: block;">
                        10 digit NISN atau 16 digit NIK putra/putri Anda.
                    </span>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-calendar-check"></i> Password / PIN (Tanggal Lahir Siswa)
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 0; display: flex; align-items: center; overflow: hidden; position: relative;">
                        <input type="tel" inputmode="numeric" pattern="[0-9\-]*" name="tgl_lahir_anak" id="inputTglLahirAnak"
                            maxlength="10" autocomplete="off"
                            value="{{ old('tgl_lahir_anak') }}"
                            placeholder="DD-MM-YYYY (contoh: 17-08-2008)"
                            style="flex: 1; border: none; background: transparent; padding: 12px 14px; color: var(--text-color); font-size: var(--text-base, 0.875rem); outline: none; border-radius: 12px 0 0 12px;">
                        <div id="btnPickerWrapper" class="calendar-picker-btn" title="Pilih tanggal dari kalender">
                            <i class="fas fa-calendar-days" style="font-size: var(--text-xl, 1.25rem); pointer-events: none;"></i>
                            <input type="date" id="nativeDatePicker" aria-label="Pilih Tanggal Lahir Siswa" tabindex="-1">
                        </div>
                    </div>
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 4px; display: block;">
                        Ketik angka tanggal lahir (DD-MM-YYYY) atau klik ikon kalender untuk memilih tanggal.
                    </span>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: var(--text-md, 1rem); justify-content: center; background: var(--success, #10b981); border-color: var(--success, #10b981); box-shadow: 0 4px 16px rgba(16,185,129,0.35);">
                    <i class="fas fa-door-open"></i> Masuk Portal Orang Tua
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('home') }}" style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/login.js') }}?v={{ file_exists(public_path('js/login.js')) ? filemtime(public_path('js/login.js')) : time() }}">
    </script>
@endsection
