<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtkKgbTracker extends Model
{
    use HasFactory;

    protected $table = 'gtk_kgb_tracker';

    protected $fillable = [
        'ptk_id',
        'nomor_sk_terakhir',
        'tgl_sk_terakhir',
        'tmt_lama',
        'tmt_baru_target',
        'gaji_pokok_lama',
        'gaji_pokok_baru',
        'mkg_tahun',
        'mkg_bulan',
        'status_usulan',
        'nomor_sk_baru',
        'file_sk_baru',
        'catatan',
    ];

    protected $casts = [
        'tgl_sk_terakhir' => 'date',
        'tmt_lama' => 'date',
        'tmt_baru_target' => 'date',
        'gaji_pokok_lama' => 'decimal:2',
        'gaji_pokok_baru' => 'decimal:2',
    ];

    public function gtk()
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'ptk_id');
    }
}
