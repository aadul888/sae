<?php

namespace App\Services;

class AutoSchedulerLayoutOptimizer
{
    /**
     * Susun ulang seluruh sesi menjadi blok utuh (minimal 2 JP, satu mapel maksimal satu blok per hari,
     * tidak melewati jam istirahat). Hasil hanya dipakai jika benar-benar bebas bentrok guru.
     */
    public function blockifyLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        int $maxJpPerSession,
        ?int $istirahatJamKe,
        mixed $guruPreferences,
        ?int $seed,
        float $budget
    ): bool {
        $templates = [];
        $jpOf = [];
        $ptkOf = [];
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                unset($rombelOccupied[$rId][$row['hari']][$k]);
                if ($row['ptk_id']) unset($guruOccupied[$row['ptk_id']][$row['hari']][$k]);
            }
            $pid = $row['pembelajaran_id'];
            $templates[$pid] ??= $row;
            $ptkOf[$pid] = $row['ptk_id'];
            $jpOf[$rId][$pid] = ($jpOf[$rId][$pid] ?? 0) + ((int) $row['jam_ke_selesai'] - (int) $row['jam_ke_mulai'] + 1);
        }

        $segments = [];
        foreach (array_keys($jpOf) as $rId) {
            foreach ($hariList as $h) {
                $dayMax = $rombelDailySlotCounts[$rId][$h] ?? ($rombelDailySlotCounts[$h] ?? 12);
                $cur = null;
                for ($k = 1; $k <= $dayMax + 1; $k++) {
                    $free = $k <= $dayMax && empty($rombelOccupied[$rId][$h][$k]);
                    if ($free) {
                        $cur ??= ['day' => $h, 'start' => $k, 'size' => 0];
                        $cur['size']++;
                    } elseif ($cur) {
                        $segments[$rId][] = $cur;
                        $cur = null;
                    }
                }
            }
        }

        $blocks = [];
        $allowOf = [];
        $capOf = [];
        foreach ($jpOf as $rId => $pids) {
            $maxSeg = 1;
            $dayCount = [];
            foreach ($segments[$rId] ?? [] as $s) {
                $maxSeg = max($maxSeg, $s['size']);
                $dayCount[$s['day']] = true;
            }
            $cap = max(2, min($maxJpPerSession, $maxSeg, 6));
            foreach ($pids as $pid => $jp) {
                $parts = AutoSchedulerSupport::decomposeJjmIntoBlocks($jp, $cap, $istirahatJamKe);
                $allowOf[$rId][$pid] = max(1, (int) ceil(count($parts) / max(1, count($dayCount))));
                $capOf[$rId][$pid] = max($maxJpPerSession, max($parts));
            }
        }
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $pid = $row['pembelajaran_id'];
            $blocks[] = [
                'rId' => $rId, 'pid' => $pid, 'ptk' => $row['ptk_id'],
                'len' => (int) $row['jam_ke_selesai'] - (int) $row['jam_ke_mulai'] + 1,
                'day' => $row['hari'], 'start' => (int) $row['jam_ke_mulai'],
                'allow' => $allowOf[$rId][$pid] ?? 1, 'cap' => $capOf[$rId][$pid] ?? $maxJpPerSession,
            ];
        }

        $prefs = [];
        foreach ($guruPreferences as $ptk => $p) {
            $un = [];
            foreach (($p->jam_unavailable ?? []) as $u) {
                $from = (int) ($u['jam_ke_mulai'] ?? 1);
                $un[] = [$u['hari'] ?? '', $from, (int) ($u['jam_ke_selesai'] ?? $from)];
            }
            $prefs[$ptk] = [
                'off' => $p->hari_off ?? [],
                'unavail' => $un,
                'max' => $p->max_jp_per_hari ?: null,
            ];
        }

        $solver = new BlockTimetableSolver();
        $result = $solver->solve($segments, $blocks, $guruOccupied, $prefs, ($seed ?? 1) + 101, $budget);
        if (!$result['ok']) return false;

        $fmt = fn($t) => strlen((string) $t) === 5 ? $t . ':00' : $t;
        $rebuilt = [];
        foreach ($result['items'] as $it) {
            $start = $it['start'];
            $end = $start + $it['len'] - 1;
            $row = $templates[$it['pid']];
            $row['rombongan_belajar_id'] = $it['rId'];
            $row['hari'] = $it['day'];
            $row['jam_ke_mulai'] = $start;
            $row['jam_ke_selesai'] = $end;
            $row['jam_mulai'] = $fmt($slots[$start]['mulai'] ?? $row['jam_mulai']);
            $row['jam_selesai'] = $fmt($slots[$end]['selesai'] ?? $row['jam_selesai']);
            $row['keterangan'] = 'Auto-Generated';
            $rebuilt[] = $row;
        }
        $batchInserts = $rebuilt;

        return true;
    }

    /**
     * Susun ulang slot per rombel (tukar antar slot, bebas bentrok guru) agar tidak ada sesi 1 JP
     * dan satu mapel tidak terpecah di hari yang sama. Jam istirahat tidak pernah disentuh.
     *
     * @return array{single_jp:int, split:int}
     */
    public function optimizeSlotLayout(
        array &$batchInserts,
        array $slots,
        array $rombelOccupied,
        array $guruOccupied,
        array $rombelDailySlotCounts,
        array $hariList,
        ?int $istirahatJamKe,
        mixed $guruPreferences,
        ?int $seed,
        float $seconds = 6.0
    ): array {
        $cell = [];
        $templates = [];
        $teacherOcc = [];
        $teacherDay = [];
        $rombelIds = [];

        foreach ($batchInserts as $row) {
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                unset($rombelOccupied[$row['rombongan_belajar_id']][$row['hari']][$k]);
                if ($row['ptk_id']) unset($guruOccupied[$row['ptk_id']][$row['hari']][$k]);
            }
        }

        $validK = [];
        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $rombelIds[$rId] = true;
            $templates[$row['pembelajaran_id']] ??= $row;
        }
        $rombelIds = array_keys($rombelIds);

        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $dayMax = $rombelDailySlotCounts[$rId][$h] ?? ($rombelDailySlotCounts[$h] ?? 12);
                for ($k = 1; $k <= $dayMax; $k++) {
                    if (empty($rombelOccupied[$rId][$h][$k])) $validK[$rId][$h][] = $k;
                }
            }
        }

        foreach ($batchInserts as $row) {
            $rId = $row['rombongan_belajar_id'];
            $h = $row['hari'];
            $ptk = $row['ptk_id'];
            for ($k = (int) $row['jam_ke_mulai']; $k <= (int) $row['jam_ke_selesai']; $k++) {
                if (!in_array($k, $validK[$rId][$h] ?? [], true)) continue;
                $cell[$rId][$h][$k] = ['pid' => $row['pembelajaran_id'], 'ptk' => $ptk];
                if ($ptk) {
                    $teacherOcc[$ptk][$h][$k] = $rId;
                    $teacherDay[$ptk][$h] = ($teacherDay[$ptk][$h] ?? 0) + 1;
                }
            }
        }

        $breakAt = $istirahatJamKe;
        $buildRuns = function (string $rId, string $h) use (&$cell, &$validK, $breakAt): array {
            $runs = [];
            $cur = null;
            foreach ($validK[$rId][$h] ?? [] as $k) {
                $c = $cell[$rId][$h][$k] ?? null;
                if ($c === null) {
                    if ($cur) { $runs[] = $cur; $cur = null; }
                    continue;
                }
                if ($cur && $cur['pid'] === $c['pid'] && $k === $cur['end'] + 1) {
                    $cur['end'] = $k;
                    $cur['len']++;
                } else {
                    if ($cur) $runs[] = $cur;
                    $cur = ['pid' => $c['pid'], 'start' => $k, 'end' => $k, 'len' => 1, 'bridge' => false];
                }
            }
            if ($cur) $runs[] = $cur;

            $merged = [];
            foreach ($runs as $run) {
                $li = count($merged) - 1;
                if ($breakAt && $li >= 0 && $merged[$li]['pid'] === $run['pid']
                    && $merged[$li]['end'] === $breakAt - 1 && $run['start'] === $breakAt + 1) {
                    $merged[$li]['end'] = $run['end'];
                    $merged[$li]['len'] += $run['len'];
                    $merged[$li]['bridge'] = true;
                    continue;
                }
                $merged[] = $run;
            }
            return $merged;
        };
        $dayCost = function (string $rId, string $h) use ($buildRuns): int {
            $cost = 0;
            $seen = [];
            foreach ($buildRuns($rId, $h) as $run) {
                if ($run['len'] === 1) $cost += 4;
                if ($run['bridge']) $cost += 1;
                if (isset($seen[$run['pid']])) $cost += 4;
                $seen[$run['pid']] = true;
            }
            return $cost;
        };

        $costCache = [];
        $total = 0;
        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $costCache[$rId][$h] = $dayCost($rId, $h);
                $total += $costCache[$rId][$h];
            }
        }

        $canTeach = function (?string $ptk, string $h, int $k) use (&$teacherOcc, &$teacherDay, $guruOccupied, $guruPreferences): bool {
            if (!$ptk) return true;
            if (!empty($guruOccupied[$ptk][$h][$k]) || isset($teacherOcc[$ptk][$h][$k])) return false;
            $pref = $guruPreferences[$ptk] ?? null;
            if ($pref) {
                if (!empty($pref->hari_off) && in_array($h, $pref->hari_off, true)) return false;
                if (AutoSchedulerSupport::isTeacherUnavailable($pref, $h, $k, $k)) return false;
                if ($pref->max_jp_per_hari && (($teacherDay[$ptk][$h] ?? 0) + 1 > $pref->max_jp_per_hari)) return false;
            }
            return true;
        };
        $tPlace = function (?string $ptk, string $rId, string $h, int $k) use (&$teacherOcc, &$teacherDay): void {
            if (!$ptk) return;
            $teacherOcc[$ptk][$h][$k] = $rId;
            $teacherDay[$ptk][$h] = ($teacherDay[$ptk][$h] ?? 0) + 1;
        };
        $tRemove = function (?string $ptk, string $h, int $k) use (&$teacherOcc, &$teacherDay): void {
            if (!$ptk) return;
            unset($teacherOcc[$ptk][$h][$k]);
            $teacherDay[$ptk][$h] = max(0, ($teacherDay[$ptk][$h] ?? 0) - 1);
        };

        mt_srand(($seed ?? 1) + 7);
        $deadline = microtime(true) + $seconds;
        $temp = 1.5;
        $badList = [];
        $iter = 0;

        while ($total > 0) {
            if ($iter % 400 === 0) {
                if (microtime(true) > $deadline) break;
                $temp = max(0.2, $temp * 0.985);
                $badList = [];
                foreach ($costCache as $rId => $days) {
                    foreach ($days as $h => $c) {
                        if ($c > 0) $badList[] = [$rId, $h];
                    }
                }
            }
            $iter++;

            if ($badList && mt_rand(1, 100) <= 80) {
                [$rId, $h] = $badList[array_rand($badList)];
            } else {
                $rId = $rombelIds[array_rand($rombelIds)];
                $h = $hariList[array_rand($hariList)];
            }
            $daySlots = $validK[$rId][$h] ?? [];
            if (!$daySlots) continue;

            $targeted = false;
            if (mt_rand(1, 100) <= 75) {
                $bad = [];
                $seenPid = [];
                foreach ($buildRuns($rId, $h) as $run) {
                    if ($run['len'] === 1 || isset($seenPid[$run['pid']])) $bad[] = $run;
                    $seenPid[$run['pid']] = true;
                }
                if ($bad) {
                    $run = $bad[array_rand($bad)];
                    $near = [];
                    foreach ([$run['start'] - 1, $run['end'] + 1] as $nk) {
                        if (in_array($nk, $daySlots, true)) $near[] = $nk;
                    }
                    $pieces = [];
                    foreach ($hariList as $hh) {
                        foreach ($validK[$rId][$hh] ?? [] as $kk) {
                            $c = $cell[$rId][$hh][$kk] ?? null;
                            if ($c && $c['pid'] === $run['pid'] && !($hh === $h && $kk >= $run['start'] && $kk <= $run['end'])) {
                                $pieces[] = [$hh, $kk];
                            }
                        }
                    }
                    if ($near && $pieces) {
                        $a = $near[array_rand($near)];
                        [$h2, $b] = $pieces[array_rand($pieces)];
                        $targeted = true;
                    }
                }
            }

            if (!$targeted) {
                $a = $daySlots[array_rand($daySlots)];
                $h2 = (mt_rand(1, 100) <= 60) ? $h : $hariList[array_rand($hariList)];
                $slots2 = $validK[$rId][$h2] ?? [];
                if (!$slots2) continue;
                $b = $slots2[array_rand($slots2)];
            }
            if ($h === $h2 && $a === $b) continue;

            $A = $cell[$rId][$h][$a] ?? null;
            $B = $cell[$rId][$h2][$b] ?? null;
            if ($A === null && $B === null) continue;
            if ($A !== null && $B !== null && $A['pid'] === $B['pid']) continue;

            if ($A) $tRemove($A['ptk'], $h, $a);
            if ($B) $tRemove($B['ptk'], $h2, $b);

            $ok = (!$A || $canTeach($A['ptk'], $h2, $b)) && (!$B || $canTeach($B['ptk'], $h, $a));
            if (!$ok) {
                if ($A) $tPlace($A['ptk'], $rId, $h, $a);
                if ($B) $tPlace($B['ptk'], $rId, $h2, $b);
                continue;
            }

            $cell[$rId][$h][$a] = $B;
            $cell[$rId][$h2][$b] = $A;
            $new1 = $dayCost($rId, $h);
            $new2 = ($h2 === $h) ? $new1 : $dayCost($rId, $h2);
            $old = $costCache[$rId][$h] + (($h2 === $h) ? 0 : $costCache[$rId][$h2]);
            $new = $new1 + (($h2 === $h) ? 0 : $new2);
            $delta = $new - $old;

            if ($delta <= 0 || (mt_rand() / mt_getrandmax()) < exp(-$delta / $temp)) {
                if ($A) $tPlace($A['ptk'], $rId, $h2, $b);
                if ($B) $tPlace($B['ptk'], $rId, $h, $a);
                $costCache[$rId][$h] = $new1;
                $costCache[$rId][$h2] = $new2;
                $total += $delta;
            } else {
                $cell[$rId][$h][$a] = $A;
                $cell[$rId][$h2][$b] = $B;
                if ($A) $tPlace($A['ptk'], $rId, $h, $a);
                if ($B) $tPlace($B['ptk'], $rId, $h2, $b);
            }
        }

        $fmt = fn($t) => strlen((string) $t) === 5 ? $t . ':00' : $t;
        $rebuilt = [];
        $singleJp = 0;
        $split = 0;
        foreach ($rombelIds as $rId) {
            foreach ($hariList as $h) {
                $cur = null;
                $seen = [];
                $flush = function () use (&$cur, &$rebuilt, &$singleJp, &$split, &$seen, $templates, $slots, $fmt, $rId, $h) {
                    if (!$cur) return;
                    $row = $templates[$cur['pid']];
                    $row['hari'] = $h;
                    $row['jam_ke_mulai'] = $cur['start'];
                    $row['jam_ke_selesai'] = $cur['end'];
                    $row['jam_mulai'] = $fmt($slots[$cur['start']]['mulai'] ?? $row['jam_mulai']);
                    $row['jam_selesai'] = $fmt($slots[$cur['end']]['selesai'] ?? $row['jam_selesai']);
                    $row['keterangan'] = 'Auto-Generated';
                    $rebuilt[] = $row;
                    if ($cur['start'] === $cur['end']) $singleJp++;
                    if (isset($seen[$cur['pid']])) $split++;
                    $seen[$cur['pid']] = true;
                    $cur = null;
                };
                foreach ($validK[$rId][$h] ?? [] as $k) {
                    $c = $cell[$rId][$h][$k] ?? null;
                    if ($c === null) { $flush(); continue; }
                    if ($cur && $cur['pid'] === $c['pid'] && $k === $cur['end'] + 1) {
                        $cur['end'] = $k;
                    } else {
                        $flush();
                        $cur = ['pid' => $c['pid'], 'start' => $k, 'end' => $k];
                    }
                }
                $flush();
            }
        }

        $batchInserts = $rebuilt;

        return ['single_jp' => $singleJp, 'split' => $split];
    }
}
