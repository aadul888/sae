@extends('layouts.app')

@section('title', 'Perpustakaan Digital — ' . (\App\Models\Sekolah::first()?->nama ?? 'Sekolah'))
@section('meta_description', 'Katalog publik dan gerbang anggota Perpustakaan Digital sekolah — telusuri koleksi buku, pindai kartu anggota, dan pantau statistik literasi secara realtime.')

@section('content')
    <div class="container">
        {{-- ===== Info Ticker ===== --}}
        <div class="ticker-wrap">
            <div class="ticker-label"><i class="fas fa-bullhorn"></i> INFO</div>
            <div class="ticker-track">
                <div class="ticker-content">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
                <div class="ticker-content" aria-hidden="true">
                    @foreach ($running_info as $info)
                        <span class="ticker-item">{{ $info }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ===== Hero + Gerbang Scan ===== --}}
        <section class="hero">
            <div>
                <span class="hero-kicker"><i class="fas fa-book-open-reader"></i> Gerbang Literasi Digital Sekolah</span>
                <h1 class="hero-title">Perpustakaan<br><span>Digital Sekolah</span></h1>
                <p class="hero-desc">
                    Telusuri {{ number_format($stats['judul'], 0, ',', '.') }} judul koleksi dan
                    {{ number_format($stats['eksemplar'], 0, ',', '.') }} eksemplar bahan pustaka. Anggota dapat masuk
                    dengan memindai kartu anggota untuk melihat riwayat pinjam dan kartu digital.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('perpustakaan.login') }}" class="btn btn-primary"
                        style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-right-to-bracket"></i> Masuk Anggota
                    </a>
                    <a href="#katalog" class="btn btn-outline" style="display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-magnifying-glass"></i> Telusuri Katalog
                    </a>
                </div>
                <div class="hero-badges">
                    <span class="tech-pill"><i class="fas fa-qrcode"></i> Kartu Anggota Digital</span>
                    <span class="tech-pill"><i class="fas fa-chart-line"></i> Statistik Realtime</span>
                    <span class="tech-pill"><i class="fas fa-mobile-screen"></i> Akses Mandiri</span>
                </div>
            </div>

            {{-- Gerbang Scan QR (prioritas) --}}
            <div class="card-nisn">
                <div class="card-nisn-head">
                    <span class="card-nisn-title"><i class="fas fa-qrcode text-primary"></i> Gerbang Scan Anggota</span>
                    <span
                        style="font-size:var(--text-xs,0.75rem);color:var(--accent);background:rgba(6,182,212,0.1);padding:2px 8px;border-radius:var(--radius-sm,6px);">Live
                        Scan</span>
                </div>
                <p style="font-size:var(--text-sm,0.8125rem);color:var(--text-muted);margin-bottom:1rem;">
                    Pindai QR kartu anggota. Anggota yang sudah login SAE akan masuk otomatis tanpa scan ulang.
                </p>

                <form method="POST" action="{{ route('perpustakaan.login.post') }}" id="ppScanForm">
                    @csrf
                    <div class="input-group" style="position:relative;">
                        <label class="input-label" for="ppTokenInput">Kartu Anggota (QR / Token)</label>
                        <div style="position:relative;display:flex;align-items:center;">
                            <input type="text" id="ppTokenInput" name="token" class="input-field"
                                placeholder="Arahkan scanner ke QR kartu..." autocomplete="off" autofocus
                                style="font-family:var(--font-family-mono,monospace);letter-spacing:1.2px;font-weight:600;padding-right:36px;">
                            <span id="ppScanDot"
                                style="position:absolute;right:12px;width:9px;height:9px;border-radius:50%;background:var(--success,#10b981);box-shadow:0 0 8px var(--success,#10b981);"></span>
                        </div>
                        <div id="ppScanHint"
                            style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:6px;">
                            <i class="fas fa-circle-info"></i> Kolom siap menerima hasil pindai otomatis.
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"
                        style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin-top:14px;">
                        <i class="fas fa-qrcode"></i> Masuk via Kartu
                    </button>
                </form>

                <div
                    style="display:flex;align-items:center;gap:10px;margin:14px 0;color:var(--text-muted);font-size:var(--text-xs,0.75rem);">
                    <span style="flex:1;height:1px;background:var(--border-glass);"></span>
                    atau
                    <span style="flex:1;height:1px;background:var(--border-glass);"></span>
                </div>

                <a href="{{ route('perpustakaan.login') }}" class="btn btn-outline"
                    style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fas fa-keyboard"></i> Login NISN / NIP
                </a>

                @if (session('user'))
                    <div
                        style="margin-top:12px;font-size:var(--text-xs,0.75rem);color:var(--success,#10b981);display:flex;align-items:flex-start;gap:7px;">
                        <i class="fas fa-circle-check" style="margin-top:2px;"></i>
                        <span>Anda sudah login SAE. Bila terdaftar sebagai anggota, Anda akan masuk otomatis.</span>
                    </div>
                @endif

                @if ($bisaAktifkan)
                    <form method="POST" action="{{ route('perpustakaan.aktifkan') }}" style="margin-top:12px;">
                        @csrf
                        <button type="submit" class="btn btn-primary"
                            style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,var(--success,#10b981),var(--accent,#06b6d4));border:none;">
                            <i class="fas fa-id-card-clip"></i> Aktifkan Kartu Anggota
                        </button>
                        <div
                            style="font-size:var(--text-xs,0.75rem);color:var(--text-muted);margin-top:7px;text-align:center;">
                            Keanggotaan dibuat otomatis dari data {{ session('user')['role'] ?? 'SAE' }} Anda.
                        </div>
                    </form>
                @endif
            </div>
        </section>

        {{-- ===== Statistik KPI ===== --}}
        <section class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-book"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $stats['judul'] }}">0</div>
                    <div class="kpi-desc">Judul Buku</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon teal"><i class="fas fa-book-bookmark"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $stats['tersedia'] }}">0</div>
                    <div class="kpi-desc">Eksemplar Tersedia</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon orange"><i class="fas fa-hand-holding-hand"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $stats['dipinjam'] }}">0</div>
                    <div class="kpi-desc">Sedang Dipinjam</div>
                </div>
            </div>
            @if (!empty($stats['ebook']))
                <div class="kpi-card">
                    <div class="kpi-icon blue"><i class="fas fa-cloud-arrow-down"></i></div>
                    <div>
                        <div class="kpi-num" data-count="{{ $stats['ebook'] }}">0</div>
                        <div class="kpi-desc">E-Book Digital</div>
                    </div>
                </div>
            @endif
            <div class="kpi-card">
                <div class="kpi-icon purple"><i class="fas fa-id-card"></i></div>
                <div>
                    <div class="kpi-num" data-count="{{ $stats['anggota'] }}">0</div>
                    <div class="kpi-desc">Anggota Aktif</div>
                </div>
            </div>
        </section>

        {{-- ===== Grafik Statistik ===== --}}
        <section class="analytics-card">
            <div class="analytics-head">
                <h3 style="font-size:clamp(1.15rem,3vw,1.4rem);font-weight:800;margin-bottom:4px;">Statistik Literasi
                    Perpustakaan</h3>
                <p style="color:var(--text-muted);font-size:clamp(0.75rem,2vw,0.875rem);">Tren kunjungan &amp;
                    peminjaman enam bulan terakhir, serta sebaran kategori koleksi.</p>
            </div>

            <div class="charts-row">
                <div class="chart-box">
                    <div class="chart-box-title"><i class="fas fa-chart-line me-2 text-primary"></i>Tren Kunjungan &amp;
                        Peminjaman</div>
                    <div class="chart-container-inner"><canvas id="ppTrenChart"></canvas></div>
                </div>
                <div class="chart-box">
                    <div class="chart-box-title"><i class="fas fa-chart-pie me-2 text-accent"></i>Kategori Koleksi</div>
                    <div class="chart-container-inner"><canvas id="ppKategoriChart"></canvas></div>
                </div>
            </div>

            <div class="filter-strip" style="margin-top:1rem;">
                <div style="flex:1 1 200px;display:flex;align-items:center;gap:8px;font-size:var(--text-sm,0.8125rem);color:var(--text-muted);">
                    <i class="fas fa-users-line text-warning"></i>
                    <span>Kunjungan hari ini: <strong
                            style="color:var(--text-main);">{{ number_format($stats['kunjungan_hari'], 0, ',', '.') }}</strong>
                        orang</span>
                </div>
                <div style="flex:1 1 200px;display:flex;align-items:center;gap:8px;font-size:var(--text-sm,0.8125rem);color:var(--text-muted);">
                    <i class="fas fa-book-reader text-success"></i>
                    <span>Peminjaman hari ini: <strong
                            style="color:var(--text-main);">{{ number_format($stats['pinjam_hari'], 0, ',', '.') }}</strong>
                        transaksi</span>
                </div>
            </div>
        </section>

        {{-- ===== Layanan / Sorotan ===== --}}
        <section>
            <div class="section-header">
                <span class="section-kicker">Layanan Pustaka</span>
                <h2 class="section-title">Kemudahan Akses Untuk Semua</h2>
                <p class="section-desc">Nikmati layanan perpustakaan yang cepat, mandiri, dan terintegrasi.</p>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <div>
                        <div class="service-icon"><i class="fas fa-qrcode"></i></div>
                        <h3 class="service-title">Masuk Dengan Kartu Digital</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Pindai QR kartu anggota</li>
                            <li><i class="fas fa-check text-success me-2"></i> Tanpa perlu mengetik password</li>
                            <li><i class="fas fa-check text-success me-2"></i> Otomatis untuk anggota yang login SAE</li>
                        </ul>
                    </div>
                    <span class="service-cta">Scan Sekarang <i class="fas fa-chevron-right"></i></span>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--accent);"><i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <h3 class="service-title">Riwayat &amp; Kartu Anggota</h3>
                        <ul class="service-list">
                            <li><i class="fas fa-check text-success me-2"></i> Riwayat peminjaman pribadi</li>
                            <li><i class="fas fa-check text-success me-2"></i> Kartu anggota siap cetak</li>
                            <li><i class="fas fa-check text-success me-2"></i> Verifikasi keaslian via QR</li>
                        </ul>
                    </div>
                    <span class="service-cta">Buka Portal <i class="fas fa-chevron-right"></i></span>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--purple);"><i class="fas fa-crown"></i></div>
                        <h3 class="service-title">Buku Terpopuler</h3>
                        <ul class="service-list">
                            @forelse ($populer->take(3) as $p)
                                <li><i class="fas fa-book text-primary me-2"></i> {{ \Illuminate\Support\Str::limit($p->judul, 42) }}
                                    <span style="color:var(--text-muted);">({{ $p->total }}×)</span></li>
                            @empty
                                <li><i class="fas fa-minus text-muted me-2"></i> Belum ada data peminjaman</li>
                            @endforelse
                        </ul>
                    </div>
                    <span class="service-cta">Lihat Katalog <i class="fas fa-chevron-right"></i></span>
                </div>

                <div class="service-card">
                    <div>
                        <div class="service-icon" style="color:var(--success);"><i class="fas fa-sparkles"></i></div>
                        <h3 class="service-title">Koleksi Baru Masuk</h3>
                        <ul class="service-list">
                            @forelse ($terbaru->take(3) as $t)
                                <li><i class="fas fa-book-open text-accent me-2"></i> {{ \Illuminate\Support\Str::limit($t->judul, 42) }}</li>
                            @empty
                                <li><i class="fas fa-minus text-muted me-2"></i> Belum ada koleksi baru</li>
                            @endforelse
                        </ul>
                    </div>
                    <span class="service-cta">Selengkapnya <i class="fas fa-chevron-right"></i></span>
                </div>
            </div>
        </section>

        {{-- ===== Katalog ===== --}}
        <section id="katalog" style="margin-top:2.5rem;">
            <div class="section-header">
                <span class="section-kicker">Katalog Koleksi</span>
                <h2 class="section-title">Telusuri Buku Perpustakaan</h2>
                <p class="section-desc">{{ number_format($totalJudul, 0, ',', '.') }} judul &bull;
                    {{ number_format($totalEksemplar, 0, ',', '.') }} eksemplar dalam koleksi sekolah.</p>
            </div>

            <form method="GET" action="{{ route('perpustakaan.index') }}" class="filter-strip"
                style="margin-bottom:1.5rem;">
                <input type="text" name="q" value="{{ $q }}" class="filter-select"
                    placeholder="Cari judul, penulis, penerbit, atau ISBN..." style="flex:2 1 240px;">
                <select name="kategori" class="filter-select" style="flex:1 1 150px;">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriList as $opt)
                        <option value="{{ $opt }}" {{ $kategori === $opt ? 'selected' : '' }}>{{ $opt }}
                        </option>
                    @endforeach
                </select>
                <select name="format" class="filter-select" style="flex:1 1 130px;">
                    <option value="">Semua Format</option>
                    <option value="fisik" {{ request('format') === 'fisik' ? 'selected' : '' }}>Buku Fisik</option>
                    <option value="ebook" {{ request('format') === 'ebook' ? 'selected' : '' }}>E-Book Digital</option>
                </select>
                <button type="submit" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">
                    <i class="fas fa-magnifying-glass"></i> Cari
                </button>
            </form>

            @if ($buku->isEmpty())
                <div class="chart-box" style="text-align:center;padding:3rem 1.5rem;">
                    <i class="fas fa-book"
                        style="font-size:var(--text-4xl);opacity:.35;display:block;margin-bottom:12px;"></i>
                    <div style="color:var(--text-muted);">Belum ada koleksi yang cocok dengan pencarian Anda.</div>
                </div>
            @else
                <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:20px;">
                    @foreach ($buku as $b)
                        @php
                            $tersedia = (int) ($b->eksemplar_tersedia ?? 0);
                            $isEb = ($b->format ?? 'fisik') === 'ebook' || !empty($b->tautan_baca) || !empty($b->file_ebook);
                            $theme = \App\Services\PerpustakaanEbookService::coverTheme($b->kategori ?? '', $b->judul ?? '');
                            $hasCover = !empty($b->cover_buku);
                        @endphp
                        <div class="service-card" style="padding:0;overflow:hidden;gap:0;">
                            {{-- Cover / Thumbnail Buku --}}
                            <div style="height:148px;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:space-between;padding:12px 14px;background:{{ $hasCover ? 'var(--bg-card)' : $theme['gradient'] }};box-shadow:inset 5px 0 0 rgba(255,255,255,0.22);color:#fff;">
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
                                        <span style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(59,130,246,0.92);color:#fff;padding:3px 8px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                            <i class="fas fa-bolt me-1 text-warning"></i>E-BOOK
                                        </span>
                                        <span style="font-size:var(--text-2xs);font-weight:700;background:rgba(16,185,129,0.92);color:#fff;padding:3px 9px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                            Akses Daring
                                        </span>
                                    @else
                                        <span style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(100,116,139,0.9);color:#fff;padding:3px 8px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
                                            <i class="fas fa-book me-1"></i>FISIK
                                        </span>
                                        <span style="font-size:var(--text-2xs);font-weight:700;background:{{ $tersedia > 0 ? 'rgba(16,185,129,0.92)' : 'rgba(239,68,68,0.92)' }};color:#fff;padding:3px 9px;border-radius:var(--radius-pill);box-shadow:0 1px 3px rgba(0,0,0,0.2);">
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

                            {{-- Isi Kartu --}}
                            <div style="padding:1.1rem 1.25rem 1.25rem;flex:1;display:flex;flex-direction:column;justify-content:space-between;">
                                <div>
                                    <h3 class="service-title" style="font-size:var(--text-base);line-height:1.35;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.7em;">
                                        {{ $b->judul }}
                                    </h3>
                                    <ul class="service-list" style="margin-top:8px;">
                                        <li>
                                            <i class="fas fa-user-pen text-primary me-2"></i>
                                            {{ \Illuminate\Support\Str::limit($b->penulis ?: 'Tanpa Penulis', 32) }}
                                        </li>
                                        <li>
                                            <i class="fas fa-building text-accent me-2"></i>
                                            {{ \Illuminate\Support\Str::limit($b->penerbit ?: '-', 26) }}{{ $b->tahun_terbit ? ' (' . $b->tahun_terbit . ')' : '' }}
                                        </li>
                                        <li>
                                            @if ($isEb)
                                                <i class="fas fa-infinity text-success me-2"></i> Bebas Dibaca Kapan Saja
                                            @elseif ($tersedia > 0)
                                                <i class="fas fa-circle-check text-success me-2"></i> Tersedia di rak perpustakaan
                                            @else
                                                <i class="fas fa-circle-xmark text-danger me-2"></i> Sedang dipinjam pemustaka
                                            @endif
                                        </li>
                                    </ul>
                                </div>

                                <div style="margin-top:14px;display:flex;gap:6px;">
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
                                            style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-size:var(--text-xs);padding:7px 10px;">
                                            <i class="fas fa-circle-info"></i> Detail Buku
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="margin-top:1.5rem;">{{ $buku->links() }}</div>
            @endif
        </section>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script>
        (function () {
            // Count-up KPI
            document.querySelectorAll('.kpi-num[data-count]').forEach(function (el) {
                var target = parseInt(el.dataset.count, 10) || 0;
                var cur = 0, step = Math.max(1, Math.ceil(target / 40));
                var t = setInterval(function () {
                    cur += step;
                    if (cur >= target) { cur = target; clearInterval(t); }
                    el.textContent = cur.toLocaleString('id-ID');
                }, 30);
            });

            if (typeof Chart === 'undefined') return;
            var tren = @json($tren);
            var kategori = @json($kategoriChart);
            var css = getComputedStyle(document.documentElement);
            var textColor = css.getPropertyValue('--text-muted').trim() || '#64748b';
            var gridColor = css.getPropertyValue('--border-glass').trim() || 'rgba(148,163,184,.25)';
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = gridColor;

            new Chart(document.getElementById('ppTrenChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: tren.labels,
                    datasets: [
                        { label: 'Kunjungan', data: tren.kunjungan, borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,.15)', fill: true, tension: .35, pointRadius: 3 },
                        { label: 'Peminjaman', data: tren.pinjam, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.15)', fill: true, tension: .35, pointRadius: 3 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
            });

            if (kategori.length) {
                new Chart(document.getElementById('ppKategoriChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: kategori.map(function (k) { return k.kategori || 'Umum'; }),
                        datasets: [{
                            data: kategori.map(function (k) { return k.total; }),
                            backgroundColor: ['#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
                            borderWidth: 0
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } }, cutout: '62%' }
                });
            }
        })();

        // Gerbang scan: auto-focus + auto-submit saat scanner mengisi (burst)
        (function () {
            var input = document.getElementById('ppTokenInput');
            var form = document.getElementById('ppScanForm');
            var hint = document.getElementById('ppScanHint');
            if (!input || !form) return;

            setInterval(function () {
                if (document.activeElement !== input && document.activeElement.tagName !== 'SELECT') input.focus();
            }, 2500);

            var timer = null;
            input.addEventListener('input', function () {
                clearTimeout(timer);
                hint.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membaca kartu...';
                timer = setTimeout(function () {
                    if (input.value.trim().length >= 16) { form.submit(); }
                    else { hint.innerHTML = '<i class="fas fa-circle-info"></i> Kolom siap menerima hasil pindai otomatis.'; }
                }, 200);
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
            });
        })();
    </script>
@endsection
