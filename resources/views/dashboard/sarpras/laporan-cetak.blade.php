@extends('layouts.cetak')

@section('title', 'Cetak Buku Induk Inventaris Sarpras')

@section('content')
    <div class="sheet a4-landscape" style="padding: 15mm 12mm; font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        {{-- Kop Resmi Sekolah --}}
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 15px 0 18px 0;">
            <h2 style="margin: 0; font-size: 13pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline;">
                BUKU INDUK INVENTARIS ASET DAN SARANA SEKOLAH
            </h2>
            <div style="font-size: 9.5pt; margin-top: 3px; font-weight: 600;">
                Tahun Ajaran {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                @if ($kategoriFilter || $sumberFilter || $kondisiFilter || $tahunFilter)
                    &bull; Filter:
                    {{ $kategoriFilter ? 'Kategori: ' . $kategoriFilter . ' | ' : '' }}
                    {{ $sumberFilter ? 'Sumber: ' . $sumberFilter . ' | ' : '' }}
                    {{ $kondisiFilter ? 'Kondisi: ' . $kondisiFilter . ' | ' : '' }}
                    {{ $tahunFilter ? 'Tahun: ' . $tahunFilter : '' }}
                @endif
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-bottom: 20px;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    <th style="padding: 5px 3px; width: 28px;">No</th>
                    <th style="padding: 5px 4px; width: 100px;">Kode Aset</th>
                    <th style="padding: 5px 6px;">Nama Barang / Aset</th>
                    <th style="padding: 5px 6px; width: 90px;">Kategori</th>
                    <th style="padding: 5px 6px; width: 110px;">Merk / Model / Spesifikasi</th>
                    <th style="padding: 5px 3px; width: 45px;">Tahun</th>
                    <th style="padding: 5px 4px; width: 65px;">Sumber</th>
                    <th style="padding: 5px 6px; width: 100px;">Penempatan</th>
                    <th style="padding: 5px 3px; width: 35px;">Jml</th>
                    <th style="padding: 5px 3px; width: 40px;">Sat</th>
                    <th style="padding: 5px 4px; width: 75px; text-align: right;">Harga (Rp)</th>
                    <th style="padding: 5px 4px; width: 85px; text-align: right;">Total Nilai (Rp)</th>
                    <th style="padding: 5px 4px; width: 70px;">Kondisi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $grandTotalUnit = 0;
                    $grandTotalNilai = 0;
                @endphp
                @forelse ($items as $idx => $it)
                    @php
                        $subtotal = ((float) $it->harga_perolehan) * ((int) $it->jumlah);
                        $grandTotalUnit += (int) $it->jumlah;
                        $grandTotalNilai += $subtotal;
                    @endphp
                    <tr>
                        <td style="padding: 4px 3px; text-align: center;">{{ $idx + 1 }}</td>
                        <td style="padding: 4px 4px; font-family: monospace; font-size: 8pt;">{{ $it->kode_aset }}</td>
                        <td style="padding: 4px 6px; font-weight: 600;">{{ $it->nama_barang }}</td>
                        <td style="padding: 4px 6px;">{{ $it->kategori }}</td>
                        <td style="padding: 4px 6px;">{{ $it->merk_tipe ?: '—' }}</td>
                        <td style="padding: 4px 3px; text-align: center;">{{ $it->tahun_perolehan ?: '—' }}</td>
                        <td style="padding: 4px 4px; text-align: center;">{{ $it->sumber_dana }}</td>
                        <td style="padding: 4px 6px;">
                            {{ $it->nama_ruang ?: 'Belum Ditentukan' }}
                            {{ $it->jurusan_singkatan ? '(' . $it->jurusan_singkatan . ')' : '' }}
                        </td>
                        <td style="padding: 4px 3px; text-align: center; font-weight: 700;">{{ $it->jumlah }}</td>
                        <td style="padding: 4px 3px; text-align: center;">{{ $it->satuan }}</td>
                        <td style="padding: 4px 4px; text-align: right;">{{ number_format((float) $it->harga_perolehan, 0, ',', '.') }}</td>
                        <td style="padding: 4px 4px; text-align: right; font-weight: 600;">{{ number_format($subtotal, 0, ',', '.') }}</td>
                        <td style="padding: 4px 4px; text-align: center; font-size: 8pt;">{{ ucfirst(str_replace('_', ' ', $it->kondisi)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" style="text-align: center; padding: 18px; color: #64748b;">
                            Tidak ada data aset pada kriteria Buku Induk Inventaris ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->isNotEmpty())
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: 800;">
                        <td colspan="8" style="padding: 6px; text-align: right;">TOTAL KESELURUHAN:</td>
                        <td style="padding: 6px; text-align: center;">{{ number_format($grandTotalUnit, 0, ',', '.') }}</td>
                        <td style="padding: 6px; text-align: center;">unit</td>
                        <td style="padding: 6px;"></td>
                        <td style="padding: 6px; text-align: right;">Rp {{ number_format($grandTotalNilai, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        {{-- Lembar Tanda Tangan 3 Kolom Resmi --}}
        <table style="width: 100%; border: none; font-size: 9pt; text-align: center; margin-top: 15px;">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $kepalaSekolah->nip ?? '—' }}</span>
                </td>
                <td style="width: 34%; vertical-align: top;">
                    Memeriksa,<br>
                    <strong>Waka Bidang Sarpras</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $wakaSarpras->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $wakaSarpras->nip ?? '—' }}</span>
                </td>
                <td style="width: 33%; vertical-align: top;">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Pengurus Barang / Staf Sarpras</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $stafSarpras->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $stafSarpras->nip ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
