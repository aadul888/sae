@extends('layouts.app')

@section('title', $berita->judul . ' — SAE')
@section('meta_description', Str::limit(strip_tags($berita->excerpt), 150))

@section('content')
    <div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-12);">
        <!-- Breadcrumb -->
        <div style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-6); flex-wrap: wrap;">
            <a href="{{ url('/') }}" style="color: var(--text-muted); text-decoration: none;"><i class="fas fa-house"></i> Beranda</a>
            <span>/</span>
            <a href="{{ route('berita.index') }}" style="color: var(--text-muted); text-decoration: none;">Warta &amp; Berita</a>
            <span>/</span>
            <span style="color: var(--primary); font-weight: 600;">{{ $berita->kategori }}</span>
        </div>

        <div class="berita-show-layout" style="display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: var(--space-10); align-items: start;">
            <!-- Kolom Konten Utama Artikel -->
            <article class="card" style="padding: clamp(1.25rem, 3vw, 2.5rem); border-radius: var(--radius-xl); border: 1px solid var(--border-color); background: var(--card-bg);">
                <!-- Kategori & Meta -->
                <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4); flex-wrap: wrap;">
                    <span class="badge badge-accent" style="font-size: var(--text-xs); padding: 4px 10px; border-radius: var(--radius-pill); font-weight: 700;">
                        {{ $berita->kategori }}
                    </span>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        <i class="fas fa-calendar-day me-1"></i> {{ $berita->created_at ? $berita->created_at->translatedFormat('l, d F Y · H:i') : '-' }} WIB
                    </span>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">&bull;</span>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        <i class="fas fa-user-pen me-1"></i> {{ $berita->penulis_nama ?: 'Redaksi' }}
                    </span>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">&bull;</span>
                    <span style="font-size: var(--text-xs); color: var(--text-muted);">
                        <i class="fas fa-eye me-1"></i> {{ number_format($berita->views) }} tayangan
                    </span>
                </div>

                <!-- Judul Artikel -->
                <h1 style="font-size: clamp(1.5rem, 3.5vw, 2.25rem); font-weight: 800; line-height: 1.3; color: var(--text-color); margin: 0 0 var(--space-6) 0; letter-spacing: -0.5px;">
                    {{ $berita->judul }}
                </h1>

                <!-- Foto Utama (Cover) -->
                @if ($berita->gambar)
                    <div style="margin-bottom: var(--space-8); border-radius: var(--radius-lg); overflow: hidden; max-height: 440px; width: 100%; background: rgba(0,0,0,0.1);">
                        <img src="{{ asset($berita->gambar) }}" alt="{{ $berita->judul }}" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                    </div>
                @else
                    <div class="news-placeholder-banner" style="height: 240px; border-radius: var(--radius-lg); margin-bottom: var(--space-8);">
                        <i class="fas fa-newspaper news-placeholder-icon" style="font-size: 3.5rem;"></i>
                        <span class="news-placeholder-tag">{{ $berita->kategori }}</span>
                    </div>
                @endif

                <!-- Ringkasan / Lead Paragraph -->
                @if ($berita->ringkasan)
                    <div style="padding: var(--space-6); background: rgba(99, 102, 241, 0.06); border-left: 4px solid var(--primary); border-radius: var(--radius-md); margin-bottom: var(--space-8); font-size: var(--text-md); font-weight: 600; line-height: 1.6; color: var(--text-color);">
                        {{ $berita->ringkasan }}
                    </div>
                @endif

                <!-- Isi Artikel Lengkap -->
                <div class="news-article-content" style="margin-bottom: var(--space-10);">
                    {!! $berita->clean_content !!}
                </div>

                <!-- Bagian Bagikan & Navigasi -->
                <div style="border-top: 1px solid var(--border-color); padding-top: var(--space-6); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: var(--space-4);">
                    <div style="display: flex; align-items: center; gap: var(--space-3); flex-wrap: wrap;">
                        <span style="font-size: var(--text-xs); font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bagikan:</span>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($berita->judul . ' ' . url()->current()) }}" target="_blank" class="btn btn-outline" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-pill); color: #25d366;" title="Bagikan ke WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-pill); color: #1877f2;" title="Bagikan ke Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/" target="_blank" class="btn btn-outline" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-pill); color: #e4405f;" title="Bagikan ke Instagram (Salin Tautan &amp; Buka Instagram)" onclick="navigator.clipboard.writeText(window.location.href); if (window.SAE && window.SAE.toast) { window.SAE.toast('Tautan berita disalin! Anda dapat menempelkannya di Story / Bio Instagram.'); }">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($berita->judul) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-pill); color: #1da1f2;" title="Bagikan ke X/Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <button type="button" class="btn btn-outline" style="width: 36px; height: 36px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-pill);" title="Salin Tautan" onclick="navigator.clipboard.writeText(window.location.href); if (window.SAE && window.SAE.toast) { window.SAE.toast('Tautan berhasil disalin!'); } else { alert('Tautan berhasil disalin!'); }">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>

                    <a href="{{ route('berita.index') }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: var(--space-2); font-size: var(--text-xs);">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Berita
                    </a>
                </div>
            </article>

            <!-- Sidebar Berita Terkait & Lainnya -->
            <aside style="display: flex; flex-direction: column; gap: var(--space-6);">
                <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); border: 1px solid var(--border-color); background: var(--card-bg);">
                    <h3 style="font-size: var(--text-md); font-weight: 700; color: var(--text-color); margin: 0 0 var(--space-4) 0; padding-bottom: var(--space-3); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: var(--space-2);">
                        <i class="fas fa-newspaper text-primary"></i> Berita Terkait Lainnya
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: var(--space-4);">
                        @forelse ($beritaTerkait as $terkait)
                            <article style="display: flex; gap: var(--space-3); align-items: center;">
                                <a href="{{ route('berita.show', $terkait->slug) }}" style="width: 72px; height: 56px; border-radius: var(--radius-md); overflow: hidden; flex-shrink: 0; background: rgba(99,102,241,0.08); display: block;">
                                    @if ($terkait->gambar)
                                        <img src="{{ asset($terkait->gambar) }}" alt="{{ $terkait->judul }}" style="width: 100%; height: 100%; object-fit: cover;">
                                    @else
                                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-base); background: rgba(99,102,241,0.1);">
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    @endif
                                </a>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-bottom: 2px;">
                                        {{ $terkait->created_at ? $terkait->created_at->translatedFormat('d M Y') : '' }}
                                    </div>
                                    <h4 style="font-size: var(--text-xs); font-weight: 600; line-height: 1.3; margin: 0;">
                                        <a href="{{ route('berita.show', $terkait->slug) }}" style="color: var(--text-color); text-decoration: none; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                            {{ $terkait->judul }}
                                        </a>
                                    </h4>
                                </div>
                            </article>
                        @empty
                            <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0;">Belum ada berita terkait lainnya.</p>
                        @endforelse
                    </div>

                    <div style="margin-top: var(--space-6); text-align: center;">
                        <a href="{{ route('berita.index') }}" class="btn btn-outline" style="width: 100%; font-size: var(--text-xs); display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);">
                            Lihat Semua Warta <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Widget Layanan Cepat -->
                <div class="card" style="padding: var(--space-6); border-radius: var(--radius-xl); border: 1px solid var(--border-color); background: var(--card-bg);">
                    <h4 style="font-size: var(--text-sm); font-weight: 700; margin: 0 0 var(--space-3) 0;">Portal Akademik &amp; Layanan</h4>
                    <p style="font-size: var(--text-xs); color: var(--text-muted); margin: 0 0 var(--space-4) 0; line-height: 1.5;">
                        Akses modul presensi cerdas, buku tamu online, serta portal wali murid terpadu.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary" style="width: 100%; font-size: var(--text-xs); display: inline-flex; align-items: center; justify-content: center; gap: var(--space-2);">
                        <i class="fas fa-right-to-bracket"></i> Masuk Akun Portal
                    </a>
                </div>
            </aside>
        </div>
    </div>

    @push('styles')
        <style>
            @media (max-width: 900px) {
                div[style*="grid-template-columns: minmax(0, 1fr) 340px"] {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
    @endpush
@endsection
