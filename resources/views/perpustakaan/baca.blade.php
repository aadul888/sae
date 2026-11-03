@extends('layouts.app')

@section('title', 'Membaca: ' . $buku->judul . ' — Perpustakaan Digital')
@section('meta_description', 'Membaca buku digital ' . $buku->judul . ' resmi di Perpustakaan Digital.')

@section('content')
    @php
        $isPdf = \Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower((string) $urlBaca), ['.pdf']) || !empty($buku->file_ebook);
        $theme = \App\Services\PerpustakaanEbookService::coverTheme($buku->kategori ?? '', $buku->judul ?? '');
        $sumberLabel = \App\Services\PerpustakaanEbookService::labelSumber($buku->sumber_ebook ?? 'sibi');
    @endphp

    <div class="container" style="max-width:1200px;padding-top:1rem;padding-bottom:2rem;">
        {{-- Toolbar Pembaca --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:1rem;padding:12px 16px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg);">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <a href="{{ route('perpustakaan.buku.detail', $buku->id) }}" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);padding:6px 12px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <div style="min-width:0;">
                    <h1 style="margin:0;font-size:var(--text-sm);font-weight:800;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:480px;">
                        {{ $buku->judul }}
                    </h1>
                    <div style="font-size:var(--text-2xs);color:var(--text-muted);">
                        {{ $buku->penulis ?: 'Penyusun Resmi' }} &bull; E-Book Digital &bull; {{ $sumberLabel }}
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if ($member && !empty($hasil['poin']))
                    <span style="font-size:var(--text-xs);font-weight:700;color:var(--warning,#f59e0b);background:rgba(245,158,11,0.12);padding:5px 12px;border-radius:var(--radius-pill);display:inline-flex;align-items:center;gap:5px;">
                        <i class="fas fa-crown"></i> +{{ $hasil['poin'] }} Poin Literasi
                    </span>
                @endif
                <a href="{{ $urlBaca }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);padding:6px 14px;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Buka Layar Penuh
                </a>
            </div>
        </div>

        @if ($isPdf)
            {{-- Frame Pembaca Langsung Dokumen PDF --}}
            <div style="background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg);overflow:hidden;position:relative;height:78vh;min-height:540px;">
                <iframe src="{{ $urlBaca }}"
                    style="width:100%;height:100%;border:none;"
                    allow="fullscreen"
                    title="Pembaca E-Book: {{ $buku->judul }}">
                </iframe>
            </div>
        @else
            {{-- Stasiun Baca Digital untuk Repositori Resmi Terbuka --}}
            <div style="background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg);padding:clamp(1.5rem, 3vw, 2.5rem);margin-bottom:1rem;display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:24px;align-items:center;">
                {{-- Mockup Sampul Buku Digital --}}
                <div style="max-width:320px;margin:0 auto;width:100%;">
                    <div style="height:260px;background:{{ $theme['gradient'] }};border-radius:var(--radius-lg);padding:20px 22px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;box-shadow:inset 6px 0 0 rgba(255,255,255,0.25), 0 8px 24px rgba(0,0,0,0.15);color:#fff;">
                        <i class="fas {{ $theme['icon'] }}" style="position:absolute;right:-15px;bottom:-15px;font-size:var(--text-6xl);opacity:0.2;pointer-events:none;"></i>

                        <div style="display:flex;justify-content:space-between;align-items:flex-start;position:relative;z-index:2;">
                            <span style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(255,255,255,0.22);backdrop-filter:blur(4px);padding:3px 9px;border-radius:var(--radius-pill);">
                                <i class="fas fa-bolt text-warning me-1"></i>E-BOOK RESMI
                            </span>
                            <span style="font-size:var(--text-2xs);font-weight:700;background:rgba(255,255,255,0.92);color:#0f172a;padding:3px 9px;border-radius:var(--radius-pill);">
                                Kelas {{ $buku->kelas ?? 'Umum' }}
                            </span>
                        </div>

                        <div style="position:relative;z-index:2;">
                            <div style="width:52px;height:52px;border-radius:var(--radius-md);background:rgba(255,255,255,0.22);display:grid;place-items:center;font-size:var(--text-xl);margin-bottom:12px;">
                                <i class="fas {{ $theme['icon'] }}"></i>
                            </div>
                            <div style="font-size:var(--text-2xs);text-transform:uppercase;letter-spacing:0.06em;opacity:0.9;font-weight:700;">
                                {{ $theme['label'] }}
                            </div>
                            <h3 style="font-size:var(--text-base);font-weight:800;margin:4px 0 0;line-height:1.3;text-shadow:0 1px 3px rgba(0,0,0,0.3);">
                                {{ \Illuminate\Support\Str::limit($buku->judul, 65) }}
                            </h3>
                        </div>

                        <div style="display:flex;justify-content:space-between;font-size:var(--text-2xs);opacity:0.85;border-top:1px solid rgba(255,255,255,0.2);padding-top:8px;position:relative;z-index:2;">
                            <span>{{ $sumberLabel }}</span>
                            <span>{{ $buku->tahun_terbit ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Informasi & Akses Membaca --}}
                <div>
                    <div style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs);font-weight:700;color:var(--success,#10b981);background:rgba(16,185,129,0.12);padding:4px 10px;border-radius:var(--radius-pill);margin-bottom:10px;">
                        <i class="fas fa-circle-check"></i> Sumber Resmi Bebas Royalti Terverifikasi
                    </div>

                    <h2 style="font-size:var(--text-xl);font-weight:800;color:var(--text-main);margin:0 0 8px;line-height:1.3;">
                        {{ $buku->judul }}
                    </h2>

                    <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:14px;display:flex;flex-wrap:wrap;gap:12px;">
                        <span><i class="fas fa-user-pen text-primary me-1"></i> {{ $buku->penulis ?: 'Penyusun Resmi' }}</span>
                        <span><i class="fas fa-building text-accent me-1"></i> {{ $buku->penerbit ?: $sumberLabel }}</span>
                        <span><i class="fas fa-layer-group text-warning me-1"></i> {{ $buku->kategori ?: 'Pelajaran' }}</span>
                    </div>

                    @if (!empty($buku->sinopsis))
                        <p style="font-size:var(--text-xs);color:var(--text-muted);line-height:1.6;margin:0 0 16px;">
                            {{ $buku->sinopsis }}
                        </p>
                    @endif

                    <div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.18);border-radius:var(--radius-md);padding:12px 14px;margin-bottom:18px;">
                        <div style="font-size:var(--text-xs);font-weight:700;color:var(--primary);margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-info-circle"></i> Catatan Pembacaan Daring
                        </div>
                        <div style="font-size:var(--text-2xs);color:var(--text-muted);line-height:1.5;">
                            Portal repositori resmi <strong>{{ $sumberLabel }}</strong> memberlakukan kebijakan keamanan frame peramban (SAMEORIGIN). E-book dibuka langsung di portal resmi untuk kenyamanan membaca terbaik, dan poin literasi Anda tetap tercatat otomatis.
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <a href="{{ $urlBaca }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary"
                            style="font-size:var(--text-sm);font-weight:700;padding:10px 20px;display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-book-open-reader"></i> Buka E-Book di Tab Baru
                        </a>
                        <button type="button" class="btn btn-outline" onclick="toggleEmbed()"
                            style="font-size:var(--text-xs);padding:10px 14px;display:inline-flex;align-items:center;gap:6px;">
                            <i class="fas fa-display"></i> Coba Pratinjau Frame
                        </button>
                    </div>
                </div>
            </div>

            {{-- Kolaps Pratinjau Frame (Opsional) --}}
            <div id="embedContainer" style="display:none;margin-bottom:1rem;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg);overflow:hidden;height:70vh;min-height:500px;">
                <iframe src="{{ $urlBaca }}"
                    style="width:100%;height:100%;border:none;"
                    allow="fullscreen"
                    title="Pratinjau E-Book: {{ $buku->judul }}">
                </iframe>
            </div>
        @endif

        <div style="margin-top:10px;font-size:var(--text-xs);color:var(--text-muted);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <span><i class="fas fa-shield-halved text-success me-1"></i> Materi resmi repositori terbuka &bull; Bebas royalti pendidikan.</span>
            <span>Aktivitas literasi tercatat pada rekam jejak pemustaka sekolah.</span>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleEmbed() {
                var el = document.getElementById('embedContainer');
                if (el) {
                    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'block' : 'none';
                }
            }
        </script>
    @endpush
@endsection
