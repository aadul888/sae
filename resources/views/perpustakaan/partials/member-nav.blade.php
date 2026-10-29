{{--
    Navigasi bersama area member Perpustakaan Digital.
    Memakai komponen & token SAE Design System (sama seperti halaman beranda).
    Parameter: $member (PerpusAnggota), $aktif (string: portal|katalog|notifikasi|profil)
--}}
@php
    $aktif = $aktif ?? '';
    $unread = \App\Services\PerpustakaanMemberService::notifikasiBelumDibaca($member);
    $levelNav = \App\Services\PerpustakaanMemberService::level($member);

    $menu = [
        ['key' => 'portal', 'label' => 'Beranda Anggota', 'icon' => 'fa-house-user', 'route' => 'perpustakaan.portal'],
        ['key' => 'katalog', 'label' => 'Katalog', 'icon' => 'fa-book-open', 'route' => 'perpustakaan.katalog'],
        ['key' => 'notifikasi', 'label' => 'Notifikasi', 'icon' => 'fa-bell', 'route' => 'perpustakaan.notifikasi'],
        ['key' => 'profil', 'label' => 'Profil', 'icon' => 'fa-user-gear', 'route' => 'perpustakaan.profil'],
    ];
@endphp

<div class="card-nisn" style="margin-bottom:1.5rem;padding:clamp(1rem,2.5vw,1.35rem);">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        {{-- Avatar + identitas --}}
        <div style="display:flex;align-items:center;gap:.85rem;flex:1 1 260px;min-width:0;">
            <div
                style="width:52px;height:52px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
                       font-weight:800;font-size:var(--text-md,1.05rem);letter-spacing:.5px;color:#fff;
                       background:linear-gradient(135deg,var(--primary,#3b82f6),var(--accent,#06b6d4));
                       box-shadow:0 6px 18px rgba(59,130,246,.35);">
                {{ $member->inisial() }}
            </div>
            <div style="min-width:0;">
                <div style="font-weight:800;font-size:var(--text-base,.9375rem);color:var(--text-main);
                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $member->nama }}
                </div>
                <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;
                            font-size:var(--text-xs,.75rem);color:var(--text-muted);margin-top:2px;">
                    <span style="font-family:var(--font-family-mono,monospace);letter-spacing:.6px;">
                        {{ $member->nomor_anggota }}
                    </span>
                    <span style="opacity:.45;">&bull;</span>
                    <span>{{ $member->tipeLabel() }}</span>
                    @if ($member->rombel_atau_unit)
                        <span style="opacity:.45;">&bull;</span>
                        <span>{{ $member->rombel_atau_unit }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Poin & level --}}
        <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
            <span class="tech-pill" style="border-color:{{ $levelNav['warna'] }}55;color:{{ $levelNav['warna'] }};">
                <i class="fas {{ $levelNav['icon'] }}"></i> {{ $levelNav['label'] }}
            </span>
            <span class="tech-pill" style="color:var(--warning,#f59e0b);border-color:rgba(245,158,11,.35);">
                <i class="fas fa-star"></i> {{ number_format($levelNav['poin'], 0, ',', '.') }} poin
            </span>
            <form method="POST" action="{{ route('perpustakaan.logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-outline"
                    style="display:inline-flex;align-items:center;gap:6px;padding:.5rem .85rem;font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);">
                    <i class="fas fa-right-from-bracket"></i> Keluar
                </button>
            </form>
        </div>
    </div>

    {{-- Menu --}}
    <div
        style="display:flex;gap:.4rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-glass);">
        @foreach ($menu as $m)
            @php $isActive = $aktif === $m['key']; @endphp
            <a href="{{ route($m['route']) }}"
                style="display:inline-flex;align-items:center;gap:7px;padding:.5rem .95rem;border-radius:var(--radius-pill,999px);
                       font-size:var(--text-sm,.8125rem);font-weight:700;text-decoration:none;position:relative;
                       transition:all .2s ease;
                       {{ $isActive
                           ? 'background:linear-gradient(135deg,var(--primary,#3b82f6),var(--accent,#06b6d4));color:#fff;box-shadow:0 4px 14px rgba(59,130,246,.35);'
                           : 'background:transparent;color:var(--text-muted);border:1px solid var(--border-glass);' }}">
                <i class="fas {{ $m['icon'] }}"></i> {{ $m['label'] }}
                @if ($m['key'] === 'notifikasi' && $unread > 0)
                    <span
                        style="background:var(--danger,#ef4444);color:#fff;font-size:var(--text-2xs,.625rem);font-weight:800;
                               min-width:17px;height:17px;border-radius:var(--radius-pill);display:inline-grid;place-items:center;padding:0 4px;">
                        {{ $unread > 99 ? '99+' : $unread }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success" style="margin-bottom:1.25rem;">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
    </div>
@endif
