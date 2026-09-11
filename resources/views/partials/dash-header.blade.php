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
        @php
            $currentUser = session('user');
            $currentUserName = is_array($currentUser)
                ? $currentUser['name'] ?? ($currentUser['nama'] ?? 'Pengguna')
                : $currentUser->name ?? ($currentUser->nama ?? 'Pengguna');
            $currentUserRole = is_array($currentUser)
                ? $currentUser['role'] ?? 'peserta_didik'
                : $currentUser->role ?? 'peserta_didik';
            $currentUserId = (string) (is_array($currentUser)
                ? $currentUser['pengguna_id'] ?? ($currentUser['id'] ?? '')
                : $currentUser->pengguna_id ?? ($currentUser->id ?? ''));

            $userNotifications = collect();
            $notifCount = 0;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('pengumuman')) {
                    $allRoleNotifs = \App\Models\Pengumuman::forUserRole($currentUserRole)->get();
                    $notifCount = $allRoleNotifs->filter(fn($n) => !$n->sudahDibacaOleh($currentUserId))->count();
                    $userNotifications = $allRoleNotifs->take(6);
                }
            } catch (\Throwable $e) {
                $userNotifications = collect();
                $notifCount = 0;
            }

            $userFoto = session('user.foto_url');
        @endphp

        <!-- 1. Notification Bell & Dropdown Popup -->
        <div class="dash-notif-container" style="position: relative;">
            <button type="button" class="dash-icon-btn" id="notifBellBtn" title="Pengumuman & Notifikasi"
                aria-label="Notifikasi" aria-haspopup="true" aria-expanded="false" style="position: relative;">
                <i class="fas fa-bell"></i>
                @if ($notifCount > 0)
                    <span class="dash-notif-badge" id="bellNotifDot"
                        style="position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 2px solid var(--nav-bg); box-shadow: 0 0 6px rgba(239, 68, 68, 0.8);"></span>
                @endif
            </button>

            <!-- Dropdown Menu Notifikasi -->
            <div class="dash-notif-dropdown" id="notifDropdown" style="display: none;">
                <div
                    style="padding: 12px 16px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02);">
                    <div
                        style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bell text-primary"></i>
                        <span>Pengumuman</span>
                        @if ($notifCount > 0)
                            <span class="badge badge-danger" id="headerNotifBadge" style="font-size: 0.68rem; padding: 2px 7px;">
                                {{ $notifCount }} Baru
                            </span>
                        @endif
                    </div>
                    @if ($notifCount > 0)
                        <button type="button" id="btnQuickMarkAllRead"
                            style="background: none; border: none; font-size: 0.72rem; color: var(--primary); cursor: pointer; display: flex; align-items: center; gap: 4px; padding: 2px 6px; border-radius: 4px; font-weight: 600;"
                            title="Tandai semua pengumuman sudah dibaca">
                            <i class="fas fa-check-double"></i>
                            <span>Tandai Baca</span>
                        </button>
                    @endif
                </div>

                <div class="dash-notif-list">
                    @forelse ($userNotifications as $notif)
                        @php
                            $isUnread = !$notif->sudahDibacaOleh($currentUserId);
                        @endphp
                        <a href="{{ route('dashboard.informasi.index', ['highlight' => $notif->id]) }}" class="dash-notif-item {{ $isUnread ? 'unread-item' : '' }}">
                            <div class="dash-notif-row">
                                <div class="dash-notif-title">
                                    @if ($isUnread)
                                        <span class="dash-notif-dot" aria-hidden="true"></span>
                                    @endif
                                    <span>{{ $notif->judul }}</span>
                                </div>
                                <span class="dash-notif-time">
                                    {{ $notif->created_at ? $notif->created_at->diffForHumans(null, true) : '-' }}
                                </span>
                            </div>
                            <div class="dash-notif-content">
                                {{ $notif->isi }}
                            </div>
                            @if ($notif->penulis_nama)
                                <div class="dash-notif-author">
                                    <i class="fas fa-user-pen me-1"></i> {{ $notif->penulis_nama }}
                                </div>
                            @endif
                        </a>
                    @empty
                        <div
                            style="padding: 28px 16px; text-align: center; color: var(--text-muted); font-size: 0.82rem;">
                            <i class="fas fa-bell-slash mb-2" style="font-size: 1.6rem; opacity: 0.4;"></i>
                            <div>Tidak ada pengumuman saat ini.</div>
                        </div>
                    @endforelse
                </div>

                <div
                    style="padding: 9px 16px; background: rgba(99,102,241,0.05); border-top: 1px solid var(--border-color); text-align: center;">
                    <a href="{{ route('dashboard.informasi.index') }}"
                        style="font-size: 0.78rem; font-weight: 600; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        <span>Lihat Semua Informasi &amp; Pengumuman</span>
                        <i class="fas fa-arrow-right" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. User Avatar Button & Dropdown Menu -->
        <div class="dash-user-container" style="position: relative;">
            <button type="button" class="dash-user-btn" id="userMenuBtn" title="Menu Akun: {{ $currentUserName }}"
                aria-label="Menu Akun Pengguna" aria-haspopup="true" aria-expanded="false">
                @if ($userFoto)
                    <img src="{{ $userFoto }}" alt="{{ $currentUserName }}" class="dash-header-avatar">
                @else
                    <div class="dash-header-avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
            </button>

            <!-- Dropdown Menu Pengguna -->
            <div class="dash-user-dropdown" id="userDropdown" style="display: none;">
                <!-- Header Info Akun -->
                <div class="dash-user-dropdown-header">
                    <div class="dash-user-dropdown-avatar-wrap">
                        @if ($userFoto)
                            <img src="{{ $userFoto }}" alt="{{ $currentUserName }}" class="dash-user-dropdown-avatar">
                        @else
                            <div class="dash-user-dropdown-avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                    </div>
                    <div class="dash-user-dropdown-info">
                        <div class="dash-user-dropdown-name" title="{{ $currentUserName }}">{{ $currentUserName }}</div>
                        <div class="dash-user-dropdown-role">
                            <span class="dash-role-badge dash-role-badge-{{ $currentUserRole }}">
                                {{ strtoupper(str_replace('_', ' ', $currentUserRole)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Body Menu Item -->
                <div class="dash-user-dropdown-body">
                    <!-- Item 1: Profile -->
                    <a href="{{ route('dashboard.profile') }}" class="dash-user-dropdown-item">
                        <div class="dash-user-item-icon">
                            <i class="fas fa-id-badge text-primary"></i>
                        </div>
                        <div class="dash-user-item-text">
                            <div class="item-title">Profil Saya</div>
                            <div class="item-desc">Data akun &amp; ubah kata sandi</div>
                        </div>
                        <i class="fas fa-chevron-right item-arrow"></i>
                    </a>

                    <!-- Item 2: Mode Gelap / Terang -->
                    <button type="button" class="dash-user-dropdown-item theme-dropdown-action" id="dropdownThemeToggle">
                        <div class="dash-user-item-icon">
                            <i class="fa-solid fa-moon text-warning theme-icon"></i>
                        </div>
                        <div class="dash-user-item-text">
                            <div class="item-title theme-text">Mode Gelap / Terang</div>
                            <div class="item-desc">Beralih tema tampilan</div>
                        </div>
                        <span class="theme-status-pill" id="dropdownThemeBadge">Auto</span>
                    </button>
                </div>

                <!-- Footer Menu Item (Logout) -->
                <div class="dash-user-dropdown-footer">
                    <form action="{{ route('logout') }}" method="POST" id="headerLogoutForm" style="margin: 0; width: 100%;">
                        @csrf
                        <button type="submit" class="dash-user-logout-btn">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                            <span>Keluar dari Akun</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
