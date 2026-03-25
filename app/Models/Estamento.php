<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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

    protected static function booted(): void
    {
        static::created(function (self $estamento): void {
            if (! Schema::hasTable('servicio_estamento')) {
                return;
            }

            $serviceIds = Servicio::query()->pluck('id_servicio');

            if ($serviceIds->isEmpty()) {
                return;
            }

            $estamento->servicios()->syncWithoutDetaching($serviceIds->all());
        });
    }

    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(
            Servicio::class,
            'servicio_estamento',
            'id_estamento',
            'id_servicio'
        );
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_estamento', 'id_estamento');
    }
}
