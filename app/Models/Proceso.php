<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proceso extends Model
{
    use BelongsToSede, HasFactory;

    protected $table = 'proceso';

    protected $primaryKey = 'id_proceso';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'id_sede',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'id_proceso', 'id_proceso');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_proceso', 'id_proceso');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_proceso', 'id_proceso');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
