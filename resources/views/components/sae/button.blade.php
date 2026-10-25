@props([
    'variant' => 'primary',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
    'block' => false,
])

@php
    $classes = collect([
        'sae-btn',
        'sae-btn--' . $variant,
        $size ? 'sae-btn--' . $size : null,
        $block ? 'sae-btn--block' : null,
    ])->filter()->implode(' ');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
        @if ($iconRight)
            <i class="{{ $iconRight }}" aria-hidden="true"></i>
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        {{ $slot }}
        @if ($iconRight)
            <i class="{{ $iconRight }}" aria-hidden="true"></i>
        @endif
    </button>
@endif
