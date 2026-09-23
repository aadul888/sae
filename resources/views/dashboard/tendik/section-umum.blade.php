{{-- Section: Portal Umum Tenaga Kependidikan & Administrasi (Universal untuk seluruh Tendik) --}}

<!-- Quick Stats Grid Universal (4 Cards Maksimal) -->
<div class="dash-stat-grid" style="margin-bottom: 24px; gap: 14px;">
    {{-- Card 1: Aktivitas Kerja Hari Ini --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #3b82f6;">{{ $aktivitasHariIniCount ?? 0 }} Aksi</div>
            <div class="dash-stat-label">Aktivitas Sistem Hari Ini</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                {{ $aktivitasHariIniSelesai ?? 0 }} Selesai, {{ $aktivitasHariIniProses ?? 0 }} Proses
            </div>
        </div>
    </div>

    {{-- Card 2: Total Kinerja Bulan Ini --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #10b981;">{{ $aktivitasBulanIniCount ?? ($aktivitasSayaList?->count() ?? 0) }} Aktivitas</div>
            <div class="dash-stat-label">Total Kinerja Bulan Ini</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                Bulan {{ now()->translatedFormat('F Y') }}
            </div>
        </div>
    </div>

    {{-- Card 3: Penugasan Kedinasan --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="fas fa-briefcase"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #f59e0b; font-size: 1.15rem;">
                {{ !empty($dutyRecords) && $dutyRecords->isNotEmpty() ? $dutyRecords->count() . ' Tugas' : 'Staf Reguler' }}
            </div>
            <div class="dash-stat-label">Penugasan GTK</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;" title="{{ $bagianTugas ?? 'Tenaga Kependidikan' }}">
                {{ $bagianTugas ?? 'Tenaga Kependidikan' }}
            </div>
        </div>
    </div>

    {{-- Card 4: Pengumuman & Informasi Kedinasan --}}
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
            <i class="fas fa-bullhorn"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="color: #8b5cf6; font-size: 1.15rem;">{{ !empty($pengumumanList) ? $pengumumanList->count() : 0 }} Info</div>
            <div class="dash-stat-label">Pengumuman &amp; Edaran</div>
            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                Agenda Kedinasan Aktif
            </div>
        </div>
    </div>
</div>

{{-- 2. Konten Utama: Tabel Aktivitas Sistem Otomatis (Full Width) --}}
<div class="card" style="border-radius: 14px; padding: 22px; border: 1px solid var(--border-color); background: var(--card-bg); margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0 0 4px 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clock-rotate-left text-primary"></i> Aktivitas &amp; Tugas Harian Saya
            </h3>
            <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">
                Riwayat log aktivitas pengerjaan data, mutasi CRUD, serta autentikasi login yang dicatat otomatis oleh sistem hari ini.
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="badge badge-accent" style="font-size: 0.74rem; padding: 5px 10px; border-radius: 6px;">
                <i class="fas fa-robot me-1"></i> Log Otomatis Terhubung
            </span>
        </div>
    </div>

    @if (!empty($aktivitasSayaList) && $aktivitasSayaList->isNotEmpty())
        <div class="table-responsive-stack" style="overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 0;">
            <table class="table-minimal-compact">
                <thead>
                    <tr>
                        <th style="min-width: 120px;">Waktu</th>
                        <th style="min-width: 110px;">Modul</th>
                        <th style="min-width: 180px;">Aktivitas Sistem</th>
                        <th style="min-width: 110px;">Hasil</th>
                        <th style="min-width: 80px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aktivitasSayaList as $akt)
                        @php
                            $statusBadge = match($akt->status) {
                                'selesai' => ['label' => 'Selesai', 'bg' => 'rgba(16,185,129,0.12)', 'color' => '#10b981', 'icon' => 'fas fa-check-circle'],
                                'proses' => ['label' => 'Diproses', 'bg' => 'rgba(245,158,11,0.12)', 'color' => '#f59e0b', 'icon' => 'fas fa-spinner fa-spin'],
                                'tertunda' => ['label' => 'Tertunda', 'bg' => 'rgba(239,68,68,0.12)', 'color' => '#ef4444', 'icon' => 'fas fa-clock-rotate-left'],
                                default => ['label' => ucfirst($akt->status), 'bg' => 'rgba(100,116,139,0.12)', 'color' => '#64748b', 'icon' => 'fas fa-circle-dot'],
                            };
                            $bidangLabel = \App\Models\TendikAktivitas::BIDANG_LABELS[$akt->bidang] ?? ucfirst(str_replace('_', ' ', $akt->bidang));
                            $isLoginAction = str_contains(strtolower($akt->judul_aktivitas), 'login') || str_contains(strtolower($akt->judul_aktivitas), 'autentikasi');
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 16px; white-space: nowrap; vertical-align: top;">
                                <div style="font-weight: 700; color: var(--text-color);">
                                    {{ \Carbon\Carbon::parse($akt->tanggal)->translatedFormat('d M Y') }}
                                </div>
                                <div style="font-size: 0.74rem; color: var(--text-muted); display: flex; align-items: center; gap: 4px; margin-top: 3px;">
                                    <i class="far fa-clock text-primary"></i> 
                                    <span>{{ substr($akt->jam_mulai, 0, 5) }} WIB</span>
                                </div>
                            </td>
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <span class="badge" style="background: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.72rem; padding: 4px 8px; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="{{ $isLoginAction ? 'fas fa-key' : 'fas fa-cube' }}"></i> {{ $bidangLabel }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <div style="font-weight: 700; color: var(--text-color); margin-bottom: 3px; font-size: 0.85rem;">
                                    {{ $akt->judul_aktivitas }}
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.78rem; line-height: 1.45;">
                                    {{ $akt->uraian_pekerjaan }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px; vertical-align: top;">
                                <div style="font-size: 0.78rem; color: var(--text-color); font-weight: 600;">
                                    {{ $akt->output_hasil ?: 'Tercatat di Sistem' }}
                                </div>
                            </td>
                            <td style="padding: 12px 16px; text-align: center; vertical-align: top;">
                                <span class="badge" style="background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['color'] }}; padding: 4px 8px; font-size: 0.72rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="{{ $statusBadge['icon'] }}"></i> {{ $statusBadge['label'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div style="padding: 36px 20px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: 10px;">
            <div style="width: 50px; height: 50px; margin: 0 auto 12px auto; border-radius: 50%; background: var(--bg-hover); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--text-muted);">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div style="font-weight: 700; font-size: 0.92rem; color: var(--text-color); margin-bottom: 4px;">Belum Ada Riwayat Aktivitas Sistem Hari Ini</div>
            <div style="font-size: 0.78rem; max-width: 480px; margin: 0 auto;">
                Setiap kali Anda masuk ke dalam sistem atau melakukan operasi tambah, perbarui, dan hapus data (CRUD), aktivitas Anda akan otomatis dicatat di sini.
            </div>
        </div>
    @endif
</div>
