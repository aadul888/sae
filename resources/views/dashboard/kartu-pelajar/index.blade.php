@extends('layouts.dashboard')

@section('title', 'Pengelolaan Kartu Pelajar & Pembayaran — SAE')
@section('dash_title', 'Kartu Pelajar')

@section('content')
    <!-- 1. Header Banner -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                <i class="fas fa-address-card"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Pengelolaan Kartu Pelajar &amp; Pembayaran
                </h2>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 2px;">
                    Pencatatan pembayaran, status cetak fisik RFID, serta pengiriman rincian administrasi via WhatsApp
                    ber-QR Code.
                </div>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-primary" onclick="openCetakRombelModal('{{ $rombel }}')"
                style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-print"></i> Cetak Masal per Rombel
            </button>
            <button type="button" class="btn btn-outline" onclick="openModalCetakPengambilan()"
                style="padding: 8px 16px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; border-color: var(--primary); color: var(--primary); background: var(--card-bg);">
                <i class="fas fa-clipboard-check"></i> List Siap Diambil (Per Tgl Cetak)
            </button>
        </div>
    </div>

    <!-- 2. Flash Messages -->
    @if (session('success'))
        <div
            style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if (session('error'))
        <div
            style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Segmented Navigation Tabs -->
    <div class="dash-segmented-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'keuangan']) }}"
            class="tab-btn btn {{ $tab === 'keuangan' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-money-bill-wave me-1"></i> Catatan Keuangan
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request']) }}"
            class="tab-btn btn {{ $tab === 'request' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-paper-plane me-1"></i> Request Cetak
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'siswa']) }}"
            class="tab-btn btn {{ $tab === 'siswa' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-users me-1"></i> Data Peserta Didik
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'harga']) }}"
            class="tab-btn btn {{ $tab === 'harga' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-tags me-1"></i> Harga Standar &amp; QRIS
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'whatsapp']) }}"
            class="tab-btn btn {{ $tab === 'whatsapp' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fab fa-whatsapp me-1"></i> Pesan Otomatis WhatsApp
        </a>
        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'garansi']) }}"
            class="tab-btn btn {{ $tab === 'garansi' ? 'btn-primary active' : 'btn-outline' }}">
            <i class="fas fa-shield-alt me-1"></i> Ketentuan &amp; Garansi
        </a>
    </div>

    @if ($tab === 'keuangan')
        <!-- TAB 1: Catatan Keuangan, Pengeluaran Bahan/Alat, Chart & Laporan -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">
                    Arus Kas &amp; Pembukuan Operasional Kartu Pelajar
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Monitoring uang masuk pembayaran siswa, pencatatan belanja alat &amp; bahan cetak, serta analisa saldo
                    kas.
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="openTambahPengeluaran()"
                    style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus-circle"></i> Catat Pengeluaran
                </button>
                <a href="{{ route('dashboard.kartu-pelajar.cetak-keuangan') }}" target="_blank" class="btn btn-outline"
                    style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; border-color: var(--primary); color: var(--primary);">
                    <i class="fas fa-print"></i> Cetak Laporan Keuangan
                </a>
            </div>
        </div>

        <!-- 4 Card Ringkasan Keuangan (Jumlah Genap: 4 Kartu) -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 24px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-arrow-down-long"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        Rp {{ number_format($keuangan['total_pemasukan'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Uang Masuk (Siswa Lunas)</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                        {{ number_format($keuangan['total_transaksi_masuk'], 0, ',', '.') }} pembayaran terverifikasi
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                    <i class="fas fa-arrow-up-long"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #ef4444;">
                        Rp {{ number_format($keuangan['total_pengeluaran'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Pengeluaran Bahan/Alat</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                        {{ number_format($keuangan['total_transaksi_keluar'], 0, ',', '.') }} catatan pengadaan logistik
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--primary);">
                        Rp {{ number_format($keuangan['saldo_bersih'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Sisa Saldo Kas Bersih</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                        {{ $keuangan['saldo_bersih'] >= 0 ? 'Surplus / Kas Tersedia' : 'Defisit Anggaran' }}
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        Rp {{ number_format($keuangan['estimasi_piutang'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Estimasi Piutang Belum Bayar</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                        Dari {{ number_format($keuangan['siswa_belum_bayar'], 0, ',', '.') }} siswa belum bayar
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Data Chart Keuangan -->
        <div id="kartuPelajarChartPayload" style="display: none;" data-chart='@json($chartKeuangan)'></div>

        <!-- 2 Visual Charts Interaktif -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- Chart 1: Arus Kas 6 Bulan Terakhir -->
            <div class="card" style="padding: 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <h4
                        style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-column text-primary"></i> Tren Arus Kas 6 Bulan Terakhir
                    </h4>
                    <span class="badge badge-outline" style="font-size: 0.7rem;">Pemasukan vs Pengeluaran</span>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="chartArusKasBar"></canvas>
                </div>
            </div>

            <!-- Chart 2: Komposisi Pengeluaran per Kategori -->
            <div class="card" style="padding: 20px;">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <h4
                        style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-pie text-warning"></i> Alokasi Pengeluaran per Kategori
                    </h4>
                    <span class="badge badge-outline" style="font-size: 0.7rem;">Distribusi Biaya</span>
                </div>
                <div
                    style="position: relative; height: 260px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="chartKategoriDoughnut"></canvas>
                </div>
            </div>
        </div>

        <!-- Dua Card Tabel: Pengeluaran & Pemasukan Terbaru -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- Tabel Kiri: Catatan Pengeluaran -->
            <div class="card table-responsive-stack" style="padding: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <h4
                        style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-receipt text-danger"></i> Catatan Belanja Bahan &amp; Alat
                    </h4>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openTambahPengeluaran()"
                        style="padding: 4px 10px; font-size: 0.76rem; border-color: #ef4444; color: #ef4444;">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </button>
                </div>

                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tanggal</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Uraian &amp; Kategori</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Nominal</th>
                            <th
                                style="padding: 10px 12px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 85px;">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($pengeluaranList && $pengeluaranList->count() > 0)
                            @foreach ($pengeluaranList as $p)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td
                                        style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap;">
                                        {{ \Carbon\Carbon::parse($p->tanggal)->translatedFormat('d M Y') }}
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">
                                            {{ $p->judul }}</div>
                                        <div
                                            style="display: flex; align-items: center; gap: 6px; margin-top: 3px; flex-wrap: wrap;">
                                            <span class="badge badge-outline"
                                                style="font-size: 0.68rem; padding: 2px 6px;">
                                                {{ $kategoriList[$p->kategori] ?? ucfirst($p->kategori) }}
                                            </span>
                                            @if ($p->petugas)
                                                <span style="font-size: 0.7rem; color: var(--text-muted);">
                                                    &bull; Oleh: {{ $p->petugas }}
                                                </span>
                                            @endif
                                        </div>
                                        @if ($p->keterangan)
                                            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                                {{ $p->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td
                                        style="padding: 12px 14px; text-align: right; font-weight: 700; color: #ef4444; font-size: 0.86rem; white-space: nowrap;">
                                        Rp {{ number_format($p->nominal, 0, ',', '.') }}
                                    </td>
                                    <td style="padding: 12px 12px; text-align: center;">
                                        <div
                                            style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                                            @if ($p->bukti_nota_path)
                                                <a href="{{ asset('storage/' . ltrim($p->bukti_nota_path, '/')) }}"
                                                    target="_blank" class="btn-icon" style="color: #3b82f6;"
                                                    title="Lihat Bukti Nota">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            @endif
                                            <form
                                                action="{{ route('dashboard.kartu-pelajar.destroy-pengeluaran', $p->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon" style="color: #ef4444;"
                                                    title="Hapus Pengeluaran">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-receipt mb-2"
                                        style="font-size: 1.8rem; opacity: 0.4; display: block;"></i>
                                    <div>Belum ada catatan pengeluaran alat / bahan kartu pelajar.</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if ($pengeluaranList && $pengeluaranList->hasPages())
                    <div style="padding: 12px 16px;">
                        {{ $pengeluaranList->links() }}
                    </div>
                @endif
            </div>

            <!-- Tabel Kanan: Pemasukan Siswa Terbaru -->
            <div class="card table-responsive-stack" style="padding: 0;">
                <div
                    style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h4
                        style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-money-check-dollar text-success"></i> Penerimaan Pembayaran Siswa Terbaru
                    </h4>
                    <span class="badge badge-success" style="font-size: 0.7rem;">Top 10 Transaksi</span>
                </div>

                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Tgl Bayar</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Peserta Didik</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                                Metode</th>
                            <th
                                style="padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">
                                Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($recentPemasukan && $recentPemasukan->count() > 0)
                            @foreach ($recentPemasukan as $rp)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td
                                        style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap;">
                                        {{ $rp->tanggal_bayar ? \Carbon\Carbon::parse($rp->tanggal_bayar)->translatedFormat('d M Y') : '-' }}
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">
                                            {{ $rp->nama }}</div>
                                        <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                            {{ $rp->nama_rombel ?: '-' }} &bull; NISN: {{ $rp->nisn ?: '-' }}
                                        </div>
                                    </td>
                                    <td style="padding: 12px 14px;">
                                        <span class="badge badge-outline" style="font-size: 0.72rem; padding: 2px 7px;">
                                            {{ strtoupper($rp->metode_pembayaran ?: 'Tunai') }}
                                        </span>
                                    </td>
                                    <td
                                        style="padding: 12px 14px; text-align: right; font-weight: 700; color: #10b981; font-size: 0.86rem; white-space: nowrap;">
                                        Rp {{ number_format($rp->nominal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" style="padding: 30px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-receipt mb-2"
                                        style="font-size: 1.8rem; opacity: 0.4; display: block;"></i>
                                    <div>Belum ada data pembayaran siswa lunas.</div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @elseif ($tab === 'request')
        <!-- TAB: Request Cetak Kartu Pelajar -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-color);">Antrean Request
                    Cetak Kartu</h3>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                    Catat permintaan cetak baru / penggantian kartu. Status "Selesai" otomatis menandai kartu sudah dicetak.
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="openTambahRequest()"
                style="padding: 8px 16px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-plus-circle"></i> Tambah Request
            </button>
        </div>

        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
            @foreach ([['menunggu', 'Menunggu', 'fa-hourglass-half', '#f59e0b', '245,158,11'], ['diproses', 'Diproses', 'fa-gears', '#2563eb', '37,99,235'], ['selesai', 'Selesai', 'fa-circle-check', '#10b981', '16,185,129'], ['ditolak', 'Ditolak', 'fa-ban', '#ef4444', '239,68,68']] as [$sKey, $sLabel, $sIcon, $sColor, $sRgb])
                <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'request', 'req_status' => $reqStatus === $sKey ? '' : $sKey]) }}"
                    class="dash-stat-card"
                    style="text-decoration: none; {{ $reqStatus === $sKey ? 'outline: 2px solid ' . $sColor . ';' : '' }}">
                    <div class="dash-stat-icon"
                        style="background: rgba({{ $sRgb }},0.12); color: {{ $sColor }};">
                        <i class="fas {{ $sIcon }}"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: {{ $sColor }};">
                            {{ number_format($requestStats[$sKey] ?? 0, 0, ',', '.') }}</div>
                        <div class="dash-stat-label">{{ $sLabel }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); width: 50px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Tanggal</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Peserta Didik</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Jenis &amp; Alasan</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">
                            Status</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center; width: 170px;">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($requestList ?? []) as $ri => $rq)
                        @php
                            $rqColor =
                                [
                                    'menunggu' => 'badge-warning',
                                    'diproses' => 'badge-primary',
                                    'selesai' => 'badge-success',
                                    'ditolak' => 'badge-danger',
                                ][$rq->status] ?? 'badge-outline';
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="text-align: center; color: var(--text-muted); font-size: 0.84rem;" data-label="No">
                                {{ $requestList->firstItem() + $ri }}</td>
                            <td style="padding: 12px 14px; font-size: 0.78rem; color: var(--text-muted); white-space: nowrap;"
                                data-label="Tanggal">
                                {{ \Carbon\Carbon::parse($rq->created_at)->translatedFormat('d M Y H:i') }}
                                <div>oleh {{ $rq->diminta_oleh ?: 'Admin' }}</div>
                            </td>
                            <td style="padding: 12px 14px;" data-label="Peserta Didik">
                                <div style="font-weight: 700; font-size: 0.86rem;">{{ $rq->nama }}</div>
                                <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">
                                    {{ $rq->nama_rombel ?: '-' }} &bull; NISN: {{ $rq->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 14px;" data-label="Jenis">
                                <span class="badge badge-outline"
                                    style="font-size: 0.72rem;">{{ \App\Models\KartuPelajarRequest::JENIS[$rq->jenis_request] ?? $rq->jenis_request }}</span>
                                @if ($rq->alasan)
                                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px;">
                                        {{ $rq->alasan }}</div>
                                @endif
                                @if ($rq->catatan_admin)
                                    <div style="font-size: 0.74rem; color: var(--primary); margin-top: 2px;"><i
                                            class="fas fa-comment-dots me-1"></i>{{ $rq->catatan_admin }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;" data-label="Status">
                                <span class="badge {{ $rqColor }}"
                                    style="font-size: 0.74rem; padding: 3px 8px;">{{ \App\Models\KartuPelajarRequest::STATUS[$rq->status] ?? $rq->status }}</span>
                                @if ($rq->selesai_at)
                                    <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                        {{ \Carbon\Carbon::parse($rq->selesai_at)->translatedFormat('d M Y H:i') }}</div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px; text-align: center;" data-label="Aksi">
                                <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                    <form action="{{ route('dashboard.kartu-pelajar.update-request', $rq->id) }}"
                                        method="POST" style="margin: 0;">
                                        @csrf
                                        <select name="status" class="form-control" onchange="this.form.submit()"
                                            data-no-ajax
                                            style="min-width: 110px; height: 32px; padding: 0 8px; font-size: 0.78rem;">
                                            @foreach (\App\Models\KartuPelajarRequest::STATUS as $stKey => $stLabel)
                                                <option value="{{ $stKey }}"
                                                    {{ $rq->status === $stKey ? 'selected' : '' }}>{{ $stLabel }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form action="{{ route('dashboard.kartu-pelajar.destroy-request', $rq->id) }}"
                                        method="POST" style="margin: 0;"
                                        onsubmit="return confirm('Hapus request ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" style="color: #ef4444;"
                                            title="Hapus Request"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-paper-plane mb-2"
                                    style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                Belum ada request cetak kartu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (!empty($requestList) && $requestList->hasPages())
            <div style="margin-bottom: 24px;">{{ $requestList->links() }}</div>
        @endif
    @elseif ($tab === 'siswa')
        <!-- 3. Stat Grid Baku SAE (6 Kartu Ringkasan - Jumlah Genap Rapi) -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['total_siswa'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Total Peserta Didik</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                    <i class="fas fa-circle-check"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                        {{ number_format($stats['total_lunas'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Pembayaran Lunas (Rp
                        {{ number_format($stats['total_dana'], 0, ',', '.') }})
                    </div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($stats['total_belum_bayar'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Belum Membayar</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(37,99,235,0.12); color: #2563eb;">
                    <i class="fas fa-print"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #2563eb;">
                        {{ number_format($stats['total_sudah_cetak'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Kartu Sudah Dicetak</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(100,116,139,0.12); color: #64748b;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['total_belum_cetak'], 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">Belum Dicetak (Antrean)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format(($stats['total_klaim_kartu'] ?? 0) + ($stats['total_klaim_stiker'] ?? 0), 0, ',', '.') }}
                    </div>
                    <div class="dash-stat-label">
                        Klaim Garansi ({{ $stats['total_klaim_kartu'] ?? 0 }} Kartu &bull;
                        {{ $stats['total_klaim_stiker'] ?? 0 }} Stiker)
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Toolbar & Filter Card -->
        <div class="card" style="padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                    <div class="toolbar-entries">
                        <label for="perPageSelect" style="margin: 0;">Tampilkan</label>
                        <select id="perPageSelect" name="perPage" class="per-page-select">
                            @foreach ([10, 15, 25, 50, 100] as $n)
                                <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                        <span>entri</span>
                    </div>

                    <select id="filterRombel" name="rombel" class="toolbar-filter-select">
                        <option value="">Semua Rombel</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->nama }}" {{ $rombel === $r->nama ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>

                    <select id="filterStatusBayar" name="status_pembayaran" class="toolbar-filter-select">
                        <option value="">Semua Status Pembayaran</option>
                        <option value="lunas" {{ $statusBayar === 'lunas' ? 'selected' : '' }}>Lunas</option>
                        <option value="belum_bayar" {{ $statusBayar === 'belum_bayar' ? 'selected' : '' }}>Belum Bayar
                        </option>
                    </select>

                    <select id="filterStatusCetak" name="status_cetak" class="toolbar-filter-select">
                        <option value="">Semua Status Cetak</option>
                        <option value="sudah_cetak" {{ $statusCetak === 'sudah_cetak' ? 'selected' : '' }}>Sudah Dicetak
                        </option>
                        <option value="belum_cetak" {{ $statusCetak === 'belum_cetak' ? 'selected' : '' }}>Belum Dicetak
                        </option>
                    </select>

                    @if ($q || $rombel || $statusBayar || $statusCetak)
                        <a href="{{ route('dashboard.kartu-pelajar.index', ['tab' => 'siswa']) }}"
                            class="btn btn-outline" style="padding: 7px 12px; font-size: 0.84rem;" title="Reset filter">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>

                <div class="live-search-wrap">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchKartuPelajar" name="q"
                        placeholder="Cari nama / NISN / NIPD..." value="{{ $q }}" autocomplete="off">
                    <button type="button" id="clearSearchKartuPelajar"
                        class="clear-search {{ !empty($q) ? 'visible' : '' }}" title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Floating Multi-Select Bulk Actions Bar -->
            <div id="bulkActionBar"
                style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div
                    style="font-size: 0.85rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-square"></i>
                    <span><span id="bulkSelectedCount">0</span> peserta didik dipilih</span>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-sm"
                        onclick="openModalBulkPembayaran('custom_bulk')" style="font-weight: 700;">
                        <i class="fas fa-sliders-h me-1"></i> Sesuaikan Masal
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="openModalBulkPembayaran('set_lunas')"
                        style="border-color: #10b981; color: #10b981; font-weight: 600;">
                        <i class="fas fa-check me-1"></i> Tandai Lunas
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="openModalBulkPembayaran('set_belum_bayar')"
                        style="border-color: #f59e0b; color: #f59e0b; font-weight: 600;">
                        <i class="fas fa-clock me-1"></i> Tandai Belum Bayar
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="openModalBulkPembayaran('set_sudah_cetak')"
                        style="border-color: #2563eb; color: #2563eb; font-weight: 600;">
                        <i class="fas fa-print me-1"></i> Tandai Sudah Cetak
                    </button>
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="openModalBulkPembayaran('set_belum_cetak')"
                        style="border-color: #64748b; color: #64748b; font-weight: 600;">
                        <i class="fas fa-hourglass-start me-1"></i> Tandai Belum Cetak
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Table Data Kartu Pelajar -->
        <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th style="width: 40px; text-align: center; padding: 12px 10px;">
                            <input type="checkbox" id="checkAllStudents" title="Pilih Semua di Halaman Ini">
                        </th>
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                            No
                        </th>
                        <th class="sortable-th {{ $sort === 'nama' ? 'sorted' : '' }}" data-sort="nama"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik
                            <span class="sort-icon">{!! $sort === 'nama' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'nama_rombel' ? 'sorted' : '' }}" data-sort="nama_rombel"
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Rombel / Kelas
                            <span class="sort-icon">{!! $sort === 'nama_rombel' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'status_pembayaran' ? 'sorted' : '' }}"
                            data-sort="status_pembayaran"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Status Pembayaran
                            <span class="sort-icon">{!! $sort === 'status_pembayaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th class="sortable-th {{ $sort === 'status_cetak' ? 'sorted' : '' }}" data-sort="status_cetak"
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Status Cetak
                            <span class="sort-icon">{!! $sort === 'status_cetak' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                        </th>
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kontak WhatsApp
                        </th>
                        <th
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 145px;">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $idx => $s)
                        @php
                            $isLunas = $s->status_pembayaran === 'lunas';
                            $isCetak = $s->status_cetak === 'sudah_cetak';
                            $phone = $s->nomor_wa ?: ($s->nomor_telepon_seluler ?: $s->nomor_telepon_rumah);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s ease;">
                            <td style="text-align: center; padding: 12px 10px;" data-label="Pilih">
                                <input type="checkbox" class="check-student-row" value="{{ $s->peserta_didik_id }}">
                            </td>
                            <td style="text-align: center; font-size: 0.84rem; color: var(--text-muted); padding: 12px 14px;"
                                data-label="No">
                                {{ $students->firstItem() + $idx }}
                            </td>
                            <td style="padding: 12px 16px;" data-label="Peserta Didik">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="{{ $s->foto_url ?: asset('img/logo-dark.png') }}"
                                        alt="{{ $s->nama }}"
                                        style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-color); flex-shrink: 0;"
                                        onerror="this.src='/img/logo-dark.png'">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                            {{ $s->nama }}
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                            NISN: {{ $s->nisn ?: '-' }} &bull; NIPD: {{ $s->nipd ?: '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 14px;" data-label="Kelas">
                                <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                    {{ $s->nama_rombel ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px;" data-label="Pembayaran">
                                @if ($isLunas)
                                    <div style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span class="badge badge-success" style="font-size: 0.74rem; padding: 3px 8px;">
                                            <i class="fas fa-check-circle me-1"></i> LUNAS
                                        </span>
                                    </div>
                                    <div
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-top: 3px;">
                                        Rp {{ number_format($s->nominal, 0, ',', '.') }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        {{ strtoupper($s->metode_pembayaran ?: 'Tunai') }}
                                        @if ($s->tanggal_bayar)
                                            &bull;
                                            {{ \Carbon\Carbon::parse($s->tanggal_bayar)->translatedFormat('d/m/Y') }}
                                        @endif
                                    </div>
                                @else
                                    <span class="badge badge-warning" style="font-size: 0.74rem; padding: 3px 8px;">
                                        <i class="fas fa-clock me-1"></i> BELUM BAYAR
                                    </span>
                                    <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 3px;">
                                        Tagihan: Rp {{ number_format($s->nominal, 0, ',', '.') }}
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;" data-label="Status Cetak">
                                @php
                                    $sisaKartu = max(
                                        0,
                                        ($settings->kuota_ganti_kartu_full ?? 1) - (int) $s->klaim_ganti_kartu,
                                    );
                                    $sisaStiker = max(0, ($settings->kuota_ganti_stiker ?? 3) - (int) $s->klaim_stiker);
                                @endphp
                                @if ($isCetak)
                                    <span id="badgeCetak_{{ $s->peserta_didik_id }}" class="badge badge-primary"
                                        style="font-size: 0.74rem; padding: 3px 8px;">
                                        <i class="fas fa-print me-1"></i> Sudah Dicetak
                                    </span>
                                    @if ($s->tanggal_cetak)
                                        <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                            {{ \Carbon\Carbon::parse($s->tanggal_cetak)->translatedFormat('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                    <div
                                        style="font-size: 0.69rem; color: #10b981; margin-top: 3px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-shield-alt text-success"></i>
                                        <span>Garansi: {{ $sisaKartu }} Kartu &bull; {{ $sisaStiker }}
                                            Stiker</span>
                                    </div>
                                @else
                                    <span id="badgeCetak_{{ $s->peserta_didik_id }}" class="badge badge-outline"
                                        style="font-size: 0.74rem; padding: 3px 8px;">
                                        <i class="fas fa-clock me-1"></i> Belum Dicetak
                                    </span>
                                    <div
                                        style="font-size: 0.69rem; color: var(--text-muted); margin-top: 3px; font-style: italic;">
                                        <i class="fas fa-shield-alt" style="opacity: 0.4;"></i> Garansi belum aktif
                                    </div>
                                @endif
                            </td>
                            <td style="padding: 12px 14px;" data-label="WhatsApp">
                                @if ($phone)
                                    <div style="font-size: 0.8rem; font-family: monospace; color: var(--text-color);">
                                        <i class="fab fa-whatsapp text-success me-1"></i> {{ $phone }}
                                    </div>
                                    @if ($s->wa_terkirim_at)
                                        <div style="font-size: 0.68rem; color: #10b981; margin-top: 2px;">
                                            <i class="fas fa-check-double me-1"></i> Terkirim
                                            {{ \Carbon\Carbon::parse($s->wa_terkirim_at)->translatedFormat('d/m/Y H:i') }}
                                        </div>
                                    @endif
                                @else
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                                        Belum ada nomor
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px; text-align: center;" data-label="Aksi">
                                <div class="table-actions" style="display: flex; gap: 4px; justify-content: center;">
                                    {{-- Tombol Edit Pembayaran --}}
                                    <button type="button" class="btn-icon" style="color: #4f46e5;"
                                        title="Catat / Edit Pembayaran"
                                        onclick="openEditPembayaran({{ json_encode($s) }})">
                                        <i class="fas fa-wallet"></i>
                                    </button>

                                    {{-- Tombol Toggle Status Cetak --}}
                                    <button type="button" class="btn-icon" style="color: #0284c7;"
                                        onclick="toggleStatusCetak('{{ $s->peserta_didik_id }}', this)"
                                        title="{{ $isCetak ? 'Tandai Belum Dicetak' : 'Tandai Sudah Dicetak' }}">
                                        <i
                                            class="fas {{ $isCetak ? 'fa-toggle-on text-primary' : 'fa-toggle-off text-muted' }}"></i>
                                    </button>

                                    {{-- Tombol Klaim Garansi --}}
                                    <button type="button" class="btn-icon"
                                        style="color: {{ $isCetak ? '#f59e0b' : '#94a3b8' }}; opacity: {{ $isCetak ? '1' : '0.6' }};"
                                        onclick="openKlaimGaransi('{{ $s->peserta_didik_id }}', '{{ addslashes($s->nama) }}', '{{ $s->nisn }}', '{{ addslashes($s->nama_rombel ?? '-') }}', {{ (int) $s->klaim_ganti_kartu }}, {{ (int) $s->klaim_stiker }}, {{ (int) ($settings->kuota_ganti_kartu_full ?? 1) }}, {{ (int) ($settings->kuota_ganti_stiker ?? 3) }}, {{ $isCetak ? 1 : 0 }})"
                                        title="{{ $isCetak ? 'Klaim Garansi (Sisa: ' . $sisaKartu . ' Kartu, ' . $sisaStiker . ' Stiker)' : 'Garansi Belum Aktif (Kartu Belum Dicetak)' }}">
                                        <i class="fas fa-shield-alt"></i>
                                    </button>

                                    {{-- Tombol Pratinjau / Cetak Kartu Tunggal (Sama dengan Peserta Didik Aktif) --}}
                                    @if (!empty($s->nisn))
                                        <button type="button" class="btn-icon" style="color: #0284c7;"
                                            title="Pratinjau / Cetak Kartu Pelajar Digital"
                                            onclick="openKartuPelajarModal('{{ $s->nisn }}')">
                                            <i class="fas fa-address-card"></i>
                                        </button>
                                    @endif

                                    {{-- Tombol Kirim WhatsApp & QR Code --}}
                                    <button type="button" class="btn-icon" style="color: #22c55e;"
                                        onclick="openModalWa('{{ $s->nisn }}')"
                                        title="Kirim Notifikasi WhatsApp &amp; QR Code">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-address-card mb-2"
                                    style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                <div>Tidak ada data peserta didik yang sesuai dengan filter pencarian.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 6. Pagination -->
        @if ($students->hasPages())
            <div class="custom-pagination" style="margin-bottom: 24px;">
                @if ($students->onFirstPage())
                    <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                @else
                    <a href="{{ $students->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                            class="fas fa-chevron-left"></i></a>
                @endif
                @php
                    $cur = $students->currentPage();
                    $last = $students->lastPage();
                    $from = max(1, $cur - 2);
                    $to = min($last, $cur + 2);
                @endphp
                @if ($from > 1)
                    <a href="{{ $students->url(1) }}" class="page-btn">1</a>
                    @if ($from > 2)
                        <span class="page-info">&hellip;</span>
                    @endif
                @endif
                @for ($i = $from; $i <= $to; $i++)
                    <a href="{{ $students->url($i) }}"
                        class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                @endfor
                @if ($to < $last)
                    @if ($to < $last - 1)
                        <span class="page-info">&hellip;</span>
                    @endif
                    <a href="{{ $students->url($last) }}" class="page-btn">{{ $last }}</a>
                @endif
                @if ($students->hasMorePages())
                    <a href="{{ $students->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                            class="fas fa-chevron-right"></i></a>
                @else
                    <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        @endif
    @elseif ($tab === 'harga')
        <!-- TAB 2: Pengaturan Harga Standar, Rekening Bank & QRIS -->
        <div class="card" style="padding: 24px; max-width: 860px; margin-bottom: 24px;">
            <div
                style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Pengaturan Harga Standar &amp; Metode Pembayaran
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Atur nominal biaya default pembuatan kartu pelajar fisik/RFID, rekening bank sekolah, serta unggahan
                        kode QRIS resmi.
                    </div>
                </div>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="subtab" value="harga">

                <div style="margin-bottom: 20px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Biaya Standar Pembuatan Kartu Pelajar (Rp) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" name="biaya_standar"
                        value="{{ old('biaya_standar', $settings->biaya_standar) }}" min="0" step="500"
                        required class="form-control"
                        style="width: 100%; max-width: 320px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.9rem; font-weight: 700;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                        Nominal ini menjadi tagihan default otomatis saat mendata pembayaran peserta didik baru.
                    </div>
                </div>

                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4
                        style="font-size: 0.92rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-university text-primary"></i> Rekening Bank Tujuan Transfer
                    </h4>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nama Bank
                            </label>
                            <input type="text" name="bank_nama" value="{{ old('bank_nama', $settings->bank_nama) }}"
                                placeholder="Contoh: Bank BJB / BRI / BCA" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nomor Rekening
                            </label>
                            <input type="text" name="bank_rekening"
                                value="{{ old('bank_rekening', $settings->bank_rekening) }}"
                                placeholder="Contoh: 0123-4567-8901" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem; font-family: monospace;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Atas Nama Rekening
                            </label>
                            <input type="text" name="bank_atas_nama"
                                value="{{ old('bank_atas_nama', $settings->bank_atas_nama) }}"
                                placeholder="Contoh: SMK NEGERI 1 PAGELARAN" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                    </div>
                </div>

                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
                    <h4
                        style="font-size: 0.92rem; font-weight: 700; color: var(--text-color); margin: 0 0 14px 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-qrcode text-primary"></i> Unggah Gambar QRIS Resmi
                    </h4>

                    <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                        @if ($settings->qris_image_path)
                            <div
                                style="text-align: center; background: #ffffff; padding: 10px; border-radius: 8px; border: 1px solid var(--border-color); width: 140px;">
                                <img src="{{ $settings->qris_image_url }}" alt="QRIS Sekolah"
                                    style="width: 120px; height: 120px; object-fit: contain; display: block; margin: 0 auto 6px auto;">
                                <div style="font-size: 0.7rem; color: #475569; font-weight: 600;">QRIS Aktif</div>
                            </div>
                        @endif

                        <div style="flex: 1; min-width: 260px;">
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; color: var(--text-color);">
                                {{ $settings->qris_image_path ? 'Ganti File Gambar QRIS' : 'Pilih File Gambar QRIS' }}
                                (PNG, JPG, WebP — Maks. 5MB)
                            </label>
                            <input type="file" name="qris_image" accept="image/png,image/jpeg,image/webp"
                                class="form-control"
                                style="width: 100%; padding: 6px 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.82rem;">

                            @if ($settings->qris_image_path)
                                <div style="margin-top: 10px;">
                                    <label
                                        style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.8rem; color: #ef4444; cursor: pointer;">
                                        <input type="checkbox" name="hapus_qris" value="1">
                                        <span><i class="fas fa-trash-alt me-1"></i> Hapus gambar QRIS yang terunggah saat
                                            ini</span>
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Instruksi &amp; Deskripsi Pembayaran
                    </label>
                    <textarea name="instruksi_pembayaran" rows="3" class="form-control"
                        placeholder="Tuliskan petunjuk pembayaran untuk orang tua murid / siswa..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; line-height: 1.5;">{{ old('instruksi_pembayaran', $settings->instruksi_pembayaran) }}</textarea>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                        Teks instruksi ini otomatis disematkan ke dalam pesan notifikasi WhatsApp saat tagihan dikirimkan.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="submit" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: 0.86rem; font-weight: 700; border-radius: 8px;">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan Harga &amp; QRIS
                    </button>
                </div>
            </form>
        </div>
    @elseif ($tab === 'whatsapp')
        <!-- TAB 3: Pengaturan Template Pesan Otomatis WhatsApp -->
        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; margin-bottom: 24px;">
            <!-- Left: Form Template -->
            <div class="card" style="padding: 22px;">
                <div
                    style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
                    <div
                        style="width: 38px; height: 38px; border-radius: 10px; background: rgba(34,197,94,0.12); color: #22c55e; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Template Pesan Otomatis WhatsApp
                        </h3>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                            Sesuaikan format pesan teks yang dikirimkan ke nomor WhatsApp siswa atau orang tua.
                        </div>
                    </div>
                </div>

                <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST">
                    @csrf
                    <input type="hidden" name="subtab" value="whatsapp">

                    <!-- Variable Tags Chips -->
                    <div style="margin-bottom: 12px;">
                        <label
                            style="display: block; font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                            Klik Tag Variabel untuk Menyisipkan ke Pesan:
                        </label>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            @php
                                $tags = [
                                    '{NAMA_SEKOLAH}' => 'Nama Sekolah',
                                    '{NAMA_SISWA}' => 'Nama Siswa',
                                    '{NISN}' => 'NISN',
                                    '{ROMBEL}' => 'Kelas / Rombel',
                                    '{STATUS_BAYAR}' => 'Status Bayar',
                                    '{NOMINAL}' => 'Biaya / Nominal',
                                    '{METODE_BAYAR}' => 'Metode Bayar',
                                    '{TANGGAL_BAYAR}' => 'Tgl Bayar',
                                    '{STATUS_CETAK}' => 'Status Cetak',
                                    '{INFO_GARANSI}' => 'Rincian Garansi',
                                    '{DESKRIPSI_PEMBAYARAN}' => 'Rekening & Instruksi',
                                    '{VERIFY_URL}' => 'Tautan Verifikasi',
                                ];
                            @endphp
                            @foreach ($tags as $tagCode => $tagDesc)
                                <button type="button" class="btn-wa-tag-chip" data-tag="{{ $tagCode }}"
                                    style="background: var(--bg-hover); border: 1px solid var(--border-color); color: var(--primary); padding: 3px 8px; border-radius: 6px; font-size: 0.74rem; font-family: monospace; font-weight: 600; cursor: pointer; transition: all 0.15s;"
                                    title="{{ $tagDesc }}">
                                    {{ $tagCode }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div style="margin-bottom: 18px;">
                        <textarea name="template_wa" id="templateWaTextarea" rows="15" required class="form-control"
                            style="width: 100%; padding: 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; font-family: monospace; line-height: 1.5;">{{ old('template_wa', $settings->template_wa ?: \App\Models\KartuPelajarPengaturan::getDefaultWaTemplate()) }}</textarea>
                    </div>

                    <div
                        style="display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <button type="submit" name="reset_template" value="1" class="btn btn-outline"
                            onclick="return confirm('Kembalikan format pesan WhatsApp ke template bawaan sistem?')"
                            style="padding: 8px 14px; font-size: 0.82rem;">
                            <i class="fas fa-undo me-1"></i> Reset ke Default
                        </button>
                        <button type="submit" class="btn btn-primary"
                            style="padding: 8px 20px; font-size: 0.84rem; font-weight: 700;">
                            <i class="fas fa-save me-1"></i> Simpan Template
                        </button>
                    </div>
                </form>
            </div>

            <!-- Right: WhatsApp Chat Mockup Live Preview -->
            <div class="card" style="padding: 22px; display: flex; flex-direction: column;">
                <div
                    style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                    <div
                        style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                        <i class="fab fa-whatsapp text-success"></i> Pratinjau Tampilan WhatsApp Live
                    </div>
                    <span class="badge badge-success" style="font-size: 0.7rem; padding: 2px 7px;">Realtime</span>
                </div>

                <div
                    style="flex: 1; background: #ece5dd; border-radius: 12px; padding: 16px; border: 1px solid #d1d7db; position: relative; overflow-y: auto; max-height: 520px;">
                    <div
                        style="background: #ffffff; color: #111827; padding: 12px 14px; border-radius: 10px; max-width: 95%; font-size: 0.8rem; line-height: 1.5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; box-shadow: 0 1px 2px rgba(0,0,0,0.12); position: relative; margin-bottom: 8px;">
                        <div id="waLivePreviewContent" style="white-space: pre-wrap; word-break: break-word;">
                            <!-- Live content rendered by JS -->
                        </div>
                        <div style="text-align: right; font-size: 0.65rem; color: #64748b; margin-top: 6px;">
                            {{ date('H:i') }} <i class="fas fa-check-double text-primary"></i>
                        </div>
                    </div>
                </div>
                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 10px;">
                    *Pratinjau ini memformat teks otomatis: tanda bintang (*) menjadi <strong>tebal</strong>, underscore (_)
                    menjadi <em>miring</em>, dan tilde (~) menjadi <del>coret</del>.
                </div>
            </div>
        </div>
    @elseif ($tab === 'garansi')
        <!-- TAB 4: Ketentuan & Kuota Garansi Kartu Pelajar -->
        <div class="dash-stat-grid"
            style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem;">
                        {{ number_format($stats['total_klaim_kartu'], 0, ',', '.') }} kali
                    </div>
                    <div class="dash-stat-label">Total Klaim Ganti Kartu Full (Fisik &amp; Stiker)</div>
                </div>
            </div>

            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                    <i class="fas fa-sticky-note"></i>
                </div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                        {{ number_format($stats['total_klaim_stiker'], 0, ',', '.') }} kali
                    </div>
                    <div class="dash-stat-label">Total Klaim Cetak Stiker Saja</div>
                </div>
            </div>
        </div>

        <!-- Form Pengaturan Kuota & Ketentuan -->
        <div class="card" style="padding: 24px; max-width: 860px; margin-bottom: 24px;">
            <div
                style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                <div
                    style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245,158,11,0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                        Pengaturan Kuota &amp; Ketentuan Garansi Kartu Pelajar
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                        Tentukan jatah garansi gratis per siswa serta kebijakan teknis pergantian kartu fisik atau stiker.
                    </div>
                </div>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.pengaturan') }}" method="POST">
                @csrf
                <input type="hidden" name="subtab" value="garansi">

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 20px;">
                    <div>
                        <label
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Kuota Ganti Kartu Penuh (Fisik PVC/RFID + Stiker) <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" name="kuota_ganti_kartu_full"
                                value="{{ old('kuota_ganti_kartu_full', $settings->kuota_ganti_kartu_full ?? 1) }}"
                                min="0" max="10" required class="form-control"
                                style="width: 100px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.9rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.85rem; color: var(--text-muted);">kali per peserta didik</span>
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                            Jatah penggantian satu paket kartu fisik utuh jika kartu patah/hilang.
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                            Kuota Cetak Ulang Stiker Kartu Saja <span style="color: #ef4444;">*</span>
                        </label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <input type="number" name="kuota_ganti_stiker"
                                value="{{ old('kuota_ganti_stiker', $settings->kuota_ganti_stiker ?? 3) }}"
                                min="0" max="20" required class="form-control"
                                style="width: 100px; height: 38px; padding: 0 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.9rem; font-weight: 700; text-align: center;">
                            <span style="font-size: 0.85rem; color: var(--text-muted);">kali per peserta didik</span>
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                            Jatah cetak ulang stiker jika pudar/terkelupas tanpa ganti kartu fisik RFID.
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Ketentuan &amp; SOP Garansi Kartu Pelajar
                    </label>
                    <textarea name="ketentuan_garansi" rows="5" class="form-control"
                        placeholder="Uraikan ketentuan garansi kartu pelajar resmi sekolah..."
                        style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; line-height: 1.5;">{{ old('ketentuan_garansi', $settings->ketentuan_garansi ?: \App\Models\KartuPelajarPengaturan::getDefaultGaransiKetentuan()) }}</textarea>
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary"
                        style="padding: 9px 22px; font-size: 0.86rem; font-weight: 700; border-radius: 8px;">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan Garansi
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat Log Klaim Garansi -->
        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <div
                style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h4
                    style="margin: 0; font-size: 0.95rem; font-weight: 800; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-history text-primary"></i> Riwayat Pengajuan Klaim Garansi Siswa
                </h4>
            </div>

            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02);">
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">
                            No</th>
                        <th
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Tanggal Klaim</th>
                        <th
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Peserta Didik</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Kelas / Rombel</th>
                        <th
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Jenis Klaim</th>
                        <th
                            style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Alasan Klaim</th>
                        <th
                            style="padding: 12px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                            Petugas Admin</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($garansiLogs && $garansiLogs->count() > 0)
                        @foreach ($garansiLogs as $gIdx => $log)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td
                                    style="text-align: center; font-size: 0.84rem; color: var(--text-muted); padding: 12px 14px;">
                                    {{ $garansiLogs->firstItem() + $gIdx }}
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($log->tanggal_klaim)->translatedFormat('d F Y H:i') }}
                                </td>
                                <td style="padding: 12px 16px;">
                                    <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-color);">
                                        {{ $log->nama_siswa ?? '-' }}</div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted); font-family: monospace;">
                                        NISN: {{ $log->nisn ?: '-' }}</div>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <span class="badge badge-outline" style="font-size: 0.76rem; padding: 3px 8px;">
                                        {{ $log->rombel_siswa ?? '-' }}
                                    </span>
                                </td>
                                <td style="padding: 12px 16px;">
                                    @if ($log->jenis_klaim === 'ganti_kartu_full')
                                        <span class="badge badge-primary" style="font-size: 0.74rem; padding: 4px 8px;">
                                            <i class="fas fa-id-card me-1"></i> Ganti Kartu Full
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.74rem; padding: 4px 8px;">
                                            <i class="fas fa-sticky-note me-1"></i> Cetak Stiker
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-color);">
                                    {{ $log->alasan }}
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);">
                                    <i class="fas fa-user-shield me-1 text-primary"></i> {{ $log->petugas ?: 'Admin' }}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="7" style="padding: 35px; text-align: center; color: var(--text-muted);">
                                <i class="fas fa-shield-alt mb-2"
                                    style="font-size: 2rem; opacity: 0.4; display: block;"></i>
                                <div>Belum ada riwayat pengajuan klaim garansi kartu pelajar.</div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($garansiLogs && $garansiLogs->hasPages())
            <div style="margin-bottom: 24px;">
                {{ $garansiLogs->links() }}
            </div>
        @endif
    @endif

    <!-- MODAL 1: Catat / Edit Pembayaran Kartu Pelajar -->
    <div id="modalEditPembayaran" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-wallet text-primary"></i> Catat / Edit Pembayaran Kartu Pelajar
                </h4>
                <button type="button" onclick="closeEditPembayaran()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Student Summary Info -->
            <div
                style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;">
                <div id="bayarPdNama" style="font-weight: 800; font-size: 0.95rem; color: var(--text-color);">-</div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                    NISN: <span id="bayarPdNisn">-</span> &bull; Kelas: <span id="bayarPdRombel">-</span>
                </div>
            </div>

            <form id="formEditPembayaran">
                <input type="hidden" name="peserta_didik_id" id="bayarPdId">

                <!-- Status Pembayaran Radio -->
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Status Pembayaran <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; gap: 18px; align-items: center;">
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: 0.86rem; font-weight: 700; color: #10b981; cursor: pointer;">
                            <input type="radio" name="status_pembayaran" value="lunas">
                            <i class="fas fa-check-circle"></i> LUNAS
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: 0.86rem; font-weight: 700; color: #f59e0b; cursor: pointer;">
                            <input type="radio" name="status_pembayaran" value="belum_bayar">
                            <i class="fas fa-clock"></i> BELUM BAYAR
                        </label>
                    </div>
                </div>

                <!-- Rincian Biaya & Metode (Toggle display if Lunas) -->
                <div id="bayarDetailFieldsWrap">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nominal / Biaya (Rp)
                            </label>
                            <input type="number" name="nominal" id="bayarNominal" class="form-control" value="25000"
                                min="0" step="1000"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Metode Pembayaran
                            </label>
                            <select name="metode_pembayaran" id="bayarMetode" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS / Digital</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Tanggal Pembayaran
                            </label>
                            <input type="date" name="tanggal_bayar" id="bayarTanggal" class="form-control"
                                value="{{ date('Y-m-d') }}"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Status Fisik Kartu
                            </label>
                            <select name="status_cetak" id="bayarStatusCetak" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                                <option value="belum_cetak">Belum Dicetak</option>
                                <option value="sudah_cetak">Sudah Dicetak</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 12px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Nomor Handphone / WhatsApp Orang Tua atau Siswa
                    </label>
                    <input type="text" name="nomor_wa" id="bayarNomorWa" class="form-control"
                        placeholder="Contoh: 08123456789"
                        style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Catatan Khusus (Opsional)
                    </label>
                    <textarea name="catatan" id="bayarCatatan" rows="2" class="form-control"
                        placeholder="Catatan bukti kwitansi / serah terima kartu..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.82rem;"></textarea>
                </div>

                <!-- Checkbox Auto-Kirim WhatsApp -->
                <div
                    style="background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.3); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px;">
                    <label
                        style="display: flex; align-items: center; gap: 8px; font-size: 0.82rem; font-weight: 700; color: #15803d; cursor: pointer; margin: 0;">
                        <input type="checkbox" id="chkKirimWaAuto" checked>
                        <span><i class="fab fa-whatsapp me-1"></i> Buka WhatsApp &amp; Kirim Bukti beserta QR Code setelah
                            disimpan</span>
                    </label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeEditPembayaran()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" id="btnSubmitPembayaran" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: 0.82rem;">
                        <i class="fas fa-check me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 1B: Penyesuaian Data Pembayaran & Cetak Masal (Bulk Action) -->
    <div id="modalBulkPembayaran" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-tasks text-primary"></i> Penyesuaian Data Pembayaran Masal
                </h4>
                <button type="button" onclick="closeModalBulkPembayaran()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Summary Count Alert -->
            <div
                style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.25); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.84rem; color: var(--primary); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-square"></i>
                <span>Menyesuaikan data untuk <strong id="bulkModalSelectedCount">0</strong> peserta didik terpilih
                    sekaligus.</span>
            </div>

            <form id="formBulkPembayaran">
                <input type="hidden" name="action" id="bulkModalAction" value="custom_bulk">

                <!-- 1. Status Pembayaran -->
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.84rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Status Pembayaran:
                    </label>
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #10b981; cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="lunas">
                            <i class="fas fa-check-circle"></i> Lunas
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 700; color: #f59e0b; cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="belum_bayar">
                            <i class="fas fa-clock"></i> Belum Bayar
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 6px; font-size: 0.84rem; font-weight: 600; color: var(--text-muted); cursor: pointer;">
                            <input type="radio" name="bulk_status_pembayaran" value="keep" checked>
                            <span>Tetap (Jangan Ubah)</span>
                        </label>
                    </div>
                </div>

                <!-- Detail Pembayaran (Aktif saat Lunas) -->
                <div id="bulkBayarDetailWrap"
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 10px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Nominal / Biaya (Rp)
                            </label>
                            <input type="number" name="bulk_nominal" id="bulkNominal" class="form-control"
                                value="{{ $settings->biaya_standar ?? 25000 }}" min="0" step="1000"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                                Metode Pembayaran
                            </label>
                            <select name="bulk_metode_pembayaran" id="bulkMetode" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS / Digital</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                            Tanggal Pembayaran
                        </label>
                        <input type="date" name="bulk_tanggal_bayar" id="bulkTanggalBayar" class="form-control"
                            value="{{ date('Y-m-d') }}"
                            style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                    </div>
                </div>

                <!-- 2. Status Fisik Kartu -->
                <div
                    style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; margin-bottom: 14px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                                Status Fisik Kartu:
                            </label>
                            <select name="bulk_status_cetak" id="bulkStatusCetak" class="form-control"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                                <option value="keep">Tetap (Jangan Ubah)</option>
                                <option value="sudah_cetak">Sudah Dicetak (Garansi Aktif)</option>
                                <option value="belum_cetak">Belum Dicetak</option>
                            </select>
                        </div>
                        <div id="bulkTanggalCetakWrap">
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                                Tanggal Selesai Dicetak:
                            </label>
                            <input type="date" name="bulk_tanggal_cetak" id="bulkTanggalCetak" class="form-control"
                                value="{{ date('Y-m-d') }}"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        </div>
                    </div>
                </div>

                <!-- 3. Catatan Khusus Massal (Opsional) -->
                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Catatan Khusus Massal (Opsional)
                    </label>
                    <textarea name="bulk_catatan" id="bulkCatatan" rows="2" class="form-control"
                        placeholder="Tambahkan catatan khusus untuk seluruh siswa terpilih..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.82rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeModalBulkPembayaran()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" id="btnSubmitBulkPembayaran" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-check-double"></i> Terapkan ke Semua Terpilih
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: Kirim Notifikasi WhatsApp & QR Code Langsung -->
    <div id="modalKirimWa" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fab fa-whatsapp text-success" style="font-size: 1.25rem;"></i> Kirim Informasi Pembayaran
                    &amp; QR Code
                </h4>
                <button type="button" onclick="closeModalWa()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <div id="waModalLoading" style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                <div>Memuat rincian pembayaran &amp; QR Code siswa...</div>
            </div>

            <div id="waModalBody" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 180px; gap: 16px; margin-bottom: 14px;">
                    <div>
                        <!-- Student Summary -->
                        <div
                            style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; margin-bottom: 12px;">
                            <div id="waTargetNama"
                                style="font-weight: 800; font-size: 0.92rem; color: var(--text-color);">-</div>
                            <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                                NISN: <span id="waTargetNisn">-</span> &bull; Kelas: <span id="waTargetRombel">-</span>
                            </div>
                        </div>

                        <div>
                            <label
                                style="display: block; font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px;">
                                Nomor WhatsApp Tujuan:
                            </label>
                            <input type="text" id="waInputPhone" class="form-control"
                                placeholder="Contoh: 08123456789 atau 628123456789"
                                style="width: 100%; height: 36px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem; font-family: monospace;">
                        </div>
                    </div>

                    <!-- Visual QR Code Card -->
                    <div
                        style="text-align: center; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <div id="waQrSvgContainer"
                            style="width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; margin-bottom: 4px;">
                            <!-- Live SVG QR Code rendered here -->
                        </div>
                        <div style="font-size: 0.65rem; color: #475569; font-weight: 700; margin-bottom: 6px;">
                            QR Code Kartu Siswa
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" onclick="unduhQrCodeSvg()"
                            style="font-size: 0.7rem; padding: 2px 8px; border-radius: 4px;" title="Unduh File QR SVG">
                            <i class="fas fa-download me-1"></i> Unduh QR
                        </button>
                    </div>
                </div>

                <!-- Teks Pesan Otomatis -->
                <div style="margin-bottom: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin: 0;">
                            Pesan Otomatis WhatsApp (Dapat Diedit):
                        </label>
                        <button type="button" class="btn btn-outline btn-sm" onclick="salinTeksPesanWa()"
                            style="padding: 2px 8px; font-size: 0.72rem; border-radius: 6px;">
                            <i class="fas fa-copy me-1"></i> Salin Teks
                        </button>
                    </div>
                    <textarea id="waTextPesan" rows="9" class="form-control"
                        style="width: 100%; padding: 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.78rem; font-family: monospace; line-height: 1.45;"></textarea>
                </div>

                <div
                    style="font-size: 0.74rem; color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-info-circle text-primary"></i>
                    <span>Tautan QR Code resmi yang dilampirkan: <a href="#" id="waVerifyLinkText"
                            target="_blank" style="color: var(--primary); text-decoration: underline;"></a></span>
                </div>

                <div
                    style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" onclick="closeModalWa()" class="btn btn-outline"
                        style="padding: 8px 16px; border-radius: 8px; font-size: 0.82rem;">Tutup</button>
                    <button type="button" onclick="kirimPesanWaSekarang()" class="btn"
                        style="background: #22c55e; color: #ffffff; padding: 8px 18px; border-radius: 8px; font-size: 0.84rem; font-weight: 700; border: none; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fab fa-whatsapp" style="font-size: 1rem;"></i> Buka Chat WhatsApp &amp; Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Klaim Garansi Kartu Pelajar (1x Ganti Kartu Full, 3x Stiker) -->
    <div id="modalKlaimGaransi" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-shield-alt text-warning"></i> Klaim Garansi Kartu Pelajar
                </h4>
                <button type="button" onclick="closeKlaimGaransi()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Student Summary Info -->
            <div
                style="background: var(--bg-hover); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;">
                <div id="garansiPdNama" style="font-weight: 800; font-size: 0.95rem; color: var(--text-color);">-</div>
                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                    NISN: <span id="garansiPdNisn">-</span> &bull; Kelas: <span id="garansiPdRombel">-</span>
                </div>
            </div>

            <!-- Banner Garansi Aktif (Jika status sudah cetak) -->
            <div id="garansiActiveNotice"
                style="display: none; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.25); color: #059669; padding: 10px 14px; border-radius: 8px; font-size: 0.82rem; margin-bottom: 14px; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <div>
                    <strong>Status Kartu: Sudah Dicetak</strong><br>
                    Garansi resmi otomatis berlaku untuk kedua fasilitas (Ganti Kartu Penuh &amp; Cetak Stiker).
                </div>
            </div>

            <!-- Banner Garansi Belum Aktif (Jika status belum cetak) -->
            <div id="garansiNotActiveNotice"
                style="display: none; background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.3); color: #d97706; padding: 10px 14px; border-radius: 8px; font-size: 0.82rem; margin-bottom: 14px; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.1rem; flex-shrink: 0;"></i>
                <div>
                    <strong>Garansi Belum Berlaku:</strong> Kartu fisik belum dicetak (Status: Belum Dicetak). Garansi resmi
                    baru otomatis berlaku setelah kartu berstatus Sudah Dicetak.
                </div>
            </div>

            <form id="formKlaimGaransi">
                <input type="hidden" name="peserta_didik_id" id="garansiPdId">

                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px; color: var(--text-color);">
                        Pilih Fasilitas Garansi yang Diklaim <span style="color: #ef4444;">*</span>
                    </label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label
                            style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-hover);">
                            <input type="radio" name="jenis_klaim" id="optGaransiKartu" value="ganti_kartu_full"
                                style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-color);">
                                    <i class="fas fa-id-card me-1 text-primary"></i> Ganti Kartu Penuh (Fisik PVC/RFID
                                    &amp; Stiker Baru)
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                    Sisa kuota: <strong id="garansiSisaKartu" style="color: var(--primary);">0
                                        kali</strong>
                                </div>
                            </div>
                        </label>

                        <label
                            style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border: 1px solid var(--border-color); border-radius: 8px; cursor: pointer; background: var(--bg-hover);">
                            <input type="radio" name="jenis_klaim" id="optGaransiStiker" value="cetak_stiker"
                                style="margin-top: 3px;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--text-color);">
                                    <i class="fas fa-sticky-note me-1 text-warning"></i> Cetak Ulang Stiker Kartu Pelajar
                                    Saja
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                                    Sisa kuota: <strong id="garansiSisaStiker" style="color: var(--primary);">0
                                        kali</strong>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 4px; color: var(--text-color);">
                        Alasan / Kendala Klaim <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="alasan" id="garansiAlasan" rows="3" required class="form-control"
                        placeholder="Contoh: Kartu fisik patah / chip RFID rusak tidak terbaca / stiker pudar..."
                        style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.82rem;"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeKlaimGaransi()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" id="btnSubmitGaransi" class="btn btn-primary"
                        style="padding: 7px 18px; font-size: 0.82rem;">
                        <i class="fas fa-check me-1"></i> Simpan Klaim Garansi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: Catat Pengeluaran Alat & Bahan Cetak -->
    <div id="modalTambahPengeluaran" class="modal-backdrop">
        <div class="card kp-modal-card">
            <div class="kp-modal-header">
                <h4 class="kp-modal-title">
                    <i class="fas fa-plus-circle text-danger"></i> Catat Pengeluaran Alat &amp; Bahan
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeTambahPengeluaran()"
                    aria-label="Tutup">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.store-pengeluaran') }}" method="POST"
                enctype="multipart/form-data" class="kp-modal-form">
                @csrf
                <div class="kp-modal-body">
                    <div class="kp-form-group">
                        <label class="kp-form-label" for="pgJudul">Uraian / Judul Pembelanjaan <span
                                class="req">*</span></label>
                        <input type="text" id="pgJudul" name="judul" required maxlength="255"
                            class="form-control" placeholder="Contoh: Beli 250 Kartu PVC RFID Blank + 1 Roll Ribbon">
                    </div>

                    <div class="kp-form-grid">
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="pgKategori">Kategori <span
                                    class="req">*</span></label>
                            <select id="pgKategori" name="kategori" required class="form-control">
                                @foreach ($kategoriList as $kKey => $kVal)
                                    <option value="{{ $kKey }}">{{ $kVal }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="pgNominal">Nominal (Rp) <span
                                    class="req">*</span></label>
                            <input type="number" id="pgNominal" name="nominal" min="1" step="500"
                                required inputmode="numeric" class="form-control" placeholder="350000">
                        </div>
                    </div>

                    <div class="kp-form-grid">
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="pgTanggal">Tanggal <span class="req">*</span></label>
                            <input type="date" id="pgTanggal" name="tanggal" value="{{ date('Y-m-d') }}"
                                required class="form-control">
                        </div>
                        <div class="kp-form-group">
                            <label class="kp-form-label" for="pgNota">Bukti Nota / Kwitansi</label>
                            <input type="file" id="pgNota" name="bukti_nota"
                                accept="image/jpeg,image/png,application/pdf,image/webp" class="form-control">
                            <div class="kp-form-hint">JPG, PNG, WebP, PDF. Maks. 5 MB.</div>
                        </div>
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="pgKeterangan">Keterangan / Toko (Opsional)</label>
                        <textarea id="pgKeterangan" name="keterangan" rows="2" maxlength="1000" class="form-control"
                            placeholder="Nama toko, nomor pesanan, atau spesifikasi barang..."></textarea>
                    </div>
                </div>

                <div class="kp-modal-footer">
                    <button type="button" onclick="closeTambahPengeluaran()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4B: Tambah Request Cetak Kartu Pelajar -->
    <div id="modalTambahRequest" class="modal-backdrop">
        <div class="card kp-modal-card">
            <div class="kp-modal-header">
                <h4 class="kp-modal-title">
                    <i class="fas fa-paper-plane text-primary"></i> Tambah Request Cetak Kartu
                </h4>
                <button type="button" class="kp-modal-close" onclick="closeTambahRequest()"
                    aria-label="Tutup">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.store-request') }}" method="POST" class="kp-modal-form"
                id="formTambahRequest">
                @csrf
                <input type="hidden" name="peserta_didik_id" id="rqPdId">
                <div class="kp-modal-body">
                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rqCari">Cari Peserta Didik <span
                                class="req">*</span></label>
                        <div class="kp-ac-wrap">
                            <input type="text" id="rqCari" class="form-control" autocomplete="off"
                                placeholder="Ketik nama / NISN / NIPD (min. 2 huruf)...">
                            <div id="rqAcList" class="kp-ac-list"></div>
                        </div>
                        <div class="kp-form-hint" id="rqPilihInfo">Belum ada peserta didik dipilih.</div>
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rqJenis">Jenis Request <span
                                class="req">*</span></label>
                        <select id="rqJenis" name="jenis_request" required class="form-control">
                            @foreach (\App\Models\KartuPelajarRequest::JENIS as $jKey => $jVal)
                                <option value="{{ $jKey }}">{{ $jVal }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="kp-form-group">
                        <label class="kp-form-label" for="rqAlasan">Alasan / Catatan</label>
                        <textarea id="rqAlasan" name="alasan" rows="3" maxlength="500" class="form-control"
                            placeholder="Contoh: Kartu hilang, chip tidak terbaca, siswa baru..."></textarea>
                    </div>
                </div>

                <div class="kp-modal-footer">
                    <button type="button" onclick="closeTambahRequest()" class="btn btn-outline">Batal</button>
                    <button type="submit" class="btn btn-primary" id="rqSubmit" disabled>
                        <i class="fas fa-paper-plane me-1"></i> Kirim Request
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 5: Dialog Pilih Tanggal Cetak untuk Lembar Pengambilan -->
    <div id="modalCetakPengambilan" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999 !important; align-items: center; justify-content: center; padding: 20px;">
        <div class="card"
            style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); padding: 22px;">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h4
                    style="margin: 0; font-size: 1.05rem; color: var(--text-color); font-weight: 700; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-clipboard-check text-primary"></i> Cetak Lembar Pengambilan Kartu
                </h4>
                <button type="button" onclick="closeModalCetakPengambilan()"
                    style="background: none; border: none; font-size: 1.2rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <form action="{{ route('dashboard.kartu-pelajar.cetak-pengambilan') }}" method="GET" target="_blank">
                <div style="margin-bottom: 14px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                        Pilih Tanggal Selesai Dicetak <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="date" name="tanggal_cetak" value="{{ date('Y-m-d') }}" required
                        class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.86rem;">
                    <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 4px;">
                        Mencetak daftar siswa yang status cetaknya selesai pada tanggal tersebut untuk diambil.
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label
                        style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 4px; color: var(--text-color);">
                        Filter Rombel / Kelas (Opsional)
                    </label>
                    <select name="rombel" class="form-control"
                        style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 6px; font-size: 0.84rem;">
                        <option value="">Semua Rombel (Seluruh Siswa yang Dicetak)</option>
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->nama }}">{{ $r->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" onclick="closeModalCetakPengambilan()" class="btn btn-outline"
                        style="padding: 7px 14px; font-size: 0.82rem;">Batal</button>
                    <button type="submit" class="btn btn-primary" onclick="closeModalCetakPengambilan()"
                        style="padding: 7px 18px; font-size: 0.82rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-print"></i> Buka Lembar Cetak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 6: Pratinjau & Cetak Lembar Kartu Pelajar Digital (Sama dengan Peserta Didik Aktif) -->
    @include('kartu-pelajar.modal-preview')

    <!-- MODAL 7: Dialog Pilih Rombel untuk Cetak Masal -->
    @include('kartu-pelajar.modal-cetak-rombel', ['filterRombel' => $rombelList->pluck('nama')])

@endsection

@push('scripts')
    <script
        src="{{ asset('js/kartu-pelajar-preview.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-preview.js')) ? filemtime(public_path('js/kartu-pelajar-preview.js')) : time() }}">
    </script>
    <script
        src="{{ asset('js/kartu-pelajar-manage.js') }}?v={{ file_exists(public_path('js/kartu-pelajar-manage.js')) ? filemtime(public_path('js/kartu-pelajar-manage.js')) : time() }}">
    </script>
@endpush
