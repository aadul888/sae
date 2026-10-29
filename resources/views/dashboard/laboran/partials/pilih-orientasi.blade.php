{{--
    Pemilih Orientasi (Portrait / Landscape) dokumen cetak — dropdown ringkas.

    Untuk halaman cetak yang tidak punya pilihan versi (mis. Kerusakan, Peserta
    Didik Aktif): bar alat tetap seragam dengan modul lain.

    Variabel opsional: $orientasi.
--}}
@php
    $oriAktif = in_array(($orientasi ?? 'portrait'), ['portrait', 'landscape'], true) ? $orientasi : 'portrait';
@endphp

<div class="doc-toolbar-group">
    <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
        title="Pilih orientasi kertas A4">
        <option value="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
            {{ $oriAktif === 'portrait' ? 'selected' : '' }}>
            Portrait
        </option>
        <option value="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
            {{ $oriAktif === 'landscape' ? 'selected' : '' }}>
            Landscape
        </option>
    </select>
</div>
