@extends('layouts.dashboard')

@section('title', 'Peserta Didik Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Portal Peserta Didik')

@section('content')
    @php
        $hour = date('H');
        $greeting =
            $hour < 11
                ? 'Selamat Pagi,'
                : ($hour < 15
                    ? 'Selamat Siang,'
                    : ($hour < 18
                        ? 'Selamat Sore,'
                        : 'Selamat Malam,'));
    @endphp
    @php
        $fotoUrl = $pd->foto_url ?? session('user.foto_url');
        $fotoSize = $pd->foto_size ?? null;
    @endphp
    <!-- Welcome Banner -->
    <div class="dash-banner" style="background: linear-gradient(135deg, rgba(6,182,212,0.15) 0%, rgba(99,102,241,0.1) 100%); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            @if ($fotoUrl)
                <!-- Pasfoto Peserta Didik (Tanpa Bingkai & Tanpa Latar Belakang) -->
                <div class="dash-banner-foto" style="flex-shrink: 0; width: 88px; height: 118px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; box-shadow: none;">
                    <img src="{{ $fotoUrl }}" alt="{{ session('user.name', 'Peserta Didik') }}" 
                         style="max-width: 100%; max-height: 100%; width: auto; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.18));">
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                    <span
                        style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">{{ $greeting }}</span>
                    {{ session('user.name', 'Peserta Didik') }}! 🎓
                </h2>
                <p style="color: var(--text-muted); font-size: 0.84rem; margin-bottom: 8px; line-height: 1.4;">
                    NISN: <strong>{{ session('user.nisn', $pd->nisn ?? '0071234567') }}</strong> &bull; Kelas:
                    <strong>{{ session('user.kelas', $pd->nama_rombel ?? 'XII RPL 1') }}</strong> &bull; Status: <span
                        class="text-success font-bold"><i class="fas fa-circle-check"></i> Aktif</span>
                </p>
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(6,182,212,0.1); border: 1px solid rgba(6,182,212,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #06b6d4;">
                    <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </div>
            </div>
        </div>
        @php
            $currentNisn = $pd->nisn ?? session('user.nisn');
        @endphp
        @if ($currentNisn)
            <div class="dash-banner-actions">
                <button type="button" class="btn btn-outline" onclick="openKartuPelajarModal('{{ $currentNisn }}')"
                    style="padding: 9px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; border-color: #06b6d4; color: #06b6d4;">
                    <i class="fas fa-id-card"></i> Kartu Digital (QR)
                </button>
            </div>
        @endif
    </div>

    <!-- Stats Counter -->
    <div class="dash-stat-grid">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['presensi_bulan_ini'] }}%</div>
                <div class="dash-stat-label">Tingkat Kehadiran Bulan Ini</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['hadir_hari'] }} Hari</div>
                <div class="dash-stat-label">Total Hadir (Izin: {{ $stats['izin_hari'] }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-star"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['poin_prestasi'] }} Poin</div>
                <div class="dash-stat-label">Poin Prestasi &amp; Sikap</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['poin_pelanggaran'] }} Poin</div>
                <div class="dash-stat-label">Pelanggaran / Disiplin</div>
            </div>
        </div>
    </div>

    <!-- 2 Columns: Attendance History & Schedule -->
    <div class="dash-grid-2">
        <!-- Recent Attendance -->
        @if (\App\Models\RolePermission::canAccess('peserta_didik', 'menu_riwayat_rfid'))
            <div class="card" style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-id-card-clip text-accent"></i> Riwayat Tap Presensi Terakhir
                    </h3>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">RFID Live</span>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                                <th style="padding: 10px;">Tanggal</th>
                                <th style="padding: 10px;">Masuk</th>
                                <th style="padding: 10px;">Pulang</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($presensi_terakhir as $p)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                                    <td style="padding: 10px; font-weight: 600; color: var(--text-color);">
                                        {{ $p['tanggal'] }}
                                    </td>
                                    <td style="padding: 10px; color: #10b981;">{{ $p['jam_masuk'] }}</td>
                                    <td style="padding: 10px; color: var(--text-muted);">{{ $p['jam_pulang'] }}</td>
                                    <td style="padding: 10px;">
                                        <span
                                            style="background: rgba(16,185,129,0.15); color: #10b981; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;">
                                            {{ $p['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Today's Schedule -->
        @if (\App\Models\RolePermission::canAccess('peserta_didik', 'menu_jadwal_pelajaran'))
            <div class="card" style="margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color);">
                        <i class="fas fa-book-bookmark text-primary"></i> Jadwal Pelajaran Hari Ini
                    </h3>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Kelas XII RPL 1</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach ($jadwal_pelajaran as $jp)
                        <div
                            style="display: flex; align-items: flex-start; gap: 12px; padding: 12px; background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px;">
                            <div
                                style="min-width: 90px; font-size: 0.78rem; font-weight: 700; color: var(--accent); background: rgba(6,182,212,0.1); padding: 6px; border-radius: 8px; text-align: center;">
                                {{ $jp['jam'] }}
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-color);">
                                    {{ $jp['mapel'] }}</div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    <i class="fas fa-chalkboard-user"></i> {{ $jp['guru'] }} &bull; <i
                                        class="fas fa-location-dot"></i> {{ $jp['ruang'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Modal Pratinjau Kartu Pelajar Digital -->
    @include('kartu-pelajar.modal-preview')
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}">
@endpush
