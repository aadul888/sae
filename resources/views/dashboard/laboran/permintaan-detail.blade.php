@extends('layouts.dashboard')

@section('title', 'Detail Bon Permintaan — Laboratorium SAE')
@section('dash_title', 'Detail Bon Permintaan')

@section('content')
    <div class="dash-content-inner">
        {{-- Banner --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(14,165,233,0.12); color: var(--info, #0ea5e9); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        {{ $permintaan->nomor_permintaan }}
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        {{ $permintaan->pemohon_nama }}
                        &bull; {{ \Carbon\Carbon::parse($permintaan->tanggal_permintaan)->translatedFormat('d F Y') }}
                        @if ($jurusan) &bull; {{ $jurusan->nama_jurusan }} @endif
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions" style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                <a href="{{ route('dashboard.laboran.permintaan.index') }}" class="btn btn-outline"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Kembali">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <a href="{{ route('dashboard.laboran.permintaan.cetak', $permintaan->id) }}" target="_blank" class="btn btn-primary"
                    style="width: 38px; height: 38px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); font-size: var(--text-md); text-decoration: none;"
                    title="Cetak Bon Permintaan">
                    <i class="fas fa-print"></i>
                </a>
            </div>
        </div>

        {{-- Info --}}
        <div class="card" style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-7);">
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Status</div>
                    <span class="badge {{ in_array($permintaan->status, ['diserahkan','selesai']) ? 'badge-success' : ($permintaan->status === 'ditolak' ? 'badge-danger' : 'badge-warning') }}" style="font-size: var(--text-xs);">
                        {{ $statusList[$permintaan->status] ?? $permintaan->status }}
                    </span>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Kelas / Rombel</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $permintaan->rombel_nama ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Mata Pelajaran</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $permintaan->mata_pelajaran ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Lokasi</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $permintaan->ruang_lab_nama ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Dibutuhkan</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ $permintaan->tanggal_dibutuhkan ? \Carbon\Carbon::parse($permintaan->tanggal_dibutuhkan)->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Petugas Laboran</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $permintaan->petugas_nama ?: '—' }}</div>
                </div>
            </div>

            @if ($permintaan->keperluan)
                <div style="margin-top: var(--space-7); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Keperluan</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $permintaan->keperluan }}</div>
                </div>
            @endif

            @if ($permintaan->catatan_pemohon)
                <div style="margin-top: var(--space-6);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Catatan Pemohon</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $permintaan->catatan_pemohon }}</div>
                </div>
            @endif
        </div>

        {{-- Tabel Item --}}
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-boxes-stacked text-primary"></i> Rincian Alat &amp; Bahan Diminta
                </h3>
                <div style="display: flex; gap: var(--space-3); flex-wrap: wrap;">
                    @if ($canUpdate)
                        <button type="button" class="btn btn-outline" id="btnPeriksaKetersediaan"
                            style="padding: 6px 12px; font-size: var(--text-sm); border-radius: var(--radius-md);"
                            title="Periksa Ketersediaan Stok">
                            <i class="fas fa-magnifying-glass"></i> Periksa Stok
                        </button>
                        @if (!in_array($permintaan->status, ['diserahkan','selesai']))
                            <button type="button" class="btn btn-primary" id="btnSerahkan"
                                style="padding: 6px 12px; font-size: var(--text-sm); border-radius: var(--radius-md);"
                                title="Catat Serah Terima">
                                <i class="fas fa-hand-holding"></i> Serah Terima
                            </button>
                        @endif
                    @endif
                </div>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 40px;">No</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nama Item</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Diminta</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Diberikan</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Ketersediaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $idx => $it)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td data-label="Item" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $it->nama_item }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $it->kode_item ?: '—' }} &bull; {{ $jenisList[$it->jenis] ?? $it->jenis }}
                                    </div>
                                </td>
                                <td data-label="Diminta" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base);">
                                    {{ (float) $it->jumlah_diminta }} <span style="font-size: var(--text-2xs); color: var(--text-muted);">{{ $it->satuan }}</span>
                                </td>
                                <td data-label="Diberikan" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base); font-weight: var(--font-bold);">
                                    {{ $it->jumlah_diberikan !== null ? (float) $it->jumlah_diberikan : '—' }}
                                </td>
                                <td data-label="Ketersediaan" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @php
                                        $kc = match ($it->status_ketersediaan) {
                                            'tersedia', 'diserahkan' => 'badge-success',
                                            'kurang' => 'badge-warning',
                                            'tidak_ada' => 'badge-danger',
                                            default => 'badge-outline',
                                        };
                                    @endphp
                                    <span class="badge {{ $kc }}" style="font-size: var(--text-2xs);">{{ $ketersediaanList[$it->status_ketersediaan] ?? $it->status_ketersediaan }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <div>Tidak ada item pada permintaan ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal Serah Terima --}}
        <div id="modalSerah" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Serah Terima Alat &amp; Bahan</h3>
                    <button type="button" data-close="modalSerah" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formSerah" method="POST" action="{{ route('dashboard.laboran.permintaan.serahkan', $permintaan->id) }}">
                    @csrf
                    <p style="font-size: var(--text-sm); color: var(--text-muted); margin-bottom: var(--space-7);">
                        Tentukan jumlah yang benar-benar diberikan. Bahan habis pakai akan mengurangi stok otomatis.
                    </p>

                    @foreach ($items as $it)
                        <div style="display: flex; align-items: center; gap: var(--space-4); padding: var(--space-4) 0; border-bottom: 1px dashed var(--border-color);">
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-size: var(--text-sm); font-weight: var(--font-semibold); color: var(--text-color);">{{ $it->nama_item }}</div>
                                <div style="font-size: var(--text-xs); color: var(--text-muted);">Diminta {{ (float) $it->jumlah_diminta }} {{ $it->satuan }}</div>
                            </div>
                            <input type="number" step="0.01" min="0" name="item_diberikan[{{ $it->id }}]"
                                value="{{ (float) $it->jumlah_diminta }}" class="form-control"
                                style="width: 100px; height: 34px; font-size: var(--text-sm);" title="Jumlah diberikan">
                        </div>
                    @endforeach

                    <div class="form-group" style="margin: var(--space-7) 0 var(--space-9) 0;">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Petugas</label>
                        <textarea name="catatan_petugas" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalSerah">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Konfirmasi Serah Terima</button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-permintaan.js') }}?v={{ file_exists(public_path('js/laboran-permintaan.js')) ? filemtime(public_path('js/laboran-permintaan.js')) : time() }}"></script>
        @endpush
    </div>
@endsection
