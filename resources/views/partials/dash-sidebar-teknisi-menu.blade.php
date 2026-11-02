{{--
    Menu grup "Teknisi & IT" — sumber tunggal untuk seluruh varian sidebar.

    Urutan grup mengikuti alur pengisian administrasi harian
    (Master → Rencana → Harian → Perbaikan → Inspeksi/Tindak Lanjut), semuanya
    dari TeknisiModuleRegistry::menu() agar tidak ada lagi daftar menu ganda.

    Param:
      $linkClass — kelas tautan anak ('dash-nav-sublink' atau 'dash-nav-nested-link')
      $iconClass — kelas ikon ('sub-icon' atau 'nested-icon')
      $dashLabel — label tautan dashboard (default 'Dashboard Teknisi')
      $dashActive — bool tautan dashboard aktif
--}}
@php
    use App\Support\TeknisiModuleRegistry;

    $linkClass = $linkClass ?? 'dash-nav-nested-link';
    $iconClass = $iconClass ?? 'nested-icon';
    $dashLabel = $dashLabel ?? 'Dashboard Teknisi';
    $dashActive = $dashActive ?? (
        request()->routeIs('dashboard.tendik') && request()->query('bidang') === 'teknisi'
    );
    $grupJudul = 'padding: 8px 14px 3px; font-size: var(--text-2xs, 0.6875rem); font-weight: 800;'
        . ' letter-spacing: 0.6px; text-transform: uppercase; color: var(--text-muted); opacity: 0.75;';
    $menu = TeknisiModuleRegistry::menu();
@endphp

<a href="{{ route('dashboard.tendik', ['bidang' => 'teknisi']) }}"
    class="{{ $linkClass }} {{ $dashActive ? 'active' : '' }}">
    <span class="nav-icon {{ $iconClass }}"><i class="fas fa-fw fa-gauge-high"></i></span>
    <span class="nav-label">{{ $dashLabel }}</span>
</a>

@foreach (TeknisiModuleRegistry::GRUP as $grupKey => $grupLabel)
    @php $items = $menu[$grupKey] ?? []; @endphp
    @continue(empty($items))
    <div style="{{ $grupJudul }}">{{ $grupLabel }}</div>
    @foreach ($items as $item)
        @php
            $izin = $item['can'] ?? [];
            $boleh = empty($izin) || collect($izin)->contains(fn ($k) => $can($k));
            $aktif = request()->routeIs($item['active']);
            if ($item['route'] === 'dashboard.teknisi.modul.index') {
                $aktif = $aktif && request()->route('modul') === ($item['params']['modul'] ?? null);
            }
        @endphp
        @continue(!$boleh)
        <a href="{{ route($item['route'], $item['params'] ?? []) }}"
            class="{{ $linkClass }} {{ $aktif ? 'active' : '' }}"
            @if (!empty($item['title'])) title="{{ $item['title'] }}" @endif>
            <span class="nav-icon {{ $iconClass }}"><i class="{{ $item['icon'] }}"></i></span>
            <span class="nav-label">{{ $item['label'] }}</span>
        </a>
    @endforeach
@endforeach
