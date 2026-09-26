@extends('layouts.dashboard')

@section('title', 'Target & Capaian Pekerjaan Tendik — SAE')
@section('dash_title', 'Target & Capaian Pekerjaan')

@section('content')
    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner">
        <div style="flex: 1; min-width: 260px;">
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px; line-height: 1.25;">
                <i class="fas fa-bullseye text-primary me-2"></i> Target &amp; Capaian Pekerjaan Tendik
                @if ($filterBidang && $filterBidang !== 'all' && isset($bidangLabels[$filterBidang]))
                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 10px; margin-left: 8px; vertical-align: middle; border-radius: 6px;">
                        <i class="fas fa-tag me-1"></i>{{ $bidangLabels[$filterBidang] }}
                    </span>
                @endif
            </h2>
            <div style="display: flex; gap: 14px; font-size: 0.82rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 6px;">
                <span title="Periode Aktif"><i class="fas fa-calendar-check text-primary me-1"></i> <strong>{{ $range['label'] }}</strong></span>
                <span title="Hari Efektif"><i class="fas fa-business-time text-warning me-1"></i> <strong>{{ $hariEfektif }} Hari Kerja</strong></span>
                <span title="Total Staf"><i class="fas fa-users text-info me-1"></i> <strong>{{ $totalTendik }} Staf Tendik</strong></span>
            </div>
            <p style="margin: 0; font-size: 0.82rem; color: var(--text-muted);">
                Indikator kepatuhan input harian pegawai, monitoring capaian target operasional, dan analitik produktivitas.
            </p>
        </div>

        <div class="dash-banner-actions">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenModalTarget"
                    style="background: #6366f1; border: none; padding: 8px 16px; font-size: 0.85rem; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 12px rgba(99,102,241,0.3); display: inline-flex; align-items: center; gap: 6px; color: #fff; cursor: pointer;">
                    <i class="fas fa-plus"></i>
                    <span>Tambah Sasaran &amp; Target</span>
                </button>
                <a href="{{ route('dashboard.tendik.aktivitas.index', $filterBidang && $filterBidang !== 'all' ? ['bidang' => $filterBidang] : []) }}" class="btn btn-outline"
                    style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-pen-to-square text-success"></i>
                    <span>Catat Aktivitas</span>
                </a>
            @endif
            <a href="{{ route('dashboard.tendik.laporan.index', $filterBidang && $filterBidang !== 'all' ? ['bidang' => $filterBidang] : []) }}" class="btn btn-outline"
                style="padding: 8px 14px; font-size: 0.85rem; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-file-invoice text-info"></i>
                <span>Laporan Kinerja</span>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.3); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-check-circle"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.86rem; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-circle-exclamation"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.84rem;">
            <div style="font-weight: 700; margin-bottom: 4px;">Periksa kembali input formulir:</div>
            <ul style="margin: 0; padding-left: 18px;">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. Stat Grid Baku SAE (2 Card Rapi & Seimbang) -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-bullseye"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($targetTotalPeriode) }} <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Tugas</span></div>
                <div class="dash-stat-label">Target Tugas Periode Ini ({{ $hariEfektif }} Hari Kerja)</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $persenCapaian }}%</div>
                <div class="dash-stat-label">Realisasi Capaian ({{ $realisasiSelesai }} Selesai, {{ $realisasiProses }} Proses)</div>
            </div>
        </div>
    </div>

    <!-- 3. Toolbar Pemilihan Periode Standar SAE -->
    <div class="card" style="padding: 16px; margin-bottom: 24px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
        <form id="formFilterPeriode" method="GET" action="{{ route('dashboard.tendik.target.index') }}"
            style="display: flex; flex-wrap: wrap; gap: 14px; justify-content: space-between; align-items: center;">
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-filter text-primary"></i> Rentang Periode:
                </label>

                <!-- Tipe Periode Selector -->
                <select name="periode" id="selectPeriodeTipe" class="toolbar-filter-select" style="min-width: 150px; font-weight: 600;">
                    <option value="hari" {{ $periodeTipe === 'hari' ? 'selected' : '' }}>Harian (1 Hari)</option>
                    <option value="minggu" {{ $periodeTipe === 'minggu' ? 'selected' : '' }}>Mingguan (7 Hari)</option>
                    <option value="bulan" {{ $periodeTipe === 'bulan' ? 'selected' : '' }}>Bulanan (Bulan Berjalan)</option>
                    <option value="triwulan" {{ $periodeTipe === 'triwulan' ? 'selected' : '' }}>Triwulan (3 Bulan)</option>
                    <option value="semester" {{ $periodeTipe === 'semester' ? 'selected' : '' }}>Semester (6 Bulan)</option>
                    <option value="tahun" {{ $periodeTipe === 'tahun' ? 'selected' : '' }}>Tahun Kalender</option>
                    <option value="tahun_ajaran" {{ $periodeTipe === 'tahun_ajaran' ? 'selected' : '' }}>Tahun Ajaran</option>
                </select>

                <!-- Dynamic Sub-controls based on period type -->
                <div id="wrapFilterHari" style="{{ $periodeTipe === 'hari' ? 'display: flex;' : 'display: none;' }} align-items: center; gap: 6px;">
                    <input type="date" name="tanggal" value="{{ $tanggalPilihan }}" class="form-control" style="height: 38px; font-size: 0.82rem; border-radius: 8px;">
                </div>

                <div id="wrapFilterBulan" style="{{ in_array($periodeTipe, ['bulan', 'triwulan']) ? 'display: flex;' : 'display: none;' }} align-items: center; gap: 6px;">
                    @if ($periodeTipe === 'bulan')
                        <select name="bulan" class="toolbar-filter-select">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ $bulan === $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    @endif

                    @if ($periodeTipe === 'triwulan')
                        <select name="triwulan" class="toolbar-filter-select">
                            <option value="1" {{ $triwulan === 1 ? 'selected' : '' }}>Triwulan I (Jan - Mar)</option>
                            <option value="2" {{ $triwulan === 2 ? 'selected' : '' }}>Triwulan II (Apr - Jun)</option>
                            <option value="3" {{ $triwulan === 3 ? 'selected' : '' }}>Triwulan III (Jul - Sep)</option>
                            <option value="4" {{ $triwulan === 4 ? 'selected' : '' }}>Triwulan IV (Okt - Des)</option>
                        </select>
                    @endif

                    <select name="tahun" class="toolbar-filter-select">
                        @for ($y = date('Y') + 1; $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div id="wrapFilterSemester" style="{{ $periodeTipe === 'semester' ? 'display: flex;' : 'display: none;' }} align-items: center; gap: 6px;">
                    <select name="semester" class="toolbar-filter-select">
                        <option value="1" {{ $semester === '1' ? 'selected' : '' }}>Semester Ganjil</option>
                        <option value="2" {{ $semester === '2' ? 'selected' : '' }}>Semester Genap</option>
                    </select>
                </div>

                <div id="wrapFilterTA" style="{{ in_array($periodeTipe, ['semester', 'tahun_ajaran']) ? 'display: flex;' : 'display: none;' }} align-items: center; gap: 6px;">
                    <select name="tahun_ajaran" class="toolbar-filter-select">
                        @php $yBase = (int) date('Y'); @endphp
                        @for ($y = $yBase + 1; $y >= $yBase - 3; $y--)
                            @php $taVal = "{$y}/" . ($y + 1); @endphp
                            <option value="{{ $taVal }}" {{ $tahunAjaran === $taVal ? 'selected' : '' }}>
                                TA {{ $taVal }}
                            </option>
                        @endfor
                    </select>
                </div>

                <button type="submit" class="btn btn-outline" style="padding: 7px 14px; font-size: 0.82rem; border-radius: 8px;">
                    <i class="fas fa-arrows-rotate me-1"></i> Terapkan
                </button>
                @if ($filterBidang)
                    <input type="hidden" name="bidang" value="{{ $filterBidang }}">
                @endif
            </div>

            <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                <i class="fas fa-circle-info text-info me-1"></i> Target Standar: 1 Aktivitas per Hari Kerja
            </div>
        </form>
    </div>

    <!-- 4. Section: Visualisasi Chart Target & Capaian (Dipindahkan ke Atas Sebelum Card) -->
    <div class="target-charts-grid {{ $isKepalaTas ? '' : 'grid-even' }}">
        <!-- Chart 1: Line/Area Tren Target vs Realisasi Capaian -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-chart-line text-primary"></i> Tren Target vs Realisasi Capaian
                    </h3>
                    <p style="font-size: 0.76rem; color: var(--text-muted); margin: 2px 0 0 0;">
                        Perbandingan target tugas harian dengan realisasi aktivitas yang dicatat.
                    </p>
                </div>
                <div style="font-size: 0.74rem; font-weight: 600; color: var(--text-muted); display: flex; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: rgba(99, 102, 241, 0.5); border-radius: 3px;"></span> Target</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: #10b981; border-radius: 3px;"></span> Capaian</span>
                </div>
            </div>

            <div style="height: 270px; position: relative; width: 100%;">
                <canvas id="chartTargetVsRealisasi"></canvas>
            </div>
        </div>

        <!-- Chart 2: Doughnut Status Pelaksanaan -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); min-width: 0;">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-pie text-accent"></i> Proporsi Status Tugas
                </h3>
                <p style="font-size: 0.76rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Distribusi pekerjaan: Selesai, Proses, atau Tertunda.
                </p>
            </div>

            <div style="height: 210px; position: relative; width: 100%;">
                <canvas id="chartStatusTugas"></canvas>
            </div>

            <div style="display: flex; justify-content: space-around; font-size: 0.76rem; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <div style="text-align: center;">
                    <div style="color: #10b981; font-weight: 700; font-size: 0.95rem;">{{ $realisasiSelesai }}</div>
                    <div style="color: var(--text-muted);">Selesai</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: #f59e0b; font-weight: 700; font-size: 0.95rem;">{{ $realisasiProses }}</div>
                    <div style="color: var(--text-muted);">Proses</div>
                </div>
                <div style="text-align: center;">
                    <div style="color: #ef4444; font-weight: 700; font-size: 0.95rem;">{{ $realisasiTertunda }}</div>
                    <div style="color: var(--text-muted);">Tertunda</div>
                </div>
            </div>
        </div>
    </div>

    @if ($isKepalaTas)
        <!-- Chart 3: Distribusi Capaian Berdasarkan Bidang Kerja (Khusus Kepala TAS) -->
        <div class="card" style="padding: 20px; margin-bottom: 24px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="margin-bottom: 14px;">
                <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-chart-column text-primary"></i> Capaian Aktivitas Berdasarkan Bidang Kerja
                </h3>
                <p style="font-size: 0.76rem; color: var(--text-muted); margin: 2px 0 0 0;">
                    Akumulasi seluruh aktivitas yang diselesaikan oleh masing-masing urusan administrasi pada periode ini.
                </p>
            </div>

            <div style="height: 240px; position: relative;">
                <canvas id="chartBebanBidang"></canvas>
            </div>
        </div>
    @endif

    <!-- 5. Card: Matriks Sasaran & Target Kinerja (Posisinya Setelah Chart) -->
    <div class="card" style="margin-bottom: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(255,255,255,0.02);">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-bullseye text-primary"></i> Matriks Sasaran &amp; Target Kinerja
                </h3>
                <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Sasaran strategis, indikator kinerja, serta target kuantitatif yang terintegrasi dengan pengisian log aktivitas harian.
                </p>
            </div>

            <!-- Filter Bidang & Tombol Tambah Target -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                @if ($isKepalaTas)
                    <form method="GET" action="{{ route('dashboard.tendik.target.index') }}" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                        <input type="hidden" name="periode" value="{{ $periodeTipe }}">
                        <input type="hidden" name="bulan" value="{{ $bulan }}">
                        <input type="hidden" name="tahun" value="{{ $tahun }}">
                        <select name="bidang" class="toolbar-filter-select" onchange="this.form.submit()" style="font-size: 0.82rem; height: 36px; border-radius: 8px;">
                            <option value="">Semua Bidang Kerja</option>
                            @foreach ($bidangLabels as $bK => $bL)
                                <option value="{{ $bK }}" {{ $filterBidang === $bK ? 'selected' : '' }}>{{ $bL }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="badge badge-info" style="font-size: 0.78rem; padding: 5px 10px;">
                        <i class="fas fa-briefcase me-1"></i> Bidang: {{ $bidangLabels[$activeBidang] ?? ucfirst($activeBidang) }}
                    </span>
                @endif

                @if ($canCreate)
                    <button type="button" class="btn btn-primary btn-sm btn-tambah-target-modal"
                        style="padding: 6px 14px; font-size: 0.8rem; border-radius: 8px; font-weight: 700; background: #6366f1; border: none; color: #fff; cursor: pointer;">
                        <i class="fas fa-plus me-1"></i> Tambah Target
                    </button>
                @endif
            </div>
        </div>

        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02); text-align: left;">
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">N0.</th>
                        @if ($isKepalaTas)
                            <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Bidang</th>
                        @endif
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 220px;">Sasaran</th>
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Indikator Kinerja</th>
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Target</th>
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Realisasi Capaian</th>
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Status</th>
                        <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 95px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($matriksIndikatorList as $idx => $ind)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td data-label="No." style="padding: 10px 14px; text-align: center; font-weight: 700; color: var(--text-muted); font-size: 0.82rem;">
                                {{ $idx + 1 }}
                            </td>
                            @if ($isKepalaTas)
                                <td data-label="Bidang" style="padding: 10px 14px;">
                                    <span class="badge badge-info" style="font-size: 0.72rem; padding: 2px 7px;">
                                        {{ $bidangLabels[$ind->bidang] ?? ucfirst($ind->bidang) }}
                                    </span>
                                </td>
                            @endif
                            <td data-label="Sasaran" style="padding: 10px 14px; font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                                {{ $ind->sasaran }}
                            </td>
                            <td data-label="Indikator Kinerja" style="padding: 10px 14px; font-size: 0.82rem; color: var(--text-muted); line-height: 1.45;">
                                {{ $ind->indikator_kinerja }}
                            </td>
                            <td data-label="Target" style="padding: 10px 14px; text-align: center; font-size: 0.84rem; font-weight: 700; color: var(--text-color);">
                                {{ $ind->target_label }}
                            </td>
                            <td data-label="Realisasi" style="padding: 10px 14px; text-align: center; font-size: 0.84rem; font-weight: 700; color: #10b981;">
                                {{ $ind->realisasi_label }} ({{ $ind->capaian_persen }}%)
                            </td>
                            <td data-label="Status" style="padding: 10px 14px; text-align: center;">
                                <span class="badge {{ $ind->status_kpi === 'Tercapai' ? 'badge-success' : 'badge-warning' }}" style="font-size: 0.72rem; padding: 3px 8px;">
                                    {{ $ind->status_kpi }}
                                </span>
                            </td>
                            <td data-label="Aksi" style="padding: 10px 14px; text-align: center;">
                                <div class="table-actions" style="justify-content: center; gap: 4px;">
                                    <button type="button" class="btn-icon btn-edit-target"
                                        data-id="{{ $ind->id }}"
                                        data-bidang="{{ $ind->bidang }}"
                                        data-sasaran="{{ $ind->sasaran }}"
                                        data-indikator="{{ $ind->indikator_kinerja }}"
                                        data-kuantitas="{{ $ind->target_kuantitas }}"
                                        data-satuan="{{ $ind->satuan }}"
                                        title="Edit Sasaran & Target"
                                        style="border: 1px solid var(--border-color); background: transparent; padding: 5px; border-radius: 6px; cursor: pointer; color: #3b82f6;">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('dashboard.tendik.target.destroy', $ind->id) }}" method="POST" data-confirm="delete" data-name="{{ $ind->sasaran }}" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" title="Hapus Sasaran & Target"
                                            style="border: 1px solid var(--border-color); background: transparent; padding: 5px; border-radius: 6px; cursor: pointer; color: #ef4444;">
                                            <i class="fas fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isKepalaTas ? 8 : 7 }}" style="text-align: center; padding: 28px; color: var(--text-muted); font-size: 0.84rem;">
                                Belum ada data sasaran &amp; target indikator kinerja terdaftar untuk bidang ini. Klik <strong>"Tambah Sasaran &amp; Target"</strong> untuk menambahkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canViewMonitoringPegawai)
        <!-- 6. Section Collapsible: Monitoring Indikator Input Harian Pegawai (Khusus Kepala TAS & Kepegawaian — Default Hide) -->
        <div class="card card-collapsible" style="margin-bottom: 24px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: rgba(255,255,255,0.02);">
                <div>
                    <h3 style="font-size: 0.96rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock-rotate-left text-warning"></i> Monitoring Indikator Input Harian Pegawai (Hari Ini: {{ Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }})
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0 0;">
                        Indikator realtime kepatuhan staf tendik dalam mencatat aktivitas pekerjaan harian.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge {{ $persenKepatuhanHariIni >= 80 ? 'badge-success' : ($persenKepatuhanHariIni >= 50 ? 'badge-warning' : 'badge-danger') }}"
                        style="font-size: 0.78rem; padding: 4px 10px; font-weight: 700;">
                        {{ $totalSudahInputHariIni }} / {{ $totalTendik }} Staf Tercatat ({{ $persenKepatuhanHariIni }}%)
                    </span>
                    <button type="button" class="btn btn-outline btn-sm btn-toggle-collapse" data-target="#collapseMonitoringHarian"
                        style="padding: 5px 12px; font-size: 0.78rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-chevron-down toggle-icon"></i>
                        <span class="toggle-text">Buka Tabel</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Monitoring Default Hide -->
            <div id="collapseMonitoringHarian" class="collapsible-content" style="display: none;">
                <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02); text-align: left;">
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Pegawai Tendik</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bidang / Jabatan</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">Status Input Hari Ini</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Jam Input</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Jumlah Log</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kepatuhanHarianList as $idx => $staf)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="No" style="padding: 10px 14px; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td data-label="Nama Pegawai" style="padding: 10px 14px; font-size: 0.84rem;">
                                        <div style="font-weight: 700; color: var(--text-color);">{{ $staf->nama }}</div>
                                        @if (!empty($staf->nip))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">NIP: {{ $staf->nip }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Bidang" style="padding: 10px 14px; font-size: 0.82rem; color: var(--text-muted);">
                                        {{ $staf->jabatan }}
                                    </td>
                                    <td data-label="Status Input" style="padding: 10px 14px; text-align: center;">
                                        @if ($staf->sudah_input)
                                            <span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-check-circle"></i> Sudah Input
                                            </span>
                                        @else
                                            <span class="badge badge-danger" style="font-size: 0.74rem; padding: 4px 8px; display: inline-flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-circle-xmark"></i> Belum Input
                                            </span>
                                        @endif
                                    </td>
                                    <td data-label="Jam Input" style="padding: 10px 14px; text-align: center; font-size: 0.82rem; font-family: monospace; color: var(--text-color);">
                                        {{ $staf->jam_input ?: '—' }}
                                    </td>
                                    <td data-label="Jumlah Log" style="padding: 10px 14px; text-align: center;">
                                        <span class="badge" style="background: rgba(99, 102, 241, 0.12); color: var(--primary); font-weight: 700; font-size: 0.78rem; padding: 3px 8px;">
                                            {{ $staf->total_input }} Tugas
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.84rem;">
                                        Belum ada data staf tendik terdaftar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 7. Section Collapsible: Rekapitulasi Capaian Tugas per Pegawai (Periode Ini — Default Hide) -->
        <div class="card card-collapsible" style="margin-bottom: 24px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: rgba(255,255,255,0.02);">
                <div>
                    <h3 style="font-size: 0.96rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-trophy text-warning"></i> Rekapitulasi Capaian Tugas per Pegawai (Periode: {{ $range['label'] }})
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 2px 0 0 0;">
                        Perhitungan persentase capaian terhadap target standar hari kerja efektif seluruh staf.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="badge badge-info" style="font-size: 0.78rem; padding: 4px 10px;">
                        {{ $capaianTendikList->count() }} Pegawai Terdata
                    </span>
                    <button type="button" class="btn btn-outline btn-sm btn-toggle-collapse" data-target="#collapseRekapCapaian"
                        style="padding: 5px 12px; font-size: 0.78rem; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-chevron-down toggle-icon"></i>
                        <span class="toggle-text">Buka Tabel</span>
                    </button>
                </div>
            </div>

            <!-- Tabel Rekap Capaian Default Hide -->
            <div id="collapseRekapCapaian" class="collapsible-content" style="display: none;">
                <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.02); text-align: left;">
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 45px; text-align: center;">No</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Pegawai</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Target</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Selesai</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 100px;">Proses</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Capaian (%)</th>
                                <th style="padding: 10px 14px; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 120px;">Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($capaianTendikList as $idx => $row)
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="No" style="padding: 10px 14px; text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                                        {{ $idx + 1 }}
                                    </td>
                                    <td data-label="Nama Pegawai" style="padding: 10px 14px; font-size: 0.84rem;">
                                        <div style="font-weight: 700; color: var(--text-color);">{{ $row->nama }}</div>
                                        @if (!empty($row->nip))
                                            <div style="font-size: 0.72rem; color: var(--text-muted); font-family: monospace;">NIP: {{ $row->nip }}</div>
                                        @endif
                                    </td>
                                    <td data-label="Target" style="padding: 10px 14px; text-align: center; font-size: 0.84rem; color: var(--text-muted);">
                                        {{ $row->target }} Tugas
                                    </td>
                                    <td data-label="Selesai" style="padding: 10px 14px; text-align: center; font-weight: 700; color: #10b981; font-size: 0.84rem;">
                                        {{ $row->selesai }}
                                    </td>
                                    <td data-label="Proses" style="padding: 10px 14px; text-align: center; color: #f59e0b; font-size: 0.84rem;">
                                        {{ $row->proses }}
                                    </td>
                                    <td data-label="Capaian" style="padding: 10px 14px; text-align: center;">
                                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                            <div style="flex: 1; max-width: 60px; height: 6px; background: var(--bg-hover); border-radius: 3px; overflow: hidden;">
                                                <div style="width: {{ min(100, $row->persen) }}%; height: 100%; background: {{ $row->persen >= 100 ? '#10b981' : ($row->persen >= 75 ? '#3b82f6' : ($row->persen >= 50 ? '#f59e0b' : '#ef4444')) }}; border-radius: 3px;"></div>
                                            </div>
                                            <span style="font-weight: 700; font-size: 0.8rem; color: var(--text-color);">{{ $row->persen }}%</span>
                                        </div>
                                    </td>
                                    <td data-label="Predikat" style="padding: 10px 14px; text-align: center;">
                                        <span class="badge {{ $row->persen >= 100 ? 'badge-success' : ($row->persen >= 75 ? 'badge-primary' : ($row->persen >= 50 ? 'badge-warning' : 'badge-danger')) }}"
                                            style="font-size: 0.72rem; padding: 3px 8px; font-weight: 600;">
                                            {{ $row->kategori }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 0.84rem;">
                                        Belum ada catatan capaian staf tendik pada periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

<!-- Modal Tambah / Edit Sasaran & Target Kinerja -->
<div id="modalTargetKinerja" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box;">
    <div class="card modal-card-responsive" style="max-width: 580px; width: 94%; max-height: 88vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 16px 40px rgba(0,0,0,0.5); border: 1px solid var(--border-color); background: var(--card-bg); overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
            <h3 id="modalTargetTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bullseye text-primary"></i> Tambah Sasaran &amp; Target Kinerja
            </h3>
            <button type="button" id="btnCloseModalTarget" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formTargetKinerja" action="{{ route('dashboard.tendik.target.store') }}" method="POST" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;">
            @csrf
            <div id="targetMethodOverride"></div>

            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px;">
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                        Bidang Tugas / Administrasi <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="bidang" id="inputTargetBidang" required class="form-control" style="font-size: 0.84rem; height: 38px; border-radius: 8px;">
                        @foreach ($bidangLabels as $bK => $bL)
                            <option value="{{ $bK }}" {{ ($filterBidang && $filterBidang === $bK) ? 'selected' : '' }}>{{ $bL }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                        Sasaran Program / Kegiatan <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="sasaran" id="inputTargetSasaran" placeholder="Contoh: Tersusunnya data kepegawaian" required class="form-control" style="font-size: 0.84rem; height: 38px; border-radius: 8px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                        Indikator Kinerja <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="indikator_kinerja" id="inputTargetIndikator" rows="3" placeholder="Contoh: Jumlah dokumen tenaga pendidik dan kependidikan" required class="form-control" style="font-size: 0.84rem; border-radius: 8px; resize: vertical;"></textarea>
                </div>

                <div class="form-grid-2" style="margin-bottom: 12px;">
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            Target Kuantitas <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="number" name="target_kuantitas" id="inputTargetKuantitas" value="1" min="1" required class="form-control" style="font-size: 0.84rem; height: 38px; border-radius: 8px;">
                    </div>
                    <div>
                        <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); margin-bottom: 4px; display: block;">
                            Satuan Ukur <span style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" name="satuan" id="inputTargetSatuan" value="dokumen" placeholder="dokumen / laporan / kegiatan" required class="form-control" style="font-size: 0.84rem; height: 38px; border-radius: 8px;">
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; flex-shrink: 0;">
                <button type="button" id="btnCancelModalTarget" class="btn btn-outline" style="padding: 7px 16px; font-size: 0.82rem; border-radius: 8px;">Batal</button>
                <button type="submit" class="btn btn-primary" style="padding: 7px 20px; font-size: 0.82rem; border-radius: 8px; font-weight: 700; background: #6366f1; border: none; color: #fff;">
                    <i class="fas fa-save me-1"></i> Simpan Target
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payload JSON Data Chart untuk JS Modular -->
<script type="application/json" id="targetCapaianChartData">
    {!! json_encode([
        'timeSeries' => $chartTimeSeries,
        'bidang'     => $chartBidang,
        'status'     => $chartStatus,
    ], JSON_UNESCAPED_UNICODE) !!}
</script>
@endsection

@push('scripts')
    <script src="{{ asset('js/tendik-target-capaian.js') }}"></script>
@endpush
