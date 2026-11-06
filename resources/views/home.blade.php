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

        <!-- Warta & Berita Terkini (1 Berita Utama Diperbesar + Berita-berita Kecil) -->
        @if ($beritaUtama)
            <section id="berita" class="news-root-section" style="margin-bottom: clamp(2.5rem, 5vw, 4rem);">
                <div class="section-header" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; text-align: left; gap: var(--space-6); margin-bottom: var(--space-8);">
                    <div>
                        <span class="section-kicker"><i class="fas fa-newspaper me-1"></i> Warta &amp; Kabar Sekolah</span>
                        <h2 class="section-title" style="margin-bottom: var(--space-2);">Berita &amp; Informasi Terkini</h2>
                        <p class="section-desc" style="margin: 0;">Ikuti perkembangan agenda akademik, prestasi siswa, dan publikasi resmi sekolah.</p>
                    </div>
                    <a href="{{ route('berita.index') }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: var(--space-2); height: 38px; padding: 0 var(--space-8); border-radius: var(--radius-lg); font-size: var(--text-sm); font-weight: 600; text-decoration: none;">
                        <span>Semua Berita</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="news-root-grid">
                    <!-- 1 Berita Utama yang Diperbesar -->
                    <article class="news-hero-card card">
                        <a href="{{ route('berita.show', $beritaUtama->slug) }}" class="news-hero-image-wrap" style="text-decoration: none;">
                            @if ($beritaUtama->gambar)
                                <img src="{{ asset($beritaUtama->gambar) }}" alt="{{ $beritaUtama->judul }}" class="news-hero-img" loading="lazy">
                            @else
                                <div class="news-placeholder-banner">
                                    <i class="fas fa-newspaper news-placeholder-icon"></i>
                                    <span class="news-placeholder-tag">{{ $beritaUtama->kategori }}</span>
                                </div>
                            @endif
                            <span class="news-badge-category">{{ $beritaUtama->kategori }}</span>
                        </a>
                        <div class="news-hero-body">
                            <div class="news-meta-row">
                                <span><i class="fas fa-calendar-day me-1"></i> {{ $beritaUtama->created_at ? $beritaUtama->created_at->translatedFormat('d M Y') : '-' }}</span>
                                <span>&bull;</span>
                                <span><i class="fas fa-user-pen me-1"></i> {{ $beritaUtama->penulis_nama ?: 'Redaksi' }}</span>
                                <span>&bull;</span>
                                <span><i class="fas fa-eye me-1"></i> {{ number_format($beritaUtama->views) }} kali</span>
                            </div>
                            <h3 class="news-hero-title">
                                <a href="{{ route('berita.show', $beritaUtama->slug) }}">{{ $beritaUtama->judul }}</a>
                            </h3>
                            <p class="news-hero-excerpt">
                                {{ $beritaUtama->excerpt }}
                            </p>
                            <div class="news-hero-action">
                                <a href="{{ route('berita.show', $beritaUtama->slug) }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: var(--space-2); font-size: var(--text-sm);">
                                    <span>Baca Selengkapnya</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>

                    <!-- Berita-berita Kecil Setelahnya -->
                    <div class="news-sub-list">
                        @forelse ($beritaLainnya as $bKecil)
                            <article class="news-sub-card card">
                                <a href="{{ route('berita.show', $bKecil->slug) }}" class="news-sub-thumb-wrap" style="text-decoration: none;">
                                    @if ($bKecil->gambar)
                                        <img src="{{ asset($bKecil->gambar) }}" alt="{{ $bKecil->judul }}" class="news-sub-thumb" loading="lazy">
                                    @else
                                        <div class="news-sub-placeholder">
                                            <i class="fas fa-bullhorn"></i>
                                        </div>
                                    @endif
                                </a>
                                <div class="news-sub-content">
                                    <div class="news-sub-meta">
                                        <span class="badge badge-accent" style="font-size: var(--text-2xs); padding: 2px 6px;">{{ $bKecil->kategori }}</span>
                                        <span class="news-sub-date"><i class="fas fa-calendar-alt me-1"></i>{{ $bKecil->created_at ? $bKecil->created_at->translatedFormat('d M Y') : '-' }}</span>
                                        <span class="news-sub-date"><i class="fas fa-eye me-1"></i>{{ number_format($bKecil->views) }}</span>
                                    </div>
                                    <h4 class="news-sub-title">
                                        <a href="{{ route('berita.show', $bKecil->slug) }}">{{ $bKecil->judul }}</a>
                                    </h4>
                                    <p class="news-sub-desc">
                                        {{ Str::limit($bKecil->excerpt, 80) }}
                                    </p>
                                </div>
                            </article>
                        @empty
                            <div class="card" style="padding: var(--space-8); text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                <i class="fas fa-info-circle mb-2" style="font-size: var(--text-2xl); opacity: 0.5;"></i>
                                <p style="margin: 0; font-size: var(--text-sm);">Nantikan publikasi dan artikel berita sekolah berikutnya.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif

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
                $totSesuai = array_sum(array_column($progres_rombel, 'sesuai'));
                $totTidakSesuai = array_sum(array_column($progres_rombel, 'tidak_sesuai'));
                $totBelum = array_sum(array_column($progres_rombel, 'belum_konfirmasi'));
                $totBerkas = array_sum(array_column($progres_rombel, 'berkas_valid'));

                $persenTotSesuai = $totSemuaSiswa > 0 ? round(($totSesuai / $totSemuaSiswa) * 100, 1) : 0;
                $persenTotTidak = $totSemuaSiswa > 0 ? round(($totTidakSesuai / $totSemuaSiswa) * 100, 1) : 0;
                $persenTotBelum = $totSemuaSiswa > 0 ? round(($totBelum / $totSemuaSiswa) * 100, 1) : 0;
                $persenTotBerkas = $totSemuaSiswa > 0 ? round(($totBerkas / $totSemuaSiswa) * 100, 1) : 0;
            @endphp
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 1.5rem;">
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--success); font-weight: 700; text-transform: uppercase;">Konfirmasi Sesuai</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--success); line-height: 1.2; margin-top: 4px;">{{ $persenTotSesuai }}%</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">{{ number_format($totSesuai) }} siswa terverifikasi</div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--warning, #f59e0b); font-weight: 700; text-transform: uppercase;">Perlu Perbaikan</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--warning, #f59e0b); line-height: 1.2; margin-top: 4px;">{{ $persenTotTidak }}%</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">{{ number_format($totTidakSesuai) }} data tidak sesuai</div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Belum Konfirmasi</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); line-height: 1.2; margin-top: 4px;">{{ $persenTotBelum }}%</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">{{ number_format($totBelum) }} siswa belum lapor</div>
                </div>
                <div style="background: var(--input-bg); border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); padding: 12px 16px;">
                    <div style="font-size: 0.75rem; color: #8b5cf6; font-weight: 700; text-transform: uppercase;">Siswa Berkas Valid</div>
                    <div style="font-size: 1.5rem; font-weight: 800; color: #8b5cf6; line-height: 1.2; margin-top: 4px;">{{ $persenTotBerkas }}%</div>
                    <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">{{ number_format($totBerkas) }} siswa berkas lengkap</div>
                </div>
            </div>

            <!-- Toolbar Filter & Search -->
            <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <input type="text" id="rombelSearch" placeholder="Cari rombel atau jurusan..." 
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
                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.72rem; color: var(--text-muted); flex-wrap: wrap;">
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--success);"></span> Sesuai</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--warning, #f59e0b);"></span> Perbaikan</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--text-muted);"></span> Belum</span>
                    <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#8b5cf6;"></span> Berkas Valid</span>
                </div>
            </div>

            <!-- Tabel Progres Rombel (Maksimal 10 Baris Default, Dapat Discroll) -->
            <div style="max-height: 540px; overflow-y: auto; overflow-x: auto; border: 1px solid var(--border-glass); border-radius: var(--radius-xl, 14px); position: relative;">
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.85rem; text-align: left;" id="tableProgresRombel">
                    <thead style="position: sticky; top: 0; z-index: 5;">
                        <tr style="background: var(--bg-card, #ffffff); border-bottom: 2px solid var(--border-glass); color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <th style="padding: 12px 14px; width: 45px; text-align: center; background: inherit; border-bottom: 2px solid var(--border-glass);">No</th>
                            <th class="th-sortable" data-col="rombel" style="padding: 12px 14px; min-width: 180px; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Rombel / Jurusan</span> <i class="fas fa-sort ms-1 sort-icon" style="opacity: 0.4;"></i>
                            </th>
                            <th class="th-sortable" data-col="sesuai" style="padding: 12px 14px; min-width: 170px; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Konfirmasi Sesuai</span> <i class="fas fa-sort ms-1 sort-icon" style="opacity: 0.4;"></i>
                            </th>
                            <th class="th-sortable" data-col="tidak_sesuai" style="padding: 12px 14px; min-width: 170px; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Perlu Perbaikan</span> <i class="fas fa-sort ms-1 sort-icon" style="opacity: 0.4;"></i>
                            </th>
                            <th class="th-sortable" data-col="belum" style="padding: 12px 14px; min-width: 170px; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Belum Konfirmasi</span> <i class="fas fa-sort ms-1 sort-icon" style="opacity: 0.4;"></i>
                            </th>
                            <th class="th-sortable" data-col="berkas" style="padding: 12px 14px; min-width: 170px; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Berkas Valid</span> <i class="fas fa-sort ms-1 sort-icon" style="opacity: 0.4;"></i>
                            </th>
                            <th class="th-sortable sorted-desc" data-col="kualitas" style="padding: 12px 14px; min-width: 120px; text-align: center; cursor: pointer; user-select: none; background: inherit; border-bottom: 2px solid var(--border-glass);">
                                <span>Kualitas Data</span> <i class="fas fa-sort-down ms-1 sort-icon" style="color: var(--success);"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProgresRombel">
                        @forelse ($progres_rombel as $idx => $r)
                            <tr class="rombel-row" 
                                data-rombel="{{ strtolower($r['rombel']) }}" 
                                data-jurusan="{{ strtolower($r['jurusan']) }}"
                                data-tingkat="{{ $r['tingkat'] }}"
                                data-val-rombel="{{ $r['rombel'] }}"
                                data-val-sesuai="{{ $r['persen_sesuai'] }}"
                                data-val-tidak_sesuai="{{ $r['persen_tidak_sesuai'] }}"
                                data-val-belum="{{ $r['persen_belum'] }}"
                                data-val-berkas="{{ $r['persen_berkas'] }}"
                                data-val-kualitas="{{ $r['kualitas'] }}"
                                style="border-bottom: 1px solid var(--border-glass); transition: background 0.15s ease;">
                                <td class="row-num" style="padding: 10px 14px; text-align: center; color: var(--text-muted); font-weight: 600;">{{ $idx + 1 }}</td>
                                <td style="padding: 10px 14px;">
                                    <div style="font-weight: 700; color: var(--text-main);">{{ $r['rombel'] }}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">{{ $r['jurusan'] }}</div>
                                </td>
                                <!-- Bar 1: Konfirmasi Sesuai -->
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 4px; font-weight: 700; color: var(--success);">
                                        <span>{{ $r['persen_sesuai'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_sesuai'] }}%; height: 100%; background: var(--success); border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <!-- Bar 2: Perlu Perbaikan (Tidak Sesuai) -->
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 4px; font-weight: 700; color: var(--warning, #f59e0b);">
                                        <span>{{ $r['persen_tidak_sesuai'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_tidak_sesuai'] }}%; height: 100%; background: var(--warning, #f59e0b); border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <!-- Bar 3: Belum Konfirmasi -->
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 4px; font-weight: 700; color: var(--text-muted);">
                                        <span>{{ $r['persen_belum'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_belum'] }}%; height: 100%; background: rgba(148, 163, 184, 0.6); border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <!-- Bar 4: Berkas Valid -->
                                <td style="padding: 10px 14px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 4px; font-weight: 700; color: #8b5cf6;">
                                        <span>{{ $r['persen_berkas'] }}%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden;">
                                        <div style="width: {{ $r['persen_berkas'] }}%; height: 100%; background: #8b5cf6; border-radius: 99px;"></div>
                                    </div>
                                </td>
                                <!-- Kualitas Data -->
                                <td style="padding: 10px 14px; text-align: center; background: rgba(16, 185, 129, 0.03);">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 52px; padding: 4px 8px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; {{ $r['kualitas'] >= 60 ? 'background: rgba(16, 185, 129, 0.14); color: var(--success);' : ($r['kualitas'] >= 30 ? 'background: rgba(245, 158, 11, 0.14); color: var(--warning, #f59e0b);' : 'background: rgba(239, 68, 68, 0.12); color: var(--danger, #ef4444);') }}">
                                        {{ $r['kualitas'] }}%
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
                const tbody = document.getElementById('tbodyProgresRombel');
                const headers = document.querySelectorAll('.th-sortable');

                let currentSortCol = 'kualitas';
                let currentSortAsc = false; // default desc kualitas data

                function renumberVisibleRows() {
                    let num = 1;
                    const rows = tbody.querySelectorAll('.rombel-row');
                    rows.forEach(function(row) {
                        if (row.style.display !== 'none') {
                            const numCell = row.querySelector('.row-num');
                            if (numCell) numCell.textContent = num++;
                        }
                    });
                }

                function filterRows() {
                    const rows = tbody.querySelectorAll('.rombel-row');
                    if (!rows.length) return;
                    const query = (searchInput.value || '').trim().toLowerCase();
                    const tingkat = (tingkatSelect.value || '').trim();
                    let count = 0;

                    rows.forEach(function(row) {
                        const rombel = row.getAttribute('data-rombel') || '';
                        const jurusan = row.getAttribute('data-jurusan') || '';
                        const rTingkat = row.getAttribute('data-tingkat') || '';

                        const matchQuery = !query || rombel.indexOf(query) !== -1 || jurusan.indexOf(query) !== -1;
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
                    renumberVisibleRows();
                }

                function sortTable(col) {
                    if (currentSortCol === col) {
                        currentSortAsc = !currentSortAsc;
                    } else {
                        currentSortCol = col;
                        currentSortAsc = (col === 'rombel'); // text default asc, angka default desc
                    }

                    // Update header icons
                    headers.forEach(function(th) {
                        const hCol = th.getAttribute('data-col');
                        const icon = th.querySelector('.sort-icon');
                        th.classList.remove('sorted-asc', 'sorted-desc');
                        if (icon) {
                            icon.className = 'fas fa-sort ms-1 sort-icon';
                            icon.style.opacity = '0.4';
                            icon.style.color = '';
                        }
                        if (hCol === currentSortCol) {
                            th.classList.add(currentSortAsc ? 'sorted-asc' : 'sorted-desc');
                            if (icon) {
                                icon.className = currentSortAsc ? 'fas fa-sort-up ms-1 sort-icon' : 'fas fa-sort-down ms-1 sort-icon';
                                icon.style.opacity = '1';
                                icon.style.color = 'var(--primary)';
                            }
                        }
                    });

                    // Sort rows in DOM
                    const rowsArray = Array.from(tbody.querySelectorAll('.rombel-row'));
                    rowsArray.sort(function(a, b) {
                        let valA = a.getAttribute('data-val-' + currentSortCol) || '';
                        let valB = b.getAttribute('data-val-' + currentSortCol) || '';

                        if (currentSortCol === 'rombel') {
                            const cmp = valA.localeCompare(valB, 'id', { numeric: true, sensitivity: 'base' });
                            return currentSortAsc ? cmp : -cmp;
                        } else {
                            const numA = parseFloat(valA) || 0;
                            const numB = parseFloat(valB) || 0;
                            return currentSortAsc ? (numA - numB) : (numB - numA);
                        }
                    });

                    rowsArray.forEach(function(r) { tbody.appendChild(r); });
                    renumberVisibleRows();
                }

                headers.forEach(function(th) {
                    th.addEventListener('click', function() {
                        const col = this.getAttribute('data-col');
                        if (col) sortTable(col);
                    });
                });

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
