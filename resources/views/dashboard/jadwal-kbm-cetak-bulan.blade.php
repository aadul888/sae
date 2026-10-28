<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formatLabel }} — {{ $bulanLabel }} — {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>

    {{-- Toolbar (tidak ikut tercetak) --}}
    <div class="no-print-bar">
        <a href="{{ route('dashboard.jadwal-kbm.index', ['view_mode' => 'pekan']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <form method="GET" action="{{ route('dashboard.jadwal-kbm.cetak-bulanan') }}" class="print-toolbar"
            style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
            <select name="format" class="select-cetak" title="Format cetak">
                @foreach (\App\Services\JadwalBulananService::FORMATS as $fKey => $fLabel)
                    <option value="{{ $fKey }}" {{ $format === $fKey ? 'selected' : '' }}>{{ $fLabel }}
                    </option>
                @endforeach
            </select>

            <select name="bulan" class="select-cetak" title="Bulan">
                @foreach (\App\Services\JadwalBulananService::BULAN_LABELS as $mKey => $mLabel)
                    <option value="{{ $mKey }}" {{ $bulan === $mKey ? 'selected' : '' }}>{{ $mLabel }}
                    </option>
                @endforeach
            </select>

            <select name="tahun" class="select-cetak" title="Tahun">
                @foreach (range(now()->year - 2, now()->year + 2) as $y)
                    <option value="{{ $y }}" {{ $tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>

            <select name="sumber" class="select-cetak" title="Sumber jadwal">
                <option value="otomatis" {{ $sumber === 'otomatis' ? 'selected' : '' }}>Otomatis</option>
                <option value="manual" {{ $sumber === 'manual' ? 'selected' : '' }}>Manual</option>
            </select>

            @if ($format === 'rombel' && count($opsiRombel) > 1)
                <select name="rombel_id" class="select-cetak" title="Pilih kelas">
                    @foreach ($opsiRombel as $r)
                        <option value="{{ $r->rombongan_belajar_id }}"
                            {{ ($rombelId ?? '') == $r->rombongan_belajar_id ? 'selected' : '' }}>
                            Kelas {{ $r->nama }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if ($format === 'guru' && count($opsiGuru) > 1)
                <select name="ptk_id" class="select-cetak" title="Pilih guru">
                    @foreach ($opsiGuru as $g)
                        <option value="{{ $g->ptk_id }}" {{ ($ptkId ?? '') == $g->ptk_id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>
            @endif

            @if ($format === 'rombel' || $format === 'guru')
                <label class="select-cetak"
                    style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0;"
                    title="Cetak seluruh rombel/guru sekaligus (dokumen lebih besar)">
                    <input type="checkbox" name="semua" value="1" {{ $cetakSemua ? 'checked' : '' }}>
                    Semua
                </label>
            @endif

            <button type="submit" class="btn-action btn-print"
                style="background: var(--primary); color: #fff; border: none;">
                <i class="fas fa-filter"></i> Terapkan
            </button>
        </form>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    {{-- Ringkasan periode --}}
    <div class="cetak-paper">
        <div class="watermark-sae">
            <img src="{{ asset('img/logo-icon.png') }}" alt="Watermark SAE" onerror="this.style.display='none'">
        </div>

        <div class="doc-header">
            <div class="school-name">{{ $sekolah->nama ?? 'SEKOLAH' }}</div>
            <div class="doc-title-main">{{ strtoupper($formatLabel) }}</div>
            <div class="doc-subtitle">
                {{ $bulanLabel }} &bull; {{ $semesterLabel }} &bull; Tahun Pelajaran {{ $tahunAjaran }}
            </div>
        </div>

        <div class="info-box" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
            <div class="info-item">
                <span class="info-label">Sumber Jadwal</span>
                <strong>{{ ucfirst($sumber) }}</strong>
            </div>
            <div class="info-item">
                <span class="info-label">Hari Efektif</span>
                <strong>{{ $ringkasan['hari_efektif'] }} hari</strong>
            </div>
            <div class="info-item">
                <span class="info-label">Libur / Ujian</span>
                <strong>{{ $ringkasan['hari_libur'] }} / {{ $ringkasan['hari_ujian'] }} hari</strong>
            </div>
            <div class="info-item">
                <span class="info-label">Total JP Efektif</span>
                <strong>{{ number_format($ringkasan['total_jp'], 0, ',', '.') }} JP</strong>
            </div>
        </div>

        @if ($ringkasan['hari_draft'] > 0)
            <div
                style="padding: 8px 12px; margin-bottom: 14px; border-radius: var(--radius-sm, 6px); background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; font-size: var(--text-sm, 0.8125rem);">
                <i class="fas fa-triangle-exclamation"></i>
                {{ $ringkasan['hari_draft'] }} hari berada pada pekan berstatus <strong>Draft</strong> sehingga tidak
                diekspansi. Berlakukan pekan tersebut pada tab <strong>Pekan</strong> agar ikut tercetak.
            </div>
        @endif
    </div>

    {{-- ================= Format A/B: Matriks JP x Tanggal ================= --}}
    @if ($format === 'rombel' || $format === 'guru')
        @php
            $entitas = $format === 'guru' ? $guruList : $rombels;
            $labelEntitas = $format === 'guru' ? 'Guru' : 'Kelas';
            $keyName = $format === 'guru' ? 'ptk_id' : 'rombongan_belajar_id';
        @endphp

        @forelse ($entitas as $ent)
            @php
                $entKey = $ent->{$keyName} ?? null;
                $entNama = $ent->nama ?? '-';
                $matriksEnt = $matriks[$entKey] ?? [];
            @endphp

            <div class="rombel-page">
                <div class="doc-header">
                    <div class="school-name">{{ $sekolah->nama ?? 'SEKOLAH' }}</div>
                    <div class="doc-title-main">
                        {{ $format === 'guru' ? 'JADWAL MENGAJAR GURU' : 'JADWAL PELAJARAN BULANAN KELAS' }}
                    </div>
                    <div class="doc-subtitle">
                        {{ $labelEntitas }}: {{ $entNama }} &bull; {{ $bulanLabel }} &bull; {{ $semesterLabel }}
                    </div>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto;">
                    <table class="table-rombel" style="min-width: 900px;">
                        <thead>
                            <tr>
                                <th class="th-jam"
                                    style="background-color: #1e3a8a !important; color: #ffffff !important; width: 90px;">
                                    Jam</th>
                                @foreach ($tanggalList as $t)
                                    <th style="background-color: #1e3a8a !important; color: #ffffff !important; padding: 4px 2px; font-size: var(--text-2xs, 0.6875rem);">
                                        <div style="font-weight: 800;">{{ (int) substr($t['tanggal'], 8, 2) }}</div>
                                        <div style="font-weight: 500; text-transform: none;">
                                            {{ substr($t['hari'], 0, 3) }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for ($jp = 1; $jp <= $maxJp; $jp++)
                                <tr>
                                    <td class="th-jam" style="font-size: var(--text-2xs, 0.6875rem);">
                                        <strong>JP {{ $jp }}</strong>
                                    </td>
                                    @foreach ($tanggalList as $t)
                                        @php
                                            $item = $matriksEnt[$t['tanggal']][$jp] ?? null;
                                            $isSpan = $item && (int) $item->jam_ke_mulai === $jp;
                                            $coveredBySpan = !empty($matriksEnt[$t['tanggal']][$jp]);
                                        @endphp

                                        @if (!$t['is_efektif'])
                                            {{-- Kolom libur/ujian/draft: satu sel span penuh pada JP 1 --}}
                                            @if ($jp === 1)
                                                <td rowspan="{{ $maxJp }}"
                                                    style="background: #f3f4f6; color: #6b7280; font-size: var(--text-2xs, 0.6875rem); vertical-align: middle; writing-mode: vertical-rl; text-align: center; padding: 4px;">
                                                    {{ $t['pekan_status'] === 'ujian' ? 'UJIAN' : ($t['pekan_status'] === 'draft' ? 'DRAFT' : 'LIBUR') }}
                                                </td>
                                            @endif
                                        @elseif ($isSpan)
                                            @php
                                                $durasi = max(1, (int) $item->jam_ke_selesai - (int) $item->jam_ke_mulai + 1);
                                                $isRoutine = in_array($item->mata_pelajaran_id, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true);
                                                $cellClass = $isRoutine ? 'cell-kbm cell-' . strtolower($item->mata_pelajaran_id) : 'cell-kbm';
                                            @endphp
                                            <td class="{{ $cellClass }}" rowspan="{{ $durasi }}"
                                                style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 2px;">
                                                <div style="font-weight: 700;">
                                                    {{ \Illuminate\Support\Str::limit($item->nama_mata_pelajaran, 18) }}
                                                </div>
                                                <div style="color: #6b7280;">
                                                    {{ \Illuminate\Support\Str::limit(($format === 'guru' ? $item->rombel_nama : $item->nama_guru) ?? '-', 14) }}
                                                </div>
                                            </td>
                                        @elseif (!$coveredBySpan)
                                            <td style="background: #ffffff; font-size: var(--text-2xs, 0.6875rem);"></td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <div class="signature-container">
                    <div class="sig-box">
                        <div>Mengetahui,</div>
                        <div>Kepala Sekolah</div>
                        <div class="sig-space"></div>
                        <div class="sig-name">{{ $kepalaSekolah->nama ?? '................................' }}
                        </div>
                        <div>NIP. {{ $kepalaSekolah->nip ?? '-' }}</div>
                    </div>
                    <div class="sig-box">
                        <div>{{ $bulanLabel }}</div>
                        <div>{{ $format === 'guru' ? 'Guru Pengampu' : 'Wali Kelas' }}</div>
                        <div class="sig-space"></div>
                        <div class="sig-name">
                            {{ $format === 'guru' ? $entNama : $ent->nama_wali_kelas ?? '................................' }}
                        </div>
                        <div>NIP. {{ $format === 'guru' ? $ent->nip ?? '-' : '-' }}</div>
                    </div>
                </div>
            </div>
        @empty
            <div class="cetak-paper" style="text-align: center; color: #6b7280;">
                <i class="fas fa-calendar-xmark" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                <p style="margin-top: 8px;">Belum ada {{ strtolower($labelEntitas) }} dengan jadwal aktif pada sumber
                    <strong>{{ ucfirst($sumber) }}</strong>.</p>
            </div>
        @endforelse
    @endif

    {{-- ================= Format C: Rekap KBM Bulanan ================= --}}
    @if ($format === 'rekap')
        <div class="rombel-page">
            <div class="doc-header">
                <div class="school-name">{{ $sekolah->nama ?? 'SEKOLAH' }}</div>
                <div class="doc-title-main">REKAP KEGIATAN BELAJAR MENGAJAR BULANAN</div>
                <div class="doc-subtitle">{{ $bulanLabel }} &bull; {{ $semesterLabel }} &bull; TP
                    {{ $tahunAjaran }}</div>
            </div>

            <table class="table-rombel">
                <thead>
                    <tr>
                        <th style="width: 46px;">No</th>
                        <th style="text-align: left;">Rombongan Belajar</th>
                        <th>Hari Efektif</th>
                        <th>Hari Libur</th>
                        <th>Hari Ujian</th>
                        <th>JP Terjadwal</th>
                        <th>JP Efektif</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td style="text-align: left; font-weight: 600;">{{ $row['nama_rombel'] }}</td>
                            <td>{{ $row['hari_efektif'] }}</td>
                            <td>{{ $row['hari_libur'] }}</td>
                            <td>{{ $row['hari_ujian'] }}</td>
                            <td>{{ number_format($row['jp_terjadwal'], 0, ',', '.') }}</td>
                            <td>{{ number_format($row['jp_efektif'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="color: #6b7280;">Belum ada jadwal aktif pada bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature-container">
                <div class="sig-box">
                    <div>Mengetahui,</div>
                    <div>Kepala Sekolah</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $kepalaSekolah->nama ?? '................................' }}</div>
                    <div>NIP. {{ $kepalaSekolah->nip ?? '-' }}</div>
                </div>
                <div class="sig-box">
                    <div>{{ $bulanLabel }}</div>
                    <div>Waka Kurikulum</div>
                    <div class="sig-space"></div>
                    <div class="sig-name">{{ $wakaKurikulum->nama ?? '................................' }}</div>
                    <div>NIP. {{ $wakaKurikulum->nip ?? '-' }}</div>
                </div>
            </div>
        </div>
    @endif

</body>

</html>
