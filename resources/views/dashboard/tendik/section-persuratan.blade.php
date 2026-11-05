{{-- Section Dashboard: Staf Administrasi Persuratan --}}

<!-- 1. Card Rekap Statistik Persuratan (Paling Atas) -->
<div class="dash-stat-grid" style="margin-bottom: 20px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_masuk'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Masuk</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-paper-plane"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_keluar'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Keluar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($stats['total_surat_ket'] ?? 0) }} Dok</div>
            <div class="dash-stat-label">Surat Keterangan Siswa</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-hard-drive"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-lg, 1.125rem);">{{ $hdd['free_formatted'] ?? '-' }}</div>
            <div class="dash-stat-label">Sisa Ruang HDD (Bebas)</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')

<!-- Tab Navigasi Modul Persuratan -->
@include('dashboard.persuratan.partials.tab-nav')


<!-- 3. Akses Cepat Menu Persuratan (selaras submenu sidebar & tupoksi) -->
@php
    $can = $can ?? fn(string $key) => \App\Models\RolePermission::canAccess(session('user') ?: 'tendik', $key, 'read');
    $quickItems = [
        [
            'label' => 'Buat Surat',
            'title' => 'Buat Surat Otomatis dari Template',
            'route' => route('dashboard.persuratan.keluar.index', ['tab' => 'template']),
            'icon' => 'fa-wand-magic-sparkles',
            'color' => 'var(--purple, #8b5cf6)',
            'bg' => 'rgba(139, 92, 246, 0.15)',
            'group' => 'layanan',
            'can' => ['menu_surat_keluar', 'menu_persuratan'],
        ],
        [
            'label' => 'Surat Masuk',
            'title' => 'Buku Agenda Surat Masuk',
            'route' => route('dashboard.persuratan.masuk.index'),
            'icon' => 'fa-inbox',
            'color' => 'var(--primary, #3b82f6)',
            'bg' => 'rgba(59, 130, 246, 0.15)',
            'group' => 'layanan',
            'can' => ['menu_surat_masuk', 'menu_persuratan'],
        ],
        [
            'label' => 'Surat Keluar',
            'title' => 'Buku Agenda Surat Keluar',
            'route' => route('dashboard.persuratan.keluar.index'),
            'icon' => 'fa-paper-plane',
            'color' => 'var(--success, #10b981)',
            'bg' => 'rgba(16, 185, 129, 0.15)',
            'group' => 'layanan',
            'can' => ['menu_surat_keluar', 'menu_persuratan'],
        ],
        [
            'label' => 'Buku Ekspedisi',
            'title' => 'Buku Ekspedisi Pengiriman Surat Keluar',
            'route' => route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']),
            'icon' => 'fa-truck-fast',
            'color' => 'var(--info, #0ea5e9)',
            'bg' => 'rgba(14, 165, 233, 0.15)',
            'group' => 'layanan',
            'can' => ['menu_surat_keluar', 'menu_persuratan'],
        ],
        [
            'label' => 'Pengaturan & Arsip',
            'title' => 'Pengaturan Persuratan & Kearsipan HDD',
            'route' => route('dashboard.persuratan.pengaturan.index'),
            'icon' => 'fa-sliders',
            'color' => 'var(--warning, #f59e0b)',
            'bg' => 'rgba(245, 158, 11, 0.15)',
            'group' => 'layanan',
            'can' => ['menu_pengaturan_persuratan', 'menu_persuratan'],
        ],
        [
            'label' => 'Target Capaian',
            'title' => 'Target & Capaian Kinerja',
            'route' => route('dashboard.tendik.target.index', ['bidang' => 'persuratan']),
            'icon' => 'fa-bullseye',
            'color' => 'var(--primary-light, #6366f1)',
            'bg' => 'rgba(99, 102, 241, 0.15)',
            'group' => 'kinerja',
            'can' => ['menu_target_capaian'],
        ],
        [
            'label' => 'Aktivitas Harian',
            'title' => 'Input Log Aktivitas Harian',
            'route' => route('dashboard.tendik.aktivitas.index', ['bidang' => 'persuratan']),
            'icon' => 'fa-clipboard-check',
            'color' => 'var(--accent, #06b6d4)',
            'bg' => 'rgba(6, 182, 212, 0.15)',
            'group' => 'kinerja',
            'can' => ['menu_aktivitas_tendik'],
        ],
        [
            'label' => 'Laporan Kinerja',
            'title' => 'Laporan & Rekapitulasi Kinerja',
            'route' => route('dashboard.tendik.laporan.index', ['bidang' => 'persuratan']),
            'icon' => 'fa-file-invoice',
            'color' => '#ec4899',
            'bg' => 'rgba(236, 72, 153, 0.15)',
            'group' => 'laporan',
            'can' => ['menu_laporan_tendik'],
        ],
        [
            'label' => 'Agenda Masuk',
            'title' => 'Cetak Buku Agenda Surat Masuk',
            'route' => route('dashboard.persuratan.masuk.agenda.cetak'),
            'icon' => 'fa-file-lines',
            'color' => 'var(--primary, #3b82f6)',
            'bg' => 'rgba(59, 130, 246, 0.15)',
            'group' => 'laporan',
            'can' => ['menu_surat_masuk', 'menu_persuratan'],
        ],
        [
            'label' => 'Agenda Keluar',
            'title' => 'Cetak Buku Agenda Surat Keluar',
            'route' => route('dashboard.persuratan.keluar.agenda.cetak'),
            'icon' => 'fa-print',
            'color' => 'var(--success, #10b981)',
            'bg' => 'rgba(16, 185, 129, 0.15)',
            'group' => 'laporan',
            'can' => ['menu_surat_keluar', 'menu_persuratan'],
        ],
        [
            'label' => 'Cetak Ekspedisi',
            'title' => 'Cetak Buku Ekspedisi Surat Keluar',
            'route' => route('dashboard.persuratan.keluar.ekspedisi.cetak'),
            'icon' => 'fa-file-export',
            'color' => 'var(--info, #0ea5e9)',
            'bg' => 'rgba(14, 165, 233, 0.15)',
            'group' => 'laporan',
            'can' => ['menu_surat_keluar', 'menu_persuratan'],
        ],
    ];
@endphp

<div class="kepegawaian-quick-grid" style="margin-bottom: 24px;" data-quick-title="Menu Cepat Persuratan">
    @foreach ($quickItems as $item)
        @continue(!collect($item['can'])->contains(fn($key) => $can($key)))
        <a href="{{ $item['route'] }}" class="kepegawaian-quick-btn"
            style="--quick-color: {{ $item['color'] }};" title="{{ $item['title'] }}"
            data-quick-group="{{ $item['group'] ?? '' }}">
            <div class="kepegawaian-quick-icon" style="background: {{ $item['bg'] }}; color: {{ $item['color'] }};">
                <i class="fas {{ $item['icon'] }}"></i>
            </div>
            <span class="kepegawaian-quick-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
</div>



<!-- 2. Card Interaktif: Nomor Surat Masuk & Keluar Terakhir (Dashboard) -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="card"
        style="padding: 14px 18px; border-radius: var(--radius-lg, 12px); border: 1px solid rgba(59,130,246,0.25); background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(99,102,241,0.03) 100%); display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
            <div
                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(59,130,246,0.15); color: var(--primary, #3b82f6); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-inbox"></i>
            </div>
            <div style="min-width: 0;">
                <div
                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    No. Surat Masuk Terakhir</div>
                <div style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--primary-hover, #2563eb); font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                    title="{{ $stats['no_surat_masuk_terakhir'] ?? '-' }}">
                    {{ $stats['no_surat_masuk_terakhir'] ?? '-' }}
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 6px; flex-shrink: 0;">
            <button type="button" class="btn btn-outline btn-sm"
                onclick="copyNomorSurat(this, '{{ $stats['no_surat_masuk_terakhir'] ?? '' }}')"
                style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);" title="Salin nomor">
                <i class="fas fa-copy"></i>
            </button>
            <a href="{{ route('dashboard.persuratan.masuk.index') }}" class="btn btn-outline btn-sm"
                style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);" title="Buka menu surat masuk">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>

    <div class="card"
        style="padding: 14px 18px; border-radius: var(--radius-lg, 12px); border: 1px solid rgba(16,185,129,0.25); background: linear-gradient(135deg, rgba(16,185,129,0.05) 0%, rgba(59,130,246,0.03) 100%); display: flex; align-items: center; justify-content: space-between; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
            <div
                style="width: 42px; height: 42px; border-radius: var(--radius-lg, 12px); background: rgba(16,185,129,0.15); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div style="min-width: 0;">
                <div
                    style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    No. Surat Keluar Terakhir</div>
                <div style="font-size: var(--text-md, 1rem); font-weight: 800; color: var(--success, #10b981); font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                    title="{{ $stats['no_surat_keluar_terakhir'] ?? '-' }}">
                    {{ $stats['no_surat_keluar_terakhir'] ?? '-' }}
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 6px; flex-shrink: 0;">
            <button type="button" class="btn btn-outline btn-sm"
                onclick="copyNomorSurat(this, '{{ $stats['no_surat_keluar_terakhir'] ?? '' }}')"
                style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);" title="Salin nomor">
                <i class="fas fa-copy"></i>
            </button>
            <a href="{{ route('dashboard.persuratan.keluar.index') }}" class="btn btn-outline btn-sm"
                style="padding: 6px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-md, 8px);" title="Buka menu surat keluar">
                <i class="fas fa-arrow-up-right-from-square"></i>
            </a>
        </div>
    </div>
</div>

@php
    $hdd = $stats['hdd_status'] ?? [
        'is_ready' => true,
        'free_formatted' => '54.2 GB',
        'total_formatted' => '256 GB',
        'percent_used' => 21.2,
        'path' => storage_path('app/arsip_persuratan'),
    ];
@endphp


<!-- 4. Section: Visualisasi Statistik & Distribusi Persuratan -->
<div class="card"
    style="padding: 20px 22px; margin-bottom: 24px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3
                style="font-size: var(--text-lg, 1.125rem); font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Distribusi Persuratan
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi volume surat berdasarkan jenis administrasi serta status pemrosesan dokumen resmi.
            </p>
        </div>
        <div
            style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--success, #10b981); background: rgba(16,185,129,0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(16,185,129,0.25);">
            <i class="fas fa-envelope-open-text me-1"></i> Tata Kelola Arsip Digital
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- Chart 1: Jenis Surat (Doughnut) -->
        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-mail-bulk text-primary"></i> Distribusi Jenis Surat
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Surat Masuk, Keluar, SK,
                &amp; Tugas</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="persuratanChartJenis"></canvas>
            </div>
        </div>

        <!-- Chart 2: Status Pemrosesan Surat (Bar) -->
        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-tasks text-success"></i> Status Pemrosesan Dokumen
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Diproses, Selesai, &amp;
                Diarsipkan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="persuratanChartStatus"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- 4. Section: Target & Capaian Aktivitas & Indikator Kinerja Persuratan -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'persuratan',
    'bidangTitle' => 'Persuratan & Tata Usaha',
])

<!-- Payload JSON Data Chart Persuratan untuk JS -->
<script type="application/json" id="sectionPersuratanChartPayload">
    {!! json_encode($persuratanCharts ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
    if (typeof window.copyNomorSurat !== 'function') {
        window.copyNomorSurat = function(btn, text) {
            if (!text || text === '-') return;
            const oldHtml = btn.innerHTML;
            const updateBtn = () => {
                btn.innerHTML = '<i class="fas fa-check text-success"></i>';
                setTimeout(() => {
                    btn.innerHTML = oldHtml;
                }, 2000);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(updateBtn).catch(() => {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                        document.execCommand('copy');
                        updateBtn();
                    } catch (e) {}
                    document.body.removeChild(ta);
                });
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    updateBtn();
                } catch (e) {}
                document.body.removeChild(ta);
            }
        };
    }
</script>
