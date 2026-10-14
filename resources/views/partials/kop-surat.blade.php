@php
    $sekolah = $sekolah ?? \App\Models\Sekolah::first();
    $sekolahMeta = $sekolahMeta ?? \App\Models\SekolahMeta::first();
    $kopUrl = $sekolahMeta?->kop_url;
    $logoUrl = $sekolahMeta?->logo_url ?: asset('img/logo-dark.png');
@endphp
<div class="kop-container">
    @if (!empty($kopUrl))
        <img src="{{ $kopUrl }}" alt="Kop Surat {{ $sekolah?->nama ?? 'Sekolah' }}" class="kop-image">
    @else
        <div class="kop-fallback">
            <img src="{{ $logoUrl }}" alt="Logo Sekolah" class="kop-logo" onerror="this.src='/img/logo-dark.png'">
            <div class="kop-text-wrap">
                <div class="kop-title">{{ $sekolah?->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                <div class="kop-subtitle">
                    NPSN: {{ $sekolah?->npsn ?? '-' }} &bull; Alamat:
                    {{ $sekolah?->alamat_jalan ?? 'Jalan Pendidikan' }}{{ !empty($sekolah?->kabupaten_kota) ? ', ' . $sekolah->kabupaten_kota : '' }}<br>
                    Kontak: {{ $sekolah?->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah?->email ?? '-' }}
                </div>
            </div>
        </div>
    @endif
</div>
