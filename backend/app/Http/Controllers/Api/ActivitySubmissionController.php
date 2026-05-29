<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicActivity;
use App\Models\ActivitySubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ActivitySubmissionController extends Controller
{
    public function index(Request $request, AcademicActivity $activity): JsonResponse
    {
        $activity->loadMissing('assignment');
        $this->authorizeActivity($request, $activity);

        $user = $request->user();
        $query = $activity->submissions()->with(['student.user']);

        if ($user->isStudent() && $user->student) {
            $query->where('student_id', $user->student->id);
        } elseif ($user->isStudent() && !$user->student) {
            return response()->json([
                'message' => 'Tu perfil de estudiante no está completado.',
            ], 422);
        }

        return response()->json(['data' => $query->get()], 200);
    }

    public function store(Request $request, AcademicActivity $activity): JsonResponse
    {
        $user = $request->user();

        if (! $user->isStudent()) {
            abort(403, 'Solo estudiantes pueden subir entregas.');
        }

        $activity->loadMissing('assignment');
        $this->authorizeActivity($request, $activity);

        $student = $user->student;
        if (! $student) {
            abort(403, 'Perfil de estudiante no encontrado.');
        }

        if ($activity->student_id && $activity->student_id !== $student->id) {
            abort(403, 'No tienes acceso a esta actividad.');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $student = $user->student;
        if (! $student) {
            abort(403, 'Perfil de estudiante no encontrado.');
        }

        if ($activity->student_id && $activity->student_id !== $student->id) {
            abort(403, 'No tienes acceso a esta actividad.');
        }

        $submission = ActivitySubmission::firstOrNew([
            'academic_activity_id' => $activity->id,
            'student_id' => $student->id,
        ]);

        if (Schema::hasColumn('activity_submissions', 'title')) {
            $submission->title = $activity->title;
        }

        if ($request->hasFile('file')) {
            if ($submission->file_path && Storage::exists($submission->file_path)) {
                Storage::delete($submission->file_path);
            }

            $submission->file_path = $request->file('file')->store('activity_submissions');
        }

        $submission->comment = $validated['comment'] ?? $submission->comment;
        $submission->submitted_at = now();
        $submission->save();
        $submission->load(['student.user']);

        return response()->json([
            'message' => 'Entrega subida exitosamente.',
            'submission' => $submission,
        ], $submission->wasRecentlyCreated ? 201 : 200);
    }

    public function download(Request $request, AcademicActivity $activity, ActivitySubmission $submission)
    {
        $activity->loadMissing('assignment');
        $this->authorizeActivity($request, $activity);

        if ($submission->academic_activity_id !== $activity->id) {
            abort(404, 'Entrega no encontrada.');
        }

        $user = $request->user();
        if ($user->isStudent() && $submission->student_id !== $user->student->id) {
            abort(403, 'No tienes acceso a este archivo.');
        }

        if (! $submission->file_path || ! Storage::exists($submission->file_path)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::download($submission->file_path, basename($submission->file_path));
    }

    public function feedback(Request $request, AcademicActivity $activity, ActivitySubmission $submission): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isTeacher()) {
            abort(403, 'No puedes corregir esta entrega.');
        }

        $activity->loadMissing('assignment');
        $this->authorizeActivity($request, $activity);

        if ($submission->academic_activity_id !== $activity->id) {
            abort(404, 'Entrega no encontrada.');
        }

        $validated = $request->validate([
            'teacher_feedback' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $submission->forceFill([
            'teacher_feedback' => $validated['teacher_feedback'],
            'reviewed_at' => now(),
        ])->save();

        $submission->load(['student.user']);

        return response()->json([
            'message' => 'Correccion guardada exitosamente.',
            'submission' => $submission,
        ], 200);
    }

    private function authorizeActivity(Request $request, AcademicActivity $activity): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isTeacher() && $activity->assignment->teacher_id === $user->teacher->id) {
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
}
