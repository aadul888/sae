@extends('layouts.dashboard')

@section('title', 'Laporan & Rekapitulasi - Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <!-- 1. Header Banner & Actions -->
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div
                    style="width: 48px; height: 48px; border-radius: var(--radius-lg, 12px); background: rgba(236,72,153,0.12); color: #ec4899; display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl, 1.5rem); flex-shrink: 0;">
                    <i class="fas fa-file-lines"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                        Laporan &amp; Rekapitulasi Perpustakaan
                    </h2>
                    <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                        Rekap koleksi, sirkulasi/denda, dan kunjungan dalam satu dokumen periode.
                    </div>
                </div>
            </div>

            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.perpustakaan.laporan.cetak', request()->only('dari', 'sampai', 'kategori')) }}"
                    target="_blank" class="btn btn-primary"
                    style="display: inline-flex; align-items: center; gap: 6px; height: 38px; padding: 0 14px; border-radius: var(--radius-md, 8px);" title="Cetak Laporan Periode">
                    <i class="fas fa-print"></i> Cetak Laporan
                </a>
            </div>
        </div>

        @include('dashboard.perpus.partials.tab-nav')

        <!-- 3. Filter Periode -->
        <div class="card" style="padding: 16px 18px; margin-bottom: 20px;">
            <form method="GET" action="{{ route('dashboard.perpustakaan.laporan.index') }}"
                style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <div>
                    <label style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Dari Tanggal</label>
                    <input type="date" name="dari" value="{{ $dari }}" class="form-control" style="height: 38px;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Sampai Tanggal</label>
                    <input type="date" name="sampai" value="{{ $sampai }}" class="form-control" style="height: 38px;">
                </div>
                <div>
                    <label style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 4px;">Kategori Buku</label>
                    <select name="kategori" class="form-control" style="height: 38px; min-width: 160px;">
                        <option value="">Semua Kategori</option>
                        @foreach ($kategoriOptions as $opt)
                            <option value="{{ $opt }}" {{ $kategori === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 38px; padding: 0 16px;">
                    <i class="fas fa-filter"></i> Terapkan
                </button>
            </form>
        </div>

        <!-- 4. Stat Grid Ringkasan -->
        <div class="dash-stat-grid" style="margin-bottom: 20px;">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: var(--primary, #3b82f6);"><i class="fas fa-book"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalJudul, 0, ',', '.') }} Judul</div>
                    <div class="dash-stat-label">Total Koleksi ({{ number_format($totalEksemplar, 0, ',', '.') }} Eks)</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);"><i class="fas fa-book-reader"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalPinjam, 0, ',', '.') }} Transaksi</div>
                    <div class="dash-stat-label">Peminjaman Periode</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($terlambat, 0, ',', '.') }} Buku</div>
                    <div class="dash-stat-label">Terlambat Kembali</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6);"><i class="fas fa-users-line"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ number_format($totalKunjungan, 0, ',', '.') }} Orang</div>
                    <div class="dash-stat-label">Kunjungan Periode</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(236,72,153,0.12); color: #ec4899;"><i class="fas fa-money-bill-wave"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">Rp {{ number_format($dendaTerkumpul, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Denda Terkumpul</div>
                </div>
            </div>
        </div>

        <!-- 5. Rekap Koleksi per Kategori -->
        <div class="card" style="padding: 18px 20px; margin-bottom: 20px;">
            <h3 style="font-size: var(--text-md, 1rem); font-weight: 800; margin: 0 0 12px 0; color: var(--text-color);"><i class="fas fa-book-bookmark text-primary"></i> Rekapitulasi Koleksi per Kategori</h3>
            <div style="overflow-x: auto;">
                <table class="table-minimal-compact" style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 8px;">Kategori</th>
                            <th style="padding: 8px; text-align: right;">Judul</th>
                            <th style="padding: 8px; text-align: right;">Eksemplar</th>
                            <th style="padding: 8px; text-align: right;">Tersedia</th>
                            <th style="padding: 8px; text-align: right;">Dipinjam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapKoleksi as $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px; font-weight: 600;">{{ $row->kategori }}</td>
                                <td style="padding: 8px; text-align: right;">{{ number_format((int) $row->judul, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right;">{{ number_format((int) $row->eksemplar, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right;">{{ number_format((int) $row->tersedia, 0, ',', '.') }}</td>
                                <td style="padding: 8px; text-align: right;">{{ number_format(max(0, (int) $row->eksemplar - (int) $row->tersedia), 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 18px; text-align: center; color: var(--text-muted);">Belum ada data koleksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 6. Buku Paling Sering Dipinjam -->
        <div class="card" style="padding: 18px 20px; margin-bottom: 20px;">
            <h3 style="font-size: var(--text-md, 1rem); font-weight: 800; margin: 0 0 12px 0; color: var(--text-color);"><i class="fas fa-fire text-warning"></i> Buku Paling Sering Dipinjam</h3>
            <div style="overflow-x: auto;">
                <table class="table-minimal-compact" style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 8px; width: 50px;">#</th>
                            <th style="padding: 8px;">Judul Buku</th>
                            <th style="padding: 8px; text-align: right; width: 120px;">Kali Dipinjam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bukuPopuler as $idx => $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px; color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td style="padding: 8px; font-weight: 600;">{{ $row->judul }}</td>
                                <td style="padding: 8px; text-align: right;">{{ number_format((int) $row->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="padding: 18px; text-align: center; color: var(--text-muted);">Belum ada peminjaman pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 7. Rekap Kunjungan -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            <div class="card" style="padding: 18px 20px;">
                <h3 style="font-size: var(--text-md, 1rem); font-weight: 800; margin: 0 0 12px 0; color: var(--text-color);"><i class="fas fa-bullseye text-primary"></i> Kunjungan per Keperluan</h3>
                <table class="table-minimal-compact" style="width: 100%;">
                    <tbody>
                        @forelse ($rekapKunjunganKeperluan as $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 7px;">{{ $row->keperluan }}</td>
                                <td style="padding: 7px; text-align: right; font-weight: 700;">{{ number_format((int) $row->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td style="padding: 14px; text-align: center; color: var(--text-muted);">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card" style="padding: 18px 20px;">
                <h3 style="font-size: var(--text-md, 1rem); font-weight: 800; margin: 0 0 12px 0; color: var(--text-color);"><i class="fas fa-users-rectangle text-success"></i> Kunjungan per Rombel / Unit</h3>
                <table class="table-minimal-compact" style="width: 100%;">
                    <tbody>
                        @forelse ($rekapKunjunganUnit as $row)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 7px;">{{ $row->unit }}</td>
                                <td style="padding: 7px; text-align: right; font-weight: 700;">{{ number_format((int) $row->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td style="padding: 14px; text-align: center; color: var(--text-muted);">Belum ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
