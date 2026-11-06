@php
    $selisih = $kuotaStats['selisih'] ?? 0;
    $isKelebihan = $selisih > 0;
    $isKurang = $selisih < 0;
    $selisihColor = $isKelebihan ? '#ef4444' : ($isKurang ? '#f59e0b' : '#10b981');
    $selisihBg = $isKelebihan
        ? 'rgba(239,68,68,0.12)'
        : ($isKurang
            ? 'rgba(245,158,11,0.12)'
            : 'rgba(16,185,129,0.12)');
    $selisihIcon = $isKelebihan ? 'fa-circle-exclamation' : ($isKurang ? 'fa-triangle-exclamation' : 'fa-circle-check');
    $selisihTitle = $isKelebihan ? 'Kelebihan +' . $selisih : ($isKurang ? 'Kurang ' . abs($selisih) : 'Pas (100%)');
@endphp

<!-- Stat Grid Baku SAE (4 Kartu Ringkasan Kuota) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- Card 1: Target Kuota Sekolah -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-bullseye"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($kuotaStats['target_total'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Pagu Sekolah</div>
        </div>
    </div>

    <!-- Card 2: Total Kuota Teralokasi -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: var(--primary-hover, #2563eb);">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary-hover, #2563eb);">
                {{ number_format($kuotaStats['total_teralokasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">
                Kuota Teralokasi
                <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                    ({{ number_format($kuotaStats['rombel_tersedia'] ?? 0) }} sisa &bull; {{ number_format($kuotaStats['rombel_penuh'] ?? 0) }} penuh)
                </span>
            </div>
        </div>
    </div>

    <!-- Card 3: Status Kelebihan / Kurang Kuota (Highlight Visual) -->
    <div class="dash-stat-card"
        style="{{ $isKelebihan ? 'outline: 2px solid var(--danger, #ef4444);' : ($isKurang ? 'outline: 2px solid var(--warning, #f59e0b);' : '') }}">
        <div class="dash-stat-icon" style="background: {{ $selisihBg }}; color: {{ $selisihColor }};">
            <i class="fas {{ $selisihIcon }}"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: {{ $selisihColor }};">
                {{ $selisihTitle }}
            </div>
            <div class="dash-stat-label">
                @if ($isKelebihan)
                    Kelebihan Pagu
                @elseif ($isKurang)
                    Belum Terbagi
                @else
                    Alokasi Pas
                @endif
            </div>
        </div>
    </div>

    <!-- Card 4: Total Usulan Terpakai -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($kuotaStats['total_terpakai'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Usulan Masuk</div>
        </div>
    </div>
</div>