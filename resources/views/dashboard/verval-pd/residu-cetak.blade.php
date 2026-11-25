<!DOCTYPE html>
<html lang="id">

{{--
    Cetak Laporan Residu Verval PD (A4, orientasi bisa dipilih).

    Menampilkan hasil penyandingan Dukcapil apa adanya (centang → Sesuai/Tidak
    Sesuai) + Nama Kelas, Status Proses, dan Catatan Masalah per peserta didik.
    Data disiapkan `VervalPdController::cetakResidu()`. Ukuran kertas diatur
    `@page` sesuai orientasi yang dipilih pada review (default landscape).
--}}

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Residu Verval PD — {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/tokens.css') }}?v={{ file_exists(public_path('css/tokens.css')) ? filemtime(public_path('css/tokens.css')) : time() }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        /* 16 kolom → landscape agar tidak terpotong; portrait tetap diizinkan. */
        @page {
            size: A4 {{ $orientasi }};
            margin: 1.2cm;
        }

        .table-residu {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: var(--text-2xs, 0.6875rem);
            margin: 10px 0 18px 0;
        }

        .table-residu th {
            background: #f1f5f9 !important;
            color: #0f172a;
            border: 1px solid #64748b;
            padding: 4px 3px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.2;
            word-break: break-word;
        }

        .table-residu td {
            border: 1px solid #94a3b8;
            padding: 3px 4px;
            vertical-align: middle;
            color: #1e293b;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .table-residu tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* Nama panjang tetap dalam satu kolom, jangan melebar. */
        .table-residu td.col-nama {
            font-weight: 600;
        }

        /* NISN / NIK selalu satu baris (dipisah <br>), jangan melebar. */
        .table-residu td.col-nisn {
            font-family: monospace;
            font-size: var(--text-2xs, 0.6875rem);
        }

        @media print {
            .table-residu th {
                background: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-residu tr:nth-child(even) td {
                background: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-residu tr {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

    {{-- Toolbar (tidak ikut tercetak) --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.verval-pd.residu', request()->only(['status', 'q'])) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}" title="Kertas A4 Portrait">
                <i class="fas fa-file"></i> Portrait
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}" title="Kertas A4 Landscape">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
        </div>

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Dokumen (Print / PDF)
        </button>
    </div>

    @php
        $statusLabel = fn($v) => \App\Services\VervalPdResiduService::STATUS_LABELS[$v] ?? 'Belum Diproses';

        // Sel centang → ikon berwarna saja (hijau ✓ / merah ✗), tanpa teks tambahan.
        $renderSel = function ($v) {
            $v = trim((string) $v);
            if ($v === '') {
                return '<span style="color: #94a3b8;">-</span>';
            }
            if (preg_match('/[✓✔√]/u', $v) === 1) {
                return '<i class="fas fa-check" style="color: var(--success, #059669);"></i>';
            }
            if (preg_match('/[✗✘×]/u', $v) === 1) {
                return '<i class="fas fa-xmark" style="color: var(--danger, #dc2626);"></i>';
            }

            return e($v);
        };
    @endphp

    {{-- Halaman Dokumen A4 (orientasi mengikuti pilihan) --}}
    <div class="cetak-page {{ $orientasi === 'landscape' ? 'landscape' : '' }}">
        {{-- Watermark Logo SAE --}}
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        {{-- KOP Surat Resmi Sekolah --}}
        @include('partials.kop-surat')

        {{-- Judul Laporan --}}
        <div class="doc-header">
            <div class="doc-title-main">LAPORAN RESIDU VERVAL PD</div>
            <div class="doc-subtitle" style="text-align: center;">
                Hasil Penyandingan Dukcapil
                @if ($selectedCount > 0)
                    &bull; Data Terpilih: {{ $selectedCount }} Baris
                @else
                    @if ($status !== '')
                        &bull; Filter Status: {{ $statusLabel($status) }}
                    @endif
                    @if ($q !== '')
                        &bull; Pencarian: "{{ $q }}"
                    @endif
                    &bull; Total: {{ $items->count() }} Baris
                @endif
            </div>
        </div>

        <table class="table-residu">
            <thead>
                <tr>
                    <th style="width: 3%;">No</th>
                    <th style="width: 9%;">NISN / NIK</th>
                    <th style="width: 12%;">Nama</th>
                    <th style="width: 5.5%;">Kelas</th>
                    <th style="width: 6.5%;">Status Proses</th>
                    <th style="width: 6.5%;">Rombel/Sekolah</th>
                    <th style="width: 5.5%;">NISN</th>
                    <th style="width: 5.5%;">NIK Dukcapil</th>
                    <th style="width: 5%;">NIK Ganda</th>
                    <th style="width: 8%;">Nama</th>
                    <th style="width: 7.5%;">Tempat Lahir</th>
                    <th style="width: 5.5%;">Tanggal Lahir</th>
                    <th style="width: 7.5%;">Ibu Kandung</th>
                    <th style="width: 3%;">JK</th>
                    <th style="width: 7.5%;">Desa/Kelurahan</th>
                    <th style="width: 12%;">Catatan Masalah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td class="col-nisn">
                            {{ $it->nisn ?: '-' }}
                            @if ($it->nik)
                                <br>{{ $it->nik }}
                            @endif
                        </td>
                        <td class="col-nama">{{ $it->nama ?: '-' }}</td>
                        <td style="text-align: center;">{{ $it->kelas ?: '-' }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $statusLabel($it->status_proses) }}</td>
                        <td style="text-align: center;">{!! $renderSel($it->rombel_sekolah) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->nisn_banding) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->nik_dukcapil) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->nik_ganda) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->nama_banding) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->tempat_lahir) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->tanggal_lahir) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->ibu_kandung) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->jenis_kelamin) !!}</td>
                        <td style="text-align: center;">{!! $renderSel($it->desa_kelurahan) !!}</td>
                        <td>{{ trim((string) $it->catatan) !== '' ? $it->catatan : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" style="text-align: center; padding: 18px; color: #64748b;">
                            Tidak ada data Residu pada kriteria ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Tanda Tangan Pengesahan --}}
        <div class="signature-container" style="page-break-inside: avoid; margin-top: 18px;">
            <div class="sig-box">
                <div class="sig-title">Mengetahui,<br><strong>Kepala Sekolah</strong></div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $kepsek->nama ?? '( .............................................. )' }}</div>
                <div class="sig-nip">NIP: {{ $kepsek->nip ?? '—' }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    {{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }},
                    {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Operator Verval PD</strong>
                </div>
                <div class="sig-space"></div>
                <div class="sig-name">( .............................................. )</div>
                <div class="sig-nip">NIP: —</div>
            </div>
        </div>

        {{-- Footer Keabsahan --}}
        <div class="doc-footer" style="margin-top: 20px;">
            <div>
                <strong>Laporan Residu Verval PD — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ now()->translatedFormat('l, d F Y H:i') }} WIB
            </div>
            <div>Format A4 {{ ucfirst($orientasi) }}</div>
        </div>
    </div>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
