<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip e-Izin Resmi - {{ $tiket->nomor_tiket }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('css/cetak.css') }}?v={{ file_exists(public_path('css/cetak.css')) ? filemtime(public_path('css/cetak.css')) : time() }}">
</head>

<body>
    <div class="no-print-bar">
        <button type="button" class="btn-action btn-back"
            onclick="if(window.history.length>1){window.history.back();}else{window.close();}">
            <i class="fas fa-arrow-left"></i> Kembali
        </button>
        <button type="button" class="btn-action btn-print"
            onclick="window.SAE_Print ? window.SAE_Print() : window.print()">
            <i class="fas fa-print"></i> Cetak Slip (Print / PDF)
        </button>
    </div>

    <div class="header">
        <h2>{{ $sekolah->nama ?? 'SISTEM APLIKASI EDUKASI' }}</h2>
        <h3>SLIP RESMI e-IZIN KELUAR-MASUK &amp; PULANG PESERTA DIDIK</h3>
        <p>{{ $sekolah->alamat_jalan ?? 'Lingkungan Satuan Pendidikan' }} &bull; NPSN: {{ $sekolah->npsn ?? '-' }}</p>
    </div>

    <div class="title-box">
        <h4>SURAT BUKTI e-IZIN MENINGGALKAN LINGKUNGAN SEKOLAH</h4>
        <div class="ticket-no">NO. TIKET: {{ $tiket->nomor_tiket }}</div>
    </div>

    <table class="content-table">
        <tr>
            <td class="label">Nama Peserta Didik</td>
            <td class="sep">:</td>
            <td><strong>{{ $tiket->nama_siswa }}</strong></td>
            <td class="label">Kelas / Rombel</td>
            <td class="sep">:</td>
            <td>{{ $tiket->nama_rombel ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NISN</td>
            <td class="sep">:</td>
            <td>{{ $tiket->nisn ?? '-' }}</td>
            <td class="label">Tanggal Izin</td>
            <td class="sep">:</td>
            <td>{{ \Carbon\Carbon::parse($tiket->tanggal)->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Jenis Izin</td>
            <td class="sep">:</td>
            <td>
                <span class="badge">
                    {{ $tiket->jenis_izin === 'pulang_cepat' ? 'IZIN PULANG (LEBIH CEPAT)' : 'IZIN KELUAR-MASUK KBM' }}
                </span>
            </td>
            <td class="label">Waktu Izin Keluar</td>
            <td class="sep">:</td>
            <td>
                Pukul <strong>{{ substr($tiket->jam_izin_keluar, 0, 5) }} WIB</strong>
                @if ($tiket->jenis_izin === 'keluar_sebentar' && $tiket->jam_rencana_kembali)
                    (Rencana Kembali: {{ substr($tiket->jam_rencana_kembali, 0, 5) }} WIB)
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Alasan / Keperluan</td>
            <td class="sep">:</td>
            <td colspan="4"><em>&ldquo;{{ $tiket->alasan }}&rdquo;</em></td>
        </tr>
        @if ($tiket->satpam_checkout_at || $tiket->satpam_checkin_at)
            <tr>
                <td class="label">Log Gerbang Security</td>
                <td class="sep">:</td>
                <td colspan="4">
                    @if ($tiket->satpam_checkout_at)
                        Keluar: <strong>{{ \Carbon\Carbon::parse($tiket->satpam_checkout_at)->format('H:i') }} WIB</strong> (Oleh: {{ $tiket->satpam_checkout_by ?? 'Security' }})
                    @endif
                    @if ($tiket->satpam_checkin_at)
                        &bull; Kembali: <strong>{{ \Carbon\Carbon::parse($tiket->satpam_checkin_at)->format('H:i') }} WIB</strong> (Oleh: {{ $tiket->satpam_checkin_by ?? 'Security' }})
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <div style="font-size: 11px; font-style: italic; color: #333; margin-top: 5px; border-left: 3px solid #666; padding-left: 8px;">
        *Catatan Penting: Slip ini merupakan dokumen izin resmi sekolah yang telah disetujui Wali Kelas &amp; Guru Piket. Wajib ditunjukkan kepada Petugas Security di pos gerbang saat keluar dan masuk kembali.
    </div>

    <!-- Tanda Tangan 4 Pihak Terlibat -->
    <table class="signature-table" style="margin-top: 20px;">
        <tr>
            <td style="width: 25%; text-align: center;">
                Peserta Didik,<br>
                <div class="sign-space" style="height: 48px;"></div>
                <strong>{{ $tiket->nama_siswa }}</strong>
            </td>
            <td style="width: 25%; text-align: center;">
                Wali Kelas,<br>
                <div class="sign-space" style="height: 48px;"></div>
                <strong>{{ $tiket->nama_wali ?: 'Wali Kelas' }}</strong>
            </td>
            <td style="width: 25%; text-align: center;">
                Guru Piket Sekolah,<br>
                <div class="sign-space" style="height: 48px;"></div>
                <strong>{{ $tiket->nama_piket ?: ($tiket->created_by ?: 'Guru Piket') }}</strong>
            </td>
            <td style="width: 25%; text-align: center;">
                Petugas Security Gerbang,<br>
                <div class="sign-space" style="height: 48px;"></div>
                <strong>{{ $tiket->satpam_checkout_by ?: '( Security / Satpam )' }}</strong>
            </td>
        </tr>
    </table>

    <script
        src="{{ asset('js/cetak.js') }}?v={{ file_exists(public_path('js/cetak.js')) ? filemtime(public_path('js/cetak.js')) : time() }}">
    </script>
</body>

</html>
