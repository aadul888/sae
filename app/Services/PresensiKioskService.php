<?php

namespace App\Services;

use App\Models\KalenderPendidikan;
use App\Models\NotifikasiTransaksi;
use App\Models\PesertaDidikMeta;
use App\Models\PresensiHarian;
use App\Models\PresensiPengaturan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service operasional Terminal Kiosk & Scanner RFID/QR Presensi Sekolah.
 * ponytail: static-scanner; upgrade ke WebSocket push jika frekuensi tap kartu melebihi 50 tap/detik.
 */
class PresensiKioskService
{
    /**
     * Buka Akses Terminal Kiosk dengan Kode Akses PIN atau Tap Kartu RFID Guru/Tendik/Admin.
     */
    public static function unlock(string $inputCode): array
    {
        $pengaturan = PresensiPengaturan::getPengaturan();
        $expectedCode = str_replace(' ', '', strtoupper(trim($pengaturan->kode_akses ?: 'SAE123')));
        $cleanCode = str_replace(' ', '', strtoupper(trim($inputCode)));

        $unlockedBy = null;

        if ($cleanCode === $expectedCode) {
            $unlockedBy = 'Kode Akses PIN Terminal';
        } else {
            $userRfid = User::where('rfid_uid', $cleanCode)->first();
            if ($userRfid && in_array($userRfid->role, ['admin', 'guru', 'tendik'], true)) {
                $roleLabel = match ($userRfid->role) {
                    'admin' => 'Administrator',
                    'guru' => 'Guru',
                    'tendik' => 'Tenaga Kependidikan',
                    default => ucfirst($userRfid->role)
                };
                $unlockedBy = "{$userRfid->nama} ({$roleLabel})";
            }
        }

        if (!$unlockedBy) {
            return [
                'status'  => 'error',
                'code'    => 422,
                'message' => 'Kode akses salah atau kartu RFID belum terdaftar untuk Guru / Tendik. Silakan periksa kembali.',
            ];
        }

        session([
            'kiosk_access_granted' => true,
            'kiosk_login_at'       => now()->toIso8601String(),
            'kiosk_unlocked_by'    => $unlockedBy,
        ]);

        return [
            'status'       => 'success',
            'code'         => 200,
            'message'      => "Akses terminal berhasil dibuka oleh [{$unlockedBy}]. Mengalihkan ke scanner...",
            'redirect_url' => route('presensi.scan'),
            'unlocked_by'  => $unlockedBy,
        ];
    }

    /**
     * Data untuk view scanner station full screen.
     */
    public static function getScanKioskData(bool $isKioskAllowed, bool $isUserAllowed): array
    {
        $isKioskSession = $isKioskAllowed && !$isUserAllowed;
        $pengaturan = PresensiPengaturan::getPengaturan();
        $today = now()->toDateString();
        $statusHari = KalenderPendidikan::getStatusHari($today, 'pd');
        $isHariAktif = $pengaturan->isHariAktif(now());
        $isLibur = ($statusHari['mode'] === 'libur') || ($statusHari['is_libur'] ?? false) || !$isHariAktif;
        $agendaLibur = $statusHari['agenda'];

        $recentScans = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where('ph.tanggal', $today)
            ->select(
                'ph.id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.foto_masuk',
                'ph.foto_pulang',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel',
                'pdm.foto_path'
            )
            ->orderBy('ph.updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($s) {
                $s->foto_url = !empty($s->foto_path) ? asset('storage/' . ltrim($s->foto_path, '/')) : null;
                $s->snapshot_url = !empty($s->foto_masuk) ? asset('storage/' . ltrim($s->foto_masuk, '/')) : null;
                return $s;
            });

        $schoolLat = $pengaturan->getEffectiveLatitude();
        $schoolLon = $pengaturan->getEffectiveLongitude();
        $schoolRadius = $pengaturan->getEffectiveRadius();
        $requireLocation = (bool) $pengaturan->require_location;

        return compact(
            'pengaturan',
            'isLibur',
            'agendaLibur',
            'statusHari',
            'isHariAktif',
            'recentScans',
            'schoolLat',
            'schoolLon',
            'schoolRadius',
            'requireLocation',
            'isKioskSession'
        );
    }

    /**
     * Simpan snapshot foto webcam dari base64 string.
     */
    public static function saveSnapshotImage(string $base64Data, string $identifier, Carbon $now): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                $type = 'jpg';
            }
            $data = base64_decode($data);
            if ($data === false) {
                throw new \Exception('Gagal mendecode base64 image');
            }
        } else {
            throw new \Exception('Data URI tidak valid');
        }

        $folder = 'presensi/foto/' . $now->format('Y-m');
        $fileName = 'snap_' . $identifier . '_' . $now->format('Ymd_His') . '_' . Str::random(4) . '.' . $type;
        $fullPath = $folder . '/' . $fileName;

        Storage::disk('public')->put($fullPath, $data);

        return $fullPath;
    }

    /**
     * Format data siswa untuk respon JSON Kiosk/Scan.
     */
    public static function formatSiswaResponseData($siswa, PresensiHarian $presensi): array
    {
        return [
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'nama' => $siswa->nama,
            'nisn' => $siswa->nisn,
            'rombel' => $siswa->nama_rombel ?? '-',
            'foto_url' => !empty($siswa->foto_path) ? asset('storage/' . ltrim($siswa->foto_path, '/')) : null,
            'jam_masuk' => $presensi->jam_masuk ? substr($presensi->jam_masuk, 0, 5) : null,
            'jam_pulang' => $presensi->jam_pulang ? substr($presensi->jam_pulang, 0, 5) : null,
            'status' => $presensi->status,
            'status_label' => $presensi->status_label,
            'menit_terlambat' => $presensi->menit_terlambat,
            'foto_masuk_url' => $presensi->foto_masuk_url,
            'foto_pulang_url' => $presensi->foto_pulang_url,
            'latitude' => $presensi->latitude ? (float) $presensi->latitude : null,
            'longitude' => $presensi->longitude ? (float) $presensi->longitude : null,
            'jarak_meter' => $presensi->jarak_meter !== null ? (float) $presensi->jarak_meter : null,
        ];
    }

    /**
     * Proses Pemindaian Tap RFID / QR Code / Barcode di Terminal Kiosk.
     */
    public static function processScan(Request $request): array
    {
        $rawIdentifier = trim((string) $request->input('identifier'));
        $mode = $request->input('mode', 'auto');
        $snapshotBase64 = $request->input('snapshot');

        $now = Carbon::now();
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $pengaturan = PresensiPengaturan::getPengaturan();

        $statusHari = KalenderPendidikan::getStatusHari($today, 'pd');
        $isHariAktif = $pengaturan->isHariAktif($now);

        if (($statusHari['mode'] === 'libur') || ($statusHari['is_libur'] ?? false) || !$isHariAktif) {
            $agenda = $statusHari['agenda'];
            $alasan = $statusHari['nama_agenda'] ?: ($agenda ? $agenda->nama_kegiatan : 'Hari Libur / Non-Aktif Belajar');
            return [
                'status'      => 'warning',
                'code'        => 422,
                'title'       => 'Hari Libur / Non-Aktif Belajar',
                'message'     => "Hari ini adalah {$alasan}. Presensi dinonaktifkan.",
                'speech_text' => 'Hari libur sekolah.',
            ];
        }

        if ($statusHari['mode'] === 'daring') {
            $agenda = $statusHari['agenda'];
            return [
                'status'      => 'warning',
                'code'        => 422,
                'title'       => 'Pembelajaran Daring (PJJ)',
                'message'     => 'Hari ini dijadwalkan Pembelajaran Daring (PJJ): ' . ($agenda ? $agenda->nama_kegiatan : 'Belajar di Rumah') . '. Terminal scanner gerbang dinonaktifkan.',
                'speech_text' => 'Pembelajaran daring.',
            ];
        }

        $userLat = $request->filled('latitude') ? (float) $request->input('latitude') : null;
        $userLon = $request->filled('longitude') ? (float) $request->input('longitude') : null;
        $locCheck = $pengaturan->checkLocationRadius($userLat, $userLon);

        if ($pengaturan->require_location && !$locCheck['allowed']) {
            return [
                'status'      => 'error',
                'code'        => 422,
                'title'       => 'Di Luar Radius Sekolah',
                'message'     => $locCheck['message'],
                'speech_text' => 'Di luar radius sekolah.',
                'data'        => [
                    'distance_meter' => $locCheck['distance_meter'],
                    'radius_meter'   => $locCheck['radius_meter'],
                ],
            ];
        }

        if (!$pengaturan->isHariAktif($now)) {
            return [
                'status'      => 'warning',
                'code'        => 422,
                'title'       => 'Bukan Hari Belajar',
                'message'     => 'Hari ini bukan merupakan hari aktif belajar di jadwal sekolah.',
                'speech_text' => 'Bukan hari aktif sekolah.',
            ];
        }

        $cleanId = $rawIdentifier;
        if (preg_match('/\/v\/([0-9a-zA-Z]+)/', $rawIdentifier, $match)) {
            $cleanId = $match[1];
        } elseif (str_starts_with($rawIdentifier, 'SAE-QR:')) {
            $parts = explode(':', $rawIdentifier);
            if (count($parts) >= 2) {
                $cleanId = $parts[1];
            }
        }

        $metaSiswa = PesertaDidikMeta::where('rfid_uid', $cleanId)->first();
        $pdId = $metaSiswa ? $metaSiswa->peserta_didik_id : null;

        $siswaQuery = DB::table('peserta_didik as pd')
            ->leftJoin('rombongan_belajar as rb', 'pd.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->select(
                'pd.peserta_didik_id',
                'pd.nama',
                'pd.nisn',
                'pd.nipd',
                'pd.rombongan_belajar_id',
                'rb.nama as nama_rombel',
                'pdm.foto_path'
            );

        if ($pdId) {
            $siswa = $siswaQuery->where('pd.peserta_didik_id', $pdId)->first();
        } else {
            $siswa = $siswaQuery->where(function ($q) use ($cleanId) {
                $q->where('pd.nisn', $cleanId)
                    ->orWhere('pd.peserta_didik_id', $cleanId)
                    ->orWhere('pd.nipd', $cleanId);
            })->first();
        }

        if (!$siswa) {
            return [
                'status'      => 'error',
                'code'        => 404,
                'title'       => 'Peserta Didik Tidak Ditemukan',
                'message'     => "Kartu RFID atau QR [{$cleanId}] belum terdaftar dalam sistem database sekolah.",
                'speech_text' => 'Tidak terdaftar.',
            ];
        }

        $rb = DB::table('rombongan_belajar')->where('rombongan_belajar_id', $siswa->rombongan_belajar_id)->first();
        if ($rb && !$pengaturan->isJurusanAktif($rb->jurusan_id)) {
            return [
                'status'      => 'warning',
                'code'        => 422,
                'title'       => 'Jurusan Tidak Aktif Presensi',
                'message'     => 'Kompetensi Keahlian ' . ($rb->jurusan_id_str ?: 'peserta didik') . ' saat ini tidak dijadwalkan untuk presensi gerbang (misal: sedang PKL/Prakerin).',
                'speech_text' => 'Jurusan tidak aktif.',
            ];
        }

        $metodeScan = 'manual';
        if ($metaSiswa && $metaSiswa->rfid_uid === $cleanId) {
            $metodeScan = 'rfid';
        } elseif (strlen($cleanId) >= 10 && ctype_digit($cleanId)) {
            $metodeScan = 'qr_code';
        }

        $presensi = PresensiHarian::firstOrNew([
            'peserta_didik_id' => $siswa->peserta_didik_id,
            'tanggal'          => $today,
        ]);

        $presensi->nisn = $siswa->nisn;
        $presensi->rombongan_belajar_id = $siswa->rombongan_belajar_id;

        $hasSnapshot = !empty($snapshotBase64) && str_starts_with($snapshotBase64, 'data:image/');
        $actionType = 'masuk';
        $speechGreeting = '';
        $messageDetail = '';

        $jamPulangMulai = $pengaturan->jam_pulang_mulai ?: '14:30:00';
        $jamMasukSelesai = $pengaturan->jam_masuk_selesai ?: '07:15:00';

        if ($mode === 'pulang' || (!empty($presensi->jam_masuk) && $currentTime >= $jamPulangMulai)) {
            $actionType = 'pulang';

            if (!empty($presensi->jam_pulang)) {
                return [
                    'status'       => 'info',
                    'code'         => 200,
                    'is_duplicate' => true,
                    'action'       => 'pulang',
                    'title'        => 'Sudah Presensi Pulang',
                    'message'      => "{$siswa->nama} sudah tercatat presensi pulang pada pukul {$presensi->jam_pulang} WIB.",
                    'speech_text'  => "{$siswa->nama}, sudah pulang.",
                    'data'         => self::formatSiswaResponseData($siswa, $presensi),
                ];
            }

            $presensi->jam_pulang = $currentTime;
            $presensi->metode_pulang = $metodeScan;

            if ($hasSnapshot) {
                try {
                    $presensi->foto_pulang = self::saveSnapshotImage($snapshotBase64, $siswa->nisn ?: $siswa->peserta_didik_id, $now);
                } catch (\Throwable $e) {}
            }

            if ($currentTime < $jamPulangMulai) {
                $presensi->status_ketepatan_pulang = 'pulang_cepat';
                $messageDetail = "Presensi pulang berhasil (Pulang Cepat pada {$currentTime} WIB).";
                $speechGreeting = "Sampai jumpa {$siswa->nama}.";
            } else {
                $presensi->status_ketepatan_pulang = 'tepat_waktu';
                $messageDetail = "Presensi pulang tepat waktu pada {$currentTime} WIB.";
                $speechGreeting = "Sampai jumpa {$siswa->nama}.";
            }

            if ($userLat !== null && $userLon !== null) {
                $presensi->latitude = $userLat;
                $presensi->longitude = $userLon;
                $presensi->jarak_meter = $locCheck['distance_meter'];
            }

            $presensi->save();
        } else {
            $actionType = 'masuk';

            if (!empty($presensi->jam_masuk)) {
                return [
                    'status'       => 'info',
                    'code'         => 200,
                    'is_duplicate' => true,
                    'action'       => 'masuk',
                    'title'        => 'Sudah Presensi Masuk',
                    'message'      => "{$siswa->nama} sudah tercatat presensi masuk pada pukul {$presensi->jam_masuk} WIB.",
                    'speech_text'  => "{$siswa->nama}, sudah masuk.",
                    'data'         => self::formatSiswaResponseData($siswa, $presensi),
                ];
            }

            $presensi->jam_masuk = $currentTime;
            $presensi->metode_masuk = $metodeScan;

            if ($hasSnapshot) {
                try {
                    $presensi->foto_masuk = self::saveSnapshotImage($snapshotBase64, $siswa->nisn ?: $siswa->peserta_didik_id, $now);
                } catch (\Throwable $e) {}
            }

            $timeMasukTarget = Carbon::parse($today . ' ' . $jamMasukSelesai);
            $timeNow = Carbon::parse($today . ' ' . $currentTime);

            if ($timeNow->gt($timeMasukTarget)) {
                $diffMinutes = $timeMasukTarget->diffInMinutes($timeNow);
                if ($diffMinutes > $pengaturan->toleransi_terlambat_menit) {
                    $presensi->status = 'T';
                    $presensi->status_ketepatan_masuk = 'terlambat';
                    $presensi->menit_terlambat = $diffMinutes;
                    $messageDetail = "Presensi masuk tercatat: Terlambat {$diffMinutes} menit ({$currentTime} WIB).";
                    $speechGreeting = "{$siswa->nama}, terlambat {$diffMinutes} menit.";
                } else {
                    $presensi->status = 'H';
                    $presensi->status_ketepatan_masuk = 'tepat_waktu';
                    $presensi->menit_terlambat = 0;
                    $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                    $speechGreeting = "Selamat pagi {$siswa->nama}.";
                }
            } else {
                $presensi->status = 'H';
                $presensi->status_ketepatan_masuk = 'tepat_waktu';
                $presensi->menit_terlambat = 0;
                $messageDetail = "Presensi masuk tepat waktu ({$currentTime} WIB).";
                $speechGreeting = "Selamat pagi {$siswa->nama}.";
            }

            if ($userLat !== null && $userLon !== null) {
                $presensi->latitude = $userLat;
                $presensi->longitude = $userLon;
                $presensi->jarak_meter = $locCheck['distance_meter'];
            }

            $presensi->save();
        }

        try {
            $jamStr = substr($actionType === 'pulang' ? $presensi->jam_pulang : $presensi->jam_masuk, 0, 5);
            $notifTipe = $actionType === 'pulang' ? 'info' : ($presensi->status === 'T' ? 'warning' : 'success');
            $notifIcon = $actionType === 'pulang' ? 'fa-solid fa-door-open' : ($presensi->status === 'T' ? 'fa-solid fa-clock-rotate-left' : 'fa-solid fa-calendar-check');

            $jamShort = substr($jamStr, 0, 5);
            $isPulang = ($actionType === 'pulang');

            $notifJudul = $isPulang ? 'Presensi Pulang' : ($presensi->status === 'T' ? 'Presensi: Terlambat' : 'Presensi: Hadir');
            $pesanNotif = $isPulang ? "Kehadiran pulang tercatat ({$jamShort} WIB)." : ($presensi->status === 'T' ? "Kehadiran tercatat ({$jamShort} WIB, terlambat {$presensi->menit_terlambat}m)." : "Kehadiran tercatat ({$jamShort} WIB).");

            $judulWali = $isPulang ? "Pulang: {$siswa->nama}" : "Masuk: {$siswa->nama}";
            $pesanWali = "{$siswa->nama} tercatat " . ($isPulang ? 'pulang' : 'masuk') . " ({$jamShort} WIB).";

            $judulOrtu = $isPulang ? "Presensi Pulang: {$siswa->nama}" : "Presensi Masuk: {$siswa->nama}";
            $pesanOrtu = "{$siswa->nama} tercatat " . ($isPulang ? 'pulang sekolah' : ($presensi->status === 'T' ? 'masuk sekolah (Terlambat)' : 'masuk sekolah tepat waktu')) . " ({$jamShort} WIB).";

            NotifikasiTransaksi::kirimNotifikasiPresensi(
                $siswa->peserta_didik_id,
                $siswa->rombongan_belajar_id ?? '',
                $notifJudul,
                $pesanNotif,
                $judulWali,
                $pesanWali,
                $notifTipe,
                $notifIcon,
                route('dashboard.peserta-didik.presensi.index'),
                route('dashboard.wali-kelas.presensi.index'),
                $judulOrtu,
                $pesanOrtu
            );
        } catch (\Throwable $th) {
            Log::warning('Gagal membuat notifikasi transaksi presensi: ' . $th->getMessage());
        }

        $formattedData = self::formatSiswaResponseData($siswa, $presensi);

        RealtimeService::trigger('presensi.scanned', [
            'nama'            => $siswa->nama,
            'nisn'            => $siswa->nisn,
            'rombel'          => $siswa->nama_rombel ?? '-',
            'foto_url'        => $formattedData['foto_url'] ?? null,
            'jam'             => substr($actionType === 'pulang' ? $presensi->jam_pulang : $presensi->jam_masuk, 0, 5),
            'status'          => $presensi->status,
            'status_label'    => $formattedData['status_label'] ?? 'Hadir',
            'action'          => $actionType,
            'menit_terlambat' => $presensi->menit_terlambat ?? 0,
        ]);

        return [
            'status'      => 'success',
            'code'        => 200,
            'action'      => $actionType,
            'title'       => 'Presensi Berhasil',
            'message'     => $messageDetail,
            'speech_text' => $speechGreeting,
            'data'        => $formattedData,
        ];
    }

    /**
     * 6 Data Presensi Terkini untuk Live Monitor Kiosk.
     */
    public static function getLiveLog(Request $request): array
    {
        PresensiHarian::autoCloseUncheckedOut();
        $today = now()->toDateString();
        $recent = DB::table('presensi_harian as ph')
            ->join('peserta_didik as pd', 'ph.peserta_didik_id', '=', 'pd.peserta_didik_id')
            ->leftJoin('rombongan_belajar as rb', 'ph.rombongan_belajar_id', '=', 'rb.rombongan_belajar_id')
            ->leftJoin('peserta_didik_meta as pdm', 'pd.peserta_didik_id', '=', 'pdm.peserta_didik_id')
            ->where('ph.tanggal', $today)
            ->select(
                'ph.id',
                'ph.status',
                'ph.jam_masuk',
                'ph.jam_pulang',
                'ph.menit_terlambat',
                'ph.foto_masuk',
                'ph.foto_pulang',
                'pd.nama',
                'pd.nisn',
                'rb.nama as rombel',
                'pdm.foto_path'
            )
            ->orderBy('ph.updated_at', 'desc')
            ->limit(6)
            ->get()
            ->map(function ($s) {
                $s->foto_url = !empty($s->foto_path) ? asset('storage/' . ltrim($s->foto_path, '/')) : null;
                $s->snapshot_url = !empty($s->foto_masuk) ? asset('storage/' . ltrim($s->foto_masuk, '/')) : null;
                $s->status_label = PresensiHarian::STATUS_LABELS[$s->status] ?? $s->status;
                return $s;
            });

        $totalSiswa = DB::table('peserta_didik')->count();
        $countHadir = PresensiHarian::where('tanggal', $today)->whereIn('status', ['H', 'T'])->count();

        return [
            'status' => 'success',
            'recent' => $recent,
            'stats'  => [
                'total_siswa' => $totalSiswa,
                'hadir'       => $countHadir,
                'persen'      => $totalSiswa > 0 ? round(($countHadir / $totalSiswa) * 100, 1) : 0,
            ],
        ];
    }
}
