{{--
    Blok tanda tangan dokumen cetak Laboran.

    Urutan pihak (kiri → kanan):
      1. Petugas Laboran (pelaksana)
      2. Kepala Laboratorium / Bengkel (KEPALA_LAB)
      3. Kepala Program Keahlian (KAPROG) — NAMA DITAMPILKAN sesuai jurusan
      4. Kepala Satuan Pendidikan (mengetahui)

    Variabel opsional:
      • $petugas, $kepalaLab, $kaprog, $kepalaSekolah
      • $sigTitles — override judul tiap kolom (array 4 elemen)
      • $sigTanggal — tampilkan baris tempat/tanggal pada kolom terakhir
--}}
@php
    $petugas       = $petugas ?? null;
    $kepalaLab     = $kepalaLab ?? null;
    $kaprog        = $kaprog ?? null;
    $kepalaSekolah = $kepalaSekolah ?? null;

    $petugasNama = is_array($petugas) ? ($petugas['nama'] ?? null) : ($petugas->nama ?? null);
    $petugasNip  = is_array($petugas) ? ($petugas['nip'] ?? null) : ($petugas->nip ?? null);

    $sigTitles = $sigTitles ?? [
        'Petugas Laboran,',
        'Kepala Laboratorium / Bengkel,',
        'Kepala Program Keahlian,',
        'Mengetahui,<br>Kepala Satuan Pendidikan,',
    ];

    $namaJurusan = $jurusan->nama_jurusan ?? ($context['jurusan_nama'] ?? null);

    // Penanda tangan yang belum memiliki penugasan aktif → diberi garis titik
    // agar dokumen tetap sah ditandatangani manual saat supervisi.
    $belumDitetapkan = fn ($pejabat) => empty($pejabat?->nama) ? ' sig-belum-ditetapkan' : '';
@endphp

<div class="signature-container">
    <div class="sig-box">
        <div class="sig-title">{!! $sigTitles[0] !!}</div>
        <div class="sig-name">{{ $petugasNama ?: '...................................' }}</div>
        <div class="sig-nip">NIP: {{ $petugasNip ?: '—' }}</div>
    </div>

    <div class="sig-box{{ $belumDitetapkan($kepalaLab) }}">
        <div class="sig-title">{!! $sigTitles[1] !!}</div>
        <div class="sig-name">{{ $kepalaLab?->nama ?: '...................................' }}</div>
        <div class="sig-nip">
            NIP: {{ $kepalaLab?->nip ?: ($kepalaLab?->nuptk ?: '—') }}
            @if (empty($kepalaLab?->nama))
                <br><span class="sig-jurusan">belum ditetapkan</span>
            @endif
        </div>
    </div>

    <div class="sig-box{{ $belumDitetapkan($kaprog) }}">
        <div class="sig-title">{!! $sigTitles[2] !!}</div>
        <div class="sig-name">{{ $kaprog?->nama ?: '...................................' }}</div>
        <div class="sig-nip">
            NIP: {{ $kaprog?->nip ?: ($kaprog?->nuptk ?: '—') }}
            @if (!empty($namaJurusan))
                <br><span class="sig-jurusan">{{ $namaJurusan }}</span>
            @elseif (empty($kaprog?->nama))
                <br><span class="sig-jurusan">belum ditetapkan</span>
            @endif
        </div>
    </div>

    <div class="sig-box{{ $belumDitetapkan($kepalaSekolah) }}">
        <div class="sig-title">{!! $sigTitles[3] !!}</div>
        <div class="sig-name">{{ $kepalaSekolah?->nama ?: '...................................' }}</div>
        <div class="sig-nip">
            NIP: {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}
            @if (empty($kepalaSekolah?->nama))
                <br><span class="sig-jurusan">belum ditetapkan</span>
            @endif
        </div>
    </div>
</div>
