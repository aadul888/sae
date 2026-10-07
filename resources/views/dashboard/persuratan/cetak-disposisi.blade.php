<!DOCTYPE html>
<html lang="id">
@php
    $orientasi = $orientasi ?? 'portrait';
    $surat = $surat ?? null;
    $disposisi = $disposisi ?? null;
    $kepsek = $kepsek ?? null;
    $kepalaTas = $kepalaTas ?? null;
    $disposisiCatatan = $disposisiCatatan ?? ($disposisi?->catatan ?? null);
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Disposisi — {{ $surat->nomor_surat }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Floating Bar --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.persuratan.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <div class="orientation-switch">
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'portrait']) }}"
                class="btn-orientasi {{ $orientasi === 'portrait' ? 'active' : '' }}">
                <i class="fas fa-file"></i> Portrait
            </a>
            <a href="{{ request()->fullUrlWithQuery(['orientasi' => 'landscape']) }}"
                class="btn-orientasi {{ $orientasi === 'landscape' ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i> Landscape
            </a>
        </div>

        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Lembar Disposisi (Print / PDF)
        </button>
    </div>

    {{-- Lembar Cetak --}}
    <div class="cetak-page">
        {{-- Watermark Logo SAE --}}
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        {{-- Kop Surat Sekolah --}}
        <div class="kop-container">
            @if (!empty($sekolahMeta?->kop_url))
                <img src="{{ $sekolahMeta->kop_url }}" alt="Kop Surat" class="kop-image">
            @else
                <div class="kop-fallback">
                    @php
                        $logoUrl = !empty($sekolahMeta?->logo_url) ? $sekolahMeta->logo_url : asset('img/logo-dark.png');
                    @endphp
                    <img src="{{ $logoUrl }}" alt="Logo" class="kop-logo" onerror="this.src='/img/logo-dark.png'">
                    <div class="kop-text-wrap">
                        <div class="kop-title">{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI (SAE)' }}</div>
                        <div class="kop-subtitle">
                            NPSN: {{ $sekolah->npsn ?? '-' }} &bull; Alamat: {{ $sekolah->alamat_jalan ?? 'Jalan Pendidikan' }},
                            {{ $sekolah->kabupaten_kota ?? '' }}<br>
                            Kontak: {{ $sekolah->nomor_telepon ?? '-' }} &bull; Email: {{ $sekolah->email ?? '-' }}
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Header Lembar Disposisi --}}
        <div class="doc-header">
            <div class="doc-title-main">LEMBAR DISPOSISI SURAT DINAS</div>
            <div class="doc-subtitle">Nomor Agenda: #{{ str_pad($surat->id, 4, '0', STR_PAD_LEFT) }} &bull; Klasifikasi: {{ strtoupper($surat->jenis_surat) }}</div>
        </div>

        {{-- Data Surat Masuk --}}
        <table class="table-disp">
            <tr>
                <th>Surat Dari</th>
                <td><strong>{{ $surat->pengirim_asal ?: '—' }}</strong></td>
                <th style="width: 130px;">Tanggal Surat</th>
                <td style="width: 170px;">{{ \Carbon\Carbon::parse($surat->tanggal_surat)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <th>Nomor Surat</th>
                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                <th>Tanggal Diterima</th>
                <td>{{ $surat->tanggal_diterima ? \Carbon\Carbon::parse($surat->tanggal_diterima)->translatedFormat('d F Y') : '—' }}</td>
            </tr>
            <tr>
                <th>Perihal / Isi Ringkas</th>
                <td colspan="3">
                    <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 4px;">{{ $surat->perihal }}</div>
                    @if ($surat->keterangan)
                        <div style="font-size: 0.8rem; color: #4b5563;">{{ $surat->keterangan }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Instruksi Disposisi Kepala Sekolah / Pimpinan --}}
        <table class="table-disp">
            <tr>
                <th style="width: 50%;">DITERUSKAN KEPADA:</th>
                <th style="width: 50%;">DENGAN HORMAT HARAP:</th>
            </tr>
            <tr>
                <td style="padding: 12px;">
                    <div class="instruksi-grid">
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Kurikulum', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Kurikulum</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Kesiswaan', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Kesiswaan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Sarpras', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Sarpras</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Waka Hubin', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Waka Hubin</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Kepala TAS', $disposisiTujuan ?? []) || in_array('KTU', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Kepala TAS / Tata Usaha</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ in_array('Guru Piket', $disposisiTujuan ?? []) ? '✓' : '' }}</span>
                            <span>Guru Piket / Kesiswaan</span>
                        </div>
                    </div>

                    @if (!empty($disposisiTujuanLain))
                        <div style="margin-top: 10px; font-size: 0.8rem; border-top: 1px dashed #cbd5e1; padding-top: 6px;">
                            <strong>Personel Ditunjuk:</strong> {{ $disposisiTujuanLain }}
                        </div>
                    @endif
                </td>
                <td style="padding: 12px;">
                    <div class="instruksi-grid">
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Tanggapi / Tindak Lanjuti' ? '✓' : '' }}</span>
                            <span>Tanggapi / Tindak Lanjuti</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Hadir / Wakili' ? '✓' : '' }}</span>
                            <span>Hadir / Wakili</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Teliti & Laporkan' ? '✓' : '' }}</span>
                            <span>Teliti &amp; Laporkan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Koordinasikan' ? '✓' : '' }}</span>
                            <span>Koordinasikan</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Bicarakan Bersama' ? '✓' : '' }}</span>
                            <span>Bicarakan Bersama</span>
                        </div>
                        <div class="instruksi-item">
                            <span class="box-check">{{ ($disposisiInstruksi ?? '') === 'Arsipkan' ? '✓' : '' }}</span>
                            <span>Ketahui / Arsipkan</span>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th colspan="2">CATATAN / PETUNJUK KHUSUS PIMPINAN:</th>
            </tr>
            <tr>
                <td colspan="2" style="min-height: 80px; padding: 12px; font-size: 0.84rem; line-height: 1.45;">
                    {{ $disposisiCatatan ?: 'Segera koordinasikan dengan unit terkait dan laporkan hasilnya kepada Kepala Sekolah.' }}
                </td>
            </tr>
        </table>

        {{-- Tanda Tangan Pimpinan --}}
        <div class="signature-container">
            <div class="sig-box">
                <div class="sig-title">
                    Kepala Tenaga Administrasi (TAS),
                </div>
                <div class="sig-name">{{ $kepalaTas?->nama ?: 'Euis Nur Komariah, S.Pd.' }}</div>
                <div class="sig-nip">NIP: {{ $kepalaTas?->nip ?: ($kepalaTas?->nuptk ?: '—') }}</div>
            </div>

            <div class="sig-box">
                <div class="sig-title">
                    Kepala Satuan Pendidikan,
                </div>
                <div class="sig-name">{{ $kepsek?->nama ?: ($sekolah->nama_kepsek ?? 'Drs. H. Mulyadi, M.Pd.') }}</div>
                <div class="sig-nip">NIP: {{ $kepsek?->nip ?: ($sekolah->nip_kepsek ?? '—') }}</div>
            </div>
        </div>

        {{-- Footer Keabsahan --}}
        <div class="doc-footer">
            <div>
                <strong>Dokumen Sah Tata Naskah Dinas Sekolah — Sistem Aplikasi Edukasi (SAE)</strong><br>
                Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y H:i') }} WIB &bull; Status: {{ strtoupper($surat->status) }}
            </div>
            <div>
                Format A4 {{ ucfirst($orientasi) }}
            </div>
        </div>
    </div>

</body>

</html>
