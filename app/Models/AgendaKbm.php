<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AgendaKbm extends Model
{
    use HasFactory;

    protected $table = 'agenda_kbm';

    protected $fillable = [
        'presensi_mengajar_id',
        'jadwal_kbm_id',
        'ptk_id',
        'rombongan_belajar_id',
        'pembelajaran_id',
        'mata_pelajaran_id',
        'nama_mata_pelajaran',
        'tanggal',
        'hari',
        'jam_ke_mulai',
        'jam_ke_selesai',
        'pertemuan_ke',
        'materi_pokok',
        'uraian_kegiatan',
        'penugasan',
        'status_kbm',
        'hambatan_catatan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jam_ke_mulai' => 'integer',
        'jam_ke_selesai' => 'integer',
        'pertemuan_ke' => 'integer',
    ];

    public const STATUS_MAP = [
        'Terlaksana' => ['badge' => 'badge-success', 'icon' => 'fa-check-double'],
        'Sebagian'   => ['badge' => 'badge-warning', 'icon' => 'fa-hourglass-half'],
        'Tertunda'   => ['badge' => 'badge-danger',  'icon' => 'fa-clock-rotate-left'],
        'Digantikan' => ['badge' => 'badge-outline', 'icon' => 'fa-user-group'],
    ];

    public function getStatusBadgeAttribute(): string
    {
        $info = self::STATUS_MAP[$this->status_kbm] ?? ['badge' => 'badge-outline', 'icon' => 'fa-info-circle'];
        return "<span class=\"badge {$info['badge']}\"><i class=\"fas {$info['icon']} me-1\"></i>{$this->status_kbm}</span>";
    }

    public function getNamaRombelAttribute(): string
    {
        return DB::table('rombongan_belajar')
            ->where('rombongan_belajar_id', $this->rombongan_belajar_id)
            ->value('nama') ?? ($this->rombongan_belajar_id ?: '-');
    }

    public function getNamaGuruAttribute(): string
    {
        return DB::table('gtk')
            ->where('ptk_id', $this->ptk_id)
            ->value('nama') ?? '-';
    }

    public function jadwal()
    {
        return $this->belongsTo(JadwalKbm::class, 'jadwal_kbm_id');
    }

    public function presensiMengajar()
    {
        return $this->belongsTo(PresensiMengajar::class, 'presensi_mengajar_id');
    }

    /**
     * Hitung nomor pertemuan ke berikutnya untuk kelas dan mapel tertentu
     */
    public static function getNextPertemuanKe(string $rombonganBelajarId, ?string $pembelajaranId = null): int
    {
        $query = self::where('rombongan_belajar_id', $rombonganBelajarId);
        if ($pembelajaranId) {
            $query->where('pembelajaran_id', $pembelajaranId);
        }
        $max = $query->max('pertemuan_ke');
        return ($max ? (int) $max : 0) + 1;
    }
}
