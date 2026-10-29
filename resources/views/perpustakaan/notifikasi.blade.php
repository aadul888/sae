@extends('layouts.app')

@section('title', 'Notifikasi — ' . $member->nama . ' | Perpustakaan Digital')
@section('meta_description', 'Pusat notifikasi anggota Perpustakaan Digital: pengingat jatuh tempo, status reservasi, dan informasi layanan.')

@php
    $belumDibaca = $daftar->where('is_read', false)->count();
@endphp

@section('content')
    <div class="container">
        @include('perpustakaan.partials.member-nav', ['member' => $member, 'aktif' => 'notifikasi'])

        <section class="hero" style="grid-template-columns:1fr;padding-top:.5rem;padding-bottom:clamp(1rem,2.5vw,1.5rem);">
            <div>
                <span class="hero-kicker"><i class="fas fa-bell"></i> Pusat Notifikasi</span>
                <h1 class="hero-title" style="font-size:clamp(1.4rem,3.5vw,2rem);">
                    Kabar dari <span>Perpustakaan</span>
                </h1>
                <p class="hero-desc">
                    @if ($belumDibaca > 0)
                        Anda memiliki <strong>{{ $belumDibaca }} notifikasi belum dibaca</strong>.
                    @else
                        Semua notifikasi sudah Anda baca. Terima kasih telah menjaga ketertiban.
                    @endif
                </p>
                @if ($belumDibaca > 0)
                    <form method="POST" action="{{ route('perpustakaan.notifikasi.baca') }}" style="display:inline-block;">
                        @csrf
                        <button type="submit" class="btn btn-outline"
                            style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-check-double"></i> Tandai Semua Dibaca
                        </button>
                    </form>
                @endif
            </div>
        </section>

        <section class="analytics-card" style="padding:0;overflow:hidden;">
            <div class="analytics-head"
                style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="chart-box-title" style="margin:0;">
                    <i class="fas fa-inbox me-2 text-primary"></i>Riwayat Notifikasi
                </div>
                <span class="badge badge-outline">{{ $daftar->count() }} pesan</span>
            </div>

            <div style="padding:.5rem 1.25rem 1.25rem;">
                @forelse ($daftar as $n)
                    @php
                        $warna = match ($n->tipe) {
                            'success' => 'var(--success,#10b981)',
                            'warning' => 'var(--warning,#f59e0b)',
                            'danger'  => 'var(--danger,#ef4444)',
                            default   => 'var(--primary,#3b82f6)',
                        };
                    @endphp
                    <div
                        style="display:flex;gap:.9rem;align-items:flex-start;padding:1rem 0;
                               border-bottom:1px dashed var(--border-glass);
                               {{ $n->is_read ? 'opacity:.78;' : '' }}">
                        <div
                            style="width:38px;height:38px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
                                   background:{{ $warna }}1f;color:{{ $warna }};font-size:var(--text-base,.85rem);">
                            <i class="{{ $n->icon ?: 'fa-solid fa-bell' }}"></i>
                        </div>

                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:.55rem;flex-wrap:wrap;">
                                <span
                                    style="font-size:var(--text-sm,.8125rem);font-weight:{{ $n->is_read ? '600' : '800' }};color:var(--text-main);">
                                    {{ $n->judul }}
                                </span>
                                @if (!$n->is_read)
                                    <span
                                        style="font-size:var(--text-2xs,.6875rem);font-weight:800;text-transform:uppercase;
                                               letter-spacing:.05em;color:{{ $warna }};background:{{ $warna }}1f;
                                               padding:2px 7px;border-radius:var(--radius-pill,999px);">Baru</span>
                                @endif
                                <span
                                    style="font-size:var(--text-2xs,.6875rem);color:var(--text-muted);
                                           background:rgba(148,163,184,.14);padding:2px 7px;border-radius:var(--radius-pill,999px);">
                                    {{ $n->kategori }}
                                </span>
                            </div>

                            @if ($n->pesan)
                                <div style="font-size:var(--text-sm,.8125rem);color:var(--text-muted);margin-top:4px;line-height:1.6;">
                                    {{ $n->pesan }}
                                </div>
                            @endif

                            <div style="display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;margin-top:6px;">
                                <span style="font-size:var(--text-2xs,.6875rem);color:var(--text-muted);opacity:.8;">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ \Carbon\Carbon::parse($n->created_at)->translatedFormat('d M Y, H:i') }}
                                    &bull; {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                                </span>
                                @if (!empty($n->url))
                                    <a href="{{ $n->url }}"
                                        style="font-size:var(--text-2xs,.6875rem);color:var(--primary);font-weight:700;text-decoration:none;">
                                        Buka <i class="fas fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>

                        @if (!$n->is_read)
                            <form method="POST" action="{{ route('perpustakaan.notifikasi.baca.satu', $n->id) }}"
                                style="margin:0;flex-shrink:0;">
                                @csrf
                                <button type="submit" class="btn btn-outline" aria-label="Tandai notifikasi ini sudah dibaca"
                                    style="padding:.35rem .65rem;font-size:var(--text-xs,.75rem);">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
                        <i class="fas fa-bell-slash"
                            style="font-size:var(--text-4xl,2.5rem);opacity:.3;display:block;margin-bottom:14px;"></i>
                        <div style="font-size:var(--text-sm,.8125rem);">Belum ada notifikasi untuk Anda.</div>
                        <a href="{{ route('perpustakaan.katalog') }}"
                            style="font-size:var(--text-xs,.75rem);color:var(--primary);font-weight:700;text-decoration:none;">
                            Telusuri katalog <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
