<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = TeacherAssignment::with(['teacher.user', 'course.students.user', 'subject']);

        if ($user->isTeacher()) {
            $query->where('teacher_id', $user->teacher->id);
        }

        $query->when($request->teacher_id, fn ($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->course_id, fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id));

        return response()->json(['data' => $query->get()], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $this->ensureSubjectBelongsToCourse($validated['subject_id'], $validated['course_id']);

        $assignment = TeacherAssignment::create($validated);
        $assignment->load(['teacher.user', 'course.students.user', 'subject']);

        return response()->json([
            'message' => 'Asignacion creada exitosamente.',
            'assignment' => $assignment,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $assignment = TeacherAssignment::findOrFail($id);
        $validated = $this->validatePayload($request, $assignment->id);
        $this->ensureSubjectBelongsToCourse($validated['subject_id'], $validated['course_id']);

        $assignment->update($validated);
        $assignment->load(['teacher.user', 'course.students.user', 'subject']);

        return response()->json([
            'message' => 'Asignacion actualizada exitosamente.',
            'assignment' => $assignment,
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $assignment = TeacherAssignment::findOrFail($id);
        $assignment->delete();

        return response()->json([
            'message' => 'Asignacion eliminada exitosamente.',
        ], 200);
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'teacher_id' => ['required', 'exists:teachers,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'subject_id' => [
                'required',
                'exists:subjects,id',
                Rule::unique('teacher_assignments')
                    ->where('teacher_id', $request->teacher_id)
                    ->where('course_id', $request->course_id)
                    ->ignore($ignoreId),
            ],
        ], [
            'subject_id.unique' => 'Esta asignacion ya existe para el docente, curso y asignatura indicados.',
        ]);
    }

    private function ensureSubjectBelongsToCourse(int $subjectId, int $courseId): void
    {
        $subject = Subject::findOrFail($subjectId);

        if ((int) $subject->course_id !== (int) $courseId) {
            abort(422, 'La asignatura seleccionada no pertenece al curso indicado.');
        }
    }
}
