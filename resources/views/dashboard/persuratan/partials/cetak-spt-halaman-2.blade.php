{{-- Halaman 2: Surat Perintah Perjalanan Dinas (SPPD) 2-Kolom Landscape Format Resmi DOCX --}}
<div class="sppd-grid">
    {{-- KOLOM KIRI: Lembar Depan SPPD (10 Poin Rincian + TTD KPA) --}}
    <div class="sppd-col sppd-col-left">
        @include('partials.kop-surat')

        <table class="sppd-meta-table">
            <tr>
                <td style="width: 72px;">Lembar ke</td>
                <td style="width: 10px;">:</td>
                <td>1</td>
            </tr>
            <tr>
                <td>Nomor</td>
                <td>:</td>
                <td>{{ $spt->nomor_spt }}</td>
            </tr>
        </table>

        <div class="sppd-title">SURAT PERINTAH PERJALANAN DINAS (SPPD)</div>

        <table class="sppd-table">
            <tbody>
                <tr>
                    <td style="width: 18px; text-align: center;">1</td>
                    <td style="width: 44%;">Pengguna Anggaran / Kuasa Pengguna Anggaran</td>
                    <td colspan="2">{{ $namaKepsek }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td>Nama/NIP Pegawai Yang melaksanakan perjalanan dinas</td>
                    <td colspan="2">
                        @forelse ($gtkList as $g)
                            <div><strong>{{ $g->nama }}</strong></div>
                            <div>NIP. {{ $g->nip ?: ($g->nuptk ?: '-') }}</div>
                        @empty
                            -
                        @endforelse
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td>
                        a. Pangkat dan Golongan<br>
                        b. Jabatan/Intansi<br>
                        c. Tingkat Biaya Perjalanan Dinas
                    </td>
                    <td colspan="2">
                        @php
                            $firstGtk = $gtkList->first();
                            $rawGolGtk = trim($firstGtk?->pangkat_golongan_terakhir ?? '');
                            $golGtk = $pangkatMap[$rawGolGtk] ?? ($rawGolGtk ?: '-');
                            $jabGtk = $firstGtk?->jabatan_ptk_id_str ?: ($firstGtk?->jenis_ptk_id_str ?: 'Guru');
                        @endphp
                        a. {{ $golGtk }}<br>
                        b. {{ $jabGtk }} / {{ $sekolah?->nama ?? 'Sekolah' }}<br>
                        c. {{ $spt->tingkat_biaya ?: '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">4</td>
                    <td>Maksud Perjalanan Dinas</td>
                    <td colspan="2">{{ $spt->nama_kegiatan }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">5</td>
                    <td>Alat Angkutan Yang dipergunakan</td>
                    <td colspan="2">{{ $spt->alat_angkutan ?: 'Kendaraan Darat' }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">6</td>
                    <td>
                        a. Tempat Berangkat<br>
                        b. Tempat Tujuan
                    </td>
                    <td colspan="2">
                        a. {{ $tempatBerangkat }}<br>
                        b. {{ $spt->lokasi_tujuan }}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">7</td>
                    <td>
                        a. Lamanya Perjalanan Dinas<br>
                        b. Tanggal Berangkat<br>
                        c. Tanggal Harus Kembali
                    </td>
                    <td colspan="2">
                        a. {{ $spt->lama_hari }} hari<br>
                        b. {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                        c. {{ $tglKembali->translatedFormat('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">8</td>
                    <td style="width: 28%;">Pengikut:{!! $spt->pengikut ? '<br>' . nl2br(e($spt->pengikut)) : '' !!}</td>
                    <td style="width: 26%;">Tanggal Lahir:</td>
                    <td>Keterangan:</td>
                </tr>
                <tr>
                    <td style="text-align: center;">9</td>
                    <td>
                        Pembebanan Anggaran<br>
                        a. Instansi<br>
                        b. Akun/Kodering
                    </td>
                    <td colspan="2">
                        a. {{ $spt->instansi_pembebanan ?: ($sekolah?->nama ?? 'Sekolah') }}<br>
                        b. {{ $spt->akun_kodering ?: ($spt->beban_anggaran ?: '-') }}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center;">10</td>
                    <td>Keterangan Lain</td>
                    <td colspan="2">{{ $spt->beban_anggaran ?: '-' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="sppd-ttd-kpa">
            Dikeluarkan di : {{ $kotaTtd }}<br>
            Tanggal : {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
            Kuasa Pengguna Anggaran
            <div style="height: 52px;"></div>
            <strong><u>{{ strtoupper($namaKepsek) }}</u></strong><br>
            @if (!empty($pangkatGolKepsek))
                {{ $pangkatGolKepsek }}<br>
            @endif
            NIP. {{ $nipKepsek }}
        </div>
    </div>

    {{-- KOLOM KANAN: Lembar Belakang / Visum Riwayat Perjalanan (Tabel I s.d. VI) --}}
    <div class="sppd-col sppd-col-right">
        <table class="sppd-table-visum">
            <tbody>
                {{-- Baris I --}}
                <tr class="visum-row visum-row-compact">
                    <td style="width: 48%;"></td>
                    <td style="width: 52%;">
                        <strong>I.</strong>&nbsp;&nbsp;Berangkat dari : {{ $tempatBerangkat }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ke : {{ $spt->lokasi_tujuan }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                        <div style="margin-top: 3px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala</div>
                        <div style="height: 38px;"></div>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><u>{{ strtoupper($namaKepsek) }}</u></strong><br>
                        @if (!empty($pangkatGolKepsek))
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{{ $pangkatGolKepsek }}<br>
                        @endif
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;NIP. {{ $nipKepsek }}
                    </td>
                </tr>

                {{-- Baris II --}}
                <tr class="visum-row">
                    <td style="position: relative;">
                        <strong>II.</strong>&nbsp;&nbsp;Tiba di : {{ $spt->lokasi_tujuan }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #b0b0b0; font-size: 10pt; line-height: 1; pointer-events: none; user-select: none;">&#10003;</div>
                        <div style="height: 48px;"></div>
                    </td>
                    <td style="position: relative;">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Berangkat dari : {{ $spt->lokasi_tujuan }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ke : {{ $tempatBerangkat }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : {{ $tglBerangkat->translatedFormat('d F Y') }}<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #b0b0b0; font-size: 10pt; line-height: 1; pointer-events: none; user-select: none;">&#10003;</div>
                        <div style="height: 48px;"></div>
                    </td>
                </tr>

                {{-- Baris III --}}
                <tr class="visum-row">
                    <td>
                        <strong>III.</strong>&nbsp;Tiba di :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal :<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="height: 48px;"></div>
                    </td>
                    <td>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Berangkat dari :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ke :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal :<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="height: 48px;"></div>
                    </td>
                </tr>

                {{-- Baris IV --}}
                <tr class="visum-row">
                    <td>
                        <strong>IV.</strong>&nbsp;Tiba di :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal :<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="height: 48px;"></div>
                    </td>
                    <td>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Berangkat dari :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ke :<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal :<br>
                        <div style="margin-top: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="height: 48px;"></div>
                    </td>
                </tr>

                {{-- Baris V --}}
                <tr class="visum-row visum-row-compact">
                    <td>
                        <strong>V.</strong>&nbsp;&nbsp;Tiba di : {{ $tempatBerangkat }}<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Pada Tanggal : {{ $tglKembali->translatedFormat('d F Y') }}<br>
                        <div style="margin-top: 3px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kepala :</div>
                        <div style="height: 38px;"></div>
                    </td>
                    <td>
                        <div style="font-size: 7pt; line-height: 1.15; text-align: justify; margin-bottom: 2px;">
                            Telah diperiksa dengan keterangan bahwa perjalanan tersebut atas perintahnya dan semata-mata untuk kepentingan jabatan dalam waktu yang sesingkat-singkatnya
                        </div>
                        <div style="font-size: 7.5pt; margin-top: 2px;">Kuasa pengguna anggaran</div>
                        <div style="height: 38px;"></div>
                        <strong><u>{{ strtoupper($namaKepsek) }}</u></strong><br>
                        @if (!empty($pangkatGolKepsek))
                            {{ $pangkatGolKepsek }}<br>
                        @endif
                        NIP. {{ $nipKepsek }}
                    </td>
                </tr>

                {{-- Baris Catatan --}}
                <tr>
                    <td style="padding: 5px 6px;"><strong>Catatan Lain-lain</strong></td>
                    <td style="padding: 5px 6px;"></td>
                </tr>

                {{-- Baris VI. Perhatian --}}
                <tr>
                    <td colspan="2" style="padding: 4px 6px;">
                        <strong>VI. PERHATIAN</strong>
                        <div style="font-size: 6.8pt; line-height: 1.15; text-align: justify; margin-top: 1px;">
                            PA/KPA Yang menerbitkan SPPD, pegawai yang melakukan perjalanan dinas, para pejabat yang mengesahkan tanggal berangkat/tiba, serta bendahara pengeluaran bertanggung jawab berdasarkan peraturan-peraturan keuangan Negara apabila Negara menderita rugi akibat kesalahan, kelalaian dan kealpaannya.
                        </div>
                        @if (!empty($qrUri))
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #94a3b8; font-size: 6.5pt; color: #475569;">
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <img src="{{ $qrUri }}" alt="QR Verifikasi" style="width: 26px; height: 26px; border: 1px solid #cbd5e1; padding: 1px; background: #fff;">
                                    <span>Pindai QR Code untuk verifikasi keabsahan SPPD (ID: <code>{{ $docId }}</code>)</span>
                                </div>
                                <div>Lembar 2 (Landscape)</div>
                            </div>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
