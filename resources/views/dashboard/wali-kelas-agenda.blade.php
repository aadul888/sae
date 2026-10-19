@extends('layouts.dashboard')

@section('title', 'Wali Kelas — Agenda Kelas — SAE')
@section('dash_title', 'Wali Kelas — Agenda Kelas')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: 12px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-book-open-reader"></i>
            </div>
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 800; color: var(--text-color); margin: 0 0 2px 0;">
                    Agenda Kelas Binaan
                </h2>
                <p style="color: var(--text-muted); font-size: 0.82rem; margin: 0;">
                    @if ($hasRombel && $activeRombel)
                        Kelas: <strong style="color: var(--text-color);">{{ $activeRombel->nama }}</strong>
                        &bull; Wali Kelas: <strong style="color: var(--primary);">{{ $waliNama }}</strong>
                        &bull; Pengisian agenda sesuai jadwal harian dibantu guru mapel, wali kelas, koordinator, dan
                        petugas piket.
                    @else
                        Monitoring kegiatan belajar mengajar dan pencatatan agenda harian kelas binaan.
                    @endif
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            @if ($canSwitchRombel && $rombelList->isNotEmpty())
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="adminRombelSelect"
                        style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted); margin: 0; white-space: nowrap;">
                        <i class="fas fa-chalkboard me-1"></i> Rombel:
                    </label>
                    <select id="adminRombelSelect" class="form-control"
                        style="font-size: 0.85rem; padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-color); min-width: 170px;">
                        @foreach ($rombelList as $r)
                            <option value="{{ $r->rombongan_belajar_id }}"
                                {{ $activeRombel?->rombongan_belajar_id === $r->rombongan_belajar_id ? 'selected' : '' }}>
                                {{ $r->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($hasRombel)
                <a href="{{ route('dashboard.wali-kelas.agenda.cetak', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tanggal' => $tab === 'jadwal' ? $tanggal : null])) }}"
                    target="_blank" class="btn btn-outline" title="Cetak Rekap Jurnal Agenda Kelas"
                    style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-size: 0.84rem; text-decoration: none;">
                    <i class="fas fa-print text-primary"></i>
                    <span>Cetak Jurnal</span>
                </a>

                @if ($canCreate)
                    <button type="button" class="btn btn-primary" id="btnOpenCreateAgenda"
                        style="height: 38px; padding: 0 14px; display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; font-size: 0.84rem; font-weight: 700;">
                        <i class="fas fa-plus"></i>
                        <span>Input Agenda Tambahan</span>
                    </button>
                @endif
            @endif
        </div>
    </div>

    @if (!$hasRombel)
        <!-- Empty State jika belum terikat rombel -->
        <div class="card" style="padding: 40px 20px; text-align: center; margin-top: 20px;">
            <div
                style="width: 70px; height: 70px; border-radius: 50%; background: rgba(245, 158, 11, 0.15); color: #f59e0b; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 16px;">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-color); margin-bottom: 8px;">
                Belum Ada Rombel Terpilih
            </h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 540px; margin: 0 auto 20px;">
                Akun Anda belum terikat dengan rombongan belajar aktif sebagai Wali Kelas, Koordinator Kelas, atau Petugas
                Piket. Silakan hubungi Administrator sistem atau Operator Dapodik sekolah.
            </p>
        </div>
    @else
        <!-- 2. Navigasi Tab Segmented: Agenda Sesuai Jadwal vs Semua Riwayat -->
        <div
            style="display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <a href="{{ route('dashboard.wali-kelas.agenda.index', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tab' => 'jadwal', 'tanggal' => $tanggal])) }}"
                    class="btn btn-sm {{ $tab === 'jadwal' ? 'btn-primary' : 'btn-outline' }}"
                    style="border-radius: 20px; padding: 7px 16px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 7px;">
                    <i class="fas fa-calendar-check"></i>
                    <span>Agenda Sesuai Jadwal ({{ $hari }})</span>
                </a>
                <a href="{{ route('dashboard.wali-kelas.agenda.index', array_filter(['rombel_id' => $canSwitchRombel ? $activeRombel?->rombongan_belajar_id : null, 'tab' => 'riwayat'])) }}"
                    class="btn btn-sm {{ $tab === 'riwayat' ? 'btn-primary' : 'btn-outline' }}"
                    style="border-radius: 20px; padding: 7px 16px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 7px;">
                    <i class="fas fa-clock-rotate-left"></i>
                    <span>Semua Riwayat Agenda Kelas ({{ number_format($summary['total'] ?? 0) }})</span>
                </a>
            </div>

            <!-- Identitas Peran Pengisi Aktif -->
            <div style="font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                <span>Peran Anda:</span>
                @if ($isAdmin)
                    <span class="badge badge-primary"><i class="fas fa-shield me-1"></i> Administrator</span>
                @elseif ($isPiket)
                    <span class="badge badge-outline" style="border-color: #0284c7; color: #0284c7; font-weight: 700;"><i
                            class="fas fa-user-shield me-1"></i> Petugas Piket KBM</span>
                @elseif ($isKoordinator)
                    <span class="badge badge-warning text-dark"><i class="fas fa-crown me-1"></i> Koordinator Kelas</span>
                @else
                    <span class="badge badge-success"><i class="fas fa-user-tie me-1"></i> Wali Kelas</span>
                @endif
            </div>
        </div>

        @if ($tab === 'jadwal')
            <!-- 3. DATE CONTROL BAR (Pilihan Hari/Tanggal KBM Aktif) -->
            <div class="card"
                style="padding: 14px 18px; margin-bottom: 20px; border-radius: 12px; background: var(--bg-card);">
                <div
                    style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <!-- Sisi Kiri: Tanggal Terpilih & Navigasi -->
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span
                            style="font-size: 0.82rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-calendar-day text-primary"></i> Pilih Tanggal KBM:
                        </span>

                        <input type="date" id="inputJadwalTanggal" value="{{ $tanggal }}"
                            style="height: 36px; padding: 0 10px; border: 1.5px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 600; cursor: pointer;">

                        @php
                            $todayDate = date('Y-m-d');
                            $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
                            $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
                        @endphp
                        <button type="button"
                            class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $todayDate ? 'active' : '' }}"
                            data-date="{{ $todayDate }}"
                            style="padding: 6px 12px; font-size: 0.78rem; border-radius: 6px;">
                            Hari Ini
                        </button>
                        <button type="button"
                            class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $yesterdayDate ? 'active' : '' }}"
                            data-date="{{ $yesterdayDate }}"
                            style="padding: 6px 12px; font-size: 0.78rem; border-radius: 6px;">
                            Kemarin
                        </button>
                        <button type="button"
                            class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $tomorrowDate ? 'active' : '' }}"
                            data-date="{{ $tomorrowDate }}"
                            style="padding: 6px 12px; font-size: 0.78rem; border-radius: 6px;">
                            Besok
                        </button>
                    </div>

                    <!-- Sisi Kanan: Badge Penanda Hari -->
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="badge badge-primary"
                            style="font-size: 0.82rem; padding: 6px 14px; font-weight: 800; border-radius: 8px;">
                            <i class="fas fa-clock me-1"></i> {{ $hari }},
                            {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                        </span>
                        @if (!empty($statusKalender['is_libur']) || !empty($statusKalender['libur_gtk']))
                            <span class="badge badge-danger"
                                style="font-size: 0.78rem; padding: 6px 10px; border-radius: 8px;">
                                <i class="fas fa-umbrella-beach me-1"></i> Libur Sekolah
                            </span>
                        @endif
                    </div>
                </div>

                @if (!empty($agendaKalender))
                    <div
                        style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color); font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-calendar-star text-warning"></i>
                        <span>Kalender Pendidikan: <strong>{{ $agendaKalender->nama_agenda }}</strong> &bull;
                            {{ $agendaKalender->keterangan ?? 'Kegiatan KBM reguler disesuaikan.' }}</span>
                    </div>
                @endif
            </div>

            <!-- 4. Stat Grid Harian Sesuai Jadwal -->
            <div class="dash-stat-grid"
                style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem;">
                            {{ $dailySummary['total_slots'] }} <span
                                style="font-size: 0.74rem; font-weight: 600;">Mapel</span>
                        </div>
                        <div class="dash-stat-label">Mapel Terjadwal ({{ $hari }})</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #10b981;">
                            {{ $dailySummary['terisi'] }} <span style="font-size: 0.74rem; font-weight: 600;">Mapel</span>
                        </div>
                        <div class="dash-stat-label">Agenda Sudah Diisi</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: #f59e0b;">
                            {{ $dailySummary['belum'] }} <span style="font-size: 0.74rem; font-weight: 600;">Mapel</span>
                        </div>
                        <div class="dash-stat-label">Belum Diisi Agenda</div>
                    </div>
                </div>

                <div class="dash-stat-card">
                    <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="dash-stat-info">
                        <div class="dash-stat-value" style="font-size: 1.35rem; color: var(--accent);">
                            {{ $dailySummary['persen'] }}%
                        </div>
                        <div class="dash-stat-label">Keterisian Agenda Hari Ini</div>
                    </div>
                </div>
            </div>

            <!-- 5. DAFTAR MATA PELAJARAN AKTIF SESUAI JADWAL PADA TANGGAL INI -->
            <div class="card table-responsive-stack" id="tableDataContainer"
                style="padding: 0; margin-bottom: 24px; overflow: hidden;">
                <div
                    style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); background: rgba(99,102,241,0.03); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <div
                            style="font-weight: 800; font-size: 0.95rem; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-timeline text-primary"></i> Jadwal Pelajaran Aktif Hari {{ $hari }}
                        </div>
                        <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Menampilkan mata pelajaran aktif (kegiatan istirahat, upacara, pembiasaan &amp; slot tanpa guru
                            telah dikecualikan).
                        </div>
                    </div>
                    <div style="font-size: 0.76rem; color: var(--text-muted);">
                        Total <strong>{{ $dailySlots->count() }}</strong> slot pelajaran
                    </div>
                </div>

                <div style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd table-agenda-jadwal"
                        style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr
                                style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                                <th class="th-jam"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                                    <i class="fas fa-clock text-primary me-1"></i> Jam KBM
                                </th>
                                <th class="th-mapel"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 200px;">
                                    <i class="fas fa-book text-primary me-1"></i> Mata Pelajaran
                                </th>
                                <th class="th-guru"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px;">
                                    <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu
                                </th>
                                <th class="th-materi"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 260px;">
                                    <i class="fas fa-file-lines text-primary me-1"></i> Materi &amp; Kegiatan KBM
                                </th>
                                <th class="th-status"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">
                                    <i class="fas fa-signal text-primary me-1"></i> Status Agenda
                                </th>
                                <th class="th-aksi"
                                    style="padding: 13px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 150px;">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dailySlots as $slot)
                                @php
                                    $agenda = $slot->agenda;
                                    $isTerisi = $slot->is_terisi;
                                    $namaGuru = $slot->nama_guru;
                                @endphp
                                <tr class="slot-row {{ $isTerisi ? 'slot-terisi' : 'slot-belum-terisi' }}"
                                    style="border-bottom: 1px solid var(--border-color); transition: background 0.18s ease; {{ !$isTerisi ? 'background: rgba(245, 158, 11, 0.02);' : '' }}">
                                    <!-- Kolom Jam Pelajaran -->
                                    <td class="cell-slot-jam" data-label="Jam KBM" style="padding: 14px 16px;">
                                        <div class="cell-col-right">
                                            <div style="font-weight: 800; font-size: 0.88rem; color: var(--primary);">
                                                Jam {{ $slot->jam_ke_mulai }}-{{ $slot->jam_ke_selesai }}
                                            </div>
                                            <div style="font-size: 0.73rem; color: var(--text-muted); margin-top: 2px;">
                                                {{ $slot->jam_waktu_range ?: 'Waktu reguler' }} &bull;
                                                {{ $slot->durasi_jp }} JP
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kolom Mata Pelajaran -->
                                    <td class="cell-slot-mapel" data-label="Mata Pelajaran" style="padding: 14px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 700; font-size: 0.9rem; color: var(--text-color); line-height: 1.35;">
                                                {{ $slot->nama_mata_pelajaran }}
                                            </div>
                                            @if (!empty($slot->ruangan))
                                                <div
                                                    style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                                    <i class="fas fa-door-open me-1"></i> Ruang: {{ $slot->ruangan }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Kolom Guru Pengampu -->
                                    <td class="cell-slot-guru" data-label="Guru Pengampu" style="padding: 14px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 700; font-size: 0.88rem; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-user-tie text-primary" style="font-size: 0.82rem;"></i>
                                                <span>{{ $namaGuru }}</span>
                                            </div>
                                            @if ($isTerisi && !empty($agenda->created_by))
                                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                                    <i class="fas fa-pen-nib me-1" style="font-size: 0.65rem;"></i>
                                                    Dicatat: {{ $agenda->created_by }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Kolom Materi & Kegiatan KBM -->
                                    <td class="cell-slot-materi" data-label="Materi KBM" style="padding: 14px 16px;">
                                        <div class="cell-col-right">
                                            @if ($isTerisi)
                                                <div
                                                    style="font-weight: 700; color: var(--text-color); font-size: 0.88rem; margin-bottom: 2px; line-height: 1.35;">
                                                    <span class="badge"
                                                        style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: 0.72rem; padding: 2px 6px; font-weight: 800; margin-right: 4px;">
                                                        #{{ $agenda->pertemuan_ke }}
                                                    </span>
                                                    {{ $agenda->materi_pokok }}
                                                </div>
                                                @if (!empty($agenda->uraian_kegiatan))
                                                    <div
                                                        style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                        {{ $agenda->uraian_kegiatan }}
                                                    </div>
                                                @endif
                                                @if (!empty($agenda->penugasan))
                                                    <div
                                                        style="font-size: 0.72rem; color: #10b981; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fas fa-tasks"></i>
                                                        <span>{{ \Illuminate\Support\Str::limit($agenda->penugasan, 45) }}</span>
                                                    </div>
                                                @endif
                                            @else
                                                <div
                                                    style="color: var(--text-muted); font-size: 0.82rem; font-style: italic; display: flex; align-items: center; gap: 6px;">
                                                    <i class="fas fa-hourglass-start text-warning"></i>
                                                    <span>Agenda belum dicatat untuk jam pelajaran ini.</span>
                                                </div>
                                                <div
                                                    style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                                    Rencana pertemuan ke: <strong>#{{ $slot->next_pertemuan }}</strong>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Kolom Status KBM -->
                                    <td class="cell-slot-status" data-label="Status KBM"
                                        style="padding: 14px 16px; text-align: center;">
                                        <div class="cell-col-right" style="align-items: center;">
                                            @if ($isTerisi)
                                                {!! $agenda->status_badge !!}
                                            @else
                                                <span class="badge badge-warning"
                                                    style="font-size: 0.75rem; padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                                    <i class="fas fa-clock"></i> Belum Diisi
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Kolom Aksi -->
                                    <td class="cell-slot-aksi" data-label="Aksi"
                                        style="padding: 14px 18px; text-align: right;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                            @if ($isTerisi)
                                                <button type="button" class="btn-icon btn-detail-agenda"
                                                    data-id="{{ $agenda->id }}" title="Lihat Detail Agenda"
                                                    style="width: 32px; height: 32px; border-radius: 6px;">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                @if ($canUpdate)
                                                    <button type="button" class="btn-icon btn-edit-agenda"
                                                        data-id="{{ $agenda->id }}" title="Edit Agenda Kelas"
                                                        style="width: 32px; height: 32px; border-radius: 6px; color: #f59e0b;">
                                                        <i class="fas fa-pen-to-square"></i>
                                                    </button>
                                                @endif

                                                @if ($canDelete)
                                                    <button type="button" class="btn-icon btn-delete-agenda"
                                                        data-id="{{ $agenda->id }}" title="Hapus Agenda"
                                                        style="width: 32px; height: 32px; border-radius: 6px; color: #ef4444;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            @else
                                                @if ($canCreate)
                                                    <button type="button"
                                                        class="btn btn-primary btn-sm btn-bantu-isi-slot"
                                                        data-jadwal-id="{{ $slot->id }}"
                                                        data-ptk-id="{{ $slot->ptk_id }}"
                                                        data-guru="{{ $namaGuru }}"
                                                        data-mapel="{{ $slot->nama_mata_pelajaran }}"
                                                        data-pembelajaran-id="{{ $slot->pembelajaran_id }}"
                                                        data-mapel-id="{{ $slot->mata_pelajaran_id }}"
                                                        data-hari="{{ $hari }}"
                                                        data-jam-mulai="{{ $slot->jam_ke_mulai }}"
                                                        data-jam-selesai="{{ $slot->jam_ke_selesai }}"
                                                        data-pertemuan="{{ $slot->next_pertemuan }}"
                                                        style="padding: 6px 12px; font-size: 0.78rem; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fas fa-plus"></i> <span>Isi Agenda</span>
                                                    </button>
                                                @else
                                                    <span class="text-muted" style="font-size: 0.78rem;">-</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="cell-empty"
                                        style="padding: 38px 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                                        <i class="fas fa-calendar-xmark mb-2"
                                            style="font-size: 2.2rem; opacity: 0.4;"></i>
                                        <div style="font-weight: 800; color: var(--text-color); margin-bottom: 4px;">Tidak
                                            Ada Jadwal KBM pada Hari {{ $hari }}</div>
                                        <div style="font-size: 0.8rem; max-width: 480px; margin: 0 auto;">
                                            Tidak ditemukan mata pelajaran aktif pada jadwal KBM reguler rombel ini pada
                                            tanggal {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- 3. Toolbar & Filter -->
            <div class="card" style="padding: 16px; margin-bottom: 20px;">
                <div
                    style="display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center;">
                    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div class="toolbar-entries">
                            <label for="perPageSelect"
                                style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Tampilkan</label>
                            <select id="perPageSelect" class="per-page-select" style="height: 38px;">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>
                                        {{ $n }}</option>
                                @endforeach
                            </select>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">entri</span>
                        </div>

                        <!-- Filter Rentang Tanggal Inline -->
                        <div
                            style="display: inline-flex; align-items: center; gap: 6px; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 0 10px; height: 38px; box-sizing: border-box; flex-shrink: 0;">
                            <i class="fas fa-calendar-day text-primary" style="font-size: 0.8rem;"
                                title="Rentang Tanggal"></i>
                            <input type="date" id="filterTanggalMulai" value="{{ $tanggalMulai }}"
                                title="Tanggal Mulai"
                                style="background: transparent; border: none; color: var(--text-color); font-size: 0.82rem; padding: 0; outline: none; width: 125px; cursor: pointer;">
                            <span style="color: var(--text-muted); font-size: 0.74rem;">&ndash;</span>
                            <input type="date" id="filterTanggalSelesai" value="{{ $tanggalSelesai }}"
                                title="Tanggal Selesai"
                                style="background: transparent; border: none; color: var(--text-color); font-size: 0.82rem; padding: 0; outline: none; width: 125px; cursor: pointer;">
                        </div>

                        <!-- Filter Guru Pengampu -->
                        <select id="filterPtk" class="toolbar-filter-select" style="min-width: 160px; height: 38px;">
                            <option value="">Semua Guru Pengampu</option>
                            @foreach ($guruMapelList as $g)
                                <option value="{{ $g->ptk_id }}" {{ $filterPtk === $g->ptk_id ? 'selected' : '' }}>
                                    {{ $g->nama_guru }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Filter Mapel -->
                        <select id="filterMapel" class="toolbar-filter-select" style="min-width: 160px; height: 38px;">
                            <option value="">Semua Mata Pelajaran</option>
                            @foreach ($mapelList as $m)
                                <option value="{{ $m }}" {{ $filterMapel === $m ? 'selected' : '' }}>
                                    {{ $m }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Filter Status KBM -->
                        <select id="filterStatus" class="toolbar-filter-select" style="min-width: 140px; height: 38px;">
                            <option value="">Semua Status</option>
                            <option value="Terlaksana" {{ $filterStatus === 'Terlaksana' ? 'selected' : '' }}>Terlaksana
                            </option>
                            <option value="Sebagian" {{ $filterStatus === 'Sebagian' ? 'selected' : '' }}>Sebagian
                            </option>
                            <option value="Tertunda" {{ $filterStatus === 'Tertunda' ? 'selected' : '' }}>Tertunda
                            </option>
                            <option value="Digantikan" {{ $filterStatus === 'Digantikan' ? 'selected' : '' }}>Digantikan
                            </option>
                        </select>

                        @if ($q || $filterPtk || $filterMapel || $filterStatus || $tanggalMulai || $tanggalSelesai)
                            <button type="button" id="btnResetFilter" class="btn btn-outline"
                                style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;"
                                title="Reset filter">
                                <i class="fas fa-undo"></i>
                            </button>
                        @endif
                    </div>

                    <!-- Live Search Box -->
                    <div class="live-search-wrap" style="height: 38px;">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="liveSearchInput" placeholder="Cari materi, guru, mapel, tugas..."
                            value="{{ $q }}" autocomplete="off">
                        <button type="button" class="clear-search {{ $q ? 'visible' : '' }}" title="Hapus pencarian">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 4. Datatable Agenda Kelas (Baku SAE: table-responsive-stack & table-pd) -->
            <div class="card table-responsive-stack" id="tableDataContainer"
                style="padding: 0; margin-bottom: 24px; overflow: hidden;">
                <div style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd table-wali-agenda"
                        style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr
                                style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color); text-align: left;">
                                <th class="th-no d-none-mobile"
                                    style="padding: 13px 14px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 48px; text-align: center;">
                                    No
                                </th>
                                <th class="sortable-th {{ $sort === 'tanggal' ? 'sorted' : '' }}" data-sort="tanggal"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 150px; cursor: pointer;">
                                    <i class="fas fa-calendar-day me-1 text-primary"></i> Tanggal &amp; Jam
                                    <span class="sort-icon">{!! $sort === 'tanggal' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th class="sortable-th {{ $sort === 'pertemuan_ke' ? 'sorted' : '' }}"
                                    data-sort="pertemuan_ke"
                                    style="padding: 13px 12px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 80px; cursor: pointer;">
                                    <i class="fas fa-hashtag me-1 text-primary"></i> Pert.
                                    <span class="sort-icon">{!! $sort === 'pertemuan_ke' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px;">
                                    <i class="fas fa-chalkboard-user me-1 text-primary"></i> Guru Pengampu
                                </th>
                                <th class="sortable-th {{ $sort === 'nama_mata_pelajaran' ? 'sorted' : '' }}"
                                    data-sort="nama_mata_pelajaran"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px; cursor: pointer;">
                                    <i class="fas fa-book me-1 text-primary"></i> Mata Pelajaran
                                    <span class="sort-icon">{!! $sort === 'nama_mata_pelajaran' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 260px;">
                                    <i class="fas fa-file-lines me-1 text-primary"></i> Materi &amp; Uraian Pembelajaran
                                </th>
                                <th class="sortable-th {{ $sort === 'status_kbm' ? 'sorted' : '' }}"
                                    data-sort="status_kbm"
                                    style="padding: 13px 16px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 130px; cursor: pointer;">
                                    <i class="fas fa-signal me-1 text-primary"></i> Status
                                    <span class="sort-icon">{!! $sort === 'status_kbm' ? ($sortDir === 'asc' ? '&#9650;' : '&#9660;') : '&#9650;&#9660;' !!}</span>
                                </th>
                                <th
                                    style="padding: 13px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; min-width: 120px;">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $index => $item)
                                @php
                                    $rowNum = ($list->currentPage() - 1) * $list->perPage() + $index + 1;
                                @endphp
                                <tr class="agenda-row"
                                    style="border-bottom: 1px solid var(--border-color); transition: background 0.18s ease;">
                                    <!-- No Urut (Desktop Only) -->
                                    <td class="cell-pd-no d-none-mobile" data-label="No"
                                        style="padding: 12px 14px; text-align: center; color: var(--text-muted); font-size: 0.82rem; font-weight: 700;">
                                        {{ $rowNum }}
                                    </td>

                                    <!-- Tanggal & Jam -->
                                    <td class="cell-pd-tanggal" data-label="Tanggal &amp; Jam"
                                        style="padding: 12px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; white-space: nowrap;">
                                                {{ $item->tanggal ? $item->tanggal->translatedFormat('d M Y') : '-' }}
                                            </div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">
                                                {{ $item->hari }} &bull; Jam {{ $item->jam_ke_mulai }} s.d.
                                                {{ $item->jam_ke_selesai }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Pertemuan Ke -->
                                    <td class="cell-pd-pertemuan" data-label="Pertemuan Ke"
                                        style="padding: 12px 12px; text-align: center;">
                                        <div class="cell-col-right" style="align-items: center;">
                                            <span class="badge"
                                                style="background: rgba(99,102,241,0.12); color: var(--primary); font-weight: 800; font-size: 0.8rem; padding: 3px 8px; border-radius: 6px;">
                                                #{{ $item->pertemuan_ke }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Guru Pengampu -->
                                    <td class="cell-pd-guru" data-label="Guru Pengampu" style="padding: 12px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-chalkboard-user text-primary"
                                                    style="font-size: 0.82rem;"></i>
                                                <span>{{ $item->nama_guru ?: '-' }}</span>
                                            </div>
                                            @if (!empty($item->created_by))
                                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 2px;">
                                                    <i class="fas fa-pen-nib me-1" style="font-size: 0.65rem;"></i>
                                                    {{ $item->created_by }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Mata Pelajaran -->
                                    <td class="cell-pd-mapel" data-label="Mata Pelajaran" style="padding: 12px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 600; color: var(--text-color); font-size: 0.86rem; line-height: 1.35;">
                                                {{ $item->nama_mata_pelajaran }}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Materi & Uraian Kegiatan -->
                                    <td class="cell-pd-materi" data-label="Materi &amp; Uraian"
                                        style="padding: 12px 16px;">
                                        <div class="cell-col-right">
                                            <div
                                                style="font-weight: 700; color: var(--text-color); font-size: 0.86rem; margin-bottom: 2px; line-height: 1.35;">
                                                {{ $item->materi_pokok }}
                                            </div>
                                            @if (!empty($item->uraian_kegiatan))
                                                <div
                                                    style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                    {{ $item->uraian_kegiatan }}
                                                </div>
                                            @endif
                                            @if (!empty($item->penugasan))
                                                <div
                                                    style="font-size: 0.72rem; color: #10b981; margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                                    <i class="fas fa-tasks"></i>
                                                    <span>{{ \Illuminate\Support\Str::limit($item->penugasan, 45) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status KBM -->
                                    <td class="cell-pd-status" data-label="Status KBM"
                                        style="padding: 12px 16px; text-align: center;">
                                        <div class="cell-col-right" style="align-items: center;">
                                            {!! $item->status_badge !!}
                                        </div>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="cell-pd-aksi" data-label="Aksi"
                                        style="padding: 12px 18px; text-align: right;">
                                        <div class="table-actions"
                                            style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                            <button type="button" class="btn-icon btn-detail-agenda"
                                                data-id="{{ $item->id }}" title="Lihat Detail Agenda"
                                                style="width: 32px; height: 32px; border-radius: 6px;">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            @if ($canUpdate)
                                                <button type="button" class="btn-icon btn-edit-agenda"
                                                    data-id="{{ $item->id }}" title="Edit Agenda Kelas"
                                                    style="width: 32px; height: 32px; border-radius: 6px; color: #f59e0b;">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </button>
                                            @endif

                                            @if ($canDelete)
                                                <button type="button" class="btn-icon btn-delete-agenda"
                                                    data-id="{{ $item->id }}" title="Hapus Agenda Kelas"
                                                    style="width: 32px; height: 32px; border-radius: 6px; color: #ef4444;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="cell-empty"
                                        style="padding: 36px 20px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                                        <i class="fas fa-book-open mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                                        <div style="font-weight: 700; color: var(--text-color); margin-bottom: 4px;">Belum
                                            Ada
                                            Agenda Kelas</div>
                                        <div style="font-size: 0.8rem; max-width: 480px; margin: 0 auto;">
                                            Belum ada rekaman agenda kegiatan belajar mengajar untuk rombel ini yang sesuai
                                            dengan kriteria filter.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer Keterangan Entri & Pagination -->
                @if ($total > 0)
                    <div
                        style="padding: 14px 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div class="total-count-text" style="font-size: 0.83rem; color: var(--text-muted);">
                            Menampilkan <strong>{{ $list->firstItem() ?: 0 }}</strong> sampai
                            <strong>{{ $list->lastItem() ?: 0 }}</strong> dari
                            <strong>{{ number_format($total, 0, ',', '.') }}</strong> agenda kelas
                        </div>
                    </div>
                @endif
            </div>

            @if ($list->hasPages())
                <div class="custom-pagination">
                    @if ($list->onFirstPage())
                        <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
                    @else
                        <a href="{{ $list->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                                class="fas fa-chevron-left"></i></a>
                    @endif
                    @php
                        $cur = $list->currentPage();
                        $last = $list->lastPage();
                        $from = max(1, $cur - 2);
                        $to = min($last, $cur + 2);
                    @endphp
                    @if ($from > 1)
                        <a href="{{ $list->url(1) }}" class="page-btn">1</a>
                        @if ($from > 2)
                            <span class="page-info">&hellip;</span>
                        @endif
                    @endif
                    @for ($i = $from; $i <= $to; $i++)
                        <a href="{{ $list->url($i) }}"
                            class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
                    @endfor
                    @if ($to < $last)
                        @if ($to < $last - 1)
                            <span class="page-info">&hellip;</span>
                        @endif
                        <a href="{{ $list->url($last) }}" class="page-btn">{{ $last }}</a>
                    @endif
                    @if ($list->hasMorePages())
                        <a href="{{ $list->nextPageUrl() }}" class="page-btn" title="Berikutnya"><i
                                class="fas fa-chevron-right"></i></a>
                    @else
                        <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
                    @endif
                </div>
            @endif
        @endif
    @endif

    <!-- MODAL 1: FORM TAMBAH / EDIT AGENDA KELAS (BANTUAN WALI KELAS / KOORDINATOR / PIKET) -->
    <div id="modalFormAgendaKelas" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px; overflow-y: auto;">
        <div class="card modal-card-responsive"
            style="max-width: 660px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div class="ma-modal-head"
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; flex-shrink: 0;">
                <h3 id="modalAgendaKelasTitle"
                    style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-book-open-reader text-primary"></i> Input Agenda Kegiatan Kelas
                </h3>
                <button type="button" class="btn-close-modal close-agenda-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll"
                style="overflow-y: auto; -webkit-overflow-scrolling: touch; padding-right: 4px;">
                <div
                    style="background: rgba(99,102,241,0.08); border: 1px dashed rgba(99,102,241,0.3); border-radius: 8px; padding: 8px 12px; margin-bottom: 14px; font-size: 0.78rem; color: var(--text-color); display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-circle-info text-primary" style="font-size: 1rem; flex-shrink: 0;"></i>
                    <span>Fitur pendampingan Wali Kelas &amp; Koordinator untuk membantu mencatat jurnal KBM. Data yang
                        diinput akan otomatis tersinkronkan dengan jurnal guru mata pelajaran.</span>
                </div>

                <form id="formAgendaKelas">
                    @csrf
                    <input type="hidden" id="agendaKelasId" name="id">
                    <input type="hidden" id="inputJadwalKbmId" name="jadwal_kbm_id">
                    <input type="hidden" id="inputPembelajaranId" name="pembelajaran_id">
                    <input type="hidden" id="inputMataPelajaranId" name="mata_pelajaran_id">
                    <input type="hidden" id="inputPtkId" name="ptk_id">
                    <input type="hidden" id="inputHari" name="hari">

                    <!-- Slot Jadwal Terpilih Banner (Jika diklik dari baris jadwal) -->
                    <div id="slotJadwalBanner"
                        style="display: none; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.3); border-radius: 8px; padding: 10px 12px; margin-bottom: 14px;">
                        <div
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">
                            Mengisi Slot Terjadwal:</div>
                        <div style="font-size: 0.88rem; font-weight: 800; color: var(--text-color); margin-top: 2px;">
                            <span id="bannerSlotMapel">-</span> &bull; <span id="bannerSlotGuru"
                                class="text-primary">-</span>
                        </div>
                        <div style="font-size: 0.74rem; color: var(--text-muted); margin-top: 2px;">
                            <span id="bannerSlotWaktu">-</span> &bull; Kelas:
                            <strong>{{ $activeRombel->nama ?? '-' }}</strong>
                        </div>
                    </div>

                    <!-- Mode Pilihan: Dari Jadwal Pelajaran Kelas -->
                    @if ($jadwalList->isNotEmpty())
                        <div id="wrapSelectJadwalKelas" style="margin-bottom: 14px;">
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-calendar-days text-primary"></i> Pilih dari Jadwal Pelajaran Kelas:
                            </label>
                            <select id="selectJadwalKelas"
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                                <option value="">-- Pilih Slot Jadwal KBM (Otomatis Isi Guru &amp; Mapel) --</option>
                                @foreach ($jadwalList as $j)
                                    <option value="{{ $j['id'] }}" data-ptk-id="{{ $j['ptk_id'] }}"
                                        data-guru="{{ $j['nama_guru'] }}" data-mapel="{{ $j['nama_mata_pelajaran'] }}"
                                        data-pembelajaran-id="{{ $j['pembelajaran_id'] }}"
                                        data-mapel-id="{{ $j['mata_pelajaran_id'] }}" data-hari="{{ $j['hari'] }}"
                                        data-jam-mulai="{{ $j['jam_ke_mulai'] }}"
                                        data-jam-selesai="{{ $j['jam_ke_selesai'] }}">
                                        [{{ $j['hari'] }}] {{ $j['nama_mata_pelajaran'] }} &bull;
                                        {{ $j['nama_guru'] }} (Jam
                                        {{ $j['jam_ke_mulai'] }}-{{ $j['jam_ke_selesai'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-book text-primary"></i> Mata Pelajaran <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="inputNamaMataPelajaran" name="nama_mata_pelajaran" required
                                placeholder="Nama Mata Pelajaran..."
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-chalkboard-user text-primary"></i> Guru Pengampu <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <select id="selectGuruPengampu" required
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                                <option value="">-- Pilih Guru Pengampu --</option>
                                @foreach ($guruMapelList as $g)
                                    <option value="{{ $g->ptk_id }}">{{ $g->nama_guru }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1.2fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-calendar-day text-primary"></i> Tanggal <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <input type="date" id="inputTanggal" name="tanggal" value="{{ date('Y-m-d') }}"
                                required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-hashtag text-primary"></i> Pert. Ke <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" id="inputPertemuanKe" name="pertemuan_ke" min="1"
                                max="100" value="1" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; font-weight: 700; box-sizing: border-box; text-align: center;">
                        </div>

                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                Jam Mulai <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" id="inputJamKeMulai" name="jam_ke_mulai" min="0"
                                max="20" value="1" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box; text-align: center;">
                        </div>

                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                Jam Selesai <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="number" id="inputJamKeSelesai" name="jam_ke_selesai" min="0"
                                max="20" value="2" required
                                style="width: 100%; height: 38px; padding: 0 8px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.84rem; box-sizing: border-box; text-align: center;">
                        </div>
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label
                            style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-heading text-primary"></i> Materi Pokok / Bahasan <span
                                style="color: #ef4444;">*</span>
                        </label>
                        <input type="text" id="inputMateriPokok" name="materi_pokok" required
                            placeholder="Contoh: Pengenalan Topologi Jaringan &amp; Konfigurasi Router..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 12px;">
                        <label
                            style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-list-check text-primary"></i> Uraian Kegiatan KBM <span
                                style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="inputUraianKegiatan" name="uraian_kegiatan" rows="3" required
                            placeholder="Ringkasan aktivitas pembelajaran, diskusi, demonstrasi materi, atau praktik di kelas/lab..."
                            style="width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-tasks text-primary"></i> Penugasan / Tagihan Siswa
                            </label>
                            <input type="text" id="inputPenugasan" name="penugasan"
                                placeholder="Tugas mandiri / PR / kelompok..."
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                        </div>

                        <div>
                            <label
                                style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <i class="fas fa-signal text-primary"></i> Status KBM <span
                                    style="color: #ef4444;">*</span>
                            </label>
                            <select id="inputStatusKbm" name="status_kbm" required
                                style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                                <option value="Terlaksana" selected>Terlaksana Penuh</option>
                                <option value="Sebagian">Sebagian</option>
                                <option value="Tertunda">Tertunda</option>
                                <option value="Digantikan">Digantikan</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label
                            style="font-size: 0.78rem; font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-comment-dots text-primary"></i> Hambatan / Catatan Tambahan (Opsional)
                        </label>
                        <input type="text" id="inputHambatanCatatan" name="hambatan_catatan"
                            placeholder="Catatan kelas, kendala siswa, atau keterangan guru pengganti..."
                            style="width: 100%; height: 38px; padding: 0 10px; border: 1px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
                    </div>

                    <div
                        style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                        <button type="button" class="btn btn-outline close-agenda-modal"
                            style="padding: 7px 16px; font-size: 0.84rem;">
                            Batal
                        </button>
                        <button type="submit" id="btnSubmitAgendaKelas" class="btn btn-primary"
                            style="padding: 7px 18px; font-size: 0.84rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-save"></i> <span>Simpan Agenda</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: DETAIL LENGKAP AGENDA KELAS -->
    <div id="modalDetailAgendaKelas" class="modal-backdrop"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 12px;">
        <div class="card modal-card-responsive"
            style="max-width: 620px; width: 100%; max-height: 90vh; display: flex; flex-direction: column; margin: auto; border-radius: 14px; padding: 22px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); border: 1px solid var(--border-color); background: var(--bg-card);">
            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; flex-shrink: 0;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div
                        style="width: 40px; height: 40px; border-radius: 10px; background: rgba(99,102,241,0.12); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0;">
                        <i class="fas fa-book-open-reader"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin: 0;">
                            Detail Agenda KBM Kelas
                        </h3>
                        <div id="detailSubTitle" style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">
                            Laporan pelaksanaan kegiatan belajar mengajar
                        </div>
                    </div>
                </div>
                <button type="button" class="close-detail-modal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.2rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-scroll" style="overflow-y: auto; flex: 1; padding-right: 4px;">
                <div id="detailLoading" style="text-align: center; padding: 30px; color: var(--text-muted);">
                    <i class="fas fa-spinner fa-spin me-2" style="font-size: 1.4rem;"></i>
                    <div>Memuat detail agenda...</div>
                </div>

                <div id="detailContent" style="display: none; display: flex; flex-direction: column; gap: 12px;">
                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: var(--bg-hover); padding: 12px; border-radius: 10px; border: 1px solid var(--border-color);">
                        <div>
                            <span
                                style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Mata
                                Pelajaran:</span>
                            <strong id="dtMapel" style="font-size: 0.92rem; color: var(--text-color);">-</strong>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Guru
                                Pengampu:</span>
                            <strong id="dtGuru" style="font-size: 0.92rem; color: var(--text-color);">-</strong>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Waktu
                                Pertemuan:</span>
                            <span id="dtWaktu"
                                style="font-size: 0.84rem; color: var(--text-color); font-weight: 600;">-</span>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block;">Status
                                KBM:</span>
                            <div id="dtStatus" style="margin-top: 2px;">-</div>
                        </div>
                    </div>

                    <div>
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Materi
                            Pokok:</span>
                        <div id="dtMateri"
                            style="font-size: 0.9rem; font-weight: 700; color: var(--text-color); background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                            -
                        </div>
                    </div>

                    <div>
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Uraian
                            Kegiatan KBM:</span>
                        <div id="dtUraian"
                            style="font-size: 0.85rem; color: var(--text-color); line-height: 1.5; background: var(--card-bg); padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); white-space: pre-line;">
                            -
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <span
                                style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Penugasan:</span>
                            <div id="dtPenugasan"
                                style="font-size: 0.82rem; color: var(--text-color); background: var(--card-bg); padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                                -
                            </div>
                        </div>
                        <div>
                            <span
                                style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Dicatat
                                Oleh:</span>
                            <div id="dtCreatedBy"
                                style="font-size: 0.82rem; color: var(--text-color); background: var(--card-bg); padding: 8px 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                                -
                            </div>
                        </div>
                    </div>

                    <div id="boxDtCatatan" style="display: none;">
                        <span
                            style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; display: block; margin-bottom: 4px;">Hambatan
                            / Catatan:</span>
                        <div id="dtCatatan"
                            style="font-size: 0.82rem; color: #d97706; background: rgba(245,158,11,0.08); padding: 8px 10px; border-radius: 8px; border: 1px dashed rgba(245,158,11,0.3);">
                            -
                        </div>
                    </div>
                </div>
            </div>

            <div
                style="display: flex; justify-content: flex-end; margin-top: 14px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn btn-outline close-detail-modal"
                    style="padding: 7px 18px; font-size: 0.84rem; border-radius: 8px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .table-agenda-jadwal thead th,
        .table-wali-agenda thead th {
            white-space: nowrap;
        }

        .table-agenda-jadwal tbody tr:hover,
        .table-wali-agenda tbody tr:hover {
            background: rgba(99, 102, 241, 0.04);
        }

        .table-agenda-jadwal tbody td,
        .table-wali-agenda tbody td {
            vertical-align: middle;
        }

        .btn-quick-date.active {
            background: var(--primary) !important;
            color: #ffffff !important;
            border-color: var(--primary) !important;
        }

        @media (max-width: 768px) {

            .table-responsive-stack tbody tr.slot-row,
            .table-responsive-stack tbody tr.agenda-row {
                display: flex !important;
                flex-direction: column !important;
                padding: 14px 15px !important;
                margin-bottom: 14px !important;
                border-radius: 14px !important;
                background: var(--bg-card) !important;
                border: 1px solid var(--border-color) !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03) !important;
            }

            .table-responsive-stack tbody td.cell-pd-no {
                display: none !important;
            }

            .table-responsive-stack tbody td.cell-slot-jam,
            .table-responsive-stack tbody td.cell-pd-tanggal {
                order: 1 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 0 0 10px 0 !important;
                margin-bottom: 6px !important;
                border-bottom: 1px solid var(--border-color) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-slot-mapel,
            .table-responsive-stack tbody td.cell-pd-mapel {
                order: 2 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-slot-guru,
            .table-responsive-stack tbody td.cell-pd-guru {
                order: 3 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-slot-materi,
            .table-responsive-stack tbody td.cell-pd-materi {
                order: 4 !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 9px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
                text-align: left !important;
            }

            .table-responsive-stack tbody td.cell-slot-status,
            .table-responsive-stack tbody td.cell-pd-status {
                order: 5 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px dashed rgba(255, 255, 255, 0.08) !important;
                width: 100% !important;
            }

            .table-responsive-stack tbody td.cell-slot-aksi,
            .table-responsive-stack tbody td.cell-pd-aksi {
                order: 10 !important;
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                width: 100% !important;
                padding: 10px 0 0 0 !important;
                margin-top: 4px !important;
                border-top: 1px solid var(--border-color) !important;
                border-bottom: none !important;
            }

            .table-responsive-stack tbody td.cell-slot-aksi::before,
            .table-responsive-stack tbody td.cell-pd-aksi::before {
                content: attr(data-label) !important;
                font-weight: 700 !important;
                font-size: 0.74rem !important;
                color: var(--text-muted) !important;
                text-transform: uppercase !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.WALI_AGENDA_CFG = {
            currentTanggal: @json($tanggal),
            currentRombelId: @json($activeRombel?->rombongan_belajar_id),
            routes: {
                store: @json(route('dashboard.wali-kelas.agenda.store')),
                nextPertemuan: @json(route('dashboard.wali-kelas.agenda.next-pertemuan')),
                detail: @json(url('/dashboard/wali-kelas/agenda/detail')),
                update: @json(url('/dashboard/wali-kelas/agenda')),
                destroy: @json(url('/dashboard/wali-kelas/agenda')),
            }
        };
    </script>
    <script
        src="{{ asset('js/wali-kelas-agenda.js') }}?v={{ file_exists(public_path('js/wali-kelas-agenda.js')) ? filemtime(public_path('js/wali-kelas-agenda.js')) : time() }}">
    </script>
@endpush
