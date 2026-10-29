@extends('layouts.app')

@section('title', $buku->judul . ' — Katalog Perpustakaan Digital')
@section('meta_description', 'Detail koleksi "' . \Illuminate\Support\Str::limit($buku->judul, 120) . '" oleh ' . ($buku->penulis ?: 'penulis tidak diketahui') . ' — ketersediaan, lokasi rak, ulasan, dan reservasi.')

@php
    $tersedia = (int) ($buku->eksemplar_tersedia ?? 0);
    $totalEks = (int) ($buku->jumlah_eksemplar ?? 0);
    $dipinjam = max(0, $totalEks - $tersedia);
@endphp

@section('content')
    <div class="container">
        @if ($member)
            @include('perpustakaan.partials.member-nav', ['member' => $member, 'aktif' => 'katalog'])
        @endif

        {{-- Breadcrumb --}}
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;margin-bottom:1.25rem;
                    font-size:var(--text-xs,.75rem);color:var(--text-muted);">
            <a href="{{ route('perpustakaan.index') }}"
                style="color:var(--text-muted);text-decoration:none;">Perpustakaan</a>
            <i class="fas fa-chevron-right" style="opacity:.4;font-size:var(--text-2xs,.6rem);"></i>
            <a href="{{ route('perpustakaan.katalog') }}"
                style="color:var(--text-muted);text-decoration:none;">Katalog</a>
            <i class="fas fa-chevron-right" style="opacity:.4;font-size:var(--text-2xs,.6rem);"></i>
            <span style="color:var(--text-main);font-weight:600;">{{ \Illuminate\Support\Str::limit($buku->judul, 45) }}</span>
        </div>

        {{-- ===== Detail Utama ===== --}}
        <section class="hero" style="grid-template-columns:.85fr 1.15fr;align-items:start;padding-bottom:clamp(1.5rem,3vw,2rem);">
            {{-- Cover --}}
            <div class="card-nisn" style="padding:0;overflow:hidden;">
                <div
                    style="min-height:320px;display:grid;place-items:center;
                           background:linear-gradient(135deg,rgba(59,130,246,.18),rgba(6,182,212,.12));">
                    @if (!empty($buku->cover_buku))
                        <img src="{{ asset('storage/' . ltrim($buku->cover_buku, '/')) }}"
                            alt="Cover {{ $buku->judul }}" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <div style="text-align:center;padding:2rem;">
                            <i class="fas fa-book" style="font-size:var(--text-6xl,4rem);color:var(--primary);opacity:.35;"></i>
                            <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);margin-top:12px;">
                                Cover belum tersedia
                            </div>
                        </div>
                    @endif
                </div>
                <div style="padding:1.1rem 1.25rem;border-top:1px solid var(--border-glass);">
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;gap:10px;
                               font-size:var(--text-xs,.75rem);color:var(--text-muted);">
                        <span><i class="fas fa-copy me-1"></i>{{ $totalEks }} eksemplar</span>
                        <span><i class="fas fa-hand-holding-hand me-1"></i>{{ $dipinjam }} dipinjam</span>
                        <span style="color:{{ $tersedia > 0 ? 'var(--success,#10b981)' : 'var(--danger,#ef4444)' }};font-weight:800;">
                            <i class="fas fa-circle-check me-1"></i>{{ $tersedia }} tersedia
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info --}}
            <div>
                <span class="hero-kicker" style="margin-bottom:.6rem;">
                    <i class="fas fa-tag"></i> {{ $buku->kategori ?: 'Umum' }}
                </span>
                <h1 class="hero-title" style="font-size:clamp(1.4rem,3.5vw,2.05rem);line-height:1.25;margin-bottom:.6rem;">
                    {{ $buku->judul }}
                </h1>

                <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <span style="font-size:var(--text-sm,.8125rem);color:var(--text-muted);">
                        <i class="fas fa-user-pen me-1"></i> {{ $buku->penulis ?: 'Tanpa Penulis' }}
                    </span>
                    @if ($ratingRata)
                        <span
                            style="display:inline-flex;align-items:center;gap:5px;font-size:var(--text-sm,.8125rem);
                                   color:var(--warning,#f59e0b);font-weight:800;">
                            <i class="fas fa-star"></i> {{ $ratingRata }} / 5
                            <span style="color:var(--text-muted);font-weight:400;">({{ $ulasan->count() }} ulasan)</span>
                        </span>
                    @endif
                    @if ($buku->klasifikasi_ddc)
                        <span class="tech-pill"><i class="fas fa-hashtag"></i> DDC {{ $buku->klasifikasi_ddc }}</span>
                    @endif
                </div>

                {{-- Grid metadata --}}
                <div
                    style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.85rem;margin-bottom:1.25rem;">
                    @php
                        $meta = [
                            ['label' => 'Penerbit', 'value' => $buku->penerbit ?: '-', 'icon' => 'fa-building'],
                            ['label' => 'Tahun Terbit', 'value' => $buku->tahun_terbit ?: '-', 'icon' => 'fa-calendar'],
                            ['label' => 'ISBN', 'value' => $buku->isbn ?: '-', 'icon' => 'fa-barcode'],
                            ['label' => 'Lokasi Rak', 'value' => $buku->lokasi_rak ?: '-', 'icon' => 'fa-map-location-dot'],
                        ];
                    @endphp
                    @foreach ($meta as $m)
                        <div
                            style="background:var(--bg-card);border:1px solid var(--border-glass);
                                   border-radius:var(--radius-lg,12px);padding:.75rem .9rem;">
                            <div
                                style="font-size:var(--text-2xs,.6875rem);color:var(--text-muted);text-transform:uppercase;
                                       letter-spacing:.06em;font-weight:700;margin-bottom:3px;">
                                <i class="fas {{ $m['icon'] }} me-1"></i>{{ $m['label'] }}
                            </div>
                            <div style="font-size:var(--text-sm,.8125rem);font-weight:700;color:var(--text-main);
                                        word-break:break-word;">
                                {{ $m['value'] }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Aksi --}}
                <div style="display:flex;gap:.6rem;flex-wrap:wrap;">
                    @if ($member)
                        @if ($tersedia > 0)
                            <a href="{{ route('perpustakaan.katalog', ['tersedia' => 1]) }}" class="btn btn-primary"
                                style="display:inline-flex;align-items:center;gap:8px;">
                                <i class="fas fa-hand-holding-heart"></i> Pinjam di Meja Layanan
                            </a>
                        @else
                            <form method="POST" action="{{ route('perpustakaan.buku.reservasi', $buku->id) }}"
                                style="margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-primary"
                                    style="display:inline-flex;align-items:center;gap:8px;">
                                    <i class="fas fa-clock"></i> Reservasi / Antre
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('perpustakaan.login') }}" class="btn btn-primary"
                            style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-right-to-bracket"></i> Masuk untuk Meminjam
                        </a>
                    @endif

                    <a href="{{ route('perpustakaan.katalog') }}" class="btn btn-outline"
                        style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-arrow-left"></i> Kembali ke Katalog
                    </a>
                </div>

                @if ($member && $sudahMeminjam)
                    <div class="alert alert-info" style="margin-top:1rem;font-size:var(--text-xs,.75rem);">
                        <i class="fas fa-circle-check"></i> Anda pernah meminjam buku ini — silakan berikan ulasan di bawah.
                    </div>
                @endif
            </div>
        </section>

        {{-- ===== Ulasan ===== --}}
        <section class="analytics-card" style="padding:0;overflow:hidden;">
            <div class="analytics-head"
                style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="chart-box-title" style="margin:0;">
                    <i class="fas fa-comments me-2 text-accent"></i>Ulasan Pembaca
                </div>
                @if ($ratingRata)
                    <span class="badge badge-warning"><i class="fas fa-star"></i> {{ $ratingRata }} / 5</span>
                @endif
            </div>

            <div style="padding:1.25rem;">
                {{-- Form ulasan --}}
                @if ($member && $sudahMeminjam)
                    <form method="POST" action="{{ route('perpustakaan.buku.ulasan', $buku->id) }}"
                        style="margin-bottom:1.5rem;padding:1rem;border-radius:var(--radius-lg,12px);
                               background:rgba(59,130,246,.06);border:1px solid var(--border-glass);">
                        @csrf
                        <div class="chart-box-title" style="margin-bottom:.85rem;">
                            <i class="fas fa-pen-to-square me-2 text-primary"></i>
                            {{ $ulasanSaya ? 'Perbarui Ulasan Anda' : 'Tulis Ulasan Anda' }}
                        </div>
                        <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin-bottom:.85rem;">
                            <span style="font-size:var(--text-sm,.8125rem);color:var(--text-muted);font-weight:600;">Rating:</span>
                            <div style="display:flex;gap:4px;" id="ppRatingStars">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" data-value="{{ $i }}" aria-label="Beri rating {{ $i }} dari 5"
                                        style="background:none;border:none;cursor:pointer;padding:2px;font-size:var(--text-lg,1.15rem);
                                               color:{{ ($ulasanSaya->rating ?? 0) >= $i ? 'var(--warning,#f59e0b)' : 'var(--text-muted)' }};opacity:{{ ($ulasanSaya->rating ?? 0) >= $i ? '1' : '.3' }};">
                                        <i class="fas fa-star"></i>
                                    </button>
                                @endfor
                            </div>
                            <input type="hidden" name="rating" id="ppRatingValue" value="{{ $ulasanSaya->rating ?? 5 }}">
                            @error('rating')
                                <span style="font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="input-group" style="margin-bottom:.85rem;">
                            <label class="input-label" for="ppKomentar">Komentar (opsional)</label>
                            <textarea id="ppKomentar" name="komentar" class="input-field" rows="3"
                                placeholder="Bagikan kesan Anda tentang buku ini..."
                                style="resize:vertical;min-height:80px;">{{ old('komentar', $ulasanSaya->komentar ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"
                            style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-paper-plane"></i> {{ $ulasanSaya ? 'Perbarui Ulasan' : 'Kirim Ulasan' }}
                        </button>
                    </form>
                @elseif ($member)
                    <div class="alert alert-info" style="margin-bottom:1.25rem;font-size:var(--text-xs,.75rem);">
                        <i class="fas fa-circle-info"></i> Ulasan hanya dapat diberikan setelah Anda meminjam buku ini.
                    </div>
                @endif

                {{-- Daftar ulasan --}}
                @forelse ($ulasan as $u)
                    <div
                        style="padding:1rem 0;border-bottom:1px dashed var(--border-glass);display:flex;gap:.85rem;align-items:flex-start;">
                        <div
                            style="width:38px;height:38px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
                                   font-weight:800;font-size:var(--text-xs,.75rem);color:#fff;
                                   background:linear-gradient(135deg,var(--primary,#3b82f6),var(--accent,#06b6d4));">
                            {{ mb_strtoupper(mb_substr($u->nama_anggota ?? 'A', 0, 1)) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                                <span style="font-weight:700;font-size:var(--text-sm,.8125rem);color:var(--text-main);">
                                    {{ $u->nama_anggota ?? 'Anggota' }}
                                </span>
                                <span style="color:var(--warning,#f59e0b);font-size:var(--text-xs,.75rem);letter-spacing:1px;">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star" style="opacity:{{ $i <= $u->rating ? '1' : '.25' }};"></i>
                                    @endfor
                                </span>
                                <span style="font-size:var(--text-2xs,.6875rem);color:var(--text-muted);opacity:.8;">
                                    {{ \Carbon\Carbon::parse($u->created_at)->diffForHumans() }}
                                </span>
                            </div>
                            @if ($u->komentar)
                                <div style="font-size:var(--text-sm,.8125rem);color:var(--text-muted);margin-top:5px;line-height:1.6;">
                                    {{ $u->komentar }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                        <i class="fas fa-comment-slash"
                            style="font-size:var(--text-3xl,2rem);opacity:.3;display:block;margin-bottom:10px;"></i>
                        <div style="font-size:var(--text-sm,.8125rem);">Belum ada ulasan untuk buku ini.</div>
                    </div>
                @endforelse
            </div>
        </section>

        {{-- ===== Buku Serupa ===== --}}
        @if ($serupa->count())
            <section style="margin-top:2rem;">
                <div class="section-header" style="text-align:left;margin-bottom:1.1rem;">
                    <span class="section-kicker">Koleksi Serupa</span>
                    <h2 class="section-title" style="font-size:clamp(1.15rem,2.5vw,1.5rem);">Buku Lain di Kategori Ini</h2>
                </div>
                <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));margin-bottom:0;">
                    @foreach ($serupa as $s)
                        <a href="{{ route('perpustakaan.buku.detail', $s->id) }}" class="service-card"
                            style="gap:8px;text-decoration:none;">
                            <div>
                                <h3 class="service-title" style="font-size:var(--text-sm,.8125rem);">
                                    {{ \Illuminate\Support\Str::limit($s->judul, 48) }}
                                </h3>
                                <ul class="service-list">
                                    <li><i class="fas fa-user-pen text-primary me-2"></i>
                                        {{ \Illuminate\Support\Str::limit($s->penulis ?: '-', 26) }}</li>
                                    <li><i class="fas fa-circle-check text-success me-2"></i>
                                        {{ $s->eksemplar_tersedia }} tersedia</li>
                                </ul>
                            </div>
                            <span class="service-cta">Detail <i class="fas fa-chevron-right"></i></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            var wrap = document.getElementById('ppRatingStars');
            var input = document.getElementById('ppRatingValue');
            if (!wrap || !input) return;

            function paint(val) {
                wrap.querySelectorAll('button').forEach(function (b) {
                    var on = parseInt(b.dataset.value, 10) <= val;
                    b.style.color = on ? 'var(--warning,#f59e0b)' : 'var(--text-muted)';
                    b.style.opacity = on ? '1' : '.3';
                });
            }

            wrap.querySelectorAll('button').forEach(function (b) {
                b.addEventListener('click', function () {
                    input.value = b.dataset.value;
                    paint(parseInt(b.dataset.value, 10));
                });
                b.addEventListener('mouseenter', function () { paint(parseInt(b.dataset.value, 10)); });
            });
            wrap.addEventListener('mouseleave', function () { paint(parseInt(input.value, 10) || 0); });

            paint(parseInt(input.value, 10) || 0);
        })();
    </script>
@endsection
