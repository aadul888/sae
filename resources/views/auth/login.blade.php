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
                <img id="loginLogo" src="{{ asset('img/logo-dark.png') }}" data-dark="{{ asset('img/logo-dark.png') }}" data-light="{{ asset('img/logo-light.png') }}"
                    alt="SAE Logo"
                    style="height: 48px; max-width: 180px; width: auto; object-fit: contain; margin-bottom: 12px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">Portal
                    Multi-User</h2>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Sistem Aplikasi Edukasi (SAE)</p>
            </div>

            @if (session('info'))
                <div
                    style="background: rgba(6,182,212,0.1); border: 1px solid var(--accent); color: var(--accent); padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> {{ session('info') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px;">
                    <i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-user"></i> Username / NISN / NIP / Email
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="text" name="username" id="usernameInput" required
                            placeholder="Masukkan ID pengguna..."
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="password" name="password" id="passwordInput" required placeholder="••••••••"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                        <button type="button" onclick="togglePass()"
                            style="border: none; background: transparent; color: var(--text-muted); padding: 0 12px; cursor: pointer;">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35);">
                    <i class="fas fa-arrow-right-to-bracket"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Quick Demo Switcher -->
            <div
                style="margin-top: 28px; padding-top: 20px; border-top: 1px dashed var(--border-color); text-align: center;">
                <p
                    style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.05em;">
                    Coba Akses Cepat (Demo):</p>
                <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                    <button type="button" onclick="fillDemo('admin')"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-color); font-size: 0.75rem; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                        <i class="fas fa-user-shield text-primary"></i> Admin
                    </button>
                    <button type="button" onclick="fillDemo('guru')"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-color); font-size: 0.75rem; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                        <i class="fas fa-chalkboard-user text-success"></i> Guru
                    </button>
                    <button type="button" onclick="fillDemo('siswa')"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-color); font-size: 0.75rem; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: 0.2s;">
                        <i class="fas fa-user-graduate text-accent"></i> Siswa
                    </button>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('home') }}" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                </a>
            </div>
        </div>
    </div>

    <script>
        function fillDemo(role) {
            const userInput = document.getElementById('usernameInput');
            const passInput = document.getElementById('passwordInput');
            userInput.value = role;
            passInput.value = '123456';
        }

        function togglePass() {
            const pass = document.getElementById('passwordInput');
            const eye = document.getElementById('eyeIcon');
            if (pass.type === 'password') {
                pass.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                pass.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
    </script>
@endsection
