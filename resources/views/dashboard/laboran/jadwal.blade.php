@extends('layouts.dashboard')

@section('title', 'Jadwal Praktik & Penggunaan Lokasi â€” Laboratorium SAE')
@section('dash_title', 'Jadwal Praktik')

@section('content')
    <div class="dash-content-inner">
        {{-- 1. Header Banner & Action Buttons --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Jadwal Praktik &amp; Penggunaan Lokasi
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        Penjadwalan guru untuk praktik di laboratorium, bengkel, lahan/kebun, dan lapangan sekolah.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <span class="badge"
                    style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6); border: 1px solid rgba(59,130,246,0.25); padding: var(--space-3) var(--space-6); border-radius: var(--radius-md); font-size: var(--text-xs); font-weight: var(--font-bold); display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas {{ $scopeBadge['icon'] ?? 'fa-graduation-cap' }}"></i> {{ $scopeBadge['label'] ?? 'Semua Jurusan' }}
                </span>
                @if ($canRead)
                    <div class="jadwal-view-switcher"
                        style="display: inline-flex; background: var(--bg-hover); padding: 3px; border-radius: var(--radius-md, 8px); border: 1px solid var(--border-color);">
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['view_mode' => 'grid'])) }}"
                            class="btn {{ ($viewMode ?? 'grid') === 'grid' ? 'btn-primary' : 'btn-outline' }}"
                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border: none; border-radius: var(--radius-sm, 6px); box-shadow: none;">
                            <i class="fas fa-table-cells me-1"></i> Matriks Grid
                        </a>
                        <a href="{{ route('dashboard.laboran.jadwal.index', array_merge(request()->query(), ['view_mode' => 'table'])) }}"
                            class="btn {{ ($viewMode ?? 'grid') === 'table' ? 'btn-primary' : 'btn-outline' }}"
                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border: none; border-radius: var(--radius-sm, 6px); box-shadow: none;">
                            <i class="fas fa-list me-1"></i> Rekap Tabel
                        </a>
                    </div>

                    {{-- Dropdown Cetak Jadwal Multi-Mode --}}
                    <div style="position: relative; display: inline-block;">
                        <button type="button" class="btn btn-outline" id="btnDropdownCetakJadwal"
                            style="height: 38px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md); font-size: var(--text-sm);"
                            title="Pilihan Cetak Jadwal Praktik">
                            <i class="fas fa-print"></i> Cetak <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                        </button>
                        <div id="dropdownMenuCetakJadwal"
                            style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 10px 25px rgba(0,0,0,0.25); min-width: 220px; z-index: 1000; overflow: hidden;">
                            <a href="{{ route('dashboard.laboran.jadwal.cetak', ['mode' => 'mingguan', 'auto' => 1]) }}" target="_blank"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm); border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-calendar-week text-primary" style="width: 16px;"></i>
                                <div>
                                    <strong>Jadwal Mingguan</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Sesi berulang per minggu</div>
                                </div>
                            </a>
                            <a href="{{ route('dashboard.laboran.jadwal.cetak', ['mode' => 'semester', 'auto' => 1]) }}" target="_blank"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm); border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-calendar-range text-success" style="width: 16px;"></i>
                                <div>
                                    <strong>Jadwal Semester</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Rekap per bulan satu semester</div>
                                </div>
                            </a>
                            <a href="{{ route('dashboard.laboran.laporan.index') }}"
                                style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; color: var(--text-color); text-decoration: none; font-size: var(--text-sm);">
                                <i class="fas fa-file-lines text-warning" style="width: 16px;"></i>
                                <div>
                                    <strong>Laporan Lokasi</strong>
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted);">Bulan, triwulan, tahunan</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline" id="btnBukaModalPilihMode"
                        style="height: 38px; padding: 0 12px; display: inline-flex; align-items: center; gap: 6px; border-radius: var(--radius-md); font-size: var(--text-sm);"
                        title="Ubah Mode Pemberlakuan Jadwal">
                        <i class="fas fa-sliders text-primary"></i> Mode: {{ ucfirst($activeMode ?? 'Mingguan') }}
                    </button>
                @endif
                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenModalJadwal"
                        data-active-mode="{{ $activeMode ?? 'mingguan' }}"
                        style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md);"
                        title="Jadwalkan Praktik Baru">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Stat Grid (4 Kartu Ringkasan) --}}
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-10); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statTotal, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Sesi Terjadwal</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statHariIni, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Hari Ini</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statBerlangsung, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Sedang Berlangsung</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($statSelesai, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Praktik Selesai</div>
                </div>
            </div>
        </div>

        {{-- 2b. Tab Pemberlakuan Jadwal (Mingguan / Semester) â€” eksklusif: hanya satu mode berlaku --}}
        @if ($canRead)
            @php
                $activeMode = $pengaturanJadwal->mode_pemberlakuan ?? 'mingguan';
                $isActive = ($pengaturanJadwal->status ?? 'draft') === 'aktif';
                $isActiveMode = $modeDiberlakukan === $activeMode;
            @endphp
            <div class="periode-nav-wrapper" style="margin-bottom: var(--space-8);">
                <div class="periode-nav-desktop">
                    @foreach ($modePemberlakuanLabels as $mKey => $mLabel)
                        <a href="{{ route('dashboard.laboran.jadwal.index', ['mode_pemberlakuan' => $mKey]) }}"
                            class="periode-nav-tab {{ ($pengaturanJadwal->mode_pemberlakuan ?? 'mingguan') === $mKey ? 'active' : '' }}">
                            <i class="fas {{ $mKey === 'mingguan' ? 'fa-calendar-week' : 'fa-calendar-range' }}"></i>
                            {{ $mLabel }}
                            @if ($modeDiberlakukan === $mKey)
                                <span class="badge badge-success" style="font-size: var(--text-2xs); margin-left: 6px;">
                                    <i class="fas fa-check-circle me-1"></i> Diberlakukan
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="card" style="padding: var(--space-7) var(--space-9); margin-bottom: var(--space-9); display: flex; justify-content: space-between; align-items: center; gap: var(--space-6); flex-wrap: wrap;">
                <div>
                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base); margin-bottom: 2px;">
                        <i class="fas fa-sliders text-primary me-1"></i>
                        Pemberlakuan Penentuan Jadwal: <strong style="color: var(--primary);">{{ $modePemberlakuanLabels[$activeMode] ?? $activeMode }}</strong>
                        @if ($modeDiberlakukan !== null && !$isActiveMode)
                            <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-left: 6px;" title="Mode lain sedang diberlakukan">
                                <i class="fas fa-circle-info me-1"></i> Diberlakukan: {{ $modePemberlakuanLabels[$modeDiberlakukan] ?? $modeDiberlakukan }}
                            </span>
                        @endif
                    </div>
                    <div style="font-size: var(--text-sm); color: var(--text-muted);">
                        @if ($activeMode === 'mingguan')
                            Jadwal praktik ditentukan per minggu: pilih hari praktik (bisa lebih dari satu) yang berulang setiap minggu, dengan acuan tanggal awal berlaku.
                        @else
                            Jadwal praktik ditentukan per semester (Ganjil Juliâ€“Desember / Genap Januariâ€“Juni) sesuai tahun ajaran berjalan, dengan pemetaan hari praktik.
                        @endif
                        @if ($isActive)
                            <span class="badge badge-success" style="margin-left: 6px;">
                                <i class="fas fa-circle-check me-1"></i> Aktif
                            </span>
                        @else
                            <span class="badge badge-warning" style="margin-left: 6px;">
                                <i class="fas fa-pause me-1"></i> Draft
                            </span>
                        @endif
                    </div>
                </div>
                @if ($canUpdate)
                    <button type="button" class="btn {{ $isActiveMode ? 'btn-outline' : 'btn-primary' }} btn-toggle-pemberlakuan-lab"
                        data-mode="{{ $activeMode }}"
                        data-status="{{ $isActiveMode ? 'draft' : 'aktif' }}"
                        data-url="{{ route('dashboard.laboran.jadwal.toggle-pemberlakuan') }}"
                        title="{{ $isActiveMode ? 'Matikan pemberlakuan mode ini (kembali ke draft)' : ($modeDiberlakukan !== null ? 'Alihkan pemberlakuan ke mode ' . ($modePemberlakuanLabels[$activeMode] ?? $activeMode) . ' (mematikan mode lain)' : 'Aktifkan mode ini sebagai acuan penentuan jadwal praktik') }}">
                        @if ($isActiveMode)
                            <i class="fas fa-pause me-1"></i> Matikan Pemberlakuan
                        @else
                            <i class="fas fa-play me-1"></i> Aktifkan Pemberlakuan
                        @endif
                    </button>
                @endif
            </div>
        @endif

        {{-- 3a. Matriks Grid (pola Jadwal KBM) --}}
        @if (($viewMode ?? 'grid') === 'grid')
            <!-- Bar Informasi Jam Pelajaran -->
            <div class="card" id="jadwalTabBarContainer" style="padding: 12px 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="badge badge-primary" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 8px;">
                            <i class="fas fa-clock me-1"></i> Slot Jam Pelajaran per Hari (Pengaturan KBM)
                        </span>
                        @foreach ($gridLokasi as $h)
                            @php
                                $jpH = $rutinitas['slotCounts'][$h] ?? 0;
                            @endphp
                            <span class="badge {{ $selectedHari === $h ? 'badge-primary' : 'badge-outline' }}"
                                style="font-size: var(--text-xs, 0.75rem); padding: 3px 8px; {{ $selectedHari === $h ? 'border-color: var(--primary);' : '' }}">
                                {{ $h }}: {{ $jpH }} JP
                            </span>
                        @endforeach
                    </div>
                    <div style="display: flex; gap: 14px;">
                        <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: rgba(99,102,241,0.2); border: 1px solid var(--primary); margin-right: 4px;"></span> Terjadwal</span>
                        <span><span style="display: inline-block; width: 10px; height: 10px; border-radius: var(--radius-xs, 4px); background: var(--bg-hover); border: 1px dashed var(--border-color); margin-right: 4px;"></span> Kosong (Bisa Diisi)</span>
                    </div>
                </div>
            </div>

            <!-- Timetable Matriks: baris = Jam Pelajaran, kolom = Hari -->
            <div class="card table-responsive-stack" id="gridMatrixContainer"
                style="padding: 0; margin-bottom: 24px; overflow-x: auto;">
                <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0; min-width: 900px;">
                    <thead>
                        <tr>
                            <th style="position: sticky; left: 0; z-index: 20; background: var(--bg-card); width: 130px; min-width: 130px; padding: 14px 12px; border-bottom: 2px solid var(--border-color); border-right: 2px solid var(--border-color); font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color); text-transform: uppercase;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-clock text-primary"></i> Jam Pelajaran
                                </div>
                            </th>
                            @foreach ($gridLokasi as $hari)
                                @php
                                    $isHariAktif = $selectedHari === $hari;
                                @endphp
                                <th style="padding: 12px 10px; border-bottom: 2px solid var(--border-color); border-right: 1px solid var(--border-color); text-align: center; min-width: 200px; max-width: 260px; background: {{ $isHariAktif ? 'rgba(99,102,241,0.10)' : 'var(--bg-card)' }};">
                                    <div style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: {{ $isHariAktif ? 'var(--primary)' : 'var(--text-color)' }}; margin-bottom: 2px;">
                                        {{ $hari }}
                                    </div>
                                    <span class="badge" style="{{ $isHariAktif ? 'background: rgba(99,102,241,0.18); color: var(--primary);' : 'background: rgba(99,102,241,0.12); color: var(--primary);' }} font-size: var(--text-xs, 0.75rem); padding: 2px 6px; font-weight: 700;">
                                        {{ $countPerHari[$hari] ?? 0 }} sesi
                                    </span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($row = 1; $row <= max(1, $maxSlots); $row++)
                            <tr style="height: 64px;">
                                <td style="position: sticky; left: 0; z-index: 10; background: var(--bg-card); height: 64px; box-sizing: border-box; padding: 6px 10px; border-bottom: 1px solid var(--border-color); border-right: 2px solid var(--border-color); vertical-align: middle;">
                                    <div style="font-weight: 800; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); display: flex; align-items: center; justify-content: space-between;">
                                        <span>Jam Ke-{{ $row }}</span>
                                    </div>
                                </td>

                                @foreach ($gridLokasi as $hari)
                                    @php
                                        $isHariAktif = $selectedHari === $hari;
                                        $slot = $slotsPerHari[$hari][$row] ?? null;

                                        // Rutinitas hari ini (Upacara / Pembiasaan / Istirahat) dari Pengaturan KBM
                                        $rutinLabel = null;
                                        if ($slot) {
                                            if ($rutinitas['upacara']['aktif'] && $rutinitas['upacara']['hari'] === $hari && $row === $rutinitas['upacara']['jam_ke']) {
                                                $rutinLabel = 'Upacara';
                                            } elseif ($rutinitas['pembiasaan']['aktif'] && $rutinitas['pembiasaan']['hari'] === $hari && $row === $rutinitas['pembiasaan']['jam_ke']) {
                                                $rutinLabel = 'Pembiasaan';
                                            } elseif (!empty($slot['is_break'])) {
                                                $rutinLabel = 'Istirahat';
                                            }
                                        }

                                        $sesiList = ($slot && isset($gridMatrix[$hari][$row])) ? $gridMatrix[$hari][$row] : [];
                                    @endphp

                                    @if (!$slot)
                                        {{-- JP hari ini sudah habis sebelum baris maksimum --}}
                                        <td style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); background: rgba(127,127,127,0.06); text-align: center; vertical-align: middle;">
                                            <span style="color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem);">â€”</span>
                                        </td>
                                    @elseif ($rutinLabel)
                                        <td style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle;">
                                            <div style="text-align: center; padding: 6px 4px; border-radius: var(--radius-sm, 6px); background: {{ $rutinLabel === 'Upacara' ? 'rgba(239,68,68,0.06)' : ($rutinLabel === 'Pembiasaan' ? 'rgba(16,185,129,0.06)' : 'rgba(245,158,11,0.06)') }}; border: 1px dashed {{ $rutinLabel === 'Upacara' ? 'rgba(239,68,68,0.35)' : ($rutinLabel === 'Pembiasaan' ? 'rgba(16,185,129,0.35)' : 'rgba(245,158,11,0.35)') }};">
                                                <i class="fas {{ $rutinLabel === 'Upacara' ? 'fa-flag text-danger' : ($rutinLabel === 'Pembiasaan' ? 'fa-hands-praying text-success' : 'fa-mug-hot text-warning') }}"
                                                    style="font-size: var(--text-sm, 0.8125rem);"></i>
                                                <div style="font-size: var(--text-2xs, 0.6875rem); font-weight: 700; color: var(--text-muted); margin-top: 2px;">{{ $rutinLabel }}</div>
                                                <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">{{ $slot['mulai'] }} - {{ $slot['selesai'] }}</div>
                                            </div>
                                        </td>
                                    @elseif (count($sesiList) > 0)
                                        <td style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: top; background: rgba(99,102,241,0.03); box-sizing: border-box;">
                                            @foreach ($sesiList as $sesi)
                                                @php
                                                    $item = $sesi['item'];
                                                    $durasi = max(1, (int) $sesi['slotAkhir'] - (int) $row + 1);
                                                    $jenisKeyG = $item->jenis_lokasi ?: 'lainnya';
                                                    $borderCol = match ($jenisKeyG) {
                                                        'lab' => 'var(--primary)',
                                                        'bengkel' => 'var(--warning, #f59e0b)',
                                                        'lahan' => 'var(--success, #10b981)',
                                                        'lapangan' => 'var(--purple, #8b5cf6)',
                                                        default => 'var(--text-muted)',
                                                    };
                                                    $jenisIconG = match ($jenisKeyG) {
                                                        'lab' => 'fa-flask',
                                                        'bengkel' => 'fa-screwdriver-wrench',
                                                        'lahan' => 'fa-seedling',
                                                        'lapangan' => 'fa-futbol',
                                                        default => 'fa-location-dot',
                                                    };
                                                @endphp
                                                <div class="jadwal-grid-card" style="background: var(--bg-card); border: 1px solid rgba(99,102,241,0.35); border-left: 4px solid {{ $borderCol }}; border-radius: var(--radius-md, 8px); padding: 6px 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 4px; box-sizing: border-box; display: flex; flex-direction: column;">
                                                    <div>
                                                        <div style="font-weight: 700; font-size: var(--text-sm, 0.8125rem); color: var(--text-color); line-height: 1.25; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="{{ $item->mata_pelajaran }}">
                                                            {{ $item->mata_pelajaran }}
                                                        </div>
                                                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                                                            <i class="fas fa-chalkboard-user text-primary" style="flex-shrink: 0;"></i>
                                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;" title="{{ $item->guru_nama ?: '-' }}">{{ $item->guru_nama ?: '-' }}</span>
                                                        </div>
                                                        @if (!empty($item->rombel_nama))
                                                            <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-bottom: 2px;">
                                                                <i class="fas fa-users me-1"></i>{{ $item->rombel_nama }}
                                                            </div>
                                                        @endif
                                                        <div style="font-size: var(--text-2xs, 0.6875rem); color: {{ $borderCol }}; font-weight: 700; display: flex; align-items: center; gap: 4px;" title="Lokasi: {{ $item->ruang_lab_nama }}">
                                                            <i class="fas {{ $jenisIconG }} me-1"></i>
                                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">{{ $item->ruang_lab_nama }}</span>
                                                        </div>
                                                    </div>
                                                    <div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                                                        <span class="badge badge-primary" style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 700;">{{ $durasi }} JP</span>
                                                        <span class="badge" style="background: var(--bg-hover); color: var(--text-muted); font-size: var(--text-2xs, 0.6875rem); padding: 2px 5px; font-weight: 600;">{{ substr((string) $item->jam_mulai, 0, 5) }}â€“{{ substr((string) $item->jam_selesai, 0, 5) }}</span>
                                                        @if ($canUpdate || $canDelete)
                                                            <div style="display: flex; align-items: center; gap: 2px; flex-shrink: 0;">
                                                                @if ($canUpdate)
                                                                    <button type="button" class="btn-icon btn-edit-jadwal"
                                                                        data-id="{{ $item->id }}"
                                                                        data-lokasi="{{ $item->ruang_lab_nama }}"
                                                                        data-jenis="{{ $jenisKeyG }}"
                                                                        data-rombel-id="{{ $item->rombel_id }}"
                                                                        data-rombel-nama="{{ $item->rombel_nama }}"
                                                                        data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                                                        data-mapel="{{ $item->mata_pelajaran }}"
                                                                        data-ptk-id="{{ $item->ptk_id }}"
                                                                        data-tanggal="{{ $item->tanggal }}"
                                                                        data-jam-mulai="{{ substr((string) $item->jam_mulai, 0, 5) }}"
                                                                        data-jam-selesai="{{ substr((string) $item->jam_selesai, 0, 5) }}"
                                                                        data-status="{{ $item->status }}"
                                                                        title="Edit Jadwal" style="width: 22px; height: 22px; font-size: var(--text-2xs, 0.6875rem); padding: 0;">
                                                                        <i class="fas fa-pencil text-primary"></i>
                                                                    </button>
                                                                @endif
                                                                @if ($canDelete)
                                                                    <button type="button" class="btn-icon btn-delete-jadwal"
                                                                        data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                                        title="Hapus Jadwal" style="width: 22px; height: 22px; font-size: var(--text-2xs, 0.6875rem); padding: 0; color: var(--danger, #ef4444);">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </td>
                                    @else
                                        <td style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle; text-align: center; height: 64px; box-sizing: border-box;">
                                            @if ($canCreate)
                                                <button type="button" class="btn-quick-slot"
                                                    title="Klik untuk mengisi jadwal {{ $hari }} Jam Ke-{{ $row }}"
                                                    data-hari="{{ $hari }}" data-jam-ke="{{ $row }}"
                                                    data-jam-mulai="{{ $slot['mulai'] }}" data-jam-selesai="{{ $slot['selesai'] }}"
                                                    style="width: 100%; height: 100%; min-height: 54px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm, 6px); background: transparent; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; color: var(--text-muted); transition: all 0.2s ease;">
                                                    <i class="fas fa-plus" style="font-size: var(--text-xs, 0.75rem);"></i>
                                                    <span style="font-size: var(--text-2xs, 0.6875rem); font-weight: 600;">Isi Jadwal</span>
                                                </button>
                                            @else
                                                <div style="min-height: 54px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: var(--text-xs, 0.75rem);">-</div>
                                            @endif
                                        </td>
                                    @endif
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        @else
        {{-- 3b. Datatable Terpadu --}}
        <div class="card datatable-card-wrapper" id="tableDataContainer">
            <div class="datatable-toolbar">
                <form method="GET" action="{{ route('dashboard.laboran.jadwal.index') }}" id="filterForm" style="margin: 0;">
                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-6); justify-content: space-between; align-items: center;">
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-5); align-items: center;">
                            <div class="toolbar-entries">
                                <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                                <select id="perPageSelect" name="perPage" class="per-page-select" style="height: 38px;">
                                    @foreach ([10, 15, 25, 50, 100] as $n)
                                        <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                            {{ $n }}</option>
                                    @endforeach
                                </select>
                                <span>entri</span>
                            </div>

                            <select name="jenis" id="filterJenis" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Jenis Lokasi">
                                <option value="">Semua Jenis Lokasi</option>
                                @foreach ($jenisLokasiLabels ?? \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI as $jKey => $jLabel)
                                    <option value="{{ $jKey }}" {{ $jenisFilter === $jKey ? 'selected' : '' }}>
                                        {{ $jLabel }}</option>
                                @endforeach
                            </select>

                            <select name="lab" id="filterLab" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Lokasi Praktik">
                                <option value="">Semua Lokasi</option>
                                @foreach ($daftarLab as $lab)
                                    <option value="{{ $lab }}" {{ $labFilter === $lab ? 'selected' : '' }}>
                                        {{ $lab }}</option>
                                @endforeach
                            </select>

                            <select name="status" id="filterStatus" class="toolbar-filter-select" style="height: 38px;"
                                title="Filter Status Sesi">
                                <option value="">Semua Status</option>
                                @foreach (\App\Http\Controllers\LaboranJadwalController::STATUS_LIST as $sKey => $sLabel)
                                    <option value="{{ $sKey }}" {{ $statusFilter === $sKey ? 'selected' : '' }}>
                                        {{ $sLabel }}</option>
                                @endforeach
                            </select>

                            <input type="date" name="tanggal" id="filterTanggal" value="{{ $tanggalFilter }}"
                                class="form-control"
                                style="height: 38px; font-size: var(--text-sm); padding: 0 var(--space-5); border-radius: var(--radius-md);"
                                title="Filter Tanggal Praktik">

                            @if ($search || $labFilter || $jenisFilter || $statusFilter || $tanggalFilter)
                                <a href="{{ route('dashboard.laboran.jadwal.index') }}" class="btn btn-outline btn-reset-filter"
                                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md);"
                                    title="Reset filter" data-live-reset><i class="fas fa-undo"></i></a>
                            @endif
                        </div>

                        <div class="live-search-wrap" style="height: 38px;">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="q" id="liveSearch" value="{{ $search }}"
                                placeholder="Cari mapel, topik, guru, kelas, atau lokasi..." autocomplete="off">
                            <button type="button" id="clearSearch" class="clear-search {{ $search ? 'visible' : '' }}"
                                title="Hapus pencarian">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                No</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Lokasi &amp; Tanggal</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Mapel &amp; Topik</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">
                                Guru &amp; Kelas</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Waktu Sesi</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">
                                Status</th>
                            <th style="padding: var(--space-6) var(--space-8); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($list as $idx => $item)
                            @php
                                $jenisKey = $item->jenis_lokasi ?: 'lainnya';
                                $jenisLabel = \App\Http\Controllers\LaboranJadwalController::JENIS_LOKASI[$jenisKey] ?? 'Lokasi Lainnya';
                                $jenisIcon = match ($jenisKey) {
                                    'lab' => 'fa-flask',
                                    'bengkel' => 'fa-screwdriver-wrench',
                                    'lahan' => 'fa-seedling',
                                    'lapangan' => 'fa-futbol',
                                    default => 'fa-location-dot',
                                };
                                $jenisColor = match ($jenisKey) {
                                    'lab' => 'var(--primary, #3b82f6)',
                                    'bengkel' => 'var(--warning, #f59e0b)',
                                    'lahan' => 'var(--success, #10b981)',
                                    'lapangan' => 'var(--purple, #8b5cf6)',
                                    default => 'var(--text-muted)',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base); color: var(--text-muted);">
                                    {{ $list->firstItem() + $idx }}
                                </td>
                                <td data-label="Lokasi & Tanggal" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->ruang_lab_nama }}
                                    </div>
                                    <div style="font-size: var(--text-xs); color: {{ $jenisColor }}; font-weight: var(--font-semibold); margin-top: var(--space-1);">
                                        <i class="fas {{ $jenisIcon }} me-1"></i>{{ $jenisLabel }}
                                    </div>
                                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                                        <i class="fas fa-calendar-day text-primary me-1"></i>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                    </div>
                                </td>
                                <td data-label="Mapel & Topik" style="padding: var(--space-7) var(--space-8);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color); font-size: var(--text-base);">
                                        {{ $item->mata_pelajaran }}
                                    </div>
                                    @if (!empty($item->topik_praktik))
                                        <div style="font-size: var(--text-sm); color: var(--primary); font-weight: var(--font-medium);">
                                            {{ $item->topik_praktik }}
                                        </div>
                                    @else
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">
                                            Topik belum diisi
                                        </div>
                                    @endif
                                    @if (!empty($item->alat_bahan_digunakan))
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); margin-top: var(--space-1);">
                                            <i class="fas fa-toolbox me-1"></i>{{ \Illuminate\Support\Str::limit($item->alat_bahan_digunakan, 60) }}
                                        </div>
                                    @endif
                                    @if (!empty($item->laporan_kerusakan))
                                        <div style="font-size: var(--text-xs); color: var(--danger, #ef4444); margin-top: var(--space-1);">
                                            <i class="fas fa-circle-exclamation me-1"></i>
                                            {{ \Illuminate\Support\Str::limit($item->laporan_kerusakan, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Guru & Kelas" style="padding: var(--space-7) var(--space-8); font-size: var(--text-base);">
                                    <div style="font-weight: var(--font-semibold); color: var(--text-color);">
                                        {{ $item->guru_nama ?: '-' }}
                                    </div>
                                    @if (!empty($item->guru_pendamping))
                                        <div style="font-size: var(--text-xs); color: var(--primary); margin-top: 2px;" title="Guru Pendamping: {{ $item->guru_pendamping }}">
                                            <i class="fas fa-users me-1"></i>+ {{ $item->guru_pendamping }}
                                        </div>
                                    @endif
                                    <div style="margin-top: var(--space-2); display: flex; gap: var(--space-2); flex-wrap: wrap;">
                                        <span class="badge badge-outline" style="font-size: var(--text-xs);">
                                            {{ $item->rombel_nama ?: 'Seluruh Siswa' }}
                                        </span>
                                        @if (!empty($item->pemberlakuan) && $item->pemberlakuan !== 'sekali')
                                            <span class="badge badge-primary" style="font-size: var(--text-2xs);" title="Penjadwalan Berkala">
                                                <i class="fas fa-repeat me-1"></i>{{ ucfirst($item->pemberlakuan) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Waktu" style="padding: var(--space-7) var(--space-8); text-align: center; font-size: var(--text-base); font-family: var(--font-family-mono);">
                                    {{ substr((string) $item->jam_mulai, 0, 5) }} - {{ substr((string) $item->jam_selesai, 0, 5) }}
                                </td>
                                <td data-label="Status" style="padding: var(--space-7) var(--space-8); text-align: center;">
                                    @if ($item->status === 'dijadwalkan')
                                        <span class="badge badge-info">Dijadwalkan</span>
                                    @elseif ($item->status === 'berlangsung')
                                        <span class="badge badge-warning">Berlangsung</span>
                                    @elseif ($item->status === 'selesai')
                                        <span class="badge badge-success">Selesai</span>
                                    @else
                                        <span class="badge badge-danger">Batal</span>
                                    @endif
                                </td>
                                <td data-label="Aksi" style="padding: var(--space-7) var(--space-8); text-align: right;">
                                    <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-jadwal"
                                                data-id="{{ $item->id }}"
                                                data-lokasi="{{ $item->ruang_lab_nama }}"
                                                data-jenis="{{ $jenisKey }}"
                                                data-rombel-id="{{ $item->rombel_id }}"
                                                data-rombel-nama="{{ $item->rombel_nama }}"
                                                data-pembelajaran-id="{{ $item->pembelajaran_id }}"
                                                data-mapel="{{ $item->mata_pelajaran }}"
                                                data-ptk-id="{{ $item->ptk_id }}"
                                                data-ptk-ids="{{ $item->ptk_ids }}"
                                                data-pendamping="{{ $item->guru_pendamping }}"
                                                data-pemberlakuan="{{ $item->pemberlakuan ?? 'sekali' }}"
                                                data-guru="{{ $item->guru_nama }}"
                                                data-topik="{{ $item->topik_praktik }}"
                                                data-tanggal="{{ $item->tanggal }}"
                                                data-jam-mulai="{{ substr((string) $item->jam_mulai, 0, 5) }}"
                                                data-jam-selesai="{{ substr((string) $item->jam_selesai, 0, 5) }}"
                                                data-alat="{{ $item->alat_bahan_digunakan }}"
                                                data-status="{{ $item->status }}"
                                                title="Ubah Detail Jadwal">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-status-lab"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                data-status="{{ $item->status }}"
                                                data-laporan="{{ $item->laporan_kerusakan }}" title="Update Status Sesi">
                                                <i class="fas fa-sliders" style="color: var(--success, #10b981);"></i>
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" class="btn-icon btn-delete-jadwal"
                                                data-id="{{ $item->id }}" data-mapel="{{ $item->mata_pelajaran }}"
                                                title="Hapus Jadwal" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-folder-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada jadwal praktik yang cocok dengan filter.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($list->hasPages())
                <div class="datatable-footer">
                    @include('partials.datatable-pagination', ['paginator' => $list])
                </div>
            @endif
        </div>
        @endif

        @include('dashboard.laboran.partials.jadwal-modal-mingguan')
        @include('dashboard.laboran.partials.jadwal-modal-semester')
        @include('dashboard.laboran.partials.jadwal-modal-pilih-mode')
        @include('dashboard.laboran.partials.jadwal-modal-status')
    </div>

    @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script
                src="{{ asset('js/laboran-jadwal.js') }}?v={{ file_exists(public_path('js/laboran-jadwal.js')) ? filemtime(public_path('js/laboran-jadwal.js')) : time() }}">
            </script>
        @endpush
    @endsection
