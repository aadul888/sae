<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persuratan extends Model
{
    use HasFactory;

    protected $table = 'persuratan';

    protected $fillable = [
        'nomor_surat',
        'kode_indeks',
        'jenis_surat',
        'perihal',
        'pengirim_asal',
        'tujuan_penerima',
        'tanggal_surat',
        'tanggal_diterima',
        'status',
        'file_path',
        'file_size',
        'file_name_original',
        'keterangan',
        'sarpras_aset_id',
        'created_by',
    ];

    protected $casts = [
        'tanggal_surat' => 'date:Y-m-d',
        'tanggal_diterima' => 'date:Y-m-d',
    ];

    public const STATUS_MAP = [
        'draf' => [
            'label' => 'Draf',
            'badge' => 'badge-outline',
            'icon'  => 'fa-file-lines',
        ],
        'menunggu_disposisi' => [
            'label' => 'Menunggu Disposisi',
            'badge' => 'badge-warning',
            'icon'  => 'fa-hourglass-half',
        ],
        'diproses' => [
            'label' => 'Sedang Diproses',
            'badge' => 'badge-info',
            'icon'  => 'fa-arrows-rotate',
        ],
        'selesai' => [
            'label' => 'Selesai',
            'badge' => 'badge-success',
            'icon'  => 'fa-check-circle',
        ],
        'diarsipkan' => [
            'label' => 'Diarsipkan',
            'badge' => 'badge-secondary',
            'icon'  => 'fa-box-archive',
        ],
    ];

    public const JENIS_MAP = [
        'masuk'      => ['label' => 'Surat Masuk', 'color' => '#2563eb', 'icon' => 'fa-inbox'],
        'keluar'     => ['label' => 'Surat Keluar', 'color' => '#16a34a', 'icon' => 'fa-paper-plane'],
        'disposisi'  => ['label' => 'Disposisi', 'color' => '#d97706', 'icon' => 'fa-share-from-square'],
        'keputusan'  => ['label' => 'SK / Keputusan', 'color' => '#7c3aed', 'icon' => 'fa-stamp'],
        'tugas'      => ['label' => 'Surat Tugas', 'color' => '#0891b2', 'icon' => 'fa-briefcase'],
    ];

    public function getStatusBadgeAttribute(): string
    {
        $info = self::STATUS_MAP[$this->status] ?? [
            'label' => ucfirst($this->status),
            'badge' => 'badge-outline',
            'icon'  => 'fa-info-circle',
        ];
        return "<span class=\"badge {$info['badge']}\"><i class=\"fas {$info['icon']} me-1\"></i>{$info['label']}</span>";
    }

    public function getJenisBadgeAttribute(): string
    {
        $info = self::JENIS_MAP[$this->jenis_surat] ?? [
            'label' => ucfirst($this->jenis_surat),
            'color' => '#64748b',
            'icon'  => 'fa-file',
        ];
        return "<span class=\"badge\" style=\"background-color: {$info['color']}15; color: {$info['color']}; border: 1px solid {$info['color']}30;\"><i class=\"fas {$info['icon']} me-1\"></i>{$info['label']}</span>";
    }

    public function disposisi()
    {
        return $this->hasMany(PersuratanDisposisi::class, 'persuratan_id');
    }

    public function latestDisposisi()
    {
        return $this->hasOne(PersuratanDisposisi::class, 'persuratan_id')->latestOfMany();
    }

    public function suratKeterangan()
    {
        return $this->hasOne(SuratKeteranganPd::class, 'nomor_surat', 'nomor_surat');
    }

    public function sarprasAset()
    {
        return $this->belongsTo(SarprasAset::class, 'sarpras_aset_id');
    }
}
