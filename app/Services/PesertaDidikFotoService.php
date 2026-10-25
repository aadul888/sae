<?php

namespace App\Services;

use App\Models\PesertaDidikMeta;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PesertaDidikFotoService
{
    /**
     * Unggah dan auto-kompresi pasfoto peserta didik (khusus format PNG).
     */
    public function uploadFoto(Request $request, ImageOptimizerService $optimizer, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        $pdId = $request->input('peserta_didik_id');
        $pesertaDidik = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
        if (!$pesertaDidik) {
            return ['status' => 'error', 'message' => 'Data peserta didik tidak ditemukan di database.', 'status_code' => 404];
        }

        if (!$isAdmin && $isWaliOrAdmin) {
            $waliRombelInfo = RolePermission::getWaliKelasRombelInfo($user);
            $waliRombelName = $waliRombelInfo?->nama ?: RolePermission::getWaliKelasRombel($user);
            $waliRombelId = $waliRombelInfo?->rombongan_belajar_id ?? null;

            $isMatch = false;
            if ($waliRombelId && !empty($pesertaDidik->rombongan_belajar_id) && $pesertaDidik->rombongan_belajar_id === $waliRombelId) {
                $isMatch = true;
            } elseif ($waliRombelName && !empty($pesertaDidik->nama_rombel) && strcasecmp(trim($pesertaDidik->nama_rombel), trim($waliRombelName)) === 0) {
                $isMatch = true;
            } elseif (!$waliRombelName && !$waliRombelId) {
                $isMatch = true;
            }

            if (!$isMatch) {
                return [
                    'status' => 'error',
                    'message' => 'Akses ditolak: Anda hanya berwenang mengubah pasfoto peserta didik di kelas binaan Anda (' . ($waliRombelName ?: 'Kelas Binaan') . ').',
                    'status_code' => 403,
                ];
            }
        }

        try {
            $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);

            if ($meta->foto_path) {
                $optimizer->deleteFile($meta->foto_path);
            }

            $prefix = 'foto_' . ($pesertaDidik->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $pdId));
            $result = $optimizer->optimizeAndSavePng(
                $request->file('foto'),
                ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                $prefix,
                1000
            );

            $meta->nisn = $pesertaDidik->nisn;
            $meta->foto_path = $result['path'];
            $meta->foto_size = $result['size'];
            $meta->foto_width = $result['width'];
            $meta->foto_height = $result['height'];
            $meta->save();

            DB::table('pengguna')->where('peserta_didik_id', $pdId)->update(['foto_path' => $result['path']]);

            $sessUser = session('user');
            if (is_array($sessUser) && (($sessUser['peserta_didik_id'] ?? null) === $pdId || ($sessUser['pengguna_id'] ?? null) === $pdId)) {
                $sessUser['foto_path'] = $result['path'];
                $sessUser['foto_url'] = $meta->foto_url;
                session(['user' => $sessUser]);
            }

            return [
                'status' => 'success',
                'message' => 'Foto peserta didik berhasil disimpan dan dikompresi.',
                'data' => [
                    'foto_url' => $meta->foto_url,
                    'foto_size' => $meta->formatted_foto_size,
                    'width' => $result['width'],
                    'height' => $result['height'],
                    'savings' => $result['savings_percent'],
                    'peserta_didik_id' => $pdId,
                    'nama' => $pesertaDidik->nama,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'message' => 'Gagal memproses foto: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    /**
     * Hapus pasfoto peserta didik.
     */
    public function deleteFoto(string|int $id, ImageOptimizerService $optimizer, mixed $user): array
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $id)->first();
        if (!$isAdmin && $isWaliOrAdmin && $pd) {
            $waliRombelInfo = RolePermission::getWaliKelasRombelInfo($user);
            $waliRombelName = $waliRombelInfo?->nama ?: RolePermission::getWaliKelasRombel($user);
            $waliRombelId = $waliRombelInfo?->rombongan_belajar_id ?? null;

            $isMatch = false;
            if ($waliRombelId && !empty($pd->rombongan_belajar_id) && $pd->rombongan_belajar_id === $waliRombelId) {
                $isMatch = true;
            } elseif ($waliRombelName && !empty($pd->nama_rombel) && strcasecmp(trim($pd->nama_rombel), trim($waliRombelName)) === 0) {
                $isMatch = true;
            } elseif (!$waliRombelName && !$waliRombelId) {
                $isMatch = true;
            }

            if (!$isMatch) {
                return [
                    'status' => 'error',
                    'message' => 'Akses ditolak: Anda hanya berwenang menghapus pasfoto peserta didik di kelas binaan Anda (' . ($waliRombelName ?: 'Kelas Binaan') . ').',
                    'status_code' => 403,
                ];
            }
        }

        $meta = PesertaDidikMeta::where('peserta_didik_id', $id)->first();
        if (!$meta || empty($meta->foto_path)) {
            return ['status' => 'error', 'message' => 'Foto peserta didik tidak ditemukan.', 'status_code' => 404];
        }

        $optimizer->deleteFile($meta->foto_path);
        $meta->foto_path = null;
        $meta->foto_size = null;
        $meta->foto_width = null;
        $meta->foto_height = null;
        $meta->save();

        DB::table('pengguna')->where('peserta_didik_id', $id)->update(['foto_path' => null]);

        $sessUser = session('user');
        if (is_array($sessUser) && (($sessUser['peserta_didik_id'] ?? null) === (string)$id || ($sessUser['pengguna_id'] ?? null) === (string)$id)) {
            $sessUser['foto_path'] = null;
            $sessUser['foto_url'] = null;
            session(['user' => $sessUser]);
        }

        return [
            'status' => 'success',
            'message' => 'Foto peserta didik berhasil dihapus.',
        ];
    }

    /**
     * Unduh berkas fisik pasfoto peserta didik.
     */
    public function downloadFoto(string|int $id, mixed $user)
    {
        $role = is_array($user) ? ($user['role'] ?? '') : ($user->role ?? '');
        $isAdmin = ($role === 'admin');
        $isWaliOrAdmin = RolePermission::isWaliKelasOrAdmin($user);

        $pd = DB::table('peserta_didik')->where('peserta_didik_id', $id)->first();
        if (!$pd) {
            abort(404, 'Data peserta didik tidak ditemukan.');
        }

        if (!$isAdmin && $isWaliOrAdmin) {
            $waliRombelInfo = RolePermission::getWaliKelasRombelInfo($user);
            $waliRombelName = $waliRombelInfo?->nama ?: RolePermission::getWaliKelasRombel($user);
            $waliRombelId = $waliRombelInfo?->rombongan_belajar_id ?? null;

            $isMatch = false;
            if ($waliRombelId && !empty($pd->rombongan_belajar_id) && $pd->rombongan_belajar_id === $waliRombelId) {
                $isMatch = true;
            } elseif ($waliRombelName && !empty($pd->nama_rombel) && strcasecmp(trim($pd->nama_rombel), trim($waliRombelName)) === 0) {
                $isMatch = true;
            } elseif (!$waliRombelName && !$waliRombelId) {
                $isMatch = true;
            }

            if (!$isMatch) {
                abort(403, 'Akses ditolak: Anda hanya berwenang mengunduh pasfoto peserta didik di kelas binaan Anda.');
            }
        }

        $meta = PesertaDidikMeta::where('peserta_didik_id', $id)->first();
        if (!$meta || empty($meta->foto_path)) {
            abort(404, 'Pasfoto peserta didik belum diunggah.');
        }

        $cleanPath = ltrim($meta->foto_path, '/');
        $fullPath = storage_path('app/public/' . $cleanPath);

        if (!File::exists($fullPath)) {
            abort(404, 'Berkas fisik pasfoto tidak ditemukan pada penyimpanan server.');
        }

        $cleanNama = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $pd->nama);
        $nisn = $pd->nisn ?: $pd->nipd ?: 'foto';
        $downloadFilename = "Pasfoto_{$nisn}_{$cleanNama}.png";

        return response()->download($fullPath, $downloadFilename, [
            'Content-Type' => 'image/png',
        ]);
    }

    /**
     * Menerima upload foto masal atau batch multipart.
     */
    public function bulkUploadFoto(Request $request, ImageOptimizerService $optimizer, mixed $user): array
    {
        if ($request->hasFile('foto') && $request->filled('peserta_didik_id')) {
            return $this->uploadFoto($request, $optimizer, $user);
        }

        if ($request->hasFile('zip')) {
            return $this->bulkUploadFotoZip($request, $optimizer);
        }

        $files = $request->file('files', []);
        $mappingsRaw = $request->input('mappings', '[]');
        $mappings = json_decode($mappingsRaw, true) ?: [];

        if (empty($files) || !is_array($files)) {
            return ['status' => 'error', 'message' => 'Tidak ada berkas foto yang dikirim.', 'status_code' => 422];
        }

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $pdId = $mappings[$originalName] ?? null;

            if (!$pdId) {
                $cleanName = pathinfo($originalName, PATHINFO_FILENAME);
                $foundStudent = DB::table('peserta_didik')
                    ->where('nisn', $cleanName)
                    ->orWhere('nipd', $cleanName)
                    ->orWhere('peserta_didik_id', $cleanName)
                    ->first();
                $pdId = $foundStudent?->peserta_didik_id;
            }

            if (!$pdId) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => 'Tidak dapat menemukan data peserta didik untuk berkas ini.',
                ];
                continue;
            }

            $student = DB::table('peserta_didik')->where('peserta_didik_id', $pdId)->first();
            if (!$student) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => "Peserta didik ID {$pdId} tidak ditemukan.",
                ];
                continue;
            }

            try {
                $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $pdId]);
                if ($meta->foto_path) {
                    $optimizer->deleteFile($meta->foto_path);
                }

                $prefix = 'foto_' . ($student->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $pdId));
                $optResult = $optimizer->optimizeAndSavePng(
                    $file,
                    ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                    $prefix,
                    1000
                );

                $meta->nisn = $student->nisn;
                $meta->foto_path = $optResult['path'];
                $meta->foto_size = $optResult['size'];
                $meta->foto_width = $optResult['width'];
                $meta->foto_height = $optResult['height'];
                $meta->save();

                DB::table('pengguna')->where('peserta_didik_id', $pdId)->update(['foto_path' => $optResult['path']]);

                $successCount++;
                $results[] = [
                    'peserta_didik_id' => $pdId,
                    'nama' => $student->nama,
                    'nisn' => $student->nisn,
                    'filename' => $originalName,
                    'status' => 'success',
                    'foto_url' => $meta->foto_url,
                    'foto_size' => $meta->formatted_foto_size,
                    'savings' => $optResult['savings_percent'],
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $results[] = [
                    'filename' => $originalName,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => "Proses masal selesai: {$successCount} foto berhasil disimpan, {$failedCount} gagal.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'data' => $results,
        ];
    }

    /**
     * Menerima upload berkas ZIP berisi pasfoto siswa rombel.
     */
    public function bulkUploadFotoZip(Request $request, ImageOptimizerService $optimizer): array
    {
        $request->validate([
            'zip' => 'required|file|mimes:zip|max:51200',
        ], [
            'zip.required' => 'File ZIP wajib diunggah.',
            'zip.mimes' => 'Format arsip harus berupa ZIP (.zip).',
            'zip.max' => 'Ukuran file ZIP maksimal adalah 50 MB.',
        ]);

        if (!class_exists(ZipArchive::class)) {
            return ['status' => 'error', 'message' => 'Ekstensi PHP ZipArchive belum aktif di server.', 'status_code' => 500];
        }

        $zipFile = $request->file('zip');
        $zip = new ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            return ['status' => 'error', 'message' => 'File ZIP tidak dapat dibuka atau rusak.', 'status_code' => 422];
        }

        $rombel = trim($request->input('rombel', ''));
        if (!$rombel) {
            $zipBaseName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);
            $foundRombel = DB::table('peserta_didik')
                ->where('nama_rombel', $zipBaseName)
                ->value('nama_rombel');
            if ($foundRombel) {
                $rombel = $foundRombel;
            }
        }

        $rombelStudents = collect();
        if ($rombel) {
            $rombelStudents = DB::table('peserta_didik')
                ->where('nama_rombel', $rombel)
                ->select('peserta_didik_id', 'nama', 'nisn', 'nipd', 'nik', 'nama_rombel')
                ->orderBy('nama', 'asc')
                ->get();
        }

        $tempDir = storage_path('app/temp/foto_zip_' . uniqid('', true));
        File::makeDirectory($tempDir, 0755, true, true);

        $successCount = 0;
        $failedCount = 0;
        $results = [];

        $allowedExts = ['png', 'jpg', 'jpeg'];

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                $basename = basename(str_replace('\\', '/', $entry));

                if ($basename === '' || str_ends_with($entry, '/') || str_starts_with($basename, '._') || str_contains($entry, '__MACOSX')) {
                    continue;
                }

                $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExts, true)) {
                    continue;
                }

                $rawName = pathinfo($basename, PATHINFO_FILENAME);
                $cleanName = preg_replace('/[^0-9a-zA-Z]/', '', $rawName);
                $numbersOnly = preg_replace('/[^0-9]/', '', $rawName);

                $student = null;
                $matchedVia = '';

                if ($rombelStudents->isNotEmpty()) {
                    if (preg_match('/^0*(\d{1,2})(?:[_\s\.\-].*)?$/', $rawName, $m)) {
                        $noUrut = (int) $m[1];
                        if ($noUrut >= 1 && $noUrut <= $rombelStudents->count()) {
                            $student = $rombelStudents->get($noUrut - 1);
                            $matchedVia = "No. Urut {$noUrut}";
                        }
                    }

                    if (!$student) {
                        foreach ($rombelStudents as $s) {
                            $sNisn = (string) ($s->nisn ?? '');
                            $sNipd = (string) ($s->nipd ?? '');
                            $sNik  = (string) ($s->nik ?? '');

                            if ($sNisn !== '' && ($rawName === $sNisn || $cleanName === $sNisn || (strlen($numbersOnly) >= 5 && $numbersOnly === $sNisn))) {
                                $student = $s;
                                $matchedVia = "NISN";
                                break;
                            }
                            if ($sNipd !== '' && ($rawName === $sNipd || $cleanName === $sNipd || (strlen($numbersOnly) >= 5 && $numbersOnly === $sNipd))) {
                                $student = $s;
                                $matchedVia = "NIPD";
                                break;
                            }
                            if ($sNik !== '' && ($rawName === $sNik || $cleanName === $sNik)) {
                                $student = $s;
                                $matchedVia = "NIK";
                                break;
                            }
                        }
                    }

                    if (!$student) {
                        $cleanRaw = strtolower(preg_replace('/[^a-z0-9]/', '', $rawName));
                        if (strlen($cleanRaw) >= 4) {
                            foreach ($rombelStudents as $s) {
                                $cleanNama = strtolower(preg_replace('/[^a-z0-9]/', '', $s->nama ?? ''));
                                if (strlen($cleanNama) >= 4 && (str_contains($cleanRaw, $cleanNama) || str_contains($cleanNama, $cleanRaw))) {
                                    $student = $s;
                                    $matchedVia = "Nama";
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!$student) {
                    $found = DB::table('peserta_didik')
                        ->where(function ($q) use ($rawName, $cleanName, $numbersOnly) {
                            $q->where('nisn', $rawName)
                              ->orWhere('nisn', $cleanName)
                              ->orWhere('nipd', $rawName)
                              ->orWhere('nipd', $cleanName)
                              ->orWhere('peserta_didik_id', $rawName)
                              ->orWhere('peserta_didik_id', $cleanName);
                            if (strlen($numbersOnly) >= 5) {
                                $q->orWhere('nisn', $numbersOnly)
                                  ->orWhere('nipd', $numbersOnly)
                                  ->orWhere('peserta_didik_id', $numbersOnly);
                            }
                        })
                        ->first();

                    if ($found) {
                        $student = $found;
                        $matchedVia = "NISN/NIPD Global";
                    }
                }

                if (!$student) {
                    $failedCount++;
                    $results[] = [
                        'filename' => $basename,
                        'status' => 'error',
                        'message' => "Tidak ditemukan peserta didik yang cocok untuk berkas \"{$basename}\"." . ($rombel ? " (Rombel: {$rombel})" : "")
                    ];
                    continue;
                }

                $stream = $zip->getStream($entry);
                if (!$stream) {
                    $failedCount++;
                    $results[] = ['filename' => $basename, 'status' => 'error', 'message' => 'Berkas gambar di dalam ZIP tidak dapat dibaca.'];
                    continue;
                }

                $tmpExt = in_array($ext, ['jpg', 'jpeg'], true) ? 'jpg' : 'png';
                $tmpPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('foto_', true) . '.' . $tmpExt;
                file_put_contents($tmpPath, stream_get_contents($stream));
                fclose($stream);

                try {
                    if (in_array($ext, ['jpg', 'jpeg'], true)) {
                        $srcImg = @imagecreatefromjpeg($tmpPath);
                        if (!$srcImg) {
                            throw new \RuntimeException("Berkas gambar JPG tidak valid atau rusak.");
                        }
                        $pngPath = $tempDir . DIRECTORY_SEPARATOR . uniqid('foto_', true) . '.png';
                        imagepng($srcImg, $pngPath);
                        imagedestroy($srcImg);
                        @unlink($tmpPath);
                        $tmpPath = $pngPath;
                        $basename = pathinfo($basename, PATHINFO_FILENAME) . '.png';
                    }
                    $uploaded = new UploadedFile($tmpPath, $basename, 'image/png', null, true);
                    $meta = PesertaDidikMeta::firstOrNew(['peserta_didik_id' => $student->peserta_didik_id]);
                    if ($meta->foto_path) {
                        $optimizer->deleteFile($meta->foto_path);
                    }

                    $prefix = 'foto_' . ($student->nisn ?: preg_replace('/[^a-zA-Z0-9_-]/', '', $student->peserta_didik_id));
                    $optResult = $optimizer->optimizeAndSavePng(
                        $uploaded,
                        ImageOptimizerService::ASSET_DIR_FOTO_PESERTA_DIDIK,
                        $prefix,
                        1000
                    );

                    $meta->nisn = $student->nisn;
                    $meta->foto_path = $optResult['path'];
                    $meta->foto_size = $optResult['size'];
                    $meta->foto_width = $optResult['width'];
                    $meta->foto_height = $optResult['height'];
                    $meta->save();

                    DB::table('pengguna')->where('peserta_didik_id', $student->peserta_didik_id)->update(['foto_path' => $optResult['path']]);

                    $successCount++;
                    $results[] = [
                        'peserta_didik_id' => $student->peserta_didik_id,
                        'nama' => $student->nama,
                        'nisn' => $student->nisn,
                        'filename' => $basename,
                        'matched_by' => $matchedVia,
                        'status' => 'success',
                        'foto_url' => $meta->foto_url,
                        'foto_size' => $meta->formatted_foto_size,
                        'savings' => $optResult['savings_percent'],
                    ];
                } catch (\Throwable $e) {
                    $failedCount++;
                    $results[] = ['filename' => $basename, 'status' => 'error', 'message' => $e->getMessage()];
                }
            }
        } finally {
            $zip->close();
            File::deleteDirectory($tempDir);
        }

        return [
            'status' => 'success',
            'message' => "Proses ZIP selesai: {$successCount} foto berhasil disimpan, {$failedCount} gagal.",
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'data' => $results,
        ];
    }
}
