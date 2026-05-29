<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Schedule::with(['course', 'subject', 'teacher.user'])
            ->when($request->course_id, fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->subject_id, fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->teacher_id, fn ($q) => $q->where('teacher_id', $request->teacher_id))
            ->when($request->day_of_week, fn ($q) => $q->where('day_of_week', $request->day_of_week));

        if ($user->isTeacher()) {
            $this->scopeForTeacher($query, $user->teacher->id);
        }

        if ($user->isStudent()) {
            $query->where('course_id', $user->student->course_id);
        }

        return response()->json([
            'data' => $query->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);
        $this->ensureTeacherAssignmentExists($validated);
        $schedule = Schedule::create($validated);
        $schedule->load(['course', 'subject', 'teacher.user']);

        return response()->json([
            'message' => 'Horario creado exitosamente.',
            'schedule' => $schedule,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::with(['course', 'subject', 'teacher.user'])->findOrFail($id);
        $this->authorizeSchedule($request, $schedule);

        return response()->json(['schedule' => $schedule], 200);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $this->authorizeSchedule($request, $schedule, write: true);
        $validated = $this->validatePayload($request, partial: true);
        $candidate = array_merge($schedule->only(['course_id', 'subject_id', 'teacher_id']), $validated);
        if (isset($validated['course_id']) && ! isset($validated['subject_id'])) {
            abort(422, 'Selecciona una asignatura para el nuevo curso del horario.');
        }
        $this->ensureTeacherAssignmentExists($candidate);
        $schedule->update($validated);
        $schedule->load(['course', 'subject', 'teacher.user']);

        return response()->json([
            'message' => 'Horario actualizado exitosamente.',
            'schedule' => $schedule,
        ], 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $this->authorizeSchedule($request, $schedule, write: true);
        $schedule->delete();

        return response()->json(['message' => 'Horario eliminado exitosamente.'], 200);
    }

    private function validatePayload(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'course_id' => [$required, 'exists:courses,id'],
            'subject_id' => [$required, 'exists:subjects,id'],
            'teacher_id' => [$required, 'exists:teachers,id'],
            'day_of_week' => [$required, 'integer', 'min:1', 'max:7'],
            'starts_at' => [$required, 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'ends_at' => [$required, 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'classroom' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function ensureTeacherAssignmentExists(array $payload): void
    {
        if (
            empty($payload['teacher_id']) ||
            empty($payload['course_id']) ||
            empty($payload['subject_id'])
        ) {
            return;
        }

        $exists = TeacherAssignment::where('teacher_id', $payload['teacher_id'])
            ->where('course_id', $payload['course_id'])
            ->where('subject_id', $payload['subject_id'])
            ->exists();

        if (! $exists) {
            abort(422, 'El docente no tiene asignada esa asignatura en ese curso.');
        }
    }

    private function authorizeSchedule(Request $request, Schedule $schedule, bool $write = false): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if (! $write && $user->isTeacher() && $this->belongsToTeacher($schedule, $user->teacher->id)) {
            return;
        }

        if (! $write && $user->isStudent() && $schedule->course_id === $user->student->course_id) {
            return;
        }

        abort(403, 'No tienes acceso a este horario.');
    }

    private function scopeForTeacher(Builder $query, int $teacherId): void
    {
        $query->where(function (Builder $q) use ($teacherId) {
            $q->where('teacher_id', $teacherId)
                ->orWhereExists(function ($subquery) use ($teacherId) {
                    $subquery->selectRaw('1')
                        ->from('teacher_assignments')
                        ->whereColumn('teacher_assignments.course_id', 'schedules.course_id')
                        ->whereColumn('teacher_assignments.subject_id', 'schedules.subject_id')
                        ->where('teacher_assignments.teacher_id', $teacherId);
                });
        });
    }

    private function belongsToTeacher(Schedule $schedule, int $teacherId): bool
    {
        if ((int) $schedule->teacher_id === $teacherId) {
            return true;
        }

        return $schedule->course_id && $schedule->subject_id
            && $schedule->course
                ->teacherAssignments()
                ->where('teacher_id', $teacherId)
                ->where('subject_id', $schedule->subject_id)
                ->exists();
    }
}
