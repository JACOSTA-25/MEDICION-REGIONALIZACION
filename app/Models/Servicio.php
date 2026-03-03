<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    use HasFactory;

    protected $table = 'servicio';

    protected $primaryKey = 'id_servicio';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'id_dependencia',
        'nombre',
    ];

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'id_dependencia', 'id_dependencia');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_servicio', 'id_servicio');
    }
}
