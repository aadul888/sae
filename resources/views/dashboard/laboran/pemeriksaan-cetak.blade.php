@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Checklist Pemeriksaan Alat & Bahan.

    Mode RINGKASAN → rekap hasil pemeriksaan per jenis.
    Mode LENGKAP   → rekap + rincian tiap item yang diperiksa.

    Data disiapkan `LaboranPemeriksaanController::cetak()`.
--}}

@section('doc_title', 'CHECKLIST PEMERIKSAAN ALAT & BAHAN')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; FORMAT: A4 {{ strtoupper($orientasi) }}
    &bull; VERSI: {{ strtoupper($mode) }}
    &bull; JUMLAH: {{ $items->count() }} BERKAS PEMERIKSAAN
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
                <span class="info-label">Total Item Diperiksa:</span>
                <span class="info-val">{{ (int) $items->sum('total_item') }} item</span>
            </div>
            <div class="info-item">
                <span class="info-label">Temuan Tidak Sesuai:</span>
                <span class="info-val">
                    <strong>{{ (int) $items->sum('total_tidak_sesuai') }}</strong> tidak sesuai •
                    <strong>{{ (int) $items->sum('total_tidak_ada') }}</strong> tidak ditemukan
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-layer-group"></i> A. Rekapitulasi Pemeriksaan per Jenis</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Jenis Pemeriksaan</th>
                <th style="width: 100px;">Berkas</th>
                <th style="width: 100px;">Item Sesuai</th>
                <th style="width: 110px;">Tidak Sesuai</th>
                <th style="width: 110px;">Tidak Ditemukan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapJenis as $i => $rj)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $rj->label }}</strong></td>
                    <td class="center font-bold">{{ $rj->total }}</td>
                    <td class="center">{{ $rj->sesuai }}</td>
                    <td class="center">{{ $rj->tidak }}</td>
                    <td class="center">{{ $rj->hilang }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada berkas pemeriksaan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="center">{{ (int) $items->sum('total_sesuai') }}</td>
                <td class="center">{{ (int) $items->sum('total_tidak_sesuai') }}</td>
                <td class="center">{{ (int) $items->sum('total_tidak_ada') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-subhead"><i class="fas fa-clipboard-check"></i> B. Daftar Berkas Pemeriksaan</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 130px;">Nomor Pemeriksaan</th>
                <th style="width: 90px;">Tanggal</th>
                <th>Ruang / Lokasi</th>
                <th style="width: 100px;">Jenis</th>
                <th style="width: 130px;">Petugas</th>
                <th style="width: 70px;">Item</th>
                <th style="width: 70px;">Sesuai</th>
                <th style="width: 70px;">Tidak</th>
                <th style="width: 70px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $p)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td class="center font-mono">{{ $p->nomor_pemeriksaan }}</td>
                    <td class="center">
                        {{ $p->tanggal_pemeriksaan ? \Carbon\Carbon::parse($p->tanggal_pemeriksaan)->format('d/m/Y') : '—' }}
                    </td>
                    <td><strong>{{ $p->ruang_lab_nama ?: '—' }}</strong></td>
                    <td>{{ $jenisList[$p->jenis] ?? $p->jenis }}</td>
                    <td>{{ $p->petugas_nama ?: '—' }}</td>
                    <td class="center">{{ (int) $p->total_item }}</td>
                    <td class="center">{{ (int) $p->total_sesuai }}</td>
                    <td class="center">
                        {{ (int) $p->total_tidak_sesuai + (int) $p->total_tidak_ada }}
                    </td>
                    <td class="center">{{ $statusList[$p->status] ?? $p->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada berkas pemeriksaan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($mode === 'ringkasan')
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat rekap per jenis dan daftar berkas pemeriksaan.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
            {{ $rincian->count() ?: 'seluruh' }} item yang diperiksa beserta hasil tiap item.
        </div>
    @elseif ($rincian->isNotEmpty())
        <div class="section-subhead"><i class="fas fa-list"></i> C. Rincian Item yang Diperiksa</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 120px;">Nomor Berkas</th>
                    <th style="width: 80px;">Tanggal</th>
                    <th style="width: 110px;">Lokasi</th>
                    <th>Nama Item</th>
                    <th style="width: 90px;">Kode</th>
                    <th style="width: 60px;">Jml</th>
                    <th style="width: 90px;">Standar</th>
                    <th style="width: 90px;">Hasil</th>
                    <th style="width: 120px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rincian as $i => $r)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $r->nomor_pemeriksaan }}</td>
                        <td class="center">
                            {{ $r->tanggal_pemeriksaan ? \Carbon\Carbon::parse($r->tanggal_pemeriksaan)->format('d/m/Y') : '—' }}
                        </td>
                        <td>{{ $r->ruang_lab_nama ?: '—' }}</td>
                        <td><strong>{{ $r->nama_item }}</strong></td>
                        <td class="center font-mono">{{ $r->kode_item ?: '—' }}</td>
                        <td class="center">{{ (float) $r->jumlah_diperiksa }} {{ $r->satuan }}</td>
                        <td>{{ $r->kondisi_standar ?: '—' }}</td>
                        <td class="center">{{ $hasilList[$r->hasil] ?? ($r->hasil ?: '—') }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->keterangan, 50) ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Tidak ada rincian item pada berkas pemeriksaan yang sesuai filter.
        </div>
    @endif
@endsection
