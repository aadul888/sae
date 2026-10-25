@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<div {{ $attributes->class(['sae-card']) }}>
    @if ($title || isset($actions))
        <div class="sae-card__header">
            @if ($title)
                <h3 class="sae-card__title">
                    @if ($icon)
                        <i class="{{ $icon }}" aria-hidden="true"></i>
                    @endif
                    {{ $title }}
                </h3>
            @endif

            @isset($actions)
                <div class="sae-row">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if ($subtitle)
        <p class="sae-page-header__subtitle" style="margin-bottom: var(--space-6);">{{ $subtitle }}</p>
    @endif

    <div class="sae-card__body">
        {{ $slot }}
    </div>
</div>
