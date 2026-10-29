{{-- Partial: Sub-tab Katalog & Koleksi (daftar katalog, import massal, input scan barcode).
     Dipakai oleh halaman Katalog (koleksi) dan halaman Import & Scan agar keduanya terasa satu modul. --}}
@php
    $bagianKoleksi = request()->routeIs('dashboard.perpustakaan.import*')
        ? (request()->query('bagian') === 'scan' ? 'scan' : 'import')
        : 'daftar';

    $subKoleksi = [
        [
            'kunci' => 'daftar',
            'label' => 'Daftar Katalog',
            'icon' => 'fas fa-list',
            'url' => route('dashboard.perpustakaan.koleksi.index'),
        ],
        [
            'kunci' => 'import',
            'label' => 'Import Massal',
            'icon' => 'fas fa-file-import',
            'url' => route('dashboard.perpustakaan.import.form', ['bagian' => 'import']) . '#import-massal',
        ],
        [
            'kunci' => 'scan',
            'label' => 'Input Scan Barcode',
            'icon' => 'fas fa-barcode',
            'url' => route('dashboard.perpustakaan.import.form', ['bagian' => 'scan']) . '#input-scan',
        ],
    ];
@endphp

<div class="periode-nav-wrapper" style="margin-bottom: 16px;">
    <div class="periode-nav-desktop" style="flex-wrap: wrap; gap: 6px;">
        @foreach ($subKoleksi as $sub)
            <a href="{{ $sub['url'] }}"
                class="periode-nav-tab {{ $bagianKoleksi === $sub['kunci'] ? 'active' : '' }}">
                <i class="{{ $sub['icon'] }}"></i> {{ $sub['label'] }}
            </a>
        @endforeach
    </div>
    <div class="periode-nav-mobile">
        <div class="periode-mobile-select-box" style="margin-bottom: 0;">
            <label><i class="fas fa-book text-primary me-1"></i> Bagian Katalog:</label>
            <select class="periode-mobile-select" onchange="if(this.value) window.location.href=this.value;"
                aria-label="Pilih bagian katalog">
                @foreach ($subKoleksi as $sub)
                    <option value="{{ $sub['url'] }}" {{ $bagianKoleksi === $sub['kunci'] ? 'selected' : '' }}>
                        {{ $sub['label'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
