<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeknisiWorkOrder extends Model
{
    use HasFactory;

    protected $table = 'teknisi_work_order';

    protected $fillable = [
        'nomor_wo',
        'tanggal',
        'lokasi_unit',
        'kategori_perbaikan',
        'deskripsi_kerusakan',
        'tingkat_urgensi',
        'pelapor_nama',
        'teknisi_ptk_id',
        'tgl_selesai',
        'tindakan_perbaikan',
        'estimasi_biaya_part',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tgl_selesai' => 'date',
        'estimasi_biaya_part' => 'decimal:2',
    ];

    public function teknisi()
    {
        return $this->belongsTo(Gtk::class, 'teknisi_ptk_id', 'ptk_id');
    }
}
