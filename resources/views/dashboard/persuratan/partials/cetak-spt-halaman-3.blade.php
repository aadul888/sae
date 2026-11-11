{{-- Halaman 3: Laporan Hasil Perjalanan Dinas --}}
@include('partials.kop-surat')

<div class="sp-title">Laporan Hasil Perjalanan Dinas</div>
<div class="sp-subtitle">&nbsp;</div>

<p style="margin: 0 0 10px 0;">Yang bertanda tangan di bawah ini bahwa saya :</p>

<table class="sp-table sp-table--plain" style="margin-bottom: 12px;">
    @forelse ($gtkList as $g)
        <tr>
            <td style="width: 140px;">Nama</td>
            <td style="width: 14px;">:</td>
            <td><strong>{{ $g->nama }}</strong></td>
        </tr>
        <tr>
            <td>NIP / NUPTK</td>
            <td>:</td>
            <td>{{ $g->nip ?: ($g->nuptk ?: '-') }}</td>
        </tr>
        <tr>
            <td>Pangkat / Golongan</td>
            <td>:</td>
            <td>{{ \App\Services\SuratSptService::resolveGtkPangkat($g) }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>:</td>
            <td>{{ $g->jabatan_ptk_id_str ?: ($g->jenis_ptk_id_str ?: 'Guru') }}</td>
        </tr>
        <tr>
            <td>Unit Kerja</td>
            <td>:</td>
            <td>{{ $sekolah?->nama ?? 'Sekolah' }}</td>
        </tr>
        @if (!$loop->last)
            <tr>
                <td colspan="3" style="height: 8px;"></td>
            </tr>
        @endif
    @empty
        <tr>
            <td colspan="3">Tidak ada personil terdaftar.</td>
        </tr>
    @endforelse
</table>

<p style="margin: 0 0 12px 0; text-align: justify;">
    Telah melaksanakan perjalanan dinas dalam rangka <strong>{{ $spt->nama_kegiatan }}</strong>, dengan
    Hari / Tanggal: <strong>{{ $hariTanggal }}</strong>, bertempat di
    <strong>{{ $spt->lokasi_tujuan }}</strong>.
</p>

<p style="margin: 0 0 8px 0;">Hasil dari perjalanan dinas tersebut adalah sebagai berikut :</p>

<table class="sp-table">
    <tr>
        <td style="height: 350px;">&nbsp;</td>
    </tr>
</table>

<p style="margin: 14px 0 0 0; text-align: justify;">
    Demikian laporan hasil perjalanan dinas ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya.
</p>

<table class="sp-table sp-table--plain" style="margin-top: 26px;">
    <tr>
        <td style="width: 50%; text-align: center; vertical-align: top;">
            Mengetahui,<br>
            Kepala Sekolah,
            <div class="sp-space"></div>
            <div class="sp-sign">{{ $namaKepsek }}</div>
            @if (!empty($pangkatGolKepsek))
                <div>{{ $pangkatGolKepsek }}</div>
            @endif
            <div>NIP. {{ $nipKepsek }}</div>
        </td>
        <td style="width: 50%; text-align: center; vertical-align: top;">
            {{ $kotaTtd }}, {{ $tglKembali->translatedFormat('d F Y') }}<br>
            Yang melaksanakan tugas,
            <div class="sp-space"></div>
            @php $firstGtk = $gtkList->first(); @endphp
            <div class="sp-sign">{{ $firstGtk->nama ?? '-' }}</div>
            @php $firstPangkat = \App\Services\SuratSptService::resolveGtkPangkat($firstGtk); @endphp
            @if (!empty($firstPangkat) && $firstPangkat !== '-')
                <div>{{ $firstPangkat }}</div>
            @endif
            <div>NIP. {{ $firstGtk->nip ?? ($firstGtk->nuptk ?? '-') }}</div>
        </td>
    </tr>
</table>

@if (!empty($qrUri))
    <div class="spt-footer-verify">
        <div class="qr-box">
            <img src="{{ $qrUri }}" alt="QR Code Verifikasi">
            <div>
                <strong>Laporan Perjalanan Dinas — Sistem Aplikasi Edukasi (SAE)</strong><br>
                ID Dokumen: <code>{{ $docId }}</code> &bull; Pindai QR Code untuk verifikasi keabsahan laporan.
            </div>
        </div>
        <div style="text-align: right;">
            Halaman 3 / 3<br>
            Format Kertas: A4 Portrait
        </div>
    </div>
@endif
