@extends('layouts.dashboard')

@section('title', 'Detail Peminjaman Sarpras - Teknisi')

@section('content')
    @php
        $sc = match ($peminjaman->status) {
            'dikembalikan' => 'badge-success',
            'dipinjam' => 'badge-info',
            'disetujui' => 'badge-primary',
            'terlambat', 'ditolak' => 'badge-danger',
            default => 'badge-warning',
        };
    @endphp

    <x-sae.teknisi-header
        banner-title="{{ $peminjaman->nomor_pinjam }}"
        banner-desc="{{ $peminjaman->peminjam_nama }} — {{ $tipeList[$peminjaman->peminjam_tipe] ?? $peminjaman->peminjam_tipe }}"
        banner-icon="fas fa-hand-holding"
        banner-color="var(--info, #0ea5e9)"
        filter-route="{{ route('dashboard.teknisi.peminjaman.index') }}"
        :show-search="false"
        :per-page="15"
        search="">

        <thead>
            <tr style="border-bottom:1px solid var(--border-color);">
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; width:40px;">No</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase;">Item</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Sumber</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Jumlah</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Kondisi Pinjam</th>
                <th style="padding:12px 18px; font-size:var(--text-sm); font-weight:700; color:var(--text-muted); text-transform:uppercase; text-align:center;">Kondisi Kembali</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $idx => $it)
                <tr style="border-bottom:1px solid var(--border-color);">
                    <td style="padding:14px 18px; color:var(--text-muted);">{{ $idx + 1 }}</td>
                    <td style="padding:14px 18px;">
                        <div style="font-weight:700; color:var(--text-color);">{{ $it->nama_item }}</div>
                        <div style="font-size:var(--text-xs); color:var(--text-muted);">{{ $it->kode_item ?: '—' }}</div>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <span class="badge badge-outline" style="font-size:var(--text-2xs);">{{ $it->sumber === 'ruang' ? 'Ruang' : 'Aset' }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        {{ rtrim(rtrim(number_format((float) $it->jumlah, 2, '.', ''), '0'), '.') }}
                        <span style="font-size:var(--text-2xs); color:var(--text-muted);">{{ $it->satuan }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        <span class="badge badge-outline" style="font-size:var(--text-2xs);">{{ $kondisiList[$it->kondisi_pinjam] ?? $it->kondisi_pinjam }}</span>
                    </td>
                    <td style="padding:14px 18px; text-align:center;">
                        @if ($it->kondisi_kembali)
                            <span class="badge {{ in_array($it->kondisi_kembali, ['rusak_berat','hilang'], true) ? 'badge-danger' : ($it->kondisi_kembali === 'rusak_ringan' ? 'badge-warning' : 'badge-success') }}" style="font-size:var(--text-2xs);">
                                {{ $kondisiList[$it->kondisi_kembali] ?? $it->kondisi_kembali }}
                            </span>
                        @else
                            <span style="font-size:var(--text-xs); color:var(--text-muted);">Belum kembali</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center; padding:36px; color:var(--text-muted);">Tidak ada item pada peminjaman ini.</td></tr>
            @endforelse
        </tbody>
    </x-sae.teknisi-header>

    <div class="dash-content-inner" style="margin-top:-12px;">
        @if ($canUpdate && in_array($peminjaman->status, ['diajukan', 'disetujui', 'dipinjam', 'terlambat'], true))
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;">
                @if ($peminjaman->status === 'diajukan')
                    <button type="button" class="btn btn-primary btn-pinjam-setujui" data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"><i class="fas fa-check"></i> Setujui</button>
                @endif
                @if ($peminjaman->status === 'disetujui')
                    <button type="button" class="btn btn-primary btn-pinjam-serah" data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"><i class="fas fa-hand-holding"></i> Serah Terima</button>
                @endif
                @if (in_array($peminjaman->status, ['dipinjam', 'terlambat'], true))
                    <button type="button" class="btn btn-primary btn-pinjam-kembali" data-id="{{ $peminjaman->id }}" data-nama="{{ $peminjaman->peminjam_nama }}"><i class="fas fa-rotate-left"></i> Pengembalian</button>
                @endif
                <a href="{{ route('dashboard.teknisi.peminjaman.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        @endif

        <div class="card" style="padding:20px; margin-bottom:20px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px;">
                <div>
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Status</div>
                    <span class="badge {{ $sc }}" style="font-size:var(--text-xs);">{{ $statusList[$peminjaman->status] ?? $peminjaman->status }}</span>
                </div>
                <div>
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Kelas / Unit</div>
                    <div style="font-weight:600; color:var(--text-color);">{{ $peminjaman->peminjam_kelas ?: '—' }}</div>
                </div>
                <div>
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Tanggal Pinjam</div>
                    <div style="font-weight:600; color:var(--text-color);">{{ $peminjaman->tanggal_pinjam ? \Carbon\Carbon::parse($peminjaman->tanggal_pinjam)->translatedFormat('d M Y') : '—' }}</div>
                </div>
                <div>
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Rencana Kembali</div>
                    <div style="font-weight:600; color:var(--text-color);">{{ $peminjaman->tanggal_kembali_rencana ? \Carbon\Carbon::parse($peminjaman->tanggal_kembali_rencana)->translatedFormat('d M Y') : '—' }}</div>
                </div>
                <div>
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Lokasi</div>
                    <div style="font-weight:600; color:var(--text-color);">{{ $peminjaman->lokasi ?: '—' }}</div>
                </div>
            </div>
            <div style="margin-top:16px; padding-top:14px; border-top:1px solid var(--border-color);">
                <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Keperluan</div>
                <div style="font-size:var(--text-sm); color:var(--text-color);">{{ $peminjaman->keperluan }}</div>
            </div>
            @if ($peminjaman->catatan)
                <div style="margin-top:12px;">
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Catatan</div>
                    <div style="font-size:var(--text-sm); color:var(--text-color);">{{ $peminjaman->catatan }}</div>
                </div>
            @endif
            @if ($peminjaman->catatan_pengembalian)
                <div style="margin-top:12px;">
                    <div style="font-size:var(--text-2xs); text-transform:uppercase; font-weight:700; color:var(--text-muted);">Catatan Pengembalian</div>
                    <div style="font-size:var(--text-sm); color:var(--text-color);">{{ $peminjaman->catatan_pengembalian }}</div>
                </div>
            @endif
        </div>
    </div>

    @include('dashboard.teknisi._partials.modal-peminjaman-kembali', ['kondisiList' => $kondisiList])
@endsection

@push('scripts')
    @include('dashboard.teknisi._partials.scripts')
    <script src="{{ asset('js/teknisi-peminjaman.js') }}?v={{ file_exists(public_path('js/teknisi-peminjaman.js')) ? filemtime(public_path('js/teknisi-peminjaman.js')) : time() }}"></script>
@endpush
