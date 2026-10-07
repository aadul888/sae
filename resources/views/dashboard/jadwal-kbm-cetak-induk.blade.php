<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Induk KBM - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    <div class="print-page">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- Header Dokumen Bersih (Tanpa Kop Surat) -->
        <div class="doc-header">
            <div class="school-name">{{ $sekolah->nama ?? 'SEKOLAH' }}</div>
            <div class="doc-title-main">JADWAL PELAJARAN INDUK SEKOLAH</div>
            <div class="doc-subtitle">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }} &bull; Berlaku untuk
                Seluruh Rombongan Belajar</div>
        </div>

        <!-- Matriks Jadwal per Hari -->
        @foreach ($hariList as $hari)
            <div style="margin-bottom: 24px; page-break-inside: avoid;">
                <div class="day-header-banner"
                    style="background: #1e3a8a; color: #ffffff !important; padding: 6px 12px; font-weight: 800; font-size: 0.82rem; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #ffffff !important;"><i class="fas fa-calendar-day me-1"
                            style="color: #ffffff !important;"></i> HARI {{ strtoupper($hari) }}</span>
                    <span style="color: #ffffff !important;">Total JP: {{ count($slots) }} JP</span>
                </div>
                <table class="table-induk">
                    <thead>
                        <tr>
                            <th style="width: 140px; background-color: #1e3a8a !important; color: #ffffff !important;">
                                Kelas / Rombel</th>
                            @foreach ($slots as $slot)
                                <th style="background-color: #1e3a8a !important; color: #ffffff !important;">
                                    <div style="color: #ffffff !important; font-weight: 700;">JP {{ $slot['ke'] }}
                                    </div>
                                    <div style="font-size: 0.62rem; font-weight: 500; color: #e2e8f0 !important;">
                                        {{ $slot['mulai'] }}-{{ $slot['selesai'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rombels as $rombel)
                            <tr>
                                <td class="th-rombel">{{ $rombel->nama }}</td>
                                @foreach ($slots as $slot)
                                    @php
                                        $sK = $slot['ke'];
                                        $item = $matrix[$rombel->rombongan_belajar_id][$hari][$sK] ?? null;
                                        $isOccupied = !empty($occupied[$rombel->rombongan_belajar_id][$hari][$sK]);
                                    @endphp
                                    @if ($item)
                                        @php
                                            $span = $item->jam_ke_selesai - $item->jam_ke_mulai + 1;
                                            $isUpacara = $item->mata_pelajaran_id === 'UPACARA';
                                            $isPembiasaan = $item->mata_pelajaran_id === 'PEMBIASAAN';
                                            $isIstirahat = $item->mata_pelajaran_id === 'ISTIRAHAT';

                                            $cellClass = 'cell-kbm';
                                            if ($isUpacara) {
                                                $cellClass .= ' cell-upacara';
                                            } elseif ($isPembiasaan) {
                                                $cellClass .= ' cell-pembiasaan';
                                            } elseif ($isIstirahat) {
                                                $cellClass .= ' cell-istirahat';
                                            }
                                        @endphp
                                        <td class="{{ $cellClass }}" colspan="{{ $span }}">
                                            @if ($isUpacara)
                                                <span class="routine-title"><i class="fas fa-flag text-danger"></i>
                                                    {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Dewan Guru &amp; Siswa</span>
                                                @if ($item->ruangan)
                                                    <span
                                                        style="font-size: 0.6rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
                                                @endif
                                            @elseif ($isPembiasaan)
                                                <span class="routine-title"><i
                                                        class="fas fa-hands-praying text-success"></i>
                                                    {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Wali Kelas &amp; Guru</span>
                                            @elseif ($isIstirahat)
                                                <span class="routine-title"><i class="fas fa-mug-hot text-warning"></i>
                                                    {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Jeda Istirahat</span>
                                            @else
                                                <span class="mapel-name">{{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">{{ $item->nama_guru ?? 'Guru' }}</span>
                                                @if ($item->ruangan)
                                                    <span
                                                        style="font-size: 0.6rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
                                                @endif
                                            @endif
                                        </td>
                                    @elseif (!$isOccupied)
                                        <td class="cell-empty">-</td>
                                    @endif
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($slots) + 1 }}" style="padding: 12px; color: #9ca3af;">Tidak ada
                                    data rombongan belajar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach

        <!-- Tanda Tangan -->
        <div class="signature-container">
            <div class="sig-box">
                <p>Mengetahui,</p>
                <p style="font-weight: 600;">Kepala Sekolah,</p>
                <div class="sig-space"></div>
                <p class="sig-name">
                    {{ $kepalaSekolah?->nama ?? '......................................................' }}</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP.
                    {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</p>
            </div>

            <div class="sig-box">
                <p>{{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }},
                    {{ now()->translatedFormat('d F Y') }}</p>
                <p style="font-weight: 600;">Waka Kurikulum,</p>
                <div class="sig-space"></div>
                <p class="sig-name">
                    {{ $wakaKurikulum?->nama ?? '......................................................' }}</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP.
                    {{ $wakaKurikulum?->nip ?: ($wakaKurikulum?->nuptk ?: '—') }}</p>
            </div>
        </div>
    </div>

</body>

</html>
