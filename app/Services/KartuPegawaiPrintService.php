<?php

namespace App\Services;

use App\Models\SekolahMeta;
use App\Models\User;
use App\Support\SemesterHelper;
use App\Support\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KartuPegawaiPrintService
{
    /**
     * Dapatkan data profil sekolah untuk kop dan identitas kartu.
     */
    public function getSekolahInfo(): array
    {
        $sekolah = Table::exists('sekolah') ? DB::table('sekolah')->first() : null;
        $sekolahMeta = Table::exists('sekolah_meta') ? SekolahMeta::first() : null;

        $alamatLengkap = collect([
            $sekolah?->alamat_jalan,
            $sekolah?->desa_kelurahan,
            $sekolah?->kecamatan,
            $sekolah?->kabupaten_kota,
        ])->filter()->implode(', ');

        return [
            'nama' => $sekolah?->nama ?: 'SMK NEGERI 1 PAGELARAN',
            'npsn' => $sekolah?->npsn ?: '20252031',
            'alamat' => $alamatLengkap ?: 'Jl. Raya Pasirpari, Sindangkerta, Kec. Pagelaran, Kab. Cianjur',
            'telepon' => $sekolah?->nomor_telepon ?: '-',
            'email' => $sekolah?->email ?: 'info@sekolah.sch.id',
            'website' => $sekolah?->website ?: 'https://smkn1pagelaran.sch.id',
            'logo_url' => $sekolahMeta?->logo_url ?: asset('img/logo-icon.png'),
            'kop_url' => $sekolahMeta?->kop_url,
        ];
    }

    /**
     * Muat data lengkap kartu pegawai digital berdasarkan ID (ptk_id / kode / id / NIP / NUPTK / akun).
     */
    public function loadEmployeeCardData(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }

        $ptkRow = null;
        $nonAsnRow = null;
        $isNonAsn = false;

        // 1. Cek prefix nonasn:{id}
        if (str_starts_with($id, 'nonasn:')) {
            $rawId = substr($id, 7);
            if (Table::exists('gtk_non_asn')) {
                $nonAsnRow = DB::table('gtk_non_asn')->where('id', $rawId)->first();
                if ($nonAsnRow) {
                    $isNonAsn = true;
                }
            }
        }

        // 2. Cek Dapodik gtk by ptk_id
        if (!$isNonAsn && Table::exists('gtk')) {
            $ptkRow = DB::table('gtk')->where('ptk_id', $id)->first();
        }

        // 3. Cek Non-ASN by kode atau id numerik
        if (!$ptkRow && !$isNonAsn && Table::exists('gtk_non_asn')) {
            $nonAsnRow = DB::table('gtk_non_asn')
                ->where('kode', $id)
                ->orWhere('id', is_numeric($id) ? (int) $id : 0)
                ->first();
            if ($nonAsnRow) {
                $isNonAsn = true;
            }
        }

        // 4. Cek Dapodik gtk by NIP atau NUPTK
        if (!$ptkRow && !$isNonAsn && Table::exists('gtk')) {
            $ptkRow = DB::table('gtk')
                ->where('nip', $id)
                ->orWhere('nuptk', $id)
                ->first();
        }

        // 5. Cek Non-ASN by NIP, NUPTK, atau NIK
        if (!$ptkRow && !$isNonAsn && Table::exists('gtk_non_asn')) {
            $nonAsnRow = DB::table('gtk_non_asn')
                ->where('nip', $id)
                ->orWhere('nuptk', $id)
                ->orWhere('nik', $id)
                ->first();
            if ($nonAsnRow) {
                $isNonAsn = true;
            }
        }

        // 6. Cek via User login / Pengguna account
        if (!$ptkRow && !$isNonAsn && Table::exists('pengguna')) {
            $u = DB::table('pengguna')
                ->where('pengguna_id', $id)
                ->orWhere('username', $id)
                ->first();
            if ($u) {
                if (!empty($u->ptk_id) && Table::exists('gtk')) {
                    $ptkRow = DB::table('gtk')->where('ptk_id', $u->ptk_id)->first();
                }
                if (!$ptkRow && Table::exists('gtk_non_asn')) {
                    $nonAsnRow = DB::table('gtk_non_asn')
                        ->where('pengguna_id', $u->pengguna_id)
                        ->orWhere('username', $u->username)
                        ->first();
                    if ($nonAsnRow) {
                        $isNonAsn = true;
                    }
                }
            }
        }

        if (!$ptkRow && !$nonAsnRow) {
            return null;
        }

        $sekolah = $this->getSekolahInfo();
        $semesterLabel = SemesterHelper::getActiveSemesterLabel();

        if ($isNonAsn && $nonAsnRow) {
            $sumber = 'non_asn';
            $identifier = $nonAsnRow->kode ?: ('nonasn:' . $nonAsnRow->id);
            $rawId = (string) $nonAsnRow->id;
            $namaDasar = $nonAsnRow->nama;
            $gelarDepan = $nonAsnRow->gelar_depan;
            $gelarBelakang = $nonAsnRow->gelar_belakang;
            $namaLengkap = GtkMetaService::namaLengkap($namaDasar, $gelarDepan, $gelarBelakang);
            $nip = $nonAsnRow->nip;
            $nuptk = $nonAsnRow->nuptk;
            $nik = $nonAsnRow->nik;
            $jenisPtk = $nonAsnRow->jenis_ptk ?: 'GTK Non ASN';
            $jabatan = $nonAsnRow->jabatan ?: $nonAsnRow->jenis_ptk ?: 'Pegawai Non-ASN';
            $subJabatan = $nonAsnRow->bidang_studi ?: '-';
            $statusKepegawaian = $nonAsnRow->status_kepegawaian ?: 'Non-ASN';
            $tmt = $nonAsnRow->tmt;
            $penggunaId = $nonAsnRow->pengguna_id;
            $username = $nonAsnRow->username;

            // Foto Profil
            $fotoUrl = null;
            if ($penggunaId) {
                $fotoUrl = User::where('pengguna_id', $penggunaId)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }
            if (!$fotoUrl && $username) {
                $fotoUrl = User::where('username', $username)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }
            if (!$fotoUrl && !empty($namaDasar)) {
                $fotoUrl = User::where('nama', $namaDasar)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }
        } else {
            $sumber = 'dapodik';
            $identifier = $ptkRow->ptk_id;
            $rawId = $ptkRow->ptk_id;
            $namaDasar = $ptkRow->nama;

            // Gelar mandiri dari tabel gtk_meta
            $gelarService = app(GtkMetaService::class);
            $gelar = $gelarService->gelar($ptkRow->ptk_id);
            $gelarDepan = $gelar['gelar_depan'];
            $gelarBelakang = $gelar['gelar_belakang'];
            $namaLengkap = GtkMetaService::namaLengkap($namaDasar, $gelarDepan, $gelarBelakang);

            $nip = $ptkRow->nip;
            $nuptk = $ptkRow->nuptk;
            $nik = $ptkRow->nik;
            $jenisPtk = $ptkRow->jenis_ptk_id_str ?: 'Pendidik / Tenaga Kependidikan';
            $jabatan = $ptkRow->jabatan_ptk_id_str ?: ($ptkRow->jenis_ptk_id_str ?: 'Pegawai');

            // Sub-jabatan (Mapel Utama / Tugas Tambahan)
            $subJabatan = null;
            if (Table::exists('pembelajaran')) {
                $topM = DB::table('pembelajaran')
                    ->where('ptk_id', $ptkRow->ptk_id)
                    ->select('nama_mata_pelajaran', DB::raw('SUM(jam_mengajar_per_minggu) as total_jam'))
                    ->groupBy('nama_mata_pelajaran')
                    ->orderByDesc('total_jam')
                    ->first();
                if ($topM) {
                    $subJabatan = $topM->nama_mata_pelajaran;
                }
            }
            if (!$subJabatan && Table::exists('ptk_tugas_tambahan') && Table::exists('ref_tugas_tambahan')) {
                $duty = DB::table('ptk_tugas_tambahan as ptt')
                    ->join('ref_tugas_tambahan as rtt', 'ptt.tugas_tambahan_id', '=', 'rtt.id')
                    ->where('ptt.is_active', true)
                    ->where('rtt.is_active', true)
                    ->where('ptt.ptk_id', $ptkRow->ptk_id)
                    ->value('rtt.nama');
                if ($duty) {
                    $subJabatan = $duty;
                }
            }
            if (!$subJabatan) {
                $subJabatan = $ptkRow->bidang_studi_terakhir ?: '-';
            }

            $statusKepegawaian = $ptkRow->status_kepegawaian_id_str ?: 'ASN';
            $tmt = $ptkRow->tanggal_surat_tugas;

            // Pengguna terkait
            $u = Table::exists('pengguna') ? DB::table('pengguna')->where('ptk_id', $ptkRow->ptk_id)->first() : null;
            $penggunaId = $u?->pengguna_id;
            $username = $u?->username;

            // Foto Profil
            $fotoUrl = User::where('ptk_id', $ptkRow->ptk_id)
                ->whereNotNull('foto_path')
                ->where('foto_path', '!=', '')
                ->first()?->foto_url;

            if (!$fotoUrl && $penggunaId) {
                $fotoUrl = User::where('pengguna_id', $penggunaId)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }

            if (!$fotoUrl && $username) {
                $fotoUrl = User::where('username', $username)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }

            if (!$fotoUrl && !empty($namaDasar)) {
                $fotoUrl = User::where('nama', $namaDasar)
                    ->whereNotNull('foto_path')
                    ->where('foto_path', '!=', '')
                    ->first()?->foto_url;
            }
        }

        // Fallback foto dari sesi jika pengguna yang sedang login melihat kartu miliknya
        if (!$fotoUrl && session('user')) {
            $sUser = session('user');
            $sPtkId = is_array($sUser) ? ($sUser['ptk_id'] ?? null) : ($sUser->ptk_id ?? null);
            $sPenggunaId = is_array($sUser) ? ($sUser['pengguna_id'] ?? null) : ($sUser->pengguna_id ?? null);
            $sNama = is_array($sUser) ? ($sUser['nama'] ?? ($sUser['name'] ?? null)) : ($sUser->nama ?? ($sUser->name ?? null));
            $sNip = is_array($sUser) ? ($sUser['nip'] ?? null) : ($sUser->nip ?? null);

            $isSelf = ($sPtkId && ($sPtkId === $identifier || $sPtkId === ($ptkRow->ptk_id ?? null))) ||
                ($sPenggunaId && ($sPenggunaId === ($penggunaId ?? null))) ||
                ($sNip && !empty($nip) && $nip !== '-' && $sNip === $nip) ||
                ($sNama && !empty($namaDasar) && strcasecmp($sNama, $namaDasar) === 0);

            if ($isSelf) {
                $fotoUrl = is_array($sUser) ? ($sUser['foto_url'] ?? null) : ($sUser->foto_url ?? null);
            }
        }

        // Kode Barcode: NIP > NUPTK > Kode Non-ASN > Alfanumerik Identifier
        $barcodeText = null;
        if (!empty($nip) && $nip !== '-' && strlen($nip) >= 5) {
            $barcodeText = preg_replace('/\s+/', '', (string) $nip);
        } elseif (!empty($nuptk) && $nuptk !== '-' && strlen($nuptk) >= 5) {
            $barcodeText = preg_replace('/\s+/', '', (string) $nuptk);
        } elseif (!empty($nonAsnRow?->kode)) {
            $barcodeText = $nonAsnRow->kode;
        } else {
            $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '', $identifier);
            $barcodeText = $cleanCode ?: 'PEG-0000';
        }

        $verifyUrl = route('kartu-pegawai.verify-short', ['id' => $identifier]);

        return [
            'identifier' => $identifier,
            'raw_id' => $rawId,
            'sumber' => $sumber,
            'nama' => $namaLengkap,
            'nama_dasar' => $namaDasar,
            'gelar_depan' => $gelarDepan,
            'gelar_belakang' => $gelarBelakang,
            'nip' => $nip ?: '-',
            'nuptk' => $nuptk ?: '-',
            'nik' => $nik ?: '-',
            'jenis_ptk' => $jenisPtk,
            'jabatan' => $jabatan,
            'sub_jabatan' => $subJabatan,
            'status_kepegawaian' => $statusKepegawaian,
            'tmt' => $tmt ?: '-',
            'pengguna_id' => $penggunaId,
            'username' => $username,
            'status' => 'AKTIF',
            'foto_url' => $fotoUrl,
            'barcode_text' => $barcodeText,
            'qr_code_svg' => QrCodeService::generateSvg($verifyUrl, 120, 0),
            'barcode_svg' => BarcodeService::generateCode128Svg($barcodeText, 36, 2),
            'verify_url' => $verifyUrl,
            'sekolah' => $sekolah,
            'tahun_pelajaran' => 'TP. ' . $semesterLabel,
        ];
    }
}
