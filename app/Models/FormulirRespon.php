<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulirRespon extends Model
{
    use HasFactory;

    protected $table = 'formulir_respon';

    protected $fillable = [
        'formulir_id',
        'pengguna_id',
        'peserta_didik_id',
        'ptk_id',
        'nama_responden',
        'identitas_responden',
        'ip_address',
        'user_agent',
        'jawaban',
    ];

    protected $casts = [
        'jawaban' => 'array',
    ];

    public function formulir(): BelongsTo
    {
        return $this->belongsTo(Formulir::class, 'formulir_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id', 'pengguna_id');
    }
}
