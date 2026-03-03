<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependencia extends Model
{
    use HasFactory;

    protected $table = 'dependencia';

    protected $primaryKey = 'id_dependencia';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'id_proceso',
        'nombre',
    ];

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_proceso');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_dependencia', 'id_dependencia');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_dependencia', 'id_dependencia');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_dependencia', 'id_dependencia');
    }
}
