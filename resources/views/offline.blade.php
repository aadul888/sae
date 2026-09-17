@extends('layouts.app')

@section('title', 'Koneksi Terputus (Offline) — SAE')

@section('content')
<div class="container" style="min-height: 75vh; display: flex; align-items: center; justify-content: center; padding: 40px 16px;">
    <div style="width: 100%; max-width: 480px; background: var(--bg-card); border: 1px solid var(--border-glass); border-radius: 20px; padding: 40px 28px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.25); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); position: relative; overflow: hidden;">
        
        <!-- Offline Status Badge & Icon -->
        <div style="width: 72px; height: 72px; border-radius: 20px; background: rgba(239, 68, 68, 0.12); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.25); display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 20px;">
            <i class="fas fa-wifi-slash"></i>
        </div>

        <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin-bottom: 8px;">
            Koneksi Terputus
        </h2>

        <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.55; margin-bottom: 24px;">
            Perangkat Anda sedang tidak terhubung ke internet. Halaman ini ditampilkan oleh <strong>SAE Offline Cache</strong>. Silakan periksa koneksi Wi-Fi atau data seluler Anda.
        </p>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
            <button type="button" onclick="window.location.reload()" class="btn btn-primary" style="padding: 0.75rem 1.25rem; font-size: 0.9rem; justify-content: center; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-rotate-right"></i>
                <span>Coba Muat Ulang</span>
            </button>

            <a href="{{ route('presensi.scan') }}" class="btn" style="background: var(--input-bg); border: 1px solid var(--border-glass); color: var(--text-main); padding: 0.75rem 1.25rem; font-size: 0.9rem; justify-content: center; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-qrcode"></i>
                <span>Buka Terminal Presensi</span>
            </a>
        </div>

        <div id="onlineStatusNotice" style="display: none; padding: 8px 12px; border-radius: 8px; font-size: 0.8rem; background: rgba(16,185,129,0.15); color: var(--success); border: 1px solid rgba(16,185,129,0.3); align-items: center; justify-content: center; gap: 6px;">
            <i class="fas fa-circle-check"></i>
            <span>Koneksi pulih! Memuat ulang otomatis...</span>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Realtime connection listener
    window.addEventListener('online', function() {
        const notice = document.getElementById('onlineStatusNotice');
        if (notice) {
            notice.style.display = 'inline-flex';
        }
        setTimeout(() => {
            window.location.reload();
        }, 1200);
    });
</script>
@endpush
