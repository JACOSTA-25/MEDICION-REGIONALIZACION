<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estamento extends Model
{
    use HasFactory;

    protected $table = 'estamento';

    protected $primaryKey = 'id_estamento';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'nombre',
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_estamento', 'id_estamento');
    }
}
