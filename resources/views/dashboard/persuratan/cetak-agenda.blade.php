<!DOCTYPE html>
<html lang="id">
@php
    $orientasi = $orientasi ?? 'landscape';
    $jenisSurat = $jenisSurat ?? 'masuk';
    $judul = $judul ?? ($jenisSurat === 'masuk' ? 'BUKU AGENDA SURAT MASUK' : 'BUKU AGENDA SURAT KELUAR');
    $items = $items ?? collect();
    $sekolah = $sekolah ?? \App\Models\Sekolah::first();
    $sekolahMeta = $sekolahMeta ?? \App\Models\SekolahMeta::first();
    $kepsek = $kepsek ?? null;
    $kepalaTas = $kepalaTas ?? null;
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judul }} — {{ $sekolah?->nama ?? 'Sekolah' }}</title>
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

        .filter-form-bar input,
        .filter-form-bar select {
            height: 28px;
            padding: 0 8px;
            border-radius: var(--radius-sm, 6px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            background: #ffffff;
            color: #1e293b;
            font-size: var(--text-sm, 0.8125rem);
            font-weight: 600;
        }

        .table-agenda {
            width: 100%;
            border-collapse: collapse;
            font-size: var(--text-sm, 0.8125rem);
            margin-top: 14px;
            margin-bottom: 24px;
        }

        .table-agenda th {
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #64748b;
            padding: 7px 8px;
            text-align: center;
            font-weight: 700;
            font-size: var(--text-sm, 0.8125rem);
            text-transform: uppercase;
        }

        .table-agenda td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: top;
            color: #1e293b;
        }

        .table-agenda tr:nth-child(even) td {
            background: #f8fafc;
        }

        .agenda-header {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .agenda-header h2 {
            font-size: var(--text-xl, 1.25rem);
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.5px;
        }

        .agenda-header p {
            font-size: var(--text-base, 0.875rem);
            color: #475569;
            margin: 4px 0 0 0;
            font-weight: 600;
        }

        @media print {
            .table-agenda th {
                background: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-agenda tr:nth-child(even) td {
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
        <a href="{{ $jenisSurat === 'masuk' ? route('dashboard.persuratan.masuk.index') : route('dashboard.persuratan.keluar.index') }}"
            class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <form method="GET" action="" class="filter-form-bar">
            <span>Dari:</span>
            <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai ?? '' }}">
            <span>S/d:</span>
            <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai ?? '' }}">
            <button type="submit" class="btn-action"
                style="padding: 4px 10px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    {{-- Halaman Dokumen A4 Landscape --}}
    <div class="cetak-page landscape" style="min-height: 100%;">
        {{-- Watermark Logo SAE --}}
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        {{-- KOP Surat Resmi Sekolah --}}
        @include('partials.kop-surat')

        {{-- Judul Buku Agenda --}}
        <div class="agenda-header">
            <h2>{{ $judul }}</h2>
            <p>Periode: {{ $periodeText }} &bull; Total Berkas: {{ $items->count() }} Dokumen</p>
        </div>

        {{-- Tabel Register Agenda Kedinasan --}}
        <table class="table-agenda">
            <thead>
                @if ($jenisSurat === 'masuk')
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 80px;">Tgl Terima</th>
                        <th style="width: 140px;">No. Surat Pengirim</th>
                        <th style="width: 85px;">Tgl Surat</th>
                        <th style="width: 160px;">Instansi / Asal Pengirim</th>
                        <th>Perihal / Ringkasan Isi Surat</th>
                        <th style="width: 140px;">Diteruskan / Disposisi Ke</th>
                        <th style="width: 90px;">Status</th>
                    </tr>
                @else
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th style="width: 80px;">Kode Indeks</th>
                        <th style="width: 85px;">Tgl Surat</th>
                        <th style="width: 160px;">Nomor Surat Keluar</th>
                        <th style="width: 160px;">Alamat / Tujuan Penerima</th>
                        <th>Perihal / Ringkasan Isi Surat</th>
                        <th style="width: 120px;">Pembuat / Unit</th>
                        <th style="width: 90px;">Status</th>
                    </tr>
                @endif
            </thead>
            <tbody>
                @forelse ($items as $idx => $item)
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $idx + 1 }}</td>
                        @if ($jenisSurat === 'masuk')
                            <td style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->tanggal_diterima ? \Carbon\Carbon::parse($item->tanggal_diterima)->format('d/m/Y') : '—' }}
                            </td>
                            <td style="font-family: monospace; font-weight: 600; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->nomor_surat }}
                            </td>
                            <td style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->tanggal_surat ? \Carbon\Carbon::parse($item->tanggal_surat)->format('d/m/Y') : '—' }}
                            </td>
                            <td style="font-weight: 600;">
                                {{ $item->pengirim_asal ?: '—' }}
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ $item->perihal }}</div>
                                @if (!empty($item->keterangan))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: #475569; margin-top: 2px;">
                                        {{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td style="font-size: var(--text-sm, 0.8125rem);">
                                @php
                                    $disposisiList = $item->disposisi ?? collect();
                                    $tujuanStr = $disposisiList
                                        ->map(fn($d) => $d->disposisi_ke ?: ($d->ptk?->nama ?: '—'))
                                        ->filter()
                                        ->unique()
                                        ->implode(', ');
                                @endphp
                                {{ $tujuanStr ?: 'Belum Didisposisi' }}
                            </td>
                            <td
                                style="text-align: center; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; font-weight: 700;">
                                {{ str_replace('_', ' ', $item->status) }}
                            </td>
                        @else
                            <td style="text-align: center; font-family: monospace; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->kode_indeks ?: '-' }}
                            </td>
                            <td style="text-align: center; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->tanggal_surat ? \Carbon\Carbon::parse($item->tanggal_surat)->format('d/m/Y') : '—' }}
                            </td>
                            <td style="font-family: monospace; font-weight: 600; font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->nomor_surat }}
                            </td>
                            <td style="font-weight: 600;">
                                {{ $item->tujuan_penerima ?: '—' }}
                            </td>
                            <td>
                                <div style="font-weight: 700;">{{ $item->perihal }}</div>
                                @if (!empty($item->keterangan))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: #475569; margin-top: 2px;">
                                        {{ $item->keterangan }}</div>
                                @endif
                            </td>
                            <td style="font-size: var(--text-sm, 0.8125rem);">
                                {{ $item->created_by ?: 'Petugas Persuratan' }}
                            </td>
                            <td
                                style="text-align: center; font-size: var(--text-xs, 0.75rem); text-transform: uppercase; font-weight: 700;">
                                {{ str_replace('_', ' ', $item->status) }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 24px; color: #64748b;">
                            Tidak ada dokumen surat yang tercatat pada rentang periode yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Tanda Tangan Pengesahan --}}
        <div class="signature-container" style="page-break-inside: avoid; margin-top: 20px;">
            <div class="sig-box">
                <div class="sig-title">
                    Pengelola Kearsipan &amp; Persuratan,
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
                <div class="sig-name">{{ $kepsek?->nama ?: $sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.' }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: $sekolah->nip_kepsek ?? '—' }}</div>
            </div>
        </div>

        {{-- Footer Keabsahan --}}
        <div class="doc-footer" style="margin-top: 24px;">
            <div>
                <strong>Buku Agenda Resmi Tata Persuratan Sekolah — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i') }} WIB &bull; Pengarsipan
                Berkas Digital
            </div>
            <div>
                Format A4 Landscape
            </div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
