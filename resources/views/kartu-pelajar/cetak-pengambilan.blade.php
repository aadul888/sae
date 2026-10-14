<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pengambilan Kartu Pelajar — {{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}
    </title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>
    <div class="no-print-bar" style="white-space: nowrap; flex-wrap: nowrap; gap: 10px; max-width: 96vw;">
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'siswa']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <!-- Filter Form Tanggal Cetak & Rombel (Satu Baris Rapi, Auto-Submit on Change) -->
        <form method="GET" action="{{ route('dashboard.kartu-pelajar.cetak-pengambilan') }}"
            style="display: inline-flex; align-items: center; gap: 8px; margin: 0 4px; white-space: nowrap; flex-shrink: 0;">
            <label style="font-size: 0.78rem; color: #cbd5e1; margin: 0; font-weight: 600;">Tanggal Cetak:</label>
            <select name="tanggal_cetak" class="select-cetak" onchange="this.form.submit()"
                title="Pilih Tanggal Selesai Cetak">
                @if ($tanggalCetakList && $tanggalCetakList->count() > 0)
                    @foreach ($tanggalCetakList as $tc)
                        <option value="{{ $tc->tgl }}" {{ $tanggalCetak === $tc->tgl ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($tc->tgl)->translatedFormat('d M Y') }} ({{ $tc->total }}
                            kartu)
                        </option>
                    @endforeach
                @else
                    <option value="{{ date('Y-m-d') }}">{{ \Carbon\Carbon::now()->translatedFormat('d M Y') }}
                    </option>
                @endif
            </select>

            <label style="font-size: 0.78rem; color: #cbd5e1; margin: 0 0 0 4px; font-weight: 600;">Rombel:</label>
            <select name="rombel" class="select-cetak" onchange="this.form.submit()" title="Filter Berdasarkan Rombel">
                <option value="">Semua Rombel Terpilih</option>
                @foreach ($rombelList as $rName)
                    <option value="{{ $rName }}" {{ $rombel === $rName ? 'selected' : '' }}>
                        {{ $rName }}
                    </option>
                @endforeach
            </select>
        </form>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper"
        style="max-width: 900px; margin: 20px auto; background: #ffffff; padding: 25px 35px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
        <!-- Kop Surat Resmi Sekolah -->
        @include('partials.kop-surat')

        <div style="text-align: center; margin-top: 14px; margin-bottom: 14px;">
            <h3
                style="font-size: 1.1rem; font-weight: 800; text-transform: uppercase; margin: 0 0 4px 0; letter-spacing: 0.5px;">
                DAFTAR DISTRIBUSI &amp; SERAH TERIMA KARTU PELAJAR FISIK (RFID)
            </h3>
            <div style="font-size: 0.82rem; color: #475569;">
                Daftar Peserta Didik Aktif yang Telah Selesai Dicetak &amp; Siap Diambil
            </div>
        </div>

        <!-- Meta Summary Box -->
        <div class="meta-grid-box">
            <div>
                <div class="meta-col-title">Tanggal Cetak Selesai</div>
                <div class="meta-col-val" style="color: #2563eb;">
                    {{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}
                </div>
            </div>
            <div>
                <div class="meta-col-title">Rombongan Belajar / Kelas</div>
                <div class="meta-col-val" style="color: #0f172a;">
                    {{ $rombel ?: 'Semua Rombel Terpilih' }}
                </div>
            </div>
            <div>
                <div class="meta-col-title">Total Kartu Siap Diambil</div>
                <div class="meta-col-val" style="color: #10b981;">
                    {{ number_format($students->count(), 0, ',', '.') }} Kartu Fisik
                </div>
            </div>
        </div>

        <table class="table-distribusi">
            <thead>
                <tr>
                    <th style="width: 32px; text-align: center;">No</th>
                    <th style="width: 95px; white-space: nowrap;">NISN / NIPD</th>
                    <th>Nama Lengkap Peserta Didik</th>
                    <th style="width: 85px; white-space: nowrap;">Kelas</th>
                    <th style="width: 75px; text-align: center; white-space: nowrap;">Jam Cetak</th>
                    <th style="width: 75px; text-align: center; white-space: nowrap;">Pembayaran</th>
                    <th style="width: 140px; text-align: center; white-space: nowrap;">Tanda Tangan Pengambil</th>
                    <th style="width: 80px; text-align: center;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $idx => $s)
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $idx + 1 }}</td>
                        <td style="font-family: monospace; font-size: 0.74rem; line-height: 1.25; white-space: nowrap;">
                            {{ $s->nisn ?: '-' }}<br>
                            <span style="color: #64748b;">{{ $s->nipd ?: '-' }}</span>
                        </td>
                        <td>
                            <strong style="font-size: 0.82rem;">{{ $s->nama }}</strong>
                        </td>
                        <td style="white-space: nowrap; font-size: 0.78rem; font-weight: 700;">
                            {{ $s->nama_rombel ?: '-' }}
                        </td>
                        <td style="text-align: center; font-size: 0.75rem; color: #475569; white-space: nowrap;">
                            {{ $s->tanggal_cetak ? \Carbon\Carbon::parse($s->tanggal_cetak)->format('H:i') : '-' }} WIB
                        </td>
                        <td style="text-align: center; white-space: nowrap;">
                            @if ($s->status_pembayaran === 'lunas')
                                <span style="color: #059669; font-weight: 700; font-size: 0.72rem;">LUNAS</span>
                            @else
                                <span style="color: #d97706; font-weight: 700; font-size: 0.72rem;">BELUM</span>
                            @endif
                        </td>
                        <td>
                            @if (($idx + 1) % 2 !== 0)
                                <div class="ttd-box-odd">
                                    <span>{{ $idx + 1 }}.</span>
                                    <span class="ttd-dot">....................</span>
                                </div>
                            @else
                                <div class="ttd-box-even">
                                    <span>{{ $idx + 1 }}.</span>
                                    <span class="ttd-dot">....................</span>
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 0.74rem; color: #64748b;">
                            {{ $s->catatan ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 20px;">
                            Tidak ada data kartu yang dicetak pada tanggal
                            <strong>{{ \Carbon\Carbon::parse($tanggalCetak)->translatedFormat('d F Y') }}</strong>{{ $rombel ? ' untuk rombel ' . $rombel : '' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Blok Tanda Tangan Serah Terima -->
        @if (!empty($rombel))
            <div class="signature-grid">
                <div class="sig-block">
                    <div>Diterima oleh,</div>
                    <div style="font-weight: 700;">Wali Kelas {{ $rombel }}</div>
                    <div class="sig-space"></div>
                    @if (!empty($waliGtk))
                        <div
                            style="font-weight: 800; border-bottom: 1px solid #0f172a; display: inline-block; min-width: 180px;">
                            {{ $waliGtk->nama }}
                        </div>
                        <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">
                            NIP. {{ $waliGtk->nip ?? ($waliGtk->nuptk ?? '-') }}
                        </div>
                    @else
                        <div
                            style="font-weight: 800; border-bottom: 1px dotted #0f172a; display: inline-block; min-width: 180px;">
                            &nbsp;
                        </div>
                        <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">NIP. -</div>
                    @endif
                </div>

                <div class="sig-block">
                    <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                    <div style="font-weight: 700;">Petugas Cetak / Pengelola Kartu Pelajar</div>
                    <div class="sig-space"></div>
                    <div
                        style="font-weight: 800; border-bottom: 1px solid #0f172a; display: inline-block; min-width: 180px;">
                        {{ session('user')['nama'] ?? 'ADMINISTRATOR SAE' }}
                    </div>
                    <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">Petugas Pengelola Kartu RFID</div>
                </div>
            </div>
        @else
            <div style="display: flex; justify-content: flex-end; margin-top: 36px; page-break-inside: avoid;">
                <div class="sig-block" style="min-width: 250px; text-align: center;">
                    <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                    <div style="font-weight: 700;">Petugas Cetak / Pengelola Kartu Pelajar</div>
                    <div class="sig-space"></div>
                    <div
                        style="font-weight: 800; border-bottom: 1px solid #0f172a; display: inline-block; min-width: 200px;">
                        {{ session('user')['nama'] ?? 'ADMINISTRATOR SAE' }}
                    </div>
                    <div style="font-size: 0.78rem; color: #475569; margin-top: 2px;">Petugas Pengelola Kartu RFID</div>
                </div>
            </div>
        @endif
    </div>
</body>

</html>
