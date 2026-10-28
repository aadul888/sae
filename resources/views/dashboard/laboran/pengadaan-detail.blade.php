@extends('layouts.dashboard')

@section('title', 'Detail Usulan Pengadaan — Laboratorium SAE')
@section('dash_title', 'Detail Usulan Pengadaan')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(139,92,246,0.12); color: var(--purple, #8b5cf6); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        {{ $usulan->nomor_usulan }}
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        {{ \Carbon\Carbon::parse($usulan->tanggal_usulan)->translatedFormat('d F Y') }}
                        &bull; {{ \App\Http\Controllers\Laboran\LaboranPengadaanController::JENIS_PENGADAAN[$usulan->jenis ?? 'semua'] ?? 'Semua' }}
                        @if ($jurusan) &bull; {{ $jurusan->nama_jurusan }} @endif
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.pengadaan.index') }}" class="btn btn-outline"
                    title="Kembali ke Daftar Usulan" aria-label="Kembali ke Daftar Usulan">
                    <i class="fas fa-arrow-left"></i>
                </a>
                @if ($canUpdate)
                    <button type="button" class="btn btn-primary" id="btnTambahItemUsulan"
                        title="Tambah Item Usulan" aria-label="Tambah Item Usulan">
                        <i class="fas fa-plus"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Ringkasan --}}
        <div class="dash-stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: var(--space-9); gap: var(--space-7);">
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--primary, #3b82f6);"><i class="fas fa-list"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $usulan->total_item }}</div>
                    <div class="dash-stat-label">Jumlah Item</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(139, 92, 246, 0.15); color: var(--purple, #8b5cf6);"><i class="fas fa-money-bill-wave"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-md);">Rp {{ number_format((float) $usulan->total_estimasi_biaya, 0, ',', '.') }}</div>
                    <div class="dash-stat-label">Total Estimasi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning, #f59e0b);"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value">{{ $urgensiList[$usulan->urgensi] ?? $usulan->urgensi }}</div>
                    <div class="dash-stat-label">Tingkat Urgensi</div>
                </div>
            </div>
            <div class="dash-stat-card">
                <div class="dash-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success, #10b981);"><i class="fas fa-circle-check"></i></div>
                <div class="dash-stat-info">
                    <div class="dash-stat-value" style="font-size: var(--text-md);">{{ $statusList[$usulan->status] ?? $usulan->status }}</div>
                    <div class="dash-stat-label">Status Usulan</div>
                </div>
            </div>
        </div>

        {{-- 3. Info Usulan --}}
        <div class="card" style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-7);">
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Tahun Ajaran</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $usulan->tahun_ajaran ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Semester</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $usulan->semester === '1' ? 'Ganjil' : ($usulan->semester === '2' ? 'Genap' : '—') }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Jenis Pengadaan</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ \App\Http\Controllers\Laboran\LaboranPengadaanController::JENIS_PENGADAAN[$usulan->jenis ?? 'semua'] ?? 'Semua (Alat & Bahan)' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Tanggal Verifikasi</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ $usulan->tanggal_verifikasi ? \Carbon\Carbon::parse($usulan->tanggal_verifikasi)->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>
            </div>

            @if ($usulan->justifikasi)
                <div style="margin-top: var(--space-7); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Justifikasi</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $usulan->justifikasi }}</div>
                </div>
            @endif

            @if ($usulan->catatan_verifikasi)
                <div style="margin-top: var(--space-6); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Catatan Verifikasi</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $usulan->catatan_verifikasi }}</div>
                </div>
            @endif
        </div>

        {{-- 4. Tabel Item Usulan --}}
        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: var(--space-9);">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-boxes-stacked text-primary"></i> Rincian Item Pengadaan
                </h3>
                <span class="badge badge-outline" style="font-size: var(--text-xs);">{{ $items->count() }} item</span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 40px;">No</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nama Item</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Harga Satuan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Subtotal</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Prioritas</th>
                            @if ($canUpdate)
                                <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: right;">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $idx => $it)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td data-label="Nama" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $it->nama_item }}</div>
                                    @if ($it->spesifikasi)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted);">{{ \Illuminate\Support\Str::limit($it->spesifikasi, 70) }}</div>
                                    @endif
                                    @if ($it->keterangan)
                                        <div style="font-size: var(--text-xs); color: var(--text-muted); font-style: italic;">{{ $it->keterangan }}</div>
                                    @endif
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs); margin-top: var(--space-2);">
                                        {{ $it->jenis === 'alat' ? 'Alat' : 'Bahan Habis Pakai' }}
                                    </span>
                                </td>
                                <td data-label="Jumlah" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base);">
                                    {{ (float) $it->jumlah }} <span style="font-size: var(--text-2xs); color: var(--text-muted);">{{ $it->satuan }}</span>
                                </td>
                                <td data-label="Harga" style="padding: var(--space-6) var(--space-7); text-align: right; font-size: var(--text-sm); white-space: nowrap;">
                                    Rp {{ number_format((float) $it->harga_satuan, 0, ',', '.') }}
                                </td>
                                <td data-label="Subtotal" style="padding: var(--space-6) var(--space-7); text-align: right; font-size: var(--text-sm); font-weight: var(--font-bold); white-space: nowrap;">
                                    Rp {{ number_format((float) $it->subtotal, 0, ',', '.') }}
                                </td>
                                <td data-label="Prioritas" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $urgensiList[$it->prioritas] ?? $it->prioritas }}</span>
                                </td>
                @if ($canUpdate)
                                    <td data-label="Aksi" style="padding: var(--space-6) var(--space-7); text-align: right;">
                                        <div class="table-actions" style="justify-content: flex-end; gap: var(--space-3);">
                                            <button type="button" class="btn-icon btn-edit-item-usulan"
                                                data-usulan="{{ $usulan->id }}" data-id="{{ $it->id }}"
                                                data-nama="{{ $it->nama_item }}" data-jenis="{{ $it->jenis }}"
                                                data-spesifikasi="{{ $it->spesifikasi }}" data-jumlah="{{ (float) $it->jumlah }}"
                                                data-satuan="{{ $it->satuan }}" data-harga="{{ (float) $it->harga_satuan }}"
                                                data-prioritas="{{ $it->prioritas }}" data-keterangan="{{ $it->keterangan }}"
                                                title="Ubah Item">
                                                <i class="fas fa-pen-to-square" style="color: var(--primary);"></i>
                                            </button>
                                            <button type="button" class="btn-icon btn-hapus-item-usulan"
                                                data-usulan="{{ $usulan->id }}" data-id="{{ $it->id }}"
                                                data-nama="{{ $it->nama_item }}" title="Hapus Item"
                                                style="color: var(--danger, #ef4444);">
                                                <i class="fas fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canUpdate ? 7 : 6 }}" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-box-open mb-2" style="font-size: var(--text-3xl); opacity: 0.5;"></i>
                                    <div>Belum ada item. Tambahkan item untuk melengkapi usulan.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($items->count() > 0)
                        <tfoot>
                            <tr style="border-top: 2px solid var(--border-color); background: rgba(59,130,246,0.04);">
                                <td colspan="{{ $canUpdate ? 4 : 3 }}" style="padding: var(--space-6) var(--space-7); text-align: right; font-weight: var(--font-bold); font-size: var(--text-base);">
                                    Total Estimasi Biaya
                                </td>
                                <td style="padding: var(--space-6) var(--space-7); text-align: right; font-weight: var(--font-extrabold); font-size: var(--text-base); white-space: nowrap; color: var(--primary, #3b82f6);">
                                    Rp {{ number_format((float) $usulan->total_estimasi_biaya, 0, ',', '.') }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Modal Item Usulan --}}
        <div id="modalItemUsulan" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 620px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 id="modalItemUsulanTitle" style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Tambah Item Usulan</h3>
                    <button type="button" data-close="modalItemUsulan" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formItemUsulan" method="POST" action="{{ route('dashboard.laboran.pengadaan.item.store', $usulan->id) }}">
                    @csrf
                    <input type="hidden" name="_method" id="itemUsulanMethod" value="POST">

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Nama Item <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <input type="text" name="nama_item" id="iuNama" class="form-control" required placeholder="Nama alat / bahan">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jenis <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <select name="jenis" id="iuJenis" class="form-control" required>
                                <option value="alat">Alat</option>
                                <option value="bahan_habis_pakai">Bahan Habis Pakai</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Prioritas</label>
                            <select name="prioritas" id="iuPrioritas" class="form-control">
                                <option value="rendah">Rendah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Spesifikasi</label>
                        <textarea name="spesifikasi" id="iuSpesifikasi" class="form-control" rows="2" placeholder="Detail teknis, ukuran, merek yang diinginkan..."></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Jumlah <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" name="jumlah" id="iuJumlah" class="form-control" value="1" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                                Satuan <span style="color: var(--danger, #ef4444);">*</span>
                            </label>
                            <input type="text" name="satuan" id="iuSatuan" class="form-control" value="unit" required>
                        </div>
                        <div>
                            <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Harga Satuan</label>
                            <input type="number" step="0.01" min="0" name="harga_satuan" id="iuHarga" class="form-control" placeholder="0">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Keterangan</label>
                        <input type="text" name="keterangan" id="iuKeterangan" class="form-control" placeholder="Catatan tambahan">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalItemUsulan">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Item</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-pengadaan.js') }}?v={{ file_exists(public_path('js/laboran-pengadaan.js')) ? filemtime(public_path('js/laboran-pengadaan.js')) : time() }}"></script>
        @endpush
    @endsection
