{{--
    Blok tanda tangan dokumen cetak Teknisi (A4).

    Pihak penanda tangan (kiri → kanan):
      1. Teknisi pelaksana (petugas yang login saat mencetak)
      2. Kepala Tenaga Administrasi Sekolah (Kepala TAS) — tugas tambahan KEPALA_TAS
      3. Kepala Satuan Pendidikan (mengetahui)

    Pejabat diresolusi sekali di controller (`$teknisiTtd`) agar view tetap
    tipis; variabel opsional agar komponen ini juga aman dipakai mandiri.
--}}
@php
    $teknisiTtd = $teknisiTtd ?? [];
    $petugasTtd    = $teknisiTtd['petugas'] ?? null;
    $kepalaTasTtd  = $teknisiTtd['kepalaTas'] ?? null;
    $kepalaSekTtd  = $teknisiTtd['kepalaSekolah'] ?? null;

    $namaPetugas = is_array($petugasTtd) ? ($petugasTtd['nama'] ?? null) : ($petugasTtd->nama ?? null);
    $nipPetugas  = is_array($petugasTtd) ? ($petugasTtd['nip'] ?? null) : ($petugasTtd->nip ?? null);

    $titikKosong = fn ($pejabat) => empty($pejabat?->nama) ? ' sig-belum-ditetapkan' : '';
@endphp

<div class="signature-container">
    <div class="sig-box">
        <div class="sig-title">Teknisi,</div>
        <div class="sig-name">{{ $namaPetugas ?: '...................................' }}</div>
        <div class="sig-nip">NIP: {{ $nipPetugas ?: '—' }}</div>
    </div>

    <div class="sig-box{{ $titikKosong($kepalaTasTtd) }}">
        <div class="sig-title">Kepala Tenaga Administrasi Sekolah,</div>
        <div class="sig-name">{{ $kepalaTasTtd?->nama ?: '...................................' }}</div>
        <div class="sig-nip">
            NIP: {{ $kepalaTasTtd?->nip ?: ($kepalaTasTtd?->nuptk ?: '—') }}
            @if (empty($kepalaTasTtd?->nama))
                <br><span class="sig-jurusan">belum ditetapkan</span>
            @endif
        </div>
    </div>

    <div class="sig-box{{ $titikKosong($kepalaSekTtd) }}">
        <div class="sig-title">Mengetahui,<br>Kepala Satuan Pendidikan,</div>
        <div class="sig-name">{{ $kepalaSekTtd?->nama ?: '...................................' }}</div>
        <div class="sig-nip">
            NIP: {{ $kepalaSekTtd?->nip ?: ($kepalaSekTtd?->nuptk ?: '—') }}
            @if (empty($kepalaSekTtd?->nama))
                <br><span class="sig-jurusan">belum ditetapkan</span>
            @endif
        </div>
    </div>
</div>
