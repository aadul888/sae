@props([
    'value' => null,
    'label' => null,
    'icon' => null,
    'color' => 'primary',
])

@php
    // Warna ikon memakai variabel tema agar konsisten gelap/terang.
    $colorVar = match ($color) {
        'success' => 'var(--success)',
        'warning' => 'var(--warning)',
        'danger' => 'var(--danger)',
        'accent' => 'var(--accent)',
        'purple' => 'var(--purple)',
        default => 'var(--primary)',
    };
@endphp

<div {{ $attributes->class(['sae-stat']) }}>
    @if ($icon)
        <span class="sae-stat__icon"
            style="background: color-mix(in srgb, {{ $colorVar }} 14%, transparent); color: {{ $colorVar }};">
            <i class="{{ $icon }}" aria-hidden="true"></i>
        </span>
    @endif

    <div class="sae-stat__body">
        <div class="sae-stat__value">{{ $value }}</div>
        @if ($label)
            <div class="sae-stat__label">{{ $label }}</div>
        @endif
    </div>
</div>
