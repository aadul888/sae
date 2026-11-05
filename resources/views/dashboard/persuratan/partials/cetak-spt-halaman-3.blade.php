{{-- Halaman 3: Laporan Hasil Perjalanan Dinas --}}
@include('partials.kop-surat')

<div class="sp-title">Laporan Hasil Perjalanan Dinas</div>
<div class="sp-subtitle">&nbsp;</div>

<p style="margin: 0 0 10px 0;">Yang bertanda tangan di bawah ini bahwa saya :</p>

<table class="sp-table sp-table--plain" style="margin-bottom: 12px;">
    @forelse ($gtkList as $g)
        <tr>
            <td style="width: 120px;">Nama</td>
            <td style="width: 14px;">:</td>
            <td>{{ $g->nama }}</td>
        </tr>
        <tr>
            <td>NIP / NUPTK</td>
            <td>:</td>
            <td>{{ $g->nip ?: ($g->nuptk ?: '-') }}</td>
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
        <td style="height: 210px;">&nbsp;</td>
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
            <div>NIP. {{ $nipKepsek }}</div>
        </td>
        <td style="width: 50%; text-align: center; vertical-align: top;">
            {{ $kotaTtd }}, {{ $tglKembali->translatedFormat('d F Y') }}<br>
            Yang melaksanakan tugas,
            <div class="sp-space"></div>
            <div class="sp-sign">{{ $gtkList->first()->nama ?? '-' }}</div>
            <div>NIP. {{ $gtkList->first()->nip ?? ($gtkList->first()->nuptk ?? '-') }}</div>
        </td>
    </tr>
</table>
