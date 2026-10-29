@extends('layouts.app')

@section('title', 'Beranda Anggota — ' . $member->nama . ' | Perpustakaan Digital')
@section('meta_description', 'Dashboard anggota Perpustakaan Digital: kartu anggota QR, poin literasi, pinjaman aktif, reservasi, dan rekomendasi buku.')

@php
    $unread = $statistik['notifikasi_unread'] ?? 0;
    $jatuhTempo = $statistik['jatuh_tempo'] ?? null;
    $sisaHari = $jatuhTempo ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($jatuhTempo->tgl_jatuh_tempo)->startOfDay(), false) : null;
@endphp

@section('content')
    <div class="container">
        @include('perpustakaan.partials.member-nav', ['member' => $member, 'aktif' => 'portal'])

        {{-- ===== HERO: Kartu Anggota Digital ===== --}}
        <section class="hero" style="grid-template-columns:1.05fr .95fr;padding-top:0;padding-bottom:clamp(1.5rem,3vw,2rem);">
            <div>
                <span class="hero-kicker"><i class="fas fa-id-card"></i> Kartu Anggota Digital</span>
                <h1 class="hero-title" style="font-size:clamp(1.5rem,4vw,2.25rem);">
                    Selamat datang,<br><span>{{ $member->nama }}</span>
                </h1>
                <p class="hero-desc" style="margin-bottom:1.25rem;">
                    @if ($statistik['pinjam_aktif'] > 0)
                        Anda sedang meminjam <strong>{{ $statistik['pinjam_aktif'] }} buku</strong>@if ($jatuhTempo),
                            jatuh tempo terdekat <strong>{{ \Carbon\Carbon::parse($jatuhTempo->tgl_jatuh_tempo)->translatedFormat('d F Y') }}</strong>@endif.
                    @else
                        Belum ada buku yang sedang dipinjam. Telusuri katalog dan temukan bacaan berikutnya.
                    @endif
                </p>

                <div class="hero-actions" style="margin-bottom:1rem;">
                    <a href="{{ route('perpustakaan.katalog') }}" class="btn btn-primary"
                        style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-book-open"></i> Telusuri Katalog
                    </a>
                    <form method="POST" action="{{ route('perpustakaan.checkin') }}" style="margin:0;">
                        @csrf
                        <button type="submit" class="btn btn-outline"
                            style="display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-location-dot"></i> Check-in Kunjungan
                        </button>
                    </form>
                    <a href="{{ $member->verifyUrl() }}" target="_blank" rel="noopener" class="btn btn-outline"
                        style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-shield-halved"></i> Verifikasi
                    </a>
                </div>

                <div class="hero-badges">
                    <span class="tech-pill"><i class="fas fa-star"></i>
                        {{ number_format($level['poin'], 0, ',', '.') }} Poin</span>
                    <span class="tech-pill" style="color:{{ $level['warna'] }};border-color:{{ $level['warna'] }}55;">
                        <i class="fas {{ $level['icon'] }}"></i> {{ $level['label'] }}</span>
                    <span class="tech-pill"><i class="fas fa-fire"></i> {{ $statistik['kunjungan'] }} Kunjungan</span>
                </div>
            </div>

            {{-- Kartu fisik digital --}}
            <div class="card-nisn" style="position:relative;overflow:hidden;">
                <div
                    style="position:absolute;inset:auto -40px -60px auto;width:180px;height:180px;border-radius:50%;
                           background:radial-gradient(circle,rgba(59,130,246,.28),transparent 70%);pointer-events:none;">
                </div>
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-address-card text-primary"></i> Kartu Anggota</span>
                    <span
                        style="font-size:var(--text-2xs,.6875rem);font-weight:800;text-transform:uppercase;letter-spacing:.06em;
                               color:var(--success,#10b981);background:rgba(16,185,129,.14);padding:3px 9px;border-radius:var(--radius-pill,999px);">
                        {{ $member->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>

                <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
                    <div
                        style="background:#fff;border-radius:var(--radius-lg,12px);padding:9px;line-height:0;
                               box-shadow:0 6px 20px rgba(0,0,0,.16);flex-shrink:0;">
                        {!! \App\Services\QrCodeService::generateSvg($member->verifyUrl(), 132, 1) !!}
                    </div>
                    <div style="flex:1;min-width:150px;">
                        <div
                            style="font-family:var(--font-family-mono,monospace);font-size:var(--text-base,.9375rem);
                                   font-weight:800;letter-spacing:1.4px;color:var(--primary);margin-bottom:.4rem;">
                            {{ $member->nomor_anggota }}
                        </div>
                        <div
                            style="display:flex;flex-direction:column;gap:.3rem;font-size:var(--text-xs,.75rem);color:var(--text-muted);">
                            <span><i class="fas fa-user-tag me-2"></i>{{ $member->tipeLabel() }}</span>
                            @if ($member->rombel_atau_unit)
                                <span><i class="fas fa-school me-2"></i>{{ $member->rombel_atau_unit }}</span>
                            @endif
                            <span><i class="fas fa-calendar-check me-2"></i>Berlaku s/d
                                {{ optional($member->tgl_kadaluarsa)->translatedFormat('d M Y') ?: 'Tidak ditentukan' }}</span>
                            <span><i class="fas fa-clock-rotate-left me-2"></i>Login terakhir
                                {{ optional($member->last_login_at)->diffForHumans() ?: 'Baru saja' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Progres level literasi --}}
                <div style="margin-top:1.1rem;padding-top:1rem;border-top:1px solid var(--border-glass);">
                    <div
                        style="display:flex;justify-content:space-between;align-items:center;gap:8px;
                               font-size:var(--text-xs,.75rem);margin-bottom:6px;">
                        <span style="color:var(--text-muted);font-weight:600;">
                            <i class="fas {{ $level['icon'] }} me-1" style="color:{{ $level['warna'] }};"></i>
                            {{ $level['label'] }}
                        </span>
                        <span style="color:var(--text-muted);">
                            @if ($level['next'])
                                {{ number_format($level['sisa'], 0, ',', '.') }} poin lagi → {{ $level['next_label'] }}
                            @else
                                Level tertinggi tercapai 🎉
                            @endif
                        </span>
                    </div>
                    <div
                        style="height:8px;border-radius:var(--radius-pill);background:rgba(148,163,184,.22);overflow:hidden;">
                        <div
                            style="height:100%;width:{{ $level['progress'] }}%;border-radius:var(--radius-pill);
                                   background:linear-gradient(90deg,{{ $level['warna'] }},var(--accent,#06b6d4));
                                   transition:width .8s cubic-bezier(.4,0,.2,1);">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ===== KPI ===== --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-book-reader"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $statistik['pinjam_aktif'] }}">0</div>
                    <div class="kpi-desc">Sedang Dipinjam</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-clock-rotate-left"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $statistik['total_kembali'] }}">0</div>
                    <div class="kpi-desc">Sudah Dikembalikan</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-bookmark"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $statistik['reservasi_aktif'] }}">0</div>
                    <div class="kpi-desc">Reservasi Aktif</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-star"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $level['poin'] }}">0</div>
                    <div class="kpi-desc">Poin Literasi</div>
                </div>
            </div>
        </section>

        {{-- ===== Peringatan jatuh tempo / denda ===== --}}
        @if ($statistik['terlambat'] > 0 || $statistik['denda'] > 0)
            <div class="alert alert-danger" style="margin-bottom:1.5rem;align-items:flex-start;">
                <i class="fas fa-triangle-exclamation" style="margin-top:2px;"></i>
                <div>
                    <strong>Perhatian!</strong>
                    @if ($statistik['terlambat'] > 0)
                        Ada {{ $statistik['terlambat'] }} buku melewati jatuh tempo.
                    @endif
                    @if ($statistik['denda'] > 0)
                        Denda belum lunas: <strong>Rp {{ number_format($statistik['denda'], 0, ',', '.') }}</strong>.
                    @endif
                    Segera kembalikan ke meja layanan perpustakaan.
                </div>
            </div>
        @endif

        {{-- ===== Pinjaman Aktif + Notifikasi ===== --}}
        <section class="charts-row" style="align-items:start;">
            {{-- Pinjaman aktif --}}
            <div class="chart-box" style="padding:0;overflow:hidden;">
                <div class="analytics-head"
                    style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div class="chart-box-title" style="margin:0;"><i
                            class="fas fa-book-open-reader me-2 text-primary"></i>Pinjaman Aktif</div>
                    <span class="badge badge-info">{{ $aktif->count() }} buku</span>
                </div>
                <div style="padding:1rem 1.25rem;">
                    @forelse ($aktif as $a)
                        @php
                            $jt = $a->tgl_jatuh_tempo ? \Carbon\Carbon::parse($a->tgl_jatuh_tempo) : null;
                            $sisa = $jt ? (int) now()->startOfDay()->diffInDays($jt->copy()->startOfDay(), false) : null;
                            $warna = $sisa === null ? 'var(--text-muted)' : ($sisa < 0 ? 'var(--danger,#ef4444)' : ($sisa <= 2 ? 'var(--warning,#f59e0b)' : 'var(--success,#10b981)'));
                        @endphp
                        <div
                            style="display:flex;gap:.9rem;align-items:flex-start;padding:.85rem 0;
                                   border-bottom:1px dashed var(--border-glass);">
                            <div
                                style="width:40px;height:52px;border-radius:var(--radius-sm,6px);flex-shrink:0;
                                       background:linear-gradient(135deg,rgba(59,130,246,.22),rgba(6,182,212,.16));
                                       display:grid;place-items:center;color:var(--primary);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;font-size:var(--text-sm,.8125rem);color:var(--text-main);
                                            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $a->judul_buku ?: 'Judul tidak tersedia' }}
                                </div>
                                <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);margin-top:2px;">
                                    {{ $a->penulis_buku ?: '-' }} &bull;
                                    <span style="font-family:var(--font-family-mono,monospace);">{{ $a->kode_transaksi }}</span>
                                </div>
                                <div style="font-size:var(--text-xs,.75rem);color:{{ $warna }};margin-top:4px;font-weight:700;">
                                    <i class="fas fa-clock me-1"></i>
                                    @if ($sisa === null)
                                        Jatuh tempo tidak ditentukan
                                    @elseif ($sisa < 0)
                                        Terlambat {{ abs($sisa) }} hari
                                    @elseif ($sisa === 0)
                                        Jatuh tempo HARI INI
                                    @else
                                        {{ $sisa }} hari lagi ({{ $jt->translatedFormat('d M Y') }})
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                            <i class="fas fa-mug-hot"
                                style="font-size:var(--text-3xl,1.9rem);opacity:.35;display:block;margin-bottom:10px;"></i>
                            <div style="font-size:var(--text-sm,.8125rem);">Tidak ada pinjaman aktif.</div>
                            <a href="{{ route('perpustakaan.katalog') }}"
                                style="font-size:var(--text-xs,.75rem);color:var(--primary);text-decoration:none;font-weight:700;">
                                Cari buku sekarang <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Notifikasi terbaru --}}
            <div class="chart-box" style="padding:0;overflow:hidden;">
                <div class="analytics-head"
                    style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div class="chart-box-title" style="margin:0;"><i
                            class="fas fa-bell me-2 text-warning"></i>Notifikasi</div>
                    <a href="{{ route('perpustakaan.notifikasi') }}"
                        style="font-size:var(--text-xs,.75rem);color:var(--primary);text-decoration:none;font-weight:700;">
                        Lihat semua
                        @if ($unread > 0)
                            ({{ $unread }})
                        @endif
                    </a>
                </div>
                <div style="padding:.5rem 1.25rem 1rem;">
                    @forelse ($notifikasi as $n)
                        @php
                            $warna = match ($n->tipe) {
                                'success' => 'var(--success,#10b981)',
                                'warning' => 'var(--warning,#f59e0b)',
                                'danger'  => 'var(--danger,#ef4444)',
                                default   => 'var(--primary,#3b82f6)',
                            };
                        @endphp
                        <div
                            style="display:flex;gap:.75rem;align-items:flex-start;padding:.7rem 0;border-bottom:1px dashed var(--border-glass);">
                            <div
                                style="width:32px;height:32px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;
                                       background:{{ $warna }}1f;color:{{ $warna }};font-size:var(--text-xs,.75rem);">
                                <i class="{{ $n->icon ?: 'fa-solid fa-bell' }}"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div
                                    style="font-size:var(--text-sm,.8125rem);font-weight:{{ $n->is_read ? '600' : '800' }};color:var(--text-main);">
                                    {{ $n->judul }}
                                    @if (!$n->is_read)
                                        <span
                                            style="display:inline-block;width:6px;height:6px;border-radius:50%;background:{{ $warna }};margin-left:5px;vertical-align:middle;"></span>
                                    @endif
                                </div>
                                @if ($n->pesan)
                                    <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);margin-top:2px;">
                                        {{ \Illuminate\Support\Str::limit($n->pesan, 90) }}
                                    </div>
                                @endif
                                <div style="font-size:var(--text-2xs,.6875rem);color:var(--text-muted);opacity:.75;margin-top:3px;">
                                    {{ \Carbon\Carbon::parse($n->created_at)->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center;padding:2rem 1rem;color:var(--text-muted);">
                            <i class="fas fa-bell-slash"
                                style="font-size:var(--text-3xl,1.9rem);opacity:.35;display:block;margin-bottom:10px;"></i>
                            <div style="font-size:var(--text-sm,.8125rem);">Belum ada notifikasi.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ===== Reservasi ===== --}}
        @if ($reservasi->whereIn('status', ['menunggu', 'siap'])->count())
            <section class="analytics-card" style="padding:0;overflow:hidden;">
                <div class="analytics-head"
                    style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);">
                    <div class="chart-box-title" style="margin:0;"><i
                            class="fas fa-hourglass-half me-2 text-accent"></i>Reservasi Saya</div>
                </div>
                <div style="overflow-x:auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Buku</th>
                                <th>Antrean</th>
                                <th>Status</th>
                                <th>Batas Ambil</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservasi->whereIn('status', ['menunggu', 'siap']) as $r)
                                <tr>
                                    <td>
                                        <div style="font-weight:700;">{{ $r->judul_buku ?: 'Buku #' . $r->buku_id }}</div>
                                        <div style="font-size:var(--text-xs,.75rem);color:var(--text-muted);">
                                            {{ $r->penulis_buku ?: '-' }}</div>
                                    </td>
                                    <td><span class="badge badge-outline">#{{ $r->antrean }}</span></td>
                                    <td>
                                        @if ($r->status === 'siap')
                                            <span class="badge badge-success">Siap Diambil</span>
                                        @else
                                            <span class="badge badge-warning">Menunggu</span>
                                        @endif
                                    </td>
                                    <td style="font-size:var(--text-sm,.8125rem);">
                                        {{ $r->expires_at ? \Carbon\Carbon::parse($r->expires_at)->translatedFormat('d M Y') : '-' }}
                                    </td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="{{ route('perpustakaan.reservasi.batal', $r->id) }}"
                                            style="margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-outline"
                                                style="padding:.35rem .7rem;font-size:var(--text-xs,.75rem);color:var(--danger,#ef4444);">
                                                <i class="fas fa-xmark"></i> Batalkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- ===== Rekomendasi Buku ===== --}}
        <section style="margin-top:2.5rem;">
            <div class="section-header" style="text-align:left;margin-bottom:1.25rem;">
                <span class="section-kicker">Untuk Anda</span>
                <h2 class="section-title" style="font-size:clamp(1.25rem,3vw,1.75rem);">Rekomendasi Bacaan</h2>
                <p class="section-desc" style="text-align:left;">Dipilih dari kategori yang paling sering Anda pinjam.</p>
            </div>

            @if ($rekomendasi->isEmpty())
                <div class="chart-box" style="text-align:center;padding:2.5rem 1.5rem;">
                    <i class="fas fa-book-open"
                        style="font-size:var(--text-4xl,2.2rem);opacity:.3;display:block;margin-bottom:12px;"></i>
                    <div style="color:var(--text-muted);font-size:var(--text-sm,.8125rem);">
                        Belum ada rekomendasi — pinjam buku pertama Anda untuk mulai.
                    </div>
                </div>
            @else
                <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr));">
                    @foreach ($rekomendasi as $b)
                        <a href="{{ route('perpustakaan.buku.detail', $b->id) }}" class="service-card"
                            style="gap:10px;text-decoration:none;">
                            <div>
                                <div
                                    style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
                                    <h3 class="service-title" style="flex:1;font-size:var(--text-sm,.8125rem);">
                                        {{ \Illuminate\Support\Str::limit($b->judul, 48) }}</h3>
                                </div>
                                <ul class="service-list">
                                    <li><i class="fas fa-user-pen text-primary me-2"></i>
                                        {{ \Illuminate\Support\Str::limit($b->penulis ?: 'Tanpa Penulis', 26) }}</li>
                                    <li><i class="fas fa-circle-check text-success me-2"></i>
                                        {{ $b->eksemplar_tersedia }} eksemplar tersedia</li>
                                </ul>
                            </div>
                            <span class="service-cta">Lihat Detail <i class="fas fa-chevron-right"></i></span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- ===== Riwayat Peminjaman ===== --}}
        <section class="analytics-card" style="padding:0;overflow:hidden;margin-top:1rem;">
            <div class="analytics-head"
                style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border-glass);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
                <div class="chart-box-title" style="margin:0;"><i
                        class="fas fa-clock-rotate-left me-2 text-primary"></i>Riwayat Peminjaman</div>
                <span class="badge badge-outline">{{ $riwayat->count() }} transaksi</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Judul Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Kembali</th>
                            <th>Status</th>
                            <th style="text-align:right;">Denda</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayat as $r)
                            <tr>
                                <td style="font-family:var(--font-family-mono,monospace);font-size:var(--text-xs,.75rem);">
                                    {{ $r->kode_transaksi }}</td>
                                <td style="font-weight:600;">{{ $r->judul_buku ?: '-' }}</td>
                                <td style="font-size:var(--text-sm,.8125rem);">
                                    {{ $r->tgl_pinjam ? \Carbon\Carbon::parse($r->tgl_pinjam)->format('d/m/Y') : '-' }}</td>
                                <td style="font-size:var(--text-sm,.8125rem);">
                                    {{ $r->tgl_jatuh_tempo ? \Carbon\Carbon::parse($r->tgl_jatuh_tempo)->format('d/m/Y') : '-' }}
                                </td>
                                <td style="font-size:var(--text-sm,.8125rem);">
                                    {{ $r->tgl_kembali ? \Carbon\Carbon::parse($r->tgl_kembali)->format('d/m/Y') : '-' }}</td>
                                <td>
                                    @if ($r->status === 'dipinjam')
                                        <span class="badge badge-warning">Dipinjam</span>
                                    @elseif ($r->status === 'kembali')
                                        <span class="badge badge-success">Kembali</span>
                                    @elseif ($r->status === 'hilang')
                                        <span class="badge badge-danger">Hilang</span>
                                    @else
                                        <span class="badge badge-outline">{{ ucfirst($r->status) }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right;font-size:var(--text-sm,.8125rem);
                                           font-weight:{{ ($r->denda ?? 0) > 0 ? '700' : '400' }};
                                           color:{{ ($r->denda ?? 0) > 0 ? 'var(--danger,#ef4444)' : 'var(--text-muted)' }};">
                                    {{ ($r->denda ?? 0) > 0 ? 'Rp ' . number_format($r->denda, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding:2.5rem;text-align:center;color:var(--text-muted);">
                                    <i class="fas fa-book"
                                        style="font-size:var(--text-3xl,2rem);opacity:.3;display:block;margin-bottom:10px;"></i>
                                    Belum ada riwayat peminjaman.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ===== Kunjungan terakhir ===== --}}
        @if ($kunjunganTerakhir->count())
            <section class="chart-box" style="margin-top:1rem;">
                <div class="chart-box-title"><i class="fas fa-location-dot me-2 text-accent"></i>Kunjungan Terakhir</div>
                <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.85rem;">
                    @foreach ($kunjunganTerakhir as $k)
                        <span class="tech-pill">
                            <i class="fas fa-calendar-day"></i>
                            {{ \Carbon\Carbon::parse($k->tanggal)->translatedFormat('d M Y') }}
                            @if ($k->jam_kunjung)
                                &bull; {{ \Carbon\Carbon::parse($k->jam_kunjung)->format('H:i') }}
                            @endif
                        </span>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            document.querySelectorAll('.kpi-num[data-count]').forEach(function (el) {
                var target = parseInt(el.dataset.count, 10) || 0;
                if (target === 0) { el.textContent = '0'; return; }
                var cur = 0, step = Math.max(1, Math.ceil(target / 40));
                var t = setInterval(function () {
                    cur += step;
                    if (cur >= target) { cur = target; clearInterval(t); }
                    el.textContent = cur.toLocaleString('id-ID');
                }, 28);
            });
        })();
    </script>
@endsection
