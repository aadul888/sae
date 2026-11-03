@extends('layouts.dashboard')

@section('title', 'Input Nilai — SAE')
@section('dash_title', 'Buku Induk Siswa')

@section('content')
    {{-- 1. Header Banner --}}
    <div class="dash-banner"
        style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div
                style="width: 44px; height: 44px; border-radius: var(--radius-lg, 12px); background: rgba(67,56,202,0.12); color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: var(--text-xl, 1.25rem); flex-shrink: 0;">
                <i class="fas fa-pen-to-square"></i>
            </div>
            <div>
                <h2 style="font-size: var(--text-xl, 1.25rem); font-weight: 800; color: var(--text-color); margin: 0;">
                    Input Nilai per Kelas
                </h2>
                <p style="margin: 2px 0 0 0; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Isi nilai <strong>manual per kelas akhir (XII)</strong> atau impor berkas nilai kelas ini
                    (siap menerima ekspor e-Rapor). Semester dapat dipilih mundur mulai 1 sampai 6.
                </p>
            </div>
        </div>
    </div>

    {{-- 2. Tab Navigasi --}}
    @include('dashboard.kesiswaan.buku-induk.partials.tabs', ['activeTab' => 'input_nilai'])

    {{-- 3. Pemilih Semester & Kelas --}}
    <div class="card" style="padding: 16px 18px; margin-bottom: 20px;">
        <form method="GET" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai') }}"
            style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-calendar-day" style="color: #4338ca;"></i>
                <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color);">Semester</span>
                <select name="semester" class="bi-input" style="min-width: 150px;" onchange="this.form.submit()">
                    @foreach ($semesterList as $sem)
                        <option value="{{ $sem }}" {{ (int) $semester === $sem ? 'selected' : '' }}>
                            Semester {{ $sem }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-chalkboard" style="color: #0d9488;"></i>
                <span style="font-size: var(--text-sm, 0.8125rem); font-weight: 800; color: var(--text-color);">Kelas</span>
                <select name="kelas" class="bi-input" style="min-width: 260px;" onchange="this.form.submit()">
                    @forelse ($kelasList as $k)
                        <option value="{{ $k['id'] }}" {{ (string) $kelasId === (string) $k['id'] ? 'selected' : '' }}>
                            {{ $k['nama'] }}
                        </option>
                    @empty
                        <option value="">&mdash; Tidak ada kelas tingkat ini &mdash;</option>
                    @endforelse
                </select>
            </div>
            <noscript><button type="submit" class="btn btn-outline">Tampilkan</button></noscript>
            @if (!empty($mapel))
                <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted); flex: 1; min-width: 200px;">
                    {{ count($mapel) }} mapel pada semester ini
                    @if (in_array((int) $semester, $pklSemester ?? [], true)) &mdash; <strong>terkunci: hanya PKL</strong> @endif
                </div>
            @endif
        </form>
    </div>

    @if ($kelasId === '' || !$kelas)
        <div class="card" style="padding: 18px;">
            <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Belum ada kelas pada tingkat semester ini.</div>
        </div>
    @else
        {{-- 4. Impor Nilai per Kelas --}}
        @if ($can['create'])
            <div class="card" style="padding: 18px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 38px; height: 38px; border-radius: var(--radius-md, 8px); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-lg, 1.125rem); flex-shrink: 0;">
                            <i class="fas fa-file-excel"></i>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem);">Import Nilai — {{ $kelas['nama'] }} (Semester {{ $semester }})</div>
                            <div style="font-size: var(--text-xs, 0.75rem); color: var(--text-muted);">Kolom dikenali per nama mapel (NISN wajib milik kelas ini).</div>
                        </div>
                    </div>
                    <a id="linkTemplateNilai" href="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.template', ['kelas' => $kelasId, 'semester' => $semester]) }}"
                        class="btn btn-outline"
                        style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: var(--text-sm, 0.8125rem); text-decoration: none; border-color: var(--success, #10b981); color: var(--success, #10b981);">
                        <i class="fas fa-download"></i> Unduh Template
                    </a>
                </div>

                <form id="formInputImport" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.import') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $kelasId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <div class="bi-import-row">
                        <input type="file" name="file_excel" accept=".xlsx,.xls,.csv,.txt" class="bi-file-input" required>
                        <button type="submit" id="btnInputImport" class="btn btn-primary"
                            style="padding: 9px 22px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                            <i class="fas fa-cloud-arrow-up"></i> Import Nilai
                        </button>
                    </div>
                    <div style="font-size: var(--text-2xs, 0.6875rem); color: var(--text-muted); margin-top: 6px;">
                        Mendukung .xlsx, .xls, .csv (maks 20 MB). Hanya baris siswa kelas ini yang diterima.
                    </div>
                </form>
            </div>
        @endif

        {{-- 5. Tabel Nilai Manual --}}
        <div class="card" style="padding: 18px;">
            <div style="font-weight: 800; color: var(--text-color); font-size: var(--text-md, 1rem); margin-bottom: 12px;">
                Daftar Nilai — {{ $kelas['nama'] }} · Semester {{ $semester }}
            </div>

            @if (!$mapel)
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">
                    Belum ada mapel untuk semester ini. Atur di tab <strong>Maping Mata Pelajaran</strong>.
                </div>
            @elseif (empty($siswa))
                <div style="font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Belum ada siswa di kelas ini.</div>
            @else
                <form id="formInputNilai" action="{{ route('dashboard.kesiswaan.buku-induk.input-nilai.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kelas" value="{{ $kelasId }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">

                    <div class="bi-nilai-scroll">
                        <table class="bi-nilai-table">
                            <thead>
                                <tr>
                                    <th style="min-width: 40px;">No</th>
                                    <th style="min-width: 110px;">NISN</th>
                                    <th style="min-width: 200px;">Nama</th>
                                    @foreach ($mapel as $nama)
                                        @php $sing = $singkatan[$nama] ?? ''; @endphp
                                        <th title="{{ $nama }}" style="min-width: 90px;">
                                            <span class="bi-nilai-dot" style="background: {{ ($kelompok[$nama] ?? 'umum') === 'kejuruan' ? '#f59e0b' : '#10b981' }};"></span>
                                            {{ $sing !== '' ? $sing : $nama }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($siswa as $i => $s)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $s->nisn }}</td>
                                        <td class="bi-nilai-nama">{{ $s->nama }}</td>
                                        @foreach ($mapel as $nama)
                                            <td>
                                                <input type="text" inputmode="decimal" class="bi-nilai-input"
                                                    name="nilai[{{ $s->peserta_didik_id }}][{{ $nama }}]"
                                                    value="{{ $tersimpan[$s->peserta_didik_id][$nama] ?? '' }}">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($can['update'])
                        <button type="submit" class="btn btn-primary"
                            style="margin-top: 16px; padding: 8px 20px; font-size: var(--text-sm, 0.8125rem); display: inline-flex; align-items: center; gap: 6px;">
                            <i class="fas fa-save"></i> Simpan Nilai
                        </button>
                        <span id="inputNilaiInfo" style="margin-left: 10px; font-size: var(--text-xs, 0.75rem); color: var(--text-muted);"></span>
                    @else
                        <div style="margin-top: 14px; font-size: var(--text-sm, 0.8125rem); color: var(--text-muted);">Anda tidak memiliki hak untuk mengubah nilai.</div>
                    @endif
                </form>
            @endif
        </div>
    @endif
@endsection

@include('dashboard.kesiswaan.buku-induk.partials.buku-induk-style')

@push('scripts')
    <script src="{{ asset('js/buku-induk-nilai.js') }}?v={{ file_exists(public_path('js/buku-induk-nilai.js')) ? filemtime(public_path('js/buku-induk-nilai.js')) : time() }}"></script>
@endpush
