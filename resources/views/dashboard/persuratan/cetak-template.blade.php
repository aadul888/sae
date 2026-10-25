@extends('layouts.cetak')

@section('title', $doc->perihal . ' — ' . $doc->nomor_surat)

@section('back_url', route('dashboard.persuratan.keluar.index', ['tab' => 'template']))
@section('back_text', 'Kembali ke Surat Keluar')
@section('print_text', 'Cetak Surat (Print / PDF)')

@section('content')
    <div class="cetak-page">
        {{-- Kop Surat Sekolah Resmi --}}
        @include('partials.kop-surat')

        {{-- Meta Surat Keluar (Nomor, Lampiran, Hal di kiri; Tempat & Tanggal di kanan) --}}
        <div
            style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; font-size: 11pt; line-height: 1.5;">
            <div style="width: 58%;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 85px; vertical-align: top;">Nomor</td>
                        <td style="width: 15px; vertical-align: top;">:</td>
                        <td style="font-weight: 700; vertical-align: top;">{{ $doc->nomor_surat }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">Lampiran</td>
                        <td style="vertical-align: top;">:</td>
                        <td style="vertical-align: top;">{{ $doc->lampiran ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td style="vertical-align: top;">Perihal</td>
                        <td style="vertical-align: top;">:</td>
                        <td style="font-weight: 700; vertical-align: top;">{{ $doc->perihal }}</td>
                    </tr>
                </table>
            </div>
            <div style="width: 38%; text-align: right;">
                <div>
                    {{ $sekolah->kabupaten_kota ? str_replace(['Kabupaten ', 'Kota '], '', $sekolah->kabupaten_kota) : 'Tempat' }},
                    {{ \Carbon\Carbon::parse($doc->tanggal_surat)->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        {{-- Tujuan Penerima --}}
        <div style="margin-bottom: 20px; font-size: 11pt; line-height: 1.5;">
            <div>Kepada Yth.</div>
            <div style="font-weight: 700;">{{ $doc->tujuan_penerima }}</div>
            <div>di {{ $doc->tujuan_lokasi ?: 'Tempat' }}</div>
        </div>

        {{-- Isi Naskah Surat --}}
        <div class="surat-body" style="font-size: 11pt; line-height: 1.6; text-align: justify; margin-bottom: 24px;">
            <p style="margin-bottom: 12px; text-indent: 0;">
                {{ $doc->salam_pembuka ?: 'Dengan hormat,' }}
            </p>

            @if ($doc->paragraf_pembuka)
                <p style="margin-bottom: 12px; text-indent: 28px;">
                    {{ $doc->paragraf_pembuka }}
                </p>
            @endif

            {{-- Rincian Kegiatan jika ada --}}
            @php
                $rincian = $doc->rincian_kegiatan ?? [];
                $hasRincian =
                    !empty($rincian['hari_tanggal']) ||
                    !empty($rincian['waktu']) ||
                    !empty($rincian['tempat']) ||
                    !empty($rincian['agenda']);
            @endphp

            @if ($hasRincian)
                <table style="width: 90%; margin: 14px 0 16px 28px; border-collapse: collapse; font-size: 11pt;">
                    @if (!empty($rincian['hari_tanggal']))
                        <tr>
                            <td style="width: 150px; padding: 4px 0; vertical-align: top;">Hari / Tanggal</td>
                            <td style="width: 15px; padding: 4px 0; vertical-align: top;">:</td>
                            <td style="font-weight: 700; padding: 4px 0; vertical-align: top;">
                                {{ $rincian['hari_tanggal'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($rincian['waktu']))
                        <tr>
                            <td style="padding: 4px 0; vertical-align: top;">Waktu</td>
                            <td style="padding: 4px 0; vertical-align: top;">:</td>
                            <td style="padding: 4px 0; vertical-align: top;">{{ $rincian['waktu'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($rincian['tempat']))
                        <tr>
                            <td style="padding: 4px 0; vertical-align: top;">Tempat</td>
                            <td style="padding: 4px 0; vertical-align: top;">:</td>
                            <td style="padding: 4px 0; vertical-align: top;">{{ $rincian['tempat'] }}</td>
                        </tr>
                    @endif
                    @if (!empty($rincian['agenda']))
                        <tr>
                            <td style="padding: 4px 0; vertical-align: top;">Acara / Agenda</td>
                            <td style="padding: 4px 0; vertical-align: top;">:</td>
                            <td style="font-weight: 700; padding: 4px 0; vertical-align: top;">{{ $rincian['agenda'] }}
                            </td>
                        </tr>
                    @endif
                </table>
            @endif

            @if ($doc->isi_surat)
                <div style="margin-bottom: 14px; white-space: pre-line; text-indent: 28px;">
                    {{ $doc->isi_surat }}
                </div>
            @endif

            @if ($doc->paragraf_penutup)
                <p style="margin-bottom: 12px; text-indent: 28px;">
                    {{ $doc->paragraf_penutup }}
                </p>
            @endif
        </div>

        {{-- Area Tanda Tangan Pejabat --}}
        <div class="signature-container"
            style="display: flex; justify-content: flex-end; margin-top: 36px; page-break-inside: avoid;">
            <div class="sig-box" style="text-align: center; width: 260px; font-size: 11pt;">
                <div style="font-weight: 600; margin-bottom: 60px;">
                    {{ $doc->pejabat_jabatan ?: 'Kepala Satuan Pendidikan' }},
                </div>
                <div class="sig-name" style="font-weight: 800; text-decoration: underline;">
                    {{ $doc->pejabat_nama }}
                </div>
                <div class="sig-nip" style="font-size: 9.5pt; color: #374151; margin-top: 2px;">
                    NIP. {{ $doc->pejabat_nip ?: '—' }}
                </div>
            </div>
        </div>

        {{-- Tembusan jika ada --}}
        @if (!empty($doc->tembusan))
            <div style="margin-top: 24px; font-size: 9.5pt; line-height: 1.4; page-break-inside: avoid;">
                <strong>Tembusan:</strong>
                <div style="margin-top: 2px; white-space: pre-line; color: #374151;">
                    {{ $doc->tembusan }}
                </div>
            </div>
        @endif

        {{-- Footer Keabsahan & QR Code Verifikasi --}}
        <div class="doc-footer"
            style="margin-top: 30px; padding-top: 10px; border-top: 1px dashed #d1d5db; display: flex; justify-content: space-between; align-items: center; font-size: 8pt; color: var(--text-muted, #64748b); page-break-inside: avoid;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="{{ $qrUri }}" alt="QR Code Verifikasi"
                    style="width: 44px; height: 44px; border: 1px solid #d1d5db; padding: 2px; background: #fff; border-radius: var(--radius-xs, 4px);">
                <div>
                    <strong>Dokumen Resmi Satuan Pendidikan — Sistem Aplikasi Edukasi (SAE)</strong><br>
                    ID Dokumen: <code>{{ $doc->doc_id }}</code> &bull; Pindai QR Code untuk verifikasi keaslian naskah
                    dinas.
                </div>
            </div>
            <div style="text-align: right;">
                Halaman 1 / 1<br>
                Format Kertas: A4 Portrait
            </div>
        </div>
    </div>
@endsection
