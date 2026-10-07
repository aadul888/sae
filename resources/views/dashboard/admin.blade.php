@extends('layouts.dashboard')

@section('title', 'Admin Dashboard — Sistem Aplikasi Edukasi (SAE)')
@section('dash_title', 'Dashboard Administrator')

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
            ? $sessionUser['name'] ?? ($sessionUser['nama'] ?? 'Admin')
            : $sessionUser->name ?? ($sessionUser->nama ?? 'Admin');
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
    @endphp
    <!-- Welcome Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px; flex: 1; min-width: 0;">
            @if ($fotoUrl)
                <!-- Pasfoto Admin -->
                <div class="dash-banner-foto"
                    style="flex-shrink: 0; width: 88px; height: 118px; display: flex; align-items: center; justify-content: center; background: transparent; border: none; box-shadow: none;">
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
                    {{ $userName }}! 👋
                </h2>
                <div
                    style="display: flex; align-items: center; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    @if (!empty($sekolah->nama))
                        <span title="Satuan Pendidikan">
                            <i class="fas fa-school text-primary me-1"></i>
                            <strong style="color: var(--text-color);">{{ $sekolah->nama }}</strong>
                        </span>
                        @if (!empty($sekolah->npsn))
                            <span title="Nomor Pokok Sekolah Nasional (NPSN)">
                                <i class="fas fa-barcode text-muted me-1"></i>
                                <strong>{{ $sekolah->npsn }}</strong>
                            </span>
                        @endif
                    @endif
                    <span title="Level Hak Akses">
                        <i class="fas fa-shield-halved text-danger me-1"></i>
                        <span class="badge badge-danger" style="font-size: 0.72rem; padding: 2px 7px;">Administrator</span>
                    </span>
                </div>
                <div
                    style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                    <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                </div>
            </div>
        </div>
        <div class="dash-banner-actions">
            <a href="{{ route('dashboard.dapodik') }}" class="btn btn-outline"
                style="padding: 9px 16px; font-size: 0.85rem;">
                <i class="fas fa-cloud-arrow-down"></i> Tarik Data Dapodik
            </a>
        </div>
    </div>

    <!-- Stats Counter -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99,102,241,0.15); color: var(--primary);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_peserta_didik']) }}</div>
                <div class="dash-stat-label">Total Peserta Didik</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_guru'] }}</div>
                <div class="dash-stat-label">Guru &amp; Pendidik ({{ $stats['total_tendik'] }} Tendik)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.15); color: var(--accent);">
                <i class="fas fa-school"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['total_kelas'] }}</div>
                <div class="dash-stat-label">Rombongan Belajar</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                <i class="fas fa-book-bookmark"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pembelajaran']) }}</div>
                <div class="dash-stat-label">Total Pembelajaran</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;">
                <i class="fas fa-users-gear"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_pengguna']) }}</div>
                <div class="dash-stat-label">Akun Pengguna</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                <i class="fas fa-id-card-clip"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $stats['presensi_today'] }}%</div>
                <div class="dash-stat-label">Presensi Masuk Hari Ini</div>
            </div>
        </div>
    </div>

    <!-- Interactive Mixed Analytics Charts Section -->
    <div style="margin-top: 24px; margin-bottom: 24px;">
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
            <div>
                <h3
                    style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-line text-primary"></i> Analitik &amp; Visualisasi Data Terkelola
                </h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Ringkasan grafis kehadiran, sebaran jurusan, profil GTK, dan proporsi tingkat kelas secara interaktif.
                </p>
            </div>
            <div
                style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: var(--primary);">
                <i class="fas fa-circle-dot" style="font-size: 0.6rem; color: #10b981;"></i> Data Real-Time Sekolah
            </div>
        </div>

        <!-- Charts Row 1: Line Chart & Doughnut Chart -->
        <div class="dash-grid-2" style="margin-bottom: 20px;">
            <!-- Chart 1: Tren Presensi 7 Hari -->
            <div class="card" style="margin-bottom: 0;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-area text-primary"></i> Tren Kehadiran Presensi Harian
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Tingkat kehadiran Peserta Didik vs
                            Guru</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <div class="trend-filter-group"
                            style="display: inline-flex; background: rgba(255,255,255,0.05); border: 1px solid var(--border-color); border-radius: 8px; padding: 2px; gap: 2px;">
                            <button type="button" class="btn-trend-filter active" data-filter="all"
                                style="padding: 3px 8px; font-size: 0.72rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s ease;">Semua</button>
                            <button type="button" class="btn-trend-filter" data-filter="siswa"
                                style="padding: 3px 8px; font-size: 0.72rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">Siswa</button>
                            <button type="button" class="btn-trend-filter" data-filter="guru"
                                style="padding: 3px 8px; font-size: 0.72rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">Guru</button>
                        </div>
                        <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                            <i class="fas fa-arrow-trend-up me-1"></i> Rata-rata {{ $chartTrend['average'] ?? '0' }}%
                        </span>
                    </div>
                </div>
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="chartPresensiTrend"></canvas>
                </div>
            </div>

            <!-- Chart 2: Komposisi GTK -->
            <div class="card" style="margin-bottom: 0;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-pie text-accent"></i> Komposisi GTK: Pendidik vs Tendik
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Total
                            {{ $stats['total_guru'] + $stats['total_tendik'] }} Tenaga Pendidik &amp; Kependidikan</span>
                    </div>
                    <span class="badge"
                        style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; padding: 3px 8px;">
                        {{ $stats['total_guru'] }} Guru : {{ $stats['total_tendik'] }} Tendik
                    </span>
                </div>
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="chartGtkComposition"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Bar Chart & Polar Area Chart -->
        <div class="dash-grid-2">
            <!-- Chart 3: Sebaran Jurusan -->
            <div class="card" style="margin-bottom: 0;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-chart-column text-warning"></i> Sebaran Siswa per Konsentrasi Keahlian
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">6 Program Keahlian / Jurusan
                            Terbesar</span>
                    </div>
                    <span class="badge"
                        style="background: rgba(245,158,11,0.12); color: #f59e0b; font-size: 0.72rem; padding: 3px 8px;">
                        {{ $stats['total_kelas'] }} Rombel Aktif
                    </span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="chartJurusan"></canvas>
                </div>
            </div>

            <!-- Chart 4: Tingkat Kelas -->
            <div class="card" style="margin-bottom: 0;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <h4
                            style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-compass text-primary"></i> Proporsi Siswa per Tingkat Kelas
                        </h4>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">Distribusi Kelas X, Kelas XI, dan Kelas
                            XII</span>
                    </div>
                    <span class="badge"
                        style="background: rgba(16,185,129,0.12); color: #10b981; font-size: 0.72rem; padding: 3px 8px;">
                        Total {{ number_format($stats['total_peserta_didik']) }} Siswa
                    </span>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="chartTingkat"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart Full Width: Tren Jumlah Peserta Didik per Bulan dalam 1 Tahun Pelajaran -->
        <div class="card" style="margin-top: 20px; margin-bottom: 0; width: 100%;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h4
                        style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-line text-primary"></i> Tren Jumlah Peserta Didik per Bulan
                    </h4>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        Distribusi populasi peserta didik aktif per bulan dalam 1 Tahun Pelajaran
                        ({{ $chartSiswaBulanan['tahun_ajaran'] ?? '' }})
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="badge"
                        style="background: rgba(99,102,241,0.12); color: var(--primary); font-size: 0.72rem; padding: 3px 8px;">
                        <i class="fas fa-calendar-days me-1"></i> Tahun Pelajaran
                        {{ $chartSiswaBulanan['tahun_ajaran'] ?? '' }}
                    </span>
                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                        <i class="fas fa-user-graduate me-1"></i> {{ number_format($stats['total_peserta_didik']) }} Siswa
                        Aktif
                    </span>
                </div>
            </div>
            <div style="position: relative; height: 230px; width: 100%;">
                <canvas id="chartSiswaBulanan"></canvas>
            </div>
        </div>
    </div>

    <!-- Main Section: Grid 2 Columns (Datatable Aktivitas & Status Sistem) -->
    <div class="dash-admin-main-grid">
        <!-- Left: Datatable Log Aktivitas Administrator -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 0;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-shield text-primary"></i> Log Aktivitas Administrator
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0 0;">
                        Seluruh aktivitas transaksi &amp; manajemen sistem yang dilakukan administrator.
                    </p>
                </div>
                <span class="badge badge-outline badge-total" id="totalRiwayatBadge" style="font-size: 0.72rem;">
                    {{ $aktivitasLogs->total() }} Riwayat Tercatat
                </span>
            </div>

            <!-- Toolbar Datatable: Entries, Filter Modul, Live Search -->
            <div
                style="padding: 12px 20px; background: var(--bg-hover); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div class="toolbar-entries"
                        style="display: flex; align-items: center; gap: 6px; font-size: 0.8rem; color: var(--text-muted);">
                        <label for="perPageSelectAdmin" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelectAdmin" class="per-page-select"
                            style="padding: 3px 8px; border-radius: 6px; font-size: 0.8rem;">
                            @foreach ([5, 10, 25, 50] as $n)
                                <option value="{{ $n }}" {{ ($perPage ?? 5) == $n ? 'selected' : '' }}>
                                    {{ $n }}</option>
                            @endforeach
                        </select>
                    </div>

                    <select id="filterModulAdmin" class="form-select"
                        style="height: 32px; padding: 2px 10px; border-radius: 6px; font-size: 0.8rem; min-width: 130px;">
                        <option value="">Semua Modul</option>
                        @foreach ($availableModules as $mod)
                            <option value="{{ $mod }}" {{ request('modul') === $mod ? 'selected' : '' }}>
                                {{ $mod }}</option>
                        @endforeach
                    </select>

                    @if (request('q') || request('modul'))
                        <a href="{{ route('dashboard.admin') }}" class="btn btn-outline btn-reset-filter"
                            style="height: 32px; padding: 0 10px; font-size: 0.78rem; display: inline-flex; align-items: center; gap: 4px;"
                            title="Reset Filter" data-live-reset="true">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap" style="min-width: 200px;">
                    <i class="fas fa-search search-icon" style="font-size: 0.8rem;"></i>
                    <input type="text" id="liveSearchAdmin" placeholder="Cari aktivitas..."
                        value="{{ request('q') }}" autocomplete="off" style="font-size: 0.82rem; height: 32px;">
                    <button type="button" id="clearSearchAdmin"
                        class="clear-search {{ request('q') ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times" style="font-size: 0.75rem;"></i>
                    </button>
                </div>
            </div>

            <!-- Tabel Log Aktivitas -->
            <div style="overflow-x: auto;">
                <table class="table table-pd mb-0" style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                    <thead>
                        <tr style="background: var(--bg-hover); border-bottom: 1px solid var(--border-color);">
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">
                                No</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 170px; white-space: nowrap;">
                                Waktu</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 110px;">
                                Modul</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Aktivitas Administrator</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px;">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aktivitasLogs as $idx => $act)
                            <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                                <td style="padding: 12px 14px; text-align: center; color: var(--text-muted);"
                                    data-label="No">
                                    {{ $aktivitasLogs->firstItem() + $idx }}
                                </td>
                                <td style="padding: 12px 14px; white-space: nowrap;" data-label="Waktu">
                                    <span
                                        style="font-weight: 600; font-size: 0.82rem; color: var(--text-color); white-space: nowrap; display: inline-block;">
                                        {{ $act->created_at ? $act->created_at->format('d/m/Y H:i') . ' WIB' : '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Modul">
                                    @php
                                        $modColors = [
                                            'Dapodik' => ['bg' => 'rgba(2, 132, 199, 0.12)', 'color' => '#0284c7'],
                                            'Formulir' => ['bg' => 'rgba(147, 51, 234, 0.12)', 'color' => '#9333ea'],
                                            'Sistem' => ['bg' => 'rgba(16, 185, 129, 0.12)', 'color' => '#10b981'],
                                            'Backup' => ['bg' => 'rgba(245, 158, 11, 0.12)', 'color' => '#f59e0b'],
                                            'Pengumuman' => [
                                                'bg' => 'rgba(99, 102, 241, 0.12)',
                                                'color' => 'var(--primary)',
                                            ],
                                            'Hak Akses' => ['bg' => 'rgba(239, 68, 68, 0.12)', 'color' => '#ef4444'],
                                            'Presensi' => ['bg' => 'rgba(6, 182, 212, 0.12)', 'color' => '#06b6d4'],
                                        ];
                                        $c = $modColors[$act->modul] ?? [
                                            'bg' => 'rgba(148, 163, 184, 0.12)',
                                            'color' => 'var(--text-muted)',
                                        ];
                                    @endphp
                                    <span class="badge"
                                        style="background: {{ $c['bg'] }}; color: {{ $c['color'] }}; font-size: 0.72rem; padding: 2px 7px; border-radius: 4px;">
                                        {{ $act->modul }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px;" data-label="Aktivitas">
                                    <strong
                                        style="color: var(--text-color); font-size: 0.84rem; display: block; line-height: 1.35; margin-bottom: 2px;">
                                        {{ $act->aktivitas }}
                                    </strong>
                                    @if ($act->keterangan)
                                        <div class="aktivitas-outline"
                                            style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 440px;"
                                            title="{{ $act->keterangan }}">
                                            {{ \Illuminate\Support\Str::limit($act->keterangan, 65) }}
                                        </div>
                                    @endif
                                    <div
                                        style="font-size: 0.72rem; color: var(--text-muted); margin-top: 3px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                        <span><i class="fas fa-user-circle me-1"></i>{{ $act->admin_name }}</span>
                                        @if ($act->ip_address)
                                            <span style="opacity: 0.65;">• IP: {{ $act->ip_address }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;" data-label="Status">
                                    @if ($act->tipe === 'success')
                                        <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">
                                            <i class="fas fa-check me-1"></i>Sukses
                                        </span>
                                    @elseif ($act->tipe === 'info')
                                        <span class="badge"
                                            style="background: rgba(2,132,199,0.12); color: #0284c7; font-size: 0.68rem; padding: 2px 6px;">
                                            <i class="fas fa-info-circle me-1"></i>Info
                                        </span>
                                    @elseif ($act->tipe === 'warning')
                                        <span class="badge badge-warning" style="font-size: 0.68rem; padding: 2px 6px;">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Perhatian
                                        </span>
                                    @else
                                        <span class="badge badge-danger" style="font-size: 0.68rem; padding: 2px 6px;">
                                            <i class="fas fa-times me-1"></i>Gagal
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    style="padding: 30px 14px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-clipboard-list mb-2"
                                        style="font-size: 1.8rem; opacity: 0.4; display: block;"></i>
                                    <div>Belum ada data aktivitas administrator yang cocok.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination Footer -->
            @if ($aktivitasLogs->hasPages())
                <div
                    style="padding: 12px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                        Menampilkan
                        <strong>{{ $aktivitasLogs->firstItem() ?? 0 }}</strong>–<strong>{{ $aktivitasLogs->lastItem() ?? 0 }}</strong>
                        dari <strong>{{ $aktivitasLogs->total() }}</strong> aktivitas
                    </div>
                    <div class="custom-pagination" style="margin: 0; padding: 0;">
                        @if ($aktivitasLogs->onFirstPage())
                            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                        @else
                            <a href="{{ $aktivitasLogs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                                    class="fas fa-chevron-left"></i></a>
                        @endif
                        @php
                            $cur = $aktivitasLogs->currentPage();
                            $last = $aktivitasLogs->lastPage();
                            $from = max(1, $cur - 1);
                            $to = min($last, $cur + 1);
                        @endphp
                        @for ($i = $from; $i <= $to; $i++)
                            <a href="{{ $aktivitasLogs->url($i) }}"
                                class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                        @endfor
                        @if ($aktivitasLogs->hasMorePages())
                            <a href="{{ $aktivitasLogs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                                    class="fas fa-chevron-right"></i></a>
                        @else
                            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Right: Status Perangkat & Sistem (Sesuai Permintaan) -->
        <div class="card" style="margin-bottom: 0;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                <div>
                    <h3
                        style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-server text-accent"></i> Status Sistem &amp; Pembaruan
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0 0;">
                        Konektivitas sinkronisasi Dapodik dan siklus pembaruan aplikasi.
                    </p>
                </div>
                @if (!empty($updateStatus['updates_available']))
                    <span class="badge badge-warning" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                        <i class="fas fa-circle-exclamation me-1"></i> Update Tersedia
                    </span>
                @else
                    <span class="badge badge-success" style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px;">
                        <i class="fas fa-circle-check me-1"></i> Normal
                    </span>
                @endif
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <!-- 1. Sinkron Dapodik Terakhir -->
                <div
                    style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 10px; flex-wrap: wrap; gap: 8px;">
                    <div style="flex: 1; min-width: 150px;">
                        <div
                            style="font-size: 0.84rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-cloud-arrow-up text-warning"></i> Sinkron Dapodik Terakhir
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Sinkronisasi lokal data pokok satuan pendidikan
                        </div>
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); white-space: nowrap;">
                        {{ $stats['sync_dapodik'] }}
                    </span>
                </div>

                <!-- 2. Versi Aplikasi -->
                <div
                    style="display: flex; justify-content: space-between; align-items: center; padding: 12px 14px; background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); border-radius: 10px; flex-wrap: wrap; gap: 8px;">
                    <div style="flex: 1; min-width: 150px;">
                        <div
                            style="font-size: 0.84rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-code-commit text-primary"></i> Versi Aplikasi SAE
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Rilis core sistem aplikasi edukasi saat ini
                        </div>
                    </div>
                    <span class="badge"
                        style="background: rgba(99, 102, 241, 0.15); color: var(--primary); font-weight: 800; font-size: 0.82rem; padding: 4px 10px; border-radius: 8px; white-space: nowrap;">
                        v{{ $stats['app_version'] }}
                    </span>
                </div>

                <!-- 3. Informasi & Tombol Update Sistem -->
                @if (!empty($updateStatus['updates_available']))
                    <div
                        style="padding: 16px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.35); margin-top: 4px;">
                        <div style="display: flex; align-items: flex-start; gap: 10px;">
                            <div
                                style="width: 36px; height: 36px; border-radius: 8px; background: rgba(245, 158, 11, 0.2); color: #f59e0b; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.9rem; font-weight: 700; color: #f59e0b;">
                                    Pembaruan Sistem Tersedia!
                                </div>
                                <div
                                    style="font-size: 0.78rem; color: var(--text-muted); margin-top: 3px; line-height: 1.4;">
                                    Tersedia <strong>{{ $updateStatus['behind_count'] }} pembaruan baru</strong> dari
                                    repositori resmi SAE untuk stabilitas dan fitur terkini.
                                </div>
                                @if (!empty($updateStatus['changes'][0]))
                                    <div
                                        style="font-size: 0.72rem; color: var(--text-muted); background: rgba(0,0,0,0.12); padding: 4px 8px; border-radius: 6px; margin-top: 6px; font-family: monospace; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $updateStatus['changes'][0] }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('dashboard.update') }}" class="btn btn-primary"
                            style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; padding: 10px 16px; font-weight: 700; font-size: 0.85rem; border-radius: 8px;">
                            <i class="fas fa-circle-arrow-up"></i> Buka Halaman Update Sistem
                        </a>
                    </div>
                @else
                    <div
                        style="padding: 16px; border-radius: 12px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); margin-top: 4px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div
                                style="width: 36px; height: 36px; border-radius: 8px; background: rgba(16, 185, 129, 0.18); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem;">
                                <i class="fas fa-circle-check"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: 0.9rem; font-weight: 700; color: #10b981;">
                                    Sistem Berjalan pada Versi Terbaru
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                    Seluruh berkas, dependensi, dan skema database dalam keadaan mutakhir.
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('dashboard.update') }}" class="btn btn-outline"
                            style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 14px; padding: 9px 16px; font-size: 0.82rem; border-radius: 8px;">
                            <i class="fas fa-rotate"></i> Cek Pembaruan Sistem
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- JSON Payload untuk Chart Interaktif -->
    <script id="adminChartPayload" type="application/json">
        {!! json_encode([
            'trend' => $chartTrend,
            'jurusan' => $jurusanStats,
            'gtk' => $gtkComposition,
            'tingkat' => $tingkatStats,
            'siswaBulanan' => $chartSiswaBulanan,
        ]) !!}
    </script>

    @push('scripts')
        <script
            src="{{ asset('js/admin-dashboard.js') }}?v={{ file_exists(public_path('js/admin-dashboard.js')) ? filemtime(public_path('js/admin-dashboard.js')) : time() }}">
        </script>
    @endpush
@endsection
