<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    use HasFactory;

    protected $table = 'programa';

    protected $primaryKey = 'id_programa';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'nombre',
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_programa', 'id_programa');
    }
}
