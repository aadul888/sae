@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Daftar Usulan Pengadaan Alat & Bahan Praktik.

    Mode RINGKASAN → rekap usulan per status + daftar usulan.
    Mode LENGKAP   → rekap + rincian tiap item pada usulan.

    Data disiapkan `LaboranPengadaanController::cetak()`.
--}}

@section('doc_title', 'DAFTAR USULAN PENGADAAN ALAT & BAHAN PRAKTIK')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; FORMAT: A4 {{ strtoupper($orientasi) }}
    &bull; VERSI: {{ strtoupper($mode) }}
    &bull; JUMLAH: {{ $items->count() }} USULAN
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
                <span class="info-label">Total Nilai Usulan:</span>
                <span class="info-val">
                    Rp {{ number_format((float) $items->sum('total_estimasi_biaya'), 0, ',', '.') }}
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Disetujui / Direalisasi:</span>
                <span class="info-val">
                    <strong>{{ $items->whereIn('status', ['disetujui', 'direalisasi'])->count() }}</strong> usulan
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-layer-group"></i> A. Rekapitulasi Usulan per Status</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Status Usulan</th>
                <th style="width: 110px;">Jumlah</th>
                <th style="width: 170px;">Nilai Estimasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapStatus as $i => $rs)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $rs->label }}</strong></td>
                    <td class="center font-bold">{{ $rs->jumlah }}</td>
                    <td class="right font-mono">Rp {{ number_format($rs->biaya, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada usulan pengadaan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="right">Rp {{ number_format((float) $items->sum('total_estimasi_biaya'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-subhead"><i class="fas fa-file-invoice"></i> B. Daftar Usulan Pengadaan</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 140px;">Nomor Usulan</th>
                <th style="width: 90px;">Tanggal</th>
                <th style="width: 90px;">Jenis</th>
                <th style="width: 140px;">Pemohon</th>
                <th style="width: 70px;">Item</th>
                <th style="width: 160px;">Estimasi Biaya</th>
                <th style="width: 110px;">Sumber Dana</th>
                <th style="width: 80px;">Urgensi</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $u)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td class="center font-mono">{{ $u->nomor_usulan }}</td>
                    <td class="center">
                        {{ $u->tanggal_usulan ? \Carbon\Carbon::parse($u->tanggal_usulan)->format('d/m/Y') : '—' }}
                    </td>
                    <td>{{ $jenisList[$u->jenis] ?? ($u->jenis ?: '—') }}</td>
                    <td>{{ $u->pemohon_nama ?: '—' }}</td>
                    <td class="center">{{ (int) $u->total_item }}</td>
                    <td class="right font-mono">
                        Rp {{ number_format((float) $u->total_estimasi_biaya, 0, ',', '.') }}
                    </td>
                    <td>{{ $u->sumber_dana ?: '—' }}</td>
                    <td class="center">{{ $urgensiList[$u->urgensi] ?? ($u->urgensi ?: '—') }}</td>
                    <td class="center">{{ $statusList[$u->status] ?? $u->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada usulan pengadaan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($mode === 'ringkasan')
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat rekap per status dan daftar usulan.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
            {{ $rincian->count() ?: 'seluruh' }} item pada setiap usulan beserta harga satuan.
        </div>
    @elseif ($rincian->isNotEmpty())
        <div class="section-subhead"><i class="fas fa-list"></i> C. Rincian Item Usulan Pengadaan</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 130px;">Nomor Usulan</th>
                    <th>Nama Alat / Bahan</th>
                    <th style="width: 80px;">Jenis</th>
                    <th style="width: 120px;">Spesifikasi</th>
                    <th style="width: 70px;">Jumlah</th>
                    <th style="width: 110px;">Harga Satuan</th>
                    <th style="width: 120px;">Subtotal</th>
                    <th style="width: 80px;">Prioritas</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rincian as $i => $r)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $r->nomor_usulan }}</td>
                        <td><strong>{{ $r->nama_item }}</strong></td>
                        <td class="center">{{ $jenisList[$r->jenis] ?? ($r->jenis ?: '—') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->spesifikasi, 50) ?: '—' }}</td>
                        <td class="center">{{ (float) $r->jumlah }} {{ $r->satuan }}</td>
                        <td class="right font-mono">Rp {{ number_format((float) $r->harga_satuan, 0, ',', '.') }}</td>
                        <td class="right font-mono">Rp {{ number_format((float) $r->subtotal, 0, ',', '.') }}</td>
                        <td class="center">{{ $urgensiList[$r->prioritas] ?? ($r->prioritas ?: '—') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7" class="right">Jumlah</td>
                    <td class="right">Rp {{ number_format((float) $rincian->sum('subtotal'), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Tidak ada rincian item pada usulan pengadaan yang sesuai filter.
        </div>
    @endif
@endsection
