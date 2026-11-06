@php
    $kuotaPenuh = ($stats['sisa_kuota_kelas'] ?? 0) <= 0;
@endphp

@if (!$isAdmin && !empty($isWaliLocked))
    <!-- Notice Usulan Terkunci untuk Wali Kelas -->
    <div
        style="margin-bottom: 18px; padding: 14px 18px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.08); border: 1.5px solid rgba(239,68,68,0.3); color: var(--danger, #ef4444); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-lg, 12px); background: rgba(239,68,68,0.15); display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-lock"></i>
            </div>
            <div>
                <strong style="font-size: var(--text-base, 0.875rem); color: var(--danger, #ef4444);">Pengusulan PIP Sedang Ditutup / Terkunci</strong>
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                    Pengusulan oleh Wali Kelas saat ini dikunci oleh Administrator atau telah melewati batas waktu pengisian.
                    Data usulan hanya dapat dipantau dan tidak dapat ditambah atau diubah urutan prioritasnya.
                </div>
            </div>
        </div>
        <span class="badge badge-danger" style="font-size: var(--text-sm, 0.8125rem); padding: 4px 10px;">
            <i class="fas fa-ban me-1"></i> Mode Readonly
        </span>
    </div>
@endif

<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    <!-- 1. Total Usulan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-users"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--primary);">
                {{ number_format($stats['total_usulan'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">Total Usulan</div>
        </div>
    </div>

    <!-- 2. Sudah Diusulkan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ number_format($stats['sudah_diajukan'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">
                Sudah Diusulkan
                @if (!empty($stats['total_usulan_diubah']))
                    <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                        ({{ number_format($stats['total_usulan_diubah']) }} ada perubahan)
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. Belum Diusulkan -->
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-hourglass-half"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ number_format($stats['belum_diajukan'] ?? 0, 0, ',', '.') }}
            </div>
            <div class="dash-stat-label">
                Belum Diusulkan
                @if (!empty($stats['ditolak']))
                    <span style="font-size: var(--text-xs, 0.75rem); opacity: 0.8; display: block;">
                        ({{ number_format($stats['ditolak']) }} ditolak)
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- 4. Kuota Kelas / Pagu Kuota -->
    @if ($activeRombel)
        <div class="dash-stat-card">
            <div class="dash-stat-icon"
                style="background: {{ $kuotaPenuh ? 'rgba(239,68,68,0.12)' : 'rgba(6,182,212,0.12)' }}; color: {{ $kuotaPenuh ? '#ef4444' : 'var(--accent)' }};">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value"
                    style="font-size: var(--text-2xl, 1.5rem); color: {{ $kuotaPenuh ? '#ef4444' : 'var(--accent)' }};">
                    {{ $stats['terpakai_kelas'] ?? 0 }} / {{ $stats['kuota_kelas'] ?? 0 }}
                </div>
                <div class="dash-stat-label">Kuota Kelas {{ $activeRombel->nama }}</div>
            </div>
        </div>
    @else
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                <i class="fas fa-ticket"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent);">
                    {{ number_format($stats['total_usulan'] ?? 0, 0, ',', '.') }} /
                    {{ number_format($stats['total_pagu_sekolah'] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">Pagu Sekolah</div>
            </div>
        </div>
    @endif
</div>