{{-- Section Dashboard: Guru Piket (Presensi Guru, Jurnal Agenda KBM, dan e-Izin Keluar Masuk Siswa) --}}

<!-- Quick Stats Grid Guru Piket -->
<div class="dash-stat-grid"
    style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); margin-bottom: 24px; gap: 16px;">
    {{-- 1. Presensi Guru --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['guru_hadir'] ?? 36 }} / {{ $piketStats['total_guru'] ?? 48 }}</div>
            <div class="dash-stat-label">Guru Hadir Mengajar Hari Ini</div>
        </div>
    </div>

    {{-- 2. Jurnal Agenda Guru --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-open-reader"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['jurnal_terisi'] ?? 28 }} Jurnal</div>
            <div class="dash-stat-label">Agenda KBM Terisi Hari Ini</div>
        </div>
    </div>

    {{-- 3. e-Izin Keluar Masuk Siswa --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['izin_hari_ini'] ?? 5 }} Siswa</div>
            <div class="dash-stat-label">e-Izin Keluar / Masuk Siswa</div>
        </div>
    </div>

    {{-- 4. Status Piket --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: 1.05rem;">Aktif Bertugas</div>
            <div class="dash-stat-label">Piket: {{ \Carbon\Carbon::now()->translatedFormat('l, d M Y') }}</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Guru Piket (2 Kolom) -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px;" class="dash-main-grid">
    <!-- Left Column: Monitoring e-Izin Siswa & Jurnal KBM -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        {{-- Card 1: Monitoring e-Izin Keluar Masuk Siswa Hari Ini --}}
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color);">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(245, 158, 11, 0.03);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-person-walking-arrow-right text-warning me-2"></i> Pemantauan e-Izin Keluar &amp; Masuk Siswa
                </div>
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="btn btn-sm btn-primary" title="Input Izin Baru"
                    style="font-size: 0.8rem; padding: 4px 10px; border-radius: 6px; background: #f59e0b; border: none;">
                    <i class="fas fa-plus"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Siswa &amp; Rombel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Jenis Perizinan</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Keperluan / Alasan</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentIzinSiswa ?? [] as $izin)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>{{ $izin->siswa_nama ?? 'Peserta Didik' }}</div>
                                    <small class="text-muted" style="font-weight: 400;">{{ $izin->rombel_nama ?? 'Rombel' }} • NISN: {{ $izin->nisn ?? '-' }}</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.12); color: #d97706; font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        {{ strtoupper($izin->jenis ?? 'IZIN') }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    {{ \Illuminate\Support\Str::limit($izin->alasan ?? 'Izin keperluan keluarga / dinas sekolah', 45) }}
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    @if (($izin->status ?? 'disetujui') === 'disetujui')
                                        <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                            <i class="fas fa-check-circle me-1"></i> Disetujui
                                        </span>
                                    @else
                                        <span class="badge badge-warning" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                            <i class="fas fa-clock me-1"></i> Menunggu
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- Fallback Demo Data Izin Siswa --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>Muhammad Rizky Pratama</div>
                                    <small class="text-muted" style="font-weight: 400;">X TBSM 1 • NISN: 0071284920</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.12); color: #d97706; font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        IZIN KELUAR
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    Dispensasi Lomba Futsal Antar Pelajar Dinas
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-check-circle me-1"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>Siti Aisyah Rahmawati</div>
                                    <small class="text-muted" style="font-weight: 400;">XI RPL 2 • NISN: 0068940122</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.12); color: #dc2626; font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        SAKIT DI UKS
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    Pusing dan demam, dirujuk istirahat ke UKS
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-check-circle me-1"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>Dimas Anggara</div>
                                    <small class="text-muted" style="font-weight: 400;">XII TKJ 1 • NISN: 0057392019</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem;">
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.12); color: #2563eb; font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        MASUK TERLAMBAT
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    Ban motor kempes di perjalanan menuju sekolah
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-warning" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-clock me-1"></i> Verifikasi Piket
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card 2: Pemantauan Jurnal & Agenda KBM Guru Hari Ini --}}
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color);">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(59, 130, 246, 0.03);">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-book-bookmark text-primary me-2"></i> Keterisian Jurnal &amp; Agenda KBM Kelas Hari Ini
                </div>
                <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-sm btn-outline-primary" title="Lihat Semua Agenda KBM"
                    style="font-size: 0.8rem; padding: 4px 10px; border-radius: 6px;">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255,255,255,0.01);">
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Guru &amp; Mapel</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Kelas / Jam</th>
                            <th style="padding: 10px 14px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted);">Uraian Materi Pembelajaran</th>
                            <th style="padding: 10px 16px; font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); text-align: center;">Status KBM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAgendaKbm ?? [] as $agenda)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>{{ $agenda->guru_nama ?? 'Guru Pengampu' }}</div>
                                    <small class="text-muted" style="font-weight: 400;">{{ $agenda->nama_mata_pelajaran ?? 'Mata Pelajaran' }}</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">{{ $agenda->rombel_nama ?? 'Kelas' }}</div>
                                    <small class="text-muted">Jam ke-{{ $agenda->jam_ke_mulai ?? '1' }} s/d {{ $agenda->jam_ke_selesai ?? '2' }}</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    {{ \Illuminate\Support\Str::limit($agenda->materi_pokok ?? ($agenda->uraian_kegiatan ?? 'Pelaksanaan materi pembelajaran teori dan praktikum'), 45) }}
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-check-double me-1"></i> Terisi
                                    </span>
                                </td>
                            </tr>
                        @empty
                            {{-- Fallback Demo Data Agenda KBM --}}
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>Drs. H. Mulyadi, M.Pd</div>
                                    <small class="text-muted" style="font-weight: 400;">Matematika Terapan</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">XII TKJ 1</div>
                                    <small class="text-muted">Jam ke-1 s/d 3 (07:30 - 09:45)</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    Perhitungan matriks invers dan determinan pada sistem jaringan
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-check-double me-1"></i> Terisi
                                    </span>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 16px; font-size: 0.82rem; font-weight: 600; color: var(--text-color);">
                                    <div>Ratna Dewi, S.Kom</div>
                                    <small class="text-muted" style="font-weight: 400;">Pemrograman Web &amp; Mobile</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    <div style="font-weight: 600;">XI RPL 1</div>
                                    <small class="text-muted">Jam ke-2 s/d 4 (08:15 - 10:30)</small>
                                </td>
                                <td style="padding: 12px 14px; font-size: 0.8rem; color: var(--text-color);">
                                    Implementasi CRUD Controller dan REST API Endpoint Laravel
                                </td>
                                <td style="padding: 12px 16px; font-size: 0.8rem; text-align: center;">
                                    <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px;">
                                        <i class="fas fa-check-double me-1"></i> Terisi
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Presensi Guru & Quick Links Piket -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        {{-- Card 3: Presensi Kedatangan & Mengajar Guru Hari Ini --}}
        <div class="card" style="padding: 18px 20px; border-radius: 14px; border: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color);">
                    <i class="fas fa-calendar-check text-success me-2"></i> Presensi Guru Hari Ini
                </div>
                <span class="badge badge-success" style="font-size: 0.72rem; padding: 3px 8px;">
                    Aktif
                </span>
            </div>

            @php
                $totG = $piketStats['total_guru'] ?: 48;
                $hadG = $piketStats['guru_hadir'] ?: 36;
                $pctG = round(($hadG / $totG) * 100);
            @endphp
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 5px;">
                    <span class="text-muted">Tingkat Kehadiran Guru</span>
                    <strong style="color: #10b981;">{{ $pctG }}% ({{ $hadG }}/{{ $totG }})</strong>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ $pctG }}%; height: 100%; background: #10b981; border-radius: 4px;"></div>
                </div>
            </div>

            {{-- List Presensi Guru Terkini --}}
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @forelse ($recentPresensiGuru ?? [] as $pm)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                        <div>
                            <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">{{ $pm->guru_nama ?? 'Guru' }}</div>
                            <small class="text-muted">{{ $pm->nama_mata_pelajaran ?? 'KBM' }} • {{ $pm->rombel_nama ?? 'Kelas' }}</small>
                        </div>
                        <span class="badge badge-success" style="font-size: 0.7rem; padding: 2px 6px;">
                            {{ $pm->status ?? 'Mengajar' }}
                        </span>
                    </div>
                @empty
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                        <div>
                            <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Bambang Sugiarto, S.Pd</div>
                            <small class="text-muted">Bahasa Indonesia • X AKL 1</small>
                        </div>
                        <span class="badge badge-success" style="font-size: 0.7rem; padding: 2px 6px;">Mengajar</span>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color);">
                        <div>
                            <div style="font-size: 0.82rem; font-weight: 600; color: var(--text-color);">Nurlela Sari, S.Pd</div>
                            <small class="text-muted">Bimbingan Konseling • Ruang BK</small>
                        </div>
                        <span class="badge badge-info" style="font-size: 0.7rem; padding: 2px 6px;">Hadir Piket</span>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-sm btn-outline-success w-100" style="border-radius: 8px; font-weight: 600;">
                    <i class="fas fa-clipboard-check me-1"></i> Buka Presensi Guru Mengajar
                </a>
            </div>
        </div>

        {{-- Card 4: Pintasan & Akses Cepat Guru Piket --}}
        <div class="card" style="padding: 18px 20px; border-radius: 14px; border: 1px solid var(--border-color);">
            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-color); margin-bottom: 12px;">
                <i class="fas fa-bolt text-warning me-2"></i> Akses Cepat Guru Piket
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; background: rgba(245, 158, 11, 0.08); color: #d97706; text-decoration: none; font-size: 0.84rem; font-weight: 600;">
                    <span><i class="fas fa-person-walking-arrow-right me-2"></i> e-Izin Keluar Masuk Siswa</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('dashboard.presensi-mengajar.index') }}"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; background: rgba(16, 185, 129, 0.08); color: #059669; text-decoration: none; font-size: 0.84rem; font-weight: 600;">
                    <span><i class="fas fa-calendar-check me-2"></i> Presensi Guru Mengajar</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('dashboard.agenda-kbm.index') }}"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; background: rgba(59, 130, 246, 0.08); color: #2563eb; text-decoration: none; font-size: 0.84rem; font-weight: 600;">
                    <span><i class="fas fa-book-open-reader me-2"></i> Jurnal &amp; Agenda KBM</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('dashboard.tendik.aktivitas.index') }}"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; background: rgba(139, 92, 246, 0.08); color: #7c3aed; text-decoration: none; font-size: 0.84rem; font-weight: 600;">
                    <span><i class="fas fa-clipboard-check me-2"></i> Catat Jurnal Piket Harian</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>

                <a href="{{ route('dashboard.tendik.laporan.index') }}"
                    style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 8px; background: rgba(99, 102, 241, 0.08); color: #4f46e5; text-decoration: none; font-size: 0.84rem; font-weight: 600;">
                    <span><i class="fas fa-file-lines me-2"></i> Laporan Kinerja &amp; Piket</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.75rem;"></i>
                </a>
            </div>
        </div>
    </div>
</div>
