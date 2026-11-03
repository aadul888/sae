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
            <i class="fas fa-sliders"></i> Pengaturan
        </a>
        <a href="{{ route('dashboard.kesiswaan.buku-induk.mapel.index') }}"
            class="periode-nav-tab {{ $activeTab === 'mapel' ? 'active' : '' }}">
            <i class="fas fa-diagram-project"></i> Maping Mata Pelajaran
        </a>
        <a href="{{ route('dashboard.kesiswaan.buku-induk.input-nilai') }}"
            class="periode-nav-tab {{ $activeTab === 'input_nilai' ? 'active' : '' }}">
            <i class="fas fa-pen-to-square"></i> Input Nilai
        </a>
    </div>
</div>
