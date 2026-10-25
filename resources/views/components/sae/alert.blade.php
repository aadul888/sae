@props([
    'variant' => 'info',
    'icon' => null,
    'dismissible' => false,
])

@php
    $defaultIcons = [
        'success' => 'fas fa-circle-check',
        'warning' => 'fas fa-triangle-exclamation',
        'danger' => 'fas fa-circle-exclamation',
        'info' => 'fas fa-circle-info',
    ];
    $resolvedIcon = $icon ?? ($defaultIcons[$variant] ?? 'fas fa-circle-info');
@endphp

<div {{ $attributes->class(['sae-alert', 'sae-alert--' . $variant]) }} role="alert">
    <i class="{{ $resolvedIcon }}" aria-hidden="true" style="margin-top: 2px; flex: 0 0 auto;"></i>

    <div style="flex: 1 1 auto;">
        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" class="sae-btn sae-btn--ghost sae-btn--icon sae-btn--sm"
            onclick="this.closest('.sae-alert').remove()" aria-label="Tutup pesan">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    @endif
</div>
