<?php

namespace App\Services;

class AutoSchedulerSwapResolver
{
    public function __construct(
        protected ?AutoSchedulerPlacementService $placementService = null
    ) {
        $this->placementService ??= new AutoSchedulerPlacementService();
    }

    /**
     * Resolusi sesi unallocated via Augmenting Swap (1-Step Ejection Chain).
     */
    public function resolveUnallocatedBySwap(
        array &$unallocatedSessions,
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array $rombelDailySlotCounts,
        ?int $istirahatJamKe,
        array $slots,
        mixed $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        for ($round = 0; $round < 3; $round++) {
            $unallocatedAfterSwap = [];
            $anySwapped = false;

            foreach ($unallocatedSessions as $sess) {
                $rId = $sess['rombongan_belajar_id'];
                $gId = $sess['ptk_id'];
                $placed = false;

                foreach ($hariList as $h) {
                    $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : ($rombelDailySlotCounts[$h] ?? 12);

                    for ($k = 1; $k <= $dayMax; $k++) {
                        if ($istirahatJamKe && $k === $istirahatJamKe) continue;
                        if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                        if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                        if ($gId && !empty($guruOccupied[$gId][$h][$k])) continue;

                        $foundItemIdx = null;
                        foreach ($batchInserts as $idx => $itemCheck) {
                            if ($itemCheck['rombongan_belajar_id'] === $rId && $itemCheck['hari'] === $h) {
                                if ($k >= (int)$itemCheck['jam_ke_mulai'] && $k <= (int)$itemCheck['jam_ke_selesai']) {
                                    $foundItemIdx = $idx;
                                    break;
                                }
                            }
                        }

                        if ($foundItemIdx === null) continue;

                        $bItem = $batchInserts[$foundItemIdx];
                        $gB = $bItem['ptk_id'];
                        $bStart = (int) $bItem['jam_ke_mulai'];
                        $bEnd = (int) $bItem['jam_ke_selesai'];
                        $bDur = $bEnd - $bStart + 1;
                        $bMapelId = $bItem['mata_pelajaran_id'];

                        if ($bDur > 3 || in_array($bMapelId, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                        for ($x = $bStart; $x <= $bEnd; $x++) {
                            unset($rombelOccupied[$rId][$h][$x]);
                            if ($gB) unset($guruOccupied[$gB][$h][$x]);
                        }

                        $rombelOccupied[$rId][$h][$k] = true;
                        if ($gId) $guruOccupied[$gId][$h][$k] = true;

                        $altPlaced = false;
                        foreach ($hariList as $altH) {
                            $altCands = $this->placementService->findCandidateSlots($rId, $altH, $bDur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false);
                            foreach ($altCands as $altCand) {
                                if (!$altCand['split']) {
                                    $as = $altCand['start'];
                                    $ae = $altCand['end'];
                                    if ($altH === $h && ($as <= $bEnd && $ae >= $bStart)) continue;

                                    $free = true;
                                    if ($gB) {
                                        for ($x = $as; $x <= $ae; $x++) {
                                            if (!empty($guruOccupied[$gB][$altH][$x])) { $free = false; break; }
                                        }
                                    }
                                    if ($free) {
                                        for ($x = $as; $x <= $ae; $x++) {
                                            $rombelOccupied[$rId][$altH][$x] = true;
                                            if ($gB) $guruOccupied[$gB][$altH][$x] = true;
                                        }

                                        $batchInserts[$foundItemIdx]['hari'] = $altH;
                                        $batchInserts[$foundItemIdx]['jam_ke_mulai'] = $as;
                                        $batchInserts[$foundItemIdx]['jam_ke_selesai'] = $ae;
                                        $batchInserts[$foundItemIdx]['jam_mulai'] = $slots[$as]['mulai'] ? (strlen($slots[$as]['mulai']) === 5 ? $slots[$as]['mulai'] . ':00' : $slots[$as]['mulai']) : $batchInserts[$foundItemIdx]['jam_mulai'];
                                        $batchInserts[$foundItemIdx]['jam_selesai'] = $slots[$ae]['selesai'] ? (strlen($slots[$ae]['selesai']) === 5 ? $slots[$ae]['selesai'] . ':00' : $slots[$ae]['selesai']) : $batchInserts[$foundItemIdx]['jam_selesai'];

                                        if ($altH !== $h) {
                                            if (isset($rombelDayMapel[$rId][$h][$bMapelId])) {
                                                $rombelDayMapel[$rId][$h][$bMapelId] = max(0, $rombelDayMapel[$rId][$h][$bMapelId] - 1);
                                            }
                                            $rombelDayMapel[$rId][$altH][$bMapelId] = ($rombelDayMapel[$rId][$altH][$bMapelId] ?? 0) + 1;

                                            if ($gB) {
                                                $guruDayJp[$gB][$h] = max(0, ($guruDayJp[$gB][$h] ?? 0) - $bDur);
                                                $guruDayJp[$gB][$altH] = ($guruDayJp[$gB][$altH] ?? 0) + $bDur;
                                            }
                                        }

                                        $altPlaced = true;
                                        break;
                                    }
                                }
                            }
                            if ($altPlaced) break;
                        }

                        if ($altPlaced) {
                            $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                            $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                            if ($gId) $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                            $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Swap Resolved)');

                            $placed = true;
                            $anySwapped = true;
                            break;
                        } else {
                            $slicePlaced = false;
                            if ($bDur >= 2 && ($k === $bStart || $k === $bEnd)) {
                                foreach ($hariList as $altH) {
                                    $altDayMax = isset($rombelDailySlotCounts[$rId][$altH]) ? $rombelDailySlotCounts[$rId][$altH] : ($rombelDailySlotCounts[$altH] ?? 12);
                                    for ($altK = 1; $altK <= $altDayMax; $altK++) {
                                        if ($istirahatJamKe && $altK === $istirahatJamKe) continue;
                                        if ($altK === 1 && $altH === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                                        if ($altK === 1 && $altH === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                                        if ($altH === $h && $altK >= $bStart && $altK <= $bEnd) continue;

                                        if (!empty($rombelOccupied[$rId][$altH][$altK])) continue;
                                        if ($gB && !empty($guruOccupied[$gB][$altH][$altK])) continue;

                                        if ($k === $bEnd) {
                                            $batchInserts[$foundItemIdx]['jam_ke_selesai'] = $bEnd - 1;
                                            $batchInserts[$foundItemIdx]['jam_selesai'] = $slots[$bEnd - 1]['selesai'] ? (strlen($slots[$bEnd - 1]['selesai']) === 5 ? $slots[$bEnd - 1]['selesai'] . ':00' : $slots[$bEnd - 1]['selesai']) : $batchInserts[$foundItemIdx]['jam_selesai'];
                                            for ($x = $bStart; $x <= $bEnd - 1; $x++) {
                                                $rombelOccupied[$rId][$h][$x] = true;
                                                if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                            }
                                        } else {
                                            $batchInserts[$foundItemIdx]['jam_ke_mulai'] = $bStart + 1;
                                            $batchInserts[$foundItemIdx]['jam_mulai'] = $slots[$bStart + 1]['mulai'] ? (strlen($slots[$bStart + 1]['mulai']) === 5 ? $slots[$bStart + 1]['mulai'] . ':00' : $slots[$bStart + 1]['mulai']) : $batchInserts[$foundItemIdx]['jam_mulai'];
                                            for ($x = $bStart + 1; $x <= $bEnd; $x++) {
                                                $rombelOccupied[$rId][$h][$x] = true;
                                                if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                            }
                                        }

                                        $sliceItem = $bItem;
                                        $sliceItem['hari'] = $altH;
                                        $sliceItem['jam_ke_mulai'] = $altK;
                                        $sliceItem['jam_ke_selesai'] = $altK;
                                        $sliceItem['jam_mulai'] = $slots[$altK]['mulai'] ? (strlen($slots[$altK]['mulai']) === 5 ? $slots[$altK]['mulai'] . ':00' : $slots[$altK]['mulai']) : '07:00:00';
                                        $sliceItem['jam_selesai'] = $slots[$altK]['selesai'] ? (strlen($slots[$altK]['selesai']) === 5 ? $slots[$altK]['selesai'] . ':00' : $slots[$altK]['selesai']) : '07:45:00';
                                        $sliceItem['keterangan'] = 'Auto-Generated (Swap Slice)';
                                        $batchInserts[] = $sliceItem;

                                        $rombelOccupied[$rId][$altH][$altK] = true;
                                        if ($gB) $guruOccupied[$gB][$altH][$altK] = true;

                                        if ($altH !== $h) {
                                            $rombelDayMapel[$rId][$altH][$bMapelId] = ($rombelDayMapel[$rId][$altH][$bMapelId] ?? 0) + 1;
                                            if ($gB) {
                                                $guruDayJp[$gB][$h] = max(0, ($guruDayJp[$gB][$h] ?? 0) - 1);
                                                $guruDayJp[$gB][$altH] = ($guruDayJp[$gB][$altH] ?? 0) + 1;
                                            }
                                        }

                                        $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                                        $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                        if ($gId) $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                                        $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Swap Slice Resolved)');

                                        $placed = true;
                                        $anySwapped = true;
                                        $slicePlaced = true;
                                        break;
                                    }
                                    if ($slicePlaced) break;
                                }
                            }

                            if (!$slicePlaced) {
                                unset($rombelOccupied[$rId][$h][$k]);
                                if ($gId) unset($guruOccupied[$gId][$h][$k]);

                                for ($x = $bStart; $x <= $bEnd; $x++) {
                                    $rombelOccupied[$rId][$h][$x] = true;
                                    if ($gB) $guruOccupied[$gB][$h][$x] = true;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                    if ($placed) break;
                }

                // Strategi B: Inter-Rombel Teacher Shift
                if (!$placed && $gId) {
                    foreach ($hariList as $h) {
                        $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : ($rombelDailySlotCounts[$h] ?? 12);
                        for ($k = 1; $k <= $dayMax; $k++) {
                            if ($istirahatJamKe && $k === $istirahatJamKe) continue;
                            if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                            if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;

                            if (!empty($rombelOccupied[$rId][$h][$k])) continue;
                            if (empty($guruOccupied[$gId][$h][$k])) continue;

                            $otherItemIdx = null;
                            foreach ($batchInserts as $idx => $itemCheck) {
                                if ($itemCheck['ptk_id'] === $gId && $itemCheck['hari'] === $h) {
                                    if ($k >= (int)$itemCheck['jam_ke_mulai'] && $k <= (int)$itemCheck['jam_ke_selesai']) {
                                        $otherItemIdx = $idx;
                                        break;
                                    }
                                }
                            }

                            if ($otherItemIdx === null) continue;

                            $otherItem = $batchInserts[$otherItemIdx];
                            $otherRId = $otherItem['rombongan_belajar_id'];
                            if ($otherRId === $rId) continue;

                            $oStart = (int) $otherItem['jam_ke_mulai'];
                            $oEnd = (int) $otherItem['jam_ke_selesai'];
                            $oDur = $oEnd - $oStart + 1;
                            $oMapelId = $otherItem['mata_pelajaran_id'];

                            if ($oDur > 6 || in_array($oMapelId, ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                            for ($x = $oStart; $x <= $oEnd; $x++) {
                                unset($rombelOccupied[$otherRId][$h][$x]);
                                unset($guruOccupied[$gId][$h][$x]);
                            }

                            $rombelOccupied[$rId][$h][$k] = true;
                            $guruOccupied[$gId][$h][$k] = true;

                            $altPlaced = false;
                            foreach ($hariList as $altH) {
                                $altCands = $this->placementService->findCandidateSlots($otherRId, $altH, $oDur, $rombelOccupied, $rombelDailySlotCounts, $istirahatJamKe, false);
                                foreach ($altCands as $altCand) {
                                    if (!$altCand['split']) {
                                        $as = $altCand['start'];
                                        $ae = $altCand['end'];
                                        if ($altH === $h && ($as <= $oEnd && $ae >= $oStart)) continue;

                                        $free = true;
                                        for ($x = $as; $x <= $ae; $x++) {
                                            if (!empty($guruOccupied[$gId][$altH][$x])) { $free = false; break; }
                                        }
                                        if ($free) {
                                            for ($x = $as; $x <= $ae; $x++) {
                                                $rombelOccupied[$otherRId][$altH][$x] = true;
                                                $guruOccupied[$gId][$altH][$x] = true;
                                            }

                                            $batchInserts[$otherItemIdx]['hari'] = $altH;
                                            $batchInserts[$otherItemIdx]['jam_ke_mulai'] = $as;
                                            $batchInserts[$otherItemIdx]['jam_ke_selesai'] = $ae;
                                            $batchInserts[$otherItemIdx]['jam_mulai'] = $slots[$as]['mulai'] ? (strlen($slots[$as]['mulai']) === 5 ? $slots[$as]['mulai'] . ':00' : $slots[$as]['mulai']) : $batchInserts[$otherItemIdx]['jam_mulai'];
                                            $batchInserts[$otherItemIdx]['jam_selesai'] = $slots[$ae]['selesai'] ? (strlen($slots[$ae]['selesai']) === 5 ? $slots[$ae]['selesai'] . ':00' : $slots[$ae]['selesai']) : $batchInserts[$otherItemIdx]['jam_selesai'];

                                            if ($altH !== $h) {
                                                if (isset($rombelDayMapel[$otherRId][$h][$oMapelId])) {
                                                    $rombelDayMapel[$otherRId][$h][$oMapelId] = max(0, $rombelDayMapel[$otherRId][$h][$oMapelId] - 1);
                                                }
                                                $rombelDayMapel[$otherRId][$altH][$oMapelId] = ($rombelDayMapel[$otherRId][$altH][$oMapelId] ?? 0) + 1;

                                                $guruDayJp[$gId][$h] = max(0, ($guruDayJp[$gId][$h] ?? 0) - $oDur);
                                                $guruDayJp[$gId][$altH] = ($guruDayJp[$gId][$altH] ?? 0) + $oDur;
                                            }

                                            $altPlaced = true;
                                            break;
                                        }
                                    }
                                }
                                if ($altPlaced) break;
                            }

                            if ($altPlaced) {
                                $rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] = ($rombelDayMapel[$rId][$h][$sess['mata_pelajaran_id']] ?? 0) + 1;
                                $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                $guruDayJp[$gId][$h] = ($guruDayJp[$gId][$h] ?? 0) + 1;

                                $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $h, $k, $k, $slots, $rombelMap, 'Auto-Generated (Teacher Shift Resolved)');

                                $placed = true;
                                $anySwapped = true;
                                break;
                            } else {
                                unset($rombelOccupied[$rId][$h][$k]);
                                unset($guruOccupied[$gId][$h][$k]);

                                for ($x = $oStart; $x <= $oEnd; $x++) {
                                    $rombelOccupied[$otherRId][$h][$x] = true;
                                    $guruOccupied[$gId][$h][$x] = true;
                                }
                            }
                        }
                        if ($placed) break;
                    }
                }

                if (!$placed) {
                    $unallocatedAfterSwap[] = $sess;
                }
            }
            $unallocatedSessions = $unallocatedAfterSwap;

            if (!$anySwapped || empty($unallocatedSessions)) {
                break;
            }
        }
    }

    /**
     * Resolusi sesi unallocated via Intra-Rombel Relocation (Menukar sesi internal dalam rombel yang sama).
     */
    public function resolveUnallocatedByIntraRombelSwap(
        array &$unallocatedSessions,
        array &$batchInserts,
        array &$rombelOccupied,
        array &$guruOccupied,
        array &$rombelDayMapel,
        array &$rombelPureKbmJp,
        array &$guruDayJp,
        array $rombelDailySlotCounts,
        ?int $istirahatJamKe,
        array $slots,
        mixed $rombelMap,
        array $hariList,
        ?object $pengaturan
    ): void {
        for ($round = 0; $round < 5; $round++) {
            $stillUnallocated = [];
            $anySwapped = false;

            foreach ($unallocatedSessions as $sess) {
                $rId = $sess['rombongan_belajar_id'];
                $gId = $sess['ptk_id'];
                $placed = false;

                $emptySlots = [];
                foreach ($hariList as $h) {
                    $dayMax = isset($rombelDailySlotCounts[$rId][$h]) ? $rombelDailySlotCounts[$rId][$h] : 12;
                    for ($k = 1; $k <= $dayMax; $k++) {
                        if ($istirahatJamKe && $k === $istirahatJamKe && $h !== 'Jumat') continue;
                        if ($k === 1 && $h === 'Senin' && !empty($pengaturan->upacara['aktif'])) continue;
                        if ($k === 1 && $h === 'Jumat' && !empty($pengaturan->pembiasaan['aktif'])) continue;
                        if (empty($rombelOccupied[$rId][$h][$k])) {
                            $emptySlots[] = ['hari' => $h, 'jam_ke' => $k];
                        }
                    }
                }

                foreach ($emptySlots as $es) {
                    $eh = $es['hari'];
                    $ek = $es['jam_ke'];
                    if (!$gId || empty($guruOccupied[$gId][$eh][$ek])) {
                        $rombelOccupied[$rId][$eh][$ek] = true;
                        if ($gId) $guruOccupied[$gId][$eh][$ek] = true;
                        $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $eh, $ek, $ek, $slots, $rombelMap, 'Auto-Generated (Direct Empty Fill)');
                        $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                        if ($gId) $guruDayJp[$gId][$eh] = ($guruDayJp[$gId][$eh] ?? 0) + 1;
                        $placed = true;
                        $anySwapped = true;
                        break;
                    }
                }

                if ($placed) continue;

                foreach ($emptySlots as $es) {
                    $eh = $es['hari'];
                    $ek = $es['jam_ke'];

                    foreach ($batchInserts as $idx => $bItem) {
                        if ($bItem['rombongan_belajar_id'] !== $rId) continue;
                        if (in_array($bItem['mata_pelajaran_id'], ['UPACARA', 'PEMBIASAAN', 'ISTIRAHAT'], true)) continue;

                        $bH = $bItem['hari'];
                        $bStart = (int) $bItem['jam_ke_mulai'];
                        $bEnd = (int) $bItem['jam_ke_selesai'];
                        $bDur = $bEnd - $bStart + 1;
                        $gB = $bItem['ptk_id'];

                        if ($gB && !empty($guruOccupied[$gB][$eh][$ek])) continue;

                        if ($bDur === 1) {
                            if (!$gId || empty($guruOccupied[$gId][$bH][$bStart])) {
                                unset($rombelOccupied[$rId][$bH][$bStart]);
                                if ($gB) unset($guruOccupied[$gB][$bH][$bStart]);

                                $rombelOccupied[$rId][$eh][$ek] = true;
                                if ($gB) $guruOccupied[$gB][$eh][$ek] = true;

                                $batchInserts[$idx]['hari'] = $eh;
                                $batchInserts[$idx]['jam_ke_mulai'] = $ek;
                                $batchInserts[$idx]['jam_ke_selesai'] = $ek;
                                $batchInserts[$idx]['jam_mulai'] = $slots[$ek]['mulai'] ? (strlen($slots[$ek]['mulai']) === 5 ? $slots[$ek]['mulai'] . ':00' : $slots[$ek]['mulai']) : $batchInserts[$idx]['jam_mulai'];
                                $batchInserts[$idx]['jam_selesai'] = $slots[$ek]['selesai'] ? (strlen($slots[$ek]['selesai']) === 5 ? $slots[$ek]['selesai'] . ':00' : $slots[$ek]['selesai']) : $batchInserts[$idx]['jam_selesai'];

                                $rombelOccupied[$rId][$bH][$bStart] = true;
                                if ($gId) $guruOccupied[$gId][$bH][$bStart] = true;

                                $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $bH, $bStart, $bStart, $slots, $rombelMap, 'Auto-Generated (Intra-Rombel Swap Resolved)');
                                $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                if ($gId) $guruDayJp[$gId][$bH] = ($guruDayJp[$gId][$bH] ?? 0) + 1;
                                $placed = true;
                                $anySwapped = true;
                                break 2;
                            }
                        } elseif ($bDur >= 2) {
                            foreach ([$bStart, $bEnd] as $targetK) {
                                if (!$gId || empty($guruOccupied[$gId][$bH][$targetK])) {
                                    unset($rombelOccupied[$rId][$bH][$targetK]);
                                    if ($gB) unset($guruOccupied[$gB][$bH][$targetK]);

                                    if ($targetK === $bStart) {
                                        $batchInserts[$idx]['jam_ke_mulai'] = $bStart + 1;
                                        $batchInserts[$idx]['jam_mulai'] = $slots[$bStart + 1]['mulai'] ? (strlen($slots[$bStart + 1]['mulai']) === 5 ? $slots[$bStart + 1]['mulai'] . ':00' : $slots[$bStart + 1]['mulai']) : $batchInserts[$idx]['jam_mulai'];
                                    } else {
                                        $batchInserts[$idx]['jam_ke_selesai'] = $bEnd - 1;
                                        $batchInserts[$idx]['jam_selesai'] = $slots[$bEnd - 1]['selesai'] ? (strlen($slots[$bEnd - 1]['selesai']) === 5 ? $slots[$bEnd - 1]['selesai'] . ':00' : $slots[$bEnd - 1]['selesai']) : $batchInserts[$idx]['jam_selesai'];
                                    }

                                    $rombelOccupied[$rId][$eh][$ek] = true;
                                    if ($gB) $guruOccupied[$gB][$eh][$ek] = true;

                                    $sliceItem = $bItem;
                                    $sliceItem['hari'] = $eh;
                                    $sliceItem['jam_ke_mulai'] = $altK ?? $ek;
                                    $sliceItem['jam_ke_selesai'] = $altK ?? $ek;
                                    $sliceItem['jam_mulai'] = $slots[$ek]['mulai'] ? (strlen($slots[$ek]['mulai']) === 5 ? $slots[$ek]['mulai'] . ':00' : $slots[$ek]['mulai']) : '07:15:00';
                                    $sliceItem['jam_selesai'] = $slots[$ek]['selesai'] ? (strlen($slots[$ek]['selesai']) === 5 ? $slots[$ek]['selesai'] . ':00' : $slots[$ek]['selesai']) : '08:00:00';
                                    $sliceItem['keterangan'] = ($bItem['keterangan'] ?? 'Auto-Generated') . ' (Slice Relocated)';
                                    $batchInserts[] = $sliceItem;

                                    $rombelOccupied[$rId][$bH][$targetK] = true;
                                    if ($gId) $guruOccupied[$gId][$bH][$targetK] = true;

                                    $batchInserts[] = AutoSchedulerSupport::buildScheduleItem($sess, $rId, $bH, $targetK, $targetK, $slots, $rombelMap, 'Auto-Generated (Slice Swap Resolved)');
                                    $rombelPureKbmJp[$rId] = ($rombelPureKbmJp[$rId] ?? 0) + 1;
                                    if ($gId) $guruDayJp[$gId][$bH] = ($guruDayJp[$gId][$bH] ?? 0) + 1;
                                    $placed = true;
                                    $anySwapped = true;
                                    break 3;
                                }
                            }
                        }
                    }
                }

                if (!$placed) {
                    $stillUnallocated[] = $sess;
                }
            }

            $unallocatedSessions = $stillUnallocated;
            if (!$anySwapped || empty($unallocatedSessions)) {
                break;
            }
        }
    }
}
