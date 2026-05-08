<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Servicio extends Model
{
    use BelongsToSede, HasFactory;

    protected $table = 'servicio';

    protected $primaryKey = 'id_servicio';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'id_sede',
        'id_dependencia',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $servicio): void {
            if (! Schema::hasTable('servicio_estamento')) {
                return;
            }

            $estamentoIds = Estamento::query()->pluck('id_estamento');

            if ($estamentoIds->isEmpty()) {
                return;
            }

            $servicio->estamentos()->syncWithoutDetaching($estamentoIds->all());
        });
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'id_dependencia', 'id_dependencia');
    }

    public function estamentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Estamento::class,
            'servicio_estamento',
            'id_servicio',
            'id_estamento'
        );
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_servicio', 'id_servicio');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    public function scopeAllowedForEstamento($query, mixed $estamentoId)
    {
        if (blank($estamentoId)) {
            return $query;
        }

        return $query->whereHas('estamentos', fn ($estamentosQuery) => $estamentosQuery->where(
            'estamento.id_estamento',
            (int) $estamentoId
        ));
    }

    public function scopeAvailableForSurvey($query)
    {
        return $query
            ->active()
            ->whereHas('dependencia', fn ($dependenciaQuery) => $dependenciaQuery
                ->where('activo', true)
                ->whereHas('proceso', fn ($processQuery) => $processQuery->where('activo', true)));
    }
}
