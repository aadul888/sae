@extends('layouts.dashboard')

@section('title', 'Portal Tendik — SAE')
@section('dash_title', ($isKepalaTas ?? false) ? 'Portal TAS' : 'Portal Tendik')

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

        $activeSection = $viewSection ?? 'umum';

        // Daftar semua bidang tugas untuk switcher Kepala TAS & Tendik Multi-Tugas
        $allDomains = [
            'umum' => ['label' => 'Portal Umum', 'icon' => 'fas fa-gauge-high', 'color' => '#3b82f6'],
            'kepala-tas' => ['label' => 'Ikhtisar Koordinator', 'icon' => 'fas fa-landmark', 'color' => '#10b981'],
            'piket' => ['label' => 'Guru Piket', 'icon' => 'fas fa-clipboard-user', 'color' => '#8b5cf6'],
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

        <!-- Kontrol Dropdown Pindah Bidang Kerja (Standar Baku SAE: .dash-custom-dropdown) -->
        <div class="dash-banner-actions" style="display: flex; align-items: center;">
            @php
                // Kumpulkan opsi bidang kerja yang tersedia untuk user ini
                $dropdownOptions = [];
                $dropdownOptions['umum'] = [
                    'label' => 'Portal Umum (Semua Tendik)',
                    'short_label' => 'Portal Umum',
                    'icon'  => 'fas fa-gauge-high',
                    'color' => '#3b82f6',
                    'url'   => route('dashboard.tendik'),
                ];

                if (($isKepalaTas ?? false) || ($userRole ?? '') === 'admin' || (session('user.role') ?? '') === 'admin') {
                    foreach ($allDomains as $s => $d) {
                        if ($s === 'umum') continue;
                        $dropdownOptions[$s] = [
                            'label' => 'Bidang ' . $d['label'],
                            'short_label' => $d['label'],
                            'icon'  => $d['icon'],
                            'color' => $d['color'],
                            'url'   => route('dashboard.tendik', ['bidang' => $s]),
                        ];
                    }
                } elseif (isset($dutyCodes) && $dutyCodes->isNotEmpty()) {
                    foreach ($allDomains as $s => $d) {
                        if ($s === 'umum') continue;
                        $dutyKey = match ($s) {
                            'persuratan'   => 'STAF_PERSURATAN',
                            'kesiswaan'    => 'STAF_KESISWAAN',
                            'kepegawaian'  => 'STAF_KEPEGAWAIAN',
                            'sarpras'      => 'STAF_SARPRAS',
                            'laboran'      => 'LABORAN',
                            'perpustakaan' => 'PUSTAKAWAN',
                            'teknisi'      => 'TEKNISI_IT',
                            'keamanan'     => 'SATPAM',
                            'penjaga'      => 'PENJAGA_SEKOLAH',
                            'piket'        => 'GURU_PIKET',
                            default        => null,
                        };
                        if ($dutyKey && $dutyCodes->contains($dutyKey)) {
                            $dropdownOptions[$s] = [
                                'label' => 'Bidang ' . $d['label'],
                                'short_label' => $d['label'],
                                'icon'  => $d['icon'],
                                'color' => $d['color'],
                                'url'   => route('dashboard.tendik', ['bidang' => $s]),
                            ];
                        }
                    }
                }

                $activeOpt = $dropdownOptions[$activeSection] ?? $dropdownOptions['umum'];
            @endphp

            {{-- Dropdown Baku SAE (.dash-custom-dropdown) --}}
            @if (count($dropdownOptions) > 1)
                <div class="dash-custom-dropdown" style="min-width: 240px; max-width: 300px;">
                    <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                        <div class="custom-dropdown-trigger-label">
                            <i class="{{ $activeOpt['icon'] }} text-primary me-2" style="color: {{ $activeOpt['color'] }} !important;"></i>
                            <span>{{ $activeOpt['label'] }}</span>
                        </div>
                        <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
                    </button>
                    <div class="custom-dropdown-menu" role="listbox">
                        @foreach ($dropdownOptions as $s => $opt)
                            @php $isActive = ($activeSection === $s); @endphp
                            <a href="{{ $opt['url'] }}"
                                class="custom-dropdown-item {{ $isActive ? 'active' : '' }}"
                                style="text-decoration: none;">
                                <div class="dropdown-item-left">
                                    <i class="{{ $opt['icon'] }} text-primary me-2" style="color: {{ $opt['color'] }} !important; width: 18px; text-align: center;"></i>
                                    <span>{{ $opt['label'] }}</span>
                                </div>
                                @if ($isActive)
                                    <span class="badge badge-primary badge-sm ms-2"><i class="fas fa-check"></i></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Dynamic Section Content by Domain -->
    @include('dashboard.tendik.section-' . $activeSection)

@endsection
