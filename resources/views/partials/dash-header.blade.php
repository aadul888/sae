<header class="dash-header">
    <div class="dash-header-left">
        <button type="button" class="dash-toggle-btn" id="dashToggleBtn" aria-label="Toggle Sidebar">
            <i class="fas fa-bars-staggered"></i>
        </button>
        <div class="dash-breadcrumb">
            <i class="fas fa-layer-group text-primary"></i>
            <span>@yield('dash_title', 'Dashboard')</span>
        </div>
    </div>

    <div class="dash-header-right">
        <!-- Theme Switcher (Single Action Button) -->
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Ganti Tema">
            <i class="fas fa-moon"></i>
        </button>

        <!-- Notification Bell (Dummy) -->
        <div style="position: relative; cursor: pointer;" title="Notifikasi">
            <div class="dash-icon-btn" style="position: relative;">
                <i class="fas fa-bell"></i>
                <span style="position: absolute; top: 6px; right: 6px; width: 8px; height: 8px; background: var(--danger); border-radius: 50%;"></span>
            </div>
        </div>

        <!-- Quick Profile Link / Role Badge -->
        @php
            $currentUser = session('user');
        @endphp
        <div style="display: flex; align-items: center; gap: 10px; padding-left: 10px; border-left: 1px solid var(--border-glass);">
            <div style="text-align: right; display: none; line-height: 1.2;" class="d-md-block">
                <div style="font-size: 0.85rem; font-weight: 700; color: var(--text-main);">{{ $currentUser['name'] ?? 'Pengguna' }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase;">{{ $currentUser['role'] ?? 'Member' }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn btn-outline" style="padding: 7px 12px; font-size: 0.8rem; border-color: rgba(239,68,68,0.3); color: var(--danger);" title="Keluar">
                    <i class="fas fa-power-off"></i>
                </button>
            </form>
        </div>
    </div>
</header>
