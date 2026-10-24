<?php

namespace App\Services;

class AutoSchedulerSupport
{
    /**
     * Format struktur baris jadwal KBM untuk batch insert.
     */
    public static function buildScheduleItem(
        array $sess,
        string $rId,
        string $h,
        int $startK,
        int $endK,
        array $slots,
        mixed $rombelMap,
        string $keterangan
    ): array {
        $jamMulai = $slots[$startK]['mulai'] ?? '06:30';
        $jamSelesai = $slots[$endK]['selesai'] ?? '14:45';
        $semesterId = $rombelMap[$rId]->semester_id ?? null;

        return [
            'rombongan_belajar_id' => $rId,
            'pembelajaran_id'      => $sess['pembelajaran_id'],
            'ptk_id'               => $sess['ptk_id'],
            'mata_pelajaran_id'    => $sess['mata_pelajaran_id'],
            'nama_mata_pelajaran'  => $sess['nama_mata_pelajaran'],
            'hari'                 => $h,
            'jam_ke_mulai'         => $startK,
            'jam_ke_selesai'       => $endK,
            'jam_mulai'            => strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai,
            'jam_selesai'          => strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai,
            'ruangan'              => null,
            'semester_id'          => $semesterId,
            'sumber'               => 'otomatis',
            'is_active'            => true,
            'keterangan'           => $keterangan,
            'created_at'           => now(),
            'updated_at'           => now(),
        ];
    }

    /**
     * Periksa ketersediaan jam berhalangan spesifik guru.
     */
    public static function isTeacherUnavailable(?object $gPref, string $h, int $startK, int $endK): bool
    {
        if (!$gPref || empty($gPref->jam_unavailable)) {
            return false;
        }

        foreach ($gPref->jam_unavailable as $un) {
            if (($un['hari'] ?? '') === $h) {
                $unS = (int) ($un['jam_ke_mulai'] ?? 1);
                $unE = (int) ($un['jam_ke_selesai'] ?? $unS);
                if ($startK <= $unE && $endK >= $unS) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Memecah total jam mengajar mingguan (JJM) menjadi sesi blok pertemuan yang teratur dan fleksibel (hingga 9 JP).
     */
    public static function decomposeJjmIntoBlocks(int $jjm, int $maxBlock = 3, ?int $istirahatJamKe = null): array
    {
        if ($jjm <= 0) return [];
        $maxBlock = max(2, min(9, $maxBlock));

        if ($jjm === 18) {
            if ($maxBlock >= 6) return [6, 6, 6];
            return [3, 3, 3, 3, 3, 3];
        }
        if ($jjm === 12) {
            if ($maxBlock >= 6) return [6, 6];
            if ($maxBlock === 2) return [2, 2, 2, 2, 2, 2];
            return [3, 3, 3, 3];
        }
        if ($jjm === 10) {
            if ($maxBlock >= 5) return [5, 5];
            if ($maxBlock === 2) return [2, 2, 2, 2, 2];
            return [3, 3, 2, 2];
        }
        if ($jjm === 9) {
            if ($maxBlock >= 5) return [5, 4];
            if ($maxBlock >= 3) return [3, 3, 3];
            return [3, 2, 2, 2];
        }
        if ($jjm === 8) {
            if ($maxBlock >= 4) return [4, 4];
            if ($maxBlock === 3) return [3, 3, 2];
            return [2, 2, 2, 2];
        }
        if ($jjm === 7) {
            if ($maxBlock >= 4) return [4, 3];
            return [3, 2, 2];
        }
        if ($jjm === 6) {
            if ($maxBlock >= 3) return [3, 3];
            return [2, 2, 2];
        }
        if ($jjm === 5) {
            return [3, 2];
        }
        if ($jjm === 4) {
            return [2, 2];
        }
        if ($jjm <= 3) {
            return [$jjm];
        }

        $blocks = [];
        $remaining = $jjm;
        while ($remaining > 0) {
            $take = min($maxBlock, $remaining);
            if ($take === 1 && count($blocks) > 0) {
                $blocks[count($blocks) - 1] += 1;
                break;
            }
            if (($remaining - $take) === 1 && $take > 2) {
                $take -= 1;
            }
            $blocks[] = $take;
            $remaining -= $take;
        }

        return $blocks;
    }

    /**
     * Gabungkan sesi berdekatan dari rombel, hari, mapel, dan guru yang sama menjadi satu blok solid.
     */
    public static function mergeAdjacentSessions(array &$batchInserts, array $slots): void
    {
        usort($batchInserts, function ($a, $b) {
            if ($a["rombongan_belajar_id"] !== $b["rombongan_belajar_id"]) return strcmp($a["rombongan_belajar_id"], $b["rombongan_belajar_id"]);
            if ($a["hari"] !== $b["hari"]) return strcmp($a["hari"], $b["hari"]);
            return (int)$a["jam_ke_mulai"] <=> (int)$b["jam_ke_mulai"];
        });

        $merged = [];
        foreach ($batchInserts as $item) {
            $rId = $item["rombongan_belajar_id"];
            $h = $item["hari"];
            $mId = $item["mata_pelajaran_id"];
            $gId = $item["ptk_id"];
            $isRoutine = in_array($mId, ["UPACARA", "PEMBIASAAN", "ISTIRAHAT"], true);

            $lastIdx = count($merged) - 1;
            if (!$isRoutine && $lastIdx >= 0) {
                $prev = &$merged[$lastIdx];
                if ($prev["rombongan_belajar_id"] === $rId && $prev["hari"] === $h && $prev["mata_pelajaran_id"] === $mId && $prev["ptk_id"] === $gId) {
                    $pEnd = (int) $prev["jam_ke_selesai"];
                    $cStart = (int) $item["jam_ke_mulai"];
                    $isNext = ($cStart === $pEnd + 1);
                    if ($isNext) {
                        $cEnd = (int) $item["jam_ke_selesai"];
                        $prev["jam_ke_selesai"] = $cEnd;
                        $prev["jam_selesai"] = $slots[$cEnd]["selesai"] ? (strlen($slots[$cEnd]["selesai"]) === 5 ? $slots[$cEnd]["selesai"] . ":00" : $slots[$cEnd]["selesai"]) : $item["jam_selesai"];
                        continue;
                    }
                }
            }
            $merged[] = $item;
        }
        $batchInserts = $merged;
    }
}
