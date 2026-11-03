@php
    use Carbon\Carbon;
    $pdId = $pd->peserta_didik_id;
    $tgl = function ($v) {
        if (!$v) return '—';
        try { return Carbon::parse($v)->translatedFormat('d F Y'); } catch (\Throwable $e) { return $v; }
    };
    $val = fn ($a, $b = null, $fallback = '—') => ($a !== null && $a !== '') ? $a : (($b !== null && $b !== '') ? $b : $fallback);

    // Pas foto: ambil dari peserta_didik_meta, fallback arsip alumni.
    $foto = ($meta->foto_url ?? null)
        ?: (!empty($pd->foto_path) ? asset('storage/' . ltrim($pd->foto_path, '/')) : null);

    $umum = array_values(array_filter($matriks, fn ($r) => $r['kelompok'] === 'umum'));
    $kejuruan = array_values(array_filter($matriks, fn ($r) => $r['kelompok'] === 'kejuruan'));

    $sumSem = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];
    $sumAkhir = 0;
    foreach ($matriks as $r) {
        for ($s = 1; $s <= 6; $s++) { $sumSem[$s] += (float) ($r['nilai'][$s] ?? 0); }
        $sumAkhir += (float) ($r['akhir'] ?? 0);
    }
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Induk — {{ $pd->nama }} (A3)</title>
    <link rel="icon" type="image/png" href="{{ asset('img/logo-icon.png') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <style>
        @page { size: A3 landscape; margin: 6mm; }

        * { box-sizing: border-box; }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #e5e7eb;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
        }

        .no-print {
            position: sticky;
            top: 0;
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            align-items: center;
            z-index: 50;
        }
        .no-print a, .no-print button {
            padding: 7px 15px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            color: #fff;
            background: #2563eb;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .no-print .btn-back { background: #334155; }
        .no-print .lbl { font-size: 12px; opacity: .85; margin-right: auto; }

        .sheet {
            background: #fff;
            width: 408mm;
            margin: 12px auto;
            padding: 3mm 4mm;
            box-shadow: 0 4px 16px rgba(0,0,0,.15);
        }

        .layout { display: flex; gap: 0; align-items: stretch; border: 1.5px solid #000; }
        .col-bio { width: 44%; border-right: 1.5px solid #000; padding: 7px 12px; }
        .col-nilai { width: 56%; padding: 0; display: flex; flex-direction: column; }

        .bio-title { text-align: center; font-weight: 700; font-size: 11pt; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 9px; }
        .bio-table { width: 100%; border-collapse: collapse; }
        .bio-table td { vertical-align: top; padding: 0.5px 3px; font-size: 9pt; line-height: 1.3; }
        .bio-table td.no { width: 18px; }
        .bio-table td.lbl { white-space: nowrap; width: 1px; }
        .bio-table td.col { width: 9px; }
        .bio-table td.v { font-weight: 600; }
        .bio-table tr.sub td.lbl { padding-left: 14px; }
        .bio-table tr.sub2 td.lbl { padding-left: 30px; font-size: 8.2pt; color: #222; white-space: nowrap; }

        .photo-cell { width: 104px; vertical-align: top !important; padding: 6px 4px !important; text-align: center; }
        .photo-box {
            width: 96px; height: 128px;
            border: 1px solid #000;
            margin: 0 auto;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            /* Khusus pas foto: latar merah (tetap tercetak). */
            background: #d92b2b;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .photo-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .photo-cap { font-size: 6.4pt; line-height: 1.1; margin-top: 3px; }

        table.grid { width: 100%; height: 100%; border-collapse: collapse; table-layout: fixed; }
        table.grid th, table.grid td {
            border: 1px solid #000;
            padding: 1.5px 2px;
            text-align: center;
            font-size: 7.2pt;
            line-height: 1.15;
            vertical-align: middle;
        }
        table.grid thead th { background: #fff; font-weight: 700; }
        .th-title { font-size: 11pt; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 4px !important; }
        .th-mapel { font-size: 8.2pt; vertical-align: middle !important; }
        table.grid tbody td { vertical-align: middle; }
        .grp { text-align: center !important; font-weight: 700; padding: 3px 4px !important; background: #fff; font-size: 8pt; }
        .nm { text-align: left !important; padding-left: 5px !important; font-weight: 400; font-size: 7.4pt; }
        .num { width: 20px; }
        .cell { font-weight: 600; }
        .foot-lbl { text-align: left !important; font-weight: 400; padding-left: 5px !important; font-size: 7.4pt; }
        .foot-lbl-c { text-align: center !important; font-weight: 400; font-size: 7.2pt; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { width: auto; min-height: auto; margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <span class="lbl"><i class="fas fa-file"></i> Format A3 Landscape (siap cetak / PDF)</span>
        <a href="{{ route('dashboard.kesiswaan.buku-induk.import-nilai') }}" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
        <button onclick="window.print()"><i class="fas fa-print"></i> Cetak Buku Induk (A3)</button>
    </div>

    <div class="sheet">
        <div class="layout">
            {{-- ============ BIODATA ============ --}}
            <div class="col-bio">
                <div class="bio-title">Biodata</div>
                <table class="bio-table">
                    <tr><td class="no">1.</td><td class="lbl">Nama Lengkap</td><td class="col">:</td><td class="v">{{ $val($pd->nama) }}</td>
                        <td class="photo-cell" rowspan="10">
                            <div class="photo-box">
                                @if ($foto)<img src="{{ $foto }}" alt="" onerror="this.remove()">@endif
                            </div>
                            <div class="photo-cap">Pas Foto<br>3 × 4</div>
                        </td>
                    </tr>
                    <tr><td class="no">2.</td><td class="lbl">NIK</td><td class="col">:</td><td class="v">{{ $val($identitas->nik ?? null, $pd->nik) }}</td></tr>
                    <tr><td class="no">3.</td><td class="lbl">NIS/NIPD</td><td class="col">:</td><td class="v">{{ $val($klaper->nomor_induk ?? null, $pd->nipd) }}</td></tr>
                    <tr><td class="no">4.</td><td class="lbl">NISN</td><td class="col">:</td><td class="v">{{ $val($pd->nisn) }}</td></tr>
                    <tr><td class="no">5.</td><td class="lbl">Jenis Kelamin</td><td class="col">:</td><td class="v">{{ ($pd->jenis_kelamin ?? '') === 'P' ? 'P' : 'L' }}</td></tr>
                    <tr><td class="no">6.</td><td class="lbl">Tempat / Tanggal Lahir</td><td class="col">:</td><td class="v">{{ $val($identitas->tempat_lahir ?? null, $pd->tempat_lahir) }}, {{ $tgl($identitas->tanggal_lahir ?? $pd->tanggal_lahir) }}</td></tr>
                    <tr><td class="no">7.</td><td class="lbl">Warga Negara</td><td class="col">:</td><td class="v">{{ $val($identitas->kewarganegaraan ?? null, 'Indonesia') }}</td></tr>
                    <tr><td class="no">8.</td><td class="lbl">Agama</td><td class="col">:</td><td class="v">{{ $val($identitas->agama_id_str ?? null, $pd->agama_id_str) }}</td></tr>
                    <tr><td class="no">9.</td><td class="lbl">Penyakit Berat Yang di Derita</td><td class="col">:</td><td class="v">{{ $val($identitas->penyakit_pernah_diderita ?? null, 'Tidak ada') }}</td></tr>
                    <tr><td class="no">10.</td><td class="lbl">Anak Ke</td><td class="col">:</td><td class="v">{{ $val($identitas->anak_keberapa ?? null, '—') }}</td></tr>

                    <tr><td class="no">11.</td><td class="lbl" colspan="3"><b>Selama Bersekolah</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Tinggal Bersama</td><td class="col">:</td><td class="v">{{ $val($identitas->tempat_tinggal_str ?? null, 'Bersama orang tua') }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Alamat</td><td class="col"></td><td class="v"></td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Jl</td><td class="col">:</td><td class="v">{{ $val($identitas->alamat_jalan ?? null, $pd->alamat_jalan) }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Desa/Kel.</td><td class="col">:</td><td class="v">{{ $val($identitas->desa_kelurahan ?? null) }} {{ $val($identitas->rt ?? null, '-') }}/{{ $val($identitas->rw ?? null, '-') }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Kec</td><td class="col">:</td><td class="v">{{ $val($identitas->kecamatan ?? null) }}</td></tr>

                    <tr><td class="no">12.</td><td class="lbl" colspan="3"><b>Orang Tua Kandung</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Nama Ayah</td><td class="col">:</td><td class="v">{{ $val($identitas->nama_ayah ?? null, $pd->nama_ayah) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Pekerjaan</td><td class="col">:</td><td class="v">{{ $val($identitas->pekerjaan_ayah_str ?? null, $pd->pekerjaan_ayah_id_str ?? null) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">c. Nama Ibu</td><td class="col">:</td><td class="v">{{ $val($identitas->nama_ibu ?? null, $pd->nama_ibu) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">d. Pekerjaan</td><td class="col">:</td><td class="v">{{ $val($identitas->pekerjaan_ibu_str ?? null, $pd->pekerjaan_ibu_id_str ?? null) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">e. Alamat Rumah</td><td class="col"></td><td class="v"></td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Jl</td><td class="col">:</td><td class="v">{{ $val($identitas->alamat_jalan ?? null, $pd->alamat_jalan) }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Desa/Kel.</td><td class="col">:</td><td class="v">{{ $val($identitas->desa_kelurahan ?? null) }} {{ $val($identitas->rt ?? null, '-') }}/{{ $val($identitas->rw ?? null, '-') }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Kec</td><td class="col">:</td><td class="v">{{ $val($identitas->kecamatan ?? null) }}</td></tr>

                    <tr><td class="no">13.</td><td class="lbl" colspan="3"><b>Nama Wali Siswa</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Pekerjaan</td><td class="col">:</td><td class="v">{{ $val($identitas->pekerjaan_wali_str ?? null, $pd->pekerjaan_wali_id_str ?? null) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Alamat Rumah</td><td class="col"></td><td class="v"></td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Jl</td><td class="col">:</td><td class="v">{{ $val($identitas->alamat_jalan ?? null, $pd->alamat_jalan) }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Desa/Kel.</td><td class="col">:</td><td class="v">{{ $val($identitas->desa_kelurahan ?? null) }}</td></tr>
                    <tr class="sub2"><td></td><td class="lbl">Kab/Kec</td><td class="col">:</td><td class="v">{{ $val($identitas->kecamatan ?? null) }}</td></tr>

                    <tr><td class="no">14.</td><td class="lbl" colspan="3"><b>Diterima Menjadi Siswa</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Mulai Tanggal</td><td class="col">:</td><td class="v">{{ $tgl($identitas->tanggal_masuk_sekolah ?? $pd->tanggal_masuk_sekolah) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Asal Sekolah</td><td class="col">:</td><td class="v">{{ $val($identitas->sekolah_asal ?? null, $pd->sekolah_asal ?? null) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">c. Alamat Sekolah</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">15.</td><td class="lbl" colspan="3"><b>Prakerin</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Nama DU/DI</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Alamat</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">16.</td><td class="lbl">Kegiatan Ekstrakulikuler</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr><td class="no">17.</td><td class="lbl">Prestasi Di Sekolah Ini</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">18.</td><td class="lbl" colspan="3"><b>Penerimaan Beasiswa</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Nama Beasiswa</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Dikelas/Tahun</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">19.</td><td class="lbl" colspan="3"><b>Putus Sekolah</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Pada Kelas</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Tanggal</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">c. Alasan</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">20.</td><td class="lbl" colspan="3"><b>Pindah Sekolah Ke</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. Dari Kelas</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. Tanggal</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">c. Alasan</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">21.</td><td class="lbl" colspan="3"><b>Tamat Pendidikan Dari</b></td></tr>
                    <tr class="sub"><td></td><td class="lbl">a. No./Tanggal Ijazah</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">b. No./Tanggal SKHUN</td><td class="col">:</td><td class="v">{{ $val($pd->skhun ?? null) }}</td></tr>
                    <tr class="sub"><td></td><td class="lbl">c. Melanjutkan Ke</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">d. Bekerja Di</td><td class="col">:</td><td class="v">—</td></tr>
                    <tr class="sub"><td></td><td class="lbl">e. Berwirausaha Bidang</td><td class="col">:</td><td class="v">—</td></tr>

                    <tr><td class="no">22.</td><td class="lbl">Keterangan Lain</td><td class="col">:</td><td class="v">{{ $val($identitas->catatan_kesiswaan ?? null) }}</td></tr>
                    <tr><td class="no">23.</td><td class="lbl">Rombel Terakhir</td><td class="col">:</td><td class="v">{{ $val($rombel->rombel_nama ?? null, $pd->nama_rombel ?? null) }}</td></tr>
                </table>
            </div>

            {{-- ============ HASIL PRESTASI BELAJAR ============ --}}
            <div class="col-nilai">
                <table class="grid">
                    <colgroup>
                        <col class="num" style="width: 20px;">
                        <col>
                        @for ($c = 0; $c < 6; $c++)<col style="width: 56px;">@endfor
                        <col style="width: 48px;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="th-title" colspan="9">HASIL PRESTASI BELAJAR</th>
                        </tr>
                        <tr>
                            <th class="th-mapel" rowspan="3" colspan="2">MATA PELAJARAN</th>
                            <th colspan="2">Kelas X</th>
                            <th colspan="2">Kelas XI</th>
                            <th colspan="2">Kelas XII</th>
                            <th rowspan="3">Nilai<br>Akhir</th>
                        </tr>
                        <tr>
                            <th colspan="6">Semester</th>
                        </tr>
                        <tr>
                            @for ($s = 1; $s <= 6; $s++)<th>{{ $s }}</th>@endfor
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td class="grp" colspan="9">I. &nbsp; KELOMPOK MATA PELAJARAN UMUM</td></tr>
                        @foreach ($umum as $i => $r)
                            <tr>
                                <td>{{ $i + 1 }}.</td>
                                <td class="nm">{{ $r['nama'] }}</td>
                                @for ($s = 1; $s <= 6; $s++)
                                    <td class="cell">{{ ($r['nilai'][$s] ?? null) !== null ? number_format($r['nilai'][$s], 0) : '' }}</td>
                                @endfor
                                <td class="cell">{{ $r['akhir'] !== null ? number_format($r['akhir'], 0) : '' }}</td>
                            </tr>
                        @endforeach

                        <tr><td class="grp" colspan="9">II. &nbsp; KELOMPOK MATA PELAJARAN KEJURUAN</td></tr>
                        @foreach ($kejuruan as $i => $r)
                            <tr>
                                <td>{{ $i + 1 }}.</td>
                                <td class="nm">{{ $r['nama'] }}</td>
                                @for ($s = 1; $s <= 6; $s++)
                                    <td class="cell">{{ ($r['nilai'][$s] ?? null) !== null ? number_format($r['nilai'][$s], 0) : '' }}</td>
                                @endfor
                                <td class="cell">{{ $r['akhir'] !== null ? number_format($r['akhir'], 0) : '' }}</td>
                            </tr>
                        @endforeach

                        <tr>
                            <td class="foot-lbl" colspan="2">JUMLAH NILAI</td>
                            @for ($s = 1; $s <= 6; $s++)
                                <td class="cell">{{ $sumSem[$s] > 0 ? number_format($sumSem[$s], 0) : '' }}</td>
                            @endfor
                            <td class="cell">{{ $sumAkhir > 0 ? number_format($sumAkhir, 0) : '' }}</td>
                        </tr>
                        <tr>
                            <td class="foot-lbl" colspan="2">Ranking</td>
                            <td colspan="7"></td>
                        </tr>
                        <tr>
                            <td class="foot-lbl" colspan="2" rowspan="3">Absen</td>
                            <td class="foot-lbl-c">Sakit</td><td colspan="6"></td>
                        </tr>
                        <tr><td class="foot-lbl-c">Ijin</td><td colspan="6"></td></tr>
                        <tr><td class="foot-lbl-c">Alfa</td><td colspan="6"></td></tr>
                        <tr>
                            <td class="foot-lbl" colspan="2">Jumlah</td>
                            <td colspan="7"></td>
                        </tr>
                        <tr>
                            <td class="foot-lbl" colspan="2">Status Akhir tahun</td>
                            <td colspan="7"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
