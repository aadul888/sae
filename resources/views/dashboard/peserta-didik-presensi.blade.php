@extends('layouts.dashboard')

@section('title', 'Riwayat Presensi Harian — SAE')
@section('dash_title', 'Riwayat Presensi Harian')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/presensi.css') }}?v={{ file_exists(public_path('css/presensi.css')) ? filemtime(public_path('css/presensi.css')) : time() }}">
    <style>
        .filter-period-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        }
        .period-pill-group {
            display: inline-flex;
            background: rgba(148, 163, 184, 0.12);
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
            margin-bottom: 16px;
        }
        .period-pill-btn {
            border: none;
            background: transparent;
            color: var(--text-muted, #64748b);
            font-size: 0.82rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .period-pill-btn.active {
            background: var(--primary, #6366f1);
            color: #ffffff !important;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }
        .stat-card-kpi {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 14px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card-kpi:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        }
        .stat-icon-wrapper {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
    </style>
@endpush

@section('content')
    <!-- Dash Banner -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-calendar-check text-primary me-2"></i> Riwayat Presensi Harian Peserta Didik
            </h2>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Rekapitulasi lengkap catatan kehadiran harian, ketepatan waktu masuk/pulang, bukti snapshot kamera gerbang, serta unduh laporan resmi.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="{{ route('dashboard.peserta-didik.presensi.cetak', request()->all()) }}" target="_blank" class="btn btn-outline"
                style="padding: 9px 16px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-print text-primary"></i> Cetak / Unduh Laporan
            </a>
            <button type="button" class="btn btn-primary" id="btnBukaModalIzin"
                style="padding: 9px 18px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-signature"></i> Ajukan Surat Izin / Sakit
            </button>
        </div>
    </div>

    <!-- Panel Filter Periode (Per Bulan, Per Semester, Per Tahun) -->
    <div class="filter-period-card">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
            <div>
                <div style="font-size: 0.92rem; font-weight: 800; color: var(--text-color);">
                    <i class="fas fa-filter text-primary me-1"></i> Pilih Periode Riwayat
                </div>
                <div style="font-size: 0.78rem; color: var(--text-muted);">
                    Menampilkan data kehadiran untuk: <strong style="color: var(--primary);">{{ $range['label'] }}</strong>
                </div>
            </div>

            <!-- Segmented Switcher Pill Buttons -->
            <div class="period-pill-group">
                <a href="{{ route('dashboard.peserta-didik.presensi.index', array_merge(request()->except('page'), ['periode' => 'bulan'])) }}"
                    class="period-pill-btn {{ $periodeTipe === 'bulan' ? 'active' : '' }}">
                    <i class="fas fa-calendar-day"></i> Per Bulan
                </a>
                <a href="{{ route('dashboard.peserta-didik.presensi.index', array_merge(request()->except('page'), ['periode' => 'semester'])) }}"
                    class="period-pill-btn {{ $periodeTipe === 'semester' ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Per Semester
                </a>
                <a href="{{ route('dashboard.peserta-didik.presensi.index', array_merge(request()->except('page'), ['periode' => 'tahun'])) }}"
                    class="period-pill-btn {{ $periodeTipe === 'tahun' ? 'active' : '' }}">
                    <i class="fas fa-calendar"></i> Per Tahun
                </a>
            </div>
        </div>

        <!-- Filter Form Controls -->
        <form method="GET" action="{{ route('dashboard.peserta-didik.presensi.index') }}" id="formFilterPresensi">
            <input type="hidden" name="periode" value="{{ $periodeTipe }}">

            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                @if ($periodeTipe === 'bulan')
                    <!-- Filter Bulan -->
                    <div style="flex: 1; min-width: 140px;">
                        <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                            Bulan:
                        </label>
                        <select name="bulan" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 7px 12px;">
                            @php
                                $namaBulan = [
                                    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                ];
                            @endphp
                            @foreach ($namaBulan as $mKey => $mLabel)
                                <option value="{{ $mKey }}" {{ str_pad((string)$bulan, 2, '0', STR_PAD_LEFT) === $mKey ? 'selected' : '' }}>
                                    {{ $mLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Tahun -->
                    <div style="flex: 1; min-width: 120px;">
                        <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                            Tahun:
                        </label>
                        <select name="tahun" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 7px 12px;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                @elseif ($periodeTipe === 'semester')
                    <!-- Filter Semester -->
                    <div style="flex: 1; min-width: 150px;">
                        <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                            Semester:
                        </label>
                        <select name="semester" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 7px 12px;">
                            <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1 (Ganjil: Juli - Des)</option>
                            <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2 (Genap: Jan - Jun)</option>
                        </select>
                    </div>

                    <!-- Filter Tahun Ajaran -->
                    <div style="flex: 1; min-width: 140px;">
                        <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                            Tahun Ajaran:
                        </label>
                        <select name="tahun_ajaran" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 7px 12px;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                @php $ta = ($y - 1) . '/' . $y; @endphp
                                <option value="{{ $ta }}" {{ $tahunAjaran === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                            @endfor
                        </select>
                    </div>
                @else
                    <!-- Filter Mode Per Tahun -->
                    <div style="flex: 1; min-width: 150px;">
                        <label style="display: block; font-size: 0.76rem; font-weight: 700; color: var(--text-muted); margin-bottom: 5px;">
                            Tahun Ajaran / Periode:
                        </label>
                        <select name="tahun_ajaran" class="form-control" style="width: 100%; font-size: 0.85rem; padding: 7px 12px;">
                            @for ($y = date('Y') + 1; $y >= 2024; $y--)
                                @php $ta = ($y - 1) . '/' . $y; @endphp
                                <option value="{{ $ta }}" {{ $tahunAjaran === $ta ? 'selected' : '' }}>Tahun Ajaran {{ $ta }}</option>
                            @endfor
                        </select>
                    </div>
                @endif

                <div>
                    <button type="submit" class="btn btn-primary" style="padding: 7px 18px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-magnifying-glass"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Student Info & Mini QR Card Grid -->
    <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 20px; margin-bottom: 24px;">
        <!-- Left: Student Biodata -->
        <div class="card" style="padding: 20px; border-radius: 16px; display: flex; align-items: center; gap: 20px;">
            <img src="{{ $siswa->foto_url ?: asset('img/logo-dark.png') }}" alt="{{ $siswa->nama }}"
                style="width: 78px; height: 78px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary); box-shadow: 0 0 16px var(--primary-glow);"
                onerror="this.src='/img/logo-dark.png';">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 4px 0;">
                    {{ $siswa->nama }}
                </h3>
                <div style="font-size: 0.86rem; color: var(--primary); font-weight: 700; margin-bottom: 4px;">
                    {{ $siswa->nama_rombel ?: 'Rombel Belum Ditentukan' }} &bull; {{ $siswa->jurusan_id_str ?: 'Umum' }}
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <span style="font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">NISN: {{ $siswa->nisn }}</span>
                    @if ($siswa->wali_nama)
                        <span style="font-size: 0.78rem; color: var(--text-muted);"><i class="fas fa-chalkboard-user me-1 text-accent"></i> Wali: {{ $siswa->wali_nama }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Fast QR Code (Anti-Screenshot) -->
        <div class="card" style="padding: 16px; border-radius: 16px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 16px;">
            <div style="background: #fff; padding: 6px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                <img src="{{ $dynamicQrUri }}" alt="QR Presensi" style="width: 78px; height: 78px; display: block;">
            </div>
            <div style="text-align: left;">
                <div style="font-size: 0.85rem; font-weight: 800; color: var(--text-color); margin-bottom: 2px;">
                    <i class="fas fa-qrcode text-primary me-1"></i> QR Presensi Digital
                </div>
                <div style="font-size: 0.72rem; color: var(--text-muted); line-height: 1.3;">
                    Pindai di terminal gerbang masuk sekolah saat tiba dan pulang.
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik Kehadiran (KPI Cards) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 24px;">
        <!-- Hadir Tepat Waktu -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.12); color: var(--success, #10b981);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--success, #10b981);">{{ $stats['hadir'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Tepat Waktu</div>
            </div>
        </div>

        <!-- Terlambat -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(245, 158, 11, 0.12); color: var(--warning, #f59e0b);">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--warning, #f59e0b);">{{ $stats['terlambat'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Terlambat</div>
            </div>
        </div>

        <!-- Izin -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(59, 130, 246, 0.12); color: var(--primary, #6366f1);">
                <i class="fas fa-file-signature"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary, #6366f1);">{{ $stats['izin'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Izin</div>
            </div>
        </div>

        <!-- Sakit -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
                <i class="fas fa-notes-medical"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: #8b5cf6;">{{ $stats['sakit'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Sakit</div>
            </div>
        </div>

        <!-- Dispen -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(6, 182, 212, 0.12); color: #06b6d4;">
                <i class="fas fa-award"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: #06b6d4;">{{ $stats['dispen'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Dispensasi</div>
            </div>
        </div>

        <!-- Alpha -->
        <div class="stat-card-kpi">
            <div class="stat-icon-wrapper" style="background: rgba(239, 68, 68, 0.12); color: var(--danger, #ef4444);">
                <i class="fas fa-circle-xmark"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--danger, #ef4444);">{{ $stats['alpha'] }}</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Alpha</div>
            </div>
        </div>

        <!-- Persentase Kehadiran -->
        <div class="stat-card-kpi" style="background: rgba(99, 102, 241, 0.05); border-color: rgba(99, 102, 241, 0.3);">
            <div class="stat-icon-wrapper" style="background: rgba(99, 102, 241, 0.2); color: var(--primary);">
                <i class="fas fa-percent"></i>
            </div>
            <div>
                <div style="font-size: 1.35rem; font-weight: 800; color: var(--primary);">{{ $stats['persen'] }}%</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Tingkat Hadir</div>
            </div>
        </div>
    </div>

    <!-- Container Tabel Datatable Responsif Presensi -->
    <div class="card table-responsive-stack" id="tableDataContainer" style="padding: 0; margin-bottom: 24px; border-radius: 16px; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    <i class="fas fa-table-list text-primary me-2"></i> Rincian Catatan Kehadiran Harian
                </h3>
                <span style="font-size: 0.75rem; color: var(--text-muted);">
                    Total {{ $stats['total'] }} rekaman hari belajar terdata pada periode ini.
                </span>
            </div>
            <div>
                <a href="{{ route('dashboard.peserta-didik.presensi.cetak', request()->all()) }}" target="_blank" class="btn btn-outline"
                    style="padding: 6px 14px; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-file-pdf text-danger"></i> Unduh / Cetak Laporan
                </a>
            </div>
        </div>

        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02); border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 50px; text-align: center;">No</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Tanggal</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px;">Hari</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Jam Masuk</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">Jam Pulang</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 130px;">Status</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 100px; text-align: center;">Bukti Foto</th>
                    <th style="padding: 12px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $index => $log)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px 16px; text-align: center; font-size: 0.82rem; color: var(--text-muted);">
                            {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                        </td>
                        <td style="padding: 12px 16px; font-weight: 700; font-size: 0.85rem; color: var(--text-color); font-family: monospace;">
                            {{ \Carbon\Carbon::parse($log->tanggal)->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-muted);">
                            {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('l') }}
                        </td>
                        <td style="padding: 12px 16px;">
                            @if ($log->jam_masuk)
                                <div style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">
                                    {{ substr($log->jam_masuk, 0, 5) }} WIB
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">
                                    Metode: {{ strtoupper($log->metode_masuk ?: 'RFID') }}
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.82rem;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px;">
                            @if ($log->jam_pulang)
                                <div style="font-weight: 700; font-family: monospace; font-size: 0.85rem; color: var(--text-color);">
                                    {{ substr($log->jam_pulang, 0, 5) }} WIB
                                </div>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">
                                    Metode: {{ strtoupper($log->metode_pulang ?: 'RFID') }}
                                </div>
                            @else
                                <span style="color: var(--text-muted); font-size: 0.82rem;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px;">
                            @if ($log->status === 'H')
                                <span class="badge badge-success" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-check-circle me-1"></i> Tepat Waktu
                                </span>
                            @elseif ($log->status === 'T')
                                <span class="badge badge-warning" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-clock me-1"></i> Terlambat {{ $log->menit_terlambat }}m
                                </span>
                            @elseif ($log->status === 'I')
                                <span class="badge badge-primary" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-file-signature me-1"></i> Izin
                                </span>
                            @elseif ($log->status === 'S')
                                <span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6; font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-notes-medical me-1"></i> Sakit
                                </span>
                            @elseif ($log->status === 'D')
                                <span class="badge badge-accent" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-award me-1"></i> Dispen
                                </span>
                            @else
                                <span class="badge badge-danger" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="fas fa-circle-xmark me-1"></i> Alpha
                                </span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; text-align: center;">
                            @php
                                $fotoMasukUrl = !empty($log->foto_masuk) ? asset('storage/' . ltrim($log->foto_masuk, '/')) : null;
                                $fotoPulangUrl = !empty($log->foto_pulang) ? asset('storage/' . ltrim($log->foto_pulang, '/')) : null;
                            @endphp
                            @if ($fotoMasukUrl)
                                <button type="button" class="btn btn-outline btn-view-snapshot"
                                    data-url="{{ $fotoMasukUrl }}"
                                    data-caption="Snapshot Masuk — {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('d M Y') }}"
                                    style="padding: 3px 6px; font-size: 0.72rem;" title="Foto Masuk">
                                    <i class="fas fa-camera text-primary"></i>
                                </button>
                            @endif
                            @if ($fotoPulangUrl)
                                <button type="button" class="btn btn-outline btn-view-snapshot"
                                    data-url="{{ $fotoPulangUrl }}"
                                    data-caption="Snapshot Pulang — {{ \Carbon\Carbon::parse($log->tanggal)->translatedFormat('d M Y') }}"
                                    style="padding: 3px 6px; font-size: 0.72rem; margin-left: 2px;" title="Foto Pulang">
                                    <i class="fas fa-camera text-success"></i>
                                </button>
                            @endif
                            @if (!$fotoMasukUrl && !$fotoPulangUrl)
                                <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                            @endif
                        </td>
                        <td style="padding: 12px 16px; font-size: 0.82rem; color: var(--text-muted);">
                            {{ $log->keterangan ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 36px 16px; color: var(--text-muted);">
                            <i class="fas fa-calendar-xmark mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <div style="font-weight: 700; margin-top: 6px;">Belum ada rekaman data presensi</div>
                            <div style="font-size: 0.8rem;">Tidak ditemukan catatan presensi pada periode yang Anda pilih.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Custom Pagination Baku SAE -->
        @if ($logs->hasPages())
            <div style="padding: 16px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 0.78rem; color: var(--text-muted);">
                    Menampilkan {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }} dari {{ $logs->total() }} data
                </div>
                <div class="custom-pagination">
                    @if ($logs->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $cur = $logs->currentPage();
                        $last = $logs->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp
                    @if ($from > 1)
                        <a href="{{ $logs->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2)
                            <span class="page-info">&hellip;</span>
                        @endif
                    @endif
                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $logs->url($i) }}" class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="page-info">&hellip;</span>
                        @endif
                        <a href="{{ $logs->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Modal View Snapshot Kamera -->
    <div id="modalSnapshotSaya" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 440px; width: 92%; margin: auto; padding: 20px; border-radius: 16px; text-align: center;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                <h4 id="snapshotSayaCaption" style="font-size: 0.95rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Foto Bukti Presensi
                </h4>
                <button type="button" id="btnCloseSnapshotSaya" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="margin: 10px 0;">
                <img id="imgSnapshotSaya" src="" alt="Snapshot" style="width: 100%; border-radius: 12px; object-fit: cover; max-height: 380px; border: 1px solid var(--border-color);">
            </div>
        </div>
    </div>

    <!-- Modal Pengajuan Izin / Sakit Mandiri -->
    <div id="modalPengajuanIzin" class="modal-backdrop" style="display: none; z-index: 99999 !important;">
        <div class="card" style="max-width: 500px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    <i class="fas fa-file-signature text-primary me-2"></i> Pengajuan Surat Izin / Sakit
                </h3>
                <button type="button" id="btnCloseModalIzin" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="formPengajuanIzin" enctype="multipart/form-data">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Jenis Permohonan:
                    </label>
                    <select name="jenis" required class="form-control" style="width: 100%; padding: 8px 12px; font-size: 0.85rem;">
                        <option value="izin">Izin (Keperluan Keluarga / Khusus)</option>
                        <option value="sakit">Sakit (Wajib Lampirkan Surat Dokter)</option>
                        <option value="dispen">Dispensasi (Lomba / Tugas Sekolah)</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                            Mulai Tanggal:
                        </label>
                        <input type="date" name="tanggal_mulai" value="{{ now()->toDateString() }}" required class="form-control" style="width: 100%; font-size: 0.85rem;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                            Sampai Tanggal:
                        </label>
                        <input type="date" name="tanggal_selesai" value="{{ now()->toDateString() }}" required class="form-control" style="width: 100%; font-size: 0.85rem;">
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Alasan &amp; Keterangan Lengkap:
                    </label>
                    <textarea name="alasan" rows="3" required placeholder="Jelaskan alasan izin atau kondisi sakit Anda..." class="form-control" style="width: 100%; font-size: 0.85rem;"></textarea>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-color); margin-bottom: 5px;">
                        Unggah Bukti Surat / Foto Surat Dokter:
                    </label>
                    <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="width: 100%; font-size: 0.82rem; padding: 7px;">
                    <small style="color: var(--text-muted); font-size: 0.72rem; display: block; margin-top: 4px;">
                        Format: JPG, PNG, atau PDF (Maksimal 4 MB).
                    </small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="btnCancelModalIzin" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.85rem;">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 22px; font-size: 0.85rem; font-weight: 700;">
                        Kirim Permohonan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/peserta-didik-presensi.js') }}?v={{ file_exists(public_path('js/peserta-didik-presensi.js')) ? filemtime(public_path('js/peserta-didik-presensi.js')) : time() }}"></script>
@endpush
