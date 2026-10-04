<?php

namespace App\Services;

/**
 * Memoles susunan jadwal KBM yang sudah bebas bentrok: sesi per rombel dipindah/ditukar/digabung agar tidak ada sesi 1 JP
 * dan satu mapel tidak terpecah di hari yang sama, tanpa pernah melewati jam istirahat/kegiatan rutin (dibatasi segmen slot).
 * Bentrok guru diperlakukan sebagai pelanggaran keras; hasil hanya dikembalikan bila pelanggaran keras = 0.
 */
class BlockTimetableSolver
{
    private const W_HARD = 6;
    private const W_SINGLE = 2;
    private const W_DUP = 3;
    private const W_CAP = 2;

    /**
     * @param array $segmentsByRombel [rId => [['day'=>string,'start'=>int,'size'=>int], ...]]
     * @param array $blocks           sesi awal: ['rId','pid','ptk','len','day','start','allow','cap']
     * @param array $ext              [ptk][day][slot] = true (slot guru terpakai di luar sesi ini)
     * @param array $prefs            [ptk] = ['off'=>[hari], 'unavail'=>[[hari,mulai,selesai]], 'max'=>?int]
     * @return array{ok:bool,hard:int,soft:int,iterations:int,items:array,reason:?string}
     */
    public function solve(array $segmentsByRombel, array $blocks, array $ext, array $prefs, int $seed, float $budget): array
    {
        mt_srand($seed);
        $fail = fn(string $why) => ['ok' => false, 'hard' => PHP_INT_MAX, 'soft' => 0, 'iterations' => 0, 'items' => [], 'reason' => $why];

        $dayIdx = [];
        $segDay = $segDi = $segStart = $segSize = $segRombel = $segsOf = [];
        $si = 0;
        foreach ($segmentsByRombel as $rId => $list) {
            foreach ($list as $s) {
                $dayIdx[$s['day']] ??= count($dayIdx);
                $segDay[$si] = $s['day'];
                $segDi[$si] = $dayIdx[$s['day']];
                $segStart[$si] = $s['start'];
                $segSize[$si] = $s['size'];
                $segRombel[$si] = $rId;
                $segsOf[$rId][] = $si;
                $si++;
            }
        }
        $segCount = $si;

        $ptkIdx = [];
        $ptkName = [];
        $pidIdx = [];
        $blkLen = $blkPi = $blkPx = $blkAllow = $blkCap = $blkPid = [];
        foreach ($blocks as $i => $b) {
            if ($b['ptk'] !== null && !isset($ptkIdx[$b['ptk']])) {
                $ptkIdx[$b['ptk']] = count($ptkIdx);
                $ptkName[] = $b['ptk'];
            }
            $pidIdx[$b['pid']] ??= count($pidIdx);
            $blkLen[$i] = $b['len'];
            $blkPi[$i] = $b['ptk'] === null ? -1 : $ptkIdx[$b['ptk']];
            $blkPx[$i] = $pidIdx[$b['pid']];
            $blkPid[$i] = $b['pid'];
            $blkAllow[$i] = $b['allow'] ?? 1;
            $blkCap[$i] = $b['cap'] ?? 99;
        }
        $nextId = count($blocks);

        $newDummy = function () use (&$blkLen, &$blkPi, &$blkPx, &$blkPid, &$blkAllow, &$blkCap, &$nextId): int {
            $blkLen[$nextId] = 1;
            $blkPi[$nextId] = -1;
            $blkPx[$nextId] = -1;
            $blkPid[$nextId] = null;
            $blkAllow[$nextId] = 1;
            $blkCap[$nextId] = 1;
            return $nextId++;
        };

        $perSeg = [];
        foreach ($blocks as $i => $b) {
            $found = false;
            foreach ($segsOf[$b['rId']] ?? [] as $s) {
                if ($segDay[$s] === $b['day'] && $b['start'] >= $segStart[$s] && $b['start'] + $b['len'] <= $segStart[$s] + $segSize[$s]) {
                    $perSeg[$s][] = [$b['start'], $i];
                    $found = true;
                    break;
                }
            }
            if (!$found) return $fail('Sesi awal berada di luar segmen slot');
        }
        $segBlocks = [];
        for ($s = 0; $s < $segCount; $s++) {
            $list = $perSeg[$s] ?? [];
            usort($list, fn($x, $y) => $x[0] <=> $y[0]);
            $pos = $segStart[$s];
            $end = $segStart[$s] + $segSize[$s];
            $row = [];
            foreach ($list as [$st, $i]) {
                for (; $pos < $st; $pos++) $row[] = $newDummy();
                $row[] = $i;
                $pos += $blkLen[$i];
            }
            for (; $pos < $end; $pos++) $row[] = $newDummy();
            $segBlocks[$s] = $row;
        }

        $maxDay = [];
        foreach ($prefs as $ptk => $p) {
            if (!empty($p['max']) && isset($ptkIdx[$ptk])) $maxDay[$ptkIdx[$ptk]] = (int) $p['max'];
        }
        $isBad = function (int $pi, string $day, int $slot) use ($ext, $prefs, $ptkName): bool {
            $ptk = $ptkName[$pi];
            if (!empty($ext[$ptk][$day][$slot])) return true;
            $p = $prefs[$ptk] ?? null;
            if (!$p) return false;
            if (!empty($p['off']) && in_array($day, $p['off'], true)) return true;
            foreach ($p['unavail'] ?? [] as [$d, $from, $to]) {
                if ($d === $day && $slot >= $from && $slot <= $to) return true;
            }
            return false;
        };

        $slotBad = [];
        $occ = [];
        $dayCnt = [];
        $pidDay = [];
        $hard = 0;
        $soft = 0;

        // Tambah (+1) / lepas (-1) kontribusi satu segmen ke seluruh penghitung biaya.
        $apply = function (int $s, int $sign) use (&$segBlocks, &$blkLen, &$blkPi, &$blkPx, &$blkAllow, &$blkCap, &$segDay, &$segDi, &$segStart, &$occ, &$dayCnt, &$pidDay, &$hard, &$soft, &$slotBad, &$maxDay, $isBad): void {
            $k = $segStart[$s];
            $day = $segDay[$s];
            $di = $segDi[$s];
            $runPx = -1;
            $runLen = 0;
            $runAllow = 1;
            $runCap = 99;

            foreach ($segBlocks[$s] as $b) {
                $len = $blkLen[$b];
                $pi = $blkPi[$b];
                $px = $blkPx[$b];

                if ($px >= 0 && $px === $runPx) {
                    $runLen += $len;
                } else {
                    if ($runPx >= 0) {
                        if ($runLen === 1) $soft += $sign * self::W_SINGLE;
                        if ($runLen > $runCap) $soft += $sign * self::W_CAP;
                        $pk = $runPx * 8 + $di;
                        if ($sign > 0) {
                            if (($pidDay[$pk] ?? 0) >= $runAllow) $soft += self::W_DUP;
                            $pidDay[$pk] = ($pidDay[$pk] ?? 0) + 1;
                        } else {
                            $pidDay[$pk]--;
                            if ($pidDay[$pk] >= $runAllow) $soft -= self::W_DUP;
                        }
                    }
                    $runPx = $px;
                    $runLen = $len;
                    $runAllow = $blkAllow[$b];
                    $runCap = $blkCap[$b];
                }

                if ($pi >= 0) {
                    $dk = $pi * 8 + $di;
                    $base = $dk * 32;
                    $max = $maxDay[$pi] ?? null;
                    $c = $dayCnt[$dk] ?? 0;
                    for ($i = 0; $i < $len; $i++) {
                        $key = $base + $k + $i;
                        if (!isset($slotBad[$key])) $slotBad[$key] = $isBad($pi, $day, $k + $i) ? 1 : 0;
                        if ($sign > 0) {
                            if (($occ[$key] ?? 0) > 0) $hard++;
                            $occ[$key] = ($occ[$key] ?? 0) + 1;
                            $hard += $slotBad[$key];
                            if ($max !== null && $c >= $max) $hard++;
                            $c++;
                        } else {
                            $occ[$key]--;
                            if ($occ[$key] > 0) $hard--;
                            $hard -= $slotBad[$key];
                            $c--;
                            if ($max !== null && $c >= $max) $hard--;
                        }
                    }
                    $dayCnt[$dk] = $c;
                }
                $k += $len;
            }
            if ($runPx >= 0) {
                if ($runLen === 1) $soft += $sign * self::W_SINGLE;
                if ($runLen > $runCap) $soft += $sign * self::W_CAP;
                $pk = $runPx * 8 + $di;
                if ($sign > 0) {
                    if (($pidDay[$pk] ?? 0) >= $runAllow) $soft += self::W_DUP;
                    $pidDay[$pk] = ($pidDay[$pk] ?? 0) + 1;
                } else {
                    $pidDay[$pk]--;
                    if ($pidDay[$pk] >= $runAllow) $soft -= self::W_DUP;
                }
            }
        };

        for ($s = 0; $s < $segCount; $s++) $apply($s, 1);

        $segIsBad = function (int $s) use (&$segBlocks, &$blkLen, &$blkPi, &$blkPx, &$blkAllow, &$segDi, &$segStart, &$occ, &$pidDay, &$slotBad, &$dayCnt, &$maxDay): bool {
            $k = $segStart[$s];
            $di = $segDi[$s];
            foreach ($segBlocks[$s] as $b) {
                $len = $blkLen[$b];
                $pi = $blkPi[$b];
                if ($blkPx[$b] >= 0) {
                    if ($len === 1) return true;
                    if (($pidDay[$blkPx[$b] * 8 + $di] ?? 0) > $blkAllow[$b]) return true;
                }
                if ($pi >= 0) {
                    $dk = $pi * 8 + $di;
                    for ($i = 0; $i < $len; $i++) {
                        $key = $dk * 32 + $k + $i;
                        if (($occ[$key] ?? 0) > 1 || !empty($slotBad[$key])) return true;
                    }
                    if (isset($maxDay[$pi]) && ($dayCnt[$dk] ?? 0) > $maxDay[$pi]) return true;
                }
                $k += $len;
            }
            return false;
        };

        $workable = [];
        for ($s = 0; $s < $segCount; $s++) {
            if (count($segBlocks[$s]) >= 1) $workable[] = $s;
        }

        $best = null;
        $bestSoft = PHP_INT_MAX;
        if ($hard === 0) {
            $best = [$segBlocks, $blkLen];
            $bestSoft = $soft;
        }

        $start = microtime(true);
        $iter = 0;
        $temp = 1.0;
        $badSegs = [];
        $W = self::W_HARD;

        while ($workable && !($hard === 0 && $soft === 0)) {
            if ($iter % 1500 === 0) {
                $elapsed = microtime(true) - $start;
                if ($elapsed > $budget) break;
                $temp = max(0.15, 1.0 * (1 - $elapsed / $budget));
                $badSegs = [];
                foreach ($workable as $s) {
                    if ($segIsBad($s)) $badSegs[] = $s;
                }
            }
            $iter++;

            $s1 = ($badSegs && mt_rand(1, 100) <= 80) ? $badSegs[array_rand($badSegs)] : $workable[array_rand($workable)];
            $n1 = count($segBlocks[$s1]);
            $type = mt_rand(1, 100);
            $before = $hard * $W + $soft;
            $restoreLen = [];
            $newIds = 0;

            if ($type <= 15) {
                if ($n1 < 2) continue;
                $i = mt_rand(0, $n1 - 1);
                $j = mt_rand(0, $n1 - 1);
                if ($i === $j || $blkLen[$segBlocks[$s1][$i]] === $blkLen[$segBlocks[$s1][$j]] && $blkPx[$segBlocks[$s1][$i]] === $blkPx[$segBlocks[$s1][$j]]) continue;
                $old1 = $segBlocks[$s1];
                $apply($s1, -1);
                [$segBlocks[$s1][$i], $segBlocks[$s1][$j]] = [$segBlocks[$s1][$j], $segBlocks[$s1][$i]];
                $apply($s1, 1);
                $touched = [[$s1, $old1]];
            } else {
                $own = $segsOf[$segRombel[$s1]];
                $s2 = $own[array_rand($own)];
                if ($s2 === $s1) continue;
                $n2 = count($segBlocks[$s2]);
                $old1 = $segBlocks[$s1];
                $old2 = $segBlocks[$s2];

                if ($type <= 35) {
                    // Tukar dua blok berdurasi sama antar segmen.
                    $i = mt_rand(0, $n1 - 1);
                    $len = $blkLen[$old1[$i]];
                    $js = [];
                    for ($j = 0; $j < $n2; $j++) {
                        if ($blkLen[$old2[$j]] === $len) $js[] = $j;
                    }
                    if (!$js) continue;
                    $j = $js[array_rand($js)];
                    $apply($s1, -1);
                    $apply($s2, -1);
                    [$segBlocks[$s1][$i], $segBlocks[$s2][$j]] = [$old2[$j], $old1[$i]];
                } elseif ($type <= 60) {
                    // Susun ulang acak isi dua segmen (jumlah durasi tetap).
                    $union = array_merge($old1, $old2);
                    $target = $segSize[$s1];
                    $newA = null;
                    for ($t = 0; $t < 6 && $newA === null; $t++) {
                        shuffle($union);
                        $sum = 0;
                        foreach ($union as $idx => $b) {
                            $sum += $blkLen[$b];
                            if ($sum === $target) { $newA = $idx + 1; break; }
                            if ($sum > $target) break;
                        }
                    }
                    if ($newA === null) continue;
                    $apply($s1, -1);
                    $apply($s2, -1);
                    $segBlocks[$s1] = array_slice($union, 0, $newA);
                    $segBlocks[$s2] = array_slice($union, $newA);
                } elseif ($type <= 80) {
                    // Pecah: blok (>= 2 JP) di s1 melepas 1 JP ke s2 dan menerima satu blok 1 JP dari s2.
                    $is = [];
                    foreach ($old1 as $idx => $b) {
                        if ($blkPx[$b] >= 0 && $blkLen[$b] >= 2) $is[] = $idx;
                    }
                    $js = [];
                    foreach ($old2 as $idx => $b) {
                        if ($blkLen[$b] === 1) $js[] = $idx;
                    }
                    if (!$is || !$js) continue;
                    $i = $is[array_rand($is)];
                    $j = $js[array_rand($js)];
                    $b = $old1[$i];
                    $c = $old2[$j];
                    $apply($s1, -1);
                    $apply($s2, -1);
                    $restoreLen[$b] = $blkLen[$b];
                    $blkLen[$b]--;
                    $p = $nextId++;
                    $newIds = 1;
                    $blkLen[$p] = 1;
                    $blkPi[$p] = $blkPi[$b];
                    $blkPx[$p] = $blkPx[$b];
                    $blkPid[$p] = $blkPid[$b];
                    $blkAllow[$p] = $blkAllow[$b];
                    $blkCap[$p] = $blkCap[$b];
                    $segBlocks[$s2][$j] = $p;
                    array_splice($segBlocks[$s1], mt_rand(0, $n1 - 1), 0, [$c]);
                } else {
                    // Gabung: blok 1 JP di s1 digabung ke blok sejenis di s2; s1 menerima satu blok 1 JP lain dari s2.
                    $is = [];
                    foreach ($old1 as $idx => $b) {
                        if ($blkPx[$b] >= 0 && $blkLen[$b] === 1) $is[] = $idx;
                    }
                    if (!$is) continue;
                    $i = $is[array_rand($is)];
                    $p = $old1[$i];
                    $js = [];
                    $cs = [];
                    foreach ($old2 as $idx => $b) {
                        if ($blkPx[$b] === $blkPx[$p] && $blkLen[$b] < $blkCap[$b]) $js[] = $idx;
                        if ($blkLen[$b] === 1) $cs[] = $idx;
                    }
                    if (!$js || !$cs) continue;
                    $j = $js[array_rand($js)];
                    $b = $old2[$j];
                    $cj = $cs[array_rand($cs)];
                    $c = $old2[$cj];
                    if ($c === $b) continue;
                    $apply($s1, -1);
                    $apply($s2, -1);
                    $restoreLen[$b] = $blkLen[$b];
                    $blkLen[$b]++;
                    $segBlocks[$s1][$i] = $c;
                    unset($segBlocks[$s2][$cj]);
                    $segBlocks[$s2] = array_values($segBlocks[$s2]);
                }
                $apply($s1, 1);
                $apply($s2, 1);
                $touched = [[$s1, $old1], [$s2, $old2]];
            }

            $delta = ($hard * $W + $soft) - $before;
            if ($delta > 0 && (mt_rand() / mt_getrandmax()) >= exp(-$delta / $temp)) {
                foreach ($touched as [$s, $o]) $apply($s, -1);
                foreach ($restoreLen as $b => $l) $blkLen[$b] = $l;
                foreach ($touched as [$s, $o]) $segBlocks[$s] = $o;
                foreach ($touched as [$s, $o]) $apply($s, 1);
                $nextId -= $newIds;
            } elseif ($hard === 0 && $soft < $bestSoft) {
                $bestSoft = $soft;
                $best = [$segBlocks, $blkLen];
            }
        }

        if ($best === null) {
            return ['ok' => false, 'hard' => $hard, 'soft' => $soft, 'iterations' => $iter, 'items' => [], 'reason' => null];
        }

        [$finalBlocks, $finalLen] = $best;
        $items = [];
        for ($s = 0; $s < $segCount; $s++) {
            $k = $segStart[$s];
            $cur = null;
            foreach ($finalBlocks[$s] as $b) {
                $len = $finalLen[$b];
                $pid = $blkPid[$b];
                if ($pid !== null && $cur && $cur['pid'] === $pid) {
                    $cur['len'] += $len;
                } else {
                    if ($cur) $items[] = $cur;
                    $cur = $pid === null ? null : ['rId' => $segRombel[$s], 'pid' => $pid, 'day' => $segDay[$s], 'start' => $k, 'len' => $len];
                }
                $k += $len;
            }
            if ($cur) $items[] = $cur;
        }

        return ['ok' => true, 'hard' => 0, 'soft' => $bestSoft, 'iterations' => $iter, 'items' => $items, 'reason' => null];
    }
}
