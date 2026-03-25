<?php

namespace App\Http\Requests\Encuesta;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Proceso;
use App\Models\Servicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarRespuestaPublicaEncuestaRequest extends FormRequest
{
    private const ESTAMENTOS_REQUIEREN_PROGRAMA = [
        'docente',
        'egresado',
        'estudiante',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_dependencia' => [
                'required',
                'integer',
                Rule::exists('dependencia', 'id_dependencia')->where(fn ($query) => $query->where('activo', true)),
            ],
            'id_estamento' => ['required', 'integer', 'exists:estamento,id_estamento'],
            'id_programa' => ['nullable', 'integer', 'exists:programa,id_programa'],
            'id_proceso' => [
                'required',
                'integer',
                Rule::exists('proceso', 'id_proceso')->where(fn ($query) => $query->where('activo', true)),
            ],
            'id_servicio' => [
                'required',
                'integer',
                Rule::exists('servicio', 'id_servicio')->where(fn ($query) => $query->where('activo', true)),
            ],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'pregunta1' => ['required', 'integer', 'between:1,5'],
            'pregunta2' => ['required', 'integer', 'between:1,5'],
            'pregunta3' => ['required', 'integer', 'between:1,5'],
            'pregunta4' => ['required', 'integer', 'between:1,5'],
            'pregunta5' => ['required', 'integer', 'between:1,5'],
            'pregunta6' => ['required', 'integer', 'between:1,5'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $estamento = Estamento::query()->find($this->input('id_estamento'));

                if (! $estamento) {
                    return;
                }

                if ($this->estamentoRequierePrograma($estamento->nombre) && ! $this->filled('id_programa')) {
                    $validator->errors()->add('id_programa', 'El programa es obligatorio para el estamento seleccionado.');
                }

                $proceso = Proceso::query()->find($this->input('id_proceso'));

                if (! $proceso || ! $proceso->activo) {
                    $validator->errors()->add('id_proceso', 'El proceso seleccionado esta inactivo o no existe.');
                    return;
                }

                $dependencia = Dependencia::query()->find($this->input('id_dependencia'));

                if (! $dependencia || ! $dependencia->activo) {
                    $validator->errors()->add('id_dependencia', 'La dependencia seleccionada esta inactiva o no existe.');
                    return;
                }

                if ($dependencia->id_proceso !== (int) $this->input('id_proceso')) {
                    $validator->errors()->add('id_dependencia', 'La dependencia seleccionada no pertenece al proceso indicado.');
                    return;
                }

                $servicio = Servicio::query()->find($this->input('id_servicio'));

                if (! $servicio || ! $servicio->activo || $servicio->id_dependencia !== (int) $this->input('id_dependencia')) {
                    $validator->errors()->add('id_servicio', 'El servicio seleccionado no pertenece a la dependencia indicada.');
                    return;
                }

                $serviceSupportsEstamento = $servicio->estamentos()
                    ->where('estamento.id_estamento', (int) $this->input('id_estamento'))
                    ->exists();

                if (! $serviceSupportsEstamento) {
                    $validator->errors()->add('id_servicio', 'El servicio seleccionado no puede ser prestado al estamento indicado.');
                }
            },
        ];
    }

    private function estamentoRequierePrograma(string $nombreEstamento): bool
    {
        return in_array(mb_strtolower($nombreEstamento), self::ESTAMENTOS_REQUIEREN_PROGRAMA, true);
    }
}
