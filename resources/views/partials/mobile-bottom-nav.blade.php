@php
    $user = session('user', ['role' => 'siswa']);
    $role = $user['role'] ?? 'siswa';
    $currentRoute = request()->route()->getName();
@endphp

<nav class="mobile-bottom-nav">
    <!-- 1. Home -->
    <a href="{{ route('dashboard.' . $role) }}" class="mobile-nav-item {{ request()->routeIs('dashboard.*') && !request()->has('tab') ? 'active' : '' }}">
        <div class="mobile-nav-icon">
            <i class="fas fa-house"></i>
        </div>
        <span class="mobile-nav-label">Home</span>
    </a>

    <!-- 2. Identitas -->
    <a href="?tab=identitas" class="mobile-nav-item {{ request('tab') === 'identitas' ? 'active' : '' }}">
        <div class="mobile-nav-icon">
            <i class="fas fa-id-card"></i>
        </div>
        <span class="mobile-nav-label">Identitas</span>
    </a>

    <!-- 3. Berkas (Prominent / Floating Center Button) -->
    <a href="?tab=berkas" class="mobile-nav-item item-prominent {{ request('tab') === 'berkas' ? 'active' : '' }}">
        <div class="prominent-btn">
            <i class="fas fa-folder-open"></i>
        </div>
        <span class="mobile-nav-label">Berkas</span>
    </a>

    <!-- 4. Laporan -->
    <a href="?tab=laporan" class="mobile-nav-item {{ request('tab') === 'laporan' ? 'active' : '' }}">
        <div class="mobile-nav-icon">
            <i class="fas fa-chart-column"></i>
        </div>
        <span class="mobile-nav-label">Laporan</span>
    </a>

    <!-- 5. FAQ -->
    <a href="?tab=faq" class="mobile-nav-item {{ request('tab') === 'faq' ? 'active' : '' }}">
        <div class="mobile-nav-icon">
            <i class="fas fa-circle-question"></i>
        </div>
        <span class="mobile-nav-label">FAQ</span>
    </a>
</nav>
