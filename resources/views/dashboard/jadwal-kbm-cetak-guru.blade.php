<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar Guru - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Jadwal Guru (Print / PDF)
        </button>
    </div>

    @forelse ($guruList as $guru)
        @php
            $totalJp = $totalJpPerGuru[$guru->ptk_id] ?? 0;
            $totalSesi = $totalSesiPerGuru[$guru->ptk_id] ?? 0;
        @endphp
        <div class="guru-page">
            <!-- Watermark Logo SAE -->
            <div class="watermark-sae">
                <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
            </div>

            <!-- Header Dokumen Bersih (Tanpa Kop Surat) -->
            <div class="doc-header">
                <div class="school-name">{{ $sekolah->nama ?? 'SEKOLAH' }}</div>
                <div class="doc-title-main">JADWAL MENGAJAR GURU (KARTU GTK)</div>
                <div class="doc-subtitle">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }} &bull; Semester Genap
                </div>
            </div>

            <div class="bio-box">
                <div>
                    <div class="bio-item">
                        <span class="bio-label">Nama Guru:</span>
                        <strong style="color: #1e3a8a; font-size: 0.9rem;">{{ $guru->nama }}</strong>
                    </div>
                    <div class="bio-item">
                        <span class="bio-label">NIP / NUPTK:</span>
                        <span>{{ $guru->nip ?: ($guru->nuptk ?: '-') }}</span>
                    </div>
                </div>
                <div>
                    <div class="bio-item">
                        <span class="bio-label">Total Beban:</span>
                        <strong style="color: #2563eb;">{{ $totalJp }} Jam Pelajaran (JP) / Minggu</strong>
                    </div>
                    <div class="bio-item">
                        <span class="bio-label">Jumlah Sesi:</span>
                        <span>{{ $totalSesi }} Pertemuan KBM</span>
                    </div>
                </div>
            </div>

            <!-- Matriks Jam x Hari (Format Per Kelas dengan Pemetaan Rombel & Mapel) -->
            <table class="table-guru">
                <thead>
                    <tr>
                        <th class="th-jam" style="background-color: #1e3a8a !important; color: #ffffff !important;">Jam
                            / Waktu</th>
                        @foreach ($hariList as $h)
                            <th style="background-color: #1e3a8a !important; color: #ffffff !important;">
                                {{ strtoupper($h) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($slots as $slot)
                        @php
                            $sK = $slot['ke'];
                        @endphp
                        <tr>
                            <td class="th-jam">
                                <div><strong>JP {{ $sK }}</strong></div>
                                <div style="font-size: 0.68rem; color: #6b7280;">{{ $slot['mulai'] }} -
                                    {{ $slot['selesai'] }}</div>
                            </td>
                            @foreach ($hariList as $h)
                                @php
                                    $item = $matrix[$guru->ptk_id][$h][$sK] ?? null;
                                    $isOccupied = !empty($occupied[$guru->ptk_id][$h][$sK]);
                                @endphp
                                @if ($item && !$isOccupied)
                                    @php
                                        $maxAllowedRowspan = count($slots) - $sK + 1;
                                        $rowSpan = min(
                                            max(1, (int) $item->jam_ke_selesai - (int) $item->jam_ke_mulai + 1),
                                            $maxAllowedRowspan,
                                        );
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
                                    <td class="{{ $cellClass }}" rowspan="{{ $rowSpan }}">
                                        @if ($isUpacara)
                                            <span class="routine-title"><i class="fas fa-flag text-danger"></i>
                                                {{ $item->nama_mata_pelajaran }}</span>
                                            <span class="routine-desc">Dewan Guru &amp; Siswa</span>
                                            @if ($item->ruangan)
                                                <span class="ruangan-badge">[{{ $item->ruangan }}]</span>
                                            @endif
                                        @elseif ($isPembiasaan)
                                            <span class="routine-title"><i
                                                    class="fas fa-hands-praying text-success"></i>
                                                {{ $item->nama_mata_pelajaran }}</span>
                                            <span class="routine-desc">Wali Kelas &amp; Guru</span>
                                        @elseif ($isIstirahat)
                                            <span class="routine-title"><i class="fas fa-mug-hot text-warning"></i>
                                                {{ $item->nama_mata_pelajaran }}</span>
                                            <span class="routine-desc">Jeda Istirahat</span>
                                        @else
                                            <span class="rombel-name">{{ $item->nama_rombel }}</span>
                                            <span class="mapel-name">{{ $item->nama_mata_pelajaran }}</span>
                                            @if ($item->ruangan)
                                                <span class="ruangan-badge">[{{ $item->ruangan }}]</span>
                                            @endif
                                        @endif
                                    </td>
                                @elseif (!$isOccupied)
                                    <td style="color: #9ca3af; font-size: 0.7rem;">-</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Tanda Tangan: Kepala Sekolah di Kiri, Guru Pengampu di Kanan -->
            <div class="signature-container">
                <div class="sig-box">
                    <p>Mengetahui,</p>
                    <p style="font-weight: 600;">Kepala Sekolah,</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">
                        {{ $kepalaSekolah?->nama ?? '......................................................' }}</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP.
                        {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</p>
                </div>

                <div class="sig-box">
                    <p>{{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }},
                        {{ now()->translatedFormat('d F Y') }}</p>
                    <p style="font-weight: 600;">Guru Pengampu,</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">{{ $guru->nama }}</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. {{ $guru->nip ?: ($guru->nuptk ?: '-') }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="guru-page" style="text-align: center; padding: 50px;">
            <p style="color: #9ca3af;">Tidak ada data guru yang ditemukan.</p>
        </div>
    @endforelse

</body>

</html>
