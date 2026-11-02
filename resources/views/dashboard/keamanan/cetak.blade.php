@extends('layouts.cetak')

@section('title', $judul)

@section('content')
    <div class="sheet a4-landscape" style="padding: 15mm 12mm; font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 14px 0 16px 0;">
            <h2 style="margin: 0; font-size: 13pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline;">
                {{ $judul }}
            </h2>
            @if (!empty($filterInfo))
                <div style="font-size: 9pt; margin-top: 3px; font-weight: 600; color: #334155;">{{ $filterInfo }}</div>
            @endif
            <div style="font-size: 8.5pt; margin-top: 2px; color: #64748b;">
                Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &bull; Jumlah data: {{ number_format($rows->count(), 0, ',', '.') }}
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; font-size: 8pt;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    @if ($modul === 'tamu')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px; width: 60px;">Jam</th>
                        <th style="padding: 4px;">Nama Tamu</th>
                        <th style="padding: 4px;">Instansi / Asal</th>
                        <th style="padding: 4px;">Tujuan / Keperluan</th>
                        <th style="padding: 4px; width: 80px;">Status</th>
                    @elseif ($modul === 'pelanggaran')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Nama Siswa</th>
                        <th style="padding: 4px; width: 70px;">NISN</th>
                        <th style="padding: 4px;">Tempat</th>
                        <th style="padding: 4px; width: 45px;">Poin</th>
                        <th style="padding: 4px;">Keterangan</th>
                    @elseif ($modul === 'izin')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Nama Siswa</th>
                        <th style="padding: 4px; width: 90px;">Jenis Izin</th>
                        <th style="padding: 4px;">Alasan</th>
                        <th style="padding: 4px; width: 55px;">Keluar</th>
                        <th style="padding: 4px; width: 55px;">Kembali</th>
                        <th style="padding: 4px; width: 80px;">Status</th>
                    @elseif ($modul === 'patroli')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px; width: 55px;">Jam</th>
                        <th style="padding: 4px; width: 130px;">Rute / Zona</th>
                        <th style="padding: 4px; width: 110px;">Kondisi Lingkungan</th>
                        <th style="padding: 4px;">Catatan Temuan</th>
                        <th style="padding: 4px; width: 110px;">Petugas</th>
                    @elseif ($modul === 'insiden')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 110px;">No. Laporan</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Judul Insiden</th>
                        <th style="padding: 4px; width: 100px;">Lokasi</th>
                        <th style="padding: 4px; width: 65px;">Urgensi</th>
                        <th style="padding: 4px;">Tindakan</th>
                        <th style="padding: 4px; width: 95px;">Status</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $idx => $r)
                    <tr>
                        <td style="padding: 3px; text-align: center;">{{ $idx + 1 }}</td>
                        @if ($modul === 'tamu')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_masuk, 0, 5) }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_tamu }}</td>
                            <td style="padding: 3px;">{{ $r->instansi_asal ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->keperluan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->status === 'sudah_keluar' ? 'Keluar' : 'Di Lokasi' }}</td>
                        @elseif ($modul === 'pelanggaran')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal_kejadian)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_siswa ?? '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->nisn ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->tempat_kejadian ?: '—' }}</td>
                            <td style="padding: 3px; text-align: center; font-weight: 700;">{{ (int) $r->poin }}</td>
                            <td style="padding: 3px;">{{ $r->keterangan ?: '—' }}</td>
                        @elseif ($modul === 'izin')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_siswa ?? '—' }}</td>
                            <td style="padding: 3px;">{{ ucfirst(str_replace('_', ' ', $r->jenis_izin)) }}</td>
                            <td style="padding: 3px;">{{ $r->alasan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_izin_keluar, 0, 5) }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->jam_kembali_aktual ? \Illuminate\Support\Str::substr((string) $r->jam_kembali_aktual, 0, 5) : '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                        @elseif ($modul === 'patroli')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_patroli, 0, 5) }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->rute_zona }}</td>
                            <td style="padding: 3px;">{{ ucfirst(str_replace('_', ' ', $r->kondisi_lingkungan)) }}</td>
                            <td style="padding: 3px;">{{ $r->catatan_temuan ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->nama_petugas ?: '—' }}</td>
                        @elseif ($modul === 'insiden')
                            <td style="padding: 3px; text-align: center; font-family: monospace;">{{ $r->nomor_laporan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->judul_insiden }}</td>
                            <td style="padding: 3px;">{{ $r->lokasi_kejadian }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst($r->tingkat_urgensi) }}</td>
                            <td style="padding: 3px;">{{ $r->tindakan_diambil ?: '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst(str_replace('_', ' ', $r->status_penyelesaian)) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 14px; text-align: center; color: #64748b;">Tidak ada data pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table style="width: 100%; border: none; font-size: 9pt; text-align: center; margin-top: 15px;">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $kepalaSekolah->nip ?? '—' }}</span>
                </td>
                <td style="width: 34%; vertical-align: top;">&nbsp;</td>
                <td style="width: 33%; vertical-align: top;">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Koordinator Keamanan / Satpam</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $koordinator->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $koordinator->nip ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
