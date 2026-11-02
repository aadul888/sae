{{-- Section Dashboard: Staf Administrasi Kesiswaan --}}


<!-- 2. Quick Stats Grid Kesiswaan -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_siswa'] ?? 0, 0, ',', '.') }}</div>
            <div class="dash-stat-label">Siswa Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['siswa_berfoto'] ?? 0 }}</div>
            <div class="dash-stat-label">Foto Kartu Pelajar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-door-open"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_rombel'] ?? 0 }}</div>
            <div class="dash-stat-label">Rombel / Kelas</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-venus-mars"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-lg, 1.125rem);">{{ $stats['siswa_laki'] ?? '-' }} L / {{ $stats['siswa_perempuan'] ?? '-' }} P</div>
            <div class="dash-stat-label">Gender Siswa</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')

<!-- 1. Akses Cepat Menu Kesiswaan (selaras submenu sidebar) -->
@php
    $can = $can ?? fn(string $key) => \App\Models\RolePermission::canAccess(session('user') ?: 'tendik', $key, 'read');
    $quickItems = [
        [
            'label' => 'Peserta Didik',
            'title' => 'Data Induk & Profil Peserta Didik',
            'route' => route('dashboard.kesiswaan.peserta-didik.index'),
            'icon' => 'fa-users',
            'color' => 'var(--primary, #3b82f6)',
            'bg' => 'rgba(59, 130, 246, 0.15)',
            'can' => ['menu_kesiswaan_peserta_didik', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Buku Induk',
            'title' => 'Pengelolaan Buku Induk Siswa',
            'route' => route('dashboard.kesiswaan.buku-induk.index'),
            'icon' => 'fa-address-book',
            'color' => 'var(--accent, #06b6d4)',
            'bg' => 'rgba(6, 182, 212, 0.15)',
            'can' => ['menu_buku_induk', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Administrasi',
            'title' => 'Buku Klaper, Mutasi & Kelulusan',
            'route' => route('dashboard.kesiswaan.administrasi.index'),
            'icon' => 'fa-book-bookmark',
            'color' => 'var(--warning, #f59e0b)',
            'bg' => 'rgba(245, 158, 11, 0.15)',
            'can' => ['menu_kesiswaan_administrasi', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Kedisiplinan',
            'title' => 'Tata Tertib, Poin & Pembinaan Siswa',
            'route' => route('dashboard.kesiswaan.kedisiplinan.index'),
            'icon' => 'fa-shield-halved',
            'color' => 'var(--danger, #ef4444)',
            'bg' => 'rgba(239, 68, 68, 0.15)',
            'can' => ['menu_kesiswaan_kedisiplinan', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Kegiatan Siswa',
            'title' => 'Organisasi, Ekstrakurikuler & Agenda',
            'route' => route('dashboard.kesiswaan.kegiatan.index'),
            'icon' => 'fa-people-group',
            'color' => 'var(--success, #10b981)',
            'bg' => 'rgba(16, 185, 129, 0.15)',
            'can' => ['menu_kesiswaan_kegiatan', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Prestasi',
            'title' => 'Prestasi Akademik & Nonakademik',
            'route' => route('dashboard.kesiswaan.prestasi.index'),
            'icon' => 'fa-trophy',
            'color' => '#8b5cf6',
            'bg' => 'rgba(139, 92, 246, 0.15)',
            'can' => ['menu_kesiswaan_prestasi', 'menu_kesiswaan'],
        ],
        [
            'label' => 'Target Capaian',
            'title' => 'Target & Capaian Kinerja',
            'route' => route('dashboard.tendik.target.index', ['bidang' => 'kesiswaan']),
            'icon' => 'fa-bullseye',
            'color' => 'var(--primary-light, #6366f1)',
            'bg' => 'rgba(99, 102, 241, 0.15)',
            'can' => ['menu_target_capaian'],
        ],
        [
            'label' => 'Laporan Kinerja',
            'title' => 'Laporan & Rekapitulasi Kinerja',
            'route' => route('dashboard.tendik.laporan.index', ['bidang' => 'kesiswaan']),
            'icon' => 'fa-file-invoice',
            'color' => '#ec4899',
            'bg' => 'rgba(236, 72, 153, 0.15)',
            'can' => ['menu_laporan_tendik'],
        ],
    ];
@endphp
<div class="kepegawaian-quick-grid">
    @foreach ($quickItems as $item)
        @continue(!collect($item['can'])->contains(fn ($key) => $can($key)))
        <a href="{{ $item['route'] }}" class="kepegawaian-quick-btn"
            style="--quick-color: {{ $item['color'] }};" title="{{ $item['title'] }}">
            <div class="kepegawaian-quick-icon" style="background: {{ $item['bg'] }}; color: {{ $item['color'] }};">
                <i class="fas {{ $item['icon'] }}"></i>
            </div>
            <span class="kepegawaian-quick-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>


<!-- Section: Visualisasi Statistik & Analitik Kesiswaan -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Demografi Kesiswaan
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi rasio gender, sebaran rombel per tingkat, serta distribusi jurusan kompetensi keahlian.
            </p>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary, #3b82f6); background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(59,130,246,0.25);">
            <i class="fas fa-user-graduate me-1"></i> {{ number_format($stats['total_siswa'] ?? 0, 0, ',', '.') }} Siswa
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Gender Siswa (Doughnut) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-venus-mars text-accent"></i> Proporsi Gender Siswa
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Rasio Laki-laki vs Perempuan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartGender"></canvas>
            </div>
            <div style="display: flex; justify-content: center; gap: 16px; margin-top: 10px; font-size: var(--text-sm, 0.8125rem); border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--primary, #3b82f6);"></span>
                    <span>Laki-laki: <strong>{{ $kesiswaanCharts['gender']['L'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ec4899;"></span>
                    <span>Perempuan: <strong>{{ $kesiswaanCharts['gender']['P'] ?? 0 }}</strong></span>
                </span>
            </div>
        </div>

        <!-- Chart 2: Siswa per Tingkat (Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-layer-group text-primary"></i> Siswa per Jenjang Kelas
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Populasi Siswa Kelas 10, 11, &amp; 12</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartTingkat"></canvas>
            </div>
        </div>

        <!-- Chart 3: Jurusan Keahlian (Horizontal Bar) -->
        <div style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-graduation-cap text-success"></i> Rombel per Kompetensi Keahlian
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Distribusi kelas jurusan sekolah</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="kesiswaanChartJurusan"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Kesiswaan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'kesiswaan',
    'bidangTitle' => 'Kesiswaan'
])

<!-- Payload JSON Data Chart Kesiswaan untuk JS -->
<script type="application/json" id="sectionKesiswaanChartPayload">
    {!! json_encode($kesiswaanCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>


