@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Data Peserta Didik Jurusan.

    Mode RINGKASAN → rekapitulasi jumlah peserta didik per kelas/rombel.
    Mode LENGKAP   → daftar rinci seluruh peserta didik sesuai filter.

    Data disiapkan `LaboranPesertaDidikController::cetak()`.
--}}

@section('doc_title', 'DATA ' . strtoupper($tabLabel) . ' JURUSAN')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; JUMLAH: {{ $items->count() }} PESERTA DIDIK
@endsection

@section('content')
    <div class="info-box">
        <div>
            <div class="info-item">
                <span class="info-label">Cakupan Jurusan:</span>
                <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Kelompok Data:</span>
                <span class="info-val">{{ $tabLabel }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Petugas Laboran:</span>
                <span class="info-val">{{ $petugas['nama'] }}</span>
            </div>
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Filter Diterapkan:</span>
                <span class="info-val">{{ $filterLabel ? implode(' • ', $filterLabel) : 'Tanpa filter (seluruh data)' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Jumlah Kelas/Rombel:</span>
                <span class="info-val">{{ $rekap->count() }} kelas</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    @if ($mode === 'ringkasan')
        <div class="section-subhead"><i class="fas fa-table-cells"></i> A. Rekapitulasi Peserta Didik per Kelas</div>
        <table class="table-data is-ringkasan">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Kelas / Rombel</th>
                    <th style="width: 90px;">Laki-Laki</th>
                    <th style="width: 90px;">Perempuan</th>
                    <th style="width: 90px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rekap as $i => $r)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $r->kelas }}</strong></td>
                        <td class="center">{{ $r->laki }}</td>
                        <td class="center">{{ $r->perempuan }}</td>
                        <td class="center font-bold">{{ $r->total }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada data peserta didik yang sesuai filter.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="right">Jumlah</td>
                    <td class="center">{{ $items->where('jenis_kelamin', 'L')->count() }}</td>
                    <td class="center">{{ $items->where('jenis_kelamin', 'P')->count() }}</td>
                    <td class="center">{{ $items->count() }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> menampilkan rekapitulasi jumlah peserta didik per kelas.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk daftar rinci
            {{ $items->count() }} peserta didik beserta biodata pokoknya.
        </div>
    @else
        <div class="section-subhead"><i class="fas fa-users"></i> B. Daftar Rinci Peserta Didik</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Peserta Didik</th>
                    <th style="width: 34px;">L/P</th>
                    <th style="width: 90px;">NISN</th>
                    <th style="width: 80px;">NIPD</th>
                    <th style="width: 110px;">Kelas</th>
                    <th style="width: 130px;">Tempat, Tgl Lahir</th>
                    <th style="width: 100px;">Agama</th>
                    <th style="width: 110px;">Kontak</th>
                    <th style="width: 150px;">Alamat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $p)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td><strong>{{ $p->nama ?: '—' }}</strong></td>
                        <td class="center">{{ $p->jenis_kelamin ?: '—' }}</td>
                        <td class="center font-mono">{{ $p->nisn ?: '—' }}</td>
                        <td class="center font-mono">{{ $p->nipd ?: '—' }}</td>
                        <td>{{ $p->nama_rombel_terakhir ?: '—' }}</td>
                        <td>
                            {{ $p->tempat_lahir ?: '—' }}{{ $p->tanggal_lahir ? ', ' . \Carbon\Carbon::parse($p->tanggal_lahir)->format('d/m/Y') : '' }}
                        </td>
                        <td>{{ $p->agama_id_str ?: '—' }}</td>
                        <td>{{ $p->nomor_telepon_seluler ?: '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($p->alamat_jalan, 60) ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Tidak ada data peserta didik yang sesuai filter.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
@endsection
