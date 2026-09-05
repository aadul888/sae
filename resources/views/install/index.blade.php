@extends('layouts.app')

@section('title', 'Instalasi Sistem — SAE (Sistem Aplikasi Edukasi)')

@section('content')
    <div class="container"
        style="min-height: 85vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
        <div
            style="width: 100%; max-width: 480px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px; padding: 36px 30px; box-shadow: var(--card-shadow); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
            <div
                style="position: absolute; top: -50px; right: -50px; width: 140px; height: 140px; background: radial-gradient(circle, rgba(99,102,241,0.25) 0%, transparent 70%); border-radius: 50%; pointer-events: none;">
            </div>

            <div style="text-align: center; margin-bottom: 24px;">
                <img id="installLogo" src="/img/logo-dark.png" data-dark="/img/logo-dark.png" data-light="/img/logo-light.png"
                    alt="SAE Logo"
                    style="height: 48px; max-width: 180px; width: auto; object-fit: contain; margin-bottom: 12px;">
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">Installer
                    Sistem SAE</h2>
            </div>

            @if (session('error'))
                <div
                    style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; color: #ef4444; padding: 10px 14px; border-radius: 10px; font-size: 0.82rem; margin-bottom: 20px;">
                    <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('install.process') }}" method="POST">
                @csrf
                <input type="hidden" name="db_host" value="{{ old('db_host', '127.0.0.1') }}">
                <input type="hidden" name="db_port" value="{{ old('db_port', '3306') }}">

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-database text-primary"></i> Nama Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="text" name="db_name" required value="{{ old('db_name', 'db_sae') }}"
                            placeholder="contoh: db_sae"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-user text-primary"></i> Username Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="text" name="db_user" required value="{{ old('db_user', 'root') }}"
                            placeholder="contoh: root"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div style="margin-bottom: 22px;">
                    <label
                        style="display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px;">
                        <i class="fas fa-lock text-primary"></i> Password Database
                    </label>
                    <div class="input-group"
                        style="background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center;">
                        <input type="password" name="db_pass" placeholder="Kosongkan jika tanpa password"
                            style="flex: 1; border: none; background: transparent; padding: 10px 14px; color: var(--text-color); font-size: 0.9rem; outline: none;">
                    </div>
                </div>

                <div
                    style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); border-radius: 12px; padding: 14px; margin-bottom: 22px; font-size: 0.8rem; color: var(--text-muted); line-height: 1.6;">
                    <div style="font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                        <i class="fas fa-info-circle text-primary me-1"></i> Informasi Akun Bawaan:
                    </div>
                    <div>• <b>Admin:</b> admin@sae.id / <code style="color: var(--primary);">Admin543!</code></div>
                    <div>• <b>Guru:</b> gtk@sae.id / <code style="color: var(--primary);">Geteka543!</code></div>
                    <div>• <b>Siswa:</b> siswa@sae.id / <code style="color: var(--primary);">Siswa543!</code></div>
                </div>

                <button type="submit" class="btn btn-primary"
                    style="width: 100%; padding: 13px; font-weight: 700; font-size: 0.95rem; justify-content: center; box-shadow: 0 4px 16px rgba(99,102,241,0.35);">
                    <i class="fas fa-rocket me-2"></i> Mulai Instalasi Sistem
                </button>
            </form>

            <div style="margin-top: 20px; text-align: center;">
                <a href="{{ route('home') }}" style="color: var(--text-muted); font-size: 0.8rem; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection
