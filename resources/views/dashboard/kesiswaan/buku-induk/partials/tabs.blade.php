@php $activeTab = $activeTab ?? 'peserta'; @endphp
{{-- Navigasi Tab Buku Induk (di bawah banner, sebelum konten) --}}
<div class="periode-nav-wrapper" style="margin-bottom: 20px;">
    <div class="periode-nav-desktop">
        <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}"
            class="periode-nav-tab {{ $activeTab === 'peserta' ? 'active' : '' }}">
            <i class="fas fa-address-book"></i> Buku Induk
        </a>
        <a href="{{ route('dashboard.kesiswaan.buku-induk.import-nilai') }}"
            class="periode-nav-tab {{ $activeTab === 'nilai' ? 'active' : '' }}">
            <i class="fas fa-file-import"></i> Import Nilai &amp; Pengaturan
        </a>
    </div>
</div>
