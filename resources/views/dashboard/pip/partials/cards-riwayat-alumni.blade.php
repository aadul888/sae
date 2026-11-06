<!-- Stat Grid Baku SAE (4 Kartu Ringkasan Riwayat Kelas Lain & Alumni) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($riwayatAlumniStats['total_siswa'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Alumni & Non-Aktif</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($riwayatAlumniStats['total_pemberian'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Pemberian</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-file-signature"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format($riwayatAlumniStats['total_nominasi'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">SK Nominasi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: #0ea5e9; white-space: nowrap;">
                Rp {{ number_format($riwayatAlumniStats['total_nominal'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Cair</div>
        </div>
    </div>
</div>