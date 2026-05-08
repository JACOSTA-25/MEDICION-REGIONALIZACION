<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSede;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Respuesta extends Model
{
    use BelongsToSede, HasFactory;

    protected $table = 'respuesta';

    protected $primaryKey = 'id_respuesta';

    protected $keyType = 'int';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'id_sede',
        'id_estamento',
        'id_programa',
        'id_proceso',
        'id_dependencia',
        'id_servicio',
        'pregunta1',
        'pregunta2',
        'pregunta3',
        'pregunta4',
        'pregunta5',
        'observaciones',
        'fecha_respuesta',
    ];

    protected function casts(): array
    {
        return [
            'id_sede' => 'integer',
            'id_estamento' => 'integer',
            'id_programa' => 'integer',
            'id_proceso' => 'integer',
            'id_dependencia' => 'integer',
            'id_servicio' => 'integer',
            'pregunta1' => 'integer',
            'pregunta2' => 'integer',
            'pregunta3' => 'integer',
            'pregunta4' => 'integer',
            'pregunta5' => 'integer',
            'fecha_respuesta' => 'datetime',
        ];
    }

    public function estamento(): BelongsTo
    {
        return $this->belongsTo(Estamento::class, 'id_estamento', 'id_estamento');
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class, 'id_programa', 'id_programa');
    }

    public function proceso(): BelongsTo
    {
        return $this->belongsTo(Proceso::class, 'id_proceso', 'id_proceso');
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'id_dependencia', 'id_dependencia');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio', 'id_servicio');
    }
}
