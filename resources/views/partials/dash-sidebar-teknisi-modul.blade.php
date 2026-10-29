{{--
    Submenu modul Teknisi Lapangan (generik), dikelompokkan per alur pengisian.

    Judul, ikon, dan daftar modul diambil dari TeknisiModuleRegistry agar
    penambahan modul baru tidak perlu menyunting sidebar.

    Param:
      $linkClass — kelas tautan ('dash-nav-sublink' atau 'dash-nav-nested-link')
      $iconClass — kelas ikon ('sub-icon' atau 'nested-icon')
      $grup      — (opsional) tampilkan hanya modul pada grup ini;
                   dikosongkan = tampilkan semua grup.
      $judul     — (opsional) cetak judul grup; default true.
--}}
@php
    $linkClass = $linkClass ?? 'dash-nav-sublink';
    $iconClass = $iconClass ?? 'nested-icon';
    $judul = $judul ?? true;
    $modul = collect(\App\Support\TeknisiModuleRegistry::all());
    $grupAktif = $grup ?? null;
@endphp
@foreach (\App\Support\TeknisiModuleRegistry::GRUP as $grupKey => $grupLabel)
    @php $items = $modul->filter(fn ($spec) => ($spec['grup'] ?? 'inspeksi') === $grupKey); @endphp
    @continue($items->isEmpty())
    @if ($grupAktif === null || $grupAktif === $grupKey)
        @if ($judul)
            <div
                style="padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;">
                {{ $grupLabel }}
            </div>
        @endif
        @foreach ($items as $key => $spec)
            <a href="{{ route('dashboard.teknisi.modul.index', ['modul' => $key]) }}"
                class="{{ $linkClass }} {{ request()->routeIs('dashboard.teknisi.modul.*') && request()->route('modul') === $key ? 'active' : '' }}">
                <span class="nav-icon {{ $iconClass }}"><i class="{{ $spec['icon'] }}"></i></span>
                <span class="nav-label">{{ $spec['title'] }}</span>
            </a>
        @endforeach
    @endif
@endforeach
