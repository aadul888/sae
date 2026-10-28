@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Rekapitulasi Supervisi Laboratorium.

    Halaman ini adalah "halaman muka ordner supervisi": satu lembar yang
    merekap 9 indikator tupoksi laboran beserta bukti fisiknya, angka kunci
    kondisi laboratorium, dan (pada mode lengkap) daftar tindak lanjut.

    Data disiapkan `LaboranDashboardController::cetak()`.
--}}

@section('doc_title', 'REKAPITULASI SUPERVISI LABORATORIUM')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; PERIODE: {{ strtoupper($periode ?? now()->translatedFormat('F Y')) }}
@endsection

@section('content')
    <div class="info-box">
        <div>
            <div class="info-item">
                <span class="info-label">Satuan Pendidikan:</span>
                <span class="info-val">{{ $sekolah->nama ?? '—' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Cakupan Jurusan:</span>
                <span class="info-val">{{ $jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'Semua Jurusan') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Petugas Laboran:</span>
                <span class="info-val">{{ $petugas['nama'] }}</span>
            </div>
        </div>
        <div>
            <div class="info-item">
                <span class="info-label">Kepala Program Keahlian:</span>
                <span class="info-val">{{ $kaprog->nama ?? '......................................' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="doc-summary-grid">
        <div class="doc-summary-card">
            <span class="lbl">Indikator Terpenuhi</span>
            <span class="val">{{ $indikatorTerpenuhi }} / {{ count($indikator) }}</span>
            <span class="sub">Indikator tupoksi laboran</span>
        </div>
        <div class="doc-summary-card">
            <span class="lbl">Total Bukti Fisik</span>
            <span class="val">{{ number_format($jumlahBukti, 0, ',', '.') }}</span>
            <span class="sub">Dokumen / catatan tercatat</span>
        </div>
        <div class="doc-summary-card">
            <span class="lbl">Laik Pakai</span>
            <span class="val">{{ $kondisiBaik }}%</span>
            <span class="sub">Dari total item inventaris</span>
        </div>
        <div class="doc-summary-card">
            <span class="lbl">Peminjaman Aktif</span>
            <span class="val">{{ number_format($pinjamanAktif, 0, ',', '.') }}</span>
            <span class="sub">{{ $pinjamTerlambat }} melewati tenggat</span>
        </div>
        <div class="doc-summary-card">
            <span class="lbl">Perawatan Jatuh Tempo</span>
            <span class="val">{{ number_format($kartuJatuhTempo, 0, ',', '.') }}</span>
            <span class="sub">≤ 30 hari ke depan</span>
        </div>
        <div class="doc-summary-card">
            <span class="lbl">Kebutuhan Kritis</span>
            <span class="val">{{ number_format($kebutuhanKritis, 0, ',', '.') }}</span>
            <span class="sub">Prioritas kritis belum tertangani</span>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-list-check"></i> A. Rekapitulasi 9 Indikator Tupoksi Laboran</div>
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Indikator Tupoksi</th>
                <th>Bukti Fisik / Dokumen</th>
                <th style="width: 70px;">Jumlah</th>
                <th style="width: 80px;">Satuan</th>
                <th style="width: 200px;">Catatan Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($indikator as $ind)
                <tr>
                    <td class="center font-mono">{{ $ind->no }}</td>
                    <td><strong>{{ $ind->tupoksi }}</strong></td>
                    <td>{{ $ind->bukti }}</td>
                    <td class="center font-bold">{{ number_format($ind->jumlah, 0, ',', '.') }}</td>
                    <td class="center">{{ $ind->satuan }}</td>
                    <td>{{ $ind->catatan }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="right">Jumlah Bukti Fisik</td>
                <td class="center">{{ number_format($jumlahBukti, 0, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    @if ($mode === 'lengkap')
        <div class="section-subhead"><i class="fas fa-triangle-exclamation"></i> B. Daftar Tindak Lanjut Prioritas</div>
        @if ($tindakLanjut->isEmpty())
            <div class="doc-trace">
                <i class="fas fa-circle-check"></i>
                Tidak ada temuan prioritas: stok mencukupi, tidak ada peminjaman melewati tenggat,
                kartu perawatan tidak jatuh tempo dalam 30 hari, dan tidak ada kebutuhan kritis.
            </div>
        @else
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 150px;">Kategori Temuan</th>
                        <th>Uraian</th>
                        <th style="width: 150px;">Ruang / Kelas</th>
                        <th style="width: 180px;">Rincian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tindakLanjut as $i => $t)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td><strong>{{ $t->kategori }}</strong></td>
                            <td>{{ $t->uraian }}</td>
                            <td>{{ $t->ruang }}</td>
                            <td>{{ $t->detail }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Dokumen ini merupakan rekapitulasi supervisi. Rincian bukti fisik tiap indikator
            dapat dicetak dari menu modul terkait (inventaris, kebutuhan, perawatan, pemeriksaan,
            pengadaan, kerusakan, arsip, jurnal, kebersihan) dan seluruhnya otomatis tercatat
            pada <strong>Arsip Dokumen &rarr; Riwayat Cetak</strong>.
        </div>
    @else
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> memuat angka kunci dan rekapitulasi 9 indikator.
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk menambahkan
            daftar tindak lanjut prioritas (stok menipis, peminjaman terlambat,
            perawatan jatuh tempo, kebutuhan kritis).
        </div>
    @endif
@endsection
