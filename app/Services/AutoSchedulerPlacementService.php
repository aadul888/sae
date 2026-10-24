<?php

namespace App\Services;

class AutoSchedulerPlacementService
{
    /**
     * Cari kandidat slot waktu untuk rombel $rId pada hari $h yang muat durasi $dur.
     *
     * @return array Array kandidat slot [ ['start' => int, 'end' => int, 'split' => bool] | ['start_pagi' => int, ...] ]
     */
    public function findCandidateSlots(
        string $rId,
        string $h,
        int $dur,
        array &$rombelOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        bool $allowGaps = false,
        bool $isPkl = false
    ): array {
        $dayMax = isset($dailySlotCounts[$rId][$h]) 
            ? $dailySlotCounts[$rId][$h] 
            : ($dailySlotCounts[$h] ?? 12);
        $rOcc = $rombelOccupied[$rId][$h] ?? [];

        $morningMax = ($h === 'Jumat' || !$istirahatJamKe) ? $dayMax : ($istirahatJamKe - 1);
        $candidates = [];

        // 1. Sesi Pagi
        for ($startK = 1; $startK <= ($morningMax - $dur + 1); $startK++) {
            $endK = $startK + $dur - 1;
            $free = true;
            for ($k = $startK; $k <= $endK; $k++) {
                if (!empty($rOcc[$k])) { $free = false; break; }
            }
            if ($free) {
                $candidates[] = [
                    'start' => $startK,
                    'end'   => $endK,
                    'split' => false,
                ];
            }
        }

        // 2. Bridge Across Break (Pagi -> Siang)
        if ($h !== 'Jumat' && $istirahatJamKe && $dur > 1) {
            for ($pagiFit = min($dur - 1, $morningMax); $pagiFit >= 1; $pagiFit--) {
                $siangFit = $dur - $pagiFit;
                $sp = $morningMax - $pagiFit + 1;
                $ep = $morningMax;
                $ss = $istirahatJamKe + 1;
                $es = $ss + $siangFit - 1;

                if ($es <= $dayMax && $sp >= 1) {
                    $free = true;
                    for ($k = $sp; $k <= $ep; $k++) {
                        if (!empty($rOcc[$k])) { $free = false; break; }
                    }
                    for ($k = $ss; $k <= $es; $k++) {
                        if (!empty($rOcc[$k])) { $free = false; break; }
                    }
                    if ($free) {
                        $candidates[] = [
                            'start_pagi'  => $sp,
                            'end_pagi'    => $ep,
                            'start_siang' => $ss,
                            'end_siang'   => $es,
                            'dur_pagi'    => $pagiFit,
                            'dur_siang'   => $siangFit,
                            'split'       => true,
                        ];
                    }
                }
            }
        }

        // 3. Sesi Siang
        if ($h !== 'Jumat' && $istirahatJamKe) {
            $afternoonStart = $istirahatJamKe + 1;
            for ($startK = $afternoonStart; $startK <= ($dayMax - $dur + 1); $startK++) {
                $endK = $startK + $dur - 1;
                $free = true;
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($rOcc[$k])) { $free = false; break; }
                }
                if ($free) {
                    $candidates[] = [
                        'start' => $startK,
                        'end'   => $endK,
                        'split' => false,
                    ];
                }
            }
        }

        // 4. Fallback Cadangan
        if (empty($candidates) && $allowGaps) {
            for ($startK = 1; $startK <= ($dayMax - $dur + 1); $startK++) {
                $endK = $startK + $dur - 1;
                if ($istirahatJamKe && $startK <= $istirahatJamKe && $endK >= $istirahatJamKe) continue;

                $free = true;
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($rOcc[$k])) { $free = false; break; }
                }
                if ($free) {
                    $candidates[] = [
                        'start' => $startK,
                        'end'   => $endK,
                        'split' => false,
                    ];
                    break;
                }
            }
        }

        return $candidates;
    }

    /**
     * Eksekusi penempatan kandidat sesi ke matriks occupied dan batch inserts.
     */
    public function tryPlaceCandidate(
        array $sess,
        array $cand,
        string $rId,
        string $h,
        ?object $gPref,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array &$batchInserts,
        array $slots,
        mixed $rombelMap,
        string $keterangan
    ): bool {
        $gId   = $sess['ptk_id'];
        $mId   = $sess['mata_pelajaran_id'];
        $dur   = $sess['duration'];

        if (!$cand['split']) {
            $startK = $cand['start'];
            $endK   = $cand['end'];

            if (AutoSchedulerSupport::isTeacherUnavailable($gPref, $h, $startK, $endK)) {
                return false;
            }

            if ($gId) {
                for ($k = $startK; $k <= $endK; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) {
                        return false;
                    }
                }
            }

            for ($k = $startK; $k <= $endK; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) {
                    $guruOccupied[$gId][$h][$k] = true;
                }
            }

            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
            if ($gId) {
                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
            }

            $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $startK, $endK, $slots, $rombelMap, $keterangan);
            return true;
        } else {
            $sp = $cand['start_pagi'];
            $ep = $cand['end_pagi'];
            $ss = $cand['start_siang'];
            $es = $cand['end_siang'];

            if (AutoSchedulerSupport::isTeacherUnavailable($gPref, $h, $sp, $ep) || AutoSchedulerSupport::isTeacherUnavailable($gPref, $h, $ss, $es)) {
                return false;
            }

            if ($gId) {
                for ($k = $sp; $k <= $ep; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) return false;
                }
                for ($k = $ss; $k <= $es; $k++) {
                    if (!empty($guruOccupied[$gId][$h][$k])) return false;
                }
            }

            for ($k = $sp; $k <= $ep; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) $guruOccupied[$gId][$h][$k] = true;
            }
            for ($k = $ss; $k <= $es; $k++) {
                $rombelOccupied[$rId][$h][$k] = true;
                if ($gId) $guruOccupied[$gId][$h][$k] = true;
            }

            $rombelDayMapel[$rId][$h][$mId] = ($rombelDayMapel[$rId][$h][$mId] ?? 0) + 1;
            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + $dur;
            if ($gId) {
                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + $dur;
            }

            $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $sp, $ep, $slots, $rombelMap, $keterangan . ' (Pagi)');
            $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $ss, $es, $slots, $rombelMap, $keterangan . ' (Lanjutan Siang)');
            return true;
        }
    }

    /**
     * Padatkan jadwal rombel ke jam-jam awal (Bubble Compaction) agar jam kosong terkumpul di akhir hari.
     */
    public function compactRombelSchedules(
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array $dailySlotCounts,
        ?int $istirahatJamKe,
        array $slots
    ): void {
        usort($batchInserts, function ($a, $b) {
            if ($a['rombongan_belajar_id'] !== $b['rombongan_belajar_id']) {
                return strcmp($a['rombongan_belajar_id'], $b['rombongan_belajar_id']);
            }
            if ($a['hari'] !== $b['hari']) {
                return strcmp($a['hari'], $b['hari']);
            }
            return $a['jam_ke_mulai'] <=> $b['jam_ke_mulai'];
        });

        for ($iter = 0; $iter < 12; $iter++) {
            $anyMoved = false;

            foreach ($batchInserts as &$item) {
                if (in_array($item['mata_pelajaran_id'], ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) {
                    continue;
                }

                $rId   = $item['rombongan_belajar_id'];
                $h     = $item['hari'];
                $gId   = $item['ptk_id'];
                $start = (int) $item['jam_ke_mulai'];
                $end   = (int) $item['jam_ke_selesai'];
                $dur   = $end - $start + 1;

                if ($istirahatJamKe && $start > $istirahatJamKe) {
                    $morningMax = $istirahatJamKe - 1;
                    for ($mStart = 1; $mStart <= ($morningMax - $dur + 1); $mStart++) {
                        $mEnd = $mStart + $dur - 1;
                        $mFree = true;
                        for ($mk = $mStart; $mk <= $mEnd; $mk++) {
                            if (!empty($rombelOccupied[$rId][$h][$mk])) {
                                $mFree = false;
                                break;
                            }
                            if ($gId && !empty($guruOccupied[$gId][$h][$mk])) {
                                $mFree = false;
                                break;
                            }
                        }

                        if ($mFree) {
                            for ($k = $start; $k <= $end; $k++) {
                                unset($rombelOccupied[$rId][$h][$k]);
                                if ($gId) unset($guruOccupied[$gId][$h][$k]);
                            }

                            for ($k = $mStart; $k <= $mEnd; $k++) {
                                $rombelOccupied[$rId][$h][$k] = true;
                                if ($gId) $guruOccupied[$gId][$h][$k] = true;
                            }

                            $item['jam_ke_mulai'] = $mStart;
                            $item['jam_ke_selesai'] = $mEnd;
                            $item['jam_mulai'] = $slots[$mStart]['mulai'] ? (strlen($slots[$mStart]['mulai']) === 5 ? $slots[$mStart]['mulai'] . ':00' : $slots[$mStart]['mulai']) : $item['jam_mulai'];
                            $item['jam_selesai'] = $slots[$mEnd]['selesai'] ? (strlen($slots[$mEnd]['selesai']) === 5 ? $slots[$mEnd]['selesai'] . ':00' : $slots[$mEnd]['selesai']) : $item['jam_selesai'];

                            $anyMoved = true;
                            break;
                        }
                    }
                    if ($anyMoved) continue;
                }

                $minAllowed = ($istirahatJamKe && $start > $istirahatJamKe) ? ($istirahatJamKe + 1) : 1;
                for ($targetStart = $minAllowed; $targetStart < $start; $targetStart++) {
                    $targetEnd = $targetStart + $dur - 1;

                    if ($istirahatJamKe && $start <= $istirahatJamKe && $targetEnd >= $istirahatJamKe) {
                        continue;
                    }

                    $canFit = true;
                    for ($k = $targetStart; $k <= $targetEnd; $k++) {
                        if ($k < $start && !empty($rombelOccupied[$rId][$h][$k])) {
                            $canFit = false;
                            break;
                        }
                        if ($gId && $k < $start && !empty($guruOccupied[$gId][$h][$k])) {
                            $canFit = false;
                            break;
                        }
                    }

                    if ($canFit) {
                        for ($k = $start; $k <= $end; $k++) {
                            unset($rombelOccupied[$rId][$h][$k]);
                            if ($gId) unset($guruOccupied[$gId][$h][$k]);
                        }
                        for ($k = $targetStart; $k <= $targetEnd; $k++) {
                            $rombelOccupied[$rId][$h][$k] = true;
                            if ($gId) $guruOccupied[$gId][$h][$k] = true;
                        }

                        $item['jam_ke_mulai'] = $targetStart;
                        $item['jam_ke_selesai'] = $targetEnd;
                        $item['jam_mulai'] = $slots[$targetStart]['mulai'] ? (strlen($slots[$targetStart]['mulai']) === 5 ? $slots[$targetStart]['mulai'] . ':00' : $slots[$targetStart]['mulai']) : $item['jam_mulai'];
                        $item['jam_selesai'] = $slots[$targetEnd]['selesai'] ? (strlen($slots[$targetEnd]['selesai']) === 5 ? $slots[$targetEnd]['selesai'] . ':00' : $slots[$targetEnd]['selesai']) : $item['jam_selesai'];

                        $anyMoved = true;
                        break;
                    }
                }
            }
            unset($item);

            if (!$anyMoved) break;
        }
    }
}
