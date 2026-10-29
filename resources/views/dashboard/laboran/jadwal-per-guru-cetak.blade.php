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
            {{-- B. Rincian sesi — hanya pada versi LENGKAP --}}
            @if ($rentang === 'semester')
                <div class="section-subhead"><i class="fas fa-calendar-days"></i> C. Rincian Sesi per Bulan</div>
                @foreach ($perBulan as $blok)
                    <div class="section-subhead" style="margin-top: 10px;">
                        <i class="fas fa-calendar"></i> {{ $blok['label'] }}
                        &bull; {{ count($blok['rows']) }} sesi
                    </div>
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th style="width: 40px;">No</th>
                                <th style="width: 110px;">Tanggal</th>
                                <th style="width: 110px;">Jam</th>
                                <th>Lokasi Praktik</th>
                                <th style="width: 110px;">Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th style="width: 150px;">Guru</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($blok['rows'] as $i => $r)
                                <tr>
                                    <td class="center font-mono">{{ $i + 1 }}</td>
                                    <td class="center">
                                        {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d/m/Y') }}
                                        <div style="color: #6b7280;">
                                            {{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l') }}
                                        </div>
                                    </td>
                                    <td class="center font-mono">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $r->ruang_lab_nama ?: '—' }}</td>
                                    <td class="center">{{ $r->rombel_nama ?: '—' }}</td>
                                    <td>{{ $r->mata_pelajaran ?: '—' }}</td>
                                    <td>
                                        {{ $r->guru_nama ?: '—' }}
                                        @if (!empty($r->guru_pendamping))
                                            <div style="color: #6b7280;">+ {{ $r->guru_pendamping }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            @elseif ($rentang === 'mingguan')
                <div class="section-subhead"><i class="fas fa-calendar-week"></i> C. Rincian Sesi per Hari</div>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th style="width: 110px;">Jam</th>
                            <th>Lokasi Praktik</th>
                            <th style="width: 110px;">Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th style="width: 150px;">Guru</th>
                            <th>Topik / Materi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($perHari as $hari => $rows)
                            @continue($rows->isEmpty())
                            <tr>
                                <td colspan="6" style="font-weight: 700; background: #f1f5f9;">
                                    {{ strtoupper($hari) }}
                                </td>
                            </tr>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="center font-mono">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                                    <td>
                                        {{ $r->ruang_lab_nama ?: '—' }}
                                        <div style="color: #6b7280;">{{ $jenisLabels[$r->jenis_lokasi] ?? '' }}</div>
                                    </td>
                                    <td class="center">{{ $r->rombel_nama ?: '—' }}</td>
                                    <td>{{ $r->mata_pelajaran ?: '—' }}</td>
                                    <td>
                                        {{ $r->guru_nama ?: '—' }}
                                        @if (!empty($r->guru_pendamping))
                                            <div style="color: #6b7280;">+ {{ $r->guru_pendamping }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $r->topik_praktik ?: '—' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="section-subhead"><i class="fas fa-calendar-days"></i> C. Rincian Sesi per Tanggal</div>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 110px;">Jam</th>
                            <th>Lokasi Praktik</th>
                            <th style="width: 110px;">Kelas</th>
                            <th>Mata Pelajaran</th>
                            <th style="width: 150px;">Guru</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 0; @endphp
                        @foreach ($perTanggal as $tgl => $rows)
                            @foreach ($rows as $r)
                                @php $no++; @endphp
                                <tr>
                                    <td class="center font-mono">{{ $no }}</td>
                                    <td class="center">
                                        {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d/m/Y') }}
                                        <div style="color: #6b7280;">
                                            {{ \Carbon\Carbon::parse($tgl)->translatedFormat('l') }}
                                        </div>
                                    </td>
                                    <td class="center font-mono">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                                    <td>{{ $r->ruang_lab_nama ?: '—' }}</td>
                                    <td class="center">{{ $r->rombel_nama ?: '—' }}</td>
                                    <td>{{ $r->mata_pelajaran ?: '—' }}</td>
                                    <td>
                                        {{ $r->guru_nama ?: '—' }}
                                        @if (!empty($r->guru_pendamping))
                                            <div style="color: #6b7280;">+ {{ $r->guru_pendamping }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @endif
@endsection
