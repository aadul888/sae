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

    <div class="periode-nav-mobile">
        <div class="periode-mobile-select-box" style="margin-bottom: 0;">
            <label style="display: block; font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); margin-bottom: 4px;">
                <i class="fas fa-filter text-primary me-1"></i> Pilih Menu Buku Induk:
            </label>
            <select class="periode-mobile-select form-select" onchange="if(this.value) window.location.href=this.value;"
                style="font-size: var(--text-base, 0.875rem); height: 42px; border-radius: var(--radius-lg, 12px); width: 100%; background: var(--bg-card, #131b2e); color: var(--text-color); border: 1px solid var(--border-color);">
                <option value="{{ route('dashboard.kesiswaan.buku-induk.index') }}" {{ $activeTab === 'peserta' ? 'selected' : '' }}>
                    Buku Induk
                </option>
                <option value="{{ route('dashboard.kesiswaan.buku-induk.import-nilai') }}" {{ $activeTab === 'nilai' ? 'selected' : '' }}>
                    Pengaturan
                </option>
                <option value="{{ route('dashboard.kesiswaan.buku-induk.mapel.index') }}" {{ $activeTab === 'mapel' ? 'selected' : '' }}>
                    Maping Mata Pelajaran
                </option>
                <option value="{{ route('dashboard.kesiswaan.buku-induk.input-nilai') }}" {{ $activeTab === 'input_nilai' ? 'selected' : '' }}>
                    Input Nilai
                </option>
            </select>
        </div>
    </div>
</div>
