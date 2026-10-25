<!DOCTYPE html>
<html lang="id">
@php
    $isSingle = $isSingle ?? true;
    $orientasi = $isSingle ? 'portrait' : 'landscape';
    $surat = $surat ?? null;
    $items = $items ?? ($surat ? collect([$surat]) : collect());
    $sekolah = $sekolah ?? \App\Models\Sekolah::first();
    $sekolahMeta = $sekolahMeta ?? \App\Models\SekolahMeta::first();
    $kepsek = $kepsek ?? null;
    $kepalaTas = $kepalaTas ?? null;
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isSingle ? 'Tanda Terima Ekspedisi Surat' : 'Buku Ekspedisi Surat Keluar' }} —
        {{ $sekolah?->nama ?? 'Sekolah' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        .filter-form-bar {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 10px;
            border-radius: var(--radius-md, 8px);
            font-size: var(--text-sm, 0.8125rem);
        }

        .filter-form-bar input {
            height: 28px;
            padding: 0 8px;
            border-radius: var(--radius-sm, 6px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            background: #ffffff;
            color: #1e293b;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 600;
        }

        .table-ekspedisi {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--text-sm, 0.8125rem);
            margin-top: 14px;
            margin-bottom: 24px;
        }

        .table-ekspedisi th {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #64748b;
            padding: 7px 8px;
            text-align: center;
            font-weight: 700;
            font-size: var(--text-sm, 0.8125rem);
            text-transform: uppercase;
        }

        .table-ekspedisi td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: top;
            color: #1e293b;
        }

        .table-ekspedisi tr:nth-child(even) td {
            background: #f8fafc;
        }

        .box-receipt {
            border: 1.5px solid #0f172a;
            border-radius: var(--radius-md, 8px);
            padding: 16px;
            margin-top: 16px;
            background: #ffffff;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 14px;
        }

        .receipt-panel {
            border: 1px solid #94a3b8;
            border-radius: var(--radius-sm, 6px);
            padding: 12px;
            background: #f8fafc;
            min-height: 170px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .receipt-title {
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 6px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        @media print {
            .table-ekspedisi th {
                background: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-ekspedisi tr:nth-child(even) td {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .receipt-panel {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    {{-- Floating Action Toolbar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.persuratan.keluar.index', ['tab' => 'ekspedisi']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        @if (!$isSingle)
            <form method="GET" action="" class="filter-form-bar">
                <span>Dari:</span>
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai', '') }}">
                <span>S/d:</span>
                <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai', '') }}">
                <button type="submit" class="btn-action"
                    style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        @endif

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    {{-- Halaman Cetak --}}
    <div class="cetak-page {{ $orientasi }}" style="min-height: 100%;">
        {{-- Watermark Logo SAE --}}
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        {{-- KOP Surat Resmi Sekolah --}}
        @include('partials.kop-surat')

        @if ($isSingle && $surat)
            {{-- TAMPILAN 1: LEMBAR TANDA TERIMA / EKSPEDISI PER-SURAT (A4 PORTRAIT) --}}
            <div class="doc-header" style="text-align: center; margin: 12px 0 16px 0;">
                <div style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-main, #0f172a); letter-spacing: 0.5px;">
                    LEMBAR EKSPEDISI / TANDA TERIMA PENGIRIMAN SURAT DINAS
                </div>
                <div style="font-size: var(--text-base, 0.875rem); color: #475569; margin-top: 3px;">
                    Nomor Agenda: #{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }} &bull; Tanggal:
                    {{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}
                </div>
            </div>

            <div class="box-receipt">
                <table style="width: 100%; border-collapse: collapse; font-size: var(--text-base, 0.875rem); line-height: 1.6;">
                    <tr>
                        <td style="width: 180px; font-weight: 700; color: #475569;">Nomor Surat Keluar</td>
                        <td style="width: 15px;">:</td>
                        <td style="font-family: monospace; font-weight: 800; color: var(--text-main, #0f172a);">{{ $surat->nomor_surat }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569;">Tanggal Surat</td>
                        <td>:</td>
                        <td>{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569;">Klasifikasi / Indeks</td>
                        <td>:</td>
                        <td>{{ $surat->kode_indeks ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569;">Tujuan / Alamat Penerima</td>
                        <td>:</td>
                        <td style="font-weight: 700; color: var(--text-main, #0f172a);">{{ $surat->tujuan_penerima }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 700; color: #475569; vertical-align: top;">Perihal / Isi Surat</td>
                        <td style="vertical-align: top;">:</td>
                        <td style="font-weight: 700; color: #1e3a8a;">
                            {{ $surat->perihal }}
                            @if ($surat->keterangan)
                                <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 400; color: var(--text-muted, #64748b); margin-top: 3px;">
                                    {{ $surat->keterangan }}</div>
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="receipt-grid">
                    {{-- Petugas Pengirim / Kurir --}}
                    <div class="receipt-panel">
                        <div>
                            <div class="receipt-title"><i class="fas fa-paper-plane me-1"></i> Pengirim / Kurir
                                Pengantar</div>
                            <div style="font-size: var(--text-sm, 0.8125rem); line-height: 1.5; color: #334155;">
                                Tanggal Kirim : .............................................<br>
                                Pukul / Waktu : ........................ WIB<br>
                                Nama Pengantar : .............................................
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <div style="border-bottom: 1px dotted #475569; height: 35px; width: 80%; margin: 0 auto;">
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: #475569; margin-top: 4px;">Tanda
                                Tangan Pengantar</div>
                        </div>
                    </div>

                    {{-- Pihak Penerima --}}
                    <div class="receipt-panel">
                        <div>
                            <div class="receipt-title"><i class="fas fa-handshake me-1"></i> Diterima Oleh (Instansi /
                                Penerima)</div>
                            <div style="font-size: var(--text-sm, 0.8125rem); line-height: 1.5; color: #334155;">
                                Tanggal Terima : .............................................<br>
                                Pukul / Waktu : ........................ WIB<br>
                                Nama Penerima : .............................................<br>
                                Jabatan / Telp : .............................................
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <div style="border-bottom: 1px dotted #475569; height: 35px; width: 80%; margin: 0 auto;">
                            </div>
                            <div style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: #475569; margin-top: 4px;">Tanda
                                Tangan &amp; Cap Stempel</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pengesahan Sekolah --}}
            <div class="signature-container" style="page-break-inside: avoid; margin-top: 28px;">
                <div class="sig-box">
                    <div class="sig-title">
                        Pengelola Persuratan (TAS),
                    </div>
                    <div class="sig-name">{{ $kepalaTas?->nama ?: 'Euis Nur Komariah, S.Pd.' }}</div>
                    <div class="sig-nip">NIP: {{ $kepalaTas?->nip ?: ($kepalaTas?->nuptk ?: '—') }}</div>
                </div>

                <div class="sig-box">
                    <div class="sig-title">
                        Kepala Satuan Pendidikan,
                    </div>
                    <div class="sig-name">{{ $kepsek?->nama ?: $sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.' }}
                    </div>
                    <div class="sig-nip">NIP: {{ $kepsek?->nip ?: $sekolah->nip_kepsek ?? '—' }}</div>
                </div>
            </div>
        @else
            {{-- TAMPILAN 2: BUKU EKSPEDISI KOLEKTIF (A4 LANDSCAPE) --}}
            <div class="doc-header" style="text-align: center; margin: 10px 0 14px 0;">
                <div style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-main, #0f172a); letter-spacing: 0.5px;">
                    BUKU EKSPEDISI PENGIRIMAN SURAT KELUAR DINAS
                </div>
                <div style="font-size: var(--text-base, 0.875rem); color: #475569; margin-top: 3px; font-weight: 600;">
                    Daftar Tanda Terima Distribusi Surat Keluar &bull; Total Berkas: {{ $items->count() }} Dokumen
                </div>
            </div>

            <table class="table-ekspedisi">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 85px;">Tgl Surat</th>
                        <th style="width: 155px;">Nomor Surat Keluar</th>
                        <th style="width: 170px;">Alamat / Tujuan Surat</th>
                        <th>Perihal / Ringkasan Isi Surat</th>
                        <th style="width: 110px;">Tgl Kirim / Kurir</th>
                        <th style="width: 150px;">Tanda Tangan &amp; Nama Penerima</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $idx => $item)
                        <tr>
                            <td style="text-align: center; font-weight: 700;">{{ $idx + 1 }}</td>
                            <td style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->tanggal_surat ? \Carbon\Carbon::parse($item->tanggal_surat)->format('d/m/Y') : '—' }}
                            </td>
                            <td style="font-family: monospace; font-weight: 600; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->nomor_surat }}
                            </td>
                            <td style="font-weight: 700; color: var(--text-main, #0f172a);">
                                {{ $item->tujuan_penerima ?: '—' }}
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ $item->perihal }}</div>
                                @if (!empty($item->keterangan))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: #475569; margin-top: 2px;">
                                        {{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td style="font-size: var(--text-sm, 0.8125rem); color: #475569;">
                                Tgl: ......./......./.......<br>
                                Kurir: .......................
                            </td>
                            <td style="height: 48px; vertical-align: bottom; font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b);">
                                <div style="border-bottom: 1px dotted var(--text-muted, #94a3b8); margin-bottom: 3px; height: 30px;"></div>
                                Tgl: ................. Nama: .................
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted, #64748b);">
                                Tidak ada data surat keluar pada daftar ekspedisi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pengesahan Sekolah --}}
            <div class="signature-container" style="page-break-inside: avoid; margin-top: 20px;">
                <div class="sig-box">
                    <div class="sig-title">
                        Pengelola Kearsipan &amp; Ekspedisi,
                    </div>
                    <div class="sig-name">{{ $kepalaTas?->nama ?: 'Euis Nur Komariah, S.Pd.' }}</div>
                    <div class="sig-nip">NIP: {{ $kepalaTas?->nip ?: ($kepalaTas?->nuptk ?: '—') }}</div>
                </div>

                <div class="sig-box">
                    <div class="sig-title">
                        {{ !empty($sekolah?->kabupaten_kota) ? $sekolah->kabupaten_kota : 'Pangandaran' }},
                        {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                        Kepala Satuan Pendidikan,
                    </div>
                    <div class="sig-name">{{ $kepsek?->nama ?: $sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.' }}
                    </div>
                    <div class="sig-nip">NIP: {{ $kepsek?->nip ?: $sekolah->nip_kepsek ?? '—' }}</div>
                </div>
            </div>
        @endif

        {{-- Footer Keabsahan --}}
        <div class="doc-footer" style="margin-top: 24px;">
            <div>
                <strong>Buku Ekspedisi Resmi Tata Naskah Dinas Sekolah — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i') }} WIB &bull; Arsip Distribusi
                Fisik
            </div>
            <div>
                Format A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
