@props([
    'name' => 'dari',
    'value' => null,
    'label' => 'Dari',
    'title' => 'Dari Tanggal',
    'id' => null,
])

{{-- Kontrol tanggal baku toolbar datatable (Laboran, Teknisi, dll). --}}
<div class="toolbar-filter-date" title="{{ $title }}">
    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
    <input type="date" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif
        value="{{ $value }}" aria-label="{{ $label }}">
</div>
