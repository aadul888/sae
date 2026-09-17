<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalGuruPreferensi extends Model
{
    use HasFactory;

    protected $table = 'jadwal_guru_preferensi';

    protected $fillable = [
        'ptk_id',
        'hari_off',
        'jam_unavailable',
        'max_jp_per_hari',
        'keterangan',
    ];

    protected $casts = [
        'hari_off' => 'array',
        'jam_unavailable' => 'array',
        'max_jp_per_hari' => 'integer',
    ];

    public function gtk()
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'ptk_id');
    }

    /**
     * Cek apakah guru tersedia pada hari dan slot jam tertentu
     */
    public static function isGuruAvailable(string $ptkId, string $hari, int $jamKeMulai = 1, int $jamKeSelesai = 1): array
    {
        $pref = self::where('ptk_id', $ptkId)->first();
        if (!$pref) {
            return ['available' => true];
        }

        // Cek apakah hari ini adalah hari off
        if (!empty($pref->hari_off) && in_array($hari, $pref->hari_off, true)) {
            return [
                'available' => false,
                'reason'    => "Guru berhalangan mengajar pada hari {$hari} (" . ($pref->keterangan ?: 'Hari Off / Izin Rutin') . ").",
            ];
        }

        // Cek apakah jam spesifik berhalangan
        if (!empty($pref->jam_unavailable)) {
            foreach ($pref->jam_unavailable as $un) {
                if (($un['hari'] ?? '') === $hari) {
                    $unStart = (int) ($un['jam_ke_mulai'] ?? 1);
                    $unEnd   = (int) ($un['jam_ke_selesai'] ?? $unStart);

                    // Overlap check slot
                    if ($jamKeMulai <= $unEnd && $jamKeSelesai >= $unStart) {
                        return [
                            'available' => false,
                            'reason'    => "Guru berhalangan pada hari {$hari} jam ke-{$unStart} s/d {$unEnd} (" . ($un['alasan'] ?? 'Izin Khusus') . ").",
                        ];
                    }
                }
            }
        }

        return ['available' => true];
    }
}
