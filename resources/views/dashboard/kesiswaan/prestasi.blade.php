@extends('layouts.dashboard')

@section('title', 'Prestasi Siswa (Akademik, Nonakademik & Rekap) — SAE')
@section('dash_title', 'Prestasi Siswa')

@section('content')
    <!-- 1. Header Banner & Actions -->
    <div class="dash-banner" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(245,158,11,0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0;">
                <i class="fas fa-trophy"></i>
            </div>
            <div>
                <h2 style="font-size: 1.3rem; font-weight: 800; color: var(--text-color); margin: 0;">
                    Prestasi Siswa
                </h2>
                <p style="margin: 2px 0 0 0; font-size: 0.82rem; color: var(--text-muted);">
                    Pencatatan perolehan medali dan kejuaraan siswa bidang akademik dan nonakademik, sertifikat, serta rekapitulasi.
                </p>
            </div>
        </div>

        <div class="dash-banner-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('dashboard.kesiswaan.prestasi.cetak', request()->all()) }}" target="_blank"
                class="btn btn-outline" title="Cetak Laporan Rekap Prestasi" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                <i class="fas fa-print"></i>
            </a>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" id="btnOpenPrestasiModal" title="Catat Prestasi Siswa Baru" style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px;">
                    <i class="fas fa-plus"></i>
                </button>
            @endif
        </div>
    </div>

    <!-- 2. Stat Grid Baku SAE -->
    <div class="dash-stat-grid" style="margin-bottom: 20px;">
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(59,130,246,0.12); color: #2563eb;">
                <i class="fas fa-award"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_akademik'] ?? 0) }}</div>
                <div class="dash-stat-label">Prestasi Akademik</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(16,185,129,0.12); color: #10b981;">
                <i class="fas fa-medal"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_nonakademik'] ?? 0) }}</div>
                <div class="dash-stat-label">Prestasi Nonakademik</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(239,68,68,0.12); color: #ef4444;">
                <i class="fas fa-earth-asia"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_nasional'] ?? 0) }}</div>
                <div class="dash-stat-label">Tingkat Nasional / Int.</div>
            </div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-icon" style="background: rgba(245,158,11,0.12); color: #f59e0b;">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="dash-stat-info">
                <div class="dash-stat-value">{{ number_format($stats['total_juara_1'] ?? 0) }}</div>
                <div class="dash-stat-label">Juara 1 (Emas)</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs Wrapper -->
    <div class="periode-nav-wrapper" style="margin-bottom: 20px;">
        <div class="periode-nav-desktop">
            <a href="{{ route('dashboard.kesiswaan.prestasi.index', ['tab' => 'akademik']) }}"
                class="periode-nav-tab {{ $activeTab === 'akademik' ? 'active' : '' }}">
                <i class="fas fa-award"></i> Akademik
            </a>
            <a href="{{ route('dashboard.kesiswaan.prestasi.index', ['tab' => 'nonakademik']) }}"
                class="periode-nav-tab {{ $activeTab === 'nonakademik' ? 'active' : '' }}">
                <i class="fas fa-medal"></i> Nonakademik
            </a>
            <a href="{{ route('dashboard.kesiswaan.prestasi.index', ['tab' => 'rekap']) }}"
                class="periode-nav-tab {{ $activeTab === 'rekap' ? 'active' : '' }}">
                <i class="fas fa-chart-column"></i> Rekapitulasi
            </a>
        </div>
    </div>

    <!-- 4. Content Sesuai Tab -->
    @if ($activeTab === 'akademik' || $activeTab === 'nonakademik')
        @php
            $currentList = ($activeTab === 'akademik') ? $akademikList : $nonakademikList;
        @endphp
        <div class="card" style="padding: 16px 20px; margin-bottom: 18px;">
            <form method="GET" action="{{ route('dashboard.kesiswaan.prestasi.index') }}" class="table-toolbar" style="margin-bottom: 0;">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="live-search-wrap" style="flex: 1; min-width: 240px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama lomba, event, atau siswa..."
                        class="form-control" style="padding-left: 38px; width: 100%; border-radius: 8px;">
                </div>
                <select name="tingkat" class="form-control" style="width: 180px; border-radius: 8px;">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="sekolah" {{ $tingkat === 'sekolah' ? 'selected' : '' }}>Sekolah</option>
                    <option value="kecamatan" {{ $tingkat === 'kecamatan' ? 'selected' : '' }}>Kecamatan</option>
                    <option value="kabupaten_kota" {{ $tingkat === 'kabupaten_kota' ? 'selected' : '' }}>Kabupaten / Kota</option>
                    <option value="provinsi" {{ $tingkat === 'provinsi' ? 'selected' : '' }}>Provinsi</option>
                    <option value="nasional" {{ $tingkat === 'nasional' ? 'selected' : '' }}>Nasional</option>
                    <option value="internasional" {{ $tingkat === 'internasional' ? 'selected' : '' }}>Internasional</option>
                </select>
                <button type="submit" class="btn btn-outline" style="border-radius: 8px;"><i class="fas fa-filter"></i> Filter</button>
            </form>
        </div>

        <div class="card table-responsive-stack" style="padding: 0; margin-bottom: 24px;">
            <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                <thead>
                    <tr style="background: rgba(255, 255, 255, 0.02); border-bottom: 1px solid var(--border-color);">
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Peringkat</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Bidang Lomba / Event</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tingkat</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tanggal</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Pembimbing</th>
                        <th style="padding: 12px 18px; font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($currentList as $item)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 12px 18px;">
                                @php
                                    $pColor = match ($item->peringkat) {
                                        'juara_1' => 'background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.4);',
                                        'juara_2' => 'background: rgba(148,163,184,0.15); color: #64748b; border: 1px solid rgba(148,163,184,0.4);',
                                        'juara_3' => 'background: rgba(180,83,9,0.15); color: #b45309; border: 1px solid rgba(180,83,9,0.4);',
                                        default => 'background: rgba(59,130,246,0.1); color: #2563eb;',
                                    };
                                @endphp
                                <span class="badge" style="{{ $pColor }} font-weight: 800;">{{ strtoupper(str_replace('_', ' ', $item->peringkat)) }}</span>
                            </td>
                            <td style="padding: 12px 18px; font-weight: 700; color: var(--text-color);">
                                {{ $item->siswa?->nama ?: '-' }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $item->siswa?->nisn ?: '-' }}</div>
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">
                                <strong>{{ $item->bidang_lomba }}</strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">{{ $item->nama_event }}</div>
                            </td>
                            <td style="padding: 12px 18px;">
                                <span class="badge badge-outline">{{ strtoupper(str_replace('_', ' ', $item->tingkat)) }}</span>
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.85rem;">
                                {{ date('d/m/Y', strtotime($item->tanggal_prestasi)) }}
                            </td>
                            <td style="padding: 12px 18px; font-size: 0.82rem; color: var(--text-muted);">
                                {{ $item->pembimbing?->nama ?: ($item->pembimbing_nama ?: '-') }}
                            </td>
                            <td style="padding: 12px 18px; text-align: right;">
                                <div class="table-actions">
                                    @if ($item->sertifikat_file)
                                        <a href="{{ asset($item->sertifikat_file) }}" target="_blank" class="btn-icon" title="Lihat Sertifikat">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        </a>
                                    @endif
                                    @if ($canDelete)
                                        <form action="{{ route('dashboard.kesiswaan.prestasi.destroy', $item->id) }}" method="POST" style="display:inline;" data-confirm="delete" data-name="prestasi ini">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-icon" title="Hapus Prestasi"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: var(--text-muted);">Belum ada catatan prestasi pada kategori ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    @elseif ($activeTab === 'rekap')
        <!-- TAB 3: REKAPITULASI PRESTASI -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div class="card" style="padding: 24px;">
                <h4 style="margin: 0 0 16px 0; font-size: 1rem; font-weight: 800; color: var(--text-color);">Perolehan Berdasarkan Tingkat Kejuaraan</h4>
                <table class="table table-pd" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">Tingkat</th>
                            <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; text-align: right;">Total Prestasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekapPerTingkat as $rk)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 14px; font-weight: 700; color: var(--text-color);">{{ strtoupper(str_replace('_', ' ', $rk->tingkat)) }}</td>
                                <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: var(--primary);">{{ $rk->total }} Prestasi</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="padding: 20px; text-align: center; color: var(--text-muted);">Belum ada data rekap.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card" style="padding: 24px;">
                <h4 style="margin: 0 0 16px 0; font-size: 1rem; font-weight: 800; color: var(--text-color);">Siswa Paling Berprestasi (Top 10)</h4>
                <table class="table table-pd" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase;">Nama Siswa</th>
                            <th style="padding: 10px 14px; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; text-align: right;">Perolehan Juara</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topSiswa as $ts)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 14px; font-weight: 700; color: var(--text-color);">
                                    {{ $ts->nama }}
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">NISN: {{ $ts->nisn ?: '-' }}</div>
                                </td>
                                <td style="padding: 10px 14px; text-align: right; font-weight: 800; color: #f59e0b;">{{ $ts->total_prestasi }} Medali / Juara</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" style="padding: 20px; text-align: center; color: var(--text-muted);">Belum ada data siswa berprestasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- MODAL: CATAT PRESTASI SISWA -->
    <div id="modalPrestasi" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; padding: 24px; border-radius: 12px; margin: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-color); margin: 0;">Catat Prestasi Siswa</h3>
                <button type="button" class="close-modal" data-target="#modalPrestasi" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px; display: inline-flex; align-items: center; justify-content: center; transition: color 0.2s ease;"><i class="fas fa-times"></i></button>
            </div>
            <form id="formPrestasi" method="POST" action="{{ route('dashboard.kesiswaan.prestasi.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Pilih Siswa <span class="text-danger">*</span></label>
                    <select name="peserta_didik_id" class="form-control" required style="width: 100%; border-radius: 8px;">
                        <option value="">-- Cari dan Pilih Siswa --</option>
                        @foreach ($siswaList as $sw)
                            <option value="{{ $sw->peserta_didik_id }}">{{ $sw->nama }} (NISN: {{ $sw->nisn ?: '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Kategori <span class="text-danger">*</span></label>
                        <select name="kategori" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="akademik">Akademik (OSN/LKS/Debat)</option>
                            <option value="nonakademik">Nonakademik (Olahraga/Seni)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Peringkat Juara <span class="text-danger">*</span></label>
                        <select name="peringkat" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="juara_1">Juara 1 (Emas)</option>
                            <option value="juara_2">Juara 2 (Perak)</option>
                            <option value="juara_3">Juara 3 (Perunggu)</option>
                            <option value="harapan_1">Juara Harapan 1</option>
                            <option value="harapan_2">Juara Harapan 2</option>
                            <option value="finalis">Finalis</option>
                            <option value="peserta">Peserta</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Bidang Lomba <span class="text-danger">*</span></label>
                        <input type="text" name="bidang_lomba" class="form-control" placeholder="Contoh: LKS Web Technologies" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tingkat <span class="text-danger">*</span></label>
                        <select name="tingkat" class="form-control" required style="width: 100%; border-radius: 8px;">
                            <option value="sekolah">Sekolah</option>
                            <option value="kecamatan">Kecamatan</option>
                            <option value="kabupaten_kota">Kabupaten / Kota</option>
                            <option value="provinsi">Provinsi</option>
                            <option value="nasional">Nasional</option>
                            <option value="internasional">Internasional</option>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Nama Event / Kejuaraan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_event" class="form-control" placeholder="Contoh: Lomba Kompetensi Siswa SMK Tingkat Provinsi 2026" required style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Tanggal Prestasi <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_prestasi" class="form-control" value="{{ date('Y-m-d') }}" required style="width: 100%; border-radius: 8px;">
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Guru Pembimbing</label>
                        <select name="pembimbing_ptk_id" class="form-control" style="width: 100%; border-radius: 8px;">
                            <option value="">-- Pilih Pembimbing --</option>
                            @foreach ($pembimbingList as $gt)
                                <option value="{{ $gt->ptk_id }}">{{ $gt->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">Upload Sertifikat / Piagam (PDF / Foto)</label>
                    <input type="file" name="sertifikat_file" class="form-control" accept=".pdf,image/*" style="width: 100%; border-radius: 8px;">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline close-modal" data-target="#modalPrestasi" style="border-radius: 8px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px;"><i class="fas fa-save"></i> Simpan Prestasi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/kesiswaan-prestasi.js') }}"></script>
@endpush
