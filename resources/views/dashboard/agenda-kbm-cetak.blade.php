<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Agenda KBM — {{ $guru->nama ?? 'Guru' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #f1f5f9;
            padding: 24px;
            font-size: 11pt;
            line-height: 1.35;
        }
        .paper {
            background: #fff;
            max-width: 297mm;
            min-height: 210mm;
            margin: 0 auto;
            padding: 15mm 15mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        /* Watermark Logo SAE */
        .watermark-sae {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 420px;
            height: 420px;
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
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
            position: relative;
            z-index: 1;
        }
        .doc-header .school-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
        }
        .doc-header .doc-title-main {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e3a8a;
            margin: 4px 0 2px 0;
        }
        .doc-header .doc-subtitle {
            font-size: 10pt;
            color: #4b5563;
        }
        .doc-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 16px;
            font-size: 10pt;
        }
        .meta-row {
            display: flex;
            gap: 6px;
        }
        .meta-label {
            width: 140px;
        }
        table.kbm-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 20px;
        }
        table.kbm-table th, table.kbm-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.kbm-table th {
            background: #e2e8f0;
            text-align: center;
            font-weight: bold;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 240px;
            text-align: center;
            font-size: 10pt;
        }
        .sig-space {
            height: 65px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .paper {
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 297mm; margin: 0 auto 16px auto; display: flex; justify-content: space-between; align-items: center;">
        <a href="{{ route('dashboard.agenda-kbm.index') }}" style="color: #4f46e5; text-decoration: none; font-size: 10pt; font-family: sans-serif; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.print()" style="padding: 8px 18px; background: #4f46e5; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 10pt; font-family: sans-serif; font-weight: bold; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- Header Dokumen Bersih (Tanpa Kop Surat) -->
        <div class="doc-header">
            <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            <div class="doc-title-main">JURNAL &amp; AGENDA KEGIATAN BELAJAR MENGAJAR (KBM)</div>
            <div class="doc-subtitle">Tahun Ajaran: {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}</div>
        </div>

        <!-- Meta Info Guru & Rombel -->
        <div class="doc-meta">
            <div>
                <div class="meta-row">
                    <span class="meta-label">Nama Pendidik</span>
                    <span>: <strong>{{ $guru->nama ?? '-' }}</strong></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">NIP / NUPTK</span>
                    <span>: {{ $guru->nip ?? ($guru->nuptk ?? '-') }}</span>
                </div>
            </div>
            <div>
                <div class="meta-row">
                    <span class="meta-label">Kelas / Rombel</span>
                    <span>: {{ $rombel->nama ?? 'Seluruh Kelas Binaan' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Tanggal Cetak</span>
                    <span>: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Tabel Agenda KBM -->
        <table class="kbm-table">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th style="width: 75px;">Hari, Tanggal</th>
                    <th style="width: 45px;">Jam Ke</th>
                    <th style="width: 70px;">Kelas</th>
                    <th style="width: 110px;">Mata Pelajaran</th>
                    <th>Materi Pokok &amp; Indikator Pembelajaran</th>
                    <th>Kegiatan KBM &amp; Penugasan</th>
                    <th style="width: 75px;">Keterlaksanaan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $item)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->hari }}</strong><br>
                            <span style="font-size: 9pt;">{{ $item->tanggal ? $item->tanggal->format('d/m/Y') : '-' }}</span>
                        </td>
                        <td style="text-align: center;">{{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}</td>
                        <td>{{ $item->nama_rombel }}</td>
                        <td>{{ $item->nama_mata_pelajaran }}</td>
                        <td>
                            <div style="font-weight: bold; margin-bottom: 2px;">(Pertemuan #{{ $item->pertemuan_ke }})</div>
                            {{ $item->materi_pokok }}
                        </td>
                        <td>
                            <div>{{ $item->uraian_kegiatan }}</div>
                            @if ($item->penugasan)
                                <div style="margin-top: 4px; font-size: 9pt; font-style: italic;">
                                    <strong>Tugas:</strong> {{ $item->penugasan }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <strong>{{ $item->status_kbm }}</strong>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">
                            Tidak ada catatan agenda KBM yang tercatat pada kriteria filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Tanda Tangan -->
        <div class="signatures">
            <div class="sig-box">
                <div>Mengetahui,</div>
                <div>Kepala Sekolah,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $kepalaSekolah?->nama ?? '...................................................' }}</div>
                <div>NIP. {{ $kepalaSekolah?->nip ?: ($kepalaSekolah?->nuptk ?: '—') }}</div>
            </div>
            <div class="sig-box">
                <div>{{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }}, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div>Guru Mata Pelajaran,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $guru->nama ?? 'Guru Pengampu' }}</div>
                <div>NIP. {{ $guru->nip ?? '-' }}</div>
            </div>
        </div>
    </div>
</body>
</html>
