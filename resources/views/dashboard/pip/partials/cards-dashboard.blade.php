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
@endphp

<!-- Stats Counter Grid Baku SAE (4 Kartu Metrik Dashboard PIP) -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- Card 1: Total Usulan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-hand-holding-dollar"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($totalUsulan) }}
            </div>
            <div class="dash-stat-label">Total Usulan</div>
        </div>
    </div>

    <!-- Card 2: Status Usulan (Sudah & Belum) -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($sudahDiajukan) }}
            </div>
            <div class="dash-stat-label">
                Sudah Diusulkan
                <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: block; font-weight: 600; margin-top: 2px;">
                    ({{ number_format($belumDiajukan) }} belum diajukan)
                </span>
            </div>
        </div>
    </div>

    <!-- Card 3: Kuota Sekolah / Kelas -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent);">
                @if ($isAdmin)
                    {{ number_format($paguSekolah) }}
                @else
                    {{ $terpakaiKelas }} / {{ $kuotaKelas }}
                @endif
            </div>
            <div class="dash-stat-label">
                @if ($isAdmin)
                    Pagu Kuota ({{ $pctTerpakai }}%)
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: block; font-weight: 600; margin-top: 2px;">
                        ({{ number_format($sisaKuota) }} sisa kuota)
                    </span>
                @else
                    Kuota Kelas
                    <span style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); display: block; font-weight: 600; margin-top: 2px;">
                        ({{ $sisaKuotaKelas }} sisa kuota)
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Card 4: Riwayat Dana Cair -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(14,165,233,0.12); color: #0ea5e9;">
            <i class="fas fa-money-bill-trend-up"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value"
                style="font-size: var(--text-2xl, 1.5rem); color: #0ea5e9; white-space: nowrap;">
                Rp {{ number_format($totalNominal, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Dana Cair</div>
        </div>
    </div>
</div>