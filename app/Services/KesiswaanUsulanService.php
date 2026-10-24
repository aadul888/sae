<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use App\Models\PesertaDidikMeta;
use App\Models\SiswaUsulanPerubahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesiswaanUsulanService
{
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
            $file = $request->file('berkas_bukti');
            $fileName = 'usulan_' . time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/usulan_siswa'), $fileName);
            $filePath = '/uploads/usulan_siswa/' . $fileName;
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Pengguna') : ($user->nama ?? 'Pengguna');

        $usulan = SiswaUsulanPerubahan::create([
            'peserta_didik_id'   => $validated['peserta_didik_id'],
            'kolom_perubahan'    => $validated['kolom_perubahan'],
            'nilai_lama'         => $validated['nilai_lama'] ?? null,
            'nilai_baru'         => $validated['nilai_baru'],
            'alasan'             => $validated['alasan'],
            'berkas_bukti'       => $filePath,
            'status'             => 'menunggu',
            'created_by'         => $userName,
        ]);

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
        if (!$pd && Schema::hasTable('peserta_didik_tidak_aktif')) {
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
        if (Schema::hasTable('peserta_didik_meta') && $pd) {
            $meta = PesertaDidikMeta::where('peserta_didik_id', $pdId)->first();
        }

        $berkas = collect();
        if (Schema::hasTable('peserta_didik_berkas') && $pd) {
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
            'status'             => 'required|string|in:disetujui,ditolak',
            'catatan_verifikasi' => 'nullable|string',
            'usulan_ids'         => 'nullable|array',
            'usulan_ids.*'       => 'integer',
        ]);

        $status = $validated['status'];
        $catatan = trim((string)($validated['catatan_verifikasi'] ?? ''));

        if ($status === 'ditolak' && empty($catatan)) {
            return [
                'status' => 'error',
                'message' => 'Alasan atau catatan penolakan wajib diisi jika usulan perubahan data ditolak.',
                'status_code' => 422,
            ];
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Verifikator') : ($user->nama ?? 'Verifikator');

        $isPd = PesertaDidik::where('peserta_didik_id', $id)->exists()
            || (Schema::hasTable('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

        if ($isPd) {
            $query = SiswaUsulanPerubahan::where('peserta_didik_id', $id);
            if (!empty($validated['usulan_ids'])) {
                $query->whereIn('id', $validated['usulan_ids']);
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
            $usulan->status = $status;
            $usulan->catatan_verifikasi = $catatan ?: null;
            $usulan->verified_by = $userName;
            $usulan->verified_at = now();
            $usulan->save();

            if ($status === 'disetujui') {
                if (Schema::hasColumn('peserta_didik', $usulan->kolom_perubahan)) {
                    $pdUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
                if (Schema::hasTable('peserta_didik_identitas') && Schema::hasColumn('peserta_didik_identitas', $usulan->kolom_perubahan)) {
                    $identitasUpdates[$usulan->kolom_perubahan] = $usulan->nilai_baru;
                }
            }
        }

        if (!empty($pdUpdates)) {
            DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->update($pdUpdates);
        }

        if (Schema::hasTable('peserta_didik_identitas')) {
            $remainingPending = SiswaUsulanPerubahan::where('peserta_didik_id', $pdId)
                ->where('status', 'menunggu')
                ->count();

            if ($remainingPending === 0 && $status === 'disetujui') {
                $identitasUpdates['status_konfirmasi'] = 'diverifikasi';
                $identitasUpdates['catatan_kesiswaan'] = 'Seluruh usulan perubahan data siswa telah disetujui & diverifikasi oleh Tim Kesiswaan.';
            } elseif ($status === 'ditolak') {
                $identitasUpdates['catatan_kesiswaan'] = "Usulan perubahan data ditolak: " . ($catatan ?: 'Dokumen pendukung tidak sesuai.');
            }

            if (!empty($identitasUpdates)) {
                DB::table('peserta_didik_identitas')->where('peserta_didik_id', $pdId)->update($identitasUpdates);
            }
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $pdId)->first();
            $labelStatus = $status === 'disetujui' ? 'Disetujui' : 'Ditolak';
            $count = count($usulanList);
            AdminAktivitas::record(
                "Verifikasi Usulan Data Siswa: {$pd?->nama} ({$count} butir usulan) [{$labelStatus}]",
                'Kesiswaan',
                $status === 'ditolak' ? "Alasan Penolakan: {$catatan}" : "Usulan perubahan data disetujui dan data siswa diperbarui.",
                $status === 'disetujui' ? 'success' : 'warning'
            );
        }

        return [
            'status' => 'success',
            'message' => "Usulan perubahan data (" . count($usulanList) . " butir) berhasil " . ($status === 'disetujui' ? 'disetujui' : 'ditolak') . ".",
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
            || (Schema::hasTable('peserta_didik_tidak_aktif') && DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->exists());

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

        if (Schema::hasTable('peserta_didik_identitas')) {
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
            }
        }

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
