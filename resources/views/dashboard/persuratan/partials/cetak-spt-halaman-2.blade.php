{{-- Halaman 2: Surat Perintah Perjalanan Dinas (SPPD) --}}
@include('partials.kop-surat')

<table class="sp-table sp-table--plain" style="margin-bottom: 6px;">
    <tr>
        <td style="width: 62%;"></td>
        <td style="width: 78px;">Lembar ke</td>
        <td style="width: 12px;">:</td>
        <td>1</td>
    </tr>
    <tr>
        <td></td>
        <td>Nomor</td>
        <td>:</td>
        <td>{{ $spt->nomor_spt }}</td>
    </tr>
</table>

<div class="sp-title">Surat Perintah Perjalanan Dinas (SPPD)</div>
<div class="sp-subtitle">&nbsp;</div>

<table class="sp-table">
    <tbody>
        <tr>
            <td style="width: 34px;" class="sp-center">1</td>
            <td style="width: 42%;">Pengguna Anggaran / Kuasa Pengguna Anggaran</td>
            <td>{{ $namaKepsek }}</td>
        </tr>
        <tr>
            <td class="sp-center">2</td>
            <td>Nama / NIP Pegawai yang melaksanakan perjalanan dinas</td>
            <td>
                @forelse ($gtkList as $g)
                    {{ $g->nama }} — NIP. {{ $g->nip ?: ($g->nuptk ?: '-') }}@if (!$loop->last)<br>@endif
                @empty
                    -
                @endforelse
            </td>
        </tr>
        <tr>
            <td class="sp-center">3</td>
            <td>a. Pangkat dan Golongan<br>b. Jabatan / Instansi<br>c. Tingkat Biaya Perjalanan Dinas</td>
            <td>
                @forelse ($gtkList as $g)
                    a. {{ $g->pangkat_golongan_terakhir ?: '-' }} &nbsp;&bull;&nbsp;
                    b. {{ $g->jabatan_ptk_id_str ?: ($g->jenis_ptk_id_str ?: 'Guru') }} / {{ $sekolah?->nama ?? 'Sekolah' }}
                    &nbsp;&bull;&nbsp; c. {{ $spt->tingkat_biaya ?: '-' }}@if (!$loop->last)<br>@endif
                @empty
                    a. - &nbsp;&bull;&nbsp; b. - &nbsp;&bull;&nbsp; c. {{ $spt->tingkat_biaya ?: '-' }}
                @endforelse
            </td>
        </tr>
        <tr>
            <td class="sp-center">4</td>
            <td>Maksud Perjalanan Dinas</td>
            <td>{{ $spt->nama_kegiatan }}{{ $spt->dasar_penugasan ? '. Dasar: ' . $spt->dasar_penugasan : '' }}</td>
        </tr>
        <tr>
            <td class="sp-center">5</td>
            <td>Alat Angkutan yang dipergunakan</td>
            <td>{{ $spt->alat_angkutan ?: 'Kendaraan Darat' }}</td>
        </tr>
        <tr>
            <td class="sp-center">6</td>
            <td>a. Tempat Berangkat<br>b. Tempat Tujuan</td>
            <td>
                a. {{ $spt->tempat_berangkat ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                b. {{ $spt->lokasi_tujuan }}{{ $spt->alamat_tujuan ? ' — ' . $spt->alamat_tujuan : '' }}
            </td>
        </tr>
        <tr>
            <td class="sp-center">7</td>
            <td>a. Lamanya Perjalanan Dinas<br>b. Tanggal Berangkat<br>c. Tanggal Harus Kembali</td>
            <td>
                a. {{ $spt->lama_hari }} hari<br>
                b. {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                c. {{ $tglKembali->translatedFormat('d F Y') }}
            </td>
        </tr>
        <tr>
            <td class="sp-center">8</td>
            <td>Pengikut</td>
            <td>{!! $spt->pengikut ? nl2br(e($spt->pengikut)) : '-' !!}</td>
        </tr>
        <tr>
            <td class="sp-center">9</td>
            <td>Pembebanan Anggaran<br>a. Instansi<br>b. Akun / Kodering</td>
            <td>
                a. {{ $spt->instansi_pembebanan ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                b. {{ $spt->akun_kodering ?: ($spt->beban_anggaran ?: '-') }}
            </td>
        </tr>
        <tr>
            <td class="sp-center">10</td>
            <td>Keterangan Lain</td>
            <td>{{ $spt->beban_anggaran ?: '-' }}</td>
        </tr>
    </tbody>
</table>

<div class="sp-ttd">
    Dikeluarkan di : {{ $kotaTtd }}<br>
    Tanggal : {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
    Kuasa Pengguna Anggaran,
    <div class="sp-space"></div>
    <div class="sp-sign">{{ $namaKepsek }}</div>
    <div>NIP. {{ $nipKepsek }}</div>
</div>

<table class="sp-table" style="margin-top: 8px;">
    <tbody>
        <tr>
            <td style="width: 50%;">
                <strong>I.</strong> Berangkat dari : {{ $spt->tempat_berangkat ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Ke : {{ $spt->lokasi_tujuan }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Hari / Tanggal : {{ $hariTanggal }}
            </td>
            <td style="width: 50%;" class="sp-center">
                {{ $kotaTtd }}, {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                Kepala,
                <div style="height: 14px;"></div>
                <strong>{{ $namaKepsek }}</strong><br>
                NIP. {{ $nipKepsek }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>II.</strong> Tiba di : {{ $spt->lokasi_tujuan }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : ................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Kepala,: ........................................
            </td>
            <td>
                <strong>II.</strong> Berangkat dari : {{ $spt->lokasi_tujuan }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Ke : {{ $spt->tempat_berangkat ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : ................................
            </td>
        </tr>
        <tr>
            <td>
                <strong>III.</strong> Tiba di : ................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : ................................
            </td>
            <td>
                <strong>III.</strong> Berangkat dari : ................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Ke : ................................
            </td>
        </tr>
        <tr>
            <td>
                <strong>IV.</strong> Tiba di : ................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : ................................
            </td>
            <td>
                <strong>IV.</strong> Berangkat dari : ................................<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Ke : ................................
            </td>
        </tr>
        <tr>
            <td>
                <strong>V.</strong> Tiba di : {{ $spt->tempat_berangkat ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                &nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : ................................
            </td>
            <td>
                <strong>Catatan Lain-lain</strong>
                <div style="height: 18px;"></div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 8pt;">
                <strong>VI. PERHATIAN.</strong> PA/KPA yang menerbitkan SPPD, pegawai yang melakukan perjalanan
                dinas, para pejabat yang mengesahkan tanggal berangkat/tiba, serta bendahara pengeluaran
                bertanggung jawab berdasarkan peraturan-peraturan keuangan Negara apabila Negara menderita rugi
                akibat kesalahan, kelalaian, dan kealpaannya.
            </td>
        </tr>
    </tbody>
</table>
