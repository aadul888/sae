@extends('layouts.dashboard')

@section('title', 'Akses Dibatasi — SAE')
@section('dash_title', 'Akses Modul Dinonaktifkan')

@section('content')
    <div class="card text-center"
        style="max-width: 680px; margin: 40px auto; padding: 48px 32px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg, #1e293b);">
        <div
            style="width: 76px; height: 76px; margin: 0 auto 24px; background: rgba(239, 68, 68, 0.12); border: 2px solid rgba(239, 68, 68, 0.25); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-lock" style="font-size: 2rem; color: #ef4444;"></i>
        </div>
        <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 10px;">
            Akses Dashboard {{ $roleName }} Dinonaktifkan
        </h3>
        <p style="font-size: 0.92rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 28px;">
            Administrator sekolah saat ini membatasi akses ke halaman utama Dashboard untuk peran
            <strong>{{ $roleName }}</strong>. Anda tetap dapat membuka modul-modul lain yang diberikan izin melalui menu
            navigasi samping (sidebar).
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('logout') }}" class="btn btn-outline" style="padding: 10px 24px; font-weight: 600;">
                <i class="fas fa-right-from-bracket me-1"></i> Keluar dari Sistem
            </a>
        </div>
    </div>
@endsection
