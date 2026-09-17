<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PresensiMengajar extends Model
{
    use HasFactory;

    protected $table = 'presensi_mengajar';

    protected $fillable = [
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
        'total_jp',
        'jam_masuk',
        'jam_keluar',
        'status',
        'guru_pengganti_ptk_id',
        'nama_guru_pengganti',
        'jumlah_siswa_hadir',
        'jumlah_siswa_tidak_hadir',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date:Y-m-d',
        'jam_ke_mulai' => 'integer',
        'jam_ke_selesai' => 'integer',
        'total_jp' => 'integer',
        'jumlah_siswa_hadir' => 'integer',
        'jumlah_siswa_tidak_hadir' => 'integer',
    ];

    public const STATUS_MAP = [
        'H' => ['label' => 'Hadir Mengajar', 'badge' => 'badge-success', 'icon' => 'fa-check-circle'],
        'I' => ['label' => 'Izin',           'badge' => 'badge-primary', 'icon' => 'fa-file-signature'],
        'S' => ['label' => 'Sakit',          'badge' => 'badge-warning', 'icon' => 'fa-notes-medical'],
        'T' => ['label' => 'Tugas Luar',     'badge' => 'badge-outline', 'icon' => 'fa-briefcase'],
        'D' => ['label' => 'Digantikan / Inval', 'badge' => 'badge-danger',  'icon' => 'fa-user-clock'],
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_MAP[$this->status]['label'] ?? ($this->status ?: 'Hadir Mengajar');
    }

    public function getStatusBadgeAttribute(): string
    {
        $info = self::STATUS_MAP[$this->status] ?? ['label' => $this->status, 'badge' => 'badge-outline', 'icon' => 'fa-clock'];
        return "<span class=\"badge {$info['badge']}\"><i class=\"fas {$info['icon']} me-1\"></i>{$info['label']}</span>";
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

    public function agenda()
    {
        return $this->hasOne(AgendaKbm::class, 'presensi_mengajar_id');
    }
}
