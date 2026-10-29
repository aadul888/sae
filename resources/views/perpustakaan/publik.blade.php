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
                    placeholder="Cari judul, penulis, penerbit, atau ISBN..." style="flex:2 1 260px;">
                <select name="kategori" class="filter-select">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriList as $opt)
                        <option value="{{ $opt }}" {{ $kategori === $opt ? 'selected' : '' }}>{{ $opt }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary"
                    style="display:inline-flex;align-items:center;gap:8px;white-space:nowrap;">
                    <i class="fas fa-magnifying-glass"></i> Cari
                </button>
            </form>

            @if ($buku->isEmpty())
                <div class="chart-box" style="text-align:center;padding:3rem 1.5rem;">
                    <i class="fas fa-book"
                        style="font-size:var(--text-4xl,2.5rem);opacity:.35;display:block;margin-bottom:12px;"></i>
                    <div style="color:var(--text-muted);">Belum ada koleksi yang cocok dengan pencarian Anda.</div>
                </div>
            @else
                <div class="services-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr));">
                    @foreach ($buku as $b)
                        @php $tersedia = (int) ($b->eksemplar_tersedia ?? 0); @endphp
                        <div class="service-card" style="gap:10px;">
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                                    <h3 class="service-title" style="flex:1;">{{ $b->judul }}</h3>
                                    <span
                                        style="font-size:var(--text-2xs,0.6875rem);font-weight:700;padding:3px 9px;border-radius:var(--radius-pill,999px);background:rgba(59,130,246,.12);color:var(--primary);white-space:nowrap;">{{ $b->kategori ?: 'Umum' }}</span>
                                </div>
                                <ul class="service-list" style="margin-top:8px;">
                                    <li><i class="fas fa-user-pen text-primary me-2"></i>
                                        {{ $b->penulis ?: 'Tanpa Penulis' }}</li>
                                    <li><i class="fas fa-building text-accent me-2"></i>
                                        {{ $b->penerbit ?: '-' }}{{ $b->tahun_terbit ? ' (' . $b->tahun_terbit . ')' : '' }}
                                    </li>
                                    <li>
                                        @if ($tersedia > 0)
                                            <i class="fas fa-circle-check text-success me-2"></i> Tersedia
                                            {{ $tersedia }} eksemplar
                                        @else
                                            <i class="fas fa-circle-xmark text-danger me-2"></i> Sedang habis dipinjam
                                        @endif
                                    </li>
                                </ul>
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
