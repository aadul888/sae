<!-- 1. DATE CONTROL BAR (Pilihan Hari/Tanggal KBM Aktif) -->
<div class="card date-control-card"
    style="padding: 14px 18px; margin-bottom: 20px; border-radius: var(--radius-lg, 12px); background: var(--bg-card);">
    <div class="date-control-flex"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <!-- Sisi Kiri: Tanggal Terpilih & Navigasi Cepat -->
        <div class="date-picker-group" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span
                style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-calendar-day text-primary"></i> Pilih Tanggal:
            </span>

            <input type="date" id="inputJadwalTanggal" value="{{ $tanggal }}"
                style="height: 36px; padding: 0 10px; border: 1.5px solid var(--border-color); background: var(--bg-hover); color: var(--text-color); border-radius: var(--radius-md, 8px); font-size: var(--text-base, 0.875rem); font-weight: 600; cursor: pointer;">

            @php
                $todayDate = date('Y-m-d');
                $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
                $tomorrowDate = date('Y-m-d', strtotime('+1 day'));
            @endphp
            <div class="quick-date-buttons" style="display: inline-flex; gap: 6px;">
                <button type="button"
                    class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $todayDate ? 'active' : '' }}"
                    data-date="{{ $todayDate }}" style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                    Hari Ini
                </button>
                <button type="button"
                    class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $yesterdayDate ? 'active' : '' }}"
                    data-date="{{ $yesterdayDate }}" style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                    Kemarin
                </button>
                <button type="button"
                    class="btn btn-outline btn-sm btn-quick-date {{ $tanggal === $tomorrowDate ? 'active' : '' }}"
                    data-date="{{ $tomorrowDate }}" style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); border-radius: var(--radius-sm, 6px);">
                    Besok
                </button>
            </div>
        </div>

        <!-- Sisi Kanan: Badge Penanda Hari & Libur -->
        <div class="date-badge-group" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span class="badge badge-primary"
                style="font-size: var(--text-sm, 0.8125rem); padding: 6px 14px; font-weight: 800; border-radius: var(--radius-md, 8px);">
                <i class="fas fa-clock me-1"></i> {{ $hari }},
                {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
            </span>
            @if (!empty($statusKalender['is_libur']) || !empty($statusKalender['libur_gtk']))
                <span class="badge badge-danger" style="font-size: var(--text-sm, 0.8125rem); padding: 6px 10px; border-radius: var(--radius-md, 8px);">
                    <i class="fas fa-umbrella-beach me-1"></i> Libur Sekolah
                </span>
            @endif
        </div>
    </div>

    @if (!empty($agendaKalender))
        <div
            style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed var(--border-color); font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-calendar-star text-warning"></i>
            <span>Kalender Pendidikan: <strong>{{ $agendaKalender->nama_agenda }}</strong> &bull;
                {{ $agendaKalender->keterangan ?? 'Kegiatan KBM reguler disesuaikan.' }}</span>
        </div>
    @endif
</div>

<!-- 2. Stat Grid Harian Sesuai Jadwal -->
<div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 20px;">
    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(99,102,241,0.12); color: var(--primary);">
            <i class="fas fa-calendar-week"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem);">
                {{ $dailySummary['total_slots'] }} <span style="font-size: var(--text-xs, 0.75rem); font-weight: 600;">Mapel</span>
            </div>
            <div class="dash-stat-label">Mapel Terjadwal ({{ $hari }})</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: var(--success, #10b981);">
            <i class="fas fa-circle-check"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--success, #10b981);">
                {{ $dailySummary['terisi'] }} <span style="font-size: var(--text-xs, 0.75rem); font-weight: 600;">Mapel</span>
            </div>
            <div class="dash-stat-label">Agenda Sudah Diisi</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: var(--warning, #f59e0b);">
            <i class="fas fa-clock"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--warning, #f59e0b);">
                {{ $dailySummary['belum'] }} <span style="font-size: var(--text-xs, 0.75rem); font-weight: 600;">Mapel</span>
            </div>
            <div class="dash-stat-label">Belum Diisi Agenda</div>
        </div>
    </div>

    <div class="dash-stat-card">
        <div class="dash-stat-icon" style="background: rgba(6,182,212,0.12); color: var(--accent);">
            <i class="fas fa-chart-pie"></i>
        </div>
        <div class="dash-stat-info">
            <div class="dash-stat-value" style="font-size: var(--text-2xl, 1.5rem); color: var(--accent);">
                {{ $dailySummary['persen'] }}%
            </div>
            <div class="dash-stat-label">Keterisian Agenda Hari Ini</div>
        </div>
    </div>
</div>

<!-- 3. DAFTAR MATA PELAJARAN AKTIF SESUAI JADWAL PADA TANGGAL INI -->
<div class="card table-responsive-stack" id="tableDataContainer"
    style="padding: 0; margin-bottom: 24px; overflow: hidden;">
    <div
        style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); background: rgba(99,102,241,0.03); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <div>
            <div
                style="font-weight: 800; font-size: var(--text-md, 1rem); color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-timeline text-primary"></i> Jadwal Pelajaran Aktif Hari {{ $hari }}
            </div>
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); margin-top: 2px;">
                Menampilkan mata pelajaran aktif (kegiatan istirahat, upacara, pembiasaan &amp; slot tanpa guru telah
                dikecualikan).
            </div>
        </div>
        <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
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
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; width: 140px;">
                        <i class="fas fa-clock text-primary me-1"></i> Jam KBM
                    </th>
                    <th class="th-mapel"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 200px;">
                        <i class="fas fa-book text-primary me-1"></i> Mata Pelajaran
                    </th>
                    <th class="th-guru"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 180px;">
                        <i class="fas fa-chalkboard-user text-primary me-1"></i> Guru Pengampu
                    </th>
                    <th class="th-materi"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; min-width: 260px;">
                        <i class="fas fa-file-lines text-primary me-1"></i> Materi &amp; Kegiatan KBM
                    </th>
                    <th class="th-status"
                        style="padding: 13px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center; width: 140px;">
                        <i class="fas fa-signal text-primary me-1"></i> Status Agenda
                    </th>
                    <th class="th-aksi"
                        style="padding: 13px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; width: 150px;">
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
                                <div style="font-weight: 800; font-size: var(--text-base, 0.875rem); color: var(--primary);">
                                    Jam {{ $slot->jam_ke_mulai }}-{{ $slot->jam_ke_selesai }}
                                </div>
                                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                    {{ $slot->jam_waktu_range ?: 'Waktu reguler' }} &bull;
                                    {{ $slot->durasi_jp }} JP
                                </div>
                            </div>
                        </td>

                        <!-- Kolom Mata Pelajaran -->
                        <td class="cell-slot-mapel" data-label="Mata Pelajaran" style="padding: 14px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); line-height: 1.35;">
                                    {{ $slot->nama_mata_pelajaran }}
                                </div>
                                @if (!empty($slot->ruangan))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-door-open me-1"></i> Ruang: {{ $slot->ruangan }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        <!-- Kolom Guru Pengampu -->
                        <td class="cell-slot-guru" data-label="Guru Pengampu" style="padding: 14px 16px;">
                            <div class="cell-col-right">
                                <div
                                    style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color); display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-user-tie text-primary" style="font-size: var(--text-sm, 0.8125rem);"></i>
                                    <span>{{ $namaGuru }}</span>
                                </div>
                                @if ($isTerisi && !empty($agenda->created_by))
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-pen-nib me-1" style="font-size: var(--text-2xs, 0.6875rem);"></i>
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
                                        style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem); margin-bottom: 2px; line-height: 1.35;">
                                        <span class="badge"
                                            style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 2px 6px; font-weight: 800; margin-right: 4px;">
                                            #{{ $agenda->pertemuan_ke }}
                                        </span>
                                        {{ $agenda->materi_pokok }}
                                    </div>
                                    @if (!empty($agenda->uraian_kegiatan))
                                        <div
                                            style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                            {{ $agenda->uraian_kegiatan }}
                                        </div>
                                    @endif
                                    @if (!empty($agenda->penugasan))
                                        <div
                                            style="font-size: var(--text-xs, 0.75rem); color: var(--success, #10b981); margin-top: 3px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-tasks"></i>
                                            <span>{{ \Illuminate\Support\Str::limit($agenda->penugasan, 45) }}</span>
                                        </div>
                                    @endif
                                @else
                                    <div
                                        style="color: var(--text-muted); font-size: var(--text-sm, 0.8125rem); font-style: italic; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-hourglass-start text-warning"></i>
                                        <span>Agenda belum dicatat untuk jam pelajaran ini.</span>
                                    </div>
                                    <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-top: 2px;">
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
                                        style="font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fas fa-clock"></i> Belum Diisi
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Kolom Aksi -->
                        <td class="cell-slot-aksi" data-label="Aksi" style="padding: 14px 18px; text-align: right;">
                            <div class="table-actions"
                                style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                @if ($isTerisi)
                                    <button type="button" class="btn-icon btn-detail-agenda"
                                        data-id="{{ $agenda->id }}" title="Lihat Detail Agenda"
                                        style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px);">
                                        <i class="fas fa-eye"></i>
                                    </button>

                                    @if ($canUpdate)
                                        <button type="button" class="btn-icon btn-edit-agenda"
                                            data-id="{{ $agenda->id }}" title="Edit Agenda Kelas"
                                            style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); color: var(--warning, #f59e0b);">
                                            <i class="fas fa-pen-to-square"></i>
                                        </button>
                                    @endif

                                    @if ($canDelete)
                                        <button type="button" class="btn-icon btn-delete-agenda"
                                            data-id="{{ $agenda->id }}" title="Hapus Agenda"
                                            style="width: 32px; height: 32px; border-radius: var(--radius-sm, 6px); color: var(--danger, #ef4444);">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    @endif
                                @else
                                    @if ($canCreate)
                                        <button type="button" class="btn btn-primary btn-sm btn-bantu-isi-slot"
                                            data-jadwal-id="{{ $slot->id }}" data-ptk-id="{{ $slot->ptk_id }}"
                                            data-guru="{{ $namaGuru }}"
                                            data-mapel="{{ $slot->nama_mata_pelajaran }}"
                                            data-pembelajaran-id="{{ $slot->pembelajaran_id }}"
                                            data-mapel-id="{{ $slot->mata_pelajaran_id }}"
                                            data-hari="{{ $hari }}"
                                            data-jam-mulai="{{ $slot->jam_ke_mulai }}"
                                            data-jam-selesai="{{ $slot->jam_ke_selesai }}"
                                            data-pertemuan="{{ $slot->next_pertemuan }}"
                                            style="padding: 6px 12px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; border-radius: var(--radius-sm, 6px); display: inline-flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-plus"></i> <span>Isi Agenda</span>
                                        </button>
                                    @else
                                        <span class="text-muted" style="font-size: var(--text-sm, 0.8125rem);">-</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="cell-empty"
                            style="padding: 38px 20px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                            <i class="fas fa-calendar-xmark mb-2" style="font-size: var(--text-3xl, 1.875rem); opacity: 0.4;"></i>
                            <div style="font-weight: 800; color: var(--text-color); margin-bottom: 4px;">Tidak
                                Ada Jadwal KBM pada Hari {{ $hari }}</div>
                            <div style="font-size: var(--text-sm, 0.8125rem); max-width: 480px; margin: 0 auto;">
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
