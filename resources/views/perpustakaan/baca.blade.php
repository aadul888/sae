@extends('layouts.app')

@section('title', 'Membaca: ' . $buku->judul . ' — Perpustakaan Digital')
@section('meta_description', 'Membaca buku digital ' . $buku->judul . ' resmi di Perpustakaan Digital.')

@section('content')
    <div class="container" style="max-width:1200px;padding-top:1rem;padding-bottom:2rem;">
        {{-- Toolbar Pembaca --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:1rem;padding:12px 16px;background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg,12px);">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <a href="{{ route('perpustakaan.buku.detail', $buku->id) }}" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs,0.75rem);padding:6px 12px;">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <div style="min-width:0;">
                    <h1 style="margin:0;font-size:var(--text-sm,0.9rem);font-weight:800;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:480px;">
                        {{ $buku->judul }}
                    </h1>
                    <div style="font-size:var(--text-2xs,0.6875rem);color:var(--text-muted);">
                        {{ $buku->penulis ?: 'Kemendikbudristek' }} &bull; E-Book Digital
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                @if ($member && !empty($hasil['poin']))
                    <span style="font-size:var(--text-xs,0.75rem);font-weight:700;color:var(--warning,#f59e0b);background:rgba(245,158,11,0.12);padding:5px 10px;border-radius:var(--radius-pill,999px);display:inline-flex;align-items:center;gap:5px;">
                        <i class="fas fa-crown"></i> +{{ $hasil['poin'] }} Poin Literasi
                    </span>
                @endif
                <a href="{{ $urlBaca }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-xs,0.75rem);padding:6px 14px;">
                    <i class="fas fa-arrow-up-right-from-square"></i> Buka Layar Penuh
                </a>
            </div>
        </div>

        {{-- Frame Pembaca / Embed Reader --}}
        <div style="background:var(--bg-card);border:1px solid var(--border-glass);border-radius:var(--radius-lg,12px);overflow:hidden;position:relative;height:78vh;min-height:540px;">
            <iframe src="{{ $urlBaca }}"
                style="width:100%;height:100%;border:none;"
                allow="fullscreen"
                title="Pembaca E-Book: {{ $buku->judul }}">
            </iframe>
        </div>

        <div style="margin-top:10px;font-size:var(--text-xs,0.75rem);color:var(--text-muted);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <span><i class="fas fa-shield-halved text-success me-1"></i> Materi resmi Kemendikbudristek / Perpustakaan Sekolah Bebas Royalti.</span>
            <span>Jika tampilan dokumen tidak muncul di peramban Anda, silakan klik <strong>Buka Layar Penuh</strong>.</span>
        </div>
    </div>
@endsection
