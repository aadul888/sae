@props([
    'title' => 'Belum ada data',
    'text' => null,
    'icon' => 'fa-inbox',
])

<div {{ $attributes->class(['sae-empty']) }}>
    <span class="sae-empty__icon">
        <i class="fas {{ $icon }}" aria-hidden="true"></i>
    </span>

    <p class="sae-empty__title">{{ $title }}</p>

    @if ($text)
        <p class="sae-empty__text">{{ $text }}</p>
    @endif

    @isset($action)
        {{ $action }}
    @endisset
</div>
