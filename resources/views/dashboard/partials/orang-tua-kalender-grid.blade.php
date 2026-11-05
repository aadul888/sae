<div class="cal-table-wrap">
    <table class="cal-table">
        <thead>
            <tr>
                <th class="cal-th">Sen</th>
                <th class="cal-th">Sel</th>
                <th class="cal-th">Rab</th>
                <th class="cal-th">Kam</th>
                <th class="cal-th">Jum</th>
                <th class="cal-th" style="color: #3b82f6;">Sab</th>
                <th class="cal-th" style="color: var(--danger, #ef4444);">Min</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($calendarWeeks as $week)
                <tr>
                    @foreach ($week as $day)
                        @if (!$day)
                            <td style="padding: 0; background: transparent; border: none;"></td>
                        @else
                            <td style="padding: 0; vertical-align: top;">
                                <div class="cal-day-cell {{ $day['is_today'] ? 'cal-day-today' : '' }}"
                                    style="background: {{ $day['bg'] }}; border-color: {{ $day['border'] }};"
                                    data-day-info='@json($day)'
                                    data-has-izin-keluar="{{ !empty($day['has_izin_keluar']) ? '1' : '0' }}"
                                    data-has-surat-izin="{{ !empty($day['has_surat_izin']) ? '1' : '0' }}"
                                    data-has-kedisiplinan="{{ !empty($day['has_kedisiplinan']) ? '1' : '0' }}"
                                    data-has-mapel="{{ !empty($day['has_mapel']) ? '1' : '0' }}"
                                    data-has-log="{{ !empty($day['has_log']) ? '1' : '0' }}"
                                    data-code="{{ $day['code'] }}"
                                    title="{{ $day['date_formatted'] }}: {{ $day['label'] }} ({{ $day['sublabel'] }})">
                                    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                        <span class="cal-day-num" style="color: {{ $day['color'] }}; font-weight: 700;">
                                            {{ $day['day'] }}
                                        </span>
                                        @if ($day['is_today'])
                                            <span class="badge cal-today-badge"
                                                style="background: var(--primary); color: #fff; width: 6px; height: 6px; padding: 0; border-radius: 50%; display: inline-block;"
                                                title="Hari Ini"></span>
                                        @endif
                                    </div>

                                    @if ($day['code'] === 'FUTURE')
                                        {{-- Clean cell for future date: no clutter --}}
                                    @elseif ($day['code'] === 'L')
                                        <div class="cal-badge"
                                            style="background: {{ $day['bg'] }}; color: {{ $day['color'] }}; border: 1px solid {{ $day['border'] }};"
                                            title="{{ $day['sublabel'] ?: 'Libur' }}">
                                            <i class="{{ $day['icon'] }}"></i>
                                        </div>
                                    @else
                                        <div class="cal-badge"
                                            style="background: {{ $day['bg'] }}; color: {{ $day['color'] }}; border: 1px solid {{ $day['border'] }};"
                                            title="{{ $day['label'] }} ({{ $day['sublabel'] }})">
                                            <i class="{{ $day['icon'] }}"></i>
                                        </div>

                                        @if ($day['has_log'])
                                            <div class="cal-time" title="Jam Masuk: {{ $day['jam_masuk'] }}">
                                                <i class="far fa-clock"></i>
                                            </div>
                                        @endif
                                    @endif

                                    <!-- Indikator Transaksi Terkait (Icon Only, Minimalis Tanpa Teks) -->
                                    <div class="cal-indicators" style="display: flex; gap: 3px; flex-wrap: wrap; margin-top: 4px;">
                                        @if (!empty($day['has_izin_keluar']))
                                            <span class="badge cal-indicator-badge"
                                                style="background: rgba(245, 158, 11, 0.18); color: #d97706; font-size: 0.65rem; padding: 2px 4px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.35);"
                                                title="Ada Tiket e-Izin Keluar Sekolah">
                                                <i class="fas fa-ticket-alt"></i>
                                            </span>
                                        @endif
                                        @if (!empty($day['has_surat_izin']))
                                            <span class="badge cal-indicator-badge"
                                                style="background: rgba(99, 102, 241, 0.18); color: #6366f1; font-size: 0.65rem; padding: 2px 4px; border-radius: 4px; border: 1px solid rgba(99, 102, 241, 0.35);"
                                                title="Ada Surat Permohonan Izin / Sakit">
                                                <i class="fas fa-envelope-open-text"></i>
                                            </span>
                                        @endif
                                        @if (!empty($day['has_kedisiplinan']))
                                            <span class="badge cal-indicator-badge"
                                                style="background: rgba(239, 68, 68, 0.18); color: #ef4444; font-size: 0.65rem; padding: 2px 4px; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.35);"
                                                title="Ada Catatan Tata Tertib / Kedisiplinan">
                                                <i class="fas fa-triangle-exclamation"></i>
                                            </span>
                                        @endif
                                        @if (!empty($day['has_mapel']))
                                            <span class="badge cal-indicator-badge"
                                                style="background: rgba(16, 185, 129, 0.18); color: #10b981; font-size: 0.65rem; padding: 2px 4px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.35);"
                                                title="Ada Presensi Mata Pelajaran">
                                                <i class="fas fa-book-open"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Interactive Date Detail Box -->
<div id="detailPresensiBox" class="cal-detail-card">
    <div class="cal-detail-header" style="display: flex; align-items: center; gap: 12px; min-width: 200px;">
        <div id="detailIconBox"
            style="width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; background: rgba(16, 185, 129, 0.15); color: #10b981; flex-shrink: 0;">
            <i id="detailIcon" class="fas fa-calendar-day"></i>
        </div>
        <div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-weight: 600;">
                Detail Tanggal:
            </div>
            <div id="detailDate" style="font-size: var(--text-base, 0.875rem); font-weight: 800; color: var(--text-color);">
                {{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}
            </div>
        </div>
    </div>

    <div class="cal-detail-body" style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 2px;">
                Status:
            </div>
            <div id="detailStatusBadge">
                <span class="badge badge-success" style="font-weight: 700; padding: 3px 8px; font-size: 0.75rem;">-</span>
            </div>
        </div>

        <div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 2px;">
                Masuk:
            </div>
            <div id="detailJamMasuk" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">
                --:--
            </div>
        </div>

        <div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 2px;">
                Pulang:
            </div>
            <div id="detailJamPulang" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-color);">
                --:--
            </div>
        </div>

        <div>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 2px;">
                Metode:
            </div>
            <div id="detailMetode" style="font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--primary);">
                -
            </div>
        </div>

        <div style="max-width: 240px;">
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); margin-bottom: 2px;">
                Keterangan:
            </div>
            <div id="detailKeterangan"
                style="font-size: var(--text-sm, 0.8125rem); color: var(--text-color); font-weight: 600; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                -
            </div>
        </div>
    </div>

    <!-- Extra: Transaksi Terkait Tanggal (e-Izin Keluar, Surat Izin, Kedisiplinan) -->
    <div id="detailExtraBox" style="width: 100%; border-top: 1px dashed var(--border-color); padding-top: 10px; margin-top: 8px; display: none;">
        <div style="font-size: var(--text-xs, 0.75rem); font-weight: 800; color: var(--text-muted); margin-bottom: 6px;">
            <i class="fas fa-layer-group text-primary me-1"></i> Transaksi Terkait Tanggal Ini:
        </div>
        <div id="detailExtraContent" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
    </div>
</div>
