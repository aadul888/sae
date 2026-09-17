<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class JadwalKbm extends Model
{
    use HasFactory;

    protected $table = 'jadwal_kbm';

    protected $fillable = [
        'rombongan_belajar_id',
        'pembelajaran_id',
        'ptk_id',
        'mata_pelajaran_id',
        'nama_mata_pelajaran',
        'hari',
        'jam_ke_mulai',
        'jam_ke_selesai',
        'jam_mulai',
        'jam_selesai',
        'ruangan',
        'semester_id',
        'is_active',
        'keterangan',
    ];

    protected $casts = [
        'jam_ke_mulai' => 'integer',
        'jam_ke_selesai' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'jam_ke_range',
        'jam_waktu_range',
        'durasi_jp',
    ];

    public const HARI_LIST = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public function getJamKeRangeAttribute(): string
    {
        if ($this->jam_ke_mulai === $this->jam_ke_selesai) {
            return "Jam ke-{$this->jam_ke_mulai}";
        }
        return "Jam ke-{$this->jam_ke_mulai} s.d {$this->jam_ke_selesai}";
    }

    public function getJamWaktuRangeAttribute(): string
    {
        $start = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) : '-';
        $end = $this->jam_selesai ? substr($this->jam_selesai, 0, 5) : '-';
        return "{$start} - {$end}";
    }

    public function getDurasiJpAttribute(): int
    {
        $diff = ($this->jam_ke_selesai ?? 1) - ($this->jam_ke_mulai ?? 1) + 1;
        return max(1, $diff);
    }

    /**
     * Memeriksa potensi bentrok jadwal (Guru atau Rombel)
     * Overlap waktu: (startA < endB) AND (endA > startB)
     */
    /**
     * Memeriksa potensi bentrok jadwal (Ketersediaan Guru, Bentrok Guru, Bentrok Rombel, Bentrok Ruangan)
     * Overlap waktu: (startA < endB) AND (endA > startB)
     */
    public static function checkConflict($ptkId, $rombelId, $hari, $jamMulai, $jamSelesai, $excludeId = null, $ruangan = null, $jamKeMulai = 1, $jamKeSelesai = 1): array
    {
        // 1. Cek Ketersediaan Guru (Off-Days / Jam Berhalangan)
        if (!empty($ptkId)) {
            $prefCheck = JadwalGuruPreferensi::isGuruAvailable($ptkId, $hari, (int) $jamKeMulai, (int) $jamKeSelesai);
            if (!$prefCheck['available']) {
                return [
                    'has_conflict' => true,
                    'type' => 'guru_unavailable',
                    'message' => "Peringatan Ketersediaan Guru: " . $prefCheck['reason'],
                    'conflict_with' => null,
                ];
            }
        }

        // Pastikan format jam HH:MM:SS
        $start = strlen($jamMulai) === 5 ? "{$jamMulai}:00" : $jamMulai;
        $end = strlen($jamSelesai) === 5 ? "{$jamSelesai}:00" : $jamSelesai;

        // 2. Cek Bentrok Guru (PTK yang sama mengajar di kelas lain pada jam yang sama)
        if (!empty($ptkId)) {
            $guruConflictQuery = self::where('ptk_id', $ptkId)
                ->where('hari', $hari)
                ->where('is_active', true)
                ->where(function ($q) use ($start, $end) {
                    $q->where('jam_mulai', '<', $end)
                      ->where('jam_selesai', '>', $start);
                });

            if ($excludeId) {
                $guruConflictQuery->where('id', '!=', $excludeId);
            }

            $guruConflict = $guruConflictQuery->first();
            if ($guruConflict) {
                $rombelName = DB::table('rombongan_belajar')
                    ->where('rombongan_belajar_id', $guruConflict->rombongan_belajar_id)
                    ->value('nama') ?? 'kelas lain';

                $guruName = DB::table('gtk')
                    ->where('ptk_id', $ptkId)
                    ->value('nama') ?? 'Guru ini';

                return [
                    'has_conflict' => true,
                    'type' => 'guru',
                    'message' => "Bentrok Jadwal Guru: {$guruName} sudah memiliki jadwal mengajar di {$rombelName} ({$guruConflict->nama_mata_pelajaran}) pada hari {$hari} pukul " . substr($guruConflict->jam_mulai, 0, 5) . " - " . substr($guruConflict->jam_selesai, 0, 5) . "!",
                    'conflict_with' => $guruConflict,
                ];
            }
        }

        // 3. Cek Bentrok Rombel (Kelas yang sama memiliki 2 mapel/guru pada jam yang sama)
        if (!empty($rombelId)) {
            $rombelConflictQuery = self::where('rombongan_belajar_id', $rombelId)
                ->where('hari', $hari)
                ->where('is_active', true)
                ->where(function ($q) use ($start, $end) {
                    $q->where('jam_mulai', '<', $end)
                      ->where('jam_selesai', '>', $start);
                });

            if ($excludeId) {
                $rombelConflictQuery->where('id', '!=', $excludeId);
            }

            $rombelConflict = $rombelConflictQuery->first();
            if ($rombelConflict) {
                $rombelName = DB::table('rombongan_belajar')
                    ->where('rombongan_belajar_id', $rombelId)
                    ->value('nama') ?? 'Kelas';

                return [
                    'has_conflict' => true,
                    'type' => 'rombel',
                    'message' => "Bentrok Jadwal Kelas: {$rombelName} sudah memiliki jadwal {$rombelConflict->nama_mata_pelajaran} pada hari {$hari} pukul " . substr($rombelConflict->jam_mulai, 0, 5) . " - " . substr($rombelConflict->jam_selesai, 0, 5) . "!",
                    'conflict_with' => $rombelConflict,
                ];
            }
        }

        // 4. Cek Bentrok Ruangan (Lab / Bengkel / Fasilitas Bersama)
        if (!empty($ruangan) && trim($ruangan) !== '') {
            $roomConflictQuery = self::where('ruangan', trim($ruangan))
                ->where('hari', $hari)
                ->where('is_active', true)
                ->where(function ($q) use ($start, $end) {
                    $q->where('jam_mulai', '<', $end)
                      ->where('jam_selesai', '>', $start);
                });

            if ($excludeId) {
                $roomConflictQuery->where('id', '!=', $excludeId);
            }

            $roomConflict = $roomConflictQuery->first();
            if ($roomConflict) {
                $occupiedRombel = DB::table('rombongan_belajar')
                    ->where('rombongan_belajar_id', $roomConflict->rombongan_belajar_id)
                    ->value('nama') ?? 'kelas lain';

                return [
                    'has_conflict' => true,
                    'type' => 'ruangan',
                    'message' => "Bentrok Ruangan: Ruangan '{$ruangan}' sedang digunakan oleh {$occupiedRombel} ({$roomConflict->nama_mata_pelajaran}) pada hari {$hari} pukul " . substr($roomConflict->jam_mulai, 0, 5) . " - " . substr($roomConflict->jam_selesai, 0, 5) . "!",
                    'conflict_with' => $roomConflict,
                ];
            }
        }

        return [
            'has_conflict' => false,
            'type' => null,
            'message' => 'Jadwal valid dan tidak ada bentrok.',
            'conflict_with' => null,
        ];
    }
}
