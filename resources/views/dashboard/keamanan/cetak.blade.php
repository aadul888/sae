@extends('layouts.cetak')

@section('title', $judul)

@section('content')
    <div class="sheet a4-landscape" style="padding: 15mm 12mm; font-family: var(--font-family-base, Arial, sans-serif); color: #000;">
        @include('partials.kop-surat')

        <div style="text-align: center; margin: 14px 0 16px 0;">
            <h2 style="margin: 0; font-size: 13pt; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; text-decoration: underline;">
                {{ $judul }}
            </h2>
            @if (!empty($filterInfo))
                <div style="font-size: 9pt; margin-top: 3px; font-weight: 600; color: #334155;">{{ $filterInfo }}</div>
            @endif
            <div style="font-size: 8.5pt; margin-top: 2px; color: #64748b;">
                Dicetak: {{ now()->translatedFormat('d F Y, H:i') }} WIB &bull; Jumlah data: {{ number_format($rows->count(), 0, ',', '.') }}
            </div>
        </div>

        @php
            $total = $rows->count();
            $palette = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#84cc16'];
            $pct = fn ($n) => $total > 0 ? round($n / $total * 100, 1) : 0.0;
            $labelize = fn ($v) => ucfirst(str_replace('_', ' ', trim((string) $v) !== '' ? (string) $v : 'tidak diketahui'));

            // Distribusi 1 kolom -> [{label,count,pct}]
            $dist = fn ($col, array $labels = []) => $rows
                ->groupBy(fn ($r) => (string) ($r->{$col} ?? ''))
                ->map(fn ($g, $k) => ['label' => $labels[$k] ?? $labelize($k), 'count' => $g->count(), 'pct' => $pct($g->count())])
                ->sortByDesc('count')->values();

            // Distribusi via aksesor (relasi/nested) -> [{label,count,pct}]
            $distBy = fn (callable $cb, array $labels = []) => $rows
                ->groupBy(fn ($r) => (string) ($cb($r) ?? ''))
                ->map(fn ($g, $k) => ['label' => $labels[$k] ?? $labelize($k), 'count' => $g->count(), 'pct' => $pct($g->count())])
                ->sortByDesc('count')->values();

            // Nilai terbanyak dari 1 kolom bebas -> [{label,count,pct}]
            $topDist = fn ($col, $n = 5) => $rows->pluck($col)
                ->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')
                ->countBy()->sortDesc()->take($n)
                ->map(fn ($c, $k) => ['label' => $labelize($k), 'count' => $c, 'pct' => $pct($c)])
                ->values();

            $warnai = fn ($items) => collect($items)->values()
                ->map(fn ($it, $i) => $it + ['warna' => $palette[$i % count($palette)]])->all();
            $angka = fn ($n) => number_format((int) $n, 0, ',', '.');

            $ringkas = [];
            $grup = [];

            if ($modul === 'tamu') {
                $ringkas = [
                    ['label' => 'Total Kunjungan', 'value' => $angka($total)],
                    ['label' => 'Masih di Lokasi', 'value' => $angka($rows->where('status', 'berada_di_lokasi')->count())],
                    ['label' => 'Sudah Keluar', 'value' => $angka($rows->where('status', 'sudah_keluar')->count())],
                    ['label' => 'Instansi Unik', 'value' => $angka($rows->pluck('instansi_asal')->map(fn ($v) => trim((string) $v))->filter()->unique()->count())],
                ];
                $grup = [
                    ['judul' => 'Distribusi Status Tamu', 'items' => $warnai($dist('status', ['berada_di_lokasi' => 'Berada di Lokasi', 'sudah_keluar' => 'Sudah Keluar']))],
                    ['judul' => 'Distribusi Kategori Tamu', 'items' => $warnai($dist('jenis_tamu', ['umum' => 'Umum', 'orang_tua' => 'Orang Tua / Wali', 'dinas' => 'Instansi / Dinas']))],
                    ['judul' => 'Instansi / Asal Terbanyak', 'items' => $warnai($topDist('instansi_asal', 5))],
                ];
            } elseif ($modul === 'pelanggaran') {
                $poinTotal = (int) $rows->sum('poin');
                $ringkas = [
                    ['label' => 'Total Kasus', 'value' => $angka($total)],
                    ['label' => 'Total Poin', 'value' => $angka($poinTotal)],
                    ['label' => 'Rata-rata Poin', 'value' => $total > 0 ? number_format($poinTotal / $total, 1, ',', '.') : '0'],
                    ['label' => 'Siswa Terlibat', 'value' => $angka($rows->pluck('peserta_didik_id')->filter()->unique()->count())],
                ];
                $grup = [
                    ['judul' => 'Kategori Aturan Dilanggar', 'items' => $warnai($distBy(fn ($r) => $r->aturan->kategori ?? '', ['perilaku' => 'Perilaku', 'kerajinan' => 'Kerajinan', 'kerapian' => 'Kerapian', 'larangan_berat' => 'Larangan Berat']))],
                    ['judul' => 'Tingkat Pelanggaran', 'items' => $warnai($distBy(fn ($r) => $r->aturan->tingkat ?? '', ['ringan' => 'Ringan', 'sedang' => 'Sedang', 'berat' => 'Berat']))],
                    ['judul' => 'Tempat Kejadian Terbanyak', 'items' => $warnai($topDist('tempat_kejadian', 5))],
                ];
            } elseif ($modul === 'izin') {
                $ringkas = [
                    ['label' => 'Total Izin', 'value' => $angka($total)],
                    ['label' => 'Menunggu Gerbang', 'value' => $angka($rows->where('status', 'menunggu_satpam')->count())],
                    ['label' => 'Sudah Kembali', 'value' => $angka($rows->where('status', 'kembali')->count())],
                    ['label' => 'Belum Kembali', 'value' => $angka($rows->whereIn('status', ['menunggu_satpam', 'di_luar'])->count())],
                ];
                $grup = [
                    ['judul' => 'Distribusi Status Izin', 'items' => $warnai($dist('status', ['menunggu_satpam' => 'Menunggu Gerbang', 'di_luar' => 'Sedang di Luar', 'kembali' => 'Sudah Kembali', 'pulang_selesai' => 'Pulang Selesai']))],
                    ['judul' => 'Jenis Izin', 'items' => $warnai($dist('jenis_izin', ['keluar' => 'Keluar', 'pulang' => 'Pulang', 'dispen' => 'Dispen', 'sakit' => 'Sakit', 'izin' => 'Izin']))],
                    ['judul' => 'Rombel / Kelas Terbanyak', 'items' => $warnai($topDist('nama_rombel', 5))],
                ];
            } elseif ($modul === 'patroli') {
                $ringkas = [
                    ['label' => 'Total Log Patroli', 'value' => $angka($total)],
                    ['label' => 'Petugas Bertugas', 'value' => $angka($rows->pluck('nama_petugas')->map(fn ($v) => trim((string) $v))->filter()->unique()->count())],
                    ['label' => 'Temuan Dicatat', 'value' => $angka($rows->filter(fn ($r) => trim((string) ($r->catatan_temuan ?? '')) !== '')->count())],
                    ['label' => 'Rute / Zona Unik', 'value' => $angka($rows->pluck('rute_zona')->map(fn ($v) => trim((string) $v))->filter()->unique()->count())],
                ];
                $grup = [
                    ['judul' => 'Kondisi Lingkungan', 'items' => $warnai($dist('kondisi_lingkungan', ['aman' => 'Aman', 'perlu_perhatian' => 'Perlu Perhatian', 'bahaya' => 'Bahaya']))],
                    ['judul' => 'Rute / Zona Terbanyak', 'items' => $warnai($topDist('rute_zona', 5))],
                    ['judul' => 'Petugas Terbanyak', 'items' => $warnai($topDist('nama_petugas', 5))],
                ];
            } elseif ($modul === 'insiden') {
                $ringkas = [
                    ['label' => 'Total Insiden', 'value' => $angka($total)],
                    ['label' => 'Dalam Penanganan', 'value' => $angka($rows->where('status_penyelesaian', 'dalam_penanganan')->count())],
                    ['label' => 'Selesai / Ditutup', 'value' => $angka($rows->whereIn('status_penyelesaian', ['selesai', 'ditutup'])->count())],
                    ['label' => 'Lokasi Unik', 'value' => $angka($rows->pluck('lokasi_kejadian')->map(fn ($v) => trim((string) $v))->filter()->unique()->count())],
                ];
                $grup = [
                    ['judul' => 'Tingkat Urgensi', 'items' => $warnai($dist('tingkat_urgensi', ['rendah' => 'Rendah', 'sedang' => 'Sedang', 'tinggi' => 'Tinggi', 'darurat' => 'Darurat']))],
                    ['judul' => 'Status Penyelesaian', 'items' => $warnai($dist('status_penyelesaian', ['dalam_penanganan' => 'Dalam Penanganan', 'selesai' => 'Selesai', 'ditutup' => 'Ditutup']))],
                    ['judul' => 'Lokasi Kejadian Terbanyak', 'items' => $warnai($topDist('lokasi_kejadian', 5))],
                ];
            }
        @endphp

        @if (!empty($ringkas))
            {{-- A. REKAP ANGKA KUNCI --}}
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                @foreach ($ringkas as $item)
                    <div style="flex: 1 1 150px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; background: #f8fafc;">
                        <div style="font-size: 7.5pt; color: #64748b; text-transform: uppercase; letter-spacing: .3px;">{{ $item['label'] }}</div>
                        <div style="font-size: 14pt; font-weight: 800; color: #0f172a;">{{ $item['value'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- B. DISTRIBUSI + PERSENTASE --}}
            @if ($total > 0)
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                @foreach ($grup as $g)
                    @php $jumlah = collect($g['items'])->sum('count'); @endphp
                    @if ($jumlah > 0)
                        <div style="flex: 1 1 31%; min-width: 210px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px;">
                            <div style="font-size: 8pt; font-weight: 800; text-transform: uppercase; color: #334155; margin-bottom: 6px;">{{ $g['judul'] }}</div>
                            @foreach ($g['items'] as $it)
                                @if ($it['count'] > 0)
                                    <div style="margin-bottom: 4px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 7.5pt;">
                                            <span style="color: #1e293b;">{{ $it['label'] }}</span>
                                            <span style="font-weight: 700; color: #0f172a;">{{ number_format($it['count'], 0, ',', '.') }} <span style="color: #64748b;">({{ $it['pct'] }}%)</span></span>
                                        </div>
                                        <div style="height: 5px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; width: {{ $it['pct'] }}%; background: {{ $it['warna'] }};"></div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
            @endif

            <div style="font-size: 9pt; font-weight: 700; color: #334155; margin-bottom: 6px; border-top: 1px solid #cbd5e1; padding-top: 8px;">
                RINCIAN DATA ({{ number_format($total, 0, ',', '.') }} baris)
            </div>
        @endif

        <table style="width: 100%; border-collapse: collapse; font-size: 8pt;" border="1">
            <thead>
                <tr style="background-color: #f1f5f9; text-align: center; font-weight: 700;">
                    @if ($modul === 'tamu')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px; width: 60px;">Jam</th>
                        <th style="padding: 4px;">Nama Tamu</th>
                        <th style="padding: 4px;">Instansi / Asal</th>
                        <th style="padding: 4px;">Tujuan / Keperluan</th>
                        <th style="padding: 4px; width: 80px;">Status</th>
                    @elseif ($modul === 'pelanggaran')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Nama Siswa</th>
                        <th style="padding: 4px; width: 70px;">NISN</th>
                        <th style="padding: 4px;">Tempat</th>
                        <th style="padding: 4px; width: 45px;">Poin</th>
                        <th style="padding: 4px;">Keterangan</th>
                    @elseif ($modul === 'izin')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Nama Siswa</th>
                        <th style="padding: 4px; width: 90px;">Jenis Izin</th>
                        <th style="padding: 4px;">Alasan</th>
                        <th style="padding: 4px; width: 55px;">Keluar</th>
                        <th style="padding: 4px; width: 55px;">Kembali</th>
                        <th style="padding: 4px; width: 80px;">Status</th>
                    @elseif ($modul === 'patroli')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px; width: 55px;">Jam</th>
                        <th style="padding: 4px; width: 130px;">Rute / Zona</th>
                        <th style="padding: 4px; width: 110px;">Kondisi Lingkungan</th>
                        <th style="padding: 4px;">Catatan Temuan</th>
                        <th style="padding: 4px; width: 110px;">Petugas</th>
                    @elseif ($modul === 'insiden')
                        <th style="padding: 4px; width: 28px;">No</th>
                        <th style="padding: 4px; width: 110px;">No. Laporan</th>
                        <th style="padding: 4px; width: 75px;">Tanggal</th>
                        <th style="padding: 4px;">Judul Insiden</th>
                        <th style="padding: 4px; width: 100px;">Lokasi</th>
                        <th style="padding: 4px; width: 65px;">Urgensi</th>
                        <th style="padding: 4px;">Tindakan</th>
                        <th style="padding: 4px; width: 95px;">Status</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $idx => $r)
                    <tr>
                        <td style="padding: 3px; text-align: center;">{{ $idx + 1 }}</td>
                        @if ($modul === 'tamu')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_masuk, 0, 5) }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_tamu }}</td>
                            <td style="padding: 3px;">{{ $r->instansi_asal ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->keperluan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->status === 'sudah_keluar' ? 'Keluar' : 'Di Lokasi' }}</td>
                        @elseif ($modul === 'pelanggaran')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal_kejadian)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_siswa ?? '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->nisn ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->tempat_kejadian ?: '—' }}</td>
                            <td style="padding: 3px; text-align: center; font-weight: 700;">{{ (int) $r->poin }}</td>
                            <td style="padding: 3px;">{{ $r->keterangan ?: '—' }}</td>
                        @elseif ($modul === 'izin')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->nama_siswa ?? '—' }}</td>
                            <td style="padding: 3px;">{{ ucfirst(str_replace('_', ' ', $r->jenis_izin)) }}</td>
                            <td style="padding: 3px;">{{ $r->alasan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_izin_keluar, 0, 5) }}</td>
                            <td style="padding: 3px; text-align: center;">{{ $r->jam_kembali_aktual ? \Illuminate\Support\Str::substr((string) $r->jam_kembali_aktual, 0, 5) : '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                        @elseif ($modul === 'patroli')
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Illuminate\Support\Str::substr((string) $r->jam_patroli, 0, 5) }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->rute_zona }}</td>
                            <td style="padding: 3px;">{{ ucfirst(str_replace('_', ' ', $r->kondisi_lingkungan)) }}</td>
                            <td style="padding: 3px;">{{ $r->catatan_temuan ?: '—' }}</td>
                            <td style="padding: 3px;">{{ $r->nama_petugas ?: '—' }}</td>
                        @elseif ($modul === 'insiden')
                            <td style="padding: 3px; text-align: center; font-family: monospace;">{{ $r->nomor_laporan }}</td>
                            <td style="padding: 3px; text-align: center;">{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                            <td style="padding: 3px; font-weight: 600;">{{ $r->judul_insiden }}</td>
                            <td style="padding: 3px;">{{ $r->lokasi_kejadian }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst($r->tingkat_urgensi) }}</td>
                            <td style="padding: 3px;">{{ $r->tindakan_diambil ?: '—' }}</td>
                            <td style="padding: 3px; text-align: center;">{{ ucfirst(str_replace('_', ' ', $r->status_penyelesaian)) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="padding: 14px; text-align: center; color: #64748b;">Tidak ada data pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table style="width: 100%; border: none; font-size: 9pt; text-align: center; margin-top: 15px;">
            <tr>
                <td style="width: 33%; vertical-align: top;">
                    Mengetahui,<br>
                    <strong>Kepala Sekolah</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $kepalaSekolah->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $kepalaSekolah->nip ?? '—' }}</span>
                </td>
                <td style="width: 34%; vertical-align: top;">&nbsp;</td>
                <td style="width: 33%; vertical-align: top;">
                    {{ config('app.kota_sekolah', 'Sekolah') }}, {{ now()->translatedFormat('d F Y') }}<br>
                    <strong>Koordinator Keamanan / Satpam</strong>
                    <div style="height: 55px;"></div>
                    <strong><u>{{ $koordinator->nama ?? '( .............................................. )' }}</u></strong><br>
                    <span>NIP. {{ $koordinator->nip ?? '—' }}</span>
                </td>
            </tr>
        </table>
    </div>
@endsection
