<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programa extends Model
{
    use BelongsToSede, HasFactory;

    protected $table = 'programa';

    protected $primaryKey = 'id_programa';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'id_sede',
        'nombre',
    ];

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_programa', 'id_programa');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }
}
