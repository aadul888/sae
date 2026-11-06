<!-- 4 Card Ringkasan Kartu Berhasil Cetak Baku SAE -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-id-card-clip"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($stats['total_sudah_cetak'] ?? ($stats['total_siswa'] ?? 0), 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Kartu Berhasil Dicetak</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($stats['total_lunas'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Pembayaran Lunas (Rp {{ number_format($stats['total_dana'] ?? 0, 0, ',', '.') }})</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format($stats['total_belum_bayar'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Belum Membayar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format(($stats['total_klaim_kartu'] ?? 0) + ($stats['total_klaim_stiker'] ?? 0), 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">
                Klaim Garansi ({{ $stats['total_klaim_kartu'] ?? 0 }} Kartu &bull; {{ $stats['total_klaim_stiker'] ?? 0 }} Stiker)
            </div>
        </div>
    </div>
</div>