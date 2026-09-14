<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KalenderPendidikan extends Model
{
    use HasFactory;

    protected $table = 'kalender_pendidikan';

    protected $fillable = [
        'nama_kegiatan',
        'tipe',
        'tanggal_mulai',
        'tanggal_selesai',
        'libur_pd',
        'libur_guru',
        'libur_tendik',
        'warna',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai'   => 'date:Y-m-d',
        'tanggal_selesai' => 'date:Y-m-d',
        'libur_pd'        => 'boolean',
        'libur_guru'      => 'boolean',
        'libur_tendik'    => 'boolean',
    ];

    public const TIPE_LABELS = [
        'libur_nasional'   => 'Libur Nasional',
        'libur_semester'   => 'Libur Semester / Akhir Tahun',
        'libur_khusus'     => 'Libur Khusus Satuan Pendidikan',
        'kegiatan_sekolah' => 'Kegiatan Sekolah / Upacara',
        'ujian_asesmen'    => 'Ujian & Asesmen',
        'hari_efektif'     => 'Hari Efektif Belajar',
    ];

    public function getTipeLabelAttribute(): string
    {
        return self::TIPE_LABELS[$this->tipe] ?? ucwords(str_replace('_', ' ', $this->tipe));
    }

    /**
     * Cek apakah suatu tanggal adalah hari libur untuk role tertentu (pd, guru, tendik)
     */
    public static function isLibur(string $date, string $role = 'pd'): bool
    {
        $column = match (strtolower($role)) {
            'guru' => 'libur_guru',
            'tendik' => 'libur_tendik',
            default => 'libur_pd',
        };

        return static::where('tanggal_mulai', '<=', $date)
            ->where('tanggal_selesai', '>=', $date)
            ->where($column, true)
            ->exists();
    }

    /**
     * Ambil seluruh agenda/kegiatan aktif pada rentang tanggal
     */
    public function scopeBetweenDates($query, string $startDate, string $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal_mulai', [$startDate, $endDate])
                ->orWhereBetween('tanggal_selesai', [$startDate, $endDate])
                ->orWhere(function ($sub) use ($startDate, $endDate) {
                    $sub->where('tanggal_mulai', '<=', $startDate)
                        ->where('tanggal_selesai', '>=', $endDate);
                });
        });
    }
}
