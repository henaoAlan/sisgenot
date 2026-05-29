<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: validación para actualizar una nota existente.
 */
class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'teacher']);
    }

    public function rules(): array
    {
        return [
            'grade'      => ['required', 'numeric', 'min:1', 'max:5'],
            'competency' => ['sometimes', 'in:ser,saber,hacer'],
        ];
    }

    public function messages(): array
    {
        return [
            'grade.required' => 'El valor de la nota es obligatorio.',
            'grade.min'      => 'La nota mínima es 1.00.',
            'grade.max'      => 'La nota máxima es 5.00.',
            'competency.in'  => 'La competencia debe ser: ser, saber o hacer.',
        ];
    }
}
