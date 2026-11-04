<?php

namespace App\Services;

use App\Support\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pasfoto GTK (Guru/Tendik Dapodik & Non-ASN) dari menu Manajemen Data.
 *
 * Foto disimpan di `pengguna.foto_path` — kolom yang sudah ada, tidak ikut
 * ditimpa saat tarik Dapodik (processPengguna tidak menyentuh foto_path),
 * dan langsung dipakai sebagai avatar login. Non-ASN ditautkan lewat
 * gtk_non_asn.pengguna_id.
 */
class GtkFotoService
{
    /** Cache foto_path per pengguna_id selama satu request. */
    private array $fotoCache = [];

    /**
     * Unggah & kompresi pasfoto GTK (PNG, sama seperti pasfoto peserta didik).
     *
     * @return array{status:string, message:string, status_code?:int, data?:array}
     */
    public function uploadFoto(Request $request, ImageOptimizerService $optimizer): array
    {
        $ptkId = trim((string) $request->input('ptk_id', ''));
        if ($ptkId === '') {
            return ['status' => 'error', 'message' => 'GTK tidak valid.', 'status_code' => 422];
        }
        if (!$request->hasFile('foto')) {
            return ['status' => 'error', 'message' => 'Berkas pasfoto belum dipilih.', 'status_code' => 422];
        }

        $pengguna = $this->penggunaUntuk($ptkId);
        if (!$pengguna) {
            return [
                'status' => 'error',
                'status_code' => 404,
                'message' => 'Akun login GTK ini belum ada. Buat akunnya dulu sebelum mengunggah pasfoto.',
            ];
        }

        try {
            if (!empty($pengguna->foto_path)) {
                $optimizer->deleteFile($pengguna->foto_path);
            }

            $prefix = 'foto_gtk_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $ptkId)
                . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($pengguna->username ?? ''));
            $result = $optimizer->optimizeAndSavePng(
                $request->file('foto'),
                ImageOptimizerService::ASSET_DIR_FOTO_GTK,
                $prefix,
                1000
            );

            DB::table('pengguna')->where('pengguna_id', $pengguna->pengguna_id)->update([
                'foto_path' => $result['path'],
                'updated_at' => now(),
            ]);
            unset($this->fotoCache[$pengguna->pengguna_id]);

            return [
                'status' => 'success',
                'message' => 'Pasfoto berhasil disimpan.',
                'data' => [
                    'ptk_id' => $ptkId,
                    'foto_url' => asset('storage/' . $result['path']),
                    'foto_size' => $this->formatSize((int) $result['size']),
                    'savings' => $result['savings_percent'],
                    'nama' => $pengguna->nama,
                ],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Gagal memproses foto: ' . $e->getMessage(), 'status_code' => 500];
        }
    }

    /**
     * Hapus pasfoto GTK.
     */
    public function deleteFoto(string $ptkId, ImageOptimizerService $optimizer): array
    {
        $pengguna = $this->penggunaUntuk($ptkId);
        if (!$pengguna) {
            return ['status' => 'error', 'message' => 'Akun login GTK tidak ditemukan.', 'status_code' => 404];
        }
        if (empty($pengguna->foto_path)) {
            return ['status' => 'error', 'message' => 'Pasfoto GTK belum ada.', 'status_code' => 404];
        }

        $optimizer->deleteFile($pengguna->foto_path);
        DB::table('pengguna')->where('pengguna_id', $pengguna->pengguna_id)->update([
            'foto_path' => null,
            'updated_at' => now(),
        ]);
        unset($this->fotoCache[$pengguna->pengguna_id]);

        return ['status' => 'success', 'message' => 'Pasfoto GTK berhasil dihapus.'];
    }

    /**
     * URL pasfoto dari pengguna_id (dipakai daftar Non-ASN).
     */
    public function fotoUrlPengguna(?string $penggunaId): ?string
    {
        if (empty($penggunaId)) {
            return null;
        }

        if (!array_key_exists($penggunaId, $this->fotoCache)) {
            $this->fotoCache[$penggunaId] = DB::table('pengguna')
                ->where('pengguna_id', $penggunaId)
                ->value('foto_path');
        }

        return self::url($this->fotoCache[$penggunaId]);
    }

    /**
     * URL publik berkas pasfoto dari path relatif storage.
     */
    public static function url(?string $path): ?string
    {
        return !empty($path) ? asset('storage/' . ltrim($path, '/')) : null;
    }

    /**
     * Baris `pengguna` pemilik pasfoto untuk ptk_id Dapodik maupun semu (nonasn:).
     */
    private function penggunaUntuk(string $ptkId): ?object
    {
        if (GtkGabunganService::isNonAsn($ptkId)) {
            if (!Table::exists('gtk_non_asn')) {
                return null;
            }
            $row = DB::table('gtk_non_asn')->where('id', substr($ptkId, 7))->first();
            if (!$row || empty($row->pengguna_id)) {
                return null;
            }

            return DB::table('pengguna')->where('pengguna_id', $row->pengguna_id)->first();
        }

        return DB::table('pengguna')->where('ptk_id', $ptkId)->first();
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}
