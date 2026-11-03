@extends('layouts.app')

@section('title', 'Katalog Koleksi — Perpustakaan Digital')
@section('meta_description', 'Telusuri katalog koleksi Perpustakaan Digital sekolah: cari berdasarkan judul, penulis, penerbit, ISBN, atau kategori.')

@php
    $totalJudul = is_object($buku) && method_exists($buku, 'total') ? $buku->total() : $buku->count();
@endphp

@section('content')
    <div class="container">
        @if ($member)
            @include('perpustakaan.partials.member-nav', ['member' => $member, 'aktif' => 'katalog'])
        @else
            <div class="card-nisn" style="margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
                <div>
                    <div class="card-nisn-title" style="margin-bottom:4px;"><i class="fas fa-book-open text-primary"></i>
                        Katalog Publik</div>
                    <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);">
                        Masuk sebagai anggota untuk meminjam, mereservasi, dan memberi ulasan.
                    </div>
                </div>
                <a href="{{ route('perpustakaan.login') }}" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-right-to-bracket"></i> Masuk Anggota
                </a>
            </div>
        @endif

        {{-- ===== Hero Katalog ===== --}}
        <section class="hero" style="grid-template-columns:1fr;padding-top:.5rem;padding-bottom:clamp(1.25rem,3vw,2rem);">
            <div>
                <span class="hero-kicker"><i class="fas fa-magnifying-glass"></i> Katalog Koleksi</span>
                <h1 class="hero-title" style="font-size:clamp(1.5rem,4vw,2.25rem);">
                    Temukan <span>Bacaan Berikutnya</span>
                </h1>
                <p class="hero-desc">
                    {{ number_format($totalJudul, 0, ',', '.') }} judul tersedia dalam katalog.
                    Gunakan pencarian untuk menelusuri judul, penulis, penerbit, ISBN, atau nomor klasifikasi DDC.
                </p>
            </div>
        </section>

        {{-- ===== Filter ===== --}}
        <form method="GET" action="{{ route('perpustakaan.katalog') }}" class="filter-strip"
            style="margin-bottom:1.5rem;">
            <input type="text" name="q" value="{{ $q }}" class="filter-select"
                placeholder="Cari judul, penulis, penerbit, ISBN..." style="flex:3 1 260px;">
            <select name="kategori" class="filter-select" style="flex:1 1 160px;">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $opt)
                    <option value="{{ $opt }}" {{ $kategori === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
            </select>
            <select name="format" class="filter-select" style="flex:1 1 140px;">
                <option value="">Semua Format</option>
                <option value="fisik" {{ ($format ?? '') === 'fisik' ? 'selected' : '' }}>Buku Fisik</option>
                <option value="ebook" {{ ($format ?? '') === 'ebook' ? 'selected' : '' }}>E-Book Digital</option>
            </select>
            <label
                style="display:inline-flex;align-items:center;gap:7px;font-size:var(--text-sm);
                       color:var(--text-muted);white-space:nowrap;cursor:pointer;padding:0 .25rem;">
                <input type="checkbox" name="tersedia" value="1" {{ $hanyaTersedia ? 'checked' : '' }}
                    style="accent-color:var(--primary,#3b82f6);width:15px;height:15px;">
                Hanya tersedia
            </label>
            <button type="submit" class="btn btn-primary"
                style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">
                <i class="fas fa-magnifying-glass"></i> Cari
            </button>
            @if ($q !== '' || $kategori !== '' || !empty($format) || $hanyaTersedia)
                <a href="{{ route('perpustakaan.katalog') }}" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            @endif
        </form>

        {{-- ===== Grid Buku ===== --}}
        @if ($buku->isEmpty())
            <div class="chart-box" style="text-align:center;padding:3rem 1.5rem;">
                <i class="fas fa-book" style="font-size:var(--text-4xl);opacity:.3;display:block;margin-bottom:12px;"></i>
                <div style="color:var(--text-muted);font-size:var(--text-sm);">
                    Tidak ada koleksi yang cocok. Coba kata kunci lain.
                </div>
            </div>
        @else
            <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));">
                @foreach ($buku as $b)
                    @php
                        $tersedia = (int) ($b->eksemplar_tersedia ?? 0);
                        $isEb = ($b->format ?? 'fisik') === 'ebook' || !empty($b->tautan_baca) || !empty($b->file_ebook);
                        $theme = \App\Services\PerpustakaanEbookService::coverTheme($b->kategori ?? '', $b->judul ?? '');
                        $hasCover = !empty($b->cover_buku);
                    @endphp
                    <div class="service-card" style="gap:0;padding:0;overflow:hidden;">
                        {{-- Cover / Thumbnail Buku --}}
                        <div
                            style="height:150px;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;padding:12px 14px;background:{{ $hasCover ? 'var(--bg-card)' : $theme['gradient'] }};box-shadow:inset 5px 0 0 rgba(255,255,255,0.22);color:#fff;">
                            @if ($hasCover)
                                <img src="{{ asset('storage/' . ltrim($b->cover_buku, '/')) }}" alt="Cover {{ $b->judul }}"
                                    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                                <div style="position:absolute;inset:0;background:linear-gradient(to top, rgba(0,0,0,0.72) 0%, rgba(0,0,0,0.15) 100%);"></div>
                            @else
                                <i class="fas {{ $theme['icon'] }}"
                                    style="position:absolute;right:-10px;bottom:-12px;font-size:var(--text-5xl);opacity:0.2;pointer-events:none;"></i>
                            @endif

                            {{-- Badges Atas --}}
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;position:relative;z-index:2;">
                                @if ($isEb)
                                    <span
                                        style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(59,130,246,0.92);color:#fff;padding:3px 8px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                        <i class="fas fa-bolt me-1 text-warning"></i>E-BOOK
                                    </span>
                                    <span
                                        style="font-size:var(--text-2xs);font-weight:700;background:rgba(16,185,129,0.92);color:#fff;padding:3px 9px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                        Akses Daring
                                    </span>
                                @else
                                    <span
                                        style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(100,116,139,0.9);color:#fff;padding:3px 8px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                        <i class="fas fa-book me-1"></i>FISIK
                                    </span>
                                    <span
                                        style="font-size:var(--text-2xs);font-weight:700;background:{{ $tersedia > 0 ? 'rgba(16,185,129,0.92)' : 'rgba(239,68,68,0.92)' }};color:#fff;padding:3px 9px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                        {{ $tersedia > 0 ? $tersedia . ' Eks' : 'Habis' }}
                                    </span>
                                @endif
                            </div>

                            {{-- Label Kategori di bawah cover --}}
                            <div style="position:relative;z-index:2;">
                                <div style="font-size:var(--text-2xs);text-transform:uppercase;letter-spacing:0.05em;opacity:0.92;font-weight:700;text-shadow:0 1px 2px rgba(0,0,0,0.4);">
                                    <i class="fas {{ $theme['icon'] }} me-1"></i>{{ $b->kategori ?: 'Buku Pelajaran' }}
                                </div>
                            </div>
                        </div>

                        <div style="padding:1.1rem 1.25rem 1.25rem;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                            <div>
                                <h3 class="service-title" style="font-size:var(--text-base);line-height:1.35;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.7em;">
                                    {{ $b->judul }}
                                </h3>
                                <ul class="service-list" style="flex:1;">
                                    <li><i class="fas fa-user-pen text-primary me-2"></i>
                                        {{ \Illuminate\Support\Str::limit($b->penulis ?: 'Tanpa Penulis', 30) }}</li>
                                    <li><i class="fas fa-building text-accent me-2"></i>
                                        {{ \Illuminate\Support\Str::limit($b->penerbit ?: '-', 24) }}{{ $b->tahun_terbit ? ' (' . $b->tahun_terbit . ')' : '' }}
                                    </li>
                                    @if (!empty($b->kategori))
                                        <li><i class="fas fa-tag text-warning me-2"></i> {{ $b->kategori }}</li>
                                    @endif
                                </ul>
                            </div>
                            <div style="display:flex;gap:6px;margin-top:14px;">
                                @if ($isEb)
                                    <a href="{{ route('perpustakaan.buku.baca', $b->id) }}" class="btn btn-primary"
                                        style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:var(--text-xs);padding:7px 10px;">
                                        <i class="fas fa-book-open-reader"></i> Baca Online
                                    </a>
                                    <a href="{{ route('perpustakaan.buku.detail', $b->id) }}" class="btn btn-outline"
                                        style="display:inline-flex;align-items:center;justify-content:center;padding:7px 11px;font-size:var(--text-xs);" title="Detail Buku">
                                        <i class="fas fa-circle-info"></i>
                                    </a>
                                @else
                                    <a href="{{ route('perpustakaan.buku.detail', $b->id) }}" class="btn btn-outline"
                                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:7px;font-size:var(--text-xs);padding:7px 10px;">
                                        <i class="fas fa-circle-info"></i> Detail Buku
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1.75rem;">{{ $buku->links() }}</div>
        @endif
    </div>
@endsection
