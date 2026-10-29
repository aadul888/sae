{{--
    Pemilih penanda tangan Kepala Program Keahlian (KAPROG) untuk dokumen cetak
    Laboran.

    Tampil HANYA bila petugas ditugaskan pada lebih dari satu jurusan
    (`$kaprogOpsi` berisi ≥2 pilihan). Dokumen resmi hanya boleh ditandatangani
    Kaprog jurusan yang bersangkutan, jadi penggunalah yang memilih — sistem
    tidak menebak. Bila hanya satu jurusan, blok ini tidak merender apa pun.
--}}
@php
    $opsiKaprog = $kaprogOpsi ?? [];
    $jurusanAktif = $jurusanId ?? ($context['jurusan_id'] ?? null);

    // Pilihan pertama dipakai bila belum ada jurusan terpilih yang sah.
    $opsiIds = array_column($opsiKaprog, 'jurusan_id');
    if (!in_array($jurusanAktif, $opsiIds, true)) {
        $jurusanAktif = $opsiIds[0] ?? $jurusanAktif;
    }

    // Tautan tetap mempertahankan seluruh parameter dokumen (mode, orientasi,
    // filter, periode); hanya `jurusan_id` yang diganti.
    $tautanKaprog = fn ($id) => request()->url() . '?' . http_build_query(
        array_merge(request()->query(), ['jurusan_id' => $id])
    );
@endphp

@if (count($opsiKaprog) > 1)
    <div class="doc-toolbar-group">
        <span class="doc-toolbar-label">Tanda tangan Kaprog:</span>

        <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
            title="Pilih jurusan penanda tangan Kepala Program Keahlian">
            @foreach ($opsiKaprog as $opsi)
                <option value="{{ $tautanKaprog($opsi['jurusan_id']) }}"
                    {{ (string) $opsi['jurusan_id'] === (string) $jurusanAktif ? 'selected' : '' }}>
                    {{ $opsi['nama'] }}{{ empty($opsi['kaprog']?->nama) ? ' (belum ditetapkan)' : '' }}
                </option>
            @endforeach
        </select>
    </div>
@endif
