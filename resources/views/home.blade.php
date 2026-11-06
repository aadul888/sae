@extends('layouts.app')

@section('title', 'SAE (Sistem Aplikasi Edukasi)')
@section('meta_description', 'SAE (Sistem Aplikasi Edukasi) — Platform digital terpadu untuk absensi pintar, manajemen
    data peserta didik, validasi berkas, dan layanan informasi akademik realtime.')

@section('content')
    <div class="container">
        <!-- Info Ticker -->
        <div class="ticker-wrap">
            <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO</div>
            <div class="ticker-track">
                <div class="ticker-content">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">● {{ $info }}</span>
                    @endforeach
                </div>
                <div class="ticker-content" aria-hidden="true">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">● {{ $info }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Hero Section -->
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-shield-halved"></i> Ekosistem Pendidikan Digital 2026</span>
                <h1 class="hero-title">Sistem Aplikasi<br><span>Edukasi (SAE)</span></h1>
                <p class="hero-desc">
                    Aplikasi terintegrasi untuk absensi pintar, manajemen data peserta didik, validasi berkas, dan layanan
                    informasi akademik realtime.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('login') }}" class="btn btn-primary"
                        style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-right-to-bracket"></i> Masuk Portal
                    </a>
                    <a href="{{ route('login', ['tab' => 'ortu']) }}" class="btn btn-outline"
                        style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-people-roof text-success"></i> Portal Orang Tua
                    </a>
                </div>
            </div>

            <!-- NISN Verification Card -->
            <div id="nisn" class="card-nisn">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-id-card-clip text-primary"></i> Pengecekan Data Peserta
                        Didik</span>
                    <span
                        style="font-size: var(--text-xs, 0.75rem); color: var(--accent); background: rgba(6,182,212,0.1); padding: 2px 8px; border-radius: var(--radius-sm, 6px);">Live
                        Check</span>
                </div>
                <p style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-bottom: 1rem;">
                    Masukkan NISN untuk memverifikasi status aktif peserta didik secara resmi.
                </p>
                <form id="nisnCheckForm" novalidate>
                    <div class="input-group" style="position: relative;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="input-label" for="nisnInput" style="margin-bottom: 0;">Nomor Induk Siswa Nasional
                                (NISN)</label>
                            <span id="nisnCounterBadge"
                                style="font-size: var(--text-xs, 0.75rem); font-weight: 700; color: var(--text-muted); font-variant-numeric: tabular-nums; transition: color 0.2s;">
                                0 / 10 digit
                            </span>
                        </div>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="text" id="nisnInput" class="input-field"
                                placeholder="Ketik 10 digit angka NISN..." maxlength="10" inputmode="numeric"
                                autocomplete="off"
                                style="letter-spacing: 1.5px; font-weight: 600; padding-right: 36px; transition: border-color 0.2s, box-shadow 0.2s;">
                            <span id="nisnStatusIcon"
                                style="position: absolute; right: 12px; pointer-events: none; display: none;"></span>
                        </div>
                        <div id="nisnErrorMsg"
                            style="display: none; font-size: var(--text-sm, 0.8125rem); color: var(--danger, #ef4444); margin-top: 6px; align-items: center; gap: 5px;">
                            <i class="fas fa-circle-exclamation"></i> <span>NISN harus berupa angka dan pas 10 digit.</span>
                        </div>
                    </div>
                    <button type="submit" id="btnCheckNisn" class="btn btn-primary"
                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fas fa-magnifying-glass"></i> Verifikasi Data
                    </button>
                </form>
            </div>
        </section>

        <!-- Key Statistics -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-user-group"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['total_students']) }}</div>
                    <div class="kpi-desc">Total Peserta Didik</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-person"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['male_count']) }}</div>
                    <div class="kpi-desc">Peserta Didik Laki-laki</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-person-dress"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['female_count']) }}</div>
                    <div class="kpi-desc">Peserta Didik Perempuan</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-chalkboard"></i></div>
                <div>
                    <div class="kpi-num">{{ number_format($stats['total_classes']) }}</div>
                    <div class="kpi-desc">Rombongan Belajar</div>
                </div>
            </div>
        </section>

        <!-- Progres Pengiriman & Kualitas Data per Rombel -->
        <section id="progres-data" class="analytics-card" style="margin-bottom: clamp(2.5rem, 5vw, 4rem);">
            <div class="analytics-head" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 1rem;">
                <div>
                    <span class="section-kicker"><i class="fas fa-chart-pie me-1"></i> Transparansi Data Mutu</span>
                    <h3 style="font-size: clamp(1.15rem, 3vw, 1.4rem); font-weight: 800; margin-bottom: 4px;">Progres Pengiriman &amp; Kualitas Data per Rombel</h3>
                    <p style="color: var(--text-muted); font-size: clamp(0.75rem, 2vw, 0.875rem); margin: 0;">
                        Pemantauan realtime status konfirmasi identitas siswa dan validitas berkas wajib (KK, Ijazah, Akta) per rombongan belajar.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="{{ route('about') }}#modul" class="btn btn-outline" style="font-size: 0.8rem; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fas fa-layer-group text-primary"></i> Seluruh Layanan &amp; Modul
                    </a>
                </div>
            </div>

            <!-- Ringkasan Global Progress -->
            @php
                $totSemuaSiswa = array_sum(array_column($progres_rombel, 'total_siswa'));
                $totSemuaKirim = array_sum(array_column($progres_rombel, 'siswa_kirim'));
                $totSemuaIdValid = array_sum(array_column($progres_rombel, 'id_valid'));
                $totSemuaBerkasValid = array_sum(array_column($progres_rombel, 'berkas_valid'));
                $persenTotalKirim = $totSemuaSiswa > 0 ? round(($totSemuaKirim / $totSemuaSiswa) * 100, 1) : 0;
                $persenTotalValid = $totSemuaSiswa > 0 ? round(($totSemuaIdValid / $totSemuaSiswa) * 100, 1) : 0;
            @endphp
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 1.5rem;">
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Sasaran Siswa</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); line-height: 1.2; margin-top: 4px;">{{ number_format($totSemuaSiswa) }} <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">siswa ({{ count($progres_rombel) }} rombel)</span></div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; text-transform: uppercase;">Sudah Terkirim / Konfirmasi</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); line-height: 1.2; margin-top: 4px;">{{ number_format($totSemuaKirim) }} <span style="font-size: 0.8rem; font-weight: 600;">({{ $persenTotalKirim }}%)</span></div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--success); font-weight: 700; text-transform: uppercase;">Identitas Sesuai / Terverifikasi</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--success); line-height: 1.2; margin-top: 4px;">{{ number_format($totSemuaIdValid) }} <span style="font-size: 0.8rem; font-weight: 600;">({{ $persenTotalValid }}%)</span></div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: #8b5cf6; font-weight: 700; text-transform: uppercase;">Siswa Berkas Valid</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #8b5cf6; line-height: 1.2; margin-top: 4px;">{{ number_format($totSemuaBerkasValid) }} <span style="font-size: 0.8rem; font-weight: 500; color: var(--text-muted);">siswa lolos</span></div>
                </div>
            </div>

            <!-- Toolbar Filter & Search -->
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <input type="text" id="rombelSearch" placeholder="Cari rombel atau wali kelas..." 
                        style="background: var(--input-bg); border: 1px solid var(--border-glass); color: var(--text-main); border-radius: var(--radius-md, 8px); padding: 6px 12px; font-size: 0.85rem; min-width: 240px; outline: none;">
                    
                    <select id="rombelTingkat" style="background: var(--input-bg); border: 1px solid var(--border-glass); color: var(--text-main); border-radius: var(--radius-md, 8px); padding: 6px 10px; font-size: 0.85rem; outline: none;">
                        <option value="">Semua Tingkat</option>
                        <option value="X">Kelas X</option>
                        <option value="XI">Kelas XI</option>
                        <option value="XII">Kelas XII</option>
                    </select>

                    <span id="rombelCountBadge" style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; padding: 4px 8px; background: rgba(0,0,0,0.03); border-radius: 6px;">
                        Menampilkan {{ count($progres_rombel) }} rombel
                    </span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">
                    <i class="fas fa-arrows-rotate me-1 text-primary"></i> Data terhubung langsung dari Dapodik &amp; SAE
                </div>
            </div>

            <!-- Tabel Progres Rombel -->
            <div style="overflow-x: auto; border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px);">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;" id="tableProgresRombel">
                    <thead>
                        <tr style="background: rgba(0,0,0,0.02); border-bottom: 2px solid var(--border-glass); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <th style="padding: 10px 14px; width: 45px; text-align: center;">No</th>
                            <th style="padding: 10px 14px; min-width: 150px;">Rombel / Tingkat</th>
                            <th style="padding: 10px 14px; min-width: 180px;">Wali Kelas</th>
                            <th style="padding: 10px 14px; text-align: center; width: 85px;">Siswa</th>
                            <th style="padding: 10px 14px; min-width: 190px;">Pengiriman Konfirmasi</th>
                            <th style="padding: 10px 14px; min-width: 190px;">Identitas Valid</th>
                            <th style="padding: 10px 14px; text-align: center; min-width: 110px;">Berkas Valid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($progres_rombel as $idx => $r)
                            <tr class="rombel-row" 
                                data-rombel="{{ strtolower($r['rombel']) }}" 
                                data-wali="{{ strtolower($r['wali_kelas']) }}" 
                                data-tingkat="{{ $r['tingkat'] }}"
                                style="border-bottom: 1px solid var(--border-glass); transition: background 0.15s ease;">
                                <td style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-weight: 600;">{{ $idx + 1 }}</td>
                                <td style="padding: 10px 14px;">
                                    <div style="font-weight: 700; color: var(--text-main);">{{ $r['rombel'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $r['jurusan'] }}</div>
                                </td>
                                <td style="padding: 10px 14px; color: var(--text-main);">
                                    <i class="fas fa-chalkboard-user me-1 text-primary" style="font-size: 0.75rem;"></i>
                                    {{ $r['wali_kelas'] }}
                                </td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 700; color: var(--text-main);">
                                    {{ $r['total_siswa'] }}
                                </td>
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                                        <span style="font-weight: 600; color: var(--text-main);">{{ $r['siswa_kirim'] }} / {{ $r['total_siswa'] }}</span>
                                        <span style="font-weight: 700; color: var(--primary);">{{ $r['persen_kirim'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_kirim'] }}%; height: 100%; background: var(--primary); border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 4px;">
                                        <span style="font-weight: 600; color: var(--text-main);">{{ $r['id_valid'] }} / {{ $r['total_siswa'] }}</span>
                                        <span style="font-weight: 700; color: var(--success);">{{ $r['persen_valid'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_valid'] }}%; height: 100%; background: var(--success); border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">
                                        <i class="fas fa-file-circle-check"></i> {{ $r['berkas_valid'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 24px; text-align: center; color: var(--text-muted);">
                                    Belum ada data rombongan belajar aktif yang terdata.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('rombelSearch');
                const tingkatSelect = document.getElementById('rombelTingkat');
                const badge = document.getElementById('rombelCountBadge');
                const rows = document.querySelectorAll('.rombel-row');

                function filterRows() {
                    if (!rows.length) return;
                    const query = (searchInput.value || '').trim().toLowerCase();
                    const tingkat = (tingkatSelect.value || '').trim();
                    let count = 0;

                    rows.forEach(function(row) {
                        const rombel = row.getAttribute('data-rombel') || '';
                        const wali = row.getAttribute('data-wali') || '';
                        const rTingkat = row.getAttribute('data-tingkat') || '';

                        const matchQuery = !query || rombel.indexOf(query) !== -1 || wali.indexOf(query) !== -1;
                        const matchTingkat = !tingkat || rTingkat === tingkat;

                        if (matchQuery && matchTingkat) {
                            row.style.display = '';
                            count++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    if (badge) {
                        badge.textContent = 'Menampilkan ' + count + ' rombel';
                    }
                }

                if (searchInput) searchInput.addEventListener('input', filterRows);
                if (tingkatSelect) tingkatSelect.addEventListener('change', filterRows);
            });
        </script>

        <!-- Interactive Data Analytics -->
        <section id="statistik" class="analytics-card">
            <div class="analytics-head">
                <h3 style="font-size: clamp(1.15rem, 3vw, 1.4rem); font-weight: 800; margin-bottom: 4px;">Statistik &amp;
                    Demografi Peserta Didik</h3>
                <p style="color: var(--text-muted); font-size: clamp(0.75rem, 2vw, 0.875rem);">Visualisasi perbandingan
                    jurusan, tingkat kelas, dan rasio gender.</p>
            </div>

            <!-- Charts Container -->
            <div class="charts-row">
                <div class="chart-box">
                    <div class="chart-box-title"><i class="fas fa-chart-column me-2 text-primary"></i>Distribusi Peserta
                        Didik per Program Keahlian</div>
                    <div class="chart-container-inner">
                        <canvas id="barMajorChart"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <div class="chart-box-title"><i class="fas fa-chart-pie me-2 text-accent"></i>Proporsi Tingkat Kelas
                    </div>
                    <div class="chart-container-inner">
                        <canvas id="pieGradeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Dynamic Filter Bar -->
            <div class="filter-strip">
                <select id="filterMajor" class="filter-select" aria-label="Pilih Jurusan">
                    <option value="">Semua Jurusan</option>
                    @foreach ($major_data as $m)
                        <option value="{{ $m['nama_jurusan'] }}">{{ $m['nama_jurusan'] }}</option>
                    @endforeach
                </select>
                <select id="filterGrade" class="filter-select" aria-label="Pilih Tingkat">
                    <option value="">Semua Tingkat</option>
                    <option value="X">Kelas X</option>
                    <option value="XI">Kelas XI</option>
                    <option value="XII">Kelas XII</option>
                </select>
            </div>
        </section>
    </div>
@endsection

@section('scripts')
    <script id="homeChartsPayload" type="application/json">
        {!! json_encode([
            'stats' => $stats,
            'major_data' => $major_data,
            'chart_detail' => $chart_detail,
        ], JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script
        src="{{ asset('js/home.js') }}?v={{ file_exists(public_path('js/home.js')) ? filemtime(public_path('js/home.js')) : time() }}">
    </script>
@endsection
