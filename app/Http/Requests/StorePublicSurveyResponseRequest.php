<?php

namespace App\Http\Requests;

use App\Models\Dependencia;
use App\Models\Estamento;
use App\Models\Servicio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePublicSurveyResponseRequest extends FormRequest
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
            'id_dependencia' => ['required', 'integer', 'exists:dependencia,id_dependencia'],
            'id_estamento' => ['required', 'integer', 'exists:estamento,id_estamento'],
            'id_programa' => ['nullable', 'integer', 'exists:programa,id_programa'],
            'id_proceso' => ['required', 'integer', 'exists:proceso,id_proceso'],
            'id_servicio' => ['required', 'integer', 'exists:servicio,id_servicio'],
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

                $dependencia = Dependencia::query()->find($this->input('id_dependencia'));

                if (
                    $dependencia &&
                    $this->filled('id_proceso') &&
                    $dependencia->id_proceso !== (int) $this->input('id_proceso')
                ) {
                    $validator->errors()->add('id_dependencia', 'La dependencia seleccionada no pertenece al proceso indicado.');
                }

                $servicio = Servicio::query()->find($this->input('id_servicio'));

                if (
                    $servicio &&
                    $this->filled('id_dependencia') &&
                    $servicio->id_dependencia !== (int) $this->input('id_dependencia')
                ) {
                    $validator->errors()->add('id_servicio', 'El servicio seleccionado no pertenece a la dependencia indicada.');
                }
            },
        ];
    }

    private function estamentoRequierePrograma(string $nombreEstamento): bool
    {
        return in_array(mb_strtolower($nombreEstamento), self::ESTAMENTOS_REQUIEREN_PROGRAMA, true);
    }
}
