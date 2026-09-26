{{-- Section Dashboard: Staf Administrasi Kepegawaian --}}

<!-- 1. Akses Cepat Menu Kepegawaian (Responsive Grid Seimbang & Genap) -->
<div class="kepegawaian-quick-grid">
    <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'pegawai']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #3b82f6;" title="Direktori Data Pegawai GTK">
        <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-users"></i>
        </div>
        <span class="kepegawaian-quick-label">Pegawai</span>
    </a>

    <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'berkas']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #06b6d4;" title="Pengarsipan Berkas Pegawai">
        <div class="kepegawaian-quick-icon" style="background: rgba(6, 182, 212, 0.15); color: #06b6d4;">
            <i class="fas fa-folder-open"></i>
        </div>
        <span class="kepegawaian-quick-label">Berkas</span>
    </a>

    <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'kgb']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #f59e0b;" title="Kenaikan Gaji Berkala Tracker">
        <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-business-time"></i>
        </div>
        <span class="kepegawaian-quick-label">KGB Tracker</span>
    </a>

    <a href="{{ route('dashboard.kepegawaian.index', ['tab' => 'cuti']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #10b981;" title="Pengajuan &amp; Riwayat Cuti/Izin">
        <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-plane-departure"></i>
        </div>
        <span class="kepegawaian-quick-label">Cuti &amp; Izin</span>
    </a>

    <a href="{{ route('dashboard.tendik.target.index') }}"
        class="kepegawaian-quick-btn" style="--quick-color: #6366f1;" title="Target &amp; Capaian Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-bullseye"></i>
        </div>
        <span class="kepegawaian-quick-label">Target Capaian</span>
    </a>

    <a href="{{ route('dashboard.tendik.laporan.index', ['bidang' => 'kepegawaian']) }}"
        class="kepegawaian-quick-btn" style="--quick-color: #ec4899;" title="Laporan &amp; Rekapitulasi Kinerja">
        <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
            <i class="fas fa-file-invoice"></i>
        </div>
        <span class="kepegawaian-quick-label">Laporan Kinerja</span>
    </a>
</div>

<!-- 2. Quick Stats Grid Kepegawaian (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_guru'] ?? 48 }} Guru</div>
            <div class="dash-stat-label">Pendidik Terdaftar</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
            <i class="fas fa-users-gear"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_tendik'] ?? 19 }} Staf</div>
            <div class="dash-stat-label">Tenaga Kependidikan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-award"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['gtk_tugas_tambahan'] ?? 38 }} GTK</div>
            <div class="dash-stat-label">Tugas Tambahan</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $stats['total_jam_kbm'] ?? 840 }} JP</div>
            <div class="dash-stat-label">Beban KBM Terdistribusi</div>
        </div>
    </div>
</div>

<!-- 2. Section: Statistik & Demografi GTK (Dengan Angka Permanen pada Diagram) -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-pie text-primary"></i> Statistik &amp; Demografi Pendidik &amp; Tenaga Kependidikan (GTK)
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Visualisasi perbandingan kualifikasi pendidikan, rasio jenis kelamin, jenis PTK, dan status kepegawaian.
            </p>
        </div>
        <div style="font-size: 0.76rem; font-weight: 700; color: var(--primary); background: rgba(99,102,241,0.1); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(99,102,241,0.25);">
            <i class="fas fa-users me-1"></i> Total: {{ ($stats['total_guru'] ?? 48) + ($stats['total_tendik'] ?? 19) }} Personel
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
        <!-- 2.1. Doughnut: Jenis Kelamin L/P -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-venus-mars text-primary"></i> Proporsi Jenis Kelamin GTK
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Rasio Laki-laki (L) vs Perempuan (P)</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="gtkChartJenisKelamin"></canvas>
            </div>
            <div style="display: flex; justify-content: center; gap: 16px; margin-top: 10px; font-size: 0.78rem; border-top: 1px solid var(--border-color); padding-top: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #3b82f6;"></span>
                    <span>Laki-laki: <strong>{{ $kepegawaianCharts['jenisKelamin']['L'] ?? 0 }}</strong></span>
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #ec4899;"></span>
                    <span>Perempuan: <strong>{{ $kepegawaianCharts['jenisKelamin']['P'] ?? 0 }}</strong></span>
                </span>
            </div>
        </div>

        <!-- 2.2. Bar: Kualifikasi Pendidikan Terakhir -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-graduation-cap text-accent"></i> Kualifikasi Pendidikan Terakhir
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Distribusi ijazah/jenjang pendidikan formal</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="gtkChartPendidikan"></canvas>
            </div>
        </div>

        <!-- 2.3. Horizontal Bar: Komposisi Jenis PTK -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-chalkboard-user text-warning"></i> Komposisi Jenis PTK
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">Pembagian Pendidik, Tendik &amp; Pimpinan</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="gtkChartJenisPtk"></canvas>
            </div>
        </div>

        <!-- 2.4. Doughnut: Status Kepegawaian -->
        <div style="background: var(--bg-hover); border-radius: 12px; padding: 16px; border: 1px solid var(--border-color); display: flex; flex-direction: column;">
            <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-id-card-clip text-success"></i> Status Kepegawaian
            </div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 10px;">PNS, PPPK, Paruh Waktu &amp; Honor</div>
            <div style="height: 180px; position: relative; flex: 1;">
                <canvas id="gtkChartStatusKepegawaian"></canvas>
            </div>
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 10px; font-size: 0.72rem; border-top: 1px solid var(--border-color); padding-top: 8px;">
                @foreach ($kepegawaianCharts['statusKepegawaian'] ?? [] as $stName => $stCount)
                    <span class="badge" style="background: var(--card-bg); color: var(--text-color); border: 1px solid var(--border-color); padding: 2px 6px;">
                        {{ $stName }}: <strong>{{ $stCount }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- 3. Section: Monitoring Target & Capaian Aktivitas Pekerjaan (Multi-Periode) -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-line text-success"></i> Target &amp; Capaian Aktivitas Pekerjaan Tendik
            </h3>
            <p id="labelAktivitasPeriodeDeskripsi" style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Monitoring produktivitas input dan penyelesaian tugas harian tenaga kependidikan.
            </p>
        </div>

        <!-- Segmented Period Switcher: Desktop Pills -->
        <div class="status-pill-group periode-pills-desktop" style="max-width: 580px; padding: 3px; gap: 4px; grid-template-columns: repeat(7, 1fr);" id="btnGroupPeriodeAktivitas">
            <button type="button" class="btn-periode-pill active" data-period="hari" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 700; cursor: pointer; background: var(--primary); color: #fff; transition: all 0.2s ease;">
                Hari
            </button>
            <button type="button" class="btn-periode-pill" data-period="minggu" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                Minggu
            </button>
            <button type="button" class="btn-periode-pill" data-period="bulan" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                Bulan
            </button>
            <button type="button" class="btn-periode-pill" data-period="triwulan" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                Triwulan
            </button>
            <button type="button" class="btn-periode-pill" data-period="semester" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                Semester
            </button>
            <button type="button" class="btn-periode-pill" data-period="tahun" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                Tahun
            </button>
            <button type="button" class="btn-periode-pill" data-period="tahun_ajaran" style="padding: 6px 8px; font-size: 0.74rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; background: transparent; color: var(--text-muted); transition: all 0.2s ease;">
                T. Ajaran
            </button>
        </div>

        <!-- Segmented Period Switcher: Mobile Dropdown (Responsive) -->
        <div class="periode-select-mobile">
            <select id="selectPeriodeAktivitasDropdown" class="toolbar-filter-select form-control" style="width: 100%; height: 38px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
                <option value="hari" selected>Harian (Hari Ini)</option>
                <option value="minggu">Mingguan (7 Hari)</option>
                <option value="bulan">Bulanan (Bulan Berjalan)</option>
                <option value="triwulan">Triwulan Berjalan</option>
                <option value="semester">Semester Berjalan</option>
                <option value="tahun">Tahun Kalender</option>
                <option value="tahun_ajaran">Tahun Ajaran</option>
            </select>
        </div>
    </div>

    <!-- Summary Stat Strip for Selected Period -->
    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; padding: 10px 14px; border-radius: 10px; background: var(--bg-hover); border: 1px solid var(--border-color); justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 16px; flex-wrap: wrap; font-size: 0.8rem;">
            <span><i class="fas fa-bullseye text-primary me-1"></i> Target: <strong id="valPeriodeTarget" style="color: var(--text-color);">0</strong></span>
            <span><i class="fas fa-circle-check text-success me-1"></i> Selesai: <strong id="valPeriodeSelesai" style="color: #10b981;">0</strong></span>
            <span><i class="fas fa-spinner text-warning me-1"></i> Proses: <strong id="valPeriodeProses" style="color: #f59e0b;">0</strong></span>
            <span><i class="fas fa-percent text-info me-1"></i> Capaian: <strong id="valPeriodePersen" style="color: var(--accent);">0%</strong></span>
        </div>
        <div style="font-size: 0.74rem; color: var(--text-muted);">
            <i class="fas fa-clock me-1"></i> Diperbarui otomatis dari input log harian tendik
        </div>
    </div>

    <!-- Chart Container: Mixed Target Line + Realisasi Bars -->
    <div style="height: 270px; position: relative;">
        <canvas id="gtkChartAktivitasPeriode"></canvas>
    </div>
</div>

<!-- 4. Matriks Sasaran & Indikator Kinerja Kepegawaian -->
<div class="card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 10px;">
        <div>
            <div style="font-weight: 800; font-size: 0.96rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bullseye text-primary"></i> Target &amp; Indikator Kinerja Kepegawaian
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                Sasaran strategis dan indikator kinerja operasional terintegrasi aktivitas harian
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;" title="Data terintegrasi Dapodik Kemendikbudristek">
                <i class="fas fa-shield-halved"></i> Dapodik Sinkron
            </span>
            <a href="{{ route('dashboard.tendik.target.index') }}" class="btn btn-outline" style="font-size: 0.78rem; padding: 5px 12px; border-radius: 8px; text-decoration: none;" title="Buka Detail Target & Capaian">
                <span>Kelola Target &amp; Capaian</span> <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>

    <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
        <table class="table-minimal-compact" style="width: 100%;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="width: 45px; text-align: center;">No.</th>
                    <th style="min-width: 180px;">Sasaran</th>
                    <th style="min-width: 260px;">Indikator Kinerja</th>
                    <th style="width: 120px; text-align: center;">Target</th>
                    <th style="width: 120px; text-align: center;">Capaian Terdata</th>
                    <th style="width: 120px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kepegawaianIndikatorList ?? [] as $idx => $ind)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td data-label="No." style="text-align: center; font-weight: 700; color: var(--text-muted); font-size: 0.82rem;">
                            {{ $idx + 1 }}
                        </td>
                        <td data-label="Sasaran" style="font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                            {{ $ind->sasaran }}
                        </td>
                        <td data-label="Indikator Kinerja" style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
                            {{ $ind->indikator_kinerja }}
                        </td>
                        <td data-label="Target" style="text-align: center; font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                            {{ $ind->target_label }}
                        </td>
                        <td data-label="Capaian" style="text-align: center; font-size: 0.84rem; font-weight: 700; color: #10b981;">
                            {{ $ind->realisasi_label }}
                        </td>
                        <td data-label="Status" style="text-align: center;">
                            <span class="badge {{ $ind->status_kpi === 'Tercapai' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.72rem; padding: 4px 9px;">
                                {{ $ind->status_kpi }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.84rem;">
                            Belum ada data indikator kinerja kepegawaian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Payload JSON Data Chart Kepegawaian untuk JS -->
<script type="application/json" id="sectionKepegawaianChartPayload">
    {!! json_encode([
        'demografi' => $kepegawaianCharts ?? [],
        'multiPeriode' => $kepegawaianAktivitasMultiPeriode ?? [],
    ], JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('sectionKepegawaianChartPayload');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let payload = {};
    try {
        payload = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing sectionKepegawaianChartPayload', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    // Plugin 1: Angka Permanen di Atas Batang Diagram Bar
    const barDataLabelsPlugin = {
        id: 'barDataLabelsKepegawaian',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const isHorizontal = chart.config.options.indexAxis === 'y';
            ctx.save();
            chart.data.datasets.forEach((dataset, dIdx) => {
                const meta = chart.getDatasetMeta(dIdx);
                if (meta.hidden) return;
                meta.data.forEach((bar, index) => {
                    const val = dataset.data[index];
                    if (val !== undefined && val !== null && val > 0) {
                        ctx.fillStyle = textColor;
                        ctx.font = 'bold 10px Inter, system-ui, sans-serif';
                        if (isHorizontal) {
                            ctx.textAlign = 'left';
                            ctx.textBaseline = 'middle';
                            ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x + 4, bar.y);
                        } else {
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'bottom';
                            ctx.fillText(Number(val).toLocaleString('id-ID'), bar.x, bar.y - 3);
                        }
                    }
                });
            });
            ctx.restore();
        }
    };

    // Plugin 2: Angka Permanen di Segmen Lingkaran & Total Tengah Donat (Persis Demografi Peserta Didik)
    const doughnutDataLabelsPlugin = {
        id: 'doughnutDataLabelsKepegawaian',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || !meta.data.length) return;

            const dataset = data.datasets[0];
            const total = dataset.data.reduce((a, b) => a + Number(b || 0), 0);

            ctx.save();
            meta.data.forEach((element, index) => {
                const val = dataset.data[index];
                if (!val || val <= 0) return;

                const pos = element.tooltipPosition();
                const pct = total > 0 ? Math.round((val / total) * 100) : 0;

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 11px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';

                if (pct >= 10) {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y - 5);
                    ctx.font = '600 9px Inter, system-ui, sans-serif';
                    ctx.fillStyle = 'rgba(255,255,255,0.9)';
                    ctx.fillText(pct + '%', pos.x, pos.y + 6);
                } else {
                    ctx.fillText(Number(val).toLocaleString('id-ID'), pos.x, pos.y);
                }
            });

            // Total GTK di Tengah Lingkaran Donat
            if (total > 0 && meta.data[0]) {
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;

                ctx.fillStyle = textMuted;
                ctx.font = '700 8px Inter, system-ui, sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('TOTAL', centerX, centerY - 9);

                ctx.fillStyle = textColor;
                ctx.font = '800 15px Inter, system-ui, sans-serif';
                ctx.fillText(Number(total).toLocaleString('id-ID'), centerX, centerY + 7);
            }
            ctx.restore();
        }
    };

    const demo = payload.demografi || {};

    // 1. Chart Jenis Kelamin (Doughnut dengan Total di Tengah)
    const ctxJK = document.getElementById('gtkChartJenisKelamin')?.getContext('2d');
    if (ctxJK && demo.jenisKelamin) {
        new Chart(ctxJK, {
            type: 'doughnut',
            data: {
                labels: ['Laki-laki', 'Perempuan'],
                datasets: [{
                    data: [demo.jenisKelamin.L || 0, demo.jenisKelamin.P || 0],
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: isLight ? '#ffffff' : '#1e293b',
                    hoverOffset: 4
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '62%'
            }
        });
    }

    // 2. Chart Pendidikan (Bar dengan Angka di Atas Batang)
    const ctxPend = document.getElementById('gtkChartPendidikan')?.getContext('2d');
    if (ctxPend && demo.pendidikan) {
        new Chart(ctxPend, {
            type: 'bar',
            data: {
                labels: Object.keys(demo.pendidikan),
                datasets: [{
                    label: 'Jumlah Personel',
                    data: Object.values(demo.pendidikan),
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 28
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                layout: { padding: { top: 14 } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: textMuted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 }, precision: 0 } }
                }
            }
        });
    }

    // 3. Chart Jenis PTK (Horizontal Bar dengan Angka di Kanan)
    const ctxJenis = document.getElementById('gtkChartJenisPtk')?.getContext('2d');
    if (ctxJenis && demo.jenisPtk) {
        new Chart(ctxJenis, {
            type: 'bar',
            data: {
                labels: Object.keys(demo.jenisPtk),
                datasets: [{
                    label: 'Jumlah',
                    data: Object.values(demo.jenisPtk),
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderRadius: 6,
                    maxBarThickness: 22
                }]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                layout: { padding: { right: 24 } },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textMuted, font: { size: 10 }, precision: 0 } },
                    y: { grid: { display: false }, ticks: { color: textMuted, font: { size: 10 } } }
                }
            }
        });
    }

    // 4. Chart Status Kepegawaian (Doughnut dengan Total di Tengah)
    const ctxStatus = document.getElementById('gtkChartStatusKepegawaian')?.getContext('2d');
    if (ctxStatus && demo.statusKepegawaian) {
        const sLabels = Object.keys(demo.statusKepegawaian);
        const sData = Object.values(demo.statusKepegawaian);
        const colors = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#06b6d4', '#ef4444'];
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: sLabels,
                datasets: [{
                    data: sData,
                    backgroundColor: colors.slice(0, sData.length),
                    borderWidth: 2,
                    borderColor: isLight ? '#ffffff' : '#1e293b',
                    hoverOffset: 4
                }]
            },
            plugins: [doughnutDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '62%'
            }
        });
    }

    // 5. Chart Aktivitas Multi-Periode Interaktif
    const multiPeriode = payload.multiPeriode || {};
    let chartAktivitas = null;

    function renderAktivitasPeriode(pKey) {
        const pData = multiPeriode[pKey] || multiPeriode['hari'] || {};
        const ctxAct = document.getElementById('gtkChartAktivitasPeriode')?.getContext('2d');
        if (!ctxAct) return;

        // Update Stat Strip
        const tTarget = pData.total_target || 0;
        const tSelesai = pData.total_selesai || 0;
        const tProses = (pData.proses || []).reduce((a, b) => a + b, 0);
        const pct = tTarget > 0 ? Math.round((tSelesai / tTarget) * 100) : 0;

        const elTarget = document.getElementById('valPeriodeTarget');
        const elSelesai = document.getElementById('valPeriodeSelesai');
        const elProses = document.getElementById('valPeriodeProses');
        const elPersen = document.getElementById('valPeriodePersen');
        const elDesk = document.getElementById('labelAktivitasPeriodeDeskripsi');

        if (elTarget) elTarget.textContent = Number(tTarget).toLocaleString('id-ID') + ' Tugas';
        if (elSelesai) elSelesai.textContent = Number(tSelesai).toLocaleString('id-ID');
        if (elProses) elProses.textContent = Number(tProses).toLocaleString('id-ID');
        if (elPersen) elPersen.textContent = pct + '%';
        if (elDesk && pData.label) elDesk.textContent = 'Monitoring progres aktivitas dan target pekerjaan pada periode ' + pData.label + '.';

        if (chartAktivitas) {
            chartAktivitas.destroy();
        }

        chartAktivitas = new Chart(ctxAct, {
            type: 'bar',
            data: {
                labels: pData.labels || [],
                datasets: [
                    {
                        type: 'line',
                        label: 'Target Pekerjaan',
                        data: pData.target || [],
                        borderColor: '#6366f1',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 3,
                        tension: 0.3,
                        order: 1
                    },
                    {
                        type: 'bar',
                        label: 'Tugas Selesai',
                        data: pData.selesai || [],
                        backgroundColor: '#10b981',
                        borderRadius: 5,
                        maxBarThickness: 26,
                        order: 2
                    },
                    {
                        type: 'bar',
                        label: 'Sedang Proses',
                        data: pData.proses || [],
                        backgroundColor: '#f59e0b',
                        borderRadius: 5,
                        maxBarThickness: 26,
                        order: 3
                    }
                ]
            },
            plugins: [barDataLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 14 } },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: textMuted,
                            boxWidth: 10,
                            padding: 10,
                            font: { size: 10, weight: '600' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 8,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)'
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textMuted, font: { size: 10 }, precision: 0 }
                    }
                }
            }
        });
    }

    // Inisialisasi awal pada periode 'hari'
    renderAktivitasPeriode('hari');

    // Event listener switch periode: Desktop Pills
    const btnGroup = document.getElementById('btnGroupPeriodeAktivitas');
    const selectDropdown = document.getElementById('selectPeriodeAktivitasDropdown');

    if (btnGroup) {
        btnGroup.querySelectorAll('.btn-periode-pill').forEach(btn => {
            btn.addEventListener('click', function () {
                const periodKey = this.getAttribute('data-period');
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                    b.classList.remove('active');
                    b.style.background = 'transparent';
                    b.style.color = 'var(--text-muted)';
                    b.style.fontWeight = '600';
                });
                this.classList.add('active');
                this.style.background = 'var(--primary)';
                this.style.color = '#fff';
                this.style.fontWeight = '700';

                if (selectDropdown) {
                    selectDropdown.value = periodKey;
                }

                renderAktivitasPeriode(periodKey);
            });
        });
    }

    // Event listener switch periode: Mobile Dropdown
    if (selectDropdown) {
        selectDropdown.addEventListener('change', function () {
            const periodKey = this.value;
            if (btnGroup) {
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                    if (b.getAttribute('data-period') === periodKey) {
                        b.classList.add('active');
                        b.style.background = 'var(--primary)';
                        b.style.color = '#fff';
                        b.style.fontWeight = '700';
                    } else {
                        b.classList.remove('active');
                        b.style.background = 'transparent';
                        b.style.color = 'var(--text-muted)';
                        b.style.fontWeight = '600';
                    }
                });
            }
            renderAktivitasPeriode(periodKey);
        });
    }
});
</script>

