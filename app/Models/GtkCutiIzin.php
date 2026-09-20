<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtkCutiIzin extends Model
{
    use HasFactory;

    protected $table = 'gtk_cuti_izin';

    protected $fillable = [
        'ptk_id',
        'jenis',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari',
        'keperluan',
        'file_pendukung',
        'status',
        'catatan_pimpinan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function gtk()
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'ptk_id');
    }
}
