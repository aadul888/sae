<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JadwalPengaturan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pengaturan';

    protected $fillable = [
        'status_jadwal',
        'mode_pemberlakuan',
        'is_diberlakukan',
        'diberlakukan_pada',
        'diberlakukan_oleh',
        'catatan_pemberlakuan',
        'jam_mulai_kbm',
        'durasi_per_jp',
        'total_slot_jp',
        'skema_hari',
        'max_jp_per_sesi',
        'slot_harian',
        'slot_tingkat_harian',
        'jp_tingkat',
        'hari_aktif',
        'istirahat',
        'upacara',
        'pembiasaan',
    ];

    protected $casts = [
        'is_diberlakukan' => 'boolean',
        'diberlakukan_pada' => 'datetime',
        'durasi_per_jp' => 'integer',
        'total_slot_jp' => 'integer',
        'max_jp_per_sesi' => 'integer',
        'slot_harian' => 'array',
        'slot_tingkat_harian' => 'array',
        'jp_tingkat' => 'array',
        'hari_aktif' => 'array',
        'istirahat' => 'array',
        'upacara' => 'array',
        'pembiasaan' => 'array',
    ];

    /**
     * Cek apakah Jadwal KBM saat ini resmi diberlakukan ke Guru & Siswa.
     * Jika $mode diberikan ('otomatis' atau 'manual'), cek apakah mode tersebut yang aktif.
     */
    public static function isDiberlakukan(?string $mode = null): bool
    {
        $setting = self::getSettings();
        if ($setting->status_jadwal === 'draft' || empty($setting->is_diberlakukan)) {
            return false;
        }

        $activeMode = $setting->mode_pemberlakuan ?: 'otomatis';
        if ($activeMode === 'draft') {
            return false;
        }

        if ($mode !== null) {
            return $activeMode === $mode;
        }

        return true;
    }

    /**
     * Ambil mode jadwal yang saat ini aktif diberlakukan ('otomatis', 'manual', atau null jika draft)
     */
    public static function getModeAktif(): ?string
    {
        $setting = self::getSettings();
        if ($setting->status_jadwal === 'draft' || empty($setting->is_diberlakukan)) {
            return null;
        }

        $mode = $setting->mode_pemberlakuan ?: 'otomatis';
        return ($mode === 'draft') ? null : $mode;
    }

    /**
     * Ambil mode pemberlakuan ('otomatis', 'manual', atau 'draft')
     */
    public static function getModePemberlakuan(): string
    {
        $setting = self::getSettings();
        if ($setting->status_jadwal === 'draft' || empty($setting->is_diberlakukan)) {
            return 'draft';
        }
        return $setting->mode_pemberlakuan ?: 'otomatis';
    }

    /**
     * Ambil status pemberlakuan jadwal (aktif / draft)
     */
    public static function getStatusJadwal(): string
    {
        $setting = self::getSettings();
        return $setting->status_jadwal ?: ($setting->is_diberlakukan ? 'aktif' : 'draft');
    }

    /**
     * Ambil instance konfigurasi jadwal (Singleton row ID: 1)
     */
    public static function getSettings(): self
    {
        $setting = self::find(1);
        if (!$setting) {
            $setting = self::create([
                'id' => 1,
                'jam_mulai_kbm' => '07:15:00',
                'durasi_per_jp' => 45,
                'total_slot_jp' => 10,
                'slot_harian' => [
                    'Senin'  => ['total_jp' => 10],
                    'Selasa' => ['total_jp' => 10],
                    'Rabu'   => ['total_jp' => 10],
                    'Kamis'  => ['total_jp' => 10],
                    'Jumat'  => ['total_jp' => 5],
                    'Sabtu'  => ['total_jp' => 5],
                ],
                'jp_tingkat' => [
                    '10' => 50,
                    '11' => 48,
                    '12' => 46,
                ],
                'hari_aktif' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'],
                'istirahat' => [
                    ['jam_ke' => 5, 'durasi_menit' => 30, 'nama' => 'Istirahat'],
                ],
                'upacara' => [
                    'aktif' => true,
                    'hari' => 'Senin',
                    'jam_ke' => 1,
                    'durasi_menit' => 45,
                    'nama' => 'Upacara Bendera',
                ],
                'pembiasaan' => [
                    'aktif' => true,
                    'hari' => 'Jumat',
                    'jam_ke' => 1,
                    'durasi_menit' => 40,
                    'nama' => 'Pembiasaan',
                ],
            ]);
        }

        // Inisialisasi slot_harian jika masih null
        if (empty($setting->slot_harian)) {
            $baseJp = (int) ($setting->total_slot_jp ?: 10);
            $setting->slot_harian = [
                'Senin'  => ['total_jp' => $baseJp],
                'Selasa' => ['total_jp' => $baseJp],
                'Rabu'   => ['total_jp' => $baseJp],
                'Kamis'  => ['total_jp' => $baseJp],
                'Jumat'  => ['total_jp' => min(5, $baseJp)],
                'Sabtu'  => ['total_jp' => min(5, $baseJp)],
            ];
            $setting->save();
        }

        // Inisialisasi jp_tingkat standar SMK jika masih null
        if (empty($setting->jp_tingkat)) {
            $setting->jp_tingkat = [
                '10' => 50,
                '11' => 48,
                '12' => 46,
            ];
            $setting->save();
        }

        if (empty($setting->max_jp_per_sesi)) {
            $setting->max_jp_per_sesi = 3;
            $setting->save();
        }

        return $setting;
    }

    /**
     * Ambil pemetaan target JP per tingkat (SMK: X=50, XI=48, XII=46)
     */
    public static function getJpTingkat(): array
    {
        $setting = self::getSettings();
        $defaults = [
            '10' => 50,
            '11' => 48,
            '12' => 46,
        ];
        if (!empty($setting->jp_tingkat) && is_array($setting->jp_tingkat)) {
            foreach ($setting->jp_tingkat as $k => $v) {
                $defaults[(string) $k] = (int) $v;
            }
        }
        return $defaults;
    }

    /**
     * Dapatkan default distribusi slot harian per tingkat berdasarkan skema hari
     */
    public static function getDefaultTingkatDailySlots(string $skema = '5_hari'): array
    {
        if ($skema === '6_hari') {
            return [
                '10' => ['Senin' => 11, 'Selasa' => 10, 'Rabu' => 10, 'Kamis' => 10, 'Jumat' => 6, 'Sabtu' => 10],
                '11' => ['Senin' => 11, 'Selasa' => 10, 'Rabu' => 9,  'Kamis' => 9,  'Jumat' => 6, 'Sabtu' => 10],
                '12' => ['Senin' => 10, 'Selasa' => 9,  'Rabu' => 9,  'Kamis' => 9,  'Jumat' => 6, 'Sabtu' => 10],
            ];
        }

        // Skema 5 Hari Sekolah (SMK: X=50 JP, XI=48 JP, XII=46 JP)
        return [
            '10' => ['Senin' => 13, 'Selasa' => 12, 'Rabu' => 12, 'Kamis' => 12, 'Jumat' => 7, 'Sabtu' => 0],
            '11' => ['Senin' => 13, 'Selasa' => 12, 'Rabu' => 11, 'Kamis' => 11, 'Jumat' => 7, 'Sabtu' => 0],
            '12' => ['Senin' => 12, 'Selasa' => 11, 'Rabu' => 11, 'Kamis' => 11, 'Jumat' => 7, 'Sabtu' => 0],
        ];
    }

    /**
     * Ambil pemetaan alokasi slot harian per tingkat (gabungan default + kustom setting)
     */
    public static function getTingkatDailySlotCounts(?string $skema = null): array
    {
        $setting = self::getSettings();
        $activeSkema = $skema ?: ($setting->skema_hari ?? '5_hari');
        $defaults = self::getDefaultTingkatDailySlots($activeSkema);

        if (!empty($setting->slot_tingkat_harian) && is_array($setting->slot_tingkat_harian)) {
            foreach (['10', '11', '12'] as $t) {
                if (isset($setting->slot_tingkat_harian[$t]) && is_array($setting->slot_tingkat_harian[$t])) {
                    foreach ($setting->slot_tingkat_harian[$t] as $h => $val) {
                        $valInt = ($val === '' || $val === null) ? 0 : max(0, min(16, (int) $val));
                        $defaults[$t][$h] = $valInt;
                    }
                }
            }
        }

        return $defaults;
    }

    /**
     * Dapatkan preset distribusi slot harian berdasarkan skema hari (5 hari atau 6 hari)
     */
    public static function getPresetSlotHarian(string $skema = '5_hari'): array
    {
        if ($skema === '6_hari') {
            return [
                'Senin'  => ['total_jp' => 11],
                'Selasa' => ['total_jp' => 10],
                'Rabu'   => ['total_jp' => 10],
                'Kamis'  => ['total_jp' => 10],
                'Jumat'  => ['total_jp' => 6],
                'Sabtu'  => ['total_jp' => 10],
            ];
        }

        // 5 Hari (Full Day): Senin 13, Sel-Kam 12, Jumat 7 (1 Pembiasaan + 6 KBM), Sabtu 0
        return [
            'Senin'  => ['total_jp' => 13],
            'Selasa' => ['total_jp' => 12],
            'Rabu'   => ['total_jp' => 12],
            'Kamis'  => ['total_jp' => 12],
            'Jumat'  => ['total_jp' => 7],
            'Sabtu'  => ['total_jp' => 0],
        ];
    }

    /**
     * Dapatkan daftar hari aktif berdasarkan skema
     */
    public static function getPresetHariAktif(string $skema = '5_hari'): array
    {
        if ($skema === '6_hari') {
            return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        }
        return ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    }

    /**
     * Ambil pemetaan jumlah slot JP aktif per hari
     */
    public static function getDailySlotCounts(): array
    {
        $setting = self::getSettings();
        $baseJp = max(0, min(16, (int) $setting->total_slot_jp));
        $defaults = [
            'Senin'  => $baseJp,
            'Selasa' => $baseJp,
            'Rabu'   => $baseJp,
            'Kamis'  => $baseJp,
            'Jumat'  => min(6, max(0, $baseJp - 4)), // Standar Jumat pulang sebelum/saat Dhuhur
            'Sabtu'  => min(6, max(0, $baseJp - 4)),
        ];

        if (is_array($setting->slot_harian)) {
            foreach ($setting->slot_harian as $h => $cfg) {
                if (isset($cfg['total_jp'])) {
                    $raw = $cfg['total_jp'];
                    $defaults[$h] = ($raw === '' || $raw === null) ? 0 : max(0, min(16, (int) $raw));
                }
            }
        }

        return $defaults;
    }

    /**
     * Menghitung seluruh slot jam pelajaran beserta jam mulai dan selesainya (Bisa spesifik per hari)
     */
    public static function getSlots(?string $hari = null): array
    {
        $setting = self::getSettings();
        
        if ($hari) {
            $dailyCounts = self::getDailySlotCounts();
            $totalSlots = $dailyCounts[$hari] ?? (int) $setting->total_slot_jp;
        } else {
            $totalSlots = (int) $setting->total_slot_jp;
        }

        // Pastikan tidak ada jam jadwal KBM aktif di database (misal kelas XII / PKL) yang terpotong
        try {
            $queryMax = DB::table('jadwal_kbm')->where('is_active', true);
            if ($hari) {
                $queryMax->where('hari', $hari);
            }
            $maxDbSlot = (int) $queryMax->max('jam_ke_selesai');
            if ($maxDbSlot > $totalSlots) {
                $totalSlots = min(16, $maxDbSlot);
            }
        } catch (\Throwable $e) {
            // Abaikan jika query belum dapat dieksekusi
        }

        $totalSlots = max(0, min(16, (int) $totalSlots));
        if ($totalSlots <= 0) {
            return [];
        }

        $durasi = max(20, min(90, (int) $setting->durasi_per_jp));
        $jamMulai = $setting->jam_mulai_kbm ? substr($setting->jam_mulai_kbm, 0, 5) : '07:15';

        $istirahatMap = [];
        if (is_array($setting->istirahat)) {
            foreach ($setting->istirahat as $ist) {
                $isAktif = !isset($ist['aktif']) || !empty($ist['aktif']);
                $jamKe = $ist['jam_ke'] ?? $ist['setelah_jp'] ?? null;
                if ($isAktif && $jamKe && isset($ist['durasi_menit'])) {
                    $istirahatMap[(int)$jamKe] = [
                        'durasi' => max(5, min(90, (int) $ist['durasi_menit'])),
                        'nama'   => !empty($ist['nama']) ? $ist['nama'] : 'Istirahat',
                    ];
                }
            }
        }

        // Konfigurasi Rutin Pagi (Upacara & Pembiasaan)
        $upacaraConfig = $setting->upacara ?? [];
        $isUpacaraAktif = !empty($upacaraConfig['aktif']);
        $upacaraHari = $upacaraConfig['hari'] ?? 'Senin';
        $upacaraJamKe = (int) ($upacaraConfig['jam_ke'] ?? 1);
        $upacaraDurasi = max(10, min(90, (int) ($upacaraConfig['durasi_menit'] ?? 45)));

        $pembiasaanConfig = $setting->pembiasaan ?? [];
        $isPembiasaanAktif = !empty($pembiasaanConfig['aktif']);
        $pembiasaanHari = $pembiasaanConfig['hari'] ?? 'Jumat';
        $pembiasaanJamKe = (int) ($pembiasaanConfig['jam_ke'] ?? 1);
        $pembiasaanDurasi = max(10, min(90, (int) ($pembiasaanConfig['durasi_menit'] ?? 40)));

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $jamMulai);

        for ($ke = 1; $ke <= $totalSlots; $ke++) {
            $isBreak = isset($istirahatMap[$ke]);
            $slotDurasi = $durasi;

            if ($hari === $upacaraHari && $isUpacaraAktif && $ke === $upacaraJamKe) {
                $slotDurasi = $upacaraDurasi;
            } elseif ($hari === $pembiasaanHari && $isPembiasaanAktif && $ke === $pembiasaanJamKe) {
                $slotDurasi = $pembiasaanDurasi;
            } elseif ($isBreak && ($hari !== 'Jumat' || $ke !== $istirahatMap[$ke])) {
                $slotDurasi = $istirahatMap[$ke]['durasi'];
            }

            $startStr = $current->format('H:i');
            $end = $current->copy()->addMinutes($slotDurasi);
            $endStr = $end->format('H:i');

            $slots[$ke] = [
                'ke'             => $ke,
                'jam_ke'         => $ke,
                'mulai'          => $startStr,
                'jam_mulai'      => strlen($startStr) === 5 ? $startStr . ':00' : $startStr,
                'selesai'        => $endStr,
                'jam_selesai'    => strlen($endStr) === 5 ? $endStr . ':00' : $endStr,
                'label'          => $isBreak ? "Jam Ke-{$ke} (Istirahat)" : "Jam Ke-{$ke}",
                'is_break'       => $isBreak,
                'break_duration' => $isBreak ? $istirahatMap[$ke]['durasi'] : 0,
                'break_name'     => $isBreak ? $istirahatMap[$ke]['nama'] : '',
            ];

            // Majukan waktu untuk jam pelajaran berikutnya
            $current = $end->copy();
        }

        return $slots;
    }

    /**
     * Hitung jam selesai perkiraan untuk jumlah slot dan hari tertentu
     */
    public static function calculateJamSelesai(int $totalJp, ?string $jamMulai = null, ?int $durasiPerJp = null, ?string $hari = null): string
    {
        if ($totalJp <= 0) {
            return '-';
        }

        $setting = self::getSettings();
        $start = $jamMulai ?: ($setting->jam_mulai_kbm ? substr($setting->jam_mulai_kbm, 0, 5) : '07:15');
        $dur = $durasiPerJp ?: (int)$setting->durasi_per_jp;

        $istirahatMap = [];
        if (is_array($setting->istirahat)) {
            foreach ($setting->istirahat as $ist) {
                $isAktif = !isset($ist['aktif']) || !empty($ist['aktif']);
                $jamKe = $ist['jam_ke'] ?? $ist['setelah_jp'] ?? null;
                if ($isAktif && $jamKe && !empty($ist['durasi_menit'])) {
                    $istirahatMap[(int)$jamKe] = (int)$ist['durasi_menit'];
                }
            }
        }

        $upacaraConfig = $setting->upacara ?? [];
        $isUpacaraAktif = !empty($upacaraConfig['aktif']);
        $upacaraHari = $upacaraConfig['hari'] ?? 'Senin';
        $upacaraJamKe = (int) ($upacaraConfig['jam_ke'] ?? 1);
        $upacaraDurasi = max(10, min(90, (int) ($upacaraConfig['durasi_menit'] ?? 45)));

        $pembiasaanConfig = $setting->pembiasaan ?? [];
        $isPembiasaanAktif = !empty($pembiasaanConfig['aktif']);
        $pembiasaanHari = $pembiasaanConfig['hari'] ?? 'Jumat';
        $pembiasaanJamKe = (int) ($pembiasaanConfig['jam_ke'] ?? 1);
        $pembiasaanDurasi = max(10, min(90, (int) ($pembiasaanConfig['durasi_menit'] ?? 40)));

        $current = Carbon::createFromFormat('H:i', $start);
        for ($k = 1; $k <= $totalJp; $k++) {
            $slotDur = $dur;
            if ($hari === $upacaraHari && $isUpacaraAktif && $k === $upacaraJamKe) {
                $slotDur = $upacaraDurasi;
            } elseif ($hari === $pembiasaanHari && $isPembiasaanAktif && $k === $pembiasaanJamKe) {
                $slotDur = $pembiasaanDurasi;
            } elseif (isset($istirahatMap[$k]) && $hari !== 'Jumat') {
                $slotDur = $istirahatMap[$k];
            }
            $current->addMinutes($slotDur);
        }

        return $current->format('H:i');
    }

    /**
     * Sinkronkan kegiatan rutin sekolah (Upacara Bendera, Pembiasaan & Istirahat) ke seluruh kelas reguler
     */
    public static function syncRoutineActivities(?string $targetSumber = null): void
    {
        $setting = self::getSettings();
        $regRombels = \Illuminate\Support\Facades\DB::table('rombongan_belajar')
            ->where(function ($w) {
                $w->where('jenis_rombel', 1)
                  ->orWhere('jenis_rombel_str', 'Kelas')
                  ->orWhere('jenis_rombel_str', 'LIKE', '%reguler%');
            })
            ->select('rombongan_belajar_id', 'nama', 'semester_id')
            ->get();

        $slots = self::getSlots();
        $dailySlots = self::getDailySlotCounts();
        $sumbers = $targetSumber ? [$targetSumber] : ['otomatis', 'manual'];

        // 1. Sinkronisasi Upacara Bendera Otomatis
        $upacara = $setting->upacara;
        $uHari = $upacara['hari'] ?? 'Senin';
        $maxSlotUpacara = $dailySlots[$uHari] ?? (int)$setting->total_slot_jp;
        if (!empty($upacara['aktif']) && $maxSlotUpacara > 0) {
            $uJamKe = max(1, (int)($upacara['jam_ke'] ?? 1));
            $daySlotsU = self::getSlots($uHari);
            $uSlot = $daySlotsU[$uJamKe] ?? ($slots[$uJamKe] ?? ['mulai' => '07:15', 'selesai' => '08:00']);
            $uNama = !empty($upacara['nama']) ? $upacara['nama'] : 'Upacara Bendera';

            // Bersihkan jika hari upacara berubah
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'UPACARA')
                ->where('hari', '!=', $uHari)
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();

            foreach ($regRombels as $r) {
                foreach ($sumbers as $sumber) {
                    \App\Models\JadwalKbm::updateOrCreate(
                        [
                            'rombongan_belajar_id' => $r->rombongan_belajar_id,
                            'mata_pelajaran_id'    => 'UPACARA',
                            'hari'                 => $uHari,
                            'sumber'               => $sumber,
                        ],
                        [
                            'nama_mata_pelajaran'  => $uNama,
                            'pembelajaran_id'      => 'ROUTINE_UPACARA',
                            'ptk_id'               => null,
                            'hari'                 => $uHari,
                            'jam_ke_mulai'         => $uJamKe,
                            'jam_ke_selesai'       => $uJamKe,
                            'jam_mulai'            => (strlen($uSlot['mulai']) === 5 ? $uSlot['mulai'] . ':00' : $uSlot['mulai']),
                            'jam_selesai'          => (strlen($uSlot['selesai']) === 5 ? $uSlot['selesai'] . ':00' : $uSlot['selesai']),
                            'ruangan'              => 'Lapangan Upacara',
                            'semester_id'          => $r->semester_id ?? null,
                            'sumber'               => $sumber,
                            'is_active'            => true,
                            'keterangan'           => 'Kegiatan Rutin Sekolah (Upacara Bendera)',
                        ]
                    );
                }
            }
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'UPACARA')
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();
        }

        // 2. Sinkronisasi Pembiasaan Rutin (Jumat / Hari Tertentu)
        $pembiasaan = $setting->pembiasaan;
        $pHari = $pembiasaan['hari'] ?? 'Jumat';
        $maxSlotPembiasaan = $dailySlots[$pHari] ?? (int)$setting->total_slot_jp;
        if (!empty($pembiasaan['aktif']) && $maxSlotPembiasaan > 0) {
            $pJamKe = max(1, (int)($pembiasaan['jam_ke'] ?? 1));
            $daySlotsP = self::getSlots($pHari);
            $pSlot = $daySlotsP[$pJamKe] ?? ($slots[$pJamKe] ?? ['mulai' => '07:15', 'selesai' => '08:00']);
            $pNama = !empty($pembiasaan['nama']) ? $pembiasaan['nama'] : 'Pembiasaan';

            // Bersihkan jika hari pembiasaan berubah
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'PEMBIASAAN')
                ->where('hari', '!=', $pHari)
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();

            foreach ($regRombels as $r) {
                foreach ($sumbers as $sumber) {
                    \App\Models\JadwalKbm::updateOrCreate(
                        [
                            'rombongan_belajar_id' => $r->rombongan_belajar_id,
                            'mata_pelajaran_id'    => 'PEMBIASAAN',
                            'hari'                 => $pHari,
                            'sumber'               => $sumber,
                        ],
                        [
                            'nama_mata_pelajaran'  => $pNama,
                            'pembelajaran_id'      => 'ROUTINE_PEMBIASAAN',
                            'ptk_id'               => null,
                            'hari'                 => $pHari,
                            'jam_ke_mulai'         => $pJamKe,
                            'jam_ke_selesai'       => $pJamKe,
                            'jam_mulai'            => (strlen($pSlot['mulai']) === 5 ? $pSlot['mulai'] . ':00' : $pSlot['mulai']),
                            'jam_selesai'          => (strlen($pSlot['selesai']) === 5 ? $pSlot['selesai'] . ':00' : $pSlot['selesai']),
                            'ruangan'              => 'Masjid / Lapangan / Kelas',
                            'semester_id'          => $r->semester_id ?? null,
                            'sumber'               => $sumber,
                            'is_active'            => true,
                            'keterangan'           => 'Kegiatan Rutin Sekolah (Pembiasaan)',
                        ]
                    );
                }
            }
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'PEMBIASAAN')
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();
        }

        // 3. Sinkronisasi Waktu Istirahat Otomatis ke Seluruh Hari KBM Aktif
        $istirahatList = $setting->istirahat;
        $firstBreak = is_array($istirahatList) ? ($istirahatList[0] ?? null) : null;
        $isBreakActive = !empty($firstBreak) && (!isset($firstBreak['aktif']) || !empty($firstBreak['aktif'])) && !empty($firstBreak['jam_ke']);

        if ($isBreakActive) {
            $bJamKe = max(1, (int)$firstBreak['jam_ke']);
            $bNama = !empty($firstBreak['nama']) ? $firstBreak['nama'] : 'Istirahat';
            $hariAktif = $setting->hari_aktif ?: ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
            $dailySlots = self::getDailySlotCounts();

            foreach ($hariAktif as $h) {
                $maxSlotsForDay = $dailySlots[$h] ?? (int)$setting->total_slot_jp;
                if ($bJamKe > $maxSlotsForDay) {
                    \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')
                        ->where('hari', $h)
                        ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                        ->delete();
                    continue;
                }

                $daySlots = self::getSlots($h);
                $bSlot = $daySlots[$bJamKe] ?? ['mulai' => '10:15', 'selesai' => '10:45'];

                foreach ($regRombels as $r) {
                    foreach ($sumbers as $sumber) {
                        $hasAnyLessonsOnDay = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                            ->where('hari', $h)
                            ->where('sumber', $sumber)
                            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                            ->exists();

                        if ($hasAnyLessonsOnDay) {
                            $hasAfternoon = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                                ->where('hari', $h)
                                ->where('sumber', $sumber)
                                ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                                ->where('jam_ke_mulai', '>', $bJamKe)
                                ->exists();

                            $morningReachedBreak = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                                ->where('hari', $h)
                                ->where('sumber', $sumber)
                                ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                                ->where('jam_ke_selesai', '>=', $bJamKe - 1)
                                ->exists();

                            if (!$hasAfternoon && !$morningReachedBreak) {
                                \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                                    ->where('mata_pelajaran_id', 'ISTIRAHAT')
                                    ->where('hari', $h)
                                    ->where('sumber', $sumber)
                                    ->delete();
                                continue;
                            }
                        }

                        \App\Models\JadwalKbm::updateOrCreate(
                            [
                                'rombongan_belajar_id' => $r->rombongan_belajar_id,
                                'mata_pelajaran_id'    => 'ISTIRAHAT',
                                'hari'                 => $h,
                                'sumber'               => $sumber,
                            ],
                            [
                                'nama_mata_pelajaran'  => $bNama,
                                'pembelajaran_id'      => 'ROUTINE_ISTIRAHAT',
                                'ptk_id'               => null,
                                'hari'                 => $h,
                                'jam_ke_mulai'         => $bJamKe,
                                'jam_ke_selesai'       => $bJamKe,
                                'jam_mulai'            => (strlen($bSlot['mulai']) === 5 ? $bSlot['mulai'] . ':00' : $bSlot['mulai']),
                                'jam_selesai'          => (strlen($bSlot['selesai']) === 5 ? $bSlot['selesai'] . ':00' : $bSlot['selesai']),
                                'ruangan'              => 'Kantin / Area Sekolah',
                                'semester_id'          => $r->semester_id ?? null,
                                'sumber'               => $sumber,
                                'is_active'            => true,
                                'keterangan'           => 'Waktu Istirahat Sekolah',
                            ]
                        );
                    }
                }
            }

            // Hapus dari hari yang tidak aktif
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')
                ->whereNotIn('hari', $hariAktif)
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')
                ->when($targetSumber, fn($q) => $q->where('sumber', $targetSumber))
                ->delete();
        }
    }
}
