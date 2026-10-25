@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'invalid' => null,
])

@php
    // Ambil pesan galat bawaan Laravel untuk field ini bila ada.
    // `$errors` hanya tersedia saat view dirender lewat request web (ViewErrorBag),
    // jadi aksesnya harus defensif agar komponen tetap aman dipakai di konteks lain.
    $fieldName = $name ?? $attributes->get('id');
    $bag = $errors ?? null;
    $errorMessage = $invalid ?? ($bag && $fieldName ? $bag->first($fieldName) : null);
    $controlClass = 'sae-input' . ($errorMessage ? ' sae-input--invalid' : '');
@endphp

<div {{ $attributes->only('class')->class(['sae-field']) }}>
    @if ($label)
        <label class="sae-field__label" for="{{ $fieldName }}">
            {{ $label }}
            @if ($required)
                <span class="sae-required" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    @if ($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $fieldName }}"
            @if ($required) required @endif
            {{ $attributes->except(['class'])->class([$controlClass, 'sae-textarea']) }}
        >{{ old($name, $value) }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $fieldName }}"
            value="{{ old($name, $value) }}"
            @if ($required) required @endif
            {{ $attributes->except(['class'])->class([$controlClass]) }}
        >
    @endif

    @if ($hint && !$errorMessage)
        <span class="sae-field__hint">{{ $hint }}</span>
    @endif

    @if ($errorMessage)
        <span class="sae-field__error">{{ $errorMessage }}</span>
    @endif
</div>
