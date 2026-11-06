<!-- 4 Card Ringkasan Antrean Request Cetak Kartu Pelajar Baku SAE -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
    @foreach ([['menunggu', 'Menunggu', 'fa-hourglass-half', '#f59e0b', '245,158,11'], ['diproses', 'Diproses', 'fa-gears', '#2563eb', '37,99,235'], ['selesai', 'Selesai', 'fa-circle-check', '#10b981', '16,185,129'], ['ditolak', 'Ditolak', 'fa-ban', '#ef4444', '239,68,68']] as [$sKey, $sLabel, $sIcon, $sColor, $sRgb])
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request', 'req_status' => ($reqStatus ?? '') === $sKey ? '' : $sKey, 'q' => $q ?? '', 'perPage' => $perPage ?? 15]) }}"
            class="dash-stat-card"
            style="text-decoration: none; {{ ($reqStatus ?? '') === $sKey ? 'outline: 2px solid ' . $sColor . ';' : '' }}">
            <div class="dash-stat-icon"
                style="background: rgba({{ $sRgb }},0.12); color: {{ $sColor }};">
                <i class="fas {{ $sIcon }}"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: {{ $sColor }};">
                    {{ number_format($requestStats[$sKey] ?? 0, 0, ',', '.') }}
                </div>
                <div class="dash-stat-label">{{ $sLabel }}</div>
            </div>
        </a>
    @endforeach
</div>