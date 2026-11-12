@php
    $stats = $verifikasiStats ?? ['total' => 0, 'belum' => 0, 'diajukan' => 0, 'diverifikasi' => 0, 'ditolak' => 0];
@endphp

<!-- Stat Grid Baku SAE (5 Kartu Ringkasan Verifikasi Laporan) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($stats['total'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Wajib Lapor (SK Pemberian)</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(148,163,184,0.15); color: var(--text-muted, #94a3b8);">
            <i class="fas fa-file-circle-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--text-muted, #94a3b8);">
                {{ number_format($stats['belum'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Belum Melapor</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format($stats['diajukan'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Menunggu Verifikasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($stats['diverifikasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Disetujui</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
            <i class="fas fa-circle-xmark"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">
                {{ number_format($stats['ditolak'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Ditolak</div>
        </div>
    </div>
</div>
