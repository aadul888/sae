<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gtk extends Model
{
    protected $table = 'gtk';
    protected $primaryKey = 'ptk_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];

    public function berkas()
    {
        return $this->hasMany(GtkBerkas::class, 'ptk_id', 'ptk_id');
    }

    public function kgb()
    {
        return $this->hasOne(GtkKgbTracker::class, 'ptk_id', 'ptk_id');
    }

    public function cutiIzin()
    {
        return $this->hasMany(GtkCutiIzin::class, 'ptk_id', 'ptk_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'ptk_id', 'ptk_id');
    }
}
