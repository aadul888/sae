<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Agenda Kelas — {{ $activeRombel->nama ?? 'Kelas Binaan' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>
    <div class="no-print-bar">
        <a href="{{ route('dashboard.wali-kelas.agenda.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper">
        <!-- Watermark Logo SAE -->
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE">
        </div>

        <!-- Header Dokumen Bersih -->
        <div class="doc-header">
            <div class="school-name">{{ $sekolah->nama ?? 'SATUAN PENDIDIKAN' }}</div>
            <div class="doc-title-main">JURNAL &amp; AGENDA KEGIATAN KELAS BINAAN</div>
            <div class="doc-subtitle">Tahun Ajaran: {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}</div>
        </div>

        <!-- Meta Info Rombel & Wali Kelas -->
        <div class="doc-meta">
            <div>
                <div class="meta-row">
                    <span class="meta-label">Rombongan Belajar</span>
                    <span>: <strong>{{ $activeRombel->nama ?? '-' }}</strong></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Wali Kelas</span>
                    <span>: {{ $waliNama ?? '-' }}</span>
                </div>
            </div>
            <div>
                <div class="meta-row">
                    <span class="meta-label">NIP / NUPTK Wali</span>
                    <span>: {{ $waliNip ?? '-' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Tanggal Cetak</span>
                    <span>: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Tabel Agenda Kelas -->
        <table class="kbm-table">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="width: 75px;">Hari, Tanggal</th>
                    <th style="width: 45px;">Jam Ke</th>
                    <th style="width: 40px;">Pert.</th>
                    <th style="width: 105px;">Guru Pengampu</th>
                    <th style="width: 110px;">Mata Pelajaran</th>
                    <th>Materi Pokok &amp; Indikator</th>
                    <th>Kegiatan &amp; Penugasan</th>
                    <th style="width: 75px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $idx => $item)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->hari }}</strong><br>
                            <span style="font-size: 0.72rem; color: #555;">
                                {{ $item->tanggal ? $item->tanggal->translatedFormat('d/m/Y') : '-' }}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 700;">
                            {{ $item->jam_ke_mulai }}-{{ $item->jam_ke_selesai }}
                        </td>
                        <td style="text-align: center; font-weight: 700;">
                            #{{ $item->pertemuan_ke }}
                        </td>
                        <td>
                            <strong>{{ $item->nama_guru ?: '-' }}</strong>
                            @if (!empty($item->created_by))
                                <div style="font-size: 0.65rem; color: #777;">(Oleh: {{ $item->created_by }})</div>
                            @endif
                        </td>
                        <td>{{ $item->nama_mata_pelajaran }}</td>
                        <td>
                            <strong>{{ $item->materi_pokok }}</strong>
                        </td>
                        <td>
                            <div>{{ $item->uraian_kegiatan }}</div>
                            @if ($item->penugasan)
                                <div style="font-size: 0.7rem; color: #059669; margin-top: 3px;">
                                    <strong>Tugas:</strong> {{ $item->penugasan }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <span
                                class="badge {{ $item->status_kbm === 'Terlaksana' ? 'badge-success' : ($item->status_kbm === 'Sebagian' ? 'badge-warning' : 'badge-danger') }}"
                                style="font-size: 0.68rem; padding: 2px 6px;">
                                {{ $item->status_kbm }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 25px; color: #888;">
                            Belum ada rekaman agenda kelas yang tersimpan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Ringkasan Statistik Singkat Dokumen -->
        <div style="margin-top: 15px; font-size: 0.78rem; color: #444; display: flex; gap: 20px;">
            <span>Total Agenda: <strong>{{ count($items) }}</strong> pertemuan</span>
            <span>Terlaksana: <strong>{{ $items->where('status_kbm', 'Terlaksana')->count() }}</strong></span>
            <span>Sebagian: <strong>{{ $items->where('status_kbm', 'Sebagian')->count() }}</strong></span>
            <span>Tertunda / Digantikan:
                <strong>{{ $items->whereIn('status_kbm', ['Tertunda', 'Digantikan'])->count() }}</strong></span>
        </div>

        <!-- Kolom Tanda Tangan Resmi -->
        <div class="ttd-container">
            <div class="ttd-box">
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">{{ $kepsek->nama ?? '....................................' }}</div>
                <div class="ttd-nip">NIP. {{ $kepsek->nip ?? ($kepsek->nuptk ?? '—') }}</div>
            </div>
            <div class="ttd-box">
                <div>{{ $sekolah->kabupaten_kota ?? 'Tempat' }},
                    {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div>Wali Kelas {{ $activeRombel->nama ?? '' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-name">{{ $waliNama ?? '....................................' }}</div>
                <div class="ttd-nip">NIP. {{ $waliNip ?? '—' }}</div>
            </div>
        </div>
    </div>
</body>

</html>
