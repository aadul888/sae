<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Induk KBM - {{ $sekolah->nama ?? 'Sekolah' }}</title>
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

        .print-page {
            background: #fff;
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .kop-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-right: 18px;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        .kop-text h2 {
            font-size: 1.3rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .kop-text h3 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .kop-text p {
            font-size: 0.76rem;
            color: #4b5563;
            line-height: 1.3;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-title h1 {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: underline;
        }
        .doc-title p {
            font-size: 0.8rem;
            color: #4b5563;
            margin-top: 2px;
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
        <!-- Kop Surat -->
        <div class="kop-surat">
            @if (!empty($sekolah->logo))
                <img src="{{ asset($sekolah->logo) }}" alt="Logo Sekolah" class="kop-logo">
            @else
                <div class="kop-logo" style="display: flex; align-items: center; justify-content: center; background: #e5e7eb; border-radius: 8px;">
                    <i class="fas fa-school" style="font-size: 2rem; color: #4b5563;"></i>
                </div>
            @endif
            <div class="kop-text">
                <h2>{{ $sekolah->nama ?? 'SEKOLAH MENENGAH KEJURUAN / ATAS' }}</h2>
                <h3>NPSN: {{ $sekolah->npsn ?? '-' }} | STATUS: TERAKREDITASI</h3>
                <p>{{ $sekolah->alamat_jalan ?? '' }} {{ $sekolah->desa_kelurahan ? 'Desa/Kel. ' . $sekolah->desa_kelurahan : '' }}, Kec. {{ $sekolah->kecamatan ?? '' }}, {{ $sekolah->kabupaten_kota ?? '' }} - {{ $sekolah->provinsi ?? '' }}</p>
                <p>Telepon: {{ $sekolah->nomor_telepon ?? '-' }} | Email: {{ $sekolah->email ?? '-' }} | Website: {{ $sekolah->website ?? '-' }}</p>
            </div>
        </div>

        <div class="doc-title">
            <h1>JADWAL PELAJARAN INDUK SEKOLAH</h1>
            <p>Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }} &bull; Berlaku untuk Seluruh Rombongan Belajar</p>
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
                                        @endphp
                                        <td class="cell-kbm" colspan="{{ $span }}">
                                            <span class="mapel-name">{{ $item->nama_mata_pelajaran }}</span>
                                            <span class="guru-name">{{ $item->nama_guru ?? 'Guru' }}</span>
                                            @if ($item->ruangan)
                                                <span style="font-size: 0.6rem; color: #059669; font-weight: 600;">[{{ $item->ruangan }}]</span>
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
                <p style="font-weight: 600;">Waka Kurikulum</p>
                <div class="sig-space"></div>
                <p class="sig-name">___________________________</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP. -</p>
            </div>

            <div class="sig-box">
                <p>{{ $sekolah->kabupaten_kota ?? 'Tempat' }}, {{ date('d F Y') }}</p>
                <p style="font-weight: 600;">Kepala Sekolah,</p>
                <div class="sig-space"></div>
                <p class="sig-name">___________________________</p>
                <p style="font-size: 0.72rem; color: #4b5563;">NIP. -</p>
            </div>
        </div>
    </div>

</body>
</html>
