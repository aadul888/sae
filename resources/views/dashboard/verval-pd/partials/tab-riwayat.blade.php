{{-- TAB RIWAYAT: log aktivitas submodul Verval PD (sumber admin_aktivitas) --}}
<div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
    <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
        <thead>
            <tr style="border-bottom: 1px solid var(--border-color);">
                @foreach (['Waktu', 'Pengguna', 'Tipe', 'Aktivitas', 'Keterangan'] as $label)
                    <th
                        style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                        {{ $label }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($riwayatList as $log)
                @php $badge = $log->crud_badge; @endphp
                <tr style="border-bottom: 1px solid var(--border-color); transition: background 0.2s ease;">
                    <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);"
                        data-label="Waktu">
                        <div style="font-weight: 600;">
                            {{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}
                        </div>
                        <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                            {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                        </div>
                    </td>
                    <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem);" data-label="Pengguna">
                        <div style="font-weight: 600; color: var(--text-color);">
                            {{ $log->admin_name ?: 'Administrator' }}
                        </div>
                        <div
                            style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); font-family: var(--font-family-mono, monospace);">
                            {{ $log->admin_username ? '@' . $log->admin_username : '-' }}
                        </div>
                    </td>
                    <td style="padding: 12px 16px;" data-label="Tipe">
                        <span class="badge"
                            style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; font-weight: 700; padding: 3px 8px; border-radius: var(--radius-sm, 6px); font-size: var(--text-xs, 0.75rem); display: inline-flex; align-items: center; gap: 4px;">
                            <i class="fas {{ $badge['icon'] }}"></i>
                            <span>{{ $badge['label'] }}</span>
                        </span>
                    </td>
                    <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-color);"
                        data-label="Aktivitas">
                        {{ $log->aktivitas }}
                    </td>
                    <td style="padding: 12px 16px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);"
                        data-label="Keterangan">
                        {{ $log->keterangan ?: '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5"
                        style="padding: 30px; text-align: center; color: var(--text-muted); font-size: var(--text-base, 0.875rem);">
                        <i class="fas fa-clock-rotate-left mb-2"
                            style="font-size: var(--text-3xl, 1.875rem); opacity: 0.5;"></i>
                        <div>Belum ada riwayat aktivitas pada submodul ini.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
