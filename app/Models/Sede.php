<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    use HasFactory;

    public const ID_MAICAO = 1;

    public const ID_FONSECA = 2;

    public const ID_VILLANUEVA = 3;

    public const ID_REGIONALIZACION = 4;

    protected $table = 'sede';

    protected $primaryKey = 'id_sede';

    protected $keyType = 'int';

    public $incrementing = true;

    protected $fillable = [
        'codigo',
        'slug',
        'nombre',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function procesos(): HasMany
    {
        return $this->hasMany(Proceso::class, 'id_sede', 'id_sede');
    }

    public function dependencias(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'id_sede', 'id_sede');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'id_sede', 'id_sede');
    }

    public function programas(): HasMany
    {
        return $this->hasMany(Programa::class, 'id_sede', 'id_sede');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(Respuesta::class, 'id_sede', 'id_sede');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_sede', 'id_sede');
    }

    public function reportingQuarters(): HasMany
    {
        return $this->hasMany(ReportingQuarter::class, 'id_sede', 'id_sede');
    }

    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }
}
