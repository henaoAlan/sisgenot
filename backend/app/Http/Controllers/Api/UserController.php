<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * UserController — Gestión de usuarios del sistema.
 *
 * Acceso exclusivo para administradores.
 * Maneja creación de usuarios junto con sus perfiles
 * (Teacher / Student) en una transacción atómica.
 *
 * Endpoints:
 *   GET    /api/users        → Listar todos los usuarios
 *   POST   /api/users        → Crear usuario con perfil
 *   GET    /api/users/{id}   → Ver detalle de un usuario
 *   PUT    /api/users/{id}   → Actualizar usuario/perfil
 *   DELETE /api/users/{id}   → Eliminar usuario (cascade)
 */
class UserController extends Controller
{
    /**
     * Lista todos los usuarios con paginación y filtros opcionales.
     *
     * Query params:
     *   ?role=teacher|student|admin
     *   ?search=texto (busca en email y full_name)
     *   ?per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->with(['teacher', 'student.course'])
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('email', 'like', "%{$request->search}%")
                          ->orWhere('full_name', 'like', "%{$request->search}%");
                });
            })
            ->orderBy('full_name');

        $users = $query->paginate($request->per_page ?? 15);

        return response()->json($users, 200);
    }

    /**
     * Crea un nuevo usuario con su perfil según el rol.
     *
     * Para role=teacher: también crea registro en teachers.
     * Para role=student: también crea registro en students.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Crear el usuario base
            $user = User::create([
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => $request->role,
                'full_name' => $request->full_name,
                'is_active' => $request->is_active ?? true,
            ]);

            // 2. Crear el perfil específico del rol
            if ($request->role === 'teacher') {
                Teacher::create([
                    'user_id'     => $user->id,
                    'document_id' => $request->document_id,
                ]);
            } elseif ($request->role === 'student') {
                Student::create([
                    'user_id'         => $user->id,
                    'document_id'     => $request->document_id,
                    'enrollment_code' => $request->enrollment_code,
                    'course_id'       => $request->course_id,
                ]);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $user->load(['teacher', 'student.course']);

            return response()->json([
                'message' => 'Usuario creado exitosamente.',
                'user'    => $user,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear el usuario.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Muestra el detalle completo de un usuario específico.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with(['teacher', 'student.course'])->findOrFail($id);

        return response()->json(['user' => $user], 200);
    }

    /**
     * Actualiza los datos del usuario y su perfil.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();

        try {
            // Actualizar campos del usuario base
            $userData = $request->only(['email', 'full_name', 'is_active', 'role']);
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }
            $user->update($userData);

            // Actualizar perfil de docente
            if ($user->isTeacher() && $request->filled('document_id')) {
                $user->teacher()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['document_id' => $request->document_id]
                );
            }

            // Actualizar perfil de estudiante
            if ($user->isStudent()) {
                $studentData = $request->only(['document_id', 'enrollment_code', 'course_id']);
                if (! empty($studentData)) {
                    $user->student()->updateOrCreate(
                        ['user_id' => $user->id],
                        $studentData
                    );
                }
            }

            DB::commit();

            $user->load(['teacher', 'student.course']);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente.',
                'user'    => $user,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar el usuario.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina un usuario (y su perfil por cascade de BD).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // No permitir auto-eliminación
        if ($user->id === request()->user()->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta.',
                'error'   => 'SELF_DELETE_FORBIDDEN',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente.',
        ], 200);
    }
}
