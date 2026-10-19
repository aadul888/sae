@php
    $d = $dashboardData ?? [];
    $totalUsulan = $d['total_usulan'] ?? 0;
    $paguSekolah = $d['pagu_sekolah'] ?? 0;
    $pctTerpakai = $d['pct_terpakai'] ?? 0;
    $totalSiswaAktif = $d['total_siswa_aktif'] ?? 0;
    $sudahDiajukan = $d['sudah_diajukan'] ?? 0;
    $belumDiajukan = $d['belum_diajukan'] ?? 0;
    $ditolak = $d['ditolak'] ?? 0;
    $sisaKuota = $d['sisa_kuota'] ?? 0;
    $kuotaKelas = $d['kuota_kelas'] ?? 5;
    $terpakaiKelas = $d['terpakai_kelas'] ?? 0;
    $sisaKuotaKelas = $d['sisa_kuota_kelas'] ?? 5;
    $totalRiwayat = $d['total_riwayat_aktif'] ?? 0;
    $totalNominal = $d['total_nominal_cair'] ?? 0;
    $rombelSummary = $d['rombel_summary'] ?? collect();
    $usulanTerbaru = $d['usulan_terbaru'] ?? collect();
@endphp

<!-- 1. Stats Counter Grid (6 Kartu Metrik Utama PIP SAE) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 22px;">
    <!-- Card 1: Total Usulan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                {{ number_format($totalUsulan) }}
            </div>
            <div class="dash-stat-label">Total Usulan Siswa ({{ $activeTahun }})</div>
        </div>
    </div>

    <!-- Card 2: Sudah Diusulkan ke Web PIP -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                {{ number_format($sudahDiajukan) }}
            </div>
            <div class="dash-stat-label">Diusulkan ke Web PIP</div>
        </div>
    </div>

    <!-- Card 3: Belum Diusulkan (Pending) -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                {{ number_format($belumDiajukan) }}
            </div>
            <div class="dash-stat-label">Belum Diusulkan (Draft)</div>
        </div>
    </div>

    <!-- Card 4: Kuota Sekolah / Kelas -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--accent);">
                @if ($isAdmin)
                    {{ number_format($paguSekolah) }}
                @else
                    {{ $terpakaiKelas }} / {{ $kuotaKelas }}
                @endif
            </div>
            <div class="dash-stat-label">
                @if ($isAdmin)
                    Target Pagu Kuota ({{ $pctTerpakai }}%)
                @else
                    Kuota Kelas Binaan
                @endif
            </div>
        </div>
    </div>

    <!-- Card 5: Sisa Kuota Terbuka -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: #8b5cf6;">
            <i class="fas fa-sliders"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.35rem; color: #8b5cf6;">
                @if ($isAdmin)
                    {{ number_format($sisaKuota) }}
                @else
                    {{ $sisaKuotaKelas }}
                @endif
            </div>
            <div class="dash-stat-label">Sisa Kuota Tersedia</div>
        </div>
    </div>

    <!-- Card 6: Riwayat Dana Cair -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.22rem; color: #0ea5e9; white-space: nowrap;">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Riwayat ({{ number_format($totalRiwayat) }} Siswa)</div>
        </div>
    </div>
</div>

<!-- 2. Interactive Charts Section -->
<div style="margin-bottom: 24px;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <div>
            <h3
                style="font-size: 1.12rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-line text-primary"></i> Visualisasi &amp; Analitik Usulan PIP
            </h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Grafik interaktif status verifikasi usulan, distribusi alasan prioritas, dan sebaran kuota per rombel.
            </p>
        </div>
        <div
            style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: var(--primary);">
            <i class="fas fa-circle-dot" style="font-size: 0.6rem; color: #10b981;"></i> Tahun {{ $activeTahun }}
            Real-Time
        </div>
    </div>

    <!-- Charts Row 1: Status Doughnut + Alasan Horizontal Bar -->
    <div class="dash-grid-2"
        style="margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
        <!-- Chart 1: Status Usulan & Keterisian Kuota -->
        <div class="card" style="margin-bottom: 0; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h4
                        style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-pie text-primary"></i> Komposisi Status Usulan PIP
                    </h4>
                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                        @if ($isAdmin)
                            Progres pengusulan terhadap pagu kuota {{ number_format($paguSekolah) }} siswa
                        @else
                            Progres usulan kelas {{ $activeRombel->nama ?? '-' }} (Kuota: {{ $kuotaKelas }})
                        @endif
                    </span>
                </div>
                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 3px 8px;">
                    {{ $pctTerpakai }}% Terisi
                </span>
            </div>
            <div
                style="position: relative; height: 240px; width: 100%; display: flex; align-items: center; justify-content: center;">
                <canvas id="chartPipStatus"></canvas>
            </div>
            <div
                style="display: flex; justify-content: center; gap: 14px; margin-top: 14px; flex-wrap: wrap; font-size: 0.75rem;">
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <span
                        style="width: 10px; height: 10px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    Sudah Diajukan ({{ $sudahDiajukan }})
                </span>
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <span
                        style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                    Belum Diajukan ({{ $belumDiajukan }})
                </span>
                @if ($ditolak > 0)
                    <span style="display: inline-flex; align-items: center; gap: 5px;">
                        <span
                            style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
                        Ditolak ({{ $ditolak }})
                    </span>
                @endif
                <span style="display: inline-flex; align-items: center; gap: 5px;">
                    <span
                        style="width: 10px; height: 10px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                    Sisa Kuota ({{ $isAdmin ? $sisaKuota : $sisaKuotaKelas }})
                </span>
            </div>
        </div>

        <!-- Chart 2: 8 Kriteria Alasan Usulan -->
        <div class="card" style="margin-bottom: 0; padding: 18px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h4
                        style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-list-check text-primary"></i> Sebaran Alasan Prioritas
                    </h4>
                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                        Distribusi 8 kriteria resmi penetapan penerima PIP
                    </span>
                </div>
                <span class="badge badge-outline" style="font-size: 0.72rem; padding: 3px 8px;">
                    <i class="fas fa-scale-balanced me-1"></i> Kriteria SiPintar
                </span>
            </div>
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="chartPipAlasan"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Khusus Admin (Sebaran Tingkat Kelas & Tren Riwayat per Tahun) / Khusus Wali Kelas (Status Kepesertaan) -->
    @if ($isAdmin)
        <div class="dash-grid-2"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
            <!-- Chart 3: Usulan per Tingkat Kelas (X, XI, XII) -->
            <div class="card" style="margin-bottom: 0; padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div>
                        <h4
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-school text-primary"></i> Usulan per Tingkat Kelas
                        </h4>
                        <span style="font-size: 0.74rem; color: var(--text-muted);">
                            Perbandingan siswa diusulkan vs total peserta didik per tingkat
                        </span>
                    </div>
                </div>
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="chartPipTingkat"></canvas>
                </div>
            </div>

            <!-- Chart 4: Tren Riwayat Penerimaan PIP per Tahun -->
            <div class="card" style="margin-bottom: 0; padding: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <div>
                        <h4
                            style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-clock-rotate-left text-primary"></i> Tren Riwayat Penerima PIP
                        </h4>
                        <span style="font-size: 0.74rem; color: var(--text-muted);">
                            Jumlah siswa penerima berdasarkan data arsip SiPintar per tahun
                        </span>
                    </div>
                </div>
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="chartPipRiwayatTahun"></canvas>
                </div>
            </div>
        </div>
    @else
        <!-- View Wali Kelas: Detail Status Siswa Kelas Binaan -->
        <div class="dash-grid-2"
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
            <div class="card" style="margin-bottom: 0; padding: 18px;">
                <h4
                    style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-users-viewfinder text-primary"></i> Status Kepesertaan PIP Siswa Kelas
                    {{ $activeRombel->nama ?? '' }}
                </h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div
                        style="padding: 14px; border-radius: 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                        <div style="font-size: 0.76rem; color: #10b981; font-weight: 700; text-transform: uppercase;">
                            Sudah Pernah Terima PIP</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; margin: 4px 0;">
                            {{ $d['wali_sudah_pip'] ?? 0 }}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Tercatat di riwayat SiPintar</div>
                    </div>
                    <div
                        style="padding: 14px; border-radius: 10px; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                        <div style="font-size: 0.76rem; color: #f59e0b; font-weight: 700; text-transform: uppercase;">
                            Belum Pernah Terima PIP</div>
                        <div style="font-size: 1.5rem; font-weight: 800; color: #f59e0b; margin: 4px 0;">
                            {{ $d['wali_belum_pip'] ?? 0 }}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">Peluang prioritas usulan baru</div>
                    </div>
                </div>
                <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                    <i class="fas fa-lightbulb text-warning me-1"></i> <strong>Tips Wali Kelas:</strong> Utamakan
                    mengajukan peserta didik yang berada pada desil terendah (DTSEN 1-4) atau rentan putus sekolah yang
                    belum pernah menerima PIP sebelumnya.
                </p>
            </div>

            <div class="card" style="margin-bottom: 0; padding: 18px;">
                <h4
                    style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-gauge-high text-primary"></i> Alokasi Kuota Kelas Anda
                </h4>
                <div style="margin-bottom: 14px;">
                    <div
                        style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 6px;">
                        <span>Keterisian Kuota Usulan:</span>
                        <strong>{{ $terpakaiKelas }} dari {{ $kuotaKelas }} siswa
                            ({{ $kuotaKelas > 0 ? round(($terpakaiKelas / $kuotaKelas) * 100) : 0 }}%)</strong>
                    </div>
                    <div
                        style="width: 100%; height: 12px; background: rgba(0,0,0,0.06); border-radius: 6px; overflow: hidden;">
                        <div
                            style="width: {{ $kuotaKelas > 0 ? min(100, round(($terpakaiKelas / $kuotaKelas) * 100)) : 0 }}%; height: 100%; background: linear-gradient(90deg, #6366f1, #10b981); border-radius: 6px;">
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="openTambahUsulan()"
                        style="padding: 7px 14px; font-size: 0.82rem; font-weight: 700;">
                        <i class="fas fa-plus me-1"></i> Tambah Usulan Siswa
                    </button>
                    <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'usulan', 'tahun' => $activeTahun])) }}"
                        class="btn btn-outline btn-sm"
                        style="padding: 7px 14px; font-size: 0.82rem; font-weight: 600;">
                        <i class="fas fa-list-ol me-1"></i> Kelola Usulan Kelas
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- 3. Rekapitulasi Rombongan Belajar (Admin) & Usulan Terbaru -->
@if ($isAdmin)
    <div class="card" style="padding: 0; margin-bottom: 24px; overflow: hidden;">
        <div
            style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h4
                    style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-table-list text-primary"></i> Rekap Keterisian Kuota &amp; Usulan per Kelas
                </h4>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 3px;">
                    Total {{ $rombelSummary->count() }} rombongan belajar aktif &bull;
                    <span class="text-success font-weight-bold">{{ $d['rombel_penuh_count'] ?? 0 }} Penuh</span>
                    &bull;
                    <span class="text-warning font-weight-bold">{{ $d['rombel_tersedia_count'] ?? 0 }} Ada
                        Kuota</span> &bull;
                    <span class="text-danger font-weight-bold">{{ $d['rombel_belum_usul_count'] ?? 0 }} Belum
                        Mengusulkan</span>
                </div>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <!-- Filter Sasaran Tingkat: Semua, Kelas Awal, Kelas Berjalan -->
                <div class="dash-rekap-tingkat-filters"
                    style="display: inline-flex; gap: 3px; background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 2px;">
                    <button type="button" class="btn-rekap-tingkat-filter active" data-tingkat="all"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s ease;">
                        Semua Tingkat ({{ $rombelSummary->count() }})
                    </button>
                    <button type="button" class="btn-rekap-tingkat-filter" data-tingkat="kelas_awal"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                        Kelas Awal ({{ $d['rombel_awal_count'] ?? 0 }})
                    </button>
                    <button type="button" class="btn-rekap-tingkat-filter" data-tingkat="kelas_berjalan"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                        Kelas Berjalan ({{ $d['rombel_berjalan_count'] ?? 0 }})
                    </button>
                </div>

                <!-- Filter status keterisian kuota -->
                <div class="dash-rekap-filters"
                    style="display: inline-flex; gap: 3px; background: rgba(0,0,0,0.03); border: 1px solid var(--border-color); border-radius: 8px; padding: 2px;">
                    <button type="button" class="btn-rekap-filter active" data-status="all"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s ease;">
                        Semua Status
                    </button>
                    <button type="button" class="btn-rekap-filter" data-status="tersedia"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                        Ada Sisa ({{ $d['rombel_tersedia_count'] ?? 0 }})
                    </button>
                    <button type="button" class="btn-rekap-filter" data-status="penuh"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                        Penuh ({{ $d['rombel_penuh_count'] ?? 0 }})
                    </button>
                    <button type="button" class="btn-rekap-filter" data-status="kosong"
                        style="padding: 4px 9px; font-size: 0.74rem; font-weight: 600; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                        Belum Usul ({{ $d['rombel_belum_usul_count'] ?? 0 }})
                    </button>
                </div>

                <div class="live-search-wrap" style="max-width: 180px;">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="filterRekapRombel" placeholder="Cari rombel..." autocomplete="off"
                        style="padding: 6px 12px 6px 32px; font-size: 0.78rem; height: 32px; border-radius: 8px;">
                </div>

                <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'kuota', 'tahun' => $activeTahun])) }}"
                    class="btn btn-outline btn-sm" style="padding: 6px 12px; font-size: 0.76rem; font-weight: 600;">
                    <i class="fas fa-sliders me-1"></i> Atur Kuota
                </a>
            </div>
        </div>

        <div class="table-responsive-stack" style="max-height: 440px; overflow-y: auto;">
            <table class="table table-pd" id="tableRekapRombel"
                style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead
                    style="position: sticky; top: 0; z-index: 2; background: var(--card-bg); box-shadow: 0 1px 0 var(--border-color);">
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); width: 45px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted);">
                            Rombongan Belajar</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted);">
                            Tingkat &amp; Jurusan</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                            Siswa Aktif</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                            Kuota</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); min-width: 150px;">
                            Progres Usulan</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                            Sisa</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                            Status</th>
                        <th
                            style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 90px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rombelSummary as $r)
                        @php
                            $isPenuh = $r->sisa <= 0 && $r->terpakai > 0;
                            $isKosong = $r->terpakai == 0;
                            $statusType = $isPenuh ? 'penuh' : ($isKosong ? 'kosong' : 'tersedia');
                            $kategoriTingkat = $r->kategori_tingkat ?? 'kelas_berjalan';
                        @endphp
                        <tr class="rekap-rombel-row" data-status="{{ $statusType }}"
                            data-tingkat="{{ $kategoriTingkat }}" data-name="{{ strtolower($r->nama) }}"
                            style="border-bottom: 1px solid var(--border-color);">
                            <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;"
                                data-label="No"><span class="rekap-row-no">{{ $loop->iteration }}</span></td>
                            <td style="padding: 10px 14px;" data-label="Rombongan Belajar">
                                <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">
                                    {{ $r->nama }}</div>
                                @if (!empty($r->ptk_id_str))
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <i class="fas fa-chalkboard-user me-1"></i>{{ $r->ptk_id_str }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted);"
                                data-label="Tingkat &amp; Jurusan">
                                <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                    <span class="badge badge-outline"
                                        style="font-size: 0.7rem; padding: 2px 6px;">{{ $r->tingkat_pendidikan_id_str ?: '-' }}</span>
                                    <span
                                        class="badge {{ $kategoriTingkat === 'kelas_awal' ? 'badge-primary' : 'badge-outline' }}"
                                        style="font-size: 0.66rem; padding: 1px 5px; opacity: 0.9;">
                                        {{ $kategoriTingkat === 'kelas_awal' ? 'Kelas Awal' : 'Kelas Berjalan' }}
                                    </span>
                                </div>
                                <div style="font-size: 0.72rem; margin-top: 3px;">{{ $r->jurusan_id_str ?: '-' }}
                                </div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-size: 0.84rem; font-weight: 600;"
                                data-label="Siswa Aktif">
                                {{ number_format($r->jumlah_siswa) }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-size: 0.86rem; font-weight: 700; color: var(--primary);"
                                data-label="Kuota">
                                {{ $r->kuota }}
                            </td>
                            <td style="padding: 10px 14px;" data-label="Progres Usulan">
                                <div
                                    style="display: flex; justify-content: space-between; font-size: 0.74rem; font-weight: 700; margin-bottom: 4px;">
                                    <span>{{ $r->terpakai }} usulan</span>
                                    <span>{{ $r->pct }}%</span>
                                </div>
                                <div
                                    style="width: 100%; height: 7px; background: rgba(0,0,0,0.06); border-radius: 4px; overflow: hidden;">
                                    <div
                                        style="width: {{ $r->pct }}%; height: 100%; background: {{ $r->pct >= 100 ? '#10b981' : ($r->pct > 0 ? '#6366f1' : '#cbd5e1') }}; border-radius: 4px;">
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; font-size: 0.84rem; font-weight: 700; color: {{ $r->sisa > 0 ? '#10b981' : '#94a3b8' }};"
                                data-label="Sisa">
                                {{ $r->sisa }}
                            </td>
                            <td style="padding: 10px 14px; text-align: center;" data-label="Status">
                                @if ($isPenuh)
                                    <span class="badge badge-success"
                                        style="font-size: 0.7rem; padding: 2px 8px;">Penuh</span>
                                @elseif ($isKosong)
                                    <span class="badge badge-danger"
                                        style="font-size: 0.7rem; padding: 2px 8px;">Belum Usul</span>
                                @else
                                    <span class="badge badge-warning" style="font-size: 0.7rem; padding: 2px 8px;">Ada
                                        Kuota</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;" data-label="Aksi">
                                <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'usulan', 'tahun' => $activeTahun, 'rombel_id' => $r->rombongan_belajar_id])) }}"
                                    class="btn btn-outline btn-sm" style="padding: 4px 8px; font-size: 0.74rem;"
                                    title="Lihat Usulan Kelas Ini">
                                    <i class="fas fa-eye me-1"></i> Buka
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                Belum ada data rombongan belajar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- 4. Daftar Usulan Terbaru (Real-Time Activity Feed) -->
<div class="card" style="padding: 0; margin-bottom: 24px; overflow: hidden;">
    <div
        style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h4
                style="font-size: 1rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Usulan PIP Terbaru
            </h4>
            <span style="font-size: 0.75rem; color: var(--text-muted);">
                Aktivitas usulan yang baru saja didaftarkan oleh Wali Kelas / Administrator
            </span>
        </div>
        <a href="{{ route('dashboard.pip.index', array_filter(['tab' => 'usulan', 'tahun' => $activeTahun])) }}"
            class="btn btn-outline btn-sm" style="font-size: 0.78rem; font-weight: 600;">
            Lihat Semua Usulan <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive-stack">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); width: 45px; text-align: center;">
                        No</th>
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted);">
                        Peserta Didik</th>
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted);">
                        Kelas</th>
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                        Prioritas</th>
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted);">
                        Alasan Usulan</th>
                    <th
                        style="padding: 10px 14px; font-size: 0.74rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">
                        Status Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usulanTerbaru as $u)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="text-align: center; color: var(--text-muted); font-size: 0.8rem;" data-label="No">
                            {{ $loop->iteration }}</td>
                        <td style="padding: 10px 14px;" data-label="Peserta Didik">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="{{ $u->foto_url ?: asset('img/logo-dark.png') }}"
                                    alt="{{ $u->nama_pd }}"
                                    style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                    onerror="this.src='/img/logo-dark.png'">
                                <div>
                                    <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-color);">
                                        {{ $u->nama_pd }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                        NISN: {{ $u->nisn ?: '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 10px 14px;" data-label="Kelas">
                            <span class="badge badge-outline"
                                style="font-size: 0.72rem; padding: 2px 7px;">{{ $u->nama_rombel ?: '-' }}</span>
                        </td>
                        <td style="padding: 10px 14px; text-align: center;" data-label="Prioritas">
                            <span class="badge badge-primary"
                                style="font-size: 0.74rem; padding: 3px 8px; font-weight: 700;">
                                #{{ $u->prioritas }}
                            </span>
                        </td>
                        <td style="padding: 10px 14px;" data-label="Alasan Usulan">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @forelse ($u->alasan_list as $al)
                                    <span class="badge badge-outline" style="font-size: 0.68rem; padding: 1px 6px;">
                                        {{ $al }}
                                    </span>
                                @empty
                                    <span style="font-size: 0.72rem; color: var(--text-muted);">-</span>
                                @endforelse
                            </div>
                        </td>
                        <td style="padding: 10px 14px; text-align: center;" data-label="Status">
                            @if ($u->status_verifikasi === 'sudah_diajukan')
                                <span class="badge badge-success" style="font-size: 0.72rem; padding: 2px 8px;">
                                    <i class="fas fa-check-circle me-1"></i> Sudah Diusulkan
                                </span>
                            @elseif ($u->status_verifikasi === 'ditolak')
                                <span class="badge badge-danger" style="font-size: 0.72rem; padding: 2px 8px;">
                                    <i class="fas fa-times-circle me-1"></i> Ditolak
                                </span>
                            @else
                                <span class="badge badge-warning" style="font-size: 0.72rem; padding: 2px 8px;">
                                    <i class="fas fa-clock me-1"></i> Belum Diusulkan
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: var(--text-muted);">
                            Belum ada usulan PIP pada tahun {{ $activeTahun }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 5. Chart Payload Data (JSON) -->
@php
    $alasanKeys = array_keys($d['alasan_distribusi'] ?? []);
    $alasanVals = array_values($d['alasan_distribusi'] ?? []);

    $tingkatKeys = array_keys($d['siswa_per_tingkat'] ?? []);
    $tingkatSiswa = array_values($d['siswa_per_tingkat'] ?? []);
    $tingkatUsulan = [];
    foreach ($tingkatKeys as $tk) {
        $tingkatUsulan[] = (int) ($d['usulan_per_tingkat'][$tk] ?? 0);
    }
    $tingkatLabels = array_map(function ($k) {
        return 'Kelas ' . $k;
    }, $tingkatKeys);

    $riwayatTahunList = $d['riwayat_per_tahun'] ?? [];
    $riwayatTahunLabels = [];
    $riwayatTahunCounts = [];
    $riwayatTahunNominals = [];
    foreach ($riwayatTahunList as $item) {
        $riwayatTahunLabels[] = 'Th ' . $item->tahun;
        $riwayatTahunCounts[] = (int) $item->n;
        $riwayatTahunNominals[] = (float) $item->total_nominal;
    }

    $chartPayload = [
        'status' => [
            'labels' => ['Sudah Diusulkan', 'Belum Diusulkan', 'Ditolak', 'Sisa Kuota'],
            'data' => [
                (int) $sudahDiajukan,
                (int) $belumDiajukan,
                (int) $ditolak,
                (int) ($isAdmin ? $sisaKuota : $sisaKuotaKelas),
            ],
            'colors' => ['#10b981', '#f59e0b', '#ef4444', '#94a3b8'],
            'total_target' => (int) ($isAdmin ? $paguSekolah : $kuotaKelas),
            'total_usulan' => (int) $totalUsulan,
            'is_admin' => (bool) $isAdmin,
        ],
        'alasan' => [
            'labels' => array_slice($alasanKeys, 0, 8),
            'data' => array_slice($alasanVals, 0, 8),
        ],
        'tingkat' => [
            'labels' => $tingkatLabels,
            'siswa' => $tingkatSiswa,
            'usulan' => $tingkatUsulan,
        ],
        'riwayat_tahun' => [
            'labels' => $riwayatTahunLabels,
            'counts' => $riwayatTahunCounts,
            'nominals' => $riwayatTahunNominals,
        ],
    ];
@endphp

<script id="pipChartPayload" type="application/json">
    {!! json_encode($chartPayload) !!}
</script>
