<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip e-Izin Siswa - {{ $tiket->nomor_tiket }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        /* Desain Slip Khusus: Rapi, Ringkas, Tanpa Kop Surat */
        .slip-wrapper {
            max-width: 680px;
            margin: 20px auto;
            background: #ffffff;
            border: 2px solid #0f172a;
            border-radius: var(--radius-md, 8px);
            padding: 20px 24px;
            color: #0f172a;
            font-size: 10pt;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .slip-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .slip-brand {
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #475569;
        }

        .slip-title {
            font-size: 13pt;
            font-weight: 900;
            letter-spacing: -0.2px;
            margin: 2px 0 0 0;
            color: #0f172a;
        }

        .slip-ticket-box {
            text-align: right;
        }

        .slip-ticket-no {
            font-family: monospace;
            font-weight: 800;
            font-size: 11pt;
            background: #0f172a;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: var(--radius-xs, 4px);
            display: inline-block;
        }

        .slip-status-badge {
            margin-top: 4px;
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
            padding: 2px 8px;
            border-radius: var(--radius-xs, 4px);
            background: #e2e8f0;
            color: #334155;
        }

        .slip-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
            margin-bottom: 14px;
        }

        .slip-codes {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: var(--radius-sm, 6px);
            background: #f8fafc;
            min-width: 140px;
        }

        .slip-codes svg {
            display: block;
            max-width: 100%;
            height: auto;
        }

        .table-data {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }

        .table-data td {
            padding: 4px 6px;
            vertical-align: top;
        }

        .table-data .lbl {
            width: 115px;
            font-weight: 600;
            color: #475569;
        }

        .table-data .colon {
            width: 10px;
            text-align: center;
            color: #64748b;
        }

        .table-data .val {
            font-weight: 700;
            color: #0f172a;
        }

        .alasan-box {
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            padding: 8px 12px;
            margin: 10px 0 14px 0;
            border-radius: 0 4px 4px 0;
            font-size: 9pt;
        }

        .alasan-label {
            font-size: 7.5pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }

        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 14px;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }

        .sig-col {
            text-align: center;
            font-size: 8.5pt;
        }

        .sig-role {
            font-weight: 700;
            color: #475569;
            margin-bottom: 38px;
        }

        .sig-name {
            font-weight: 800;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sig-status {
            font-size: 7pt;
            color: #16a34a;
            font-weight: 700;
        }

        .slip-footer {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #64748b;
            border-top: 1px dashed #cbd5e1;
            padding-top: 6px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .slip-wrapper {
                box-shadow: none !important;
                margin: 0 !important;
                max-width: 100% !important;
                border: 1.5px solid #000000 !important;
                page-break-inside: avoid;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="no-print-bar">
        <button type="button" class="btn-action btn-back"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="button" class="btn-action btn-print"
            onclick="window.SAE_Print ? window.SAE_Print() : window.print()">
            <i class="fas fa-print"></i> Cetak Slip (Print / PDF)
        </button>
    </div>

    <div class="slip-wrapper">
        <!-- Header Ringkas Slip (Tanpa Kop Surat Instansi) -->
        <div class="slip-top">
            <div>
                <div class="slip-brand">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI' }}</div>
                <h1 class="slip-title">SLIP e-IZIN PESERTA DIDIK</h1>
            </div>
            <div class="slip-ticket-box">
                <div class="slip-ticket-no">{{ $tiket->nomor_tiket }}</div>
                <div>
                    <span class="slip-status-badge">
                        {{ $tiket->jenis_izin === 'pulang_cepat' ? 'IZIN PULANG CEPAT' : 'IZIN KELUAR-MASUK' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Siswa & Barcode / QR -->
        <div class="slip-grid">
            <table class="table-data">
                <tr>
                    <td class="lbl">Nama Siswa</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $tiket->nama_siswa }}</td>
                </tr>
                <tr>
                    <td class="lbl">Kelas / Rombel</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $tiket->nama_rombel ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">NISN</td>
                    <td class="colon">:</td>
                    <td class="val">{{ $tiket->nisn ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal Izin</td>
                    <td class="colon">:</td>
                    <td class="val">{{ \Carbon\Carbon::parse($tiket->tanggal)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="lbl">Jam Izin Keluar</td>
                    <td class="colon">:</td>
                    <td class="val">
                        {{ substr($tiket->jam_izin_keluar, 0, 5) }} WIB
                        @if ($tiket->jenis_izin === 'keluar_sebentar' && $tiket->jam_rencana_kembali)
                            <span style="font-weight: 400; color: #475569;">(Rencana Kembali: {{ substr($tiket->jam_rencana_kembali, 0, 5) }} WIB)</span>
                        @endif
                    </td>
                </tr>
                @if ($tiket->satpam_checkout_at || $tiket->satpam_checkin_at || $tiket->jam_kembali_aktual)
                    <tr>
                        <td class="lbl">Aktual Gerbang</td>
                        <td class="colon">:</td>
                        <td class="val" style="color: #0369a1;">
                            @if ($tiket->satpam_checkout_at)
                                Keluar: {{ \Carbon\Carbon::parse($tiket->satpam_checkout_at)->format('H:i') }} WIB
                            @endif
                            @if ($tiket->jam_kembali_aktual || $tiket->satpam_checkin_at)
                                &bull; Masuk: {{ $tiket->jam_kembali_aktual ? substr($tiket->jam_kembali_aktual, 0, 5) : \Carbon\Carbon::parse($tiket->satpam_checkin_at)->format('H:i') }} WIB
                            @endif
                        </td>
                    </tr>
                @endif
            </table>

            <div class="slip-codes">
                @if (!empty($qrSvg))
                    <div style="width: 100px; height: 100px;">
                        {!! $qrSvg !!}
                    </div>
                @endif
                @if (!empty($barcodeSvg))
                    <div style="width: 130px; height: 35px; overflow: hidden;">
                        {!! $barcodeSvg !!}
                    </div>
                @endif
                <div style="font-family: monospace; font-size: 7.5pt; font-weight: 700; color: #64748b;">
                    {{ $tiket->nomor_tiket }}
                </div>
            </div>
        </div>

        <!-- Alasan & Keperluan -->
        <div class="alasan-box">
            <div class="alasan-label">Alasan &amp; Keperluan Izin:</div>
            <div style="font-style: italic; font-weight: 600; color: #1e293b;">
                &ldquo;{{ $tiket->alasan }}&rdquo;
            </div>
        </div>

        <!-- Validasi & Pengesahan 4 Pihak -->
        <div class="signatures-grid">
            <div class="sig-col">
                <div class="sig-role">Peserta Didik</div>
                <div class="sig-name">{{ $tiket->nama_siswa }}</div>
                <div class="sig-status">Pemohon Izin</div>
            </div>

            <div class="sig-col">
                <div class="sig-role">Wali Kelas</div>
                <div class="sig-name">{{ $tiket->nama_wali ?: 'Wali Kelas' }}</div>
                <div class="sig-status">
                    <i class="fas fa-check-circle"></i> Disetujui
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-role">Guru Piket</div>
                <div class="sig-name">{{ $tiket->nama_piket ?: ($tiket->created_by ?: 'Guru Piket') }}</div>
                <div class="sig-status">
                    <i class="fas fa-check-circle"></i> Disetujui
                </div>
            </div>

            <div class="sig-col">
                <div class="sig-role">Security Gerbang</div>
                <div class="sig-name">{{ $tiket->satpam_checkout_by ?: '( Security Gerbang )' }}</div>
                <div class="sig-status" style="color: {{ $tiket->satpam_checkout_at ? '#16a34a' : '#64748b' }};">
                    @if ($tiket->satpam_checkout_at)
                        <i class="fas fa-check-circle"></i> Terverifikasi
                    @else
                        Menunggu Tap
                    @endif
                </div>
            </div>
        </div>

        <div class="slip-footer">
            <div>*Tunjukkan slip fisik atau tiket digital pada smartphone ke Security Pos Gerbang saat tap/scan.</div>
            <div>Waktu Cetak: {{ now()->translatedFormat('d/m/Y H:i') }} WIB</div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('css/cetak.js')) ? filemtime(public_path('css/cetak.js')) : time() }}">
    </script>
</body>

</html>
