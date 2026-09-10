@extends('layouts.dashboard')

@section('title', 'Dashboard Tenaga Kependidikan — SAE')
@section('dash_title', 'Portal Tenaga Kependidikan & Administrasi')

@section('content')
    @php
        $hour = date('H');
        $greeting = $hour < 11 ? 'Selamat Pagi,' : ($hour < 15 ? 'Selamat Siang,' : ($hour < 18 ? 'Selamat Sore,' : 'Selamat Malam,'));
    @endphp
    <!-- Banner Header -->
    <div class="card"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); position: relative; overflow: hidden; border-radius: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px; line-height: 1.3;">
                    <span style="display: block; font-size: 0.95rem; font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">{{ $greeting }}</span>
                    {{ session('user')['name'] ?? 'Tenaga Kependidikan' }}! 📁
                </h2>
                <div
                    style="display: flex; gap: 16px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 12px;">
                    <span><i class="fas fa-briefcase text-primary me-1"></i> Bagian:
                        <strong>{{ session('user')['mapel'] ?? 'Tata Usaha / Administrasi Sekolah' }}</strong></span>
                    @if (!empty(session('user')['nip']))
                        <span><i class="fas fa-id-card text-primary me-1"></i> NIP:
                            <strong>{{ session('user')['nip'] }}</strong></span>
                    @endif
                </div>
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #3b82f6;">
                    <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </div>
            </div>
            <div>
                <button class="btn btn-primary"
                    style="background: #10b981; border: none; padding: 10px 18px; font-size: 0.85rem; border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-plus me-1"></i> Catat Surat / Dokumen
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid"
        style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_surat_masuk'] }} Dokumen</div>
                <div class="dash-stat-label">Surat Masuk Bulan Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_surat_keluar'] }} Berkas</div>
                <div class="dash-stat-label">Surat Keluar / Keterangan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-address-book"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['buku_tamu_hari_ini'] }} Tamu</div>
                <div class="dash-stat-label">Buku Tamu Digital Hari Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.05rem;">{{ $stats['status_presensi'] }}</div>
                <div class="dash-stat-label">Masuk: {{ $stats['presensi_masuk'] }}</div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Administrasi & Persuratan Terbaru -->
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-file-invoice text-primary me-2"></i> Log Administrasi &amp; Surat Terakhir
                </div>
                <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 8px;">
                    Hari ini: {{ date('d F Y') }}
                </span>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th
                                style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                No. Agenda</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Jenis / Kategori</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Perihal Dokumen</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                                Status</th>
                            <th
                                style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($administrasi_tugas as $adm)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="padding: 12px 16px; font-size: 0.8rem; font-family: monospace; font-weight: 600; color: var(--text-color);">
                                    {{ $adm['nomor'] }}
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: sans-serif;">
                                        {{ $adm['tgl'] }}</div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span
                                        class="badge {{ str_contains($adm['kategori'], 'Masuk') ? 'badge-primary' : (str_contains($adm['kategori'], 'Keluar') ? 'badge-success' : 'badge-warning') }}"
                                        style="font-size: 0.72rem; padding: 3px 8px;">
                                        {{ $adm['kategori'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">{{ $adm['perihal'] }}</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                        Dari/Tujuan: {{ $adm['pengirim'] }}
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 5px; color: #10b981; font-weight: 600; font-size: 0.78rem;">
                                        <i class="fas fa-circle-check" style="font-size: 0.68rem;"></i>
                                        {{ $adm['status'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: center;">
                                    <button class="btn btn-outline"
                                        style="padding: 4px 8px; font-size: 0.75rem; border-radius: 6px;"
                                        title="Lihat Dokumen">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Menu Cepat Administrasi -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <div class="card" style="padding: 18px; border-radius: 14px;">
                <div
                    style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-bolt text-primary"></i> Akses Cepat Administrasi
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                        style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                        <span><i class="fas fa-id-badge text-primary me-2"></i> Direktori Tendik Aktif</span>
                        <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                    </a>
                    <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                        style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                        <span><i class="fas fa-chalkboard-user text-primary me-2"></i> Direktori Guru Aktif</span>
                        <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                    </a>
                    <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-outline"
                        style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                        <span><i class="fas fa-user-graduate text-primary me-2"></i> Direktori Peserta Didik Aktif</span>
                        <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                    </a>
                    <a href="#" class="btn btn-outline"
                        style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-size: 0.82rem; border-radius: 8px; text-align: left;">
                        <span><i class="fas fa-address-book text-primary me-2"></i> Buku Tamu Digital</span>
                        <i class="fas fa-chevron-right text-muted" style="font-size: 0.72rem;"></i>
                    </a>
                </div>
            </div>

            <!-- Jam Kerja & Kehadiran -->
            <div class="card"
                style="padding: 18px; border-radius: 14px; background: rgba(99, 102, 241, 0.04); border: 1px solid rgba(99, 102, 241, 0.15);">
                <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); margin-bottom: 8px;">
                    <i class="far fa-clock text-primary me-1"></i> Jam Layanan TU Hari Ini
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                    Senin - Kamis: <strong>07.00 - 15.30 WIB</strong><br>
                    Jumat: <strong>07.00 - 15.00 WIB</strong>
                </div>
                <div
                    style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); font-size: 0.76rem; color: #10b981; font-weight: 600;">
                    <i class="fas fa-check-circle me-1"></i> Presensi staf telah terverifikasi via RFID Gateway.
                </div>
            </div>
        </div>
    </div>
@endsection
