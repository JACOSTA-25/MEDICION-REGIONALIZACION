<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proceso extends Model
{
    use HasFactory;

    protected $table = 'proceso';

    protected $primaryKey = 'id_proceso';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'nombre',
    ];

    public function dependencias(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'id_proceso', 'id_proceso');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_proceso', 'id_proceso');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_proceso', 'id_proceso');
    }
}
