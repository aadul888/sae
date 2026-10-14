<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengambilan Kartu Pelajar — {{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}
    </title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">

    <style>
        .meta-grid-box {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 16px 0 20px 0;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .meta-col-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 3px;
        }

        .meta-col-val {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
        }

        .table-distribusi {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
            margin-bottom: 24px;
        }

        .table-distribusi th {
            background: #f1f5f9;
            border: 1px solid #94a3b8;
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            color: #1e293b;
        }

        .table-distribusi td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            color: #334155;
            vertical-align: middle;
        }

        .ttd-box {
            width: 130px;
            height: 38px;
            position: relative;
        }

        .ttd-num {
            font-size: 0.7rem;
            font-weight: 700;
            color: #94a3b8;
            position: absolute;
            top: 2px;
            left: 3px;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
            page-break-inside: avoid;
        }

        .sig-block {
            text-align: center;
            font-size: 0.85rem;
        }

        .sig-space {
            height: 65px;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }

            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .paper {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
                padding: 12mm 15mm !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print-bar">
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'siswa']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <!-- Filter Form Tanggal Cetak & Rombel -->
        <form method="GET" action="{{ route('dashboard.kartu-pelajar.cetak-pengambilan') }}"
            style="display: inline-flex; align-items: center; gap: 8px; margin: 0 12px; flex-wrap: wrap;">
            <label style="font-size: 0.78rem; color: #cbd5e1; margin: 0;">Tanggal Cetak:</label>
            <select name="tanggal_cetak" class="select-cetak" style="padding: 4px 8px; font-size: 0.78rem;">
                @if ($tanggalCetakList && $tanggalCetakList->count() > 0)
                    @foreach ($tanggalCetakList as $tc)
                        <option value="{{ $tc->tgl }}" {{ $tanggalCetak === $tc->tgl ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($tc->tgl)->translatedFormat('d M Y') }} ({{ $tc->total }}
                            kartu)
                        </option>
                    @endforeach
                @else
                    <option value="{{ date('Y-m-d') }}">{{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}
                    </option>
                @endif
            </select>

            <label style="font-size: 0.78rem; color: #cbd5e1; margin: 0;">Rombel:</label>
            <select name="rombel" class="select-cetak" style="padding: 4px 8px; font-size: 0.78rem;">
                <option value="">Semua Rombel</option>
                @foreach ($rombelList as $rName)
                    <option value="{{ $rName }}" {{ $rombel === $rName ? 'selected' : '' }}>
                        {{ $rName }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn-action" style="padding: 5px 12px; font-size: 0.78rem;">
                <i class="fas fa-filter"></i> Tampilkan
            </button>
            @if ($rombel)
                <a href="{{ route('dashboard.kartu-pelajar.cetak-pengambilan', ['tanggal_cetak' => $tanggalCetak]) }}"
                    class="btn-action" style="padding: 5px 10px; font-size: 0.78rem;" title="Reset Filter Rombel">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </form>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper"
        style="max-width: 900px; margin: 20px auto; background: #ffffff; padding: 25px 35px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
        <!-- Kop Dokumen Resmi Sekolah -->
        <div
            style="display: flex; align-items: center; gap: 16px; border-bottom: 3px double #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
            @if (!empty($sekolahInfo['logo_url']))
                <img src="{{ $sekolahInfo['logo_url'] }}" alt="Logo Sekolah"
                    style="width: 70px; height: 70px; object-fit: contain;">
            @endif
            <div style="flex: 1; text-align: center;">
                <div style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">
                    PEMERINTAH DAERAH PROVINSI JAWA BARAT</div>
                <div style="font-size: 1.15rem; font-weight: 800; text-transform: uppercase; margin: 2px 0;">
                    {{ $sekolahInfo['nama'] ?? 'SMK NEGERI 1 PAGELARAN' }}</div>
                <div style="font-size: 0.78rem; color: #334155;">{{ $sekolahInfo['alamat'] ?? '-' }}</div>
                <div style="font-size: 0.75rem; color: #475569;">NPSN: {{ $sekolahInfo['npsn'] ?? '-' }} &bull;
                    Website: {{ $sekolahInfo['website'] ?? '-' }}</div>
            </div>
        </div>

        <div style="text-align: center; margin-bottom: 14px;">
            <h3
                style="font-size: 1.1rem; font-weight: 800; text-transform: uppercase; margin: 0 0 4px 0; letter-spacing: 0.5px;">
                DAFTAR DISTRIBUSI &amp; SERAH TERIMA KARTU PELAJAR FISIK (RFID)
            </h3>
            <div style="font-size: 0.82rem; color: #475569;">
                Daftar Peserta Didik Aktif yang Telah Selesai Dicetak &amp; Siap Diambil
            </div>
        </div>

        <!-- Meta Summary Box -->
        <div class="meta-grid-box">
            <div>
                <div class="meta-col-title">Tanggal Cetak Selesai</div>
                <div class="meta-col-val" style="color: #2563eb;">
                    {{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}
                </div>
            </div>
            <div>
                <div class="meta-col-title">Rombongan Belajar / Kelas</div>
                <div class="meta-col-val" style="color: #0f172a;">
                    {{ $rombel ?: 'Semua Rombel Terpilih' }}
                </div>
            </div>
            <div>
                <div class="meta-col-title">Total Kartu Siap Diambil</div>
                <div class="meta-col-val" style="color: #10b981;">
                    {{ number_format($students->count(), 0, ',', '.') }} Kartu Fisik
                </div>
            </div>
        </div>

        <table class="table-distribusi">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">No</th>
                    <th style="width: 100px;">NISN / NIPD</th>
                    <th>Nama Lengkap Peserta Didik</th>
                    <th style="width: 90px;">Kelas</th>
                    <th style="width: 80px; text-align: center;">Jam Cetak</th>
                    <th style="width: 80px; text-align: center;">Status Bayar</th>
                    <th style="width: 130px; text-align: center;">Tanda Tangan Pengambil</th>
                    <th style="width: 90px; text-align: center;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $idx => $s)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="font-family: monospace; font-size: 0.78rem;">
                            {{ $s->nisn ?: '-' }}<br>
                            <span style="color: #64748b;">{{ $s->nipd ?: '-' }}</span>
                        </td>
                        <td>
                            <strong>{{ $s->nama }}</strong>
                        </td>
                        <td>
                            <span
                                style="display: inline-block; padding: 2px 6px; background: #f1f5f9; border-radius: 4px; font-weight: 700; font-size: 0.75rem;">
                                {{ $s->nama_rombel ?: '-' }}
                            </span>
                        </td>
                        <td style="text-align: center; font-size: 0.78rem; color: #475569;">
                            {{ $s->tanggal_cetak ? \Carbon\Carbon::parse($s->tanggal_cetak)->format('H:i') : '-' }} WIB
                        </td>
                        <td style="text-align: center;">
                            @if ($s->status_pembayaran === 'lunas')
                                <span style="color: #059669; font-weight: 700; font-size: 0.74rem;">LUNAS</span>
                            @else
                                <span style="color: #d97706; font-weight: 700; font-size: 0.74rem;">BELUM</span>
                            @endif
                        </td>
                        <td>
                            <div class="ttd-box">
                                <span class="ttd-num">{{ $idx + 1 }}.</span>
                            </div>
                        </td>
                        <td style="text-align: center; font-size: 0.74rem; color: #64748b;">
                            {{ $s->catatan ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">
                            Tidak ada data kartu yang dicetak pada tanggal
                            <strong>{{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}</strong>{{ $rombel ? ' untuk rombel ' . $rombel : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Blok Tanda Tangan Serah Terima -->
        <div class="signature-grid">
            <div class="sig-block">
                <div>Diterima oleh,</div>
                <div style="font-weight: 700;">Wali Kelas / Pengambil Kartu</div>
                <div class="sig-space"></div>
                <div
                    style="font-weight: 800; border-bottom: 1px dotted #0f172a; display: inline-block; min-width: 180px;">
                    &nbsp;</div>
                <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">NIP. -</div>
            </div>

            <div class="sig-block">
                <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 700;">Petugas Cetak / Pengelola Kartu Pelajar</div>
                <div class="sig-space"></div>
                <div
                    style="font-weight: 800; border-bottom: 1px solid #0f172a; display: inline-block; min-width: 180px;">
                    {{ session('user')['nama'] ?? 'ADMINISTRATOR SAE' }}
                </div>
                <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">Petugas Pengelola Kartu RFID</div>
            </div>
        </div>
    </div>
</body>

</html>
