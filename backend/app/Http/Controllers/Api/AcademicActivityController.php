<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicActivity;
use App\Models\Student;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AcademicActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Validar que usuarios con rol student o teacher tengan perfil completo
        if ($user->isStudent() && !$user->student) {
            return response()->json([
                'message' => 'Tu perfil de estudiante no está completado.',
            ], 422);
        }
        
        if ($user->isTeacher() && !$user->teacher) {
            return response()->json([
                'message' => 'Tu perfil de docente no está completado.',
            ], 422);
        }
        
        $query = AcademicActivity::with(['assignment.teacher.user', 'assignment.course', 'assignment.subject', 'period', 'student.user'])
            ->withCount(['grades', 'submissions'])
            ->when($user->isStudent() && $user->student, fn ($q) => $q->with(['submissions' => fn ($sub) => $sub->where('student_id', $user->student->id)]))
            ->when($request->teacher_assignment_id, fn ($q) => $q->where('teacher_assignment_id', $request->teacher_assignment_id))
            ->when($request->period_id, fn ($q) => $q->where('period_id', $request->period_id))
            ->when($request->moment, fn ($q) => $q->where('moment', $request->moment))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('title', 'like', "%{$request->search}%")
                        ->orWhere('description', 'like', "%{$request->search}%");
                });
            });

        $this->scopeByRole($query, $user);

        $activities = $query
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $activities], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $assignment = TeacherAssignment::findOrFail($validated['teacher_assignment_id']);
        $this->authorizeAssignment($request, $assignment);

        if (! empty($validated['student_id'])) {
            $this->authorizeStudentCourse($assignment, $validated['student_id']);
        }

        $activity = AcademicActivity::create($validated);
        $this->storeActivityFile($request, $activity);
        $activity->load(['assignment.teacher.user', 'assignment.course', 'assignment.subject', 'period', 'student.user']);

        return response()->json([
            'message' => 'Actividad creada exitosamente.',
            'activity' => $activity,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $activity = AcademicActivity::with([
            'assignment.teacher.user',
            'assignment.course',
            'assignment.subject',
            'period',
            'student.user',
            'grades.student.user',
            'submissions.student.user',
        ])->findOrFail($id);

        $this->authorizeActivity($request, $activity);

        return response()->json(['activity' => $activity], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $activity = AcademicActivity::findOrFail($id);
        $this->authorizeActivity($request, $activity);

        $validated = $this->validatePayload($request, $activity->id);

        $assignment = $activity->assignment;
        if (isset($validated['teacher_assignment_id'])) {
            $assignment = TeacherAssignment::findOrFail($validated['teacher_assignment_id']);
            $this->authorizeAssignment($request, $assignment);
        }

        if (! empty($validated['student_id'])) {
            $this->authorizeStudentCourse($assignment, $validated['student_id']);
        }

        $activity->update($validated);
        $this->storeActivityFile($request, $activity);
        $activity->load(['assignment.teacher.user', 'assignment.course', 'assignment.subject', 'period', 'student.user']);

        return response()->json([
            'message' => 'Actividad actualizada exitosamente.',
            'activity' => $activity,
        ], 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $activity = AcademicActivity::findOrFail($id);
        $this->authorizeActivity($request, $activity);
        if ($activity->file_path && Storage::exists($activity->file_path)) {
            Storage::delete($activity->file_path);
        }
        $activity->delete();

        return response()->json(['message' => 'Actividad eliminada exitosamente.'], 200);
    }

    public function downloadFile(Request $request, int $id)
    {
        $activity = AcademicActivity::with('assignment')->findOrFail($id);
        $this->authorizeActivity($request, $activity);

        if (! $activity->file_path || ! Storage::exists($activity->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::download($activity->file_path, basename($activity->file_path));
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        if ($request->has('is_recovery')) {
            $request->merge([
                'is_recovery' => filter_var($request->input('is_recovery'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        return $request->validate([
            'teacher_assignment_id' => ['required', 'exists:teacher_assignments,id'],
            'period_id' => ['required', 'exists:periods,id'],
            'moment' => ['required', 'integer', 'min:1', 'max:3'],
            'activity_number' => [
                'required',
                'integer',
                'min:1',
                'max:10',
                Rule::unique('academic_activities')
                    ->where('teacher_assignment_id', $request->teacher_assignment_id)
                    ->where('period_id', $request->period_id)
                    ->where('moment', $request->moment)
                    ->where('is_recovery', filter_var($request->is_recovery ?? false, FILTER_VALIDATE_BOOLEAN))
                    ->ignore($ignoreId),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'is_recovery' => ['sometimes', 'boolean'],
            'student_id' => ['sometimes', 'nullable', 'exists:students,id'],
            'file' => ['sometimes', 'nullable', 'file', 'max:10240'],
        ], [
            'activity_number.unique' => 'Ya existe una actividad en ese periodo, momento y numero.',
        ]);
    }

    private function storeActivityFile(Request $request, AcademicActivity $activity): void
    {
        if (! $request->hasFile('file')) {
            return;
        }

        if ($activity->file_path && Storage::exists($activity->file_path)) {
            Storage::delete($activity->file_path);
        }

        $activity->forceFill([
            'file_path' => $request->file('file')->store('activity_files'),
        ])->save();
    }

    private function scopeByRole($query, $user): void
    {
        if ($user->isTeacher() && $user->teacher) {
            $query->whereHas('assignment', fn ($q) => $q->where('teacher_id', $user->teacher->id));
        }

        if ($user->isStudent() && $user->student) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('student_id')
                    ->orWhere('student_id', $user->student->id);
            })->whereHas('assignment', fn ($q) => $q->where('course_id', $user->student->course_id));
        }
    }

    private function authorizeActivity(Request $request, AcademicActivity $activity): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        $activity->loadMissing('assignment');

        if ($user->isTeacher() && $user->teacher && $activity->assignment->teacher_id === $user->teacher->id) {
            return;
        }

        if ($user->isStudent() && $user->student) {
            $isSameCourse = $activity->assignment->course_id === $user->student->course_id;
            $isAllowedStudent = $activity->student_id === null || $activity->student_id === $user->student->id;

            if ($isSameCourse && $isAllowedStudent) {
                return;
            }
        }

        abort(403, 'No tienes acceso a esta actividad.');
    }

    private function authorizeAssignment(Request $request, TeacherAssignment $assignment): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $user->teacher && $assignment->teacher_id === $user->teacher->id) {
            return;
        }

        abort(403, 'No tienes acceso a esta asignacion.');
    }

    private function authorizeStudentCourse(TeacherAssignment $assignment, int $studentId): void
    {
        $student = Student::findOrFail($studentId);

        if ($student->course_id !== $assignment->course_id) {
            abort(422, 'El estudiante no pertenece al curso de la asignacion.');
        }
    }
}
