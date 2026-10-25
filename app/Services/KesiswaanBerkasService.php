<?php

namespace App\Services;

use App\Models\AdminAktivitas;
use App\Models\KesiswaanBerkasVerifikasi;
use App\Models\PesertaDidik;
use App\Models\PesertaDidikBerkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


use App\Support\Table;

class KesiswaanBerkasService
{
    /**
     * Update Checklist Verifikasi Berkas Fisik Siswa Baru.
     */
    public function updateBerkas(Request $request, string|int $id, mixed $user): array
    {
        $berkas = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);

        $berkas->akta_kelahiran = $request->boolean('akta_kelahiran');
        $berkas->kartu_keluarga = $request->boolean('kartu_keluarga');
        $berkas->ijazah_smp     = $request->boolean('ijazah_smp');
        $berkas->ktp_orang_tua  = $request->boolean('ktp_orang_tua');
        $berkas->kip_pip        = $request->boolean('kip_pip');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->save();

        return [
            'status' => 'success',
            'message' => 'Verifikasi kelengkapan berkas berhasil diperbarui.',
            'data' => $berkas,
        ];
    }

    /**
     * Ambil data berkas digital lengkap per siswa (JSON) untuk Modal Verifikasi Berkas Kesiswaan.
     */
    public function getBerkasDetail(string|int $id): array
    {
        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Table::exists('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return ['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $berkas = PesertaDidikBerkas::where('peserta_didik_id', $id)->get()->keyBy('jenis_berkas');
        $rekomendasi = PesertaDidikBerkas::getRekomendasiPenolakanOptions();

        $items = [];
        foreach ($slots as $k => $slot) {
            $b = $berkas->get($k);
            $items[] = [
                'jenis_berkas' => $k,
                'label' => $slot['label'],
                'deskripsi' => $slot['deskripsi'],
                'icon' => $slot['icon'],
                'wajib' => $slot['wajib'],
                'is_uploaded' => !empty($b) && !empty($b->file_path),
                'berkas_id' => $b?->id,
                'file_name' => $b?->file_name,
                'file_size' => $b?->formatted_file_size,
                'file_url' => (!empty($b) && !empty($b->file_path)) ? route('dashboard.berkas.preview', $b->id) : null,
                'status' => $b?->status ?? 'belum_unggah',
                'catatan_penolakan' => $b?->catatan_penolakan,
                'rekomendasi_penolakan' => $b?->rekomendasi_penolakan,
                'verified_by' => $b?->verified_by,
                'verified_at' => $b?->verified_at ? $b->verified_at->translatedFormat('d M Y, H:i') : null,
                'uploaded_at' => $b?->created_at ? $b->created_at->translatedFormat('d M Y, H:i') : null,
            ];
        }

        return [
            'status' => 'success',
            'siswa' => [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'nama' => $pd->nama,
                'nisn' => $pd->nisn ?? '-',
                'nipd' => $pd->nipd ?? '-',
                'rombel' => $pd->nama_rombel ?? '-',
            ],
            'items' => $items,
            'rekomendasi_penolakan' => $rekomendasi,
        ];
    }

    /**
     * Verifikasi Kebenaran Berkas Siswa (valid atau tidak_valid).
     */
    public function verifikasiBerkasItem(Request $request, string|int $id, mixed $user): array
    {
        $request->validate([
            'jenis_berkas' => 'required|string',
            'status' => 'required|in:valid,tidak_valid',
            'catatan_penolakan' => 'nullable|string',
            'rekomendasi_penolakan' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $catatan = trim((string)$request->input('catatan_penolakan', ''));
        $rekomendasi = trim((string)$request->input('rekomendasi_penolakan', ''));

        if ($status === 'tidak_valid' && empty($catatan) && empty($rekomendasi)) {
            return [
                'status' => 'error',
                'message' => 'Untuk status tidak valid / tidak sesuai, wajib memberikan catatan atau memilih rekomendasi alasan penolakan.',
                'status_code' => 422,
            ];
        }

        $berkas = PesertaDidikBerkas::firstOrNew([
            'peserta_didik_id' => $id,
            'jenis_berkas' => $request->input('jenis_berkas'),
        ]);

        if (!$berkas->exists) {
            $slots = PesertaDidikBerkas::getJenisBerkasOptions();
            $berkas->nama_berkas = $slots[$request->input('jenis_berkas')]['label'] ?? 'Dokumen Siswa';
            $berkas->file_path = '';
            $berkas->file_name = '(Verifikasi Fisik / Manual)';
            $berkas->mime_type = 'application/pdf';
        }

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        $berkas->status = $status;
        $berkas->verified_by = $userName;
        $berkas->verified_at = now();
        $berkas->catatan_penolakan = $status === 'tidak_valid' ? ($catatan ?: $rekomendasi) : null;
        $berkas->rekomendasi_penolakan = $status === 'tidak_valid' ? $rekomendasi : null;
        $berkas->save();

        $coreColumns = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua', 'kip_pip'];
        if (in_array($berkas->jenis_berkas, $coreColumns, true)) {
            $kbv = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);
            $kbv->{$berkas->jenis_berkas} = ($status === 'valid');
            $kbv->verified_by = $userName;
            $kbv->verified_at = now();
            $kbv->save();
        }

        if (class_exists(AdminAktivitas::class)) {
            $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
            $statusLabel = $status === 'valid' ? 'Valid (Sesuai)' : 'Tidak Valid (Tidak Sesuai)';
            AdminAktivitas::record(
                "Verifikasi Berkas Siswa: {$pd?->nama} - {$berkas->nama_berkas} ({$statusLabel})",
                'Kesiswaan',
                $status === 'tidak_valid' ? "Penolakan: " . ($berkas->catatan_penolakan ?: '-') : "Dokumen diverifikasi valid dan sesuai persyaratan.",
                $status === 'valid' ? 'success' : 'warning'
            );
        }

        return [
            'status' => 'success',
            'message' => "Berkas \"{$berkas->nama_berkas}\" berhasil divalidasi sebagai: " . ($status === 'valid' ? 'Valid / Sesuai' : 'Tidak Valid / Tidak Sesuai') . '.',
            'data' => $berkas,
        ];
    }

    /**
     * Upload berkas siswa langsung oleh Tendik / Staf Kesiswaan.
     */
    public function uploadBerkasTendik(Request $request, string|int $id, mixed $user): array
    {
        $request->validate([
            'jenis_berkas' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'file.required' => 'Pilih file dokumen yang ingin diunggah.',
            'file.mimes' => 'Format file harus PDF, JPG, JPEG, atau PNG.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $pd = PesertaDidik::where('peserta_didik_id', $id)->first();
        if (!$pd && Table::exists('peserta_didik_tidak_aktif')) {
            $pd = DB::table('peserta_didik_tidak_aktif')->where('peserta_didik_id', $id)->first();
        }

        if (!$pd) {
            return ['status' => 'error', 'message' => 'Peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $file = $request->file('file');
        $jenisBerkas = $request->input('jenis_berkas');
        $slots = PesertaDidikBerkas::getJenisBerkasOptions();
        $namaBerkas = $slots[$jenisBerkas]['label'] ?? 'Dokumen Siswa';

        $existing = PesertaDidikBerkas::where('peserta_didik_id', $pd->peserta_didik_id)
            ->where('jenis_berkas', $jenisBerkas)
            ->first();

        if ($existing && !empty($existing->file_path)) {
            Storage::disk('public')->delete($existing->file_path);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = $jenisBerkas . '_' . time() . '.' . $extension;
        $directory = 'berkas_siswa/' . $pd->peserta_didik_id;
        $savedPath = $file->storeAs($directory, $fileName, 'public');

        $userName = is_array($user) ? ($user['nama'] ?? 'Staf Kesiswaan') : ($user->nama ?? 'Staf Kesiswaan');

        $berkas = PesertaDidikBerkas::updateOrCreate(
            [
                'peserta_didik_id' => $pd->peserta_didik_id,
                'jenis_berkas' => $jenisBerkas,
            ],
            [
                'nama_berkas' => $namaBerkas,
                'file_path' => $savedPath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => 'valid',
                'catatan_penolakan' => null,
                'rekomendasi_penolakan' => null,
                'verified_by' => $userName . ' (Tendik)',
                'verified_at' => now(),
                'uploaded_by' => $userName . ' (Tendik Kesiswaan)',
            ]
        );

        $coreColumns = ['akta_kelahiran', 'kartu_keluarga', 'ijazah_smp', 'ktp_orang_tua', 'kip_pip'];
        if (in_array($berkas->jenis_berkas, $coreColumns, true)) {
            $kbv = KesiswaanBerkasVerifikasi::firstOrNew(['peserta_didik_id' => $id]);
            $kbv->{$berkas->jenis_berkas} = true;
            $kbv->verified_by = $userName;
            $kbv->verified_at = now();
            $kbv->save();
        }

        if (class_exists(AdminAktivitas::class)) {
            AdminAktivitas::record(
                "Upload Berkas Tendik: {$pd->nama} - {$berkas->nama_berkas} (Valid)",
                'Kesiswaan',
                "Dokumen diunggah langsung oleh {$userName} dan otomatis diverifikasi valid.",
                'success'
            );
        }

        return [
            'status' => 'success',
            'message' => "Berkas \"{$berkas->nama_berkas}\" berhasil diunggah dan otomatis diverifikasi VALID.",
            'data' => [
                'berkas_id' => $berkas->id,
                'file_name' => $berkas->file_name,
                'file_size' => $berkas->formatted_file_size,
                'file_url' => route('dashboard.berkas.preview', $berkas->id),
                'status' => 'valid',
                'verified_by' => $berkas->verified_by,
                'verified_at' => $berkas->verified_at ? $berkas->verified_at->translatedFormat('d M Y, H:i') : null,
                'uploaded_at' => $berkas->created_at ? $berkas->created_at->translatedFormat('d M Y, H:i') : null,
            ]
        ];
    }
}
