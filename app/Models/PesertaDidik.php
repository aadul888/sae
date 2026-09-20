<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaDidik extends Model
{
    protected $table = 'peserta_didik';
    protected $primaryKey = 'peserta_didik_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function meta()
    {
        return $this->hasOne(PesertaDidikMeta::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function klaper()
    {
        return $this->hasOne(KesiswaanBukuKlaper::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function berkasVerifikasi()
    {
        return $this->hasOne(KesiswaanBerkasVerifikasi::class, 'peserta_didik_id', 'peserta_didik_id');
    }

    public function mutasi()
    {
        return $this->hasMany(KesiswaanMutasi::class, 'peserta_didik_id', 'peserta_didik_id');
    }
}
