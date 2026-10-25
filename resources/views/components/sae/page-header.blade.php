@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
])

<div class="sae-page-header">
    <div>
        @if ($title)
            <h1 class="sae-page-header__title">
                @if ($icon)
                    <i class="{{ $icon }}" aria-hidden="true"></i>
                @endif
                {{ $title }}
            </h1>
        @endif

        @if ($subtitle)
            <p class="sae-page-header__subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="sae-page-header__actions">
            {{ $actions }}
        </div>
    @endisset
</div>
