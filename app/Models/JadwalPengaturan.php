<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class JadwalPengaturan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_pengaturan';

    protected $fillable = [
        'jam_mulai_kbm',
        'durasi_per_jp',
        'total_slot_jp',
        'slot_harian',
        'hari_aktif',
        'istirahat',
        'upacara',
        'pembiasaan',
    ];

    protected $casts = [
        'durasi_per_jp' => 'integer',
        'total_slot_jp' => 'integer',
        'slot_harian' => 'array',
        'hari_aktif' => 'array',
        'istirahat' => 'array',
        'upacara' => 'array',
        'pembiasaan' => 'array',
    ];

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

        return $setting;
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

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $jamMulai);

        for ($ke = 1; $ke <= $totalSlots; $ke++) {
            $isBreak = isset($istirahatMap[$ke]);
            $slotDurasi = $isBreak ? $istirahatMap[$ke]['durasi'] : $durasi;

            $startStr = $current->format('H:i');
            $end = $current->copy()->addMinutes($slotDurasi);
            $endStr = $end->format('H:i');

            $slots[$ke] = [
                'ke'             => $ke,
                'mulai'          => $startStr,
                'selesai'        => $endStr,
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
    public static function calculateJamSelesai(int $totalJp, ?string $jamMulai = null, ?int $durasiPerJp = null): string
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

        $current = Carbon::createFromFormat('H:i', $start);
        for ($k = 1; $k <= $totalJp; $k++) {
            $slotDur = $istirahatMap[$k] ?? $dur;
            $current->addMinutes($slotDur);
        }

        return $current->format('H:i');
    }

    /**
     * Sinkronkan kegiatan rutin sekolah (Upacara Bendera, Pembiasaan & Istirahat) ke seluruh kelas reguler
     */
    public static function syncRoutineActivities(): void
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
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'UPACARA')->where('hari', '!=', $uHari)->delete();

            foreach ($regRombels as $r) {
                \App\Models\JadwalKbm::updateOrCreate(
                    [
                        'rombongan_belajar_id' => $r->rombongan_belajar_id,
                        'mata_pelajaran_id'    => 'UPACARA',
                        'hari'                 => $uHari,
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
                        'is_active'            => true,
                        'keterangan'           => 'Kegiatan Rutin Sekolah (Upacara Bendera)',
                    ]
                );
            }
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'UPACARA')->delete();
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
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'PEMBIASAAN')->where('hari', '!=', $pHari)->delete();

            foreach ($regRombels as $r) {
                \App\Models\JadwalKbm::updateOrCreate(
                    [
                        'rombongan_belajar_id' => $r->rombongan_belajar_id,
                        'mata_pelajaran_id'    => 'PEMBIASAAN',
                        'hari'                 => $pHari,
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
                        'is_active'            => true,
                        'keterangan'           => 'Kegiatan Rutin Sekolah (Pembiasaan)',
                    ]
                );
            }
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'PEMBIASAAN')->delete();
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
                // Jika jam_ke melebihi total JP hari tersebut (misal Jumat hanya 5 JP sedangkan istirahat diatur jam ke-6), lewati
                if ($bJamKe > $maxSlotsForDay) {
                    \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')->where('hari', $h)->delete();
                    continue;
                }

                $daySlots = self::getSlots($h);
                $bSlot = $daySlots[$bJamKe] ?? ['mulai' => '10:15', 'selesai' => '10:45'];

                foreach ($regRombels as $r) {
                    // Cek apakah rombel ini memiliki pelajaran di hari ini
                    $hasAnyLessonsOnDay = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                        ->where('hari', $h)
                        ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                        ->exists();

                    if ($hasAnyLessonsOnDay) {
                        $hasAfternoon = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                            ->where('hari', $h)
                            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                            ->where('jam_ke_mulai', '>', $bJamKe)
                            ->exists();

                        $morningReachedBreak = \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                            ->where('hari', $h)
                            ->whereNotIn('mata_pelajaran_id', ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'])
                            ->where('jam_ke_selesai', '>=', $bJamKe - 1)
                            ->exists();

                        // Jika KBM hari ini selesai sebelum batas istirahat dan tidak ada KBM siang, jangan pasang istirahat
                        if (!$hasAfternoon && !$morningReachedBreak) {
                            \App\Models\JadwalKbm::where('rombongan_belajar_id', $r->rombongan_belajar_id)
                                ->where('mata_pelajaran_id', 'ISTIRAHAT')
                                ->where('hari', $h)
                                ->delete();
                            continue;
                        }
                    }

                    \App\Models\JadwalKbm::updateOrCreate(
                        [
                            'rombongan_belajar_id' => $r->rombongan_belajar_id,
                            'mata_pelajaran_id'    => 'ISTIRAHAT',
                            'hari'                 => $h,
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
                            'is_active'            => true,
                            'keterangan'           => 'Waktu Istirahat Sekolah',
                        ]
                    );
                }
            }

            // Hapus dari hari yang tidak aktif
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')
                ->whereNotIn('hari', $hariAktif)
                ->delete();
        } else {
            \App\Models\JadwalKbm::where('mata_pelajaran_id', 'ISTIRAHAT')->delete();
        }
    }
}
