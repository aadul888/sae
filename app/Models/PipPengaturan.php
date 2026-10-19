<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PipPengaturan extends Model
{
    use HasFactory;

    protected $table = 'pip_pengaturan';

    protected $fillable = [
        'tahun',
        'fase',
        'tahun_pelajaran',
        'judul_usulan',
        'is_locked',
        'batas_waktu',
        'kunci_otomatis',
        'pesan_pengumuman',
        'updated_by',
    ];

    protected $casts = [
        'is_locked'      => 'boolean',
        'kunci_otomatis' => 'boolean',
        'batas_waktu'    => 'datetime',
    ];

    /**
     * Dapatkan data pengaturan PIP untuk tahun tertentu (singleton per tahun)
     */
    public static function getSettings(?string $tahun = null): self
    {
        $tahun = $tahun ?: (string) date('Y');
        $defaultFase = date('n') <= 6 ? 'fase_1' : 'fase_2';
        $defaultTp = class_exists(\App\Support\SemesterHelper::class)
            ? \App\Support\SemesterHelper::getActiveSemesterLabel()
            : ($tahun . '/' . ((int) $tahun + 1));
        $defaultJudul = "Usulan PIP Tahun {$tahun} — " . PipUsulan::getFaseLabel($defaultFase);

        if (!Schema::hasTable('pip_pengaturan')) {
            $dummy = new self();
            $dummy->tahun = $tahun;
            $dummy->fase = $defaultFase;
            $dummy->tahun_pelajaran = $defaultTp;
            $dummy->judul_usulan = $defaultJudul;
            $dummy->is_locked = false;
            $dummy->batas_waktu = Carbon::now()->addDays(7)->setTime(23, 59, 59);
            $dummy->kunci_otomatis = true;
            $dummy->pesan_pengumuman = 'Pengusulan peserta didik penerima Program Indonesia Pintar (PIP) dibuka untuk seluruh Wali Kelas. Mohon perhatikan batas waktu dan kuota masing-masing rombel.';
            return $dummy;
        }

        $record = self::where('tahun', $tahun)->first();

        if (!$record) {
            $record = self::create([
                'tahun'            => $tahun,
                'fase'             => $defaultFase,
                'tahun_pelajaran'  => $defaultTp,
                'judul_usulan'     => $defaultJudul,
                'is_locked'        => false,
                'batas_waktu'      => Carbon::now()->addDays(7)->setTime(23, 59, 59),
                'kunci_otomatis'   => true,
                'pesan_pengumuman' => 'Pengusulan peserta didik penerima Program Indonesia Pintar (PIP) dibuka untuk seluruh Wali Kelas. Mohon perhatikan batas waktu dan kuota masing-masing rombel.',
                'updated_by'       => 'Sistem',
            ]);
        } elseif (empty($record->judul_usulan) || empty($record->fase)) {
            $record->fase = $record->fase ?: $defaultFase;
            $record->tahun_pelajaran = $record->tahun_pelajaran ?: $defaultTp;
            $record->judul_usulan = $record->judul_usulan ?: $defaultJudul;
            $record->save();
        }

        return $record;
    }

    /**
     * Cek apakah pengusulan dikunci untuk Wali Kelas
     */
    public static function isLockedForWaliKelas(?string $tahun = null): bool
    {
        $settings = self::getSettings($tahun);

        if ($settings->is_locked) {
            return true;
        }

        if ($settings->kunci_otomatis && $settings->batas_waktu) {
            return Carbon::now()->greaterThanOrEqualTo($settings->batas_waktu);
        }

        return false;
    }

    /**
     * Ringkasan informasi status & perhitungan waktu mundur
     */
    public static function getStatusInfo(?string $tahun = null): array
    {
        $s = self::getSettings($tahun);
        $now = Carbon::now();

        $isManualLocked = (bool) $s->is_locked;
        $deadline = $s->batas_waktu ? Carbon::parse($s->batas_waktu) : null;
        $isExpired = false;
        $diffSeconds = 0;

        if ($deadline) {
            if ($now->greaterThanOrEqualTo($deadline)) {
                $isExpired = true;
                $diffSeconds = 0;
            } else {
                $diffSeconds = $now->diffInSeconds($deadline);
            }
        }

        $isClosed = $isManualLocked || ($s->kunci_otomatis && $isExpired);

        $statusType = 'open';
        $statusLabel = 'Pengusulan Dibuka';
        if ($isManualLocked) {
            $statusType = 'locked_manual';
            $statusLabel = 'Terkunci Manual oleh Admin';
        } elseif ($isExpired) {
            $statusType = 'expired';
            $statusLabel = 'Batas Waktu Berakhir';
        }

        $days = (int) floor($diffSeconds / 86400);
        $hours = (int) floor(($diffSeconds % 86400) / 3600);
        $minutes = (int) floor(($diffSeconds % 3600) / 60);
        $seconds = (int) ($diffSeconds % 60);

        $fase = $s->fase ?: (date('n') <= 6 ? 'fase_1' : 'fase_2');
        $faseLabel = PipUsulan::getFaseLabel($fase);
        $tp = $s->tahun_pelajaran ?: (class_exists(\App\Support\SemesterHelper::class) ? \App\Support\SemesterHelper::getActiveSemesterLabel() : null);
        $judul = $s->judul_usulan ?: ("Usulan PIP Tahun {$s->tahun} — " . $faseLabel);

        return [
            'tahun'              => $s->tahun,
            'fase'               => $fase,
            'fase_label'         => $faseLabel,
            'tahun_pelajaran'    => $tp,
            'judul_usulan'       => $judul,
            'is_locked'          => $isManualLocked,
            'kunci_otomatis'     => (bool) $s->kunci_otomatis,
            'is_expired'         => $isExpired,
            'is_closed'          => $isClosed,
            'status_type'        => $statusType,
            'status_label'       => $statusLabel,
            'deadline'           => $deadline ? $deadline->format('Y-m-d H:i:s') : null,
            'deadline_iso'       => $deadline ? $deadline->toISOString() : null,
            'deadline_formatted' => $deadline ? $deadline->translatedFormat('d F Y, H:i') . ' WIB' : 'Tidak ditentukan',
            'remaining_seconds'  => $diffSeconds,
            'countdown'          => [
                'days'    => $days,
                'hours'   => $hours,
                'minutes' => $minutes,
                'seconds' => $seconds,
            ],
            'pesan_pengumuman'   => $s->pesan_pengumuman,
            'updated_by'         => $s->updated_by,
            'updated_at'         => $s->updated_at ? Carbon::parse($s->updated_at)->translatedFormat('d F Y, H:i') : null,
        ];
    }
}
