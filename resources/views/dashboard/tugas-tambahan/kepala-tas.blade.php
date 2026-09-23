@extends('layouts.dashboard')

@section('title', 'Dashboard Kepala TAS / KTU — SAE')
@section('dash_title', 'Dashboard Kepala TAS')

@section('content')
    <!-- Banner Header Kepala TAS -->
    <div class="dash-banner" style="margin-bottom: 24px; padding: 22px 26px; border-radius: 16px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1; min-width: 280px;">
                <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(16, 185, 129, 0.2); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.2);">
                    <i class="fas fa-landmark"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px; font-weight: 700; background: #10b981; border-color: #10b981;">
                            KOORDINATOR TATA USAHA (KEPALA TAS)
                        </span>
                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                            TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                        </span>
                    </div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
                        Portal Kepengawasan Administrasi Sekolah
                    </h2>
                    <p style="color: var(--text-muted); font-size: 0.84rem; margin: 0; line-height: 1.5;">
                        Pusat kendali, supervisi kinerja staf tata usaha, rekap aktivitas kepegawaian, disposisi persuratan, dan inventaris sarana prasarana.
                    </p>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <a href="{{ route('dashboard.tendik') }}" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Dashboard Pokok Tendik
                </a>
                <a href="{{ route('dashboard.tendik.laporan.index') }}" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.82rem; font-weight: 700; border-radius: 8px; background: #10b981; border-color: #10b981;">
                    <i class="fas fa-file-signature me-1"></i> Rekap Kinerja Tendik
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards TAS -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-id-badge"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $totalTendik }}</div>
                <div class="dash-stat-label">Total Tenaga Kependidikan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalSuratMasuk }}</div>
                <div class="dash-stat-label">Surat Masuk</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalSuratKeluar }}</div>
                <div class="dash-stat-label">Surat Keluar</div>
            </div>
        </div>
    </div>

    <!-- Akses Bidang di Bawah Koordinasi TAS -->
    <div style="margin-bottom: 16px;">
        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0;">
            <i class="fas fa-sitemap text-primary me-2"></i> Unit Kerja di Bawah Tata Usaha
        </h3>
        <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
            Akses langsung ke setiap divisi operasional tendik sekolah.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <a href="{{ route('dashboard.tendik', ['bidang' => 'persuratan']) }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(20, 184, 166, 0.15); color: #14b8a6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Persuratan &amp; Kearsipan</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Agenda surat &amp; penomoran</div>
            </div>
        </a>

        <a href="{{ route('dashboard.tendik', ['bidang' => 'kepegawaian']) }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(139, 92, 246, 0.15); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-id-card-alt"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Kepegawaian</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">KGB, cuti, dan arsip PTK</div>
            </div>
        </a>

        <a href="{{ route('dashboard.tendik', ['bidang' => 'sarpras']) }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-building"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Sarana Prasarana</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Inventaris &amp; aset sekolah</div>
            </div>
        </a>

        <a href="{{ route('dashboard.tendik', ['bidang' => 'kesiswaan']) }}" class="card" style="padding: 16px 20px; border-radius: 12px; margin: 0; text-decoration: none; color: inherit; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 14px; transition: all 0.2s ease;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(59, 130, 246, 0.15); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div>
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color);">Kesiswaan</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Administrasi buku induk siswa</div>
            </div>
        </a>
    </div>
@endsection
