@php
    $bKey = $bidangKey ?? 'umum';
    $bTitle = $bidangTitle ?? 'Tendik';
    $pData = $bidangMultiPeriode[$bKey] ?? [];
    $indList = $bidangIndikatorList[$bKey] ?? collect();
    $chartId = 'chartAktivitas_' . str_replace('-', '_', $bKey);
    $payloadId = 'payloadAktivitas_' . str_replace('-', '_', $bKey);
    $btnGroupId = 'btnGroupPeriode_' . str_replace('-', '_', $bKey);
    $selectDropdownId = 'selectDropdownPeriode_' . str_replace('-', '_', $bKey);
    $targetId = 'valTarget_' . str_replace('-', '_', $bKey);
    $selesaiId = 'valSelesai_' . str_replace('-', '_', $bKey);
    $prosesId = 'valProses_' . str_replace('-', '_', $bKey);
    $persenId = 'valPersen_' . str_replace('-', '_', $bKey);
    $descId = 'labelDeskripsi_' . str_replace('-', '_', $bKey);
@endphp

<!-- Section: Monitoring Target & Capaian Aktivitas Pekerjaan (Multi-Periode) -->
<div class="card" style="padding: 20px 22px; margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chart-line text-success"></i> Target &amp; Capaian Aktivitas Pekerjaan {{ $bTitle }}
            </h3>
            <p id="{{ $descId }}" style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                Monitoring produktivitas input dan penyelesaian tugas harian staf {{ strtolower($bTitle) }}.
            </p>
        </div>

        <!-- Segmented Period Switcher: Desktop Pills -->
        <div class="status-pill-group periode-pills-desktop" style="max-width: 580px; padding: 3px; gap: 4px; grid-template-columns: repeat(7, 1fr);" id="{{ $btnGroupId }}">
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

        <!-- Segmented Period Switcher: Mobile Dropdown -->
        <div class="periode-select-mobile">
            <select id="{{ $selectDropdownId }}" class="toolbar-filter-select form-control" style="width: 100%; height: 38px; font-size: 0.82rem; font-weight: 700; border-radius: 8px;">
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
            <span><i class="fas fa-bullseye text-primary me-1"></i> Target: <strong id="{{ $targetId }}" style="color: var(--text-color);">0</strong></span>
            <span><i class="fas fa-circle-check text-success me-1"></i> Selesai: <strong id="{{ $selesaiId }}" style="color: #10b981;">0</strong></span>
            <span><i class="fas fa-spinner text-warning me-1"></i> Proses: <strong id="{{ $prosesId }}" style="color: #f59e0b;">0</strong></span>
            <span><i class="fas fa-percent text-info me-1"></i> Capaian: <strong id="{{ $persenId }}" style="color: var(--accent);">0%</strong></span>
        </div>
        <div style="font-size: 0.74rem; color: var(--text-muted);">
            <i class="fas fa-clock me-1"></i> Diperbarui otomatis dari input log harian tendik
        </div>
    </div>

    <!-- Chart Container -->
    <div style="height: 270px; position: relative;">
        <canvas id="{{ $chartId }}"></canvas>
    </div>
</div>

<!-- Section: Matriks Sasaran & Indikator Kinerja Bidang -->
<div class="card" style="padding: 0; overflow: hidden; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.02); flex-wrap: wrap; gap: 10px;">
        <div>
            <div style="font-weight: 800; font-size: 0.96rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bullseye text-primary"></i> Target &amp; Indikator Kinerja {{ $bTitle }}
            </div>
            <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                Sasaran strategis dan indikator kinerja operasional terintegrasi aktivitas harian
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;" title="Data terintegrasi sistem SAE">
                <i class="fas fa-shield-halved"></i> Terverifikasi
            </span>
            <a href="{{ route('dashboard.tendik.target.index', ['bidang' => $bKey]) }}" class="btn btn-outline" style="font-size: 0.78rem; padding: 5px 12px; border-radius: 8px; text-decoration: none;" title="Buka Detail Target & Capaian">
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
                @forelse ($indList as $idx => $ind)
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
                            Belum ada data indikator kinerja untuk bidang ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script type="application/json" id="{{ $payloadId }}">
    {!! json_encode($pData ?? [], JSON_UNESCAPED_UNICODE) !!}
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const payloadEl = document.getElementById('{{ $payloadId }}');
    if (!payloadEl || typeof Chart === 'undefined') return;

    let multiPeriode = {};
    try {
        multiPeriode = JSON.parse(payloadEl.textContent);
    } catch (e) {
        console.error('Gagal parsing {{ $payloadId }}', e);
        return;
    }

    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const textColor = isLight ? '#1e293b' : '#f8fafc';
    const textMuted = isLight ? '#64748b' : '#94a3b8';
    const gridColor = isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)';

    let chartAktivitas = null;

    function renderAktivitasPeriode(pKey) {
        const pData = multiPeriode[pKey] || multiPeriode['hari'] || {};
        const ctxAct = document.getElementById('{{ $chartId }}')?.getContext('2d');
        if (!ctxAct) return;

        const tTarget = pData.total_target || 0;
        const tSelesai = pData.total_selesai || 0;
        const tProses = (pData.proses || []).reduce((a, b) => a + b, 0);
        const pct = tTarget > 0 ? Math.round((tSelesai / tTarget) * 100) : 0;

        const elTarget = document.getElementById('{{ $targetId }}');
        const elSelesai = document.getElementById('{{ $selesaiId }}');
        const elProses = document.getElementById('{{ $prosesId }}');
        const elPersen = document.getElementById('{{ $persenId }}');
        const elDesk = document.getElementById('{{ $descId }}');

        if (elTarget) elTarget.textContent = tTarget.toLocaleString('id-ID');
        if (elSelesai) elSelesai.textContent = tSelesai.toLocaleString('id-ID');
        if (elProses) elProses.textContent = tProses.toLocaleString('id-ID');
        if (elPersen) elPersen.textContent = pct + '%';
        if (elDesk && pData.label) elDesk.textContent = 'Monitoring progres aktivitas dan target pekerjaan pada periode ' + pData.label + '.';

        if (chartAktivitas) {
            chartAktivitas.destroy();
        }

        chartAktivitas = new Chart(ctxAct, {
            data: {
                labels: pData.labels || [],
                datasets: [
                    {
                        type: 'line',
                        label: 'Target Pekerjaan',
                        data: pData.target || [],
                        borderColor: '#6366f1',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        pointRadius: 3,
                        pointBackgroundColor: '#6366f1',
                        fill: false,
                        tension: 0.25,
                        order: 1
                    },
                    {
                        type: 'bar',
                        label: 'Selesai Dikerjakan',
                        data: pData.selesai || [],
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#10b981',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 28,
                        order: 2
                    },
                    {
                        type: 'bar',
                        label: 'Dalam Proses',
                        data: pData.proses || [],
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderColor: '#f59e0b',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 28,
                        order: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            color: textColor,
                            font: { size: 11, weight: '600' },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        padding: 10,
                        backgroundColor: isLight ? 'rgba(15, 23, 42, 0.9)' : 'rgba(30, 41, 59, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textMuted, font: { size: 10 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: textMuted, font: { size: 10 }, precision: 0 },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    renderAktivitasPeriode('hari');

    const btnGroup = document.getElementById('{{ $btnGroupId }}');
    const selectDropdown = document.getElementById('{{ $selectDropdownId }}');

    if (btnGroup) {
        btnGroup.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-periode-pill');
            if (!btn) return;
            btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                b.classList.remove('active');
                b.style.background = 'transparent';
                b.style.color = 'var(--text-muted)';
            });
            btn.classList.add('active');
            btn.style.background = 'var(--primary)';
            btn.style.color = '#fff';

            const p = btn.getAttribute('data-period');
            if (selectDropdown) selectDropdown.value = p;
            renderAktivitasPeriode(p);
        });
    }

    if (selectDropdown) {
        selectDropdown.addEventListener('change', function () {
            const p = this.value;
            if (btnGroup) {
                btnGroup.querySelectorAll('.btn-periode-pill').forEach(b => {
                    const match = b.getAttribute('data-period') === p;
                    b.classList.toggle('active', match);
                    b.style.background = match ? 'var(--primary)' : 'transparent';
                    b.style.color = match ? '#fff' : 'var(--text-muted)';
                });
            }
            renderAktivitasPeriode(p);
        });
    }
});
</script>
