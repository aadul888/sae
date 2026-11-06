<!-- 4 Card Ringkasan Kuota & Log Garansi Kartu Pelajar Baku SAE -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format(($stats['total_klaim_kartu'] ?? 0) + ($stats['total_klaim_stiker'] ?? 0), 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Klaim Garansi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($stats['total_klaim_kartu'] ?? 0, 0, ',', '.') }} kali
            </div>
            <div class="dash-stat-label">Klaim Ganti Kartu Full</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-sticky-note"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format($stats['total_klaim_stiker'] ?? 0, 0, ',', '.') }} kali
            </div>
            <div class="dash-stat-label">Klaim Cetak Stiker Saja</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
            <i class="fas fa-sliders"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent);">
                {{ $settings->kuota_ganti_kartu_full ?? 1 }} / {{ $settings->kuota_ganti_stiker ?? 3 }}
            </div>
            <div class="dash-stat-label">Batas Kuota / Siswa (Kartu / Stiker)</div>
        </div>
    </div>
</div>