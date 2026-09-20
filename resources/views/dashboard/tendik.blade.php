@extends('layouts.dashboard')

@section('title', 'Dashboard Tenaga Kependidikan — SAE')
@section('dash_title', ($isKepalaTas ?? false) ? 'Portal Kepala Tenaga Administrasi Sekolah (TAS)' : 'Portal Tenaga Kependidikan & Administrasi')

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

        $sessionUser = session('user');
        $userName = is_array($sessionUser) ? ($sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Tenaga Kependidikan')) : ($sessionUser->name ?? ($sessionUser->nama ?? 'Tenaga Kependidikan'));
        $fotoUrl = $fotoUrl ?? (is_array($sessionUser) ? ($sessionUser['foto_url'] ?? null) : ($sessionUser->foto_url ?? null));
        if (!$fotoUrl) {
            $uId = is_array($sessionUser) ? ($sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)) : ($sessionUser->pengguna_id ?? ($sessionUser->id ?? null));
            if ($uId) {
                $fotoUrl = \App\Models\User::where('pengguna_id', $uId)->first()?->foto_url;
            }
        }
        if (!$fotoUrl && !empty($gtk?->ptk_id)) {
            $fotoUrl = \App\Models\User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        $activeSection = $viewSection ?? 'persuratan';

        // Daftar semua bidang tugas untuk switcher Kepala TAS
        $allDomains = [
            'kepala-tas' => ['label' => 'Ikhtisar Koordinator', 'icon' => 'fas fa-landmark', 'color' => '#10b981'],
            'kesiswaan' => ['label' => 'Kesiswaan', 'icon' => 'fas fa-user-graduate', 'color' => '#3b82f6'],
            'kepegawaian' => ['label' => 'Kepegawaian', 'icon' => 'fas fa-id-badge', 'color' => '#8b5cf6'],
            'sarpras' => ['label' => 'Sarpras & Aset', 'icon' => 'fas fa-building', 'color' => '#f59e0b'],
            'laboran' => ['label' => 'Laboratorium', 'icon' => 'fas fa-flask', 'color' => '#06b6d4'],
            'perpustakaan' => ['label' => 'Perpustakaan', 'icon' => 'fas fa-book-open', 'color' => '#ec4899'],
            'teknisi' => ['label' => 'Teknisi IT', 'icon' => 'fas fa-network-wired', 'color' => '#6366f1'],
            'keamanan' => ['label' => 'Keamanan & Tamu', 'icon' => 'fas fa-shield-halved', 'color' => '#ef4444'],
            'persuratan' => ['label' => 'Persuratan & Arsip', 'icon' => 'fas fa-envelope-open-text', 'color' => '#14b8a6'],
            'penjaga' => ['label' => 'Fasilitas & Penjaga', 'icon' => 'fas fa-broom', 'color' => '#84cc16'],
        ];
    @endphp

    <!-- Welcome Banner -->
    <div class="dash-banner"
        style="margin-bottom: 20px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            @if ($fotoUrl)
                <!-- Pasfoto Tenaga Kependidikan -->
                <div class="dash-banner-foto" style="flex-shrink: 0; width: 88px; height: 118px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; box-shadow: none;">
                    <img src="{{ $fotoUrl }}" alt="{{ $userName }}" 
                         style="max-width: 100%; max-height: 100%; width: auto; height: 100%; object-fit: contain; border-radius: 10px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.18));"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <h2
                    style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                    <span
                        style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">{{ $greeting }}</span>
                    {{ $userName }}! 📁
                </h2>
                <div
                    style="display: flex; align-items: center; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    <span title="Penugasan / Bidang Tugas">
                        <i class="fas fa-briefcase text-primary me-1"></i>
                        <strong style="color: var(--text-color);">{{ $bagianTugas ?? 'Tenaga Administrasi Sekolah' }}</strong>
                    </span>
                    @if (!empty(session('user.nip', $gtk->nip ?? null)))
                        <span title="Nomor Induk Pegawai (NIP)">
                            <i class="fas fa-id-badge text-warning me-1"></i>
                            <strong style="color: var(--text-color);">{{ session('user.nip', $gtk->nip) }}</strong>
                        </span>
                    @endif
                    @if(!empty($gtk->status_kepegawaian_id_str))
                        <span title="Status Kepegawaian">
                            <i class="fas fa-id-card-clip text-info me-1"></i>
                            <span class="badge badge-info" style="font-size: 0.72rem; padding: 2px 7px;">{{ $gtk->status_kepegawaian_id_str }}</span>
                        </span>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #3b82f6;">
                        <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                    </div>
                    @if($isKepalaTas ?? false)
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #10b981;">
                            <i class="fas fa-crown"></i> Kepala TAS / Koordinator Tata Usaha
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dynamic Action Buttons Berdasarkan Bidang -->
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if ($activeSection === 'kesiswaan')
                <a href="{{ route('dashboard.peserta-didik-aktif.index') }}" class="btn btn-primary"
                    style="background: #3b82f6; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-graduate"></i> Buku Induk Siswa
                </a>
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-outline"
                    style="padding: 9px 14px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-layer-group"></i> Rombongan Belajar
                </a>
            @elseif ($activeSection === 'kepegawaian')
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-primary"
                    style="background: #8b5cf6; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-id-badge"></i> Data Tendik Aktif
                </a>
                <a href="{{ route('dashboard.guru-aktif.index') }}" class="btn btn-outline"
                    style="padding: 9px 14px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-chalkboard-user"></i> Direktori Guru
                </a>
            @elseif ($activeSection === 'sarpras')
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-primary"
                    style="background: #f59e0b; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-door-open"></i> Pemetaan Ruang &amp; Kelas
                </a>
            @elseif ($activeSection === 'laboran')
                <a href="{{ route('dashboard.rombel.index') }}" class="btn btn-primary"
                    style="background: #06b6d4; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-calendar-check"></i> Jadwal Praktikum
                </a>
            @elseif ($activeSection === 'keamanan')
                <a href="{{ route('presensi.scan') }}" target="_blank" class="btn btn-primary"
                    style="background: #ef4444; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-qrcode"></i> Buka Kiosk Gate Presensi
                </a>
            @elseif ($activeSection === 'teknisi')
                <a href="{{ route('presensi.scan') }}" target="_blank" class="btn btn-primary"
                    style="background: #6366f1; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-desktop"></i> Monitor Gerbang RFID
                </a>
            @else
                @if (
                    \App\Models\RolePermission::canAccess('tendik', 'menu_persuratan') ||
                    \App\Models\RolePermission::canAccess('tendik', 'menu_surat_keluar') ||
                    \App\Models\RolePermission::canAccess('tendik', 'menu_berkas_peserta_didik'))
                    <a href="{{ route('dashboard.persuratan.index') }}" class="btn btn-primary"
                        style="background: #10b981; border: none; padding: 9px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-plus"></i> Catat Surat / Dokumen
                    </a>
                @endif
                <a href="{{ route('dashboard.tendik-aktif.index') }}" class="btn btn-outline"
                    style="padding: 9px 14px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-address-book"></i> Direktori Pegawai
                </a>
            @endif
        </div>
    </div>

    <!-- Domain / Bidang Switcher Tabs -->
    @if ($isKepalaTas ?? false)
        <div class="dash-domain-switcher" style="margin-bottom: 22px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fas fa-sliders text-primary me-1"></i> Navigasi Bidang Kerja Tata Administrasi Sekolah (TAS)
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                    Mode Supervisi Koordinator
                </span>
            </div>
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch;">
                @foreach ($allDomains as $slug => $domain)
                    @php $isActive = ($activeSection === $slug); @endphp
                    <a href="{{ route('dashboard.tendik', ['bidang' => $slug]) }}"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: {{ $isActive ? '700' : '600' }}; text-decoration: none; white-space: nowrap; transition: all 0.2s ease;
                        {{ $isActive 
                            ? 'background: ' . $domain['color'] . '; color: #ffffff; box-shadow: 0 4px 12px ' . $domain['color'] . '40; border: 1px solid ' . $domain['color'] . ';' 
                            : 'background: var(--card-bg, #ffffff); color: var(--text-color); border: 1px solid var(--border-color);' }}">
                        <i class="{{ $domain['icon'] }}" style="{{ $isActive ? 'color: #ffffff;' : 'color: ' . $domain['color'] . ';' }}"></i>
                        <span>{{ $domain['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @elseif (isset($userDuties) && count($userDuties) > 1)
        <!-- Multi-Duty Switcher untuk Tendik dengan lebih dari 1 tugas tambahan -->
        <div class="dash-domain-switcher" style="margin-bottom: 20px;">
            <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                <i class="fas fa-layer-group text-primary me-1"></i> Penugasan Aktif Anda
            </div>
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px;">
                @foreach ($userDuties as $ud)
                    @php
                        $codeMap = [
                            'KEPALA_TAS' => 'kepala-tas',
                            'STAF_KESISWAAN' => 'kesiswaan',
                            'STAF_KEPEGAWAIAN' => 'kepegawaian',
                            'STAF_SARPRAS' => 'sarpras',
                            'LABORAN' => 'laboran',
                            'PUSTAKAWAN' => 'perpustakaan',
                            'TEKNISI_IT' => 'teknisi',
                            'SATPAM' => 'keamanan',
                            'PENJAGA_SEKOLAH' => 'penjaga',
                            'STAF_PERSURATAN' => 'persuratan',
                        ];
                        $targetSlug = $codeMap[$ud->kode] ?? 'persuratan';
                        $isActive = ($activeSection === $targetSlug);
                        $info = $allDomains[$targetSlug] ?? ['label' => $ud->nama, 'icon' => 'fas fa-briefcase', 'color' => '#10b981'];
                    @endphp
                    <a href="{{ route('dashboard.tendik', ['bidang' => $targetSlug]) }}"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: {{ $isActive ? '700' : '600' }}; text-decoration: none; white-space: nowrap;
                        {{ $isActive 
                            ? 'background: ' . $info['color'] . '; color: #ffffff; border: 1px solid ' . $info['color'] . ';' 
                            : 'background: var(--card-bg, #ffffff); color: var(--text-color); border: 1px solid var(--border-color);' }}">
                        <i class="{{ $info['icon'] }}"></i>
                        <span>{{ $ud->nama }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Dynamic Section Content by Domain -->
    @include('dashboard.tendik.section-' . $activeSection)

@endsection
