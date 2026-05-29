<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form Request: validación para crear un usuario.
 *
 * Solo los administradores pueden crear usuarios,
 * y deben proveer el perfil correspondiente al rol.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'            => ['required', Password::min(8)->mixedCase()->numbers()],
            'role'                => ['required', 'in:admin,teacher,student'],
            'full_name'           => ['required', 'string', 'max:255'],
            'is_active'           => ['boolean'],

            // Campos requeridos si role = teacher
            'document_id'         => ['required_if:role,teacher,student', 'string', 'max:50'],

            // Campos requeridos si role = student
            'enrollment_code'     => ['required_if:role,student', 'string', 'max:50', 'unique:students,enrollment_code'],
            'course_id'           => ['nullable', 'exists:courses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'                  => 'Este correo ya está registrado.',
            'role.in'                       => 'El rol debe ser admin, teacher o student.',
            'document_id.required_if'       => 'El documento de identidad es obligatorio para docentes y estudiantes.',
            'enrollment_code.required_if'   => 'El código de matrícula es obligatorio para estudiantes.',
            'enrollment_code.unique'        => 'Este código de matrícula ya existe.',
            'course_id.exists'              => 'El curso especificado no existe.',
        ];
    }
}
