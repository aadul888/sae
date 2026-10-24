<!-- REF-002 BEGIN: Sidebar Footer -->
<div class="dash-sidebar-footer">
    <button type="button" class="dash-nav-link btn-pwa-install" onclick="installSaePwa()"
        style="display: none; width: 100%; border: 1px solid rgba(16,185,129,0.25); background: rgba(16,185,129,0.12); color: #10b981; cursor: pointer; text-align: left; margin-bottom: 6px; border-radius: 8px;">
        <i class="fas fa-download"></i> <span>Instal Aplikasi SAE</span>
    </button>
    <a href="{{ route('home') }}" class="dash-nav-link" style="margin-bottom: 6px;">
        <i class="fas fa-globe"></i> <span>Lihat Web Publik</span>
    </a>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="dash-nav-link"
            style="width: 100%; border: none; background: rgba(239, 68, 68, 0.1); color: #ef4444; cursor: pointer; text-align: left;">
            <i class="fas fa-right-from-bracket"></i> <span>Keluar Sistem</span>
        </button>
    </form>
</div>
<!-- REF-002 END: Sidebar Footer -->
