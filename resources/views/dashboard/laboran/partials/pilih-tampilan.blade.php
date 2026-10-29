{{--
    Pemilih Versi (Ringkasan/Lengkap) + Orientasi (Portrait/Landscape) dokumen
    cetak Laboran — SATU dropdown ringkas pada bar alat.

    Dipakai layout `layouts.laboran-cetak` maupun view cetak standalone agar
    bar alat seragam dan tidak sesak. Setiap opsi = URL dokumen dengan `mode`
    dan `orientasi` yang bersangkutan; parameter lain (filter, periode, pilihan
    Kaprog) dipertahankan apa adanya.

    Variabel opsional: $mode, $orientasi.
--}}
@php
    $modeAktif = in_array(($mode ?? 'ringkasan'), ['ringkasan', 'lengkap'], true) ? $mode : 'ringkasan';
    $oriAktif  = in_array(($orientasi ?? 'portrait'), ['portrait', 'landscape'], true) ? $orientasi : 'portrait';

    $tautanVarian = fn ($m, $o) => request()->fullUrlWithQuery(['mode' => $m, 'orientasi' => $o]);
@endphp

<div class="doc-toolbar-group">
    <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
        title="Pilih versi &amp; orientasi kertas">
        <option value="{{ $tautanVarian('ringkasan', $oriAktif) }}"
            {{ $modeAktif === 'ringkasan' ? 'selected' : '' }}>
            Ringkasan — {{ $oriAktif === 'landscape' ? 'Mendatar' : 'Tegak' }}
        </option>
        <option value="{{ $tautanVarian('lengkap', $oriAktif) }}"
            {{ $modeAktif === 'lengkap' ? 'selected' : '' }}>
            Lengkap — {{ $oriAktif === 'landscape' ? 'Mendatar' : 'Tegak' }}
        </option>
        <option value="{{ $tautanVarian($modeAktif, 'portrait') }}"
            {{ $oriAktif === 'portrait' ? 'selected' : '' }}>
            Portrait
        </option>
        <option value="{{ $tautanVarian($modeAktif, 'landscape') }}"
            {{ $oriAktif === 'landscape' ? 'selected' : '' }}>
            Landscape
        </option>
    </select>
</div>
