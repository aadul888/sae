@props([
    'fromName' => 'dari',
    'toName' => 'sampai',
    'fromValue' => null,
    'toValue' => null,
])

{{-- Rentang tanggal baku toolbar datatable: From – Sampai dalam satu baris. --}}
<div class="toolbar-filter-range">
    <x-sae.toolbar-date :name="$fromName" :value="$fromValue" label="Dari tanggal" title="Dari Tanggal" />
    <span class="toolbar-filter-range__sep" aria-hidden="true">–</span>
    <x-sae.toolbar-date :name="$toName" :value="$toValue" label="Sampai tanggal" title="Sampai Tanggal" />
</div>
