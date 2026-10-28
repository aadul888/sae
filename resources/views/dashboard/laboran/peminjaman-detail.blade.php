@extends('layouts.dashboard')

@section('title', 'Detail Peminjaman Alat — Laboratorium SAE')
@section('dash_title', 'Detail Peminjaman')

@section('content')
    @include('dashboard.laboran.partials.header-actions')
    <div class="dash-content-inner">
        {{-- 1. Banner Header --}}
        <div class="dash-banner"
            style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-8); flex-wrap: wrap; margin-bottom: var(--space-9);">
            <div style="display: flex; align-items: center; gap: var(--space-7);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(14,165,233,0.12); color: var(--info, #0ea5e9); display: flex; align-items: center; justify-content: center; font-size: var(--text-2xl); flex-shrink: 0;">
                    <i class="fas fa-hand-holding"></i>
                </div>
                <div>
                    <h2 style="font-size: var(--text-xl); font-weight: var(--font-extrabold); color: var(--text-color); margin: 0;">
                        {{ $peminjaman->nomor_pinjam }}
                    </h2>
                    <div style="font-size: var(--text-sm); color: var(--text-muted); margin-top: var(--space-1);">
                        {{ $peminjaman->peminjam_nama }}
                        &bull; {{ $tipeList[$peminjaman->peminjam_tipe] ?? $peminjaman->peminjam_tipe }}
                        @if ($jurusan) &bull; {{ $jurusan->nama_jurusan }} @endif
                    </div>
                </div>
            </div>
            <div class="dash-banner-actions dash-banner-access">
                <a href="{{ route('dashboard.laboran.peminjaman-alat.index') }}" class="btn btn-outline"
                    title="Kembali ke Daftar Peminjaman" aria-label="Kembali ke Daftar Peminjaman">
                    <i class="fas fa-arrow-left"></i>
                </a>
                @if ($canUpdate && $peminjaman->status === 'diajukan')
                    <button type="button" class="btn btn-primary btn-setujui-pinjam"
                        data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"
                        title="Setujui Peminjaman" aria-label="Setujui Peminjaman">
                        <i class="fas fa-check"></i>
                    </button>
                @endif
                @if ($canUpdate && $peminjaman->status === 'disetujui')
                    <button type="button" class="btn btn-primary btn-serah-terima"
                        data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"
                        title="Serah Terima Alat" aria-label="Serah Terima Alat">
                        <i class="fas fa-hand-holding"></i>
                    </button>
                @endif
                @if ($canUpdate && in_array($peminjaman->status, ['dipinjam', 'terlambat'], true))
                    <button type="button" class="btn btn-primary btn-kembalikan-pinjam"
                        data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"
                        title="Proses Pengembalian" aria-label="Proses Pengembalian">
                        <i class="fas fa-rotate-left"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- 2. Info Peminjaman --}}
        <div class="card" style="padding: var(--space-8) var(--space-9); margin-bottom: var(--space-9);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-7);">
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Status</div>
                    @php
                        $sc = match ($peminjaman->status) {
                            'dikembalikan' => 'badge-success',
                            'dipinjam' => 'badge-info',
                            'disetujui' => 'badge-primary',
                            'terlambat', 'ditolak' => 'badge-danger',
                            default => 'badge-warning',
                        };
                    @endphp
                    <span class="badge {{ $sc }}" style="font-size: var(--text-xs);">{{ $statusList[$peminjaman->status] ?? $peminjaman->status }}</span>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Kelas / Unit</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $peminjaman->peminjam_kelas ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Tanggal Pinjam</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ $peminjaman->tanggal_pinjam ? \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Rencana Kembali</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ $peminjaman->tanggal_kembali_rencana ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_rencana)->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Kembali Aktual</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">
                        {{ $peminjaman->tanggal_kembali_aktual ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_aktual)->translatedFormat('d M Y') : '—' }}
                    </div>
                </div>
                <div>
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-1);">Lokasi</div>
                    <div style="font-size: var(--text-base); font-weight: var(--font-semibold); color: var(--text-color);">{{ $peminjaman->ruang_lab_nama ?: '—' }}</div>
                </div>
            </div>

            <div style="margin-top: var(--space-7); padding-top: var(--space-6); border-top: 1px solid var(--border-color);">
                <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Keperluan</div>
                <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $peminjaman->keperluan }}</div>
            </div>

            @if ($peminjaman->catatan)
                <div style="margin-top: var(--space-6);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Catatan</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $peminjaman->catatan }}</div>
                </div>
            @endif

            @if ($peminjaman->catatan_pengembalian)
                <div style="margin-top: var(--space-6);">
                    <div style="font-size: var(--text-2xs); text-transform: uppercase; font-weight: var(--font-bold); color: var(--text-muted); margin-bottom: var(--space-2);">Catatan Pengembalian</div>
                    <div style="font-size: var(--text-sm); color: var(--text-color);">{{ $peminjaman->catatan_pengembalian }}</div>
                </div>
            @endif
        </div>

        {{-- 3. Tabel Item --}}
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: var(--space-8) var(--space-9); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap;">
                <h3 style="font-size: var(--text-md); font-weight: var(--font-bold); margin: 0; color: var(--text-color); display: flex; align-items: center; gap: var(--space-3);">
                    <i class="fas fa-boxes-stacked text-primary"></i> Alat yang Dipinjam
                </h3>
                <span class="badge badge-outline" style="font-size: var(--text-xs);">{{ $items->count() }} item</span>
            </div>

            <div class="table-responsive-stack" style="overflow-x: auto; width: 100%;">
                <table class="table table-pd" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-color); background: rgba(255, 255, 255, 0.02);">
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; width: 40px;">No</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase;">Nama Alat</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Jumlah</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi Pinjam</th>
                            <th style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); font-weight: var(--font-bold); color: var(--text-muted); text-transform: uppercase; text-align: center;">Kondisi Kembali</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $idx => $it)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td data-label="No" style="padding: var(--space-6) var(--space-7); font-size: var(--text-sm); color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td data-label="Alat" style="padding: var(--space-6) var(--space-7);">
                                    <div style="font-weight: var(--font-bold); color: var(--text-color); font-size: var(--text-base);">{{ $it->nama_item }}</div>
                                    <div style="font-size: var(--text-xs); color: var(--text-muted);">
                                        {{ $it->kode_item ?: '—' }}
                                        @if (($it->sumber ?? 'inventaris') === 'aset')
                                            &bull; <span class="badge badge-outline" style="font-size: var(--text-2xs);">Aset Sarpras</span>
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Jumlah" style="padding: var(--space-6) var(--space-7); text-align: center; font-size: var(--text-base);">
                                    {{ rtrim(rtrim(number_format((float) $it->jumlah, 2, '.', ''), '0'), '.') }} <span style="font-size: var(--text-2xs); color: var(--text-muted);">{{ $it->satuan }}</span>
                                </td>
                                <td data-label="Kondisi Pinjam" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    <span class="badge badge-outline" style="font-size: var(--text-2xs);">{{ $kondisiList[$it->kondisi_pinjam] ?? $it->kondisi_pinjam }}</span>
                                </td>
                                <td data-label="Kondisi Kembali" style="padding: var(--space-6) var(--space-7); text-align: center;">
                                    @if ($it->kondisi_kembali)
                                        @php
                                            $kc = in_array($it->kondisi_kembali, ['rusak_berat', 'hilang'], true)
                                                ? 'badge-danger'
                                                : ($it->kondisi_kembali === 'rusak_ringan' ? 'badge-warning' : 'badge-success');
                                        @endphp
                                        <span class="badge {{ $kc }}" style="font-size: var(--text-2xs);">{{ $kondisiList[$it->kondisi_kembali] ?? $it->kondisi_kembali }}</span>
                                    @else
                                        <span style="font-size: var(--text-xs); color: var(--text-muted);">Belum kembali</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: var(--space-11); text-align: center; color: var(--text-muted);">
                                    <div>Tidak ada item pada peminjaman ini.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal Pengembalian --}}
        <div id="modalKembali" class="modal-backdrop"
            style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 99999 !important; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
            <div class="card" style="width: 100%; max-width: 520px; padding: var(--space-10); border-radius: var(--radius-lg);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-9);">
                    <h3 style="margin: 0; font-size: var(--text-xl); font-weight: var(--font-bold); color: var(--text-color);">Proses Pengembalian</h3>
                    <button type="button" data-close="modalKembali" style="background: none; border: none; font-size: var(--text-xl); color: var(--text-muted); cursor: pointer;" title="Tutup">&times;</button>
                </div>

                <form id="formKembali" method="POST" action="">
                    @csrf
                    <p id="kembaliDesc" style="font-size: var(--text-base); color: var(--text-color); margin-bottom: var(--space-7);"></p>

                    <div class="form-group" style="margin-bottom: var(--space-6);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">
                            Kondisi Alat Saat Kembali <span style="color: var(--danger, #ef4444);">*</span>
                        </label>
                        <select name="kondisi_kembali" id="kembaliKondisi" class="form-control" required>
                            @foreach ($kondisiList as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: var(--space-9);">
                        <label style="display: block; font-size: var(--text-base); font-weight: var(--font-semibold); margin-bottom: var(--space-3); color: var(--text-color);">Catatan Pengembalian</label>
                        <textarea name="catatan_pengembalian" id="kembaliCatatan" class="form-control" rows="2"></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-4);">
                        <button type="button" class="btn btn-secondary" data-close="modalKembali">Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Konfirmasi Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

        @push('scripts')
            <script src="{{ asset('js/laboran-common.js') }}?v={{ file_exists(public_path('js/laboran-common.js')) ? filemtime(public_path('js/laboran-common.js')) : time() }}"></script>
            <script src="{{ asset('js/laboran-peminjaman.js') }}?v={{ file_exists(public_path('js/laboran-peminjaman.js')) ? filemtime(public_path('js/laboran-peminjaman.js')) : time() }}"></script>
        @endpush
    @endsection
