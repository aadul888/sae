<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Kartu Pelajar — {{ $sekolahInfo['nama'] ?? 'Sekolah' }}</title>
    <link rel="icon" type="image/png"
        href="{{ asset('img/logo-icon.png') }}?v={{ @filemtime(public_path('img/logo-icon.png')) ?: '1' }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>
    <div class="no-print-bar" style="white-space: nowrap; flex-wrap: nowrap; gap: 10px; max-width: 96vw;">
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']) }}" class="btn-action btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>

        <form method="GET" action="{{ route('dashboard.kartu-pelajar.cetak-keuangan') }}"
            style="display: inline-flex; align-items: center; gap: 8px; margin: 0 4px; white-space: nowrap; flex-shrink: 0;">
            <label style="font-size: var(--text-sm, 0.8125rem); color: #cbd5e1; margin: 0; font-weight: 600;">Dari:</label>
            <input type="date" name="dari" value="{{ $dari }}" class="select-cetak"
                onchange="this.form.submit()" style="padding: 4px 8px; font-size: var(--text-sm, 0.8125rem);">
            <label style="font-size: var(--text-sm, 0.8125rem); color: #cbd5e1; margin: 0 0 0 4px; font-weight: 600;">Sampai:</label>
            <input type="date" name="sampai" value="{{ $sampai }}" class="select-cetak"
                onchange="this.form.submit()" style="padding: 4px 8px; font-size: var(--text-sm, 0.8125rem);">
            @if ($dari || $sampai)
                <a href="{{ route('dashboard.kartu-pelajar.cetak-keuangan') }}" class="btn-action"
                    style="padding: 5px 10px; font-size: var(--text-sm, 0.8125rem);" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </a>
            @endif
        </form>

        <button onclick="window.SAE_Print ? window.SAE_Print() : window.print()" class="btn-action btn-print">
            <i class="fas fa-print"></i> Cetak / Simpan PDF
        </button>
    </div>

    <div class="paper"
        style="max-width: 900px; margin: 20px auto; background: #ffffff; padding: 25px 35px; border-radius: var(--radius-md, 8px); box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
        <!-- Kop Dokumen Resmi Sekolah -->
        @include('partials.kop-surat')

        <div style="text-align: center; margin-top: 14px; margin-bottom: 18px;">
            <h3
                style="font-size: var(--text-lg, 1.125rem); font-weight: 800; text-transform: uppercase; margin: 0 0 4px 0; letter-spacing: 0.5px;">
                LAPORAN KEUANGAN &amp; ARUS KAS KARTU PELAJAR
            </h3>
            <div style="font-size: var(--text-sm, 0.8125rem); color: #475569;">
                @if ($dari && $sampai)
                    Periode: <strong>{{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }}</strong> s/d
                    <strong>{{ \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') }}</strong>
                @elseif ($dari)
                    Periode: Sejak <strong>{{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }}</strong>
                @elseif ($sampai)
                    Periode: Sampai dengan
                    <strong>{{ \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') }}</strong>
                @else
                    Periode: <strong>Seluruh Transaksi Pembukuan</strong> (Per Tanggal
                    {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }})
                @endif
            </div>
        </div>

        <!-- 3 Summary Box Grid -->
        <div class="summary-box-grid">
            <div class="summary-box" style="border-left: 4px solid var(--success, #10b981);">
                <div class="summary-box-label">Total Uang Masuk (Siswa Lunas)</div>
                <div class="summary-box-val" style="color: var(--success, #059669);">
                    Rp {{ number_format($totalPemasukan, 0, ',', '.') }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b); margin-top: 3px;">
                    {{ number_format($pemasukanList->count(), 0, ',', '.') }} transaksi pembayaran siswa
                </div>
            </div>

            <div class="summary-box" style="border-left: 4px solid var(--danger, #ef4444);">
                <div class="summary-box-label">Total Pengeluaran (Bahan &amp; Alat)</div>
                <div class="summary-box-val" style="color: var(--danger, #dc2626);">
                    Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b); margin-top: 3px;">
                    {{ number_format($pengeluaranList->count(), 0, ',', '.') }} nota belanja logistik kartu
                </div>
            </div>

            <div class="summary-box" style="border-left: 4px solid var(--primary, #3b82f6);">
                <div class="summary-box-label">Sisa Saldo Kas Bersih</div>
                <div class="summary-box-val" style="color: var(--primary-hover, #2563eb);">
                    Rp {{ number_format($saldoBersih, 0, ',', '.') }}
                </div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b); margin-top: 3px;">
                    Status: {{ $saldoBersih >= 0 ? 'Surplus / Kas Tersedia' : 'Defisit' }}
                </div>
            </div>
        </div>

        <!-- Tabel 1: Catatan Pengeluaran Bahan & Alat Cetak -->
        <div class="section-title">
            <span>I. RINCIAN PENGELUARAN PENGADAAN ALAT &amp; BAHAN CETAK</span>
            <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--danger, #dc2626);">Subtotal: Rp
                {{ number_format($totalPengeluaran, 0, ',', '.') }}</span>
        </div>

        <table class="table-laporan">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">No</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th>Uraian / Deskripsi Pengeluaran</th>
                    <th style="width: 140px;">Kategori</th>
                    <th style="width: 120px; text-align: right;">Nominal (Rp)</th>
                    <th style="width: 100px;">Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengeluaranList as $idx => $p)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('d/m/Y') }}</td>
                        <td>
                            <strong>{{ $p->judul }}</strong>
                            @if (!empty($p->jumlah))
                                <span style="font-size: var(--text-xs, 0.75rem); color: #475569; font-weight: 600;">
                                    (Qty: {{ number_format($p->jumlah, 0, ',', '.') }}
                                    @if (!empty($p->harga_satuan) || ($p->jumlah > 1 && $p->nominal > 0))
                                        &bull; @ Rp
                                        {{ number_format($p->harga_satuan ?: $p->nominal / $p->jumlah, 0, ',', '.') }}
                                    @endif)
                                </span>
                            @endif
                            @if (!empty($p->keterangan))
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted, #64748b); margin-top: 2px;">{{ $p->keterangan }}
                                </div>
                            @endif
                        </td>
                        <td>
                            {{ $kategoriList[$p->kategori] ?? ucfirst(str_replace('_', ' ', $p->kategori)) }}
                        </td>
                        <td style="text-align: right; font-weight: 700; color: var(--danger, #dc2626);">
                            Rp {{ number_format($p->nominal, 0, ',', '.') }}
                        </td>
                        <td style="font-size: var(--text-sm, 0.8125rem);">{{ $p->petugas ?: 'Admin' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 14px;">
                            Tidak ada catatan pengeluaran pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Tabel 2: Rincian Penerimaan Pembayaran Siswa -->
        <div class="section-title" style="margin-top: 26px;">
            <span>II. RINGKASAN PENERIMAAN PEMBAYARAN KARTU SISWA (LUNAS)</span>
            <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 600; color: var(--success, #059669);">Subtotal: Rp
                {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
        </div>

        <table class="table-laporan">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">No</th>
                    <th style="width: 90px;">Tgl Bayar</th>
                    <th>Nama Peserta Didik</th>
                    <th style="width: 95px;">NISN</th>
                    <th style="width: 90px;">Kelas</th>
                    <th style="width: 90px;">Metode</th>
                    <th style="width: 110px; text-align: right;">Nominal (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pemasukanList as $idx => $m)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td>{{ $m->tanggal_bayar ? \Carbon\Carbon::parse($m->tanggal_bayar)->translatedFormat('d/m/Y') : '-' }}
                        </td>
                        <td><strong>{{ $m->nama }}</strong></td>
                        <td style="font-family: monospace;">{{ $m->nisn ?: '-' }}</td>
                        <td>{{ $m->nama_rombel ?: '-' }}</td>
                        <td>{{ strtoupper($m->metode_pembayaran ?: 'Tunai') }}</td>
                        <td style="text-align: right; font-weight: 700; color: var(--success, #059669);">
                            Rp {{ number_format($m->nominal, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted, #94a3b8); padding: 14px;">
                            Tidak ada data penerimaan pembayaran siswa pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Blok Tanda Tangan -->
        <div class="signature-grid">
            <div class="sig-block">
                <div>Mengetahui,</div>
                <div style="font-weight: 700;">Kepala Sekolah</div>
                <div class="sig-space"></div>
                <div style="font-weight: 800; text-decoration: underline;">H. DEDI KURNIAWAN, S.Pd., M.M.</div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: #475569;">NIP. 197405102005011004</div>
            </div>

            <div class="sig-block">
                <div>Pagelaran, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <div style="font-weight: 700;">Pengelola &amp; Bendahara Kartu Pelajar</div>
                <div class="sig-space"></div>
                <div style="font-weight: 800; text-decoration: underline;">
                    {{ session('user')['nama'] ?? 'ADMINISTRATOR SAE' }}</div>
                <div style="font-size: var(--text-sm, 0.8125rem); color: #475569;">Pengelola Sistem Aplikasi Edukasi (SAE)</div>
            </div>
        </div>
    </div>
</body>

</html>
