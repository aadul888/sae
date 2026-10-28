@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Kartu Peminjaman Alat Praktik.

    Mode RINGKASAN → rekap sirkulasi per status + daftar peminjaman.
    Mode LENGKAP   → rekap + rincian tiap alat yang dipinjam.

    Data disiapkan `LaboranPeminjamanController::cetak()`.
--}}

@section('doc_title', 'KARTU PEMINJAMAN ALAT PRAKTIK')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; JUMLAH: {{ $items->count() }} PEMINJAMAN
@endsection

@section('content')
    <div class="info-box">
        <div>
            <div class="info-item">
                <span class="info-label">Cakupan Jurusan:</span>
                <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Petugas Laboran:</span>
                <span class="info-val">{{ $petugas['nama'] }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Filter Diterapkan:</span>
                <span class="info-val">{{ $filterLabel ? implode(' • ', $filterLabel) : 'Tanpa filter (seluruh data)' }}</span>
            </div>
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Unit Sedang Dipinjam:</span>
                <span class="info-val">
                    <strong>{{ $items->whereIn('status', ['dipinjam', 'terlambat'])->sum('jumlah_item') }}</strong> unit
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Peminjaman Terlambat:</span>
                <span class="info-val"><strong>{{ $totalTerlambat }}</strong> kartu</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-layer-group"></i> A. Rekapitulasi Sirkulasi per Status</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Status Peminjaman</th>
                <th style="width: 110px;">Jumlah Kartu</th>
                <th style="width: 110px;">Jumlah Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapStatus as $i => $rs)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $rs->label }}</strong></td>
                    <td class="center font-bold">{{ $rs->jumlah }}</td>
                    <td class="center">{{ $rs->item }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada data peminjaman yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="center">{{ (int) $items->sum('jumlah_item') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-subhead"><i class="fas fa-hand-holding"></i> B. Daftar Kartu Peminjaman</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">Nomor Pinjam</th>
                <th style="width: 90px;">Tgl Pinjam</th>
                <th>Peminjam</th>
                <th style="width: 90px;">Tipe</th>
                <th>Keperluan</th>
                <th style="width: 70px;">Unit</th>
                <th style="width: 100px;">Tgl Kembali</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $p)
                @php
                    $terlambat = in_array($p->status, ['dipinjam', 'terlambat'], true)
                        && $p->tanggal_kembali_rencana
                        && $p->tanggal_kembali_rencana < now()->toDateString();
                @endphp
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td class="center font-mono">{{ $p->nomor_pinjam }}</td>
                    <td class="center">
                        {{ $p->tanggal_pinjam ? \Carbon\Carbon::parse($p->tanggal_pinjam)->format('d/m/Y') : '—' }}
                    </td>
                    <td>
                        <strong>{{ $p->peminjam_nama ?: '—' }}</strong>
                        @if (!empty($p->peminjam_kelas))
                            <div style="color: #4b5563;">{{ $p->peminjam_kelas }}</div>
                        @endif
                    </td>
                    <td class="center">{{ $tipeList[$p->peminjam_tipe] ?? ($p->peminjam_tipe ?: '—') }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($p->keperluan, 60) ?: '—' }}</td>
                    <td class="center font-bold">{{ (int) $p->jumlah_item }}</td>
                    <td class="center">
                        {{ $p->tanggal_kembali_rencana ? \Carbon\Carbon::parse($p->tanggal_kembali_rencana)->format('d/m/Y') : '—' }}
                        @if ($terlambat)
                            <div style="color: #b91c1c; font-weight: 700;">MELEWATI TENGGAT</div>
                        @endif
                    </td>
                    <td class="center">{{ $statusList[$p->status] ?? $p->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada data peminjaman yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($mode === 'ringkasan')
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat rekap sirkulasi dan daftar kartu peminjaman.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
            {{ $rincian->count() ?: 'seluruh' }} alat yang dipinjam beserta kondisinya.
        </div>
    @elseif ($rincian->isNotEmpty())
        <div class="section-subhead"><i class="fas fa-list"></i> C. Rincian Alat yang Dipinjam</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 130px;">Nomor Pinjam</th>
                    <th style="width: 80px;">Tanggal</th>
                    <th style="width: 110px;">Peminjam</th>
                    <th>Nama Alat</th>
                    <th style="width: 90px;">Kode</th>
                    <th style="width: 70px;">Jumlah</th>
                    <th style="width: 90px;">Kondisi Pinjam</th>
                    <th style="width: 90px;">Kondisi Kembali</th>
                    <th style="width: 120px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rincian as $i => $r)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $r->nomor_pinjam }}</td>
                        <td class="center">
                            {{ $r->tanggal_pinjam ? \Carbon\Carbon::parse($r->tanggal_pinjam)->format('d/m/Y') : '—' }}
                        </td>
                        <td>{{ $r->peminjam_nama ?: '—' }}</td>
                        <td><strong>{{ $r->nama_item }}</strong></td>
                        <td class="center font-mono">{{ $r->kode_item ?: '—' }}</td>
                        <td class="center">{{ (float) $r->jumlah }} {{ $r->satuan }}</td>
                        <td class="center">{{ $kondisiList[$r->kondisi_pinjam] ?? ($r->kondisi_pinjam ?: '—') }}</td>
                        <td class="center">{{ $kondisiList[$r->kondisi_kembali] ?? ($r->kondisi_kembali ?: '—') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->keterangan, 50) ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Tidak ada rincian alat pada peminjaman yang sesuai filter.
        </div>
    @endif
@endsection
