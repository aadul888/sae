    <!-- Brand -->
    <div class="dash-sidebar-header">
        @php
            $dashLogoDark = asset('img/logo-dark.png') . '?v=' . (@filemtime(public_path('img/logo-dark.png')) ?: '1');
            $dashLogoLight = asset('img/logo-light.png') . '?v=' . (@filemtime(public_path('img/logo-light.png')) ?: '1');
            $dashLogoIcon = asset('img/logo-icon.png') . '?v=' . (@filemtime(public_path('img/logo-icon.png')) ?: '1');
        @endphp
        <a href="{{ route($dashHomeRoute) }}" class="brand dash-sidebar-brand-link"
            style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
            <img id="dashLogo" src="{{ $dashLogoDark }}" data-dark="{{ $dashLogoDark }}" data-light="{{ $dashLogoLight }}"
                alt="SAE Logo" class="dash-brand-full" style="height: 36px; max-width: 140px; object-fit: contain;"
                onerror="this.onerror=null; this.src='/img/logo-dark.png';">
            <img id="dashLogoMini" src="{{ $dashLogoIcon }}" alt="SAE Logo" class="dash-brand-mini"
                style="height: 32px; width: 32px; object-fit: contain; display: none;"
                onerror="this.onerror=null; this.src='/img/logo-icon.png';">
        </a>
        <span class="dash-sidebar-version"
            style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px; line-height: 1;">
            <span class="badge badge-primary"
                style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; font-family: monospace; white-space: nowrap;"
                title="Versi sistem aplikasi terpasang">v{{ $appVersion ?? '1.0.5' }}</span>
            <span class="badge badge-outline"
                style="font-size: var(--text-2xs, 0.6875rem); padding: 2px 6px; font-family: monospace; white-space: nowrap;"
                data-pwa-version="short" title="Versi PWA di perangkat ini">PWA v—</span>
        </span>
    </div>
