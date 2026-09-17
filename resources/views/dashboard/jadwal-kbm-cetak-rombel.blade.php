<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran Kelas - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .rombel-page {
            background: #fff;
            max-width: 1050px;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            page-break-after: always;
        }
        .rombel-page:last-child {
            page-break-after: avoid;
        }

        /* Kop Surat */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .kop-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 16px;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        .kop-text h2 {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .kop-text h3 {
            font-size: 0.92rem;
            font-weight: 700;
        }
        .kop-text p {
            font-size: 0.72rem;
            color: #4b5563;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-title h1 {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }

        /* Info Rombel Box */
        .info-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.82rem;
        }
        .info-item {
            margin-bottom: 4px;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            display: inline-block;
            width: 100px;
        }

        /* Table */
        .table-rombel {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.76rem;
            margin-bottom: 24px;
        }
        .table-rombel th, .table-rombel td {
            border: 1px solid #9ca3af;
            padding: 6px 8px;
            vertical-align: middle;
            text-align: center;
        }
        .table-rombel th {
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
        .mapel-name {
            font-weight: 700;
            color: #1e3a8a;
            display: block;
            font-size: 0.75rem;
        }
        .guru-name {
            font-size: 0.68rem;
            color: #4b5563;
            display: block;
            margin-top: 2px;
        }

        /* Signatures */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
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
            .rombel-page {
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
            <i class="fas fa-print"></i> Cetak Jadwal Kelas (Print / PDF)
        </button>
    </div>

    @forelse ($rombelList as $rombel)
        <div class="rombel-page">
            <div class="kop-surat">
                @if (!empty($sekolah->logo))
                    <img src="{{ asset($sekolah->logo) }}" alt="Logo" class="kop-logo">
                @else
                    <div class="kop-logo" style="display: flex; align-items: center; justify-content: center; background: #e5e7eb; border-radius: 8px;">
                        <i class="fas fa-school" style="font-size: 1.8rem; color: #4b5563;"></i>
                    </div>
                @endif
                <div class="kop-text">
                    <h2>{{ $sekolah->nama ?? 'SEKOLAH MENENGAH KEJURUAN / ATAS' }}</h2>
                    <h3>NPSN: {{ $sekolah->npsn ?? '-' }} &bull; STATUS: TERAKREDITASI</h3>
                    <p>{{ $sekolah->alamat_jalan ?? '' }}, {{ $sekolah->kabupaten_kota ?? '' }}</p>
                </div>
            </div>

            <div class="doc-title">
                <h1>JADWAL PELAJARAN KELAS {{ strtoupper($rombel->nama) }}</h1>
                <p style="font-size: 0.78rem; color: #6b7280; margin-top: 2px;">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }}</p>
            </div>

            <div class="info-box">
                <div>
                    <div class="info-item">
                        <span class="info-label">Kelas / Rombel:</span>
                        <strong style="color: #1e3a8a; font-size: 0.9rem;">{{ $rombel->nama }}</strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tingkat Kelas:</span>
                        <span>Kelas {{ $rombel->tingkat_pendidikan_id }}</span>
                    </div>
                </div>
                <div>
                    <div class="info-item">
                        <span class="info-label">Wali Kelas:</span>
                        <strong>{{ $rombel->nama_wali_kelas ?: 'Belum Ditugaskan' }}</strong>
                    </div>
                    <div class="info-item">
                        <span class="info-label">NIP Wali:</span>
                        <span>{{ $rombel->nip_wali_kelas ?: '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Matriks Jam x Hari -->
            <table class="table-rombel">
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
                                    $item = $matrix[$rombel->rombongan_belajar_id][$h][$sK] ?? null;
                                    $isOccupied = !empty($occupied[$rombel->rombongan_belajar_id][$h][$sK]);
                                @endphp
                                @if ($item)
                                    @php
                                        $rowSpan = $item->jam_ke_selesai - $item->jam_ke_mulai + 1;
                                    @endphp
                                    <td class="cell-kbm" rowspan="{{ $rowSpan }}">
                                        <span class="mapel-name">{{ $item->nama_mata_pelajaran }}</span>
                                        <span class="guru-name">{{ $item->nama_guru ?? 'Guru Pengampu' }}</span>
                                        @if ($item->ruangan)
                                            <span style="font-size: 0.65rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
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

            <div class="signature-container">
                <div class="sig-box">
                    <p>Mengetahui,</p>
                    <p style="font-weight: 600;">Wali Kelas {{ $rombel->nama }}</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">{{ $rombel->nama_wali_kelas ?: '___________________________' }}</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. {{ $rombel->nip_wali_kelas ?: '-' }}</p>
                </div>

                <div class="sig-box">
                    <p>{{ $sekolah->kabupaten_kota ?? 'Tempat' }}, {{ date('d F Y') }}</p>
                    <p style="font-weight: 600;">Kepala Sekolah,</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">___________________________</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. -</p>
                </div>
            </div>
        </div>
    @empty
        <div class="rombel-page" style="text-align: center; padding: 50px;">
            <p style="color: #9ca3af;">Tidak ada rombongan belajar yang ditemukan.</p>
        </div>
    @endforelse

</body>
</html>
