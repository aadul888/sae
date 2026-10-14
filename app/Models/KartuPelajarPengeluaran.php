<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KartuPelajarPengeluaran extends Model
{
    use HasFactory;

    protected $table = 'kartu_pelajar_pengeluaran';

    protected $fillable = [
        'judul',
        'kategori',
        'nominal',
        'tanggal',
        'keterangan',
        'bukti_nota_path',
        'petugas',
    ];

    protected $casts = [
        'nominal' => 'integer',
        'tanggal' => 'date',
    ];

    public static function getKategoriList(): array
    {
        return [
            'bahan_pvc'      => 'Bahan Kartu PVC / RFID Kosong',
            'ribbon_stiker'  => 'Ribbon Printer & Stiker Pelindung',
            'alat_mesin'     => 'Pengadaan Alat & Mesin Cetak',
            'pemeliharaan'   => 'Servis & Pemeliharaan Alat Cetak',
            'operasional'    => 'Operasional & Kemasan Kartu',
            'lainnya'        => 'Pengeluaran Lainnya',
        ];
    }
}
