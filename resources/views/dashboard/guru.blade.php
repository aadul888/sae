@extends('layouts.dashboard')

@section('title', 'Guru Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Portal Guru & Pendidik')

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
        $userName = is_array($sessionUser)
            ? $sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Guru')
            : $sessionUser->name ?? ($sessionUser->nama ?? 'Guru');
        $fotoUrl =
            $fotoUrl ?? (is_array($sessionUser) ? $sessionUser['foto_url'] ?? null : $sessionUser->foto_url ?? null);
        if (!$fotoUrl) {
            $uId = is_array($sessionUser)
                ? $sessionUser['pengguna_id'] ?? ($sessionUser['id'] ?? null)
                : $sessionUser->pengguna_id ?? ($sessionUser->id ?? null);
            if ($uId) {
                $fotoUrl = \App\Models\User::where('pengguna_id', $uId)->first()?->foto_url;
            }
        }
        if (!$fotoUrl && !empty($gtk?->ptk_id)) {
            $fotoUrl = \App\Models\User::where('ptk_id', $gtk->ptk_id)->whereNotNull('foto_path')->first()?->foto_url;
        }

        $mapelUtama = $mapelUtama ?? null;
        if (empty($mapelUtama)) {
            if (!empty($gtk?->ptk_id)) {
                $topM = \DB::table('pembelajaran')
                    ->where('ptk_id', $gtk->ptk_id)
                    ->select('nama_mata_pelajaran', \DB::raw('SUM(jam_mengajar_per_minggu) as total_jam'))
                    ->groupBy('nama_mata_pelajaran')
                    ->orderByDesc('total_jam')
                    ->first();
                $mapelUtama = $topM?->nama_mata_pelajaran;
            }
            $mapelUtama = $mapelUtama ?: $gtk?->bidang_studi_terakhir ?? session('user.mapel', 'Mata Pelajaran');
        }
    @endphp

    @php
        $isViewingAsSuperadmin = in_array($userRole ?? session('user.role'), ['admin', 'tendik'], true);
    @endphp

    @if ($isViewingAsSuperadmin)
        <!-- Banner Mode Superadmin / Tendik: Pemantauan Dashboard Guru -->
        <div class="dash-banner"
            style="margin-bottom: 16px; padding: 12px 20px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="badge badge-primary" style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700;">
                    <i
                        class="fas {{ ($userRole ?? session('user.role')) === 'admin' ? 'fa-crown' : 'fa-id-badge' }} me-1"></i>
                    Mode {{ ($userRole ?? session('user.role')) === 'admin' ? 'Superadmin' : 'Tendik' }}
                </span>
                <span style="font-size: var(--text-base, 0.875rem); color: var(--text-color); font-weight: 600;">
                    Memantau Dashboard Guru: <strong class="text-primary">{{ $userName }}</strong>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                @if (isset($allGtkList) && $allGtkList->isNotEmpty())
                    <form method="GET" action="{{ route('dashboard.guru') }}"
                        style="margin: 0; display: flex; align-items: center; gap: 8px;">
                        <label for="selectGtkPreview" style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 0;">Pilih
                            Guru:</label>
                        <select id="selectGtkPreview" name="ptk_id" onchange="this.form.submit()" class="per-page-select"
                            style="font-size: var(--text-sm, 0.8125rem); min-width: 220px; padding: 5px 10px; border-radius: var(--radius-md, 8px); background: var(--card-bg, #1e293b); color: var(--text-color); border: 1px solid var(--border-color);">
                            @foreach ($allGtkList as $g)
                                <option value="{{ $g->ptk_id }}" {{ ($ptkId ?? null) === $g->ptk_id ? 'selected' : '' }}>
                                    {{ $g->nama }} {{ $g->nip ? '(' . $g->nip . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
                <a href="{{ route('dashboard.' . ($userRole ?? session('user.role', 'admin'))) }}" class="btn btn-outline"
                    style="padding: 6px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
                    {{ ucfirst($userRole ?? session('user.role', 'admin')) }}
                </a>
            </div>
        </div>
    @endif

    <!-- Welcome Banner -->
    <div class="dash-banner"
        style="background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(6,182,212,0.1) 100%); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            @if ($fotoUrl)
                <!-- Pasfoto Guru / Pendidik -->
                <div class="dash-banner-foto"
                    style="flex-shrink: 0; width: 88px; height: 118px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; box-shadow: none;">
                    <img src="{{ $fotoUrl }}" alt="{{ $userName }}"
                        style="max-width: 100%; max-height: 100%; width: auto; height: 100%; object-fit: contain; border-radius: var(--radius-lg, 12px); filter: drop-shadow(0 4px 12px rgba(0,0,0,0.18));"
                        onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <h2
                    style="font-size: var(--text-2xl, 1.5rem); font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                    <span
                        style="display: block; font-size: var(--text-base, 0.875rem); font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">{{ $greeting }}</span>
                    {{ $userName }}! 📚
                </h2>
                <div
                    style="display: flex; align-items: center; gap: 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    @if (!empty($mapelUtama) && $mapelUtama !== '-')
                        <span title="Mata Pelajaran Utama">
                            <i class="fas fa-book-open text-primary me-1"></i>
                            <strong style="color: var(--text-color);">{{ $mapelUtama }}</strong>
                        </span>
                    @endif
                    @if (!empty(session('user.nip', $gtk?->nip ?? null)))
                        <span title="Nomor Induk Pegawai (NIP)">
                            <i class="fas fa-id-badge text-warning me-1"></i>
                            <strong style="color: var(--text-color);">{{ session('user.nip', $gtk?->nip) }}</strong>
                        </span>
                    @endif
                    @if (!empty($gtk?->status_kepegawaian_id_str))
                        <span title="Status Kepegawaian">
                            <i class="fas fa-id-card-clip text-info me-1"></i>
                            <span class="badge badge-info"
                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">{{ $gtk?->status_kepegawaian_id_str }}</span>
                        </span>
                    @endif
                </div>
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: var(--radius-2xl, 20px); font-size: var(--text-xs, 0.75rem); font-weight: 700; color: #10b981;">
                    <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </div>
            </div>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.jadwal-pelajaran.index') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Jadwal Mengajar">
                <i class="fas fa-calendar-days text-primary"></i>
            </a>
            @if (\App\Models\RolePermission::canAccess('guru', 'menu_presensi_mengajar'))
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); background: linear-gradient(135deg, #10b981, #059669); text-decoration: none;"
                    title="Catat Presensi Mengajar">
                    <i class="fas fa-calendar-check"></i>
                </a>
            @endif
        </div>
    </div>

    @if (!empty($waliInfo) && !empty($pipCountdownWali))
        <!-- Banner Hitungan Mundur Batas Waktu Pengusulan PIP Kelas Binaan -->
        @php
            $infoPipWali = $pipCountdownWali;
            $isClosedPip = $infoPipWali['is_closed'] ?? false;
            $isManualPip = $infoPipWali['is_locked'] ?? false;
            $isExpPip = $infoPipWali['is_expired'] ?? false;
            $cdWali = $infoPipWali['countdown'] ?? ['days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];
            $judulWali =
                $infoPipWali['judul_usulan'] ??
                'Usulan PIP Kelas ' . $waliInfo->nama . ' — ' . ($infoPipWali['fase_label'] ?? 'Fase 1');
        @endphp
        <div class="card pip-countdown-card" id="pipWaliDashboardCountdown"
            data-deadline="{{ $infoPipWali['deadline_iso'] ?? '' }}" data-locked="{{ $isManualPip ? '1' : '0' }}"
            data-autolock="{{ $infoPipWali['kunci_otomatis'] ?? true ? '1' : '0' }}"
            data-closed="{{ $isClosedPip ? '1' : '0' }}"
            style="margin-bottom: 22px; padding: 16px 20px; border-radius: var(--radius-xl, 16px); border: 1.5px solid {{ $isClosedPip ? 'rgba(239,68,68,0.35)' : 'rgba(99,102,241,0.35)' }}; background: {{ $isClosedPip ? 'linear-gradient(135deg, rgba(239,68,68,0.06) 0%, rgba(245,158,11,0.04) 100%)' : 'linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(16,185,129,0.05) 100%)' }};">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px;">
                    <div
                        style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: {{ $isClosedPip ? 'rgba(239,68,68,0.14)' : 'rgba(99,102,241,0.14)' }}; color: {{ $isClosedPip ? '#ef4444' : 'var(--primary)' }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                        <i class="fas {{ $isClosedPip ? 'fa-lock' : 'fa-hourglass-half fa-spin-pulse' }}"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                            <span style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--text-color);">
                                {{ $judulWali }} &bull; Kelas {{ $waliInfo->nama }}
                            </span>
                            <span class="badge badge-primary"
                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; font-weight: 700; border-radius: var(--radius-2xl, 20px);">
                                <i class="fas fa-layer-group me-1"></i> {{ $infoPipWali['fase_label'] ?? 'Fase 1' }}
                            </span>
                            @if (!empty($infoPipWali['tahun_pelajaran']))
                                <span class="badge badge-outline" style="font-size: var(--text-xs, 0.75rem); padding: 2px 7px;">
                                    <i class="fas fa-graduation-cap me-1"></i> TP {{ $infoPipWali['tahun_pelajaran'] }}
                                </span>
                            @endif
                            <span id="pipWaliCdBadge" class="badge {{ $isClosedPip ? 'badge-danger' : 'badge-success' }}"
                                style="font-size: var(--text-xs, 0.75rem); padding: 2px 8px; font-weight: 700; border-radius: var(--radius-2xl, 20px);">
                                @if ($isManualPip)
                                    <i class="fas fa-lock me-1"></i> Terkunci Manual
                                @elseif ($isExpPip)
                                    <i class="fas fa-clock-rotate-left me-1"></i> Batas Waktu Berakhir
                                @else
                                    <i class="fas fa-lock-open me-1"></i> Pengusulan Dibuka
                                @endif
                            </span>
                        </div>
                        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                            Batas Akhir: <strong>{{ $infoPipWali['deadline_formatted'] }}</strong>
                            @if (!empty($infoPipWali['pesan_pengumuman']))
                                &bull; <span style="font-style: italic;">"{{ $infoPipWali['pesan_pengumuman'] }}"</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    @if ($isClosedPip)
                        <div
                            style="padding: 6px 14px; border-radius: var(--radius-md, 8px); background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; font-weight: 800; font-size: var(--text-sm, 0.8125rem);">
                            <i class="fas fa-lock me-1"></i> PENGUSULAN DITUTUP
                        </div>
                    @else
                        <div class="pip-countdown-boxes" style="display: flex; align-items: center; gap: 5px;">
                            <div
                                style="text-align: center; min-width: 48px; padding: 5px 6px; border-radius: var(--radius-md, 8px); background: var(--card-bg, #1e293b); border: 1px solid rgba(99,102,241,0.25);">
                                <div id="pipWaliCdDays"
                                    style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--primary); font-family: monospace; line-height: 1;">
                                    {{ sprintf('%02d', $cdWali['days'] ?? 0) }}
                                </div>
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Hari</div>
                            </div>
                            <span style="font-weight: 800; color: var(--primary); opacity: 0.6;">:</span>
                            <div
                                style="text-align: center; min-width: 48px; padding: 5px 6px; border-radius: var(--radius-md, 8px); background: var(--card-bg, #1e293b); border: 1px solid rgba(99,102,241,0.25);">
                                <div id="pipWaliCdHours"
                                    style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--primary); font-family: monospace; line-height: 1;">
                                    {{ sprintf('%02d', $cdWali['hours'] ?? 0) }}
                                </div>
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Jam</div>
                            </div>
                            <span style="font-weight: 800; color: var(--primary); opacity: 0.6;">:</span>
                            <div
                                style="text-align: center; min-width: 48px; padding: 5px 6px; border-radius: var(--radius-md, 8px); background: var(--card-bg, #1e293b); border: 1px solid rgba(99,102,241,0.25);">
                                <div id="pipWaliCdMinutes"
                                    style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--primary); font-family: monospace; line-height: 1;">
                                    {{ sprintf('%02d', $cdWali['minutes'] ?? 0) }}
                                </div>
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Menit</div>
                            </div>
                            <span style="font-weight: 800; color: var(--primary); opacity: 0.6;">:</span>
                            <div
                                style="text-align: center; min-width: 48px; padding: 5px 6px; border-radius: var(--radius-md, 8px); background: var(--card-bg, #1e293b); border: 1px solid rgba(16,185,129,0.3);">
                                <div id="pipWaliCdSeconds"
                                    style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: #10b981; font-family: monospace; line-height: 1;">
                                    {{ sprintf('%02d', $cdWali['seconds'] ?? 0) }}
                                </div>
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                    Detik</div>
                            </div>
                        </div>
                    @endif

                    <a href="{{ route('dashboard.pip.index', ['tab' => 'usulan']) }}" class="btn btn-primary btn-sm"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-md, 8px); text-decoration: none;">
                        <i class="fas fa-hand-holding-dollar me-1"></i> Buka Usulan PIP
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats Counter -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $stats['hari_efektif_bulan_ini'] ?? 0 }} Hari
                </div>
                <div class="dash-stat-label">HEB Bulan Ini (Jalan: {{ $stats['hari_efektif_berjalan'] ?? 0 }})</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_jam_ajar'] }} JP</div>
                <div class="dash-stat-label">Beban Ajar Mingguan</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(14,165,233,0.15); color: #0ea5e9;">
                <i class="fas fa-chalkboard"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['kelas_diampu'] }} Kelas</div>
                <div class="dash-stat-label">Rombel Diampu</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_peserta_didik'] }}</div>
                <div class="dash-stat-label">Peserta Didik Terdaftar</div>
            </div>
        </div>
    </div>





    @php
        $isLiburHariIni = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false);
    @endphp
    @if ($isLiburHariIni || !empty($agendaHariIni))
        <!-- Banner Peringatan / Info Kalender Pendidikan Hari Ini -->
        <div class="card"
            style="padding: 12px 18px; margin-bottom: 20px; border-radius: var(--radius-lg, 12px); border: 1px solid {{ $isLiburHariIni ? 'rgba(239,68,68,0.3)' : 'rgba(99,102,241,0.3)' }}; background: {{ $isLiburHariIni ? 'rgba(239,68,68,0.06)' : 'rgba(99,102,241,0.06)' }}; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: {{ $isLiburHariIni ? 'rgba(239,68,68,0.15)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isLiburHariIni ? '#ef4444' : 'var(--primary)' }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                    <i class="fas {{ $isLiburHariIni ? 'fa-umbrella-beach' : 'fa-calendar-star' }}"></i>
                </div>
                <div>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                        @if ($isLiburHariIni)
                            <span style="color: #ef4444;">Perhatian: Hari Ini Libur Sekolah</span>
                            <span class="badge"
                                style="background: #ef4444; color: #fff; font-size: var(--text-xs, 0.75rem); padding: 2px 7px; margin-left: 6px;">KBM
                                Off</span>
                        @else
                            <span style="color: var(--primary);">Agenda Khusus Kalender Pendidikan</span>
                        @endif
                    </div>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        @if (!empty($agendaHariIni))
                            <strong>{{ $agendaHariIni->nama_agenda }}</strong>
                            @if ($agendaHariIni->keterangan)
                                &mdash; {{ $agendaHariIni->keterangan }}
                            @endif
                        @else
                            {{ $statusHariIni['keterangan'] ?? 'Kegiatan KBM reguler disesuaikan dengan kalender pendidikan.' }}
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline"
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem);">
                    <i class="fas fa-calendar-alt me-1"></i> Detail Kalender
                </a>
            </div>
        </div>
    @endif

    <!-- Schedule Today -->
    <div class="card">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0;">
                <i class="fas fa-calendar-day text-success"></i> Jadwal Mengajar Hari Ini
            </h3>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"><i class="fas fa-calendar"></i>
                    {{ date('l, d F Y') }}</span>
                <a href="{{ route('dashboard.jadwal-pelajaran.index') }}" class="btn btn-outline"
                    style="padding: 5px 12px; font-size: var(--text-sm, 0.8125rem); text-decoration: none; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-calendar-days me-1"></i> Semua Jadwal
                </a>
            </div>
        </div>

        @if (empty($isJadwalDiberlakukan))
            <div
                style="text-align: center; padding: 26px 16px; background: rgba(245,158,11,0.06); border: 1.5px dashed rgba(245,158,11,0.3); border-radius: var(--radius-lg, 12px); margin-bottom: 12px;">
                <i class="fas fa-clock"
                    style="font-size: var(--text-3xl, 1.875rem); color: #f59e0b; margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                <h4 style="font-size: var(--text-md, 1rem); font-weight: 700; color: #f59e0b; margin-bottom: 4px;">Jadwal KBM Masih
                    Berstatus Draft</h4>
                <p
                    style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin: 0; max-width: 520px; display: inline-block;">
                    Jadwal KBM semester ini sedang dalam tahap penyusunan dan finalisasi oleh Tim Kurikulum. Jadwal mengajar
                    harian Anda akan tampil otomatis di sini setelah resmi diberlakukan.
                </p>
            </div>
        @elseif (empty($jadwal_hari_ini) || count($jadwal_hari_ini) === 0)
            <div
                style="text-align: center; padding: 30px 16px; background: rgba(255,255,255,0.02); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px); margin-bottom: 12px;">
                <i class="fas fa-mug-hot"
                    style="font-size: var(--text-3xl, 1.875rem); color: var(--primary); margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                <h4 style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Tidak Ada
                    Jadwal Mengajar Hari Ini</h4>
                <p
                    style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin: 0; max-width: 540px; display: inline-block;">
                    Anda tidak memiliki jadwal tatap muka/mengajar di kelas pada hari {{ $hariIni ?? 'ini' }}. Manfaatkan
                    waktu untuk evaluasi pembelajaran, pembuatan perangkat ajar, atau kegiatan tugas tambahan.
                </p>
            </div>
        @else
            <!-- Desktop Table View -->
            <div class="d-none d-md-block" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: var(--text-base, 0.875rem);">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted);">
                            <th style="padding: 12px 14px;">Waktu</th>
                            <th style="padding: 12px 14px;">Rombel / Kelas</th>
                            <th style="padding: 12px 14px;">Materi / Mapel</th>
                            <th style="padding: 12px 14px;">Ruangan</th>
                            <th style="padding: 12px 14px;">Status</th>
                            <th style="padding: 12px 14px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jadwal_hari_ini as $j)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td
                                    style="padding: 14px; font-weight: 700; color: var(--text-color); white-space: nowrap;">
                                    {{ $j['jam'] }}</td>
                                <td style="padding: 14px;"><span
                                        style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 4px 10px; border-radius: var(--radius-sm, 6px); font-weight: 600;">{{ $j['kelas'] }}</span>
                                </td>
                                <td style="padding: 14px; color: var(--text-color);">{{ $j['mapel'] }}</td>
                                <td style="padding: 14px; color: var(--text-muted);"><i class="fas fa-location-dot"></i>
                                    {{ $j['ruang'] }}</td>
                                <td style="padding: 14px;">
                                    @if ($j['status'] === 'Berlangsung')
                                        <span
                                            style="background: rgba(16,185,129,0.15); color: #10b981; padding: 4px 10px; border-radius: var(--radius-sm, 6px); font-weight: 600; font-size: var(--text-sm, 0.8125rem);">
                                            <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                                        </span>
                                    @elseif ($j['status'] === 'Selesai')
                                        <span
                                            style="background: rgba(100,116,139,0.15); color: #94a3b8; padding: 4px 10px; border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                                            <i class="fas fa-check"></i> {{ $j['status'] }}
                                        </span>
                                    @else
                                        <span
                                            style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 4px 10px; border-radius: var(--radius-sm, 6px); font-size: var(--text-sm, 0.8125rem);">
                                            {{ $j['status'] }}
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 14px; text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        @if (\App\Models\RolePermission::canAccess('guru', 'menu_presensi_mengajar'))
                                            <a href="{{ route('dashboard.presensi-mengajar.index') }}"
                                                class="btn btn-primary" style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem);"
                                                title="Presensi Mengajar">
                                                <i class="fas fa-clipboard-user"></i> Presensi
                                            </a>
                                        @endif
                                        @if (\App\Models\RolePermission::canAccess('guru', 'menu_agenda_kbm'))
                                            <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-outline"
                                                style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem);" title="Jurnal KBM">
                                                <i class="fas fa-pen-to-square"></i> Jurnal
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List View -->
            <div class="d-md-none" style="display: flex; flex-direction: column; gap: 12px;">
                @foreach ($jadwal_hari_ini as $j)
                    <div
                        style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: var(--radius-lg, 12px); padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                <i class="fas fa-clock text-primary"></i> {{ $j['jam'] }}
                            </span>
                            @if ($j['status'] === 'Berlangsung')
                                <span
                                    style="background: rgba(16,185,129,0.15); color: #10b981; padding: 3px 8px; border-radius: var(--radius-sm, 6px); font-weight: 600; font-size: var(--text-xs, 0.75rem);">
                                    <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                                </span>
                            @elseif ($j['status'] === 'Selesai')
                                <span
                                    style="background: rgba(100,116,139,0.15); color: #94a3b8; padding: 3px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem);">
                                    <i class="fas fa-check"></i> {{ $j['status'] }}
                                </span>
                            @else
                                <span
                                    style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 3px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem);">
                                    {{ $j['status'] }}
                                </span>
                            @endif
                        </div>

                        <div>
                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-md, 1rem);">
                                {{ $j['mapel'] }}
                            </div>
                            <div
                                style="display: flex; align-items: center; gap: 10px; margin-top: 4px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                <span
                                    style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 2px 6px; border-radius: var(--radius-xs, 4px); font-weight: 600;">{{ $j['kelas'] }}</span>
                                <span><i class="fas fa-location-dot"></i> {{ $j['ruang'] }}</span>
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px; margin-top: 2px;">
                            @if (\App\Models\RolePermission::canAccess('guru', 'menu_presensi_mengajar'))
                                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-primary"
                                    style="flex: 1; justify-content: center; padding: 8px 10px; font-size: var(--text-sm, 0.8125rem);">
                                    <i class="fas fa-clipboard-user"></i> Presensi
                                </a>
                            @endif
                            @if (\App\Models\RolePermission::canAccess('guru', 'menu_agenda_kbm'))
                                <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-outline"
                                    style="flex: 1; justify-content: center; padding: 8px 10px; font-size: var(--text-sm, 0.8125rem);">
                                    <i class="fas fa-pen-to-square"></i> Jurnal KBM
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    @if (!empty($waliInfo) && !empty($pipCountdownWali) && !empty($pipCountdownWali['deadline_iso']))
        <script>
            (function() {
                var deadlineIso = @json($pipCountdownWali['deadline_iso']);
                var isLocked = {{ $pipCountdownWali['is_locked'] ?? false ? 'true' : 'false' }};
                if (!deadlineIso || isLocked) return;

                var target = new Date(deadlineIso).getTime();
                var dEl = document.getElementById('pipWaliCdDays');
                var hEl = document.getElementById('pipWaliCdHours');
                var mEl = document.getElementById('pipWaliCdMinutes');
                var sEl = document.getElementById('pipWaliCdSeconds');

                function tickWali() {
                    var now = new Date().getTime();
                    var diff = target - now;
                    if (diff <= 0) {
                        if (dEl) dEl.textContent = '00';
                        if (hEl) hEl.textContent = '00';
                        if (mEl) mEl.textContent = '00';
                        if (sEl) sEl.textContent = '00';
                        return;
                    }
                    var tot = Math.floor(diff / 1000);
                    var d = Math.floor(tot / 86400);
                    var h = Math.floor((tot % 86400) / 3600);
                    var m = Math.floor((tot % 3600) / 60);
                    var s = tot % 60;
                    if (dEl) dEl.textContent = String(d).padStart(2, '0');
                    if (hEl) hEl.textContent = String(h).padStart(2, '0');
                    if (mEl) mEl.textContent = String(m).padStart(2, '0');
                    if (sEl) sEl.textContent = String(s).padStart(2, '0');
                }
                setInterval(tickWali, 1000);
                tickWali();
            })();
        </script>
    @endif
@endpush
