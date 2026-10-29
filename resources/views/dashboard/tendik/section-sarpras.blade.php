{{-- Section Dashboard Induk: Sarana & Prasarana (Kendali Pusat Seluruh Urusan Sarpras) --}}

@php
    $rp = $sarprasRingkasan ?? [];
    $rpCharts = $sarprasCharts ?? [];
    $rpDaftar = $sarprasDaftar ?? [];
    $rpLintas = $sarprasLintasBidang ?? [];
    $rpPj = $sarprasPenanggungJawab ?? [];
    $rpWarn = $sarprasPeringatan ?? [];

    $toneMap = [
        'danger' => ['bg' => 'rgba(239, 68, 68, 0.12)', 'fg' => 'var(--danger, #ef4444)', 'border' => 'rgba(239, 68, 68, 0.28)'],
        'warning' => ['bg' => 'rgba(245, 158, 11, 0.12)', 'fg' => 'var(--warning, #f59e0b)', 'border' => 'rgba(245, 158, 11, 0.28)'],
        'info' => ['bg' => 'rgba(59, 130, 246, 0.12)', 'fg' => 'var(--primary, #3b82f6)', 'border' => 'rgba(59, 130, 246, 0.28)'],
    ];

    $kondisiLabel = [
        'baik' => 'Baik',
        'rusak_ringan' => 'Rusak Ringan',
        'rusak_berat' => 'Rusak Berat',
    ];

    $statusWoLabel = [
        'antrean' => 'Antrean',
        'proses' => 'Proses',
        'menunggu_sparepart' => 'Menunggu Part',
        'selesai' => 'Selesai',
    ];

    $statusPinjamLabel = [
        'menunggu' => 'Menunggu',
        'dipinjam' => 'Dipinjam',
        'kembali' => 'Dikembalikan',
        'terlambat' => 'Terlambat',
    ];

    $urgensiLabel = [
        'rendah' => 'Rendah',
        'normal' => 'Normal',
        'sedang' => 'Sedang',
        'tinggi' => 'Tinggi',
        'darurat' => 'Darurat',
        'kritis' => 'Kritis',
    ];

    // Gerbang hak akses untuk tautan akses cepat lintas modul.
    $canModul = fn(string $key) => \App\Models\RolePermission::canAccess(
        session('user') ?: 'tendik',
        $key
    );
@endphp

<!-- 1. Akses Cepat Kendali Sarpras -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.sarpras.ruang.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--primary, #3b82f6);" title="Master Data Ruang &amp; Gedung">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-door-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Ruang &amp; Gedung</span>
    </a>

    <a href="{{ route('dashboard.sarpras.kir.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--primary-light, #6366f1);" title="Kartu Inventaris Ruangan (KIR)">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Kartu KIR</span>
    </a>

    <a href="{{ route('dashboard.sarpras.aset.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--success, #10b981);" title="Inventaris &amp; Aset Sarpras">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <span class="kepegawaian-quick-label">Inventaris Aset</span>
    </a>

    <a href="{{ route('dashboard.sarpras.persediaan.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--warning, #f59e0b);" title="Barang Habis Pakai &amp; ATK Sekolah">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-boxes-packing"></i>
        </div>
        <span class="kepegawaian-quick-label">Barang ATK/BHP</span>
    </a>

    <a href="{{ route('dashboard.sarpras.peminjaman.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--accent, #06b6d4);" title="Peminjaman &amp; Pengembalian Sarpras">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
            <i class="fas fa-hand-holding"></i>
        </div>
        <span class="kepegawaian-quick-label">Peminjaman</span>
    </a>

    <a href="{{ route('dashboard.sarpras.laporan.index') }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--success, #10b981);" title="Buku Induk &amp; Laporan Inventaris">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-book-bookmark"></i>
        </div>
        <span class="kepegawaian-quick-label">Buku Induk Aset</span>
    </a>

    @if ($canModul('menu_teknisi'))
        <a href="{{ route('dashboard.teknisi.work-order') }}" class="kepegawaian-quick-btn"
            style="--quick-color: var(--purple, #8b5cf6);" title="Work Order Perbaikan Fasilitas">
            <div class="kepegawaian-quick-icon"
                style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);">
                <i class="fas fa-screwdriver-wrench"></i>
            </div>
            <span class="kepegawaian-quick-label">Work Order</span>
        </a>
    @endif

    @if ($canModul('menu_laboran'))
        <a href="{{ route('dashboard.laboran.pengadaan.index') }}" class="kepegawaian-quick-btn"
            style="--quick-color: var(--accent, #06b6d4);" title="Verifikasi Usulan Pengadaan Laboratorium">
            <div class="kepegawaian-quick-icon"
                style="background: rgba(6, 182, 212, 0.15); color: var(--accent, #06b6d4);">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <span class="kepegawaian-quick-label">Usulan Pengadaan</span>
        </a>
    @endif

    <a href="{{ route('dashboard.tendik.target.index', ['bidang' => 'sarpras']) }}" class="kepegawaian-quick-btn"
        style="--quick-color: #ec4899;" title="Target &amp; Capaian Kinerja Sarpras">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.aktivitas.index', ['bidang' => 'sarpras']) }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--primary-light, #6366f1);" title="Input Log Aktivitas Harian Sarpras">
        <div class="kepegawaian-quick-icon"
            style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <span class="kepegawaian-quick-label">Aktivitas Harian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'sarpras']) }}" class="kepegawaian-quick-btn"
        style="--quick-color: var(--info, #0ea5e9);" title="Laporan &amp; Rekapitulasi Kinerja Sarpras">
        <div class="kepegawaian-quick-icon" style="background: rgba(14, 165, 233, 0.15); color: var(--info, #0ea5e9);">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. KPI Induk Sarpras (Data Nyata) -->
<div class="dash-stat-grid" style="margin-bottom: 18px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);">
            <i class="fas fa-building"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($rp['ruang_total'] ?? 0, 0, ',', '.') }} Ruang</div>
            <div class="dash-stat-label">{{ number_format($rp['gedung_total'] ?? 0, 0, ',', '.') }} Gedung Terdata
            </div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);">
            <i class="fas fa-boxes-stacked"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ number_format($rp['aset_unit'] ?? 0, 0, ',', '.') }} Unit</div>
            <div class="dash-stat-label">{{ number_format($rp['aset_jenis'] ?? 0, 0, ',', '.') }} Jenis Aset</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);">
            <i class="fas fa-screwdriver-wrench"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">
                {{ number_format(($rp['wo_antrean'] ?? 0) + ($rp['wo_proses'] ?? 0), 0, ',', '.') }} Tiket
            </div>
            <div class="dash-stat-label">Perbaikan Berjalan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary-light, #6366f1);">
            <i class="fas fa-gauge-high"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $rp['skor_kelayakan'] ?? 0 }}%</div>
            <div class="dash-stat-label">Skor Kelayakan Fisik</div>
        </div>
    </div>
</div>

<!-- Tab bidang tugas (desktop: tab, mobile: dropdown) -->
@include('dashboard.tendik.partials.domain-tabs')


<!-- 3. Peringatan Operasional -->
@if (!empty($rpWarn))
    <div class="card"
        style="padding: 16px 18px; margin-bottom: 18px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-md, 1rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bell-concierge" style="color: var(--warning, #f59e0b);"></i> Perlu Tindak Lanjut
            </div>
            <span class="badge badge-warning" style="font-size: var(--text-xs, 0.75rem); padding: 4px 9px;">
                {{ count($rpWarn) }} kategori
            </span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px;">
            @foreach ($rpWarn as $w)
                @php $tone = $toneMap[$w['tone']] ?? $toneMap['info']; @endphp
                <div
                    style="display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border-radius: var(--radius-lg, 12px); background: {{ $tone['bg'] }}; border: 1px solid {{ $tone['border'] }};">
                    <i class="fas {{ $w['icon'] }}" style="color: {{ $tone['fg'] }}; margin-top: 2px;"></i>
                    <div style="min-width: 0;">
                        <div
                            style="font-weight: var(--font-bold); font-size: var(--text-base, 0.875rem); color: {{ $tone['fg'] }};">
                            {{ number_format($w['count'], 0, ',', '.') }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.4;">
                            {{ $w['text'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<!-- 4. Kendali Modul Turunan (Laboran, Teknisi, Fasilitas & Penjaga, Keamanan) -->
<div class="card"
    style="padding: 20px 22px; margin-bottom: 22px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3
                style="font-size: var(--text-lg, 1.125rem); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-sitemap" style="color: var(--primary, #3b82f6);"></i> Kendali Bidang Turunan Sarpras
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Sarpras memegang master ruang &amp; aset yang dipakai Laboran, Teknisi/IT, Fasilitas &amp; Penjaga,
                serta Keamanan.
            </p>
        </div>
        <div
            style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--warning, #f59e0b); background: rgba(245, 158, 11, 0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(245, 158, 11, 0.25);">
            <i class="fas fa-diagram-project me-1"></i> {{ count($rpLintas) }} Modul Terhubung
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px;">
        @foreach ($rpLintas as $m)
            @php $aktif = !empty($m['allowed']); @endphp
            <{{ $aktif ? 'a' : 'div' }}
                @if ($aktif) href="{{ route($m['route']) }}" @endif
                title="{{ $m['label'] }} — {{ $m['desc'] }}{{ $aktif ? '' : ' (hanya monitoring, hak akses modul belum diberikan)' }}"
                style="display: flex; align-items: flex-start; gap: 12px; padding: 12px 14px; border-radius: var(--radius-lg, 12px); border: 1px solid var(--border-color); background: var(--bg-hover); text-decoration: none; {{ $aktif ? '' : 'opacity: 0.72;' }}">
                <div
                    style="width: 38px; height: 38px; flex-shrink: 0; border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.06); color: {{ $m['color'] }}; font-size: var(--text-lg, 1.125rem);">
                    <i class="fas {{ $m['icon'] }}"></i>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
                        <span
                            style="font-weight: var(--font-bold); font-size: var(--text-base, 0.875rem); color: var(--text-color);">{{ $m['label'] }}</span>
                        <span
                            style="font-weight: var(--font-extrabold); font-size: var(--text-md, 1rem); color: {{ $m['color'] }};">
                            {{ number_format($m['count'], 0, ',', '.') }}
                        </span>
                    </div>
                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); line-height: 1.4; margin-top: 2px;">
                        {{ $m['desc'] }}
                    </div>
                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 4px;">
                        @if ($aktif)
                            <i class="fas fa-layer-group me-1"></i>{{ number_format($m['count'], 0, ',', '.') }}
                            {{ $m['satuan'] }}
                        @else
                            <i class="fas fa-eye me-1"></i>Monitoring — akses modul belum diberikan
                        @endif
                    </div>
                </div>
            </{{ $aktif ? 'a' : 'div' }}>
        @endforeach
    </div>
</div>

<!-- 5. Visualisasi Statistik Sarpras -->
<div class="card"
    style="padding: 20px 22px; margin-bottom: 22px; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3
                style="font-size: var(--text-lg, 1.125rem); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie" style="color: var(--primary, #3b82f6);"></i> Statistik Fasilitas, Aset &amp;
                Layanan
            </h3>
            <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin: 3px 0 0 0;">
                Sebaran kondisi fisik, komposisi kategori aset, antrean perbaikan teknisi, serta tren peminjaman sarana.
            </p>
        </div>
        <div
            style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--success, #10b981); background: rgba(16, 185, 129, 0.1); padding: 4px 10px; border-radius: var(--radius-2xl, 20px); border: 1px solid rgba(16, 185, 129, 0.25);">
            <i class="fas fa-database me-1"></i> Data Real-time
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-door-open" style="color: var(--primary, #3b82f6);"></i> Kondisi Ruang
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Baik, rusak
                ringan &amp; rusak berat</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartKondisiRuang"></canvas>
                @if (array_sum($rpCharts['kondisiRuang'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada data ruang
                    </div>
                @endif
            </div>
        </div>

        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-toolbox" style="color: var(--success, #10b981);"></i> Kondisi Aset (Unit)
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Akumulasi unit
                per kondisi fisik</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartKondisiAset"></canvas>
                @if (array_sum($rpCharts['kondisiAset'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada data aset
                    </div>
                @endif
            </div>
        </div>

        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-boxes-stacked" style="color: var(--warning, #f59e0b);"></i> Kategori Aset
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Elektronik,
                mebel, alat peraga &amp; mesin</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartKategori"></canvas>
                @if (array_sum($rpCharts['kategoriAset'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada kategori aset
                    </div>
                @endif
            </div>
        </div>

        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-city" style="color: var(--primary, #3b82f6);"></i> Sebaran Ruang per Gedung
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Jumlah ruang
                pada tiap kompleks gedung</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartGedung"></canvas>
                @if (array_sum($rpCharts['gedung'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada data gedung
                    </div>
                @endif
            </div>
        </div>

        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-wrench" style="color: var(--purple, #8b5cf6);"></i> Status Work Order
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">Antrean,
                proses, menunggu part &amp; selesai</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartWorkOrder"></canvas>
                @if (array_sum($rpCharts['statusWorkOrder'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada work order
                    </div>
                @endif
            </div>
        </div>

        <div
            style="background: var(--bg-hover); border-radius: var(--radius-lg, 12px); padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div
                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-arrow-right-arrow-left" style="color: var(--success, #10b981);"></i> Tren Peminjaman
            </div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 10px;">6 bulan
                terakhir: pinjam vs kembali</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="sarprasChartTren"></canvas>
                @if (array_sum($rpCharts['trenPeminjaman']['pinjam'] ?? []) + array_sum($rpCharts['trenPeminjaman']['kembali'] ?? []) === 0)
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                        Belum ada transaksi peminjaman
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 6. Daftar Tindak Lanjut Operasional -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 18px; margin-bottom: 22px;">
    {{-- 6.1 Ruang Bermasalah --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-triangle-exclamation" style="color: var(--warning, #f59e0b);"></i> Ruang Perlu Perbaikan
            </div>
            <a href="{{ route('dashboard.sarpras.ruang.index', ['kondisi' => 'rusak_berat']) }}"
                style="font-size: var(--text-xs, 0.75rem); font-weight: var(--font-bold); color: var(--primary, #3b82f6); text-decoration: none;"
                title="Lihat semua ruang rusak berat">Lihat semua</a>
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 150px;">Ruang</th>
                        <th style="min-width: 110px;">Gedung</th>
                        <th style="width: 110px; text-align: center;">Kondisi</th>
                        <th style="min-width: 120px;">Penanggung Jawab</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpDaftar['ruangBermasalah'] ?? [] as $row)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Ruang"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['nama_ruang'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    {{ $row['kode_ruang'] }}
                                </div>
                            </td>
                            <td data-label="Gedung"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['gedung'] ?: '—' }}
                            </td>
                            <td data-label="Kondisi" style="text-align: center;">
                                <span
                                    class="badge {{ $row['kondisi'] === 'rusak_berat' ? 'badge-danger' : 'badge-warning' }}"
                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                                    {{ $kondisiLabel[$row['kondisi']] ?? $row['kondisi'] }}
                                </span>
                            </td>
                            <td data-label="Penanggung Jawab"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['pj_nama'] ?: 'Belum ditetapkan' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Seluruh ruang dalam kondisi baik.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6.2 Aset Perbaikan / Rusak --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-screwdriver-wrench" style="color: var(--danger, #ef4444);"></i> Aset Perlu Penanganan
            </div>
            <a href="{{ route('dashboard.sarpras.aset.index') }}"
                style="font-size: var(--text-xs, 0.75rem); font-weight: var(--font-bold); color: var(--primary, #3b82f6); text-decoration: none;"
                title="Buka inventaris aset">Kelola aset</a>
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 160px;">Nama Barang</th>
                        <th style="min-width: 100px;">Kategori</th>
                        <th style="width: 90px; text-align: center;">Jumlah</th>
                        <th style="min-width: 110px;">Lokasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpDaftar['asetPerbaikan'] ?? [] as $row)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Nama Barang"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['nama_barang'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    {{ $row['kode_aset'] }}
                                </div>
                            </td>
                            <td data-label="Kategori"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['kategori'] }}
                            </td>
                            <td data-label="Jumlah"
                                style="text-align: center; font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ number_format((int) $row['jumlah'], 0, ',', '.') }}
                            </td>
                            <td data-label="Lokasi"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['nama_ruang'] ?: 'Belum ditempatkan' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Tidak ada aset berstatus perbaikan atau rusak.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6.3 Work Order Terbaru --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clipboard-list" style="color: var(--purple, #8b5cf6);"></i> Work Order Terbaru
            </div>
            @if ($canModul('menu_teknisi'))
                <a href="{{ route('dashboard.teknisi.work-order') }}"
                    style="font-size: var(--text-xs, 0.75rem); font-weight: var(--font-bold); color: var(--primary, #3b82f6); text-decoration: none;"
                    title="Buka seluruh work order">Semua tiket</a>
            @endif
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 140px;">Nomor / Lokasi</th>
                        <th style="min-width: 120px;">Kerusakan</th>
                        <th style="width: 100px; text-align: center;">Urgensi</th>
                        <th style="width: 110px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpDaftar['woTerbaru'] ?? [] as $row)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Nomor / Lokasi"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['nomor_wo'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    {{ $row['lokasi_unit'] }}
                                </div>
                            </td>
                            <td data-label="Kerusakan"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ \Illuminate\Support\Str::limit($row['deskripsi_kerusakan'], 42) }}
                            </td>
                            <td data-label="Urgensi" style="text-align: center;">
                                <span
                                    class="badge {{ $row['tingkat_urgensi'] === 'darurat' ? 'badge-danger' : ($row['tingkat_urgensi'] === 'rendah' ? 'badge-info' : 'badge-warning') }}"
                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                                    {{ $urgensiLabel[$row['tingkat_urgensi']] ?? $row['tingkat_urgensi'] }}
                                </span>
                            </td>
                            <td data-label="Status"
                                style="text-align: center; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $statusWoLabel[$row['status']] ?? $row['status'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Belum ada work order tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6.4 Peminjaman Aktif --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-hand-holding" style="color: var(--warning, #f59e0b);"></i> Peminjaman Berjalan
            </div>
            <a href="{{ route('dashboard.sarpras.peminjaman.index') }}"
                style="font-size: var(--text-xs, 0.75rem); font-weight: var(--font-bold); color: var(--primary, #3b82f6); text-decoration: none;"
                title="Buka modul peminjaman">Kelola</a>
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 150px;">Peminjam</th>
                        <th style="min-width: 150px;">Barang</th>
                        <th style="width: 120px; text-align: center;">Jatuh Tempo</th>
                        <th style="width: 110px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpDaftar['peminjamanAktif'] ?? [] as $row)
                        @php
                            $lewat =
                                $row['tanggal_kembali_rencana'] &&
                                $row['tanggal_kembali_rencana'] < now()->toDateString();
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Peminjam"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['peminjam_nama'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    {{ $row['nomor_pinjam'] }}
                                </div>
                            </td>
                            <td data-label="Barang"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['nama_barang'] ?: '—' }}
                            </td>
                            <td data-label="Jatuh Tempo"
                                style="text-align: center; font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: {{ $lewat ? 'var(--danger, #ef4444)' : 'var(--text-color)' }};">
                                {{ $row['tanggal_kembali_rencana'] ? \Illuminate\Support\Carbon::parse($row['tanggal_kembali_rencana'])->translatedFormat('d M Y') : '—' }}
                            </td>
                            <td data-label="Status" style="text-align: center;">
                                <span class="badge {{ $lewat ? 'badge-danger' : 'badge-warning' }}"
                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                                    {{ $statusPinjamLabel[$row['status']] ?? $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Tidak ada peminjaman aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6.5 Usulan Pengadaan Laboratorium --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-invoice-dollar" style="color: var(--accent, #06b6d4);"></i> Usulan Pengadaan Masuk
            </div>
            @if ($canModul('menu_laboran'))
                <a href="{{ route('dashboard.laboran.pengadaan.index') }}"
                    style="font-size: var(--text-xs, 0.75rem); font-weight: var(--font-bold); color: var(--primary, #3b82f6); text-decoration: none;"
                    title="Verifikasi usulan pengadaan laboratorium">Verifikasi</a>
            @endif
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 140px;">Nomor / Jurusan</th>
                        <th style="width: 90px; text-align: center;">Item</th>
                        <th style="min-width: 120px; text-align: right;">Estimasi</th>
                        <th style="width: 100px; text-align: center;">Urgensi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpDaftar['pengadaanMenunggu'] ?? [] as $row)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Nomor / Jurusan"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['nomor_usulan'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    {{ $row['jurusan'] ?: ($row['pemohon_nama'] ?: 'Umum') }}
                                </div>
                            </td>
                            <td data-label="Item"
                                style="text-align: center; font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ number_format((int) $row['total_item'], 0, ',', '.') }}
                            </td>
                            <td data-label="Estimasi"
                                style="text-align: right; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                Rp {{ number_format((float) $row['total_estimasi_biaya'], 0, ',', '.') }}
                            </td>
                            <td data-label="Urgensi" style="text-align: center;">
                                <span
                                    class="badge {{ in_array($row['urgensi'], ['kritis', 'tinggi'], true) ? 'badge-danger' : 'badge-info' }}"
                                    style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                                    {{ $urgensiLabel[$row['urgensi']] ?? $row['urgensi'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Tidak ada usulan pengadaan menunggu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 6.6 Penanggung Jawab Ruang --}}
    <div class="card"
        style="padding: 0; overflow: hidden; border-radius: var(--radius-xl, 16px); border: 1px solid var(--border-color); background: var(--card-bg);">
        <div
            style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 10px;">
            <div
                style="font-weight: var(--font-extrabold); font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-user-shield" style="color: var(--success, #10b981);"></i> Penanggung Jawab Ruang
            </div>
            <span class="badge badge-info" style="font-size: var(--text-2xs, 0.6875rem); padding: 3px 8px;">
                {{ $rp['ruang_tanpa_pj'] ?? 0 }} ruang tanpa PJ
            </span>
        </div>
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="min-width: 170px;">Nama GTK</th>
                        <th style="min-width: 140px;">Jabatan</th>
                        <th style="width: 100px; text-align: center;">Ruang</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rpPj as $row)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="Nama GTK"
                                style="font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-bold); color: var(--text-color);">
                                {{ $row['nama'] }}
                                <div
                                    style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); font-weight: var(--font-normal);">
                                    NIP {{ $row['nip'] ?: '—' }}
                                </div>
                            </td>
                            <td data-label="Jabatan"
                                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                                {{ $row['jabatan'] ?: '—' }}
                            </td>
                            <td data-label="Ruang"
                                style="text-align: center; font-size: var(--text-sm, 0.8125rem); font-weight: var(--font-extrabold); color: var(--primary, #3b82f6);">
                                {{ number_format((int) $row['jumlah_ruang'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3"
                                style="text-align: center; padding: 22px; color: var(--text-muted); font-size: var(--text-sm, 0.8125rem);">
                                Belum ada penanggung jawab ruang yang ditetapkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 7. Target & Capaian Kinerja Sarpras -->
@include('dashboard.tendik.partials-kinerja-chart', [
    'bidangKey' => 'sarpras',
    'bidangTitle' => 'Sarana & Prasarana',
])

<!-- Payload JSON Data Chart Sarpras untuk JS -->
<script type="application/json" id="sectionSarprasChartPayload">
    {!! json_encode($rpCharts, JSON_UNESCAPED_UNICODE) !!}
</script>
