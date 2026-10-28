@extends('layouts.laboran-cetak')

{{--
    Dokumen cetak A4 — Daftar Arsip Dokumen (Ordner Digital).

    Mode RINGKASAN → peta ordner: rekap jumlah arsip per kategori.
    Mode LENGKAP   → peta ordner + daftar rinci arsip + riwayat cetak
                     (jejak dokumen yang pernah dicetak).

    Data disiapkan `LaboranArsipController::cetak()`.
--}}

@section('doc_title', 'DAFTAR ARSIP DOKUMEN LABORATORIUM')

@section('doc_subtitle')
    {{ strtoupper($jurusan->nama_jurusan ?? ($scopeBadge['label'] ?? 'SEMUA JURUSAN')) }}
    &bull; FORMAT: A4 {{ strtoupper($orientasi) }}
    &bull; VERSI: {{ strtoupper($mode) }}
    &bull; JUMLAH: {{ $items->count() }} ARSIP
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
                <span class="info-label">Kategori Ordner:</span>
                <span class="info-val">{{ $peta->count() }} kategori</span>
            </div>
            <div class="info-item">
                <span class="info-label">Arsip Aktif:</span>
                <span class="info-val">{{ $items->where('status', 'aktif')->count() }} dari {{ $items->count() }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Cetak:</span>
                <span class="info-val">{{ $dicetakPada->translatedFormat('d F Y H:i') }} WIB</span>
            </div>
        </div>
    </div>

    <div class="section-subhead"><i class="fas fa-sitemap"></i> A. Peta Ordner (Rekap per Kategori)</div>
    <table class="table-data is-ringkasan">
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th>Kategori Arsip</th>
                <th style="width: 100px;">Jumlah</th>
                <th style="width: 100px;">Aktif</th>
                <th style="width: 130px;">Dokumen Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($peta as $i => $p)
                <tr>
                    <td class="center font-mono">{{ $i + 1 }}</td>
                    <td><strong>{{ $p->kategori }}</strong></td>
                    <td class="center font-bold">{{ $p->jumlah }}</td>
                    <td class="center">{{ $p->aktif }}</td>
                    <td class="center">
                        {{ $p->terakhir ? \Carbon\Carbon::parse($p->terakhir)->format('d/m/Y') : '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center" style="padding: 14px; color: #6b7280;">
                        <em>Belum ada arsip yang sesuai filter.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="right">Jumlah</td>
                <td class="center">{{ $items->count() }}</td>
                <td class="center">{{ $items->where('status', 'aktif')->count() }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if ($mode === 'ringkasan')
        <div class="doc-trace">
            <i class="fas fa-circle-info"></i>
            Mode <strong>Ringkasan</strong> menampilkan peta ordner (rekap per kategori).
            Pilih <strong>Lengkap</strong> pada bar alat di atas untuk daftar rinci
            {{ $items->count() }} arsip beserta riwayat cetak dokumen.
        </div>
    @else
        <div class="section-subhead"><i class="fas fa-folder-open"></i> B. Daftar Rinci Arsip</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 120px;">Kode Arsip</th>
                    <th>Nama Dokumen</th>
                    <th style="width: 150px;">Kategori</th>
                    <th style="width: 120px;">Nomor Dokumen</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th style="width: 90px;">Lokasi Fisik</th>
                    <th style="width: 70px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $i => $a)
                    <tr>
                        <td class="center font-mono">{{ $i + 1 }}</td>
                        <td class="center font-mono">{{ $a->kode_arsip ?: '—' }}</td>
                        <td>
                            <strong>{{ $a->nama_dokumen ?: '—' }}</strong>
                            @if (!empty($a->deskripsi))
                                <div style="color: #4b5563;">{{ \Illuminate\Support\Str::limit($a->deskripsi, 70) }}</div>
                            @endif
                        </td>
                        <td>{{ $kategoriLabel[$a->kategori] ?? ($a->kategori ?: '—') }}</td>
                        <td class="center font-mono">{{ $a->nomor_dokumen ?: '—' }}</td>
                        <td class="center">
                            {{ $a->tanggal_dokumen ? \Carbon\Carbon::parse($a->tanggal_dokumen)->format('d/m/Y') : '—' }}
                        </td>
                        <td>{{ $a->lokasi_fisik ?: '—' }}</td>
                        <td class="center">{{ $statusLabel[$a->status] ?? ($a->status ?: '—') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="center" style="padding: 14px; color: #6b7280;">
                            <em>Belum ada arsip yang sesuai filter.</em>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-subhead"><i class="fas fa-clock-rotate-left"></i> C. Riwayat Cetak Dokumen Terakhir</div>
        @if ($riwayat->isEmpty())
            <div class="doc-trace">
                <i class="fas fa-circle-info"></i>
                Belum ada riwayat cetak pada cakupan jurusan ini.
            </div>
        @else
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th style="width: 130px;">Nomor Cetak</th>
                        <th>Dokumen</th>
                        <th style="width: 80px;">Versi</th>
                        <th style="width: 130px;">Periode</th>
                        <th style="width: 60px;">Baris</th>
                        <th style="width: 120px;">Petugas</th>
                        <th style="width: 120px;">Waktu Cetak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayat as $i => $r)
                        <tr>
                            <td class="center font-mono">{{ $i + 1 }}</td>
                            <td class="center font-mono">{{ $r->nomor_cetak ?: '—' }}</td>
                            <td>{{ $r->judul_dokumen ?: $r->jenis_dokumen }}</td>
                            <td class="center">{{ ucfirst((string) $r->mode) }}</td>
                            <td>{{ $r->periode_label ?: '—' }}</td>
                            <td class="center">{{ (int) $r->jumlah_baris }}</td>
                            <td>{{ $r->petugas_nama ?: '—' }}</td>
                            <td class="center">
                                {{ $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="doc-trace">
                <i class="fas fa-circle-check"></i>
                Setiap dokumen yang dicetak dari modul Laboratorium otomatis tercatat di
                <strong>Riwayat Cetak</strong> dan didaftarkan sebagai arsip digital, sehingga
                bukti fisik supervisi dapat ditelusuri kapan saja.
            </div>
        @endif
    @endif
@endsection
