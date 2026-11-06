{{--
    Navigasi tab Manajemen Data (Guru / Tendik / GTK Non-ASN / Peserta Didik Aktif / Tidak Aktif).
    Menggunakan standar komponen SAE: .periode-nav-wrapper (.periode-nav-desktop & .periode-nav-mobile)
    Konsisten dengan master-data/rombel/reguler.
    Parameter:
      $tabs  => [ ['key' => 'guru', 'label' => 'Guru', 'href' => url, 'active' => bool, 'icon' => string, 'count' => int|null] ]
--}}
@php
    $tabs = $tabs ?? [];
@endphp

@if (!empty($tabs))
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            @foreach ($tabs as $t)
                @php
                    $aktif = !empty($t['active']);
                @endphp
                <a href="{{ $t['href'] }}"
                    class="periode-nav-tab {{ $aktif ? 'active' : '' }}">
                    @if (!empty($t['icon']))
                        <i class="fas {{ $t['icon'] }}"></i>
                    @endif
                    <span>{{ $t['label'] }}</span>
                    @if (isset($t['count']))
                        <span style="font-size: var(--text-xs, 0.75rem); padding: 1px 7px; border-radius: var(--radius-pill);
                                     background: {{ $aktif ? 'rgba(255,255,255,0.22)' : 'rgba(148,163,184,0.16)' }};
                                     color: {{ $aktif ? '#ffffff' : 'var(--text-muted)' }}; margin-left: 2px;">
                            {{ number_format((int) $t['count'], 0, ',', '.') }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="periode-nav-mobile">
            <div class="periode-mobile-select-box" style="margin-bottom: 0;">
                <label><i class="fas fa-filter text-primary me-1"></i> Pilih Kategori Data:</label>
                <select class="periode-mobile-select form-select" onchange="if(this.value) window.location.href=this.value;"
                    style="font-size: var(--text-base, 0.875rem); height: 42px; border-radius: var(--radius-lg, 12px); width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                    @foreach ($tabs as $t)
                        @php
                            $aktif = !empty($t['active']);
                        @endphp
                        <option value="{{ $t['href'] }}" {{ $aktif ? 'selected' : '' }}>
                            {{ $t['label'] }} {{ isset($t['count']) ? '(' . number_format((int) $t['count'], 0, ',', '.') . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endif
