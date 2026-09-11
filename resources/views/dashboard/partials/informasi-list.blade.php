@forelse ($items as $item)
    @php
        $isUnread = !$item->sudahDibacaOleh($userId ?? null);
    @endphp
    <article class="card"
        style="margin-bottom: 14px; border-left: 4px solid {{ $isUnread ? '#ef4444' : 'var(--primary)' }}; padding: 18px;">
        <div style="display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; flex-wrap: wrap;">
            <div>
                <div style="font-size: 0.76rem; color: var(--primary); font-weight: 700; margin-bottom: 4px;">
                    <i class="fas fa-bullhorn me-1"></i> {{ $item->penulis_nama ?: 'Administrator' }}
                </div>
                <h3 style="font-size: 1.05rem; font-weight: 800; color: var(--text-color); margin-bottom: 6px;">
                    {{ $item->judul }}
                </h3>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 10px;">
                    {{ $item->isi }}
                </p>
                <div style="font-size: 0.76rem; color: var(--text-muted);">
                    <i class="fas fa-clock me-1"></i>
                    {{ $item->created_at ? $item->created_at->format('d M Y H:i') : '-' }}
                    ({{ $item->created_at ? $item->created_at->diffForHumans() : '' }})
                </div>
            </div>
            @if ($isUnread)
                <span class="badge badge-danger">Baru</span>
            @endif
        </div>
    </article>
@empty
    <div style="padding: 36px 16px; text-align: center; color: var(--text-muted);">
        <i class="fas fa-bell-slash" style="font-size: 2rem; opacity: 0.45; margin-bottom: 8px;"></i>
        <div>Belum ada informasi.</div>
    </div>
@endforelse

