<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GradeAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GradeAuditController — Consulta de la bitácora de cambios de notas.
 *
 * Permisos:
 *   - admin: acceso total al historial
 *   - teacher: historial filtrado por sus asignaciones
 *   - student: sin acceso
 *
 * Endpoints:
 *   GET /api/audit-log                      → Historial completo (admin)
 *   GET /api/audit-log/student/{studentId}  → Historial de un estudiante
 *   GET /api/audit-log/course/{courseId}    → Historial de un curso (admin, teacher)
 */
class GradeAuditController extends Controller
{
    /**
     * Lista el historial de auditoría con filtros opcionales.
     *
     * Query params:
     *   ?course_id, ?period_id, ?student_id, ?action, ?from, ?to
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = GradeAuditLog::with(['teacher.user', 'student.user', 'course', 'subject', 'period'])
            ->orderBy('created_at', 'desc');

        // Docente: solo ve registros de sus cursos
        if ($user->isTeacher()) {
            $assignments = $user->teacher->assignments()->get(['course_id', 'subject_id']);
            $query->where(function ($q) use ($assignments) {
                foreach ($assignments as $a) {
                    $q->orWhere(function ($inner) use ($a) {
                        $inner->where('course_id', $a->course_id)
                              ->where('subject_id', $a->subject_id);
                    });
                }
            });
        }

        // Filtros adicionales
        $query->when($request->course_id,  fn($q) => $q->where('course_id', $request->course_id))
              ->when($request->period_id,  fn($q) => $q->where('period_id', $request->period_id))
              ->when($request->student_id, fn($q) => $q->where('student_id', $request->student_id))
              ->when($request->action,     fn($q) => $q->where('action', $request->action))
              ->when($request->from,       fn($q) => $q->where('created_at', '>=', $request->from))
              ->when($request->to,         fn($q) => $q->where('created_at', '<=', $request->to));

        $logs = $query->paginate($request->per_page ?? 30);

        return response()->json($logs, 200);
    }

    /**
     * Historial de cambios de notas de un estudiante específico.
     *
     * Admin y teacher pueden consultar cualquier estudiante.
     */
    public function byStudent(Request $request, int $studentId): JsonResponse
    {
        $user  = $request->user();
        $query = GradeAuditLog::with(['teacher.user', 'course', 'subject', 'period'])
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'desc');

        // Docente: solo su scope de asignaciones
        if ($user->isTeacher()) {
            $assignments = $user->teacher->assignments()->get(['course_id', 'subject_id']);
            $query->where(function ($q) use ($assignments) {
                foreach ($assignments as $a) {
                    $q->orWhere(function ($inner) use ($a) {
                        $inner->where('course_id', $a->course_id)
                              ->where('subject_id', $a->subject_id);
                    });
                }
            });
        }

        $logs = $query->paginate(30);

        return response()->json($logs, 200);
    }

    /**
     * Historial de cambios de notas de un curso completo.
     */
    public function byCourse(Request $request, int $courseId): JsonResponse
    {
        $user  = $request->user();
        $query = GradeAuditLog::with(['teacher.user', 'student.user', 'subject', 'period'])
            ->where('course_id', $courseId)
            ->when($request->period_id, fn($q) => $q->where('period_id', $request->period_id))
            ->orderBy('created_at', 'desc');

        // Docente: solo sus asignaturas en ese curso
        if ($user->isTeacher()) {
            $subjectIds = $user->teacher->assignments()
                ->where('course_id', $courseId)
                ->pluck('subject_id');

            if ($subjectIds->isEmpty()) {
                return response()->json(['message' => 'No tienes asignaciones en este curso.'], 403);
            }

            $query->whereIn('subject_id', $subjectIds);
        }

        $logs = $query->paginate(30);

        return response()->json($logs, 200);
    }
}
