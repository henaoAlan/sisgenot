<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Form Request: validación para actualizar un usuario.
 */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'email'           => ['sometimes', 'string', 'email', 'max:255', "unique:users,email,{$userId}"],
            'password'        => ['sometimes', Password::min(8)->mixedCase()->numbers()],
            'full_name'       => ['sometimes', 'string', 'max:255'],
            'is_active'       => ['sometimes', 'boolean'],
            'role'            => ['sometimes', 'in:admin,teacher,student'],
            'document_id'     => ['sometimes', 'string', 'max:50'],
            'enrollment_code' => ['sometimes', 'string', 'max:50', "unique:students,enrollment_code,{$userId},user_id"],
            'course_id'       => ['nullable', 'exists:courses,id'],
        ];
    }
}
