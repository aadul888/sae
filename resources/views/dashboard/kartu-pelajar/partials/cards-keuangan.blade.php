<!-- 4 Card Ringkasan Keuangan Kartu Pelajar Baku SAE -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-arrow-down-long"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                Rp {{ number_format($keuangan['total_pemasukan'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Uang Masuk</div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                {{ number_format($keuangan['total_transaksi_masuk'] ?? 0, 0, ',', '.') }} pembayaran lunas
            </div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: var(--danger, #ef4444);">
            <i class="fas fa-arrow-up-long"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--danger, #ef4444);">
                Rp {{ number_format($keuangan['total_pengeluaran'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Belanja Bahan/Alat</div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                {{ number_format($keuangan['total_transaksi_keluar'] ?? 0, 0, ',', '.') }} catatan logistik
            </div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                Rp {{ number_format($keuangan['saldo_bersih'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Sisa Saldo Kas Bersih</div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                {{ ($keuangan['saldo_bersih'] ?? 0) >= 0 ? 'Surplus / Kas Tersedia' : 'Defisit Anggaran' }}
            </div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-clock-rotate-left"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                Rp {{ number_format($keuangan['estimasi_piutang'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Estimasi Piutang Siswa</div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                Dari {{ number_format($keuangan['siswa_belum_bayar'] ?? 0, 0, ',', '.') }} siswa belum bayar
            </div>
        </div>
    </div>
</div>