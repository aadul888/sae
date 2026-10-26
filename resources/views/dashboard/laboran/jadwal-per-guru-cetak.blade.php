<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Praktik per Guru — {{ $label }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        .sched-head {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .sched-head h1 {
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sched-head .sub {
            font-size: 10.5px;
            margin-top: 3px;
            color: #475569;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 6px;
        }

        table.sched {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table.sched th,
        table.sched td {
            border: 1px solid #94a3b8;
            padding: 4px 6px;
            vertical-align: top;
        }

        table.sched th {
            background: #e2e8f0;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
        }

        table.sched tr.hari-row td {
            background: #f1f5f9;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.4px;
        }

        .center {
            text-align: center;
        }

        .mono {
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        .muted {
            color: #64748b;
            font-size: 8.5px;
        }

        .sign {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            page-break-inside: avoid;
        }

        .sign .box {
            width: 32%;
            text-align: center;
        }

        .sign .space {
            height: 50px;
        }

        .sign .name {
            font-weight: 700;
            text-decoration: underline;
        }

        .empty {
            text-align: center;
            padding: 30px;
            font-size: 11px;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="sched-head">
        <h1>{{ $sekolah->nama ?? 'SEKOLAH' }}</h1>
        <div class="sub">
            {{ $sekolah->alamat ?? '' }}
            @if (!empty($sekolah->npsn))
                &bull; NPSN {{ $sekolah->npsn }}
            @endif
        </div>
    </div>

    <div class="meta">
        <div>
            <strong>JADWAL PRAKTIK PER GURU</strong><br>
            Periode: {{ $label }}
            ({{ $mode === 'bulanan' ? 'Bulanan' : ($mode === 'semester' ? 'Semester' : 'Mingguan') }})
        </div>
        <div style="text-align: right;">
            @if ($guru)
                Guru: <strong>{{ $guru->nama }}</strong><br>
                {{ !empty($guru->nip) ? 'NIP ' . $guru->nip : '' }}
            @else
                Seluruh Guru
            @endif
            <br>Total {{ $totalSesi }} sesi &bull;
            {{ intdiv($totalMenit, 60) }} jam {{ $totalMenit % 60 }} menit
        </div>
    </div>

    @if ($totalSesi === 0)
        <p class="empty">Tidak ada jadwal praktik pada periode ini.</p>
    @elseif ($mode === 'semester')
        {{-- Cetak semester: dikelompokkan per bulan --}}
        @foreach ($perBulan as $blok)
            <div class="meta" style="margin-top: 14px;">
                <div><strong>{{ $blok['label'] }}</strong> &bull; {{ count($blok['rows']) }} sesi</div>
            </div>
            <table class="sched">
                <thead>
                    <tr>
                        <th style="width: 12%;">Tanggal</th>
                        <th style="width: 11%;">Jam</th>
                        <th style="width: 18%;">Lokasi</th>
                        <th style="width: 15%;">Kelas</th>
                        <th style="width: 20%;">Mata Pelajaran</th>
                        <th style="width: 24%;">Guru</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($blok['rows'] as $r)
                        <tr>
                            <td class="mono">{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('d/m/Y') }}
                                <div class="muted">{{ \Carbon\Carbon::parse($r->tanggal)->translatedFormat('l') }}</div>
                            </td>
                            <td class="mono center">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                            <td>{{ $r->ruang_lab_nama }}</td>
                            <td>{{ $r->rombel_nama ?: '-' }}</td>
                            <td>{{ $r->mata_pelajaran }}</td>
                            <td>
                                {{ $r->guru_nama ?: '-' }}
                                @if (!empty($r->guru_pendamping))
                                    <div class="muted">+ {{ $r->guru_pendamping }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @elseif ($mode === 'mingguan')
        <table class="sched">
            <thead>
                <tr>
                    <th style="width: 11%;">Jam</th>
                    <th style="width: 18%;">Lokasi</th>
                    <th style="width: 15%;">Kelas</th>
                    <th style="width: 19%;">Mata Pelajaran</th>
                    <th style="width: 19%;">Guru</th>
                    <th style="width: 18%;">Topik / Materi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perHari as $hari => $rows)
                    @continue($rows->isEmpty())
                    <tr class="hari-row">
                        <td colspan="6">{{ $hari }}</td>
                    </tr>
                    @foreach ($rows as $r)
                        <tr>
                            <td class="mono center">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                            <td>
                                {{ $r->ruang_lab_nama }}
                                <div class="muted">{{ $jenisLabels[$r->jenis_lokasi] ?? '' }}</div>
                            </td>
                            <td>{{ $r->rombel_nama ?: '-' }}</td>
                            <td>{{ $r->mata_pelajaran }}</td>
                            <td>
                                {{ $r->guru_nama ?: '-' }}
                                @if (!empty($r->guru_pendamping))
                                    <div class="muted">+ {{ $r->guru_pendamping }}</div>
                                @endif
                            </td>
                            <td>{{ $r->topik_praktik ?: '&mdash;' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @else
        <table class="sched">
            <thead>
                <tr>
                    <th style="width: 12%;">Tanggal</th>
                    <th style="width: 11%;">Jam</th>
                    <th style="width: 18%;">Lokasi</th>
                    <th style="width: 15%;">Kelas</th>
                    <th style="width: 20%;">Mata Pelajaran</th>
                    <th style="width: 24%;">Guru</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($perTanggal as $tgl => $rows)
                    @foreach ($rows as $i => $r)
                        <tr>
                            @if ($i === 0)
                                <td rowspan="{{ count($rows) }}" style="font-weight: 700; vertical-align: middle;">
                                    {{ \Carbon\Carbon::parse($tgl)->translatedFormat('d/m/Y') }}
                                    <div class="muted">{{ \Carbon\Carbon::parse($tgl)->translatedFormat('l') }}</div>
                                </td>
                            @endif
                            <td class="mono center">{{ substr((string) $r->jam_mulai, 0, 5) }}&ndash;{{ substr((string) $r->jam_selesai, 0, 5) }}</td>
                            <td>{{ $r->ruang_lab_nama }}</td>
                            <td>{{ $r->rombel_nama ?: '-' }}</td>
                            <td>{{ $r->mata_pelajaran }}</td>
                            <td>
                                {{ $r->guru_nama ?: '-' }}
                                @if (!empty($r->guru_pendamping))
                                    <div class="muted">+ {{ $r->guru_pendamping }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="sign">
        <div class="box">
            <div>Mengetahui,</div>
            <div>Kepala Sekolah</div>
            <div class="space"></div>
            <div class="name">{{ $sekolah->kepala_sekolah ?? '...................................' }}</div>
        </div>
        <div class="box">
            <div>&nbsp;</div>
            <div>Petugas Laboratorium</div>
            <div class="space"></div>
            <div class="name">{{ $petugas['nama'] ?? '...................................' }}</div>
            @if (!empty($petugas['nip']))
                <div style="font-size: 9.5px;">NIP {{ $petugas['nip'] }}</div>
            @endif
        </div>
        <div class="box">
            <div>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
            <div>Kepala Laboratorium</div>
            <div class="space"></div>
            <div class="name">...................................</div>
        </div>
    </div>

    <script>
        // Cetak otomatis saat halaman dibuka lewat tombol Cetak
        if (new URLSearchParams(window.location.search).get('auto') === '1') {
            window.addEventListener('load', function () {
                window.print();
            });
        }
    </script>
</body>

</html>
