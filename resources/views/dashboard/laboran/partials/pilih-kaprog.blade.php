{{--
    Pemilih penanda tangan Kepala Program Keahlian (KAPROG) untuk dokumen cetak
    Laboran.

    Tampil HANYA bila petugas ditugaskan pada lebih dari satu jurusan
    (`$kaprogOpsi` berisi ≥2 pilihan). Dokumen resmi hanya boleh ditandatangani
    Kaprog jurusan yang bersangkutan, jadi penggunalah yang memilih — sistem
    tidak menebak.

    Bila hanya satu jurusan, blok ini tidak merender apa pun.
--}}
@php
    $opsiKaprog = $kaprogOpsi ?? [];
    $jurusanAktif = $jurusanId ?? ($context['jurusan_id'] ?? null);

    // Pertahankan seluruh parameter dokumen (mode, orientasi, filter, periode)
    // dan hanya ganti `jurusan_id`.
    $tautanKaprog = function ($id) {
        $q = array_merge(request()->query(), ['jurusan_id' => $id]);
        unset($q['hal']);

        return request()->url() . '?' . http_build_query($q);
    };
@endphp

@if (count($opsiKaprog) > 1)
    <div class="doc-toolbar-group">
        <span class="doc-toolbar-label">Tanda tangan Kaprog:</span>

        @foreach ($opsiKaprog as $opsi)
            @php $aktif = (string) $opsi['jurusan_id'] === (string) $jurusanAktif; @endphp
            <a href="{{ $tautanKaprog($opsi['jurusan_id']) }}"
                class="btn-orientasi {{ $aktif ? 'active' : '' }}"
                title="Tandatangani oleh Kaprog {{ $opsi['nama'] }}{{ empty($opsi['kaprog']?->nama) ? ' (belum ditetapkan)' : ' — ' . $opsi['kaprog']->nama }}">
                <i class="fas fa-graduation-cap"></i> {{ $opsi['nama'] }}
            </a>
        @endforeach

        <span class="doc-toolbar-sep"></span>
    </div>
@endif
