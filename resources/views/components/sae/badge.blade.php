@props([
    'variant' => 'neutral',
    'dot' => false,
])

<span {{ $attributes->class(['sae-badge', 'sae-badge--' . $variant]) }}>
    @if ($dot)
        <i class="fas fa-circle" style="font-size: var(--text-2xs, 0.6875rem);" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</span>
