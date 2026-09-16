<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PresensiMapel extends Model
{
    use HasFactory;

    protected $table = 'presensi_mapel';

    protected $fillable = [
        'rombongan_belajar_id',
        'pembelajaran_id',
        'ptk_id',
        'mata_pelajaran_id',
        'nama_mata_pelajaran',
        'peserta_didik_id',
        'nisn',
        'tanggal',
        'jam_ke',
        'status',
        'keterangan',
        'agenda_kelas_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'agenda_kelas_id' => 'integer',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
    ];

    public const STATUS_LABELS = [
        'H' => 'Hadir',
        'T' => 'Terlambat',
        'I' => 'Izin',
        'S' => 'Sakit',
        'A' => 'Alpha',
        'D' => 'Dispensasi',
    ];

    public const STATUS_BADGES = [
        'H' => '<span class="badge badge-success"><i class="fas fa-check-circle me-1"></i> Hadir</span>',
        'T' => '<span class="badge badge-warning"><i class="fas fa-clock me-1"></i> Terlambat</span>',
        'I' => '<span class="badge badge-primary"><i class="fas fa-file-signature me-1"></i> Izin</span>',
        'S' => '<span class="badge" style="background: rgba(139,92,246,0.15); color: #8b5cf6;"><i class="fas fa-notes-medical me-1"></i> Sakit</span>',
        'A' => '<span class="badge badge-danger"><i class="fas fa-times-circle me-1"></i> Alpha</span>',
        'D' => '<span class="badge badge-info"><i class="fas fa-award me-1"></i> Dispen</span>',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ($this->status ?? '-');
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? ('<span class="badge">' . ($this->status ?? '-') . '</span>');
    }

    public function pesertaDidik()
    {
        return $this->belongsTo(DB::table('peserta_didik'), 'peserta_didik_id', 'peserta_didik_id');
    }

    public function rombel()
    {
        return $this->belongsTo(DB::table('rombongan_belajar'), 'rombongan_belajar_id', 'rombongan_belajar_id');
    }
}
