<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar Guru - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #111827;
        }

        body {
            background-color: #f3f4f6;
            padding: 24px;
        }

        .guru-page {
            background: #fff;
            max-width: 1200px;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            page-break-after: always;
            position: relative;
            overflow: hidden;
        }
        .guru-page:last-child {
            page-break-after: avoid;
        }

        /* Watermark Logo SAE */
        .watermark-sae {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 440px;
            height: 440px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }
        .watermark-sae img {
            width: 100%;
            height: auto;
            filter: grayscale(100%);
        }

        .doc-header {
            text-align: center;
            margin-bottom: 18px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .doc-header .school-name {
            font-size: 1.2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #111827;
        }
        .doc-header .doc-title-main {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1e3a8a;
            margin: 3px 0 2px 0;
        }
        .doc-header .doc-subtitle {
            font-size: 0.82rem;
            color: #4b5563;
        }

        /* Bio Guru Box */
        .bio-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.82rem;
            position: relative;
            z-index: 1;
        }
        .bio-item {
            margin-bottom: 4px;
        }
        .bio-label {
            font-weight: 600;
            color: #6b7280;
            display: inline-block;
            width: 110px;
        }

        /* Table Matriks Jam x Hari */
        .table-guru {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 24px;
            position: relative;
            z-index: 1;
        }
        .table-guru th, .table-guru td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            vertical-align: middle;
            text-align: center;
        }
        .table-guru th {
            background-color: #f3f4f6;
            font-weight: 700;
        }
        .th-jam {
            background-color: #f9fafb;
            font-weight: 700;
            width: 110px;
        }
        .cell-kbm {
            background-color: #eff6ff;
            line-height: 1.25;
            padding: 6px 4px;
        }
        .cell-upacara {
            background-color: #fef2f2 !important;
            border-left: 3px solid #ef4444 !important;
        }
        .cell-pembiasaan {
            background-color: #f0fdf4 !important;
            border-left: 3px solid #10b981 !important;
        }
        .cell-istirahat {
            background-color: #fffbeb !important;
            border-left: 3px solid #f59e0b !important;
        }
        .routine-title {
            font-weight: 700;
            display: block;
            font-size: 0.76rem;
        }
        .cell-upacara .routine-title { color: #b91c1c; }
        .cell-pembiasaan .routine-title { color: #047857; }
        .cell-istirahat .routine-title { color: #b45309; }
        .routine-desc {
            font-size: 0.67rem;
            color: #4b5563;
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }
        .rombel-name {
            font-weight: 700;
            color: #1e3a8a;
            display: block;
            font-size: 0.78rem;
        }
        .mapel-name {
            font-size: 0.70rem;
            color: #374151;
            display: block;
            margin-top: 2px;
            font-weight: 500;
        }
        .ruangan-badge {
            font-size: 0.65rem;
            color: #059669;
            font-weight: 600;
            display: inline-block;
            margin-top: 1px;
        }

        /* Signatures */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
            position: relative;
            z-index: 1;
        }
        .sig-box {
            text-align: center;
            width: 240px;
        }
        .sig-space {
            height: 55px;
        }
        .sig-name {
            font-weight: 700;
            text-decoration: underline;
        }

        /* Floating bar */
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            right: 24px;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 16px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #4b5563; color: #fff; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .guru-page {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                margin-bottom: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            @page {
                size: landscape;
                margin: 10mm 12mm;
            }
        }
    </style>
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
                <div class="doc-subtitle">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }} &bull; Semester Genap</div>
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
                        <th class="th-jam">Jam / Waktu</th>
                        @foreach ($hariList as $h)
                            <th>{{ strtoupper($h) }}</th>
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
                                <div style="font-size: 0.68rem; color: #6b7280;">{{ $slot['mulai'] }} - {{ $slot['selesai'] }}</div>
                            </td>
                            @foreach ($hariList as $h)
                                @php
                                    $item = $matrix[$guru->ptk_id][$h][$sK] ?? null;
                                    $isOccupied = !empty($occupied[$guru->ptk_id][$h][$sK]);
                                @endphp
                                @if ($item)
                                    @php
                                        $rowSpan = $item->jam_ke_selesai - $item->jam_ke_mulai + 1;
                                        $isUpacara = $item->mata_pelajaran_id === 'UPACARA';
                                        $isPembiasaan = $item->mata_pelajaran_id === 'PEMBIASAAN';
                                        $isIstirahat = $item->mata_pelajaran_id === 'ISTIRAHAT';

                                        $cellClass = 'cell-kbm';
                                        if ($isUpacara) $cellClass .= ' cell-upacara';
                                        elseif ($isPembiasaan) $cellClass .= ' cell-pembiasaan';
                                        elseif ($isIstirahat) $cellClass .= ' cell-istirahat';
                                    @endphp
                                    <td class="{{ $cellClass }}" rowspan="{{ $rowSpan }}">
                                        @if ($isUpacara)
                                            <span class="routine-title"><i class="fas fa-flag text-danger"></i> {{ $item->nama_mata_pelajaran }}</span>
                                            <span class="routine-desc">Dewan Guru &amp; Siswa</span>
                                            @if ($item->ruangan)
                                                <span class="ruangan-badge">[{{ $item->ruangan }}]</span>
                                            @endif
                                        @elseif ($isPembiasaan)
                                            <span class="routine-title"><i class="fas fa-hands-praying text-success"></i> {{ $item->nama_mata_pelajaran }}</span>
                                            <span class="routine-desc">Wali Kelas &amp; Guru</span>
                                        @elseif ($isIstirahat)
                                            <span class="routine-title"><i class="fas fa-mug-hot text-warning"></i> {{ $item->nama_mata_pelajaran }}</span>
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
                    <p class="sig-name">{{ $kepalaSekolah?->nama ?? '......................................................' }}</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</p>
                </div>

                <div class="sig-box">
                    <p>{{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }}, {{ now()->translatedFormat('d F Y') }}</p>
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
