<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perpustakaan TA {{ $ta }} â€” A3</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        @page { size: A3 landscape; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; margin: 0; background: #e5e7eb; }
        .sheet { background: #fff; width: 397mm; min-height: 273mm; margin: 0 auto 16px; padding: 10mm 12mm; box-sizing: border-box; }
        .no-print { position: sticky; top: 0; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; gap: 10px; justify-content: flex-end; }
        .no-print a, .no-print button { padding: 8px 16px; border-radius:var(--radius-md); border: none; font-weight: 700; cursor: pointer; text-decoration: none; font-size: 13px; }
        .btn-print { background: #3b82f6; color: #fff; }
        .btn-back { background: #334155; color: #fff; }
        table.data { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.data th, table.data td { border: 1px solid #64748b; padding: 4px 6px; }
        table.data thead th { background: #f1f5f9; }
        .title { text-align: center; margin: 10px 0 14px; }
        .title h2 { margin: 0; font-size: 15pt; text-transform: uppercase; }
        .chart-wrap { display: flex; gap: 16px; margin: 14px 0; }
        .chart-box { flex: 1; border: 1px solid #cbd5e1; border-radius:var(--radius-md); padding: 8px; }
        .chart-box canvas { width: 100%; height: 300px; }
        .ttd { display: flex; justify-content: space-between; margin-top: 28px; font-size: 10pt; text-align: center; }
        .ttd div { width: 30%; }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <a href="{{ route('dashboard.perpustakaan.laporan.tahunan', ['ta' => $ta]) }}" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Cetak A3 (Print / PDF)</button>
    </div>

    <div class="sheet">
        @include('partials.kop-surat')

        <div class="title">
            <h2>Laporan Tahunan Perpustakaan</h2>
            <div style="font-size:11pt;font-weight:700;">Tahun Ajaran {{ $ta }} &bull; {{ $periodeLabel }}</div>
        </div>

        <div class="chart-wrap">
            <div class="chart-box"><canvas id="c1"></canvas></div>
            <div class="chart-box"><canvas id="c2"></canvas></div>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:150px;">Bulan</th>
                    <th>Peminjaman</th>
                    <th>Dikembalikan</th>
                    <th>Kunjungan</th>
                    <th>Anggota Baru</th>
                    <th>Buku Baru</th>
                    <th>Denda (Rp)</th>
                    <th>Nilai Aset Buku Baru (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bulan as $b)
                    <tr>
                        <td style="font-weight:600;">{{ $b['label'] }}</td>
                        <td style="text-align:right;">{{ number_format($b['pinjam'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['kembali'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['kunjungan'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['anggota_baru'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['buku_baru'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['denda'], 0, ',', '.') }}</td>
                        <td style="text-align:right;">{{ number_format($b['biaya_aset'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="font-weight:800;background:#f8fafc;">
                    <td>TOTAL</td>
                    <td style="text-align:right;">{{ number_format($totals['pinjam'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['kembali'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['kunjungan'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['anggota_baru'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['buku_baru'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['denda'], 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($totals['biaya_aset'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="ttd">
            <div>Mengetahui,<br><strong>Kepala Sekolah</strong><div style="height:55px;"></div>
                <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                NIP. {{ $kepalaSekolah->nip ?? '......................' }}</div>
            <div>&nbsp;</div>
            <div>{{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                <strong>Pustakawan / Pengelola Perpustakaan</strong><div style="height:55px;"></div>
                <strong><u>{{ $pustakawan->nama ?? '( .............................................. )' }}</u></strong><br>
                NIP. {{ $pustakawan->nip ?? '......................' }}</div>
        </div>
    </div>

    <script src="{{ asset('vendor/chartjs/chart.umd.js') }}"></script>
    <script>
        const data = @json($bulan);
        const labels = data.map(d => d.label);
        const baseOpts = { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } };

        if (typeof Chart !== 'undefined') {
            new Chart(document.getElementById('c1').getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Peminjaman', data: data.map(d => d.pinjam), backgroundColor: '#3b82f6' },
                    { label: 'Kunjungan', data: data.map(d => d.kunjungan), backgroundColor: '#8b5cf6' },
                    { label: 'Dikembalikan', data: data.map(d => d.kembali), backgroundColor: '#10b981' }
                ] },
                options: baseOpts
            });
            new Chart(document.getElementById('c2').getContext('2d'), {
                type: 'line',
                data: { labels, datasets: [
                    { label: 'Anggota Baru', data: data.map(d => d.anggota_baru), borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,0.15)', fill: true, tension: 0.35 },
                    { label: 'Buku Baru', data: data.map(d => d.buku_baru), borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.15)', fill: true, tension: 0.35 }
                ] },
                options: baseOpts
            });
        }

        // Render chart selesai sebelum print otomatis
        window.addEventListener('load', () => { setTimeout(() => { /* siap cetak */ }, 400); });
    </script>
</body>

</html>
