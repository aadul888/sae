{{-- Section Dashboard: Guru Piket (Presensi Guru, Jurnal Agenda KBM, dan e-Izin Keluar Masuk Siswa) --}}

<!-- Quick Stats Grid Guru Piket (Responsive) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    {{-- 1. Presensi Guru --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['guru_hadir'] ?? 36 }}/{{ $piketStats['total_guru'] ?? 48 }}</div>
            <div class="dash-stat-label">Guru Hadir Hari Ini</div>
        </div>
    </div>

    {{-- 2. Jurnal Agenda Guru --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-book-open-reader"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['jurnal_terisi'] ?? 28 }} Jurnal</div>
            <div class="dash-stat-label">Agenda KBM Terisi</div>
        </div>
    </div>

    {{-- 3. e-Izin Keluar Masuk Siswa --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-person-walking-arrow-right"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">{{ $piketStats['izin_hari_ini'] ?? 5 }} Siswa</div>
            <div class="dash-stat-label">e-Izin Gerbang Hari Ini</div>
        </div>
    </div>

    {{-- 4. Status Piket --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value">Aktif</div>
            <div class="dash-stat-label">Shift Hari Ini</div>
        </div>
    </div>
</div>

<!-- Main Content Grid Guru Piket (Responsive: 2fr 1fr desktop, 1fr mobile) -->
<div class="dash-layout-grid">
    <!-- Left Column: Monitoring e-Izin Siswa & Jurnal KBM -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        {{-- Card 1: Monitoring e-Izin Keluar Masuk Siswa Hari Ini --}}
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(245, 158, 11, 0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-person-walking-arrow-right text-warning"></i> Pemantauan e-Izin Siswa
                </div>
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Input Izin Baru">
                    <i class="fas fa-plus" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 130px;">Siswa &amp; Rombel</th>
                            <th style="min-width: 90px; text-align: center;">Jenis</th>
                            <th style="min-width: 140px;">Alasan</th>
                            <th style="min-width: 80px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentIzinSiswa ?? [] as $izin)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $izin->siswa_nama ?? 'Peserta Didik' }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        {{ $izin->rombel_nama ?? 'Rombel' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                                        {{ strtoupper($izin->jenis ?? 'IZIN') }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $izin->alasan ?? '-' }}">
                                        {{ $izin->alasan ?? '-' }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    @if (($izin->status ?? 'disetujui') === 'disetujui')
                                        <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;" title="Disetujui">
                                            <i class="fas fa-check"></i> Disetujui
                                        </span>
                                    @else
                                        <span class="badge-compact" style="background: rgba(245,158,11,0.12); color: #f59e0b;" title="Menunggu">
                                            <i class="fas fa-clock"></i> Tunggu
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            {{-- Fallback Data --}}
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">M. Rizky Pratama</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">X TBSM 1</div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                                        DISPENSASI
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Dispensasi Lomba Futsal Antar Pelajar">
                                        Lomba Futsal Dinas
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                        <i class="fas fa-check"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">Siti Aisyah R.</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">XI RPL 2</div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                                        SAKIT UKS
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Pusing dan demam, dirujuk istirahat ke UKS">
                                        Demam / Istirahat UKS
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                        <i class="fas fa-check"></i> Disetujui
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card 2: Pemantauan Jurnal & Agenda KBM Guru Hari Ini --}}
        <div class="card" style="padding: 0; overflow: hidden; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(59, 130, 246, 0.02); flex-wrap: wrap; gap: 8px;">
                <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-bookmark text-primary"></i> Jurnal KBM Kelas Hari Ini
                </div>
                <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-outline btn-icon" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;" title="Lihat Agenda KBM">
                    <i class="fas fa-arrow-right" style="font-size: 0.76rem;"></i>
                </a>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
                <table class="table-minimal-compact">
                    <thead>
                        <tr>
                            <th style="min-width: 140px;">Guru &amp; Mapel</th>
                            <th style="min-width: 90px;">Kelas / Jam</th>
                            <th style="min-width: 140px;">Materi KBM</th>
                            <th style="min-width: 70px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAgendaKbm ?? [] as $agenda)
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">
                                        {{ $agenda->guru_nama ?? 'Guru Pengampu' }}
                                    </div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        {{ $agenda->nama_mata_pelajaran ?? 'Mapel' }}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-color);">{{ $agenda->rombel_nama ?? 'Kelas' }}</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Jam {{ $agenda->jam_ke_mulai ?? '1' }}-{{ $agenda->jam_ke_selesai ?? '2' }}</div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $agenda->materi_pokok ?? ($agenda->uraian_kegiatan ?? '-') }}">
                                        {{ $agenda->materi_pokok ?? ($agenda->uraian_kegiatan ?? '-') }}
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                        <i class="fas fa-check-double"></i> Terisi
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-color); font-size: 0.84rem;">Drs. H. Mulyadi</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Matematika Terapan</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.8rem; color: var(--text-color);">XII TKJ 1</div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">Jam 1-3</div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-color); max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="Matriks invers dan determinan">
                                        Matriks Invers &amp; Determinan
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                                        <i class="fas fa-check-double"></i> Terisi
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Presensi Guru & Quick Links Piket (Stack di Mobile) -->
    <div style="display: flex; flex-direction: column; gap: 20px; min-width: 0;">
        {{-- Card 3: Presensi Kedatangan Guru --}}
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-calendar-check text-success"></i> Presensi Guru Hari Ini
                </div>
                <span class="badge badge-success" style="font-size: 0.68rem; padding: 2px 6px;">Aktif</span>
            </div>

            @php
                $totG = $piketStats['total_guru'] ?? 48;
                $hadG = $piketStats['guru_hadir'] ?? 36;
                $pctG = $totG > 0 ? round(($hadG / $totG) * 100) : 0;
            @endphp
            <div style="margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 5px;">
                    <span style="color: var(--text-muted);">Kehadiran:</span>
                    <strong style="color: #10b981;">{{ $pctG }}% ({{ $hadG }}/{{ $totG }})</strong>
                </div>
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ $pctG }}%; height: 100%; background: #10b981; border-radius: 4px;"></div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                @forelse ($recentPresensiGuru ?? [] as $pm)
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color); gap: 8px;">
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $pm->guru_nama ?? 'Guru' }}</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $pm->nama_mata_pelajaran ?? 'KBM' }}</div>
                        </div>
                        <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">
                            {{ $pm->status ?? 'Hadir' }}
                        </span>
                    </div>
                @empty
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color); gap: 8px;">
                        <div style="min-width: 0; flex: 1;">
                            <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-color);">Bambang Sugiarto, S.Pd</div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">Bahasa Indonesia</div>
                        </div>
                        <span class="badge-compact" style="background: rgba(16,185,129,0.12); color: #10b981;">Mengajar</span>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 0.78rem; padding: 8px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-clipboard-check text-success"></i> Presensi Mengajar
                </a>
            </div>
        </div>

        {{-- Card 4: Pintasan Guru Piket --}}
        <div class="card" style="padding: 18px; border-radius: 14px; border: 1px solid var(--border-color); background: var(--card-bg);">
            <div style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-bolt text-warning"></i> Menu Guru Piket
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="{{ route('dashboard.peserta-didik.izin.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-person-walking-arrow-right text-warning me-2"></i> e-Izin Siswa</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>

                <a href="{{ route('dashboard.presensi-mengajar.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-calendar-check text-success me-2"></i> Presensi Guru</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>

                <a href="{{ route('dashboard.agenda-kbm.index') }}" class="btn btn-outline"
                    style="display: flex; justify-content: space-between; align-items: center; padding: 9px 12px; font-size: 0.8rem; border-radius: 8px; text-decoration: none;">
                    <span><i class="fas fa-book-open-reader text-primary me-2"></i> Agenda KBM</span>
                    <i class="fas fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>
                </a>
            </div>
        </div>
    </div>
</div>
