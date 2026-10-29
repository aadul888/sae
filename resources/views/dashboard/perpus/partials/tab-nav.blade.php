{{-- Partial: Tab navigasi sub-modul Perpustakaan --}}
<div class="periode-nav-wrapper" style="margin-bottom: 20px;">
    <div class="periode-nav-desktop" style="flex-wrap: wrap; gap: 6px;">
        <a href="{{ route('dashboard.perpustakaan.koleksi.index') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.koleksi*') ? 'active' : '' }}">
            <i class="fas fa-book"></i> Katalog Koleksi Buku
        </a>
        <a href="{{ route('dashboard.perpustakaan.sirkulasi') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.sirkulasi*') ? 'active' : '' }}">
            <i class="fas fa-exchange-alt"></i> Sirkulasi Peminjaman
        </a>
        <a href="{{ route('dashboard.perpustakaan.kunjungan') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.kunjungan*') ? 'active' : '' }}">
            <i class="fas fa-user-check"></i> Buku Kunjungan
        </a>
        <a href="{{ route('dashboard.perpustakaan.anggota.index') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.anggota*') ? 'active' : '' }}">
            <i class="fas fa-id-card"></i> Anggota &amp; Kartu
        </a>
        <a href="{{ route('dashboard.perpustakaan.import.form') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.import*') ? 'active' : '' }}">
            <i class="fas fa-file-import"></i> Import &amp; Scan
        </a>
        <a href="{{ route('dashboard.perpustakaan.laporan.index') }}"
            class="periode-nav-tab {{ request()->routeIs('dashboard.perpustakaan.laporan*') ? 'active' : '' }}">
            <i class="fas fa-file-lines"></i> Laporan &amp; Rekap
        </a>
    </div>
</div>
