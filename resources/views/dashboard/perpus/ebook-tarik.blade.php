@extends('layouts.dashboard')

@section('title', 'Tarik E-Book Gratis & Terbuka')

@section('content')
    <div class="dash-content-inner">
        <div class="dash-banner"
            style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:14px;">
                <div
                    style="width:48px;height:48px;border-radius:var(--radius-lg);background:rgba(59,130,246,0.12);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:var(--text-2xl);flex-shrink:0;">
                    <i class="fas fa-cloud-arrow-down"></i>
                </div>
                <div>
                    <h2 style="font-size:var(--text-xl);font-weight:800;color:var(--text-color);margin:0;">
                        Tarik E-Book Gratis &amp; Terbuka
                    </h2>
                    <div style="font-size:var(--text-sm);color:var(--text-muted);margin-top:2px;">
                        Integrasi repositori resmi bebas royalti Kemendikbudristek, Ditjen Vokasi SMK, Kemenag RI, Badan Bahasa GLN, Perpusnas, dan BSE.
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-outline" onclick="openModalEbookManual()"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md, 8px); font-size: var(--text-md, 1rem);"
                    title="Tambah E-Book Mandiri" aria-label="Tambah E-Book Mandiri">
                    <i class="fas fa-file-circle-plus"></i>
                </button>
            </div>
        </div>

        @include('dashboard.perpus.partials.tab-nav')
        @include('dashboard.perpus.partials.sub-nav-koleksi')

        @if (session('error'))
            <div class="alert alert-danger" style="margin-bottom:16px;"><i class="fas fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success" style="margin-bottom:16px;"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
        @endif

        {{-- Statistik Ringkas --}}
        <div class="dash-stat-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px;">
            <div class="dash-stat-card" style="padding:14px 18px;border-radius:var(--radius-lg);background:var(--card-bg);border:1px solid var(--border-color);display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;border-radius:var(--radius-md);background:rgba(59,130,246,0.12);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:var(--text-xl);">
                    <i class="fas fa-book-bookmark"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);font-weight:700;text-transform:uppercase;">E-Book di Katalog</div>
                    <div style="font-size:var(--text-xl);font-weight:800;color:var(--text-color);">{{ number_format($totalEbookKatalog, 0, ',', '.') }} Judul</div>
                </div>
            </div>
            <div class="dash-stat-card" style="padding:14px 18px;border-radius:var(--radius-lg);background:var(--card-bg);border:1px solid var(--border-color);display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;border-radius:var(--radius-md);background:rgba(16,185,129,0.12);color:var(--success,#10b981);display:flex;align-items:center;justify-content:center;font-size:var(--text-xl);">
                    <i class="fas fa-eye"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);font-weight:700;text-transform:uppercase;">Total Dibaca Siswa/Guru</div>
                    <div style="font-size:var(--text-xl);font-weight:800;color:var(--text-color);">{{ number_format($totalDibaca, 0, ',', '.') }} Kali</div>
                </div>
            </div>
            <div class="dash-stat-card" style="padding:14px 18px;border-radius:var(--radius-lg);background:var(--card-bg);border:1px solid var(--border-color);display:flex;align-items:center;gap:14px;">
                <div style="width:42px;height:42px;border-radius:var(--radius-md);background:rgba(245,158,11,0.12);color:var(--warning,#f59e0b);display:flex;align-items:center;justify-content:center;font-size:var(--text-xl);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <div style="font-size:var(--text-xs);color:var(--text-muted);font-weight:700;text-transform:uppercase;">Tersedia di Repositori</div>
                    <div style="font-size:var(--text-xl);font-weight:800;color:var(--text-color);">{{ count($items) }} Judul Siap Tarik</div>
                </div>
            </div>
        </div>

        {{-- Filter & Pencarian --}}
        <div class="card" style="padding:16px 20px;margin-bottom:20px;border-radius:var(--radius-lg);">
            <form method="GET" action="{{ route('dashboard.perpustakaan.ebook.index') }}"
                style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;">
                <div>
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;color:var(--text-muted);">
                        Kata Kunci Pencarian:
                    </label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control"
                        placeholder="Judul, mapel, jurusan, penulis..." style="width:100%;">
                </div>
                <div>
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;color:var(--text-muted);">
                        Tingkat / Kelas:
                    </label>
                    <select name="kelas" class="form-control" style="width:100%;">
                        <option value="">Semua Tingkat</option>
                        <option value="10" {{ $kelas === '10' ? 'selected' : '' }}>Kelas X (Fase E)</option>
                        <option value="11" {{ $kelas === '11' ? 'selected' : '' }}>Kelas XI (Fase F)</option>
                        <option value="12" {{ $kelas === '12' ? 'selected' : '' }}>Kelas XII (Fase F)</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;color:var(--text-muted);">
                        Kategori / Bidang:
                    </label>
                    <select name="kategori" class="form-control" style="width:100%;">
                        <option value="">Semua Kategori</option>
                        @foreach ($daftarKategori as $kat)
                            <option value="{{ $kat }}" {{ $kategori === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;color:var(--text-muted);">
                        Sumber Repositori:
                    </label>
                    <select name="sumber" class="form-control" style="width:100%;">
                        <option value="">Semua Sumber Terbuka</option>
                        @foreach ($daftarSumber as $key => $lbl)
                            <option value="{{ $key }}" {{ $sumber === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
                        <i class="fas fa-magnifying-glass"></i> Filter
                    </button>
                    @if ($q || $kelas || $kategori || $sumber)
                        <a href="{{ route('dashboard.perpustakaan.ebook.index') }}" class="btn btn-outline" title="Reset Filter">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Form Tarik Banyak --}}
        <form method="POST" action="{{ route('dashboard.perpustakaan.ebook.tarik-banyak') }}" id="formTarikBanyak">
            @csrf
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;font-size:var(--text-sm);cursor:pointer;user-select:none;">
                        <input type="checkbox" id="checkAllEbook" onchange="togglePilihSemua(this)">
                        <strong>Pilih Semua Belum Terdaftar</strong>
                    </label>
                    <span id="labelTerpilih" style="font-size:var(--text-xs);color:var(--text-muted);display:none;"></span>
                </div>
                <button type="submit" id="btnTarikTerpilih" class="btn btn-success" disabled
                    style="display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-cloud-arrow-down"></i> Tarik Terpilih ke Katalog
                </button>
            </div>

            {{-- Grid E-Book --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px;margin-bottom:30px;">
                @forelse ($items as $item)
                    @php
                        $sudahAda = $item['sudah_ada'] ?? false;
                        $sumberKey = $item['sumber_ebook'] ?? '';
                        $theme = \App\Services\PerpustakaanEbookService::coverTheme($item['kategori'] ?? '', $item['judul'] ?? '');
                        $labelSumber = \App\Services\PerpustakaanEbookService::labelSumber($sumberKey);
                        $badgeClass = match($sumberKey) {
                            'sibi' => 'badge-primary',
                            'ditjen_smk' => 'badge-info',
                            'kemenag' => 'badge-success',
                            'badan_bahasa' => 'badge-warning',
                            'perpusnas' => 'badge-purple',
                            'bse' => 'badge-outline',
                            default => 'badge-secondary',
                        };
                    @endphp
                    <div class="card"
                        style="display:flex;flex-direction:column;justify-content:space-between;border-radius:var(--radius-lg);border:1px solid {{ $sudahAda ? 'rgba(16,185,129,0.35)' : 'var(--border-color)' }};padding:14px;position:relative;background:{{ $sudahAda ? 'rgba(16,185,129,0.02)' : 'var(--card-bg)' }};">
                        <div>
                            {{-- Cover / Thumbnail Buku --}}
                            <div style="height:115px;background:{{ $theme['gradient'] }};border-radius:var(--radius-md);margin-bottom:12px;padding:10px 12px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden;box-shadow:inset 4px 0 0 rgba(255,255,255,0.25), 0 2px 6px rgba(0,0,0,0.08);color:#fff;">
                                {{-- Background watermark icon --}}
                                <i class="fas {{ $theme['icon'] }}" style="position:absolute;right:-10px;bottom:-10px;font-size:var(--text-5xl);opacity:0.18;pointer-events:none;"></i>

                                <div style="display:flex;justify-content:space-between;align-items:flex-start;position:relative;z-index:2;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        @if (!$sudahAda)
                                            <input type="checkbox" name="kode_buku[]" value="{{ $item['kode_buku'] }}"
                                                class="check-ebook" onchange="hitungTerpilih()"
                                                style="accent-color:var(--primary);width:16px;height:16px;cursor:pointer;">
                                        @else
                                            <span style="background:rgba(255,255,255,0.95);color:#059669;font-size:var(--text-2xs);padding:2px 7px;border-radius:var(--radius-pill);font-weight:800;display:inline-flex;align-items:center;gap:3px;">
                                                <i class="fas fa-circle-check"></i> Siap
                                            </span>
                                        @endif
                                        <span style="font-size:var(--text-2xs);font-weight:800;letter-spacing:0.04em;background:rgba(0,0,0,0.35);backdrop-filter:blur(4px);padding:2px 7px;border-radius:var(--radius-pill);color:#fff;">
                                            <i class="fas fa-bolt me-1 text-warning"></i>E-BOOK
                                        </span>
                                    </div>
                                    <span style="font-size:var(--text-2xs);font-weight:700;background:rgba(255,255,255,0.92);color:#0f172a;padding:2px 8px;border-radius:var(--radius-pill);">
                                        Kelas {{ $item['kelas'] ?? 'Umum' }}
                                    </span>
                                </div>

                                <div style="position:relative;z-index:2;">
                                    <div style="font-size:var(--text-2xs);text-transform:uppercase;letter-spacing:0.05em;opacity:0.85;font-weight:700;">
                                        <i class="fas {{ $theme['icon'] }} me-1"></i>{{ $theme['label'] }}
                                    </div>
                                    <div style="font-size:var(--text-xs);font-weight:800;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-shadow:0 1px 2px rgba(0,0,0,0.3);">
                                        {{ $item['judul'] }}
                                    </div>
                                </div>
                            </div>

                            {{-- Sub-Header: Badge Sumber & DDC --}}
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;">
                                <span class="badge {{ $badgeClass }}"
                                    style="font-size:var(--text-2xs);font-weight:700;padding:2px 8px;">
                                    {{ $labelSumber }}
                                </span>
                                @if (!empty($item['klasifikasi_ddc']))
                                    <span style="font-size:var(--text-2xs);color:var(--text-muted);">
                                        DDC {{ $item['klasifikasi_ddc'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Judul & Penulis --}}
                            <h4 style="margin:0 0 6px;font-size:var(--text-sm);font-weight:800;color:var(--text-color);line-height:1.35;">
                                {{ $item['judul'] }}
                            </h4>
                            <div style="font-size:var(--text-xs);color:var(--text-muted);margin-bottom:8px;">
                                <i class="fas fa-user-pen me-1"></i> {{ $item['penulis'] ?: 'Penyusun Resmi' }} &bull;
                                <span>{{ $item['tahun_terbit'] ?? '-' }}</span>
                            </div>

                            {{-- Tag Kategori & Jurusan --}}
                            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px;">
                                <span style="font-size:var(--text-2xs);padding:2px 7px;border-radius:var(--radius-pill);background:rgba(59,130,246,0.08);color:var(--primary);font-weight:700;">
                                    <i class="fas fa-tag"></i> {{ $item['kategori'] ?? 'Pelajaran' }}
                                </span>
                                @if (!empty($item['jurusan']) && $item['jurusan'] !== 'Umum')
                                    <span style="font-size:var(--text-2xs);padding:2px 7px;border-radius:var(--radius-pill);background:rgba(139,92,246,0.08);color:var(--purple,#8b5cf6);font-weight:700;">
                                        <i class="fas fa-graduation-cap"></i> {{ $item['jurusan'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Sinopsis singkat --}}
                            @if (!empty($item['sinopsis']))
                                <p style="font-size:var(--text-xs);color:var(--text-muted);margin:0 0 12px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">
                                    {{ $item['sinopsis'] }}
                                </p>
                            @endif
                        </div>

                        {{-- Footer Aksi --}}
                        <div style="border-top:1px solid var(--border-color);padding-top:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            @if (!empty($item['tautan_baca']))
                                <a href="{{ $item['tautan_baca'] }}" target="_blank" rel="noopener noreferrer"
                                    class="btn btn-outline" style="font-size:var(--text-xs);padding:5px 10px;display:inline-flex;align-items:center;gap:5px;">
                                    <i class="fas fa-arrow-up-right-from-square"></i> Baca Daring
                                </a>
                            @else
                                <span></span>
                            @endif

                            @if ($sudahAda)
                                <span style="font-size:var(--text-xs);font-weight:700;color:var(--success,#10b981);display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fas fa-check-double"></i> Ada di Katalog
                                </span>
                            @else
                                <button type="button" class="btn btn-primary" onclick="tarikSatu('{{ $item['kode_buku'] }}')"
                                    style="font-size:var(--text-xs);padding:5px 12px;display:inline-flex;align-items:center;gap:5px;">
                                    <i class="fas fa-plus"></i> Tarik
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1;text-align:center;padding:40px 20px;color:var(--text-muted);">
                        <i class="fas fa-book-open" style="font-size:var(--text-3xl);opacity:0.4;margin-bottom:10px;display:block;"></i>
                        <div>Tidak ada e-book yang cocok dengan filter pencarian.</div>
                    </div>
                @endforelse
            </div>
        </form>

        {{-- Form Hidden untuk Tarik Satu --}}
        <form method="POST" action="{{ route('dashboard.perpustakaan.ebook.tarik') }}" id="formTarikSatu" style="display:none;">
            @csrf
            <input type="hidden" name="kode_buku" id="inputKodeBukuSatu" value="">
        </form>
    </div>

    {{-- Modal Tambah E-Book Mandiri (Lokal / Upload) --}}
    <div id="modalEbookManual"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;padding:16px;">
        <div class="card"
            style="width:100%;max-width:540px;max-height:92vh;overflow-y:auto;padding:24px;border-radius:var(--radius-lg);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <h3 style="margin:0;font-size:var(--text-md);font-weight:800;color:var(--text-color);">
                    <i class="fas fa-file-circle-plus text-primary me-2"></i>Tambah E-Book Mandiri
                </h3>
                <button type="button" class="btn-icon" onclick="closeModalEbookManual()" style="color:var(--text-muted);"><i class="fas fa-xmark"></i></button>
            </div>

            <form method="POST" action="{{ route('dashboard.perpustakaan.ebook.manual') }}" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">
                        Judul E-Book <span style="color:var(--danger,#ef4444);">*</span>
                    </label>
                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Modul Praktikum Perakitan Komputer">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Penulis / Penyusun</label>
                        <input type="text" name="penulis" class="form-control" placeholder="Nama Guru / Tim MGMP">
                    </div>
                    <div>
                        <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Penerbit / Sumber</label>
                        <input type="text" name="penerbit" class="form-control" placeholder="Internal Sekolah">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <div>
                        <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Kategori <span style="color:var(--danger,#ef4444);">*</span></label>
                        <select name="kategori" class="form-control" required>
                            @foreach ($daftarKategori as $kat)
                                <option value="{{ $kat }}">{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Tingkat / Kelas</label>
                        <select name="kelas" class="form-control">
                            <option value="Semua Kelas">Semua Kelas</option>
                            <option value="10">Kelas 10 (Fase E)</option>
                            <option value="11">Kelas 11 (Fase F)</option>
                            <option value="12">Kelas 12 (Fase F)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Jurusan / Program Keahlian</label>
                    <input type="text" name="jurusan" class="form-control" placeholder="Contoh: TJKT, Otomotif, Akuntansi, Umum">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">
                        Tautan Baca Online (URL)
                    </label>
                    <input type="url" name="tautan_baca" class="form-control" placeholder="https://drive.google.com/... atau tautan PDF eksternal">
                    <span style="font-size:var(--text-2xs);color:var(--text-muted);">Gunakan tautan resmi online jika file di-host di luar.</span>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">
                        Atau Unggah Berkas PDF (Maks. 20 MB)
                    </label>
                    <input type="file" name="file_pdf" class="form-control" accept="application/pdf">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:var(--text-xs);font-weight:700;margin-bottom:4px;">Sinopsis / Deskripsi Singkat</label>
                    <textarea name="sinopsis" rows="3" class="form-control" placeholder="Ringkasan materi atau tujuan modul e-book ini..."></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" class="btn btn-outline" onclick="closeModalEbookManual()">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan E-Book</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function openModalEbookManual() {
                var modal = document.getElementById('modalEbookManual');
                if (modal) modal.style.display = 'flex';
            }

            function closeModalEbookManual() {
                var modal = document.getElementById('modalEbookManual');
                if (modal) modal.style.display = 'none';
            }

            function tarikSatu(kodeBuku) {
                document.getElementById('inputKodeBukuSatu').value = kodeBuku;
                document.getElementById('formTarikSatu').submit();
            }

            function togglePilihSemua(el) {
                var checkboxes = document.querySelectorAll('.check-ebook');
                checkboxes.forEach(function (cb) {
                    cb.checked = el.checked;
                });
                hitungTerpilih();
            }

            function hitungTerpilih() {
                var checked = document.querySelectorAll('.check-ebook:checked');
                var count = checked.length;
                var btn = document.getElementById('btnTarikTerpilih');
                var label = document.getElementById('labelTerpilih');

                if (count > 0) {
                    btn.disabled = false;
                    label.style.display = 'inline';
                    label.textContent = count + ' e-book dipilih';
                } else {
                    btn.disabled = true;
                    label.style.display = 'none';
                }
            }
        </script>
    @endpush
@endsection
