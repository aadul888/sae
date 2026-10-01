<?php

namespace App\Http\Controllers;

use App\Models\AdminAktivitas;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikIdentitas;
use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use App\Models\SiswaUsulanPerubahan;
use App\Support\RefDapodikHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IdentitasPesertaDidikController extends Controller
{
    private function normalizeComparableValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            $value = (string) $value;
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized);
        $normalized = preg_replace('/[\x{00A0}\x{200B}\x{FEFF}]/u', '', $normalized);
        $normalized = mb_strtolower($normalized, 'UTF-8');

        return $normalized === '' ? null : $normalized;
    }

    private function hasMeaningfulDifference($oldValue, $newValue): bool
    {
        $oldNormalized = $this->normalizeComparableValue($oldValue);
        $newNormalized = $this->normalizeComparableValue($newValue);

        if ($oldNormalized === null && $newNormalized === null) {
            return false;
        }

        return $oldNormalized !== $newNormalized;
    }

    private function checkAuth()
    {
        $user = session('user');
        if (!$user) {
            return redirect()->route('login');
        }
        return null;
    }

    private function resolveStudentContext(Request $request): array
    {
        $user = session('user');
        $userRole = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $userId = is_array($user) ? ($user['pengguna_id'] ?? ($user['id'] ?? null)) : ($user->pengguna_id ?? ($user->id ?? null));
        $userName = is_array($user) ? ($user['nama'] ?? ($user['name'] ?? 'Pengguna')) : ($user->nama ?? ($user->name ?? 'Pengguna'));

        $pdId = null;
        $allPdList = collect();

        if ($userRole === 'peserta_didik') {
            $pdId = is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null);
            if (!$pdId && !empty($user['username'])) {
                $pdId = DB::table('peserta_didik')->where('nisn', $user['username'])->orWhere('nik', $user['username'])->value('peserta_didik_id');
            }
        } elseif ($userRole === 'orang_tua') {
            $pdId = session('parent_active_student_id') ?? (is_array($user) ? ($user['peserta_didik_id'] ?? null) : ($user->peserta_didik_id ?? null));
            if (!$pdId && !empty($user['username'])) {
                $pdId = DB::table('peserta_didik')->where('nisn', $user['username'])->orWhere('nik', $user['username'])->value('peserta_didik_id');
            }
        } else {
            // Admin, Guru, Tendik dapat melihat daftar dan memilih siswa
            if (Schema::hasTable('peserta_didik')) {
                $allPdList = DB::table('peserta_didik')
                    ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nama_rombel')
                    ->orderBy('nama')
                    ->limit(200)
                    ->get();
            }

            $reqPdId = $request->get('peserta_didik_id');
            if ($reqPdId) {
                $pdId = $reqPdId;
            } elseif ($allPdList->isNotEmpty()) {
                $pdId = $allPdList->first()->peserta_didik_id;
            }
        }

        $pd = null;
        if ($pdId && Schema::hasTable('peserta_didik')) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
        }

        return [
            'userRole' => $userRole,
            'userName' => $userName,
            'pd' => $pd,
            'pdId' => $pdId,
            'allPdList' => $allPdList,
        ];
    }

    /**
     * Tampilan Formulir Identitas Lengkap Peserta Didik
     */
    public function show(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $ctx = $this->resolveStudentContext($request);
        $userRole = $ctx['userRole'];
        $userName = $ctx['userName'];
        $pd = $ctx['pd'];
        $allPdList = $ctx['allPdList'];

        if (!$pd) {
            return view('dashboard.identitas-peserta-didik', [
                'pd' => null,
                'identitas' => null,
                'userRole' => $userRole,
                'allPdList' => $allPdList,
                'waliKelas' => null,
                'ref' => [],
                'usulanList' => collect(),
            ])->with('error', 'Data profil peserta didik tidak ditemukan.');
        }

        // Ambil atau inisialisasi data identitas terisolasi (aman dari overwrite Dapodik)
        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);

        // Ambil foto siswa dari peserta_didik_meta jika ada
        $meta = PesertaDidikMeta::where('peserta_didik_id', $pd->peserta_didik_id)->first();
        $studentPhoto = $meta?->foto_url ?? null;

        // Ambil data wali kelas
        $waliKelas = null;
        if (!empty($pd->rombongan_belajar_id)) {
            $rombel = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $pd->rombongan_belajar_id)->first();
            if ($rombel && !empty($rombel->ptk_id)) {
                $wali = DB::table('gtk')->where('ptk_id', $rombel->ptk_id)->first();
                if ($wali) {
                    $waliKelas = [
                        'nama' => $wali->nama,
                        'nip' => $wali->nip ?? '-',
                        'hp' => $wali->no_hp ?? '-',
                    ];
                }
            }
        }

        // Referensi Master Lookups Sesuai Formulir Dapodik 2026/2027
        $ref = [
            'agama' => RefDapodikHelper::getAgama(),
            'kebutuhan_khusus' => RefDapodikHelper::getKebutuhanKhusus(),
            'jenjang_pendidikan' => RefDapodikHelper::getJenjangPendidikan(),
            'pekerjaan' => RefDapodikHelper::getPekerjaan(),
            'penghasilan' => RefDapodikHelper::getPenghasilan(),
            'tempat_tinggal' => RefDapodikHelper::getTempatTinggal(),
            'transportasi' => RefDapodikHelper::getTransportasi(),
            'hobi' => RefDapodikHelper::getHobi(),
            'cita_cita' => RefDapodikHelper::getCitaCita(),
            'jenis_pendaftaran' => RefDapodikHelper::getJenisPendaftaran(),
            'jenis_prestasi' => RefDapodikHelper::getJenisPrestasi(),
            'tingkat_prestasi' => RefDapodikHelper::getTingkatPrestasi(),
            'kesejahteraan' => RefDapodikHelper::getKesejahteraan(),
        ];

        // Riwayat usulan perubahan data siswa ini
        $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $pd->peserta_didik_id)
            ->latest()
            ->limit(15)
            ->get();

        return view('dashboard.identitas-peserta-didik', compact(
            'pd',
            'identitas',
            'studentPhoto',
            'waliKelas',
            'userRole',
            'userName',
            'allPdList',
            'ref',
            'usulanList'
        ));
    }

    /**
     * Siswa/Orang Tua mengonfirmasi bahwa data identitas saat ini sudah sesuai dan valid
     */
    public function konfirmasiSesuai(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $ctx = $this->resolveStudentContext($request);
        $pd = $ctx['pd'];
        $userName = $ctx['userName'];
        $userRole = $ctx['userRole'];

        if (!$pd) {
            return back()->with('error', 'Peserta didik tidak ditemukan.');
        }

        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);
        $identitas->status_konfirmasi = 'sesuai';
        $identitas->dikonfirmasi_pada = now();
        $identitas->dikonfirmasi_oleh = $userName . ' (' . ucfirst($userRole) . ')';
        $identitas->catatan_siswa = $request->input('catatan_siswa', 'Data identitas telah diperiksa dan dikonfirmasi valid oleh siswa/orang tua.');
        $identitas->terakhir_diubah_oleh = $userRole;
        $identitas->save();

        if (class_exists(AdminAktivitas::class) && in_array($userRole, ['admin', 'tendik'], true)) {
            AdminAktivitas::record(
                "Konfirmasi Validitas Identitas Siswa: {$pd->nama} (NISN: {$pd->nisn})",
                'Kesiswaan',
                'Data identitas dikonfirmasi telah sesuai dan valid.',
                'success'
            );
        }

        return back()->with('success', 'Terima kasih! Konfirmasi data identitas Anda berhasil disimpan.');
    }

    /**
     * Siswa/Orang Tua/Admin memperbarui data identitas & mengajukan usulan revisi ke kesiswaan
     * NISN dan NIPD dikunci permanen dan tidak dapat diubah oleh siswa/orang tua.
     */
    public function update(Request $request)
    {
        if ($res = $this->checkAuth()) return $res;

        $ctx = $this->resolveStudentContext($request);
        $pd = $ctx['pd'];
        $userName = $ctx['userName'];
        $userRole = $ctx['userRole'];

        if (!$pd) {
            return back()->with('error', 'Peserta didik tidak ditemukan.');
        }

        $identitas = PesertaDidikIdentitas::getOrCreateFromPd($pd);

        // Nilai lama untuk perbandingan audit log usulan
        $oldSnapshot = $identitas->toArray();

        // 1. Data Pribadi (NISN dan NIPD permanen, tidak diambil dari form)
        $identitas->nama = $request->input('nama', $identitas->nama);
        $identitas->jenis_kelamin = $request->input('jenis_kelamin', $identitas->jenis_kelamin);
        $identitas->nik = $request->input('nik', $identitas->nik);
        $identitas->no_kk = $request->input('no_kk', $identitas->no_kk);
        $identitas->no_registrasi_akta_lahir = $request->input('no_registrasi_akta_lahir', $identitas->no_registrasi_akta_lahir);
        $identitas->kewarganegaraan = $request->input('kewarganegaraan', $identitas->kewarganegaraan ?? 'WNI');
        $identitas->tempat_lahir = $request->input('tempat_lahir', $identitas->tempat_lahir);
        if ($request->filled('tanggal_lahir')) {
            $identitas->tanggal_lahir = $request->input('tanggal_lahir');
        }

        $agamaList = RefDapodikHelper::getAgama();
        $identitas->agama_id = $request->input('agama_id', $identitas->agama_id);
        $identitas->agama_id_str = $agamaList[$identitas->agama_id] ?? $identitas->agama_id_str;

        $kebutuhanList = RefDapodikHelper::getKebutuhanKhusus();
        $identitas->kebutuhan_khusus_id = $request->input('kebutuhan_khusus_id', $identitas->kebutuhan_khusus_id ?? '0');
        $identitas->kebutuhan_khusus_str = $kebutuhanList[$identitas->kebutuhan_khusus_id] ?? 'Tidak ada';

        $identitas->anak_keberapa = $request->input('anak_keberapa', $identitas->anak_keberapa);
        $identitas->tinggi_badan = $request->input('tinggi_badan', $identitas->tinggi_badan);
        $identitas->berat_badan = $request->input('berat_badan', $identitas->berat_badan);

        // 2. Alamat & Domisili
        $identitas->alamat_jalan = $request->input('alamat_jalan', $identitas->alamat_jalan);
        $identitas->rt = $request->input('rt', $identitas->rt);
        $identitas->rw = $request->input('rw', $identitas->rw);
        $identitas->nama_dusun = $request->input('nama_dusun', $identitas->nama_dusun);
        $identitas->desa_kelurahan = $request->input('desa_kelurahan', $identitas->desa_kelurahan);
        $identitas->kecamatan = $request->input('kecamatan', $identitas->kecamatan);
        $identitas->kabupaten_kota = $request->input('kabupaten_kota', $identitas->kabupaten_kota);
        $identitas->provinsi = $request->input('provinsi', $identitas->provinsi);
        $identitas->kode_pos = $request->input('kode_pos', $identitas->kode_pos);
        $identitas->lintang = $request->input('lintang', $identitas->lintang);
        $identitas->bujur = $request->input('bujur', $identitas->bujur);

        $tempatTinggalList = RefDapodikHelper::getTempatTinggal();
        $identitas->tempat_tinggal_id = $request->input('tempat_tinggal_id', $identitas->tempat_tinggal_id);
        $identitas->tempat_tinggal_str = $tempatTinggalList[$identitas->tempat_tinggal_id] ?? $identitas->tempat_tinggal_str;

        $transportList = RefDapodikHelper::getTransportasi();
        $identitas->transportasi_id = $request->input('transportasi_id', $identitas->transportasi_id);
        $identitas->transportasi_str = $transportList[$identitas->transportasi_id] ?? $identitas->transportasi_str;

        // 3. Rekening Bank PIP
        $identitas->nama_bank = $request->input('nama_bank', $identitas->nama_bank);
        $identitas->no_rekening = $request->input('no_rekening', $identitas->no_rekening);
        $identitas->kcp_bank = $request->input('kcp_bank', $identitas->kcp_bank);
        $identitas->rekening_atas_nama = $request->input('rekening_atas_nama', $identitas->rekening_atas_nama);

        // 4. Data Ayah Kandung
        $pendidikanList = RefDapodikHelper::getJenjangPendidikan();
        $pekerjaanList = RefDapodikHelper::getPekerjaan();
        $penghasilanList = RefDapodikHelper::getPenghasilan();

        $identitas->status_hidup_ayah = $request->input('status_hidup_ayah', $identitas->status_hidup_ayah ?? '1');
        $identitas->nama_ayah = $request->input('nama_ayah', $identitas->nama_ayah);
        $identitas->nik_ayah = $request->input('nik_ayah', $identitas->nik_ayah);
        $identitas->tahun_lahir_ayah = $request->input('tahun_lahir_ayah', $identitas->tahun_lahir_ayah);
        $identitas->pendidikan_ayah_id = $request->input('pendidikan_ayah_id', $identitas->pendidikan_ayah_id);
        $identitas->pendidikan_ayah_str = $pendidikanList[$identitas->pendidikan_ayah_id] ?? $identitas->pendidikan_ayah_str;
        $identitas->pekerjaan_ayah_id = $request->input('pekerjaan_ayah_id', $identitas->pekerjaan_ayah_id);
        $identitas->pekerjaan_ayah_str = $pekerjaanList[$identitas->pekerjaan_ayah_id] ?? $identitas->pekerjaan_ayah_str;
        $identitas->penghasilan_ayah_id = $request->input('penghasilan_ayah_id', $identitas->penghasilan_ayah_id);
        $identitas->penghasilan_ayah_str = $penghasilanList[$identitas->penghasilan_ayah_id] ?? $identitas->penghasilan_ayah_str;
        $identitas->kebutuhan_khusus_ayah_id = $request->input('kebutuhan_khusus_ayah_id', $identitas->kebutuhan_khusus_ayah_id ?? '0');
        $identitas->kebutuhan_khusus_ayah_str = $kebutuhanList[$identitas->kebutuhan_khusus_ayah_id] ?? 'Tidak ada';

        // 5. Data Ibu Kandung
        $identitas->status_hidup_ibu = $request->input('status_hidup_ibu', $identitas->status_hidup_ibu ?? '1');
        $identitas->nama_ibu = $request->input('nama_ibu', $identitas->nama_ibu);
        $identitas->nik_ibu = $request->input('nik_ibu', $identitas->nik_ibu);
        $identitas->tahun_lahir_ibu = $request->input('tahun_lahir_ibu', $identitas->tahun_lahir_ibu);
        $identitas->pendidikan_ibu_id = $request->input('pendidikan_ibu_id', $identitas->pendidikan_ibu_id);
        $identitas->pendidikan_ibu_str = $pendidikanList[$identitas->pendidikan_ibu_id] ?? $identitas->pendidikan_ibu_str;
        $identitas->pekerjaan_ibu_id = $request->input('pekerjaan_ibu_id', $identitas->pekerjaan_ibu_id);
        $identitas->pekerjaan_ibu_str = $pekerjaanList[$identitas->pekerjaan_ibu_id] ?? $identitas->pekerjaan_ibu_str;
        $identitas->penghasilan_ibu_id = $request->input('penghasilan_ibu_id', $identitas->penghasilan_ibu_id);
        $identitas->penghasilan_ibu_str = $penghasilanList[$identitas->penghasilan_ibu_id] ?? $identitas->penghasilan_ibu_str;
        $identitas->kebutuhan_khusus_ibu_id = $request->input('kebutuhan_khusus_ibu_id', $identitas->kebutuhan_khusus_ibu_id ?? '0');
        $identitas->kebutuhan_khusus_ibu_str = $kebutuhanList[$identitas->kebutuhan_khusus_ibu_id] ?? 'Tidak ada';

        // 6. Data Wali
        $identitas->mempunyai_wali = $request->boolean('mempunyai_wali');
        if ($identitas->mempunyai_wali) {
            $identitas->nama_wali = $request->input('nama_wali', $identitas->nama_wali);
            $identitas->nik_wali = $request->input('nik_wali', $identitas->nik_wali);
            $identitas->tahun_lahir_wali = $request->input('tahun_lahir_wali', $identitas->tahun_lahir_wali);
            $identitas->pendidikan_wali_id = $request->input('pendidikan_wali_id', $identitas->pendidikan_wali_id);
            $identitas->pendidikan_wali_str = $pendidikanList[$identitas->pendidikan_wali_id] ?? $identitas->pendidikan_wali_str;
            $identitas->pekerjaan_wali_id = $request->input('pekerjaan_wali_id', $identitas->pekerjaan_wali_id);
            $identitas->pekerjaan_wali_str = $pekerjaanList[$identitas->pekerjaan_wali_id] ?? $identitas->pekerjaan_wali_str;
            $identitas->penghasilan_wali_id = $request->input('penghasilan_wali_id', $identitas->penghasilan_wali_id);
            $identitas->penghasilan_wali_str = $penghasilanList[$identitas->penghasilan_wali_id] ?? $identitas->penghasilan_wali_str;
            $identitas->kebutuhan_khusus_wali_id = $request->input('kebutuhan_khusus_wali_id', $identitas->kebutuhan_khusus_wali_id ?? '0');
            $identitas->kebutuhan_khusus_wali_str = $kebutuhanList[$identitas->kebutuhan_khusus_wali_id] ?? 'Tidak ada';
        } else {
            $identitas->nama_wali = null;
            $identitas->nik_wali = null;
            $identitas->tahun_lahir_wali = null;
            $identitas->pendidikan_wali_id = null;
            $identitas->pendidikan_wali_str = null;
            $identitas->pekerjaan_wali_id = null;
            $identitas->pekerjaan_wali_str = null;
            $identitas->penghasilan_wali_id = null;
            $identitas->penghasilan_wali_str = null;
            $identitas->kebutuhan_khusus_wali_id = null;
            $identitas->kebutuhan_khusus_wali_str = null;
        }

        // 7. Kontak & Komunikasi
        $identitas->nomor_telepon_rumah = $request->input('nomor_telepon_rumah', $identitas->nomor_telepon_rumah);
        $identitas->nomor_telepon_seluler = $request->input('nomor_telepon_seluler', $identitas->nomor_telepon_seluler);
        $identitas->email = $request->input('email', $identitas->email);

        // 8. Riwayat Prestasi (Array)
        if ($request->has('riwayat_prestasi')) {
            $prestasiInput = $request->input('riwayat_prestasi');
            $cleanPrestasi = [];
            if (is_array($prestasiInput)) {
                foreach ($prestasiInput as $p) {
                    if (!empty($p['nama'])) {
                        $cleanPrestasi[] = [
                            'jenis' => $p['jenis'] ?? 'Lain-lain',
                            'tingkat' => $p['tingkat'] ?? 'Sekolah',
                            'nama' => trim($p['nama']),
                            'tahun' => $p['tahun'] ?? date('Y'),
                            'penyelenggara' => trim($p['penyelenggara'] ?? '-'),
                            'peringkat' => trim($p['peringkat'] ?? '-'),
                        ];
                    }
                }
            }
            $identitas->riwayat_prestasi = $cleanPrestasi;
        }

        // 9. Perlindungan Sosial (Array)
        if ($request->has('perlindungan_sosial')) {
            $sosialInput = $request->input('perlindungan_sosial');
            $cleanSosial = [];
            if (is_array($sosialInput)) {
                foreach ($sosialInput as $s) {
                    if (!empty($s['no_kartu'])) {
                        $cleanSosial[] = [
                            'jenis' => $s['jenis'] ?? 'Program Indonesia Pintar (PIP)',
                            'no_kartu' => trim($s['no_kartu']),
                            'nama_di_kartu' => trim($s['nama_di_kartu'] ?? $identitas->nama),
                            'tahun_mulai' => $s['tahun_mulai'] ?? date('Y'),
                            'tahun_selesai' => $s['tahun_selesai'] ?? null,
                        ];
                    }
                }
            }
            $identitas->perlindungan_sosial = $cleanSosial;
        }

        // 10. Registrasi Masuk
        $pendaftaranList = RefDapodikHelper::getJenisPendaftaran();
        $identitas->jenis_pendaftaran_id = $request->input('jenis_pendaftaran_id', $identitas->jenis_pendaftaran_id);
        $identitas->jenis_pendaftaran_str = $pendaftaranList[$identitas->jenis_pendaftaran_id] ?? $identitas->jenis_pendaftaran_str;
        $identitas->sekolah_asal = $request->input('sekolah_asal', $identitas->sekolah_asal);
        $identitas->pernah_paud_formal = $request->boolean('pernah_paud_formal');
        $identitas->pernah_paud_non_formal = $request->boolean('pernah_paud_non_formal');

        // 11. Minat & Bakat
        $hobiList = RefDapodikHelper::getHobi();
        $identitas->hobi_id = $request->input('hobi_id', $identitas->hobi_id);
        $identitas->hobi_str = $hobiList[$identitas->hobi_id] ?? $identitas->hobi_str;

        $citaList = RefDapodikHelper::getCitaCita();
        $identitas->cita_cita_id = $request->input('cita_cita_id', $identitas->cita_cita_id);
        $identitas->cita_cita_str = $citaList[$identitas->cita_cita_id] ?? $identitas->cita_cita_str;

        // Status konfirmasi menjadi 'perlu_perbaikan' (menunggu verifikasi kesiswaan jika siswa yang ubah)
        $identitas->status_konfirmasi = 'perlu_perbaikan';
        $identitas->dikonfirmasi_pada = now();
        $identitas->dikonfirmasi_oleh = $userName . ' (' . ucfirst($userRole) . ')';
        $identitas->catatan_siswa = $request->input('catatan_siswa', 'Siswa/Orang Tua mengajukan perubahan formulir identitas.');
        $identitas->terakhir_diubah_oleh = $userRole;

        $trackedColumns = [
            'nama' => 'Nama Lengkap',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'NIK Siswa',
            'no_kk' => 'Nomor Kartu Keluarga',
            'no_registrasi_akta_lahir' => 'No. Akta Kelahiran',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama',
            'alamat_jalan' => 'Alamat Jalan',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Tempat Tinggal',
            'transportasi_id' => 'Transportasi',
            'nomor_telepon_rumah' => 'Nomor Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WhatsApp',
            'email' => 'Email Siswa',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nama_bank' => 'Nama Bank SimPel',
            'no_rekening' => 'Nomor Rekening Bank',
            'sekolah_asal' => 'Sekolah Asal',
            'jenis_pendaftaran_id' => 'Jenis Pendaftaran',
            'hobi_id' => 'Hobi',
            'cita_cita_id' => 'Cita-cita',
        ];

        $hasMeaningfulChanges = false;
        foreach ($trackedColumns as $col => $colLabel) {
            $oldVal = $oldSnapshot[$col] ?? null;
            $newVal = $identitas->{$col} ?? null;

            if ($this->hasMeaningfulDifference($oldVal, $newVal)) {
                $hasMeaningfulChanges = true;
                break;
            }
        }

        if (!$hasMeaningfulChanges) {
            return back()->with('error', 'Tidak ada perubahan yang bermakna. Perubahan yang hanya berbeda huruf besar/kecil, spasi, atau format serupa tidak akan diterima.');
        }

        $identitas->save();

        // -----------------------------------------------------------------
        // OTOMATIS CATAT PERUBAHAN KE TABEL `siswa_usulan_perubahan`
        // agar muncul di dashboard kesiswaan (kesiswaan/peserta-didik?tab=usulan)
        // -----------------------------------------------------------------
        $totalUsulanCreated = 0;
        foreach ($trackedColumns as $col => $colLabel) {
            $oldVal = $oldSnapshot[$col] ?? null;
            $newVal = $identitas->{$col} ?? null;

            if ($this->hasMeaningfulDifference($oldVal, $newVal)) {
                $oldValForRecord = $this->normalizeComparableValue($oldVal) ?? '(Kosong)';
                $newValForRecord = $this->normalizeComparableValue($newVal) ?? '(Kosong)';

                // Update atau buat usulan baru
                SiswaUsulanPerubahan::create([
                    'peserta_didik_id' => $pd->peserta_didik_id,
                    'kolom_perubahan' => $col,
                    'nilai_lama' => $oldValForRecord,
                    'nilai_baru' => $newValForRecord,
                    'alasan' => $request->input('catatan_siswa', "Pembaruan isian formulir {$colLabel} oleh {$userName}"),
                    'status' => 'menunggu',
                    'created_by' => $userName,
                ]);
                $totalUsulanCreated++;
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::record(
                "Pengajuan Usulan Revisi Identitas Siswa: {$pd->nama} (NISN: {$pd->nisn})",
                'Kesiswaan',
                "Tercatat {$totalUsulanCreated} butir perubahan data menunggu verifikasi kesiswaan.",
                'info'
            );
        }

        return back()->with('success', 'Formulir identitas berhasil disimpan! ' .
            ($totalUsulanCreated > 0
                ? "Terdapat {$totalUsulanCreated} butir perubahan data yang telah diteruskan ke Tim Kesiswaan untuk diverifikasi & di-input ke Dapodik."
                : 'Data identitas Anda tersimpan rapi & aman.'));
    }
}
