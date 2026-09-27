@extends('layouts.dashboard')

@section('title', 'Portal Orang Tua / Wali Murid — SAE')
@section('dash_title', 'Portal Orang Tua')

@section('content')
    @php
        $hour = date('H');
        $greeting = $hour < 11 ? 'Selamat Pagi,' : ($hour < 15 ? 'Selamat Siang,' : ($hour < 18 ? 'Selamat Sore,' : 'Selamat Malam,'));
        $studentPhoto = $pd->foto_url ?? null;
    @endphp

    {{-- Switcher Siswa Khusus Administrator --}}
    @if ($userRole === 'admin' && !empty($allPdList) && $allPdList->isNotEmpty())
        <div class="card" style="margin-bottom: 20px; padding: 12px 18px; border-radius: 12px; background: rgba(99,102,241,0.06); border: 1px dashed rgba(99,102,241,0.3);">
            <form method="GET" action="{{ route('dashboard.orang-tua') }}" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-user-shield"></i> Mode Administrator: Pratinjau Portal Orang Tua Siswa
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <select name="peserta_didik_id" onchange="this.form.submit()" class="form-control" style="font-size: 0.82rem; height: 36px; border-radius: 8px; min-width: 240px;">
                        @foreach ($allPdList as $item)
                            <option value="{{ $item->peserta_didik_id }}" {{ ($pd && $pd->peserta_didik_id === $item->peserta_didik_id) ? 'selected' : '' }}>
                                {{ $item->nama }} ({{ $item->nama_rombel ?: 'Tanpa Kelas' }}) - NISN: {{ $item->nisn }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    @endif

    <!-- 1. Header Banner Baku SAE -->
    <div class="dash-banner"
        style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; border-radius: 16px; padding: 20px 24px;">
        <div style="display: flex; align-items: center; gap: 18px; flex: 1; min-width: 0;">
            @if ($studentPhoto)
                <div style="flex-shrink: 0; width: 84px; height: 110px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.18);">
                    <img src="{{ $studentPhoto }}" alt="{{ $pd->nama ?? 'Siswa' }}"
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
            @else
                <div style="flex-shrink: 0; width: 68px; height: 68px; border-radius: 16px; background: rgba(16,185,129,0.15); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                    <i class="fas fa-people-roof"></i>
                </div>
            @endif

            <div style="flex: 1; min-width: 0;">
                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 2px;">
                    {{ $greeting }}
                </div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">
                    {{ $parentName }}! 👋
                </div>
                <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin: 0 0 6px 0; line-height: 1.25;">
                    Pemantauan Siswa: <span style="color: #10b981;">{{ $pd->nama ?? 'Peserta Didik' }}</span>
                </h2>
                <div style="display: flex; align-items: center; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap; margin-bottom: 8px;">
                    <span title="Nomor Induk Siswa Nasional">
                        <i class="fas fa-id-card text-primary me-1"></i>
                        NISN: <strong style="color: var(--text-color);">{{ $pd->nisn ?? '-' }}</strong>
                    </span>
                    <span title="Rombongan Belajar / Kelas">
                        <i class="fas fa-door-open text-info me-1"></i>
                        Kelas: <strong style="color: var(--text-color);">{{ $pd->nama_rombel ?? 'Reguler' }}</strong>
                    </span>
                    @if ($waliKelas)
                        <span title="Wali Kelas">
                            <i class="fas fa-chalkboard-user text-warning me-1"></i>
                            Wali Kelas: <strong style="color: var(--text-color);">{{ $waliKelas['nama'] }}</strong>
                        </span>
                    @endif
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #3b82f6;">
                        <i class="fas fa-calendar-alt"></i> TA. {{ \App\Support\SemesterHelper::getActiveSemesterLabel() }}
                    </div>
                    <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3); border-radius: 20px; font-size: 0.74rem; font-weight: 700; color: #10b981;">
                        <i class="fas fa-shield-check"></i> Portal Terverifikasi Sekolah
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Quick Stats Grid Universal (4 Cards) -->
    <div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
        {{-- Card 1: Persentase Kehadiran Bulan Ini --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-circle-check"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #10b981;">{{ $statsHarian['persen'] }}%</div>
                <div class="dash-stat-label">Kehadiran Bulan Ini</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['hadir'] }} Hadir, {{ $statsHarian['izin'] + $statsHarian['sakit'] }} Izin/Sakit, {{ $statsHarian['alpha'] }} Alpha
                </div>
            </div>
        </div>

        {{-- Card 2: Status Kehadiran Hari Ini --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="font-size: 1.15rem; color: #3b82f6;">
                    {{ $statsHarian['jam_masuk_hari_ini'] }}
                </div>
                <div class="dash-stat-label">Presensi Masuk Hari Ini</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsHarian['status_hari_ini'] }} &bull; Pulang: {{ $statsHarian['jam_pulang_hari_ini'] }}
                </div>
            </div>
        </div>

        {{-- Card 3: Presensi Mata Pelajaran (KBM) --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: var(--primary);">{{ $statsMapel['total'] }} Sesi</div>
                <div class="dash-stat-label">Presensi Mapel Kelas</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $statsMapel['hadir'] }} Hadir di Jam Pelajaran
                </div>
            </div>
        </div>

        {{-- Card 4: Izin Keluar & Masuk Sekolah --}}
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" style="color: #f59e0b;">{{ $izinKeluarList->count() + $suratIzinList->count() }} Surat</div>
                <div class="dash-stat-label">Izin Keluar / Sakit</div>
                <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                    {{ $izinKeluarList->count() }} e-Izin Gerbang &bull; {{ $suratIzinList->count() }} Surat Sakit
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Menu Aksi Cepat Portal Orang Tua (5 Menu Berikon) -->
    <div class="kepegawaian-quick-grid" style="margin-bottom: 24px;">
        <button type="button" class="kepegawaian-quick-btn action-tab-trigger active" data-target="#sectionKehadiran" style="--quick-color: #10b981;" title="Kehadiran Terpadu Siswa">
            <div class="kepegawaian-quick-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <span class="kepegawaian-quick-label">Kehadiran Siswa</span>
        </button>

        <button type="button" class="kepegawaian-quick-btn action-tab-trigger" data-target="#sectionJadwal" style="--quick-color: #3b82f6;" title="Jadwal Pelajaran KBM">
            <div class="kepegawaian-quick-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                <i class="fas fa-clock"></i>
            </div>
            <span class="kepegawaian-quick-label">Jadwal Pelajaran</span>
        </button>

        <button type="button" class="kepegawaian-quick-btn action-tab-trigger" data-target="#sectionPengumuman" style="--quick-color: #f59e0b;" title="Pengumuman & Informasi Sekolah">
            <div class="kepegawaian-quick-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                <i class="fas fa-bullhorn"></i>
            </div>
            <span class="kepegawaian-quick-label">Pengumuman</span>
        </button>

        <button type="button" class="kepegawaian-quick-btn action-tab-trigger" data-target="#sectionIdentitas" style="--quick-color: #6366f1;" title="Identitas Lengkap Siswa">
            <div class="kepegawaian-quick-icon" style="background: rgba(99, 102, 241, 0.15); color: #6366f1;">
                <i class="fas fa-id-card-clip"></i>
            </div>
            <span class="kepegawaian-quick-label">Identitas Lengkap</span>
        </button>

        @if (!empty($pd->nisn))
            <button type="button" class="kepegawaian-quick-btn" onclick="openKartuPelajarModal('{{ $pd->nisn }}')" style="--quick-color: #ec4899;" title="Buka Kartu Pelajar Digital">
                <div class="kepegawaian-quick-icon" style="background: rgba(236, 72, 153, 0.15); color: #ec4899;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="kepegawaian-quick-label">Kartu Pelajar</span>
            </button>
        @else
            <button type="button" class="kepegawaian-quick-btn" disabled style="opacity: 0.6; --quick-color: #64748b;" title="NISN belum terdaftar">
                <div class="kepegawaian-quick-icon" style="background: rgba(100, 116, 139, 0.15); color: #64748b;">
                    <i class="fas fa-qrcode"></i>
                </div>
                <span class="kepegawaian-quick-label">Kartu Pelajar</span>
            </button>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 1: KEHADIRAN TERPADU SISWA (DENGAN DROPDOWN SELECTOR)             -->
    <!-- ========================================================================= -->
    <div id="sectionKehadiran" class="action-section-panel">
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-check text-primary"></i> Laporan Kehadiran Terpadu Siswa
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                        Pilih jenis data kehadiran dari dropdown di bawah untuk melihat rincian presensi harian, per mapel, atau izin keluar.
                    </p>
                </div>

                <!-- Dropdown Selector Kategori Kehadiran -->
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <label for="selectKategoriKehadiran" style="font-size: 0.8rem; font-weight: 700; color: var(--text-color); margin: 0;">
                        <i class="fas fa-filter text-primary me-1"></i> Tampilkan:
                    </label>
                    <select id="selectKategoriKehadiran" class="toolbar-filter-select form-control" style="min-width: 250px; font-weight: 700; height: 38px; border-radius: 8px; font-size: 0.84rem;">
                        <option value="subHarian" selected>
                            Presensi Harian (RFID Gerbang) &bull; {{ count($riwayatHarian) }} Catatan
                        </option>
                        <option value="subMapel">
                            Presensi Per Mata Pelajaran &bull; {{ $statsMapel['total'] }} Sesi Kelas
                        </option>
                        <option value="subIzin">
                            e-Izin Keluar-Masuk &amp; Sakit &bull; {{ $izinKeluarList->count() + $suratIzinList->count() }} Dokumen
                        </option>
                    </select>
                </div>
            </div>

            <!-- Panel 1.A: Presensi Harian (RFID Gerbang) -->
            <div id="subPanelHarian" class="kehadiran-sub-panel">
                @if (!empty($riwayatHarian) && count($riwayatHarian) > 0)
                    <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                        <table class="table-minimal-compact" style="width: 100%;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <th style="min-width: 130px;">Hari &amp; Tanggal</th>
                                    <th style="min-width: 95px; text-align: center;">Jam Masuk</th>
                                    <th style="min-width: 95px; text-align: center;">Jam Pulang</th>
                                    <th style="min-width: 100px; text-align: center;">Metode</th>
                                    <th style="min-width: 110px; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayatHarian as $rh)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td data-label="Hari &amp; Tanggal">
                                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                                {{ $rh['tanggal'] }}
                                            </div>
                                        </td>
                                        <td data-label="Jam Masuk" style="text-align: center; font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                            <i class="far fa-clock text-primary me-1"></i> {{ $rh['jam_masuk'] }}
                                        </td>
                                        <td data-label="Jam Pulang" style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.84rem;">
                                            <i class="far fa-clock text-warning me-1"></i> {{ $rh['jam_pulang'] }}
                                        </td>
                                        <td data-label="Metode" style="text-align: center;">
                                            <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                                {{ $rh['metode'] }}
                                            </span>
                                        </td>
                                        <td data-label="Status" style="text-align: center;">
                                            <span class="badge" style="background: {{ $rh['badge_bg'] }}; color: {{ $rh['badge_color'] }}; font-size: 0.74rem; padding: 4px 10px; font-weight: 700; border-radius: 6px;">
                                                {{ $rh['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                        <i class="fas fa-fingerprint" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Belum Ada Catatan Presensi Harian</div>
                        <div style="font-size: 0.76rem; margin-top: 4px;">Catatan tap RFID di gerbang sekolah akan otomatis muncul di sini secara real-time.</div>
                    </div>
                @endif
            </div>

            <!-- Panel 1.B: Presensi per Mata Pelajaran (KBM) -->
            <div id="subPanelMapel" class="kehadiran-sub-panel" style="display: none;">
                @if ($riwayatMapel->isNotEmpty())
                    <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                        <table class="table-minimal-compact" style="width: 100%;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <th style="min-width: 110px;">Tanggal</th>
                                    <th style="min-width: 160px;">Mata Pelajaran</th>
                                    <th style="min-width: 140px;">Guru Pengampu</th>
                                    <th style="width: 80px; text-align: center;">Jam Ke</th>
                                    <th style="width: 100px; text-align: center;">Status</th>
                                    <th style="min-width: 130px;">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($riwayatMapel as $pm)
                                    @php
                                        $badgeStatus = match($pm->status) {
                                            'H' => ['label' => 'Hadir', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                            'T' => ['label' => 'Terlambat', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                                            'I' => ['label' => 'Izin', 'bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                                            'S' => ['label' => 'Sakit', 'bg' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6'],
                                            'D' => ['label' => 'Dispen', 'bg' => 'rgba(6,182,212,0.15)', 'color' => '#06b6d4'],
                                            'A' => ['label' => 'Alpha', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                            default => ['label' => $pm->status, 'bg' => 'rgba(100,116,139,0.15)', 'color' => '#64748b'],
                                        };
                                    @endphp
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td data-label="Tanggal">
                                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.82rem;">
                                                {{ \Carbon\Carbon::parse($pm->tanggal)->translatedFormat('d M Y') }}
                                            </div>
                                        </td>
                                        <td data-label="Mata Pelajaran">
                                            <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                                <i class="fas fa-book text-primary me-1"></i> {{ $pm->nama_mata_pelajaran }}
                                            </div>
                                        </td>
                                        <td data-label="Guru Pengampu">
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                {{ $pm->nama_guru ?: 'Guru Pengampu' }}
                                            </div>
                                        </td>
                                        <td data-label="Jam Ke" style="text-align: center; font-size: 0.82rem; font-weight: 600;">
                                            Jam ke-{{ $pm->jam_ke }}
                                        </td>
                                        <td data-label="Status" style="text-align: center;">
                                            <span class="badge" style="background: {{ $badgeStatus['bg'] }}; color: {{ $badgeStatus['color'] }}; font-size: 0.74rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                                {{ $badgeStatus['label'] }}
                                            </span>
                                        </td>
                                        <td data-label="Keterangan" style="font-size: 0.78rem; color: var(--text-muted);">
                                            {{ $pm->keterangan ?: 'Tercatat di sistem' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                        <i class="fas fa-chalkboard-user" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Belum Ada Riwayat Presensi Mata Pelajaran</div>
                        <div style="font-size: 0.76rem; margin-top: 4px;">Ketika guru mencatat presensi saat KBM dimulai, status kehadiran per mapel akan muncul di sini.</div>
                    </div>
                @endif
            </div>

            <!-- Panel 1.C: e-Izin Keluar-Masuk & Surat Izin Sakit -->
            <div id="subPanelIzin" class="kehadiran-sub-panel" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- e-Izin Keluar Sekolah (Piket & Gerbang) -->
                    <div>
                        <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-ticket-alt text-warning"></i> Tiket e-Izin Keluar Sekolah (Piket &amp; Gerbang)
                        </div>

                        @if ($izinKeluarList->isNotEmpty())
                            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                                <table class="table-minimal-compact" style="width: 100%;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <th style="min-width: 100px;">Tanggal</th>
                                            <th style="min-width: 120px;">Jenis Izin</th>
                                            <th style="min-width: 120px; text-align: center;">Jam Rencana</th>
                                            <th style="min-width: 120px; text-align: center;">Aktual Gerbang</th>
                                            <th style="min-width: 160px;">Alasan &amp; Tujuan</th>
                                            <th style="min-width: 100px; text-align: center;">Status</th>
                                            <th style="min-width: 130px;">Petugas Piket</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($izinKeluarList as $iz)
                                            @php
                                                $jenisLabel = $iz->jenis_izin === 'pulang_cepat' ? 'Pulang Cepat' : 'Keluar Sementara';
                                                $statusBadge = match($iz->status) {
                                                    'disetujui' => ['label' => 'Disetujui', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                                    'selesai'   => ['label' => 'Kembali/Selesai', 'bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                                                    'menunggu'  => ['label' => 'Menunggu Piket', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                                                    'ditolak'   => ['label' => 'Ditolak', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                                    default     => ['label' => ucfirst($iz->status), 'bg' => 'rgba(100,116,139,0.15)', 'color' => '#64748b']
                                                };
                                            @endphp
                                            <tr style="border-bottom: 1px solid var(--border-color);">
                                                <td data-label="Tanggal">
                                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.82rem;">
                                                        {{ \Carbon\Carbon::parse($iz->tanggal)->translatedFormat('d M Y') }}
                                                    </div>
                                                </td>
                                                <td data-label="Jenis Izin">
                                                    <span class="badge" style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                                        {{ $jenisLabel }}
                                                    </span>
                                                </td>
                                                <td data-label="Jam Rencana" style="text-align: center; font-size: 0.8rem;">
                                                    {{ substr($iz->jam_keluar_rencana ?? '', 0, 5) }} - {{ substr($iz->jam_kembali_rencana ?? '', 0, 5) ?: 'Selesai' }}
                                                </td>
                                                <td data-label="Aktual Gerbang" style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                                                    Keluar: {{ $iz->jam_keluar_aktual ? substr($iz->jam_keluar_aktual, 0, 5) : '--:--' }}<br>
                                                    Kembali: {{ $iz->jam_kembali_aktual ? substr($iz->jam_kembali_aktual, 0, 5) : '--:--' }}
                                                </td>
                                                <td data-label="Alasan &amp; Tujuan" style="font-size: 0.8rem;">
                                                    <div style="font-weight: 700; color: var(--text-color);">{{ $iz->alasan }}</div>
                                                    <div style="color: var(--text-muted); font-size: 0.74rem;">Tujuan: {{ $iz->tujuan_lokasi ?: '-' }}</div>
                                                </td>
                                                <td data-label="Status" style="text-align: center;">
                                                    <span class="badge" style="background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['color'] }}; font-size: 0.72rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                                        {{ $statusBadge['label'] }}
                                                    </span>
                                                </td>
                                                <td data-label="Petugas Piket" style="font-size: 0.78rem; color: var(--text-muted);">
                                                    {{ $iz->nama_piket ?: 'Petugas Piket' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div style="padding: 24px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                                <i class="fas fa-ticket" style="font-size: 1.8rem; color: var(--text-muted); margin-bottom: 6px; display: block;"></i>
                                <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">Tidak Ada Riwayat Izin Keluar Sekolah</div>
                                <div style="font-size: 0.74rem; margin-top: 3px;">Putra/putri Anda tertib berada di lingkungan sekolah selama jam pembelajaran.</div>
                            </div>
                        @endif
                    </div>

                    <!-- Surat Izin Sakit / Tidak Masuk Sekolah -->
                    <div style="border-top: 1px solid var(--border-color); padding-top: 16px;">
                        <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-envelope-open-text text-primary"></i> Surat Permohonan Izin / Sakit Mandiri
                        </div>

                        @if ($suratIzinList->isNotEmpty())
                            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                                <table class="table-minimal-compact" style="width: 100%;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <th style="min-width: 140px;">Rentang Tanggal</th>
                                            <th style="min-width: 90px; text-align: center;">Jenis</th>
                                            <th style="min-width: 200px;">Alasan / Keterangan</th>
                                            <th style="min-width: 110px; text-align: center;">Status Verifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suratIzinList as $si)
                                            @php
                                                $jenisRaw = strtolower($si->jenis ?? ($si->jenis_izin ?? 'izin'));
                                                $jenisIzinLabel = str_contains($jenisRaw, 's') ? 'Sakit' : 'Izin';
                                                $statusVal = strtolower($si->status ?? ($si->status_persetujuan ?? 'menunggu'));
                                                $badgeIzin = match($statusVal) {
                                                    'disetujui' => ['label' => 'Disetujui', 'bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                                    'ditolak'   => ['label' => 'Ditolak', 'bg' => 'rgba(239,68,68,0.15)', 'color' => '#ef4444'],
                                                    default     => ['label' => 'Menunggu Wali Kelas', 'bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                                                };
                                                $alasanText = $si->alasan ?? ($si->keterangan ?? '-');
                                            @endphp
                                            <tr style="border-bottom: 1px solid var(--border-color);">
                                                <td data-label="Rentang Tanggal">
                                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.82rem;">
                                                        {{ \Carbon\Carbon::parse($si->tanggal_mulai)->translatedFormat('d M Y') }}
                                                        @if (!empty($si->tanggal_selesai) && $si->tanggal_mulai !== $si->tanggal_selesai)
                                                            &bull; {{ \Carbon\Carbon::parse($si->tanggal_selesai)->translatedFormat('d M Y') }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td data-label="Jenis" style="text-align: center;">
                                                    <span class="badge" style="background: {{ $jenisIzinLabel === 'Sakit' ? 'rgba(139,92,246,0.15)' : 'rgba(59,130,246,0.15)' }}; color: {{ $jenisIzinLabel === 'Sakit' ? '#8b5cf6' : '#3b82f6' }}; font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                                        {{ $jenisIzinLabel }}
                                                    </span>
                                                </td>
                                                <td data-label="Alasan / Keterangan" style="font-size: 0.82rem; color: var(--text-color);">
                                                    {{ $alasanText }}
                                                </td>
                                                <td data-label="Status Verifikasi" style="text-align: center;">
                                                    <span class="badge" style="background: {{ $badgeIzin['bg'] }}; color: {{ $badgeIzin['color'] }}; font-size: 0.72rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                                        {{ $badgeIzin['label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div style="padding: 24px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                                <i class="fas fa-file-circle-check" style="font-size: 1.8rem; color: var(--text-muted); margin-bottom: 6px; display: block;"></i>
                                <div style="font-weight: 700; font-size: 0.86rem; color: var(--text-color);">Belum Ada Surat Permohonan Izin</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 2: JADWAL PELAJARAN                                                -->
    <!-- ========================================================================= -->
    <div id="sectionJadwal" class="action-section-panel" style="display: none;">
        <!-- Jadwal Pelajaran Hari Ini -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-calendar-day text-primary"></i> Jadwal Pelajaran Hari Ini ({{ $hariIni }}, {{ now()->translatedFormat('d F Y') }})
                    </h3>
                    <p style="font-size: 0.76rem; color: var(--text-muted); margin: 3px 0 0 0;">
                        Agenda jam pelajaran dan guru pengampu kelas {{ $pd->nama_rombel ?? 'Siswa' }} pada hari ini.
                    </p>
                </div>
                <span class="badge badge-info" style="font-size: 0.72rem; padding: 4px 10px;">
                    Kelas {{ $pd->nama_rombel ?? 'Siswa' }}
                </span>
            </div>

            @if (!empty($jadwalHariIni) && count($jadwalHariIni) > 0)
                <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                    <table class="table-minimal-compact" style="width: 100%;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="min-width: 120px;">Waktu KBM</th>
                                <th style="min-width: 180px;">Mata Pelajaran</th>
                                <th style="min-width: 160px;">Guru Pengampu</th>
                                <th style="min-width: 110px;">Ruangan</th>
                                <th style="min-width: 90px; text-align: center;">Status Live</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jadwalHariIni as $jh)
                                @php
                                    $statusColor = match($jh['status']) {
                                        'Berlangsung' => ['bg' => 'rgba(16,185,129,0.15)', 'color' => '#10b981'],
                                        'Selesai'     => ['bg' => 'rgba(100,116,139,0.15)', 'color' => '#64748b'],
                                        default       => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="Waktu KBM">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                            <i class="far fa-clock text-primary me-1"></i> {{ $jh['jam'] }}
                                        </div>
                                    </td>
                                    <td data-label="Mata Pelajaran">
                                        <div style="font-weight: 700; color: var(--text-color); font-size: 0.86rem;">
                                            {{ $jh['mapel'] }}
                                        </div>
                                    </td>
                                    <td data-label="Guru Pengampu" style="font-size: 0.82rem; color: var(--text-muted);">
                                        <i class="fas fa-chalkboard-user me-1 text-info"></i> {{ $jh['guru'] }}
                                    </td>
                                    <td data-label="Ruangan" style="font-size: 0.82rem; color: var(--text-muted);">
                                        <i class="fas fa-door-open me-1 text-warning"></i> {{ $jh['ruang'] }}
                                    </td>
                                    <td data-label="Status Live" style="text-align: center;">
                                        <span class="badge" style="background: {{ $statusColor['bg'] }}; color: {{ $statusColor['color'] }}; font-size: 0.72rem; padding: 4px 8px; font-weight: 700; border-radius: 6px;">
                                            {{ $jh['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                    <i class="fas fa-calendar-xmark" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Tidak Ada Jadwal KBM Hari Ini</div>
                    <div style="font-size: 0.76rem; margin-top: 4px;">Hari ini merupakan hari libur atau belum ada jadwal KBM aktif untuk kelas ini.</div>
                </div>
            @endif
        </div>

        <!-- Jadwal Mingguan Lengkap -->
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-week text-primary"></i> Jadwal Pelajaran Mingguan Lengkap
                </h3>
                <p style="font-size: 0.76rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Pilih hari untuk melihat susunan mata pelajaran dan guru pengampu sepanjang pekan.
                </p>
            </div>

            <!-- Hari Switcher -->
            <div style="display: flex; gap: 6px; margin-bottom: 16px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px;">
                @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hName)
                    @php $isTodayBtn = ($hName === $hariIni); @endphp
                    <button type="button" class="btn-day-pill {{ $isTodayBtn ? 'active' : '' }}" data-day="{{ $hName }}"
                        style="padding: 7px 14px; font-size: 0.78rem; font-weight: {{ $isTodayBtn ? '700' : '600' }}; border: 1px solid var(--border-color); border-radius: 8px; background: {{ $isTodayBtn ? 'var(--primary)' : 'var(--bg-hover)' }}; color: {{ $isTodayBtn ? '#fff' : 'var(--text-color)' }}; cursor: pointer; white-space: nowrap;">
                        {{ $hName }} @if($isTodayBtn) &bull; Hari Ini @endif
                    </button>
                @endforeach
            </div>

            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hName)
                @php
                    $hList = $jadwalMingguan[$hName] ?? [];
                    $isDisplay = ($hName === $hariIni);
                @endphp
                <div id="daySchedule_{{ $hName }}" class="day-schedule-panel" style="{{ $isDisplay ? 'display: block;' : 'display: none;' }}">
                    @if (!empty($hList))
                        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                            <table class="table-minimal-compact" style="width: 100%;">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <th style="min-width: 120px;">Waktu</th>
                                        <th style="min-width: 180px;">Mata Pelajaran</th>
                                        <th style="min-width: 160px;">Guru Pengampu</th>
                                        <th style="min-width: 110px;">Ruang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($hList as $item)
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <td data-label="Waktu" style="font-weight: 700; font-size: 0.82rem; color: var(--text-color);">
                                                <i class="far fa-clock text-primary me-1"></i> {{ $item['jam'] }}
                                            </td>
                                            <td data-label="Mata Pelajaran" style="font-weight: 700; font-size: 0.84rem; color: var(--text-color);">
                                                {{ $item['mapel'] }}
                                            </td>
                                            <td data-label="Guru Pengampu" style="font-size: 0.8rem; color: var(--text-muted);">
                                                {{ $item['guru'] }}
                                            </td>
                                            <td data-label="Ruang" style="font-size: 0.8rem; color: var(--text-muted);">
                                                {{ $item['ruang'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div style="padding: 24px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 8px;">
                            Tidak ada jadwal KBM pada hari {{ $hName }}.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 3: PENGUMUMAN & INFORMASI SEKOLAH                                  -->
    <!-- ========================================================================= -->
    <div id="sectionPengumuman" class="action-section-panel" style="display: none;">
        <div class="card" style="padding: 20px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="margin-bottom: 16px;">
                <h3 style="font-size: 0.98rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-bullhorn text-warning"></i> Pengumuman &amp; Informasi Kedinasan Sekolah
                </h3>
                <p style="font-size: 0.76rem; color: var(--text-muted); margin: 3px 0 0 0;">
                    Informasi resmi dari pihak manajemen sekolah dan wali kelas untuk orang tua / wali murid.
                </p>
            </div>

            @if ($pengumumanList->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    @foreach ($pengumumanList as $peng)
                        <div class="card" style="padding: 16px 18px; border-radius: 12px; border: 1px solid var(--border-color); background: var(--bg-hover);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; margin-bottom: 6px;">
                                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-circle-dot text-primary" style="font-size: 0.5rem;"></i>
                                    {{ $peng->judul }}
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    <i class="far fa-calendar-alt me-1"></i>{{ $peng->created_at ? $peng->created_at->translatedFormat('d F Y, H:i') : '-' }} WIB
                                </div>
                            </div>
                            <div style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px;">
                                {{ Str::limit(strip_tags($peng->isi), 180) }}
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.74rem;">
                                <span style="color: var(--text-muted);">
                                    Diterbitkan oleh: <strong style="color: var(--text-color);">{{ $peng->penulis_nama ?: 'Administrator' }}</strong>
                                </span>
                                <button type="button" class="btn btn-outline btn-sm btn-baca-pengumuman"
                                    data-judul="{{ $peng->judul }}"
                                    data-isi="{{ $peng->isi }}"
                                    data-penulis="{{ $peng->penulis_nama ?: 'Pihak Sekolah' }}"
                                    data-tanggal="{{ $peng->created_at ? $peng->created_at->translatedFormat('d F Y, H:i') . ' WIB' : '-' }}"
                                    style="padding: 4px 10px; font-size: 0.74rem; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-eye me-1"></i> Baca Lengkap
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
                    <i class="fas fa-bullhorn" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
                    <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color);">Belum Ada Pengumuman Baru</div>
                    <div style="font-size: 0.76rem; margin-top: 4px;">Pengumuman resmi dari pihak sekolah akan otomatis muncul di sini.</div>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 4: FORMULIR IDENTITAS LENGKAP SISWA (POINT 3)                     -->
    <!-- ========================================================================= -->
    <div id="sectionIdentitas" class="action-section-panel" style="display: none;">
        <div class="card" style="padding: 24px; border-radius: 16px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-id-card-clip text-primary"></i> Formulir Identitas Lengkap Peserta Didik
                    </h3>
                    <p style="font-size: 0.78rem; color: var(--text-muted); margin: 3px 0 0 0;">
                        Data pokok siswa dan orang tua/wali murid bersumber resmi dari basis data Dapodik sekolah.
                    </p>
                </div>
                <span class="badge badge-success" style="font-size: 0.74rem; padding: 4px 10px; font-weight: 700;">
                    <i class="fas fa-check-circle me-1"></i> Data Terverifikasi Dapodik
                </span>
            </div>

            @if ($pd)
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
                    <!-- Bagian A: Data Pribadi Siswa -->
                    <div style="background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 700; font-size: 0.9rem; color: var(--primary); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-user"></i> A. Data Pribadi Peserta Didik
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Nama Lengkap:</span>
                                <strong style="color: var(--text-color);">{{ $pd->nama }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">NISN:</span>
                                <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nisn ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">NIK:</span>
                                <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nik ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">NIPD:</span>
                                <strong style="color: var(--text-color); font-family: monospace;">{{ $pd->nipd ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tempat, Tanggal Lahir:</span>
                                <strong style="color: var(--text-color);">{{ $pd->tempat_lahir ?: '-' }}, {{ !empty($pd->tanggal_lahir) ? \Carbon\Carbon::parse($pd->tanggal_lahir)->translatedFormat('d F Y') : '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Jenis Kelamin:</span>
                                <strong style="color: var(--text-color);">{{ ($pd->jenis_kelamin === 'L') ? 'Laki-laki (L)' : 'Perempuan (P)' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Agama:</span>
                                <strong style="color: var(--text-color);">{{ $pd->agama_id_str ?: 'Islam' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Anak Ke-:</span>
                                <strong style="color: var(--text-color);">{{ $pd->anak_keberapa ?: '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Tinggi / Berat Badan:</span>
                                <strong style="color: var(--text-color);">{{ $pd->tinggi_badan ? $pd->tinggi_badan . ' cm' : '-' }} / {{ $pd->berat_badan ? $pd->berat_badan . ' kg' : '-' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Kebutuhan Khusus:</span>
                                <strong style="color: var(--text-color);">{{ $pd->kebutuhan_khusus ?: 'Tidak Ada' }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">No. Telepon / HP:</span>
                                <strong style="color: var(--text-color);">{{ $pd->nomor_telepon_seluler ?: ($pd->nomor_telepon_rumah ?: '-') }}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-muted);">Email Siswa:</span>
                                <strong style="color: var(--text-color);">{{ $pd->email ?: '-' }}</strong>
                            </div>
                            <div style="border-top: 1px solid var(--border-color); padding-top: 8px; margin-top: 4px;">
                                <div style="color: var(--text-muted); margin-bottom: 2px;">Alamat Tempat Tinggal:</div>
                                <div style="color: var(--text-color); font-weight: 600; line-height: 1.4;">{{ $pd->alamat_jalan ?: 'Data alamat terdaftar di sistem Dapodik sekolah' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian B: Data Akademik & Orang Tua -->
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <!-- Akademik -->
                        <div style="background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                            <div style="font-weight: 700; font-size: 0.9rem; color: #10b981; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-graduation-cap"></i> B. Data Akademik &amp; Rombel
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Rombongan Belajar (Kelas):</span>
                                    <strong style="color: #3b82f6;">{{ $pd->nama_rombel ?: '-' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Tingkat Pendidikan:</span>
                                    <strong style="color: var(--text-color);">Kelas {{ $pd->tingkat_pendidikan_id ?: '-' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Kurikulum:</span>
                                    <strong style="color: var(--text-color);">{{ $pd->kurikulum_id_str ?: 'Kurikulum Merdeka' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Sekolah Asal:</span>
                                    <strong style="color: var(--text-color);">{{ $pd->sekolah_asal ?: '-' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Tanggal Masuk Sekolah:</span>
                                    <strong style="color: var(--text-color);">{{ !empty($pd->tanggal_masuk_sekolah) ? \Carbon\Carbon::parse($pd->tanggal_masuk_sekolah)->translatedFormat('d F Y') : '-' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Wali Kelas:</span>
                                    <strong style="color: #10b981;">{{ $waliKelas['nama'] ?? 'Guru Wali Kelas' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Data Orang Tua / Wali -->
                        <div style="background: var(--bg-hover); padding: 18px; border-radius: 12px; border: 1px solid var(--border-color);">
                            <div style="font-weight: 700; font-size: 0.9rem; color: #f59e0b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-people-roof"></i> C. Data Orang Tua / Wali
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 10px; font-size: 0.82rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Nama Ayah Kandung:</span>
                                    <strong style="color: var(--text-color);">
                                        {{ $pd->nama_ayah ?: '-' }}
                                        @if (str_contains(strtolower($pd->pekerjaan_ayah_id_str ?? ''), 'meninggal'))
                                            <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px; margin-left: 4px;">Alm.</span>
                                        @endif
                                    </strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Pekerjaan Ayah:</span>
                                    <strong style="color: var(--text-color);">{{ $pd->pekerjaan_ayah_id_str ?: '-' }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Nama Ibu Kandung:</span>
                                    <strong style="color: var(--text-color);">
                                        {{ $pd->nama_ibu ?: '-' }}
                                        @if (str_contains(strtolower($pd->pekerjaan_ibu_id_str ?? ''), 'meninggal'))
                                            <span class="badge badge-danger" style="font-size: 0.65rem; padding: 2px 6px; margin-left: 4px;">Almh.</span>
                                        @endif
                                    </strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: var(--text-muted);">Pekerjaan Ibu:</span>
                                    <strong style="color: var(--text-color);">{{ $pd->pekerjaan_ibu_id_str ?: '-' }}</strong>
                                </div>
                                @if (!empty($pd->nama_wali))
                                    <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 8px;">
                                        <span style="color: var(--text-muted);">Nama Wali Murid:</span>
                                        <strong style="color: var(--text-color);">{{ $pd->nama_wali }} ({{ $pd->pekerjaan_wali_id_str ?: 'Wali' }})</strong>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div style="padding: 32px 16px; text-align: center; color: var(--text-muted);">
                    Data identitas peserta didik belum dimuat.
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Detail Pengumuman -->
    <div id="modalDetailPengumuman" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; box-sizing: border-box;">
        <div class="card modal-card-responsive" style="max-width: 580px; width: 94%; max-height: 85vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 20px; background: var(--card-bg); border: 1px solid var(--border-color); overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); flex-shrink: 0;">
                <h3 id="modalPengumumanTitle" style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin: 0;">
                    Detail Pengumuman
                </h3>
                <button type="button" id="btnClosePengumumanModal" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; min-height: 0; padding-right: 4px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.76rem; color: var(--text-muted); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
                    <span id="modalPengumumanPenulis"></span>
                    <span id="modalPengumumanTanggal"></span>
                </div>
                <div id="modalPengumumanBody" style="font-size: 0.86rem; color: var(--text-color); line-height: 1.6; white-space: pre-line;"></div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border-color); flex-shrink: 0;">
                <button type="button" class="btn btn-outline" id="btnTutupModalPengumuman" style="padding: 7px 18px; font-size: 0.82rem; border-radius: 8px;">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Pratinjau Kartu Pelajar Digital (Layar Penuh, Bisa Digeser) -->
    @include('kartu-pelajar.modal-fullscreen')

    <!-- Script Tab Navigasi & Interaksi Modal -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Quick Action Grid Tab Trigger (5 Menu Aksi Cepat)
            const actionTriggers = document.querySelectorAll('.action-tab-trigger');
            const sectionPanels = document.querySelectorAll('.action-section-panel');

            actionTriggers.forEach(btn => {
                btn.addEventListener('click', function () {
                    const targetSelector = this.getAttribute('data-target');
                    if (!targetSelector) return;

                    actionTriggers.forEach(b => {
                        b.classList.remove('active');
                        b.style.borderColor = 'var(--border-color)';
                        b.style.boxShadow = '0 2px 6px rgba(0, 0, 0, 0.08)';
                    });

                    this.classList.add('active');
                    this.style.borderColor = 'var(--quick-color, var(--primary))';
                    this.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.15)';

                    sectionPanels.forEach(panel => panel.style.display = 'none');
                    const targetEl = document.querySelector(targetSelector);
                    if (targetEl) targetEl.style.display = 'block';

                    // Smooth scroll to section on mobile
                    if (window.innerWidth <= 768) {
                        targetEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            // 2. Dropdown Selector Kategori Kehadiran (Presensi Harian vs Mapel vs e-Izin)
            const selectKehadiran = document.getElementById('selectKategoriKehadiran');
            const subPanels = {
                'subHarian': document.getElementById('subPanelHarian'),
                'subMapel': document.getElementById('subPanelMapel'),
                'subIzin': document.getElementById('subPanelIzin'),
            };

            if (selectKehadiran) {
                selectKehadiran.addEventListener('change', function () {
                    const val = this.value;
                    Object.keys(subPanels).forEach(key => {
                        if (subPanels[key]) {
                            subPanels[key].style.display = (key === val) ? 'block' : 'none';
                        }
                    });
                });
            }

            // 3. Day Pills Jadwal Switcher
            const dayPills = document.querySelectorAll('.btn-day-pill');
            const dayPanels = document.querySelectorAll('.day-schedule-panel');

            dayPills.forEach(pill => {
                pill.addEventListener('click', function () {
                    const selectedDay = this.getAttribute('data-day');
                    dayPills.forEach(p => {
                        p.classList.remove('active');
                        p.style.background = 'var(--bg-hover)';
                        p.style.color = 'var(--text-color)';
                        p.style.fontWeight = '600';
                    });
                    this.classList.add('active');
                    this.style.background = 'var(--primary)';
                    this.style.color = '#fff';
                    this.style.fontWeight = '700';

                    dayPanels.forEach(dp => dp.style.display = 'none');
                    const targetDayPanel = document.getElementById('daySchedule_' + selectedDay);
                    if (targetDayPanel) targetDayPanel.style.display = 'block';
                });
            });

            // 4. Modal Detail Pengumuman
            const modalPengumuman = document.getElementById('modalDetailPengumuman');
            const modalTitle = document.getElementById('modalPengumumanTitle');
            const modalPenulis = document.getElementById('modalPengumumanPenulis');
            const modalTanggal = document.getElementById('modalPengumumanTanggal');
            const modalBody = document.getElementById('modalPengumumanBody');
            const btnCloseModal = document.getElementById('btnClosePengumumanModal');
            const btnTutupModal = document.getElementById('btnTutupModalPengumuman');

            document.querySelectorAll('.btn-baca-pengumuman').forEach(btn => {
                btn.addEventListener('click', function () {
                    if (modalTitle) modalTitle.textContent = this.getAttribute('data-judul') || '';
                    if (modalPenulis) modalPenulis.textContent = 'Diterbitkan oleh: ' + (this.getAttribute('data-penulis') || '');
                    if (modalTanggal) modalTanggal.textContent = this.getAttribute('data-tanggal') || '';
                    if (modalBody) modalBody.textContent = this.getAttribute('data-isi') || '';
                    if (modalPengumuman) modalPengumuman.style.display = 'flex';
                });
            });

            function closeModal() {
                if (modalPengumuman) modalPengumuman.style.display = 'none';
            }

            if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
            if (btnTutupModal) btnTutupModal.addEventListener('click', closeModal);
            if (modalPengumuman) {
                modalPengumuman.addEventListener('click', function (e) {
                    if (e.target === modalPengumuman) closeModal();
                });
            }
        });
    </script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/kartu-pelajar.css') }}?v={{ file_exists(public_path('css/kartu-pelajar.css')) ? filemtime(public_path('css/kartu-pelajar.css')) : '1' }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/peserta-didik.js') }}?v={{ file_exists(public_path('js/peserta-didik.js')) ? filemtime(public_path('js/peserta-didik.js')) : '1' }}"></script>
@endpush
