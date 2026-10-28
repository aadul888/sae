@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Kartu Perawatan & Penataan Alat (K3).

    Mode RINGKASAN → rekap per jenis perawatan + kartu yang mendekati jatuh tempo.
    Mode LENGKAP   → rincian seluruh riwayat perawatan sesuai filter.

    Data disiapkan `LaboranPerawatanController::cetak()`.
--}}

@section('doc_title', 'KARTU PERAWATAN & PENATAAN ALAT (K3)')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; JUMLAH: {{ $items->count() }} KEGIATAN PERAWATAN
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
                <span class="info-label">Kegiatan Selesai:</span>
                <span class="info-val"><strong>{{ $items->where('status', 'selesai')->count() }}</strong> dari {{ $items->count() }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total Biaya Perawatan:</span>
                <span class="info-val">Rp {{ number_format((float) $items->sum('biaya'), 0, ',', '.') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-layer-group"></i> A. Rekapitulasi Perawatan per Jenis</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Jenis Perawatan</th>
                <th style="width: 100px;">Jumlah</th>
                <th style="width: 100px;">Selesai</th>
                <th style="width: 130px;">Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rekapJenis as $i => $rj)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $rj->label }}</strong></td>
                    <td class="center font-bold">{{ $rj->total }}</td>
                    <td class="center">{{ $rj->selesai }}</td>
                    <td class="right font-mono">Rp {{ number_format($rj->biaya, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada data perawatan yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="center">{{ $items->where('status', 'selesai')->count() }}</td>
                <td class="right">Rp {{ number_format((float) $items->sum('biaya'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($mode === 'ringkasan')
        <div class="section-subhead"><i class="fas fa-calendar-clock"></i> B. Kartu Perawatan Jatuh Tempo (≤ 30 hari)</div>
        <table class="table-data is-ringkasan">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 110px;">Kode Kartu</th>
                    <th>Nama Alat</th>
                    <th style="width: 130px;">Lokasi</th>
                    <th style="width: 80px;">Interval</th>
                    <th style="width: 110px;">Jadwal Berikutnya</th>
                    <th style="width: 130px;">Penanggung Jawab</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kartuJatuhTempo as $i => $k)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $k->kode_kartu }}</td>
                        <td><strong>{{ $k->nama_alat }}</strong></td>
                        <td>{{ $k->ruang_lab_nama ?: '—' }}</td>
                        <td class="center">{{ (int) $k->interval_hari }} hari</td>
                        <td class="center">
                            {{ $k->jadwal_berikutnya ? \Carbon\Carbon::parse($k->jadwal_berikutnya)->format('d/m/Y') : '—' }}
                        </td>
                        <td>{{ $k->penanggung_jawab_nama ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada kartu perawatan yang mendekati jatuh tempo.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat rekap jenis perawatan dan kartu yang mendekati
            jatuh tempo. Pilih <strong>Lengkap</strong> pada bar alat di atas untuk rincian
            {{ $items->count() }} kegiatan perawatan beserta uraian pekerjaan &amp; K3.
        </div>
    @else
        <div class="section-subhead"><i class="fas fa-list"></i> B. Rincian Seluruh Kegiatan Perawatan</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 100px;">Kode</th>
                    <th style="width: 80px;">Tanggal</th>
                    <th>Nama Alat &amp; Uraian Pekerjaan</th>
                    <th style="width: 100px;">Lokasi</th>
                    <th style="width: 90px;">Jenis</th>
                    <th style="width: 90px;">K3</th>
                    <th style="width: 100px;">Pelaksana</th>
                    <th style="width: 90px;">Kondisi Akhir</th>
                    <th style="width: 80px;">Status</th>
                    <th style="width: 90px;">Biaya</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $p)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $p->kode_perawatan }}</td>
                        <td class="center">
                            {{ $p->tanggal_perawatan ? \Carbon\Carbon::parse($p->tanggal_perawatan)->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            <strong>{{ $p->nama_alat }}</strong>
                            @if (!empty($p->uraian_pekerjaan))
                                <div style="color: #4b5563;">{{ \Illuminate\Support\Str::limit($p->uraian_pekerjaan, 100) }}</div>
                            @endif
                        </td>
                        <td>{{ $p->ruang_lab_nama ?: '—' }}</td>
                        <td>{{ $jenisList[$p->jenis_perawatan] ?? $p->jenis_perawatan }}</td>
                        <td>{{ $k3List[$p->kategori_k3] ?? ($p->kategori_k3 ?: '—') }}</td>
                        <td>{{ $p->pelaksana_nama ?: '—' }}</td>
                        <td class="center">{{ $kondisiList[$p->kondisi_akhir] ?? ($p->kondisi_akhir ?: '—') }}</td>
                        <td class="center">{{ $statusList[$p->status] ?? $p->status }}</td>
                        <td class="right font-mono">
                            {{ $p->biaya ? 'Rp ' . number_format((float) $p->biaya, 0, ',', '.') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Belum ada data perawatan yang sesuai filter.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
