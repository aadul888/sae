<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Buku Induk Siswa</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.kesiswaan.buku-induk.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Register (Print / PDF)
        </button>
    </div>

    <div class="cetak-page landscape">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        @include('partials.kop-surat')

        <div class="doc-header">
            <div class="doc-title-main">REGISTER BUKU INDUK PESERTA DIDIK</div>
            <div class="doc-subtitle">
                @if (!empty($rombel))
                    ROMBEL: {{ strtoupper($rombel->nama) }} &bull;
                @else
                    SELURUH ROMBEL &bull;
                @endif
                @if (!empty($tahunMasuk))
                    ANGKATAN {{ $tahunMasuk }} &bull;
                @endif
                STATUS: {{ strtoupper($status === 'all' ? 'SEMUA' : str_replace('_', ' ', $status)) }} &bull;
                JUMLAH: {{ $items->count() }} SISWA
            </div>
        </div>

        <table class="table-data" style="font-size: var(--text-2xs, 0.6875rem);">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th style="width: 78px;">No. Induk</th>
                    <th style="width: 70px;">No. Klaper</th>
                    <th>Nama Peserta Didik</th>
                    <th style="width: 32px; text-align: center;">L/P</th>
                    <th style="width: 105px;">Tempat Lahir</th>
                    <th style="width: 72px;">Tgl. Lahir</th>
                    <th style="width: 110px;">NISN</th>
                    <th style="width: 110px;">NIK</th>
                    <th style="width: 95px;">Rombel</th>
                    <th style="width: 105px;">Nama Ayah</th>
                    <th style="width: 105px;">Nama Ibu</th>
                    <th style="width: 55px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $row)
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td class="font-mono">{{ $row->nomor_induk ?: ($row->nipd ?: '—') }}</td>
                        <td class="font-mono">{{ $row->nomor_klaper ?: '—' }}</td>
                        <td><strong>{{ $row->nama }}</strong></td>
                        <td class="center">{{ $row->jenis_kelamin ?: '—' }}</td>
                        <td>{{ $row->tempat_lahir ?: '—' }}</td>
                        <td class="center">
                            {{ $row->tanggal_lahir ? \Carbon\Carbon::parse($row->tanggal_lahir)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="font-mono">{{ $row->nisn ?: '—' }}</td>
                        <td class="font-mono">{{ $row->nik ?: '—' }}</td>
                        <td>{{ $row->rombel_nama ?: '—' }}</td>
                        <td>{{ $row->nama_ayah ?: '—' }}</td>
                        <td>{{ $row->nama_ibu ?: '—' }}</td>
                        <td class="center" style="text-transform: uppercase;">
                            {{ str_replace('_', ' ', $row->status_klaper ?: 'aktif') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada data siswa pada filter yang dipilih.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            style="display: flex; justify-content: space-between; margin-top: 16px; font-size: var(--text-xs, 0.75rem);">
            <div style="text-align: center; min-width: 220px;">
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <div style="height: 56px;"></div>
                <div style="font-weight: 800; text-decoration: underline;">
                    {{ $kepsek->nama ?? '................................' }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: #4b5563;">
                    NIP. {{ $kepsek->nip ?? '................................' }}
                </div>
            </div>
            <div style="text-align: center; min-width: 220px;">
                <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div>Petugas Buku Induk</div>
                <div style="height: 56px;"></div>
                <div style="font-weight: 800; text-decoration: underline;">
                    ................................
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: #4b5563;">NIP. ................................
                </div>
            </div>
        </div>
    </div>
</body>

</html>
