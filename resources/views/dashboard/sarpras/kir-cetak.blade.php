@extends('layouts.cetak')

@section('title', 'Cetak KIR — ' . ($ruang->nama_ruang ?? 'Ruang'))

@section('content')
    <div class="sheet a4-landscape" style="padding: 20mm 15mm; font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        {{-- Kop Resmi Sekolah --}}
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 15px 0 20px 0;">
            <h2 style="margin: 0; font-size: 14pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline;">
                KARTU INVENTARIS RUANGAN (KIR)
            </h2>
            <div style="font-size: 10pt; margin-top: 4px; font-weight: 600;">
                Tahun Ajaran {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
            </div>
        </div>

        {{-- Identitas Ruangan --}}
        <table style="width: 100%; margin-bottom: 15px; font-size: 9.5pt; border-collapse: collapse;">
            <tr>
                <td style="width: 18%; padding: 3px 0; font-weight: 600;">Nama Ruangan</td>
                <td style="width: 32%; padding: 3px 0;">: <strong>{{ $ruang->nama_ruang }}</strong></td>
                <td style="width: 20%; padding: 3px 0; font-weight: 600;">Penanggung Jawab</td>
                <td style="width: 30%; padding: 3px 0;">: {{ $ruang->pj_nama ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 3px 0; font-weight: 600;">Kode Ruang</td>
                <td style="padding: 3px 0;">: {{ $ruang->kode_ruang }}</td>
                <td style="padding: 3px 0; font-weight: 600;">NIP Penanggung Jawab</td>
                <td style="padding: 3px 0;">: {{ $ruang->pj_nip ?: '—' }}</td>
            </tr>
            <tr>
                <td style="padding: 3px 0; font-weight: 600;">Gedung / Lantai</td>
                <td style="padding: 3px 0;">: {{ $ruang->gedung ?: '—' }} / Lantai {{ $ruang->lantai ?: '1' }}</td>
                <td style="padding: 3px 0; font-weight: 600;">Ukuran / Luas</td>
                <td style="padding: 3px 0;">:
                    @if ($ruang->panjang && $ruang->lebar)
                        {{ (float) $ruang->panjang }} m × {{ (float) $ruang->lebar }} m ({{ (float) $ruang->luas }} m²)
                    @elseif ($ruang->luas)
                        {{ (float) $ruang->luas }} m²
                    @else
                        —
                    @endif
                </td>
            </tr>
        </table>

        {{-- Tabel Inventaris Barang --}}
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 25px;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    <th style="padding: 6px 4px; width: 30px;">No</th>
                    <th style="padding: 6px 6px; width: 110px;">Kode Aset</th>
                    <th style="padding: 6px 8px;">Nama Barang / Jenis Aset</th>
                    <th style="padding: 6px 8px; width: 140px;">Merk / Tipe / Spesifikasi</th>
                    <th style="padding: 6px 4px; width: 55px;">Tahun</th>
                    <th style="padding: 6px 4px; width: 45px;">Jml</th>
                    <th style="padding: 6px 4px; width: 50px;">Satuan</th>
                    <th style="padding: 6px 4px; width: 50px;">Baik</th>
                    <th style="padding: 6px 4px; width: 50px;">RR</th>
                    <th style="padding: 6px 4px; width: 50px;">RB</th>
                    <th style="padding: 6px 6px; width: 100px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    @php
                        $b  = (int) ($it->jumlah_baik ?? ($it->kondisi === 'baik' ? $it->jumlah : 0));
                        $rr = (int) ($it->jumlah_rusak_ringan ?? ($it->kondisi === 'rusak_ringan' ? $it->jumlah : 0));
                        $rb = (int) ($it->jumlah_rusak_berat ?? ($it->kondisi === 'rusak_berat' ? $it->jumlah : 0));
                    @endphp
                    <tr>
                        <td style="padding: 5px 4px; text-align: center;">{{ $idx + 1 }}</td>
                        <td style="padding: 5px 6px; font-family: monospace; font-size: 8.5pt;">{{ $it->kode_aset }}</td>
                        <td style="padding: 5px 8px; font-weight: 600;">{{ $it->nama_barang }}</td>
                        <td style="padding: 5px 8px;">
                            {{ $it->merk_tipe ?: '—' }}
                            @if ($it->no_seri_pabrik)
                                <br><small style="color: #64748b;">SN: {{ $it->no_seri_pabrik }}</small>
                            @endif
                        </td>
                        <td style="padding: 5px 4px; text-align: center;">{{ $it->tahun_perolehan ?: '—' }}</td>
                        <td style="padding: 5px 4px; text-align: center; font-weight: 700;">{{ $it->jumlah }}</td>
                        <td style="padding: 5px 4px; text-align: center;">{{ $it->satuan }}</td>
                        <td style="padding: 5px 4px; text-align: center;">{{ $b > 0 ? $b : '—' }}</td>
                        <td style="padding: 5px 4px; text-align: center;">{{ $rr > 0 ? $rr : '—' }}</td>
                        <td style="padding: 5px 4px; text-align: center;">{{ $rb > 0 ? $rb : '—' }}</td>
                        <td style="padding: 5px 6px; font-size: 8pt; color: #475569;">
                            {{ $it->sumber_dana ? 'Sumber: ' . $it->sumber_dana : '' }}
                            {{ $it->jurusan_singkatan ? '(' . $it->jurusan_singkatan . ')' : '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 18px; color: #64748b;">
                            Belum ada data barang / aset yang ditempatkan pada ruangan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($items->isNotEmpty())
                <tfoot>
                    <tr style="background-color: #f8fafc; font-weight: 700; text-align: center;">
                        <td colspan="5" style="padding: 6px; text-align: right;">TOTAL UNIT:</td>
                        <td style="padding: 6px;">{{ $items->sum('jumlah') }}</td>
                        <td>unit</td>
                        <td style="padding: 6px;">{{ $items->sum(fn($i) => (int) ($i->jumlah_baik ?? ($i->kondisi === 'baik' ? $i->jumlah : 0))) }}</td>
                        <td style="padding: 6px;">{{ $items->sum(fn($i) => (int) ($i->jumlah_rusak_ringan ?? ($i->kondisi === 'rusak_ringan' ? $i->jumlah : 0))) }}</td>
                        <td style="padding: 6px;">{{ $items->sum(fn($i) => (int) ($i->jumlah_rusak_berat ?? ($i->kondisi === 'rusak_berat' ? $i->jumlah : 0))) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>

        {{-- Lembar Tanda Tangan 3 Kolom --}}
        <table style="width: 100%; border: none; font-size: 9.5pt; text-align: center; margin-top: 15px;">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                    <div style="height: 60px;"></div>
                    <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $kepalaSekolah->nip ?? '—' }}</span>
                </td>
                <td style="width: 34%; vertical-align: top;">
                    Memeriksa,<br>
                    <strong>Pengelola / Waka Sarpras</strong>
                    <div style="height: 60px;"></div>
                    <strong><u>{{ $wakaSarpras->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $wakaSarpras->nip ?? '—' }}</span>
                </td>
                <td style="width: 33%; vertical-align: top;">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Penanggung Jawab Ruangan</strong>
                    <div style="height: 60px;"></div>
                    <strong><u>{{ $ruang->pj_nama ?: '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $ruang->pj_nip ?: '—' }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
