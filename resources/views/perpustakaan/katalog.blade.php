@extends('layouts.app')

@section('title', 'Katalog Koleksi Ã¢â‚¬â€ Perpustakaan Digital')
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
            <label
                style="display:inline-flex;align-items:center;gap:7px;font-size:var(--text-sm,.8125rem);
                       color:var(--text-muted);white-space:nowrap;cursor:pointer;padding:0 .25rem;">
                <input type="checkbox" name="tersedia" value="1" {{ $hanyaTersedia ? 'checked' : '' }}
                    style="accent-color:var(--primary,#3b82f6);width:15px;height:15px;">
                Hanya tersedia
            </label>
            <button type="submit" class="btn btn-primary"
                style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">
                <i class="fas fa-magnifying-glass"></i> Cari
            </button>
            @if ($q !== '' || $kategori !== '' || $hanyaTersedia)
                <a href="{{ route('perpustakaan.katalog') }}" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            @endif
        </form>

        {{-- ===== Grid Buku ===== --}}
        @if ($buku->isEmpty())
            <div class="chart-box" style="text-align:center;padding:3rem 1.5rem;">
                <i class="fas fa-book" style="font-size:var(--text-4xl,2.5rem);opacity:.3;display:block;margin-bottom:12px;"></i>
                <div style="color:var(--text-muted);font-size:var(--text-sm,.8125rem);">
                    Tidak ada koleksi yang cocok. Coba kata kunci lain.
                </div>
            </div>
        @else
            <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));">
                @foreach ($buku as $b)
                    @php $tersedia = (int) ($b->eksemplar_tersedia ?? 0); @endphp
                    <div class="service-card" style="gap:12px;padding:0;overflow:hidden;">
                        {{-- Cover / placeholder --}}
                        <div
                            style="height:150px;display:grid;place-items:center;position:relative;
                                   background:linear-gradient(135deg,rgba(59,130,246,.16),rgba(6,182,212,.1));">
                            @if (!empty($b->cover_buku))
                                <img src="{{ asset('storage/' . ltrim($b->cover_buku, '/')) }}" alt="Cover {{ $b->judul }}"
                                    style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <i class="fas fa-book"
                                    style="font-size:var(--text-4xl,2.6rem);color:var(--primary);opacity:.45;"></i>
                            @endif
                            <span
                                style="position:absolute;top:9px;right:9px;font-size:var(--text-2xs,.6875rem);font-weight:800;
                                       padding:3px 9px;border-radius:var(--radius-pill,999px);letter-spacing:.03em;
                                       background:{{ $tersedia > 0 ? 'rgba(16,185,129,.92)' : 'rgba(239,68,68,.9)' }};color:#fff;">
                                {{ $tersedia > 0 ? $tersedia . ' tersedia' : 'Habis' }}
                            </span>
                        </div>

                        <div style="padding:0 1.25rem 1.25rem;flex:1;display:flex;flex-direction:column;">
                            <h3 class="service-title" style="font-size:var(--text-base,.9375rem);line-height:1.35;margin-bottom:6px;">
                                {{ \Illuminate\Support\Str::limit($b->judul, 60) }}
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
                            <a href="{{ route('perpustakaan.buku.detail', $b->id) }}" class="btn btn-outline"
                                style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:7px;
                                       margin-top:.85rem;font-size:var(--text-sm,.8125rem);">
                                <i class="fas fa-circle-info"></i> Detail Buku
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div style="margin-top:1.75rem;">{{ $buku->links() }}</div>
        @endif
    </div>
@endsection
