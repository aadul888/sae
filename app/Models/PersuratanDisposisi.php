<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersuratanDisposisi extends Model
{
    use HasFactory;

    protected $table = 'persuratan_disposisi';

    protected $fillable = [
        'persuratan_id',
        'disposisi_dari',
        'disposisi_ke',
        'ptk_id_tujuan',
        'instruksi',
        'catatan',
        'tanggal_disposisi',
        'status',
    ];

    protected $casts = [
        'tanggal_disposisi' => 'date',
    ];

    public function surat()
    {
        return $this->belongsTo(Persuratan::class, 'persuratan_id');
    }

    public function ptk()
    {
        return $this->belongsTo(Gtk::class, 'ptk_id_tujuan', 'ptk_id');
    }
}
