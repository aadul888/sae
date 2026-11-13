@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Laporan Presensi Laboran (Resmi ber-Kop Surat).

    Dukungan:
    1. Tipe Laporan: Per Kelas / Jurusan vs Per Murid.
    2. Periode: Harian, Bulanan, Semester, Tahun Ajaran.
    3. Mode: Ringkasan (Rekap Rombel/Statistik) vs Lengkap (Tabel Rinci).

    Data disiapkan `LaboranLogPresensiController::cetak()`.
--}}

@section('doc_title')
    @if (($tipeLaporan ?? 'kelas') === 'murid')
        LAPORAN REKAPITULASI PRESENSI PESERTA DIDIK
    @else
        {{ $rombelObj ? 'LAPORAN PRESENSI — KELAS ' . strtoupper($rombelObj->nama) : 'LAPORAN PRESENSI PESERTA DIDIK' }}
    @endif
@endsection

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    @if (($tipeLaporan ?? 'kelas') === 'murid' && !empty($siswa))
        &bull; SISWA: {{ strtoupper($siswa->nama) }}
        &bull; KELAS: {{ strtoupper($siswa->nama_rombel ?: '-') }}
    @elseif ($rombelObj)
        &bull; KELAS: {{ strtoupper($rombelObj->nama) }}
    @endif
    &bull; PERIODE: {{ strtoupper($range['label'] ?? $tanggalFormat) }}
    &bull; TERCATAT: {{ ($tipeLaporan ?? 'kelas') === 'murid' ? ($logs->count() ?? 0) : ($items->count() ?? 0) }} LOG
@endsection

@section('toolbar')
    <div class="no-print-bar">
        <a href="{{ $backUrl ?? '#' }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        @if (($tipeLaporan ?? 'kelas') === 'kelas' && !empty($rombelList) && $rombelList->isNotEmpty())
            <div class="doc-toolbar-group">
                <select class="select-cetak" onchange="if(this.value){window.location.href=this.value;}"
                    title="Pilih kelas / rombel untuk dicetak">
                    <option value="{{ request()->fullUrlWithQuery(['rombel_id' => '']) }}"
                        {{ empty($rombelId) ? 'selected' : '' }}>
                        Semua Kelas (Jurusan)
                    </option>
                    @foreach ($rombelList as $r)
                        <option value="{{ request()->fullUrlWithQuery(['rombel_id' => $r->rombongan_belajar_id]) }}"
                            {{ $rombelId === (string) $r->rombongan_belajar_id ? 'selected' : '' }}>
                            Kelas: {{ $r->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @include('dashboard.laboran.partials.pilih-tampilan')

        @include('dashboard.laboran.partials.pilih-kaprog')

        <button type="button" onclick="window.SAE_Print ? window.SAE_Print() : window.print()"
            class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>
@endsection

@section('content')
    {{-- Profil Siswa jika tipe laporan murid --}}
    @if (($tipeLaporan ?? 'kelas') === 'murid' && !empty($siswa))
        <div class="info-box" style="margin-bottom: 14px;">
            <div>
                <div class="info-item">
                    <span class="info-label">Nama Siswa:</span>
                    <span class="info-val"><strong>{{ $siswa->nama }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">NISN / NIPD:</span>
                    <span class="font-mono">{{ $siswa->nisn ?: '-' }} / {{ $siswa->nipd ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Jenis Kelamin:</span>
                    <span class="info-val">{{ ($siswa->jenis_kelamin ?? '') === 'L' ? 'Laki-Laki' : (($siswa->jenis_kelamin ?? '') === 'P' ? 'Perempuan' : '-') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cakupan Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Kelas / Rombel:</span>
                    <span class="info-val"><strong>{{ $siswa->nama_rombel ?: '-' }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Wali Kelas:</span>
                    <span class="info-val">{{ $siswa->wali_nama ?: '-' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Periode Rekap:</span>
                    <span class="info-val"><strong>{{ $range['label'] }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Petugas Laboran:</span>
                    <span class="info-val">{{ $petugas['nama'] }}</span>
                </div>
            </div>
        </div>

        {{-- Kartu Ringkasan Statistik Presensi Siswa --}}
        <div class="section-subhead"><i class="fas fa-chart-pie"></i> A. Ringkasan Presensi Siswa</div>
        <table class="table-data is-ringkasan" style="margin-bottom: 18px;">
            <thead>
                <tr>
                    <th style="width: 80px;">Hadir (H)</th>
                    <th style="width: 100px;">Terlambat (T)</th>
                    <th style="width: 70px;">Izin (I)</th>
                    <th style="width: 70px;">Sakit (S)</th>
                    <th style="width: 70px;">Dispen (D)</th>
                    <th style="width: 70px;">Alpha (A)</th>
                    <th style="width: 110px;">Hari Efektif (HEB)</th>
                    <th style="width: 95px;">% Hadir</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="center font-bold" style="color: #16a34a;">{{ $stats['hadir'] }} hari</td>
                    <td class="center font-bold" style="color: #d97706;">
                        {{ $stats['terlambat'] }} hari
                        @if (!empty($stats['menit_terlambat']))
                            <div style="font-size: 7.5pt; font-weight: normal; color: #6b7280;">({{ $stats['menit_terlambat'] }}m)</div>
                        @endif
                    </td>
                    <td class="center font-bold">{{ $stats['izin'] }} hari</td>
                    <td class="center font-bold">{{ $stats['sakit'] }} hari</td>
                    <td class="center font-bold">{{ $stats['dispen'] }} hari</td>
                    <td class="center font-bold" style="color: #dc2626;">{{ $stats['alpha'] }} hari</td>
                    <td class="center font-mono font-bold">{{ $stats['hari_efektif_berjalan'] ?? $stats['total'] }} hari</td>
                    <td class="center font-mono font-bold" style="color: #16a34a; font-size: 10.5pt;">{{ $stats['persen'] }}%</td>
                </tr>
            </tbody>
        </table>

        {{-- Tabel Riwayat Harian Presensi Siswa --}}
        <div class="section-subhead"><i class="fas fa-list-check"></i> B. Riwayat Presensi Peserta Didik</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 85px;">Tanggal</th>
                    <th style="width: 75px;">Hari</th>
                    <th style="width: 70px;">Masuk</th>
                    <th style="width: 70px;">Pulang</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 75px;">Metode</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $i => $l)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ \Carbon\Carbon::parse($l->tanggal)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($l->tanggal)->translatedFormat('l') }}</td>
                        <td class="center font-mono">{{ $l->jam_masuk ? substr($l->jam_masuk, 0, 5) . ' WIB' : '—' }}</td>
                        <td class="center font-mono">{{ $l->jam_pulang ? substr($l->jam_pulang, 0, 5) . ' WIB' : '—' }}</td>
                        <td class="center">
                            @if ($l->status === 'H')
                                <strong style="color: #16a34a;">Hadir</strong>
                            @elseif ($l->status === 'T')
                                <strong style="color: #d97706;">Terlambat</strong>
                                @if (!empty($l->menit_terlambat))
                                    <span style="font-size: 8pt; color: #6b7280;">(+{{ $l->menit_terlambat }}m)</span>
                                @endif
                            @elseif ($l->status === 'I')
                                <strong style="color: #2563eb;">Izin</strong>
                            @elseif ($l->status === 'S')
                                <strong style="color: #7e22ce;">Sakit</strong>
                            @elseif ($l->status === 'D')
                                <strong style="color: #0284c7;">Dispen</strong>
                            @else
                                <strong style="color: #dc2626;">Alpha</strong>
                            @endif
                        </td>
                        <td class="center" style="font-size: 8pt; text-transform: uppercase;">
                            {{ $l->metode_masuk ?? 'manual' }}
                        </td>
                        <td style="font-size: 8.5pt;">{{ $l->keterangan ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center" style="padding: 16px; color: #6b7280;">
                            <em>Tidak ada catatan presensi pada rentang periode ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @else
        {{-- TIPE LAPORAN: PER KELAS / JURUSAN --}}
        <div class="info-box">
            <div>
                <div class="info-item">
                    <span class="info-label">Cakupan Jurusan:</span>
                    <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Periode Presensi:</span>
                    <span class="info-val"><strong>{{ $range['label'] ?? $tanggalFormat }}</strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Petugas Laboran:</span>
                    <span class="info-val">{{ $petugas['nama'] }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Filter Diterapkan:</span>
                    <span class="info-val">{{ $filterLabel ? implode(' • ', $filterLabel) : 'Semua data' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tingkat Kehadiran:</span>
                    <span class="info-val font-bold" style="color: #10b981;">
                        {{ $stats['persen'] }}%
                        @if (!empty($stats['total_siswa']))
                            ({{ $stats['hadir'] + $stats['terlambat'] }} Hadir / {{ $stats['total_siswa'] }} Siswa terdaftar)
                        @else
                            ({{ $stats['hadir'] + $stats['terlambat'] }} / {{ $stats['total'] }} Catatan)
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Cetak:</span>
                    <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
                </div>
            </div>
        </div>

        @if ($mode === 'ringkasan')
            <div class="section-subhead"><i class="fas fa-table-cells"></i> A. Rekapitulasi Presensi per Kelas / Rombel</div>
            <table class="table-data is-ringkasan">
                <thead>
                    <tr>
                        <th style="width: 35px;">No</th>
                        <th>Kelas / Rombel</th>
                        <th style="width: 65px;">Hadir</th>
                        <th style="width: 65px;">Terlambat</th>
                        <th style="width: 60px;">Izin</th>
                        <th style="width: 60px;">Sakit</th>
                        <th style="width: 60px;">Dispen</th>
                        <th style="width: 60px;">Alpha</th>
                        <th style="width: 75px;">Tercatat</th>
                        <th style="width: 85px;">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $i => $r)
                        @php
                            $hadirTotal = $r->hadir + $r->terlambat;
                            $persen = $r->total > 0 ? round(($hadirTotal / $r->total) * 100, 1) : 0;
                        @endphp
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $r->kelas }}</strong></td>
                            <td class="center" style="color: #16a34a; font-weight: bold;">{{ $r->hadir }}</td>
                            <td class="center" style="color: #d97706;">{{ $r->terlambat }}</td>
                            <td class="center">{{ $r->izin }}</td>
                            <td class="center">{{ $r->sakit }}</td>
                            <td class="center">{{ $r->dispen }}</td>
                            <td class="center" style="color: #dc2626;">{{ $r->alpha }}</td>
                            <td class="center font-bold">{{ $r->total }}</td>
                            <td class="center font-mono font-bold">{{ $persen }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="center" style="padding: 14px; color: #6b7280;">
                                <em>Tidak ada data presensi yang sesuai filter pada rentang ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="right">Total Tercatat</td>
                        <td class="center font-bold" style="color: #16a34a;">{{ $items->where('status', 'H')->count() }}</td>
                        <td class="center font-bold" style="color: #d97706;">{{ $items->where('status', 'T')->count() }}</td>
                        <td class="center font-bold">{{ $items->where('status', 'I')->count() }}</td>
                        <td class="center font-bold">{{ $items->where('status', 'S')->count() }}</td>
                        <td class="center font-bold">{{ $items->where('status', 'D')->count() }}</td>
                        <td class="center font-bold" style="color: #dc2626;">{{ $items->where('status', 'A')->count() }}</td>
                        <td class="center font-bold">{{ $items->count() }}</td>
                        <td class="center font-mono font-bold">
                            @php
                                $totalSemuaHadir = $items->whereIn('status', ['H', 'T'])->count();
                                $persenTotal = $items->count() > 0 ? round(($totalSemuaHadir / $items->count()) * 100, 1) : 0;
                            @endphp
                            {{ $persenTotal }}%
                        </td>
                    </tr>
                </tfoot>
            </table>

            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Mode <strong>Ringkasan</strong> menyajikan rekap presensi per kelas/rombel.
                Pilih <strong>Lengkap</strong> pada bar alat untuk melihat daftar rinci {{ $items->count() }} log presensi peserta didik
                lengkap dengan tanggal dan waktu tap masuk &amp; pulang.
            </div>
        @else
            <div class="section-subhead"><i class="fas fa-list-check"></i> B. Daftar Rinci Presensi Peserta Didik</div>
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        @if ($periode !== 'hari')
                            <th style="width: 75px;">Tanggal</th>
                        @endif
                        <th>Nama Peserta Didik</th>
                        <th style="width: 85px;">NISN</th>
                        <th style="width: 100px;">Kelas / Rombel</th>
                        <th style="width: 65px;">Masuk</th>
                        <th style="width: 65px;">Pulang</th>
                        <th style="width: 80px;">Status</th>
                        <th style="width: 65px;">Metode</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $i => $item)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            @if ($periode !== 'hari')
                                <td class="center font-mono">{{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}</td>
                            @endif
                            <td><strong>{{ $item->nama_siswa ?: '—' }}</strong></td>
                            <td class="center font-mono">{{ $item->nisn_siswa ?: '—' }}</td>
                            <td>{{ $item->nama_rombel ?: '—' }}</td>
                            <td class="center font-mono">
                                {{ $item->jam_masuk ? substr($item->jam_masuk, 0, 5) : '—' }}
                            </td>
                            <td class="center font-mono">
                                {{ $item->jam_pulang ? substr($item->jam_pulang, 0, 5) : '—' }}
                            </td>
                            <td class="center">
                                @if ($item->status === 'H')
                                    <strong style="color: #16a34a;">Hadir</strong>
                                @elseif ($item->status === 'T')
                                    <strong style="color: #d97706;">Terlambat</strong>
                                    @if (!empty($item->menit_terlambat))
                                        <span style="font-size: 8pt; color: #6b7280;">(+{{ $item->menit_terlambat }}m)</span>
                                    @endif
                                @elseif ($item->status === 'I')
                                    <strong style="color: #2563eb;">Izin</strong>
                                @elseif ($item->status === 'S')
                                    <strong style="color: #7e22ce;">Sakit</strong>
                                @elseif ($item->status === 'D')
                                    <strong style="color: #0284c7;">Dispen</strong>
                                @else
                                    <strong style="color: #dc2626;">Alpha</strong>
                                @endif
                            </td>
                            <td class="center" style="font-size: 8pt; text-transform: uppercase;">
                                {{ $item->metode_masuk ?: 'manual' }}
                            </td>
                            <td style="font-size: 8.5pt;">
                                {{ $item->keterangan ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $periode !== 'hari' ? 10 : 9 }}" class="center" style="padding: 14px; color: #6b7280;">
                                <em>Tidak ada data presensi yang sesuai filter pada rentang ini.</em>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endif
    @endif
@endsection
