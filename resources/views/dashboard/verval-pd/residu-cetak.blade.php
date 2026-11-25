@extends('layouts.cetak')

{{--
    Cetak Laporan Residu Verval PD (A4 landscape).

    Menampilkan hasil penyandingan Dukcapil apa adanya (centang → Sesuai/Tidak
    Sesuai) + Nama Kelas, Status Proses, dan Catatan Masalah per peserta didik.
    Data disiapkan `VervalPdController::cetakResidu()`.
--}}

@section('title', 'Laporan Residu Verval PD')

@section('content')
    @php
        $svc = app(\App\Services\VervalPdResiduService::class);
        $labelNilai = fn($v) => $svc->tampilNilai($v);
        $statusLabel = fn($v) => \App\Services\VervalPdResiduService::STATUS_LABELS[$v] ?? 'Belum Diproses';
    @endphp

    <div class="sheet a4-landscape" style="padding: 12mm 10mm; font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 12px 0 14px 0;">
            <h2 style="margin: 0; font-size: var(--text-lg, 1.125rem); font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline;">
                LAPORAN RESIDU VERVAL PD
            </h2>
            <div style="font-size: var(--text-sm, 0.8125rem); margin-top: 3px; font-weight: 600;">
                Hasil Penyandingan Dukcapil
                @if ($status !== '')
                    &bull; Filter Status: {{ $statusLabel($status) }}
                @endif
                @if ($q !== '')
                    &bull; Pencarian: "{{ $q }}"
                @endif
                &bull; Total: {{ $items->count() }} Baris
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: var(--text-xs, 0.75rem); margin-bottom: 18px;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    <th style="padding: 4px 3px; width: 28px;">No</th>
                    <th style="padding: 4px 4px; width: 90px;">NISN / NIK</th>
                    <th style="padding: 4px 5px; width: 150px;">Nama</th>
                    <th style="padding: 4px 4px; width: 60px;">Kelas</th>
                    <th style="padding: 4px 4px; width: 80px;">Rombel/Sekolah</th>
                    <th style="padding: 4px 4px; width: 80px;">Status Proses</th>
                    <th style="padding: 4px 4px; width: 60px;">NISN Dukcapil</th>
                    <th style="padding: 4px 4px; width: 60px;">NIK Dukcapil</th>
                    <th style="padding: 4px 4px; width: 55px;">NIK Ganda</th>
                    <th style="padding: 4px 4px; width: 90px;">Nama Dukcapil</th>
                    <th style="padding: 4px 4px; width: 85px;">Tempat Lahir</th>
                    <th style="padding: 4px 4px; width: 70px;">Tanggal Lahir</th>
                    <th style="padding: 4px 4px; width: 85px;">Ibu Kandung</th>
                    <th style="padding: 4px 4px; width: 55px;">JK</th>
                    <th style="padding: 4px 4px; width: 85px;">Desa/Kelurahan</th>
                    <th style="padding: 4px 4px;">Catatan Masalah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $it)
                    <tr>
                        <td style="padding: 3px 3px; text-align: center;">{{ $idx + 1 }}</td>
                        <td style="padding: 3px 4px; font-family: monospace; font-size: var(--text-xs, 0.75rem);">
                            {{ $it->nisn ?: '-' }}
                            @if ($it->nik)
                                <br>{{ $it->nik }}
                            @endif
                        </td>
                        <td style="padding: 3px 5px; font-weight: 600;">{{ $it->nama ?: '-' }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $it->kelas ?: '-' }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->rombel_sekolah) }}</td>
                        <td style="padding: 3px 4px; text-align: center; font-weight: 700;">
                            {{ $statusLabel($it->status_proses) }}
                        </td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->nisn_banding) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->nik_dukcapil) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->nik_ganda) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->nama_banding) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->tempat_lahir) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->tanggal_lahir) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->ibu_kandung) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->jenis_kelamin) }}</td>
                        <td style="padding: 3px 4px; text-align: center;">{{ $labelNilai($it->desa_kelurahan) }}</td>
                        <td style="padding: 3px 5px;">{{ trim((string) $it->catatan) !== '' ? $it->catatan : '—' }}</td>
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
                <div style="height: 55px;"></div>
                <div class="sig-name">{{ $kepsek->nama ?? '( .............................................. )' }}</div>
                <div class="sig-nip">NIP: {{ $kepsek->nip ?? '—' }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Operator Verval PD</strong>
                </div>
                <div style="height: 55px;"></div>
                <div class="sig-name">( .............................................. )</div>
                <div class="sig-nip">NIP: —</div>
            </div>
        </div>

        <div class="doc-footer" style="margin-top: 20px;">
            <div>
                <strong>Laporan Residu Verval PD — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ now()->translatedFormat('l, d F Y H:i') }} WIB
            </div>
            <div>Format A4 Landscape</div>
        </div>
    </div>
@endsection
