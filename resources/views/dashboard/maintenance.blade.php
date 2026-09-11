@extends('layouts.dashboard')

@section('title', 'Arsip & Maintenance — SAE')
@section('dash_title', 'Arsip & Maintenance')

@section('content')
    <div class="dash-banner"
        style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05)); border: 1px solid rgba(239, 68, 68, 0.2);">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: #ef4444; margin-bottom: 4px;">
                <i class="fas fa-server me-2"></i> Arsip & Maintenance Data
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Fasilitas untuk mencadangkan (arsip) data lokal dan membersihkan file residu sistem lama.
                Gunakan dengan hati-hati.
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 24px;">

        <!-- Card Unduh Arsip -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; border-top: 3px solid var(--primary);">
            <div
                style="width: 64px; height: 64px; background: rgba(99,102,241,0.1); color: var(--primary); font-size: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <i class="fas fa-file-zipper"></i>
            </div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-color);">Cadangkan Data
                Arsip</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; max-width: 250px;">
                Unduh salinan dummy arsip (berkas log, laporan, dan konfigurasi) ke dalam format <code>.ZIP</code>.
            </p>
            <a href="{{ route('dashboard.maintenance.download') }}" class="btn btn-primary"
                style="padding: 10px 24px; font-weight: 600; border-radius: 8px;">
                <i class="fas fa-download me-2"></i> Unduh Arsip (.ZIP)
            </a>
        </div>

        <!-- Card Bersihkan Data -->
        <div class="card"
            style="padding: 24px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; border-top: 3px solid #ef4444;">
            <div
                style="width: 64px; height: 64px; background: rgba(239,68,68,0.1); color: #ef4444; font-size: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                <i class="fas fa-broom"></i>
            </div>
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; color: var(--text-color);">Bersihkan Data
                Lama</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 24px; max-width: 250px;">
                Hapus session kadaluarsa, log sisa, dan cache aplikasi. Aksi ini tidak menghapus data sekolah (Dapodik).
            </p>
            <form action="{{ route('dashboard.maintenance.clean') }}" method="POST" id="formCleanData">
                @csrf
                <button type="submit" class="btn"
                    style="background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); padding: 10px 24px; font-weight: 600; border-radius: 8px; transition: all 0.2s;">
                    <i class="fas fa-trash-can me-2"></i> Bersihkan Sistem
                </button>
            </form>
        </div>

    </div>

    @push('scripts')
        <script src="{{ asset('js/maintenance.js') }}?v={{ file_exists(public_path('js/maintenance.js')) ? filemtime(public_path('js/maintenance.js')) : time() }}"></script>
    @endpush
@endsection
