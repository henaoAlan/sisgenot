<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * SubjectController — Catálogo de asignaturas.
 *
 * Permisos:
 *   - admin: CRUD completo
 *   - teacher/student: solo lectura
 *
 * Endpoints:
 *   GET    /api/subjects        → Listar asignaturas
 *   POST   /api/subjects        → Crear (admin)
 *   GET    /api/subjects/{id}   → Ver detalle
 *   PUT    /api/subjects/{id}   → Actualizar (admin)
 *   DELETE /api/subjects/{id}   → Eliminar (admin)
 */
class SubjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subjects = Subject::query()
            ->with('course')
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%"))
            ->orderBy('course_id')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $subjects], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('subjects')->where(fn ($query) => $query->where('course_id', $request->course_id)),
            ],
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $subject = Subject::create($validated);
        $subject->load('course');

        return response()->json([
            'message' => 'Asignatura creada exitosamente.',
            'subject' => $subject,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $subject = Subject::with('course')->withCount(['teacherAssignments', 'grades'])->findOrFail($id);

        return response()->json(['subject' => $subject], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subject = Subject::findOrFail($id);
        $courseId = $request->input('course_id', $subject->course_id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('subjects')->ignore($id)->where(fn ($query) => $query->where('course_id', $courseId)),
            ],
            'course_id' => ['sometimes', 'required', 'exists:courses,id'],
        ]);

        $subject->update($validated);
        $subject->load('course');

        return response()->json([
            'message' => 'Asignatura actualizada exitosamente.',
            'subject' => $subject,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $subject = Subject::findOrFail($id);

        if ($subject->grades()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar una asignatura que tiene notas registradas.',
                'error'   => 'SUBJECT_HAS_GRADES',
            ], 409);
        }

        $subject->delete();

        return response()->json(['message' => 'Asignatura eliminada exitosamente.'], 200);
    }
}
