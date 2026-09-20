<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip e-Izin - {{ $tiket->nomor_tiket }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            margin: 0;
            padding: 15px;
            font-size: 13px;
        }
        .header {
            text-align: center;
            border-bottom: 2px double #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 2px 0;
            font-size: 14px;
        }
        .header p {
            margin: 0;
            font-size: 11px;
            color: #444;
        }
        .title-box {
            text-align: center;
            margin-bottom: 15px;
        }
        .title-box h4 {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .ticket-no {
            font-family: 'Courier New', Courier, monospace;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 4px;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .content-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .content-table td.label {
            width: 140px;
            font-weight: bold;
        }
        .content-table td.sep {
            width: 10px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #333;
        }
        .signature-table {
            width: 100%;
            margin-top: 25px;
            text-align: center;
        }
        .signature-table td {
            width: 33.33%;
            vertical-align: top;
        }
        .sign-space {
            height: 50px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Cetak Slip
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; background: #6b7280; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-left: 6px;">
            Tutup
        </button>
    </div>

    <div class="header">
        <h2>{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI' }}</h2>
        <h3>SLIP RESMI e-IZIN KELUAR-MASUK PESERTA DIDIK</h3>
        <p>{{ $sekolah->alamat_jalan ?? 'Lingkungan Satuan Pendidikan' }} &bull; NPSN: {{ $sekolah->npsn ?? '-' }}</p>
    </div>

    <div class="title-box">
        <h4>SURAT IZIN MENINGGALKAN LINGKUNGAN SEKOLAH</h4>
        <div class="ticket-no">NO: {{ $tiket->nomor_tiket }}</div>
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Nama Peserta Didik</td>
            <td class="sep">:</td>
            <td><strong>{{ $tiket->nama_siswa }}</strong></td>
            <td class="label">Kelas / Rombel</td>
            <td class="sep">:</td>
            <td>{{ $tiket->nama_rombel ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NISN</td>
            <td class="sep">:</td>
            <td>{{ $tiket->nisn ?? '-' }}</td>
            <td class="label">Tanggal Izin</td>
            <td class="sep">:</td>
            <td>{{ \Carbon\Carbon::parse($tiket->tanggal)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Izin</td>
            <td class="sep">:</td>
            <td>
                <span class="badge">
                    {{ $tiket->jenis_izin === 'keluar_sebentar' ? 'KELUAR SEBENTAR (KEMBALI KE SEKOLAH)' : ($tiket->jenis_izin === 'pulang_cepat' ? 'PULANG CEPAT' : strtoupper($tiket->jenis_izin)) }}
                </span>
            </td>
            <td class="label">Waktu Keluar</td>
            <td class="sep">:</td>
            <td>
                Pukul <strong>{{ substr($tiket->jam_izin_keluar, 0, 5) }} WIB</strong>
                @if ($tiket->jam_rencana_kembali)
                    (Rencana Kembali: {{ substr($tiket->jam_rencana_kembali, 0, 5) }} WIB)
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Alasan / Keperluan</td>
            <td class="sep">:</td>
            <td colspan="4"><em>"{{ $tiket->alasan }}"</em></td>
        </tr>
    </table>

    <div style="font-size: 11px; font-style: italic; color: #333; margin-top: 5px; border-left: 3px solid #666; padding-left: 8px;">
        *Catatan: Slip izin ini wajib ditunjukkan kepada petugas Satpam di pos gerbang sekolah saat keluar dan kembali.
    </div>

    <table class="signature-table">
        <tr>
            <td>
                Peserta Didik Ybs,<br>
                <div class="sign-space"></div>
                <strong>{{ $tiket->nama_siswa }}</strong>
            </td>
            <td>
                Petugas Satpam Pos Gerbang,<br>
                <div class="sign-space"></div>
                <strong>( ..................................... )</strong>
            </td>
            <td>
                Guru Piket Sekolah,<br>
                <div class="sign-space"></div>
                <strong>{{ $tiket->nama_piket ?: ($tiket->created_by ?: 'Guru Piket') }}</strong>
            </td>
        </tr>
    </table>

    <script>
        window.addEventListener('load', function() {
            // Auto print on load if requested
        });
    </script>
</body>
</html>
