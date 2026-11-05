@if (!empty($riwayatHarian) && count($riwayatHarian) > 0)
    <div class="table-responsive-stack" style="overflow-x: auto; width: 100%; margin: 0;">
        <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th
                        style="min-width: 140px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        Hari &amp; Tanggal</th>
                    <th
                        style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Jam Masuk</th>
                    <th
                        style="min-width: 110px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Jam Pulang</th>
                    <th
                        style="min-width: 100px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Metode</th>
                    <th
                        style="min-width: 120px; padding: 12px 18px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: center;">
                        Status Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($riwayatHarian as $rh)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td data-label="Hari &amp; Tanggal">
                            <div style="font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                                {{ $rh['tanggal'] }}
                            </div>
                        </td>
                        <td data-label="Jam Masuk"
                            style="text-align: center; font-weight: 700; color: var(--text-color); font-size: var(--text-base, 0.875rem);">
                            <i class="far fa-clock text-primary me-1"></i> {{ $rh['jam_masuk'] }}
                        </td>
                        <td data-label="Jam Pulang"
                            style="text-align: center; font-weight: 600; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                            <i class="far fa-clock text-warning me-1"></i> {{ $rh['jam_pulang'] }}
                        </td>
                        <td data-label="Metode" style="text-align: center;">
                            <span class="badge"
                                style="background: rgba(99,102,241,0.1); color: var(--primary); font-size: var(--text-xs, 0.75rem); padding: 3px 8px; border-radius: var(--radius-sm, 6px);">
                                {{ $rh['metode'] }}
                            </span>
                        </td>
                        <td data-label="Status" style="text-align: center;">
                            <span class="badge"
                                style="background: {{ $rh['badge_bg'] }}; color: {{ $rh['badge_color'] }}; font-size: var(--text-xs, 0.75rem); padding: 4px 10px; font-weight: 700; border-radius: var(--radius-sm, 6px);">
                                {{ $rh['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div
        style="padding: 32px 16px; text-align: center; color: var(--text-muted); border: 1px dashed var(--border-color); border-radius: var(--radius-lg, 12px);">
        <i class="fas fa-fingerprint"
            style="font-size: var(--text-3xl, 1.875rem); color: var(--text-muted); margin-bottom: 8px; display: block;"></i>
        <div style="font-weight: 700; font-size: var(--text-base, 0.875rem); color: var(--text-color);">
            Belum Ada Catatan Presensi Harian pada Bulan Ini
        </div>
    </div>
@endif
