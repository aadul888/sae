@extends('layouts.dashboard')

@section('title', 'Maping Mata Pelajaran — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-diagram-project"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Maping Mata Pelajaran
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Tetapkan mata pelajaran <strong>per jurusan &amp; per semester</strong> — nama mapel generik
                    (Dasar Dasar Keahlian / Konsentrasi Keahlian) tampil sebagai nama jurusan yang jelas.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'mapel'])

    {{-- 3. Pemilih Jurusan --}}
    @php
        $jurusanTerpilih = $jurusanTerpilih ?? '_global';
        $labelJurusan = $jurusanTerpilih === '_global'
            ? 'Default (semua jurusan)'
            : \App\Services\BukuIndukJurusanService::label($jurusanMeta ?? []);
    @endphp
    <div class="card" style="padding: 16px 18px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <i class="fas fa-graduation-cap" style="color: #0d9488;"></i>
                <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color);">Jurusan</span>
            </div>
            <form method="GET" action="{{ route('dashboard.kesiswaan.buku-induk.mapel.index') }}"
                style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <select name="jurusan" class="bi-input" style="min-width: 300px;" onchange="this.form.submit()">
                    <option value="_global" {{ $jurusanTerpilih === '_global' ? 'selected' : '' }}>&mdash; Default (semua jurusan) &mdash;</option>
                    @foreach ($jurusanList as $kunci => $jr)
                        <option value="{{ $kunci }}" {{ (string) $jurusanTerpilih === (string) $kunci ? 'selected' : '' }}>
                            {{ \App\Services\BukuIndukJurusanService::label($jr) }}
                        </option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn btn-outline">Tampilkan</button></noscript>
            </form>
            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); flex: 1; min-width: 220px;">
                Setiap jurusan punya susunan mapel sendiri. Jurusan yang belum dipetakan memakai daftar <strong>Default</strong>.
            </div>
        </div>
    </div>

    {{-- 4. Pemetaan per Semester --}}
    @php
        $tingkatLabel = ['10' => 'X', '11' => 'XI', '12' => 'XII'];
        $semPkl = [];
        foreach (($pklSemester ?? []) as $s) {
            $s = (int) $s;
            $semPkl[$s] = $tingkatLabel[(string) (10 + intdiv($s - 1, 2))] ?? '';
        }
    @endphp
    <div class="card" style="padding: 18px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
            <div
                style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(13,148,136,0.12); color: #0d9488; display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Pemetaan Mata Pelajaran — {{ $labelJurusan }}</div>
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">
                    Ambil dari referensi <strong>pembelajaran</strong> Dapodik atau tambah manual. Gunakan panah untuk mengatur urutan.
                </div>
            </div>
        </div>

        <form id="formPengaturan" action="{{ route('dashboard.kesiswaan.buku-induk.mapel.save') }}" method="POST">
            @csrf
            <input type="hidden" name="jurusan" value="{{ $jurusanTerpilih }}">
            <input type="hidden" name="pkl_ada" value="1">

            <div class="bi-sem-tabs">
                @foreach (range(1, 6) as $sem)
                    <button type="button" class="bi-sem-tab {{ $sem === 1 ? 'active' : '' }}" data-sem="{{ $sem }}">
                        Semester {{ $sem }}
                        <span class="bi-sem-badge">{{ isset($semPkl[$sem]) ? 'PKL' : (count($perSemester[$sem]['umum']) + count($perSemester[$sem]['kejuruan'])) }}</span>
                    </button>
                @endforeach
            </div>

            @foreach (range(1, 6) as $sem)
                <div class="bi-sem-panel {{ $sem === 1 ? 'active' : '' }}" data-sem="{{ $sem }}">
                    <label class="bi-pkl-toggle bi-pkl-per-sem">
                        <input type="checkbox" name="pkl_semester[]" value="{{ $sem }}"
                            {{ in_array($sem, $pklSemester ?? [], true) ? 'checked' : '' }}>
                        <span>Semester {{ $sem }} melaksanakan <strong>PKL</strong> — semua mapel ditutup, hanya
                            <strong>Praktik Kerja Lapangan</strong> yang bisa dinilai</span>
                    </label>

                    @if (isset($semPkl[$sem]))
                        <div class="bi-sem-note" style="margin-bottom: 12px;">
                            <i class="fas fa-lock"></i> Semester {{ $sem }} dipakai untuk <strong>PKL kelas {{ $semPkl[$sem] }}</strong> — terkunci.
                            Semua mapel ditutup; hanya mapel <strong>Praktik Kerja Lapangan</strong> yang bisa dinilai.
                        </div>
                        <div class="bi-mapel-grid">
                            <div class="bi-mapel-col-locked">
                                <div class="bi-col-head">
                                    <span><i class="fas fa-briefcase" style="color: #0d9488;"></i> Mapel Kejuruan — Sem {{ $sem }}</span>
                                    <span class="bi-count">1</span>
                                </div>
                                <div class="bi-selected">
                                    <div class="bi-sel-item">
                                        <span class="bi-sel-idx">1</span>
                                        <input type="text" class="bi-input bi-singkatan-item" maxlength="20"
                                            name="singkatan[Praktik Kerja Lapangan]" title="Singkatan kolom di Input Nilai"
                                            value="{{ $singkatan['Praktik Kerja Lapangan'] ?? 'PKL' }}">
                                        <span class="bi-sel-name">Praktik Kerja Lapangan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                    @if (!($terpetakan[$sem] ?? false))
                        <div class="bi-sem-note" style="margin-bottom: 12px;">
                            <i class="fas fa-circle-info"></i> Semester {{ $sem }} belum dipetakan — masih memakai daftar mapel global. Simpan untuk menandai semester ini.
                        </div>
                    @endif

                    <div class="bi-copy-row">
                        <i class="fas fa-clone" style="color: #4338ca;"></i>
                        <span>Salin mapel dari semester:</span>
                        <select class="bi-input bi-copy-select" style="min-width: 170px;">
                            <option value="">&mdash; pilih semester &mdash;</option>
                            @foreach (range(1, 6) as $sumber)
                                @if ($sumber !== $sem)
                                    <option value="{{ $sumber }}">Semester {{ $sumber }}</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline bi-copy-btn" data-sem="{{ $sem }}">
                            <i class="fas fa-copy"></i> Salin
                        </button>
                        <span style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted);">Urutan &amp; pilihan mapel disamakan dengan semester sumber.</span>
                    </div>

                    <div class="bi-mapel-grid">
                        @foreach (['umum' => ['label' => 'Mapel Umum', 'color' => '#10b981'], 'kejuruan' => ['label' => 'Mapel Kejuruan', 'color' => '#f59e0b']] as $key => $meta)
                            <div class="bi-mapel-col" data-kelompok="{{ $key }}" data-semester="{{ $sem }}">
                                <div class="bi-col-head">
                                    <span><i class="fas fa-layer-group" style="color: {{ $meta['color'] }};"></i> {{ $meta['label'] }} — Sem {{ $sem }}</span>
                                    <span class="bi-count" data-count>0</span>
                                </div>

                                <div class="bi-ref-wrap">
                                    <div class="bi-ref-search-wrap">
                                        <i class="fas fa-search"></i>
                                        <input type="text" class="bi-input bi-ref-search" placeholder="Cari mapel referensi untuk ditambahkan...">
                                    </div>
                                    <div class="bi-ref-list" data-ref></div>
                                </div>

                                <div class="bi-selected" data-selected></div>

                                <div class="bi-add-custom">
                                    <input type="text" class="bi-input bi-custom-input" placeholder="Tambah mapel manual...">
                                    <button type="button" class="btn btn-outline bi-add-btn" title="Tambah mapel manual">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <textarea name="mapel[{{ $sem }}][{{ $key }}]" class="bi-hidden-mapel" hidden>{{ implode("\n", $perSemester[$sem][$key]) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach

            @if ($can['update'])
                <button type="submit" class="btn btn-primary"
                    style="margin-top: 16px; padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Simpan Pemetaan Mapel
                </button>
            @else
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Anda tidak memiliki hak untuk mengubah pemetaan mapel.</div>
            @endif
        </form>
    </div>

    {{-- Data referensi mapel dari tabel pembelajaran (untuk JS) --}}
    <script type="application/json" id="biRefData">@json($referensi)</script>
    {{-- Singkatan mapel per nama (untuk prefill input di daftar mapel) --}}
    <script type="application/json" id="biSingkatanData">@json($singkatan ?? [])</script>
    {{-- Mapel per semester (untuk tombol salin mapel antar semester) --}}
    <script type="application/json" id="biPerSemesterData">@json($perSemester ?? [])</script>
@endsection

@include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
