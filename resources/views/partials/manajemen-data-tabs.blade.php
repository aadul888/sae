{{--
    Navigasi tab Manajemen Data (Guru / Tendik / Aktif / Tidak Aktif).
    Parameter:
      $tabs  => [ ['key' => 'guru', 'label' => 'Guru', 'href' => url, 'active' => bool, 'count' => int|null] ]
--}}
@php
    $tabs = $tabs ?? [];
@endphp

@if (!empty($tabs))
    <div class="dash-tabs" style="display: flex; flex-wrap: wrap; gap: 6px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px;">
        @foreach ($tabs as $t)
            @php
                $aktif = !empty($t['active']);
                $nonaktifTab = !empty($t['muted']);
            @endphp
            <a href="{{ $t['href'] }}"
                class="dash-tab-btn {{ $aktif ? 'active' : '' }}"
                style="padding: 11px 18px; font-weight: 700; font-size: var(--text-base, 0.875rem); text-decoration: none;
                       display: inline-flex; align-items: center; gap: 8px; margin-bottom: -2px; transition: all 0.2s ease;
                       color: {{ $aktif ? 'var(--primary)' : 'var(--text-muted)' }};
                       border-bottom: 2px solid {{ $aktif ? 'var(--primary)' : 'transparent' }};">
                @if (!empty($t['icon']))
                    <i class="fas {{ $t['icon'] }}"></i>
                @endif
                <span>{{ $t['label'] }}</span>
                @if (isset($t['count']))
                    <span style="font-size: var(--text-xs, 0.75rem); padding: 1px 7px; border-radius: var(--radius-pill);
                                 background: {{ $aktif ? 'rgba(99,102,241,0.14)' : 'rgba(148,163,184,0.16)' }};
                                 color: {{ $aktif ? 'var(--primary)' : 'var(--text-muted)' }};">
                        {{ number_format((int) $t['count'], 0, ',', '.') }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
@endif
