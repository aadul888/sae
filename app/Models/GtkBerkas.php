<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GtkBerkas extends Model
{
    use HasFactory;

    protected $table = 'gtk_berkas';

    protected $fillable = [
        'ptk_id',
        'jenis_dokumen',
        'judul_dokumen',
        'nomor_dokumen',
        'tanggal_dokumen',
        'tmt',
        'tst',
        'file_path',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_dokumen' => 'date',
        'tmt' => 'date',
        'tst' => 'date',
    ];

    public function gtk()
    {
        return $this->belongsTo(Gtk::class, 'ptk_id', 'ptk_id');
    }
}
