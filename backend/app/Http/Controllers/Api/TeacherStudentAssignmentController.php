<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherStudentAssignmentController extends Controller
{
    /**
     * Obtener estudiantes asignados a un docente.
     */
    public function studentsByTeacher(Teacher $teacher): JsonResponse
    {
        if (Auth::user()->role === 'teacher' && Auth::user()->teacher?->id !== $teacher->id) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        $students = $teacher->assignedStudents()
            ->with(['user', 'course'])
            ->get();

        return response()->json($students, 200);
    }

    /**
     * Obtener docentes asignados a un estudiante.
     */
    public function teachersByStudent(Student $student): JsonResponse
    {
        if (Auth::user()->role === 'teacher') {
            $teacherId = Auth::user()->teacher?->id;
            $isAssigned = $student->assignedTeachers()
                ->where('teachers.id', $teacherId)
                ->exists();

            if (!$isAssigned) {
                return response()->json(['message' => 'Acceso denegado.'], 403);
            }
        }

        $teachers = $student->assignedTeachers()
            ->with('user')
            ->get();

        return response()->json($teachers, 200);
    }

    /**
     * Obtener todos los estudiantes disponibles (sin filtro).
     */
    public function availableStudents(): JsonResponse
    {
        $students = Student::with(['user', 'course'])->get();
        return response()->json($students, 200);
    }

    /**
     * Obtener todos los docentes disponibles.
     */
    public function availableTeachers(): JsonResponse
    {
        $teachers = Teacher::with('user')->get();
        return response()->json($teachers, 200);
    }

    /**
     * Asignar un estudiante a un docente.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $teacher = Teacher::findOrFail($validated['teacher_id']);
        $student = Student::findOrFail($validated['student_id']);

        // Evitar asignar dos veces
        if ($teacher->assignedStudents()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'message' => 'Este estudiante ya está asignado a este docente.',
            ], 422);
        }

        $teacher->assignedStudents()->attach($student->id);

        return response()->json([
            'message' => 'Estudiante asignado al docente exitosamente.',
        ], 201);
    }

    /**
     * Desasignar un estudiante de un docente.
     */
    public function unassign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $teacher = Teacher::findOrFail($validated['teacher_id']);
        $student = Student::findOrFail($validated['student_id']);

        $teacher->assignedStudents()->detach($student->id);

        return response()->json([
            'message' => 'Estudiante desasignado del docente exitosamente.',
        ], 200);
    }

    /**
     * Asignar múltiples estudiantes a un docente.
     */
    public function assignMultiple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['exists:students,id'],
        ]);

        $teacher = Teacher::findOrFail($validated['teacher_id']);

        // Obtener los IDs que ya están asignados
        $alreadyAssigned = $teacher->assignedStudents()
            ->whereIn('student_id', $validated['student_ids'])
            ->pluck('student_id')
            ->toArray();

        // Filtrar solo los nuevos
        $newStudentIds = array_diff($validated['student_ids'], $alreadyAssigned);

        // Asignar los nuevos
        $teacher->assignedStudents()->attach($newStudentIds);

        return response()->json([
            'message' => count($newStudentIds) . ' estudiante(s) asignado(s) exitosamente.',
            'assigned' => count($newStudentIds),
            'skipped' => count($alreadyAssigned),
        ], 201);
    }
}
