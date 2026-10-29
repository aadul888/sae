{{-- Partial: Sub-tab Laporan (rekap periode berjalan vs rekap per tahun ajaran). --}}
@php
    $subLaporan = [
        [
            'kunci' => 'periode',
            'label' => 'Rekap Periode',
            'icon' => 'fas fa-calendar-days',
            'url' => route('dashboard.perpustakaan.laporan.index'),
        ],
        [
            'kunci' => 'tahunan',
            'label' => 'Per Tahun Ajaran',
            'icon' => 'fas fa-chart-column',
            'url' => route('dashboard.perpustakaan.laporan.tahunan'),
        ],
    ];

    $bagianLaporan = request()->routeIs('dashboard.perpustakaan.laporan.tahunan*') ? 'tahunan' : 'periode';
@endphp

<div class="periode-nav-wrapper" style="margin-bottom: 16px;">
    <div class="periode-nav-desktop" style="flex-wrap: wrap; gap: 6px;">
        @foreach ($subLaporan as $sub)
            <a href="{{ $sub['url'] }}"
                class="periode-nav-tab {{ $bagianLaporan === $sub['kunci'] ? 'active' : '' }}">
                <i class="{{ $sub['icon'] }}"></i> {{ $sub['label'] }}
            </a>
        @endforeach
    </div>
    <div class="periode-nav-mobile">
        <div class="periode-mobile-select-box" style="margin-bottom: 0;">
            <label><i class="fas fa-chart-line text-primary me-1"></i> Jenis Laporan:</label>
            <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;"
                aria-label="Pilih jenis laporan perpustakaan">
                @foreach ($subLaporan as $sub)
                    <option value="{{ $sub['url'] }}" {{ $bagianLaporan === $sub['kunci'] ? 'selected' : '' }}>
                        {{ $sub['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
