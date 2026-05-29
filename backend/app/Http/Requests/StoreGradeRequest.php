<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: validación para crear/actualizar notas.
 *
 * Tanto docentes como admins pueden crear notas.
 * La lógica de autorización granular se maneja en el controlador.
 */
class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['admin', 'teacher']);
    }

    public function rules(): array
    {
        return [
            'student_id'  => ['required', 'exists:students,id'],
            'course_id'   => ['required', 'exists:courses,id'],
            'subject_id'  => ['required', 'exists:subjects,id'],
            'period_id'   => ['required', 'exists:periods,id'],
            'competency'  => ['required', 'in:ser,saber,hacer'],
            'grade'       => ['required', 'numeric', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'student_id.exists'  => 'El estudiante especificado no existe.',
            'course_id.exists'   => 'El curso especificado no existe.',
            'subject_id.exists'  => 'La asignatura especificada no existe.',
            'period_id.exists'   => 'El período especificado no existe.',
            'competency.in'      => 'La competencia debe ser: ser, saber o hacer.',
            'grade.min'          => 'La nota mínima es 1.00.',
            'grade.max'          => 'La nota máxima es 5.00.',
        ];
    }
}
