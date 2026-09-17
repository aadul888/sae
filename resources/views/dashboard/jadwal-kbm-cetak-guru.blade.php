<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar Guru - {{ $sekolah->nama ?? 'Sekolah' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #111827;
        }

        body {
            background-color: #f3f4f6;
            padding: 24px;
        }

        .guru-page {
            background: #fff;
            max-width: 960px;
            margin: 0 auto 28px auto;
            padding: 24px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            page-break-after: always;
        }
        .guru-page:last-child {
            page-break-after: avoid;
        }

        /* Kop Surat */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .kop-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 16px;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        .kop-text h2 {
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .kop-text h3 {
            font-size: 0.92rem;
            font-weight: 700;
        }
        .kop-text p {
            font-size: 0.72rem;
            color: #4b5563;
        }

        .doc-title {
            text-align: center;
            margin-bottom: 16px;
        }
        .doc-title h1 {
            font-size: 1.1rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }

        /* Bio Guru Box */
        .bio-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.82rem;
        }
        .bio-item {
            margin-bottom: 4px;
        }
        .bio-label {
            font-weight: 600;
            color: #6b7280;
            display: inline-block;
            width: 110px;
        }

        /* Table */
        .table-guru {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
            margin-bottom: 24px;
        }
        .table-guru th, .table-guru td {
            border: 1px solid #9ca3af;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .table-guru th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-align: center;
        }
        .badge-hari {
            font-weight: 700;
            color: #1e3a8a;
        }

        /* Signatures */
        .signature-container {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            font-size: 0.82rem;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            width: 240px;
        }
        .sig-space {
            height: 55px;
        }
        .sig-name {
            font-weight: 700;
            text-decoration: underline;
        }

        /* Floating bar */
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            right: 24px;
            display: flex;
            gap: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 16px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 30px;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #4b5563; color: #fff; }

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .guru-page {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
                margin-bottom: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            @page {
                size: portrait;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <a href="{{ route('dashboard.jadwal-kbm.index') }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
        <button onclick="window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak Jadwal Guru (Print / PDF)
        </button>
    </div>

    @forelse ($guruList as $guru)
        @php
            $guruSchedules = $schedules->get($guru->ptk_id, collect());
            $totalJp = $guruSchedules->sum(fn($i) => max(1, $i->jam_ke_selesai - $i->jam_ke_mulai + 1));
        @endphp
        <div class="guru-page">
            <div class="kop-surat">
                @if (!empty($sekolah->logo))
                    <img src="{{ asset($sekolah->logo) }}" alt="Logo" class="kop-logo">
                @else
                    <div class="kop-logo" style="display: flex; align-items: center; justify-content: center; background: #e5e7eb; border-radius: 8px;">
                        <i class="fas fa-school" style="font-size: 1.8rem; color: #4b5563;"></i>
                    </div>
                @endif
                <div class="kop-text">
                    <h2>{{ $sekolah->nama ?? 'SEKOLAH MENENGAH KEJURUAN / ATAS' }}</h2>
                    <h3>NPSN: {{ $sekolah->npsn ?? '-' }} &bull; STATUS: TERAKREDITASI</h3>
                    <p>{{ $sekolah->alamat_jalan ?? '' }}, {{ $sekolah->kabupaten_kota ?? '' }}</p>
                </div>
            </div>

            <div class="doc-title">
                <h1>JADWAL MENGAJAR GURU (KARTU GTK)</h1>
                <p style="font-size: 0.78rem; color: #6b7280; margin-top: 2px;">Tahun Pelajaran {{ date('Y') }}/{{ date('Y') + 1 }}</p>
            </div>

            <div class="bio-box">
                <div>
                    <div class="bio-item">
                        <span class="bio-label">Nama Guru:</span>
                        <strong>{{ $guru->nama }}</strong>
                    </div>
                    <div class="bio-item">
                        <span class="bio-label">NIP / NUPTK:</span>
                        <span>{{ $guru->nip ?: ($guru->nuptk ?: '-') }}</span>
                    </div>
                </div>
                <div>
                    <div class="bio-item">
                        <span class="bio-label">Total Beban:</span>
                        <strong style="color: #2563eb;">{{ $totalJp }} Jam Pelajaran (JP) / Minggu</strong>
                    </div>
                    <div class="bio-item">
                        <span class="bio-label">Jumlah Sesi:</span>
                        <span>{{ $guruSchedules->count() }} Pertemuan KBM</span>
                    </div>
                </div>
            </div>

            <table class="table-guru">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th style="width: 80px;">Hari</th>
                        <th style="width: 90px;">Jam Ke</th>
                        <th style="width: 100px;">Waktu</th>
                        <th style="width: 120px;">Kelas / Rombel</th>
                        <th>Mata Pelajaran</th>
                        <th style="width: 90px;">Ruangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guruSchedules as $idx => $sch)
                        @php
                            $jpCount = max(1, $sch->jam_ke_selesai - $sch->jam_ke_mulai + 1);
                        @endphp
                        <tr>
                            <td style="text-align: center;">{{ $idx + 1 }}</td>
                            <td class="badge-hari" style="text-align: center;">{{ $sch->hari }}</td>
                            <td style="text-align: center;">
                                @if ($sch->jam_ke_mulai === $sch->jam_ke_selesai)
                                    JP {{ $sch->jam_ke_mulai }}
                                @else
                                    JP {{ $sch->jam_ke_mulai }}-{{ $sch->jam_ke_selesai }}
                                @endif
                                <span style="font-size: 0.68rem; color: #6b7280;">({{ $jpCount }} JP)</span>
                            </td>
                            <td style="text-align: center; font-size: 0.72rem;">
                                {{ substr($sch->jam_mulai, 0, 5) }} - {{ substr($sch->jam_selesai, 0, 5) }}
                            </td>
                            <td style="font-weight: 700; color: #1f2937;">{{ $sch->nama_rombel }}</td>
                            <td>{{ $sch->nama_mata_pelajaran }}</td>
                            <td style="text-align: center; color: #059669; font-weight: 600;">
                                {{ $sch->ruangan ?: 'Kelas' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 16px; color: #9ca3af;">
                                Belum ada jadwal mengajar yang tercatat untuk guru ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature-container">
                <div class="sig-box">
                    <p>Mengetahui,</p>
                    <p style="font-weight: 600;">Waka Kurikulum</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">___________________________</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. -</p>
                </div>

                <div class="sig-box">
                    <p>{{ $sekolah->kabupaten_kota ?? 'Tempat' }}, {{ date('d F Y') }}</p>
                    <p style="font-weight: 600;">Guru Pengampu,</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">{{ $guru->nama }}</p>
                    <p style="font-size: 0.72rem; color: #6b7280;">NIP. {{ $guru->nip ?: '-' }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="guru-page" style="text-align: center; padding: 50px;">
            <p style="color: #9ca3af;">Tidak ada guru yang ditemukan.</p>
        </div>
    @endforelse

</body>
</html>
