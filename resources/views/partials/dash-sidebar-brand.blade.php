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
                style="height: 30px; width: 30px; object-fit: contain; display: none;"
                onerror="this.onerror=null; this.src='/img/logo-icon.png';">
        </a>
        <button type="button" class="dash-sidebar-toggle-btn" id="btnToggleSidebarMini"
            title="Kecilkan Sidebar (Minimize)" aria-label="Toggle Minimize Sidebar">
            <i class="fas fa-angles-left" id="iconToggleSidebarMini"></i>
        </button>
    </div>
