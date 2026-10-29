@extends('layouts.dashboard')

@section('title', 'Laporan Tahun Ajaran â€” Perpustakaan')

@section('content')
    <div class="dash-content-inner">
        <div class="dash-banner" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div style="width:48px;height:48px;border-radius:var(--radius-lg,12px);background:rgba(236,72,153,0.12);color:#ec4899;display:flex;align-items:center;justify-content:center;font-size:var(--text-2xl,1.5rem);flex-shrink:0;">
                    <i class="fas fa-chart-column"></i>
                </div>
                <div>
                    <h2 style="font-size:var(--text-xl,1.25rem);font-weight:800;color:var(--text-color);margin:0;">Laporan Per Tahun Ajaran</h2>
                    <div style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-top:2px;">
                        Rekap bulanan (Juliâ€“Juni) dengan grafik â€” siap cetak ukuran A3.
                    </div>
                </div>
            </div>
            <a href="{{ route('dashboard.perpustakaan.laporan.tahunan.cetak', ['ta' => $ta]) }}" target="_blank"
                class="btn btn-primary" style="display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 14px;">
                <i class="fas fa-print"></i> Cetak A3
            </a>
        </div>

        @include('dashboard.perpus.partials.tab-nav')
        @include('dashboard.perpus.partials.sub-nav-laporan')

        <div class="card" style="padding:14px 16px;margin-bottom:20px;">
            <form method="GET" action="{{ route('dashboard.perpustakaan.laporan.tahunan') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                <div>
                    <label style="font-size:var(--text-xs,0.78rem);font-weight:700;color:var(--text-muted);display:block;margin-bottom:4px;">Tahun Ajaran</label>
                    <select name="ta" class="form-control" style="height:38px;min-width:150px;">
                        @foreach ($taOptions as $opt)
                            <option value="{{ $opt }}" {{ $ta === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height:38px;padding:0 16px;"><i class="fas fa-filter"></i> Tampilkan</button>
                <span style="font-size:var(--text-sm,0.82rem);color:var(--text-muted);">Periode: {{ $periodeLabel }}</span>
            </form>
        </div>

        <div class="dash-stat-grid" style="margin-bottom:20px;">
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(59,130,246,0.12);color:var(--primary,#3b82f6);"><i class="fas fa-book-reader"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($totals['pinjam'], 0, ',', '.') }}</div><div class="dash-stat-label">Total Peminjaman</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(139,92,246,0.12);color:var(--purple,#8b5cf6);"><i class="fas fa-users-line"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($totals['kunjungan'], 0, ',', '.') }}</div><div class="dash-stat-label">Total Kunjungan</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(16,185,129,0.12);color:var(--success,#10b981);"><i class="fas fa-user-plus"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">{{ number_format($totals['anggota_baru'], 0, ',', '.') }}</div><div class="dash-stat-label">Anggota Baru</div></div></div>
            <div class="dash-stat-card"><div class="dash-stat-icon" style="background:rgba(236,72,153,0.12);color:#ec4899;"><i class="fas fa-money-bill-wave"></i></div>
                <div class="dash-stat-info"><div class="dash-stat-value">Rp {{ number_format($totals['biaya_aset'], 0, ',', '.') }}</div><div class="dash-stat-label">Nilai Aset Buku Baru</div></div></div>
        </div>

        <div class="card" style="padding:18px 20px;margin-bottom:20px;">
            <h3 style="margin:0 0 14px;font-size:var(--text-md,1rem);font-weight:800;"><i class="fas fa-chart-line" style="color:var(--primary,#3b82f6);"></i> Tren Bulanan</h3>
            <div style="height:280px;"><canvas id="chartTahunanPerpus"></canvas></div>
        </div>

        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:14px 16px;border-bottom:1px solid var(--border-color);font-weight:800;"><i class="fas fa-table"></i> Rincian per Bulan</div>
            <div style="overflow-x:auto;">
                <table class="table-minimal-compact" style="width:100%;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border-color);">
                            <th style="padding:9px;">Bulan</th>
                            <th style="padding:9px;text-align:right;">Peminjaman</th>
                            <th style="padding:9px;text-align:right;">Dikembalikan</th>
                            <th style="padding:9px;text-align:right;">Kunjungan</th>
                            <th style="padding:9px;text-align:right;">Anggota Baru</th>
                            <th style="padding:9px;text-align:right;">Buku Baru</th>
                            <th style="padding:9px;text-align:right;">Denda</th>
                            <th style="padding:9px;text-align:right;">Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bulan as $b)
                            <tr style="border-bottom:1px solid var(--border-color);">
                                <td style="padding:9px;font-weight:600;">{{ $b['label'] }}</td>
                                <td style="padding:9px;text-align:right;">{{ number_format($b['pinjam'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">{{ number_format($b['kembali'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">{{ number_format($b['kunjungan'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">{{ number_format($b['anggota_baru'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">{{ number_format($b['buku_baru'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">Rp {{ number_format($b['denda'], 0, ',', '.') }}</td>
                                <td style="padding:9px;text-align:right;">Rp {{ number_format($b['biaya_aset'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--bg-hover);font-weight:800;">
                            <td style="padding:9px;">TOTAL</td>
                            <td style="padding:9px;text-align:right;">{{ number_format($totals['pinjam'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">{{ number_format($totals['kembali'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">{{ number_format($totals['kunjungan'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">{{ number_format($totals['anggota_baru'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">{{ number_format($totals['buku_baru'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">Rp {{ number_format($totals['denda'], 0, ',', '.') }}</td>
                            <td style="padding:9px;text-align:right;">Rp {{ number_format($totals['biaya_aset'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
        <script>
            (function () {
                const data = @json($bulan);
                const el = document.getElementById('chartTahunanPerpus');
                if (!el || typeof Chart === 'undefined') return;
                new Chart(el.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.label),
                        datasets: [
                            { label: 'Peminjaman', data: data.map(d => d.pinjam), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.15)', tension: 0.35, fill: true },
                            { label: 'Kunjungan', data: data.map(d => d.kunjungan), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.12)', tension: 0.35, fill: true },
                            { label: 'Anggota Baru', data: data.map(d => d.anggota_baru), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', tension: 0.35 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            })();
        </script>
    @endpush
@endsection
