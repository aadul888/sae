@extends('layouts.dashboard')

@section('title', 'Master Data — Jadwal KBM — SAE')
@section('dash_title', 'Jadwal Kegiatan Belajar Mengajar (KBM)')

@section('content')
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-alt text-primary me-2"></i> Master Data — Jadwal KBM
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Visualisasi jadwal mengajar guru per rombel secara modular dan anti-bentrok, terintegrasi ke presensi dan
                agenda KBM.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- View Mode Switcher -->
            <div
                style="display: inline-flex; background: var(--bg-hover); padding: 3px; border-radius: 8px; border: 1px solid var(--border-color);">
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                    class="btn {{ $viewMode === 'grid' ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: 0.8rem; border: none; border-radius: 6px; box-shadow: none;">
                    <i class="fas fa-table-cells me-1"></i> Matriks Grid
                </a>
                <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['view_mode' => 'table'])) }}"
                    class="btn {{ $viewMode === 'table' ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 12px; font-size: 0.8rem; border: none; border-radius: 6px; box-shadow: none;">
                    <i class="fas fa-list me-1"></i> Rekap Tabel
                </a>
            </div>

            <!-- Dropdown Cetak Jadwal Multi-Format -->
            <div style="position: relative; display: inline-block;">
                <button type="button" class="btn btn-outline" id="btnDropdownCetak"
                    style="padding: 8px 14px; font-size: 0.85rem;">
                    <i class="fas fa-print me-1"></i> Cetak Jadwal <i class="fas fa-chevron-down ms-1"
                        style="font-size: 0.7rem;"></i>
                </button>
                <div id="dropdownMenuCetak"
                    style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); min-width: 220px; z-index: 1000; overflow: hidden;">
                    <a href="{{ route('dashboard.jadwal-kbm.cetak-induk') }}" target="_blank"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: 0.84rem; border-bottom: 1px solid var(--border-color);">
                        <i class="fas fa-table text-primary" style="width: 18px;"></i>
                        <div>
                            <strong>Jadwal Induk Sekolah</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Matriks besar seluruh kelas</div>
                        </div>
                    </a>
                    <a href="{{ route('dashboard.jadwal-kbm.cetak-guru') }}" target="_blank"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: 0.84rem; border-bottom: 1px solid var(--border-color);">
                        <i class="fas fa-chalkboard-user text-success" style="width: 18px;"></i>
                        <div>
                            <strong>Jadwal per Guru</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Kartu jadwal mengajar GTK</div>
                        </div>
                    </a>
                    <a href="{{ route('dashboard.jadwal-kbm.cetak-rombel') }}" target="_blank"
                        style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: var(--text-color); text-decoration: none; font-size: 0.84rem;">
                        <i class="fas fa-school text-warning" style="width: 18px;"></i>
                        <div>
                            <strong>Jadwal per Kelas</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Format siap tempel di kelas</div>
                        </div>
                    </a>
                </div>
            </div>

            @if ($canUpdate)
                <button type="button" class="btn btn-outline" id="btnBukaPreferensiGuru"
                    style="padding: 8px 14px; font-size: 0.85rem;" title="Atur Hari Off dan Jam Berhalangan Guru">
                    <i class="fas fa-user-clock me-1"></i> Preferensi Guru
                </button>
                <button type="button" class="btn btn-outline" id="btnBukaPengaturanSlot"
                    style="padding: 8px 14px; font-size: 0.85rem;" title="Atur Jam Pelajaran, Durasi JP, dan Istirahat">
                    <i class="fas fa-sliders me-1"></i> Atur Jam Pelajaran
                </button>
            @endif

            @if ($canCreate)
                <button type="button" class="btn" id="btnBukaAutoGenerate"
                    style="padding: 8px 16px; font-size: 0.85rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); color: #fff; font-weight: 700; border: none; box-shadow: 0 4px 12px rgba(99,102,241,0.35);">
                    <i class="fas fa-wand-magic-sparkles me-1"></i> Tombol Sakti (Auto-Generate)
                </button>
                <button type="button" class="btn btn-primary" id="btnTambahJadwal"
                    style="padding: 8px 16px; font-size: 0.85rem;">
                    <i class="fas fa-plus me-1"></i> Tambah Manual
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total_jadwal'], 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Total Jadwal KBM</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['total_rombel'] }}
                </div>
                <div class="dash-stat-label">Rombel Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ $summary['total_guru'] }}
                </div>
                <div class="dash-stat-label">Guru Terjadwal</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.35rem;">
                    {{ number_format($summary['total_jp'], 0, ',', '.') }} JP
                </div>
                <div class="dash-stat-label">Total Jam Pelajaran</div>
            </div>
        </div>
    </div>

    @if ($viewMode === 'grid')
        <!-- ==================== TAMPILAN MATRIKS GRID MODULAR ==================== -->
        <!-- Tab Pemilihan Hari -->
        <div class="card" id="jadwalTabBarContainer" style="padding: 12px 16px; margin-bottom: 16px;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px;">
                <!-- Tab Hari Buttons -->
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span
                        style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px;">
                        <i class="fas fa-calendar-day me-1"></i> Hari:
                    </span>
                    @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                        @php
                            $isDayActive = $selectedHari === $h;
                            $count = $countPerHari[$h] ?? 0;
                            $hJp = $dailySlotCounts[$h] ?? ($h === 'Jumat' ? 5 : $pengaturan->total_slot_jp ?? 10);
                        @endphp
                        <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['hari' => $h, 'view_mode' => 'grid'])) }}"
                            class="btn {{ $isDayActive ? 'btn-primary' : 'btn-outline' }} btn-day-tab"
                            data-hari="{{ $h }}"
                            style="padding: 6px 14px; font-size: 0.82rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
                            <span>{{ $h }}</span>
                            @if ($hJp > 0)
                                <span class="badge"
                                    style="{{ $isDayActive ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: var(--bg-hover); color: var(--text-muted);' }} font-size: 0.72rem; padding: 2px 6px; border-radius: 10px;"
                                    title="{{ $hJp }} JP ({{ $count }} jadwal)">
                                    {{ $hJp }} JP
                                </span>
                            @else
                                <span class="badge"
                                    style="{{ $isDayActive ? 'background: rgba(255,255,255,0.25); color: #fff;' : 'background: var(--bg-hover); color: var(--text-muted); opacity: 0.75;' }} font-size: 0.68rem; padding: 2px 6px; border-radius: 10px;"
                                    title="0 JP (Libur / Tanpa Jam Pelajaran)">
                                    Libur
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <!-- Filter Tingkat Rombel -->
                <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                    <span
                        style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-right: 4px;">
                        Tingkat:
                    </span>
                    @foreach (['' => 'Semua', '10' => 'Kelas X', '11' => 'Kelas XI', '12' => 'Kelas XII'] as $tVal => $tLabel)
                        <a href="{{ route('dashboard.jadwal-kbm.index', array_merge(request()->query(), ['tingkat' => $tVal, 'view_mode' => 'grid', 'hari' => $selectedHari])) }}"
                            class="btn {{ $tingkat === $tVal ? 'btn-primary' : 'btn-outline' }} btn-tingkat-filter"
                            data-tingkat="{{ $tVal }}"
                            style="padding: 4px 10px; font-size: 0.78rem; border-radius: 6px;">
                            {{ $tLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div
                style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; font-size: 0.8rem; color: var(--text-muted);">
                <div style="display: flex; align-items: center; gap: 8px;">
                    @if (count($timeSlots) > 0)
                        <span class="badge badge-primary" style="font-size: 0.76rem; padding: 4px 8px;">
                            <i class="fas fa-clock me-1"></i> KBM {{ $selectedHari }}: {{ count($timeSlots) }} JP
                        </span>
                        <span style="font-weight: 600; color: var(--text-color);">
                            {{ $timeSlots[1]['mulai'] ?? '07:15' }} s/d {{ end($timeSlots)['selesai'] ?? '' }} WIB
                        </span>
                    @else
                        <span class="badge" style="background: var(--bg-hover); color: var(--text-muted); font-size: 0.76rem; padding: 4px 8px; border: 1px solid var(--border-color);">
                            <i class="fas fa-calendar-xmark me-1"></i> KBM {{ $selectedHari }}: 0 JP
                        </span>
                        <span style="font-weight: 600; color: var(--text-muted);">
                            Libur / Tanpa Jam Pelajaran
                        </span>
                    @endif
                </div>
                <div style="display: flex; gap: 14px;">
                    <span><span
                            style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: rgba(99,102,241,0.2); border: 1px solid var(--primary); margin-right: 4px;"></span>
                        Terjadwal</span>
                    <span><span
                            style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: var(--bg-hover); border: 1px dashed var(--border-color); margin-right: 4px;"></span>
                        Kosong (Bisa Diisi)</span>
                </div>
            </div>
        </div>

        <!-- Timetable Matrix Grid Container -->
        <div class="card table-responsive-stack" id="gridMatrixContainer"
            style="padding: 0; margin-bottom: 24px; overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0; min-width: 900px;">
                <thead>
                    <tr>
                        <!-- Header Kolom Jam Pelajaran (Sticky Left) -->
                        <th
                            style="position: sticky; left: 0; z-index: 20; background: var(--bg-card); width: 130px; min-width: 130px; padding: 14px 12px; border-bottom: 2px solid var(--border-color); border-right: 2px solid var(--border-color); font-size: 0.78rem; font-weight: 800; color: var(--text-color); text-transform: uppercase;">
                            <div style="display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-clock text-primary"></i> Jam Pelajaran
                            </div>
                        </th>

                        <!-- Header Kolom Seluruh Kelas / Rombel -->
                        @forelse ($gridRombels as $r)
                            <th
                                style="padding: 12px 10px; border-bottom: 2px solid var(--border-color); border-right: 1px solid var(--border-color); text-align: center; min-width: 180px; max-width: 220px; background: var(--bg-card);">
                                <div
                                    style="font-weight: 800; font-size: 0.88rem; color: var(--text-color); margin-bottom: 2px;">
                                    {{ $r->nama }}
                                </div>
                                <span class="badge"
                                    style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.7rem; padding: 2px 6px; font-weight: 700;">
                                    Tingkat {{ $r->tingkat_pendidikan_id }}
                                </span>
                            </th>
                        @empty
                            <th style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                Tidak ada rombel pada tingkat ini.
                            </th>
                        @endforelse
                    </tr>
                </thead>
                <tbody>
                    @forelse ($timeSlots as $slotKey => $slot)
                        <tr style="height: 64px;">
                            <!-- Sticky Left: Label Jam Pelajaran -->
                            <td
                                style="position: sticky; left: 0; z-index: 10; background: var(--bg-card); height: 64px; box-sizing: border-box; padding: 6px 10px; border-bottom: 1px solid var(--border-color); border-right: 2px solid var(--border-color); vertical-align: middle;">
                                <div
                                    style="font-weight: 800; font-size: 0.82rem; color: var(--text-color); display: flex; align-items: center; justify-content: space-between;">
                                    <span>Jam Ke-{{ $slot['ke'] }}</span>
                                    <span class="badge"
                                        style="background: var(--bg-hover); color: var(--text-muted); font-size: 0.65rem; font-weight: 700; padding: 2px 5px;">JP</span>
                                </div>
                                <div
                                    style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px; font-weight: 600;">
                                    {{ $slot['mulai'] }} - {{ $slot['selesai'] }}
                                </div>
                            </td>

                            <!-- Cell Per Rombel -->
                            @foreach ($gridRombels as $r)
                                @php
                                    $rId = $r->rombongan_belajar_id;
                                    $item = $gridMatrix[$rId][$slotKey] ?? null;
                                    $isOccupied = isset($gridOccupied[$rId][$slotKey]);
                                @endphp

                                @if ($item)
                                    @php
                                        $durasi = max(1, (int) $item->jam_ke_selesai - (int) $item->jam_ke_mulai + 1);
                                        $isUpacara = $item->mata_pelajaran_id === 'UPACARA';
                                        $isPembiasaan = $item->mata_pelajaran_id === 'PEMBIASAAN';
                                        $isIstirahat = $item->mata_pelajaran_id === 'ISTIRAHAT';
                                        $isRoutine = $isUpacara || $isPembiasaan || $isIstirahat;
                                        $borderCol = $isUpacara
                                            ? '#ef4444'
                                            : ($isPembiasaan
                                                ? '#10b981'
                                                : ($isIstirahat
                                                    ? '#f59e0b'
                                                    : 'var(--primary)'));
                                        $bgCard = $isUpacara
                                            ? 'rgba(239,68,68,0.03)'
                                            : ($isPembiasaan
                                                ? 'rgba(16,185,129,0.03)'
                                                : ($isIstirahat
                                                    ? 'rgba(245,158,11,0.04)'
                                                    : 'var(--bg-card)'));
                                    @endphp
                                    <!-- Sel Terisi Jadwal (Spanning Multi JP) -->
                                    <td rowspan="{{ $durasi }}"
                                        style="height: {{ $durasi * 64 }}px; padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: top; background: rgba(99,102,241,0.03); box-sizing: border-box;">
                                        <div class="jadwal-grid-card"
                                            style="background: {{ $bgCard }}; border: 1px solid {{ $isRoutine ? $borderCol : 'rgba(99,102,241,0.35)' }}; border-left: 4px solid {{ $borderCol }}; border-radius: 7px; padding: 6px 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); position: relative; height: calc(100% - 2px); min-height: {{ max(54, $durasi * 64 - 10) }}px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between;">
                                            <div>
                                                <!-- Mapel Title -->
                                                <div style="font-weight: 700; font-size: 0.8rem; color: var(--text-color); line-height: 1.25; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: {{ $durasi > 1 ? '3' : '1' }}; -webkit-box-orient: vertical; overflow: hidden;"
                                                    title="{{ $item->nama_mata_pelajaran }}">
                                                    @if ($isUpacara)
                                                        <i class="fas fa-flag text-danger me-1"></i>
                                                    @elseif ($isPembiasaan)
                                                        <i class="fas fa-hands-praying text-success me-1"></i>
                                                    @elseif ($isIstirahat)
                                                        <i class="fas fa-mug-hot text-warning me-1"></i>
                                                    @endif
                                                    {{ $item->nama_mata_pelajaran }}
                                                </div>

                                                <!-- Guru / Subtitle -->
                                                <div
                                                    style="font-size: 0.73rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
                                                    @if ($isUpacara)
                                                        <i class="fas fa-users text-danger"
                                                            style="font-size: 0.7rem; flex-shrink: 0;"></i>
                                                        <span
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Dewan
                                                            Guru &amp; Siswa</span>
                                                    @elseif ($isPembiasaan)
                                                        <i class="fas fa-users text-success"
                                                            style="font-size: 0.7rem; flex-shrink: 0;"></i>
                                                        <span
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Wali
                                                            Kelas &amp; Guru</span>
                                                    @elseif ($isIstirahat)
                                                        <i class="fas fa-mug-hot text-warning"
                                                            style="font-size: 0.7rem; flex-shrink: 0;"></i>
                                                        <span
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;">Siswa
                                                            &amp; Dewan Guru</span>
                                                    @else
                                                        <i class="fas fa-chalkboard-user text-primary"
                                                            style="font-size: 0.7rem; flex-shrink: 0;"></i>
                                                        <span
                                                            style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px;"
                                                            title="{{ $item->nama_guru }}">
                                                            {{ $item->nama_guru ?: 'Belum Ada Guru' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($durasi >= 4)
                                                    <div
                                                        style="font-size: 0.68rem; color: var(--primary); font-weight: 600; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                                        <i class="fas fa-cubes"></i> Blok Praktik ({{ $durasi }} JP)
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Badges & Action Buttons Footer -->
                                            <div
                                                style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                                                <div
                                                    style="display: flex; gap: 3px; flex-wrap: wrap; align-items: center;">
                                                    @if ($isUpacara)
                                                        <span class="badge"
                                                            style="background: rgba(239,68,68,0.12); color: #ef4444; font-size: 0.65rem; padding: 2px 5px; font-weight: 700;">
                                                            Upacara
                                                        </span>
                                                    @elseif ($isPembiasaan)
                                                        <span class="badge"
                                                            style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.65rem; padding: 2px 5px; font-weight: 700;">
                                                            Pembiasaan
                                                        </span>
                                                    @elseif ($isIstirahat)
                                                        <span class="badge"
                                                            style="background: rgba(245,158,11,0.12); color: #f59e0b; font-size: 0.65rem; padding: 2px 5px; font-weight: 700;">
                                                            Istirahat
                                                        </span>
                                                    @else
                                                        <span class="badge badge-primary"
                                                            style="font-size: 0.65rem; padding: 2px 5px; font-weight: 700;">
                                                            {{ $durasi }} JP
                                                        </span>
                                                    @endif

                                                    @if (!empty($item->ruangan))
                                                        <span class="badge"
                                                            style="background: var(--bg-hover); color: var(--text-color); font-size: 0.65rem; padding: 2px 5px; border: 1px solid var(--border-color);"
                                                            title="Ruangan: {{ $item->ruangan }}">
                                                            <i class="fas fa-door-open"></i> {{ $item->ruangan }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- Action Buttons (Quick Edit & Delete) -->
                                                @if ($isRoutine)
                                                    <span class="badge"
                                                        style="background: var(--bg-hover); color: var(--text-muted); font-size: 0.62rem; padding: 2px 5px;"
                                                        title="Disinkronkan otomatis dari Pengaturan KBM">
                                                        <i class="fas fa-sync-alt me-1"></i> Rutin
                                                    </span>
                                                @elseif ($canUpdate || $canDelete)
                                                    <div
                                                        style="display: flex; align-items: center; gap: 2px; flex-shrink: 0;">
                                                        @if ($canUpdate)
                                                            <button type="button" class="btn-icon btn-edit-jadwal"
                                                                title="Edit Jadwal" data-id="{{ $item->id }}"
                                                                data-rombel="{{ $item->rombongan_belajar_id }}"
                                                                data-pembelajaran="{{ $item->pembelajaran_id }}"
                                                                data-hari="{{ $item->hari }}"
                                                                data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                                                                data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                                                                data-jam-mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                                                                data-jam-selesai="{{ substr($item->jam_selesai, 0, 5) }}"
                                                                data-ruangan="{{ $item->ruangan }}"
                                                                data-keterangan="{{ $item->keterangan }}"
                                                                style="width: 22px; height: 22px; font-size: 0.68rem; padding: 0;">
                                                                <i class="fas fa-pencil text-primary"></i>
                                                            </button>
                                                        @endif

                                                        @if ($canDelete)
                                                            <form
                                                                action="{{ route('dashboard.jadwal-kbm.destroy', $item->id) }}"
                                                                method="POST" data-confirm="delete"
                                                                data-name="{{ $item->nama_mata_pelajaran }} ({{ $r->nama }})"
                                                                style="display: inline; margin: 0;">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn-icon"
                                                                    title="Hapus Jadwal"
                                                                    style="width: 22px; height: 22px; font-size: 0.68rem; padding: 0;">
                                                                    <i class="fas fa-trash text-danger"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                @elseif (!$isOccupied)
                                    <!-- Sel Kosong (Interaktif Click-to-Assign) -->
                                    <td
                                        style="padding: 4px; border-bottom: 1px solid var(--border-color); border-right: 1px solid var(--border-color); vertical-align: middle; text-align: center; height: 64px; box-sizing: border-box;">
                                        @if ($canCreate)
                                            <button type="button" class="btn-quick-slot"
                                                title="Klik untuk mengisi jadwal di kelas {{ $r->nama }} Jam Ke-{{ $slot['ke'] }}"
                                                data-rombel-id="{{ $r->rombongan_belajar_id }}"
                                                data-rombel-nama="{{ $r->nama }}" data-hari="{{ $selectedHari }}"
                                                data-jam-ke="{{ $slot['ke'] }}" data-jam-mulai="{{ $slot['mulai'] }}"
                                                data-jam-selesai="{{ $slot['selesai'] }}"
                                                style="width: 100%; height: 100%; min-height: 54px; border: 1px dashed var(--border-color); border-radius: 6px; background: transparent; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; color: var(--text-muted); transition: all 0.2s ease;">
                                                <i class="fas fa-plus" style="font-size: 0.75rem;"></i>
                                                <span style="font-size: 0.65rem; font-weight: 600;">Isi Jadwal</span>
                                            </button>
                                        @else
                                            <div
                                                style="min-height: 54px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 0.72rem;">
                                                -
                                            </div>
                                        @endif
                                    </td>
                                @endif
                                <!-- Jika isOccupied bernilai true, jangan render <td> karena cell atasnya sudah me-rowspan -->
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($gridRombels) + 1) }}" style="padding: 48px 24px; text-align: center; color: var(--text-muted); background: var(--bg-card);">
                                <i class="fas fa-calendar-xmark mb-3" style="font-size: 2.2rem; opacity: 0.4;"></i>
                                <div style="font-weight: 700; font-size: 1rem; color: var(--text-color); margin-bottom: 4px;">Tidak Ada KBM pada Hari {{ $selectedHari }}</div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">Total JP untuk hari {{ $selectedHari }} diatur 0 JP (Libur). Klik tombol <strong>Atur Jam Pelajaran</strong> untuk menyesuaikan jam pelajaran.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <!-- ==================== TAMPILAN TABEL DATA REKAP ==================== -->
        <!-- Toolbar & Filter -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterHari" class="toolbar-filter-select">
                        <option value="">Semua Hari</option>
                        @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                            <option value="{{ $h }}" {{ $hari === $h ? 'selected' : '' }}>{{ $h }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterRombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $rombelId === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterGuru" class="toolbar-filter-select">
                        <option value="">Semua Guru Pengampu</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}" {{ $guruId === $g->ptk_id ? 'selected' : '' }}>
                                {{ $g->nama }}
                            </option>
                        @endforeach
                    </select>

                    @if ($q || $hari || $rombelId || $guruId)
                        <a href="{{ route('dashboard.jadwal-kbm.index', ['view_mode' => 'table']) }}"
                            class="btn btn-outline btn-responsive-icon" style="padding: 7px 12px; font-size: 0.8rem;"
                            title="Reset filter">
                            <i class="fas fa-undo"></i> <span class="btn-responsive-text">Reset</span>
                        </a>
                    @endif
                </div>

                <div class="toolbar-search-box">
                    <i class="fas fa-search toolbar-search-icon"></i>
                    <input type="text" id="liveSearchInput" class="toolbar-search-input"
                        placeholder="Cari mapel, rombel, guru, ruang..." value="{{ $q }}">
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: var(--bg-hover);">
                        <th
                            style="width: 45px; text-align: center; padding: 12px 8px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            No
                        </th>
                        <th class="sortable-th {{ $sort === 'hari' ? 'sorted' : '' }}" data-sort="hari"
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Hari &amp; Waktu
                            <span class="sort-icon">{!! $sort === 'hari' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'nama_rombel' ? 'sorted' : '' }}" data-sort="nama_rombel"
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Rombel / Kelas
                            <span class="sort-icon">{!! $sort === 'nama_rombel' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                            data-sort="nama_mata_pelajaran"
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Mata Pelajaran
                            <span class="sort-icon">{!! $sort === 'nama_mata_pelajaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'nama_guru' ? 'sorted' : '' }}" data-sort="nama_guru"
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Guru Pengampu
                            <span class="sort-icon">{!! $sort === 'nama_guru' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Ruangan
                        </th>
                        @if ($canUpdate || $canDelete)
                            <th
                                style="width: 100px; text-align: center; padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Aksi
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($list as $index => $item)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td
                                style="text-align: center; padding: 12px 8px; color: var(--text-muted); font-size: 0.85rem;">
                                {{ $list->firstItem() + $index }}
                            </td>
                            <td style="padding: 12px 14px;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    @php
                                        $hariBadgeColors = [
                                            'Senin' => 'background: rgba(99,102,241,0.15); color: #6366f1;',
                                            'Selasa' => 'background: rgba(16,185,129,0.15); color: #10b981;',
                                            'Rabu' => 'background: rgba(245,158,11,0.15); color: #f59e0b;',
                                            'Kamis' => 'background: rgba(6,182,212,0.15); color: #06b6d4;',
                                            'Jumat' => 'background: rgba(236,72,153,0.15); color: #ec4899;',
                                            'Sabtu' => 'background: rgba(139,92,246,0.15); color: #8b5cf6;',
                                        ];
                                        $badgeStyle =
                                            $hariBadgeColors[$item->hari] ??
                                            'background: rgba(107,114,128,0.15); color: #6b7280;';
                                    @endphp
                                    <span class="badge"
                                        style="{{ $badgeStyle }} font-weight: 700; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem;">
                                        {{ $item->hari }}
                                    </span>
                                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-color);">
                                        {{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }}
                                    </span>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    <i class="fas fa-layer-group me-1"></i>
                                    @if ($item->jam_ke_mulai === $item->jam_ke_selesai)
                                        Jam ke-{{ $item->jam_ke_mulai }} (1 JP)
                                    @else
                                        Jam ke-{{ $item->jam_ke_mulai }} s.d {{ $item->jam_ke_selesai }}
                                        ({{ $item->jam_ke_selesai - $item->jam_ke_mulai + 1 }} JP)
                                    @endif
                                </div>
                            </td>
                            <td style="padding: 12px 14px;">
                                <span class="badge badge-primary"
                                    style="font-size: 0.82rem; padding: 4px 8px; font-weight: 700;">
                                    <i class="fas fa-chalkboard me-1"></i> {{ $item->nama_rombel ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 600; color: var(--text-color); font-size: 0.88rem;">
                                    {{ $item->nama_mata_pelajaran }}
                                </div>
                                @if (!empty($item->keterangan))
                                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-info-circle me-1"></i> {{ $item->keterangan }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 600; color: var(--text-color); font-size: 0.88rem;">
                                    {{ $item->nama_guru ?: 'Belum Ditugaskan' }}
                                </div>
                                @if (!empty($item->nip_guru))
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        NIP: {{ $item->nip_guru }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;">
                                @if (!empty($item->ruangan))
                                    <span class="badge"
                                        style="background: var(--bg-hover); color: var(--text-color); border: 1px solid var(--border-color); font-size: 0.78rem;">
                                        <i class="fas fa-door-open me-1"></i> {{ $item->ruangan }}
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">-</span>
                                @endif
                            </td>
                            @if ($canUpdate || $canDelete)
                                <td style="text-align: center; padding: 12px 14px;">
                                    <div class="table-actions" style="display: flex; gap: 6px; justify-content: center;">
                                        @if ($canUpdate)
                                            <button type="button" class="btn-icon btn-edit-jadwal" title="Edit Jadwal"
                                                data-id="{{ $item->id }}"
                                                data-rombel="{{ $item->rombongan_belajar_id }}"
                                                data-pembelajaran="{{ $item->pembelajaran_id }}"
                                                data-hari="{{ $item->hari }}"
                                                data-jam-ke-mulai="{{ $item->jam_ke_mulai }}"
                                                data-jam-ke-selesai="{{ $item->jam_ke_selesai }}"
                                                data-jam-mulai="{{ substr($item->jam_mulai, 0, 5) }}"
                                                data-jam-selesai="{{ substr($item->jam_selesai, 0, 5) }}"
                                                data-ruangan="{{ $item->ruangan }}"
                                                data-keterangan="{{ $item->keterangan }}">
                                                <i class="fas fa-pencil text-primary"></i>
                                            </button>
                                        @endif

                                        @if ($canDelete)
                                            <form action="{{ route('dashboard.jadwal-kbm.destroy', $item->id) }}"
                                                method="POST" data-confirm="delete"
                                                data-name="{{ $item->nama_mata_pelajaran }} ({{ $item->nama_rombel }})"
                                                style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon" title="Hapus Jadwal">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canUpdate || $canDelete ? 7 : 6 }}"
                                style="text-align: center; padding: 40px 16px; color: var(--text-muted);">
                                <div style="font-size: 2.2rem; margin-bottom: 10px; color: var(--border-color);">
                                    <i class="fas fa-calendar-xmark"></i>
                                </div>
                                <div
                                    style="font-weight: 700; font-size: 1rem; color: var(--text-color); margin-bottom: 4px;">
                                    Belum Ada Jadwal KBM
                                </div>
                                <p
                                    style="font-size: 0.85rem; margin-bottom: 16px; max-width: 420px; margin-left: auto; margin-right: auto;">
                                    Data jadwal pembelajaran mingguan belum tersedia atau tidak cocok dengan filter
                                    pencarian.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Custom Pagination Baku SAE -->
        @if ($list->hasPages())
            <div class="custom-pagination">
                @if ($list->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif

                @php
                    $cur = $list->currentPage();
                    $last = $list->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp

                @if ($from > 1)
                    <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif

                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $list->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor

                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif

                @if ($list->hasMorePages())
                    <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @endif

    <!-- Modal Form Tambah / Edit Jadwal KBM (Standard SAE with z-index 99999) -->
    <div id="modalJadwalKbm" class="sae-modal"
        data-route-pembelajaran="{{ route('dashboard.jadwal-kbm.pembelajaran-by-rombel') }}"
        data-route-conflict="{{ route('dashboard.jadwal-kbm.check-conflict') }}"
        data-route-base="{{ route('dashboard.jadwal-kbm.index') }}" data-slots='@json($timeSlots)'
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 id="modalJadwalTitle"
                    style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-calendar-plus text-primary me-2"></i> Tambah Jadwal KBM
                </h3>
                <button type="button" id="btnTutupModalJadwal"
                    style="background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formJadwalKbm">
                @csrf
                <input type="hidden" id="jadwalId" name="id" value="">

                <!-- Feedback Validasi Anti-Bentrok -->
                <div id="conflictAlertBox"
                    style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.84rem;">
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modalRombelId"
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Rombongan Belajar (Kelas) <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modalRombelId" name="rombongan_belajar_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                        required>
                        <option value="">-- Pilih Rombel --</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modalPembelajaranId"
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Mata Pelajaran &amp; Guru Pengampu <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="modalPembelajaranId" name="pembelajaran_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                        required disabled>
                        <option value="">-- Pilih Rombel Terlebih Dahulu --</option>
                    </select>
                    <small id="pembelajaranHint"
                        style="color: var(--text-muted); font-size: 0.78rem; margin-top: 4px; display: block;">
                        Daftar mapel dan alokasi jam mengajar disinkronkan langsung dari data Pembelajaran Dapodik.
                    </small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalHari"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Hari <span style="color: #ef4444;">*</span>
                        </label>
                        <select id="modalHari" name="hari" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                            @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalRuangan"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Ruangan / Lab (Opsional)
                        </label>
                        <input type="text" id="modalRuangan" name="ruangan" class="form-control"
                            placeholder="Contoh: Lab RPL 1, R. 12"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalJamKeMulai"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Pelajaran Mulai (JP Ke-) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="modalJamKeMulai" name="jam_ke_mulai" min="1" max="20"
                            value="1" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="modalJamKeSelesai"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Pelajaran Selesai (JP Ke-) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="modalJamKeSelesai" name="jam_ke_selesai" min="1"
                            max="20" value="2" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>
                </div>

                <!-- Durasi JP Quick Helper Buttons -->
                <div style="margin-bottom: 16px; padding: 10px; background: var(--bg-hover); border-radius: 6px;">
                    <span
                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 6px;">
                        Pilih Cepat Durasi JP:
                    </span>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        @foreach ([1 => '1 JP', 2 => '2 JP', 3 => '3 JP', 4 => '4 JP', 5 => '5 JP', 6 => '6 JP', 8 => '8 JP', 9 => '9 JP (Maks)'] as $durVal => $durText)
                            <button type="button" class="btn btn-outline btn-quick-durasi"
                                data-durasi="{{ $durVal }}"
                                style="padding: 3px 8px; font-size: 0.74rem; border-radius: 4px;">
                                {{ $durText }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="modalJamMulai"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Waktu Mulai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="time" id="modalJamMulai" name="jam_mulai" value="07:15" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="modalJamSelesai"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Waktu Selesai <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="time" id="modalJamSelesai" name="jam_selesai" value="08:45"
                            class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="modalKeterangan"
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Keterangan Tambahan (Opsional)
                    </label>
                    <textarea id="modalKeterangan" name="keterangan" rows="2" class="form-control"
                        placeholder="Catatan jam KBM, materi khusus, dll."
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"></textarea>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalJadwal" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanJadwal" class="btn btn-primary"
                        style="padding: 9px 20px; font-size: 0.85rem;">
                        <i class="fas fa-save me-1"></i> Simpan Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Pengaturan Jam Pelajaran & Durasi per JP -->
    <div id="modalPengaturanSlot" class="sae-modal"
        data-route-pengaturan="{{ route('dashboard.jadwal-kbm.pengaturan') }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-sliders text-primary me-2"></i> Pengaturan Jam Pelajaran &amp; Waktu KBM
                </h3>
                <button type="button" id="btnTutupModalPengaturan"
                    style="background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPengaturanSlot">
                @csrf
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="setJamMulai"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Jam Mulai KBM (JP 1) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="time" id="setJamMulai" name="jam_mulai_kbm"
                            value="{{ substr($pengaturan->jam_mulai_kbm ?? '07:15:00', 0, 5) }}" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="setDurasiJp"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Durasi per JP (Menit) <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" id="setDurasiJp" name="durasi_per_jp" min="20" max="90"
                            value="{{ $pengaturan->durasi_per_jp ?? 45 }}" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                            required>
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Standar SMK/SMA: 40–45 menit</small>
                    </div>
                </div>

                <!-- Konfigurasi Jam Selesai & Total JP per Hari (Bisa Beda Hari) -->
                <div
                    style="margin-bottom: 16px; padding: 12px; background: var(--bg-hover); border-radius: 8px; border: 1px solid var(--border-color);">
                    <div
                        style="font-weight: 700; font-size: 0.84rem; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-business-time text-primary me-1"></i> Jam Selesai &amp; Total JP per
                            Hari</span>
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Sesuaikan hari yang selesai lebih awal
                            (misal Jumat)</small>
                    </div>
                    @php
                        $dailySlotsMap = \App\Models\JadwalPengaturan::getDailySlotCounts();
                    @endphp
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 8px;">
                        @foreach (\App\Models\JadwalKbm::HARI_LIST as $dh)
                            @php
                                $curJp =
                                    $dailySlotsMap[$dh] ?? ($dh === 'Jumat' ? 5 : $pengaturan->total_slot_jp ?? 10);
                                $curSelesai = \App\Models\JadwalPengaturan::calculateJamSelesai($curJp);
                            @endphp
                            <div
                                style="background: var(--bg-card); padding: 8px 6px; border-radius: 6px; border: 1px solid var(--border-color); text-align: center;">
                                <strong
                                    style="font-size: 0.8rem; color: var(--text-color); display: block; margin-bottom: 4px;">{{ $dh }}</strong>
                                <div
                                    style="display: flex; align-items: center; justify-content: center; gap: 4px; margin-bottom: 4px;">
                                    <input type="number" name="slot_harian[{{ $dh }}][total_jp]"
                                        value="{{ $curJp }}" min="0" max="16" placeholder="0" class="form-control input-slot-harian"
                                        data-hari="{{ $dh }}"
                                        style="width: 50px; padding: 4px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.8rem; text-align: center; font-weight: 700;">
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">JP</span>
                                </div>
                                <div style="font-size: 0.68rem; color: var(--text-muted); font-weight: 600;">
                                    Selesai: <span class="badge-jam-selesai" data-hari="{{ $dh }}"
                                        style="color: {{ $curJp > 0 ? 'var(--primary)' : 'var(--text-muted)' }}; font-weight: 700;">{{ $curJp > 0 ? $curSelesai : '-' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 8px;">
                        <i class="fas fa-info-circle me-1"></i> Contoh: Hari Jumat diatur <strong>5 JP (selesai sebelum
                            Sholat Jumat)</strong>, sedangkan Sabtu diatur <strong>0 JP (Libur)</strong>.
                    </small>
                </div>

                <!-- Konfigurasi Alokasi Target JP per Tingkat (Standar Kurikulum SMK) -->
                @php
                    $jpTingkatSettings = \App\Models\JadwalPengaturan::getJpTingkat();
                @endphp
                <div style="margin-bottom: 16px; padding: 12px; background: rgba(99, 102, 241, 0.04); border-radius: 8px; border: 1px solid rgba(99, 102, 241, 0.25);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <span style="font-weight: 700; font-size: 0.84rem; color: var(--text-color);">
                            <i class="fas fa-graduation-cap text-primary me-1"></i> Target Alokasi JP per Tingkat (SMK)
                        </span>
                        <span class="badge" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.68rem; font-weight: 700;">
                            Struktur SMK
                        </span>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.72rem; margin-bottom: 10px; line-height: 1.35;">
                        Batas maksimal alokasi JP per minggu per rombel. Pada kelas XII mencakup alokasi mapel PKL (Praktek Kerja Lapangan).
                    </p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <div style="background: var(--bg-card); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color); text-align: center;">
                            <strong style="font-size: 0.8rem; color: var(--text-color); display: block; margin-bottom: 4px;">Tingkat X</strong>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <input type="number" name="jp_tingkat[10]" value="{{ $jpTingkatSettings['10'] ?? 50 }}" min="20" max="80" class="form-control"
                                    style="width: 60px; padding: 4px 6px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.84rem; text-align: center; font-weight: 700;" required>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">JP/mgg</span>
                            </div>
                            <span style="font-size: 0.68rem; color: var(--text-muted); display: block; margin-top: 3px;">Standar: 50 JP</span>
                        </div>
                        <div style="background: var(--bg-card); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color); text-align: center;">
                            <strong style="font-size: 0.8rem; color: var(--text-color); display: block; margin-bottom: 4px;">Tingkat XI</strong>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <input type="number" name="jp_tingkat[11]" value="{{ $jpTingkatSettings['11'] ?? 48 }}" min="20" max="80" class="form-control"
                                    style="width: 60px; padding: 4px 6px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.84rem; text-align: center; font-weight: 700;" required>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">JP/mgg</span>
                            </div>
                            <span style="font-size: 0.68rem; color: var(--text-muted); display: block; margin-top: 3px;">Standar: 48 JP</span>
                        </div>
                        <div style="background: var(--bg-card); padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color); text-align: center;">
                            <strong style="font-size: 0.8rem; color: var(--text-color); display: block; margin-bottom: 4px;">Tingkat XII</strong>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <input type="number" name="jp_tingkat[12]" value="{{ $jpTingkatSettings['12'] ?? 46 }}" min="20" max="80" class="form-control"
                                    style="width: 60px; padding: 4px 6px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.84rem; text-align: center; font-weight: 700;" required>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">JP/mgg</span>
                            </div>
                            <span style="font-size: 0.68rem; color: #10b981; font-weight: 600; display: block; margin-top: 3px;">46 JP (Inc. PKL)</span>
                        </div>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.7rem; display: block; margin-top: 8px;">
                        <i class="fas fa-shield-halved text-success me-1"></i> Jadwal PKL tetap dipetakan di jadwal induk &amp; rombel, namun otomatis disembunyikan dari akun guru karena presensi &amp; KBM PKL dilakukan via aplikasi terpisah (<strong>ePKL</strong>).
                    </small>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; color: var(--text-color);">
                        Hari KBM Aktif
                    </label>
                    @php
                        $activeHariList = $pengaturan->hari_aktif ?? ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    @endphp
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @foreach (\App\Models\JadwalKbm::HARI_LIST as $h)
                            <label
                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; cursor: pointer; background: var(--bg-hover); padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                                <input type="checkbox" name="hari_aktif[]" class="check-hari-aktif" data-hari="{{ $h }}" value="{{ $h }}"
                                    {{ in_array($h, $activeHariList, true) ? 'checked' : '' }}>
                                {{ $h }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Waktu Istirahat Sekolah (Sisipkan Otomatis) -->
                @php
                    $breaks = $pengaturan->istirahat ?? [
                        ['aktif' => true, 'jam_ke' => 5, 'durasi_menit' => 30, 'nama' => 'Istirahat'],
                    ];
                    $b1 = $breaks[0] ?? ['aktif' => true, 'jam_ke' => 5, 'durasi_menit' => 30, 'nama' => 'Istirahat'];
                    $isB1Active = !isset($b1['aktif']) || !empty($b1['aktif']);
                @endphp
                <div
                    style="margin-bottom: 14px; padding: 12px; background: rgba(245, 158, 11, 0.04); border-radius: 8px; border: 1px solid rgba(245, 158, 11, 0.25);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label
                            style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.84rem; font-weight: 700; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" id="setIstirahatAktif" name="istirahat[0][aktif]" value="1"
                                {{ $isB1Active ? 'checked' : '' }}>
                            <span><i class="fas fa-mug-hot text-warning me-1"></i> Sisipkan Otomatis Waktu Istirahat</span>
                        </label>
                        <span class="badge"
                            style="background: rgba(245,158,11,0.12); color: #f59e0b; font-size: 0.68rem; font-weight: 700;">Rutin
                            Sekolah</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 8px;">
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Nama
                                Kegiatan:</span>
                            <input type="text" id="setIstirahatNama" name="istirahat[0][nama]" value="{{ $b1['nama'] ?? 'Istirahat' }}"
                                placeholder="Istirahat"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color);">
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                Jam Ke-:</span>
                            <input type="number" id="setIstirahatJamKe" name="istirahat[0][jam_ke]"
                                value="{{ $b1['jam_ke'] ?? ($b1['setelah_jp'] ?? 5) }}" min="1" max="16"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;"
                                required>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                (Mnt):</span>
                            <input type="number" id="setIstirahatDurasi" name="istirahat[0][durasi_menit]"
                                value="{{ $b1['durasi_menit'] ?? 30 }}" min="5" max="90"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;"
                                required>
                        </div>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 8px;">
                        <i class="fas fa-info-circle me-1"></i> Disisipkan ke seluruh hari KBM aktif pada jam ke- yang
                        ditentukan. Waktu belajar pada jam ini terkunci untuk istirahat siswa dan dewan guru.
                    </small>
                </div>

                <!-- Upacara Bendera Otomatis -->
                @php
                    $upacaraConfig = $pengaturan->upacara ?? [
                        'aktif' => true,
                        'hari' => 'Senin',
                        'jam_ke' => 1,
                        'durasi_menit' => 45,
                        'nama' => 'Upacara Bendera',
                    ];
                @endphp
                <div
                    style="margin-bottom: 14px; padding: 12px; background: rgba(239, 68, 68, 0.04); border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.25);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label
                            style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.84rem; font-weight: 700; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="upacara[aktif]" value="1"
                                {{ !empty($upacaraConfig['aktif']) ? 'checked' : '' }}>
                            <span><i class="fas fa-flag text-danger me-1"></i> Sisipkan Otomatis Upacara Bendera</span>
                        </label>
                        <span class="badge"
                            style="background: rgba(239,68,68,0.12); color: #ef4444; font-size: 0.68rem; font-weight: 700;">Rutin
                            Sekolah</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-top: 8px;">
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Hari
                                Upacara:</span>
                            <select name="upacara[hari]" class="form-control"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color);">
                                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                                    <option value="{{ $dh }}"
                                        {{ ($upacaraConfig['hari'] ?? 'Senin') === $dh ? 'selected' : '' }}>
                                        {{ $dh }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                Jam Ke-:</span>
                            <input type="number" name="upacara[jam_ke]" value="{{ $upacaraConfig['jam_ke'] ?? 1 }}"
                                min="1" max="10"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;">
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                (Mnt):</span>
                            <input type="number" name="upacara[durasi_menit]"
                                value="{{ $upacaraConfig['durasi_menit'] ?? 45 }}" min="15" max="90"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;">
                        </div>
                    </div>
                </div>

                <!-- Pembiasaan Rutin (Jumat / Hari Tertentu) -->
                @php
                    $pembiasaanConfig = $pengaturan->pembiasaan ?? [
                        'aktif' => true,
                        'hari' => 'Jumat',
                        'jam_ke' => 1,
                        'durasi_menit' => 40,
                        'nama' => 'Pembiasaan',
                    ];
                @endphp
                <div
                    style="margin-bottom: 16px; padding: 12px; background: rgba(16, 185, 129, 0.04); border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.25);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                        <label
                            style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.84rem; font-weight: 700; color: var(--text-color); cursor: pointer; margin: 0;">
                            <input type="checkbox" name="pembiasaan[aktif]" value="1"
                                {{ !empty($pembiasaanConfig['aktif']) ? 'checked' : '' }}>
                            <span><i class="fas fa-hands-praying text-success me-1"></i> Sisipkan Kegiatan Pembiasaan
                                Rutin</span>
                        </label>
                        <span class="badge"
                            style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.68rem; font-weight: 700;">Rutin
                            Sekolah</span>
                    </div>
                    <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap: 8px; margin-top: 8px;">
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Nama
                                Kegiatan:</span>
                            <input type="text" name="pembiasaan[nama]"
                                value="{{ $pembiasaanConfig['nama'] ?? 'Pembiasaan' }}" placeholder="Pembiasaan"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color);">
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Hari
                                Rutin:</span>
                            <select name="pembiasaan[hari]" class="form-control"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color);">
                                @foreach (['Jumat', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Sabtu'] as $dh)
                                    <option value="{{ $dh }}"
                                        {{ ($pembiasaanConfig['hari'] ?? 'Jumat') === $dh ? 'selected' : '' }}>
                                        {{ $dh }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Di
                                Jam Ke-:</span>
                            <input type="number" name="pembiasaan[jam_ke]"
                                value="{{ $pembiasaanConfig['jam_ke'] ?? 1 }}" min="1" max="10"
                                style="width: 100%; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;">
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 2px;">Durasi
                                (Mnt):</span>
                            <input type="number" name="pembiasaan[durasi_menit]"
                                value="{{ $pembiasaanConfig['durasi_menit'] ?? 40 }}" min="15" max="90"
                                style="width: 50px; padding: 5px 8px; font-size: 0.78rem; border: 1px solid var(--border-color); border-radius: 4px; background: var(--bg-card); color: var(--text-color); text-align: center;">
                        </div>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalPengaturan" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanPengaturan" class="btn btn-primary"
                        style="padding: 9px 20px; font-size: 0.85rem;">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tombol Sakti: Auto-Generate Jadwal KBM -->
    <div id="modalAutoGenerate" class="sae-modal"
        data-route-auto-generate="{{ route('dashboard.jadwal-kbm.auto-generate') }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3
                    style="margin: 0; font-size: 1.2rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <span
                        style="background: linear-gradient(135deg, #6366f1, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-wand-magic-sparkles"></i> Tombol Sakti Auto-Generate
                    </span>
                </h3>
                <button type="button" id="btnTutupModalAuto"
                    style="background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div
                style="padding: 12px 14px; background: rgba(99,102,241,0.08); border-left: 4px solid var(--primary); border-radius: 6px; margin-bottom: 14px; font-size: 0.82rem; color: var(--text-color); line-height: 1.4;">
                <strong>AI Constraint Solver:</strong> Sistem akan memetakan seluruh mata pelajaran dan guru dari data
                <strong>Pembelajaran Dapodik</strong> ke hari dan jam pelajaran secara otomatis dengan <strong>zero-conflict
                    (tanpa bentrok guru atau kelas)</strong>.
            </div>

            <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                <span class="badge" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; padding: 4px 8px; border-radius: 4px;">
                    <i class="fas fa-check-circle me-1"></i> Target X: 50 JP
                </span>
                <span class="badge" style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; padding: 4px 8px; border-radius: 4px;">
                    <i class="fas fa-check-circle me-1"></i> Target XI: 48 JP
                </span>
                <span class="badge" style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.72rem; padding: 4px 8px; border-radius: 4px;">
                    <i class="fas fa-check-circle me-1"></i> Target XII: 46 JP (Inc. PKL)
                </span>
            </div>

            <form id="formAutoGenerate">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Mode Pembuatan Jadwal <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label
                            style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.82rem; cursor: pointer; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-hover);">
                            <input type="radio" name="clear_existing" value="1" checked style="margin-top: 2px;">
                            <div>
                                <strong style="color: var(--text-color); display: block;">Bersihkan &amp; Buat Ulang Semua
                                    (Fresh Start - Direkomendasikan)</strong>
                                <span style="color: var(--text-muted); font-size: 0.74rem;">Menghapus jadwal sebelumnya
                                    pada rombel yang dipilih dan menyusun ulang seluruh jadwal dari nol secara
                                    optimal.</span>
                            </div>
                        </label>
                        <label
                            style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.82rem; cursor: pointer; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-hover);">
                            <input type="radio" name="clear_existing" value="0" style="margin-top: 2px;">
                            <div>
                                <strong style="color: var(--text-color); display: block;">Hanya Isi Jadwal Kosong (Fill
                                    Gaps)</strong>
                                <span style="color: var(--text-muted); font-size: 0.74rem;">Mempertahankan jadwal yang
                                    sudah Anda buat manual, dan hanya mengisi jam pelajaran yang masih kosong.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label for="autoTingkat"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Cakupan Tingkat Kelas
                        </label>
                        <select id="autoTingkat" name="tingkat" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);">
                            <option value="">Semua Tingkat (Seluruh Rombel)</option>
                            <option value="10">Hanya Kelas X</option>
                            <option value="11">Hanya Kelas XI</option>
                            <option value="12">Hanya Kelas XII</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="autoMaxJp"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Maks. JP per Pertemuan
                        </label>
                        <select id="autoMaxJp" name="max_jp_per_sesi" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);">
                            <option value="2">Maks 2 JP (Mapel teori dipecah)</option>
                            <option value="3" selected>Maks 3 JP (Standar seimbang)</option>
                            <option value="4">Maks 4 JP (Sesi blok 4 JP)</option>
                            <option value="5">Maks 5 JP (Sesi blok 5 JP)</option>
                            <option value="6">Maks 6 JP (Sesi blok produktif 6 JP)</option>
                            <option value="8">Maks 8 JP (Sesi blok penuh 8 JP)</option>
                            <option value="9">Maks 9 JP (Full Praktik Sehari - Maks 9 JP)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Pilihan Hari Belajar
                    </label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                            <label
                                style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; cursor: pointer; background: var(--bg-hover); padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border-color);">
                                <input type="checkbox" name="hari_aktif[]" value="{{ $dh }}"
                                    {{ $dh !== 'Sabtu' ? 'checked' : '' }}>
                                {{ $dh }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalAuto" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" id="btnEksekusiAuto" class="btn"
                        style="padding: 9px 22px; font-size: 0.85rem; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); color: #fff; font-weight: 700; border: none; box-shadow: 0 4px 12px rgba(99,102,241,0.35);">
                        <i class="fas fa-wand-magic-sparkles me-1"></i> Mulai Generate Otomatis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preferensi & Ketersediaan Guru (Off-Days) -->
    <div id="modalPreferensiGuru" class="sae-modal"
        data-route-get="{{ route('dashboard.jadwal-kbm.guru-preferensi') }}"
        data-route-save="{{ route('dashboard.jadwal-kbm.simpan-guru-preferensi') }}"
        style="display: none; position: fixed; inset: 0; z-index: 99999 !important; background: rgba(0,0,0,0.55); align-items: center; justify-content: center; backdrop-filter: blur(3px);">
        <div class="card"
            style="width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; margin: 16px; padding: 24px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-user-clock text-primary me-2"></i> Preferensi Ketersediaan Guru
                </h3>
                <button type="button" id="btnTutupModalPref"
                    style="background: transparent; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPreferensiGuru">
                @csrf
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="prefPtkId"
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Pilih Guru (PTK) <span style="color: #ef4444;">*</span>
                    </label>
                    <select id="prefPtkId" name="ptk_id" class="form-control"
                        style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"
                        required>
                        <option value="">-- Pilih Guru --</option>
                        @foreach ($guruList as $g)
                            <option value="{{ $g->ptk_id }}">{{ $g->nama }} ({{ $g->nip ?: 'Non-NIP' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="prefLoadingIndicator"
                    style="display: none; padding: 12px; text-align: center; color: var(--primary); font-size: 0.85rem;">
                    <i class="fas fa-spinner fa-spin me-1"></i> Memuat preferensi guru...
                </div>

                <div id="prefFormBody">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Hari Libur / Tidak Bersedia Mengajar (Off-Days)
                        </label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $dh)
                                <label
                                    style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; cursor: pointer; background: var(--bg-hover); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-color);">
                                    <input type="checkbox" name="hari_off[]" value="{{ $dh }}"
                                        class="pref-hari-off">
                                    {{ $dh }}
                                </label>
                            @endforeach
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                            Centang hari di mana guru berhalangan mengajar (misal mengajar di sekolah/kampus lain).
                        </small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="prefMaxJp"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Batas Maksimal JP per Hari (Opsional)
                        </label>
                        <input type="number" id="prefMaxJp" name="max_jp_per_hari" min="1" max="16"
                            placeholder="Kosongkan jika tidak ada batas" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);">
                        <small style="color: var(--text-muted); font-size: 0.72rem;">Membatasi jam mengajar per hari agar
                            tidak kelelahan (contoh: maks 6 JP/hari).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="prefKeterangan"
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Catatan / Alasan Khusus
                        </label>
                        <textarea id="prefKeterangan" name="keterangan" rows="2"
                            placeholder="Contoh: Mengajar di Universitas X hari Senin & Rabu" class="form-control"
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-card); color: var(--text-color);"></textarea>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 16px;">
                    <button type="button" id="btnBatalPref" class="btn btn-outline"
                        style="padding: 9px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" id="btnSimpanPref" class="btn btn-primary"
                        style="padding: 9px 20px; font-size: 0.85rem;">
                        <i class="fas fa-save me-1"></i> Simpan Preferensi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/jadwal-kbm.js') }}"></script>
@endpush
