@extends('layouts.app')

@section('title', 'Warta & Berita Sekolah — SAE (Sistem Aplikasi Edukasi)')
@section('meta_description', 'Kumpulan berita resmi, agenda kegiatan, prestasi peserta didik, dan informasi akademik satuan pendidikan.')

@section('content')
    <div class="container" style="padding-top: var(--space-8); padding-bottom: var(--space-12);">
        <!-- Breadcrumb & Header Banner -->
        <div style="margin-bottom: var(--space-8);">
            <div style="display: flex; align-items: center; gap: var(--space-2); font-size: var(--text-xs); color: var(--text-muted); margin-bottom: var(--space-3);">
                <a href="{{ url('/') }}" style="color: var(--text-muted); text-decoration: none;"><i class="fas fa-house"></i> Beranda</a>
                <span>/</span>
                <span style="color: var(--primary); font-weight: 600;">Warta &amp; Berita</span>
            </div>

            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: var(--space-6);">
                <div>
                    <span class="section-kicker"><i class="fas fa-newspaper me-1"></i> Portal Publikasi Sekolah</span>
                    <h1 style="font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; color: var(--text-color); margin: 0 0 var(--space-2) 0; letter-spacing: -0.5px;">
                        Warta &amp; Berita Terkini
                    </h1>
                    <p style="color: var(--text-muted); font-size: var(--text-base); margin: 0; max-width: 600px; line-height: 1.5;">
                        Informasi resmi, agenda kegiatan sekolah, prestasi siswa, dan publikasi edukasi terpadu.
                    </p>
                </div>

                <!-- Form Pencarian Cepat -->
                <form action="{{ route('berita.index') }}" method="GET" style="display: flex; gap: var(--space-2); min-width: 280px; max-width: 380px; width: 100%;">
                    @if ($kategori)
                        <input type="hidden" name="kategori" value="{{ $kategori }}">
                    @endif
                    <div style="position: relative; flex: 1;">
                        <input type="text" name="q" value="{{ $q }}" placeholder="Cari judul atau topik berita..."
                            style="width: 100%; height: 42px; padding: 0 var(--space-8) 0 38px; border-radius: var(--radius-lg); border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); font-size: var(--text-sm); box-sizing: border-box;">
                        <i class="fas fa-search" style="position: absolute; left: 14px; top: 13px; color: var(--text-muted); font-size: var(--text-sm);"></i>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 var(--space-6); border-radius: var(--radius-lg); font-size: var(--text-sm); font-weight: 600;">
                        Cari
                    </button>
                    @if ($q || $kategori)
                        <a href="{{ route('berita.index') }}" class="btn btn-outline" style="height: 42px; width: 42px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-lg);" title="Reset filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Filter Kategori Kancing (Pills) -->
        <div style="display: flex; gap: var(--space-2); overflow-x: auto; padding-bottom: var(--space-4); margin-bottom: var(--space-8); scrollbar-width: none;">
            <a href="{{ route('berita.index', array_filter(['q' => $q])) }}"
                class="btn {{ empty($kategori) || $kategori === 'Semua' ? 'btn-primary' : 'btn-outline' }}"
                style="padding: 6px 14px; font-size: var(--text-xs); border-radius: var(--radius-pill); white-space: nowrap; text-decoration: none;">
                Semua Kategori
            </a>
            @php
                $allKategori = ['Akademik', 'Prestasi', 'Kegiatan', 'Pengumuman', 'Umum'];
                if (!empty($kategoriList)) {
                    $allKategori = array_unique(array_merge($allKategori, $kategoriList));
                }
            @endphp
            @foreach ($allKategori as $kat)
                <a href="{{ route('berita.index', array_filter(['q' => $q, 'kategori' => $kat])) }}"
                    class="btn {{ $kategori === $kat ? 'btn-primary' : 'btn-outline' }}"
                    style="padding: 6px 14px; font-size: var(--text-xs); border-radius: var(--radius-pill); white-space: nowrap; text-decoration: none;">
                    {{ $kat }}
                </a>
            @endforeach
        </div>

        <!-- Grid Kumpulan Berita Publik -->
        @if ($beritaList->count() > 0)
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: var(--space-8); margin-bottom: var(--space-10);">
                @foreach ($beritaList as $item)
                    <article class="card news-hero-card" style="display: flex; flex-direction: column; height: 100%;">
                        <a href="{{ route('berita.show', $item->slug) }}" class="news-hero-image-wrap" style="height: 200px; text-decoration: none;">
                            @if ($item->gambar)
                                <img src="{{ asset($item->gambar) }}" alt="{{ $item->judul }}" class="news-hero-img" loading="lazy">
                            @else
                                <div class="news-placeholder-banner">
                                    <i class="fas fa-newspaper news-placeholder-icon" style="font-size: 2.75rem;"></i>
                                    <span class="news-placeholder-tag">{{ $item->kategori }}</span>
                                </div>
                            @endif
                            <span class="news-badge-category">{{ $item->kategori }}</span>
                        </a>

                        <div class="news-hero-body" style="padding: var(--space-6); display: flex; flex-direction: column; flex: 1;">
                            <div class="news-meta-row" style="margin-bottom: var(--space-2);">
                                <span><i class="fas fa-calendar-day me-1"></i> {{ $item->created_at ? $item->created_at->translatedFormat('d M Y') : '-' }}</span>
                                <span>&bull;</span>
                                <span><i class="fas fa-user-pen me-1"></i> {{ $item->penulis_nama ?: 'Redaksi' }}</span>
                                <span>&bull;</span>
                                <span><i class="fas fa-eye me-1"></i> {{ number_format($item->views) }}</span>
                            </div>

                            <h3 style="font-size: var(--text-md); font-weight: 700; line-height: 1.4; margin: 0 0 var(--space-3) 0;">
                                <a href="{{ route('berita.show', $item->slug) }}" style="color: var(--text-color); text-decoration: none; transition: color 0.2s;">
                                    {{ $item->judul }}
                                </a>
                            </h3>

                            <p style="color: var(--text-muted); font-size: var(--text-xs); line-height: 1.5; margin: 0 0 var(--space-4) 0; flex: 1;">
                                {{ Str::limit($item->excerpt, 120) }}
                            </p>

                            <div style="margin-top: auto; padding-top: var(--space-4); border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                                <a href="{{ route('berita.show', $item->slug) }}" style="font-size: var(--text-xs); font-weight: 700; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: var(--space-1);">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <!-- Pagination -->
            @if ($beritaList->hasPages())
                <div style="display: flex; justify-content: center; margin-top: var(--space-8);">
                    {{ $beritaList->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="card" style="padding: var(--space-12); text-align: center; border-radius: var(--radius-xl);">
                <div style="width: 64px; height: 64px; margin: 0 auto var(--space-4); border-radius: var(--radius-pill); background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: var(--text-2xl);">
                    <i class="fas fa-newspaper"></i>
                </div>
                <h3 style="font-size: var(--text-lg); font-weight: 700; margin-bottom: var(--space-2); color: var(--text-color);">Belum Ada Berita yang Sesuai</h3>
                <p style="color: var(--text-muted); font-size: var(--text-sm); max-width: 420px; margin: 0 auto var(--space-6);">
                    Tidak ditemukan berita dengan kata kunci atau kategori yang Anda pilih. Coba gunakan kata kunci lain atau reset filter.
                </p>
                <a href="{{ route('berita.index') }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: var(--space-2);">
                    <i class="fas fa-undo"></i> Tampilkan Semua Berita
                </a>
            </div>
        @endif
    </div>
@endsection
