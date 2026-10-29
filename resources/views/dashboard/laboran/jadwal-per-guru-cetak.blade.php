@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Jadwal Praktik per Guru.

    Disamakan dengan dokumen Laboran lainnya:
      • memakai layout bersama `layouts.laboran-cetak` (A4, kop, bar alat,
        tanda tangan 3 pihak, footer bernomor cetak)
      • tersedia dua VERSI: `ringkasan` (rekap) dan `lengkap` (rincian)
      • cakupan waktu tetap: mingguan | bulanan | semester

    Data disiapkan `LaboranJadwalLaporanService::buildJadwalPerGuru()`.
--}}

@section('doc_title', 'JADWAL PRAKTIK PER GURU')

@section('doc_subtitle')
    {{ strtoupper($label) }}
    &bull; {{ strtoupper($rentang) }}
    &bull; {{ $totalSesi }} SESI
@endsection

@section('content')
    <div class="info-box">
        <div>
            <div class="info-item">
                <span class="info-label">Cakupan Jurusan:</span>
                <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Periode:</span>
                <span class="info-val">{{ $label }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Guru:</span>
                <span class="info-val">
                    @if ($guru)
                        {{ $guru->nama }}{{ !empty($guru->nip) ? ' (NIP ' . $guru->nip . ')' : '' }}
                    @else
                        Seluruh Guru
                    @endif
                </span>
            </div>
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Total Sesi:</span>
                <span class="info-val"><strong>{{ $totalSesi }}</strong> sesi</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Durasi:</span>
                <span class="info-val">{{ intdiv($totalMenit, 60) }} jam {{ $totalMenit % 60 }} menit</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    @if ($totalSesi === 0)
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Tidak ada jadwal praktik pada periode ini.
        </div>
    @else
        {{-- A. Rekap selalu tampil pada kedua versi --}}
        <div class="section-subhead"><i class="fas fa-user-tie"></i> A. Rekapitulasi Beban Mengajar per Guru</div>
        <table class="table-data is-ringkasan">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Guru</th>
                    <th style="width: 90px;">Sesi</th>
                    <th style="width: 90px;">Hari</th>
                    <th style="width: 120px;">Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapGuru as $i => $rg)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $rg->nama }}</strong></td>
                        <td class="center font-bold">{{ $rg->sesi }}</td>
                        <td class="center">{{ $rg->hari }}</td>
                        <td class="center">{{ intdiv($rg->menit, 60) }}j {{ $rg->menit % 60 }}m</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada data guru pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="right">Jumlah</td>
                    <td class="center">{{ $totalSesi }}</td>
                    <td class="center">{{ $hariAktif }}</td>
                    <td class="center">{{ intdiv($totalMenit, 60) }}j {{ $totalMenit % 60 }}m</td>
                </tr>
            </tfoot>
        </table>

        <div class="section-subhead"><i class="fas fa-location-dot"></i> B. Rekapitulasi Penggunaan Lokasi Praktik</div>
        <table class="table-data is-ringkasan">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Lokasi Praktik</th>
                    <th style="width: 90px;">Sesi</th>
                    <th style="width: 90px;">Hari</th>
                    <th style="width: 120px;">Durasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekapLokasi as $i => $rl)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $rl->nama }}</strong></td>
                        <td class="center font-bold">{{ $rl->sesi }}</td>
                        <td class="center">{{ $rl->hari }}</td>
                        <td class="center">{{ intdiv($rl->menit, 60) }}j {{ $rl->menit % 60 }}m</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada data lokasi pada periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="right">Jumlah</td>
                    <td class="center">{{ $totalSesi }}</td>
                    <td class="center">{{ $hariAktif }}</td>
                    <td class="center">{{ intdiv($totalMenit, 60) }}j {{ $totalMenit % 60 }}m</td>
                </tr>
            </tfoot>
        </table>

        @if ($mode === 'ringkasan')
            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> memuat rekap beban mengajar dan penggunaan lokasi.
                Pilih <strong>Lengkap</strong> pada bar alat di atas untuk daftar rinci
                {{ $totalSesi }} sesi praktik beserta jam, kelas, dan materi.
            </div>
        @else
            {{-- C. Matriks JAM × TANGGAL gaya jadwal KBM.
                 Mingguan → satu blok per pekan; Semester → satu blok per bulan;
                 Bulanan → satu blok. Semua blok tercetak dalam satu dokumen. --}}
            <div class="section-subhead"><i class="fas fa-table-cells"></i> C. Matriks Jadwal Praktik</div>

            @foreach ($blokMinggu as $blok)
                @if ($blok['label'] !== '')
                    <div class="section-subhead" style="margin-top: 10px;">
                        <i class="fas fa-calendar-week"></i> {{ $blok['label'] }}
                    </div>
                @endif

                <table class="table-data jadwal-praktik-grid">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Jam</th>
                            @foreach ($blok['tanggal'] as $t)
                                <th class="center {{ $t['is_libur'] ? 'is-libur' : '' }}"
                                    style="min-width: 74px;">
                                    <div style="font-weight: 800;">{{ (int) substr($t['tanggal'], 8, 2) }}</div>
                                    <div style="font-weight: 500;">{{ strtoupper($t['hari']) }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blok['slot'] as $jam)
                            <tr>
                                <td class="center font-mono font-bold">{{ $jam }}</td>
                                @foreach ($blok['tanggal'] as $t)
                                    @php $sesi = $blok['matriks'][$t['tanggal']][$jam] ?? []; @endphp
                                    <td class="{{ $t['is_libur'] ? 'is-libur' : '' }}"
                                        style="vertical-align: top; padding: 4px;">
                                        @foreach ($sesi as $r)
                                            <div style="margin-bottom: 4px; line-height: 1.25;">
                                                <div style="font-weight: 700;">{{ $r->mata_pelajaran ?: '—' }}</div>
                                                <div style="color: #6b7280;">{{ $r->ruang_lab_nama ?: '—' }}</div>
                                                <div style="color: #6b7280;">{{ $r->rombel_nama ?: '' }}</div>
                                                <div style="color: #6b7280;">{{ $r->guru_nama ?: '' }}</div>
                                            </div>
                                        @endforeach
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($blok['tanggal']) + 1 }}" class="center"
                                    style="padding: 14px; color: #6b7280;">
                                    <em>Tidak ada sesi praktik pada periode ini.</em>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach
        @endif
    @endif
@endsection
