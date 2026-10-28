@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Analisis & Kebutuhan Alat/Bahan Praktik.

    Mode RINGKASAN → rekap jumlah & selisih kebutuhan per prioritas.
    Mode LENGKAP   → rincian seluruh baris kebutuhan sesuai filter.

    Data disiapkan `LaboranKebutuhanController::cetak()`.
--}}

@section('doc_title', 'ANALISIS & KEBUTUHAN ALAT/BAHAN PRAKTIK')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; JUMLAH: {{ $items->count() }} USULAN KEBUTUHAN
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
                <span class="info-label">Total Kebutuhan:</span>
                <span class="info-val">{{ $items->count() }} item</span>
            </div>
            <div class="info-item">
                <span class="info-label">Prioritas Kritis:</span>
                <span class="info-val"><strong>{{ $items->where('prioritas', 'kritis')->count() }}</strong> item</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-layer-group"></i> A. Rekapitulasi Kebutuhan per Prioritas</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Prioritas</th>
                <th style="width: 110px;">Jumlah Item</th>
                <th style="width: 110px;">Total Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapPrioritas as $i => $rp)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $rp->label }}</strong></td>
                    <td class="center font-bold">{{ $rp->jumlah }}</td>
                    <td class="center font-mono">{{ number_format($rp->selisih, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada data kebutuhan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="center">{{ number_format((float) $items->sum('selisih'), 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($mode === 'ringkasan')
        @php
            $prioritasMendesak = $items->whereIn('prioritas', ['kritis', 'tinggi'])->values();
        @endphp
        <div class="section-subhead"><i class="fas fa-fire"></i> B. Kebutuhan Mendesak (Kritis &amp; Tinggi)</div>
        <table class="table-data is-ringkasan">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Nama Alat / Bahan</th>
                    <th style="width: 90px;">Jenis</th>
                    <th style="width: 120px;">Lokasi</th>
                    <th style="width: 80px;">Butuh</th>
                    <th style="width: 80px;">Tersedia</th>
                    <th style="width: 80px;">Selisih</th>
                    <th style="width: 90px;">Prioritas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($prioritasMendesak as $i => $k)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $k->nama_item }}</strong></td>
                        <td class="center">{{ $jenisList[$k->jenis] ?? $k->jenis }}</td>
                        <td>{{ $k->ruang_lab_nama ?: '—' }}</td>
                        <td class="center">{{ (float) $k->jumlah_kebutuhan }} {{ $k->satuan }}</td>
                        <td class="center">{{ (float) $k->jumlah_tersedia }}</td>
                        <td class="center font-bold">{{ (float) $k->selisih }}</td>
                        <td class="center">{{ $prioritasList[$k->prioritas] ?? $k->prioritas }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada kebutuhan berprioritas kritis/tinggi pada filter ini.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat rekap per prioritas dan daftar kebutuhan mendesak.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
            {{ $items->count() }} baris kebutuhan beserta analisis &amp; status verifikasinya.
        </div>
    @else
        <div class="section-subhead"><i class="fas fa-list"></i> B. Rincian Seluruh Kebutuhan</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Alat / Bahan</th>
                    <th style="width: 80px;">Jenis</th>
                    <th style="width: 100px;">Lokasi</th>
                    <th style="width: 90px;">Spesifikasi</th>
                    <th style="width: 70px;">Butuh</th>
                    <th style="width: 70px;">Ada</th>
                    <th style="width: 60px;">Selisih</th>
                    <th style="width: 80px;">Prioritas</th>
                    <th style="width: 90px;">Status</th>
                    <th style="width: 100px;">Tgl Input</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $k)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $k->nama_item }}</strong>
                            @if (!empty($k->analisis_kebutuhan))
                                <div style="color: #4b5563;">{{ \Illuminate\Support\Str::limit($k->analisis_kebutuhan, 90) }}</div>
                            @endif
                        </td>
                        <td class="center">{{ $jenisList[$k->jenis] ?? $k->jenis }}</td>
                        <td>{{ $k->ruang_lab_nama ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($k->spesifikasi, 50) ?: '—' }}</td>
                        <td class="center">{{ (float) $k->jumlah_kebutuhan }} {{ $k->satuan }}</td>
                        <td class="center">{{ (float) $k->jumlah_tersedia }}</td>
                        <td class="center font-bold">{{ (float) $k->selisih }}</td>
                        <td class="center">{{ $prioritasList[$k->prioritas] ?? $k->prioritas }}</td>
                        <td class="center">{{ $statusList[$k->status] ?? $k->status }}</td>
                        <td class="center">
                            {{ $k->tanggal_input ? \Carbon\Carbon::parse($k->tanggal_input)->format('d/m/Y') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Belum ada data kebutuhan yang sesuai filter.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
