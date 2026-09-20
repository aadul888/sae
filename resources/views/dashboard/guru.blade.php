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
        $userName = is_array($sessionUser) ? ($sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Guru')) : ($sessionUser->name ?? ($sessionUser->nama ?? 'Guru'));
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

        if (!isset($mapelUtama) || empty($mapelUtama)) {
            if (!empty($gtk?->ptk_id)) {
                $topM = \DB::table('pembelajaran')
                    ->where('ptk_id', $gtk->ptk_id)
                    ->select('nama_mata_pelajaran', \DB::raw('SUM(jam_mengajar_per_minggu) as total_jam'))
                    ->groupBy('nama_mata_pelajaran')
                    ->orderByDesc('total_jam')
                    ->first();
                $mapelUtama = $topM?->nama_mata_pelajaran;
            }
            $mapelUtama = $mapelUtama ?: ($gtk->bidang_studi_terakhir ?? session('user.mapel', 'Mata Pelajaran'));
        }
    @endphp
    <!-- Welcome Banner -->
    <div class="dash-banner" style="background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(6,182,212,0.1) 100%); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            @if ($fotoUrl)
                <!-- Pasfoto Guru / Pendidik -->
                <div class="dash-banner-foto" style="flex-shrink: 0; width: 88px; height: 118px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; box-shadow: none;">
                    <img src="{{ $fotoUrl }}" alt="{{ $userName }}" 
                         style="max-width: 100%; max-height: 100%; width: auto; height: 100%; object-fit: contain; border-radius: 10px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.18));"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                    <span
                        style="display: block; font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px;">{{ $greeting }}</span>
                    {{ $userName }}! 📚
                </h2>
                <div style="display: flex; align-items: center; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    @if (!empty($mapelUtama) && $mapelUtama !== '-')
                        <span title="Mata Pelajaran Utama">
                            <i class="fas fa-book-open text-primary me-1"></i>
                            <strong style="color: var(--text-color);">{{ $mapelUtama }}</strong>
                        </span>
                    @endif
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
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #10b981;">
                    <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </div>
            </div>
        </div>
        <div class="dash-banner-actions">
            @if (\App\Models\RolePermission::canAccess('guru', 'menu_presensi_mengajar'))
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-primary"
                    style="padding: 9px 16px; font-size: 0.85rem; background: linear-gradient(135deg, #10b981, #059669); text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check"></i> Catat Presensi Mengajar
                </a>
            @endif
        </div>
    </div>

    <!-- Stats Counter -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $stats['hari_efektif_bulan_ini'] ?? 0 }} Hari</div>
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

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.15rem; color: #10b981;">{{ $stats['status_presensi'] }}
                </div>
                <div class="dash-stat-label">Masuk: {{ $stats['presensi_masuk'] }}</div>
            </div>
        </div>
    </div>

    @php
        $isLiburHariIni = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false);
    @endphp
    @if ($isLiburHariIni || !empty($agendaHariIni))
        <!-- Banner Peringatan / Info Kalender Pendidikan Hari Ini -->
        <div class="card" style="padding: 12px 18px; margin-bottom: 20px; border-radius: 12px; border: 1px solid {{ $isLiburHariIni ? 'rgba(239,68,68,0.3)' : 'rgba(99,102,241,0.3)' }}; background: {{ $isLiburHariIni ? 'rgba(239,68,68,0.06)' : 'rgba(99,102,241,0.06)' }}; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: {{ $isLiburHariIni ? 'rgba(239,68,68,0.15)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isLiburHariIni ? '#ef4444' : 'var(--primary)' }}; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                    <i class="fas {{ $isLiburHariIni ? 'fa-umbrella-beach' : 'fa-calendar-star' }}"></i>
                </div>
                <div>
                    <div style="font-size: 0.88rem; font-weight: 700; color: var(--text-color);">
                        @if ($isLiburHariIni)
                            <span style="color: #ef4444;">Perhatian: Hari Ini Libur Sekolah</span>
                            <span class="badge" style="background: #ef4444; color: #fff; font-size: 0.7rem; padding: 2px 7px; margin-left: 6px;">KBM Off</span>
                        @else
                            <span style="color: var(--primary);">Agenda Khusus Kalender Pendidikan</span>
                        @endif
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        @if (!empty($agendaHariIni))
                            <strong>{{ $agendaHariIni->nama_agenda }}</strong> @if($agendaHariIni->keterangan) &mdash; {{ $agendaHariIni->keterangan }} @endif
                        @else
                            {{ $statusHariIni['keterangan'] ?? 'Kegiatan KBM reguler disesuaikan dengan kalender pendidikan.' }}
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <a href="{{ route('dashboard.kalender-pendidikan.index') }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.78rem;">
                    <i class="fas fa-calendar-alt me-1"></i> Detail Kalender
                </a>
            </div>
        </div>
    @endif

    <!-- Schedule Today -->
    <div class="card">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-color);">
                <i class="fas fa-calendar-day text-success"></i> Jadwal Mengajar Hari Ini
            </h3>
            <span style="font-size: 0.8rem; color: var(--text-muted);"><i class="fas fa-calendar"></i>
                {{ date('l, d F Y') }}</span>
        </div>

        <!-- Desktop Table View -->
        <div class="d-none d-md-block" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
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
                            <td style="padding: 14px; font-weight: 700; color: var(--text-color); white-space: nowrap;">
                                {{ $j['jam'] }}</td>
                            <td style="padding: 14px;"><span
                                    style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 4px 10px; border-radius: 6px; font-weight: 600;">{{ $j['kelas'] }}</span>
                            </td>
                            <td style="padding: 14px; color: var(--text-color);">{{ $j['mapel'] }}</td>
                            <td style="padding: 14px; color: var(--text-muted);"><i class="fas fa-location-dot"></i>
                                {{ $j['ruang'] }}</td>
                            <td style="padding: 14px;">
                                @if ($j['status'] === 'Berlangsung')
                                    <span
                                        style="background: rgba(16,185,129,0.15); color: #10b981; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.78rem;">
                                        <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                                    </span>
                                @else
                                    <span
                                        style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 4px 10px; border-radius: 6px; font-size: 0.78rem;">
                                        {{ $j['status'] }}
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 14px; text-align: right;">
                                @if (\App\Models\RolePermission::canAccess('guru', 'menu_agenda_kbm'))
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 0.78rem;">
                                        <i class="fas fa-pen-to-square"></i> Jurnal KBM
                                    </button>
                                @endif
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
                    style="background: rgba(255,255,255,0.02); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: var(--text-color); font-size: 0.88rem;">
                            <i class="fas fa-clock text-primary"></i> {{ $j['jam'] }}
                        </span>
                        @if ($j['status'] === 'Berlangsung')
                            <span
                                style="background: rgba(16,185,129,0.15); color: #10b981; padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 0.72rem;">
                                <i class="fas fa-spinner fa-spin"></i> {{ $j['status'] }}
                            </span>
                        @else
                            <span
                                style="background: rgba(255,255,255,0.05); color: var(--text-muted); padding: 3px 8px; border-radius: 6px; font-size: 0.72rem;">
                                {{ $j['status'] }}
                            </span>
                        @endif
                    </div>

                    <div>
                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.95rem;">
                            {{ $j['mapel'] }}
                        </div>
                        <div
                            style="display: flex; align-items: center; gap: 10px; margin-top: 4px; font-size: 0.78rem; color: var(--text-muted);">
                            <span
                                style="background: rgba(99,102,241,0.1); color: var(--primary); padding: 2px 6px; border-radius: 4px; font-weight: 600;">{{ $j['kelas'] }}</span>
                            <span><i class="fas fa-location-dot"></i> {{ $j['ruang'] }}</span>
                        </div>
                    </div>

                    @if (\App\Models\RolePermission::canAccess('guru', 'menu_agenda_kbm'))
                        <div style="margin-top: 2px;">
                            <button class="btn btn-outline"
                                style="width: 100%; justify-content: center; padding: 8px 12px; font-size: 0.8rem;">
                                <i class="fas fa-pen-to-square"></i> Jurnal KBM
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
