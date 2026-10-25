    <!-- Brand -->
    <div class="dash-sidebar-header">
        @php
            $dashLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
            $dashLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
        @endphp
        <a href="{{ route($dashHomeRoute) }}" class="brand"
            style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img id="dashLogo" src="{{ $dashLogoDark }}" data-dark="{{ $dashLogoDark }}" data-light="{{ $dashLogoLight }}"
                alt="SAE Logo" style="height: 36px; max-width: 140px; object-fit: contain;"
                onerror="this.onerror=null; this.src='/img/logo-dark.png';">
        </a>
        <span class="badge badge-primary"
            style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; font-family: monospace;">v{{ $appVersion ?? '1.0.1' }}</span>
    </div>
