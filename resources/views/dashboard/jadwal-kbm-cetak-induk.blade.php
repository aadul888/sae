<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Induk KBM - {{ $sekolah->nama ?? 'Sekolah' }}</title>
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

        .print-page {
            background: #fff;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        /* Watermark Logo SAE */
        .watermark-sae {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 460px;
            height: 460px;
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
            margin-bottom: 20px;
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .doc-header .school-name {
            font-size: 1.25rem;
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
            margin: 4px 0 2px 0;
        }
        .doc-header .doc-subtitle {
            font-size: 0.82rem;
            color: #4b5563;
        }

        /* Tabel Matriks */
        .table-induk {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.72rem;
            margin-bottom: 20px;
        }
        .table-induk th, .table-induk td {
            border: 1px solid #9ca3af;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
        }
        .table-induk th {
            background-color: #e5e7eb;
            font-weight: 700;
        }
        .th-rombel {
            background-color: #f3f4f6;
            font-weight: 700;
            text-align: left !important;
            padding-left: 8px !important;
            white-space: nowrap;
        }
        .cell-kbm {
            background-color: #eff6ff;
            line-height: 1.2;
            padding: 4px 2px;
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
            font-size: 0.7rem;
        }
        .cell-upacara .routine-title { color: #b91c1c; }
        .cell-pembiasaan .routine-title { color: #047857; }
        .cell-istirahat .routine-title { color: #b45309; }
        .mapel-name {
            font-weight: 700;
            color: #1e3a8a;
            display: block;
            font-size: 0.7rem;
        }
        .guru-name {
            font-size: 0.65rem;
            color: #4b5563;
            display: block;
        }
        .cell-empty {
            color: #9ca3af;
            font-size: 0.68rem;
        }

        /* Tanda Tangan */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            font-size: 0.82rem;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            width: 260px;
        }
        .sig-space {
            height: 60px;
        }
        .sig-name {
            font-weight: 700;
            text-decoration: underline;
        }

        /* Tombol Navigasi / Floating */
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
            transition: transform 0.2s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
        .btn-print {
            background: #2563eb;
            color: #fff;
        }
        .btn-back {
            background: #4b5563;
            color: #fff;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .print-page {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
            @page {
                size: landscape;
                margin: 8mm 10mm;
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
            <div class="doc-subtitle">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }} &bull; Berlaku untuk Seluruh Rombongan Belajar</div>
        </div>

        <!-- Matriks Jadwal per Hari -->
        @foreach ($hariList as $hari)
            <div style="margin-bottom: 24px; page-break-inside: avoid;">
                <div style="background: #1e3a8a; color: #fff; padding: 5px 12px; font-weight: 800; font-size: 0.8rem; border-radius: 4px 4px 0 0; display: flex; justify-content: space-between;">
                    <span><i class="fas fa-calendar-day me-1"></i> HARI {{ strtoupper($hari) }}</span>
                    <span>Total JP: {{ count($slots) }} JP</span>
                </div>
                <table class="table-induk">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Kelas / Rombel</th>
                            @foreach ($slots as $slot)
                                <th>
                                    <div>JP {{ $slot['ke'] }}</div>
                                    <div style="font-size: 0.62rem; font-weight: 500; color: #4b5563;">{{ $slot['mulai'] }}-{{ $slot['selesai'] }}</div>
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
                                            if ($isUpacara) $cellClass .= ' cell-upacara';
                                            elseif ($isPembiasaan) $cellClass .= ' cell-pembiasaan';
                                            elseif ($isIstirahat) $cellClass .= ' cell-istirahat';
                                        @endphp
                                        <td class="{{ $cellClass }}" colspan="{{ $span }}">
                                            @if ($isUpacara)
                                                <span class="routine-title"><i class="fas fa-flag text-danger"></i> {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Dewan Guru &amp; Siswa</span>
                                                @if ($item->ruangan)
                                                    <span style="font-size: 0.6rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
                                                @endif
                                            @elseif ($isPembiasaan)
                                                <span class="routine-title"><i class="fas fa-hands-praying text-success"></i> {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Wali Kelas &amp; Guru</span>
                                            @elseif ($isIstirahat)
                                                <span class="routine-title"><i class="fas fa-mug-hot text-warning"></i> {{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">Jeda Istirahat</span>
                                            @else
                                                <span class="mapel-name">{{ $item->nama_mata_pelajaran }}</span>
                                                <span class="guru-name">{{ $item->nama_guru ?? 'Guru' }}</span>
                                                @if ($item->ruangan)
                                                    <span style="font-size: 0.6rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
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
                                <td colspan="{{ count($slots) + 1 }}" style="padding: 12px; color: #9ca3af;">Tidak ada data rombongan belajar.</td>
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
                <p class="sig-name">{{ $kepalaSekolah?->nama ?? '......................................................' }}</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP. {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</p>
            </div>

            <div class="sig-box">
                <p>{{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }}, {{ now()->translatedFormat('d F Y') }}</p>
                <p style="font-weight: 600;">Waka Kurikulum,</p>
                <div class="sig-space"></div>
                <p class="sig-name">{{ $wakaKurikulum?->nama ?? '......................................................' }}</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP. {{ $wakaKurikulum?->nip ?: ($wakaKurikulum?->nuptk ?: '—') }}</p>
            </div>
        </div>
    </div>

</body>
</html>
