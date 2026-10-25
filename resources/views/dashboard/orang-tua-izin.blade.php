@extends('layouts.dashboard')

@section('title', 'e-Izin & Surat Sakit Siswa — Portal Orang Tua SAE')
@section('dash_title', 'e-Izin & Surat Sakit')

@section('content')
    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(99, 102, 241, 0.08) 100%); border: 1px solid rgba(245, 158, 11, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: var(--radius-xl, 16px); padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            <div style="flex: 1; min-width: 0;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline btn-sm"
                        style="padding: 4px 10px; font-size: var(--text-xs, 0.75rem); border-radius: var(--radius-sm, 6px);">
                        <i class="fas fa-arrow-left me-1"></i> Dashboard
                    </a>
                    <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Portal Orang Tua &bull;
                        {{ $parentName }}</span>
                </div>
                <h2
                    style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                    <i class="fas fa-ticket-alt text-warning me-1"></i> Perizinan &amp; Surat Sakit: <span
                        style="color: var(--warning, #f59e0b);">{{ $pd->nama ?? 'Peserta Didik' }}</span>
                </h2>
                <div
                    style="display: flex; align-items: center; gap: 12px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-wrap: wrap;">
                    <span><i class="fas fa-id-card text-primary me-1"></i> NISN:
                        <strong>{{ $pd->nisn ?? '-' }}</strong></span>
                    <span><i class="fas fa-door-open text-info me-1"></i> Kelas:
                        <strong>{{ $pd->nama_rombel ?? 'Reguler' }}</strong></span>
                    @if ($waliKelas)
                        <span><i class="fas fa-chalkboard-user text-warning me-1"></i> Wali:
                            <strong>{{ $waliKelas['nama'] }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.orang-tua') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Beranda Orang Tua">
                <i class="fas fa-house"></i>
            </a>
            <a href="{{ route('dashboard.orang-tua.kehadiran') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Kehadiran Siswa">
                <i class="fas fa-calendar-check text-success"></i>
            </a>
        </div>
    </div>

    <!-- 2. Quick Stats Grid Perizinan -->
    <div class="dash-stat-grid"
        style="margin-bottom: 24px; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--warning, #f59e0b);">{{ $izinKeluarList->count() }} Tiket</div>
                <div class="dash-stat-label">Tiket e-Izin Gerbang Keluar</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    Izin meninggalkan sekolah saat KBM
                </div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--primary);">{{ $suratIzinList->count() }} Surat</div>
                <div class="dash-stat-label">Surat Permohonan Izin / Sakit</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                    Verifikasi persetujuan Wali Kelas
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Tabel Riwayat Tiket e-Izin Keluar Sekolah (Piket & Gerbang) -->
    <div class="card"
        style="padding: 20px 22px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <div>
                <h3
                    style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-ticket-alt text-warning"></i> Log Tiket e-Izin Gerbang Keluar / Masuk Sekolah
                </h3>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                    Tiket resmi yang diterbitkan Guru Piket dan diverifikasi langsung oleh petugas Satpam di pos gerbang
                    sekolah.
                </p>
            </div>
            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700;">
                {{ $izinKeluarList->count() }} Riwayat
            </span>
        </div>

        @if ($izinKeluarList->isNotEmpty())
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal</th>
                            <th
                                style="min-width: 120px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis Izin</th>
                            <th
                                style="min-width: 120px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jam Rencana</th>
                            <th
                                style="min-width: 130px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Aktual Gerbang</th>
                            <th
                                style="min-width: 180px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Alasan &amp; Tujuan</th>
                            <th
                                style="min-width: 110px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status</th>
                            <th
                                style="min-width: 130px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Petugas Piket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($izinKeluarList as $iz)
                            @php
                                $jenisLabel = $iz->jenis_izin === 'pulang_cepat' ? 'Pulang Cepat' : 'Keluar Sementara';
                                $statusBadge = match ($iz->status) {
                                    'disetujui' => [
                                        'label' => 'Disetujui',
                                        'bg' => 'rgba(16,185,129,0.15)',
                                        'color' => '#10b981',
                                    ],
                                    'selesai' => [
                                        'label' => 'Kembali/Selesai',
                                        'bg' => 'rgba(59,130,246,0.15)',
                                        'color' => '#3b82f6',
                                    ],
                                    'menunggu' => [
                                        'label' => 'Menunggu Piket',
                                        'bg' => 'rgba(245,158,11,0.15)',
                                        'color' => '#f59e0b',
                                    ],
                                    'ditolak' => [
                                        'label' => 'Ditolak',
                                        'bg' => 'rgba(239,68,68,0.15)',
                                        'color' => '#ef4444',
                                    ],
                                    default => [
                                        'label' => ucfirst($iz->status),
                                        'bg' => 'rgba(100,116,139,0.15)',
                                        'color' => '#64748b',
                                    ],
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Tanggal">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                        {{ \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td data-label="Jenis Izin">
                                    <span class="badge"
                                        style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                        {{ $jenisLabel }}
                                    </span>
                                </td>
                                <td data-label="Jam Rencana" style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                    {{ substr($iz->jam_keluar_rencana ?? '', 0, 5) }} -
                                    {{ substr($iz->jam_kembali_rencana ?? '', 0, 5) ?: 'Selesai' }}
                                </td>
                                <td data-label="Aktual Gerbang"
                                    style="text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    Keluar:
                                    {{ $iz->jam_keluar_aktual ? substr($iz->jam_keluar_aktual, 0, 5) . ' WIB' : '--:--' }}<br>
                                    Kembali:
                                    {{ $iz->jam_kembali_aktual ? substr($iz->jam_kembali_aktual, 0, 5) . ' WIB' : '--:--' }}
                                </td>
                                <td data-label="Alasan &amp; Tujuan" style="font-size: var(--text-sm, 0.8125rem);">
                                    <div style="font-weight: 700; color: var(--text-color);">{{ $iz->alasan }}</div>
                                    <div style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">Tujuan:
                                        {{ $iz->tujuan_lokasi ?: '-' }}</div>
                                </td>
                                <td data-label="Status" style="text-align: center;">
                                    <span class="badge"
                                        style="background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                        {{ $statusBadge['label'] }}
                                    </span>
                                </td>
                                <td data-label="Petugas Piket" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    {{ $iz->nama_piket ?: 'Petugas Piket' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div
                style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                <i class="fas fa-ticket"
                    style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">Tidak Ada Riwayat e-Izin Keluar
                    Sekolah</div>
                <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 4px;">Putra/putri Anda tertib berada di lingkungan sekolah
                    selama jam pembelajaran.</div>
            </div>
        @endif
    </div>

    <!-- 4. Tabel Surat Permohonan Izin / Sakit Mandiri -->
    <div class="card"
        style="padding: 20px 22px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <div>
                <h3
                    style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-envelope-open-text text-primary"></i> Surat Permohonan Izin Tidak Masuk / Sakit Mandiri
                </h3>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                    Surat izin tidak hadir sekolah yang diajukan ke Wali Kelas dan tercatat resmi di basis data kehadiran
                    sekolah.
                </p>
            </div>
            <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700;">
                {{ $suratIzinList->count() }} Surat
            </span>
        </div>

        @if ($suratIzinList->isNotEmpty())
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Rentang Tanggal</th>
                            <th
                                style="min-width: 90px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Jenis Izin</th>
                            <th
                                style="min-width: 220px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Alasan / Keterangan Permohonan</th>
                            <th
                                style="min-width: 120px; text-align: center; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Status Verifikasi</th>
                            <th
                                style="min-width: 130px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suratIzinList as $si)
                            @php
                                $jenisRaw = strtolower($si->jenis ?? ($si->jenis_izin ?? 'izin'));
                                $jenisIzinLabel = str_contains($jenisRaw, 's') ? 'Sakit' : 'Izin';
                                $statusVal = strtolower($si->status ?? ($si->status_persetujuan ?? 'menunggu'));
                                $badgeIzin = match ($statusVal) {
                                    'disetujui' => [
                                        'label' => 'Disetujui',
                                        'bg' => 'rgba(16,185,129,0.15)',
                                        'color' => '#10b981',
                                    ],
                                    'ditolak' => [
                                        'label' => 'Ditolak',
                                        'bg' => 'rgba(239,68,68,0.15)',
                                        'color' => '#ef4444',
                                    ],
                                    default => [
                                        'label' => 'Menunggu Wali Kelas',
                                        'bg' => 'rgba(245,158,11,0.15)',
                                        'color' => '#f59e0b',
                                    ],
                                };
                                $alasanText = $si->alasan ?? ($si->keterangan ?? '-');
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="Rentang Tanggal">
                                    <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-sm, 0.8125rem);">
                                        {{ \Carbon\Carbon::parse($si->tanggal_mulai)->translatedFormat('d M Y') }}
                                        @if (!empty($si->tanggal_selesai) && $si->tanggal_mulai !== $si->tanggal_selesai)
                                            &bull;
                                            {{ \Carbon\Carbon::parse($si->tanggal_selesai)->translatedFormat('d M Y') }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Jenis Izin" style="text-align: center;">
                                    <span class="badge"
                                        style="background: {{ $jenisIzinLabel === 'Sakit' ? 'rgba(139,92,246,0.15)' : 'rgba(59,130,246,0.15)' }}; color: {{ $jenisIzinLabel === 'Sakit' ? '#8b5cf6' : 'var(--primary, #3b82f6)' }}; font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                        {{ $jenisIzinLabel }}
                                    </span>
                                </td>
                                <td data-label="Alasan / Keterangan"
                                    style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color);">
                                    {{ $alasanText }}
                                </td>
                                <td data-label="Status Verifikasi" style="text-align: center;">
                                    <span class="badge"
                                        style="background: {{ $badgeIzin['bg'] }}; color: {{ $badgeIzin['color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 8px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                        {{ $badgeIzin['label'] }}
                                    </span>
                                </td>
                                <td data-label="Wali Kelas" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                    {{ $si->disetujui_oleh ?: $waliKelas['nama'] ?? 'Wali Kelas' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div
                style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
                <i class="fas fa-envelope-circle-check"
                    style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">Belum Ada Surat Permohonan Izin
                    / Sakit</div>
            </div>
        @endif
    </div>
@endsection
