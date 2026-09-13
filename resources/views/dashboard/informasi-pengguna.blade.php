@extends('layouts.dashboard')

@section('title', 'Pusat Pengumuman & Informasi — SAE')
@section('dash_title', 'Pusat Pengumuman & Informasi')

@section('content')
    <!-- Banner Header -->
    <div class="dash-banner">
        <div>
            <h2 style="font-size: 1.35rem; font-weight: 800; color: var(--text-color); margin-bottom: 4px;">
                <i class="fas fa-bullhorn text-primary me-2"></i> Pusat Pengumuman &amp; Informasi
            </h2>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                Pemberitahuan resmi sekolah, agenda akademik, dan pengumuman sistem untuk
                <strong style="color: var(--primary); text-transform: capitalize;">{{ str_replace('_', ' ', $role) }}</strong>.
            </p>
        </div>
        <div class="dash-banner-actions" style="display: flex; gap: 8px; flex-wrap: wrap;">
            @if ($canManage)
                <a href="{{ route('dashboard.pengumuman.index') }}" class="btn btn-outline"
                    style="padding: 9px 16px; font-size: 0.85rem;">
                    <i class="fas fa-sliders me-1"></i> Kelola Pengumuman
                </a>
            @endif
            <form action="{{ route('dashboard.informasi.mark-all-read') }}" method="POST" id="formMarkAllRead" style="margin: 0; display: inline;">
                @csrf
                <button type="submit" class="btn btn-primary" id="btnMarkAllRead"
                    style="padding: 9px 18px; font-size: 0.85rem; font-weight: 600;"
                    {{ $unreadCount === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-check-double me-1"></i> Tandai Semua Dibaca
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $totalCount }}</div>
                <div class="dash-stat-label">Total Informasi Tersedia</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
                <i class="fas fa-bell"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value" id="statUnreadCount">{{ $unreadCount }}</div>
                <div class="dash-stat-label">Belum Dibaca</div>
            </div>
        </div>

        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                <i class="fas fa-clock-rotate-left"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ $recentCount }}</div>
                <div class="dash-stat-label">Terbit 7 Hari Terakhir</div>
            </div>
        </div>
    </div>

    <!-- Segmented Navigation Tabs (Desktop) -->
    <div class="dash-tabs-nav dash-desktop-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'semua', 'page' => 1]) }}"
            class="btn {{ $activeTab === 'semua' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-layer-group me-1"></i> Semua ({{ $countSemua }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'unread', 'page' => 1]) }}"
            class="btn {{ $activeTab === 'unread' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-bell me-1"></i> Belum Dibaca
            @if ($countUnread > 0)
                <span class="badge badge-danger ms-1" style="font-size: 0.68rem; padding: 2px 6px;">{{ $countUnread }}</span>
            @endif
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'umum', 'page' => 1]) }}"
            class="btn {{ $activeTab === 'umum' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-bullhorn me-1"></i> Pengumuman Sekolah ({{ $countUmum }})
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'sistem', 'page' => 1]) }}"
            class="btn {{ $activeTab === 'sistem' ? 'btn-primary' : 'btn-outline' }}">
            <i class="fas fa-server me-1"></i> Dari Sistem ({{ $countSistem }})
        </a>
    </div>

    <!-- Mobile Custom Dropdown Selector -->
    <div class="dash-mobile-tab-select-wrap">
        <div class="dash-custom-dropdown">
            <button type="button" class="custom-dropdown-trigger" aria-haspopup="listbox" aria-expanded="false">
                <div class="custom-dropdown-trigger-label">
                    <i class="fas fa-filter text-primary me-2"></i>
                    <span>
                        @if ($activeTab === 'unread')
                            Belum Dibaca ({{ $countUnread }})
                        @elseif($activeTab === 'umum')
                            Pengumuman Sekolah ({{ $countUmum }})
                        @elseif($activeTab === 'sistem')
                            Dari Sistem ({{ $countSistem }})
                        @else
                            Semua Informasi ({{ $countSemua }})
                        @endif
                    </span>
                </div>
                <i class="fas fa-chevron-down custom-dropdown-arrow"></i>
            </button>
            <div class="custom-dropdown-menu" role="listbox">
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'semua', 'page' => 1]) }}"
                    class="custom-dropdown-item {{ $activeTab === 'semua' ? 'active' : '' }}">
                    <span>Semua Informasi</span>
                    <span class="badge badge-outline badge-sm">{{ $countSemua }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'unread', 'page' => 1]) }}"
                    class="custom-dropdown-item {{ $activeTab === 'unread' ? 'active' : '' }}">
                    <span>Belum Dibaca</span>
                    <span class="badge {{ $countUnread > 0 ? 'badge-danger' : 'badge-outline' }} badge-sm">{{ $countUnread }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'umum', 'page' => 1]) }}"
                    class="custom-dropdown-item {{ $activeTab === 'umum' ? 'active' : '' }}">
                    <span>Pengumuman Sekolah</span>
                    <span class="badge badge-outline badge-sm">{{ $countUmum }}</span>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['tab' => 'sistem', 'page' => 1]) }}"
                    class="custom-dropdown-item {{ $activeTab === 'sistem' ? 'active' : '' }}">
                    <span>Dari Sistem</span>
                    <span class="badge badge-outline badge-sm">{{ $countSistem }}</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Search Toolbar -->
    <div class="toolbar-row">
        <div style="font-size: 0.84rem; color: var(--text-muted);">
            Menampilkan <strong style="color: var(--text-color);">{{ $items->total() }}</strong> informasi relevan
            @if ($q)
                untuk kata kunci &ldquo;<span style="color: var(--primary);">{{ $q }}</span>&rdquo;
            @endif
        </div>

        <div class="live-search-wrap">
            <form action="{{ route('dashboard.informasi.index') }}" method="GET" style="margin: 0;">
                @if ($activeTab !== 'semua')
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                @endif
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="q" placeholder="Cari judul atau isi pengumuman..." value="{{ $q }}"
                    autocomplete="off">
                @if ($q)
                    <a href="{{ request()->fullUrlWithQuery(['q' => null]) }}" class="clear-search visible"
                        title="Hapus pencarian">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>
    </div>

    <!-- Feed Container -->
    <div class="informasi-feed" style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
        @forelse ($items as $item)
            @php
                $isUnread = !$item->sudahDibacaOleh($userId);
                $isSystem = str_contains(strtolower($item->penulis_nama ?? ''), 'sistem');
            @endphp
            <article class="card card-informasi-item {{ $isUnread ? 'unread-item' : '' }}"
                id="item-pengumuman-{{ $item->id }}"
                style="margin-bottom: 0; padding: 20px; border-left: 4px solid {{ $isUnread ? '#ef4444' : 'var(--primary)' }}; transition: all 0.2s ease; position: relative;">

                <!-- Header Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span class="badge {{ $isSystem ? 'badge-accent' : 'badge-primary' }}"
                            style="font-size: 0.72rem; padding: 4px 9px; display: inline-flex; align-items: center; gap: 5px;">
                            <i class="fas {{ $isSystem ? 'fa-robot' : 'fa-user-pen' }}"></i>
                            <span>{{ $item->penulis_nama ?: 'Administrator' }}</span>
                        </span>

                        @if ($item->target_peran !== 'semua')
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 4px 8px;">
                                <i class="fas fa-user-tag me-1"></i> Sasaran: {{ ucfirst(str_replace('_', ' ', $item->target_peran)) }}
                            </span>
                        @else
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 4px 8px;">
                                <i class="fas fa-users me-1"></i> Semua Pengguna
                            </span>
                        @endif

                        @if ($item->target === 'semua')
                            <span class="badge badge-outline" style="font-size: 0.7rem; padding: 3px 6px; opacity: 0.8;" title="Tayang di teks berjalan & lonceng">
                                <i class="fas fa-bullhorn"></i> Publik &amp; Lonceng
                            </span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if ($isUnread)
                            <span class="badge badge-danger badge-unread-status" style="font-size: 0.72rem; padding: 4px 9px;">
                                <i class="fas fa-circle-dot me-1"></i> Baru
                            </span>
                        @else
                            <span class="badge badge-outline" style="font-size: 0.72rem; padding: 4px 8px; opacity: 0.7;">
                                <i class="fas fa-check me-1 text-success"></i> Sudah Dibaca
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Content Body -->
                <div style="margin-bottom: 14px;">
                    <h3 class="info-card-title btn-open-reader" data-id="{{ $item->id }}" data-item='@json($item)'
                        style="font-size: 1.08rem; font-weight: 800; color: var(--text-color); margin-bottom: 8px; line-height: 1.4; cursor: pointer;">
                        {{ $item->judul }}
                    </h3>
                    <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin: 0; white-space: pre-line;">
                        {{ Str::limit($item->isi, 240) }}
                    </p>
                </div>

                <!-- Footer Bar -->
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 12px; gap: 10px; flex-wrap: wrap;">
                    <div style="font-size: 0.76rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-clock text-primary"></i>
                        <span>{{ $item->created_at ? $item->created_at->diffForHumans() : '-' }}</span>
                        <span style="opacity: 0.5;">&bull;</span>
                        <span>{{ $item->created_at ? $item->created_at->format('d M Y, H:i') : '' }} WIB</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if ($isUnread)
                            <button type="button" class="btn btn-outline btn-mark-single-read"
                                data-id="{{ $item->id }}"
                                style="padding: 6px 12px; font-size: 0.78rem; border-color: rgba(16,185,129,0.3); color: #10b981;"
                                title="Tandai sudah dibaca">
                                <i class="fas fa-check me-1"></i> Tandai Dibaca
                            </button>
                        @endif
                        <button type="button" class="btn btn-primary btn-open-reader"
                            data-id="{{ $item->id }}" data-item='@json($item)'
                            style="padding: 6px 14px; font-size: 0.78rem; font-weight: 600;">
                            <i class="fas fa-book-open-reader me-1"></i> Baca Selengkapnya
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="card" style="padding: 48px 24px; text-align: center; color: var(--text-muted);">
                <i class="fas fa-bell-slash mb-3" style="font-size: 2.8rem; opacity: 0.35; color: var(--primary);"></i>
                <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--text-color); margin-bottom: 6px;">
                    Tidak Ada Informasi
                </h4>
                <p style="font-size: 0.85rem; max-width: 420px; margin: 0 auto 16px;">
                    @if ($q)
                        Tidak ditemukan pengumuman yang cocok dengan pencarian &ldquo;{{ $q }}&rdquo;.
                    @elseif($activeTab === 'unread')
                        Hebat! Anda telah membaca seluruh informasi dan pengumuman terbaru.
                    @else
                        Belum ada informasi yang dipublikasikan saat ini.
                    @endif
                </p>
                @if ($q || $activeTab !== 'semua')
                    <a href="{{ route('dashboard.informasi.index') }}" class="btn btn-outline" style="padding: 8px 18px; font-size: 0.82rem;">
                        <i class="fas fa-rotate-left me-1"></i> Reset Filter
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($items->hasPages())
        <div class="custom-pagination">
            @if ($items->onFirstPage())
                <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            @else
                <a href="{{ $items->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                        class="fas fa-chevron-left"></i></a>
            @endif

            @php
                $cur = $items->currentPage();
                $last = $items->lastPage();
                $from = max(1, $cur - 2);
                $to = min($last, $cur + 2);
            @endphp

            @if ($from > 1)
                <a href="{{ $items->url(1) }}" class="page-btn">1</a>
                @if ($from > 2)
                    <span class="page-info">&hellip;</span>
                @endif
            @endif

            @for ($i = $from; $i <= $to; $i++)
                <a href="{{ $items->url($i) }}"
                    class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
            @endfor

            @if ($to < $last)
                @if ($to < $last - 1)
                    <span class="page-info">&hellip;</span>
                @endif
                <a href="{{ $items->url($last) }}" class="page-btn">{{ $last }}</a>
            @endif

            @if ($items->hasMorePages())
                <a href="{{ $items->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                        class="fas fa-chevron-right"></i></a>
            @else
                <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    @endif

    <!-- Modal Reader (Pop-up Tampilan Baca Lengkap) -->
    <div id="userReaderModal" class="modal-backdrop">
        <div class="card" style="max-width: 620px; width: 92%; margin: auto; padding: 24px; border-radius: 16px; box-shadow: 0 16px 45px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 14px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span id="readerPenulisBadge" class="badge badge-primary" style="font-size: 0.72rem;"></span>
                        <span id="readerSasaranBadge" class="badge badge-outline" style="font-size: 0.72rem;"></span>
                    </div>
                    <h3 id="readerJudul" style="font-size: 1.2rem; font-weight: 800; color: var(--text-color); margin: 0; line-height: 1.35;"></h3>
                </div>
                <button type="button" id="btnCloseReaderModal"
                    style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.25rem; padding: 4px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div style="display: flex; align-items: center; gap: 12px; font-size: 0.78rem; color: var(--text-muted); margin-bottom: 16px;">
                <div><i class="fas fa-user-pen me-1 text-primary"></i> <span id="readerPenulis"></span></div>
                <span>&bull;</span>
                <div><i class="fas fa-clock me-1"></i> <span id="readerTanggal"></span></div>
            </div>

            <div id="readerIsi"
                style="font-size: 0.92rem; line-height: 1.7; color: var(--text-color); background: rgba(255,255,255,0.02); padding: 18px 20px; border-radius: 10px; border: 1px solid var(--border-color); margin-bottom: 22px; max-height: 55vh; overflow-y: auto; white-space: pre-line;">
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span class="badge badge-outline" style="font-size: 0.72rem; color: var(--text-muted);">
                    <i class="fas fa-check-circle me-1 text-success"></i> Ditandai sudah dibaca
                </span>
                <button type="button" id="btnCloseReaderBtn" class="btn btn-outline" style="padding: 8px 20px; font-size: 0.85rem;">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            window.PAGE_HIGHLIGHT_ID = @json($highlightId ?? null);
        </script>
        <script src="{{ asset('js/pengumuman.js') }}"></script>
    @endpush
@endsection

