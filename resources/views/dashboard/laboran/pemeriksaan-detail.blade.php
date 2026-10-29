@extends('layouts.dashboard')

@section('title', 'Ceklist Pemeriksaan — Laboratorium SAE')
@section('dash_title', 'Ceklist Pemeriksaan')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(16,185,129,0.12); color: var(--success, #10b981); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-list-check"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        Ceklist Pemeriksaan
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        <span style="font-family: var(--font-family-mono); font-weight: var(--font-bold); color: var(--primary);">{{ $pemeriksaan->nomor_pemeriksaan }}</span>
                        &bull; {{ \Carbon\Carbon::parse($pemeriksaan->tanggal_pemeriksaan)->translatedFormat('d F Y') }}
                        &bull; {{ $jenisList[$pemeriksaan->jenis] ?? $pemeriksaan->jenis }}
                        @if ($jurusan) &bull; {{ $jurusan->nama_jurusan }} @endif
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.pemeriksaan.index') }}" class="btn btn-outline"
                    title="Kembali ke Daftar Pemeriksaan" aria-label="Kembali ke Daftar Pemeriksaan">
                    <i class="fas fa-arrow-left"></i>
                </a>
                @if ($canUpdate)
                    <button type="button" class="btn btn-primary" id="btnTambahItem"
                        title="Tambah Item Ceklist" aria-label="Tambah Item Ceklist">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Ringkasan Hasil --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="rekapTotal">{{ $pemeriksaan->total_item }}</div>
                    <div class="dash-stat-label">Total Item</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="rekapSesuai">{{ $pemeriksaan->total_sesuai }}</div>
                    <div class="dash-stat-label">Sesuai</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-triangle-exclamation"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="rekapTidakSesuai">{{ $pemeriksaan->total_tidak_sesuai }}</div>
                    <div class="dash-stat-label">Tidak Sesuai</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: var(--danger, #ef4444);"><i class="fas fa-circle-xmark"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" id="rekapTidakAda">{{ $pemeriksaan->total_tidak_ada }}</div>
                    <div class="dash-stat-label">Tidak Ada</div>
                </div>
            </div>
        </div>

        {{-- 3. Info Pemeriksaan --}}
        <div class="card" style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-7);">
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Lokasi</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $pemeriksaan->ruang_lab_nama ?: 'Semua Lokasi' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Petugas</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $pemeriksaan->petugas_nama ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Status</div>
                    <span class="badge {{ $pemeriksaan->status === 'final' ? 'badge-success' : 'badge-warning' }}" style="font-size: var(--text-xs);">
                        {{ $statusList[$pemeriksaan->status] ?? $pemeriksaan->status }}
                    </span>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Kesimpulan</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $pemeriksaan->kesimpulan ?: '—' }}</div>
                </div>
            </div>
            @if ($pemeriksaan->rekomendasi)
                <div style="margin-top: var(--space-7); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Rekomendasi</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $pemeriksaan->rekomendasi }}</div>
                </div>
            @endif
        </div>

        {{-- 4. Form Ceklist --}}
        <form id="formCeklist" data-id="{{ $pemeriksaan->id }}">
            @csrf
            <div class="card" style="padding: 0; overflow: hidden; margin-bottom: var(--space-9);">
                <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;">
                    <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                        <i class="fas fa-clipboard-check text-primary"></i> Daftar Ceklist Item
                    </h3>
                    <span class="badge badge-outline" style="font-size: var(--text-xs);">{{ $items->count() }} item</span>
                </div>

                <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                    <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color); ">
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 40px;">No</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Item</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Standar Kondisi</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center; width: 110px;">Jumlah</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center; width: 150px;">Hasil</th>
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Keterangan</th>
                @if ($canUpdate)
                                    <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right; width: 70px;">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $idx => $it)
                                <tr data-item-row="{{ $it->id }}" style="border-bottom: 1px solid var(--border-color);">
                                    <td data-label="No" style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-muted);">{{ $idx + 1 }}</td>
                                    <td data-label="Item" style="padding: var(--space-6) var(--space-7);">
                                        <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $it->nama_item }}</div>
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ $it->kode_item ?: '—' }}</div>
                                    </td>
                                    <td data-label="Standar" style="padding: var(--space-6) var(--space-7); font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $it->kondisi_standar ?: '—' }}
                                    </td>
                                    <td data-label="Jumlah" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <input type="number" step="0.01" min="0" class="form-control ck-jumlah"
                                            value="{{ $it->jumlah_diperiksa !== null ? (float) $it->jumlah_diperiksa : '' }}"
                                            style="height: 34px; text-align: center; font-size: var(--text-sm);"
                                            {{ $canUpdate ? '' : 'disabled' }}>
                                        <div style="font-size: var(--text-2xs); color: var(--text-muted); margin-top: var(--space-1);">{{ $it->satuan }}</div>
                                    </td>
                                    <td data-label="Hasil" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                        <select class="form-control ck-hasil" style="height: 34px; font-size: var(--text-sm);" {{ $canUpdate ? '' : 'disabled' }}>
                                            @foreach ($hasilList as $hk => $hv)
                                                <option value="{{ $hk }}" {{ $it->hasil === $hk ? 'selected' : '' }}>{{ $hv }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td data-label="Keterangan" style="padding: var(--space-6) var(--space-7);">
                                        <input type="text" class="form-control ck-keterangan" value="{{ $it->keterangan }}"
                                            placeholder="Catatan kondisi..." style="height: 34px; font-size: var(--text-sm);"
                                            {{ $canUpdate ? '' : 'disabled' }}>
                                        <input type="text" class="form-control ck-tindak" value="{{ $it->tindak_lanjut }}"
                                            placeholder="Tindak lanjut..." style="height: 34px; font-size: var(--text-sm); margin-top: var(--space-2);"
                                            {{ $canUpdate ? '' : 'disabled' }}>
                                    </td>
                                    @if ($canUpdate)
                                        <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                            <button type="button" class="btn-icon btn-hapus-item"
                                                data-id="{{ $it->id }}" data-nama="{{ $it->nama_item }}"
                                                title="Hapus Item" style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canUpdate ? 7 : 6 }}" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                        <i class="fas fa-inbox mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                        <div>Belum ada item ceklist. Tambahkan item untuk memulai pemeriksaan.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($canUpdate && $items->count() > 0)
                    <div style="padding: var(--space-8) var(--space-9); border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Hasil Ceklist</button>
                    </div>
                @endif
            </div>
        </form>

        {{-- Modal Tambah Item --}}
        <div id="modalTambahItem" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Tambah Item Ceklist</h3>
                    <button type="button" data-close="modalTambahItem" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formTambahItem" method="POST" action="{{ route('dashboard.laboran.pemeriksaan.tambah-item', $pemeriksaan->id) }}">
                    @csrf

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Pilih dari Inventaris</label>
                        <select name="item_id" id="tiItem" class="form-control">
                            <option value="">-- Atau isi manual di bawah --</option>
                            @foreach ($itemOptions as $io)
                                <option value="{{ $io->id }}">{{ $io->nama_item }} ({{ $io->kode_item }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Nama Item <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="nama_item" id="tiNama" class="form-control" placeholder="Nama item yang diperiksa">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Kode Item</label>
                            <input type="text" name="kode_item" id="tiKode" class="form-control">
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Satuan</label>
                            <input type="text" name="satuan" id="tiSatuan" class="form-control" placeholder="unit">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Standar Kondisi</label>
                        <textarea name="kondisi_standar" id="tiStandar" class="form-control" rows="2" placeholder="Kondisi ideal yang diharapkan..."></textarea>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Jumlah Diperiksa</label>
                        <input type="number" step="0.01" min="0" name="jumlah_diperiksa" id="tiJumlah" class="form-control">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalTambahItem">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Tambah Item</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-pemeriksaan.js') }}?v={{ file_exists(public_path('js/laboran-pemeriksaan.js')) ? filemtime(public_path('js/laboran-pemeriksaan.js')) : time() }}"></script>
        @endpush
    @endsection
