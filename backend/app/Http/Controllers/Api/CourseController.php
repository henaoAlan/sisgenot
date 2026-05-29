<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CourseController — Gestión de cursos académicos.
 *
 * Permisos:
 *   - admin: CRUD completo
 *   - teacher: solo lectura (sus cursos asignados)
 *   - student: ver su propio curso
 *
 * Endpoints:
 *   GET    /api/courses                   → Listar cursos
 *   POST   /api/courses                   → Crear (admin)
 *   GET    /api/courses/{id}              → Ver detalle
 *   PUT    /api/courses/{id}              → Actualizar (admin)
 *   DELETE /api/courses/{id}              → Eliminar (admin)
 *   GET    /api/courses/{id}/students     → Estudiantes del curso (admin, teacher)
 */
class CourseController extends Controller
{
    /**
     * Lista cursos filtrando según el rol del usuario.
     *
     * - Admin: todos los cursos
     * - Teacher: solo los cursos donde tiene asignaciones
     * - Student: solo su curso
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Course::query()
            ->when($request->year, fn($q) => $q->where('year', $request->year))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)));

        if ($user->isTeacher()) {
            // Solo cursos donde el docente tiene asignaciones
            $teacherId = $user->teacher->id;
            $query->whereHas('teacherAssignments', fn($q) => $q->where('teacher_id', $teacherId));
        } elseif ($user->isStudent()) {
            // Solo el curso del estudiante
            $courseId = $user->student->course_id;
            if (! $courseId) {
                return response()->json(['data' => []], 200);
            }
            $query->where('id', $courseId);
        }

        $courses = $query->withCount('students')->orderBy('year', 'desc')->orderBy('name')->get();

        return response()->json(['data' => $courses], 200);
    }

    /**
     * Crea un nuevo curso (solo admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'grade'     => ['required', 'string', 'max:20'],
            'year'      => ['required', 'integer', 'min:2000', 'max:2100'],
            'is_active' => ['boolean'],
        ]);

        $course = Course::create($validated);

        return response()->json([
            'message' => 'Curso creado exitosamente.',
            'course'  => $course,
        ], 201);
    }

    /**
     * Muestra el detalle de un curso con sus conteos.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user   = $request->user();
        $course = Course::withCount('students')->findOrFail($id);

        // Estudiante solo puede ver su propio curso
        if ($user->isStudent() && $user->student->course_id !== $id) {
            return response()->json(['message' => 'No tienes acceso a este curso.'], 403);
        }

        // Docente solo puede ver sus cursos asignados
        if ($user->isTeacher()) {
            $hasAccess = $course->teacherAssignments()
                ->where('teacher_id', $user->teacher->id)
                ->exists();

            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes acceso a este curso.'], 403);
            }
        }

        return response()->json(['course' => $course], 200);
    }

    /**
     * Actualiza un curso (solo admin).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name'      => ['sometimes', 'string', 'max:100'],
            'grade'     => ['sometimes', 'string', 'max:20'],
            'year'      => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $course->update($validated);

        return response()->json([
            'message' => 'Curso actualizado exitosamente.',
            'course'  => $course,
        ], 200);
    }

    /**
     * Elimina un curso (solo admin).
     */
    public function destroy(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);

        // Verificar si el curso tiene estudiantes activos
        if ($course->students()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar un curso con estudiantes inscritos.',
                'error'   => 'COURSE_HAS_STUDENTS',
            ], 409);
        }

        $course->delete();

        return response()->json([
            'message' => 'Curso eliminado exitosamente.',
        ], 200);
    }

    /**
     * Lista los estudiantes de un curso con sus notas promedio.
     * Accesible para admin y teacher (con restricción de asignación).
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $user   = $request->user();

        // Docentes solo pueden ver sus cursos asignados
        if ($user->isTeacher()) {
            $hasAccess = $course->teacherAssignments()
                ->where('teacher_id', $user->teacher->id)
                ->exists();

            if (! $hasAccess) {
                return response()->json(['message' => 'No tienes asignaciones en este curso.'], 403);
            }
        }

        $students = Student::with(['user', 'grades' => function ($query) use ($id) {
            $query->where('course_id', $id)->with(['subject', 'period']);
        }])
        ->where('course_id', $id)
        ->get()
        ->map(function ($student) {
            return [
                'id'              => $student->id,
                'full_name'       => $student->user->full_name,
                'enrollment_code' => $student->enrollment_code,
                'document_id'     => $student->document_id,
                'grades_count'    => $student->grades->count(),
                'grade_average'   => $student->grades->avg('grade')
                    ? round($student->grades->avg('grade'), 2)
                    : null,
            ];
        });

        return response()->json([
            'course'   => ['id' => $course->id, 'name' => $course->name],
            'students' => $students,
            'total'    => $students->count(),
        ], 200);
    }
}
