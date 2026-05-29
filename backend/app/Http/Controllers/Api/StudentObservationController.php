<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentObservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentObservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = StudentObservation::with(['student.user', 'student.course', 'course', 'teacher.user', 'subject', 'period'])
            ->when($request->student_id, fn ($q) => $q->where('student_id', $request->student_id))
            ->when($request->teacher_id, fn ($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->period_id, fn ($q) => $q->where('period_id', $request->period_id))
            ->when($request->search, fn ($q) => $q->where('observation', 'like', "%{$request->search}%"));

        if ($user->isTeacher()) {
            $teacherId = $user->teacher->id;
            $query->where(function ($inner) use ($teacherId) {
                $inner->where('teacher_id', $teacherId)
                    ->orWhereHas('student.course.teacherAssignments', fn ($q) => $q->where('teacher_id', $teacherId));
            });
        }

        if ($user->isStudent()) {
            $query->where('student_id', $user->student->id);
        }

        return response()->json([
            'data' => $query->orderByDesc('created_at')->get(),
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->isTeacher()) {
            $request->merge(['teacher_id' => $request->user()->teacher->id]);
        }

        $validated = $this->validatePayload($request);
        $student = Student::findOrFail($validated['student_id']);
        $this->authorizeStudent($request, $student);

        $validated['course_id'] = $student->course_id;

        $observation = StudentObservation::create($validated);
        $observation->load(['student.user', 'student.course', 'course', 'teacher.user', 'subject', 'period']);

        return response()->json([
            'message' => 'Observacion creada exitosamente.',
            'observation' => $observation,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $observation = StudentObservation::with(['student.user', 'student.course', 'course', 'teacher.user', 'subject', 'period'])
            ->findOrFail($id);
        $this->authorizeObservation($request, $observation);

        return response()->json(['observation' => $observation], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $observation = StudentObservation::findOrFail($id);
        $this->authorizeObservation($request, $observation);

        $validated = $this->validatePayload($request, partial: true);

        if (isset($validated['student_id'])) {
            $student = Student::findOrFail($validated['student_id']);
            $this->authorizeStudent($request, $student);
        }

        if ($request->user()->isTeacher()) {
            unset($validated['teacher_id']);
        }

        if (isset($validated['student_id'])) {
            $validated['course_id'] = Student::findOrFail($validated['student_id'])->course_id;
        }

        $observation->update($validated);
        $observation->load(['student.user', 'student.course', 'course', 'teacher.user', 'subject', 'period']);

        return response()->json([
            'message' => 'Observacion actualizada exitosamente.',
            'observation' => $observation,
        ], 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $observation = StudentObservation::findOrFail($id);
        $this->authorizeObservation($request, $observation);
        $observation->delete();

        return response()->json(['message' => 'Observacion eliminada exitosamente.'], 200);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'student_id' => [$required, 'exists:students,id'],
            'teacher_id' => [$required, 'exists:teachers,id'],
            'subject_id' => [$required, 'exists:subjects,id'],
            'period_id' => ['nullable', 'exists:periods,id'],
            'observation' => [$required, 'string', 'min:3'],
        ]);
    }

    private function authorizeObservation(Request $request, StudentObservation $observation): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && (
            $observation->teacher_id === $user->teacher->id ||
            $observation->student->course?->teacherAssignments()->where('teacher_id', $user->teacher->id)->exists()
        )) {
            return;
        }

        if ($user->isStudent() && $observation->student_id === $user->student->id) {
            return;
        }

        abort(403, 'No tienes acceso a esta observacion.');
    }

    private function authorizeStudent(Request $request, Student $student): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $student->course?->teacherAssignments()->where('teacher_id', $user->teacher->id)->exists()) {
            return;
        }

        abort(403, 'No tienes acceso a este estudiante.');
    }
}
