@extends('layouts.dashboard')

@section('title', 'Presensi Mengajar — SAE')
@section('dash_title', 'Presensi Mengajar')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Presensi Mengajar
                </h2>
                <p style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); margin: 0;">
                    Kehadiran KBM terintegrasi Jadwal, Rombel &amp; Mapel.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.presensi.kelas') }}" class="btn btn-outline"
                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem); text-decoration: none;"
                title="Presensi Kelas">
                <i class="fas fa-users-viewfinder"></i>
            </a>
            <div
                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: var(--radius-2xl, 20px); font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color);">
                <i class="fas fa-calendar-day text-primary"></i>
                <span>{{ $hariIni }}, {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d M Y') }}</span>
            </div>
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: var(--success, #10b981); padding: 10px 14px; border-radius: var(--radius-lg, 12px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); padding: 10px 14px; border-radius: var(--radius-lg, 12px); margin-bottom: 16px; font-size: var(--text-base, 0.875rem); display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- 3. Stat Grid Baku SAE (Minimalis & Compact) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-calendar-days"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--success, #10b981);">{{ number_format($stats['hari_efektif'] ?? 0) }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">Hari</span></div>
                <div class="dash-stat-label">HEB Bulan Ini (Jalan: {{ $stats['hari_efektif_berjalan'] ?? 0 }})</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_sesi'] ?? 0) }}</div>
                <div class="dash-stat-label">Total Sesi</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_hadir'] ?? 0) }}</div>
                <div class="dash-stat-label">Hadir</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_izin'] ?? 0) }}</div>
                <div class="dash-stat-label">Izin / Sakit</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-user-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_inval'] ?? 0) }}</div>
                <div class="dash-stat-label">Tugas / Inval</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.12); color: #a855f7;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_jp'] ?? 0) }} <span
                        style="font-size: var(--text-xs, 0.75rem); font-weight: 500;">JP</span></div>
                <div class="dash-stat-label">Beban JP</div>
            </div>
        </div>
    </div>

    @php
        $isHariLibur = ($statusHariIni['is_libur'] ?? false) || ($statusHariIni['libur_gtk'] ?? false);
    @endphp
    @if ($isHariLibur || !empty($agendaHariIni))
        <!-- Banner Peringatan / Info Kalender Pendidikan Hari Ini -->
        <div class="card"
            style="padding: 12px 18px; margin-bottom: 20px; border-radius: var(--radius-lg, 12px); border: 1px solid {{ $isHariLibur ? 'rgba(239,68,68,0.3)' : 'rgba(99,102,241,0.3)' }}; background: {{ $isHariLibur ? 'rgba(239,68,68,0.06)' : 'rgba(99,102,241,0.06)' }}; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div
                    style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: {{ $isHariLibur ? 'rgba(239,68,68,0.15)' : 'rgba(99,102,241,0.15)' }}; color: {{ $isHariLibur ? '#ef4444' : 'var(--primary)' }}; display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                    <i class="fas {{ $isHariLibur ? 'fa-umbrella-beach' : 'fa-calendar-day' }}"></i>
                </div>
                <div>
                    <div style="font-size: var(--text-base, 0.875rem); font-weight: 700; color: var(--text-color);">
                        @if ($isHariLibur)
                            <span style="color: var(--danger, #ef4444);">Perhatian: Hari Ini Libur Sekolah</span>
                            <span class="badge"
                                style="background: var(--danger, #ef4444); color: #fff; font-size: var(--text-xs, 0.75rem); padding: 2px 7px; margin-left: 6px;">KBM
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
                    style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem);" title="Detail Kalender Pendidikan">
                    <i class="fas fa-calendar-alt"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- 4. Navigasi 2 Tab Presensi Mengajar (Jadwal Hari Ini & Riwayat Presensi) -->
    <div class="dash-tabs-nav dash-desktop-tabs" style="margin-bottom: 20px;">
        <button type="button"
            class="btn {{ ($activeTab ?? 'jadwal') === 'jadwal' ? 'btn-primary' : 'btn-outline' }} pm-tab-btn"
            data-tab="jadwal">
            <i class="fas fa-calendar-day me-1"></i> Jadwal Hari Ini ({{ count($jadwalHariIni) }})
        </button>
        <button type="button"
            class="btn {{ ($activeTab ?? 'jadwal') === 'riwayat' ? 'btn-primary' : 'btn-outline' }} pm-tab-btn"
            data-tab="riwayat">
            <i class="fas fa-list-check me-1"></i> Riwayat Presensi Mengajar ({{ $items->total() }})
        </button>
    </div>

    <!-- Mobile Custom Dropdown Selector Baku SAE -->
    <div class="dash-mobile-tab-select-wrap" style="margin-bottom: 20px;">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" id="pmMobileDropdownTrigger">
                <div class="custom-dropdown-trigger-label">
                    <i
                        class="fas {{ ($activeTab ?? 'jadwal') === 'jadwal' ? 'fa-calendar-day' : 'fa-list-check' }} text-primary me-2"></i>
                    <span
                        id="pmMobileDropdownLabel">{{ ($activeTab ?? 'jadwal') === 'jadwal' ? 'Jadwal Hari Ini' : 'Riwayat Presensi Mengajar' }}</span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" id="pmMobileDropdownMenu">
                <button type="button"
                    class="custom-dropdown-item pm-tab-btn {{ ($activeTab ?? 'jadwal') === 'jadwal' ? 'active' : '' }}"
                    data-tab="jadwal">
                    <i class="fas fa-calendar-day text-primary me-2"></i> Jadwal Hari Ini
                </button>
                <button type="button"
                    class="custom-dropdown-item pm-tab-btn {{ ($activeTab ?? 'jadwal') === 'riwayat' ? 'active' : '' }}"
                    data-tab="riwayat">
                    <i class="fas fa-list-check text-primary me-2"></i> Riwayat Presensi Mengajar
                </button>
            </div>
        </div>
    </div>

    <!-- TAB PANE 1: JADWAL HARI INI -->
    <div id="tabPaneJadwal" class="pm-tab-pane"
        style="{{ ($activeTab ?? 'jadwal') === 'jadwal' ? 'display: block;' : 'display: none;' }}">

        @if (!empty($isPiket))
            <!-- Filter Jadwal Hari Ini Khusus Guru Piket / Admin -->
            <div class="card"
                style="padding: 12px 16px; margin-bottom: 16px; border-radius: var(--radius-lg, 12px); background: var(--bg-card); border: 1px solid var(--border-color);">
                <form action="{{ route('dashboard.presensi-mengajar.index') }}" method="GET"
                    style="margin: 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;">
                    <input type="hidden" name="tab" value="jadwal">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <span
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-filter text-primary"></i> Filter Jadwal Guru:
                        </span>
                        @if (!empty($guruList) && count($guruList) > 0)
                            <select name="filter_ptk_id" class="toolbar-filter-select"
                                style="min-width: 170px; height: 36px; font-size: var(--text-sm, 0.8125rem);" onchange="this.form.submit()">
                                <option value="">Semua Guru Mapel</option>
                                @foreach ($guruList as $g)
                                    <option value="{{ $g->ptk_id }}"
                                        {{ request('filter_ptk_id') === $g->ptk_id ? 'selected' : '' }}>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        <select name="rombongan_belajar_id" class="toolbar-filter-select"
                            style="min-width: 140px; height: 36px; font-size: var(--text-sm, 0.8125rem);" onchange="this.form.submit()">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ request('rombongan_belajar_id') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>
                        @if (request('filter_ptk_id') || request('rombongan_belajar_id'))
                            <a href="{{ route('dashboard.presensi-mengajar.index', ['tab' => 'jadwal']) }}"
                                class="btn btn-outline"
                                style="height: 36px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        @endif
                    </div>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                        Menampilkan jadwal KBM aktif hari {{ $hariIni }}
                    </div>
                </form>
            </div>
        @endif

        <!-- Quick Action Widget: Jadwal Mengajar Hari Ini -->
        <div class="card card-widget-kbm"
            style="border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); margin-bottom: 20px; background: var(--bg-card); padding: 16px 18px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div
                        style="width: 32px; height: 32px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: var(--text-md, 1rem);">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div>
                        <h3 style="font-size: var(--text-md, 1rem); font-weight: 700; color: var(--text-color); margin: 0;">
                            Jadwal Hari Ini ({{ $hariIni }})
                        </h3>
                    </div>
                </div>
                <span class="badge"
                    style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 3px 8px; font-weight: 700;">
                    {{ count($jadwalHariIni) }} Sesi Mapel
                </span>
            </div>

            @if ($isHariLibur)
                <div
                    style="text-align: center; padding: 22px 14px; background: rgba(239,68,68,0.05); border: 1px dashed rgba(239,68,68,0.25); border-radius: var(--radius-lg, 12px); color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-umbrella-beach"
                        style="font-size: var(--text-3xl, 1.875rem); color: var(--danger, #ef4444); margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                    <strong style="color: var(--danger, #ef4444); font-size: var(--text-base, 0.875rem); display: block; margin-bottom: 2px;">Hari Ini Libur
                        Sekolah — KBM Reguler Ditiadakan</strong>
                    <span>Sesuai Kalender Pendidikan, tidak ada jadwal mengajar aktif untuk hari ini.</span>
                </div>
            @elseif (!empty($isGuruViewOnly) && empty($isJadwalDiberlakukan))
                <div
                    style="text-align: center; padding: 22px 14px; background: rgba(245,158,11,0.06); border: 1px dashed rgba(245,158,11,0.3); border-radius: var(--radius-lg, 12px); color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                    <i class="fas fa-clock"
                        style="font-size: var(--text-3xl, 1.875rem); color: var(--warning, #f59e0b); margin-bottom: 8px; display: block; opacity: 0.85;"></i>
                    <strong style="color: var(--warning, #f59e0b); font-size: var(--text-base, 0.875rem); display: block; margin-bottom: 2px;">Jadwal KBM
                        Masih
                        Berstatus Draft</strong>
                    <span>Jadwal KBM semester ini sedang dalam proses penyusunan/finalisasi oleh Tim Kurikulum dan belum
                        resmi
                        diberlakukan.</span>
                </div>
            @elseif (count($jadwalHariIni) > 0)
                <div class="card-kbm-today-grid">
                    @foreach ($jadwalHariIni as $j)
                        @php
                            $key = 'jadwal_' . $j->id;
                            $presensiToday = $presensiHariIniKeyed[$key] ?? null;
                            $rombelNama =
                                \Illuminate\Support\Facades\DB::table('rombongan_belajar')
                                    ->where('rombongan_belajar_id', $j->rombongan_belajar_id)
                                    ->value('nama') ?? $j->rombongan_belajar_id;

                            // Periksa apakah waktu saat ini belum mencapai jam_mulai jadwal KBM
                            $nowTime = \Carbon\Carbon::now()->format('H:i');
                            $jamMulai = !empty($j->jam_mulai) ? substr($j->jam_mulai, 0, 5) : null;
                            $isBelumSaatnya = $jamMulai && $nowTime < $jamMulai;
                        @endphp
                        <div class="today-kbm-item {{ $presensiToday ? 'done' : '' }}">
                            <div>
                                <div
                                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; gap: 6px; width: 100%;">
                                    <span class="badge badge-primary"
                                        style="font-size: var(--text-xs, 0.75rem); font-weight: 700; padding: 2px 7px; flex-shrink: 0;">
                                        <i class="fas fa-chalkboard me-1"></i>{{ $rombelNama }}
                                    </span>
                                    <span class="badge-jam">
                                        <i class="far fa-clock me-1"></i>Jam
                                        {{ $j->jam_ke_mulai }}-{{ $j->jam_ke_selesai }}
                                        ({{ $j->durasi_jp }} JP)
                                    </span>
                                </div>

                                <div class="mapel-title" title="{{ $j->nama_mata_pelajaran }}">
                                    {{ $j->nama_mata_pelajaran }}
                                </div>

                                @php
                                    $guruNama = $j->gtk?->nama ?? null;
                                @endphp
                                @if (!empty($guruNama))
                                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; margin-top: 3px; display: flex; align-items: center; gap: 6px;"
                                        title="Guru Mapel: {{ $guruNama }}">
                                        <i class="fas fa-user-tie text-primary"
                                            style="font-size: var(--text-xs, 0.75rem); flex-shrink: 0;"></i>
                                        <span
                                            style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $guruNama }}</span>
                                    </div>
                                @endif

                                <div
                                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                    <span><i class="far fa-clock me-1"></i>{{ $j->jam_waktu_range }}</span>
                                    @if ($j->ruangan)
                                        <span><i class="fas fa-location-dot me-1"></i>{{ $j->ruangan }}</span>
                                    @endif
                                </div>
                            </div>

                            <div
                                style="border-top: 1px solid var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 8px; width: 100%;">
                                @if ($presensiToday)
                                    <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                                        {!! $presensiToday->status_badge !!}
                                        <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); white-space: nowrap;">
                                            {{ substr($presensiToday->jam_masuk, 0, 5) }}
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-outline btn-sm btn-edit-row"
                                        data-id="{{ $presensiToday->id }}" title="Edit Presensi"
                                        style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px); flex-shrink: 0;">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                @elseif ($isBelumSaatnya)
                                    <span
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;"
                                        title="Sesi KBM belum dimulai (dimulai pukul {{ $jamMulai }})">
                                        <i class="fas fa-hourglass-start text-muted"></i> Belum Saatnya
                                    </span>
                                @else
                                    <span
                                        style="font-size: var(--text-xs, 0.75rem); color: var(--warning, #f59e0b); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;">
                                        <i class="fas fa-clock"></i> Belum
                                    </span>
                                    @if ($canCreate)
                                        <button type="button" class="btn btn-primary btn-sm btn-quick-checkin"
                                            data-jadwal-id="{{ $j->id }}"
                                            data-rombel-id="{{ $j->rombongan_belajar_id }}"
                                            data-rombel-nama="{{ $rombelNama }}"
                                            data-mapel="{{ $j->nama_mata_pelajaran }}"
                                            data-pembelajaran-id="{{ $j->pembelajaran_id }}"
                                            data-mapel-id="{{ $j->mata_pelajaran_id }}"
                                            data-jam-mulai="{{ $j->jam_ke_mulai }}"
                                            data-jam-selesai="{{ $j->jam_ke_selesai }}"
                                            data-jam-masuk="{{ !empty($j->jam_mulai) ? substr($j->jam_mulai, 0, 5) : '' }}"
                                            data-jam-keluar="{{ !empty($j->jam_selesai) ? substr($j->jam_selesai, 0, 5) : '' }}"
                                            data-jam-waktu="{{ $j->jam_waktu_range }}"
                                            data-durasi-jp="{{ $j->durasi_jp }}" data-hari="{{ $j->hari }}"
                                            data-ptk-id="{{ $j->ptk_id }}" data-guru-nama="{{ $guruNama ?? '' }}"
                                            title="Presensi Sekarang"
                                            style="font-size: var(--text-sm, 0.8125rem); padding: 5px 10px; border-radius: var(--radius-md, 8px); background: linear-gradient(135deg, var(--success, #10b981), #059669); font-weight: 600; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center; color: #fff; border: none; box-shadow: 0 2px 5px rgba(16,185,129,0.3); cursor: pointer;">
                                            <i class="fas fa-calendar-check"></i>
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div
                    style="text-align: center; padding: 18px 12px; background: var(--bg-hover); border-radius: var(--radius-md, 8px); color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                    <i class="fas fa-calendar-xmark"
                        style="font-size: var(--text-2xl, 1.5rem); margin-bottom: 6px; display: block; opacity: 0.5;"></i>
                    Tidak ada jadwal KBM aktif {{ $isGuruViewOnly ? 'untuk Anda' : 'dengan guru mata pelajaran' }} pada
                    hari
                    {{ $hariIni }}.
                </div>
            @endif
        </div>
    </div>

    <!-- TAB PANE 2: RIWAYAT PRESENSI MENGAJAR (TABEL DI BAWAH JADWAL HARI INI) -->
    <div id="tabPaneRiwayat" class="pm-tab-pane"
        style="{{ ($activeTab ?? 'jadwal') === 'riwayat' ? 'display: block;' : 'display: none;' }}">

        <!-- Unified Datatable Presensi Mengajar (Toolbar + Table + Pagination dalam 1 Card Terpadu) -->
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <!-- 1. Toolbar Header (Entries, Filters & Live Search) -->
            <div class="datatable-toolbar">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                            <select id="perPageSelect" name="per_page" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}"
                                        {{ request('per_page', 15) == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span>entri</span>
                        </div>

                        <!-- Filter Rentang Tanggal Inline Baku SAE -->
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md, 8px); padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                            <i class="fas fa-calendar-day text-primary" style="font-size: var(--text-sm, 0.8125rem);"
                                title="Rentang Tanggal"></i>
                            <input type="date" id="filterTanggalMulai" name="tanggal_mulai"
                                value="{{ request('tanggal_mulai') }}" title="Tanggal Mulai"
                                style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;">
                            <span style="color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">&ndash;</span>
                            <input type="date" id="filterTanggalSelesai" name="tanggal_selesai"
                                value="{{ request('tanggal_selesai') }}" title="Tanggal Selesai"
                                style="background: transparent; border: none; color: var(--text-color); font-size: var(--text-sm, 0.8125rem); padding: 0; outline: none; width: 125px; cursor: pointer;">
                        </div>

                        <!-- Filter Rombel -->
                        <select id="filterRombel" name="rombongan_belajar_id" class="toolbar-filter-select"
                            style="min-width: 140px; height: 38px;">
                            <option value="">Semua Kelas</option>
                            @foreach ($rombelList as $r)
                                <option value="{{ $r->rombongan_belajar_id }}"
                                    {{ request('rombongan_belajar_id') === $r->rombongan_belajar_id || request('rombel') === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                    {{ $r->nama }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Filter Status -->
                        <select id="filterStatus" name="status" class="toolbar-filter-select"
                            style="min-width: 130px; height: 38px;">
                            <option value="">Semua Status</option>
                            <option value="H" {{ request('status') === 'H' ? 'selected' : '' }}>Hadir</option>
                            <option value="I" {{ request('status') === 'I' ? 'selected' : '' }}>Izin</option>
                            <option value="S" {{ request('status') === 'S' ? 'selected' : '' }}>Sakit</option>
                            <option value="T" {{ request('status') === 'T' ? 'selected' : '' }}>Tugas</option>
                            <option value="D" {{ request('status') === 'D' ? 'selected' : '' }}>Inval</option>
                        </select>

                        @if (!$isGuruViewOnly && count($guruList) > 0)
                            <select id="filterPtk" name="filter_ptk_id" class="toolbar-filter-select"
                                style="min-width: 140px; height: 38px;">
                                <option value="">Semua Guru</option>
                                @foreach ($guruList as $g)
                                    <option value="{{ $g->ptk_id }}"
                                        {{ request('filter_ptk_id') === $g->ptk_id || request('ptk_id') === $g->ptk_id ? 'selected' : '' }}>
                                        {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        @if (request('q') ||
                                request('tanggal_mulai') ||
                                request('tanggal_selesai') ||
                                request('rombongan_belajar_id') ||
                                request('rombel') ||
                                request('status') ||
                                request('filter_ptk_id') ||
                                request('ptk_id'))
                            <a href="{{ route('dashboard.presensi-mengajar.index', ['tab' => 'riwayat']) }}"
                                id="btnResetFilter" class="btn btn-outline"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px);"
                                title="Reset filter" data-live-reset="true">
                                <i class="fas fa-undo"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Live Search Box Baku SAE -->
                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearchInput" name="q"
                            placeholder="Cari mapel, kelas, catatan..." value="{{ request('q') }}" autocomplete="off">
                        <button type="button" class="clear-search {{ request('q') ? 'visible' : '' }}"
                            title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Konten Datatable Container Baku SAE -->
            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                @include('dashboard.presensi-mengajar-table')
            </div>

            <!-- 3. Footer Paginasi Terpadu Datatable -->
            @if ($items->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $items])
                </div>
            @endif
        </div>
    </div>

    <!-- 6. Modal Form Catat Presensi Mengajar (z-index: 99999 !important) -->
    <div id="modalFormPresensi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 580px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 18px 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box; overflow: hidden;">
            <div class="pm-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3 id="modalPresensiTitle"
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-primary"></i> Presensi Mengajar
                </h3>
                <button type="button" class="btn-close-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPresensiMengajar"
                style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
                @csrf
                <input type="hidden" id="presensiId" name="id">
                <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                <input type="hidden" id="inputPtkId" name="ptk_id" value="{{ $ptkId }}">
                <input type="hidden" id="inputHari" name="hari">

                <!-- Body Modal Scrollable (persis seperti peserta-didik-aktif) -->
                <div class="modal-body-scroll"
                    style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px; -webkit-overflow-scrolling: touch;">

                    <!-- Selector Sumber Jadwal -->
                    <div class="pm-field" style="margin-bottom: 12px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal Terdaftar
                        </label>
                        <select id="selectJadwalKbm"
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                            <option value="">-- Pilih Jadwal KBM --</option>
                            @foreach ($jadwalList as $j)
                                <option value="{{ $j['id'] }}" data-rombel-id="{{ $j['rombongan_belajar_id'] }}"
                                    data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                    data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                    data-mapel-id="{{ $j['mata_pelajaran_id'] }}" data-hari="{{ $j['hari'] }}"
                                    data-jam-mulai="{{ $j['jam_ke_mulai'] }}"
                                    data-jam-selesai="{{ $j['jam_ke_selesai'] }}"
                                    data-jam-masuk="{{ !empty($j['jam_mulai']) ? substr($j['jam_mulai'], 0, 5) : '' }}"
                                    data-jam-keluar="{{ !empty($j['jam_selesai']) ? substr($j['jam_selesai'], 0, 5) : '' }}"
                                    data-jam-waktu="{{ $j['jam_waktu_range'] ?? '' }}"
                                    data-durasi-jp="{{ $j['durasi_jp'] }}" data-ptk-id="{{ $j['ptk_id'] }}">
                                    [{{ $j['hari'] }}] {{ $j['rombel_nama'] }} — {{ $j['nama_mata_pelajaran'] }}
                                    (Jam
                                    {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }}{{ !empty($j['jam_waktu_range']) ? ' • ' . $j['jam_waktu_range'] : '' }})
                                    {{ !$isGuru && !empty($j['guru_nama']) && $j['guru_nama'] !== '-' ? ' • ' . $j['guru_nama'] : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (!$isGuruViewOnly && count($guruList) > 0)
                        <!-- Pilihan Guru Pengampu (Khusus Guru Piket / Tendik / Admin) -->
                        <div class="pm-field" style="margin-bottom: 12px;" id="wrapPtkSelector">
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-user-tie text-primary"></i> Guru Mata Pelajaran <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select id="selectPtkId"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                @foreach ($guruList as $g)
                                    <option value="{{ $g->ptk_id }}">{{ $g->nama }}
                                        {{ $g->nip ? '(' . $g->nip . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Alert Info Kalender Pendidikan pada Tanggal Terpilih -->
                    <div id="infoKalenderTanggalPresensi"
                        style="display: none; margin-bottom: 12px; padding: 8px 12px; border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem);">
                    </div>

                    <div class="form-grid-2">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-chalkboard text-primary"></i> Kelas <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select id="inputRombonganBelajarId" name="rombongan_belajar_id" required
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($rombelList as $r)
                                    <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-book text-primary"></i> Mata Pelajaran <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required
                                placeholder="Mata Pelajaran..."
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="form-grid-3 pm-grid-jam">
                        <div>
                            <label
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-calendar-day text-primary"></i> Tanggal <span
                                    style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="date" id="inputTanggal" name="tanggal" value="{{ $tanggalHariIni }}"
                                required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-play text-primary"></i> Jam Mulai
                                </span>
                                <span id="badgeAutoJamMulai"
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--success, #10b981); font-weight: 600;">
                                    <i class="fas fa-lock me-1"></i> Otomatis
                                </span>
                            </label>
                            <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0"
                                max="20" value="1" readonly required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-muted); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i class="fas fa-stop text-primary"></i> Jam Selesai
                                </span>
                                <span id="labelDurasiJp" style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">
                                    - JP
                                </span>
                            </label>
                            <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0"
                                max="20" value="2" readonly required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 700; box-sizing: border-box; cursor: not-allowed;">
                        </div>
                    </div>

                    <!-- Info Banner Waktu KBM Nyata dari Jadwal -->
                    <div id="wrapWaktuKbm"
                        style="display: none; margin-bottom: 12px; margin-top: -4px; padding: 7px 12px; border-radius: var(--radius-md, 8px); background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); font-size: var(--text-xs, 0.75rem); color: var(--text-color); justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <i class="far fa-clock text-primary"></i>
                            <span>Waktu Sesuai Jadwal: <strong id="textWaktuKbm"
                                    style="color: var(--primary);">-</strong></span>
                        </div>
                        <span id="badgeHariJadwal" class="badge"
                            style="background: rgba(99,102,241,0.15); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 2px 7px; font-weight: 600;">-</span>
                    </div>

                    <!-- Pilihan Status Kehadiran (Segmented Pill Buttons Touch-Friendly) -->
                    <div class="pm-field" style="margin-bottom: 14px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                            <i class="fas fa-signal text-primary"></i> Status Kehadiran Guru <span
                                style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <div class="status-pill-group">
                            <label class="status-pill-item active" data-status="H">
                                <input type="radio" name="status" value="H" checked>
                                <i class="fas fa-check-circle" style="color: var(--success, #10b981);"></i>
                                <span>Hadir</span>
                            </label>
                            <label class="status-pill-item" data-status="I">
                                <input type="radio" name="status" value="I">
                                <i class="fas fa-file-signature" style="color: var(--primary);"></i>
                                <span>Izin</span>
                            </label>
                            <label class="status-pill-item" data-status="S">
                                <input type="radio" name="status" value="S">
                                <i class="fas fa-notes-medical" style="color: var(--warning, #f59e0b);"></i>
                                <span>Sakit</span>
                            </label>
                            <label class="status-pill-item" data-status="T">
                                <input type="radio" name="status" value="T">
                                <i class="fas fa-briefcase" style="color: var(--text-muted, #64748b);"></i>
                                <span>Tugas</span>
                            </label>
                            <label class="status-pill-item" data-status="D">
                                <input type="radio" name="status" value="D">
                                <i class="fas fa-user-clock" style="color: var(--danger, #ef4444);"></i>
                                <span>Inval</span>
                            </label>
                        </div>
                    </div>

                    <!-- Input Guru Pengganti (Muncul jika status Inval) -->
                    <div id="wrapGuruPengganti"
                        style="display: none; margin-bottom: 12px; padding: 10px 12px; background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.2); border-radius: var(--radius-md, 8px);">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--danger, #ef4444); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-user-clock"></i> Nama Guru Pengganti (Inval) <span
                                style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" id="inputNamaGuruPengganti" name="nama_guru_pengganti"
                            placeholder="Ketik nama guru pengganti..."
                            style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box;">
                    </div>

                    <!-- Jam Masuk & Jam Selesai Sesuai Jadwal (Grid 2 Kolom) -->
                    <div class="form-grid-2 pm-grid-waktu" style="margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i class="far fa-clock text-primary"></i> Jam Masuk
                                </span>
                                <span id="badgeAutoJamMasuk"
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--success, #10b981); font-weight: 600;">
                                    <i class="fas fa-check-double me-1"></i> Sesuai Jadwal
                                </span>
                            </label>
                            <input type="time" id="inputJamMasuk" name="jam_masuk" value="{{ date('H:i') }}"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 600; box-sizing: border-box;">
                        </div>
                        <div>
                            <label
                                style="font-size: var(--text-xs, 0.75rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                                <span style="display: flex; align-items: center; gap: 4px;">
                                    <i class="far fa-clock text-primary"></i> Jam Selesai
                                </span>
                                <span id="badgeAutoJamKeluar"
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--success, #10b981); font-weight: 600;">
                                    <i class="fas fa-check-double me-1"></i> Sesuai Jadwal
                                </span>
                            </label>
                            <input type="time" id="inputJamKeluar" name="jam_keluar"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 600; box-sizing: border-box;">
                        </div>
                    </div>

                    <div class="pm-field" style="margin-bottom: 16px;">
                        <label
                            style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-pen-fancy text-primary"></i> Catatan Sesi KBM
                        </label>
                        <textarea id="inputKeterangan" name="keterangan" rows="2"
                            placeholder="Catatan opsional KBM, topik bahasan, kendala teknis..."
                            style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-sm, 0.8125rem); box-sizing: border-box; resize: vertical;"></textarea>
                    </div>

                </div>

                <!-- Footer Modal (tetap di dasar modal) -->
                <div class="pm-actions"
                    style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; flex-shrink: 0;">
                    <button type="button" class="btn btn-outline btn-close-modal"
                        style="padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                        Batal
                    </button>
                    <button type="submit" id="btnSavePresensi" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px); font-weight: 600; background: linear-gradient(135deg, var(--success, #10b981), #059669);">
                        <i class="fas fa-check me-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 7. Modal Detail Presensi Mengajar -->
    <div id="modalDetailPresensi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 520px; width: 96%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: var(--radius-xl, 16px); padding: 18px 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--bg-card); box-sizing: border-box; overflow: hidden;">
            <div class="pm-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3
                    style="font-size: var(--text-lg, 1.125rem); font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary"></i> Rincian Presensi
                </h3>
                <button type="button" class="btn-close-detail"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: var(--text-xl, 1.25rem); padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll"
                style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px; -webkit-overflow-scrolling: touch;">
                <div id="detailPresensiContent"
                    style="font-size: var(--text-base, 0.875rem); display: flex; flex-direction: column; gap: 8px;">
                    <!-- Konten dinamis via JS -->
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 10px; flex-shrink: 0;">
                <button type="button" class="btn btn-outline btn-close-detail"
                    style="padding: 7px 16px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);">
                    Tutup
                </button>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script
        src="{{ asset('js/presensi-mengajar.js') }}?v={{ file_exists(public_path('js/presensi-mengajar.js')) ? filemtime(public_path('js/presensi-mengajar.js')) : time() }}">
    </script>
@endpush
