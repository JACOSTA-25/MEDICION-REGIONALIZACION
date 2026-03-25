<?php

namespace App\Http\Requests\Perfil;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPerfilRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:80',
                Rule::unique(User::class, 'username')->ignore($this->user()->id),
            ],
        ];
    }
}
