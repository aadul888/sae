<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use App\Models\NotifikasiTransaksi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
use App\Models\SiswaUsulanPerubahan;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


use App\Support\Table;

class KesiswaanUsulanService
{
    /**
     * Kirim notifikasi in-app ke peserta didik terkait hasil verifikasi usulan identitas.
     */
    private function notifyStudent(string|int $pdId, string $judul, string $pesan, string $tipe, string $icon): void
    {
        try {
            if (!Table::exists('notifikasi_transaksi')) return;

            $url = route('dashboard.identitas.index', ['peserta_didik_id' => $pdId]);

            $siswaUser = \App\Models\User::where('peserta_didik_id', $pdId)->first();
            if ($siswaUser) {
                NotifikasiTransaksi::create([
                    'pengguna_id'      => $siswaUser->pengguna_id,
                    'peserta_didik_id' => (string) $pdId,
                    'kategori'         => 'usulan_identitas',
                    'judul'            => $judul,
                    'pesan'            => $pesan,
                    'tipe'             => $tipe,
                    'icon'             => $icon,
                    'url'              => $url,
                    'is_read'          => false,
                ]);
            }

            // Wali / orang tua yang menautkan anak (peserta_didik_id sama)
            $waliUsers = \App\Models\User::where('peserta_didik_id', $pdId)
                ->where(function ($q) {
                    $q->where('peran_id_str', 'like', '%orang%')
                        ->orWhere('peran_id_str', 'like', '%wali%');
                })
                ->get();

            foreach ($waliUsers as $wali) {
                if ($siswaUser && (string) $wali->pengguna_id === (string) $siswaUser->pengguna_id) {
                    continue;
                }
                NotifikasiTransaksi::create([
                    'pengguna_id'      => $wali->pengguna_id,
                    'peserta_didik_id' => (string) $pdId,
                    'kategori'         => 'usulan_identitas',
                    'judul'            => $judul,
                    'pesan'            => str_replace(['Anda', 'anda'], ['putra/putri Anda', 'putra/putri Anda'], $pesan),
                    'tipe'             => $tipe,
                    'icon'             => $icon,
                    'url'              => $url,
                    'is_read'          => false,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi usulan: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi in-app ke seluruh pengguna yang berhak mengelola
     * (menu Kesiswaan → Peserta Didik) terkait transaksi usulan identitas.
     */
    private function notifyKesiswaanAdmins(string $judul, string $pesan, string $tipe, string $icon, ?string $excludePenggunaId = null): void
    {
        try {
            if (!Table::exists('notifikasi_transaksi') || !Table::exists('pengguna')) return;

            $kandidat = \App\Models\User::query()
                ->where(function ($q) {
                    $q->whereNotNull('ptk_id')
                        ->orWhere('peran_id_str', 'like', '%admin%')
                        ->orWhere('peran_id_str', 'like', '%tendik%');
                })
                ->get();

            $url = route('dashboard.kesiswaan.peserta-didik.index', ['tab' => 'usulan']);

            foreach ($kandidat as $admin) {
                if ($excludePenggunaId && (string) $admin->pengguna_id === (string) $excludePenggunaId) {
                    continue;
                }

                try {
                    if (!\App\Models\RolePermission::canAccess($admin, 'menu_kesiswaan_peserta_didik', 'read')) {
                        continue;
                    }
                } catch (\Throwable $e) {
                    continue;
                }

                NotifikasiTransaksi::create([
                    'pengguna_id'      => $admin->pengguna_id,
                    'peserta_didik_id' => null,
                    'kategori'         => 'usulan_identitas',
                    'judul'            => $judul,
                    'pesan'            => $pesan,
                    'tipe'             => $tipe,
                    'icon'             => $icon,
                    'url'              => $url,
                    'is_read'          => false,
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal kirim notifikasi admin usulan: ' . $e->getMessage());
        }
    }

    /**
     * Simpan Usulan Perubahan Data Siswa.
     */
    public function storeUsulan(Request $request, mixed $user): array
    {
        $validated = $request->validate([
            'peserta_didik_id' => 'required|string',
            'kolom_perubahan'  => 'required|string|max:100',
            'nilai_lama'       => 'nullable|string',
            'nilai_baru'       => 'required|string',
            'alasan'           => 'required|string|max:255',
            'berkas_bukti'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = null;
        if ($request->hasFile('berkas_bukti')) {
            $hasil = app(ImageOptimizerService::class)->optimizeAndSaveImage(
                $request->file('berkas_bukti'),
                public_path('uploads/usulan_siswa'),
                'usulan'
            );
            $filePath = '/uploads/usulan_siswa/' . $hasil['filename'];
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Pengguna') : ($user->nama ?? 'Pengguna');

        $usulan = SiswaUsulanPerubahan::create([
            'peserta_didik_id'   => $validated['peserta_didik_id'],
            'batch_id'           => (string) \Illuminate\Support\Str::uuid(),
            'kolom_perubahan'    => $validated['kolom_perubahan'],
            'nilai_lama'         => $validated['nilai_lama'] ?? null,
            'nilai_baru'         => $validated['nilai_baru'],
            'alasan'             => $validated['alasan'],
            'berkas_bukti'       => $filePath,
            'status'             => 'menunggu',
            'created_by'         => $userName,
        ]);

        // Notifikasi transaksi: siswa + admin kesiswaan
        $pdNama = PesertaDidik::where('peserta_didik_id', $validated['peserta_didik_id'])->value('nama');
        $this->notifyStudent(
            $validated['peserta_didik_id'],
            'Pengajuan Usulan Data Terkirim',
            'Pengajuan perubahan data Anda telah terkirim dan menunggu verifikasi Tim Kesiswaan.',
            'info',
            'fas fa-paper-plane'
        );
        $this->notifyKesiswaanAdmins(
            'Usulan Baru: ' . ($pdNama ?: 'Peserta Didik'),
            'Pengajuan usulan perubahan data baru oleh ' . $userName . ($pdNama ? " untuk {$pdNama}" : '') . ' menunggu verifikasi.',
            'warning',
            'fas fa-file-pen',
            is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null)
        );

        return [
            'status' => 'success',
            'message' => 'Usulan perubahan data siswa berhasil dikirim dan menunggu verifikasi.',
            'data' => $usulan,
        ];
    }

    /**
     * Ambil data detail lengkap usulan perubahan siswa (JSON) untuk Modal Formulir Pengelolaan Usulan.
     */
    public function getUsulanDetail(string|int $id): array
    {
        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Table::exists('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if ($pd) {
            $pdId = $pd->peserta_didik_id;
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $single = SiswaUsulanPerubahan::with('siswa')->find($id);
            if (!$single) {
                return ['status' => 'error', 'message' => 'Data usulan atau peserta didik tidak ditemukan.', 'status_code' => 404];
            }
            $pd = $single->siswa;
            $pdId = $single->peserta_didik_id;
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        $meta = null;
        if (Table::exists('peserta_didik_meta') && $pd) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pdId)->first();
        }

        $berkas = collect();
        if (Table::exists('peserta_didik_berkas') && $pd) {
            $berkas = PesertaDidikBerkas::where('peserta_didik_id', $pdId)
                ->whereIn('jenis_berkas', ['kartu_keluarga', 'ijazah_smp', 'akta_kelahiran'])
                ->get()
                ->keyBy('jenis_berkas');
        }

        $kk = $berkas->get('kartu_keluarga');
        $ijazah = $berkas->get('ijazah_smp');
        $akta = $berkas->get('akta_kelahiran');

        $kolomLabels = [
            'nama' => 'Nama Lengkap Siswa',
            'jenis_kelamin' => 'Jenis Kelamin',
            'nik' => 'Nomor Induk Kependudukan (NIK)',
            'no_kk' => 'Nomor Kartu Keluarga (KK)',
            'no_registrasi_akta_lahir' => 'No. Registrasi Akta Lahir',
            'kewarganegaraan' => 'Kewarganegaraan',
            'tempat_lahir' => 'Tempat Lahir',
            'tanggal_lahir' => 'Tanggal Lahir',
            'agama_id' => 'Agama & Kepercayaan',
            'alamat_jalan' => 'Alamat Jalan / Tempat Tinggal',
            'rt' => 'RT',
            'rw' => 'RW',
            'desa_kelurahan' => 'Desa / Kelurahan',
            'kecamatan' => 'Kecamatan',
            'kabupaten_kota' => 'Kabupaten / Kota',
            'provinsi' => 'Provinsi',
            'kode_pos' => 'Kode Pos',
            'tempat_tinggal_id' => 'Status Tempat Tinggal',
            'transportasi_id' => 'Moda Transportasi',
            'anak_keberapa' => 'Anak Ke-berapa (di KK)',
            'nama_ayah' => 'Nama Ayah Kandung',
            'nik_ayah' => 'NIK Ayah',
            'tahun_lahir_ayah' => 'Tahun Lahir Ayah',
            'pendidikan_ayah_id' => 'Pendidikan Ayah',
            'pekerjaan_ayah_id' => 'Pekerjaan Ayah',
            'penghasilan_ayah_id' => 'Penghasilan Ayah',
            'nama_ibu' => 'Nama Ibu Kandung',
            'nik_ibu' => 'NIK Ibu',
            'tahun_lahir_ibu' => 'Tahun Lahir Ibu',
            'pendidikan_ibu_id' => 'Pendidikan Ibu',
            'pekerjaan_ibu_id' => 'Pekerjaan Ibu',
            'penghasilan_ibu_id' => 'Penghasilan Ibu',
            'nama_wali' => 'Nama Wali',
            'nik_wali' => 'NIK Wali',
            'nomor_telepon_rumah' => 'Nomor Telepon Rumah',
            'nomor_telepon_seluler' => 'No. HP / WhatsApp Siswa',
            'email' => 'Email Siswa',
            'sekolah_asal' => 'Sekolah Asal',
            'tinggi_badan' => 'Tinggi Badan (cm)',
            'berat_badan' => 'Berat Badan (kg)',
            'lingkar_kepala' => 'Lingkar Kepala (cm)',
            'jarak_rumah_sekolah' => 'Jarak Rumah ke Sekolah',
            'jarak_rumah_sekolah_km' => 'Jarak Rumah (km)',
            'waktu_tempuh_jam' => 'Waktu Tempuh (Jam)',
            'waktu_tempuh_menit' => 'Waktu Tempuh (Menit)',
            'jumlah_saudara_kandung' => 'Jumlah Saudara Kandung',
        ];

        $rekomendasiPenolakan = [
            'Data yang diusulkan tidak sesuai dengan dokumen resmi Kartu Keluarga (KK).',
            'Data nama / tempat / tanggal lahir tidak cocok dengan Ijazah SMP atau Akta Kelahiran.',
            'Lampiran berkas bukti buram, terpotong, atau tidak terbaca dengan jelas.',
            'Perubahan nama atau identitas pokok wajib menyertakan Akta Kelahiran / Penetapan Pengadilan.',
            'Format penulisan NIK atau No. KK tidak valid (harus 16 digit terdaftar di Dukcapil).',
            'Pengajuan usulan dibatalkan atas permintaan siswa atau orang tua.',
        ];

        $itemsData = [];
        foreach ($usulanList as $u) {
            $itemsData[] = [
                'id' => $u->id,
                'kolom_perubahan' => $u->kolom_perubahan,
                'kolom_label' => $kolomLabels[$u->kolom_perubahan] ?? ucwords(str_replace('_', ' ', $u->kolom_perubahan)),
                'nilai_lama' => $u->nilai_lama ?: '(Belum Terisi / Kosong)',
                'nilai_baru' => $u->nilai_baru,
                'alasan' => $u->alasan ?: 'Penyesuaian formulir identitas mandiri.',
                'status' => $u->status,
                'catatan_verifikasi' => $u->catatan_verifikasi,
                'verified_by' => $u->verified_by,
                'verified_at' => $u->verified_at ? $u->verified_at->translatedFormat('d F Y, H:i') : null,
                'created_at' => $u->created_at ? $u->created_at->translatedFormat('d F Y, H:i') : null,
                'berkas_bukti_url' => !empty($u->berkas_bukti) ? asset('storage/' . ltrim($u->berkas_bukti, '/')) : null,
            ];
        }

        $menungguCount = $usulanList->where('status', 'menunggu')->count();
        $disetujuiCount = $usulanList->where('status', 'disetujui')->count();
        $ditolakCount = $usulanList->where('status', 'ditolak')->count();
        $dapodikCount = $usulanList->where('status', 'sudah_ke_dapodik')->count();

        $statusAgregat = 'menunggu';
        if ($menungguCount > 0) {
            $statusAgregat = 'menunggu';
        } elseif ($disetujuiCount > 0) {
            $statusAgregat = 'disetujui';
        } elseif ($ditolakCount === count($itemsData) && count($itemsData) > 0) {
            $statusAgregat = 'ditolak';
        } elseif ($dapodikCount === count($itemsData) && count($itemsData) > 0) {
            $statusAgregat = 'sudah_ke_dapodik';
        }

        return [
            'status' => 'success',
            'data' => [
                'id' => $usulanList->first()?->id,
                'peserta_didik_id' => $pdId,
                'nama_siswa' => $pd?->nama ?: 'Siswa #' . $pdId,
                'nisn' => $pd?->nisn ?: '-',
                'nipd' => $pd?->nipd ?: '-',
                'rombel' => $pd?->nama_rombel ?: '-',
                'foto_url' => $meta?->foto_url ?? null,
                'total_usulan' => count($itemsData),
                'menunggu_count' => $menungguCount,
                'disetujui_count' => $disetujuiCount,
                'ditolak_count' => $ditolakCount,
                'dapodik_count' => $dapodikCount,
                'status' => $statusAgregat,
                'items' => $itemsData,
                'kolom_perubahan' => $usulanList->first()?->kolom_perubahan,
                'kolom_label' => $kolomLabels[$usulanList->first()?->kolom_perubahan ?? ''] ?? '',
                'nilai_lama' => $usulanList->first()?->nilai_lama ?: '(Belum Terisi / Kosong)',
                'nilai_baru' => $usulanList->first()?->nilai_baru,
                'alasan' => $usulanList->first()?->alasan ?: 'Penyesuaian formulir identitas mandiri.',
                'catatan_verifikasi' => $usulanList->first()?->catatan_verifikasi,
                'verified_by' => $usulanList->first()?->verified_by,
                'verified_at' => $usulanList->first()?->verified_at ? $usulanList->first()->verified_at->translatedFormat('d F Y, H:i') : null,
                'created_at' => $usulanList->first()?->created_at ? $usulanList->first()->created_at->translatedFormat('d F Y, H:i') : null,
                'berkas_bukti_url' => !empty($usulanList->first()?->berkas_bukti) ? asset('storage/' . ltrim($usulanList->first()->berkas_bukti, '/')) : null,
                'kk' => [
                    'exists' => !empty($kk),
                    'status' => $kk?->status ?? 'belum_unggah',
                    'file_url' => $kk ? route('dashboard.berkas.preview', $kk->id) : null,
                    'file_name' => $kk?->file_name,
                ],
                'ijazah' => [
                    'exists' => !empty($ijazah),
                    'status' => $ijazah?->status ?? 'belum_unggah',
                    'file_url' => $ijazah ? route('dashboard.berkas.preview', $ijazah->id) : null,
                    'file_name' => $ijazah?->file_name,
                ],
                'akta' => [
                    'exists' => !empty($akta),
                    'status' => $akta?->status ?? 'belum_unggah',
                    'file_url' => $akta ? route('dashboard.berkas.preview', $akta->id) : null,
                    'file_name' => $akta?->file_name,
                ],
            ],
            'rekomendasi_penolakan' => $rekomendasiPenolakan,
        ];
    }

    /**
     * Verifikasi Usulan Perubahan Data Siswa (Setujui / Tolak).
     */
    public function verifikasiUsulan(Request $request, string|int $id, mixed $user): array
    {
        $validated = $request->validate([
            'status'             => 'nullable|string|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
            'usulan_ids'         => 'nullable|array',
            'usulan_ids.*'       => 'integer',
            'items'              => 'nullable|array',
            'items.*.id'         => 'required|integer',
            'items.*.status'     => 'required|string|in:disetujui,ditolak',
        ]);

        $catatan = trim((string)($validated['catatan_verifikasi'] ?? ''));

        // Keputusan per butir usulan: { id => disetujui|ditolak }.
        $decisions = [];
        foreach ($validated['items'] ?? [] as $it) {
            $decisions[(int) $it['id']] = $it['status'];
        }

        $status = $validated['status'] ?? null;
        $hasRejected = in_array('ditolak', $decisions, true) || $status === 'ditolak';

        if ($status === null && empty($decisions)) {
            return ['status' => 'error', 'message' => 'Tidak ada keputusan verifikasi yang dikirim.', 'status_code' => 422];
        }

        if ($hasRejected && $catatan === '') {
            return [
                'status' => 'error',
                'message' => 'Alasan atau catatan penolakan wajib diisi jika usulan perubahan data ditolak.',
                'status_code' => 422,
            ];
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Verifikator') : ($user->nama ?? 'Verifikator');

        $isPd = PesertaDidik::where('peserta_didik_id', $id)->exists()
            || (Table::exists('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

        if ($isPd) {
            $query = SiswaUsulanPerubahan::where('peserta_didik_id', $id);
            $targetIds = !empty($validated['usulan_ids']) ? $validated['usulan_ids'] : array_keys($decisions);
            if (!empty($targetIds)) {
                $query->whereIn('id', $targetIds);
            }
            $usulanList = $query->get();
            $pdId = $id;
        } else {
            $single = SiswaUsulanPerubahan::findOrFail($id);
            $usulanList = collect([$single]);
            $pdId = $single->peserta_didik_id;
        }

        $pdUpdates = [];
        $identitasUpdates = [];

        foreach ($usulanList as $usulan) {
            $rowStatus = $decisions[(int) $usulan->id] ?? $status ?? 'disetujui';
            $usulan->status = $rowStatus;
            $usulan->catatan_verifikasi = $catatan ?: null;
            $usulan->verified_by = $userName;
            $usulan->verified_at = now();
            $usulan->save();

            if ($rowStatus === 'disetujui') {
                if (Schema::hasColumn('peserta_didik', $usulan->kolom_perubahan)) {
                    $pdUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
                if (Table::exists('peserta_didik_identitas') && Schema::hasColumn('peserta_didik_identitas', $usulan->kolom_perubahan)) {
                    $identitasUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
            }
        }

        if (!empty($pdUpdates)) {
            DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->update($pdUpdates);
        }

        $remainingPending = 0;

        if (Table::exists('peserta_didik_identitas')) {
            $remainingPending = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->where('status', 'menunggu')
                ->count();

            if ($remainingPending === 0) {
                if ($hasRejected) {
                    // Buka kembali formulir agar siswa dapat mengusulkan ulang.
                    $identitasUpdates['status_konfirmasi'] = 'perlu_perbaikan';
                    $identitasUpdates['catatan_kesiswaan'] = "Usulan perubahan data ditolak: " . ($catatan ?: 'Dokumen pendukung tidak sesuai.');
                } else {
                    $identitasUpdates['status_konfirmasi'] = 'diverifikasi';
                    $identitasUpdates['catatan_kesiswaan'] = 'Seluruh usulan perubahan data siswa telah disetujui & diverifikasi oleh Tim Kesiswaan.';
                }
            }

            if (!empty($identitasUpdates)) {
                DB::table('peserta_didik_identitas')->where('peserta_didik_id', $pdId)->update($identitasUpdates);
            }
        }

        if ($hasRejected) {
            $this->notifyStudent(
                $pdId,
                'Usulan Perubahan Data Ditolak',
                'Usulan perubahan data Anda ditolak oleh Tim Kesiswaan. Alasan: ' . ($catatan ?: 'Dokumen pendukung tidak sesuai.') . ' Silakan perbaiki dan ajukan ulang melalui formulir identitas.',
                'danger',
                'fas fa-circle-xmark'
            );
        } elseif ($remainingPending === 0) {
            $this->notifyStudent(
                $pdId,
                'Usulan Perubahan Data Disetujui',
                'Usulan perubahan data Anda telah disetujui Tim Kesiswaan dan menunggu di-input ke aplikasi Dapodik.',
                'success',
                'fas fa-circle-check'
            );
        }

        // Notifikasi ke Tim Kesiswaan/Admin lain bahwa verifikasi telah dilakukan.
        $pdNamaAdmin = PesertaDidik::where('peserta_didik_id', $pdId)->value('nama');
        $this->notifyKesiswaanAdmins(
            'Verifikasi Usulan: ' . ($pdNamaAdmin ?: ('Siswa #' . $pdId)),
            ($pdNamaAdmin ?: 'Seorang siswa') . " — verifikasi usulan selesai oleh {$userName}. "
                . count($usulanList) . ' butir: ' . ($hasRejected ? 'ada yang ditolak' : 'disetujui') . '.',
            $hasRejected ? 'warning' : 'success',
            'fas fa-clipboard-check',
            is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null)
        );

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
            $labelStatus = $hasRejected ? 'Disetujui/Ditolak Sebagian' : 'Disetujui';
            $count = count($usulanList);
            AdminAktivitas::record(
                "Verifikasi Usulan Data Siswa: {$pd?->nama} ({$count} butir usulan) [{$labelStatus}]",
                'Kesiswaan',
                $hasRejected ? "Alasan Penolakan: {$catatan}" : "Usulan perubahan data disetujui dan data siswa diperbarui.",
                $hasRejected ? 'warning' : 'success'
            );
        }

        return [
            'status' => 'success',
            'message' => "Verifikasi {$usulanList->count()} butir usulan berhasil disimpan.",
            'data' => $usulanList,
        ];
    }

    /**
     * Tandai Usulan Perubahan Data telah Di-update / Disinkronkan ke Aplikasi Dapodik.
     */
    public function markDapodikUpdated(string|int $id, mixed $user): array
    {
        $userName = is_array($user) ? ($user['nama'] ?? 'Operator Dapodik') : ($user->nama ?? 'Operator Dapodik');

        $isPd = PesertaDidik::where('peserta_didik_id', $id)->exists()
            || (Table::exists('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

        if ($isPd) {
            $usulanList = SiswaUsulanPerubahan::where('peserta_didik_id', $id)
                ->where('status', 'disetujui')
                ->get();
            $pdId = $id;
        } else {
            $single = SiswaUsulanPerubahan::findOrFail($id);
            $usulanList = collect([$single]);
            $pdId = $single->peserta_didik_id;
        }

        foreach ($usulanList as $usulan) {
            $usulan->status = 'sudah_ke_dapodik';
            $usulan->catatan_verifikasi = ($usulan->catatan_verifikasi ? $usulan->catatan_verifikasi . ' | ' : '') . "Telah di-update ke Aplikasi Dapodik oleh {$userName} pada " . now()->translatedFormat('d M Y, H:i');
            $usulan->save();
        }

        if (Table::exists('peserta_didik_identitas')) {
            $remaining = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->count();

            if ($remaining === 0) {
                DB::table('peserta_didik_identitas')
                    ->where('peserta_didik_id', $pdId)
                    ->update([
                        'status_konfirmasi' => 'disinkronkan_dapodik',
                        'catatan_kesiswaan' => 'Seluruh usulan perubahan data siswa telah selesai di-input ke aplikasi Dapodik.',
                    ]);

                $this->notifyStudent(
                    $pdId,
                    'Perubahan Data Berhasil ke Dapodik',
                    'Usulan perubahan data Anda telah diterima dan datanya telah disesuaikan/diperbarui ke aplikasi Dapodik sesuai usulan yang disetujui.',
                    'success',
                    'fas fa-cloud-arrow-up'
                );
            }
        }

        // Notifikasi ke Tim Kesiswaan/Admin lain bahwa data telah disinkronkan ke Dapodik.
        $pdNamaDapodik = PesertaDidik::where('peserta_didik_id', $pdId)->value('nama');
        $this->notifyKesiswaanAdmins(
            'Dapodik Diperbarui: ' . ($pdNamaDapodik ?: ('Siswa #' . $pdId)),
            ($pdNamaDapodik ?: 'Seorang siswa') . " — usulan identitas telah ditandai selesai di-input ke Dapodik oleh {$userName}.",
            'success',
            'fas fa-cloud-arrow-up',
            is_array($user) ? ($user['pengguna_id'] ?? null) : ($user->pengguna_id ?? null)
        );

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
            AdminAktivitas::record(
                "Update Dapodik Selesai: {$pd?->nama}",
                'Kesiswaan',
                "Perubahan data siswa berhasil ditandai selesai di-input ke Dapodik.",
                'success'
            );
        }

        return [
            'status' => 'success',
            'message' => 'Berhasil! Data perubahan ditandai telah selesai di-update ke aplikasi Dapodik.',
        ];
    }
}
