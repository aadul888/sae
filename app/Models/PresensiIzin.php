<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PresensiIzin extends Model
{
    use HasFactory;

    protected $table = 'presensi_izin';

    protected $fillable = [
        'peserta_didik_id',
        'nisn',
        'rombongan_belajar_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'jenis',
        'alasan',
        'lampiran_path',
        'status',
        'disetujui_oleh',
        'catatan_petugas',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
    ];

    protected $appends = [
        'lampiran_url',
        'jenis_label',
        'status_badge',
    ];

    public function getLampiranUrlAttribute(): ?string
    {
        if (empty($this->lampiran_path)) {
            return null;
        }
        return asset('storage/' . ltrim($this->lampiran_path, '/'));
    }

    public function getJenisLabelAttribute(): string
    {
        return match ($this->jenis) {
            'sakit' => 'Sakit',
            'dispen' => 'Dispensasi',
            default => 'Izin',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'disetujui' => '<span class="badge badge-success"><i class="fas fa-check-circle me-1"></i> Disetujui</span>',
            'ditolak' => '<span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i> Ditolak</span>',
            default => '<span class="badge badge-warning"><i class="fas fa-hourglass-half me-1"></i> Menunggu</span>',
        };
    }

    /**
     * Terapkan persetujuan izin ke log presensi_harian pada rentang tanggal
     */
    public function applyToDailyAttendance(string $verifiedBy): void
    {
        $startDate = Carbon::parse($this->tanggal_mulai);
        $endDate = Carbon::parse($this->tanggal_selesai);
        $statusCode = match ($this->jenis) {
            'sakit' => 'S',
            'dispen' => 'D',
            default => 'I',
        };

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->toDateString();

            // Abaikan jika hari libur sekolah dari kalender pendidikan
            if (KalenderPendidikan::isLibur($dateStr, 'pd')) {
                continue;
            }

            PresensiHarian::updateOrCreate(
                [
                    'peserta_didik_id' => $this->peserta_didik_id,
                    'tanggal' => $dateStr,
                ],
                [
                    'nisn' => $this->nisn,
                    'rombongan_belajar_id' => $this->rombongan_belajar_id,
                    'status' => $statusCode,
                    'keterangan' => $this->alasan,
                    'lampiran_dokumen' => $this->lampiran_path,
                    'verified_by' => $verifiedBy,
                ]
            );
        }
    }
}
