{{-- Halaman 1: Surat Perintah Tugas (SPT) --}}
@include('partials.kop-surat')

<div class="sp-title">Surat Perintah Tugas</div>
<div class="sp-subtitle">Nomor: {{ $spt->nomor_spt }}</div>

<table class="sp-table sp-table--plain" style="margin-bottom: 14px;">
    <tr>
        <td style="width: 120px; vertical-align: top;">Dasar</td>
        <td style="width: 14px; vertical-align: top;">:</td>
        <td style="vertical-align: top;">{{ $spt->dasar_penugasan ?: '-' }}</td>
    </tr>
</table>

<div style="font-weight: 700; text-align: center; text-transform: uppercase; letter-spacing: 0.5px; margin: 12px 0 8px 0;">
    Memerintahkan
</div>

<table class="sp-table">
    <thead>
        <tr>
            <th style="width: 34px;">No</th>
            <th>Nama Lengkap</th>
            <th style="width: 110px;">Pangkat / Gol</th>
            <th style="width: 140px;">Jabatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($gtkList as $idx => $g)
            <tr>
                <td class="sp-center">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $g->nama }}</strong><br>
                    <small class="sp-muted">NIP. {{ $g->nip ?: ($g->nuptk ?: '-') }}</small>
                </td>
                <td class="sp-center">{{ $g->pangkat_golongan_terakhir ?: '-' }}</td>
                <td>{{ $g->jabatan_ptk_id_str ?: ($g->jenis_ptk_id_str ?: 'Guru') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="sp-center">Tidak ada personil terdaftar.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="sp-table sp-table--plain" style="margin-top: 14px;">
    <tr>
        <td style="width: 120px; vertical-align: top;">Untuk</td>
        <td style="width: 14px; vertical-align: top;">:</td>
        <td style="vertical-align: top;">
            {{ $spt->nama_kegiatan }}, yang akan dilaksanakan pada :
            <table class="sp-table sp-table--plain" style="margin-top: 6px;">
                <tr>
                    <td style="width: 120px;">Hari / Tanggal</td>
                    <td style="width: 14px;">:</td>
                    <td>{{ $hariTanggal }}</td>
                </tr>
                <tr>
                    <td>Waktu</td>
                    <td>:</td>
                    <td>{{ $spt->waktu_pelaksanaan ?: 'Sesuai jadwal kegiatan' }}</td>
                </tr>
                <tr>
                    <td>Tempat</td>
                    <td>:</td>
                    <td>{{ $spt->lokasi_tujuan }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $spt->alamat_tujuan ?: $spt->lokasi_tujuan }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="sp-ttd">
    {{ $kotaTtd }}, {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
    Kepala Sekolah,
    <div class="sp-space"></div>
    <div class="sp-sign">{{ $namaKepsek }}</div>
    <div>NIP. {{ $nipKepsek }}</div>
</div>
