@extends('layouts.cetak')

@section('title', 'Cetak Laporan Perpustakaan')

@section('content')
    <div class="paper" style="font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 12px 0 16px 0;">
            <h2 style="margin: 0; font-size: 13pt; font-weight: 800; text-transform: uppercase; text-decoration: underline;">
                LAPORAN &amp; REKAPITULASI PERPUSTAKAAN SEKOLAH
            </h2>
            <div style="font-size: 9.5pt; margin-top: 3px; font-weight: 600;">
                Periode: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') }}
                @if ($kategori) &bull; Kategori: {{ $kategori }} @endif
            </div>
        </div>

        {{-- A. Rekapitulasi Koleksi --}}
        <h3 style="font-size: 10.5pt; font-weight: 800; margin: 0 0 6px 0;">A. Rekapitulasi Koleksi Buku</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 16px;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    <th style="padding: 4px; width: 30px;">No</th>
                    <th style="padding: 4px 6px;">Kategori</th>
                    <th style="padding: 4px; width: 70px;">Judul</th>
                    <th style="padding: 4px; width: 80px;">Eksemplar</th>
                    <th style="padding: 4px; width: 70px;">Tersedia</th>
                    <th style="padding: 4px; width: 70px;">Dipinjam</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapKoleksi as $idx => $row)
                    <tr>
                        <td style="padding: 3px; text-align: center;">{{ $idx + 1 }}</td>
                        <td style="padding: 3px 6px;">{{ $row->kategori }}</td>
                        <td style="padding: 3px; text-align: center;">{{ (int) $row->judul }}</td>
                        <td style="padding: 3px; text-align: center;">{{ (int) $row->eksemplar }}</td>
                        <td style="padding: 3px; text-align: center;">{{ (int) $row->tersedia }}</td>
                        <td style="padding: 3px; text-align: center;">{{ max(0, (int) $row->eksemplar - (int) $row->tersedia) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding: 12px; text-align: center;">Tidak ada data koleksi.</td></tr>
                @endforelse
            </tbody>
            @if ($rekapKoleksi->isNotEmpty())
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: 800;">
                        <td colspan="2" style="padding: 5px; text-align: right;">TOTAL</td>
                        <td style="padding: 5px; text-align: center;">{{ number_format($totalJudul, 0, ',', '.') }}</td>
                        <td style="padding: 5px; text-align: center;">{{ number_format($totalEksemplar, 0, ',', '.') }}</td>
                        <td style="padding: 5px; text-align: center;">{{ number_format($totalTersedia, 0, ',', '.') }}</td>
                        <td style="padding: 5px; text-align: center;">{{ number_format(max(0, $totalEksemplar - $totalTersedia), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        {{-- B. Rekapitulasi Sirkulasi --}}
        <h3 style="font-size: 10.5pt; font-weight: 800; margin: 0 0 6px 0;">B. Rekapitulasi Sirkulasi &amp; Denda</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8px;" border="1">
            <tr style="background-color: #f1f5f9; font-weight: 700; text-align: center;">
                <td style="padding: 4px;">Total Pinjam</td>
                <td style="padding: 4px;">Dikembalikan</td>
                <td style="padding: 4px;">Masih Dipinjam</td>
                <td style="padding: 4px;">Terlambat</td>
                <td style="padding: 4px;">Denda Terkumpul</td>
            </tr>
            <tr style="text-align: center;">
                <td style="padding: 4px;">{{ number_format($totalPinjam, 0, ',', '.') }}</td>
                <td style="padding: 4px;">{{ number_format($totalKembali, 0, ',', '.') }}</td>
                <td style="padding: 4px;">{{ number_format($masihDipinjam, 0, ',', '.') }}</td>
                <td style="padding: 4px;">{{ number_format($terlambat, 0, ',', '.') }}</td>
                <td style="padding: 4px;">Rp {{ number_format($dendaTerkumpul, 0, ',', '.') }}</td>
            </tr>
        </table>

        <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 16px;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    <th style="padding: 4px; width: 28px;">No</th>
                    <th style="padding: 4px;">Kode</th>
                    <th style="padding: 4px;">Peminjam</th>
                    <th style="padding: 4px;">Judul Buku</th>
                    <th style="padding: 4px; width: 70px;">Pinjam</th>
                    <th style="padding: 4px; width: 70px;">Tempo</th>
                    <th style="padding: 4px; width: 70px;">Kembali</th>
                    <th style="padding: 4px; width: 60px;">Status</th>
                    <th style="padding: 4px; width: 70px;">Denda</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapSirkulasi as $idx => $row)
                    <tr>
                        <td style="padding: 3px; text-align: center;">{{ $idx + 1 }}</td>
                        <td style="padding: 3px; font-family: monospace; font-size: 8pt;">{{ $row->kode_transaksi }}</td>
                        <td style="padding: 3px 5px;">{{ $row->peminjam_nama }}</td>
                        <td style="padding: 3px 5px;">{{ $row->judul_buku ?? '-' }}</td>
                        <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($row->tgl_pinjam)->format('d/m/y') }}</td>
                        <td style="padding: 3px; text-align: center;">{{ $row->tgl_jatuh_tempo ? \Carbon\Carbon::parse($row->tgl_jatuh_tempo)->format('d/m/y') : '-' }}</td>
                        <td style="padding: 3px; text-align: center;">{{ $row->tgl_kembali ? \Carbon\Carbon::parse($row->tgl_kembali)->format('d/m/y') : '-' }}</td>
                        <td style="padding: 3px; text-align: center;">{{ ucfirst($row->status) }}</td>
                        <td style="padding: 3px; text-align: right;">{{ $row->denda > 0 ? number_format((float) $row->denda, 0, ',', '.') : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="padding: 12px; text-align: center;">Tidak ada transaksi sirkulasi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- C. Rekapitulasi Kunjungan --}}
        <h3 style="font-size: 10.5pt; font-weight: 800; margin: 0 0 6px 0;">C. Rekapitulasi Kunjungan ({{ number_format($totalKunjungan, 0, ',', '.') }} orang)</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 16px;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                    <table style="width: 100%; border-collapse: collapse;" border="1">
                        <thead>
                            <tr style="background-color: #f1f5f9; font-weight: 700;">
                                <th style="padding: 4px;">Keperluan</th>
                                <th style="padding: 4px; width: 60px;">Jml</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapKunjunganKeperluan as $row)
                                <tr>
                                    <td style="padding: 3px 6px;">{{ $row->keperluan }}</td>
                                    <td style="padding: 3px; text-align: center;">{{ (int) $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="padding: 10px; text-align: center;">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 8px;">
                    <table style="width: 100%; border-collapse: collapse;" border="1">
                        <thead>
                            <tr style="background-color: #f1f5f9; font-weight: 700;">
                                <th style="padding: 4px;">Rombel / Unit</th>
                                <th style="padding: 4px; width: 60px;">Jml</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapKunjunganUnit as $row)
                                <tr>
                                    <td style="padding: 3px 6px;">{{ $row->unit }}</td>
                                    <td style="padding: 3px; text-align: center;">{{ (int) $row->total }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" style="padding: 10px; text-align: center;">-</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        @if ($bukuPopuler->isNotEmpty())
            <h4 style="font-size: 9.5pt; font-weight: 800; margin: 0 0 6px 0;">Buku Paling Sering Dipinjam</h4>
            <table style="width: 60%; border-collapse: collapse; font-size: 9pt; margin-bottom: 16px;" border="1">
                <thead>
                    <tr style="background-color: #f1f5f9; font-weight: 700;">
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px;">Judul</th>
                        <th style="padding: 4px; width: 60px;">Kali</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bukuPopuler as $idx => $row)
                        <tr>
                            <td style="padding: 3px; text-align: center;">{{ $idx + 1 }}</td>
                            <td style="padding: 3px 6px;">{{ $row->judul }}</td>
                            <td style="padding: 3px; text-align: center;">{{ (int) $row->total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Tanda Tangan --}}
        <table style="width: 100%; border: none; font-size: 9pt; text-align: center; margin-top: 20px;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $kepalaSekolah->nip ?? '......................' }}</span>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Pustakawan / Pengelola Perpustakaan</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $pustakawan->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $pustakawan->nip ?? '......................' }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
